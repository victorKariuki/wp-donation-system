<?php
class WP_Donation_System_MPesa_Transaction {
    private $table_name;
    private $logger;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'mpesa_transactions';
        $this->logger = new WP_Donation_System_Logger();
        
        // Ensure table exists
        $this->create_table();
    }

    /**
     * Create the transactions table
     */
    private function create_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            donation_id bigint(20) NOT NULL,
            merchant_request_id varchar(50),
            checkout_request_id varchar(50),
            transaction_id varchar(50),
            phone_number varchar(15) NOT NULL,
            amount decimal(10,2) NOT NULL,
            request_type varchar(20) NOT NULL,
            request_status varchar(20) NOT NULL,
            raw_request longtext,
            raw_response longtext,
            result_code varchar(10),
            result_desc text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY donation_id (donation_id),
            KEY merchant_request_id (merchant_request_id),
            KEY checkout_request_id (checkout_request_id),
            KEY transaction_id (transaction_id),
            KEY request_status (request_status)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Save STK push request
     */
    public function save_stk_request($donation_id, $request_data, $response_data) {
        global $wpdb;

        try {
            $data = [
                'donation_id' => $donation_id,
                'merchant_request_id' => $response_data->MerchantRequestID ?? null,
                'checkout_request_id' => $response_data->CheckoutRequestID ?? null,
                'phone_number' => $request_data['PhoneNumber'],
                'amount' => $request_data['Amount'],
                'request_type' => 'stk_push',
                'request_status' => 'initiated',
                'raw_request' => wp_json_encode($request_data),
                'raw_response' => wp_json_encode($response_data),
                'result_code' => $response_data->ResponseCode ?? null,
                'result_desc' => $response_data->ResponseDescription ?? null
            ];

            $result = $wpdb->insert($this->table_name, $data);

            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }

            $this->logger->log('Saved STK push transaction', 'debug', [
                'donation_id' => $donation_id,
                'transaction_id' => $wpdb->insert_id
            ]);

            return $wpdb->insert_id;

        } catch (Exception $e) {
            $this->logger->log('Failed to save STK push transaction', 'error', [
                'error' => $e->getMessage(),
                'donation_id' => $donation_id
            ]);
            return false;
        }
    }

    /**
     * Update transaction from callback
     */
    public function update_from_callback($checkout_request_id, $callback_data) {
        global $wpdb;

        try {
            $status = $callback_data['ResultCode'] === '0' ? 'completed' : 'failed';
            
            $data = [
                'request_status' => $status,
                'result_code' => $callback_data['ResultCode'],
                'result_desc' => $callback_data['ResultDesc'],
                'raw_response' => wp_json_encode($callback_data)
            ];

            // Add transaction ID if payment successful
            if ($status === 'completed' && !empty($callback_data['CallbackMetadata']['Item'])) {
                foreach ($callback_data['CallbackMetadata']['Item'] as $item) {
                    if ($item['Name'] === 'MpesaReceiptNumber') {
                        $data['transaction_id'] = $item['Value'];
                        break;
                    }
                }
            }

            $result = $wpdb->update(
                $this->table_name,
                $data,
                ['checkout_request_id' => $checkout_request_id]
            );

            if ($result === false) {
                throw new Exception($wpdb->last_error);
            }

            $this->logger->log('Updated transaction from callback', 'debug', [
                'checkout_request_id' => $checkout_request_id,
                'status' => $status
            ]);

            return true;

        } catch (Exception $e) {
            $this->logger->log('Failed to update transaction from callback', 'error', [
                'error' => $e->getMessage(),
                'checkout_request_id' => $checkout_request_id
            ]);
            return false;
        }
    }

    /**
     * Get transaction by various IDs
     */
    public function get_transaction($id_type, $id_value) {
        global $wpdb;

        $valid_id_types = ['id', 'donation_id', 'merchant_request_id', 'checkout_request_id', 'transaction_id'];
        
        if (!in_array($id_type, $valid_id_types)) {
            return false;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE {$id_type} = %s",
            $id_value
        ));
    }

    /**
     * Get transactions with optional filtering
     */
    public function get_transactions($args = []) {
        global $wpdb;

        $defaults = [
            'status' => '',
            'type' => '',
            'start_date' => '',
            'end_date' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC'
        ];

        $args = wp_parse_args($args, $defaults);
        $where = ['1=1'];
        $values = [];

        if (!empty($args['status'])) {
            $where[] = 'request_status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['type'])) {
            $where[] = 'request_type = %s';
            $values[] = $args['type'];
        }

        if (!empty($args['start_date'])) {
            $where[] = 'created_at >= %s';
            $values[] = $args['start_date'];
        }

        if (!empty($args['end_date'])) {
            $where[] = 'created_at <= %s';
            $values[] = $args['end_date'] . ' 23:59:59';
        }

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE " . implode(' AND ', $where) . "
            ORDER BY {$orderby}
            LIMIT %d OFFSET %d",
            array_merge($values, [$args['per_page'], $offset])
        );

        return $wpdb->get_results($query);
    }

    /**
     * Count transactions with optional filtering
     */
    public function count_transactions($args = []) {
        global $wpdb;

        $where = ['1=1'];
        $values = [];

        if (!empty($args['status'])) {
            $where[] = 'request_status = %s';
            $values[] = $args['status'];
        }

        if (!empty($args['type'])) {
            $where[] = 'request_type = %s';
            $values[] = $args['type'];
        }

        $query = "SELECT COUNT(*) FROM {$this->table_name} WHERE " . implode(' AND ', $where);
        
        if (!empty($values)) {
            $query = $wpdb->prepare($query, $values);
        }

        return $wpdb->get_var($query);
    }
} 