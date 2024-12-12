<?php
class WP_Donation_System_Database {
    private $settings_manager;
    
    public function __construct() {
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-settings-manager.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-model.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/models/class-donation.php';
        $this->settings_manager = WP_Donation_System_Settings_Manager::get_instance();
    }
    
    /**
     * Get default values from settings
     *
     * @return array Default values from settings
     */
    private function get_default_values() {
        $general_settings = $this->settings_manager->get_settings('general');
        $payment_settings = $this->settings_manager->get_settings('payment');
        
        return array(
            'currency' => isset($general_settings['default_currency']) ? $general_settings['default_currency'] : 'USD',
            'minimum_amount' => isset($general_settings['donation_minimum']) ? $general_settings['donation_minimum'] : 5,
            'maximum_amount' => isset($general_settings['donation_maximum']) ? $general_settings['donation_maximum'] : 10000,
            'mpesa_account_ref' => isset($payment_settings['mpesa_account_ref']) ? $payment_settings['mpesa_account_ref'] : 'DONATION',
            'mpesa_transaction_desc' => isset($payment_settings['mpesa_transaction_desc']) ? $payment_settings['mpesa_transaction_desc'] : 'Donation Payment',
        );
    }

    /**
     * Create plugin database tables
     */
    public function create_tables() {
        try {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            
            // Core donations table
            $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}donation_system_donations (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                donor_name varchar(100) NOT NULL,
                donor_email varchar(100) NOT NULL,
                donor_phone varchar(20),
                amount decimal(10,2) NOT NULL,
                currency varchar(3) NOT NULL DEFAULT 'USD',
                payment_method varchar(20) NOT NULL,
                status varchar(20) NOT NULL DEFAULT 'pending',
                is_anonymous tinyint(1) DEFAULT 0,
                is_recurring tinyint(1) DEFAULT 0,
                frequency varchar(20) DEFAULT NULL,
                parent_donation_id bigint(20) DEFAULT NULL,
                campaign_id bigint(20) DEFAULT NULL,
                utm_source varchar(100) DEFAULT NULL,
                utm_medium varchar(100) DEFAULT NULL,
                utm_campaign varchar(100) DEFAULT NULL,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text,
                notes text,
                metadata longtext,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                completed_at datetime DEFAULT NULL,
                PRIMARY KEY  (id),
                KEY donor_email (donor_email),
                KEY status (status),
                KEY payment_method (payment_method),
                KEY created_at (created_at),
                KEY campaign_id (campaign_id),
                KEY parent_donation_id (parent_donation_id)
            ) $charset_collate";

            // MPesa gateway transactions table
            $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}donation_system_mpesa_transactions (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                donation_id bigint(20) NOT NULL,
                checkout_request_id varchar(100),
                merchant_request_id varchar(100),
                transaction_id varchar(100),
                mpesa_receipt_number varchar(50),
                phone_number varchar(15),
                result_code varchar(10),
                result_desc text,
                raw_request longtext,
                raw_response longtext,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY donation_id (donation_id),
                KEY checkout_request_id (checkout_request_id),
                KEY merchant_request_id (merchant_request_id),
                KEY transaction_id (transaction_id),
                CONSTRAINT fk_mpesa_donation 
                    FOREIGN KEY (donation_id) 
                    REFERENCES {$wpdb->prefix}donation_system_donations(id)
                    ON DELETE CASCADE
            ) $charset_collate";

            // PayPal gateway transactions table
            $sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}donation_system_paypal_transactions (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                donation_id bigint(20) NOT NULL,
                paypal_transaction_id varchar(100),
                payer_id varchar(100),
                payer_email varchar(100),
                payment_status varchar(50),
                raw_request longtext,
                raw_response longtext,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY donation_id (donation_id),
                KEY paypal_transaction_id (paypal_transaction_id),
                CONSTRAINT fk_paypal_donation 
                    FOREIGN KEY (donation_id) 
                    REFERENCES {$wpdb->prefix}donation_system_donations(id)
                    ON DELETE CASCADE
            ) $charset_collate";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            foreach ($sql as $query) {
                dbDelta($query);
            }

            return true;

        } catch (Exception $e) {
            error_log('WP Donation System: Database creation failed: ' . $e->getMessage());
            error_log('WP Donation System: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    /**
     * Save donation to database
     *
     * @param array $data Donation data
     * @return int|false Donation ID or false on failure
     */
    public function save_donation($data) {
        return WP_Donation_System_Donation::create_from_data($data);
    }

    /**
     * Update donation status
     *
     * @param int $donation_id Donation ID
     * @param string $status New status
     * @param array $additional_data Additional data to update
     * @return bool Success/failure
     */
    public function update_donation_status($donation_id, $status, $additional_data = []) {
        $donation = WP_Donation_System_Donation::find($donation_id);
        if ($donation) {
            return $donation->update_status($status, $additional_data);
        }
        return false;
    }

    /**
     * Get donation by ID
     *
     * @param int $donation_id Donation ID
     * @return object|null Donation object or null if not found
     */
    public function get_donation($donation_id) {
        return WP_Donation_System_Donation::find($donation_id);
    }

    /**
     * Update tables for migration
     */
    public function update_tables() {
        global $wpdb;
        $donations_table = $wpdb->prefix . 'donation_system_donations';
        
        try {
            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Create new gateway tables
            $this->create_tables();

            // Migrate existing MPesa data
            $wpdb->query("INSERT INTO {$wpdb->prefix}donation_system_mpesa_transactions 
                (donation_id, checkout_request_id, merchant_request_id, transaction_id, mpesa_receipt_number)
                SELECT id, checkout_request_id, merchant_request_id, transaction_id, mpesa_receipt_number
                FROM {$donations_table}
                WHERE payment_method = 'mpesa' 
                AND (checkout_request_id IS NOT NULL 
                    OR merchant_request_id IS NOT NULL 
                    OR transaction_id IS NOT NULL 
                    OR mpesa_receipt_number IS NOT NULL)");

            // Drop old columns from donations table
            $columns_to_drop = [
                'checkout_request_id',
                'merchant_request_id',
                'transaction_id',
                'mpesa_receipt_number'
            ];

            foreach ($columns_to_drop as $column) {
                $wpdb->query("ALTER TABLE {$donations_table} DROP COLUMN IF EXISTS {$column}");
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            return true;

        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            error_log('Failed to update tables: ' . $e->getMessage());
            throw $e;
        }
    }

    public function insert_donation($data) {
        try {
            // Validate required fields
            $required_fields = ['donor_name', 'donor_email', 'amount', 'payment_method'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Required field missing: {$field}");
                }
            }

            // Use the ORM's create_from_data method
            $donation = WP_Donation_System_Donation::create_from_data($data);
            return $donation ? $donation->id : false;

        } catch (Exception $e) {
            error_log('Exception during donation insertion: ' . $e->getMessage());
            error_log('Exception trace: ' . $e->getTraceAsString());
            return false;
        }
    }

    /**
     * Update donation record
     *
     * @param int $donation_id Donation ID
     * @param array $data Data to update
     * @return bool Success/failure
     */
    public function update_donation($donation_id, $data) {
        $donation = WP_Donation_System_Donation::find($donation_id);
        if ($donation) {
            return $donation->fill($data)->save();
        }
        return false;
    }
}
