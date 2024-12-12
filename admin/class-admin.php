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

    private $logger;

    /**
     * Initialize the admin class
     */
    public function __construct() {
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        $this->logger = new WP_Donation_System_Logger();
        
        // Define hardcoded defaults
        $this->default_settings = array(
            'mpesa_consumer_key' => '',
            'mpesa_consumer_secret' => '',
            'mpesa_shortcode' => '',
            'mpesa_number' => '',
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

        // Initialize managers
        WP_Donation_System_Settings_Manager::get_instance();
        WP_Donation_System_Gateway_Manager::get_instance();

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_init', array($this, 'initialize_settings'));

        // Add AJAX actions
        add_action('wp_ajax_save_donation_settings', array($this, 'ajax_save_settings'));
        add_action('wp_ajax_get_donation_logs', array($this, 'get_donation_logs'));
        add_action('wp_ajax_clear_donation_logs', array($this, 'clear_donation_logs'));
        add_action('wp_ajax_test_mpesa_credentials', array($this, 'test_mpesa_credentials'));
        add_action('wp_ajax_check_test_transaction_status', array($this, 'check_test_transaction_status'));

        // Add script enqueuing
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

        // Add AJAX handlers
        add_action('wp_ajax_save_gateway_settings', array($this, 'handle_save_gateway_settings'));
        add_action('wp_ajax_reset_gateway_settings', array($this, 'handle_reset_gateway_settings'));
        add_action('wp_ajax_test_gateway_connection', array($this, 'handle_test_gateway_connection'));
        add_action('wp_ajax_save_settings', array($this, 'handle_save_settings'));
        add_action('wp_ajax_reset_settings', array($this, 'handle_reset_settings'));
        add_action('wp_ajax_toggle_gateway', array($this, 'handle_toggle_gateway'));

        // Add debug AJAX handlers
        add_action('wp_ajax_get_debug_logs', array($this, 'ajax_get_debug_logs'));
        add_action('wp_ajax_clear_debug_logs', array($this, 'ajax_clear_debug_logs'));
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
        // Verify general settings nonce
        if (!isset($_POST['general_settings_nonce']) || 
            !wp_verify_nonce($_POST['general_settings_nonce'], 'wp_donation_system_settings')
        ) {
            return;
        }

        // Verify gateway settings nonce
        $gateway_id = $_POST['gateway_id'] ?? '';
        if ($gateway_id && 
            (!isset($_POST['gateway_settings_nonce_' . $gateway_id]) || 
            !wp_verify_nonce(
                $_POST['gateway_settings_nonce_' . $gateway_id], 
                'wp_donation_system_gateway_settings'
            ))
        ) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        // Get gateway manager instance
        $gateway_manager = WP_Donation_System_Gateway_Manager::get_instance();
        $gateways = $gateway_manager->get_all_gateways();

        // Save gateway settings
        foreach ($gateways as $gateway) {
            $gateway_id = $gateway->get_id();
            $gateway_settings = [];
            
            // Get gateway fields
            $fields = $gateway->get_settings_fields();
            
            // Handle enabled status
            $gateway_settings['enabled'] = isset($_POST[$gateway_id . '_enabled']);
            
            // Process each field
            foreach ($fields as $field_id => $field) {
                if ($field_id === 'enabled') continue; // Already handled
                
                $post_key = $gateway_id . '_' . $field_id;
                if (isset($_POST[$post_key])) {
                    $gateway_settings[$field_id] = sanitize_text_field($_POST[$post_key]);
                }
            }
            
            // Save gateway settings
            update_option('wp_donation_system_' . $gateway_id . '_settings', $gateway_settings);
        }

        // Save general settings
        $general_settings = [
            'test_mode' => isset($_POST['test_mode']),
            'default_currency' => sanitize_text_field($_POST['default_currency']),
            'email_notifications' => isset($_POST['email_notifications']),
            'admin_email' => sanitize_email($_POST['admin_email']),
            'donation_minimum' => floatval($_POST['donation_minimum']),
            'donation_maximum' => floatval($_POST['donation_maximum']),
            'success_page' => intval($_POST['success_page']),
            'cancel_page' => intval($_POST['cancel_page']),
            'email_template' => sanitize_text_field($_POST['email_template']),
            'receipt_footer' => wp_kses_post($_POST['receipt_footer'])
        ];
        
        update_option('wp_donation_system_settings', $general_settings);
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
            'dashicons-money-alt'
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
        $logger = $this->logger;
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
        // Only load on our plugin's pages
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

        // Add payment gateways specific styles
        if (strpos($hook, 'wp-donation-system-settings') !== false) {
            wp_enqueue_style(
                'wp-donation-system-payment-gateways',
                WP_DONATION_SYSTEM_URL . 'admin/css/payment-gateways.css',
                array('wp-donation-system-admin'),
                WP_DONATION_SYSTEM_VERSION
            );
        }

        // Enqueue admin scripts
        wp_enqueue_script(
            'wp-donation-system-admin',
            WP_DONATION_SYSTEM_URL . 'admin/js/admin.js',
            array('jquery', 'wp-util'),
            WP_DONATION_SYSTEM_VERSION,
            true
        );

        // Add payment gateways specific scripts
        if (strpos($hook, 'wp-donation-system-settings') !== false) {
            wp_enqueue_script(
                'wp-donation-system-payment-gateways',
                WP_DONATION_SYSTEM_URL . 'admin/js/payment-gateways.js',
                array('jquery', 'wp-donation-system-admin'),
                WP_DONATION_SYSTEM_VERSION,
                true
            );

            // Add settings scripts
            wp_enqueue_script(
                'wp-donation-system-settings',
                WP_DONATION_SYSTEM_URL . 'admin/js/settings.js',
                array('jquery', 'wp-donation-system-admin'),
                WP_DONATION_SYSTEM_VERSION,
                true
            );
        }

        // Localize script
        wp_localize_script('wp-donation-system-admin', 'wpDonationSystem', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_donation_system_admin'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this donation?', 'wp-donation-system'),
                'saving' => __('Saving...', 'wp-donation-system'),
                'saved' => __('Settings saved successfully.', 'wp-donation-system'),
                'error' => __('An error occurred.', 'wp-donation-system'),
                'test_success' => __('Test transaction successful!', 'wp-donation-system'),
                'test_failed' => __('Test transaction failed.', 'wp-donation-system'),
                'checking_status' => __('Checking payment status...', 'wp-donation-system'),
                'network_error' => __('Network error occurred', 'wp-donation-system'),
                'confirm_reset' => __('Are you sure you want to reset settings to defaults?', 'wp-donation-system'),
                'enabled' => __('Enabled', 'wp-donation-system'),
                'disabled' => __('Disabled', 'wp-donation-system')
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
        $mpesa_settings = get_option('wp_donation_system_mpesa_settings', []);
        $mpesa_settings['enabled'] = isset($_POST['mpesa_enabled']);
        
        if ($mpesa_settings['enabled']) {
            $mpesa_fields = array(
                'environment' => 'env',
                'shortcode' => 'shortcode',
                'consumer_key' => 'consumer_key',
                'consumer_secret' => 'consumer_secret',
                'passkey' => 'passkey'
            );
            
            foreach ($mpesa_fields as $setting_key => $post_key) {
                if (isset($_POST['mpesa_' . $post_key])) {
                    $mpesa_settings[$setting_key] = sanitize_text_field($_POST['mpesa_' . $post_key]);
                }
            }
        }
        
        update_option('wp_donation_system_mpesa_settings', $mpesa_settings);
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
        check_ajax_referer('wp_donation_system_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized access', 'wp-donation-system'));
        }
        
        $logger = new WP_Donation_System_Logger();
        $logs_data = $logger->get_logs([
            'level' => $_POST['level'] ?? '',
            'start_date' => $_POST['start_date'] ?? '',
            'end_date' => $_POST['end_date'] ?? '',
            'page' => intval($_POST['page'] ?? 1),
            'per_page' => intval($_POST['per_page'] ?? 20)
        ], true);
        
        ob_start();
        foreach ($logs_data['logs'] as $log) {
            $context = !empty($log->context) ? json_decode($log->context) : null;
            ?>
            <tr class="log-entry log-level-<?php echo esc_attr($log->level); ?>">
                <td><?php echo esc_html($log->timestamp); ?></td>
                <td><span class="log-level"><?php echo esc_html(strtoupper($log->level)); ?></span></td>
                <td><?php echo esc_html($log->message); ?></td>
                <td>
                    <?php if ($context && !empty((array)$context)): ?>
                        <button type="button" class="button-link toggle-context" role="button">
                            <?php _e('Show Details', 'wp-donation-system'); ?>
                        </button>
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
        
        wp_send_json_success([
            'html' => $html,
            'pagination' => [
                'total_pages' => $logs_data['total_pages'],
                'current_page' => $logs_data['current_page'],
                'total' => $logs_data['total'],
                'total_text' => sprintf(
                    _n('%s item', '%s items', $logs_data['total'], 'wp-donation-system'),
                    number_format_i18n($logs_data['total'])
                )
            ]
        ]);
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

    /**
     * Test M-Pesa credentials
     */
    public function test_mpesa_credentials() {
        try {
            check_ajax_referer('wp_donation_system_admin', 'security');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Unauthorized access', 'wp-donation-system'));
            }

            $phone_number = sanitize_text_field($_POST['phone_number'] ?? '');
            if (empty($phone_number)) {
                throw new Exception(__('Phone number is required', 'wp-donation-system'));
            }

            require_once WP_DONATION_SYSTEM_PATH . 'includes/gateways/class-mpesa.php';
            $mpesa = new WP_Donation_System_MPesa();
            $result = $mpesa->test_credentials($phone_number);

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            // Store the checkout request ID for status checking
            set_transient('mpesa_test_transaction_' . get_current_user_id(), $result['checkout_request_id'], 5 * MINUTE_IN_SECONDS);

            wp_send_json_success([
                'message' => $result['message'],
                'checkout_request_id' => $result['checkout_request_id']
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check test transaction status
     */
    public function check_test_transaction_status() {
        try {
            check_ajax_referer('wp_donation_system_admin', 'security');

            if (!current_user_can('manage_options')) {
                throw new Exception(__('Unauthorized access', 'wp-donation-system'));
            }

            $checkout_request_id = get_transient('mpesa_test_transaction_' . get_current_user_id());
            if (!$checkout_request_id) {
                throw new Exception(__('Test transaction not found or expired', 'wp-donation-system'));
            }

            $donation = WP_Donation_System_Donation::query()
                ->where('checkout_request_id', $checkout_request_id)
                ->first();

            if (!$donation) {
                wp_send_json_success(['status' => 'pending']);
            }

            $metadata = json_decode($donation->metadata, true);
            $response_data = [
                'status' => $donation->status,
                'message' => $donation->notes,
                'is_test' => !empty($metadata['test_transaction']),
                'transaction_id' => $donation->transaction_id
            ];

            // Add additional details for completed transactions
            if ($donation->status === 'completed') {
                $response_data['success_message'] = sprintf(
                    __('Test transaction successful! Transaction ID: %s', 'wp-donation-system'),
                    $donation->transaction_id
                );
            }

            // Add failure details
            if ($donation->status === 'failed') {
                $response_data['error_details'] = $donation->notes;
            }

            wp_send_json_success($response_data);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    public function enqueue_admin_scripts($hook) {
        // Only load on our plugin's pages
        if (strpos($hook, 'wp-donation-system') === false) {
            return;
        }

        wp_enqueue_script(
            'wp-donation-system-admin',
            WP_DONATION_SYSTEM_URL . 'admin/js/admin.js',
            array('jquery', 'wp-util'),
            WP_DONATION_SYSTEM_VERSION,
            true
        );
        
        wp_localize_script('wp-donation-system-admin', 'wp_donation_system', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_donation_system_admin'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this donation?', 'wp-donation-system'),
                'saving' => __('Saving...', 'wp-donation-system'),
                'saved' => __('Settings saved successfully.', 'wp-donation-system'),
                'error' => __('An error occurred.', 'wp-donation-system'),
                'test_success' => __('Test transaction successful!', 'wp-donation-system'),
                'test_failed' => __('Test transaction failed.', 'wp-donation-system'),
                'checking_status' => __('Checking payment status...', 'wp-donation-system'),
                'network_error' => __('Network error occurred', 'wp-donation-system'),
                'confirm_clear_logs' => __('Are you sure you want to clear all logs? This cannot be undone.', 'wp-donation-system'),
                'transaction_id' => __('Transaction ID', 'wp-donation-system'),
                'test_timeout' => __('Test transaction timed out. Please check logs for details.', 'wp-donation-system')
            )
        ));

        // Add dashicons for the test interface
        wp_enqueue_style('dashicons');
    }

    public function handle_save_gateway_settings() {
        check_ajax_referer('wp_donation_system_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-donation-system')));
        }
        
        $gateway_id = sanitize_text_field($_POST['gateway'] ?? '');
        if (empty($gateway_id)) {
            wp_send_json_error(array('message' => __('Invalid gateway.', 'wp-donation-system')));
        }
        
        $gateway_manager = WP_Donation_System_Gateway_Manager::get_instance();
        $gateway = $gateway_manager->get_gateway($gateway_id);
        
        if (!$gateway) {
            wp_send_json_error(array('message' => __('Gateway not found.', 'wp-donation-system')));
        }
        
        // Get current settings
        $settings = get_option('wp_donation_system_' . $gateway_id . '_settings', []);
        
        // Update enabled status
        $settings['enabled'] = isset($_POST[$gateway_id . '_enabled']);
        
        // Update other fields
        $fields = $gateway->get_settings_fields();
        foreach ($fields as $field_id => $field) {
            if ($field_id === 'enabled') continue;
            
            $field_name = $gateway_id . '_' . $field_id;
            if (isset($_POST[$field_name])) {
                // Sanitize based on field type
                switch ($field['type']) {
                    case 'number':
                        $settings[$field_id] = floatval($_POST[$field_name]);
                        break;
                    case 'checkbox':
                        $settings[$field_id] = !empty($_POST[$field_name]);
                        break;
                    case 'select':
                        $settings[$field_id] = sanitize_text_field($_POST[$field_name]);
                        break;
                    case 'password':
                        if (!empty($_POST[$field_name])) {
                            $settings[$field_id] = sanitize_text_field($_POST[$field_name]);
                        }
                        break;
                    default:
                        $settings[$field_id] = sanitize_text_field($_POST[$field_name]);
                }
            }
        }
        
        // Save settings
        update_option('wp_donation_system_' . $gateway_id . '_settings', $settings);
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully.', 'wp-donation-system')
        ));
    }

    public function handle_reset_gateway_settings() {
        check_ajax_referer('wp_donation_system_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-donation-system')));
        }
        
        $gateway_id = sanitize_text_field($_POST['gateway'] ?? '');
        if (empty($gateway_id)) {
            wp_send_json_error(array('message' => __('Invalid gateway.', 'wp-donation-system')));
        }
        
        delete_option('wp_donation_system_' . $gateway_id . '_settings');
        
        wp_send_json_success();
    }

    public function handle_test_gateway_connection() {
        check_ajax_referer('wp_donation_system_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-donation-system')));
        }
        
        $gateway_id = sanitize_text_field($_POST['gateway'] ?? '');
        if (empty($gateway_id)) {
            wp_send_json_error(array('message' => __('Invalid gateway.', 'wp-donation-system')));
        }
        
        $gateway_manager = WP_Donation_System_Gateway_Manager::get_instance();
        $gateway = $gateway_manager->get_gateway($gateway_id);
        
        if (!$gateway) {
            wp_send_json_error(array('message' => __('Gateway not found.', 'wp-donation-system')));
        }
        
        $result = $gateway->test_connection();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Connection test successful!', 'wp-donation-system')));
    }

    public function handle_save_settings() {
        check_ajax_referer('wp_donation_system_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-donation-system')));
        }
        
        $group_id = sanitize_text_field($_POST['settings_group'] ?? '');
        if (empty($group_id)) {
            wp_send_json_error(array('message' => __('Invalid settings group.', 'wp-donation-system')));
        }
        
        $settings_manager = WP_Donation_System_Settings_Manager::get_instance();
        $group = $settings_manager->get_settings_group($group_id);
        
        if (!$group) {
            wp_send_json_error(array('message' => __('Settings group not found.', 'wp-donation-system')));
        }
        
        $settings = array();
        foreach ($group['fields'] as $field_id => $field) {
            $field_name = $group_id . '_' . $field_id;
            
            switch ($field['type']) {
                case 'checkbox':
                    $settings[$field_id] = isset($_POST[$field_name]);
                    break;
                case 'number':
                    $settings[$field_id] = floatval($_POST[$field_name] ?? $field['default'] ?? 0);
                    break;
                case 'textarea':
                    $settings[$field_id] = wp_kses_post($_POST[$field_name] ?? '');
                    break;
                default:
                    $settings[$field_id] = sanitize_text_field($_POST[$field_name] ?? '');
            }
        }
        
        $settings_manager->save_settings($group_id, $settings);
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully.', 'wp-donation-system')
        ));
    }

    public function handle_reset_settings() {
        check_ajax_referer('wp_donation_system_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-donation-system')));
        }
        
        $group_id = sanitize_text_field($_POST['group'] ?? '');
        if (empty($group_id)) {
            wp_send_json_error(array('message' => __('Invalid settings group.', 'wp-donation-system')));
        }
        
        delete_option('wp_donation_system_' . $group_id . '_settings');
        
        wp_send_json_success();
    }

    public function handle_toggle_gateway() {
        check_ajax_referer('wp_donation_system_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'wp-donation-system')));
        }
        
        $gateway_id = sanitize_text_field($_POST['gateway'] ?? '');
        if (empty($gateway_id)) {
            wp_send_json_error(array('message' => __('Invalid gateway.', 'wp-donation-system')));
        }
        
        $enabled = !empty($_POST['enabled']);
        
        // Get current settings
        $settings = get_option('wp_donation_system_' . $gateway_id . '_settings', []);
        
        // Update enabled status
        $settings['enabled'] = $enabled;
        
        // Save settings
        update_option('wp_donation_system_' . $gateway_id . '_settings', $settings);
        
        wp_send_json_success(array(
            'message' => __('Gateway status updated successfully.', 'wp-donation-system')
        ));
    }

    public function get_donation_stats() {
        $stats = [
            'total' => WP_Donation_System_Donation::query()->count(),
            'completed' => WP_Donation_System_Donation::completed()->count(),
            'pending' => WP_Donation_System_Donation::pending()->count(),
            'total_amount' => WP_Donation_System_Donation::completed()
                ->sum('amount'),
            'monthly_amount' => WP_Donation_System_Donation::completed()
                ->where('created_at', '>=', date('Y-m-01'))
                ->sum('amount')
        ];
        
        return $stats;
    }

    public function ajax_get_debug_logs() {
        check_ajax_referer('wp_donation_system_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-donation-system')]);
        }
        
        $logger = new WP_Donation_System_Logger();
        $logs = $logger->get_logs($_POST);
        
        wp_send_json_success($logs);
    }

    public function ajax_clear_debug_logs() {
        check_ajax_referer('wp_donation_system_admin', 'security');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'wp-donation-system')]);
        }
        
        $logger = new WP_Donation_System_Logger();
        $result = $logger->clear_logs();
        
        if ($result !== false) {
            wp_send_json_success(['message' => __('Logs cleared successfully', 'wp-donation-system')]);
        } else {
            wp_send_json_error(['message' => __('Failed to clear logs', 'wp-donation-system')]);
        }
    }
}
