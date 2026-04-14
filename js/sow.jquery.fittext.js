/*global jQuery, sowb */
/*!
 * FitText.js 1.2
 *
 * Copyright 2011, Dave Rupert http://daverupert.com
 * Released under the WTFPL license
 * http://sam.zoy.org/wtfpl/
 *
 * Date: Thu May 05 14:23:00 2011 -0600
 */
var sowb = window.sowb || {};

(function ($) {

	$.fn.fitText = function (kompressor, options) {

		// Setup options
		var compressor = kompressor || 1,
			settings = $.extend({
				'minFontSize': Number.NEGATIVE_INFINITY,
				'maxFontSize': Number.POSITIVE_INFINITY
			}, options);

		return this.each(function () {

			// Store the object
			var $this = $(this);
			var fitTextData = $this.data( 'fitTextData' ) || {};

			if ( fitTextData.initialized ) {
				fitTextData.resizer();
				return;
			}

			// Resizer() resizes items based on the object width divided by the compressor * 10
			var resizer = function () {
				var width = $this.width();

				if ( ! width ) {
					return;
				}

				$this.css(
					'font-size',
					Math.max(
						Math.min( width / ( compressor * 10 ), parseFloat( settings.maxFontSize ) ),
						parseFloat( settings.minFontSize )
					)
				);
			};

			fitTextData.initialized = true;
			fitTextData.resizer = resizer;
			$this.data( 'fitTextData', fitTextData );

			// Call once to set.
			resizer();

			// Call on resize. Opera debounces their resize by default.
			$( window ).on( 'resize.fittext orientationchange.fittext', resizer );
			$( sowb ).on( 'setup_widgets', resizer );

		});
	};
})(jQuery);

jQuery( function( $ ){

	// Apply FitText to all Widgets Bundle FitText wrappers
	sowb.runFitText = function () {
		$( '.so-widget-fittext-wrapper' ).each( function() {
			var fitTextWrapper = $( this );
			if ( ! fitTextWrapper.is( ':visible' ) ) {
				return fitTextWrapper;
			}

			var compressor = fitTextWrapper.data( 'fitTextCompressor' ) || 0.85;
			var fitTextDone = fitTextWrapper.data( 'fitTextDone' );
			fitTextWrapper.find( 'h1,h2,h3,h4,h5,h6' ).each( function () {
				var $$ = $( this );
				$$.fitText( compressor, {
					minFontSize: '12px',
					maxFontSize: $$.css( 'font-size' )
				} );
			} );

			if ( ! fitTextDone ) {
				fitTextWrapper.data( 'fitTextDone', true );
				fitTextWrapper.trigger( 'fitTextDone' );
			}
		});
	};

	$( window ).on( 'resize', sowb.runFitText );
	$( window ).on( 'load', sowb.runFitText );
	$( sowb ).on( 'setup_widgets', sowb.runFitText );

	if ( document.fonts && document.fonts.ready ) {
		document.fonts.ready.then( sowb.runFitText );
	}

	sowb.runFitText();
} );

window.sowb = sowb;
