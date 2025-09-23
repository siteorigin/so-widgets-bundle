/* global jQuery, soWidgets */

( function( $ ) {

	const setupOrderField = function( e ) {
		const $field = $( this );
		if ( $field.attr( 'data-initialized' ) ) {
			return;
		}

		$field.attr( 'data-initialized', true );

		const $valField = $field.find( '.siteorigin-widget-input' );
		const $items = $field.find( '.siteorigin-widget-order-items' );

		$items.sortable( {
			stop: function( e, ui ) {
				const val = $( this ).sortable( 'toArray', { attribute: 'data-value' } );
				$valField.val( val.join( ',' ) );
				$valField.trigger( 'change', { silent: true } );

				// Prevent Site Editor sortable from interfering.
				if ( e && e.stopImmediatePropagation ) {
					e.stopImmediatePropagation();
				}
			},
			zIndex: 999999,
		} );

		$field.on( 'change', function( event, params ) {
			if ( ! ( params && params.silent ) ) {
				const values = $valField.val() === '' ? [] : $valField.val().split( ',' );
				if ( values.length ) {
					for ( let i = 0; i < values.length; i++) {
						const val = values[ i ];
						const $item = $field.find( '.siteorigin-widget-order-item[data-value=' + val + ']' );
						$items.append( $item );
					}
				}
			}
		} );
	}

	 // If the current page isn't the site editor, set up the Order field now.
	 if (
		window.top === window.self &&
		(
			typeof pagenow === 'string' &&
			pagenow !== 'site-editor'
		)
	 ) {
		 $( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-order', setupOrderField );
	 }

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-order' ).each( function() {
				setupOrderField.call( this );
			} );
		}
	} );

}( jQuery ) );
