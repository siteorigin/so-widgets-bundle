( function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-toggle', function( e ) {
		const $field = $( this );

		if ( $field.data( 'initialized' ) ) {
			return;
		}

		const $state = $field.find( '.sowb-toggle-switch-input' );
		const $label = $field.find( '.sowb-toggle-switch' );
		const $container = $field.find( '.siteorigin-widget-toggle' );

		$state.on( 'change', () => {
			const status = $state.is( ':checked' );

			if ( status ) {
				$container.slideDown( 200 );
				$label.addClass( 'sowb-toggled-on' ).removeClass( 'sowb-toggled-off' );
			} else {
				$container.slideUp( 200 );
				$label.addClass( 'sowb-toggled-off' ).removeClass( 'sowb-toggled-on' );
			}

			$state.val( status ? 'open' : 'closed' );
		} );

		$field.data( 'initialized', true );
	} );

} )( jQuery );
