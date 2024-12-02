<div class="wp-donation-form">
    <?php if (get_option('wp_donation_test_mode', false)): ?>
        <div class="test-mode-notice">
            <p><?php _e('Test Mode Enabled - No real payments will be processed', 'wp-donation-system'); ?></p>
        </div>
    <?php endif; ?>
    <form id="donation-form" method="post">
        <div class="form-group">
            <label for="donor_name"><?php _e('Name', 'wp-donation-system'); ?></label>
            <input type="text" id="donor_name" name="donor_name" required>
        </div>
        
        <div class="form-group">
            <label for="donor_email"><?php _e('Email', 'wp-donation-system'); ?></label>
            <input type="email" id="donor_email" name="donor_email" required>
        </div>
        
        <div class="form-group">
            <label for="amount"><?php _e('Amount', 'wp-donation-system'); ?></label>
            <input type="number" id="amount" name="amount" min="1" required>
        </div>
        
        <div class="form-group">
            <label><?php _e('Payment Method', 'wp-donation-system'); ?></label>
            <select name="payment_method" id="payment_method" required>
                <option value=""><?php _e('Select Payment Method', 'wp-donation-system'); ?></option>
                <option value="paypal"><?php _e('PayPal', 'wp-donation-system'); ?></option>
                <option value="mpesa"><?php _e('M-Pesa', 'wp-donation-system'); ?></option>
            </select>
        </div>

        <div id="payment-forms">
            <div id="mpesa-form" class="payment-form" style="display: none;">
                <div class="form-group">
                    <label for="phone_number"><?php _e('M-Pesa Phone Number', 'wp-donation-system'); ?></label>
                    <input type="text" id="phone_number" name="phone_number" placeholder="254700000000">
                </div>
            </div>
        </div>
        
        <?php wp_nonce_field('donation_form_nonce', 'donation_nonce'); ?>
        <button type="submit" id="donate-button"><?php _e('Donate Now', 'wp-donation-system'); ?></button>
    </form>
</div>
