<?php
class WP_Donation_System_Admin {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Donations', 'wp-donation-system'),
            __('Donations', 'wp-donation-system'),
            'manage_options',
            'wp-donation-system',
            array($this, 'display_donations_page'),
            'dashicons-heart'
        );

        add_submenu_page(
            'wp-donation-system',
            __('Settings', 'wp-donation-system'),
            __('Settings', 'wp-donation-system'),
            'manage_options',
            'wp-donation-system-settings',
            array($this, 'display_settings_page')
        );

        add_submenu_page(
            'wp-donation-system',
            __('Reports', 'wp-donation-system'),
            __('Reports', 'wp-donation-system'),
            'manage_options',
            'wp-donation-system-reports',
            array($this, 'display_reports_page')
        );

        add_submenu_page(
            'wp-donation-system',
            __('Export', 'wp-donation-system'),
            __('Export', 'wp-donation-system'),
            'manage_options',
            'wp-donation-system-export',
            array($this, 'handle_export')
        );
    }

    public function register_settings() {
        register_setting('wp_donation_system_settings', 'wp_donation_paypal_client_id');
        register_setting('wp_donation_system_settings', 'wp_donation_paypal_client_secret');
        register_setting('wp_donation_system_settings', 'wp_donation_paypal_sandbox');
        register_setting('wp_donation_system_settings', 'wp_donation_mpesa_consumer_key');
        register_setting('wp_donation_system_settings', 'wp_donation_mpesa_consumer_secret');
        register_setting('wp_donation_system_settings', 'wp_donation_mpesa_shortcode');
        register_setting('wp_donation_system_settings', 'wp_donation_mpesa_passkey');
        register_setting('wp_donation_system_settings', 'wp_donation_mpesa_sandbox');
    }
}
