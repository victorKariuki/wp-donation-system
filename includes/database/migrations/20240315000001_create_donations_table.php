<?php
class Migration_20240315000001_Create_Donations_Table extends WP_Donation_System_Migration {
    public function getName(): string {
        return 'Create donations table';
    }

    public function getDescription(): string {
        return 'Creates the main donations table';
    }

    public function up(): void {
        $this->createTable('donation_system_donations', function($table) {
            $table->id();
            $table->string('donor_name', 100)->notNull();
            $table->string('donor_email', 100)->notNull();
            $table->string('donor_phone', 20)->nullable();
            $table->decimal('amount')->notNull();
            $table->string('currency', 3)->notNull()->default('USD');
            $table->string('payment_method', 20)->notNull();
            $table->string('status', 20)->notNull()->default('pending');
            $table->text('notes')->nullable();
            $table->text('metadata')->nullable();
            $table->boolean('is_anonymous')->default(0);
            $table->boolean('is_recurring')->default(0);
            $table->timestamps();
            
            $table->index('donor_email');
            $table->index('status');
            $table->index('payment_method');
            $table->index('created_at');
        });
    }

    public function down(): void {
        $this->dropTable('donation_system_donations');
    }
} 