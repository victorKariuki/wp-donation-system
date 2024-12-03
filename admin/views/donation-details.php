<?php
if (!defined('ABSPATH')) {
    exit;
}

// Get donation details
global $wpdb;
$donation = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}donations WHERE id = %d",
    $donation_id
));

if (!$donation) {
    wp_die(__('Donation not found.', 'wp-donation-system'));
}

require_once WP_DONATION_SYSTEM_PATH . 'includes/class-currency.php';
$currency = new WP_Donation_System_Currency();
?>

<div class="wrap donation-system-wrap">
    <div class="donation-header">
        <h1 class="wp-heading-inline">
            <?php printf(__('Donation #%d', 'wp-donation-system'), $donation_id); ?>
            <span class="status-badge status-<?php echo esc_attr($donation->status); ?>">
                <?php echo esc_html(ucfirst($donation->status)); ?>
            </span>
        </h1>
        
        <a href="<?php echo admin_url('admin.php?page=wp-donation-system'); ?>" class="page-title-action">
            <?php _e('← Back to Donations', 'wp-donation-system'); ?>
        </a>
    </div>

    <div class="donation-details-grid">
        <!-- Donor Information -->
        <div class="donation-card">
            <div class="card-header">
                <h2><?php _e('Donor Information', 'wp-donation-system'); ?></h2>
            </div>
            <div class="card-body">
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Name:', 'wp-donation-system'); ?></span>
                    <span class="detail-value"><?php echo esc_html($donation->donor_name); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Email:', 'wp-donation-system'); ?></span>
                    <span class="detail-value">
                        <a href="mailto:<?php echo esc_attr($donation->donor_email); ?>">
                            <?php echo esc_html($donation->donor_email); ?>
                        </a>
                    </span>
                </div>
                <?php if (!empty($donation->donor_phone)): ?>
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Phone:', 'wp-donation-system'); ?></span>
                    <span class="detail-value">
                        <a href="tel:<?php echo esc_attr($donation->donor_phone); ?>">
                            <?php echo esc_html($donation->donor_phone); ?>
                        </a>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Payment Information -->
        <div class="donation-card">
            <div class="card-header">
                <h2><?php _e('Payment Information', 'wp-donation-system'); ?></h2>
            </div>
            <div class="card-body">
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Amount:', 'wp-donation-system'); ?></span>
                    <span class="detail-value amount">
                        <?php echo esc_html($currency->format_amount($donation->amount, $donation->currency)); ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Payment Method:', 'wp-donation-system'); ?></span>
                    <span class="detail-value"><?php echo esc_html(ucfirst($donation->payment_method)); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Transaction ID:', 'wp-donation-system'); ?></span>
                    <span class="detail-value"><?php echo esc_html($donation->transaction_id); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><?php _e('Date:', 'wp-donation-system'); ?></span>
                    <span class="detail-value">
                        <?php echo date_i18n(
                            get_option('date_format') . ' ' . get_option('time_format'),
                            strtotime($donation->created_at)
                        ); ?>
                    </span>
                </div>
            </div>
        </div>

        <?php if (!empty($donation->notes)): ?>
        <!-- Notes -->
        <div class="donation-card">
            <div class="card-header">
                <h2><?php _e('Notes', 'wp-donation-system'); ?></h2>
            </div>
            <div class="card-body">
                <div class="donation-notes">
                    <?php echo wp_kses_post($donation->notes); ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="donation-card">
            <div class="card-header">
                <h2><?php _e('Actions', 'wp-donation-system'); ?></h2>
            </div>
            <div class="card-body">
                <div class="action-buttons">
                    <?php if ($donation->status === 'pending'): ?>
                    <a href="<?php echo wp_nonce_url(
                        admin_url(sprintf('admin.php?page=wp-donation-system&action=complete&id=%d', $donation_id)),
                        'complete_donation_' . $donation_id
                    ); ?>" class="button button-primary">
                        <?php _e('Mark as Completed', 'wp-donation-system'); ?>
                    </a>
                    <?php endif; ?>

                    <a href="<?php echo wp_nonce_url(
                        admin_url(sprintf('admin.php?page=wp-donation-system&action=delete&id=%d', $donation_id)),
                        'delete_donation_' . $donation_id
                    ); ?>" class="button button-link-delete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this donation?', 'wp-donation-system'); ?>');">
                        <?php _e('Delete Donation', 'wp-donation-system'); ?>
                    </a>

                    <a href="#" class="button button-secondary" onclick="window.print();">
                        <?php _e('Print Details', 'wp-donation-system'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* General Layout */
.donation-system-wrap {
    margin: 20px 0;
}

.donation-header {
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.donation-header h1 {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Grid Layout */
.donation-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

/* Card Styles */
.donation-card {
    background: #fff;
    border: 1px solid #e2e4e7;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.card-header {
    border-bottom: 1px solid #e2e4e7;
    padding: 15px 20px;
}

.card-header h2 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #1d2327;
}

.card-body {
    padding: 20px;
}

/* Detail Rows */
.detail-row {
    display: flex;
    margin-bottom: 12px;
    line-height: 1.5;
}

.detail-row:last-child {
    margin-bottom: 0;
}

.detail-label {
    font-weight: 600;
    min-width: 120px;
    color: #1d2327;
}

.detail-value {
    color: #50575e;
}

.detail-value.amount {
    font-size: 18px;
    font-weight: 600;
    color: #2271b1;
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #f0f0f1;
    color: #50575e;
}

.status-processing {
    background: #f0f6fc;
    color: #0a4b78;
}

.status-completed {
    background: #edfaef;
    color: #1a7a2d;
}

.status-failed {
    background: #fcf0f1;
    color: #8a1f1f;
}

/* Notes Section */
.donation-notes {
    background: #f6f7f7;
    padding: 15px;
    border-radius: 4px;
    color: #50575e;
}

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

/* Print Styles */
@media print {
    .donation-header a,
    .action-buttons {
        display: none !important;
    }

    .donation-card {
        break-inside: avoid;
        border: 1px solid #ddd;
        margin-bottom: 20px;
    }
}

/* Responsive Design */
@media screen and (max-width: 782px) {
    .donation-header {
        flex-direction: column;
        align-items: flex-start;
    }

    .detail-row {
        flex-direction: column;
    }

    .detail-label {
        margin-bottom: 4px;
    }

    .action-buttons {
        flex-direction: column;
    }

    .action-buttons .button {
        width: 100%;
        text-align: center;
    }
}
</style> 