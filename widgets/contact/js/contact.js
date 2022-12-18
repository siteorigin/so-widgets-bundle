/* globals sowb, jQuery */

window.sowb = window.sowb || {};

sowb.SiteOriginContactForm = {
	init: function ($, useRecaptcha) {
		var $contactForms = $('form.sow-contact-form,.sow-contact-form-success');
		$contactForms.each( function() {
			var $el = $( this );
			var formId = $el.attr( 'id' );
			var formSubmitSuccess = $el.is( '.sow-contact-form-success' );
			var formSubmitted = $el.is( '.sow-contact-submitted' );
			if ( formSubmitted || formSubmitSuccess ) {
				// The form was submitted. Let's try to scroll to it so the user can see the result.
				if ( sowContact.scrollto ) {
					var formPosition = $el.offset().top;
					if ( $el.is( ':hidden' ) ) {
						// The form is hidden, so scroll to it's closest visible ancestor.
						var $container = $el.closest( ':visible' );
						formPosition = $container.offset().top;
						// If the closest visible ancestor is either SOWB Accordion or Tabs widget, try to open the panel.
						if ( $container.is( '.sow-accordion-panel' ) ) {
							$container.find( '> .sow-accordion-panel-header-container > .sow-accordion-panel-header' ).trigger( 'click' );
						} else if ( $container.is( '.sow-tabs-panel-container' ) ) {
							var tabIndex = $el.closest( '.sow-tabs-panel' ).index();
							$container.siblings( '.sow-tabs-tab-container' ).find( '> .sow-tabs-tab' ).eq( tabIndex ).trigger( 'click' );
						}
					}
					formPosition += parseInt( sowContact.scrollto_offset );
					$( 'html, body' ).scrollTop( formPosition );
				}
				
				if ( formSubmitSuccess ) {
					// The form was submitted successfully, so we don't need to do anything else.
					return;
				}
			}
			var $submitButton = $( this ).find( '.sow-submit-wrapper > .sow-submit' );

			if ( useRecaptcha && sowb.SiteOriginContactFormV2 ) {
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
			$contactForms.on( 'submit', function() {
				$submitButton.prop( 'disabled', true );

				if ( $submitButton.data( 'js-key' ) ) {
					var js_key = $submitButton.data( 'js-key' );
					$( this ).append( `<input type="hidden" name="sow-js-${js_key}" value="${js_key}">` );
				}
			} );
		} );
	},
};

function soContactFormInitialize() {
	sowb.SiteOriginContactForm.init(window.jQuery, true);
}

// reCAPTCHA v3 form submission.
function soContactFormSubmit( token, e ) {
	sowb.SiteOriginContactFormV3.parent().parent().trigger( 'submit' );
}

jQuery( function ( $ ) {
	var recaptcha = $( 'form.sow-contact-form .sow-recaptcha' );
	// Check if reCAPTCHA is being used.
	if ( recaptcha.length ) {
		if (window.recaptcha) {
			sowb.SiteOriginContactForm.init( $, recaptcha );
		} else {
			var apiUrl = 'https://www.google.com/recaptcha/api.js?onload=soContactFormInitialize';
			// v2 requires a specific render type.
			if ( recaptcha.first().data( 'config' ) != undefined ) {
				sowb.SiteOriginContactFormV2 = true;
				apiUrl += '&render=explicit';
			} else {
				// v3 requires a click event for submission.
				$( 'button.sow-submit ' ).on( 'click', function( e ) {
					e.preventDefault();
					sowb.SiteOriginContactFormV3 = $( this );
				} );
			}
			var script = $( '<script type="text/javascript" src="' + apiUrl + '" async defer>' );
			$( 'body' ).append( script );
		}
	} else {
		sowb.SiteOriginContactForm.init( $, recaptcha );
	}
} );
