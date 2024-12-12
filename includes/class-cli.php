<?php
class WP_Donation_System_CLI {
    /**
     * Run database migrations
     * 
     * ## OPTIONS
     * 
     * [--force]
     * : Force run all migrations
     * 
     * [--reset]
     * : Reset and rerun all migrations
     */
    public function migrate($args, $assoc_args) {
        try {
            $migration_manager = new WP_Donation_System_Migration_Manager();

            if (isset($assoc_args['reset'])) {
                WP_CLI::log('Resetting migrations...');
                $migration_manager->reset();
            }

            WP_CLI::log('Running migrations...');
            $migration_manager->migrate(isset($assoc_args['force']));
            WP_CLI::success('Migrations completed successfully');

        } catch (Exception $e) {
            WP_CLI::error($e->getMessage());
        }
    }
}

if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('donation-system', 'WP_Donation_System_CLI');
} 