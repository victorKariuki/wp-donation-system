<?php
if (!defined('ABSPATH')) {
    exit;
}

$total_donations = $wpdb->get_var("SELECT SUM(amount) FROM {$wpdb->prefix}donations WHERE status = 'completed'");
$donation_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}donations WHERE status = 'completed'");

$chart_data = $this->get_chart_data();
?>

<div class="wrap">
    <h1><?php _e('Donation Reports', 'wp-donation-system'); ?></h1>
    
    <div class="donation-stats">
        <div class="stat-box">
            <h3><?php _e('Total Donations', 'wp-donation-system'); ?></h3>
            <p><?php echo number_format($total_donations, 2); ?></p>
        </div>
        <div class="stat-box">
            <h3><?php _e('Number of Donations', 'wp-donation-system'); ?></h3>
            <p><?php echo $donation_count; ?></p>
        </div>
    </div>
    
    <div class="donation-charts">
        <canvas id="donations-chart"></canvas>
        <canvas id="payment-methods-chart"></canvas>
    </div>
</div> 

<script>
    const donationsChartData = <?php echo json_encode($chart_data['donations']); ?>;
    const paymentMethodsData = <?php echo json_encode($chart_data['methods']); ?>;
</script> 

<script>
    // Add Chart.js implementation
</script> 