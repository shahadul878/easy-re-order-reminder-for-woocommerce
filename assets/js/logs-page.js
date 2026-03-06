jQuery(document).ready(function($) {
    $('.easyrere-resend-email').on('click', function() {
        var button = $(this);
        var logId = button.data('id');
        var nonce = button.data('nonce');
        var statusSpan = button.next('.easyrere-resend-status');
        
        button.prop('disabled', true).text(easyrereLogs.i18n.sending);
        statusSpan.html('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'easyrere_resend_email',
                log_id: logId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    statusSpan.html('<span style="color: #46b450; font-size: 18px;" title="' + easyrereLogs.i18n.emailSent + '">&#10003;</span>');
                } else {
                    statusSpan.html('<span style="color: #dc3232;" title="' + (response.data || 'Error') + '">&#10060;</span>');
                    alert(response.data || easyrereLogs.i18n.error);
                }
            },
            error: function() {
                statusSpan.html('<span style="color: #dc3232;">&#10060;</span>');
                alert(easyrereLogs.i18n.networkError);
            },
            complete: function() {
                button.prop('disabled', false).text(easyrereLogs.i18n.resend);
            }
        });
    });
});
