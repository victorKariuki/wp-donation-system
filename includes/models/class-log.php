<?php
class WP_Donation_System_Log extends WP_Donation_System_Model {
    protected static $table_name = 'donation_system_logs';
    protected static $fillable = [
        'timestamp',
        'level',
        'message',
        'context'
    ];

    // Scopes
    public static function info() {
        return static::query()->where('level', 'info');
    }

    public static function error() {
        return static::query()->where('level', 'error');
    }

    public static function warning() {
        return static::query()->where('level', 'warning');
    }

    public static function debug() {
        return static::query()->where('level', 'debug');
    }

    public static function between($start_date, $end_date) {
        return static::query()
            ->where('timestamp', '>=', $start_date)
            ->where('timestamp', '<=', $end_date);
    }

    // Mutators
    protected function setContextAttribute($value) {
        $this->attributes['context'] = is_array($value) ? $value : [];
    }

    // Accessors
    protected function getContextAttribute($value) {
        return $value ? json_decode($value, true) : [];
    }

    public static function ensureTableExists(bool $force = false): void {
        parent::ensureTableExists($force);
        
        // Additional check specific to logs table
        global $wpdb;
        $table = $wpdb->prefix . static::$table_name;
        
        if ($force || $wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-migration-manager.php';
            $migration_manager = new WP_Donation_System_Migration_Manager();
            $migration_manager->migrate(true);
        }
    }
} 