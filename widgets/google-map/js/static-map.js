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
			var $src = $this.data( 'src' );

			// If no mobile zoom is set, skip this static map
			if ( $src.length == 0) {
				return;
			}

			if ( window.innerWidth > soWidgetsGoogleMapStatic.breakpoint ) {
				 if ( $src.desktop != $this.attr( 'src' ) ) {
					$this.attr( 'src', $src.desktop );
				}
			} else {
				if ( $src.mobile != $this.attr( 'src' ) ) {
					$this.attr( 'src', $src.mobile );
				}
			}
			
		} );
	} );
} );

window.sowb = sowb;
