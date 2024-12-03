jQuery(document).ready(function($) {
    // Ensure wp_donation_system is available
    if (typeof window.wp_donation_system === 'undefined') {
        console.error('WP Donation System: Required JavaScript object not found');
        return;
    }

    // Test M-Pesa credentials
    $('#test_mpesa_credentials').on('click', function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var $result = $('#test_result');
        var $status = $result.find('.status');
        var $message = $result.find('.message');
        var phone_number = $('#test_phone_number').val();

        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $result.addClass('hidden');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'test_mpesa_credentials',
                security: wp_donation_system.nonce,
                phone_number: phone_number
            },
            success: function(response) {
                if (response.success) {
                    $status.html('Checking payment status...');
                    $message.html(response.data.message);
                    $result.removeClass('hidden').addClass('notice notice-info');
                    checkTestStatus();
                } else {
                    $status.html('Test Failed');
                    $message.html(response.data.message);
                    $result.removeClass('hidden').addClass('notice notice-error');
                }
            },
            error: function() {
                $status.html('Test Failed');
                $message.html('Network error occurred');
                $result.removeClass('hidden').addClass('notice notice-error');
            },
            complete: function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
            }
        });
    });

    function checkTestStatus() {
        var checkInterval = setInterval(function() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_test_transaction_status',
                    security: wp_donation_system.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var $result = $('#test_result');
                        var $status = $result.find('.status');
                        var $message = $result.find('.message');

                        // Update status display
                        if (response.data.status === 'completed') {
                            clearInterval(checkInterval);
                            var successMessage = response.data.success_message || wp_donation_system.strings.test_success;
                            $result
                                .removeClass('notice-info notice-error')
                                .addClass('notice-success')
                                .find('.status').html(successMessage);

                            if (response.data.transaction_id) {
                                $message.html(wp_donation_system.strings.transaction_id + ': ' + response.data.transaction_id);
                            }
                        } else if (response.data.status === 'failed') {
                            clearInterval(checkInterval);
                            var errorMessage = response.data.error_details || wp_donation_system.strings.test_failed;
                            $result
                                .removeClass('notice-info notice-success')
                                .addClass('notice-error')
                                .find('.status').html(errorMessage);
                        }
                        // Update message if provided
                        if (response.data.message) {
                            $message.html(response.data.message);
                        }
                    }
                }
            });
        }, 5000); // Check every 5 seconds

        // Stop checking after 2 minutes
        setTimeout(function() {
            clearInterval(checkInterval);
            var $result = $('#test_result');
            if ($result.find('.status').text().indexOf('Checking') !== -1) {
                $result
                    .removeClass('notice-info notice-success')
                    .addClass('notice-error')
                    .find('.status').html(wp_donation_system.strings.test_timeout);
            }
        }, 120000);
    }
}); 