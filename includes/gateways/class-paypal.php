<?php
class WP_Donation_System_Gateway_PayPal extends WP_Donation_System_Gateway {
    private $client_id;
    private $client_secret;
    private $environment;
    
    public function __construct() {
        $this->id = 'paypal';
        $this->title = __('PayPal', 'wp-donation-system');
        $this->description = __('Accept donations via PayPal. Available globally with support for multiple currencies.', 'wp-donation-system');
        
        parent::__construct();
        
        // Load PayPal specific settings
        $this->client_id = $this->settings['client_id'] ?? '';
        $this->client_secret = $this->settings['client_secret'] ?? '';
        $this->environment = $this->settings['environment'] ?? 'sandbox';
    }

    public function get_settings_fields() {
        return [
            'enabled' => [
                'title' => __('Enable/Disable', 'wp-donation-system'),
                'type' => 'checkbox',
                'label' => __('Enable PayPal payments', 'wp-donation-system'),
                'default' => 'no'
            ],
            'environment' => [
                'title' => __('Environment', 'wp-donation-system'),
                'type' => 'select',
                'options' => [
                    'sandbox' => __('Sandbox', 'wp-donation-system'),
                    'live' => __('Live', 'wp-donation-system')
                ],
                'default' => 'sandbox'
            ],
            'client_id' => [
                'title' => __('Client ID', 'wp-donation-system'),
                'type' => 'text',
                'description' => __('Enter your PayPal Client ID', 'wp-donation-system')
            ],
            'client_secret' => [
                'title' => __('Client Secret', 'wp-donation-system'),
                'type' => 'password',
                'description' => __('Enter your PayPal Client Secret', 'wp-donation-system')
            ]
        ];
    }

    public function validate_fields($data) {
        // PayPal doesn't require additional field validation
        return true;
    }

    public function get_payment_fields() {
        // PayPal doesn't require additional fields in the form
        return [];
    }

    public function process_payment($donation_data) {
        try {
            // Create PayPal order
            $order = $this->create_paypal_order([
                'amount' => $donation_data['amount'],
                'currency' => $donation_data['currency'] ?? 'USD',
                'donation_id' => $donation_data['donation_id']
            ]);

            return [
                'result' => 'success',
                'redirect' => true,
                'redirect_url' => $order['approve_url']
            ];

        } catch (Exception $e) {
            $this->log('PayPal payment processing failed: ' . $e->getMessage(), 'error', $donation_data);
            throw $e;
        }
    }

    private function create_paypal_order($data) {
        $api_url = $this->get_api_url('v2/checkout/orders');
        $access_token = $this->get_access_token();

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'intent' => 'CAPTURE',
                'purchase_units' => [[
                    'amount' => [
                        'currency_code' => $data['currency'],
                        'value' => number_format($data['amount'], 2, '.', '')
                    ],
                    'custom_id' => $data['donation_id']
                ]],
                'application_context' => [
                    'return_url' => add_query_arg([
                        'donation_id' => $data['donation_id'],
                        'gateway' => 'paypal'
                    ], home_url('/donation-complete/')),
                    'cancel_url' => add_query_arg([
                        'donation_id' => $data['donation_id'],
                        'gateway' => 'paypal',
                        'cancelled' => '1'
                    ], home_url('/donation-cancelled/'))
                ]
            ])
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($result['id'])) {
            throw new Exception('Invalid PayPal response');
        }

        // Find the approve URL
        $approve_url = '';
        foreach ($result['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approve_url = $link['href'];
                break;
            }
        }

        if (!$approve_url) {
            throw new Exception('PayPal approve URL not found');
        }

        return [
            'id' => $result['id'],
            'approve_url' => $approve_url
        ];
    }

    private function get_access_token() {
        $cache_key = 'paypal_access_token_' . $this->environment;
        $access_token = get_transient($cache_key);
        
        if ($access_token) {
            return $access_token;
        }

        $api_url = $this->get_api_url('v1/oauth2/token');
        $credentials = base64_encode($this->client_id . ':' . $this->client_secret);

        $response = wp_remote_post($api_url, [
            'headers' => [
                'Authorization' => 'Basic ' . $credentials,
                'Content-Type' => 'application/x-www-form-urlencoded'
            ],
            'body' => 'grant_type=client_credentials'
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $result = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($result['access_token'])) {
            throw new Exception('Failed to get PayPal access token');
        }

        set_transient($cache_key, $result['access_token'], $result['expires_in'] - 60);
        
        return $result['access_token'];
    }

    private function get_api_url($endpoint) {
        $base_url = $this->environment === 'live' 
            ? 'https://api.paypal.com/'
            : 'https://api.sandbox.paypal.com/';
            
        return $base_url . $endpoint;
    }

    public function has_test_mode() {
        return true;
    }

    public function get_security_badge() {
        return WP_DONATION_SYSTEM_URL . 'assets/images/paypal-verified.png';
    }

    public function get_fields_title() {
        return __('Complete Payment with PayPal', 'wp-donation-system');
    }
} 