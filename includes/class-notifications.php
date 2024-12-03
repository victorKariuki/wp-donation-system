<?php
class WP_Donation_System_Notifications {
    private $settings;
    private $logger;

    public function __construct() {
        $this->settings = get_option('wp_donation_system_settings', array());
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        $this->logger = new WP_Donation_System_Logger();
    }

    public function send_payment_confirmation($donation_id) {
        try {
            global $wpdb;
            
            // Get donation details
            $donation = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}donations WHERE id = %d",
                $donation_id
            ));

            if (!$donation) {
                throw new Exception("Donation not found: {$donation_id}");
            }

            // Send donor notification
            $donor_subject = sprintf(
                __('Thank you for your donation of %s %s', 'wp-donation-system'),
                $donation->currency,
                number_format($donation->amount, 2)
            );

            $donor_message = $this->get_donor_email_template($donation);
            
            $sent = wp_mail(
                $donation->donor_email,
                $donor_subject,
                $donor_message,
                $this->get_email_headers()
            );

            if (!$sent) {
                throw new Exception("Failed to send donor notification");
            }

            // Send admin notification
            if ($this->settings['admin_notifications'] ?? true) {
                $admin_email = $this->settings['admin_email'] ?? get_option('admin_email');
                
                $admin_subject = sprintf(
                    __('New donation received: %s %s', 'wp-donation-system'),
                    $donation->currency,
                    number_format($donation->amount, 2)
                );

                $admin_message = $this->get_admin_email_template($donation);
                
                wp_mail(
                    $admin_email,
                    $admin_subject,
                    $admin_message,
                    $this->get_email_headers()
                );
            }

            $this->logger->log('Payment notifications sent', 'info', [
                'donation_id' => $donation_id,
                'donor_email' => $donation->donor_email
            ]);

        } catch (Exception $e) {
            $this->logger->log('Failed to send notifications', 'error', [
                'error' => $e->getMessage(),
                'donation_id' => $donation_id
            ]);
        }
    }

    private function get_email_headers() {
        $from_name = $this->settings['email_from_name'] ?? get_bloginfo('name');
        $from_email = $this->settings['email_from_email'] ?? get_option('admin_email');
        
        return [
            'Content-Type: text/html; charset=UTF-8',
            "From: {$from_name} <{$from_email}>"
        ];
    }

    private function get_donor_email_template($donation) {
        // Load and return donor email template
        ob_start();
        include WP_DONATION_SYSTEM_PATH . 'templates/emails/donor-confirmation.php';
        return ob_get_clean();
    }

    private function get_admin_email_template($donation) {
        // Load and return admin email template
        ob_start();
        include WP_DONATION_SYSTEM_PATH . 'templates/emails/admin-notification.php';
        return ob_get_clean();
    }
}
