<?php
/**
 * Admin Class
 *
 * @package WP_Donation_System
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Donation_System_Admin {
    /**
     * Default settings
     *
     * @var array
     */
    private $default_settings;

    /**
     * Current settings
     *
     * @var array
     */
    private $settings;

    /**
     * Initialize the admin class
     */
    public function __construct() {
        // Define hardcoded defaults
        $this->default_settings = array(
            'mpesa_consumer_key' => '',
            'mpesa_consumer_secret' => '',
            'mpesa_shortcode' => '',
            'test_mode' => true,
            'default_currency' => 'USD',
            'email_notifications' => true,
            'admin_email' => get_option('admin_email'),
            'donation_minimum' => 5,
            'donation_maximum' => 10000,
            'success_page' => 0,
            'cancel_page' => 0,
            'email_template' => 'default',
            'receipt_footer' => '',
            'mpesa_passkey' => '',
            'mpesa_callback_url' => '',
            'mpesa_timeout_url' => '',
            'mpesa_result_url' => '',
            'mpesa_confirmation_url' => '',
            'mpesa_validation_url' => '',
            'mpesa_env' => 'sandbox',
            'mpesa_type' => 'paybill',
            'mpesa_account_ref' => 'DONATION',
            'mpesa_transaction_desc' => 'Donation Payment',
            'mpesa_enabled' => false,
            'debug_mode' => false,
            'delete_data' => false,
            'rate_limiting' => true,
            'custom_css' => '',
        );

        // Initialize settings
        $this->settings = get_option('wp_donation_system_settings', $this->default_settings);

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'initialize_settings'));

        // Add AJAX actions
        add_action('wp_ajax_save_donation_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_get_donation_logs', array($this, 'get_donation_logs'));
        add_action('wp_ajax_clear_donation_logs', array($this, 'clear_donation_logs'));
    }

    /**
     * Initialize plugin settings
     */
    public function initialize_settings() {
        $current_settings = get_option('wp_donation_system_settings', array());
        
        // If settings don't exist, create them with defaults
        if (empty($current_settings)) {
            $current_settings = $this->default_settings;
            
            // Set admin email default if not set
            if (empty($current_settings['admin_email'])) {
                $current_settings['admin_email'] = get_option('admin_email');
            }
            
            update_option('wp_donation_system_settings', $current_settings);
        }
        
        // Register settings
        register_setting(
            'wp_donation_system_settings',
            'wp_donation_system_settings',
            array($this, 'sanitize_settings')
        );
    }

    /**
     * Get plugin settings
     */
    public function get_settings($key = '') {
        if (!empty($key)) {
            return isset($this->settings[$key]) ? $this->settings[$key] : $this->default_settings[$key];
        }
        return $this->settings;
    }

    /**
     * Save plugin settings
     */
    private function save_settings() {
        if (!current_user_can('manage_options') || !check_admin_referer('wp_donation_system_settings', 'donation_nonce')) {
            return;
        }

        $active_tab = isset($_POST['active_tab']) ? sanitize_key($_POST['active_tab']) : 'general';
        
        // Get current settings
        $new_settings = $this->settings;

        switch ($active_tab) {
            case 'general':
                $new_settings['test_mode'] = isset($_POST['test_mode']);
                $new_settings['default_currency'] = sanitize_text_field($_POST['default_currency']);
                $new_settings['donation_minimum'] = floatval($_POST['donation_minimum']);
                $new_settings['donation_maximum'] = floatval($_POST['donation_maximum']);
                break;

            case 'payment':
                // M-Pesa Settings
                $new_settings['mpesa_enabled'] = isset($_POST['mpesa_enabled']);
                if ($new_settings['mpesa_enabled']) {
                    $mpesa_fields = array(
                        'env', 'type', 'shortcode', 'consumer_key', 'consumer_secret',
                        'passkey', 'account_ref', 'transaction_desc'
                    );
                    foreach ($mpesa_fields as $field) {
                        $key = 'mpesa_' . $field;
                        if (isset($_POST[$key])) {
                            $new_settings[$key] = sanitize_text_field($_POST[$key]);
                        }
                    }
                }
                break;

            case 'email':
                $email_fields = array(
                    'email_notifications' => 'bool',
                    'admin_email' => 'email',
                    'email_from_name' => 'text',
                    'email_from_address' => 'email',
                    'admin_email_subject' => 'text',
                    'admin_email_template' => 'html',
                    'donor_email_subject' => 'text',
                    'donor_email_template' => 'html',
                    'receipt_footer' => 'html'
                );

                foreach ($email_fields as $field => $type) {
                    if ($type === 'bool') {
                        $new_settings[$field] = isset($_POST[$field]);
                    } else if ($type === 'email') {
                        $new_settings[$field] = sanitize_email($_POST[$field]);
                    } else if ($type === 'html') {
                        $new_settings[$field] = wp_kses_post($_POST[$field]);
                    } else {
                        $new_settings[$field] = sanitize_text_field($_POST[$field]);
                    }
                }
                break;

            case 'advanced':
                $new_settings['success_page'] = absint($_POST['success_page']);
                $new_settings['cancel_page'] = absint($_POST['cancel_page']);
                $new_settings['delete_data'] = isset($_POST['delete_data']);
                $new_settings['debug_mode'] = isset($_POST['debug_mode']);
                $new_settings['rate_limiting'] = isset($_POST['rate_limiting']);
                $new_settings['custom_css'] = sanitize_textarea_field($_POST['custom_css']);
                break;
        }

        // Update settings
        $this->settings = $new_settings;
        update_option('wp_donation_system_settings', $new_settings);

        // Clear caches
        wp_cache_delete('wp_donation_system_settings', 'options');
        delete_transient('wp_donation_system_cache');

        // Add success message
        add_settings_error(
            'wp_donation_system_messages',
            'settings_updated',
            __('Settings saved successfully.', 'wp-donation-system'),
            'updated'
        );

        // Redirect to same tab
        wp_safe_redirect(add_query_arg(
            array(
                'page' => 'wp-donation-system-settings',
                'tab' => $active_tab,
                'settings-updated' => 'true'
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Donations', 'wp-donation-system'),
            __('Donations', 'wp-donation-system'),
            'manage_options',
            'wp-donation-system',
            array($this, 'display_donations_page'),
            'dashicons-heart',
            30
        );

        add_submenu_page(
            'wp-donation-system',
            __('All Donations', 'wp-donation-system'),
            __('All Donations', 'wp-donation-system'),
            'manage_options',
            'wp-donation-system',
            array($this, 'display_donations_page')
        );

        add_submenu_page(
            'wp-donation-system',
            __('Settings', 'wp-donation-system'),
            __('Settings', 'wp-donation-system'),
            'manage_options',
            'wp-donation-system-settings',
            array($this, 'display_settings_page')
        );

        add_submenu_page(
            'wp-donation-system',
            __('Reports', 'wp-donation-system'),
            __('Reports', 'wp-donation-system'),
            'manage_options',
            'wp-donation-system-reports',
            array($this, 'display_reports_page')
        );
    }

    /**
     * Display donations page
     */
    public function display_donations_page() {
        // Create an instance of the donations list table
        require_once WP_DONATION_SYSTEM_PATH . 'admin/class-donations-list-table.php';
        $donations_table = new WP_Donation_System_List_Table();
        
        // Process any bulk actions
        $donations_table->process_bulk_action();
        
        // Prepare items for display
        $donations_table->prepare_items();

        // Include the view file
        include WP_DONATION_SYSTEM_PATH . 'admin/views/donations.php';
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        if (
            isset($_POST['save_wp_donation_settings']) && 
            isset($_POST['wp_donation_system_action']) && 
            $_POST['wp_donation_system_action'] === 'save_settings'
        ) {
            $this->save_settings();
        }
        
        $settings = $this->get_settings();
        include WP_DONATION_SYSTEM_PATH . 'admin/views/settings.php';
    }

    /**
     * Display reports page
     */
    public function display_reports_page() {
        include WP_DONATION_SYSTEM_PATH . 'admin/views/reports.php';
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wp-donation-system') === false) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'wp-donation-system-admin',
            WP_DONATION_SYSTEM_URL . 'admin/css/admin-style.css',
            array(),
            WP_DONATION_SYSTEM_VERSION
        );

        // Enqueue admin script
        wp_enqueue_script(
            'wp-donation-system-admin',
            WP_DONATION_SYSTEM_URL . 'admin/js/admin-script.js',
            array('jquery'),
            WP_DONATION_SYSTEM_VERSION,
            true
        );

        // Localize script with separate nonces
        wp_localize_script('wp-donation-system-admin', 'wpDonationSystem', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_donation_system_settings'),
            'logs_nonce' => wp_create_nonce('wp_donation_system_logs'),
            'i18n' => array(
                'saving' => __('Saving...', 'wp-donation-system'),
                'saved' => __('Settings saved successfully.', 'wp-donation-system'),
                'error' => __('An error occurred while saving settings.', 'wp-donation-system'),
                'confirmClearLogs' => __('Are you sure you want to clear all logs?', 'wp-donation-system')
            )
        ));
    }

    /**
     * Debug settings
     * Add this method to the WP_Donation_System_Admin class
     */
    public function debug_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = get_option('wp_donation_system_settings');
        echo '<div class="wrap">';
        echo '<h2>Saved Settings Debug</h2>';
        echo '<pre>';
        print_r($settings);
        echo '</pre>';
        echo '</div>';
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts() {
        // ... existing enqueue code ...
        
        wp_localize_script('wp-donation-system-admin', 'wp_donation_system', array(
            'nonce' => wp_create_nonce('wp_donation_system_settings'),
            'i18n' => array(
                'saving' => __('Saving...', 'wp-donation-system'),
                'save_changes' => __('Save Changes', 'wp-donation-system'),
                'success' => __('Settings saved successfully.', 'wp-donation-system'),
                'error' => __('An error occurred while saving settings.', 'wp-donation-system')
            )
        ));
    }

    /**
     * Handle AJAX settings save
     */
    public function ajax_save_settings() {
        try {
            // Verify nonce first
            if (!check_ajax_referer('wp_donation_system_settings', 'security', false)) {
                throw new Exception(__('Security verification failed', 'wp-donation-system'));
            }
            
            if (!current_user_can('manage_options')) {
                throw new Exception(__('You do not have permission to perform this action.', 'wp-donation-system'));
            }
            
            // Get current settings
            $current_settings = get_option('wp_donation_system_settings', array());
            $new_settings = $current_settings;
            $active_tab = isset($_POST['active_tab']) ? sanitize_key($_POST['active_tab']) : 'general';

            // Process settings based on active tab
            switch ($active_tab) {
                case 'general':
                    $new_settings['donation_minimum'] = floatval($_POST['donation_minimum'] ?? 5);
                    $new_settings['donation_maximum'] = floatval($_POST['donation_maximum'] ?? 10000);
                    $new_settings['default_currency'] = sanitize_text_field($_POST['default_currency'] ?? 'USD');
                    $new_settings['success_page'] = absint($_POST['success_page'] ?? 0);
                    $new_settings['cancel_page'] = absint($_POST['cancel_page'] ?? 0);
                    break;

                case 'payment':
                    // Save payment gateway settings
                    $this->save_payment_settings($new_settings);
                    break;

                case 'email':
                    $new_settings['email_notifications'] = isset($_POST['email_notifications']);
                    $new_settings['admin_email'] = sanitize_email($_POST['admin_email'] ?? get_option('admin_email'));
                    $new_settings['email_from_name'] = sanitize_text_field($_POST['email_from_name'] ?? get_bloginfo('name'));
                    $new_settings['email_from_address'] = sanitize_email($_POST['email_from_address'] ?? get_option('admin_email'));
                    $new_settings['receipt_subject'] = sanitize_text_field($_POST['receipt_subject'] ?? '');
                    $new_settings['receipt_header'] = wp_kses_post($_POST['receipt_header'] ?? '');
                    $new_settings['receipt_footer'] = wp_kses_post($_POST['receipt_footer'] ?? '');
                    break;

                case 'advanced':
                    $new_settings['debug_mode'] = isset($_POST['debug_mode']);
                    $new_settings['delete_data'] = isset($_POST['delete_data']);
                    break;
            }

            // Update settings
            $updated = update_option('wp_donation_system_settings', $new_settings);
            
            if ($updated === false && $new_settings === $current_settings) {
                // No changes were made
                wp_send_json_success(array(
                    'message' => __('No changes were made to settings.', 'wp-donation-system'),
                    'tab' => $active_tab
                ));
            } elseif ($updated === false) {
                throw new Exception(__('Failed to save settings', 'wp-donation-system'));
            }

            // Clear caches
            wp_cache_delete('wp_donation_system_settings', 'options');
            delete_transient('wp_donation_system_cache');

            wp_send_json_success(array(
                'message' => __('Settings saved successfully.', 'wp-donation-system'),
                'tab' => $active_tab
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => $e->getMessage()
            ));
        }
    }

    /**
     * Save payment gateway settings
     */
    private function save_payment_settings(&$settings) {
        // M-Pesa Settings
        $settings['mpesa_enabled'] = isset($_POST['mpesa_enabled']);
        if ($settings['mpesa_enabled']) {
            $mpesa_fields = array(
                'env', 'type', 'shortcode', 'consumer_key', 'consumer_secret',
                'passkey', 'account_ref', 'transaction_desc'
            );
            foreach ($mpesa_fields as $field) {
                $key = 'mpesa_' . $field;
                if (isset($_POST[$key])) {
                    $settings[$key] = sanitize_text_field($_POST[$key]);
                }
            }

            // Validate required M-Pesa fields
            $required_fields = array('consumer_key', 'consumer_secret', 'shortcode', 'passkey');
            foreach ($required_fields as $field) {
                $key = 'mpesa_' . $field;
                if (empty($settings[$key])) {
                    throw new Exception(sprintf(__('%s is required for M-Pesa integration.', 'wp-donation-system'), $field));
                }
            }
        } else {
            // Clear M-Pesa settings when disabled
            $mpesa_fields = array(
                'env', 'type', 'shortcode', 'consumer_key', 'consumer_secret',
                'passkey', 'account_ref', 'transaction_desc'
            );
            foreach ($mpesa_fields as $field) {
                $settings['mpesa_' . $field] = '';
            }
        }
    }

    /**
     * Save email settings
     */
    private function save_email_settings(&$settings) {
        $email_fields = array(
            'email_notifications' => 'bool',
            'admin_email' => 'email',
            'email_from_name' => 'text',
            'email_from_address' => 'email',
            'admin_email_subject' => 'text',
            'admin_email_template' => 'html',
            'donor_email_subject' => 'text',
            'donor_email_template' => 'html',
            'receipt_footer' => 'html'
        );

        foreach ($email_fields as $field => $type) {
            switch ($type) {
                case 'bool':
                    $settings[$field] = isset($_POST[$field]);
                    break;
                case 'email':
                    $settings[$field] = sanitize_email($_POST[$field]);
                    break;
                case 'html':
                    $settings[$field] = wp_kses_post($_POST[$field]);
                    break;
                default:
                    $settings[$field] = sanitize_text_field($_POST[$field]);
            }
        }
    }

    /**
     * Save advanced settings
     */
    private function save_advanced_settings(&$settings) {
        $settings['success_page'] = absint($_POST['success_page']);
        $settings['cancel_page'] = absint($_POST['cancel_page']);
        $settings['delete_data'] = isset($_POST['delete_data']);
        $settings['debug_mode'] = isset($_POST['debug_mode']);
        $settings['rate_limiting'] = isset($_POST['rate_limiting']);
        $settings['custom_css'] = sanitize_textarea_field($_POST['custom_css']);
    }

    /**
     * Get logs via AJAX
     */
    public function get_donation_logs() {
        check_ajax_referer('wp_donation_system_logs', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'wp-donation-system'));
        }
        
        $logger = new WP_Donation_System_Logger();
        $logs = $logger->get_logs([
            'level' => $_POST['level'] ?? '',
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'limit' => 50
        ]);
        
        ob_start();
        foreach ($logs as $log) {
            // Safely decode context
            $context = !empty($log->context) ? json_decode($log->context) : null;
            ?>
            <tr class="log-entry log-level-<?php echo esc_attr($log->level); ?>">
                <td><?php echo esc_html($log->timestamp); ?></td>
                <td><span class="log-level"><?php echo esc_html(strtoupper($log->level)); ?></span></td>
                <td><?php echo esc_html($log->message); ?></td>
                <td>
                    <?php if ($context && !empty((array)$context)): ?>
                        <button class="button-link toggle-context"><?php _e('Show Details', 'wp-donation-system'); ?></button>
                        <div class="context-data hidden">
                            <pre><?php echo esc_html(json_encode($context, JSON_PRETTY_PRINT)); ?></pre>
                        </div>
                    <?php else: ?>
                        <span class="no-context"><?php _e('No additional context', 'wp-donation-system'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
        }
        $html = ob_get_clean();
        
        wp_send_json_success(['html' => $html]);
    }
    
    /**
     * Clear logs via AJAX
     */
    public function clear_donation_logs() {
        check_ajax_referer('wp_donation_system_logs', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'wp-donation-system'));
        }
        
        $logger = new WP_Donation_System_Logger();
        $logger->clear_logs();
        
        wp_send_json_success();
    }

    public function view_mpesa_logs() {
        if (!current_user_can('manage_options')) {
            return;
        }

        $logs = $this->logger->get_logs('mpesa', 100); // Get last 100 M-Pesa related logs
        include WP_DONATION_SYSTEM_PATH . 'admin/views/mpesa-logs.php';
    }
}
