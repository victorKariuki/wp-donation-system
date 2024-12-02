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
    <h2><?php _e('New Donation Received', 'wp-donation-system'); ?></h2>
    <p><?php _e('Donation Details:', 'wp-donation-system'); ?></p>
    <ul>
        <li><?php _e('Donor:', 'wp-donation-system'); ?> <?php echo esc_html($donation_data['donor_name']); ?></li>
        <li><?php _e('Email:', 'wp-donation-system'); ?> <?php echo esc_html($donation_data['donor_email']); ?></li>
        <li><?php _e('Amount:', 'wp-donation-system'); ?> <?php echo esc_html($donation_data['currency']) . ' ' . number_format($donation_data['amount'], 2); ?></li>
        <li><?php _e('Payment Method:', 'wp-donation-system'); ?> <?php echo esc_html(ucfirst($donation_data['payment_method'])); ?></li>
        <li><?php _e('Transaction ID:', 'wp-donation-system'); ?> <?php echo esc_html($donation_data['transaction_id']); ?></li>
    </ul>
</body>
</html>
