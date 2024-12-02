<?php
class WP_Donation_System_PayPal {
    private $client_id;
    private $client_secret;
    private $is_sandbox;
    private $access_token;

    public function __construct() {
        add_action('wp_ajax_process_paypal_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_process_paypal_payment', array($this, 'process_payment'));
        
        $is_test_mode = get_option('wp_donation_test_mode', false);
        
        if ($is_test_mode) {
            $this->client_id = get_option('wp_donation_paypal_test_client_id');
            $this->client_secret = get_option('wp_donation_paypal_test_client_secret');
            $this->is_sandbox = true;
        } else {
            $this->client_id = get_option('wp_donation_paypal_client_id');
            $this->client_secret = get_option('wp_donation_paypal_client_secret');
            $this->is_sandbox = false;
        }
    }

    private function get_access_token() {
        if ($this->access_token) {
            return $this->access_token;
        }

        $api_url = $this->is_sandbox 
            ? 'https://api-m.sandbox.paypal.com/v1/oauth2/token'
            : 'https://api-m.paypal.com/v1/oauth2/token';

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($this->client_id . ':' . $this->client_secret),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => 'grant_type=client_credentials'
        ));

        if (is_wp_error($response)) {
            $this->log_error('PayPal access token error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['access_token'])) {
            $this->access_token = $body['access_token'];
            return $this->access_token;
        }

        return false;
    }

    public function process_payment() {
        check_ajax_referer('wp_donation_system_nonce', 'nonce');

        $form_data = $_POST['form_data'];
        
        // Create PayPal order
        $order = $this->create_paypal_order($form_data);
        
        if ($order) {
            wp_send_json_success(array(
                'redirect_url' => $order['approve_url']
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to create PayPal payment', 'wp-donation-system')
            ));
        }
    }

    private function create_paypal_order($form_data) {
        $access_token = $this->get_access_token();
        if (!$access_token) {
            return false;
        }

        $api_url = $this->is_sandbox 
            ? 'https://api-m.sandbox.paypal.com/v2/checkout/orders'
            : 'https://api-m.paypal.com/v2/checkout/orders';

        $amount = floatval($form_data['amount']);
        
        $payload = array(
            'intent' => 'CAPTURE',
            'purchase_units' => array(
                array(
                    'amount' => array(
                        'currency_code' => 'USD',
                        'value' => number_format($amount, 2, '.', '')
                    ),
                    'description' => 'Donation to ' . get_bloginfo('name')
                )
            ),
            'application_context' => array(
                'return_url' => add_query_arg('action', 'paypal_return', home_url()),
                'cancel_url' => home_url(),
                'notify_url' => rest_url('wp-donation-system/v1/paypal-callback')
            )
        );

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($payload)
        ));

        if (is_wp_error($response)) {
            $this->log_error('PayPal order creation error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['id'])) {
            // Save donation details to database
            $donation_id = $this->save_donation($form_data, $body['id']);
            
            // Find approval URL
            foreach ($body['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return array(
                        'order_id' => $body['id'],
                        'approve_url' => $link['href'],
                        'donation_id' => $donation_id
                    );
                }
            }
        }

        return false;
    }

    private function save_donation($form_data, $transaction_id) {
        global $wpdb;
        
        $data = array(
            'donor_name' => sanitize_text_field($form_data['donor_name']),
            'donor_email' => sanitize_email($form_data['donor_email']),
            'amount' => floatval($form_data['amount']),
            'currency' => 'USD',
            'payment_method' => 'paypal',
            'transaction_id' => $transaction_id,
            'status' => 'pending',
            'created_at' => current_time('mysql')
        );

        $wpdb->insert($wpdb->prefix . 'donations', $data);
        return $wpdb->insert_id;
    }

    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($message);
        }
    }

    private function handle_successful_payment($donation_data) {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'donations',
            array('status' => 'completed'),
            array('id' => $donation_data['id'])
        );
        
        $notifications = new WP_Donation_System_Notifications();
        $notifications->send_donor_notification($donation_data);
        $notifications->send_admin_notification($donation_data);
    }

    public function verify_ipn($params) {
        $is_sandbox = get_option('wp_donation_test_mode', false);
        $paypal_url = $is_sandbox 
            ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr'
            : 'https://ipnpb.paypal.com/cgi-bin/webscr';

        // Add 'cmd' parameter
        $params['cmd'] = '_notify-validate';

        $response = wp_remote_post($paypal_url, array(
            'body' => $params,
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            $this->log_error('PayPal IPN verification failed: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        return $body === 'VERIFIED';
    }
}
