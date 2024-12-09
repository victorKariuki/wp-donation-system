<?php

class WP_Donation_System_Ajax {
    private $db;
    private $mpesa;
    private $logger;

    public function __construct() {
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-database.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/gateways/class-mpesa.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        
        $this->db = new WP_Donation_System_Database();
        $this->mpesa = new WP_Donation_System_Mpesa();
        $this->logger = new WP_Donation_System_Logger();
    }

    public function process_donation() {
        try {
            // Verify nonce
            if (!check_ajax_referer('process_donation', 'security', false)) {
                throw new Exception('Invalid security token');
            }

            // Validate required fields
            $required_fields = ['donation_amount', 'donor_name', 'donor_email', 'payment_method'];
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Required field missing: {$field}");
                }
            }

            // Sanitize and validate input data
            $donation_data = [
                'donor_name' => sanitize_text_field($_POST['donor_name']),
                'donor_email' => sanitize_email($_POST['donor_email']),
                'amount' => floatval($_POST['donation_amount']),
                'payment_method' => sanitize_text_field($_POST['payment_method']),
                'status' => 'pending',
                'metadata' => json_encode($_POST)
            ];

            // Add phone number for M-Pesa payments
            if ($donation_data['payment_method'] === 'mpesa') {
                if (empty($_POST['phone_number'])) {
                    throw new Exception('Phone number is required for M-Pesa payments');
                }
                $donation_data['donor_phone'] = sanitize_text_field($_POST['phone_number']);
            }

            // Log the incoming request
            $this->logger->log('Processing donation request', 'info', [
                'donation_data' => $donation_data,
                'raw_post' => $_POST
            ]);

            // Insert donation record
            $donation_id = $this->db->insert_donation($donation_data);
            if (!$donation_id) {
                throw new Exception('Failed to create donation record');
            }

            // Process payment based on method
            switch ($donation_data['payment_method']) {
                case 'mpesa':
                    $response = $this->mpesa->initiate_payment([
                        'phone' => $donation_data['donor_phone'],
                        'amount' => $donation_data['amount'],
                        'donation_id' => $donation_id
                    ]);

                    if (is_wp_error($response)) {
                        throw new Exception($response->get_error_message());
                    }

                    // Update donation record with M-Pesa request IDs
                    $this->db->update_donation($donation_id, [
                        'checkout_request_id' => $response['CheckoutRequestID'],
                        'merchant_request_id' => $response['MerchantRequestID']
                    ]);

                    wp_send_json_success([
                        'message' => 'M-Pesa payment initiated. Please check your phone to complete the payment.',
                        'donation_id' => $donation_id,
                        'checkout_request_id' => $response['CheckoutRequestID']
                    ]);
                    break;

                // Add other payment methods here
                default:
                    throw new Exception('Unsupported payment method');
            }

        } catch (Exception $e) {
            $this->logger->log('Donation processing failed', 'error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'post_data' => $_POST
            ]);

            wp_send_json_error([
                'message' => 'Failed to process donation. Please try again.',
                'error' => $e->getMessage()
            ]);
        }
    }
}