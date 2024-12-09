<div class="wp-donation-form">
    <?php if (get_option('wp_donation_test_mode', false)): ?>
        <div class="test-mode-notice">
            <p><?php _e('Test Mode Enabled - No real payments will be processed', 'wp-donation-system'); ?></p>
        </div>
    <?php endif; ?>

    <!-- Progress Steps -->
    <div class="progress-steps">
        <div class="step active" data-step="1">
            <div class="step-circle">1</div>
            <div class="step-label"><?php _e('Amount', 'wp-donation-system'); ?></div>
        </div>
        <div class="step" data-step="2">
            <div class="step-circle">2</div>
            <div class="step-label"><?php _e('Your Info', 'wp-donation-system'); ?></div>
        </div>
        <div class="step" data-step="3">
            <div class="step-circle">3</div>
            <div class="step-label"><?php _e('Payment', 'wp-donation-system'); ?></div>
        </div>
    </div>

    <form id="donation-form" method="post" onsubmit="return false;">
        <?php wp_nonce_field('process_donation', 'donation_nonce'); ?>
        <input type="hidden" name="action" value="process_donation">

        <!-- Step 1: Amount Selection -->
        <div class="form-step active" data-step="1">
            <div class="form-section">
                <h2><?php _e('Select Amount', 'wp-donation-system'); ?></h2>
                
                <!-- Preset Amounts -->
                <div class="amount-presets">
                    <button type="button" class="amount-preset" data-amount="100">KSh 100</button>
                    <button type="button" class="amount-preset" data-amount="500">KSh 500</button>
                    <button type="button" class="amount-preset" data-amount="1000">KSh 1,000</button>
                    <button type="button" class="amount-preset" data-amount="5000">KSh 5,000</button>
                </div>

                <!-- Custom Amount -->
                <div class="custom-amount-wrapper">
                    <label for="donation_amount"><?php _e('Custom Amount', 'wp-donation-system'); ?></label>
                    <div class="amount-input">
                        <span class="currency">KSh</span>
                        <input type="number" 
                            id="donation_amount" 
                            name="donation_amount" 
                            min="<?php echo esc_attr($min_amount); ?>" 
                            max="<?php echo esc_attr($max_amount); ?>" 
                            step="1" 
                            required
                            placeholder="Enter amount">
                    </div>
                    <div class="amount-hint">
                        <?php printf(
                            __('Min: KSh %s | Max: KSh %s', 'wp-donation-system'),
                            number_format($min_amount),
                            number_format($max_amount)
                        ); ?>
                    </div>
                </div>
            </div>

            <div class="form-navigation">
                <button type="button" class="next-step primary-button">
                    <?php _e('Continue', 'wp-donation-system'); ?>
                    <span class="button-icon">→</span>
                </button>
            </div>
        </div>

        <!-- Step 2: Donor Details -->
        <div class="form-step" data-step="2">
            <div class="form-section">
                <h2><?php _e('Donor Information', 'wp-donation-system'); ?></h2>
                
                <div class="input-group">
                    <label for="donor_name">
                        <?php _e('Full Name', 'wp-donation-system'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" 
                        id="donor_name" 
                        name="donor_name" 
                        required
                        autocomplete="name">
                </div>

                <div class="input-group">
                    <label for="donor_email">
                        <?php _e('Email Address', 'wp-donation-system'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="email" 
                        id="donor_email" 
                        name="donor_email" 
                        required
                        autocomplete="email">
                    <div class="input-hint">
                        <?php _e('Your donation receipt will be sent to this email.', 'wp-donation-system'); ?>
                    </div>
                </div>

                <!-- Anonymous Donation Option -->
                <div class="input-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" 
                            id="anonymous_donation" 
                            name="anonymous_donation">
                        <span class="checkbox-text">
                            <?php _e('Make this donation anonymous', 'wp-donation-system'); ?>
                        </span>
                    </label>
                    <div class="input-hint">
                        <?php _e('Your name will appear as "Anonymous Guest" publicly', 'wp-donation-system'); ?>
                    </div>
                </div>
            </div>

            <div class="form-navigation">
                <button type="button" class="prev-step secondary-button">
                    <span class="button-icon">←</span>
                    <?php _e('Back', 'wp-donation-system'); ?>
                </button>
                <button type="button" class="next-step primary-button">
                    <?php _e('Continue', 'wp-donation-system'); ?>
                    <span class="button-icon">→</span>
                </button>
            </div>
        </div>

        <!-- Step 3: Payment Method -->
        <div class="form-step" data-step="3">
            <div class="form-section">
                <h2><?php _e('Payment Method', 'wp-donation-system'); ?></h2>
                
                <div class="payment-methods">
                    <?php 
                    $gateway_manager = WP_Donation_System_Gateway_Manager::get_instance();
                    $available_gateways = $gateway_manager->get_available_gateways();
                    
                    foreach ($available_gateways as $gateway): 
                        $gateway_id = $gateway->get_id();
                    ?>
                        <div class="payment-option">
                            <input type="radio" 
                                id="payment_<?php echo esc_attr($gateway_id); ?>" 
                                name="payment_method" 
                                value="<?php echo esc_attr($gateway_id); ?>" 
                                class="payment-radio"
                                required>
                            <label for="payment_<?php echo esc_attr($gateway_id); ?>" class="payment-label">
                                <div class="payment-header">
                                    <img src="<?php echo esc_url(WP_DONATION_SYSTEM_URL . 'assets/images/' . $gateway_id . '.png'); ?>" 
                                        alt="<?php echo esc_attr($gateway->get_title()); ?>"
                                        class="payment-icon">
                                    <div class="payment-info">
                                        <span class="payment-name"><?php echo esc_html($gateway->get_title()); ?></span>
                                        <span class="payment-desc"><?php echo esc_html($gateway->get_description()); ?></span>
                                    </div>
                                    <div class="payment-check"></div>
                                </div>
                            </label>

                            <?php if (!empty($gateway->get_payment_fields())): ?>
                                <div class="payment-fields">
                                    <div class="fields-wrapper">
                                        <?php foreach ($gateway->get_payment_fields() as $field_id => $field): ?>
                                            <div class="field-row">
                                                <label for="<?php echo esc_attr($field_id); ?>">
                                                    <?php echo esc_html($field['label']); ?>
                                                    <?php if (!empty($field['required'])): ?>
                                                        <span class="required">*</span>
                                                    <?php endif; ?>
                                                </label>
                                                <input 
                                                    type="<?php echo esc_attr($field['type']); ?>"
                                                    id="<?php echo esc_attr($field_id); ?>"
                                                    name="<?php echo esc_attr($field_id); ?>"
                                                    class="gateway-field"
                                                    <?php echo !empty($field['required']) ? 'required' : ''; ?>
                                                    <?php echo !empty($field['pattern']) ? 'pattern="' . esc_attr($field['pattern']) . '"' : ''; ?>
                                                    <?php echo !empty($field['maxlength']) ? 'maxlength="' . esc_attr($field['maxlength']) . '"' : ''; ?>
                                                    <?php echo !empty($field['placeholder']) ? 'placeholder="' . esc_attr($field['placeholder']) . '"' : ''; ?>>
                                                <?php if (!empty($field['hint'])): ?>
                                                    <div class="field-hint"><?php echo esc_html($field['hint']); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-navigation">
                <button type="button" class="prev-step secondary-button">
                    <span class="button-icon">←</span>
                    <?php _e('Back', 'wp-donation-system'); ?>
                </button>
                <button type="submit" class="submit-donation primary-button">
                    <span class="button-text"><?php _e('Complete Donation', 'wp-donation-system'); ?></span>
                    <span class="loading-spinner"></span>
                </button>
            </div>
        </div>
    </form>
</div>

<script>
console.log(' Donation Form Template Loaded');
if (typeof jQuery === 'undefined') {
    console.error('❌ jQuery not loaded!');
} else {
    console.log('✅ jQuery version:', jQuery.fn.jquery);
}
</script>
