<?php
if (!defined('ABSPATH')) {
    exit;
}

global $donation_message, $donation_type;
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Donations', 'wp-donation-system'); ?></h1>
    
    <?php
    // Display admin notices
    if (!empty($donation_message)) {
        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr($donation_type),
            esc_html($donation_message)
        );
    }
    ?>

    <!-- Analytics Dashboard -->
    <div class="analytics-dashboard">
        <!-- Primary Stats -->
        <div class="stats-row primary-stats">
            <div class="stat-card total-donations">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo number_format_i18n($donations_table->get_total_donations()); ?></span>
                    <span class="stat-label"><?php _e('Total Donations', 'wp-donation-system'); ?></span>
                </div>
            </div>
            <div class="stat-card total-amount">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $donations_table->get_total_amount(); ?></span>
                    <span class="stat-label"><?php _e('Total Amount', 'wp-donation-system'); ?></span>
                </div>
            </div>
            <div class="stat-card monthly-amount">
                <div class="stat-icon">📅</div>
                <div class="stat-content">
                    <span class="stat-value"><?php echo $donations_table->get_monthly_amount(); ?></span>
                    <span class="stat-label"><?php _e('This Month', 'wp-donation-system'); ?></span>
                </div>
            </div>
        </div>

        <!-- Secondary Stats -->
        <div class="stats-row secondary-stats">
            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-value"><?php echo $donations_table->get_completed_count(); ?></span>
                    <span class="stat-label"><?php _e('Completed', 'wp-donation-system'); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-value"><?php echo $donations_table->get_pending_count(); ?></span>
                    <span class="stat-label"><?php _e('Pending', 'wp-donation-system'); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-value"><?php echo $donations_table->get_average_amount(); ?></span>
                    <span class="stat-label"><?php _e('Average', 'wp-donation-system'); ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-content">
                    <span class="stat-value"><?php echo $donations_table->get_success_rate(); ?>%</span>
                    <span class="stat-label"><?php _e('Success Rate', 'wp-donation-system'); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="tablenav top">
        <form method="get" class="donations-filter">
            <input type="hidden" name="page" value="wp-donation-system">
            
            <select name="status">
                <option value=""><?php _e('All Statuses', 'wp-donation-system'); ?></option>
                <option value="pending" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'pending'); ?>>
                    <?php _e('Pending', 'wp-donation-system'); ?>
                </option>
                <option value="completed" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'completed'); ?>>
                    <?php _e('Completed', 'wp-donation-system'); ?>
                </option>
                <option value="failed" <?php selected(isset($_GET['status']) ? $_GET['status'] : '', 'failed'); ?>>
                    <?php _e('Failed', 'wp-donation-system'); ?>
                </option>
            </select>

            <select name="payment_method">
                <option value=""><?php _e('All Payment Methods', 'wp-donation-system'); ?></option>
                <option value="mpesa" <?php selected(isset($_GET['payment_method']) ? $_GET['payment_method'] : '', 'mpesa'); ?>>
                    <?php _e('M-Pesa', 'wp-donation-system'); ?>
                </option>
            </select>

            <input type="date" name="start_date" value="<?php echo isset($_GET['start_date']) ? esc_attr($_GET['start_date']) : ''; ?>" placeholder="<?php _e('Start Date', 'wp-donation-system'); ?>">
            <input type="date" name="end_date" value="<?php echo isset($_GET['end_date']) ? esc_attr($_GET['end_date']) : ''; ?>" placeholder="<?php _e('End Date', 'wp-donation-system'); ?>">

            <?php submit_button(__('Filter', 'wp-donation-system'), 'secondary', 'filter', false); ?>
            <a href="<?php echo admin_url('admin.php?page=wp-donation-system'); ?>" class="button button-secondary">
                <?php _e('Reset', 'wp-donation-system'); ?>
            </a>
        </form>
    </div>

    <!-- Donations List -->
    <form id="donations-filter" method="post">
        <?php
        wp_nonce_field('bulk-' . $donations_table->_args['plural']);
        $donations_table->prepare_items();
        $donations_table->display();
        ?>
    </form>
</div>

<style>
/* Analytics Dashboard */
.analytics-dashboard {
    margin: 20px 0;
}

.stats-row {
    display: grid;
    gap: 20px;
    margin-bottom: 20px;
}

.primary-stats {
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
}

.secondary-stats {
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
}

/* Stat Cards */
.stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.primary-stats .stat-card {
    background: linear-gradient(145deg, #fff 0%, #f8f9fa 100%);
}

.stat-icon {
    font-size: 24px;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(33, 113, 177, 0.1);
    border-radius: 8px;
}

.stat-content {
    flex: 1;
}

.stat-value {
    display: block;
    font-size: 24px;
    font-weight: 600;
    color: #2271b1;
    line-height: 1.2;
}

.stat-label {
    display: block;
    font-size: 13px;
    color: #666;
    margin-top: 4px;
}

/* Status Colors */
.total-donations .stat-value { color: #2271b1; }
.total-amount .stat-value { color: #00a32a; }
.monthly-amount .stat-value { color: #8c5e58; }

/* Filter Form */
.donations-filter {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.donations-filter select,
.donations-filter input[type="date"] {
    max-width: 200px;
}

/* Status Badges */
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending { background: #f0f0f1; color: #50575e; }
.status-completed { background: #edfaef; color: #1a7a2d; }
.status-failed { background: #fcf0f1; color: #8a1f1f; }

/* Responsive Design */
@media screen and (max-width: 782px) {
    .stats-row {
        grid-template-columns: 1fr;
    }

    .donations-filter {
        flex-direction: column;
        align-items: stretch;
    }

    .donations-filter select,
    .donations-filter input[type="date"] {
        max-width: 100%;
    }

    .stat-card {
        padding: 15px;
    }

    .stat-value {
        font-size: 20px;
    }
}
</style> 