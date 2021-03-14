/* globals jQuery */

jQuery( function ( $ ) {
	$( '.sow-slider-control' ).on( 'click', function() {
		var $$ = $( this ),
			cycleContainer = $$.parents( '.sow-slider-images' );
			slideValue = $$.data( 'slide' );

		if ( ! isNaN( slideValue ) ) {
			cycleContainer.cycle( 'goto', slideValue );
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
