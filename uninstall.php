<?php
/**
 * Uninstall WP Donation System
 *
 * @package WP_Donation_System
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Get settings manager
require_once plugin_dir_path(__FILE__) . 'includes/class-settings-manager.php';
$settings_manager = WP_Donation_System_Settings_Manager::get_instance();

// Check if we should delete data
$advanced_settings = $settings_manager->get_settings('advanced');
$delete_data = isset($advanced_settings['delete_data']) ? $advanced_settings['delete_data'] : false;

if ($delete_data) {
    global $wpdb;
    
    // Delete settings
    delete_option('wp_donation_system_general_settings');
    delete_option('wp_donation_system_payment_settings');
    delete_option('wp_donation_system_email_settings');
    delete_option('wp_donation_system_advanced_settings');
    
    // Drop tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}donation_system_donations");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}donation_system_logs");
}
