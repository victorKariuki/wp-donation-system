<?php
class Migration_20240315000003_Create_Logs_Table extends WP_Donation_System_Migration {
    public function getName(): string {
        return 'Create Logs Table';
    }

    public function getDescription(): string {
        return 'Creates the table for storing system logs';
    }

    public function up(): void {
        $this->createTable('donation_system_logs', function($table) {
            $table->id();
            $table->timestamp('timestamp')->notNull()->default('CURRENT_TIMESTAMP');
            $table->string('level', 20)->notNull()->default('info');
            $table->text('message')->notNull();
            $table->longText('context')->nullable();
            $table->timestamps();
            
            $table->index('level');
            $table->index('timestamp');
        });
    }

    public function down(): void {
        $this->dropTable('donation_system_logs');
    }
}