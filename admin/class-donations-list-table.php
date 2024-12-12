<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WP_Donation_System_List_Table extends WP_List_Table {
    private $logger;
    private $currency;
    private $settings_manager;

    public function __construct() {
        parent::__construct([
            'singular' => 'donation',
            'plural'   => 'donations',
            'ajax'     => false
        ]);

        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-logger.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-currency.php';
        require_once WP_DONATION_SYSTEM_PATH . 'includes/class-settings-manager.php';
        
        $this->logger = new WP_Donation_System_Logger();
        $this->currency = new WP_Donation_System_Currency();
        $this->settings_manager = WP_Donation_System_Settings_Manager::get_instance();
    }

    public function prepare_items() {
        global $wpdb;

        // Set column headers
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns()
        );

        // Build query
        $table_name = $wpdb->prefix . 'donation_system_donations';
        $where_clauses = array('1=1');
        $query_args = array();

        // Handle search
        if (!empty($_REQUEST['s'])) {
            $search = sanitize_text_field($_REQUEST['s']);
            $where_clauses[] = '(donor_name LIKE %s OR donor_email LIKE %s OR transaction_id LIKE %s)';
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $query_args[] = $search_like;
            $query_args[] = $search_like;
            $query_args[] = $search_like;
        }

        // Handle filters
        if (!empty($_REQUEST['status'])) {
            $where_clauses[] = 'status = %s';
            $query_args[] = sanitize_text_field($_REQUEST['status']);
        }

        if (!empty($_REQUEST['payment_method'])) {
            $where_clauses[] = 'payment_method = %s';
            $query_args[] = sanitize_text_field($_REQUEST['payment_method']);
        }

        // Handle date range
        if (!empty($_REQUEST['start_date'])) {
            $where_clauses[] = 'created_at >= %s';
            $query_args[] = sanitize_text_field($_REQUEST['start_date']) . ' 00:00:00';
        }

        if (!empty($_REQUEST['end_date'])) {
            $where_clauses[] = 'created_at <= %s';
            $query_args[] = sanitize_text_field($_REQUEST['end_date']) . ' 23:59:59';
        }

        // Build WHERE clause
        $where = implode(' AND ', $where_clauses);
        
        // Handle sorting
        $orderby = !empty($_REQUEST['orderby']) ? sanitize_sql_orderby($_REQUEST['orderby']) : 'created_at';
        $order = !empty($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC';

        // Pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Get total items with prepared query
        $count_query = "SELECT COUNT(*) FROM $table_name WHERE $where";
        if (!empty($query_args)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_args));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }

        // Build main query
        $query = "SELECT * FROM $table_name WHERE $where";
        
        // Add ORDER BY clause
        $query .= " ORDER BY " . esc_sql($orderby) . " " . esc_sql($order);
        
        // Add LIMIT and OFFSET
        $query .= " LIMIT %d OFFSET %d";

        // Add pagination parameters to query args
        $query_args[] = $per_page;
        $query_args[] = $offset;

        // Get items with prepared query
        if (!empty($query_args)) {
            $this->items = $wpdb->get_results($wpdb->prepare($query, $query_args));
        } else {
            $this->items = $wpdb->get_results($query);
        }

        // Set pagination args
        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);

        $this->logger->log('Donations list prepared', 'debug', [
            'total_items' => $total_items,
            'current_page' => $current_page,
            'per_page' => $per_page,
            'query' => $wpdb->last_query // Log the final query for debugging
        ]);
    }

    public function get_columns() {
        return array(
            'cb'             => '<input type="checkbox" />',
            'donor_name'     => __('Donor', 'wp-donation-system'),
            'donor_email'    => __('Email', 'wp-donation-system'),
            'amount'         => __('Amount', 'wp-donation-system'),
            'payment_method' => __('Payment Method', 'wp-donation-system'),
            'status'        => __('Status', 'wp-donation-system'),
            'created_at'    => __('Date', 'wp-donation-system')
        );
    }

    public function get_sortable_columns() {
        return array(
            'donor_name'  => array('donor_name', false),
            'amount'      => array('amount', false),
            'created_at'  => array('created_at', true)
        );
    }

    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'amount':
                return $this->currency->format($item->amount, $item->currency);
            case 'payment_method':
                return ucfirst($item->payment_method);
            case 'status':
                return $this->get_status_badge($item->status);
            case 'created_at':
                return date_i18n(
                    get_option('date_format') . ' ' . get_option('time_format'), 
                    strtotime($item->created_at)
                );
            case 'actions':
                return $this->get_row_actions($item);
            default:
                return isset($item->$column_name) ? esc_html($item->$column_name) : '';
        }
    }

    private function get_status_badge($status) {
        $statuses = array(
            'pending' => array(
                'label' => __('Pending', 'wp-donation-system'),
                'class' => 'status-pending'
            ),
            'processing' => array(
                'label' => __('Processing', 'wp-donation-system'),
                'class' => 'status-processing'
            ),
            'completed' => array(
                'label' => __('Completed', 'wp-donation-system'),
                'class' => 'status-completed'
            ),
            'failed' => array(
                'label' => __('Failed', 'wp-donation-system'),
                'class' => 'status-failed'
            )
        );

        $status_info = isset($statuses[$status]) ? $statuses[$status] : array(
            'label' => ucfirst($status),
            'class' => 'status-' . $status
        );

        return sprintf(
            '<span class="status-badge %s">%s</span>',
            esc_attr($status_info['class']),
            esc_html($status_info['label'])
        );
    }

    private function get_row_actions($item) {
        $actions = array(
            'view' => sprintf(
                '<a href="%s">%s</a>',
                admin_url('admin.php?page=wp-donation-system&action=view&id=' . $item->id),
                __('View', 'wp-donation-system')
            )
        );

        return $this->row_actions($actions);
    }

    public function no_items() {
        _e('No donations found.', 'wp-donation-system');
    }

    /**
     * Column CB
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="donation[]" value="%s" />', 
            $item->id
        );
    }

    /**
     * Column donor_name with actions
     */
    public function column_donor_name($item) {
        // Build row actions
        $actions = array(
            'view' => sprintf(
                '<a href="%s">%s</a>',
                wp_nonce_url(
                    admin_url(sprintf('admin.php?page=wp-donation-system&action=view&id=%d', $item->id)),
                    'view_donation_' . $item->id
                ),
                __('View', 'wp-donation-system')
            ),
            'delete' => sprintf(
                '<a href="%s" onclick="return confirm(\'%s\');">%s</a>',
                wp_nonce_url(
                    admin_url(sprintf('admin.php?page=wp-donation-system&action=delete&id=%d', $item->id)),
                    'delete_donation_' . $item->id
                ),
                esc_js(__('Are you sure you want to delete this donation?', 'wp-donation-system')),
                __('Delete', 'wp-donation-system')
            )
        );

        return sprintf(
            '<strong><a href="%s">%s</a></strong>%s',
            wp_nonce_url(
                admin_url(sprintf('admin.php?page=wp-donation-system&action=view&id=%d', $item->id)),
                'view_donation_' . $item->id
            ),
            esc_html($item->donor_name),
            $this->row_actions($actions)
        );
    }

    /**
     * Process bulk actions
     */
    public function process_bulk_action() {
        global $donation_message, $donation_type;

        // Handle single deletion
        if ('delete' === $this->current_action() && isset($_GET['id'])) {
            $donation_id = intval($_GET['id']);
            check_admin_referer('delete_donation_' . $donation_id);

            if ($this->delete_donation($donation_id)) {
                $donation_message = __('Donation deleted successfully.', 'wp-donation-system');
                $donation_type = 'success';
            } else {
                $donation_message = __('Error deleting donation.', 'wp-donation-system');
                $donation_type = 'error';
            }
        }

        // Handle bulk deletion
        if ('delete' === $this->current_action() && !empty($_REQUEST['donation'])) {
            // Security check
            $nonce = isset($_REQUEST['_wpnonce']) ? $_REQUEST['_wpnonce'] : '';
            if (!wp_verify_nonce($nonce, 'bulk-' . $this->_args['plural'])) {
                wp_die(__('Invalid nonce specified', 'wp-donation-system'), __('Error', 'wp-donation-system'), array(
                    'response' => 403,
                    'back_link' => true,
                ));
            }
            
            $donation_ids = array_map('intval', (array) $_REQUEST['donation']);
            $deleted = 0;
            
            foreach ($donation_ids as $id) {
                if ($this->delete_donation($id)) {
                    $deleted++;
                }
            }

            if ($deleted > 0) {
                $donation_message = sprintf(
                    _n(
                        '%s donation deleted successfully.',
                        '%s donations deleted successfully.',
                        $deleted,
                        'wp-donation-system'
                    ),
                    number_format_i18n($deleted)
                );
                $donation_type = 'success';
            } else {
                $donation_message = __('Error deleting donations.', 'wp-donation-system');
                $donation_type = 'error';
            }
        }
    }

    /**
     * Get bulk actions
     */
    public function get_bulk_actions() {
        return array(
            'delete' => __('Delete', 'wp-donation-system')
        );
    }

    /**
     * Delete a donation
     */
    private function delete_donation($donation_id) {
        global $wpdb;
        
        // Log the deletion attempt
        $this->logger->log('Deleting donation', 'info', [
            'donation_id' => $donation_id,
            'user_id' => get_current_user_id()
        ]);

        // Delete the donation
        $result = $wpdb->delete(
            $wpdb->prefix . 'donation_system_donations',
            ['id' => $donation_id],
            ['%d']
        );

        if ($result === false) {
            $this->logger->log('Failed to delete donation', 'error', [
                'donation_id' => $donation_id,
                'wpdb_last_error' => $wpdb->last_error
            ]);
            return false;
        }

        // Log successful deletion
        $this->logger->log('Donation deleted successfully', 'info', [
            'donation_id' => $donation_id
        ]);

        return true;
    }

    /**
     * Export donations
     */
    private function export_donations($donation_ids) {
        global $wpdb;

        $donation_ids = array_map('intval', $donation_ids);
        $placeholders = implode(',', array_fill(0, count($donation_ids), '%d'));

        $donations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}donation_system_donations WHERE id IN ($placeholders)",
            $donation_ids
        ));

        if (!$donations) {
            return;
        }

        $filename = 'donations-export-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        
        // Add headers with all relevant fields
        fputcsv($output, array(
            'ID',
            'Donor Name',
            'Email',
            'Phone',
            'Amount',
            'Currency',
            'Payment Method',
            'Status',
            'Transaction ID',
            'Checkout Request ID',
            'Merchant Request ID',
            'M-Pesa Receipt',
            'Is Anonymous',
            'Is Recurring',
            'Frequency',
            'Campaign ID',
            'UTM Source',
            'UTM Medium',
            'UTM Campaign',
            'IP Address',
            'Created At',
            'Completed At'
        ));

        // Add data for all fields
        foreach ($donations as $donation) {
            fputcsv($output, array(
                $donation->id,
                $donation->donor_name,
                $donation->donor_email,
                $donation->donor_phone,
                $donation->amount,
                $donation->currency,
                $donation->payment_method,
                $donation->status,
                $donation->transaction_id,
                $donation->checkout_request_id,
                $donation->merchant_request_id,
                $donation->mpesa_receipt_number,
                $donation->is_anonymous ? 'Yes' : 'No',
                $donation->is_recurring ? 'Yes' : 'No',
                $donation->frequency,
                $donation->campaign_id,
                $donation->utm_source,
                $donation->utm_medium,
                $donation->utm_campaign,
                $donation->ip_address,
                $donation->created_at,
                $donation->completed_at
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Get total number of donations
     */
    public function get_total_donations() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}donation_system_donations");
        return $count ? (int) $count : 0;
    }

    /**
     * Get total donation amount
     */
    public function get_total_amount() {
        global $wpdb;
        $default_currency = $this->settings_manager->get_setting('default_currency', 'general', 'USD');
        
        $total = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}donation_system_donations WHERE status = 'completed'");
        return $this->currency->format($total ?: 0, $default_currency);
    }

    /**
     * Get monthly donation amount
     */
    public function get_monthly_amount() {
        global $wpdb;
        $default_currency = $this->settings_manager->get_setting('default_currency', 'general', 'USD');
        
        $first_day = date('Y-m-01 00:00:00');
        $last_day = date('Y-m-t 23:59:59');
        
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(amount) FROM {$wpdb->prefix}donation_system_donations 
            WHERE status = 'completed' 
            AND created_at BETWEEN %s AND %s",
            $first_day,
            $last_day
        ));
        
        return $this->currency->format($total ?: 0, $default_currency);
    }

    /**
     * Get completed donations count
     */
    public function get_completed_count() {
        global $wpdb;
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}donation_system_donations WHERE status = 'completed'"
        );
        return $count ? (int) $count : 0;
    }

    /**
     * Get pending donations count
     */
    public function get_pending_count() {
        global $wpdb;
        $count = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}donation_system_donations WHERE status = 'pending'"
        );
        return $count ? (int) $count : 0;
    }

    /**
     * Get average donation amount
     */
    public function get_average_amount() {
        global $wpdb;
        $default_currency = $this->settings_manager->get_setting('default_currency', 'general', 'USD');
        
        $avg = $wpdb->get_var(
            "SELECT AVG(amount) FROM {$wpdb->prefix}donation_system_donations WHERE status = 'completed'"
        );
        return $this->currency->format($avg ?: 0, $default_currency);
    }

    /**
     * Get donation success rate
     */
    public function get_success_rate() {
        global $wpdb;
        $total = (int) $this->get_total_donations();
        
        // Return 0 if no donations
        if ($total === 0) {
            return 0;
        }
        
        $completed = (int) $this->get_completed_count();
        
        // Avoid division by zero
        if ($total > 0) {
            return round(($completed / $total) * 100);
        }
        
        return 0;
    }

    /**
     * Get donation statistics
     */
    public function get_donation_stats() {
        return [
            'total' => $this->get_total_donations(),
            'completed' => $this->get_completed_count(),
            'pending' => $this->get_pending_count(),
            'total_amount' => $this->get_total_amount(),
            'monthly_amount' => $this->get_monthly_amount(),
            'average_amount' => $this->get_average_amount(),
            'success_rate' => $this->get_success_rate()
        ];
    }

    /**
     * Display donation statistics
     */
    public function display_donation_stats() {
        $stats = $this->get_donation_stats();
        $default_currency = $this->settings_manager->get_setting('default_currency', 'general', 'USD');
            
        ?>
        <div class="donation-stats">
            <div class="stat-box">
                <span class="dashicons dashicons-chart-bar"></span>
                <h3><?php echo esc_html($stats['total']); ?></h3>
                <p><?php _e('Total Donations', 'wp-donation-system'); ?></p>
            </div>
            
            <div class="stat-box">
                <span class="dashicons dashicons-money-alt"></span>
                <h3><?php echo esc_html($this->currency->format($stats['total_amount'], $default_currency)); ?></h3>
                <p><?php _e('Total Amount', 'wp-donation-system'); ?></p>
            </div>
            
            <div class="stat-box">
                <span class="dashicons dashicons-calendar"></span>
                <h3><?php echo esc_html($this->currency->format($stats['monthly_amount'], $default_currency)); ?></h3>
                <p><?php _e('This Month', 'wp-donation-system'); ?></p>
            </div>
            
            <div class="stat-box">
                <span class="dashicons dashicons-yes-alt"></span>
                <h3><?php echo esc_html($stats['completed']); ?></h3>
                <p><?php _e('Completed', 'wp-donation-system'); ?></p>
            </div>
            
            <div class="stat-box">
                <span class="dashicons dashicons-clock"></span>
                <h3><?php echo esc_html($stats['pending']); ?></h3>
                <p><?php _e('Pending', 'wp-donation-system'); ?></p>
            </div>
            
            <div class="stat-box">
                <span class="dashicons dashicons-chart-area"></span>
                <h3><?php echo esc_html($this->currency->format($stats['average_amount'], $default_currency)); ?></h3>
                <p><?php _e('Average', 'wp-donation-system'); ?></p>
            </div>
            
            <div class="stat-box">
                <span class="dashicons dashicons-performance"></span>
                <h3><?php echo esc_html($stats['success_rate']); ?>%</h3>
                <p><?php _e('Success Rate', 'wp-donation-system'); ?></p>
            </div>
        </div>
        <?php
    }

    // For getting donations with filters
    protected function get_donations($per_page, $page_number) {
        $query = WP_Donation_System_Donation::query();
        
        // Add search filter
        if (!empty($_REQUEST['s'])) {
            $search = sanitize_text_field($_REQUEST['s']);
            $query->where('donor_name', 'LIKE', "%{$search}%")
                  ->orWhere('donor_email', 'LIKE', "%{$search}%");
        }
        
        // Add status filter
        if (!empty($_REQUEST['status'])) {
            $query->where('status', sanitize_text_field($_REQUEST['status']));
        }
        
        // Add ordering
        $orderby = !empty($_REQUEST['orderby']) ? $_REQUEST['orderby'] : 'created_at';
        $order = !empty($_REQUEST['order']) ? $_REQUEST['order'] : 'DESC';
        $query->orderBy($orderby, $order);
        
        // Add pagination
        $query->limit($per_page)
              ->offset(($page_number - 1) * $per_page);
        
        return $query->get();
    }
} 