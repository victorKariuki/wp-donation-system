<?php
abstract class WP_Donation_System_Gateway {
    protected $id;
    protected $title;
    protected $description;
    protected $enabled = false;
    protected $logger;
    protected $settings;

    abstract public function process_payment($donation_data);
    abstract public function validate_fields($data);
    abstract public function get_payment_fields();
    
    public function __construct() {
        $this->logger = new WP_Donation_System_Logger();
        $this->load_settings();
    }

    public function is_enabled() {
        return $this->enabled;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_title() {
        return $this->title;
    }

    public function get_description() {
        return $this->description;
    }

    public function has_test_mode() {
        return false;
    }

    protected function load_settings() {
        $this->settings = get_option('wp_donation_system_' . $this->id . '_settings', []);
        $this->enabled = isset($this->settings['enabled']) ? $this->settings['enabled'] : false;
    }

    public function get_settings_fields() {
        return [];
    }

    protected function log($message, $level = 'info', $context = []) {
        $this->logger->log($message, $level, array_merge($context, [
            'gateway' => $this->id
        ]));
    }

    public function save_settings($settings) {
        update_option('wp_donation_system_' . $this->id . '_settings', $settings);
    }

    public function get_setting($key, $default = '') {
        return $this->settings[$key] ?? $default;
    }

    public function validate_settings($settings) {
        return true;
    }

    /**
     * Get security badge URL
     * 
     * @return string|false Security badge URL or false if none
     */
    public function get_security_badge() {
        return false;
    }

    /**
     * Get fields title
     * 
     * @return string
     */
    public function get_fields_title() {
        return __('Enter Payment Details', 'wp-donation-system');
    }
} 