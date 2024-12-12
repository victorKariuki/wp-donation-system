<?php
class WP_Donation_System_Donation_Form {
    public static function init() {
        add_shortcode('donation_form', array(__CLASS__, 'render_form'));
    }

    public static function render_form($atts = array()) {
        // Start output buffering
        ob_start();

        // Include the template
        include WP_DONATION_SYSTEM_PATH . 'templates/donation-form.php';

        // Return the buffered content
        return ob_get_clean();
    }
}

// Initialize the form
WP_Donation_System_Donation_Form::init();
