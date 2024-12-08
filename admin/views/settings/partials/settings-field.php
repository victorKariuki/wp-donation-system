<?php switch ($field['type']):
    case 'select': ?>
        <select name="<?php echo esc_attr($field_name); ?>" 
            id="<?php echo esc_attr($field_name); ?>" 
            class="regular-text"
            <?php echo !empty($field['required']) ? 'required' : ''; ?>>
            <?php foreach ($field['options'] as $option_value => $option_label): ?>
                <option value="<?php echo esc_attr($option_value); ?>" 
                    <?php selected($field_value, $option_value); ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php break;

    case 'textarea': ?>
        <textarea name="<?php echo esc_attr($field_name); ?>" 
            id="<?php echo esc_attr($field_name); ?>" 
            class="large-text code"
            rows="5"
            <?php echo !empty($field['required']) ? 'required' : ''; ?>><?php echo esc_textarea($field_value); ?></textarea>
        <?php break;

    case 'checkbox': ?>
        <label class="switch">
            <input type="checkbox" 
                name="<?php echo esc_attr($field_name); ?>" 
                id="<?php echo esc_attr($field_name); ?>" 
                value="1"
                <?php checked($field_value); ?>>
            <span class="slider round"></span>
        </label>
        <?php break;

    default: ?>
        <input type="<?php echo esc_attr($field['type']); ?>" 
            name="<?php echo esc_attr($field_name); ?>" 
            id="<?php echo esc_attr($field_name); ?>" 
            value="<?php echo esc_attr($field_value); ?>" 
            class="regular-text"
            <?php echo !empty($field['required']) ? 'required' : ''; ?>
            <?php if (isset($field['min'])): ?>min="<?php echo esc_attr($field['min']); ?>"<?php endif; ?>
            <?php if (isset($field['max'])): ?>max="<?php echo esc_attr($field['max']); ?>"<?php endif; ?>
            <?php if (!empty($field['placeholder'])): ?>placeholder="<?php echo esc_attr($field['placeholder']); ?>"<?php endif; ?>>
<?php endswitch; ?>

<?php if (!empty($field['description'])): ?>
    <p class="description"><?php echo esc_html($field['description']); ?></p>
<?php endif; ?> 