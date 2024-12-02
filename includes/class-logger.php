<?php
class WP_Donation_System_Logger {
    public function log($message, $type = 'info') {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $log_file = WP_CONTENT_DIR . '/donation-system-logs/debug.log';
        $timestamp = current_time('mysql');
        
        if (!file_exists(dirname($log_file))) {
            wp_mkdir_p(dirname($log_file));
        }
        
        error_log("[$timestamp] [$type] $message\n", 3, $log_file);
    }
} 