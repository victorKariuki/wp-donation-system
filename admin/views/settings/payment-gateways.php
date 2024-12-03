<?php if (!defined('ABSPATH')) exit; ?>

<!-- M-Pesa Settings -->
<div class="settings-section">
    <h2><?php _e('M-Pesa Settings', 'wp-donation-system'); ?></h2>
    <div class="gateway-header">
        <img src="<?php echo esc_url(WP_DONATION_SYSTEM_URL . 'assets/images/mpesa.png'); ?>" alt="M-Pesa" class="gateway-logo">
        <label class="switch">
            <input type="checkbox" name="mpesa_enabled" id="mpesa_enabled" value="1" 
                <?php checked(get_setting_value($settings, 'mpesa_enabled', false)); ?>>
            <span class="slider round"></span>
        </label>
    </div>
    <div class="gateway-settings" id="mpesa-settings" style="<?php echo get_setting_value($settings, 'mpesa_enabled', false) ? 'display: block;' : 'display: none;'; ?>">
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="mpesa_env"><?php _e('Environment', 'wp-donation-system'); ?></label>
                </th>
                <td>
                    <select name="mpesa_env" id="mpesa_env" class="regular-text">
                        <option value="sandbox" <?php selected(get_setting_value($settings, 'mpesa_env'), 'sandbox'); ?>>
                            <?php _e('Sandbox (Testing)', 'wp-donation-system'); ?>
                        </option>
                        <option value="live" <?php selected(get_setting_value($settings, 'mpesa_env'), 'live'); ?>>
                            <?php _e('Live (Production)', 'wp-donation-system'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="mpesa_type"><?php _e('Integration Type', 'wp-donation-system'); ?></label>
                </th>
                <td>
                    <select name="mpesa_type" id="mpesa_type" class="regular-text">
                        <option value="paybill" <?php selected(get_setting_value($settings, 'mpesa_type'), 'paybill'); ?>>
                            <?php _e('Paybill Number', 'wp-donation-system'); ?>
                        </option>
                        <option value="till" <?php selected(get_setting_value($settings, 'mpesa_type'), 'till'); ?>>
                            <?php _e('Till Number', 'wp-donation-system'); ?>
                        </option>
                    </select>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="mpesa_shortcode"><?php _e('Shortcode', 'wp-donation-system'); ?></label>
                </th>
                <td>
                    <input type="text" id="mpesa_shortcode" name="mpesa_shortcode" 
                        value="<?php echo esc_attr(get_setting_value($settings, 'mpesa_shortcode')); ?>" 
                        class="regular-text">
                    <p class="description"><?php _e('Your M-Pesa API Shortcode', 'wp-donation-system'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="mpesa_number"><?php _e('Business Number', 'wp-donation-system'); ?></label>
                </th>
                <td>
                    <input type="text" id="mpesa_number" name="mpesa_number" 
                        value="<?php echo esc_attr(get_setting_value($settings, 'mpesa_number')); ?>" 
                        class="regular-text">
                    <p class="description"><?php _e('Your Paybill or Till Number that customers will use', 'wp-donation-system'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="mpesa_consumer_key"><?php _e('Consumer Key', 'wp-donation-system'); ?></label>
                </th>
                <td>
                    <input type="text" id="mpesa_consumer_key" name="mpesa_consumer_key" 
                        value="<?php echo esc_attr(get_setting_value($settings, 'mpesa_consumer_key')); ?>" 
                        class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="mpesa_consumer_secret"><?php _e('Consumer Secret', 'wp-donation-system'); ?></label>
                </th>
                <td>
                    <input type="password" id="mpesa_consumer_secret" name="mpesa_consumer_secret" 
                        value="<?php echo esc_attr(get_setting_value($settings, 'mpesa_consumer_secret')); ?>" 
                        class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="mpesa_passkey"><?php _e('Passkey', 'wp-donation-system'); ?></label>
                </th>
                <td>
                    <input type="password" id="mpesa_passkey" name="mpesa_passkey" 
                        value="<?php echo esc_attr(get_setting_value($settings, 'mpesa_passkey')); ?>" 
                        class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="mpesa_account_ref"><?php _e('Account Reference', 'wp-donation-system'); ?></label>
                </th>
                <td>
                    <input type="text" id="mpesa_account_ref" name="mpesa_account_ref" 
                        value="<?php echo esc_attr(get_setting_value($settings, 'mpesa_account_ref', 'DONATION')); ?>" 
                        class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="mpesa_transaction_desc"><?php _e('Transaction Description', 'wp-donation-system'); ?></label>
                </th>
                <td>
                    <input type="text" id="mpesa_transaction_desc" name="mpesa_transaction_desc" 
                        value="<?php echo esc_attr(get_setting_value($settings, 'mpesa_transaction_desc', 'Donation Payment')); ?>" 
                        class="regular-text">
                </td>
            </tr>

            <tr>
                <th scope="row"><?php _e('Callback URLs', 'wp-donation-system'); ?></th>
                <td>
                    <div class="callback-urls">
                        <?php
                        // Define default callback URLs
                        $default_urls = array(
                            'mpesa_callback_url' => rest_url('wp-donation-system/v1/payment/callback'),
                            'mpesa_timeout_url' => rest_url('wp-donation-system/v1/payment/timeout'),
                            'mpesa_result_url' => rest_url('wp-donation-system/v1/payment/result')
                        );

                        // URL field labels
                        $url_labels = array(
                            'mpesa_callback_url' => __('Callback URL:', 'wp-donation-system'),
                            'mpesa_timeout_url' => __('Timeout URL:', 'wp-donation-system'),
                            'mpesa_result_url' => __('Result URL:', 'wp-donation-system')
                        );

                        foreach ($default_urls as $key => $default_url):
                        ?>
                            <div class="url-field">
                                <label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($url_labels[$key]); ?></label>
                                <input type="url" 
                                    id="<?php echo esc_attr($key); ?>" 
                                    name="<?php echo esc_attr($key); ?>" 
                                    value="<?php echo esc_url($default_url); ?>" 
                                    class="regular-text"
                                    readonly>
                                <button type="button" class="button copy-url" data-clipboard-target="#<?php echo esc_attr($key); ?>">
                                    <?php _e('Copy', 'wp-donation-system'); ?>
                                </button>
                                <button type="button" class="button reset-url" data-default="<?php echo esc_url($default_url); ?>" data-target="#<?php echo esc_attr($key); ?>">
                                    <?php _e('Reset', 'wp-donation-system'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="description">
                        <?php _e('Configure these URLs in your Safaricom Developer Portal. Click Copy to copy URL, Reset to restore default.', 'wp-donation-system'); ?>
                    </p>
                </td>
            </tr>
        </table>
    </div>
</div> 