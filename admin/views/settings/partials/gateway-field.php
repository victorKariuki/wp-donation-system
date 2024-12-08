<?php switch ($field['type']):
    case 'select': ?>
        <select name="<?php echo esc_attr($field_name); ?>" 
            id="<?php echo esc_attr($field_name); ?>" 
            class="regular-text"
            <?php echo $is_required ? 'required' : ''; ?>>
            <?php foreach ($field['options'] as $option_value => $option_label): ?>
                <option value="<?php echo esc_attr($option_value); ?>" 
                    <?php selected($field_value, $option_value); ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php break;
    
    case 'password': ?>
        <div class="password-field">
            <input type="password" 
                name="<?php echo esc_attr($field_name); ?>" 
                id="<?php echo esc_attr($field_name); ?>" 
                value="<?php echo esc_attr($field_value); ?>" 
                class="regular-text"
                <?php echo $is_required ? 'required' : ''; ?>>
            <button type="button" class="button toggle-password" tabindex="-1">
                <span class="dashicons dashicons-visibility"></span>
            </button>
        </div>
        <?php break;
    
    default: ?>
        <input type="<?php echo esc_attr($field['type']); ?>" 
            name="<?php echo esc_attr($field_name); ?>" 
            id="<?php echo esc_attr($field_name); ?>" 
            value="<?php echo esc_attr($field_value); ?>" 
            class="regular-text"
            <?php echo $is_required ? 'required' : ''; ?>
            <?php echo !empty($field['placeholder']) ? 'placeholder="' . esc_attr($field['placeholder']) . '"' : ''; ?>>
<?php endswitch; ?>

<?php if (!empty($field['description'])): ?>
    <p class="description"><?php echo esc_html($field['description']); ?></p>
<?php endif; ?> 