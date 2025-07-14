/* global jQuery, soWidgets */

( function( $ ) {

	const setupRadioField = function( e ) {
		const $$ = $( this );
		$$.find( 'input[type="radio"]:checked' ).parent().addClass( 'so-selected' );
		$$.find( 'input[type="radio"]' ).on( 'change', function() {
			$$.find( 'input[type="radio"]' ).parent().removeClass( 'so-selected' );
			$$.find( 'input[type="radio"]:checked' ).parent().addClass( 'so-selected' );
		} );
	};

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-image-radio', setupRadioField );

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-image-radio' ).each( function() {
				setupRadioField.call( this );
			} );
		}
	} );

}( jQuery ) );
