/* globals sowb, jQuery */

window.sowb = window.sowb || {};

sowb.SiteOriginContactForm = {
	init: function ($, useRecaptcha) {
		var $contactForms = $('form.sow-contact-form,.sow-contact-form-success');
		$contactForms.each(function () {
			var $el = $( this );
			var formId = $el.attr( 'id' );
			var formSubmitted = window.location.hash.indexOf( formId ) > -1;
			var formSubmitSuccess = $el.is( '.sow-contact-form-success' );
			if ( formSubmitted ) {
				// The form was submitted. Let's try to scroll to it so the user can see the result.
				var formPosition = $el.offset().top;
				if ( $el.is( ':hidden' ) ) {
					// The form is hidden, so scroll to it's closest visible ancestor.
					var $container = $el.closest( ':visible' );
					formPosition = $container.offset().top;
					// If the closest visible ancestor is either SOWB Accordion or Tabs widget, try to open the panel.
					if ( $container.is( '.sow-accordion-panel' ) ) {
						$container.find( '> .sow-accordion-panel-header' ).click();
					} else if ( $container.is( '.sow-tabs-panel-container' ) ) {
						var tabIndex = $el.closest( '.sow-tabs-panel' ).index();
						$container.siblings( '.sow-tabs-tab-container' ).find( '> .sow-tabs-tab' ).eq( tabIndex ).click();
					}
				}
				$( 'html, body' ).scrollTop( formPosition );
				
				if ( formSubmitSuccess ) {
					// The form was submitted successfully, so we don't need to do anything else.
					return;
				}
			}
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
			$contactForms.submit( function () {
				$submitButton.prop( 'disabled', true );
				// Preserve existing anchors, if any.
				var locationHash = window.location.hash;
				if ( locationHash ) {
					var formAction = $( this ).attr( 'action' );
					
					if ( locationHash.indexOf( formId ) > -1 ) {
						var re = new RegExp( formId + ',?', 'g' );
						locationHash = locationHash.replace( re, '' );
					}
					$( this ).attr( 'action', formAction + ',' + locationHash.replace( /^#/, '' ) );
				}
			} );
		} );
	},
};

function soContactFormInitialize() {
	sowb.SiteOriginContactForm.init(window.jQuery, true);
}

jQuery(function ($) {

	var $contactForms = $('form.sow-contact-form');
	// Check if there are any recaptcha placeholders.
	var useRecaptcha = $contactForms.toArray().some(function (form) {
		return $(form).find('div').hasClass('sow-recaptcha');
	});

	if (useRecaptcha) {
		if (window.recaptcha) {
			sowb.SiteOriginContactForm.init($, useRecaptcha);
		} else {
			// Load the recaptcha API
			var apiUrl = 'https://www.google.com/recaptcha/api.js?onload=soContactFormInitialize&render=explicit';
			var script = $('<script type="text/javascript" src="' + apiUrl + '" async defer>');
			$('body').append(script);
		}
	} else {
		sowb.SiteOriginContactForm.init($, useRecaptcha);
	}
});
