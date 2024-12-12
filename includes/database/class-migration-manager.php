<?php
class WP_Donation_System_Migration_Manager {
    private $migrations_table;
    private $migrations_path;
    private $logger;

    public function __construct() {
        global $wpdb;
        $this->migrations_table = $wpdb->prefix . 'donation_system_migrations';
        $this->migrations_path = WP_DONATION_SYSTEM_PATH . 'includes/database/migrations/';
        $this->logger = new WP_Donation_System_Logger();
    }

    public function migrate($force = false) {
        try {
            // Create migrations table if it doesn't exist
            $this->create_migrations_table();

            // Get all migration files
            $files = glob($this->migrations_path . '*.php');
            if (empty($files)) {
                return true;
            }

            // Sort files by name to ensure order
            sort($files);

            // Get already run migrations
            $ran_migrations = $this->get_ran_migrations();

            // Start transaction
            global $wpdb;
            $wpdb->query('START TRANSACTION');

            foreach ($files as $file) {
                $migration_name = basename($file);
                
                // Skip if already run and not forcing
                if (in_array($migration_name, $ran_migrations) && !$force) {
                    continue;
                }

                // Include and run migration
                require_once $file;
                $class_name = $this->get_migration_class_name($migration_name);
                
                if (!class_exists($class_name)) {
                    throw new Exception("Migration class {$class_name} not found in {$migration_name}");
                }

                $migration = new $class_name();
                
                if ($force) {
                    // Drop tables first if forcing
                    $migration->down();
                }
                
                // Run migration
                $migration->up();

                // Log successful migration only if it's not already logged
                if (!in_array($migration_name, $ran_migrations)) {
                    $this->log_migration($migration_name);
                }
            }

            // Commit transaction
            $wpdb->query('COMMIT');
            return true;

        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            $this->logger->log('Migration failed: ' . $e->getMessage(), 'error');
            throw new Exception('Migration failed: ' . $e->getMessage());
        }
    }

    private function create_migrations_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->migrations_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            migration varchar(255) NOT NULL,
            batch int(11) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY migration (migration)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private function get_ran_migrations() {
        global $wpdb;
        return $wpdb->get_col("SELECT migration FROM {$this->migrations_table}");
    }

    private function log_migration($migration) {
        global $wpdb;
        
        // Get the next batch number
        $batch = (int)$wpdb->get_var("SELECT MAX(batch) FROM {$this->migrations_table}") + 1;
        
        // Only insert if not already exists
        $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$this->migrations_table} (migration, batch) VALUES (%s, %d)",
            $migration,
            $batch
        ));
    }

    private function get_migration_class_name($file_name) {
        // Remove extension and convert to StudlyCase
        $name = str_replace('.php', '', $file_name);
        $name = preg_replace('/^\d+_/', '', $name); // Remove timestamp prefix
        return 'WP_Donation_System_Migration_' . str_replace(' ', '', ucwords(str_replace('_', ' ', $name)));
    }

    public function reset() {
        global $wpdb;
        
        try {
            $wpdb->query('START TRANSACTION');
            
            // Get all migrations in reverse order
            $files = glob($this->migrations_path . '*.php');
            rsort($files);
            
            foreach ($files as $file) {
                require_once $file;
                $class_name = $this->get_migration_class_name(basename($file));
                $migration = new $class_name();
                $migration->down();
            }
            
            // Clear migrations table
            $wpdb->query("TRUNCATE TABLE {$this->migrations_table}");
            
            $wpdb->query('COMMIT');
            return true;
            
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            $this->logger->log('Migration reset failed: ' . $e->getMessage(), 'error');
            throw $e;
        }
    }
} 