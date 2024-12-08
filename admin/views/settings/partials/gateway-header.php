<div class="accordion-header">
    <div class="gateway-info">
        <img src="<?php echo esc_url(WP_DONATION_SYSTEM_URL . 'assets/images/' . $gateway_id . '.png'); ?>" 
            alt="" 
            class="gateway-icon">
        <div class="gateway-title">
            <h3><?php echo esc_html($gateway->get_title()); ?></h3>
            <span class="status-badge <?php echo $is_enabled ? 'enabled' : 'disabled'; ?>">
                <?php echo $is_enabled ? esc_html__('Enabled', 'wp-donation-system') : esc_html__('Disabled', 'wp-donation-system'); ?>
            </span>
        </div>
    </div>
    <div class="accordion-actions">
        <label class="toggle-switch">
            <input type="checkbox" 
                class="gateway-toggle"
                data-gateway="<?php echo esc_attr($gateway_id); ?>"
                <?php checked($is_enabled); ?>>
            <span class="toggle-slider"></span>
        </label>
        <button type="button" class="accordion-toggle">
            <span class="dashicons dashicons-arrow-down-alt2"></span>
        </button>
    </div>
</div> 