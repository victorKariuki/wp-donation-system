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

try {
    // Load necessary WordPress files
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    global $wpdb;

    // Only proceed if delete_data setting is enabled
    $settings = get_option('wp_donation_system_settings', array());
    $delete_data = isset($settings['delete_data']) ? $settings['delete_data'] : false;

    // Initialize logger early for error tracking
    if (file_exists(dirname(__FILE__) . '/includes/class-logger.php')) {
        require_once dirname(__FILE__) . '/includes/class-logger.php';
        $logger = new WP_Donation_System_Logger();
    }

    if ($delete_data) {
        // Delete plugin tables
        $tables = array(
            $wpdb->prefix . 'donations',
            $wpdb->prefix . 'donation_logs',
            $wpdb->prefix . 'donation_meta'
        );

        foreach ($tables as $table) {
            $result = $wpdb->query("DROP TABLE IF EXISTS {$table}");
            if ($result === false) {
                throw new Exception("Failed to delete table: {$table}");
            }
        }

        // Delete plugin options
        $options = array(
            'wp_donation_system_settings',
            'wp_donation_system_version',
            'wp_donation_system_db_version',
            'wp_donation_system_installed'
        );

        foreach ($options as $option) {
            if (!delete_option($option)) {
                $logger->log("Failed to delete option: {$option}", 'warning');
            }
        }

        // Delete transients with error checking
        $transients_deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_wp_donation_system_%' 
            OR option_name LIKE '_transient_timeout_wp_donation_system_%'"
        );
        if ($transients_deleted === false) {
            throw new Exception('Failed to delete transients');
        }

        // Delete logs directory and files with error checking
        $upload_dir = wp_upload_dir();
        $logs_dir = $upload_dir['basedir'] . '/wp-donation-system/logs';
        
        if (is_dir($logs_dir)) {
            $files = glob($logs_dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (!unlink($file)) {
                        $logger->log("Failed to delete file: {$file}", 'warning');
                    }
                }
            }
            if (!rmdir($logs_dir)) {
                throw new Exception("Failed to delete logs directory: {$logs_dir}");
            }
        }

        // Log successful uninstallation
        if (isset($logger)) {
            $logger->log('Plugin uninstalled successfully', 'info', [
                'delete_data' => $delete_data,
                'timestamp' => current_time('mysql')
            ]);
        }
    }
} catch (Exception $e) {
    if (isset($logger)) {
        $logger->log('Uninstallation failed', 'error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    // Write to error log as fallback
    error_log('WP Donation System uninstallation failed: ' . $e->getMessage());
}
