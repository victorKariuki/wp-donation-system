<?php
class WP_Donation_System_Callbacks {
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_endpoints'));
    }

    public function register_endpoints() {
        // PayPal IPN endpoint
        register_rest_route('wp-donation-system/v1', '/paypal-callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_paypal_callback'),
            'permission_callback' => '__return_true'
        ));

        // M-Pesa callback endpoint
        register_rest_route('wp-donation-system/v1', '/mpesa-callback', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_mpesa_callback'),
            'permission_callback' => '__return_true'
        ));
    }

    public function handle_paypal_callback($request) {
        $logger = new WP_Donation_System_Logger();
        $logger->log('PayPal callback received: ' . json_encode($request->get_params()), 'info');

        $params = $request->get_params();
        
        // Verify PayPal IPN
        $verified = $this->verify_paypal_ipn($params);
        if (!$verified) {
            $logger->log('PayPal IPN verification failed', 'error');
            return new WP_Error('verification_failed', 'IPN verification failed', array('status' => 400));
        }

        // Process the payment
        if ($params['payment_status'] === 'Completed') {
            global $wpdb;
            
            // Update donation status
            $wpdb->update(
                $wpdb->prefix . 'donations',
                array('status' => 'completed'),
                array('transaction_id' => $params['txn_id'])
            );

            // Get donation details
            $donation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}donations WHERE transaction_id = %s",
                $params['txn_id']
            ));

            if ($donation) {
                // Send notifications
                $notifications = new WP_Donation_System_Notifications();
                $notifications->send_donor_notification($donation);
                $notifications->send_admin_notification($donation);
            }
        }

        return new WP_REST_Response(array('status' => 'success'), 200);
    }

    public function handle_mpesa_callback($request) {
        $logger = new WP_Donation_System_Logger();
        $logger->log('M-Pesa callback received: ' . json_encode($request->get_params()), 'info');

        $params = $request->get_params();
        
        // Verify M-Pesa callback
        if (!$this->verify_mpesa_callback($params)) {
            $logger->log('M-Pesa callback verification failed', 'error');
            return new WP_Error('verification_failed', 'Callback verification failed', array('status' => 400));
        }

        // Process the callback
        if ($params['ResultCode'] === '0') { // Success
            global $wpdb;
            
            // Update donation status
            $wpdb->update(
                $wpdb->prefix . 'donations',
                array(
                    'status' => 'completed',
                    'transaction_id' => $params['TransactionID']
                ),
                array('checkout_request_id' => $params['CheckoutRequestID'])
            );

            // Get donation details
            $donation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}donations WHERE checkout_request_id = %s",
                $params['CheckoutRequestID']
            ));

            if ($donation) {
                // Send notifications
                $notifications = new WP_Donation_System_Notifications();
                $notifications->send_donor_notification($donation);
                $notifications->send_admin_notification($donation);
            }
        } else {
            // Update donation status as failed
            $wpdb->update(
                $wpdb->prefix . 'donations',
                array('status' => 'failed'),
                array('checkout_request_id' => $params['CheckoutRequestID'])
            );
        }

        return new WP_REST_Response(array('status' => 'success'), 200);
    }

    private function verify_paypal_ipn($params) {
        $paypal = new WP_Donation_System_PayPal();
        return $paypal->verify_ipn($params);
    }

    private function verify_mpesa_callback($params) {
        $mpesa = new WP_Donation_System_MPesa();
        return $mpesa->verify_callback($params);
    }
} 