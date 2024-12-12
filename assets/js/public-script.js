jQuery(document).ready(function($) {
    // Check for required dependencies
    if (typeof wpDonationSystem === 'undefined') {
        console.error('Required wpDonationSystem object not found');
        return;
    }

    const DonationForm = {
        init: function() {
            this.bindEvents();
            this.initializeForm();
            
            // Add step validation
            $('.next-step').on('click', function() {
                const currentStep = $(this).closest('.form-step').data('step');
                if (DonationForm.validateStep(currentStep)) {
                    DonationForm.goToStep(currentStep + 1);
                }
            });
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
            $('#donor_name, #donor_email').on('input', () => {
                this.validateDonorDetails();
            });
            
            // Update summary on donor info change
            $('#donor_name, #donor_email').on('input', function() {
                DonationForm.updateSummary();
            });
            
            // Handle anonymous donation toggle
            $('#anonymous_donation').on('change', () => {
                this.handleAnonymousToggle();
                this.validateDonorDetails();
                this.updateSummary();
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
            // Default currency settings if not provided
            const currency = wpDonationSystem.currency || {
                code: 'KES',
                symbol: 'KSh',
                position: 'left',
                decimals: 2,
                decimal_separator: '.',
                thousand_separator: ','
            };

            // Format number
            let formattedAmount = parseFloat(amount).toFixed(currency.decimals);
            
            // Add thousand separators
            formattedAmount = formattedAmount.replace(/\B(?=(\d{3})+(?!\d))/g, currency.thousand_separator);
            
            // Replace decimal point if needed
            if (currency.decimal_separator !== '.') {
                formattedAmount = formattedAmount.replace('.', currency.decimal_separator);
            }
            
            // Add currency symbol in correct position
            if (currency.position === 'left') {
                return currency.symbol + ' ' + formattedAmount;
            } else if (currency.position === 'right') {
                return formattedAmount + ' ' + currency.symbol;
            } else if (currency.position === 'left_space') {
                return currency.symbol + ' ' + formattedAmount;
            } else if (currency.position === 'right_space') {
                return formattedAmount + ' ' + currency.symbol;
            }
            
            // Default to left position
            return currency.symbol + ' ' + formattedAmount;
        },

        validateStep: function(step) {
            this.clearErrors();
            let isValid = true;
            
            switch(step) {
                case 1: // Amount step
                    isValid = this.validateAmountStep();
                    break;
                case 2: // Donor details step
                    isValid = this.validateDonorStep();
                    break;
                case 3: // Payment step
                    isValid = this.validatePaymentStep();
                    break;
            }
            
            return isValid;
        },

        validateAmountStep: function() {
            const amount = parseFloat($('#donation_amount').val());
            const minAmount = parseFloat($('#donation_amount').attr('min'));
            const maxAmount = parseFloat($('#donation_amount').attr('max'));
            
            if (!amount || isNaN(amount)) {
                this.showError('donation_amount', wpDonationSystem.strings.invalid_amount);
                return false;
            }
            
            if (amount < minAmount) {
                this.showError('donation_amount', 
                    wpDonationSystem.strings.minimum_amount.replace('%s', this.formatCurrency(minAmount))
                );
                return false;
            }
            
            if (amount > maxAmount) {
                this.showError('donation_amount', 
                    wpDonationSystem.strings.maximum_amount.replace('%s', this.formatCurrency(maxAmount))
                );
                return false;
            }
            
            return true;
        },

        validateDonorStep: function() {
            let isValid = true;
            const isAnonymous = $('#anonymous_donation').is(':checked');
            
            if (!isAnonymous) {
                const name = $('#donor_name').val().trim();
                const email = $('#donor_email').val().trim();
                
                if (!name) {
                    this.showError('donor_name', wpDonationSystem.strings.name_required);
                    isValid = false;
                }
                
                if (!email) {
                    this.showError('donor_email', wpDonationSystem.strings.email_required);
                    isValid = false;
                } else if (!this.isValidEmail(email)) {
                    this.showError('donor_email', wpDonationSystem.strings.invalid_email);
                    isValid = false;
                }
            }
            
            return isValid;
        },

        validatePaymentStep: function() {
            const $paymentMethods = $('input[name="payment_method"]');
            
            if (!$paymentMethods.filter(':checked').length) {
                this.showError('payment-method', wpDonationSystem.strings.select_payment);
                return false;
            }
            
            return true;
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
            console.log('Starting donation submission...');
            
            const self = DonationForm;
            const $form = $(this);
            const $submitButton = $form.find('.submit-donation');
            
            // Show processing state
            self.setProcessingState($submitButton, true);
            
            // Prepare form data
            const formData = new FormData($form[0]);
            
            // Handle anonymous donation
            const isAnonymous = $('#anonymous_donation').is(':checked');
            if (isAnonymous) {
                formData.set('donor_name', 'Anonymous Guest');
                formData.set('donor_email', 'anonymous@' + window.location.hostname);
                formData.set('is_anonymous', '1');
            }
            
            // Add required action and nonce
            formData.append('action', 'process_donation');
            formData.append('security', wpDonationSystem.nonce);
            
            // Log form data for debugging
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
                        self.showError('payment-error', response.data.message);
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
                            self.showError('payment-error', response.data.message);
                            break;
                    }
                },
                error: function(xhr, status, error) {
                    console.error('🔥 Network error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    self.showError('network-error', wpDonationSystem.strings.network_error);
                },
                complete: function() {
                    self.setProcessingState($submitButton, false);
                }
            });
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

        showError: function(fieldId, message) {
            // Guard against invalid input
            if (!fieldId || !message) {
                console.warn('Invalid parameters passed to showError');
                return;
            }

            // Find the field and its container
            const $field = $('#' + fieldId);
            if (!$field.length) {
                console.warn('Field not found:', fieldId);
                return;
            }

            // Find the appropriate container
            const $container = $field.closest('.input-group, .custom-amount-wrapper');
            if (!$container.length) {
                console.warn('Container not found for field:', fieldId);
                return;
            }

            // Clear any existing errors
            this.clearFieldError($field);

            // Add error class and message
            $container.addClass('has-error');
            
            // Create error message element
            const $errorMessage = $('<div>', {
                class: 'error-message',
                text: message
            });

            // Insert error message after the field
            $field.after($errorMessage);

            try {
                // Only scroll if the container is not visible in viewport
                const containerTop = $container.offset().top;
                const scrollTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                
                if (containerTop < scrollTop || containerTop > (scrollTop + windowHeight)) {
                    $('html, body').animate({
                        scrollTop: Math.max(0, containerTop - 100)
                    }, 300);
                }
            } catch (e) {
                console.warn('Error scrolling to error message:', e);
            }

            // Focus the field if possible
            if ($field.is(':visible')) {
                $field.focus();
            }
        },

        clearErrors: function() {
            $('.has-error').removeClass('has-error');
            $('.error-message').remove();
        },

        isValidEmail: function(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        handlePaymentMethodSelection: function() {
            const self = DonationForm;
            const $radio = $(this);
            
            // Get amount and format it
            const amount = self.getSelectedAmount();
            if (!amount) {
                console.warn('No amount selected');
                return;
            }
            
            const formattedAmount = self.formatCurrency(amount);
            const gatewayTitle = $radio.siblings('.payment-label').find('.payment-name').text();
            
            // Update button text
            $('.submit-donation .button-text').text(
                wpDonationSystem.strings.pay_with
                    .replace('{amount}', formattedAmount)
                    .replace('{gateway}', gatewayTitle)
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
            const $nameField = $('#donor_name');
            const $emailField = $('#donor_email');
            const isAnonymous = $('#anonymous_donation').is(':checked');
            
            if (isAnonymous) {
                // Store original values
                $nameField.data('original-name', $nameField.val());
                $emailField.data('original-email', $emailField.val());
                
                // Set anonymous values
                $nameField.val('Anonymous Guest').prop('readonly', true);
                $emailField.val('anonymous@' + window.location.hostname).prop('readonly', true);
                
                // Add visual indication
                $nameField.closest('.input-group').addClass('anonymous-input');
                $emailField.closest('.input-group').addClass('anonymous-input');
            } else {
                // Restore original values
                const originalName = $nameField.data('original-name') || '';
                const originalEmail = $emailField.data('original-email') || '';
                
                $nameField.val(originalName).prop('readonly', false);
                $emailField.val(originalEmail).prop('readonly', false);
                
                // Remove visual indication
                $nameField.closest('.input-group').removeClass('anonymous-input');
                $emailField.closest('.input-group').removeClass('anonymous-input');
            }
            
            // Update validation state
            this.validateDonorDetails();
            
            // Trigger change event on fields to update any dependent UI
            $nameField.trigger('change');
            $emailField.trigger('change');
        },

        goToStep: function(step) {
            $('.form-step').removeClass('active').hide();
            $('.form-step[data-step="' + step + '"]').addClass('active').fadeIn();
            
            // Update progress indicator
            $('.progress-steps .step').removeClass('active completed');
            $('.progress-steps .step').each(function() {
                const stepNum = $(this).data('step');
                if (stepNum < step) {
                    $(this).addClass('completed');
                } else if (stepNum === step) {
                    $(this).addClass('active');
                }
            });
            
            // Update summary if needed
            this.updateSummary();
        },

        validateDonorDetails: function() {
            let isValid = true;
            const isAnonymous = $('#anonymous_donation').is(':checked');
            
            if (!isAnonymous) {
                const name = $('#donor_name').val().trim();
                const email = $('#donor_email').val().trim();
                
                if (!name) {
                    this.showFieldError('donor_name', wpDonationSystem.strings.name_required);
                    isValid = false;
                }
                
                if (!email) {
                    this.showFieldError('donor_email', wpDonationSystem.strings.email_required);
                    isValid = false;
                } else if (!this.isValidEmail(email)) {
                    this.showFieldError('donor_email', wpDonationSystem.strings.invalid_email);
                    isValid = false;
                }
            }
            
            return isValid;
        },

        showFieldError: function(fieldId, message) {
            const $field = $('#' + fieldId);
            const $group = $field.closest('.input-group');
            
            // Remove any existing errors first
            this.clearFieldError($field);
            
            // Add error class and message
            $group.addClass('has-error');
            $field.after('<div class="error-message">' + message + '</div>');
        },

        clearFieldError: function($field) {
            const $group = $field.closest('.input-group');
            $group.removeClass('has-error');
            $group.find('.error-message').remove();
        },

        // Add helper method to check element visibility
        isElementInViewport: function($el) {
            if (!$el.length) return false;

            const rect = $el[0].getBoundingClientRect();
            
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        // Add new method for payment-specific errors
        showPaymentError: function(message) {
            const $paymentSection = $('.payment-methods-section');
            
            // Clear any existing errors
            this.clearErrors();
            
            // Add error class to the payment section
            $paymentSection.addClass('has-error');
            
            // Create and insert error message
            const $errorMessage = $('<div>', {
                class: 'error-message',
                text: message
            });
            
            // Insert at the top of the payment section
            $paymentSection.prepend($errorMessage);
            
            // Scroll to payment section if not in viewport
            try {
                const sectionTop = $paymentSection.offset().top;
                const scrollTop = $(window).scrollTop();
                const windowHeight = $(window).height();
                
                if (sectionTop < scrollTop || sectionTop > (scrollTop + windowHeight)) {
                    $('html, body').animate({
                        scrollTop: Math.max(0, sectionTop - 100)
                    }, 300);
                }
            } catch (e) {
                console.warn('Error scrolling to payment section:', e);
            }
        }
    };

    DonationForm.init();
});