<?php
abstract class WP_Donation_System_Migration {
    protected $wpdb;
    protected $charset_collate;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function createTable($table, $callback): void {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        $schema = new WP_Donation_System_Schema_Builder($table);
        $callback($schema);
        
        dbDelta($schema->toSql() . " {$this->charset_collate};");
    }

    protected function dropTable($table): void {
        $this->wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
} 