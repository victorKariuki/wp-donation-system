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
    private $business_number;
    private $transactions;

    public function __construct() {
        $this->settings = get_option('wp_donation_system_settings', array());
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-mpesa-transaction.php';
        $this->logger = new WP_Donation_System_Logger();
        $this->transactions = new WP_Donation_System_MPesa_Transaction();
        
        // Initialize settings
        $this->environment = $this->get_setting('mpesa_env', 'sandbox');
        $this->consumer_key = $this->get_setting('mpesa_consumer_key', '');
        $this->consumer_secret = $this->get_setting('mpesa_consumer_secret', '');
        $this->business_shortcode = $this->get_setting('mpesa_shortcode', '');
        $this->passkey = $this->get_setting('mpesa_passkey', '');
        $this->business_number = $this->get_setting('mpesa_number', '');
        
        // Validate required credentials
        if (empty($this->consumer_key) || empty($this->consumer_secret)) {
            $this->logger->log('Missing M-Pesa credentials', 'error', [
                'environment' => $this->environment,
                'has_consumer_key' => !empty($this->consumer_key),
                'has_consumer_secret' => !empty($this->consumer_secret)
            ]);
        }
        
        // Set callback URL - ensure it's a full, valid URL
        $callback_base = site_url('/wp-json/wp-donation-system/v1/payment/callback');
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
            $log_context = [
                'payment_data' => $payment_data,
                'environment' => $this->environment,
                'shortcode' => $this->business_shortcode,
                'request_time' => current_time('mysql')
            ];
            
            // Validate credentials first
            if (empty($this->consumer_key) || empty($this->consumer_secret)) {
                $this->logger->log('M-Pesa credentials not configured', 'error', $log_context);
                throw new Exception('M-Pesa credentials not configured');
            }

            // Log the start of STK push process
            $this->logger->log('Starting M-Pesa STK Push', 'info', $log_context);

            // Validate required data
            if (empty($payment_data['phone_number']) || empty($payment_data['amount'])) {
                $log_context['validation_error'] = 'Missing required fields';
                $this->logger->log('Missing required payment data', 'error', [
                    ...$log_context,
                    'missing_fields' => array_filter([
                        'phone_number' => empty($payment_data['phone_number']),
                        'amount' => empty($payment_data['amount'])
                    ])
                ]);
                throw new Exception('Missing required payment data');
            }

            // Validate callback URL
            if (!filter_var($this->callback_url, FILTER_VALIDATE_URL)) {
                $log_context['validation_error'] = 'Invalid callback URL';
                $this->logger->log('Invalid callback URL', 'error', [
                    ...$log_context,
                    'callback_url' => $this->callback_url
                ]);
                throw new Exception('Invalid callback URL configuration');
            }

            // Get access token
            $this->logger->log('Requesting M-Pesa access token', 'debug', $log_context);
            $access_token = $this->get_access_token();
            
            if (!$access_token) {
                $log_context['error_type'] = 'access_token_failure';
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
                'TransactionType' => $this->get_setting('mpesa_type', 'till') === 'till' ? 'CustomerBuyGoodsOnline' : 'CustomerPayBillOnline',
                'Amount' => ceil($payment_data['amount']),
                'PartyA' => $payment_data['phone_number'],
                'PartyB' => $this->business_number,
                'PhoneNumber' => $payment_data['phone_number'],
                'CallBackURL' => $this->callback_url,
                'AccountReference' => $this->get_setting('mpesa_account_ref', 'DONATION').'-'.$payment_data['donation_id'],
                'TransactionDesc' => $this->get_setting('mpesa_transaction_desc', 'Donation Payment')
            );

            // Log the STK push request
            $log_context['request_data'] = $stk_request_data;
            $log_context['api_url'] = $this->get_api_url('stkpush');
            $this->logger->log('Sending M-Pesa STK Push request', 'debug', [
                ...$log_context,
                'request_id' => uniqid('stk_', true)
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
            $log_context['response'] = $response;
            $log_context['response_code'] = wp_remote_retrieve_response_code($response);
            $this->logger->log('Received M-Pesa STK Push response', 'debug', [
                ...$log_context
            ]);

            if (is_wp_error($response)) {
                $log_context['error_type'] = 'api_request_failed';
                $log_context['error_message'] = $response->get_error_message();
                $this->logger->log('M-Pesa API request failed', 'error', [
                    ...$log_context
                ]);
                throw new Exception($response->get_error_message());
            }

            $body = json_decode(wp_remote_retrieve_body($response));

            if (!$body) {
                $log_context['error_type'] = 'invalid_response';
                $log_context['raw_body'] = wp_remote_retrieve_body($response);
                $this->logger->log('Invalid M-Pesa response format', 'error', [
                    ...$log_context
                ]);
                throw new Exception('Invalid response from M-Pesa');
            }

            // Log the parsed response
            $log_context['parsed_response'] = $body;
            $this->logger->log('Parsed M-Pesa response', 'debug', [
                ...$log_context
            ]);

            // Check response code with proper error handling
            $responseCode = isset($body->ResponseCode) ? $body->ResponseCode : null;
            if ($responseCode !== '0') {
                $errorMessage = isset($body->errorMessage) ? $body->errorMessage : 
                    (isset($body->ResponseDescription) ? $body->ResponseDescription : 'STK Push failed');
                
                $log_context['error_type'] = 'stk_push_failed';
                $log_context['error_message'] = $errorMessage;
                $this->logger->log('M-Pesa STK Push failed', 'error', [
                    ...$log_context,
                    'response_code' => $responseCode
                ]);
                
                throw new Exception($errorMessage);
            }

            // Log successful STK push
            $log_context['checkout_request_id'] = $body->CheckoutRequestID ?? null;
            $log_context['merchant_request_id'] = $body->MerchantRequestID ?? null;
            $this->logger->log('M-Pesa STK Push successful', 'info', [
                ...$log_context,
                'response_description' => $body->ResponseDescription ?? '',
                'completion_time' => current_time('mysql')
            ]);

            // Save transaction record
            $transaction_id = $this->transactions->save_stk_request(
                $payment_data['donation_id'],
                $stk_request_data,
                $body
            );
            
            if (!$transaction_id) {
                throw new Exception('Failed to save transaction record');
            }

            return (object) array(
                'success' => true,
                'checkout_request_id' => $body->CheckoutRequestID ?? null,
                'merchant_request_id' => $body->MerchantRequestID ?? null
            );

        } catch (Exception $e) {
            $log_context['error_type'] = 'stk_push_exception';
            $log_context['error_message'] = $e->getMessage();
            $log_context['error_trace'] = $e->getTraceAsString();
            $this->logger->log('STK Push process failed', 'error', [
                ...$log_context
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
        // Validate credentials before making request
        if (empty($this->consumer_key) || empty($this->consumer_secret)) {
            $this->logger->log('Cannot get access token - missing credentials', 'error');
            return false;
        }

        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        
        // Log the request (without sensitive data)
        $this->logger->log('Requesting M-Pesa access token', 'debug', [
            'environment' => $this->environment,
            'api_url' => $this->get_api_url('oauth')
        ]);
        
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
            // Log the error response
            $this->logger->log('Invalid access token response', 'error', [
                'response' => $body,
                'http_code' => wp_remote_retrieve_response_code($response),
                'environment' => $this->environment
            ]);
            return false;
        }

        // Verify the token looks valid
        if (!is_string($body->access_token) || strlen($body->access_token) < 10) {
            $this->logger->log('Suspicious access token received', 'error', [
                'token_length' => strlen($body->access_token)
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

    /**
     * Test credentials with minimal transaction
     */
    public function test_credentials($phone_number) {
        try {
            if (empty($phone_number)) {
                throw new Exception('Phone number is required for testing');
            }

            // Format phone number (remove + and ensure 254 prefix)
            $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
            if (strlen($phone_number) === 9) {
                $phone_number = '254' . $phone_number;
            } elseif (strlen($phone_number) === 10) {
                $phone_number = '254' . substr($phone_number, 1);
            }

            if (!preg_match('/^254[0-9]{9}$/', $phone_number)) {
                throw new Exception('Invalid phone number format. Use format: 254XXXXXXXXX');
            }

            $test_amount = 5; // 5 KES minimum test amount
            
            // Create a test donation record
            require_once WP_DONATION_SYSTEM_PATH . 'includes/class-database.php';
            $database = new WP_Donation_System_Database();
            
            $donation_data = [
                'donor_name' => 'Test Transaction',
                'donor_email' => get_option('admin_email'),
                'donor_phone' => $phone_number,
                'amount' => $test_amount,
                'currency' => 'KES',
                'payment_method' => 'mpesa',
                'status' => 'pending',
                'metadata' => wp_json_encode(['test_transaction' => true])
            ];
            
            $donation_id = $database->insert_donation($donation_data);
            if (!$donation_id) {
                throw new Exception('Failed to create test donation record');
            }
            
            $payment_data = [
                'amount' => $test_amount,
                'phone_number' => $phone_number,
                'donation_id' => $donation_id
            ];

            $this->logger->log('Initiating test transaction', 'info', [
                'phone' => $phone_number,
                'amount' => $test_amount,
                'donation_id' => $donation_id
            ]);

            $response = $this->initiate_stk_push($payment_data);

            if (!$response->success) {
                // Update donation status to failed
                $database->update_donation($donation_id, [
                    'status' => 'failed',
                    'notes' => $response->message
                ]);
                throw new Exception($response->message);
            }

            // Update donation with checkout request ID
            $database->update_donation($donation_id, [
                'checkout_request_id' => $response->checkout_request_id,
                'merchant_request_id' => $response->merchant_request_id,
                'status' => 'processing'
            ]);

            return [
                'success' => true,
                'message' => 'Test transaction initiated. Please check your phone for the STK push.',
                'checkout_request_id' => $response->checkout_request_id,
                'donation_id' => $donation_id
            ];

        } catch (Exception $e) {
            $this->logger->log('Test transaction failed', 'error', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 