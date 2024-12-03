jQuery(document).ready(function($) {
    const form = $('#donation-form');
    const submitButton = $('#donate-button');
    
    // Payment method selection
    $('.payment-method-option').click(function() {
        $('.payment-method-option').removeClass('active');
        $(this).addClass('active');
        $('input[name="payment_method"]').val($(this).data('method'));
        
        // Toggle M-Pesa phone field visibility
        if ($(this).data('method') === 'mpesa') {
            $('#mpesa-phone-group').slideDown();
        } else {
            $('#mpesa-phone-group').slideUp();
        }
    });
    
    // Form validation
    form.on('submit', function(e) {
        e.preventDefault();
        
        if (validateForm()) {
            submitButton.prop('disabled', true)
                       .addClass('loading')
                       .text('Processing...');
            
            const formData = new FormData(this);
            const paymentMethod = formData.get('payment_method');
            
            if (paymentMethod === 'mpesa') {
                processMPesaPayment(formData);
            }
        }
    });
    
    function validateForm() {
        clearMessages();
        let isValid = true;
        
        // Required fields validation
        form.find('[required]').each(function() {
            if (!$(this).val()) {
                showError($(this).attr('name') + ' is required');
                $(this).addClass('error');
                isValid = false;
            }
        });
        
        // Amount validation
        const amount = parseFloat($('input[name="amount"]').val());
        if (isNaN(amount) || amount <= 0) {
            showError('Please enter a valid amount');
            isValid = false;
        }
        
        // Email validation
        const email = $('input[name="donor_email"]').val();
        if (email && !isValidEmail(email)) {
            showError('Please enter a valid email address');
            isValid = false;
        }
        
        return isValid;
    }
    
    function showMessage(message, type = 'error') {
        const messageDiv = $('<div>')
            .addClass('donation-message')
            .addClass(type)
            .text(message);
        
        form.prepend(messageDiv);
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: messageDiv.offset().top - 100
        }, 500);
    }
    
    function clearMessages() {
        $('.donation-message').remove();
        form.find('.error').removeClass('error');
    }
    
    function showError(message) {
        showMessage(message, 'error');
    }
    
    function showSuccess(message) {
        showMessage(message, 'success');
    }
    
    function isValidEmail(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }
});
