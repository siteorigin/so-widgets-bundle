jQuery( document ).on( 'sow_validate_widget_data', function( e, valid, form, id ) {
	if ( id !== 'sow-contact-form' ) {
		return valid;
	}

	const values = sowbForms.getWidgetFormValues( form );

	// If we don't have the required data to validate, don't attempt
	// to validate.
	if (
		! values ||
		! values.settings ||
		! values.settings.to.length ||
		! values.settings.from.length
	) {
		return valid;
	}

	// If the emails are different, we don't need to validate.
	if ( values.settings.to !== values.settings.from ) {
		return valid;
	}

	// Emails are the same. Let's show an error.
	const $settingsSection = form.find( '.siteorigin-widget-field-settings' );
	const $settingsSectionLabel = form.find( '.siteorigin-widget-field-label ' );
	if ( ! $settingsSectionLabel.hasClass( 'siteorigin-widget-section-visible' ) ) {
		$settingsSectionLabel.trigger( 'click' );
	}

	// Ensure the error message isn't already present.
	if ( form.find( '.siteorigin-widget-form-notification.sow-error' ).length ) {
		return false;
	}

	const $errorMessage = jQuery( `<div class="siteorigin-widget-form-notification sow-error">${ sowContactAdmin.error }</div>` );
	$settingsSection.prepend(
		$errorMessage
	);

	// Clear error message after either email field is changed.
	form.find( '.siteorigin-widget-field-to .siteorigin-widget-input, .siteorigin-widget-field-from .siteorigin-widget-input' )
		.one( 'change', () => {
		$errorMessage.remove();
	} );

	return false;
} );
