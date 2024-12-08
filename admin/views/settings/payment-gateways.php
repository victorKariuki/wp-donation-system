<?php if (!defined('ABSPATH')) exit; ?>

<div class="payment-gateways-wrapper">
    <?php
    $gateway_manager = WP_Donation_System_Gateway_Manager::get_instance();
    $gateways = $gateway_manager->get_all_gateways();
    ?>

    <!-- Gateway Accordions -->
    <div class="gateway-accordions">
        <?php foreach ($gateways as $gateway):
            $gateway_id = $gateway->get_id();
            $gateway_settings = get_option('wp_donation_system_' . $gateway_id . '_settings', []);
            $is_enabled = !empty($gateway_settings['enabled']);
        ?>
            <div class="gateway-accordion <?php echo $is_enabled ? 'enabled' : ''; ?>" data-gateway="<?php echo esc_attr($gateway_id); ?>">
                <?php 
                // Include accordion header template
                include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/partials/gateway-header.php';
                
                // Include accordion content template
                include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/partials/gateway-content.php';
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>