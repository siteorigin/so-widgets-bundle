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

	 // If the current page isn't the site editor, set up the Image Radio field now.
	 if (
		 window.top === window.self &&
		 (
			 typeof pagenow === 'string' &&
			 pagenow !== 'site-editor'
		 )
	 ) {
		 $( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-image-radio', setupRadioField );
	 }

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-image-radio' ).each( function() {
				setupRadioField.call( this );
			} );
		}
	} );

}( jQuery ) );