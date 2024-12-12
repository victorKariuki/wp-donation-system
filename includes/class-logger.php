<?php
class WP_Donation_System_Logger {
    private $log_file;
    private $debug_mode;
    private $log_table;
    private $log_dir;

    public function __construct() {
        global $wpdb;
        $this->debug_mode = get_option('wp_donation_system_debug_mode', false);
        $this->log_dir = WP_DONATION_SYSTEM_PATH . 'logs';
        $this->log_file = $this->log_dir . '/donation-system.log';
        $this->log_table = $wpdb->prefix . 'donation_system_logs';
        
        // Create logs directory with proper permissions if it doesn't exist
        $this->ensure_log_directory();
    }

    private function ensure_log_directory() {
        // If directory doesn't exist, try to create it
        if (!file_exists($this->log_dir)) {
            // Try to create directory with full permissions
            if (!@mkdir($this->log_dir, 0777, true)) {
                // If creation fails, log to WordPress error log
                error_log('WP Donation System: Failed to create logs directory at ' . $this->log_dir);
                return false;
            }
            // Set proper permissions after creation
            @chmod($this->log_dir, 0777);
        }

        // If file doesn't exist, try to create it
        if (!file_exists($this->log_file)) {
            // Try to create file with full permissions
            if (!@touch($this->log_file)) {
                error_log('WP Donation System: Failed to create log file at ' . $this->log_file);
                return false;
            }
            // Set proper permissions after creation
            @chmod($this->log_file, 0666);
        }

        // Verify directory is writable
        if (!is_writable($this->log_dir)) {
            error_log('WP Donation System: Logs directory is not writable: ' . $this->log_dir);
            return false;
        }

        // Verify file is writable
        if (!is_writable($this->log_file)) {
            error_log('WP Donation System: Log file is not writable: ' . $this->log_file);
            return false;
        }

        return true;
    }

    public function log($message, $level = 'info', $context = []) {
        if (!$this->debug_mode && $level === 'debug') {
            return;
        }

        $timestamp = current_time('mysql');
        $formatted_context = $context ? json_encode($context, JSON_PRETTY_PRINT) : '';
        
        // Try to log to file
        if ($this->ensure_log_directory()) {
            $log_entry = sprintf(
                "[%s] %s: %s %s\n",
                $timestamp,
                strtoupper($level),
                $message,
                $formatted_context
            );
            
            // Use file_put_contents with LOCK_EX for atomic writes
            @file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        }

        // Always log to database as backup
        $this->log_to_db($message, $level, $context, $timestamp);

        // Also log to WordPress debug log if enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[WP Donation System] %s: %s %s',
                strtoupper($level),
                $message,
                $formatted_context
            ));
        }
    }

    private function log_to_db($message, $level, $context, $timestamp) {
        global $wpdb;
        
        $wpdb->insert(
            $this->log_table,
            array(
                'timestamp' => $timestamp,
                'level' => $level,
                'message' => $message,
                'context' => is_array($context) ? json_encode($context) : $context
            ),
            array('%s', '%s', '%s', '%s')
        );
    }

    /**
     * Get logs with optional filtering
     */
    public function get_logs($args = array()) {
        global $wpdb;

        $defaults = array(
            'level' => '',
            'date_from' => '',
            'date_to' => '',
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'timestamp',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);
        
        // Start building query
        $query = "SELECT * FROM {$this->log_table} WHERE 1=1";
        $query_args = array();

        // Add filters
        if (!empty($args['level'])) {
            $query .= " AND level = %s";
            $query_args[] = $args['level'];
        }

        if (!empty($args['date_from'])) {
            $query .= " AND timestamp >= %s";
            $query_args[] = $args['date_from'];
        }

        if (!empty($args['date_to'])) {
            $query .= " AND timestamp <= %s";
            $query_args[] = $args['date_to'] . ' 23:59:59';
        }

        // Add ordering
        $allowed_columns = array('timestamp', 'level', 'message');
        $orderby = in_array($args['orderby'], $allowed_columns) ? $args['orderby'] : 'timestamp';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $query .= " ORDER BY {$orderby} {$order}";

        // Add pagination
        $offset = ($args['page'] - 1) * $args['per_page'];
        $query .= " LIMIT %d OFFSET %d";
        $query_args[] = $args['per_page'];
        $query_args[] = $offset;

        // Prepare and execute query
        if (!empty($query_args)) {
            $query = $wpdb->prepare($query, $query_args);
        }

        $results = $wpdb->get_results($query);

        // Get total count for pagination
        $total_query = "SELECT COUNT(*) FROM {$this->log_table} WHERE 1=1";
        if (!empty($args['level'])) {
            $total_query = $wpdb->prepare($total_query . " AND level = %s", $args['level']);
        }
        $total = $wpdb->get_var($total_query);

        return array(
            'logs' => $results,
            'total' => (int)$total,
            'pages' => ceil($total / $args['per_page'])
        );
    }

    /**
     * Clear logs based on age
     */
    public function clear_logs($days_old = 0) {
        global $wpdb;
        
        if ($days_old > 0) {
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
            return $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->log_table} WHERE timestamp <= %s",
                $cutoff_date
            ));
        }
        
        return $wpdb->query("TRUNCATE TABLE {$this->log_table}");
    }
} 