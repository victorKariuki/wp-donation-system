<?php
class WP_Donation_System_Logger {
    private $table_name;
    
    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'donation_logs';
        $this->ensure_table_exists();
    }
    
    /**
     * Create logs table if it doesn't exist
     */
    private function ensure_table_exists() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            level varchar(20) NOT NULL DEFAULT 'info',
            message text NOT NULL,
            context longtext,
            PRIMARY KEY  (id),
            KEY level (level),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Log a message
     *
     * @param mixed $message Message to log
     * @param string $level Log level (info, error, warning, debug)
     * @param array $context Additional context data
     */
    public function log($message, $level = 'info', $context = array()) {
        global $wpdb;
        
        try {
            // Convert message to string if needed
            if (!is_string($message)) {
                $message = print_r($message, true);
            }
            
            // Insert log entry
            $result = $wpdb->insert(
                $this->table_name,
                array(
                    'timestamp' => current_time('mysql'),
                    'level' => $level,
                    'message' => $message,
                    'context' => !empty($context) ? wp_json_encode($context) : null
                ),
                array(
                    '%s', // timestamp
                    '%s', // level
                    '%s', // message
                    '%s'  // context
                )
            );
            
            if ($result === false) {
                error_log('Failed to write to log table: ' . $wpdb->last_error);
            }
            
        } catch (Exception $e) {
            error_log('Logging error: ' . $e->getMessage());
        }
    }
    
    /**
     * Get logs with optional filtering
     *
     * @param array $args Query arguments
     * @return array Log entries
     */
    public function get_logs($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'level' => '',
            'limit' => 100,
            'offset' => 0,
            'order' => 'DESC',
            'start_date' => '',
            'end_date' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if (!empty($args['level'])) {
            $where[] = 'level = %s';
            $values[] = $args['level'];
        }
        
        if (!empty($args['start_date'])) {
            $where[] = 'timestamp >= %s';
            $values[] = $args['start_date'];
        }
        
        if (!empty($args['end_date'])) {
            $where[] = 'timestamp <= %s';
            $values[] = $args['end_date'];
        }
        
        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name} 
            WHERE " . implode(' AND ', $where) . "
            ORDER BY timestamp {$args['order']}
            LIMIT %d OFFSET %d",
            array_merge($values, array($args['limit'], $args['offset']))
        );
        
        return $wpdb->get_results($query);
    }
    
    /**
     * Clear logs
     *
     * @param int $days_old Delete logs older than X days (0 for all)
     */
    public function clear_logs($days_old = 0) {
        global $wpdb;
        
        if ($days_old > 0) {
            $date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$this->table_name} WHERE timestamp < %s",
                $date
            ));
        } else {
            $wpdb->query("TRUNCATE TABLE {$this->table_name}");
        }
    }
} 