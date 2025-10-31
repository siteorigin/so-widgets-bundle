/* global jQuery, soWidgets */

( function( $ ) {

	const setupTabsField = function( e ) {
		const $field = $( this );

		if ( $field.data( 'initialized' ) ) {
			return;
		}

		const $items = $field.find( '.siteorigin-widget-tabs > li' );
		if ( $items.length == 1 ) {
			// There's only one tab. Show the linked section as a standard tab.
			$( '.siteorigin-widget-field-' + $items.data( 'id' ) ).find( '> .siteorigin-widget-field-label ' ).removeClass( 'siteorigin-widget-section-tab' );
		} else {
			$items.on( 'click', function( e ) {
				const $$ = $( this );
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
	};

	 // If the current page isn't the site editor, set up the Tabs field now.
	 if (
		 window.top === window.self &&
		 (
			 typeof pagenow === 'string' &&
			 pagenow !== 'site-editor'
		 )
	 ) {
		 $( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-tabs', setupTabsField );
	 }

		// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-tabs' ).each( function() {
				setupTabsField.call( this );
			} );
		}
	} );

} )( jQuery );
