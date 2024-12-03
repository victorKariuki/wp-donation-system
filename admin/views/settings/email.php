<?php if (!defined('ABSPATH')) exit; ?>

<div class="settings-section">
    <h2><?php _e('Email Settings', 'wp-donation-system'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row">
                <label for="email_notifications"><?php _e('Enable Notifications', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <label class="switch">
                    <input type="checkbox" id="email_notifications" name="email_notifications" value="1" 
                        <?php checked(get_setting_value($settings, 'email_notifications', true)); ?>>
                    <span class="slider round"></span>
                </label>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="email_from_name"><?php _e('From Name', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <input type="text" id="email_from_name" name="email_from_name" 
                    value="<?php echo esc_attr(get_setting_value($settings, 'email_from_name', get_bloginfo('name'))); ?>" 
                    class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="email_from_address"><?php _e('From Email', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <input type="email" id="email_from_address" name="email_from_address" 
                    value="<?php echo esc_attr(get_setting_value($settings, 'email_from_address', get_option('admin_email'))); ?>" 
                    class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="admin_email"><?php _e('Admin Email', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <input type="email" id="admin_email" name="admin_email" 
                    value="<?php echo esc_attr(get_setting_value($settings, 'admin_email', get_option('admin_email'))); ?>" 
                    class="regular-text">
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="admin_email_subject"><?php _e('Admin Email Subject', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <input type="text" id="admin_email_subject" name="admin_email_subject" 
                    value="<?php echo esc_attr(get_setting_value($settings, 'admin_email_subject', __('New Donation Received', 'wp-donation-system'))); ?>" 
                    class="regular-text">
                <p class="description"><?php _e('Available variables: {amount}, {donor_name}, {donation_id}', 'wp-donation-system'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="admin_email_template"><?php _e('Admin Email Template', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <?php 
                $default_admin_template = __(
                    "New donation received!\n\n" .
                    "Amount: {amount}\n" .
                    "Donor: {donor_name}\n" .
                    "Email: {donor_email}\n" .
                    "Payment Method: {payment_method}\n" .
                    "Status: {status}\n" .
                    "Date: {date}\n\n" .
                    "View donation: {admin_url}",
                    'wp-donation-system'
                );
                
                wp_editor(
                    get_setting_value($settings, 'admin_email_template', $default_admin_template),
                    'admin_email_template',
                    array(
                        'textarea_name' => 'admin_email_template',
                        'textarea_rows' => 10,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => true
                    )
                ); 
                ?>
                <p class="description">
                    <?php _e('Available variables: {amount}, {donor_name}, {donor_email}, {payment_method}, {status}, {date}, {donation_id}, {admin_url}', 'wp-donation-system'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="donor_email_subject"><?php _e('Donor Receipt Subject', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <input type="text" id="donor_email_subject" name="donor_email_subject" 
                    value="<?php echo esc_attr(get_setting_value($settings, 'donor_email_subject', __('Thank you for your donation!', 'wp-donation-system'))); ?>" 
                    class="regular-text">
                <p class="description"><?php _e('Available variables: {amount}, {donor_name}, {donation_id}', 'wp-donation-system'); ?></p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="donor_email_template"><?php _e('Donor Receipt Template', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <?php 
                $default_donor_template = __(
                    "Dear {donor_name},\n\n" .
                    "Thank you for your generous donation of {amount}!\n\n" .
                    "Donation Details:\n" .
                    "Date: {date}\n" .
                    "Payment Method: {payment_method}\n" .
                    "Receipt Number: {donation_id}\n\n" .
                    "Your support means a lot to us.\n\n" .
                    "Best regards,\n" .
                    "{site_name}",
                    'wp-donation-system'
                );
                
                wp_editor(
                    get_setting_value($settings, 'donor_email_template', $default_donor_template),
                    'donor_email_template',
                    array(
                        'textarea_name' => 'donor_email_template',
                        'textarea_rows' => 10,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => true
                    )
                ); 
                ?>
                <p class="description">
                    <?php _e('Available variables: {amount}, {donor_name}, {date}, {payment_method}, {donation_id}, {site_name}', 'wp-donation-system'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="receipt_footer"><?php _e('Receipt Footer', 'wp-donation-system'); ?></label>
            </th>
            <td>
                <?php 
                wp_editor(
                    get_setting_value($settings, 'receipt_footer', ''), 
                    'receipt_footer',
                    array(
                        'textarea_name' => 'receipt_footer',
                        'textarea_rows' => 5,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => true
                    )
                ); 
                ?>
                <p class="description"><?php _e('This text will appear at the bottom of donation receipt emails.', 'wp-donation-system'); ?></p>
            </td>
        </tr>
    </table>
</div> 