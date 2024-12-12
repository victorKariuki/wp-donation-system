<?php
class Migration_20240315000002_Create_MPesa_Transactions_Table extends WP_Donation_System_Migration {
    public function getName(): string {
        return 'Create MPesa Transactions Table';
    }

    public function getDescription(): string {
        return 'Creates the table for storing MPesa transaction records';
    }

    public function up(): void {
        $this->createTable('donation_system_mpesa_transactions', function($table) {
            $table->id();
            $table->bigInteger('donation_id')->notNull();
            $table->string('checkout_request_id', 50)->nullable();
            $table->string('merchant_request_id', 50)->nullable();
            $table->string('transaction_id', 50)->nullable();
            $table->string('phone_number', 15)->notNull();
            $table->decimal('amount')->notNull();
            $table->string('request_type', 20)->notNull();
            $table->string('request_status', 20)->notNull();
            $table->text('raw_request')->nullable();
            $table->text('raw_response')->nullable();
            $table->string('result_code', 10)->nullable();
            $table->text('result_desc')->nullable();
            $table->timestamps();
            
            $table->foreignKey('donation_id', 'donation_system_donations.id');
            $table->index('checkout_request_id');
            $table->index('merchant_request_id');
            $table->index('transaction_id');
            $table->index('request_status');
        });
    }

    public function down(): void {
        $this->dropTable('donation_system_mpesa_transactions');
    }
}