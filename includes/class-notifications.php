<?php
class WP_Donation_System_Notifications {
    public function send_donor_notification($donation_data) {
        $to = $donation_data['donor_email'];
        $subject = __('Thank you for your donation!', 'wp-donation-system');
        
        ob_start();
        include WP_DONATION_SYSTEM_PATH . 'templates/email-templates/donor-notification.php';
        $message = ob_get_clean();
        
        wp_mail($to, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }

    public function send_admin_notification($donation_data) {
        $to = get_option('admin_email');
        $subject = __('New donation received', 'wp-donation-system');
        
        ob_start();
        include WP_DONATION_SYSTEM_PATH . 'templates/email-templates/admin-notification.php';
        $message = ob_get_clean();
        
        wp_mail($to, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
    }
}
