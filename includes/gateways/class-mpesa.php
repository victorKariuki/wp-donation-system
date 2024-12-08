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
    }

    public function get_callback_url()
    {
        if (!$this->callback_url) {
            $this->callback_url = rest_url('wp-donation-system/v1/mpesa-callback');
        }
        return $this->callback_url;
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
        if (empty($data['donor_phone'])) {
            throw new Exception(__('Phone number is required for M-Pesa payments', 'wp-donation-system'));
        }

        // Validate phone number format
        if (!preg_match('/^254[0-9]{9}$/', $data['donor_phone'])) {
            throw new Exception(__('Invalid phone number format. Use format: 254XXXXXXXXX', 'wp-donation-system'));
        }

        return true;
    }

    public function get_payment_fields()
    {
        return [
            'donor_phone' => [
                'type' => 'text',
                'label' => __('M-Pesa Phone Number', 'wp-donation-system'),
                'required' => true,
                'placeholder' => '254XXXXXXXXX',
                'description' => __('Enter your M-Pesa phone number', 'wp-donation-system'),
                'value' => $this->settings['donor_phone'] ?? '',
                'validation' => '/^254[0-9]{9}$/'
            ]
        ];
    }

    public function process_payment($donation_data)
    {
        try {
            $this->validate_fields($donation_data);

            // Initialize STK Push
            $stk_response = $this->initiate_stk_push([
                'phone_number' => $donation_data['donor_phone'],
                'amount' => $donation_data['amount'],
                'donation_id' => $donation_data['donation_id']
            ]);

            if (!$stk_response->success) {
                throw new Exception($stk_response->message);
            }

            return [
                'result' => 'success',
                'redirect' => false,
                'checkout_request_id' => $stk_response->checkout_request_id
            ];

        } catch (Exception $e) {
            $this->log('Payment processing failed: ' . $e->getMessage(), 'error', $donation_data);
            throw $e;
        }
    }

    private function initiate_stk_push($data)
    {
        try {
            $access_token = $this->get_access_token();

            $timestamp = date('YmdHis');
            $password = base64_encode($this->shortcode . $this->passkey . $timestamp);

            $api_url = $this->get_api_url('mpesa/stkpush/v1/processrequest');

            $body = [
                'BusinessShortCode' => $this->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => $this->type === 'paybill' ? 'CustomerPayBillOnline' : 'CustomerBuyGoodsOnline',
                'Amount' => $data['amount'],
                'PartyA' => $data['phone_number'],
                'PartyB' => $this->shortcode,
                'PhoneNumber' => $data['phone_number'],
                'CallBackURL' => $this->get_callback_url(),
                'AccountReference' => 'Donation #' . $data['donation_id'],
                'TransactionDesc' => 'Donation Payment'
            ];

            $response = wp_remote_post($api_url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode($body)
            ]);

            if (is_wp_error($response)) {
                throw new Exception($response->get_error_message());
            }

            $result = json_decode(wp_remote_retrieve_body($response));

            if (!isset($result->ResponseCode) || $result->ResponseCode !== '0') {
                throw new Exception($result->errorMessage ?? 'STK push failed');
            }

            return (object) [
                'success' => true,
                'checkout_request_id' => $result->CheckoutRequestID,
                'message' => 'STK push initiated successfully'
            ];

        } catch (Exception $e) {
            $this->log('STK push failed: ' . $e->getMessage(), 'error', $data);
            return (object) [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function get_access_token() {
        $cache_key = 'mpesa_access_token_' . $this->environment;
        $access_token = get_transient($cache_key);

        if ($access_token) {
            return $access_token;
        }

        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        $api_url = $this->get_api_url('oauth/v1/generate?grant_type=client_credentials');

        $response = wp_remote_get($api_url, [
            'headers' => [
                'Authorization' => 'Basic ' . $credentials
            ]
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $result = json_decode(wp_remote_retrieve_body($response));

        if (!isset($result->access_token)) {
            throw new Exception('Failed to get access token');
        }

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
        $this->settings = get_option('wp_donation_system_' . $this->id . '_settings', []);
        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : false;
        
        // Load specific settings
        $this->consumer_key = $this->settings['consumer_key'] ?? '';
        $this->consumer_secret = $this->settings['consumer_secret'] ?? '';
        $this->passkey = $this->settings['passkey'] ?? '';
        $this->shortcode = $this->settings['shortcode'] ?? '';
        $this->environment = $this->settings['environment'] ?? 'sandbox';
        $this->type = $this->settings['type'] ?? 'paybill';
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
}