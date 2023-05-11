/* globals jQuery */

jQuery( function ( $ ) {
	$( '.sow-slide-control' ).on( 'click', function( e ) {
		var $$ = $( this ),
			cycleContainer = $$.parents( '.sow-slider-images' );
			slideValue = $$.attr( 'href' ).substr( 1 );

		e.preventDefault();

		if ( ! isNaN( slideValue ) ) {
			cycleContainer.cycle( 'goto', Math.abs( slideValue - 1 ) );
		} else {
			switch ( slideValue ) {
				case 'first':
					cycleContainer.cycle( 'goto', 0 );
					break;
				case 'last':
					cycleContainer.cycle( 'goto', cycleContainer.find( '.sow-slider-image:not(.cycle-sentinel)' ).length - 1 );
					break;
				case 'next':
					cycleContainer.cycle( 'next' );
					break;
				case 'previous':
					cycleContainer.cycle( 'prev' );
					break;
			}
		}
	} );
} );
