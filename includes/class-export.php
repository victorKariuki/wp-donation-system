<?php
class WP_Donation_System_Export {
    public function export_csv() {
        global $wpdb;
        
        $donations = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}donations ORDER BY created_at DESC");
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="donations-export-' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, array('ID', 'Donor Name', 'Email', 'Amount', 'Currency', 'Payment Method', 'Status', 'Date'));
        
        foreach ($donations as $donation) {
            fputcsv($output, array(
                $donation->id,
                $donation->donor_name,
                $donation->donor_email,
                $donation->amount,
                $donation->currency,
                $donation->payment_method,
                $donation->status,
                $donation->created_at
            ));
        }
        
        fclose($output);
        exit;
    }
} 