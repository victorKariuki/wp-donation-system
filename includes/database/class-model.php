<?php
abstract class WP_Donation_System_Model {
    protected static $table_name;
    protected static $primary_key = 'id';
    protected static $fillable = [];
    protected $attributes = [];
    protected $original = [];
    
    public function __construct(array $attributes = []) {
        $this->fill($attributes);
        $this->original = $this->attributes;
    }
    
    public function __get($key) {
        return $this->getAttribute($key);
    }
    
    public function __set($key, $value) {
        $this->setAttribute($key, $value);
    }
    
    protected function getAttribute($key) {
        return $this->attributes[$key] ?? null;
    }
    
    protected function setAttribute($key, $value) {
        if (empty(static::$fillable) || in_array($key, static::$fillable)) {
            $this->attributes[$key] = $value;
        }
    }
    
    protected function fill(array $attributes) {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }
    
    public function getDirty() {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $value !== $this->original[$key]) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }
    
    public static function query() {
        return new WP_Donation_System_Query_Builder(static::class);
    }
    
    public static function find($id) {
        return static::query()->where(static::$primary_key, $id)->first();
    }
    
    public static function create(array $attributes) {
        $model = new static($attributes);
        $model->save();
        return $model;
    }
    
    public function save() {
        global $wpdb;
        $table = static::getTable();
        $data = $this->prepareForDatabase($this->attributes);
        
        if (empty($this->attributes[static::$primary_key])) {
            $result = $wpdb->insert($table, $data);
            if ($result) {
                $this->attributes[static::$primary_key] = $wpdb->insert_id;
                $this->original = $this->attributes;
            }
        } else {
            $dirty = $this->prepareForDatabase($this->getDirty());
            if (!empty($dirty)) {
                $result = $wpdb->update(
                    $table,
                    $dirty,
                    [static::$primary_key => $this->attributes[static::$primary_key]]
                );
                if ($result !== false) {
                    $this->original = $this->attributes;
                }
            }
        }
        
        return $result !== false;
    }
    
    public function delete() {
        global $wpdb;
        return $wpdb->delete(
            static::getTable(),
            [static::$primary_key => $this->attributes[static::$primary_key]]
        );
    }
    
    public static function getTable() {
        global $wpdb;
        return $wpdb->prefix . static::$table_name;
    }
    
    protected static function getCurrentTime($type = 'mysql') {
        return current_time($type);
    }
    
    protected static function prepare($query, $args = []) {
        global $wpdb;
        
        if (empty($args)) {
            return $query;
        }

        // Convert array values to JSON
        $args = array_map(function($value) {
            if (is_array($value)) {
                return wp_json_encode($value);
            }
            return $value;
        }, $args);

        return $wpdb->prepare($query, ...$args);
    }

    public static function tableExists(): bool {
        global $wpdb;
        $table = $wpdb->prefix . static::$table_name;
        return $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    }

    public static function ensureTableExists(bool $force = false): void {
        if ($force || !self::tableExists()) {
            require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-migration.php';
            require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-schema-builder.php';
            require_once WP_DONATION_SYSTEM_PATH . 'includes/database/class-migration-manager.php';
            
            $migration_manager = new WP_Donation_System_Migration_Manager();
            
            if ($force) {
                $migration_manager->reset();
            }
            
            $migration_manager->migrate(true);
        }
    }

    public static function dropTable(): void {
        global $wpdb;
        $table = $wpdb->prefix . static::$table_name;
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }

    protected function serializeValue($value) {
        if (is_array($value)) {
            return wp_json_encode($value);
        }
        return $value;
    }

    protected function prepareForDatabase($data) {
        return array_map([$this, 'serializeValue'], $data);
    }
}

class WP_Donation_System_Query_Builder {
    protected $model;
    protected $wheres = [];
    protected $orders = [];
    protected $limit;
    protected $offset;
    
    public function __construct($modelClass) {
        $this->model = $modelClass;
    }
    
    public function where($column, $operator = null, $value = null) {
        if (is_array($column)) {
            foreach ($column as $key => $value) {
                $this->where($key, '=', $value);
            }
            return $this;
        }
        
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $this->wheres[] = compact('column', 'operator', 'value');
        return $this;
    }
    
    public function orderBy($column, $direction = 'ASC') {
        $this->orders[] = compact('column', 'direction');
        return $this;
    }
    
    public function limit($limit) {
        $this->limit = (int)$limit;
        return $this;
    }
    
    public function offset($offset) {
        $this->offset = (int)$offset;
        return $this;
    }
    
    public function get() {
        global $wpdb;
        
        $query = $this->buildQuery();
        $results = $wpdb->get_results($query, ARRAY_A);
        
        return array_map(function($result) {
            return new $this->model($result);
        }, $results);
    }
    
    public function first() {
        $results = $this->limit(1)->get();
        return $results[0] ?? null;
    }
    
    public function paginate($per_page, $current_page = 1) {
        global $wpdb;
        
        // Get total count for pagination
        $table = $this->model::getTable();
        $count_query = "SELECT COUNT(*) FROM {$table} WHERE 1=1";
        foreach ($this->wheres as $where) {
            $count_query .= $wpdb->prepare(" AND {$where['column']} {$where['operator']} %s", $where['value']);
        }
        $total = $wpdb->get_var($count_query);
        
        // Calculate pagination
        $total_pages = ceil($total / $per_page);
        $offset = ($current_page - 1) * $per_page;
        
        // Get paginated results
        $results = $this->limit($per_page)
                       ->offset($offset)
                       ->get();
        
        return [
            'data' => $results,
            'total' => (int)$total,
            'per_page' => (int)$per_page,
            'current_page' => (int)$current_page,
            'last_page' => (int)$total_pages
        ];
    }
    
    protected function buildQuery() {
        global $wpdb;
        
        $table = $this->model::getTable();
        $query = "SELECT * FROM {$table} WHERE 1=1";
        
        $values = [];
        foreach ($this->wheres as $where) {
            $query .= $wpdb->prepare(" AND {$where['column']} {$where['operator']} %s", $where['value']);
        }
        
        if (!empty($this->orders)) {
            $query .= " ORDER BY " . implode(', ', array_map(function($order) {
                return "{$order['column']} {$order['direction']}";
            }, $this->orders));
        }
        
        if ($this->limit) {
            $query .= " LIMIT {$this->limit}";
            if ($this->offset) {
                $query .= " OFFSET {$this->offset}";
            }
        }
        
        return $query;
    }

    /**
     * Delete records based on current query conditions
     *
     * @return bool|int Number of rows affected or false on error
     */
    public function delete() {
        global $wpdb;
        
        $table = $this->model::getTable();
        $query = "DELETE FROM {$table} WHERE 1=1";
        
        foreach ($this->wheres as $where) {
            $query .= $wpdb->prepare(" AND {$where['column']} {$where['operator']} %s", $where['value']);
        }
        
        return $wpdb->query($query);
    }
}