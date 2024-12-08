<?php
class WP_Donation_System_Gateway_Manager {
    private $gateways = [];
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init_gateways'), 5);
    }

    public function init_gateways() {
        // Register default gateways
        $this->register_gateway('WP_Donation_System_Gateway_PayPal');
        $this->register_gateway('WP_Donation_System_Gateway_MPesa');
        // Allow other plugins to register gateways
        do_action('wp_donation_system_register_gateways', $this);
    }

    public function register_gateway($gateway_class) {
        if (!class_exists($gateway_class)) {
            return false;
        }

        $gateway = new $gateway_class();
        if (!($gateway instanceof WP_Donation_System_Gateway)) {
            return false;
        }

        $this->gateways[$gateway->get_id()] = $gateway;
        return true;
    }

    public function get_available_gateways() {
        $available_gateways = [];
        
        foreach ($this->gateways as $gateway) {
            if ($gateway->is_enabled()) {
                $available_gateways[$gateway->get_id()] = $gateway;
            }
        }

        return apply_filters('wp_donation_system_available_gateways', $available_gateways);
    }

    public function get_gateway($gateway_id) {
        return isset($this->gateways[$gateway_id]) ? $this->gateways[$gateway_id] : null;
    }

    /**
     * Get all registered gateways, regardless of enabled status
     *
     * @return array Array of gateway instances
     */
    public function get_all_gateways() {
        return $this->gateways;
    }
} 