/* global jQuery, soWidgets */

( function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-tabs', function( e ) {
		var $field = $( this );

		if ( $field.data( 'initialized' ) ) {
			return;
		}

		$field.find( '.siteorigin-widget-tabs > li' ).on( 'click', function( e ) {
			var $$ = $( this );
			e.preventDefault();
			if ( ! $$.hasClass( 'sow-active-tab' ) ) {
				$( '.sow-active-tab' ).removeClass( 'sow-active-tab' );
				$( '.siteorigin-widget-field-' + $$.data( 'id' ) ).find( '> .siteorigin-widget-section' ).addClass( 'sow-active-tab' );
				$$.addClass( 'sow-active-tab' );
			}
		} );
		$field.find( '.siteorigin-widget-tabs > li:first-of-type' ).trigger( 'click' );

		$field.data( 'initialized', true );
	} );

} )( jQuery );
