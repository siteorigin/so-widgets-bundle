/* global jQuery, soWidgets */

( function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-tabs', function( e ) {
		var $field = $( this );

		if ( $field.data( 'initialized' ) ) {
			return;
		}

		$items = $field.find( '.siteorigin-widget-tabs > li' );
		if ( $items.length == 1 ) {
			// There's only one tab. Show the linked section as a standard tab.
			$( '.siteorigin-widget-field-' + $items.data( 'id' ) ).find( '> .siteorigin-widget-field-label ' ).removeClass( 'siteorigin-widget-section-tab' );
		} else {
			$items.on( 'click', function( e ) {
				var $$ = $( this );
				e.preventDefault();
				if ( ! $$.hasClass( 'sow-active-tab' ) ) {
					$( '.sow-active-tab' ).removeClass( 'sow-active-tab' );
					$( '.siteorigin-widget-field-' + $$.data( 'id' ) ).find( '> .siteorigin-widget-section' ).addClass( 'sow-active-tab' );
					$$.addClass( 'sow-active-tab' );
				}
			} );
			$items.first().trigger( 'click' );

		}


		$field.data( 'initialized', true );
	} );

} )( jQuery );
