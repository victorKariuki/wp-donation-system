<?php if (!defined('ABSPATH')) exit; ?>

<div class="settings-section">
    <h2><?php _e('Debug Information', 'wp-donation-system'); ?></h2>
    
    <!-- Log Viewer Controls -->
    <div class="log-controls">
        <select id="log-level-filter">
            <option value=""><?php _e('All Levels', 'wp-donation-system'); ?></option>
            <option value="info"><?php _e('Info', 'wp-donation-system'); ?></option>
            <option value="error"><?php _e('Error', 'wp-donation-system'); ?></option>
            <option value="warning"><?php _e('Warning', 'wp-donation-system'); ?></option>
            <option value="debug"><?php _e('Debug', 'wp-donation-system'); ?></option>
        </select>
        
        <input type="date" id="log-date-start" placeholder="<?php _e('Start Date', 'wp-donation-system'); ?>">
        <input type="date" id="log-date-end" placeholder="<?php _e('End Date', 'wp-donation-system'); ?>">
        
        <button class="button" id="refresh-logs">
            <?php _e('Refresh', 'wp-donation-system'); ?>
        </button>
        
        <button class="button" id="clear-logs">
            <?php _e('Clear Logs', 'wp-donation-system'); ?>
        </button>
    </div>

    <!-- Log Viewer -->
    <div class="log-viewer">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Time', 'wp-donation-system'); ?></th>
                    <th><?php _e('Level', 'wp-donation-system'); ?></th>
                    <th><?php _e('Message', 'wp-donation-system'); ?></th>
                    <th><?php _e('Context', 'wp-donation-system'); ?></th>
                </tr>
            </thead>
            <tbody id="log-entries">
                <?php
                $logger = new WP_Donation_System_Logger();
                $logs = $logger->get_logs(['limit' => 50]);
                foreach ($logs as $log) {
                    // Safely decode context
                    $context = !empty($log->context) ? json_decode($log->context) : null;
                    ?>
                    <tr class="log-entry log-level-<?php echo esc_attr($log->level); ?>">
                        <td><?php echo esc_html($log->timestamp); ?></td>
                        <td><span class="log-level"><?php echo esc_html(strtoupper($log->level)); ?></span></td>
                        <td><?php echo esc_html($log->message); ?></td>
                        <td>
                            <?php if ($context && !empty((array)$context)): ?>
                                <button class="button-link toggle-context"><?php _e('Show Details', 'wp-donation-system'); ?></button>
                                <div class="context-data hidden">
                                    <pre><?php echo esc_html(json_encode($context, JSON_PRETTY_PRINT)); ?></pre>
                                </div>
                            <?php else: ?>
                                <span class="no-context"><?php _e('No additional context', 'wp-donation-system'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num"></span>
                <span class="pagination-links"></span>
            </div>
        </div>
    </div>

    <hr>

    <!-- System Information -->
    <h3><?php _e('System Information', 'wp-donation-system'); ?></h3>
    <div class="debug-info">
        <h4><?php _e('Current Settings', 'wp-donation-system'); ?></h4>
        <pre><?php print_r($settings); ?></pre>

        <h4><?php _e('Default Settings', 'wp-donation-system'); ?></h4>
        <pre><?php print_r($default_settings); ?></pre>
    </div>
</div>

<style>
.log-controls {
    margin: 20px 0;
    display: flex;
    gap: 10px;
    align-items: center;
}

.log-level {
    padding: 2px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
}

.log-level-error .log-level {
    background: #f8d7da;
    color: #721c24;
}

.log-level-warning .log-level {
    background: #fff3cd;
    color: #856404;
}

.log-level-info .log-level {
    background: #d1ecf1;
    color: #0c5460;
}

.log-level-debug .log-level {
    background: #d6d8d9;
    color: #1b1e21;
}

.context-data {
    margin-top: 10px;
    background: #f8f9fa;
    padding: 10px;
    border-radius: 4px;
}

.context-data pre {
    margin: 0;
    white-space: pre-wrap;
}

.hidden {
    display: none;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Toggle context data
    $('.toggle-context').click(function() {
        const $context = $(this).siblings('.context-data');
        $context.toggleClass('hidden');
        $(this).text($context.hasClass('hidden') ? 
            '<?php _e('Show Details', 'wp-donation-system'); ?>' : 
            '<?php _e('Hide Details', 'wp-donation-system'); ?>'
        );
    });

    // Refresh logs
    $('#refresh-logs').click(function() {
        const data = {
            action: 'get_donation_logs',
            security: wpDonationSystem.logs_nonce,
            level: $('#log-level-filter').val(),
            start_date: $('#log-date-start').val(),
            end_date: $('#log-date-end').val()
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                $('#log-entries').html(response.data.html);
            }
        });
    });

    // Clear logs
    $('#clear-logs').click(function() {
        if (!confirm(wpDonationSystem.i18n.confirmClearLogs)) {
            return;
        }

        const data = {
            action: 'clear_donation_logs',
            security: wpDonationSystem.logs_nonce,
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                $('#log-entries').empty();
            }
        });
    });
});
</script> 