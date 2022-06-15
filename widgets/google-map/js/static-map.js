/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	var setupStaticMapErrorHandler = function () {
		
		$( '.sowb-google-map-static' ).each( function () {
			var $this = $( this );
			var showFallbackImage = function () {
				if ( $this.data( 'fallbackImage' ) ) {
					var imgData = $this.data( 'fallbackImage' );
					if ( imgData.hasOwnProperty( 'img' ) && imgData.img.length > 0 ) {
						$this.parent().append( imgData.img );
						$this.remove();
					}
				}
			};
			if ( this.sowbLoadError ) {
				showFallbackImage();
			} else if ( !this.complete ) {
				$this.on( 'error', showFallbackImage );
			}
		} );
		
	};
	setupStaticMapErrorHandler();

	$( window ).on('load resize setup_widgets', function() {
		$( '.sowb-google-map-static' ).each( function () {
			var $this = $( this );
			var src = $this.prop( 'src' );
			var breakpointCheck = window.matchMedia( '(max-width: ' + $this.data( 'breakpoint' ) + 'px)' )
			// Check if the user is viewing the map on mobile
			if ( breakpointCheck.matches ) {
				// Scale the map for mobile
				 $this.attr( 'src', src + '&scale=2' );
			} else {
				// Check if the static map enabled for mobile and if it is, restore it back to normal
				if ( src.indexOf( '&scale=2' ) >= 0 ) {
					$this.attr( 'src', src.split('&scale=2')[0] );
				}
			}
			
		} );
	} );
} );

window.sowb = sowb;
