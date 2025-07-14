/* global jQuery, soWidgets */

( function( $ ) {

	const setupImageSizeField = function() {
		const $$ = $( this ),
			custom_size_wrapper = $$.find( '.custom-size-wrapper' );

		$$.find( 'select.siteorigin-widget-input' ).on( 'change', function() {
			if ( $( this ).val() == 'custom_size' ) {
				custom_size_wrapper.show();
			} else {
				custom_size_wrapper.hide();
			}
		} ).trigger( 'change' );
	}

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-image-size', setupRadioField );

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-image-size' ).each( function() {
				setupImageSizeField.call( this );
			} );
		}
	} );

}( jQuery ) );
