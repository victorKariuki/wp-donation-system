<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body>
    <h2><?php _e('Thank You for Your Donation!', 'wp-donation-system'); ?></h2>
    <p><?php printf(__('Dear %s,', 'wp-donation-system'), esc_html($donation_data['donor_name'])); ?></p>
    <p><?php _e('We have received your donation of', 'wp-donation-system'); ?> 
       <?php echo esc_html($donation_data['currency']) . ' ' . number_format($donation_data['amount'], 2); ?></p>
    <p><?php _e('Donation Reference:', 'wp-donation-system'); ?> #<?php echo esc_html($donation_data['id']); ?></p>
    <p><?php _e('Payment Method:', 'wp-donation-system'); ?> <?php echo esc_html(ucfirst($donation_data['payment_method'])); ?></p>
</body>
</html>
