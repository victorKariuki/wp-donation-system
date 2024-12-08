<div class="settings-group <?php echo esc_attr($group_id); ?>">
    <?php if (!empty($group['custom_render'])): ?>
        <?php 
        // Include custom template if specified
        if (!empty($group['template'])) {
            include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/' . $group['template'] . '.php';
        }
        ?>
    <?php else: ?>
        <form class="settings-form" method="post" data-group="<?php echo esc_attr($group_id); ?>">
            <div class="settings-description">
                <h2><?php echo esc_html($group['title']); ?></h2>
                <?php if (!empty($group['description'])): ?>
                    <p><?php echo esc_html($group['description']); ?></p>
                <?php endif; ?>
            </div>

            <div class="settings-grid">
                <?php foreach ($group['fields'] as $field_id => $field): 
                    $field_name = $group_id . '_' . $field_id;
                    $field_value = $settings[$field_id] ?? $field['default'] ?? '';
                ?>
                    <div class="field-group field-type-<?php echo esc_attr($field['type']); ?>">
                        <label for="<?php echo esc_attr($field_name); ?>" class="field-label">
                            <?php echo esc_html($field['title']); ?>
                            <?php if (!empty($field['required'])): ?>
                                <span class="required">*</span>
                            <?php endif; ?>
                        </label>

                        <div class="field-wrapper">
                            <?php include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/partials/settings-field.php'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-actions">
                <input type="hidden" name="settings_group" value="<?php echo esc_attr($group_id); ?>">
                <?php 
                // Remove ID from nonce field
                wp_nonce_field('wp_donation_system_admin', 'settings_nonce', false); 
                ?>

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
    <?php endif; ?>
</div> 