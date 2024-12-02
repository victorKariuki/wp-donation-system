<?php
class WP_Donation_System_Form {
    private $validator;

    public function __construct() {
        $this->validator = new WP_Donation_System_Form_Validator();
        add_shortcode('donation_form', array($this, 'render_donation_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function render_donation_form() {
        ob_start();
        include WP_DONATION_SYSTEM_PATH . 'templates/donation-form.php';
        return ob_get_clean();
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-donation-system', WP_DONATION_SYSTEM_URL . 'assets/css/public-style.css');
        wp_enqueue_script('wp-donation-system', WP_DONATION_SYSTEM_URL . 'assets/js/public-script.js', array('jquery'), false, true);
        wp_localize_script('wp-donation-system', 'wpDonationSystem', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_donation_system_nonce')
        ));
    }

    public function process_form() {
        check_ajax_referer('wp_donation_system_nonce', 'nonce');

        $form_data = $this->sanitize_form_data($_POST);
        $validation = $this->validator->validate($form_data);

        if ($validation !== true) {
            wp_send_json_error(array(
                'message' => implode('<br>', $validation)
            ));
        }

        // Process payment based on method
        $payment_method = $form_data['payment_method'];
        if ($payment_method === 'paypal') {
            $paypal = new WP_Donation_System_PayPal();
            $result = $paypal->process_payment($form_data);
        } elseif ($payment_method === 'mpesa') {
            $mpesa = new WP_Donation_System_MPesa();
            $result = $mpesa->process_payment($form_data);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message()
            ));
        }

        wp_send_json_success($result);
    }

    private function sanitize_form_data($data) {
        return array(
            'donor_name' => sanitize_text_field($data['donor_name']),
            'donor_email' => sanitize_email($data['donor_email']),
            'amount' => floatval($data['amount']),
            'payment_method' => sanitize_text_field($data['payment_method']),
            'phone' => isset($data['phone']) ? sanitize_text_field($data['phone']) : ''
        );
    }
}
