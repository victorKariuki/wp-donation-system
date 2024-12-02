<?php
/**
 * Plugin Name: WP Donation System
 * Description: A WordPress plugin for handling donations via PayPal and M-Pesa
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: wp-donation-system
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_DONATION_SYSTEM_VERSION', '1.0.0');
define('WP_DONATION_SYSTEM_PATH', plugin_dir_path(__FILE__));
define('WP_DONATION_SYSTEM_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'wp_donation_system_activate');

function wp_donation_system_activate() {
    // Create necessary database tables
    require_once WP_DONATION_SYSTEM_PATH . 'includes/class-database.php';
    $database = new WP_Donation_System_Database();
    $database->create_tables();
}

// Load plugin classes
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-donation-form.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-paypal.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-mpesa.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-notifications.php';
require_once WP_DONATION_SYSTEM_PATH . 'admin/class-admin.php';
require_once WP_DONATION_SYSTEM_PATH . 'admin/class-donations-list-table.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-export.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-callbacks.php';

// Initialize plugin
function wp_donation_system_init() {
    new WP_Donation_System_Form();
    new WP_Donation_System_PayPal();
    new WP_Donation_System_MPesa();
    new WP_Donation_System_Notifications();
    new WP_Donation_System_Admin();
    new WP_Donation_System_Callbacks();
}
add_action('plugins_loaded', 'wp_donation_system_init');