<?php
class WP_Donation_System_Gateway_MPesa extends WP_Donation_System_Gateway
{
    private $consumer_key;
    private $consumer_secret;
    private $passkey;
    private $shortcode;
    private $environment;
    private $type;
    private $callback_url;
    private $number;
    
    public function __construct() {
        $this->id = 'mpesa';
        $this->title = __('M-Pesa Express', 'wp-donation-system');
        $this->description = __('Accept donations via M-Pesa mobile money payments. Available for customers in Kenya.', 'wp-donation-system');

        parent::__construct();

        // Load M-Pesa specific settings
        $this->consumer_key = $this->settings['consumer_key'] ?? '';
        $this->consumer_secret = $this->settings['consumer_secret'] ?? '';
        $this->passkey = $this->settings['passkey'] ?? '';
        $this->shortcode = $this->settings['shortcode'] ?? '';
        $this->environment = $this->settings['environment'] ?? 'sandbox';
        $this->type = $this->settings['type'] ?? 'till';
        $this->number = $this->settings['number'] ?? '';
    }

    public function get_callback_url()
    {
        // Get the site URL and ensure it's HTTPS
        $site_url = site_url();
        $site_url = str_replace('http://', 'https://', $site_url);
        
        // Build the callback URL
        $callback_url = rest_url('wp-donation-system/v1/payment/callback');
        $callback_url = str_replace('http://', 'https://', $callback_url);
        
        // Log the callback URL for debugging
        $this->logger->log('Generated callback URL', 'debug', [
            'url' => $callback_url
        ]);
        
        return $callback_url;
    }

    public function get_settings_fields()
    {
        return [
            'enabled' => [
                'title' => __('Enable/Disable', 'wp-donation-system'),
                'type' => 'checkbox',
                'label' => __('Enable M-Pesa payments', 'wp-donation-system'),
                'default' => 'no'
            ],
            'environment' => [
                'title' => __('Environment', 'wp-donation-system'),
                'type' => 'select',
                'options' => [
                    'sandbox' => __('Sandbox', 'wp-donation-system'),
                    'live' => __('Live', 'wp-donation-system')
                ],
                'default' => 'sandbox',
                'description' => __('Select sandbox for testing or live for real transactions.', 'wp-donation-system')
            ],
            'type' => [
                'title' => __('Type', 'wp-donation-system'),
                'type' => 'select',
                'options' => [
                    'paybill' => __('Paybill', 'wp-donation-system'),
                    'till' => __('Till', 'wp-donation-system')
                ],
                'default' => 'paybill',
                'description' => __('Select your M-Pesa integration type.', 'wp-donation-system')
            ],
            'shortcode' => [
                'title' => __('Shortcode', 'wp-donation-system'),
                'type' => 'text',
                'description' => __('Enter your M-Pesa Shortcode/Paybill', 'wp-donation-system'),
                'required' => true
            ],
            'number' => [
                'title' => __('Till Number / Paybill', 'wp-donation-system'),
                'type' => 'text',
                'description' => __('Enter your M-Pesa Till Number / Paybill', 'wp-donation-system'),
                'required' => true
            ],
            'consumer_key' => [
                'title' => __('Consumer Key', 'wp-donation-system'),
                'type' => 'text',
                'description' => __('Enter your M-Pesa API Consumer Key', 'wp-donation-system'),
                'required' => true
            ],
            'consumer_secret' => [
                'title' => __('Consumer Secret', 'wp-donation-system'),
                'type' => 'password',
                'description' => __('Enter your M-Pesa API Consumer Secret', 'wp-donation-system'),
                'required' => true
            ],
            'passkey' => [
                'title' => __('Passkey', 'wp-donation-system'),
                'type' => 'password',
                'description' => __('Enter your M-Pesa Passkey', 'wp-donation-system'),
                'required' => true
            ]
        ];
    }

    public function validate_fields($data)
    {
        $this->logger->log('Validating M-Pesa payment fields', 'debug', $data);

        if (empty($data['donor_phone'])) {
            $this->logger->log('Phone number validation failed: Empty phone number', 'error');
            throw new Exception(__('Phone number is required for M-Pesa payments', 'wp-donation-system'));
        }

        // Validate phone number format
        if (!preg_match('/^254[0-9]{9}$/', $data['donor_phone'])) {
            $this->logger->log('Phone number validation failed: Invalid format', 'error', ['phone' => $data['donor_phone']]);
            throw new Exception(__('Invalid phone number format. Use format: 254XXXXXXXXX', 'wp-donation-system'));
        }

        $this->logger->log('M-Pesa payment fields validated successfully', 'debug');
        return true;
    }

    public function get_payment_fields()
    {
        return [
            'donor_phone' => [
                'type' => 'tel',
                'label' => __('M-Pesa Phone Number', 'wp-donation-system'),
                'required' => true,
                'placeholder' => '254XXXXXXXXX',
                'pattern' => '254[0-9]{9}',
                'maxlength' => 12,
                'hint' => __('Enter your M-Pesa phone number starting with 254', 'wp-donation-system'),
                'validation' => '/^254[0-9]{9}$/'
            ]
        ];
    }

    public function process_payment($donation_data)
    {
        global $wpdb;
        
        try {
            $this->logger->log('Processing M-Pesa payment', 'debug', $donation_data);
            
            // Validate and sanitize amount
            $amount = $donation_data['amount'];
            
            // Convert to numeric value if it's a string
            if (is_string($amount)) {
                $amount = str_replace(',', '', $amount); // Remove any commas
                $amount = trim($amount);
            }
            
            // Convert to integer
            $amount = (int) $amount;
            
            $this->logger->log('Amount validation', 'debug', [
                'original' => $donation_data['amount'],
                'processed' => $amount,
                'type' => gettype($amount)
            ]);
            
            // Validate minimum amount
            if ($amount < 1) {
                throw new Exception(__('Donation amount must be at least 1', 'wp-donation-system'));
            }

            // Get access token
            $access_token = $this->get_access_token();
            
            // Format phone number
            $phone = $this->format_phone_number($donation_data['donor_phone']);
            
            // Prepare STK Push request
            $timestamp = date('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
            
            $callback_url = $this->get_callback_url();
            
            $stk_request = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $this->type === 'paybill' ? 'CustomerPayBillOnline' : 'CustomerBuyGoodsOnline',
                'Amount' => $amount,
                'PartyA' => $phone,
                'PartyB' => $this->number,
                'PhoneNumber' => $phone,
                'CallBackURL' => $callback_url,
                'AccountReference' => 'DON-' . $donation_data['donation_id'],
                'TransactionDesc' => 'Donation Payment'
            ];

            $this->logger->log('Sending STK push request', 'debug', [
                'request' => array_merge($stk_request, ['Password' => '***'])
            ]);

            // Make STK Push request
            $response = wp_remote_post($this->get_api_url('mpesa/stkpush/v1/processrequest'), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ],
                'body' => wp_json_encode($stk_request)
            ]);

            if (is_wp_error($response)) {
                $this->logger->log('STK push request failed', 'error', ['error' => $response->get_error_message()]);
                throw new Exception($response->get_error_message());
            }

            $result = json_decode(wp_remote_retrieve_body($response));
            $this->logger->log('STK push response received', 'debug', ['response' => $result]);

            if (!isset($result->ResponseCode) || $result->ResponseCode !== '0') {
                $error_message = $result->errorMessage ?? 'STK push failed';
                $this->logger->log('STK push failed', 'error', ['error' => $error_message]);
                throw new Exception($error_message);
            }

            // Update donation record with M-Pesa request IDs
            $wpdb->update(
                $wpdb->prefix . 'donation_system_donations',
                array(
                    'checkout_request_id' => $result->CheckoutRequestID,
                    'merchant_request_id' => $result->MerchantRequestID
                ),
                array('id' => $donation_data['donation_id'])
            );

            return [
                'result' => 'success',
                'redirect' => false,
                'donation_id' => $donation_data['donation_id'],
                'checkout_request_id' => $result->CheckoutRequestID
            ];

        } catch (Exception $e) {
            $this->logger->log('Payment processing failed: ' . $e->getMessage(), 'error', $donation_data);
            throw $e;
        }
    }

    private function format_phone_number($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/\D/', '', $phone);
        
        // Ensure it starts with 254
        if (strlen($phone) === 9) {
            $phone = '254' . $phone;
        } elseif (strlen($phone) === 10 && $phone[0] === '0') {
            $phone = '254' . substr($phone, 1);
        }
        
        return $phone;
    }

    private function get_access_token() {
        $cache_key = 'mpesa_access_token_' . $this->environment;
        $access_token = get_transient($cache_key);

        if ($access_token) {
            $this->logger->log('Using cached access token', 'debug');
            return $access_token;
        }

        $this->logger->log('Requesting new access token', 'debug');
        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        $api_url = $this->get_api_url('oauth/v1/generate?grant_type=client_credentials');

        $response = wp_remote_get($api_url, [
            'headers' => [
                'Authorization' => 'Basic ' . $credentials
            ]
        ]);

        if (is_wp_error($response)) {
            $this->logger->log('Access token request failed', 'error', ['error' => $response->get_error_message()]);
            throw new Exception($response->get_error_message());
        }

        $result = json_decode(wp_remote_retrieve_body($response));

        if (!isset($result->access_token)) {
            $this->logger->log('Failed to get access token', 'error', ['response' => $result]);
            throw new Exception('Failed to get access token');
        }

        $this->logger->log('New access token obtained', 'debug');
        set_transient($cache_key, $result->access_token, 3500); // Token expires in 1 hour

        return $result->access_token;
    }

    private function get_api_url($endpoint) {
        $base_url = $this->environment === 'live'
            ? 'https://api.safaricom.co.ke/'
            : 'https://sandbox.safaricom.co.ke/';

        return $base_url . $endpoint;
    }

    protected function load_settings()
    {
        $this->logger->log('Loading M-Pesa settings', 'debug');
        $this->settings = get_option('wp_donation_system_' . $this->id . '_settings', []);
        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : false;
        
        // Load specific settings
        $this->consumer_key = $this->settings['consumer_key'] ?? '';
        $this->consumer_secret = $this->settings['consumer_secret'] ?? '';
        $this->passkey = $this->settings['passkey'] ?? '';
        $this->shortcode = $this->settings['shortcode'] ?? '';
        $this->environment = $this->settings['environment'] ?? 'sandbox';
        $this->type = $this->settings['type'] ?? 'paybill';
        
        $this->logger->log('M-Pesa settings loaded', 'debug', [
            'enabled' => $this->enabled,
            'environment' => $this->environment,
            'type' => $this->type
        ]);
    }

    public function has_test_mode()
    {
        return true;
    }

    /**
     * Get security badge URL
     * 
     * @return string|false Security badge URL or false if none
     */
    public function get_security_badge() {
        return false; // M-Pesa doesn't have a security badge
    }

    /**
     * Get fields title
     * 
     * @return string
     */
    public function get_fields_title() {
        return __('Enter M-Pesa Payment Details', 'wp-donation-system');
    }

    /**
     * Save transaction details
     */
    protected function save_transaction_details($donation_id, $data) {
        return WP_Donation_System_MPesa_Transaction::create([
            'donation_id' => $donation_id,
            'checkout_request_id' => $data['checkout_request_id'] ?? '',
            'merchant_request_id' => $data['merchant_request_id'] ?? '',
            'phone_number' => $data['phone_number'] ?? '',
            'raw_request' => wp_json_encode($data['request'] ?? []),
            'raw_response' => wp_json_encode($data['response'] ?? [])
        ]);
    }

    /**
     * Update transaction status
     */
    protected function update_transaction_status($checkout_request_id, $status_data) {
        $transaction = WP_Donation_System_MPesa_Transaction::query()
            ->where('checkout_request_id', $checkout_request_id)
            ->first();
            
        if ($transaction) {
            return $transaction->fill([
                'transaction_id' => $status_data['transaction_id'] ?? '',
                'mpesa_receipt_number' => $status_data['receipt_number'] ?? '',
                'result_code' => $status_data['result_code'] ?? '',
                'result_desc' => $status_data['result_desc'] ?? '',
                'raw_response' => wp_json_encode($status_data)
            ])->save();
        }
        return false;
    }
}