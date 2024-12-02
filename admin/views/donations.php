<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php _e('Donations', 'wp-donation-system'); ?></h1>
    
    <?php
    $donations_table = new WP_Donation_System_List_Table();
    $donations_table->prepare_items();
    ?>
    
    <form method="get">
        <input type="hidden" name="page" value="wp-donation-system" />
        <?php $donations_table->display(); ?>
    </form>
</div> 