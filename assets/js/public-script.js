jQuery(document).ready(function($) {
    const DonationForm = {
        init: function() {
            this.bindEvents();
            this.initializeForm();
        },

        bindEvents: function() {
            $('.amount-option input[type="radio"]').on('change', this.handleAmountSelection);
            $('.custom-amount').on('input', this.handleCustomAmount);
            $('.prev-step').on('click', this.prevStep);
            $('.next-step').on('click', this.nextStep);
            $('.donation-form').on('submit', this.handleSubmit);
            $('.payment-radio').on('change', this.handlePaymentMethodSelection);
            $('.gateway-field').on('input change', this.handleGatewayFieldInput);
        },

        initializeForm: function() {
            this.updateSummary();
            this.validateStep(1);
            
            const $selectedGateway = $('input[name="payment_method"]:checked');
            if ($selectedGateway.length) {
                const $accordion = $selectedGateway.closest('.payment-method-accordion');
                $accordion.addClass('active')
                    .find('.accordion-content').show();
                
                // Update submit button
                const amount = this.formatCurrency(this.getSelectedAmount());
                const gatewayTitle = $accordion.find('.method-name').text();
                
                $('.submit-donation')
                    .prop('disabled', false)
                    .find('.button-text')
                    .text(wpDonationSystem.strings.pay_with
                        .replace('{gateway}', gatewayTitle)
                        .replace('{amount}', amount)
                    );
            }
            
            $('#donor_name, #donor_email').on('change keyup', () => {
                this.updateSummary();
            });
        },

        handleAmountSelection: function() {
            const self = DonationForm;
            const $selected = $(this);
            const $customAmount = $('.custom-amount');
            
            if ($selected.hasClass('custom-radio')) {
                $customAmount.prop('disabled', false).focus();
            } else {
                $customAmount.prop('disabled', true);
                // Update summary immediately for preset amounts
                self.updateSummary();
            }
            
            self.validateStep(1);
        },

        handleCustomAmount: function() {
            const self = DonationForm;
            const value = $(this).val();
            if (value) {
                $('.custom-radio').prop('checked', true);
                // Update summary when custom amount changes
                self.updateSummary();
            }
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
            const $selected = $('input[name="amount"]:checked');
            if ($selected.hasClass('custom-radio')) {
                return parseFloat($('.custom-amount').val()) || 0;
            }
            return parseFloat($selected.val()) || 0;
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
            const $nextButton = $('.next-step');
            const $submitButton = $('.submit-donation');
            
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
            if (step === 3) {
                $submitButton.prop('disabled', !isValid);
            } else {
                $nextButton.prop('disabled', !isValid);
            }
            
            return isValid;
        },

        clearErrors: function() {
            $('.error-message').remove();
            $('.has-error').removeClass('has-error');
        },

        validateAmountStep: function() {
            const amount = this.getSelectedAmount();
            const minAmount = parseFloat($('#min_amount').val());
            const maxAmount = parseFloat($('#max_amount').val());
            
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
                return false;
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
            
            return isValid;
        },

        prevStep: function() {
            const $currentStep = $('.form-step.active');
            const currentStepNum = parseInt($currentStep.data('step'));
            
            if (currentStepNum > 1) {
                // Slide out current step
                $currentStep.fadeOut(300, function() {
                    $(this).removeClass('active');
                    
                    // Slide in previous step
                    $('.form-step[data-step="' + (currentStepNum - 1) + '"]')
                        .fadeIn(300)
                        .addClass('active');
                    
                    // Update progress
                    $('.step-item').removeClass('active completed');
                    $('.step-item[data-step="' + (currentStepNum - 1) + '"]')
                        .addClass('active');
                    
                    // Scroll to top of form
                    $('html, body').animate({
                        scrollTop: $('.wp-donation-form').offset().top - 20
                    }, 300);
                    
                    DonationForm.validateStep(currentStepNum - 1);
                });
            }
        },

        nextStep: function() {
            const $currentStep = $('.form-step.active');
            const currentStepNum = parseInt($currentStep.data('step'));
            
            if (DonationForm.validateStep(currentStepNum)) {
                // Slide out current step
                $currentStep.fadeOut(300, function() {
                    $(this).removeClass('active');
                    
                    // Slide in next step
                    $('.form-step[data-step="' + (currentStepNum + 1) + '"]')
                        .fadeIn(300)
                        .addClass('active');
                    
                    // Update progress
                    $('.step-item[data-step="' + currentStepNum + '"]')
                        .removeClass('active')
                        .addClass('completed');
                    $('.step-item[data-step="' + (currentStepNum + 1) + '"]')
                        .addClass('active');
                    
                    // Scroll to top of form
                    $('html, body').animate({
                        scrollTop: $('.wp-donation-form').offset().top - 20
                    }, 300);
                    
                    DonationForm.validateStep(currentStepNum + 1);
                    DonationForm.updateSummary();
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
            
            // Log form data
            const formData = new FormData($form[0]);
            console.log('📝 Form Data:', {
                amount: self.formatCurrency(self.getSelectedAmount()),
                name: formData.get('donor_name'),
                email: formData.get('donor_email'),
                payment_method: formData.get('payment_method')
            });
            
            // Show processing state
            console.log('⏳ Setting processing state...');
            self.setProcessingState($submitButton, true);
            
            // Submit form
            console.log('📤 Submitting payment...');
            self.processPayment($form);
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
            
            $('.donation-error').remove();
            $('.donation-form').prepend($error);
            
            $('html, body').animate({
                scrollTop: $('.donation-form').offset().top - 50
            }, 500);
        },

        validateDonorDetails: function() {
            const name = $('#donor_name').val().trim();
            const email = $('#donor_email').val().trim();
            let isValid = true;
            
            // Clear previous errors
            $('.error-message').remove();
            $('.has-error').removeClass('has-error');
            
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
            
            return isValid;
        },

        showFieldError: function(fieldId, message) {
            const $field = $('#' + fieldId);
            const $group = $field.closest('.input-group');
            
            $group.addClass('has-error');
            $group.append('<div class="error-message">' + message + '</div>');
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
        }
    };

    DonationForm.init();
});