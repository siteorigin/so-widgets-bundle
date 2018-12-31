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
				$this.error( showFallbackImage );
			}
		} );
		
	};
	setupStaticMapErrorHandler();

	$( window ).on('load resize', function() {
		$( '.sowb-google-map-static' ).each( function () {
			var $this = $( this );
			var src = $this.prop( 'src' );

			var breakpointCheck = window.matchMedia( '(max-width: ' + soWidgetsGoogleMapStatic.breakpoint + 'px)' )
			// Check if the user is viewing the map on mobile
			if ( breakpointCheck.matches ) {
				// Scale the map for mobile
				 $this.attr( 'src', src + '&mobile=true&scale=2' );
			} else {
				// Ensure the static map enabled for mobile and if it is, restore it back to normal
				if ( src.indexOf( '&mobile=true' ) >= 0 ) {
					$this.attr( 'src', src.split('&mobile=true')[0] );
				}
			}
			
		} );
	} );
} );

window.sowb = sowb;
