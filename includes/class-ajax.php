<?php

class WP_Donation_System_Ajax {
    private $settings;
    private $logger;

    public function __construct() {
        $this->settings = get_option('wp_donation_system_settings', array());
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        $this->logger = new WP_Donation_System_Logger();
    }

    public function init() {
        add_action('wp_ajax_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_process_donation', array($this, 'process_donation'));
        
        add_action('wp_ajax_check_donation_status', array($this, 'check_donation_status'));
        add_action('wp_ajax_nopriv_check_donation_status', array($this, 'check_donation_status'));
    }

    /**
     * Process donation submission
     */
    public function process_donation() {
        try {
            // Initialize logger with more context
            $log_context = [
                'source' => 'ajax_process_donation',
                'ip' => $_SERVER['REMOTE_ADDR'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'request_time' => current_time('mysql'),
                'post_data' => $this->sanitize_log_data($_POST)
            ];

            $this->logger->log('Starting donation processing', 'info', $log_context);

            // Verify nonce
            if (!check_ajax_referer('process_donation', 'security', false)) {
                $this->logger->log('Nonce verification failed', 'error', $log_context);
                throw new Exception(__('Security check failed', 'wp-donation-system'));
            }

            // Handle anonymous donation
            $is_anonymous = isset($_POST['anonymous_donation']) && $_POST['anonymous_donation'] === 'on';
            
            // Sanitize and prepare donation data
            $donation_data = [
                'donor_name' => $is_anonymous ? 'Anonymous Guest' : sanitize_text_field($_POST['donor_name']),
                'donor_email' => $is_anonymous ? 'anonymous@' . parse_url(home_url(), PHP_URL_HOST) : sanitize_email($_POST['donor_email']),
                'donor_phone' => sanitize_text_field($_POST['donor_phone']),
                'amount' => (int)ceil(floatval($_POST['donation_amount'])),
                'payment_method' => sanitize_text_field($_POST['payment_method']),
                'is_anonymous' => $is_anonymous,
            ];

            // Update log context with sanitized donation data
            $log_context['donation_data'] = $donation_data;
            $this->logger->log('Donation data prepared', 'debug', $log_context);

            // Validate amount
            if ($donation_data['amount'] < 1) {
                $this->logger->log('Invalid amount validation failed', 'error', [
                    ...$log_context,
                    'invalid_amount' => $donation_data['amount']
                ]);
                throw new Exception(__('Donation amount must be at least 1', 'wp-donation-system'));
            }

            // Start database transaction
            global $wpdb;
            $wpdb->query('START TRANSACTION');

            try {
                // Create donation record
                $this->logger->log('Creating donation record', 'debug', $log_context);
                
                $donation = WP_Donation_System_Donation::create([
                    'donor_name' => $donation_data['donor_name'],
                    'donor_email' => $donation_data['donor_email'],
                    'donor_phone' => $donation_data['donor_phone'],
                    'amount' => $donation_data['amount'],
                    'currency' => $this->settings['default_currency'] ?? 'KES',
                    'payment_method' => $donation_data['payment_method'],
                    'status' => 'pending',
                    'is_anonymous' => $donation_data['is_anonymous'] ? 1 : 0
                ]);

                if (!$donation) {
                    throw new Exception(__('Failed to create donation record', 'wp-donation-system'));
                }

                $donation_data['donation_id'] = $donation->id;
                $log_context['donation_id'] = $donation->id;
                
                $this->logger->log('Donation record created', 'info', [
                    ...$log_context,
                    'donation_id' => $donation->id
                ]);

                // Process payment based on method
                $this->logger->log('Processing payment', 'debug', [
                    ...$log_context,
                    'payment_method' => $donation_data['payment_method']
                ]);

                switch ($donation_data['payment_method']) {
                    case 'mpesa':
                        $gateway = new WP_Donation_System_MPesa();
                        $result = $gateway->process_payment($donation_data);
                        break;

                    default:
                        $this->logger->log('Invalid payment method', 'error', [
                            ...$log_context,
                            'payment_method' => $donation_data['payment_method']
                        ]);
                        throw new Exception(__('Invalid payment method', 'wp-donation-system'));
                }

                $wpdb->query('COMMIT');
                
                $this->logger->log('Donation processed successfully', 'info', [
                    ...$log_context,
                    'result' => $result,
                    'processing_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]
                ]);

                wp_send_json_success([
                    'status' => 'pending',
                    'gateway' => $donation_data['payment_method'],
                    'donation_id' => $donation->id,
                    'checkout_request_id' => $result['checkout_request_id'] ?? null,
                    'message' => $result['message'] ?? __('Processing payment...', 'wp-donation-system')
                ]);

            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                throw $e;
            }

        } catch (Exception $e) {
            $this->logger->log('Donation processing failed', 'error', [
                ...$log_context,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'processing_time' => microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]
            ]);
            
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sanitize sensitive data for logging
     */
    private function sanitize_log_data($data) {
        $sanitized = [];
        foreach ($data as $key => $value) {
            // Skip sensitive fields
            if (in_array($key, ['security', 'password', 'card_number'])) {
                continue;
            }
            // Mask phone numbers
            if (strpos($key, 'phone') !== false) {
                $sanitized[$key] = $this->mask_phone_number($value);
                continue;
            }
            // Mask email addresses
            if (strpos($key, 'email') !== false) {
                $sanitized[$key] = $this->mask_email($value);
                continue;
            }
            $sanitized[$key] = $value;
        }
        return $sanitized;
    }

    /**
     * Mask phone number for logging
     */
    private function mask_phone_number($phone) {
        if (strlen($phone) < 8) return $phone;
        return substr($phone, 0, 4) . str_repeat('*', strlen($phone) - 7) . substr($phone, -3);
    }

    /**
     * Mask email address for logging
     */
    private function mask_email($email) {
        if (!strpos($email, '@')) return $email;
        list($name, $domain) = explode('@', $email);
        $masked_name = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
        return $masked_name . '@' . $domain;
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
        $checkout_request_id = sanitize_text_field($_POST['checkout_request_id']);
        
        $donation = WP_Donation_System_Donation::query()
            ->where('checkout_request_id', $checkout_request_id)
            ->first();

        if (!$donation) {
            wp_send_json_success(['status' => 'pending']);
        }

        $metadata = $donation->getMetadataArray();
        // ... rest of the handler
    }

    public function check_payment_status() {
        $log_context = [
            'source' => 'ajax_check_payment_status',
            'donation_id' => $_POST['donation_id'] ?? null,
            'checkout_request_id' => $_POST['checkout_request_id'] ?? null
        ];

        try {
            $this->logger->log('Checking payment status', 'info', $log_context);
            
            check_ajax_referer('wp_donation_system_donation', 'security');

            $donation_id = intval($_POST['donation_id']);
            $checkout_request_id = sanitize_text_field($_POST['checkout_request_id']);

            $transaction = WP_Donation_System_MPesa_Transaction::query()
                ->where('donation_id', $donation_id)
                ->where('checkout_request_id', $checkout_request_id)
                ->first();

            if (!$transaction) {
                $this->logger->log('Transaction not found', 'error', $log_context);
                throw new Exception(__('Transaction not found', 'wp-donation-system'));
            }

            $donation = $transaction->donation();
            $log_context['status'] = $transaction->request_status;
            $log_context['donation_status'] = $donation->status;

            $this->logger->log('Payment status checked', 'info', $log_context);

            wp_send_json_success([
                'status' => $transaction->request_status,
                'donation_status' => $donation->status,
                'message' => $this->get_status_message($transaction->request_status)
            ]);

        } catch (Exception $e) {
            $log_context['error'] = $e->getMessage();
            $this->logger->log('Payment status check failed', 'error', $log_context);
            
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    private function get_status_message($status) {
        $messages = [
            'pending' => __('Payment is being processed. Please wait...', 'wp-donation-system'),
            'completed' => __('Payment completed successfully. Thank you!', 'wp-donation-system'),
            'failed' => __('Payment failed. Please try again.', 'wp-donation-system')
        ];
        return $messages[$status] ?? $messages['pending'];
    }
}