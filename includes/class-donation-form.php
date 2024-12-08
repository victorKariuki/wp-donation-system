<?php
class WP_Donation_System_Form {
    private $settings;
    private $currency;

    public function __construct() {
        add_shortcode('donation_form', array($this, 'render_donation_form'));
        $this->settings = get_option('wp_donation_system_settings', array());
        
        // Initialize currency class
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-currency.php';
        $this->currency = new WP_Donation_System_Currency();
        
        // Add script enqueuing
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Helper function to get setting value
     */
    private function get_setting_value($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Render donation form
     */
    public function render_donation_form($atts = array(), $content = null, $tag = '') {
        // Ensure scripts are enqueued
        wp_enqueue_script('wp-donation-system');
        wp_enqueue_style('wp-donation-system');

        // Start output buffering
        ob_start();

        // Make settings and helper function available to template
        $settings = $this->settings;
        $currency = $this->currency;
        $get_setting_value = array($this, 'get_setting_value');
        $default_currency = $this->get_setting_value('default_currency', 'USD');
        $min_amount = $this->get_setting_value('donation_minimum', 5);
        $max_amount = $this->get_setting_value('donation_maximum', 10000);

        // Include the template
        include WP_DONATION_SYSTEM_PATH . 'templates/donation-form.php';

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Enqueue necessary scripts and styles
     */
    public function enqueue_scripts() {
        // Enqueue styles
        wp_enqueue_style(
            'wp-donation-system',
            WP_DONATION_SYSTEM_URL . 'assets/css/public-style.css',
            array(),
            WP_DONATION_SYSTEM_VERSION
        );

        // Enqueue jQuery
        wp_enqueue_script('jquery');

        // Enqueue our script
        wp_enqueue_script(
            'wp-donation-system',
            WP_DONATION_SYSTEM_URL . 'assets/js/public-script.js',
            array('jquery'),
            WP_DONATION_SYSTEM_VERSION,
            true
        );

        // Localize script
        $this->localize_script();
    }

    public function localize_script() {
        $currency = new WP_Donation_System_Currency();
        $default_currency = $this->get_setting_value('default_currency', 'USD');
        $currency_data = $currency->get_currency($default_currency);

        wp_localize_script('wp-donation-system', 'wpDonationSystem', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_donation_system'),
            'currency' => array(
                'code' => $default_currency,
                'data' => $currency_data
            ),
            'i18n' => array(
                'invalid_amount' => __('Please enter a valid donation amount.', 'wp-donation-system'),
                'amount_range' => __('Please enter an amount between {min} and {max}.', 'wp-donation-system'),
                'required_field' => __('This field is required.', 'wp-donation-system'),
                'invalid_email' => __('Please enter a valid email address.', 'wp-donation-system'),
                'select_payment' => __('Please select a payment method.', 'wp-donation-system'),
                'invalid_phone' => __('Please enter a valid M-Pesa phone number starting with 254.', 'wp-donation-system'),
                'processing' => __('Processing...', 'wp-donation-system'),
                'donate' => __('Complete Donation', 'wp-donation-system'),
                'error' => __('An error occurred. Please try again.', 'wp-donation-system'),
                'waiting_payment' => __('Waiting for payment...', 'wp-donation-system'),
                'payment_timeout' => __('Payment timeout. Please try again.', 'wp-donation-system'),
                'retry_payment' => __('Retry Payment', 'wp-donation-system'),
            ),
            'mpesa_waiting_title' => __('Waiting for M-Pesa Payment', 'wp-donation-system'),
            'mpesa_waiting_message' => __('Please check your phone and enter your M-Pesa PIN to complete the payment.', 'wp-donation-system'),
            'mpesa_timeout' => __('M-Pesa payment request timed out. Please try again.', 'wp-donation-system'),
            'seconds_remaining' => __('seconds remaining', 'wp-donation-system'),
            'cancel_payment' => __('Cancel Payment', 'wp-donation-system'),
            'retry_payment' => __('Try Again', 'wp-donation-system'),
            'complete_required_fields' => __('Please complete all required fields.', 'wp-donation-system'),
        ));
    }
}
