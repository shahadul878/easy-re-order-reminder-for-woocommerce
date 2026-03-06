jQuery(document).ready(function($) {
	$('#easyrere-reminder-days-form').on('submit', function(e) {
		e.preventDefault();
		
		var form = $(this);
		var messageDiv = $('#easyrere-reminder-message');
		var button = form.find('button[type="submit"]');
        var btnText = button.find('.easyrere-btn-text');
		var originalText = btnText.text();
		
		button.prop('disabled', true);
        btnText.html('<span class="easyrere-spinner"></span> ' + easyrereThankYou.i18n.saving);
		messageDiv.hide().removeClass('easyrere-success easyrere-error');
		
		$.ajax({
			url: easyrereThankYou.ajaxUrl,
			type: 'POST',
			data: {
				action: 'easyrere_save_reminder_days',
				order_id: $('input[name="easyrere_order_id"]').val(),
				reminder_days: $('#easyrere_reminder_days').val(),
				nonce: $('input[name="easyrere_nonce"]').val(),
				key: easyrereThankYou.orderKey
			},
			success: function(response) {
				if (response.success) {
					messageDiv.addClass('easyrere-success').html(easyrereThankYou.i18n.success).fadeIn();
					setTimeout(function() {
						messageDiv.fadeOut();
					}, 3000);
				} else {
					messageDiv.addClass('easyrere-error').html(response.data || easyrereThankYou.i18n.error).fadeIn();
				}
			},
			error: function() {
				messageDiv.addClass('easyrere-error').html(easyrereThankYou.i18n.error).fadeIn();
			},
            complete: function() {
                button.prop('disabled', false);
                btnText.text(originalText);
            }
		});
	});
});
