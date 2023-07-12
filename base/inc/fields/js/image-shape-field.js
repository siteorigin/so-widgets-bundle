/* global jQuery, soWidgets */

( function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-image_shape', function( e ) {
		if ( $( this ).data( 'initialized' ) ) {
			return;
		}

		var $field = $( this ),
			$search = $field.find( '.siteorigin-widget-shape-search' ),
			$shapes = $field.find( '.siteorigin-widget-shapes' ),
			$data = $field.find( '.siteorigin-widget-input' ),
			$current = $field.find( '.siteorigin-widget-shape-current' );

		var loadActiveShape = function( $shape ) {
			$shape.addClass( 'selected' );
			$current.find( '.siteorigin-widget-shape' ).html( $shape.find( '.siteorigin-widget-shape-image' ).clone() );
			$current.find( '.siteorigin-widget-shape-image' ).data( 'shape', $shape.data( 'shape' ) )
			$field.trigger( 'shape_change', {
				shape: $shape.data( 'shape' ),
				field: $field
			} );
		}

		$current.on( 'click', function() {
			$shapes.toggleClass( 'siteorigin-widget-shapes-open' );
		} );

		// Handle shape selection.
		$shapes.find( '.siteorigin-widget-shape' ).on( 'click', function() {
			var $$ = $( this );
			$field.find( '.siteorigin-widget-shape' ).removeClass( 'selected' );
			loadActiveShape( $$ );
			$data.val( $$.data( 'shape' ) );
			$current.trigger( 'click' );
		} );

		// Handle searching for shapes.
		$search.on( 'keyup change', function() {
			var searchVal = $search.val().toLowerCase();
			if ( searchVal === '' ) {
				$shapes.find( '.siteorigin-widget-shape' ).show();
			} else {
				$shapes.find( '.siteorigin-widget-shape' ).each( function() {
					var $$ = $( this );
					if ( $$.data( 'shape' ).indexOf( searchVal ) === -1 ) {
						$$.hide();
					} else {
						$$.show();
					}
				} );
			}
		} );

		loadActiveShape( $shapes.find( '.siteorigin-widget-shape[data-shape="' + ( $data.val() !== '' ? $data.val() : 'circle' ) + '"]' ) );

		$field.data( 'initialized', true );
	} );

} )( jQuery );
