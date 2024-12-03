<?php
class WP_Donation_System_MPesa {
    private $consumer_key;
    private $consumer_secret;
    private $business_shortcode;
    private $passkey;
    private $is_sandbox;
    private $access_token;

    public function __construct() {
        add_action('wp_ajax_process_mpesa_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_process_mpesa_payment', array($this, 'process_payment'));
        add_action('wp_ajax_check_mpesa_status', array($this, 'check_status'));
        add_action('wp_ajax_nopriv_check_mpesa_status', array($this, 'check_status'));

        $is_test_mode = get_option('wp_donation_test_mode', false);
        
        if ($is_test_mode) {
            $this->consumer_key = get_option('wp_donation_mpesa_test_consumer_key');
            $this->consumer_secret = get_option('wp_donation_mpesa_test_consumer_secret');
            $this->business_shortcode = get_option('wp_donation_mpesa_test_shortcode');
            $this->is_sandbox = true;
        } else {
            $this->consumer_key = get_option('wp_donation_mpesa_consumer_key');
            $this->consumer_secret = get_option('wp_donation_mpesa_consumer_secret');
            $this->business_shortcode = get_option('wp_donation_mpesa_shortcode');
            $this->is_sandbox = false;
        }
        
        $this->passkey = get_option('wp_donation_mpesa_passkey');

        // Validate required credentials
        if (!$this->consumer_key || !$this->consumer_secret || !$this->business_shortcode || !$this->passkey) {
            $this->log_error('M-Pesa configuration error: Missing required credentials');
        }
    }

    private function get_access_token() {
        if ($this->access_token) {
            return $this->access_token;
        }

        if (!$this->consumer_key || !$this->consumer_secret) {
            $this->log_error('M-Pesa authentication error: Missing consumer key or secret');
            return false;
        }

        $url = $this->is_sandbox
            ? 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
            : 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);

        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . $credentials
            ),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            $this->log_error('M-Pesa access token error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $this->log_error('M-Pesa access token error: Unexpected status code ' . $status_code);
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($body) || !isset($body['access_token'])) {
            $this->log_error('M-Pesa access token error: Invalid response format');
            return false;
        }

        $this->access_token = $body['access_token'];
        return $this->access_token;
    }

    private function initiate_stk_push($form_data) {
        if (empty($form_data['amount']) || empty($form_data['phone_number'])) {
            $this->log_error('M-Pesa STK push error: Missing required form data');
            return false;
        }

        $access_token = $this->get_access_token();
        if (!$access_token) {
            return false;
        }

        $timestamp = date('YmdHis');
        $password = base64_encode($this->business_shortcode . $this->passkey . $timestamp);

        $url = $this->is_sandbox
            ? 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest';

        $amount = floatval($form_data['amount']);
        $phone = sanitize_text_field($form_data['phone_number']);
        
        // Convert to KES if needed
        if ($form_data['currency'] !== 'KES') {
            $amount = $this->convert_to_kes($amount, $form_data['currency']);
        }
        
        $payload = array(
            'BusinessShortCode' => $this->business_shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => ceil($amount),
            'PartyA' => $phone,
            'PartyB' => $this->business_shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => rest_url('wp-donation-system/v1/mpesa-callback'),
            'AccountReference' => 'Donation',
            'TransactionDesc' => 'Donation to ' . get_bloginfo('name')
        );

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($payload),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            $this->log_error('M-Pesa STK push error: ' . $response->get_error_message());
            return false;
        }

        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            $this->log_error('M-Pesa STK push error: Unexpected status code ' . $status_code);
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($body) || !isset($body['CheckoutRequestID'])) {
            $this->log_error('M-Pesa STK push error: Invalid response format');
            return false;
        }

        try {
            $donation_id = $this->save_donation($form_data, $body['CheckoutRequestID']);
            if (!$donation_id) {
                throw new Exception('Failed to save donation');
            }
            
            return array(
                'CheckoutRequestID' => $body['CheckoutRequestID'],
                'donation_id' => $donation_id
            );
        } catch (Exception $e) {
            $this->log_error('M-Pesa donation save error: ' . $e->getMessage());
            return false;
        }
    }

    private function check_transaction_status($checkout_request_id) {
        if (empty($checkout_request_id)) {
            $this->log_error('M-Pesa status check error: Missing checkout request ID');
            return 'failed';
        }

        $donation = $this->get_donation_by_transaction($checkout_request_id);
        if (!$donation) {
            $this->log_error('M-Pesa status check error: Donation not found for ID ' . $checkout_request_id);
            return 'failed';
        }

        return $donation->status;
    }

    private function save_donation($form_data, $checkout_request_id) {
        global $wpdb;
        
        try {
            if (empty($form_data['donor_name']) || empty($form_data['donor_email'])) {
                throw new Exception('Missing required donor information');
            }

            $data = array(
                'donor_name' => sanitize_text_field($form_data['donor_name']),
                'donor_email' => sanitize_email($form_data['donor_email']),
                'amount' => floatval($form_data['amount']),
                'currency' => 'KES',
                'payment_method' => 'mpesa',
                'transaction_id' => $checkout_request_id,
                'status' => 'pending',
                'created_at' => current_time('mysql')
            );

            $result = $wpdb->insert($wpdb->prefix . 'donations', $data);
            if ($result === false) {
                throw new Exception('Database insert failed: ' . $wpdb->last_error);
            }

            return $wpdb->insert_id;
        } catch (Exception $e) {
            $this->log_error('Donation save error: ' . $e->getMessage());
            return false;
        }
    }

    private function get_donation_by_transaction($transaction_id) {
        global $wpdb;
        
        try {
            $donation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}donations WHERE transaction_id = %s",
                $transaction_id
            ));

            if ($wpdb->last_error) {
                throw new Exception('Database query failed: ' . $wpdb->last_error);
            }

            return $donation;
        } catch (Exception $e) {
            $this->log_error('Donation retrieval error: ' . $e->getMessage());
            return false;
        }
    }

    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WP Donation System M-Pesa] ' . $message);
        }
    }

    public function process_payment() {
        try {
            check_ajax_referer('wp_donation_system_nonce', 'nonce');
            
            $rate_limiter = new WP_Donation_System_Rate_Limiter();
            if (!$rate_limiter->check_rate_limit($_SERVER['REMOTE_ADDR'])) {
                throw new Exception(__('Too many attempts. Please try again later.', 'wp-donation-system'));
            }
            
        } catch (Exception $e) {
            $this->log_error('Payment processing error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    public function verify_callback($params) {
        // Verify required parameters
        $required_params = array(
            'CheckoutRequestID',
            'ResultCode',
            'TransactionID'
        );

        foreach ($required_params as $param) {
            if (!isset($params[$param])) {
                $this->log_error('Missing required M-Pesa callback parameter: ' . $param);
                return false;
            }
        }

        // Verify callback using M-Pesa API if needed
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return false;
        }

        $url = $this->is_sandbox
            ? 'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query'
            : 'https://api.safaricom.co.ke/mpesa/stkpushquery/v1/query';

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode(array(
                'CheckoutRequestID' => $params['CheckoutRequestID']
            ))
        ));

        if (is_wp_error($response)) {
            $this->log_error('M-Pesa callback verification failed: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['ResultCode']) && $body['ResultCode'] === '0';
    }
}
