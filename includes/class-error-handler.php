<?php
/**
 * Error Handler Class
 *
 * @package WP_Donation_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Donation_System_Error_Handler {
    /**
     * Handle errors consistently across the plugin
     *
     * @param string $error_code Error code
     * @param string $message Error message
     * @param array $data Additional error data
     * @return WP_Error|void Returns WP_Error or sends JSON response for AJAX requests
     */
    public function handle_error($error_code, $message, $data = array()) {
        $logger = new WP_Donation_System_Logger();
        $logger->log("Error {$error_code}: {$message}", 'error');
        
        if (wp_doing_ajax()) {
            wp_send_json_error(array(
                'code' => $error_code,
                'message' => $message,
                'data' => $data
            ));
        }
        
        return new WP_Error($error_code, $message, $data);
    }

    /**
     * Get error message for error code
     *
     * @param string $error_code Error code
     * @return string Error message
     */
    public function get_error_message($error_code) {
        $messages = array(
            'ERR_001' => __('Invalid API credentials', 'wp-donation-system'),
            'ERR_002' => __('Payment processing failed', 'wp-donation-system'),
            'ERR_003' => __('Database connection error', 'wp-donation-system'),
            'ERR_004' => __('Invalid form data', 'wp-donation-system'),
            'ERR_005' => __('Payment gateway error', 'wp-donation-system'),
        );

        return $messages[$error_code] ?? __('Unknown error occurred', 'wp-donation-system');
    }
} 