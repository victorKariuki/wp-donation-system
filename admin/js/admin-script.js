jQuery(document).ready(function($) {
    // Handle donation deletion
    $('.delete-donation').on('click', function(e) {
        if (!confirm(wpDonationSystem.strings.confirmDelete)) {
            e.preventDefault();
        }
    });

    // Handle form submission
    $('form#wp-donation-settings-form').on('submit', function() {
        var $form = $(this);
        var $submit = $form.find('input[type="submit"]');
        
        if ($form.data('submitted')) {
            return false;
        }
        
        $form.data('submitted', true);
        $submit.val('Saving...').prop('disabled', true);
        
        return true;
    });

    // Add loading state to form submission
    $('#wp-donation-settings-form').on('submit', function() {
        const $submitButton = $(this).find('input[type="submit"]');
        $submitButton.prop('disabled', true)
            .val('Saving...')
            .addClass('updating-message');
    });

    // Tab Navigation with URL updates
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        const target = $(this).attr('href');
        const tabId = $(this).data('tab');
        
        // Update tabs
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        // Hide all panels first
        $('.settings-panel').hide();
        
        // Show target panel
        $(target).fadeIn(300).addClass('active');
        
        // Update active tab input
        $('#active_tab').val(tabId);

        // Update URL without reload
        const newUrl = new URL(window.location);
        newUrl.searchParams.set('tab', tabId);
        window.history.pushState({}, '', newUrl);
    });

    // Copy URL functionality
    $('.copy-url').on('click', function() {
        var targetId = $(this).data('clipboard-target');
        var $input = $(targetId);
        
        $input.select();
        document.execCommand('copy');
        
        var $button = $(this);
        var originalText = $button.text();
        $button.text('Copied!');
        
        setTimeout(function() {
            $button.text(originalText);
        }, 2000);
    });

    // Reset URL functionality
    $('.reset-url').on('click', function() {
        var defaultUrl = $(this).data('default');
        var targetId = $(this).data('target');
        $(targetId).val(defaultUrl);
    });

    // Handle browser back/forward
    window.addEventListener('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'general';
        
        // Simulate tab click
        $(`.nav-tab[data-tab="${tab}"]`).trigger('click');
    });

    // Initialize active tab on page load
    $(document).ready(function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tab = urlParams.get('tab') || 'general';
        $(`.nav-tab[data-tab="${tab}"]`).click();
    });

    // Settings Form Submission Handler
    $('#wp-donation-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitButton = $form.find('input[type="submit"]');
        const $feedback = $('#setting-save-feedback');
        
        // Clear previous feedback
        $feedback.hide().removeClass('notice-success notice-error');
        
        // Disable submit button and show loading state
        $submitButton.prop('disabled', true)
            .val(wpDonationSystem.i18n.saving);
        
        // Collect form data
        const formData = new FormData($form[0]);
        formData.append('action', 'save_donation_settings');
        formData.append('security', wpDonationSystem.nonce);
        formData.append('active_tab', $('input[name="active_tab"]').val());
        
        // AJAX request
        $.ajax({
            url: wpDonationSystem.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $feedback.addClass('notice-success')
                        .find('p').text(response.data.message);
                } else {
                    $feedback.addClass('notice-error')
                        .find('p').text(response.data.message || wpDonationSystem.i18n.error);
                }
                $feedback.fadeIn();
            },
            error: function() {
                $feedback.addClass('notice-error')
                    .find('p').text(wpDonationSystem.i18n.error)
                    .fadeIn();
            },
            complete: function() {
                $submitButton.prop('disabled', false)
                    .val(wpDonationSystem.i18n.saved);
            }
        });
    });

    // Payment Gateway Toggle
    $('#paypal_enabled').on('change', function() {
        $('#paypal-settings').slideToggle(300);
        if (!$(this).prop('checked')) {
            // Clear PayPal fields when disabled
            $('#paypal-settings input').val('');
        }
    });

    $('#mpesa_enabled').on('change', function() {
        $('#mpesa-settings').slideToggle(300);
        if (!$(this).prop('checked')) {
            // Clear M-Pesa fields when disabled
            $('#mpesa-settings input').val('');
            $('#mpesa-settings select').prop('selectedIndex', 0);
        }
    });
});
