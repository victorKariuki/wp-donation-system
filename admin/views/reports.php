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
$table_name = $wpdb->prefix . 'donation_system_donations';

// Get time periods
$current_month_start = date('Y-m-01 00:00:00');
$current_month_end = date('Y-m-t 23:59:59');
$last_month_start = date('Y-m-01 00:00:00', strtotime('-1 month'));
$last_month_end = date('Y-m-t 23:59:59', strtotime('-1 month'));

// Get donation statistics
$stats = array(
    'total_donations' => $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
    
    'total_amount' => $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM {$table_name} WHERE status = %s",
        'completed'
    )),
    
    'this_month' => $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM {$table_name} 
        WHERE status = %s AND created_at BETWEEN %s AND %s",
        'completed', $current_month_start, $current_month_end
    )),
    
    'last_month' => $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(amount) FROM {$table_name} 
        WHERE status = %s AND created_at BETWEEN %s AND %s",
        'completed', $last_month_start, $last_month_end
    )),
    
    'payment_methods' => $wpdb->get_results(
        "SELECT payment_method, COUNT(*) as count, SUM(amount) as total 
        FROM {$table_name} 
        WHERE status = 'completed' 
        GROUP BY payment_method"
    ),
    
    'recurring_count' => $wpdb->get_var(
        "SELECT COUNT(*) FROM {$table_name} WHERE is_recurring = 1"
    ),
    
    'campaign_stats' => $wpdb->get_results(
        "SELECT campaign_id, COUNT(*) as count, SUM(amount) as total 
        FROM {$table_name} 
        WHERE campaign_id IS NOT NULL AND status = 'completed'
        GROUP BY campaign_id"
    )
);

// Get recent donations
$recent_donations = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT * FROM {$table_name} 
        ORDER BY created_at DESC 
        LIMIT %d",
        10
    )
);

// Initialize currency formatter
$currency = new WP_Donation_System_Currency();

// Update the currency formatting to use the saved currency
$settings_manager = WP_Donation_System_Settings_Manager::get_instance();
$default_currency = $settings_manager->get_setting('default_currency', 'general', 'USD');
?>

<div class="wrap donation-reports">
    <div class="reports-header">
        <h1><?php _e('Donation Reports', 'wp-donation-system'); ?></h1>
        <p><?php _e('Overview of your donation statistics and recent activity.', 'wp-donation-system'); ?></p>
    </div>

    <div class="donation-stats-grid">
        <div class="stat-box">
            <h3><?php _e('Total Donations', 'wp-donation-system'); ?></h3>
            <p class="stat-number"><?php echo number_format_i18n($stats['total_donations']); ?></p>
        </div>

        <div class="stat-box">
            <h3><?php _e('Total Amount', 'wp-donation-system'); ?></h3>
            <p class="stat-number">
                <?php echo esc_html($currency->format($stats['total_amount'] ?? 0, $default_currency)); ?>
            </p>
        </div>

        <div class="stat-box">
            <h3><?php _e('This Month', 'wp-donation-system'); ?></h3>
            <p class="stat-number">
                <?php echo esc_html($currency->format($stats['this_month'] ?? 0, $default_currency)); ?>
            </p>
            <?php if ($stats['last_month']): ?>
                <div class="stat-comparison">
                    <?php 
                    $change = (($stats['this_month'] - $stats['last_month']) / $stats['last_month']) * 100;
                    $icon = $change >= 0 ? 'arrow-up-alt2' : 'arrow-down-alt2';
                    $change_class = $change >= 0 ? 'positive' : 'negative';
                    ?>
                    <span class="<?php echo esc_attr($change_class); ?>">
                        <span class="dashicons dashicons-<?php echo esc_attr($icon); ?>"></span>
                        <?php printf('%.1f%%', abs($change)); ?>
                    </span>
                    <?php _e('vs last month', 'wp-donation-system'); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="stat-box">
            <h3><?php _e('Recurring Donations', 'wp-donation-system'); ?></h3>
            <p class="stat-number"><?php echo number_format_i18n($stats['recurring_count']); ?></p>
        </div>
    </div>

    <!-- Payment Methods Breakdown -->
    <div class="report-section">
        <h2><?php _e('Payment Methods', 'wp-donation-system'); ?></h2>
        <div class="payment-methods-grid">
            <?php foreach ($stats['payment_methods'] as $method): ?>
            <div class="method-box">
                <h4><?php echo esc_html(ucfirst($method->payment_method)); ?></h4>
                <p class="method-count"><?php echo number_format_i18n($method->count); ?> donations</p>
                <p class="method-total">
                    <?php echo esc_html($currency->format($method->total ?? 0, $default_currency)); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Donations -->
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
                            <td>
                                <?php if ($donation->is_anonymous): ?>
                                    <em><?php _e('Anonymous', 'wp-donation-system'); ?></em>
                                <?php else: ?>
                                    <?php echo esc_html($donation->donor_name); ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo esc_html($currency->format($donation->amount, $donation->currency)); ?></td>
                            <td><?php echo esc_html(ucfirst($donation->payment_method)); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo esc_attr($donation->status); ?>">
                                    <?php echo esc_html(ucfirst($donation->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date_i18n(
                                get_option('date_format') . ' ' . get_option('time_format'), 
                                strtotime($donation->created_at)
                            )); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="6"><?php _e('No donations found.', 'wp-donation-system'); ?></td>
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

.stat-comparison {
    font-size: 12px;
    margin: 5px 0 0;
    color: #666;
}

.stat-comparison .positive {
    color: #46b450;
}

.stat-comparison .negative {
    color: #dc3232;
}

.report-section {
    background: #fff;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    margin: 20px 0;
}

.payment-methods-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 15px;
}

.method-box {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 4px;
    text-align: center;
}

.method-box h4 {
    margin: 0 0 10px;
    color: #23282d;
}

.method-count {
    color: #666;
    margin: 5px 0;
}

.method-total {
    font-weight: bold;
    color: #0073aa;
    margin: 5px 0 0;
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

.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending {
    background: #f0f0f1;
    color: #50575e;
}

.status-completed {
    background: #edfaef;
    color: #1a7a2d;
}

.status-failed {
    background: #fcf0f1;
    color: #8a1f1f;
}

@media screen and (max-width: 782px) {
    .donation-stats-grid {
        grid-template-columns: 1fr;
    }
    
    .payment-methods-grid {
        grid-template-columns: 1fr;
    }
}
</style> 