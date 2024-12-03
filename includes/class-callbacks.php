<?php
class WP_Donation_System_Callbacks {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        // M-Pesa callback endpoint
        register_rest_route('wp-donation-system/v1', '/mpesa-callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_mpesa_callback'),
            'permission_callback' => '__return_true'
        ));
    }

    public function handle_mpesa_callback($request) {
        $this->logger->log('M-Pesa callback received', 'debug', [
            'request' => $request->get_params()
        ]);

        $body = $request->get_json_params();
        
        if (empty($body)) {
            $this->logger->log('Empty callback body received', 'error');
            return new WP_Error('invalid_callback', 'Invalid callback data', ['status' => 400]);
        }

        try {
            // Extract relevant data
            $result_code = $body['Body']['stkCallback']['ResultCode'] ?? null;
            $result_desc = $body['Body']['stkCallback']['ResultDesc'] ?? '';
            $merchant_request_id = $body['Body']['stkCallback']['MerchantRequestID'] ?? '';
            $checkout_request_id = $body['Body']['stkCallback']['CheckoutRequestID'] ?? '';

            $this->logger->log('Processing M-Pesa callback', 'info', [
                'result_code' => $result_code,
                'result_desc' => $result_desc,
                'merchant_request_id' => $merchant_request_id,
                'checkout_request_id' => $checkout_request_id
            ]);

            // Find the donation by checkout request ID
            global $wpdb;
            $donation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}donations WHERE checkout_request_id = %s",
                $checkout_request_id
            ));

            if (!$donation) {
                throw new Exception('Donation not found for checkout request ID: ' . $checkout_request_id);
            }

            if ($result_code === '0') {
                // Payment successful
                $this->database->update_donation($donation->id, [
                    'status' => 'completed',
                    'transaction_id' => $body['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'] ?? '',
                    'notes' => $result_desc
                ]);
            } else {
                // Payment failed
                $this->database->update_donation($donation->id, [
                    'status' => 'failed',
                    'notes' => $result_desc
                ]);
            }

            return new WP_REST_Response(['ResultCode' => 0, 'ResultDesc' => 'Success'], 200);

        } catch (Exception $e) {
            $this->logger->log('Callback processing failed', 'error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return new WP_Error(
                'callback_processing_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    private function verify_mpesa_callback($params) {
        $mpesa = new WP_Donation_System_MPesa();
        return $mpesa->verify_callback($params);
    }
} 