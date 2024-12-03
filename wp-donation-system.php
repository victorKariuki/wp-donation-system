<?php
/**
 * Plugin Name: WP Donation System
 * Description: A WordPress plugin for handling donations via M-Pesa
 * Version: 1.0.1
 * Author: Victor Kariuki
 * Author URI: https://github.com/victorKariuki
 * Plugin URI: https://github.com/victorKariuki/wp-donation-system
 * Text Domain: wp-donation-system
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_DONATION_SYSTEM_VERSION', '1.0.1');
define('WP_DONATION_SYSTEM_PATH', plugin_dir_path(__FILE__));
define('WP_DONATION_SYSTEM_URL', plugin_dir_url(__FILE__));

// Activation hook
register_activation_hook(__FILE__, 'wp_donation_system_activate');

function wp_donation_system_activate() {
    try {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            throw new Exception('This plugin requires WordPress version 5.0 or higher.');
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.2', '<')) {
            throw new Exception('This plugin requires PHP version 7.2 or higher.');
        }

        // Check if required PHP extensions are installed
        $required_extensions = array('curl', 'json', 'mbstring');
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                throw new Exception("Required PHP extension not found: {$ext}");
            }
        }

        // Create necessary database tables
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-database.php';
        $database = new WP_Donation_System_Database();
        $tables_created = $database->create_tables();
        if (!$tables_created) {
            throw new Exception('Failed to create database tables.');
        }
        
        $tables_updated = $database->update_tables();
        if (!$tables_updated) {
            throw new Exception('Failed to update database tables.');
        }

        // Create required directories
        $upload_dir = wp_upload_dir();
        $plugin_dir = $upload_dir['basedir'] . '/wp-donation-system';
        $logs_dir = $plugin_dir . '/logs';

        if (!wp_mkdir_p($logs_dir)) {
            throw new Exception('Failed to create logs directory.');
        }

        // Create .htaccess to protect logs
        $htaccess_content = "Order deny,allow\nDeny from all";
        if (!file_put_contents($logs_dir . '/.htaccess', $htaccess_content)) {
            throw new Exception('Failed to create .htaccess file.');
        }

        // Initialize logger and log successful activation
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        $logger = new WP_Donation_System_Logger();
        $logger->log('Plugin activated successfully', 'info', [
            'version' => WP_DONATION_SYSTEM_VERSION,
            'php_version' => PHP_VERSION,
            'wp_version' => get_bloginfo('version')
        ]);

    } catch (Exception $e) {
        // Log the error if logger is available
        if (class_exists('WP_Donation_System_Logger')) {
            $logger = new WP_Donation_System_Logger();
            $logger->log('Activation failed', 'error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Deactivate the plugin
        deactivate_plugins(plugin_basename(__FILE__));

        // Display error message
        wp_die(
            'Plugin activation failed: ' . $e->getMessage() . 
            '<br><br><a href="' . admin_url('plugins.php') . '">&laquo; Return to Plugins</a>'
        );
    }
}

// Load core classes first
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-form-validator.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-error-handler.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-currency.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-rate-limiter.php';

// Load feature classes
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-donation-form.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/gateways/class-mpesa.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-notifications.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-callbacks.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-updater.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-ajax.php';

// Load admin classes
require_once WP_DONATION_SYSTEM_PATH . 'admin/class-admin.php';
require_once WP_DONATION_SYSTEM_PATH . 'admin/class-donations-list-table.php';

// Initialize plugin
function wp_donation_system_init() {
    new WP_Donation_System_Form();
    new WP_Donation_System_MPesa();
    new WP_Donation_System_Notifications();
    new WP_Donation_System_Admin();
    new WP_Donation_System_Callbacks();
    new WP_Donation_System_Updater();

    // Add AJAX actions
    add_action('wp_ajax_process_donation', array(new WP_Donation_System_Ajax(), 'process_donation'));
    add_action('wp_ajax_nopriv_process_donation', array(new WP_Donation_System_Ajax(), 'process_donation'));
    add_action('wp_ajax_check_donation_status', array(new WP_Donation_System_Ajax(), 'check_donation_status'));
    add_action('wp_ajax_nopriv_check_donation_status', array(new WP_Donation_System_Ajax(), 'check_donation_status'));
}
add_action('plugins_loaded', 'wp_donation_system_init');

// Add to the existing register_activation_hook
register_activation_hook(__FILE__, function() {
    // Flush rewrite rules to ensure our endpoint works
    flush_rewrite_rules();
});

// Add this near the top of the file with other add_action calls
add_action('rest_api_init', function() {
    register_rest_route('wp-donation-system/v1', '/mpesa-callback', array(
        'methods' => 'POST',
        'callback' => array('WP_Donation_System_Callbacks', 'handle_mpesa_callback'),
        'permission_callback' => '__return_true'
    ));
});