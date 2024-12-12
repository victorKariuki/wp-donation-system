<?php
class WP_Donation_System_Schema_Builder {
    protected $table;
    protected $columns = [];
    protected $keys = [];

    public function __construct($table) {
        global $wpdb;
        $this->table = $wpdb->prefix . $table;
    }
    

    public function id() {
        $this->bigInteger('id', true)->primary();
        return $this;
    }

    public function string($column, $length = 255) {
        $this->columns[] = "`{$column}` varchar({$length})";
        return $this;
    }

    public function text($column) {
        $this->columns[] = "`{$column}` text";
        return $this;
    }

    public function longText($column) {
        $this->columns[] = "`{$column}` longtext";
        return $this;
    }

    public function datetime($column) {
        $this->columns[] = "`{$column}` datetime";
        return $this;
    }

    public function timestamp($column) {
        $this->columns[] = "`{$column}` timestamp";
        return $this;
    }

    public function decimal($column, $precision = 10, $scale = 2) {
        $this->columns[] = "`{$column}` decimal({$precision},{$scale})";
        return $this;
    }

    public function boolean($column) {
        $this->columns[] = "`{$column}` tinyint(1)";
        return $this;
    }

    public function timestamps() {
        $this->datetime('created_at')->default('CURRENT_TIMESTAMP');
        $this->datetime('updated_at')->default('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        return $this;
    }

    public function index($columns, $name = null) {
        if (!is_array($columns)) {
            $columns = [$columns];
        }
        $name = $name ?: implode('_', $columns);
        $this->keys[] = "KEY `{$name}` (`" . implode('`,`', $columns) . "`)";
        return $this;
    }

    public function primary($column = 'id') {
        $this->keys[] = "PRIMARY KEY (`{$column}`)";
        return $this;
    }

    public function bigInteger($column, $autoIncrement = false) {
        $this->columns[] = "`{$column}` bigint(20)" . ($autoIncrement ? " NOT NULL AUTO_INCREMENT" : "");
        return $this;
    }

    public function foreignKey($column, $reference) {
        list($table, $key) = explode('.', $reference);
        global $wpdb;
        $table = $wpdb->prefix . $table;
        $this->keys[] = "FOREIGN KEY (`{$column}`) REFERENCES `{$table}`(`{$key}`) ON DELETE CASCADE";
        return $this;
    }

    public function nullable() {
        $lastIndex = count($this->columns) - 1;
        if (!str_contains($this->columns[$lastIndex], 'DEFAULT NULL')) {
            $this->columns[$lastIndex] .= " DEFAULT NULL";
        }
        return $this;
    }

    public function notNull() {
        $lastIndex = count($this->columns) - 1;
        $this->columns[$lastIndex] .= " NOT NULL";
        return $this;
    }

    public function default($value) {
        $lastIndex = count($this->columns) - 1;
        if ($value === 'CURRENT_TIMESTAMP' || strpos($value, 'CURRENT_TIMESTAMP ON UPDATE') === 0) {
            $this->columns[$lastIndex] .= " DEFAULT {$value}";
        } else {
            $this->columns[$lastIndex] .= " DEFAULT " . (is_numeric($value) ? $value : "'{$value}'");
        }
        return $this;
    }

    public function toSql() {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (\n";
        $sql .= implode(",\n", array_merge($this->columns, $this->keys));
        $sql .= "\n)";
        return $sql;
    }
} 