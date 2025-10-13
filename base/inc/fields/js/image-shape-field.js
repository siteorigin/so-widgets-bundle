/* global jQuery, soWidgets */

( function( $ ) {
	const setupImageShapeField = function( e ) {
		const $field = $( this );

		if ( $field.attr( 'data-initialized' ) !== undefined ) {
			return;
		}

		const $search = $field.find( '.siteorigin-widget-shape-search' ),
			$shapes = $field.find( '.siteorigin-widget-shapes' ),
			$data = $field.find( '.siteorigin-widget-input' ),
			$current = $field.find( '.siteorigin-widget-shape-current' );

		const loadActiveShape = function( $shape ) {
			$shape.addClass( 'selected' );
			$current.find( '.siteorigin-widget-shape' ).html( $shape.find( '.siteorigin-widget-shape-image' ).clone() );
			$current.find( '.siteorigin-widget-shape-image' ).data( 'shape', $shape.data( 'shape' ) )
			$field.trigger( 'shape_change', {
				shape: $shape.data( 'shape' ),
				field: $field
			} );
		}

		$current.on( 'click', function() {
			$shapes.hasClass('siteorigin-widget-shapes-open') ?
				$shapes.removeClass('siteorigin-widget-shapes-open') :
				$shapes.addClass('siteorigin-widget-shapes-open');
		} );

		// Handle shape selection.
		$shapes.find( '.siteorigin-widget-shape' ).on( 'click', function() {
			const $$ = $( this );
			$field.find( '.siteorigin-widget-shape' ).removeClass( 'selected' );
			loadActiveShape( $$ );
			$data.val( $$.data( 'shape' ) );
			$current.trigger( 'click' );
		} );

		// Handle searching for shapes.
		$search.on( 'keyup change', function() {
			const searchVal = $search.val().toLowerCase();
			if ( searchVal === '' ) {
				$shapes.find( '.siteorigin-widget-shape' ).show();
			} else {
				$shapes.find( '.siteorigin-widget-shape' ).each( function() {
					const $$ = $( this );
					if ( $$.data( 'shape' ).indexOf( searchVal ) === -1 ) {
						$$.hide();
					} else {
						$$.show();
					}
				} );
			}
		} );

		loadActiveShape( $shapes.find( '.siteorigin-widget-shape[data-shape="' + ( $data.val() !== '' ? $data.val() : 'circle' ) + '"]' ) );

		$field.attr( 'data-initialized', true );
	};


	 // If the current page isn't the site editor, set up the Image Shape field now.
	 if (
		 window.top === window.self &&
		 (
			 typeof pagenow === 'string' &&
			 pagenow !== 'site-editor'
		 )
	 ) {
		 $( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-image_shape', setupImageShapeField );
	 }

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-image_shape' ).each( function() {
				setupImageShapeField.call( this );
			} );
		}
	} );

} )( jQuery );
