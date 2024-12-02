<?php
if (!defined('ABSPATH')) {
    exit;
}

if (isset($_POST['save_settings'])) {
    check_admin_referer('wp_donation_system_settings');
    // Save settings logic here
}
?>

<div class="wrap">
    <h1><?php _e('Donation System Settings', 'wp-donation-system'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('wp_donation_system_settings'); ?>
        
        <h2><?php _e('Test Mode', 'wp-donation-system'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Test Mode', 'wp-donation-system'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="wp_donation_test_mode" value="1" 
                            <?php checked(get_option('wp_donation_test_mode'), 1); ?>>
                        <?php _e('Enable test mode for payments', 'wp-donation-system'); ?>
                    </label>
                    <p class="description">
                        <?php _e('When enabled, payments will be processed in test/sandbox mode.', 'wp-donation-system'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h2><?php _e('PayPal Settings', 'wp-donation-system'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Test Client ID', 'wp-donation-system'); ?></th>
                <td>
                    <input type="text" name="wp_donation_paypal_test_client_id" class="regular-text"
                        value="<?php echo esc_attr(get_option('wp_donation_paypal_test_client_id')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Test Client Secret', 'wp-donation-system'); ?></th>
                <td>
                    <input type="password" name="wp_donation_paypal_test_client_secret" class="regular-text"
                        value="<?php echo esc_attr(get_option('wp_donation_paypal_test_client_secret')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Live Client ID', 'wp-donation-system'); ?></th>
                <td>
                    <input type="text" name="wp_donation_paypal_client_id" class="regular-text"
                        value="<?php echo esc_attr(get_option('wp_donation_paypal_client_id')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Live Client Secret', 'wp-donation-system'); ?></th>
                <td>
                    <input type="password" name="wp_donation_paypal_client_secret" class="regular-text"
                        value="<?php echo esc_attr(get_option('wp_donation_paypal_client_secret')); ?>">
                </td>
            </tr>
        </table>

        <h2><?php _e('M-Pesa Settings', 'wp-donation-system'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Test Consumer Key', 'wp-donation-system'); ?></th>
                <td>
                    <input type="text" name="wp_donation_mpesa_test_consumer_key" class="regular-text"
                        value="<?php echo esc_attr(get_option('wp_donation_mpesa_test_consumer_key')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Test Consumer Secret', 'wp-donation-system'); ?></th>
                <td>
                    <input type="password" name="wp_donation_mpesa_test_consumer_secret" class="regular-text"
                        value="<?php echo esc_attr(get_option('wp_donation_mpesa_test_consumer_secret')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Test Shortcode', 'wp-donation-system'); ?></th>
                <td>
                    <input type="text" name="wp_donation_mpesa_test_shortcode" class="regular-text"
                        value="<?php echo esc_attr(get_option('wp_donation_mpesa_test_shortcode')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Live Consumer Key', 'wp-donation-system'); ?></th>
                <td>
                    <input type="text" name="wp_donation_mpesa_consumer_key" class="regular-text"
                        value="<?php echo esc_attr(get_option('wp_donation_mpesa_consumer_key')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Live Consumer Secret', 'wp-donation-system'); ?></th>
                <td>
                    <input type="password" name="wp_donation_mpesa_consumer_secret" class="regular-text"
                        value="<?php echo esc_attr(get_option('wp_donation_mpesa_consumer_secret')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Live Shortcode', 'wp-donation-system'); ?></th>
                <td>
                    <input type="text" name="wp_donation_mpesa_shortcode" class="regular-text"
                        value="<?php echo esc_attr(get_option('wp_donation_mpesa_shortcode')); ?>">
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="save_settings" class="button-primary" 
                value="<?php _e('Save Settings', 'wp-donation-system'); ?>">
        </p>
    </form>

    <div class="test-credentials">
        <h2><?php _e('Test Credentials', 'wp-donation-system'); ?></h2>
        <h3><?php _e('PayPal Sandbox', 'wp-donation-system'); ?></h3>
        <p>
            <?php _e('Create a sandbox account at:', 'wp-donation-system'); ?>
            <a href="https://developer.paypal.com/developer/accounts/" target="_blank">PayPal Developer</a>
        </p>
        
        <h3><?php _e('M-Pesa Sandbox', 'wp-donation-system'); ?></h3>
        <p>
            <?php _e('Get test credentials at:', 'wp-donation-system'); ?>
            <a href="https://developer.safaricom.co.ke/" target="_blank">Safaricom Developer Portal</a>
        </p>
        <p>
            <?php _e('Test Phone Numbers:', 'wp-donation-system'); ?><br>
            254708374149<br>
            254700000000
        </p>
    </div>
</div>
