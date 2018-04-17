/* globals jQuery, packery, sowb */

var sowb = window.sowb || {};

jQuery( function($){
	sowb.setupSimpleMasonries = function() {
		var $grid = $('.sow-masonry-grid');

		var resizeMasonry = function(){
			$grid.each(function(){
				var $gridEl = $(this);
				var layouts = $gridEl.data('layouts');
				var tabletQuery = window.matchMedia('(max-width: ' + layouts.tablet.breakPoint + 'px)');
				var mobileQuery = window.matchMedia('(max-width: ' + layouts.mobile.breakPoint + 'px)');
				var layout = layouts.desktop;
				if(mobileQuery.matches) {
					layout = layouts.mobile;
				} else if (tabletQuery.matches) {
					layout = layouts.tablet;
				}
				var numColumns = layout.numColumns;
				$gridEl.css('width', 'auto');
				var horizontalGutterSpace = layout.gutter * ( numColumns - 1 );
				var columnWidth = Math.floor( ( $gridEl.width() - ( horizontalGutterSpace ) ) / numColumns );
				$gridEl.width( ( columnWidth * numColumns ) + horizontalGutterSpace );

				$gridEl.imagesLoaded( function() {
					$gridEl.find('> .sow-masonry-grid-item').each(function(){
						var $$ = $(this);
						var colSpan = $$.data('colSpan');
						colSpan = Math.max(Math.min(colSpan, layout.numColumns), 1);
						$$.width( ( columnWidth * colSpan ) + (layout.gutter * (colSpan-1)));
						var rowSpan = $$.data('rowSpan');
						rowSpan = Math.max(Math.min(rowSpan, layout.numColumns), 1);
						//Use rowHeight if non-zero else fall back to matching columnWidth.
						var rowHeight = layout.rowHeight || columnWidth;
						$$.css('height', (rowHeight * rowSpan) + (layout.gutter * (rowSpan-1)));

						var $img = $$.find('> img,> a > img');
						var imgAR = $img.attr('height') > 0 ? $img.attr('width')/$img.attr('height') : 1;
						var itemAR = $$.height() > 0 ? $$.width()/$$.height() : 1;
						imgAR = parseFloat(imgAR.toFixed(3));
						itemAR = parseFloat(itemAR.toFixed(3));
						if(imgAR > itemAR) {
							$img.css('width', 'auto');
							$img.css('height', '100%');
							$img.css('margin-top', '');
							var marginLeft = ($img.width() - $$.width()) * -0.5;
							$img.css('margin-left', marginLeft+'px');
						}
						else {
							$img.css('height', 'auto');
							$img.css('width', '100%');
							$img.css('margin-left', '');
							var marginTop = ($img.height() - $$.height()) * -0.5;
							$img.css('margin-top', marginTop+'px');
						}
					});
					$gridEl.packery({
						itemSelector: '.sow-masonry-grid-item',
						columnWidth: columnWidth,
						gutter: layout.gutter
					});
				});
			});
		};
		
		// If the Simple Masonry container is hidden it won't display properly. This is an attempt to make it display by
		// calling resize when a custom 'show' event is fired. The 'show' event is something we fire in a few widgets
		// like Accordion and Tabs and in future any widgets which might show and hide content using `display:none;`.
		if ( $( $grid ).is( ':hidden' ) ) {
			var $visParent = $( $grid ).closest( ':visible' );
			$visParent.find( '> :hidden' ).on( 'show', function () {
				resizeMasonry();
			} );
		}

		$(window).on('resize panelsStretchRows', resizeMasonry);

		// Ensure that the masonry has resized correctly on load.
		setTimeout( function () {
			resizeMasonry();
		}, 100 );
	};
	sowb.setupSimpleMasonries();

	$( sowb ).on( 'setup_widgets', sowb.setupSimpleMasonries );
} );

window.sowb = sowb;
