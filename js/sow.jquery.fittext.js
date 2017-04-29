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

            // Resizer() resizes items based on the object width divided by the compressor * 10
            var resizer = function () {
                $this.css('font-size', Math.max(Math.min($this.width() / (compressor * 10), parseFloat(settings.maxFontSize)), parseFloat(settings.minFontSize)));
            };

            // Call once to set.
            resizer();

            // Call on resize. Opera debounces their resize by default.
            $(window).on('resize.fittext orientationchange.fittext', resizer);

        });
    };
})(jQuery);

jQuery( function( $ ){

    // Apply FitText to all Widgets Bundle FitText wrappers
	sowb.runFitText = function () {
		var fitTextWrapper = $( '.so-widget-fittext-wrapper' );

		fitTextWrapper.find( 'h1,h2,h3,h4,h5,h6' ).each( function () {
			var $$ = $( this );
			$$.fitText( 0.85, {
				maxFontSize: $$.css( 'font-size' )
			} );
		} );
		fitTextWrapper.trigger( 'fitTextDone' );
	};

	$( sowb ).on( 'setup_widgets', sowb.runFitText );

	sowb.runFitText();
} );

window.sowb = sowb;
