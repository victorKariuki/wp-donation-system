<?php
class WP_Donation_System_MPesa {
    private $settings;
    private $logger;
    private $environment;
    private $consumer_key;
    private $consumer_secret;
    private $business_shortcode;
    private $passkey;
    private $callback_url;

    public function __construct() {
        $this->settings = get_option('wp_donation_system_settings', array());
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        $this->logger = new WP_Donation_System_Logger();
        
        // Initialize settings
        $this->environment = $this->get_setting('mpesa_env', 'sandbox');
        $this->consumer_key = $this->get_setting('mpesa_consumer_key', '');
        $this->consumer_secret = $this->get_setting('mpesa_consumer_secret', '');
        $this->business_shortcode = $this->get_setting('mpesa_shortcode', '');
        $this->passkey = $this->get_setting('mpesa_passkey', '');
        
        // Set callback URL - ensure it's a full, valid URL
        $callback_base = site_url('/wp-json/wp-donation-system/v1/pesa-callback');
        $this->callback_url = esc_url_raw($callback_base);

        // Log initialization with callback URL validation
        $this->logger->log('M-Pesa Gateway Initialized', 'debug', [
            'environment' => $this->environment,
            'business_shortcode' => $this->business_shortcode,
            'callback_url' => $this->callback_url,
            'callback_url_valid' => filter_var($this->callback_url, FILTER_VALIDATE_URL) !== false
        ]);
    }

    /**
     * Initiate STK Push
     */
    public function initiate_stk_push($payment_data) {
        try {
            // Log the start of STK push process
            $this->logger->log('Starting M-Pesa STK Push', 'info', [
                'payment_data' => $payment_data,
                'environment' => $this->environment
            ]);

            // Validate required data
            if (empty($payment_data['phone_number']) || empty($payment_data['amount'])) {
                $this->logger->log('Missing required payment data', 'error', [
                    'provided_data' => $payment_data
                ]);
                throw new Exception('Missing required payment data');
            }

            // Validate callback URL
            if (!filter_var($this->callback_url, FILTER_VALIDATE_URL)) {
                $this->logger->log('Invalid callback URL', 'error', [
                    'callback_url' => $this->callback_url
                ]);
                throw new Exception('Invalid callback URL configuration');
            }

            // Get access token
            $this->logger->log('Requesting M-Pesa access token', 'debug');
            $access_token = $this->get_access_token();
            
            if (!$access_token) {
                $this->logger->log('Failed to get M-Pesa access token', 'error');
                throw new Exception('Failed to get access token');
            }

            // Prepare STK Push request
            $timestamp = date('YmdHis');
            $password = base64_encode($this->business_shortcode . $this->passkey . $timestamp);
            
            $stk_request_data = array(
                'BusinessShortCode' => $this->business_shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => ceil($payment_data['amount']),
                'PartyA' => $payment_data['phone_number'],
                'PartyB' => $this->business_shortcode,
                'PhoneNumber' => $payment_data['phone_number'],
                'CallBackURL' => $this->callback_url,
                'AccountReference' => $this->get_setting('mpesa_account_ref', 'DONATION'),
                'TransactionDesc' => $this->get_setting('mpesa_transaction_desc', 'Donation Payment')
            );

            // Log the STK push request with URL validation
            $this->logger->log('Sending M-Pesa STK Push request', 'debug', [
                'request_data' => $stk_request_data,
                'api_url' => $this->get_api_url('stkpush'),
                'callback_url_valid' => filter_var($this->callback_url, FILTER_VALIDATE_URL) !== false
            ]);

            // Make API request
            $response = wp_remote_post($this->get_api_url('stkpush'), array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ),
                'body' => wp_json_encode($stk_request_data)
            ));

            // Log the raw response
            $this->logger->log('Received M-Pesa STK Push response', 'debug', [
                'raw_response' => $response
            ]);

            if (is_wp_error($response)) {
                $this->logger->log('M-Pesa API request failed', 'error', [
                    'error' => $response->get_error_message()
                ]);
                throw new Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response));

            if (!$body) {
                $this->logger->log('Invalid M-Pesa response format', 'error', [
                    'raw_body' => wp_remote_retrieve_body($response)
                ]);
                throw new Exception('Invalid response from M-Pesa');
            }

            // Log the parsed response
            $this->logger->log('Parsed M-Pesa response', 'debug', [
                'response_body' => $body
            ]);

            // Check response code with proper error handling
            $responseCode = isset($body->ResponseCode) ? $body->ResponseCode : null;
            if ($responseCode !== '0') {
                $errorMessage = isset($body->errorMessage) ? $body->errorMessage : 
                    (isset($body->ResponseDescription) ? $body->ResponseDescription : 'STK Push failed');
                
                $this->logger->log('M-Pesa STK Push failed', 'error', [
                    'response_code' => $responseCode,
                    'error_message' => $errorMessage,
                    'full_response' => $body
                ]);
                
                throw new Exception($errorMessage);
            }

            // Log successful STK push
            $this->logger->log('M-Pesa STK Push successful', 'info', [
                'checkout_request_id' => $body->CheckoutRequestID ?? null,
                'merchant_request_id' => $body->MerchantRequestID ?? null,
                'response_code' => $responseCode,
                'response_description' => $body->ResponseDescription ?? ''
            ]);

            return (object) array(
                'success' => true,
                'checkout_request_id' => $body->CheckoutRequestID ?? null,
                'merchant_request_id' => $body->MerchantRequestID ?? null
            );

        } catch (Exception $e) {
            $this->logger->log('STK Push process failed', 'error', [
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'payment_data' => $payment_data
            ]);

            return (object) array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }

    /**
     * Get M-Pesa API access token
     */
    private function get_access_token() {
        $this->logger->log('Getting M-Pesa access token', 'debug', [
            'environment' => $this->environment
        ]);

        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        
        $response = wp_remote_get($this->get_api_url('oauth'), array(
            'headers' => array(
                'Authorization' => 'Basic ' . $credentials
            )
        ));

        if (is_wp_error($response)) {
            $this->logger->log('Access token request failed', 'error', [
                'error' => $response->get_error_message()
            ]);
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response));
        
        if (!isset($body->access_token)) {
            $this->logger->log('Invalid access token response', 'error', [
                'response' => $body
            ]);
            return false;
        }

        $this->logger->log('Successfully obtained access token', 'debug');
        return $body->access_token;
    }

    /**
     * Get API URL based on environment
     */
    private function get_api_url($endpoint) {
        $base_url = $this->environment === 'live' 
            ? 'https://api.safaricom.co.ke' 
            : 'https://sandbox.safaricom.co.ke';

        $url = '';
        switch ($endpoint) {
            case 'oauth':
                $url = $base_url . '/oauth/v1/generate?grant_type=client_credentials';
                break;
            case 'stkpush':
                $url = $base_url . '/mpesa/stkpush/v1/processrequest';
                break;
        }

        $this->logger->log('Generated API URL', 'debug', [
            'endpoint' => $endpoint,
            'environment' => $this->environment,
            'url' => $url
        ]);

        return $url;
    }

    /**
     * Get setting value with default
     */
    private function get_setting($key, $default = '') {
        $value = isset($this->settings[$key]) ? $this->settings[$key] : $default;
        
        $this->logger->log('Retrieved setting', 'debug', [
            'key' => $key,
            'value' => $value === $this->consumer_secret ? '[REDACTED]' : $value
        ]);
        
        return $value;
    }
} 