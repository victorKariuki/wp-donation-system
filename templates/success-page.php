<?php
if (!defined('ABSPATH')) {
    exit;
}

$donation_id = isset($_GET['donation_id']) ? intval($_GET['donation_id']) : 0;
$donation = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}donations WHERE id = %d",
    $donation_id
));
?>

<div class="donation-success">
    <div class="success-icon">
        <svg viewBox="0 0 24 24" width="64" height="64">
            <path fill="#28a745" d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/>
        </svg>
    </div>

    <h2><?php _e('Thank You for Your Donation!', 'wp-donation-system'); ?></h2>
    
    <?php if ($donation): ?>
        <div class="donation-details">
            <div class="detail-item">
                <span class="label"><?php _e('Amount:', 'wp-donation-system'); ?></span>
                <span class="value"><?php echo esc_html($donation->currency) . ' ' . number_format($donation->amount, 2); ?></span>
            </div>
            <div class="detail-item">
                <span class="label"><?php _e('Reference:', 'wp-donation-system'); ?></span>
                <span class="value">#<?php echo esc_html($donation->id); ?></span>
            </div>
            <div class="detail-item">
                <span class="label"><?php _e('Date:', 'wp-donation-system'); ?></span>
                <span class="value"><?php echo date_i18n(get_option('date_format'), strtotime($donation->created_at)); ?></span>
            </div>
        </div>

        <div class="email-notice">
            <p><?php _e('A confirmation email has been sent to your email address.', 'wp-donation-system'); ?></p>
        </div>
    <?php endif; ?>

    <div class="return-link">
        <a href="<?php echo esc_url(home_url()); ?>" class="button">
            <?php _e('Return to Homepage', 'wp-donation-system'); ?>
        </a>
    </div>
</div>

<style>
.donation-success {
    max-width: 600px;
    margin: 3em auto;
    padding: 40px;
    text-align: center;
    background: #fff;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

.success-icon {
    margin-bottom: 20px;
}

.donation-success h2 {
    color: #28a745;
    margin-bottom: 30px;
}

.donation-details {
    margin: 30px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #dee2e6;
}

.detail-item:last-child {
    border-bottom: none;
}

.detail-item .label {
    font-weight: 600;
    color: #6c757d;
}

.email-notice {
    margin: 20px 0;
    padding: 15px;
    background: #e8f4ff;
    border-radius: 4px;
    color: #004085;
}

.return-link {
    margin-top: 30px;
}

.return-link .button {
    display: inline-block;
    padding: 12px 24px;
    background: #0073aa;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.return-link .button:hover {
    background: #005177;
}
</style>
