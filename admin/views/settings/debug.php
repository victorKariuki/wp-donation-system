<?php if (!defined('ABSPATH')) exit; ?>

<div class="debug-settings-wrapper">
    <div class="log-filters">
        <select id="log-level-filter">
            <option value=""><?php _e('All Levels', 'wp-donation-system'); ?></option>
            <option value="debug"><?php _e('Debug', 'wp-donation-system'); ?></option>
            <option value="info"><?php _e('Info', 'wp-donation-system'); ?></option>
            <option value="warning"><?php _e('Warning', 'wp-donation-system'); ?></option>
            <option value="error"><?php _e('Error', 'wp-donation-system'); ?></option>
        </select>

        <input type="date" id="log-date-from" placeholder="<?php _e('From Date', 'wp-donation-system'); ?>">
        <input type="date" id="log-date-to" placeholder="<?php _e('To Date', 'wp-donation-system'); ?>">
        
        <button type="button" class="button" id="refresh-logs">
            <span class="dashicons dashicons-update"></span>
            <?php _e('Refresh', 'wp-donation-system'); ?>
        </button>

        <button type="button" class="button" id="clear-logs">
            <span class="dashicons dashicons-trash"></span>
            <?php _e('Clear Logs', 'wp-donation-system'); ?>
        </button>
    </div>

    <div class="log-table-wrapper">
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-timestamp"><?php _e('Timestamp', 'wp-donation-system'); ?></th>
                    <th class="column-level"><?php _e('Level', 'wp-donation-system'); ?></th>
                    <th class="column-message"><?php _e('Message', 'wp-donation-system'); ?></th>
                    <th class="column-context"><?php _e('Context', 'wp-donation-system'); ?></th>
                </tr>
            </thead>
            <tbody id="log-entries">
                <!-- Log entries will be loaded here -->
            </tbody>
        </table>
    </div>

    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num"></span>
            <span class="pagination-links">
                <button class="button prev-page" disabled>‹</button>
                <span class="paging-input">
                    <label for="current-page-selector" class="screen-reader-text">
                        <?php _e('Current Page', 'wp-donation-system'); ?>
                    </label>
                    <span class="current-page">1</span>
                    <span class="tablenav-paging-text"> of <span class="total-pages">1</span></span>
                </span>
                <button class="button next-page" disabled>›</button>
            </span>
        </div>
    </div>
</div>

<style>
.debug-settings-wrapper {
    margin: 20px 0;
}

.log-filters {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.log-table-wrapper {
    margin-bottom: 20px;
}

.column-timestamp {
    width: 150px;
}

.column-level {
    width: 100px;
}

.column-context {
    width: 300px;
}

.log-level {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: 500;
    text-transform: uppercase;
}

.log-level-debug { background: #e5e7eb; color: #374151; }
.log-level-info { background: #dbeafe; color: #1e40af; }
.log-level-warning { background: #fef3c7; color: #92400e; }
.log-level-error { background: #fee2e2; color: #991b1b; }

.context-data {
    max-height: 200px;
    overflow-y: auto;
    font-family: monospace;
    font-size: 12px;
    white-space: pre-wrap;
    background: #f8fafc;
    padding: 8px;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
}
</style>

<script>
jQuery(document).ready(function($) {
    const Debug = {
        currentPage: 1,
        totalPages: 1,
        
        init: function() {
            this.bindEvents();
            this.loadLogs();
        },
        
        bindEvents: function() {
            $('#refresh-logs').on('click', () => this.loadLogs());
            $('#clear-logs').on('click', () => this.clearLogs());
            $('#log-level-filter, #log-date-from, #log-date-to').on('change', () => this.loadLogs());
            $('.prev-page').on('click', () => this.changePage(-1));
            $('.next-page').on('click', () => this.changePage(1));
        },
        
        loadLogs: function() {
            const filters = {
                level: $('#log-level-filter').val(),
                date_from: $('#log-date-from').val(),
                date_to: $('#log-date-to').val(),
                page: this.currentPage
            };
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_debug_logs',
                    security: wpDonationSystem.nonce,
                    ...filters
                },
                success: (response) => {
                    if (response.success) {
                        this.renderLogs(response.data);
                    } else {
                        console.error('Failed to load logs:', response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Ajax error:', error);
                }
            });
        },
        
        renderLogs: function(data) {
            const $tbody = $('#log-entries');
            $tbody.empty();
            
            if (!data.logs.length) {
                $tbody.append(`
                    <tr>
                        <td colspan="4" class="no-logs">
                            ${wpDonationSystem.strings.no_logs_found}
                        </td>
                    </tr>
                `);
                return;
            }
            
            data.logs.forEach(log => {
                const context = log.context ? JSON.parse(log.context) : {};
                $tbody.append(`
                    <tr>
                        <td>${log.timestamp}</td>
                        <td>
                            <span class="log-level log-level-${log.level}">
                                ${log.level}
                            </span>
                        </td>
                        <td>${log.message}</td>
                        <td>
                            <div class="context-data">
                                ${JSON.stringify(context, null, 2)}
                            </div>
                        </td>
                    </tr>
                `);
            });
            
            // Update pagination
            this.totalPages = data.pages;
            this.updatePagination();
        },
        
        updatePagination: function() {
            $('.current-page').text(this.currentPage);
            $('.total-pages').text(this.totalPages);
            $('.prev-page').prop('disabled', this.currentPage <= 1);
            $('.next-page').prop('disabled', this.currentPage >= this.totalPages);
            $('.displaying-num').text(
                wpDonationSystem.strings.items_found.replace('{count}', this.totalPages)
            );
        },
        
        changePage: function(delta) {
            const newPage = this.currentPage + delta;
            if (newPage >= 1 && newPage <= this.totalPages) {
                this.currentPage = newPage;
                this.loadLogs();
            }
        },
        
        clearLogs: function() {
            if (!confirm(wpDonationSystem.strings.confirm_clear_logs)) {
                return;
            }
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'clear_debug_logs',
                    security: wpDonationSystem.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.loadLogs();
                    } else {
                        console.error('Failed to clear logs:', response.data.message);
                    }
                }
            });
        }
    };
    
    Debug.init();
});
</script> 