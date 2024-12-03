<?php
/**
 * Reports Page View
 *
 * @package WP_Donation_System
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Get donation statistics
$total_donations = $wpdb->get_var(
    "SELECT COUNT(*) FROM {$wpdb->prefix}donations"
);

$total_amount = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT SUM(amount) FROM {$wpdb->prefix}donations WHERE status = %s",
        'completed'
    )
);

$mpesa_donations = $wpdb->get_var(
    $wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}donations WHERE payment_method = %s",
        'mpesa'
    )
);

// Get recent donations
$recent_donations = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}donations 
        ORDER BY created_at DESC 
        LIMIT %d",
        5
    )
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Donation Reports', 'wp-donation-system'); ?></h1>
    <hr class="wp-header-end">

    <div class="donation-stats-grid">
        <div class="stat-box">
            <h3><?php _e('Total Donations', 'wp-donation-system'); ?></h3>
            <p class="stat-number"><?php echo esc_html($total_donations); ?></p>
        </div>

        <div class="stat-box">
            <h3><?php _e('Total Amount', 'wp-donation-system'); ?></h3>
            <p class="stat-number">
                <?php 
                $currency = new WP_Donation_System_Currency();
                echo esc_html($currency->format_amount($total_amount ?? 0));
                ?>
            </p>
        </div>

        <div class="stat-box">
            <h3><?php _e('M-Pesa Donations', 'wp-donation-system'); ?></h3>
            <p class="stat-number"><?php echo esc_html($mpesa_donations); ?></p>
        </div>
    </div>

    <div class="recent-donations">
        <h2><?php _e('Recent Donations', 'wp-donation-system'); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Donor', 'wp-donation-system'); ?></th>
                    <th><?php _e('Amount', 'wp-donation-system'); ?></th>
                    <th><?php _e('Method', 'wp-donation-system'); ?></th>
                    <th><?php _e('Status', 'wp-donation-system'); ?></th>
                    <th><?php _e('Date', 'wp-donation-system'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recent_donations) : ?>
                    <?php foreach ($recent_donations as $donation) : ?>
                        <tr>
                            <td><?php echo esc_html($donation->donor_name); ?></td>
                            <td><?php echo esc_html($currency->format_amount($donation->amount)); ?></td>
                            <td><?php echo esc_html(ucfirst($donation->payment_method)); ?></td>
                            <td><?php echo esc_html(ucfirst($donation->status)); ?></td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($donation->created_at))); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5"><?php _e('No donations found.', 'wp-donation-system'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.donation-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-box {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.stat-box h3 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    margin: 0;
    color: #0073aa;
}

.recent-donations {
    margin-top: 30px;
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.recent-donations h2 {
    margin-top: 0;
}
</style> 