jQuery(document).ready(function($) {
	$('#easyrere_send_test_email').on('click', function() {
		var email = $('#easyrere_test_email_address').val();
		if (!email) {
			alert(easyrereSettings.i18n.enterEmail);
			return;
		}
		$('#easyrere_test_email_result').html('<span style="color: #666;">' + easyrereSettings.i18n.sending + '</span>');
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'easyrere_send_test_email',
				email: email,
				nonce: easyrereSettings.nonce
			},
			success: function(response) {
				if (response.success) {
					$('#easyrere_test_email_result').html('<span style="color: #46b450;">' + easyrereSettings.i18n.success + '</span>');
				} else {
					$('#easyrere_test_email_result').html('<span style="color: #dc3232;">' + (response.data || easyrereSettings.i18n.error) + '</span>');
				}
			},
			error: function() {
				$('#easyrere_test_email_result').html('<span style="color: #dc3232;">' + easyrereSettings.i18n.error + '</span>');
			}
		});
	});
});
