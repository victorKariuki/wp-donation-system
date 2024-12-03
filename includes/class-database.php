<?php
class WP_Donation_System_Database {
    /**
     * Get default values from settings
     *
     * @return array Default values from settings
     */
    private function get_default_values() {
        $settings = get_option('wp_donation_system_settings', array());
        
        return array(
            'currency' => isset($settings['default_currency']) ? $settings['default_currency'] : 'USD',
            'minimum_amount' => isset($settings['donation_minimum']) ? $settings['donation_minimum'] : 5,
            'maximum_amount' => isset($settings['donation_maximum']) ? $settings['donation_maximum'] : 10000,
            'mpesa_account_ref' => isset($settings['mpesa_account_ref']) ? $settings['mpesa_account_ref'] : 'DONATION',
            'mpesa_transaction_desc' => isset($settings['mpesa_transaction_desc']) ? $settings['mpesa_transaction_desc'] : 'Donation Payment',
        );
    }

    /**
     * Create plugin database tables
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Donations table
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}donations (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            donor_name varchar(100) NOT NULL,
            donor_email varchar(100) NOT NULL,
            donor_phone varchar(20),
            amount decimal(10,2) NOT NULL,
            currency varchar(3) NOT NULL DEFAULT 'USD',
            payment_method varchar(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            transaction_id varchar(100),
            checkout_request_id varchar(100),
            merchant_request_id varchar(100),
            mpesa_receipt_number varchar(50),
            notes text,
            metadata longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY donor_email (donor_email),
            KEY status (status),
            KEY payment_method (payment_method),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Save donation to database
     *
     * @param array $data Donation data
     * @return int|false Donation ID or false on failure
     */
    public function save_donation($data) {
        global $wpdb;

        // Prepare data for insertion
        $insert_data = array(
            'donor_name' => $data['donor_name'],
            'donor_email' => $data['donor_email'],
            'donor_phone' => $data['donor_phone'],
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'payment_method' => $data['payment_method'],
            'status' => $data['status'],
            'transaction_id' => isset($data['transaction_id']) ? $data['transaction_id'] : '',
            'checkout_request_id' => isset($data['checkout_request_id']) ? $data['checkout_request_id'] : '',
            'merchant_request_id' => isset($data['merchant_request_id']) ? $data['merchant_request_id'] : '',
            'mpesa_receipt_number' => isset($data['mpesa_receipt_number']) ? $data['mpesa_receipt_number'] : '',
            'notes' => isset($data['notes']) ? $data['notes'] : '',
            'metadata' => $data['metadata']
        );

        // Define format for each field
        $format = array(
            '%s', // donor_name
            '%s', // donor_email
            '%s', // donor_phone
            '%f', // amount
            '%s', // currency
            '%s', // payment_method
            '%s', // status
            '%s', // transaction_id
            '%s', // checkout_request_id
            '%s', // merchant_request_id
            '%s', // mpesa_receipt_number
            '%s', // notes
            '%s'  // metadata
        );

        $result = $wpdb->insert(
            $wpdb->prefix . 'donations',
            $insert_data,
            $format
        );

        if ($result === false) {
            error_log('Donation insert failed: ' . $wpdb->last_error);
            return new WP_Error(
                'db_error',
                __('Failed to save donation', 'wp-donation-system')
            );
        }

        return $wpdb->insert_id;
    }

    /**
     * Update donation status
     *
     * @param int $donation_id Donation ID
     * @param string $status New status
     * @param array $additional_data Additional data to update
     * @return bool Success/failure
     */
    public function update_donation_status($donation_id, $status, $additional_data = array()) {
        global $wpdb;

        $data = array_merge(
            array('status' => $status),
            $additional_data
        );

        // Define format for each field
        $format = array();
        foreach ($data as $field => $value) {
            if (is_numeric($value)) {
                $format[] = is_float($value) ? '%f' : '%d';
            } else {
                $format[] = '%s';
            }
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'donations',
            $data,
            array('id' => $donation_id),
            $format,
            array('%d')
        );

        if ($result !== false) {
            /**
             * Action hook after donation status is updated
             *
             * @param int $donation_id The donation ID
             * @param string $status The new status
             * @param array $additional_data Additional updated data
             */
            do_action('wp_donation_status_updated', $donation_id, $status, $additional_data);
        } else {
            error_log('Failed to update donation status: ' . $wpdb->last_error);
        }

        return $result !== false;
    }

    /**
     * Get donation by ID
     *
     * @param int $donation_id Donation ID
     * @return object|null Donation object or null if not found
     */
    public function get_donation($donation_id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}donations WHERE id = %d",
            $donation_id
        ));
    }

    /**
     * Update tables
     */
    public function update_tables() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'donations';
        
        // Check if payment_status column exists and status doesn't
        $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE table_name = '{$table_name}' AND column_name = 'payment_status'");
        
        if (!empty($row)) {
            // Rename payment_status to status
            $wpdb->query("ALTER TABLE {$table_name} 
                CHANGE COLUMN payment_status status varchar(20) NOT NULL DEFAULT 'pending'");
        }
        
        // Check if metadata column exists
        $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE table_name = '{$table_name}' AND column_name = 'metadata'");
        
        if (empty($row)) {
            // Add metadata column if it doesn't exist
            $wpdb->query("ALTER TABLE {$table_name} 
                ADD COLUMN metadata longtext AFTER notes");
        }
    }

    public function insert_donation($data) {
        global $wpdb;
        
        try {
            // Log the incoming data
            error_log('Attempting to insert donation with data: ' . print_r($data, true));
            
            // Validate required fields
            $required_fields = ['donor_name', 'donor_email', 'amount', 'payment_method'];
            foreach ($required_fields as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Required field missing: {$field}");
                }
            }

            // Prepare the data
            $insert_data = [
                'donor_name' => $data['donor_name'],
                'donor_email' => $data['donor_email'],
                'donor_phone' => $data['donor_phone'] ?? '',
                'amount' => floatval($data['amount']),
                'currency' => $data['currency'] ?? 'KES',
                'payment_method' => $data['payment_method'],
                'status' => $data['status'] ?? 'pending',
                'metadata' => $data['metadata'] ?? '',
                'created_at' => current_time('mysql')
            ];

            // Define format for each field
            $format = [
                '%s', // donor_name
                '%s', // donor_email
                '%s', // donor_phone
                '%f', // amount
                '%s', // currency
                '%s', // payment_method
                '%s', // status
                '%s', // metadata
                '%s'  // created_at
            ];

            // Log the prepared data
            error_log('Prepared data for insertion: ' . print_r($insert_data, true));

            // Perform the insertion
            $result = $wpdb->insert(
                $wpdb->prefix . 'donations',
                $insert_data,
                $format
            );

            if ($result === false) {
                throw new Exception('Database insertion failed: ' . $wpdb->last_error);
            }

            $donation_id = $wpdb->insert_id;
            error_log("Donation inserted successfully with ID: {$donation_id}");
            
            return $donation_id;

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
        global $wpdb;
        
        try {
            // Log update attempt
            error_log('Attempting to update donation: ' . $donation_id . ' with data: ' . print_r($data, true));
            
            if (empty($donation_id)) {
                throw new Exception('Invalid donation ID');
            }

            // Define format for each field
            $format = [];
            foreach ($data as $field => $value) {
                switch ($field) {
                    case 'amount':
                        $format[] = '%f';
                        break;
                    case 'status':
                    case 'checkout_request_id':
                    case 'merchant_request_id':
                    case 'transaction_id':
                    case 'notes':
                        $format[] = '%s';
                        break;
                    default:
                        $format[] = '%s';
                }
            }

            // Perform update
            $result = $wpdb->update(
                $wpdb->prefix . 'donations',
                $data,
                ['id' => $donation_id],
                $format,
                ['%d']
            );

            if ($result === false) {
                throw new Exception('Database update failed: ' . $wpdb->last_error);
            }

            // Log successful update
            error_log("Donation {$donation_id} updated successfully");
            
            return true;

        } catch (Exception $e) {
            error_log('Exception during donation update: ' . $e->getMessage());
            return false;
        }
    }
}
