jQuery(document).ready(function($) {
    const Settings = {
        init: function() {
            this.bindEvents();
            this.initializeActiveTab();
            
            // Add this to ensure proper initial state
            this.handleUrlParams();
        },

        bindEvents: function() {
            $('.settings-form').on('submit', this.handleFormSubmit);
            $('.reset-settings').on('click', this.handleResetSettings);
            $('.nav-tab').on('click', this.handleTabClick);
            window.addEventListener('popstate', this.handlePopState.bind(this));
        },

        handleUrlParams: function() {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'general';
            this.activateTab(tab, false); // false means don't push state
        },

        initializeActiveTab: function() {
            // Show the first tab by default if no tab is specified
            if (!window.location.search.includes('tab=')) {
                this.activateTab('general', false);
            }
        },

        handleTabClick: function(e) {
            e.preventDefault();
            const tab = $(this).data('tab'); // Changed to use data attribute
            Settings.activateTab(tab, true); // true means push state
        },

        handlePopState: function(event) {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || 'general';
            this.activateTab(tab, false);
        },

        activateTab: function(tab, pushState) {
            // Update tab navigation
            $('.nav-tab').removeClass('nav-tab-active');
            $(`.nav-tab[data-tab="${tab}"]`).addClass('nav-tab-active');

            // Update content visibility with smooth transition
            $('.settings-group').fadeOut(200).promise().done(function() {
                $(`.settings-group.${tab}`).fadeIn(200);
            });

            // Update URL if needed
            if (pushState) {
                const newUrl = new URL(window.location);
                newUrl.searchParams.set('tab', tab);
                history.pushState({tab: tab}, '', newUrl);
            }

            // Trigger custom event for other scripts
            $(document).trigger('settings-tab-changed', [tab]);
        },

        handleFormSubmit: function(e) {
            e.preventDefault();
            const $form = $(this);
            const $submitButton = $form.find('.save-settings');
            const $spinner = $submitButton.find('.dashicons');
            
            // Show loading state
            $submitButton.prop('disabled', true);
            $spinner.addClass('spin');
            
            // Create FormData object
            const formData = new FormData($form[0]);
            formData.append('action', 'save_settings');
            formData.append('security', wpDonationSystem.nonce);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Settings.showMessage($form, wpDonationSystem.strings.saved, 'success');
                    } else {
                        Settings.showMessage($form, response.data.message || wpDonationSystem.strings.error, 'error');
                    }
                },
                error: function() {
                    Settings.showMessage($form, wpDonationSystem.strings.error, 'error');
                },
                complete: function() {
                    $submitButton.prop('disabled', false);
                    $spinner.removeClass('spin');
                }
            });
        },

        handleResetSettings: function() {
            if (!confirm(wpDonationSystem.strings.confirm_reset)) {
                return;
            }

            const $form = $(this).closest('form');
            const groupId = $form.data('group');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'reset_settings',
                    group: groupId,
                    security: wpDonationSystem.nonce
                },
                success: function(response) {
                    if (response.success) {
                        window.location.reload();
                    } else {
                        Settings.showMessage($form, response.data.message || wpDonationSystem.strings.error, 'error');
                    }
                },
                error: function() {
                    Settings.showMessage($form, wpDonationSystem.strings.error, 'error');
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
        }
    };

    Settings.init();
}); 