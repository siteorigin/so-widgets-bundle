/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	var setupStaticMapErrorHandler = function() {

		$( '.sowb-google-map-static' ).each( function () {
			var $this = $( this );
			var showFallbackImage = function() {
				if ( $this.data( 'fallbackImage' ) ) {
					var imgData = $this.data( 'fallbackImage' );
					if ( imgData.hasOwnProperty( 'img' ) ) {
						$this.parent().append( imgData.img );
						$this.remove();
					}
				}
			};
			if ( this.sowbLoadError ) {
				showFallbackImage();
			} else if ( ! this.complete ) {
				$this.error( showFallbackImage );
			}
		} );

	};
	setupStaticMapErrorHandler();
} );

window.sowb = sowb;
