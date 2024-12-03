jQuery(document).ready(function($) {
    // Form step navigation
    let currentStep = 1;
    
    // Amount options
    $('.amount-option').click(function() {
        $('.amount-option').removeClass('active');
        $(this).addClass('active');
        
        if ($(this).hasClass('custom')) {
            $('#donation_amount').focus().select();
        } else {
            $('#donation_amount').val($(this).data('amount'));
        }
    });

    // Next step
    $('.next-step').click(function() {
        const $currentStep = $(this).closest('.form-step');
        if (validateStep($currentStep)) {
            currentStep++;
            updateFormSteps();
        }
    });

    // Previous step
    $('.prev-step').click(function() {
        currentStep--;
        updateFormSteps();
    });

    // M-Pesa phone field toggle
    $('#payment_mpesa').change(function() {
        if ($(this).is(':checked')) {
            $('#mpesa-form').slideDown();
        } else {
            $('#mpesa-form').slideUp();
        }
    });

    function updateFormSteps() {
        // Hide all steps
        $('.form-step').hide().removeClass('active');
        
        // Show current step
        $(`.form-step[data-step="${currentStep}"]`).fadeIn().addClass('active');
        
        // Update step indicators
        updateStepIndicators();
        
        // Scroll to top of form
        $('html, body').animate({
            scrollTop: $('.wp-donation-form').offset().top - 50
        }, 500);
    }

    function updateStepIndicators() {
        $('.step').each(function() {
            const stepNum = $(this).data('step');
            $(this).removeClass('active completed');
            
            if (stepNum < currentStep) {
                $(this).addClass('completed');
            } else if (stepNum === currentStep) {
                $(this).addClass('active');
            }
        });
    }

    // Form validation functions
    function validateStep($step) {
        let isValid = true;
        const stepNumber = parseInt($step.data('step'));

        // Clear previous errors
        $('.error-message').remove();
        $('.has-error').removeClass('has-error');

        switch(stepNumber) {
            case 1: // Amount
                const amount = parseFloat($('#donation_amount').val());
                const min = parseFloat($('#donation_amount').attr('min')) || 0;
                const max = parseFloat($('#donation_amount').attr('max')) || Infinity;

                if (!amount || isNaN(amount) || amount <= 0) {
                    showError($('#donation_amount'), wpDonationSystem.i18n.invalid_amount);
                    isValid = false;
                } else if (amount < min || amount > max) {
                    showError($('#donation_amount'), 
                        wpDonationSystem.i18n.amount_range.replace('{min}', min).replace('{max}', max)
                    );
                    isValid = false;
                }
                break;

            case 2: // Donor details
                const requiredFields = ['donor_name', 'donor_email'];
                requiredFields.forEach(field => {
                    const $field = $(`#${field}`);
                    if (!$field.val().trim()) {
                        showError($field, wpDonationSystem.i18n.required_field);
                        isValid = false;
                    }
                });

                const email = $('#donor_email').val();
                if (email && !isValidEmail(email)) {
                    showError($('#donor_email'), wpDonationSystem.i18n.invalid_email);
                    isValid = false;
                }
                break;

            case 3: // Payment method
                if (!$('input[name="payment_method"]:checked').length) {
                    showError($('.payment-methods'), wpDonationSystem.i18n.select_payment);
                    isValid = false;
                }

                if ($('#payment_mpesa:checked').length && !isValidPhone($('#phone_number').val())) {
                    showError($('#phone_number'), wpDonationSystem.i18n.invalid_phone);
                    isValid = false;
                }
                break;
        }

        return isValid;
    }

    function showError($element, message) {
        $element.addClass('has-error');
        $('<div class="error-message">' + message + '</div>').insertAfter($element);
    }

    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function isValidPhone(phone) {
        return /^254[0-9]{9}$/.test(phone);
    }

    // Initialize form
    updateFormSteps();

    // Form Submission Handler
    $('#donation-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('button[type="submit"]');
        const $feedback = $('.donation-message');
        
        // Clear previous messages
        $feedback.removeClass('success error').hide();
        $('.error-message').remove();
        $('.has-error').removeClass('has-error');
        
        // Validate form
        if (!validateForm()) {
            return false;
        }
        
        // Show loading state
        $form.addClass('processing');
        $submitButton.prop('disabled', true)
            .text(wpDonationSystem.i18n.processing);
        
        // Prepare form data
        const formData = new FormData($form[0]);
        formData.append('action', 'process_donation');
        formData.append('security', wpDonationSystem.nonce);
        
        // Add this before the AJAX call
        console.log('Form data being sent:', formData);
        
        // Send AJAX request
        $.ajax({
            url: wpDonationSystem.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.data.payment_method === 'mpesa') {
                        // Show STK push message
                        $feedback.removeClass('error').addClass('success')
                            .html(response.data.message)
                            .fadeIn();
                        
                        // Start polling for payment status
                        pollPaymentStatus(response.data.donation_id);
                        
                        // Update UI to show waiting state
                        $form.addClass('payment-pending');
                        $submitButton.prop('disabled', true)
                            .text(wpDonationSystem.i18n.waiting_payment);
                    }
                } else {
                    $feedback.addClass('error')
                        .text(response.data.message || wpDonationSystem.i18n.error)
                        .fadeIn();
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error Details:', {
                    status: status,
                    error: error,
                    responseText: xhr.responseText,
                    responseJSON: xhr.responseJSON,
                    statusText: xhr.statusText
                });
                
                $feedback.addClass('error')
                    .text(wpDonationSystem.i18n.error)
                    .fadeIn();
            },
            complete: function() {
                $form.removeClass('processing');
                $submitButton.prop('disabled', false)
                    .text(wpDonationSystem.i18n.donate);
            }
        });
    });

    // Payment status polling function
    function pollPaymentStatus(donationId) {
        const maxAttempts = 24; // 2 minutes (24 * 5 seconds)
        let attempts = 0;
        
        const checkStatus = setInterval(function() {
            if (attempts >= maxAttempts) {
                clearInterval(checkStatus);
                $feedback.removeClass('success').addClass('error')
                    .text(wpDonationSystem.i18n.payment_timeout)
                    .fadeIn();
                return;
            }
            
            attempts++;
            
            $.ajax({
                url: wpDonationSystem.ajaxurl,
                type: 'POST',
                data: {
                    action: 'check_donation_status',
                    donation_id: donationId,
                    security: wpDonationSystem.nonce
                },
                success: function(response) {
                    if (response.success) {
                        switch (response.data.status) {
                            case 'completed':
                                clearInterval(checkStatus);
                                // Redirect to success page
                                window.location.href = response.data.redirect_url;
                                break;
                                
                            case 'failed':
                                clearInterval(checkStatus);
                                $feedback.removeClass('success').addClass('error')
                                    .text(response.data.message)
                                    .fadeIn();
                                $form.removeClass('payment-pending');
                                $submitButton.prop('disabled', false)
                                    .text(wpDonationSystem.i18n.retry_payment);
                                break;
                                
                            case 'processing':
                                // Continue polling
                                break;
                        }
                    }
                },
                error: function() {
                    console.error('Failed to check payment status');
                }
            });
        }, 5000); // Check every 5 seconds
    }

    // Amount Selection Handling
    const $amountInput = $('#donation_amount');
    const $amountPresets = $('.amount-preset');
    const currencyFormatter = new Intl.NumberFormat('en-KE', {
        style: 'currency',
        currency: 'KES',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    });

    // Handle preset amount clicks
    $amountPresets.on('click', function() {
        const $this = $(this);
        const amount = $this.data('amount');
        
        // Remove active class from all presets
        $amountPresets.removeClass('active');
        
        // Add active class to clicked preset
        $this.addClass('active');
        
        if ($this.hasClass('custom')) {
            // Focus the input for custom amount
            $amountInput.val('').focus();
        } else {
            // Set the amount in the input
            $amountInput.val(amount);
            
            // Trigger validation
            validateAmount();
        }
    });

    // Handle custom amount input
    $amountInput.on('input', function() {
        const amount = $(this).val();
        
        // Remove active class from presets if amount doesn't match any
        $amountPresets.removeClass('active');
        
        // Check if amount matches any preset
        $amountPresets.each(function() {
            const presetAmount = $(this).data('amount');
            if (parseFloat(amount) === presetAmount) {
                $(this).addClass('active');
            }
        });
        
        // If no preset matches, activate custom
        if (!$amountPresets.hasClass('active')) {
            $('.amount-preset.custom').addClass('active');
        }
        
        validateAmount();
    });

    // Format amount on blur
    $amountInput.on('blur', function() {
        const amount = parseFloat($(this).val());
        if (!isNaN(amount)) {
            $(this).val(amount.toFixed(0));
        }
    });

    // Validate amount
    function validateAmount() {
        const amount = parseFloat($amountInput.val());
        const min = parseFloat($amountInput.attr('min'));
        const max = parseFloat($amountInput.attr('max'));
        
        // Remove previous error
        $('.amount-error').remove();
        $amountInput.removeClass('has-error');
        
        if (isNaN(amount) || amount < min || amount > max) {
            $amountInput.addClass('has-error');
            $('<div class="error-message amount-error">')
                .text(`Please enter an amount between ${currencyFormatter.format(min)} and ${currencyFormatter.format(max)}`)
                .insertAfter($amountInput);
            return false;
        }
        
        return true;
    }

    // Payment Method Selection
    $('.payment-method').on('click', function() {
        const $radio = $(this).find('input[type="radio"]');
        const method = $radio.val();
        
        // Update radio and active states
        $('.payment-method').removeClass('active');
        $(this).addClass('active');
        $radio.prop('checked', true);
        
        // Show/hide payment details
        if (method === 'mpesa') {
            $('#mpesa-details').slideDown(300);
        } else {
            $('#mpesa-details').slideUp(300);
        }
    });

    // Also trigger on radio change
    $('input[name="payment_method"]').on('change', function() {
        const method = $(this).val();
        
        // Update active states
        $('.payment-method').removeClass('active');
        $(this).closest('.payment-method').addClass('active');
        
        // Show/hide payment details
        if (method === 'mpesa') {
            $('#mpesa-details').slideDown(300);
        } else {
            $('#mpesa-details').slideUp(300);
        }
    });

    // Add this function to validate the entire form
    function validateForm() {
        let isValid = true;
        
        // Validate each step
        $('.form-step').each(function() {
            if (!validateStep($(this))) {
                isValid = false;
            }
        });

        // Additional validation for payment method
        if ($('input[name="payment_method"]:checked').length === 0) {
            showError($('.payment-methods'), wpDonationSystem.i18n.select_payment);
            isValid = false;
        }

        // Validate M-Pesa phone number if M-Pesa is selected
        if ($('#payment_mpesa:checked').length > 0) {
            const phoneNumber = $('#phone_number').val();
            if (!isValidPhone(phoneNumber)) {
                showError($('#phone_number'), wpDonationSystem.i18n.invalid_phone);
                isValid = false;
            }
        }

        // Scroll to first error if validation fails
        if (!isValid) {
            const $firstError = $('.error-message').first();
            if ($firstError.length) {
                $('html, body').animate({
                    scrollTop: $firstError.offset().top - 100
                }, 500);
            }
        }

        return isValid;
    }
}); 