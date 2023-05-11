/* global jQuery, soWidgets */

( function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-image-size', function( e ) {
		var $$ = $( this ),
			custom_size_wrapper = $$.find( '.custom-size-wrapper' );

		$$.find( 'select.siteorigin-widget-input' ).on( 'change', function() {
			if ( $( this ).val() == 'custom_size' ) {
				custom_size_wrapper.show();
			} else {
				custom_size_wrapper.hide();
			}
		} ).trigger( 'change' );
	} );

}( jQuery ) );
