<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap">
    <h1><?php _e('Donation System Settings', 'wp-donation-system'); ?></h1>

    <?php settings_errors(); ?>

    <?php
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'general';
    $settings_manager = WP_Donation_System_Settings_Manager::get_instance();
    ?>

    <h2 class="nav-tab-wrapper">
        <?php foreach ($settings_manager->get_all_settings_groups() as $group_id => $group): ?>
            <a href="?page=wp-donation-system-settings&tab=<?php echo esc_attr($group_id); ?>" 
                class="nav-tab <?php echo $active_tab === $group_id ? 'nav-tab-active' : ''; ?>">
                <?php echo esc_html($group['title']); ?>
            </a>
        <?php endforeach; ?>
    </h2>

    <div class="settings-wrapper">
        <?php foreach ($settings_manager->get_all_settings_groups() as $group_id => $group): 
            $settings = $settings_manager->get_settings($group_id);
            $style = $active_tab === $group_id ? '' : 'display: none;';
        ?>
            <div class="settings-group <?php echo esc_attr($group_id); ?>" style="<?php echo esc_attr($style); ?>">
                <?php include WP_DONATION_SYSTEM_PATH . 'admin/views/settings/partials/settings-group.php'; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
/* Settings Page Styles */
.nav-tab-wrapper {
    margin-bottom: 20px;
}

.nav-tab {
    position: relative;
    padding: 10px 20px;
}

.nav-tab-active {
    border-bottom: 1px solid #f0f0f1;
    background: #fff;
}

.nav-tab-active:after {
    content: '';
    position: absolute;
    bottom: -1px;
    left: 0;
    right: 0;
    height: 1px;
    background: #fff;
}

/* Settings Wrapper */
.settings-wrapper {
    background: #fff;
    padding: 20px;
    border: 1px solid #c3c4c7;
    border-top: none;
    margin-top: -1px;
}

/* Settings Groups */
.settings-group {
    display: none;
}

.settings-group.active {
    display: block;
}
</style>
