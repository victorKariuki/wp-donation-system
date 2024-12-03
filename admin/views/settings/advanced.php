<?php if (!defined('ABSPATH')) exit; ?>

<div class="settings-section">
    <h2><?php _e('Advanced Settings', 'wp-donation-system'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="debug_mode"><?php _e('Debug Mode', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <label class="switch">
                    <input type="checkbox" id="debug_mode" name="debug_mode" value="1" 
                        <?php checked(get_setting_value($settings, 'debug_mode')); ?>>
                    <span class="slider round"></span>
                </label>
            </td>
        </tr>

        <tr>
            <th scope="row">
                <label for="delete_data"><?php _e('Delete Data on Uninstall', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <label class="switch">
                    <input type="checkbox" id="delete_data" name="delete_data" value="1" 
                        <?php checked(get_setting_value($settings, 'delete_data')); ?>>
                    <span class="slider round"></span>
                </label>
            </td>
        </tr>
    </table>
</div> 