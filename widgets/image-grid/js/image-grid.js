/* globals jQuery, sowb */
var sowb = window.sowb || {};

jQuery( function ( $ ) {
	sowb.setupImageGrids = function () {
		$( '.sow-image-grid-wrapper' ).each( function () {
			var $$ = $( this );
			$$.imagesLoaded( function () {
				var maxWidth = $$.data( 'max-width' ),
					maxHeight = $$.data( 'max-height' );
				
				if ( maxWidth !== undefined || maxHeight !== undefined ) {
					$$.find( 'img' ).each( function () {
						var $img = $( this ).css( 'opacity', 1 );
						var ratio = $img.width() / $img.height();
						
						var width = [];
						
						// Lets set the widths of the image
						if ( maxWidth !== undefined && $img.width() > maxWidth ) {
							width.push( maxWidth );
						}
						
						if ( maxHeight !== undefined && $img.height() > maxHeight ) {
							width.push( Math.round( maxHeight * ratio ) );
						}
						
						if ( width.length ) {
							width = Math.min.apply( Math, width );
							$img.css( 'max-width', width );
						}
						
					} );
				}
				else {
					$$.find( 'img' ).css( 'opacity', 1 );
				}
				
				var alignImages = function () {
				};
				alignImages();
				
				$( window ).resize( alignImages );

				var event = document.createEvent('Event');
				event.initEvent('layoutComplete', true, true);
				$$.get(0).dispatchEvent(event);
			} );
		} );
	};
	sowb.setupImageGrids();
	
	$( sowb ).on( 'setup_widgets', sowb.setupImageGrids );
	
} );

window.sowb = sowb;
