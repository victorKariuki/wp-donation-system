<div class="accordion-content">
    <form class="gateway-settings-form" method="post" data-gateway="<?php echo esc_attr($gateway_id); ?>">
        <input type="hidden" name="<?php echo esc_attr($gateway_id); ?>_enabled" value="0">

        <div class="gateway-description">
            <p><?php echo esc_html($gateway->get_description()); ?></p>
        </div>

        <div class="settings-grid">
            <?php
            $fields = $gateway->get_settings_fields();
            foreach ($fields as $field_id => $field):
                if ($field_id === 'enabled')
                    continue;

                $field_name = $gateway_id . '_' . $field_id;
                $field_value = $gateway_settings[$field_id] ?? ($field['default'] ?? '');
                $field_desc = !empty($field['description']) ? $field['description'] : '';
                $is_required = !empty($field['required']);
                ?>
                <div class="field-group <?php echo esc_attr('field-type-' . $field['type']); ?>">
                    <label for="<?php echo esc_attr($field_name); ?>" class="field-label">
                        <?php echo esc_html($field['title']); ?>
                        <?php if ($is_required): ?>
                            <span class="required">*</span>
                        <?php endif; ?>
                    </label>

                    <div class="field-wrapper">
                        <?php include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/partials/gateway-field.php'; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="form-actions">
            <input type="hidden" name="gateway" value="<?php echo esc_attr($gateway_id); ?>">
            <?php wp_nonce_field('wp_donation_system_admin', 'gateway_settings_nonce', false); ?>

            <?php if ($gateway->has_test_mode()): ?>
                <button type="button" class="button test-connection" data-gateway="<?php echo esc_attr($gateway_id); ?>">
                    <span class="dashicons dashicons-marker"></span>
                    <?php _e('Test Connection', 'wp-donation-system'); ?>
                </button>
            <?php endif; ?>

            <div class="primary-actions">
                <button type="button" class="button reset-settings">
                    <span class="dashicons dashicons-image-rotate"></span>
                    <?php _e('Reset to Defaults', 'wp-donation-system'); ?>
                </button>
                <button type="submit" class="button button-primary save-settings">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save Changes', 'wp-donation-system'); ?>
                </button>
            </div>
        </div>
    </form>
</div>