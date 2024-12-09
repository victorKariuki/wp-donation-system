jQuery(document).ready(function($) {
    const DonationForm = {
        init: function() {
            this.bindEvents();
            this.initializeForm();
        },

        bindEvents: function() {
            $('.amount-preset').on('click', this.handleAmountSelection);
            $('#donation_amount').on('input', this.handleCustomAmount);
            $('.prev-step').on('click', this.prevStep);
            $('.next-step').on('click', this.nextStep);
            $('#donation-form').on('submit', function(e) {
                e.preventDefault();
                DonationForm.handleSubmit.call(this, e);
            });
            $('.payment-radio').on('change', this.handlePaymentMethodSelection);
            $('.gateway-field').on('input change', this.handleGatewayFieldInput);
            
            // Add real-time validation for donor fields
            $('#donor_name, #donor_email').on('input', function() {
                DonationForm.validateDonorDetails();
            });
            
            // Update summary on donor info change
            $('#donor_name, #donor_email').on('input', function() {
                DonationForm.updateSummary();
            });
            
            // Handle anonymous donation toggle
            $('#anonymous_donation').on('change', function() {
                DonationForm.handleAnonymousToggle();
                DonationForm.validateDonorDetails(); // Revalidate after toggle
            });
            
            // Add phone number formatting
            $('#donor_phone').on('input', function() {
                let value = $(this).val().replace(/\D/g, '');
                if (!value.startsWith('254') && value.length > 0) {
                    if (value.startsWith('0')) {
                        value = '254' + value.substring(1);
                    } else if (value.startsWith('7') || value.startsWith('1')) {
                        value = '254' + value;
                    }
                }
                $(this).val(value);
            });
        },

        initializeForm: function() {
            // Hide all steps except first
            $('.form-step').not('[data-step="1"]').hide();
            
            // Initialize first step
            this.validateStep(1);
            
            // Initialize payment method if selected
            const $selectedGateway = $('input[name="payment_method"]:checked');
            if ($selectedGateway.length) {
                this.handlePaymentMethodSelection.call($selectedGateway);
            }
        },

        handleAmountSelection: function() {
            const self = DonationForm;
            const $selected = $(this);
            const amount = $selected.data('amount');
            
            // Remove active class from all presets
            $('.amount-preset').removeClass('active');
            // Add active class to selected
            $selected.addClass('active');
            
            // Update amount input
            $('#donation_amount').val(amount);
            
            // Validate step
            self.validateStep(1);
        },

        handleCustomAmount: function() {
            const self = DonationForm;
            const value = $(this).val();
            
            // Remove active class from presets when custom amount is entered
            $('.amount-preset').removeClass('active');
            
            // Validate step
            self.validateStep(1);
        },

        updateSummary: function() {
            // Get current values
            const amount = this.getSelectedAmount();
            const donorName = $('#donor_name').val().trim();
            const donorEmail = $('#donor_email').val().trim();
            const $selectedGateway = $('input[name="payment_method"]:checked');
            
            // Update amount
            $('.amount-display').each(function() {
                $(this).text(DonationForm.formatCurrency(amount));
            });
            
            // Update donor info with fallback
            $('.donor-name-display').text(donorName || '—');
            $('.donor-email-display').text(donorEmail || '—');
            
            // Update payment method info
            if ($selectedGateway.length) {
                const $option = $selectedGateway.closest('.payment-method-option');
                const gatewayTitle = $option.find('.method-name').text();
                
                $('.payment-method-display').text(gatewayTitle);
            } else {
                $('.payment-method-display').text('—');
                $('.submit-donation .button-text').text(wpDonationSystem.strings.select_payment);
            }
            
            // Show/hide summary sections based on step
            const currentStep = $('.form-step.active').data('step');
            $('.summary-row').each(function() {
                const $row = $(this);
                const showOnStep = $row.data('show-on-step');
                if (!showOnStep || currentStep >= showOnStep) {
                    $row.slideDown();
                } else {
                    $row.slideUp();
                }
            });
        },

        getSelectedAmount: function() {
            return parseFloat($('#donation_amount').val()) || 0;
        },

        formatCurrency: function(amount) {
            const currencyCode = wpDonationSystem.currency.code;
            const currencyData = wpDonationSystem.currency.data;
            
            // Format number
            const formatted = new Intl.NumberFormat(undefined, {
                minimumFractionDigits: currencyData.decimals,
                maximumFractionDigits: currencyData.decimals,
                useGrouping: true
            }).format(amount);
            
            // Add currency symbol
            return currencyData.position === 'left' 
                ? currencyData.symbol + formatted 
                : formatted + currencyData.symbol;
        },

        validateStep: function(step) {
            let isValid = true;
            
            // Clear previous errors
            this.clearErrors();
            
            switch(step) {
                case 1: // Amount step
                    isValid = this.validateAmountStep();
                    break;
                case 2: // Donor details step
                    isValid = this.validateDonorDetails();
                    break;
                case 3: // Payment step
                    isValid = this.validatePaymentStep();
                    break;
            }
            
            // Update button states
            const $currentStep = $('.form-step[data-step="' + step + '"]');
            const $nextButton = $currentStep.find('.next-step');
            const $submitButton = $currentStep.find('.submit-donation');
            
            if ($nextButton.length) {
                $nextButton.prop('disabled', !isValid);
            }
            if ($submitButton.length) {
                $submitButton.prop('disabled', !isValid);
            }
            
            return isValid;
        },

        clearErrors: function() {
            $('.has-error').removeClass('has-error');
            $('.error-message').remove();
        },

        validateAmountStep: function() {
            const amount = parseFloat($('#donation_amount').val()) || 0;
            const minAmount = parseFloat($('#min_amount').val()) || 5;
            const maxAmount = parseFloat($('#max_amount').val()) || 10000;
            
            if (amount <= 0) {
                this.showFieldError('donation_amount', wpDonationSystem.strings.invalid_amount);
                return false;
            }
            
            if (amount < minAmount || amount > maxAmount) {
                this.showFieldError('donation_amount', 
                    wpDonationSystem.strings.amount_range
                        .replace('{min}', this.formatCurrency(minAmount))
                        .replace('{max}', this.formatCurrency(maxAmount))
                );
                return false;
            }
            
            return true;
        },

        validatePaymentStep: function() {
            const $selectedGateway = $('input[name="payment_method"]:checked');
            if (!$selectedGateway.length) {
                this.showError(wpDonationSystem.strings.select_payment);
                return false;
            }
            
            // Check if it's M-Pesa and validate phone number
            if ($selectedGateway.val() === 'mpesa') {
                const $phoneField = $('#donor_phone');
                if (!$phoneField.length || !$phoneField.val().trim()) {
                    this.showError(wpDonationSystem.strings.invalid_phone);
                    return false;
                }
                
                // Validate phone number format (254XXXXXXXXX)
                const phoneNumber = $phoneField.val().trim();
                if (!/^254[0-9]{9}$/.test(phoneNumber)) {
                    this.showError(wpDonationSystem.strings.invalid_phone);
                    return false;
                }
            }
            
            // Check gateway-specific fields
            const $fields = $('.payment-fields:visible .gateway-field[required]');
            if ($fields.length === 0) {
                return true; // No required fields, payment method is valid
            }
            
            // Validate all required fields
            let isValid = true;
            $fields.each(function() {
                if (!$(this).val().trim()) {
                    isValid = false;
                    return false; // Break the loop
                }
            });
            
            if (!isValid) {
                this.showError(wpDonationSystem.strings.complete_required_fields);
            }
            
            return isValid;
        },

        prevStep: function() {
            const $currentStep = $('.form-step.active');
            const currentStepNum = parseInt($currentStep.data('step'));
            
            if (currentStepNum > 1) {
                // Update progress steps
                $('.step').removeClass('active completed');
                $(`.step[data-step="${currentStepNum-1}"]`).addClass('active');
                for (let i = 1; i < currentStepNum-1; i++) {
                    $(`.step[data-step="${i}"]`).addClass('completed');
                }
                
                // Slide out current step
                $currentStep.fadeOut(300, function() {
                    $(this).removeClass('active');
                    
                    // Slide in previous step
                    $(`.form-step[data-step="${currentStepNum-1}"]`)
                        .fadeIn(300)
                        .addClass('active');
                    
                    // Scroll to top of form
                    $('html, body').animate({
                        scrollTop: $('.wp-donation-form').offset().top - 20
                    }, 300);
                });
            }
        },

        nextStep: function() {
            const $currentStep = $('.form-step.active');
            const currentStepNum = parseInt($currentStep.data('step'));
            const self = DonationForm;
            
            if (self.validateStep(currentStepNum)) {
                // Update progress steps
                $('.step').removeClass('active');
                $(`.step[data-step="${currentStepNum}"]`).addClass('completed');
                $(`.step[data-step="${currentStepNum+1}"]`).addClass('active');
                
                // Slide out current step
                $currentStep.fadeOut(300, function() {
                    $(this).removeClass('active');
                    
                    // Slide in next step
                    $(`.form-step[data-step="${currentStepNum+1}"]`)
                        .fadeIn(300)
                        .addClass('active');
                    
                    // Scroll to top of form
                    $('html, body').animate({
                        scrollTop: $('.wp-donation-form').offset().top - 20
                    }, 300);
                    
                    // Validate next step
                    self.validateStep(currentStepNum + 1);
                });
            }
        },

        handleSubmit: function(e) {
            e.preventDefault();
            console.log(' Starting donation submission...');
            
            const self = DonationForm;
            const $form = $(this);
            const $submitButton = $form.find('.submit-donation');
            
            // Basic validation
            if (!$('input[name="payment_method"]:checked').length) {
                console.warn('❌ No payment method selected');
                self.showError(wpDonationSystem.strings.select_payment);
                return;
            }
            
            // Show processing state
            console.log('⏳ Setting processing state...');
            self.setProcessingState($submitButton, true);
            
            // Get selected payment method
            const selectedMethod = $('input[name="payment_method"]:checked').val();
            
            // Prepare form data
            const formData = new FormData($form[0]);
            formData.append('action', 'process_donation');
            formData.append('security', wpDonationSystem.nonce);
            
            // Get all visible gateway fields for the selected payment method
            const $selectedGateway = $(`#payment_${selectedMethod}`).closest('.payment-option');
            const $gatewayFields = $selectedGateway.find('.gateway-field:visible');
            
            // Add gateway fields to form data
            $gatewayFields.each(function() {
                const $field = $(this);
                formData.append($field.attr('name'), $field.val());
            });
            
            // Log form data
            const formDataObj = {};
            for (let [key, value] of formData.entries()) {
                formDataObj[key] = value;
            }
            console.log('📝 Form Data:', formDataObj);
            
            // Submit form via AJAX
            $.ajax({
                url: wpDonationSystem.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('✅ Server response:', response);
                    
                    if (!response.success) {
                        console.error('❌ Payment failed:', response.data.message);
                        self.handleError(response.data.message);
                        return;
                    }
                    
                    // Handle different payment responses
                    switch(response.data.status) {
                        case 'completed':
                            console.log('🎉 Payment completed! Redirecting...');
                            window.location.href = response.data.redirect_url;
                            break;
                            
                        case 'pending':
                            if (response.data.gateway === 'mpesa') {
                                console.log('📱 Starting M-Pesa payment flow...');
                                self.handleMpesaPayment(response.data);
                            }
                            break;
                            
                        case 'failed':
                            console.error('❌ Payment failed:', response.data.message);
                            self.handleError(response.data.message);
                            break;
                    }
                },
                error: function(xhr, status, error) {
                    console.error('🔥 Network error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    self.handleError(wpDonationSystem.strings.network_error);
                    self.setProcessingState($submitButton, false);
                }
            });
            
            return false;
        },

        processPayment: function($form) {
            const self = this;
            const formData = new FormData($form[0]);
            
            // Add required action and nonce
            formData.append('action', 'process_donation');
            formData.append('security', wpDonationSystem.nonce);
            
            console.log('🔄 Making AJAX request to:', wpDonationSystem.ajax_url);
            
            $.ajax({
                url: wpDonationSystem.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('✅ Server response:', response);
                    
                    if (!response.success) {
                        console.error('❌ Payment failed:', response.data.message);
                        self.handleError(response.data.message);
                        return;
                    }
                    
                    // Handle different payment responses
                    console.log('💳 Payment status:', response.data.status);
                    switch(response.data.status) {
                        case 'completed':
                            console.log('🎉 Payment completed! Redirecting...');
                            window.location.href = response.data.redirect_url;
                            break;
                            
                        case 'pending':
                            if (response.data.gateway === 'mpesa') {
                                console.log('📱 Starting M-Pesa payment flow...');
                                self.handleMpesaPayment(response.data);
                            }
                            break;
                            
                        case 'failed':
                            console.error('❌ Payment failed:', response.data.message);
                            self.handleError(response.data.message);
                            break;
                    }
                },
                error: function(xhr, status, error) {
                    console.error('🔥 Network error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    self.handleError(wpDonationSystem.strings.network_error);
                }
            });
        },

        handleMpesaPayment: function(data) {
            console.log('📱 Setting up M-Pesa payment UI...');
            console.log('Payment data:', data);
            
            const self = this;
            const $form = $('.donation-form');
            
            // Show waiting UI
            $form.find('.form-step.active').fadeOut(300, function() {
                $(this).html(self.getMpesaWaitingHtml()).fadeIn(300);
                console.log('⏳ Started M-Pesa waiting screen');
                
                // Start polling for status
                self.startStatusPolling(data.donation_id);
            });
        },

        getMpesaWaitingHtml: function() {
            return `
                <div class="mpesa-waiting">
                    <div class="waiting-icon">
                        <div class="spinner"></div>
                    </div>
                    <h3>${wpDonationSystem.strings.mpesa_waiting_title}</h3>
                    <p>${wpDonationSystem.strings.mpesa_waiting_message}</p>
                    <div class="waiting-timer">
                        <span class="time-remaining">60</span> ${wpDonationSystem.strings.seconds_remaining}
                    </div>
                </div>
            `;
        },

        startStatusPolling: function(donationId) {
            console.log('🔄 Starting payment status polling for ID:', donationId);
            
            const self = this;
            let timeLeft = 60;
            let pollCount = 0;
            
            const timer = setInterval(() => {
                timeLeft--;
                $('.time-remaining').text(timeLeft);
                
                if (timeLeft <= 0) {
                    console.warn('⏰ Payment timeout reached');
                    clearInterval(timer);
                    self.handleError(wpDonationSystem.strings.payment_timeout);
                }
            }, 1000);
            
            const checkStatus = () => {
                pollCount++;
                console.log(`🔍 Checking payment status (attempt ${pollCount})...`);
                
                $.ajax({
                    url: wpDonationSystem.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'check_donation_status',
                        donation_id: donationId,
                        security: wpDonationSystem.nonce
                    },
                    success: function(response) {
                        console.log(`✅ Status check response (${pollCount}):`, response);
                        
                        if (!response.success) {
                            console.error('❌ Status check failed:', response.data.message);
                            clearInterval(timer);
                            self.handleError(response.data.message);
                            return;
                        }
                        
                        if (response.data.status === 'completed') {
                            console.log('🎉 Payment confirmed! Redirecting...');
                            clearInterval(timer);
                            window.location.href = response.data.redirect_url;
                        } else if (response.data.status === 'failed') {
                            console.error('❌ Payment failed:', response.data.message);
                            clearInterval(timer);
                            self.handleError(response.data.message);
                        } else {
                            console.log('⏳ Payment still pending, continuing to poll...');
                            setTimeout(checkStatus, 3000);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('🔥 Status check network error:', {
                            status: status,
                            error: error,
                            response: xhr.responseText
                        });
                        clearInterval(timer);
                        self.handleError(wpDonationSystem.strings.network_error);
                    }
                });
            };
            
            // Start polling
            console.log('⏳ Starting first status check in 3 seconds...');
            setTimeout(checkStatus, 3000);
        },

        handleError: function(message) {
            this.showError(message);
            this.setProcessingState($('.submit-donation'), false);
        },

        setProcessingState: function($button, isProcessing) {
            if (isProcessing) {
                $button.addClass('processing')
                    .find('.button-text')
                    .text(wpDonationSystem.strings.processing);
            } else {
                $button.removeClass('processing')
                    .find('.button-text')
                    .text(wpDonationSystem.strings.complete_donation);
            }
        },

        showError: function(message) {
            const $error = $('<div>', {
                class: 'donation-error',
                text: message
            });
            
            // Remove any existing errors
            $('.donation-error').remove();
            
            // Add new error at the top of the form
            $('#donation-form').prepend($error);
            
            // Safely scroll to error
            const $form = $('.wp-donation-form');
            if ($form.length) {
                $('html, body').animate({
                    scrollTop: $form.offset().top - 20
                }, 300);
            }
        },

        validateDonorDetails: function() {
            const name = $('#donor_name').val().trim();
            const email = $('#donor_email').val().trim();
            const isAnonymous = $('#anonymous_donation').is(':checked');
            let isValid = true;
            
            // Clear previous errors
            $('.error-message').remove();
            $('.has-error').removeClass('has-error');
            
            if (isAnonymous) {
                // If anonymous, validation always passes
                isValid = true;
            } else {
                // Validate name
                if (!name) {
                    this.showFieldError('donor_name', wpDonationSystem.strings.name_required);
                    isValid = false;
                }
                
                // Validate email
                if (!email) {
                    this.showFieldError('donor_email', wpDonationSystem.strings.email_required);
                    isValid = false;
                } else if (!this.isValidEmail(email)) {
                    this.showFieldError('donor_email', wpDonationSystem.strings.invalid_email);
                    isValid = false;
                }
            }
            
            // Enable/disable next button based on validation
            const $nextButton = $('.form-step[data-step="2"] .next-step');
            $nextButton.prop('disabled', !isValid);
            
            if (isValid) {
                this.updateSummary();
            }
            
            return isValid;
        },

        showFieldError: function(fieldId, message) {
            const $field = $('#' + fieldId);
            const $group = $field.closest('.input-group');
            
            if (!$field.length || !$group.length) {
                return; // Exit if field or group not found
            }
            
            // Remove any existing errors first
            this.clearFieldError($field);
            
            // Add error class and message
            $group.addClass('has-error');
            $field.after('<div class="error-message">' + message + '</div>');
            
            // Only scroll if this is the first error
            const $firstError = $('.has-error').first();
            if ($firstError.length && $firstError.is($group)) {
                try {
                    const offset = $group.offset();
                    if (offset) {
                        $('html, body').animate({
                            scrollTop: offset.top - 100
                        }, 300);
                    }
                } catch (e) {
                    console.warn('Could not scroll to error field:', e);
                }
            }
        },

        clearFieldError: function($field) {
            const $group = $field.closest('.input-group');
            $group.removeClass('has-error');
            $group.find('.error-message').remove();
        },

        isValidEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        handlePaymentMethodSelection: function() {
            const self = DonationForm;
            const $radio = $(this);
            
            // Update submit button text
            const amount = self.formatCurrency(self.getSelectedAmount());
            const gatewayTitle = $radio.siblings('.payment-label').find('.payment-name').text();
            
            $('.submit-donation .button-text').text(
                wpDonationSystem.strings.pay_with
                    .replace('{gateway}', gatewayTitle)
                    .replace('{amount}', amount)
            );
        },

        handleGatewayFieldInput: function() {
            const self = DonationForm;
            const isValid = self.validatePaymentStep();
            $('.submit-donation').prop('disabled', !isValid);
        },

        showSuccess: function(message) {
            const $success = $('<div>', {
                class: 'donation-success',
                text: message
            });
            
            $('.donation-success, .donation-error').remove();
            $('.donation-form').prepend($success);
        },

        handleAnonymousToggle: function() {
            const self = DonationForm;
            const isAnonymous = $(this).is(':checked');
            const $nameField = $('#donor_name');
            const $emailField = $('#donor_email');
            const siteDomain = window.location.hostname;
            
            if (isAnonymous) {
                // Store original values
                $nameField.data('original-value', $nameField.val());
                $emailField.data('original-value', $emailField.val());
                
                // Set anonymous values
                $nameField.val('Anonymous Guest').prop('readonly', true);
                $emailField.val('anonymous@' + siteDomain).prop('readonly', true);
            } else {
                // Restore original values if they exist
                const originalName = $nameField.data('original-value');
                const originalEmail = $emailField.data('original-value');
                
                $nameField.val(originalName || '').prop('readonly', false);
                $emailField.val(originalEmail || '').prop('readonly', false);
            }
            
            // Update validation and summary
            self.validateDonorDetails();
            self.updateSummary();
        }
    };

    DonationForm.init();
});