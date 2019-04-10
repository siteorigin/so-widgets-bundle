/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	var setupPriceTable = function () {
		$( '.so-widget-sow-price-table' ).each( function () {
			var $priceTable = $( this );
			
			if ( ! $priceTable.is( ':visible' ) || $priceTable.data( 'initialized' ) ) {
				return $priceTable;
			}

			function resizePriceTable() {
				$priceTable.find('.sow-equalize-row-heights').each(function () {
					var $pt = $(this);
					var equalizeHeights = function (selector) {
						var maxHeight = 0;
						var $elements = $pt.find(selector);
						$elements.css('height', '');
						$elements.each(function () {
							maxHeight = Math.max(maxHeight, $(this).height());
						});
						$elements.height(maxHeight);
					};

					var maxFeatures = 0;
					$pt.find('.ow-pt-features').each(function () {
						maxFeatures = Math.max(maxFeatures, $(this).find('.ow-pt-feature').length);
					});

					for (var i = 0; i < maxFeatures; i++) {
						equalizeHeights('.ow-pt-feature-index-' + i);
					}

					var selectors = ['.ow-pt-title', '.ow-pt-details', '.ow-pt-image', '.ow-pt-features', '.ow-pt-button'];
					selectors.forEach(equalizeHeights);
				});

				$('.ow-pt-icon[data-icon]').each(function () {
					var $$ = $(this);
					var icon = $$.data('icon');

					if ($('#so-pt-icon-' + icon).length) {
						var svg = $('#so-pt-icon-' + icon + ' svg').clone().css({
							'max-width': 24,
							'max-height': 24
						});

						if ($$.data('icon-color') !== '') {
							svg.find('path').css('fill', $$.data('icon-color'));
						} else {
							svg.find('path').css('fill', '#333333');
						}

						$$.append(svg);
					}
				});
			}

			resizePriceTable();
			$(window).on( 'resize orientationchange', resizePriceTable );

			$priceTable.data( 'initialized', true );
		} );
	};
	
	setupPriceTable();
	
	$( sowb ).on( 'setup_widgets', setupPriceTable );
} );
