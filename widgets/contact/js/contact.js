var SiteOriginContactForm = {
	init: function ($, useRecaptcha) {
		var $contactForms = $('form.sow-contact-form');
		$contactForms.each(function () {
			var $el = $(this);
			var $submitButton = $(this).find('.sow-submit-wrapper > input.sow-submit');
			if (useRecaptcha) {
				// Render recaptcha
				var $recaptchaDiv = $el.find('.sow-recaptcha');
				if ($recaptchaDiv.length) {
					var config = $recaptchaDiv.data('config');
					$submitButton.prop('disabled', true);
					grecaptcha.render($recaptchaDiv.get(0),
						{
							'sitekey': config.sitekey,
							'theme': config.theme,
							'type': config.type,
							'size': config.size,
							'callback': function (response) {
								// Enable the submit button once we have a response from recaptcha.
								$submitButton.prop('disabled', false);
							},
						}
					);
				}
			}

			// Disable the submit button on click to avoid multiple submits.
			$submitButton.click(function () {
				$submitButton.prop('disabled', true);
				//Ensure the form still submits
				$el.submit();
			});
		});
	},
};

function soContactFormInitialize() {
	SiteOriginContactForm.init(window.jQuery, true);
}

jQuery(function ($) {

	var $contactForms = $('form.sow-contact-form');
	// Check if there are any recaptcha placeholders.
	var useRecaptcha = $contactForms.toArray().some(function (form) {
		return $(form).find('div').hasClass('sow-recaptcha');
	});

	if (useRecaptcha) {
		if (window.recaptcha) {
			SiteOriginContactForm.init($, useRecaptcha);
		} else {
			// Load the recaptcha API
			var apiUrl = 'https://www.google.com/recaptcha/api.js?onload=soContactFormInitialize&render=explicit';
			var script = $('<script type="text/javascript" src="' + apiUrl + '" async defer>');
			$('body').append(script);
		}
	} else {
		SiteOriginContactForm.init($, useRecaptcha);
	}
});
