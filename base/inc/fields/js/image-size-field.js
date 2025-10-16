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

	 // If the current page isn't the site editor, set up the Image Size field now.
	 if (
		 window.top === window.self &&
		 (
			 typeof pagenow === 'string' &&
			 pagenow !== 'site-editor'
		 )
	 ) {
		 $( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-image-size', setupImageSizeField );
	 }

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-image-size' ).each( function() {
				setupImageSizeField.call( this );
			} );
		}
	} );


}( jQuery ) );
