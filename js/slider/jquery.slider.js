/**
 * SiteOrigin Slider Javascript.
 *
 * Copyright 2014, SiteOrigin
 * Released under GPL 2.0 - see http://www.gnu.org/licenses/gpl-2.0.html
 */

var sowb = window.sowb || {};

sowb.SiteOriginSlider = function($) {
	return {
		playSlideVideo: function(el) {
			$(el).find('video').each(function(){
				if(typeof this.play !== 'undefined') {
					this.play();
				}
			});
		},

		pauseSlideVideo: function(el) {
			$(el).find('video').each(function(){
				if(typeof this.pause !== 'undefined') {
					this.pause();
				}
			});
		},

		setupActiveSlide: function(slider, newActive, speed){
			// Start by setting up the active sentinel
			var
				sentinel = $(slider).find('.cycle-sentinel'),
				active = $(newActive),
				video = active.find('video.sow-background-element');

			if( speed == undefined ) {
				sentinel.css( 'height', active.outerHeight() );
			}
			else {
				sentinel.animate( {height: active.outerHeight()}, speed );
			}

			if( video.length ) {

				// Resize the video so it fits in the current slide
				var
					slideRatio = active.outerWidth() / active.outerHeight(),
					videoRatio = video.outerWidth() / video.outerHeight();

				if( slideRatio > videoRatio ) {
					video.css( {
						'width' : '100%',
						'height' : 'auto'
					} );
				}
				else {
					video.css( {
						'width' : 'auto',
						'height' : '100%'
					} );
				}

				video.css( {
					'margin-left' : -Math.ceil(video.width()/2),
					'margin-top' : -Math.ceil(video.height()/2)
				} );
			}
		},
	};
};


jQuery( function($){
	sowb.setupSliders = sowb.setupSlider = function() {
		var siteoriginSlider = new sowb.SiteOriginSlider($);

		$('.sow-slider-images').each(function(){
			var $$ = $(this);
			var $p = $$.siblings('.sow-slider-pagination');
			var $base = $$.closest('.sow-slider-base');
			var $n = $base.find('.sow-slide-nav');
			var $slides = $$.find('.sow-slider-image');
			var settings = $$.data('settings');

			$slides.each(function( index, el) {
				var $slide = $(el);
				var urlData = $slide.data('url');

				$slide.click(function(event) {

					if( urlData !== undefined ) {
						var $t = $(event.target);
						// If this isn't a link, we'll use the URL of the frame
						if( $t.prop("tagName") !== 'A' ) {
							event.preventDefault();
							window.open(urlData.url, urlData.new_window ? '_blank' : '_self');
						}
					}
				} );
			});

			var setupSlider = function(){
				// Show everything for this slider
				$base.show();

				// Setup each of the slider frames
				$$.find('.sow-slider-image').each( function(){
					var $i = $(this);

					$(window)
						.on('resize panelsStretchRows', function(){
							$i.css( 'height', $i.find('.sow-slider-image-wrapper').outerHeight() );
						})
						.resize();
				} );

				// Set up the Cycle with videos
				$$
					.on({
						'cycle-after' : function(event, optionHash, outgoingSlideEl, incomingSlideEl, forwardFlag){
							var $$ = $(this);
							siteoriginSlider.playSlideVideo(incomingSlideEl);
							siteoriginSlider.setupActiveSlide( $$, incomingSlideEl );
							$( incomingSlideEl ).trigger('sowSlideCycleAfter');
						},

						'cycle-before' : function(event, optionHash, outgoingSlideEl, incomingSlideEl, forwardFlag) {
							var $$ = $(this);
							$p.find('> li').removeClass('sow-active').eq(optionHash.slideNum-1).addClass('sow-active');
							siteoriginSlider.pauseSlideVideo(outgoingSlideEl);
							siteoriginSlider.setupActiveSlide($$, incomingSlideEl, optionHash.speed);
							$( incomingSlideEl ).trigger('sowSlideCycleBefore');
						},

						'cycle-initialized' : function(event, optionHash){
							siteoriginSlider.playSlideVideo( $(this).find('.cycle-slide-active') );
							siteoriginSlider.setupActiveSlide( $$, optionHash.slides[0] );

							$p.find('>li').removeClass('sow-active').eq(0).addClass('sow-active');
							$( this ).find('.cycle-slide-active').trigger( 'sowSlideInitial' );

							if(optionHash.slideCount <= 1) {
								// Special case when there is only one slide
								$p.hide();
								$n.hide();
							}

							$(window).resize();
						}
					})
					.cycle( {
						'slides' : '> .sow-slider-image',
						'speed' : settings.speed,
						'timeout' : settings.timeout,
						'swipe' : settings.swipe,
						'swipe-fx' : 'scrollHorz'
					} )	;

				$$ .find('video.sow-background-element').on('loadeddata', function(){
					siteoriginSlider.setupActiveSlide( $$, $$.find( '.cycle-slide-active' ) );
				} );

				// Set up showing and hiding navs
				$p.add($n).hide();
				if( !$base.hasClass('sow-slider-is-mobile') && $slides.length > 1 ) {

					var toHide = false;
					$base
						.mouseenter(function(){
							$p.add($n).clearQueue().fadeIn(150);
							toHide = false;
						})
						.mouseleave(function(){
							toHide = true;
							setTimeout(function(){
								if( toHide ) {
									$p.add($n).clearQueue().fadeOut(150);
								}
								toHide = false;
							}, 750);
						});
				}

				// Resize the sentinel when ever the window is resized
				$( window ).resize( function(){
					siteoriginSlider.setupActiveSlide( $$, $$.find( '.cycle-slide-active' ) );
				} );

				// Setup clicks on the pagination
				$p.find( '> li > a' ).click( function(e){
					e.preventDefault();
					$$.cycle( 'goto', $(this).data('goto') );
				} );

				// Clicking on the next and previous navigation buttons
				$n.find( '> a' ).click( function(e){
					e.preventDefault();
					$$.cycle( $(this).data('action') );
				} );

				$base.keydown(
					function(event) {
						if(event.which === 37) {
							//left
							$$.cycle('prev');
						}
						else if (event.which === 39) {
							//right
							$$.cycle('next');
						}
					}
				);
			};

			var images = $$.find('img');
			var imagesLoaded = 0;
			var sliderLoaded = false;

			// Preload all the images, when they're loaded, then display the slider
			images.each( function(){
				var $i = $(this);
				if( this.complete ) {
					imagesLoaded++;
				}
				else {
					$(this).one('load', function(){
						imagesLoaded++;

						if(imagesLoaded === images.length && !sliderLoaded) {
							setupSlider();
							sliderLoaded = true;
						}
					})
					// Reset src attribute to force 'load' event for cached images in IE9 and IE10.
						.attr('src', $(this).attr('src'));
				}

				if(imagesLoaded === images.length && !sliderLoaded) {
					setupSlider();
					sliderLoaded = true;
				}
			} );

			if(images.length === 0) {
				setupSlider();
			}
		});
	};
	sowb.setupSliders();

	$( sowb ).on( 'setup_widgets', sowb.setupSliders );
} );
