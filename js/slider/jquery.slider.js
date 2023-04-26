/* globals jQuery, sowb */

var sowb = window.sowb || {};

sowb.SiteOriginSlider = function($) {
	return {
		playSlideVideo: function(el) {
			$(el).find('video').each(function(){
				if(typeof this.play !== 'undefined') {
					this.play();
				}
			});
			var embed = $( el ).find( '.sow-slide-video-oembed iframe' );
			if ( embed.length ) {
				// Vimeo
				embed[0].contentWindow.postMessage( '{"method":"play"}', "*" );
				// YouTube
				embed[0].contentWindow.postMessage('{"event":"command","func":"playVideo","args":""}', '*')
			}
		},

		pauseSlideVideo: function(el) {
			$(el).find('video').each(function(){
				if(typeof this.pause !== 'undefined') {
					this.pause();
				}
			});
			var embed = $( el ).find( '.sow-slide-video-oembed iframe' );
			if ( embed.length ) {
				// Vimeo
				embed[0].contentWindow.postMessage( '{"method":"pause"}', "*" );
				// YouTube
				embed[0].contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*')
			}	
		},

		setupActiveSlide: function(slider, newActive, speed){
			// Start by setting up the active sentinel
			var
				sentinel = $(slider).find('.cycle-sentinel'),
				active = $(newActive),
				video = active.find('video.sow-background-element'),
				$unmuteButton = $( slider ).prev();

			if( speed === undefined ) {
				sentinel.css( 'height', active.outerHeight() + 'px' );
			}
			else {
				sentinel.animate( {height: active.outerHeight()}, speed );
			}

			// Hide the unmute button as needed.
			if ( $unmuteButton.length ) {
				// Mute all slide videos.
				$( slider ).find( '.sow-slider-image > video' ).prop( 'muted', true );

				var $activeSlideVideo = active.find( '> video' );
				if ( $activeSlideVideo.length ) {
					$unmuteButton.clearQueue().fadeIn( speed );

					var settings = $unmuteButton.siblings( '.sow-slider-images').data( 'settings' );
					// Unmute video if previously unmuted.
					if ( $activeSlideVideo.hasClass( 'sow-player-unmuted' ) ) {
						$activeSlideVideo.prop( 'muted', false );
						$unmuteButton.addClass( 'sow-player-unmuted' );
						// Let screen readers know how to handle this button.
						$unmuteButton.attr( 'aria-label', settings.muteLoc );
					} else {
						$unmuteButton.removeClass( 'sow-player-unmuted' );
						$unmuteButton.attr( 'aria-label', settings.unmuteLoc );
					}
				} else {
					$unmuteButton.clearQueue().fadeOut( speed );
				}
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
			
			
			if ( $$.data( 'initialized' ) ) {
				return $$;
			}
			
			var $p = $$.siblings('.sow-slider-pagination');
			var $base = $$.closest('.sow-slider-base');
			var $n = $base.find('.sow-slide-nav');
			var $slides = $$.find('.sow-slider-image');
			var settings = $$.data('settings');

			// Add mobile identifer to slider.
			if ( settings.breakpoint ) {
				$( window ).on( 'load resize', function() {
					if ( window.matchMedia( '(max-width: ' + settings.breakpoint + ')' ).matches ) {
						$base.addClass( 'sow-slider-is-mobile' );
					} else {
						$base.removeClass( 'sow-slider-is-mobile' );
					}
				} );
			}

			$slides.each(function( index, el) {
				var $slide = $(el);
				var urlData = $slide.data('url');

				if( urlData !== undefined && urlData.hasOwnProperty( 'url' ) ) {
					$slide.on( 'click', function(event) {

						event.preventDefault();
						var sliderWindow = window.open(
							urlData.url,
							urlData.hasOwnProperty( 'new_window' ) && urlData.new_window ? '_blank' : '_self'
						);
						sliderWindow.opener = null;
					} );
					$slide.find( 'a' ).on( 'click', function ( event ) {
						event.stopPropagation();
					} );
				}
			});

			var setupSlider = function() {

				// If we're inside a fittext wrapper, wait for it to complete, before setting up the slider.
				var fitTextWrapper = $$.closest('.so-widget-fittext-wrapper');
				if ( fitTextWrapper.length > 0 && ! fitTextWrapper.data('fitTextDone') ) {
					fitTextWrapper.on('fitTextDone', function () {
						setupSlider();
					});
					return;
				}

				var isLegacyParallax = $$.find( '.sow-slider-image-parallax[data-siteorigin-parallax]' ).length;
				var waitForParallax = false;
				if ( ! isLegacyParallax ) {
					var slidesWithModernParallax = $$.find( '.sow-slider-image-parallax:not([data-siteorigin-parallax])' );
					if (
						slidesWithModernParallax.length &&
						typeof parallaxStyles != 'undefined' &&
						(
							! parallaxStyles['disable-parallax-mobile'] ||
							! window.matchMedia( '(max-width: ' + parallaxStyles['mobile-breakpoint'] + ')' ).matches
						)
					) {
						waitForParallax = true;
						// Allow slider to be size itself while preventing visual "jump" in modern parallax.
						$base.css( 'opacity', 0 );
					}
				}

				// Show everything for this slider
				$base.show();
				
				var resizeFrames = function () {
					$$.find( '.sow-slider-image' ).each( function () {
						var $i = $( this );
						$i.css( 'height', $i.find( '.sow-slider-image-wrapper' ).outerHeight() + 'px' );
					} );
				};
				// Setup each of the slider frames
				$( window ).on('resize panelsStretchRows', resizeFrames ).trigger( 'resize' );
				$( sowb ).on('setup_widgets', resizeFrames );

				if ( ! isLegacyParallax && waitForParallax ) {
					// Wait for the parallax to finish setting up before
					// setting up the rest of the slider.
					if ( ! slidesWithModernParallax.find( '.simpleParallax' ).length ) {
						setTimeout( setupSlider, 50 );
						return;
					} else {
						// Trigger resize to allow for parallax to work after showing Slider.
						window.dispatchEvent( new Event( 'resize' ) );
						setTimeout( function() {
							$base.css( 'opacity', 1 );
						}, 425 );
					}
				}
				
				$$.trigger( 'slider_setup_before' );

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

							$( window ).trigger( 'resize' );

							setTimeout(function() {
								resizeFrames();
								siteoriginSlider.setupActiveSlide( $$, optionHash.slides[0] );
								// Ensure we keep auto-height functionality, but we don't want the duplicated content.
								$$.find('.cycle-sentinel').empty();
							}, 200);
						}
					})
					.cycle( {
						'slides' : '> .sow-slider-image',
						'speed' : settings.speed,
						'timeout' : settings.timeout,
						'swipe' : settings.swipe,
						'paused' : settings.paused,
						'pauseOnHover' : settings.pause_on_hover,
						'swipe-fx' : 'scrollHorz',
						'log' : false,
					} )	;

				$$ .find('video.sow-background-element').on('loadeddata', function(){
					siteoriginSlider.setupActiveSlide( $$, $$.find( '.cycle-slide-active' ) );
				} );

				// Set up showing and hiding navs
				$p.add($n).hide();
				if( $slides.length > 1 ) {
					if( !$base.hasClass('sow-slider-is-mobile') ) {
						if ( settings.nav_always_show_desktop && window.matchMedia( '(min-width: ' + settings.breakpoint + ')' ).matches ) {
							$p.show();
							$n.show();
						} else {
							var toHide = false;
							$base
								.on( 'mouseenter', function() {
									$p.add( $n ).clearQueue().fadeIn( 150 );
									toHide = false;
								} )
								.on( 'mouseleave', function() {
									toHide = true;
									setTimeout( function() {
										if( toHide ) {
											$p.add( $n ).clearQueue().fadeOut( 150 );
										}
										toHide = false;
									}, 750) ;
								} );
						}
					} else if ( settings.nav_always_show_mobile && window.matchMedia('(max-width: ' + settings.breakpoint + ')').matches) {
						$p.show();
						$n.show();
					}
				}

				// Resize the sentinel when ever the window is resized, or when widgets are being set up.
				var setupActiveSlide = function () {
					siteoriginSlider.setupActiveSlide( $$, $$.find( '.cycle-slide-active' ) );
				};
				$( window ).on( 'resize', setupActiveSlide );
				$( sowb ).on( 'setup_widgets', setupActiveSlide );

				// Setup clicks on the pagination
				$p.find( '> li > a' ).on( 'click', function(e){
					e.preventDefault();
					$$.cycle( 'goto', $(this).data('goto') );
				} );

				// Clicking on the next and previous navigation buttons
				$n.find( '> a' ).on( 'click', function(e){
					e.preventDefault();
					$$.cycle( $(this).data('action') );
				} );

				$base.on( 'keydown',
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

				if ( settings.unmute ) {
					$base.find( '.sow-player-controls-sound' ).on( 'click', function() {
						var $sc = $( this ),
							$activeSlideVideo = $sc.next().find( '.cycle-slide-active > video' );

						$activeSlideVideo.prop( 'muted',
							! $activeSlideVideo.prop( 'muted' )
						);

						if ( ! $activeSlideVideo.prop( 'muted' ) ) {
							// Used for changing the text/icon of mute button.
							$sc.addClass( 'sow-player-unmuted' );
							// State tracking.
							$activeSlideVideo.addClass( 'sow-player-unmuted' );
							// Let screen readers know how to handle this button.
							$sc.attr( 'aria-label', settings.muteLoc );
						} else {
							$sc.removeClass( 'sow-player-unmuted' );
							$activeSlideVideo.removeClass( 'sow-player-muted' );
							$sc.attr( 'aria-label', settings.unmuteLoc );
						}
					} );
				}
			};
			
			$$.trigger( 'slider_setup_after' );

			var images = $$.find( 'img.sow-slider-background-image, img.sow-slider-foreground-image' );
			var imagesLoaded = 0;
			var sliderLoaded = false;

			// Preload all of the slide images, when they're loaded, then display the slider.
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
			
			$$.data( 'initialized', true );
		});
	};
	sowb.setupSliders();

	$( sowb ).on( 'setup_widgets', sowb.setupSliders );
} );

window.sowb = sowb;
