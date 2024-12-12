<?php
class WP_Donation_System_Settings_Manager {
    private static $instance = null;
    private $settings_groups = [];

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_settings_groups();
    }

    private function init_settings_groups() {
        // Register default settings groups
        $this->register_settings_group('general', [
            'title' => __('General Settings', 'wp-donation-system'),
            'fields' => [
                'default_currency' => [
                    'title' => __('Default Currency', 'wp-donation-system'),
                    'type' => 'select',
                    'options' => [
                        'USD' => 'US Dollar',
                        'EUR' => 'Euro',
                        'GBP' => 'British Pound',
                        'KES' => 'Kenyan Shilling'
                    ],
                    'default' => 'USD',
                    'description' => __('Select the default currency for donations.', 'wp-donation-system')
                ],
                'donation_minimum' => [
                    'title' => __('Minimum Amount', 'wp-donation-system'),
                    'type' => 'number',
                    'description' => __('Minimum donation amount allowed.', 'wp-donation-system'),
                    'default' => 5,
                    'min' => 0
                ],
                'donation_maximum' => [
                    'title' => __('Maximum Amount', 'wp-donation-system'),
                    'type' => 'number',
                    'description' => __('Maximum donation amount allowed.', 'wp-donation-system'),
                    'default' => 10000,
                    'min' => 0
                ]
            ]
        ]);

        $this->register_settings_group('payment', [
            'title' => __('Payment Gateways', 'wp-donation-system'),
            'description' => __('Configure payment gateway settings', 'wp-donation-system'),
            'template' => 'payment-gateways',
            'custom_render' => true
        ]);

        $this->register_settings_group('email', [
            'title' => __('Email Settings', 'wp-donation-system'),
            'fields' => [
                'email_notifications' => [
                    'title' => __('Enable Email Notifications', 'wp-donation-system'),
                    'type' => 'checkbox',
                    'description' => __('Send email notifications for donations.', 'wp-donation-system'),
                    'default' => true
                ],
                'admin_email' => [
                    'title' => __('Admin Email', 'wp-donation-system'),
                    'type' => 'email',
                    'description' => __('Email address for receiving admin notifications.', 'wp-donation-system'),
                    'default' => get_option('admin_email')
                ],
                'email_template' => [
                    'title' => __('Email Template', 'wp-donation-system'),
                    'type' => 'select',
                    'options' => [
                        'default' => __('Default Template', 'wp-donation-system'),
                        'minimal' => __('Minimal Template', 'wp-donation-system'),
                        'custom' => __('Custom Template', 'wp-donation-system')
                    ],
                    'default' => 'default'
                ],
                'receipt_footer' => [
                    'title' => __('Receipt Footer', 'wp-donation-system'),
                    'type' => 'textarea',
                    'description' => __('Custom text to appear at the bottom of donation receipts.', 'wp-donation-system'),
                    'default' => ''
                ]
            ]
        ]);

        $this->register_settings_group('advanced', [
            'title' => __('Advanced Settings', 'wp-donation-system'),
            'fields' => [
                'debug_mode' => [
                    'title' => __('Debug Mode', 'wp-donation-system'),
                    'type' => 'checkbox',
                    'description' => __('Enable debug mode for detailed logging.', 'wp-donation-system'),
                    'default' => false
                ],
                'delete_data' => [
                    'title' => __('Delete Data on Uninstall', 'wp-donation-system'),
                    'type' => 'checkbox',
                    'description' => __('Delete all plugin data when uninstalling.', 'wp-donation-system'),
                    'default' => false
                ],
                'rate_limiting' => [
                    'title' => __('Rate Limiting', 'wp-donation-system'),
                    'type' => 'checkbox',
                    'description' => __('Enable rate limiting for donation submissions.', 'wp-donation-system'),
                    'default' => true
                ],
            ]
        ]);

        // Add Debug group
        $this->register_settings_group('debug', [
            'title' => __('Debug', 'wp-donation-system'),
            'description' => __('Debug information and tools', 'wp-donation-system'),
            'template' => 'debug',
            'custom_render' => true
        ]);

        // Allow other plugins to register settings groups
        do_action('wp_donation_system_register_settings_groups', $this);
    }

    public function register_settings_group($group_id, $args) {
        $this->settings_groups[$group_id] = $args;
    }

    public function get_settings_group($group_id) {
        return $this->settings_groups[$group_id] ?? null;
    }

    public function get_all_settings_groups() {
        return $this->settings_groups;
    }

    public function get_settings($group_id) {
        return get_option('wp_donation_system_' . $group_id . '_settings', []);
    }

    public function save_settings($group_id, $settings) {
        return update_option('wp_donation_system_' . $group_id . '_settings', $settings);
    }

    public function get_setting($key, $group, $default = '') {
        $settings = $this->get_settings($group);
        return isset($settings[$key]) ? $settings[$key] : $default;
    }
} 