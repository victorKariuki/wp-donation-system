class WP_Donation_System_Donation {
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';
    
    public function update_status($donation_id, $new_status) {
        global $wpdb;
        
        $old_status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM {$wpdb->prefix}donations WHERE id = %d",
            $donation_id
        ));
        
        if ($old_status === $new_status) {
            return;
        }
        
        $wpdb->update(
            $wpdb->prefix . 'donations',
            array('status' => $new_status),
            array('id' => $donation_id)
        );
        
        do_action('wp_donation_status_' . $new_status, $donation_id);
        do_action('wp_donation_status_changed', $donation_id, $old_status, $new_status);
    }
} 