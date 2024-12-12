<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap wp-donation-settings">
    <h1><?php _e('Donation System Settings', 'wp-donation-system'); ?></h1>

    <?php settings_errors(); ?>

    <?php
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
    $settings_manager = WP_Donation_System_Settings_Manager::get_instance();
    ?>

    <h2 class="nav-tab-wrapper">
        <?php foreach ($settings_manager->get_all_settings_groups() as $group_id => $group): ?>
            <a href="#<?php echo esc_attr($group_id); ?>" 
               data-tab="<?php echo esc_attr($group_id); ?>"
               class="nav-tab <?php echo $active_tab === $group_id ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($group['title']); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="settings-wrapper">
        <?php foreach ($settings_manager->get_all_settings_groups() as $group_id => $group): 
            $settings = $settings_manager->get_settings($group_id);
            $is_active = $active_tab === $group_id;
        ?>
            <div class="settings-group <?php echo esc_attr($group_id); ?>" 
                 style="display: <?php echo $is_active ? 'block' : 'none'; ?>;">
                <?php include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/partials/settings-group.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Settings Page Styles */
.wp-donation-settings .nav-tab-wrapper {
    margin-bottom: 20px;
    border-bottom: 1px solid #c3c4c7;
}

.wp-donation-settings .nav-tab {
    position: relative;
    margin-bottom: -1px;
    padding: 10px 20px;
    border: 1px solid #c3c4c7;
    border-bottom: none;
    background: #f0f0f1;
    color: #50575e;
    font-size: 14px;
    line-height: 1.71428571;
    text-decoration: none;
    white-space: nowrap;
}

.wp-donation-settings .nav-tab-active {
    background: #fff;
    border-bottom: 1px solid #fff;
    color: #000;
}

.wp-donation-settings .settings-wrapper {
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-top: none;
    margin-top: -1px;
}

/* Settings Groups */
.wp-donation-settings .settings-group {
    display: none;
}

.wp-donation-settings .settings-group.active {
    display: block;
}

/* Smooth Transitions */
.wp-donation-settings .settings-group {
    opacity: 1;
    transition: opacity 0.2s ease-in-out;
}

.wp-donation-settings .settings-group.fade-out {
    opacity: 0;
}

.wp-donation-settings .settings-group.fade-in {
    opacity: 1;
}
</style>
