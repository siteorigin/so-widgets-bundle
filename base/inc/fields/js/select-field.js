/* global jQuery */

( function( $ ) {

	const setupSelectField = function() {
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
	};

	// If the current page isn't the site editor, set up the Select field now.
	if (
		window.top === window.self &&
		(
			typeof pagenow === 'string' &&
			pagenow !== 'site-editor'
		)
	) {
		$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-select', setupSelectField );
	}

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-select' ).each( function() {
				setupSelectField.call( this );
			} );
		}
	} );

} )( jQuery );
