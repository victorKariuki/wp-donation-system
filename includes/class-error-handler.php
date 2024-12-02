class WP_Donation_System_Error_Handler {
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
} 