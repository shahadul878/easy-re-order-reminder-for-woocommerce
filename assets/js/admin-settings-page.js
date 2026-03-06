jQuery(document).ready(function($) {
	$('#easyrere_send_test_email_btn').on('click', function() {
		$('#easyrere_test_email_form').toggle();
		$('#easyrere_test_email_address').focus();
	});

	$('#easyrere_send_test_email_submit').on('click', function() {
		var email = $('#easyrere_test_email_address').val();
		if (!email) {
			alert(easyrereAdminSettings.i18n.enterEmail);
			return;
		}

		var $button = $(this);
		var $result = $('#easyrere_test_email_result');
		var originalText = $button.text();

		$button.prop('disabled', true).text(easyrereAdminSettings.i18n.sending);
		$result.html('');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'easyrere_send_test_email',
				email: email,
				nonce: easyrereAdminSettings.nonce
			},
			success: function(response) {
				if (response.success) {
					$result.html('<span style="color: #46b450;">' + easyrereAdminSettings.i18n.success + '</span>');
				} else {
					$result.html('<span style="color: #dc3232;">' + (response.data || easyrereAdminSettings.i18n.error) + '</span>');
				}
			},
			error: function() {
				$result.html('<span style="color: #dc3232;">' + easyrereAdminSettings.i18n.error + '</span>');
			},
			complete: function() {
				$button.prop('disabled', false).text(originalText);
			}
		});
	});
});
