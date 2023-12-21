/* global jQuery */

( function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-select', function( e ) {
		var $$ = $( this );

		if ( $$.data( 'initialized' ) ) {
			return;
		}

		if ( typeof $.fn.select2 == 'function' ) {
			$$.find( '.sow-select2' ).select2();
		}

		$$.data( 'initialized', true );
	} );

} )( jQuery );
