<?php
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class WP_Donation_System_List_Table extends WP_List_Table {
    public function prepare_items() {
        global $wpdb;
        
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $total_items = $wpdb->get_var("SELECT COUNT(id) FROM {$wpdb->prefix}donations");
        
        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page
        ));
        
        $this->items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}donations ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $per_page,
                ($current_page - 1) * $per_page
            )
        );
    }
    
    public function get_columns() {
        return array(
            'id' => __('ID', 'wp-donation-system'),
            'donor_name' => __('Donor', 'wp-donation-system'),
            'amount' => __('Amount', 'wp-donation-system'),
            'payment_method' => __('Payment Method', 'wp-donation-system'),
            'status' => __('Status', 'wp-donation-system'),
            'created_at' => __('Date', 'wp-donation-system')
        );
    }
    
    public function get_sortable_columns() {
        return array(
            'id' => array('id', true),
            'amount' => array('amount', false),
            'created_at' => array('created_at', true)
        );
    }
    
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'amount':
                $currency = new WP_Donation_System_Currency();
                return $currency->format_amount($item->amount, $item->currency);
            case 'status':
                return $this->get_status_label($item->status);
            case 'created_at':
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item->created_at));
            default:
                return isset($item->$column_name) ? esc_html($item->$column_name) : '';
        }
    }

    private function get_status_label($status) {
        $labels = array(
            'pending' => '<span class="status-pending">Pending</span>',
            'completed' => '<span class="status-completed">Completed</span>',
            'failed' => '<span class="status-failed">Failed</span>',
            'refunded' => '<span class="status-refunded">Refunded</span>'
        );
        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    public function no_items() {
        _e('No donations found.', 'wp-donation-system');
    }
    
    // ... other required WP_List_Table methods
} 