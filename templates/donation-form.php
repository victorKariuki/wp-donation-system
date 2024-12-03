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

    <form id="donation-form" method="post">
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
                <div class="custom-amount">
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

        <!-- Step 2: Donor Information -->
        <div class="form-step" data-step="2">
            <div class="form-section">
                <h2><?php _e('Your Information', 'wp-donation-system'); ?></h2>
                
                <div class="input-group">
                    <label for="donor_name"><?php _e('Full Name', 'wp-donation-system'); ?></label>
                    <input type="text" id="donor_name" name="donor_name" required>
                </div>

                <div class="input-group">
                    <label for="donor_email"><?php _e('Email Address', 'wp-donation-system'); ?></label>
                    <input type="email" id="donor_email" name="donor_email" required>
                    <div class="input-hint"><?php _e('For donation receipt and updates', 'wp-donation-system'); ?></div>
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
                    <div class="payment-method">
                        <input type="radio" id="payment_mpesa" name="payment_method" value="mpesa" required>
                        <label for="payment_mpesa">
                            <img src="<?php echo esc_url(WP_DONATION_SYSTEM_URL . 'assets/images/mpesa.png'); ?>" alt="M-Pesa">
                            <span class="method-name"><?php _e('M-Pesa', 'wp-donation-system'); ?></span>
                        </label>
                    </div>
                </div>

                <div id="mpesa-details" class="payment-details" style="display: none;">
                    <div class="input-group">
                        <label for="phone_number"><?php _e('M-Pesa Phone Number', 'wp-donation-system'); ?></label>
                        <input type="tel" 
                            id="phone_number" 
                            name="phone_number" 
                            pattern="^254[0-9]{9}$" 
                            placeholder="254700000000" 
                            required>
                        <div class="input-hint"><?php _e('Enter your M-Pesa number starting with 254', 'wp-donation-system'); ?></div>
                    </div>
                </div>
            </div>

            <div class="form-navigation">
                <button type="button" class="prev-step secondary-button">
                    <span class="button-icon">←</span>
                    <?php _e('Back', 'wp-donation-system'); ?>
                </button>
                <button type="submit" class="submit-donation primary-button">
                    <?php _e('Complete Donation', 'wp-donation-system'); ?>
                </button>
            </div>

            <!-- Donation Message -->
            <div class="donation-message" style="display: none;"></div>
        </div>

        <?php wp_nonce_field('donation_form_nonce', 'donation_nonce'); ?>
    </form>
</div>

