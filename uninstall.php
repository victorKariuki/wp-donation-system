<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
$options = array(
    'wp_donation_test_mode',
    'wp_donation_paypal_client_id',
    'wp_donation_paypal_client_secret',
    'wp_donation_mpesa_consumer_key',
    'wp_donation_mpesa_consumer_secret',
    'wp_donation_mpesa_shortcode',
    'wp_donation_mpesa_passkey'
);

foreach ($options as $option) {
    delete_option($option);
}

// Drop custom tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}donations");

// Remove log files
$log_dir = WP_CONTENT_DIR . '/donation-system-logs';
if (is_dir($log_dir)) {
    array_map('unlink', glob("$log_dir/*.*"));
    rmdir($log_dir);
}
