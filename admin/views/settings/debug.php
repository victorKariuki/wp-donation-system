<?php if (!defined('ABSPATH')) exit; ?>
<div class="debug-section">
    <h2><?php _e('System Information', 'wp-donation-system'); ?></h2>
    
    <table class="widefat debug-info">
        <tbody>
            <tr>
                <th><?php _e('PHP Version', 'wp-donation-system'); ?></th>
                <td><?php echo PHP_VERSION; ?></td>
            </tr>
            <tr>
                <th><?php _e('WordPress Version', 'wp-donation-system'); ?></th>
                <td><?php echo get_bloginfo('version'); ?></td>
            </tr>
            <tr>
                <th><?php _e('Plugin Version', 'wp-donation-system'); ?></th>
                <td><?php echo WP_DONATION_SYSTEM_VERSION; ?></td>
            </tr>
            <tr>
                <th><?php _e('Debug Mode', 'wp-donation-system'); ?></th>
                <td><?php echo WP_DEBUG ? __('Enabled', 'wp-donation-system') : __('Disabled', 'wp-donation-system'); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="settings-section">
    <h2><?php _e('System Logs', 'wp-donation-system'); ?></h2>
    <div class="log-viewer-section">
        <!-- Log Viewer Controls -->
        <div class="log-controls">
            <div class="filters">
                <select id="log-level-filter">
                    <option value=""><?php _e('All Levels', 'wp-donation-system'); ?></option>
                    <option value="info"><?php _e('Info', 'wp-donation-system'); ?></option>
                    <option value="error"><?php _e('Error', 'wp-donation-system'); ?></option>
                    <option value="warning"><?php _e('Warning', 'wp-donation-system'); ?></option>
                    <option value="debug"><?php _e('Debug', 'wp-donation-system'); ?></option>
                </select>
                
                <input type="date" id="log-date-start" placeholder="<?php _e('Start Date', 'wp-donation-system'); ?>">
                <input type="date" id="log-date-end" placeholder="<?php _e('End Date', 'wp-donation-system'); ?>">
            </div>
            
            <div class="pagination-controls">
                <select id="logs-per-page">
                    <option value="20">20 <?php _e('per page', 'wp-donation-system'); ?></option>
                    <option value="50">50 <?php _e('per page', 'wp-donation-system'); ?></option>
                    <option value="100">100 <?php _e('per page', 'wp-donation-system'); ?></option>
                </select>
            </div>
            
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
                    $logs_data = $logger->get_logs(['per_page' => 20, 'page' => 1], true);
                    $logs = $logs_data['logs'];
                    foreach ($logs as $log) {
                        $context = !empty($log->context) ? json_decode($log->context) : null;
                        ?>
                        <tr class="log-entry log-level-<?php echo esc_attr($log->level); ?>">
                            <td><?php echo esc_html($log->timestamp); ?></td>
                            <td><span class="log-level"><?php echo esc_html(strtoupper($log->level)); ?></span></td>
                            <td><?php echo esc_html($log->message); ?></td>
                            <td>
                                <?php if ($context && !empty((array)$context)): ?>
                                    <button class="button-link toggle-context" role="button">
                                        <?php _e('Show Details', 'wp-donation-system'); ?>
                                    </button>
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
                    <span class="displaying-num">
                        <?php printf(_n('%s item', '%s items', $logs_data['total'], 'wp-donation-system'), number_format_i18n($logs_data['total'])); ?>
                    </span>
                    <span class="pagination-links">
                        <button class="first-page button" aria-label="<?php esc_attr_e('First page', 'wp-donation-system'); ?>">&laquo;</button>
                        <button class="prev-page button" aria-label="<?php esc_attr_e('Previous page', 'wp-donation-system'); ?>">&lsaquo;</button>
                        <span class="paging-input">
                            <label for="current-page-selector" class="screen-reader-text"><?php _e('Current Page', 'wp-donation-system'); ?></label>
                            <input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="1" aria-describedby="table-paging">
                            <span class="tablenav-paging-text"> of <span class="total-pages"><?php echo $logs_data['total_pages']; ?></span></span>
                        </span>
                        <button class="next-page button" aria-label="<?php esc_attr_e('Next page', 'wp-donation-system'); ?>">&rsaquo;</button>
                        <button class="last-page button" aria-label="<?php esc_attr_e('Last page', 'wp-donation-system'); ?>">&raquo;</button>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Test Transaction Styles */
.mpesa-test-section {
    background: #fff;
    padding: 25px;
    border: 1px solid #ddd;
    border-radius: 8px;
    margin-bottom: 30px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.test-intro {
    margin-bottom: 25px;
}

.test-steps {
    background: #f8f9fa;
    padding: 15px 20px;
    border-radius: 6px;
    margin-top: 15px;
}

.test-steps h4 {
    margin: 0 0 10px;
    color: #1d2327;
}

.test-steps ol {
    margin: 0;
    padding-left: 20px;
}

.test-steps li {
    margin-bottom: 8px;
    color: #50575e;
}

.phone-input-group {
    margin-bottom: 20px;
}

.phone-input-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
}

.input-wrapper {
    position: relative;
    max-width: 300px;
}

.format-hint {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #666;
    font-size: 12px;
}

.test-controls {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

#test_mpesa_credentials {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
}

.test-result-box {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    border-left: 4px solid transparent;
}

.result-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.status-icon {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.status {
    font-size: 16px;
    font-weight: 600;
    margin: 0;
}

.transaction-info {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(0,0,0,0.1);
}

.transaction-info table {
    width: 100%;
    border-collapse: collapse;
}

.transaction-info th {
    text-align: left;
    padding: 8px;
    width: 140px;
    color: #666;
}

.transaction-info td {
    padding: 8px;
    font-weight: 500;
}

/* Status-specific styles */
.notice-info {
    border-left-color: #00a0d2;
    background: #f0f6fc;
}

.notice-success {
    border-left-color: #46b450;
    background: #ecf7ed;
}

.notice-error {
    border-left-color: #dc3232;
    background: #fcf0f0;
}

.hidden {
    display: none;
}

/* Spinner improvements */
.spinner {
    float: none;
    margin: 0;
    opacity: 0;
}

.spinner.is-active {
    opacity: 1;
}

/* Log Viewer Styles */
.log-controls {
    margin: 20px 0;
    display: flex;
    gap: 10px;
    align-items: center;
}

.log-viewer {
    margin-top: 20px;
    background: #fff;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
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
    padding: 15px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
    max-height: 300px;
    overflow-y: auto;
}

.context-data pre {
    margin: 0;
    white-space: pre-wrap;
    word-wrap: break-word;
    font-family: monospace;
    font-size: 13px;
    line-height: 1.4;
    color: #333;
}

.button-link.toggle-context {
    color: #0073aa;
    text-decoration: none;
    cursor: pointer;
    padding: 0;
    border: none;
    background: none;
    outline: none;
    transition: color 0.2s ease;
}

.button-link.toggle-context:hover {
    color: #00a0d2;
    text-decoration: underline;
}

.button-link.toggle-context:focus {
    box-shadow: none;
    color: #00a0d2;
}

.no-context {
    color: #6c757d;
    font-style: italic;
}

.notice-info {
    border-left-color: #00a0d2;
    background: #f0f6fc;
}

.notice-success {
    border-left-color: #46b450;
    background: #ecf7ed;
}

.notice-error {
    border-left-color: #dc3232;
    background: #fcf0f0;
}

.hidden {
    display: none;
}

/* Pagination Styles */
.tablenav {
    margin: 20px 0;
    display: flex;
    justify-content: flex-end;
}

.tablenav-pages {
    display: flex;
    align-items: center;
    gap: 10px;
}

.pagination-links {
    display: flex;
    align-items: center;
    gap: 5px;
}

.pagination-links .button {
    padding: 0 8px;
    line-height: 2;
    min-width: 30px;
    text-align: center;
}

.paging-input {
    margin: 0 5px;
    display: flex;
    align-items: center;
    gap: 5px;
}

.current-page {
    width: 50px;
    text-align: center;
}

/* Log Controls */
.log-controls {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

.filters {
    display: flex;
    gap: 10px;
    align-items: center;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Phone number formatting
    $('#test_phone_number').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (!value.startsWith('254') && value.length > 0) {
            if (value.startsWith('0')) {
                value = '254' + value.substring(1);
            } else if (value.startsWith('7') || value.startsWith('1')) {
                value = '254' + value;
            }
        }
        $(this).val(value);
    });

    // Enhance test result display
    function updateTestStatus(status, message, data = {}) {
        var $result = $('#test_result');
        var $status = $result.find('.status');
        var $message = $result.find('.message');
        var $transactionInfo = $result.find('.transaction-info');
        
        $result.removeClass('hidden notice-info notice-success notice-error');
        
        switch(status) {
            case 'checking':
                $result.addClass('notice-info');
                $status.html('<span class="dashicons dashicons-clock"></span> ' + message);
                break;
            case 'success':
                $result.addClass('notice-success');
                $status.html('<span class="dashicons dashicons-yes-alt"></span> ' + message);
                if (data.transaction_id) {
                    $transactionInfo.removeClass('hidden')
                        .find('.transaction-id').text(data.transaction_id);
                    $transactionInfo.find('.transaction-status').text('Completed');
                }
                break;
            case 'error':
                $result.addClass('notice-error');
                $status.html('<span class="dashicons dashicons-warning"></span> ' + message);
                break;
        }
        
        $message.html(data.details || '');
        $result.slideDown();
    }

    // Function to attach context toggle handlers
    function attachContextHandlers() {
        $('.toggle-context').off('click').on('click', function(e) {
            e.preventDefault();
            const $context = $(this).siblings('.context-data');
            $context.toggleClass('hidden');
            $(this).text($context.hasClass('hidden') ? 
                '<?php _e('Show Details', 'wp-donation-system'); ?>' : 
                '<?php _e('Hide Details', 'wp-donation-system'); ?>'
            );
        });
    }

    // Initial handler attachment
    attachContextHandlers();

    // Pagination variables
    var currentPage = 1;
    var totalPages = <?php echo $logs_data['total_pages']; ?>;

    // Refresh logs
    function refreshLogs() {
        const data = {
            action: 'get_donation_logs',
            security: window.wp_donation_system.nonce,
            level: $('#log-level-filter').val(),
            start_date: $('#log-date-start').val(),
            end_date: $('#log-date-end').val(),
            page: currentPage,
            per_page: $('#logs-per-page').val()
        };

        $.post(ajaxurl, data, function(response) {
            if (response.success) {
                $('#log-entries').html(response.data.html);
                updatePagination(response.data.pagination);
                // Reattach event handlers to new content
                attachContextHandlers();
            }
        });
    }

    // Update pagination display
    function updatePagination(pagination) {
        totalPages = pagination.total_pages;
        $('.displaying-num').text(pagination.total_text);
        $('.total-pages').text(pagination.total_pages);
        $('#current-page-selector').val(currentPage);
        
        // Update button states
        $('.first-page, .prev-page').prop('disabled', currentPage === 1);
        $('.last-page, .next-page').prop('disabled', currentPage === totalPages);
    }

    // Pagination event handlers
    $('.first-page').click(function() {
        if (currentPage !== 1) {
            currentPage = 1;
            refreshLogs();
        }
    });

    $('.prev-page').click(function() {
        if (currentPage > 1) {
            currentPage--;
            refreshLogs();
        }
    });

    $('.next-page').click(function() {
        if (currentPage < totalPages) {
            currentPage++;
            refreshLogs();
        }
    });

    $('.last-page').click(function() {
        if (currentPage !== totalPages) {
            currentPage = totalPages;
            refreshLogs();
        }
    });

    $('#current-page-selector').on('change keyup', function(e) {
        if (e.type === 'keyup' && e.keyCode !== 13) return;
        
        var page = parseInt($(this).val());
        if (page > 0 && page <= totalPages && page !== currentPage) {
            currentPage = page;
            refreshLogs();
        }
    });

    $('#logs-per-page').change(function() {
        currentPage = 1;
        refreshLogs();
    });

    // Clear logs
    $('#clear-logs').click(function() {
        if (!confirm(wp_donation_system.strings.confirm_clear_logs)) {
            return;
        }

        $.post(ajaxurl, {
            action: 'clear_donation_logs',
            security: wp_donation_system.nonce
        }, function(response) {
            if (response.success) {
                $('#log-entries').empty();
            }
        });
    });

    // Auto-refresh logs every 30 seconds when test transaction is running
    let autoRefresh;
    $('#test_mpesa_credentials').click(function() {
        autoRefresh = setInterval(function() {
            $('#refresh-logs').click();
        }, 30000);

        // Stop auto-refresh after 2 minutes
        setTimeout(function() {
            clearInterval(autoRefresh);
        }, 120000);
    });

    // Refresh button click handler
    $('#refresh-logs').on('click', function() {
        refreshLogs();
    });

    // Filter change handlers
    $('#log-level-filter, #log-date-start, #log-date-end').on('change', function() {
        currentPage = 1; // Reset to first page when filters change
        refreshLogs();
    });
});
</script> 