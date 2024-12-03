<?php if (!defined('ABSPATH')) exit; ?>

<div class="settings-section">
    <h2><?php _e('M-Pesa Test Transaction', 'wp-donation-system'); ?></h2>
    <div class="mpesa-test-section">
        <div class="test-intro">
            <p class="description">
                <?php _e('Test your M-Pesa integration by sending a minimal test transaction (5 KES).', 'wp-donation-system'); ?>
            </p>
            <div class="test-steps">
                <h4><?php _e('Test Process:', 'wp-donation-system'); ?></h4>
                <ol>
                    <li><?php _e('Enter your M-Pesa registered phone number', 'wp-donation-system'); ?></li>
                    <li><?php _e('Click "Send Test Transaction"', 'wp-donation-system'); ?></li>
                    <li><?php _e('Check your phone for the STK push notification', 'wp-donation-system'); ?></li>
                    <li><?php _e('Enter your M-Pesa PIN to complete the test', 'wp-donation-system'); ?></li>
                </ol>
            </div>
        </div>
        
        <div class="test-form">
            <div class="phone-input-group">
                <label for="test_phone_number"><?php _e('Test Phone Number', 'wp-donation-system'); ?></label>
                <div class="input-wrapper">
                    <input type="tel" 
                        id="test_phone_number" 
                        class="regular-text" 
                        placeholder="254XXXXXXXXX"
                        pattern="^254[0-9]{9}$"
                    >
                    <span class="format-hint">Format: 254XXXXXXXXX</span>
                </div>
            </div>

            <div class="test-controls">
                <button type="button" id="test_mpesa_credentials" class="button button-primary">
                    <span class="dashicons dashicons-smartphone"></span>
                    <?php _e('Send Test Transaction (5 KES)', 'wp-donation-system'); ?>
                </button>
                <span class="spinner"></span>
            </div>
            
            <div id="test_result" class="hidden test-result-box">
                <div class="result-header">
                    <span class="status-icon"></span>
                    <p class="status"></p>
                </div>
                <div class="result-details">
                    <p class="message"></p>
                    <div class="transaction-info hidden">
                        <table>
                            <tr>
                                <th><?php _e('Transaction ID:', 'wp-donation-system'); ?></th>
                                <td class="transaction-id"></td>
                            </tr>
                            <tr>
                                <th><?php _e('Status:', 'wp-donation-system'); ?></th>
                                <td class="transaction-status"></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="test-logs">
        <h3><?php _e('Recent Test Transactions', 'wp-donation-system'); ?></h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Time', 'wp-donation-system'); ?></th>
                    <th><?php _e('Phone Number', 'wp-donation-system'); ?></th>
                    <th><?php _e('Amount', 'wp-donation-system'); ?></th>
                    <th><?php _e('Status', 'wp-donation-system'); ?></th>
                    <th><?php _e('Transaction ID', 'wp-donation-system'); ?></th>
                </tr>
            </thead>
            <tbody id="test-transactions">
                <?php
                global $wpdb;
                $test_transactions = $wpdb->get_results(
                    "SELECT * FROM {$wpdb->prefix}donations 
                    WHERE metadata LIKE '%test_transaction%' 
                    ORDER BY created_at DESC 
                    LIMIT 10"
                );

                foreach ($test_transactions as $transaction) {
                    $metadata = json_decode($transaction->metadata, true);
                    ?>
                    <tr>
                        <td><?php echo esc_html($transaction->created_at); ?></td>
                        <td><?php echo esc_html($transaction->donor_phone); ?></td>
                        <td><?php echo esc_html($transaction->amount) . ' ' . esc_html($transaction->currency); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo esc_attr($transaction->status); ?>">
                                <?php echo esc_html(ucfirst($transaction->status)); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($transaction->transaction_id ?: '-'); ?></td>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<style>
/* Add existing styles from debug.php */
/* Add new styles for test transactions table */
.test-logs {
    margin-top: 30px;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-processing {
    background: #d1ecf1;
    color: #0c5460;
}

.status-completed {
    background: #d4edda;
    color: #155724;
}

.status-failed {
    background: #f8d7da;
    color: #721c24;
}
</style> 

<script>
jQuery(document).ready(function($) {
    // Function to update test status display
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
        
        if (data.details) {
            $message.html(data.details);
        }
        
        $result.slideDown();
    }

    // Function to check test transaction status
    function checkTestStatus() {
        var checkInterval = setInterval(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_test_transaction_status',
                    security: window.wp_donation_system.nonce
                },
                success: function(response) {
                    if (response.success) {
                        if (response.data.status === 'completed') {
                            clearInterval(checkInterval);
                            updateTestStatus('success', 
                                response.data.success_message || window.wp_donation_system.strings.test_success,
                                { transaction_id: response.data.transaction_id }
                            );
                        } else if (response.data.status === 'failed') {
                            clearInterval(checkInterval);
                            updateTestStatus('error', 
                                response.data.error_details || window.wp_donation_system.strings.test_failed
                            );
                        }
                    }
                }
            });
        }, 5000);

        // Stop checking after 2 minutes
        setTimeout(function() {
            clearInterval(checkInterval);
            if ($('#test_result .status').text().indexOf('Checking') !== -1) {
                updateTestStatus('error', window.wp_donation_system.strings.test_timeout);
            }
        }, 120000);
    }

    // Clear logs
    $('#clear-logs').click(function() {
        if (!confirm(window.wp_donation_system.strings.confirm_clear_logs)) {
            return;
        }

        $.post(ajaxurl, {
            action: 'clear_donation_logs',
            security: window.wp_donation_system.nonce
        }, function(response) {
            if (response.success) {
                $('#log-entries').empty();
            }
        });
    });

    // Test transaction handling
    $('#test_mpesa_credentials').on('click', function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var $result = $('#test_result');
        var phone_number = $('#test_phone_number').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_mpesa_credentials',
                security: window.wp_donation_system.nonce,
                phone_number: phone_number
            },
            beforeSend: function() {
                $button.prop('disabled', true);
                $spinner.addClass('is-active');
                updateTestStatus('checking', window.wp_donation_system.strings.checking_status);
            },
            success: function(response) {
                if (response.success) {
                    checkTestStatus();
                } else {
                    updateTestStatus('error', response.data.message);
                }
            },
            error: function() {
                updateTestStatus('error', window.wp_donation_system.strings.network_error);
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });
});
</script> 