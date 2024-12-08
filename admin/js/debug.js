jQuery(document).ready(function($) {
    const Debug = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            $('.clear-logs').on('click', this.handleClearLogs);
            $('.download-logs').on('click', this.handleDownloadLogs);
        },

        handleClearLogs: function() {
            if (confirm(wpDonationSystem.strings.confirm_clear_logs)) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'clear_logs',
                        security: wpDonationSystem.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            window.location.reload();
                        } else {
                            alert(response.data.message || wpDonationSystem.strings.error);
                        }
                    },
                    error: function() {
                        alert(wpDonationSystem.strings.network_error);
                    }
                });
            }
        },

        handleDownloadLogs: function() {
            window.location.href = ajaxurl + '?action=download_logs&security=' + wpDonationSystem.nonce;
        }
    };

    Debug.init();
}); 