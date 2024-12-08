jQuery(document).ready(function($) {
    const PaymentGateways = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('.accordion-header').on('click', this.handleAccordionToggle);
            $('.toggle-switch input').on('change', this.handleGatewayToggle);
            $('.gateway-settings-form').on('submit', this.handleFormSubmit);
            $('.toggle-password').on('click', this.handlePasswordToggle);
            $('.test-connection').on('click', this.handleTestConnection);
            $('.reset-settings').on('click', this.handleResetSettings);
        },

        handleAccordionToggle: function(e) {
            if ($(e.target).closest('.toggle-switch, .test-connection').length) {
                return;
            }
            
            const $accordion = $(this).closest('.gateway-accordion');
            const wasOpen = $accordion.hasClass('open');
            
            $('.gateway-accordion').removeClass('open');
            
            if (!wasOpen) {
                $accordion.addClass('open');
            }
        },

        handleGatewayToggle: function() {
            const $toggle = $(this);
            const gatewayId = $toggle.data('gateway');
            const isEnabled = $toggle.prop('checked');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'toggle_gateway',
                    gateway: gatewayId,
                    enabled: isEnabled ? 1 : 0,
                    security: wpDonationSystem.nonce
                },
                success: function(response) {
                    if (response.success) {
                        const $accordion = $toggle.closest('.gateway-accordion');
                        $accordion.toggleClass('enabled', isEnabled);
                        
                        const $badge = $accordion.find('.status-badge');
                        $badge.toggleClass('enabled', isEnabled)
                            .toggleClass('disabled', !isEnabled)
                            .text(isEnabled ? wpDonationSystem.strings.enabled : wpDonationSystem.strings.disabled);
                        
                        PaymentGateways.showMessage($accordion.find('form'), wpDonationSystem.strings.saved, 'success');
                    } else {
                        // Revert toggle if failed
                        $toggle.prop('checked', !isEnabled);
                        PaymentGateways.showMessage($accordion.find('form'), response.data.message || wpDonationSystem.strings.error, 'error');
                    }
                },
                error: function() {
                    // Revert toggle if failed
                    $toggle.prop('checked', !isEnabled);
                    PaymentGateways.showMessage($accordion.find('form'), wpDonationSystem.strings.error, 'error');
                }
            });
        },

        handleFormSubmit: function(e) {
            e.preventDefault();
            const $form = $(this);
            const $submitButton = $form.find('.save-settings');
            const $spinner = $submitButton.find('.dashicons');
            const gatewayId = $form.data('gateway');
            
            // Get enabled state from toggle switch
            const isEnabled = $('.gateway-accordion[data-gateway="' + gatewayId + '"] .toggle-switch input').is(':checked');
            
            // Create FormData object
            const formData = new FormData($form[0]);
            formData.append(gatewayId + '_enabled', isEnabled ? '1' : '0');
            formData.append('action', 'save_gateway_settings');
            formData.append('security', wpDonationSystem.nonce);
            
            // Show loading state
            $submitButton.prop('disabled', true);
            $spinner.addClass('spin');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        PaymentGateways.showMessage($form, wpDonationSystem.strings.saved, 'success');
                        // Update gateway status
                        const $accordion = $form.closest('.gateway-accordion');
                        $accordion.toggleClass('enabled', isEnabled);
                        $accordion.find('.status-badge')
                            .toggleClass('enabled', isEnabled)
                            .toggleClass('disabled', !isEnabled)
                            .text(isEnabled ? wpDonationSystem.strings.enabled : wpDonationSystem.strings.disabled);
                    } else {
                        PaymentGateways.showMessage($form, response.data.message || wpDonationSystem.strings.error, 'error');
                    }
                },
                error: function() {
                    PaymentGateways.showMessage($form, wpDonationSystem.strings.error, 'error');
                },
                complete: function() {
                    $submitButton.prop('disabled', false);
                    $spinner.removeClass('spin');
                }
            });
        },

        showMessage: function($form, message, type) {
            const $message = $('<div>', {
                class: 'notice notice-' + type + ' is-dismissible',
                html: $('<p>', { text: message })
            });
            
            $form.find('.notice').remove();
            $form.prepend($message);
            
            setTimeout(function() {
                $message.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },

        updateGatewayStatus: function($form) {
            const gatewayId = $form.find('input[name="gateway"]').val();
            const isEnabled = $form.find('input[name="' + gatewayId + '_enabled"]').is(':checked');
            
            const $accordion = $form.closest('.gateway-accordion');
            $accordion.toggleClass('enabled', isEnabled);
            
            const $badge = $accordion.find('.status-badge');
            $badge.toggleClass('enabled', isEnabled)
                .toggleClass('disabled', !isEnabled)
                .text(isEnabled ? wpDonationSystem.strings.enabled : wpDonationSystem.strings.disabled);
        },

        resetSubmitButton: function($button) {
            $button.prop('disabled', false)
                .find('.dashicons')
                .removeClass('dashicons-update-alt spin')
                .addClass('dashicons-saved');
        },

        handlePasswordToggle: function() {
            const $input = $(this).closest('.password-field').find('input');
            const $icon = $(this).find('.dashicons');
            
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
                $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
            } else {
                $input.attr('type', 'password');
                $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
            }
        },

        handleTestConnection: function() {
            const $button = $(this);
            const gatewayId = $button.data('gateway');
            
            PaymentGateways.testGatewayConnection($button, gatewayId);
        },

        testGatewayConnection: function($button, gatewayId) {
            $button.prop('disabled', true)
                .find('.dashicons')
                .addClass('spin');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'test_gateway_connection',
                    gateway: gatewayId,
                    security: wpDonationSystem.nonce
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                    } else {
                        alert(response.data.message || wpDonationSystem.strings.test_failed);
                    }
                },
                error: function() {
                    alert(wpDonationSystem.strings.network_error);
                },
                complete: function() {
                    $button.prop('disabled', false)
                        .find('.dashicons')
                        .removeClass('spin');
                }
            });
        },

        handleResetSettings: function() {
            if (confirm(wpDonationSystem.strings.confirm_reset)) {
                const $form = $(this).closest('form');
                const gatewayId = $form.data('gateway');
                
                PaymentGateways.resetGatewaySettings($form, gatewayId);
            }
        },

        resetGatewaySettings: function($form, gatewayId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'reset_gateway_settings',
                    gateway: gatewayId,
                    security: wpDonationSystem.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        PaymentGateways.showMessage($form, response.data.message, 'error');
                    }
                },
                error: function() {
                    PaymentGateways.showMessage($form, wpDonationSystem.strings.error, 'error');
                }
            });
        }
    };

    PaymentGateways.init();
}); 