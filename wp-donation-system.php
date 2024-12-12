<?php
/**
 * Plugin Name: WP Donation System
 * Plugin URI: https://github.com/victorKariuki/wp-donation-system
 * Description: A secure and reliable WordPress plugin for processing donations via M-Pesa mobile payments. Features include donation tracking, reporting, and automated payment processing.
 * Version: 1.0.1
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Author: Victor Kariuki
 * Author URI: https://github.com/victorKariuki
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-donation-system
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_DONATION_SYSTEM_VERSION', '1.0.1');
define('WP_DONATION_SYSTEM_PATH', plugin_dir_path(__FILE__));
define('WP_DONATION_SYSTEM_URL', plugin_dir_url(__FILE__));

// Load core files in correct order
require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-migration.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-schema-builder.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-migration-manager.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-model.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/models/class-log.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/models/class-donation.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/models/class-mpesa-transaction.php';

// Register activation, upgrade and uninstall hooks
register_activation_hook(__FILE__, 'wp_donation_system_activate');
register_deactivation_hook(__FILE__, 'wp_donation_system_deactivate');
register_uninstall_hook(__FILE__, 'wp_donation_system_uninstall');
add_action('plugins_loaded', 'wp_donation_system_upgrade');

function wp_donation_system_activate() {
    try {
        // Version checks
        if (version_compare(get_bloginfo('version'), '5.0', '<')) {
            throw new Exception('This plugin requires WordPress version 5.0 or higher.');
        }

        if (version_compare(PHP_VERSION, '7.2', '<')) {
            throw new Exception('This plugin requires PHP version 7.2 or higher.');
        }

        // Create logs directory with proper permissions
        $logs_dir = WP_DONATION_SYSTEM_PATH . 'logs';
        if (!file_exists($logs_dir)) {
            @mkdir($logs_dir, 0777, true);
            @chmod($logs_dir, 0777);
            
            // Create .htaccess file
            $htaccess = $logs_dir . '/.htaccess';
            @file_put_contents($htaccess, "Order deny,allow\nDeny from all");
            @chmod($htaccess, 0644);
            
            // Create .gitignore file
            $gitignore = $logs_dir . '/.gitignore';
            @file_put_contents($gitignore, "*\n!.gitignore\n!.htaccess");
            @chmod($gitignore, 0644);
        }

        // Run migrations
        $migration_manager = new WP_Donation_System_Migration_Manager();
        
        // Check if tables already exist
        global $wpdb;
        $donations_table = $wpdb->prefix . 'donation_system_donations';
        $tables_exist = $wpdb->get_var("SHOW TABLES LIKE '$donations_table'") === $donations_table;

        // Only force migrations if tables don't exist
        $migration_manager->migrate(!$tables_exist);

        // Update version options
        update_option('wp_donation_system_version', WP_DONATION_SYSTEM_VERSION);
        update_option('wp_donation_system_db_version', '1.0.0');

    } catch (Exception $e) {
        error_log('Plugin activation failed: ' . $e->getMessage());
        wp_die('Plugin activation failed: ' . $e->getMessage());
    }
}

function wp_donation_system_force_table_creation() {
    try {
        // Load required files
        require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-model.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/models/class-donation.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/models/class-mpesa-transaction.php';

        // Force table creation through migrations
        WP_Donation_System_Donation::ensureTableExists();
        WP_Donation_System_MPesa_Transaction::ensureTableExists();

        // Update DB version
        update_option('wp_donation_system_db_version', '1.0.0');

        return true;
    } catch (Exception $e) {
        error_log('Force table creation failed: ' . $e->getMessage());
        return false;
    }
}

// Add an admin notice if tables don't exist
add_action('admin_notices', 'wp_donation_system_check_tables');

function wp_donation_system_check_tables()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'donation_system_donations';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") !== $table_name) {
        ?>
        <div class="notice notice-error">
            <p><?php _e('WP Donation System: Database tables are missing. Please deactivate and reactivate the plugin.', 'wp-donation-system'); ?>
            </p>
            <p>
                <a href="#" class="button" onclick="wp_donation_system_create_tables(); return false;">
                    <?php _e('Create Tables Now', 'wp-donation-system'); ?>
                </a>
            </p>
        </div>
        <script>
            function wp_donation_system_create_tables() {
                jQuery.post(ajaxurl, {
                    action: 'wp_donation_system_create_tables',
                    nonce: '<?php echo wp_create_nonce('wp_donation_system_create_tables'); ?>'
                }, function (response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to create tables: ' + response.data.message);
                    }
                });
            }
        </script>
        <?php
    }
}

// Add AJAX handler for table creation
add_action('wp_ajax_wp_donation_system_create_tables', 'wp_donation_system_ajax_create_tables');

function wp_donation_system_ajax_create_tables()
{
    try {
        check_ajax_referer('wp_donation_system_create_tables', 'nonce');

        if (!current_user_can('manage_options')) {
            throw new Exception('Permission denied');
        }

        wp_donation_system_force_table_creation();
        wp_send_json_success(['message' => 'Tables created successfully']);

    } catch (Exception $e) {
        wp_send_json_error(['message' => $e->getMessage()]);
    }
}

// Load core classes first
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-form-validator.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-error-handler.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-currency.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-rate-limiter.php';

// Load gateway system
require_once WP_DONATION_SYSTEM_PATH . 'includes/gateways/class-abstract-gateway.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-gateway-manager.php';

// Load settings system
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-settings-manager.php';

// Load feature classes
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-donation-form.php';

// Load payment gateways
require_once WP_DONATION_SYSTEM_PATH . 'includes/gateways/class-mpesa.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/gateways/class-paypal.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/gateways/class-bank-transfer.php';

require_once WP_DONATION_SYSTEM_PATH . 'includes/class-notifications.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-callbacks.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-updater.php';
require_once WP_DONATION_SYSTEM_PATH . 'includes/class-ajax.php';

// Load admin classes
require_once WP_DONATION_SYSTEM_PATH . 'admin/class-admin.php';
require_once WP_DONATION_SYSTEM_PATH . 'admin/class-donations-list-table.php';

// Initialize plugin
function wp_donation_system_init()
{
    // Initialize core classes
    $notifications = new WP_Donation_System_Notifications();
    $admin = new WP_Donation_System_Admin();
    $callbacks = new WP_Donation_System_Callbacks();
    $updater = new WP_Donation_System_Updater();
    $ajax = new WP_Donation_System_Ajax();

    // Initialize gateway manager
    $gateway_manager = WP_Donation_System_Gateway_Manager::get_instance();

    // Add AJAX actions
    add_action('wp_ajax_process_donation', array($ajax, 'process_donation'));
    add_action('wp_ajax_nopriv_process_donation', array($ajax, 'process_donation'));

    require_once WP_DONATION_SYSTEM_PATH . 'includes/class-ajax.php';
    $ajax_handler = new WP_Donation_System_Ajax();
    $ajax_handler->init();
}

add_action('plugins_loaded', 'wp_donation_system_init');

// Register activation hook for flushing rewrite rules
register_activation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

// Register REST API endpoints
add_action('rest_api_init', function () {
    register_rest_route('wp-donation-system/v1', '/mpesa-callback', array(
        'methods' => 'POST',
        'callback' => array('WP_Donation_System_Callbacks', 'handle_mpesa_callback'),
        'permission_callback' => '__return_true'
    ));
});

function wp_donation_system_deactivate() {
    // Clear any scheduled events
    wp_clear_scheduled_hook('wp_donation_system_daily_cleanup');
    
    // Optionally flush rewrite rules
    flush_rewrite_rules();
}

function wp_donation_system_uninstall() {
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        exit;
    }

    try {
        // Load required files
        require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-model.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/models/class-donation.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/models/class-mpesa-transaction.php';
        
        // Drop all plugin tables
        WP_Donation_System_Donation::dropTable();
        WP_Donation_System_MPesa_Transaction::dropTable();
        
        // Delete plugin options
        delete_option('wp_donation_system_version');
        delete_option('wp_donation_system_db_version');
        delete_option('wp_donation_system_settings');
        
        // Clear any scheduled events
        wp_clear_scheduled_hook('wp_donation_system_daily_cleanup');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    } catch (Exception $e) {
        error_log('Plugin uninstall failed: ' . $e->getMessage());
    }
}

function wp_donation_system_upgrade() {
    $current_version = get_option('wp_donation_system_version', '0');
    $current_db_version = get_option('wp_donation_system_db_version', '0');
    
    if (version_compare($current_version, WP_DONATION_SYSTEM_VERSION, '<')) {
        try {
            // Load models
            require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-model.php';
            require_once WP_DONATION_SYSTEM_PATH . 'includes/models/class-donation.php';
            require_once WP_DONATION_SYSTEM_PATH . 'includes/models/class-mpesa-transaction.php';

            // Run any necessary database upgrades
            WP_Donation_System_Donation::ensureTableExists();
            WP_Donation_System_MPesa_Transaction::ensureTableExists();
            
            // Update version numbers
            update_option('wp_donation_system_version', WP_DONATION_SYSTEM_VERSION);
            update_option('wp_donation_system_db_version', '1.0.0');
            
            // Clear any caches
            wp_cache_flush();
            
        } catch (Exception $e) {
            error_log('Plugin upgrade failed: ' . $e->getMessage());
        }
    }
}

function wp_donation_system_ensure_tables() {
    try {
        global $wpdb;
        $logs_table = $wpdb->prefix . 'donation_system_logs';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$logs_table'") != $logs_table) {
            require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-migration-manager.php';
            $migration_manager = new WP_Donation_System_Migration_Manager();
            $migration_manager->migrate(true);
        }
    } catch (Exception $e) {
        error_log('Table creation failed: ' . $e->getMessage());
    }
}

// Add to init hook with low priority to ensure all classes are loaded
add_action('init', 'wp_donation_system_ensure_tables', 999);

// Initialize tables on plugin load
add_action('plugins_loaded', function() {
    if (!wp_doing_ajax()) {
        try {
            $migration_manager = new WP_Donation_System_Migration_Manager();
            $migration_manager->migrate();
        } catch (Exception $e) {
            error_log('Failed to run migrations: ' . $e->getMessage());
        }
    }
}, 5);

// Add proper script loading and localization
function wp_donation_system_enqueue_scripts() {
    // Only enqueue on pages with the donation form
    if (has_shortcode(get_post()->post_content, 'donation_form')) {
        wp_enqueue_script('jquery');
        
        wp_enqueue_script(
            'wp-donation-system',
            WP_DONATION_SYSTEM_URL . 'assets/js/public-script.js',
            array('jquery'),
            WP_DONATION_SYSTEM_VERSION,
            true
        );

        // Get currency settings
        $settings_manager = WP_Donation_System_Settings_Manager::get_instance();
        $currency_settings = array(
            'code' => $settings_manager->get_setting('default_currency', 'general', 'KES'),
            'symbol' => $settings_manager->get_setting('currency_symbol', 'general', 'KSh'),
            'position' => $settings_manager->get_setting('currency_position', 'general', 'left'),
            'decimals' => (int)$settings_manager->get_setting('currency_decimals', 'general', 2),
            'decimal_separator' => $settings_manager->get_setting('decimal_separator', 'general', '.'),
            'thousand_separator' => $settings_manager->get_setting('thousand_separator', 'general', ',')
        );

        // Localize script with required data
        wp_localize_script('wp-donation-system', 'wpDonationSystem', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('process_donation'),
            'strings' => array(
                'processing' => __('Processing...', 'wp-donation-system'),
                'complete_donation' => __('Complete Donation', 'wp-donation-system'),
                'network_error' => __('Network error occurred. Please try again.', 'wp-donation-system'),
                'payment_timeout' => __('Payment timeout. Please try again.', 'wp-donation-system'),
                'name_required' => __('Please enter your name.', 'wp-donation-system'),
                'email_required' => __('Please enter your email address.', 'wp-donation-system'),
                'invalid_email' => __('Please enter a valid email address.', 'wp-donation-system'),
                'select_payment' => __('Please select a payment method.', 'wp-donation-system'),
                'pay_with' => __('Pay {amount} with {gateway}', 'wp-donation-system'),
                'mpesa_waiting_title' => __('Waiting for M-Pesa Payment', 'wp-donation-system'),
                'mpesa_waiting_message' => __('Please check your phone and enter your M-Pesa PIN to complete the payment.', 'wp-donation-system'),
                'seconds_remaining' => __('seconds remaining', 'wp-donation-system'),
                'invalid_amount' => __('Please enter a valid donation amount.', 'wp-donation-system'),
                'minimum_amount' => __('The minimum donation amount is %s.', 'wp-donation-system'),
                'maximum_amount' => __('The maximum donation amount is %s.', 'wp-donation-system')
            ),
            'currency' => $currency_settings
        ));

        // Enqueue styles
        wp_enqueue_style(
            'wp-donation-system',
            WP_DONATION_SYSTEM_URL . 'assets/css/public-style.css',
            array(),
            WP_DONATION_SYSTEM_VERSION
        );
    }
}
add_action('wp_enqueue_scripts', 'wp_donation_system_enqueue_scripts');

// Add admin script localization
function wp_donation_system_admin_scripts() {
    wp_enqueue_script('wp-donation-system-admin', 
        WP_DONATION_SYSTEM_URL . 'admin/js/admin-script.js',
        array('jquery'),
        WP_DONATION_SYSTEM_VERSION,
        true
    );

    wp_localize_script('wp-donation-system-admin', 'wpDonationSystem', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wp_donation_system_admin'),
        'strings' => array(
            'no_logs_found' => __('No logs found.', 'wp-donation-system'),
            'confirm_clear_logs' => __('Are you sure you want to clear all logs?', 'wp-donation-system'),
            'items_found' => __('Found {count} items', 'wp-donation-system')
        )
    ));
}
add_action('admin_enqueue_scripts', 'wp_donation_system_admin_scripts');