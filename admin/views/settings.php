<?php
if (!defined('ABSPATH')) exit;

$settings = $this->get_settings();
$default_settings = $this->default_settings;

function get_setting_value($settings, $key, $default = '') {
    return isset($settings[$key]) ? $settings[$key] : $default;
}

// Get active tab from URL or default to 'general'
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
?>

<div class="wrap wp-donation-settings">
    <h1><?php _e('Donation System Settings', 'wp-donation-system'); ?></h1>
    
    <div id="setting-save-feedback" class="notice" style="display: none;">
        <p></p>
    </div>
    
    <?php settings_errors(); ?>

    <div class="nav-tab-wrapper">
        <a href="#general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>" data-tab="general">
            <?php _e('General', 'wp-donation-system'); ?>
        </a>
        <a href="#payment" class="nav-tab <?php echo $active_tab === 'payment' ? 'nav-tab-active' : ''; ?>" data-tab="payment">
            <?php _e('Payment Gateways', 'wp-donation-system'); ?>
        </a>
        <a href="#email" class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>" data-tab="email">
            <?php _e('Email', 'wp-donation-system'); ?>
        </a>
        <a href="#advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>" data-tab="advanced">
            <?php _e('Advanced', 'wp-donation-system'); ?>
        </a>
        <a href="#test" class="nav-tab <?php echo $active_tab === 'test' ? 'nav-tab-active' : ''; ?>" data-tab="test">
            <?php _e('Test Gateway', 'wp-donation-system'); ?>
        </a>
        <?php if (WP_DEBUG): ?>
            <a href="#debug" class="nav-tab <?php echo $active_tab === 'debug' ? 'nav-tab-active' : ''; ?>" data-tab="debug">
                <?php _e('Debug', 'wp-donation-system'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <form method="post" action="" id="wp-donation-settings-form">
        <?php wp_nonce_field('wp_donation_system_settings', 'donation_nonce'); ?>
        <input type="hidden" name="active_tab" id="active_tab" value="<?php echo esc_attr($active_tab); ?>">
        
        <!-- General Settings Panel -->
        <div id="general" class="settings-panel" style="<?php echo $active_tab === 'general' ? 'display: block;' : 'display: none;'; ?>">
            <div class="settings-section">
                <h2><?php _e('General Settings', 'wp-donation-system'); ?></h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="test_mode"><?php _e('Test Mode', 'wp-donation-system'); ?></label>
                        </th>
                        <td>
                            <label class="switch">
                                <input type="checkbox" id="test_mode" name="test_mode" value="1" 
                                    <?php checked(get_setting_value($settings, 'test_mode', true)); ?>>
                                <span class="slider round"></span>
                            </label>
                            <p class="description">
                                <?php _e('Enable test mode for development and testing', 'wp-donation-system'); ?>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="default_currency"><?php _e('Default Currency', 'wp-donation-system'); ?></label>
                        </th>
                        <td>
                            <select name="default_currency" id="default_currency" class="regular-text">
                                <?php
                                $currency = new WP_Donation_System_Currency();
                                foreach ($currency->get_currencies() as $code => $data) {
                                    printf(
                                        '<option value="%s" %s>%s (%s)</option>',
                                        esc_attr($code),
                                        selected($settings['default_currency'], $code, false),
                                        esc_html($data['name']),
                                        esc_html($data['symbol'])
                                    );
                                }
                                ?>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Donation Limits', 'wp-donation-system'); ?></th>
                        <td>
                            <div class="donation-limits">
                                <div class="limit-input">
                                    <label for="donation_minimum"><?php _e('Minimum Amount', 'wp-donation-system'); ?></label>
                                    <div class="amount-input-wrapper">
                                        <span class="currency-symbol"><?php echo esc_html($currency->get_symbol($settings['default_currency'])); ?></span>
                                        <input type="number" 
                                            id="donation_minimum" 
                                            name="donation_minimum" 
                                            value="<?php echo esc_attr(get_setting_value($settings, 'donation_minimum', 5)); ?>" 
                                            min="0" 
                                            step="0.01" 
                                            class="regular-text">
                                    </div>
                                    <p class="description">
                                        <?php _e('The minimum donation amount allowed', 'wp-donation-system'); ?>
                                    </p>
                                </div>
                                
                                <div class="limit-input">
                                    <label for="donation_maximum"><?php _e('Maximum Amount', 'wp-donation-system'); ?></label>
                                    <div class="amount-input-wrapper">
                                        <span class="currency-symbol"><?php echo esc_html($currency->get_symbol($settings['default_currency'])); ?></span>
                                        <input type="number" 
                                            id="donation_maximum" 
                                            name="donation_maximum" 
                                            value="<?php echo esc_attr(get_setting_value($settings, 'donation_maximum', 10000)); ?>" 
                                            min="0" 
                                            step="0.01" 
                                            class="regular-text">
                                    </div>
                                    <p class="description">
                                        <?php _e('The maximum donation amount allowed (0 for no limit)', 'wp-donation-system'); ?>
                                    </p>
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Payment Gateways Panel -->
        <div id="payment" class="settings-panel" style="<?php echo $active_tab === 'payment' ? 'display: block;' : 'display: none;'; ?>">
            <?php include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/payment-gateways.php'; ?>
        </div>

        <!-- Email Settings Panel -->
        <div id="email" class="settings-panel" style="<?php echo $active_tab === 'email' ? 'display: block;' : 'display: none;'; ?>">
            <?php include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/email.php'; ?>
        </div>

        <!-- Advanced Settings Panel -->
        <div id="advanced" class="settings-panel" style="<?php echo $active_tab === 'advanced' ? 'display: block;' : 'display: none;'; ?>">
            <?php include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/advanced.php'; ?>
        </div>

        <!-- Test Gateway Panel -->
        <div id="test" class="settings-panel" style="<?php echo $active_tab === 'test' ? 'display: block;' : 'display: none;'; ?>">
            <?php include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/test.php'; ?>
        </div>

        <!-- Debug Panel -->
        <?php if (WP_DEBUG): ?>
            <div id="debug" class="settings-panel" style="<?php echo $active_tab === 'debug' ? 'display: block;' : 'display: none;'; ?>">
                <?php include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/debug.php'; ?>
            </div>
        <?php endif; ?>

        <div class="submit-wrapper">
            <?php submit_button(__('Save Settings', 'wp-donation-system'), 'primary', 'save_wp_donation_settings', false); ?>
        </div>
    </form>
</div>

<style>
/* Settings Page Styles */
.wp-donation-settings {
    max-width: 1200px;
    margin: 20px auto;
}

.settings-panel {
    display: none;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
    margin-top: 20px;
}

.settings-panel.active {
    display: block;
}

.settings-section {
    margin-bottom: 30px;
}

/* Form Improvements */
.form-table th {
    width: 200px;
    padding: 20px;
}

.form-table td {
    padding: 20px;
}

.description {
    margin-top: 8px;
    color: #666;
}

/* Navigation Tabs */
.nav-tab-wrapper {
    margin-bottom: 0;
}

.nav-tab {
    margin-left: 0;
    margin-right: 4px;
}

.nav-tab-active {
    background: #fff;
    border-bottom-color: #fff;
}

.submit-wrapper {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccd0d4;
    box-shadow: 0 1px 1px rgba(0,0,0,.04);
}

/* Donation Limits Styling */
.donation-limits {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.limit-input {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #ddd;
}

.limit-input label {
    display: block;
    font-weight: 600;
    margin-bottom: 10px;
    color: #1d2327;
}

.amount-input-wrapper {
    position: relative;
    max-width: 200px;
}

.amount-input-wrapper .currency-symbol {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
    font-weight: 500;
}

.limit-input input[type="number"] {
    padding: 10px;
    padding-left: 30px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 16px;
    color: #1d2327;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab Navigation
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Update tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Update panels
        $('.settings-panel').removeClass('active');
        $(target).addClass('active');
    });
});
</script>
