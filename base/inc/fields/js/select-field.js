/* global jQuery */

( function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-select', function( e ) {
		var $$ = $( this );

		if ( $$.data( 'initialized' ) ) {
			return;
		}

		if ( typeof $.fn.select2 == 'function' ) {
			$$.find( '.sow-select2' ).select2();

			// Prevent gap between dropdown items.
			let listMargin;
			$$.find( '.sow-select2' ).on( 'select2:open', function() {
				setTimeout( function() {
					var $dropdown = $( '.select2-results__option' );
					listMargin = $dropdown.css('margin');
					$dropdown.css('margin', '0');
				}, 1 );
			} );

			$$.find( '.sow-select2' ).on( 'select2:close', function() {
				$( '.select2-results__option' ).css( 'margin', listMargin );
			} );
		}

		$$.data( 'initialized', true );
	} );

} )( jQuery );
