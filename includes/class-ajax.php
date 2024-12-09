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
        try {
            // Enable error reporting for debugging
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            
            // Log the raw POST data
            $this->logger->log('Raw donation data received', 'debug', $_POST);

            // Verify nonce
            if (!check_ajax_referer('wp_donation_system', 'security', false)) {
                throw new Exception(__('Security check failed', 'wp-donation-system'));
            }

            // Validate required fields
            $required_fields = ['donor_name', 'donor_email', 'donation_amount', 'payment_method'];
            $donation_data = [];
            
            foreach ($required_fields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception(sprintf(__('Missing required field: %s', 'wp-donation-system'), $field));
                }
                $donation_data[$field] = $_POST[$field];
            }

            // Prepare donation data
            $donation_data = [
                'donor_name' => sanitize_text_field($_POST['donor_name']),
                'donor_email' => sanitize_email($_POST['donor_email']),
                'amount' => floatval($_POST['donation_amount']),
                'payment_method' => sanitize_text_field($_POST['payment_method']),
                'donor_phone' => isset($_POST['donor_phone']) ? sanitize_text_field($_POST['donor_phone']) : '',
                'status' => 'pending',
                'currency' => 'KES',
                'metadata' => wp_json_encode([
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'ip_address' => $this->get_client_ip(),
                    'anonymous' => !empty($_POST['anonymous_donation'])
                ])
            ];

            // Log prepared donation data
            $this->logger->log('Prepared donation data', 'debug', $donation_data);

            // Insert donation record
            $donation_id = $this->database->insert_donation($donation_data);
            if (!$donation_id) {
                throw new Exception(__('Failed to save donation record', 'wp-donation-system'));
            }

            // Add donation ID to data
            $donation_data['donation_id'] = $donation_id;

            // Get gateway instance
            $gateway_manager = WP_Donation_System_Gateway_Manager::get_instance();
            $gateway = $gateway_manager->get_gateway($donation_data['payment_method']);
            
            if (!$gateway || !$gateway->is_enabled()) {
                throw new Exception(sprintf(
                    __('%s gateway is not available', 'wp-donation-system'),
                    ucfirst($donation_data['payment_method'])
                ));
            }

            // Process payment through gateway
            $this->logger->log('Processing payment through gateway', 'info', [
                'gateway' => $donation_data['payment_method'],
                'donation_id' => $donation_id
            ]);

            $result = $gateway->process_payment($donation_data);

            // Update donation with gateway response
            $update_data = ['status' => 'processing'];
            if (isset($result['checkout_request_id'])) {
                $update_data['checkout_request_id'] = $result['checkout_request_id'];
            }
            $this->database->update_donation($donation_id, $update_data);

            // Log success
            $this->logger->log('Payment processing successful', 'info', [
                'donation_id' => $donation_id,
                'result' => $result
            ]);

            // Return success response
            wp_send_json_success(array_merge($result, [
                'donation_id' => $donation_id,
                'gateway' => $donation_data['payment_method']
            ]));

        } catch (Exception $e) {
            // Log error
            $this->logger->log('Payment processing error', 'error', [
                'error_message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'POST_data' => $_POST
            ]);

            // Return error response
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