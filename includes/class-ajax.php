<?php

class WP_Donation_System_Ajax {
    private $settings;
    private $database;
    private $logger;

    public function __construct() {
        $this->settings = get_option('wp_donation_system_settings', array());
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-database.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        $this->database = new WP_Donation_System_Database();
        $this->logger = new WP_Donation_System_Logger();
    }

    /**
     * Process donation submission
     */
    public function process_donation() {
        header('Content-Type: application/json');

        try {
            // Enable error reporting for debugging
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            
            // Log the raw POST data
            $this->logger->log('Raw POST data received', 'debug', [
                'POST' => $_POST,
                'FILES' => $_FILES
            ]);

            // Verify nonce first
            if (!check_ajax_referer('wp_donation_system', 'security', false)) {
                throw new Exception('Security check failed');
            }

            // Validate required fields
            $required_fields = ['donor_name', 'donor_email', 'donation_amount', 'payment_method'];
            $donation_data = [];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Missing required field: {$field}");
                }
                $donation_data[$field] = $_POST[$field];
            }

            // Prepare donation data
            $donation_data = [
                'donor_name' => sanitize_text_field($_POST['donor_name']),
                'donor_email' => sanitize_email($_POST['donor_email']),
                'amount' => floatval($_POST['donation_amount']),
                'payment_method' => sanitize_text_field($_POST['payment_method']),
                'donor_phone' => isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '',
                'status' => 'pending',
                'currency' => 'KES',
                'metadata' => wp_json_encode([
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'ip_address' => $this->get_client_ip()
                ])
            ];

            // Log the prepared donation data
            $this->logger->log('Prepared donation data', 'debug', $donation_data);

            // Insert donation
            $donation_id = $this->database->insert_donation($donation_data);

            if (!$donation_id) {
                throw new Exception('Failed to save donation');
            }

            // Log successful insertion
            $this->logger->log('Donation saved successfully', 'info', [
                'donation_id' => $donation_id
            ]);

            // Process payment based on method
            switch ($donation_data['payment_method']) {
                case 'mpesa':
                    $this->logger->log('Initiating M-Pesa STK push', 'info');
                    
                    // Load M-Pesa gateway
                    require_once WP_DONATION_SYSTEM_PATH . 'includes/gateways/class-mpesa.php';
                    $mpesa = new WP_Donation_System_MPesa();
                    
                    try {
                        // Prepare payment data
                        $payment_data = [
                            'phone_number' => $donation_data['donor_phone'],
                            'amount' => $donation_data['amount'],
                            'donation_id' => $donation_id
                        ];
                        
                        $this->logger->log('Processing M-Pesa payment', 'debug', [
                            'payment_data' => $payment_data
                        ]);
                        
                        // Initiate STK push
                        $stk_response = $mpesa->initiate_stk_push($payment_data);
                        
                        if (!$stk_response->success) {
                            throw new Exception($stk_response->message);
                        }
                        
                        // Ensure we have the required response data
                        if (empty($stk_response->checkout_request_id)) {
                            throw new Exception('Invalid M-Pesa response: Missing checkout request ID');
                        }
                        
                        // Update donation with checkout request ID
                        $update_result = $this->database->update_donation($donation_id, [
                            'checkout_request_id' => $stk_response->checkout_request_id,
                            'status' => 'processing'
                        ]);

                        if (!$update_result) {
                            throw new Exception('Failed to update donation with checkout request ID');
                        }
                        
                        $this->logger->log('M-Pesa STK push successful', 'info', [
                            'checkout_request_id' => $stk_response->checkout_request_id,
                            'donation_id' => $donation_id
                        ]);
                        
                        wp_send_json_success([
                            'message' => __('Please check your phone to complete the payment.', 'wp-donation-system'),
                            'donation_id' => $donation_id,
                            'payment_method' => 'mpesa'
                        ]);
                        
                    } catch (Exception $e) {
                        $this->logger->log('M-Pesa payment failed', 'error', [
                            'error' => $e->getMessage(),
                            'donation_id' => $donation_id
                        ]);
                        
                        // Update donation status to failed
                        $this->database->update_donation($donation_id, [
                            'status' => 'failed',
                            'notes' => $e->getMessage()
                        ]);
                        
                        throw new Exception(__('Failed to initiate M-Pesa payment. Please try again.', 'wp-donation-system'));
                    }
                    break;

                default:
                    throw new Exception(__('Invalid payment method', 'wp-donation-system'));
            }

        } catch (Exception $e) {
            $this->logger->log('Donation processing error', 'error', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'POST_data' => $_POST
            ]);

            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }

    /**
     * Check donation status
     */
    public function check_donation_status() {
        // Set JSON header
        header('Content-Type: application/json');

        try {
            if (!check_ajax_referer('wp_donation_system', 'security', false)) {
                throw new Exception(__('Security check failed', 'wp-donation-system'));
            }

            $donation_id = isset($_POST['donation_id']) ? intval($_POST['donation_id']) : 0;
            if (!$donation_id) {
                throw new Exception(__('Invalid donation ID', 'wp-donation-system'));
            }

            $donation = $this->database->get_donation($donation_id);
            if (!$donation) {
                throw new Exception(__('Donation not found', 'wp-donation-system'));
            }

            echo wp_json_encode(array(
                'success' => true,
                'data' => array(
                    'status' => $donation->status,
                    'message' => $donation->status === 'failed' ? 
                        __('Payment failed. Please try again.', 'wp-donation-system') : '',
                    'redirect_url' => $donation->status === 'completed' ? 
                        add_query_arg('donation', $donation_id, get_page_link($this->settings['success_page'])) : ''
                )
            ));
            exit;

        } catch (Exception $e) {
            error_log('Donation Status Check Error: ' . $e->getMessage());
            echo wp_json_encode(array(
                'success' => false,
                'data' => array(
                    'message' => $e->getMessage()
                )
            ));
            exit;
        }
    }
}