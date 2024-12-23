<?php
class WP_Donation_System_Callbacks {
    private $logger;
    private $database;
    private $transactions;

    public function __construct() {
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-database.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-mpesa-transaction.php';
        $this->logger = new WP_Donation_System_Logger();
        $this->database = new WP_Donation_System_Database();
        $this->transactions = new WP_Donation_System_MPesa_Transaction();
        
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        // Register payment callback endpoints
        register_rest_route('wp-donation-system/v1', '/payment/callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_payment_callback'),
            'permission_callback' => '__return_true'
        ));

        // Register payment timeout endpoints
        register_rest_route('wp-donation-system/v1', '/payment/timeout', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_payment_timeout'),
            'permission_callback' => '__return_true'
        ));

        register_rest_route('wp-donation-system/v1', '/payment/result', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_payment_result'),
            'permission_callback' => '__return_true'
        ));
    }

    public function handle_payment_callback($request) {
        $log_context = [
            'request_time' => current_time('mysql'),
            'request_id' => uniqid('callback_', true),
            'remote_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        $this->logger->log('Payment callback received', 'debug', [
            ...$log_context,
            'raw_data' => $request->get_body(),
            'headers' => $request->get_headers(),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
        ]);

        $body = $request->get_json_params();
        
        if (empty($body) || empty($body['Body']['stkCallback'])) {
            $log_context['error_type'] = 'invalid_callback';
            $this->logger->log('Invalid callback data received', 'error', [
                ...$log_context,
                'raw_body' => $request->get_body()
            ]);
            return new WP_REST_Response(['ResultCode' => 0], 200);
        }

        $callback_data = $body['Body']['stkCallback'];
        $result_code = $callback_data['ResultCode'];
        $result_desc = $callback_data['ResultDesc'];
        $merchant_request_id = $callback_data['MerchantRequestID'];
        $checkout_request_id = $callback_data['CheckoutRequestID'];

        $log_context['result_code'] = $result_code;
        $log_context['merchant_request_id'] = $merchant_request_id;
        $log_context['checkout_request_id'] = $checkout_request_id;
        $this->logger->log('Processing payment callback', 'info', [
            ...$log_context,
            'result_desc' => $result_desc
        ]);

        // Find the donation
        global $wpdb;
        $donation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}donations WHERE checkout_request_id = %s",
            $checkout_request_id
        ));

        if (!$donation) {
            $log_context['error_type'] = 'donation_not_found';
            $this->logger->log("Donation not found for checkout request ID: {$checkout_request_id}", 'error', [
                ...$log_context
            ]);
            return new WP_REST_Response(['ResultCode' => 0], 200);
        }

        $log_context['donation_id'] = $donation->id;

        if ($result_code === 0) {
            // Extract payment details from callback metadata items
            $metadata_items = $callback_data['CallbackMetadata']['Item'];
            $payment_details = [];
            
            foreach ($metadata_items as $item) {
                $payment_details[$item['Name']] = $item['Value'];
            }

            $log_context['payment_details'] = $payment_details;

            // Update donation as completed
            $update_data = [
                'status' => 'completed',
                'transaction_id' => $payment_details['MpesaReceiptNumber'],
                'notes' => $result_desc,
                'metadata' => wp_json_encode([
                    'amount' => $payment_details['Amount'],
                    'phone' => $payment_details['PhoneNumber'],
                    'transaction_date' => $payment_details['TransactionDate'],
                    'merchant_request_id' => $merchant_request_id
                ])
            ];

            $updated = $this->database->update_donation($donation->id, $update_data);
            
            if (!$updated) {
                $log_context['error_type'] = 'update_failed';
                $this->logger->log('Failed to update donation status', 'error', [
                    ...$log_context,
                    'update_data' => $update_data
                ]);
                return new WP_REST_Response(['ResultCode' => 0], 200);
            }

            // Send notifications
            require_once WP_DONATION_SYSTEM_PATH . 'includes/class-notifications.php';
            $notifications = new WP_Donation_System_Notifications();
            $notifications->send_payment_confirmation($donation->id);

            $this->logger->log('Payment completed successfully', 'info', [
                ...$log_context,
                'completion_time' => current_time('mysql')
            ]);

        } else {
            // Update donation as failed
            $update_data = [
                'status' => 'failed',
                'notes' => $result_desc
            ];

            $this->database->update_donation($donation->id, $update_data);

            $log_context['error_type'] = 'payment_failed';
            $this->logger->log('Payment failed', 'error', [
                ...$log_context,
                'failure_time' => current_time('mysql')
            ]);
        }

        // Update transaction record
        $this->transactions->update_from_callback(
            $checkout_request_id,
            $callback_data
        );

        return new WP_REST_Response([
            'ResultCode' => 0,
            'ResultDesc' => 'Callback processed successfully'
        ], 200);
    }
} 