/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	// We remove animations if the user has motion disabled.
	const reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	$.fn.fixContainerHeight = function() {
		const $$ = $( this );
		const $largestItem = $$.find( '.sow-carousel-item' ).sort( function( a, b ) {
			return $( b ).outerHeight() - $( a ).outerHeight();
		} )[0];

		const $largestItemEl = $( $largestItem );

		$$.css( 'height',
			$largestItemEl.outerHeight() +
			parseFloat( $largestItemEl.css( 'margin-bottom' ) )
		);
	};

	$( '.sow-carousel-wrapper' ).on( 'init', function( e, slick ) {
		const $$ = $( this );
		$$.css( 'opacity', 1 );

		const carousel_settings = $$.data( 'carousel_settings' );
		if (
			carousel_settings.dynamic_navigation ||
			carousel_settings.theme !== 'cards' ||
			$$.hasClass( 'fixed-navigation' )
		) {
			return;
		}

		const $items = $$.find( '.sow-carousel-items' );

		$$.addClass( 'fixed-navigation' );
		$items.fixContainerHeight();

		$( window ).on( 'resize', () => {
			$items.fixContainerHeight();
		} );
	} );

	sowb.setupCarousel = function () {
		$.fn.setSlideTo = function( slide ) {
			$items = $( this );
			// We need to reset the Slick slide settings to avoid https://github.com/kenwheeler/slick/issues/1006.
			const slidesToShow = $item.slick( 'slickGetOption', 'slidesToShow' );
			const slidesToScroll = $item.slick( 'slickGetOption', 'slidesToScroll' );

			$items.slick( 'slickSetOption', 'slidesToShow', 1 );
			$items.slick( 'slickSetOption', 'slidesToScroll', 1 );
			$items.navigateToSlide( slide );
			$items.slick( 'slickSetOption', 'slidesToShow', slidesToShow );
			$items.slick( 'slickSetOption', 'slidesToScroll', slidesToScroll );
		};

		// The carousel widget
		$( '.sow-carousel-wrapper' ).each( function () {
			var $$ = $( this ),
				$items = $$.find( '.sow-carousel-items' ),
				responsiveSettings = $$.data( 'responsive' ),
				carouselSettings = $$.data( 'carousel_settings' );

			// Remove animations if needed.
			if ( reduceMotion ) {
				carouselSettings.animation_speed = 0;
			}

			// Store reference to Adaptive height and speed for later use.
			$items.data( 'adaptive_height', carouselSettings.adaptive_height );
			$items.data( 'animation_speed', carouselSettings.animation_speed );

			const isBlockEditor = $( 'body' ).hasClass( 'block-editor-page' );
			const isContinuous = carouselSettings.autoplay === 'continuous';

			$items.not( '.slick-initialized' ).slick( {
				arrows: false,
				dots: carouselSettings.dots,
				appendDots: carouselSettings.appendDots ? $$.find( '.sow-carousel-nav' ) : $$,
				rows: 0,
				rtl: $$.data( 'dir' ) == 'rtl',
				touchThreshold: 20,
				infinite:
					carouselSettings.loop &&
					(
						! $$.data( 'ajax-url' ) ||
						(
							$$.data( 'ajax-url' ) &&
							isContinuous
						)
					),
				variableWidth: $$.data( 'variable_width' ),
				accessibility: false,
				cssEase: carouselSettings.animation,
				speed: carouselSettings.animation_speed,
				slidesToScroll: responsiveSettings.desktop_slides_to_scroll,
				slidesToShow: typeof responsiveSettings.desktop_slides_to_show == 'undefined'
					? responsiveSettings.desktop_slides_to_scroll
					: responsiveSettings.desktop_slides_to_show,
				responsive: [
					{
						breakpoint: responsiveSettings.tablet_portrait_breakpoint,
						settings: {
							slidesToScroll: responsiveSettings.tablet_portrait_slides_to_scroll,
							slidesToShow: typeof responsiveSettings.tablet_portrait_slides_to_show == 'undefined'
								? responsiveSettings.tablet_portrait_slides_to_scroll
								: responsiveSettings.tablet_portrait_slides_to_show,
						}
					},
					{
						breakpoint: responsiveSettings.mobile_breakpoint,
						settings: {
							slidesToScroll: responsiveSettings.mobile_slides_to_scroll,
							slidesToShow: typeof responsiveSettings.mobile_slides_to_show == 'undefined'
								? responsiveSettings.mobile_slides_to_scroll
								: responsiveSettings.mobile_slides_to_show,
						}
					},
				],
				autoplay: ! isBlockEditor && isContinuous,
				autoplaySpeed: 0,
			} );

			// Clear the pre-fill width if one is set.
			if ( carouselSettings.item_overflow ) {
				$items.css( 'width', '' );
				$items.css( 'opacity', '' );
			}

			// Trigger navigation click on swipe
			$items.on( 'swipe', function( e, slick, direction ) {
				$$.parent().parent().find( '.sow-carousel-' + ( direction == 'left' ? 'next' : 'prev' ) ).trigger( 'touchend' );
			} );

			// Set up Autoplay. We use a custom autoplay rather than the Slick
			// autoplay to account for the (sometimes) non-standard nature
			// of our navigation that Slick has trouble accounting for.
			if (
				carouselSettings.autoplay &&
				carouselSettings.autoplay !== 'off'
			) {
				var interrupted = false;
				// Check if this is a Block Editor preview or continuous autoplay is enabled.
				// If either are true, don't setup (this) autoplay.
				if (
					isBlockEditor ||
					isContinuous
				) {
					return;
				}

				setInterval( function() {
					if ( ! interrupted ) {
						handleCarouselNavigation( true, false );
					}
				}, carouselSettings.autoplaySpeed );

				if ( carouselSettings.pauseOnHover ) {
					$items.on('mouseenter.slick', function() {
							interrupted = true;
					} );
					$items.on( 'mouseleave.slick', function() {
							interrupted = false;
					} );
				}
			}

			/**
			 * Navigate to a specific slide in the carousel, and then
			 * (optionally) adapts the height of the carousel.
			 *
			 * This function navigates to a specific slide in the carousel.
			 * If adaptive height is enabled, by calling adaptiveHeight().
			 *
			 * @param {number|string|null} newSlide - The slide to navigate to.
			 * Can be a slide index (int), command (string), or null.
			 *
			 * @returns {void}
			 */
			$.fn.navigateToSlide = function( newSlide ) {
				const $$ = $( this );

				if ( newSlide !== null ) {
					if ( typeof newSlide === 'string' ) {
						$$.slick( newSlide );
					} else {
						$$.slick( 'slickGoTo', newSlide - 1 );
					}
				}

				adaptiveHeight( $$ );
			};

			/**
			 * Adjust the height of the carousel to fit the tallest
			 * visible slide.
			 *
			 * This function adjusts the height of the carousel to fit
			 * the tallest visible slide, including any bottom margin.
			 * It is used as a custom solution for adaptive height since
			 * Slick's adaptive height only factors in the "active" item,
			 * not all visible items.
			 *
			 * @returns {void}
			 */
			const adaptiveHeight = function( $$ ) {
				if ( ! $$.data( 'adaptive_height' ) ) {
					return;
				}

				// We're using a custom solution for adaptive height as Slick's
				// adaptive height only factors in the "active" item, not all
				// visible items.
				const visibleSlides = $$.find( '.slick-active' );
				visibleSlides.css( 'height', 'fit-content' );

				let maxHeight = 0;
				visibleSlides.each( function() {
					const $item = $( this );
					const slideHeight = $item.outerHeight();

					if ( slideHeight > maxHeight ) {
						maxHeight = slideHeight;
					}
				} );

				// It's possible that the slides will have a margin-bottom set,
				// and we need to account for that in the sizing.
				const marginBottom = parseFloat( visibleSlides.first().css( 'margin-bottom' ) );

				$$.find( '.slick-list' ).animate( {
					height: maxHeight + marginBottom,
				}, $$.data( 'adaptive_height' ) );

				visibleSlides.css( 'height', maxHeight );
			}

			// Trigger adaptive height resize during setup.
			$items.on( 'init', () => {
				adaptiveHeight( $items );
			} ).trigger( 'init' );

			var handleCarouselNavigation = function( nextSlide, refocus ) {
				const $items = $$.find( '.sow-carousel-items' );
				const navigationContainer = $$.parent().parent();

				const currentSlide = $items.slick( 'slickCurrentSlide' );
				const numItems = $items.find( '.sow-carousel-item' ).length;
				const complete = numItems >= $$.data( 'item_count' );
				const numVisibleItems = Math.floor( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) );

				let slidesToScroll = $items.slick( 'slickGetOption', 'slidesToScroll' );
				let lastPosition = numItems - numVisibleItems;
				let loading = $$.data( 'fetching' );
				let preloaded = $$.data( 'preloaded' );
				let loadMorePosts = false;

				if (
					! complete &&
					! preloaded &&
					(
						currentSlide + numVisibleItems >= numItems - 1 ||
						currentSlide + ( slidesToScroll * 2 ) > lastPosition
					)
				) {
					// For Ajax Carousels, check if we need to fetch the next batch of items.
					loadMorePosts = true;
				}

				// Enable/disable navigation buttons as needed.
				if ( ! $$.data( 'carousel_settings' ).loop ) {
					const direction = $$.data( 'dir' ) == 'ltr' ? 'previous' : 'next';

					if ( currentSlide == 0 ) {
						navigationContainer.find( `.sow-carousel-${ direction }` )
							.removeClass( 'sow-carousel-disabled' )
							.removeAttr( 'aria-disabled' );
					} else if (
						! nextSlide &&
						currentSlide - slidesToScroll == 0
					) {
						navigationContainer.find( `.sow-carousel-${ direction }` )
							.addClass( 'sow-carousel-disabled' )
							.attr( 'aria-disabled', 'true' );
					}
				}

				// A custom navigation is used due to a Slick limitation that prevents the slide from stopping
				// the slide from changing and wanting to remain consistent with the previous carousel.
				// https://github.com/kenwheeler/slick/pull/2104
				//
				// The Slick Infinite setting has a positioning bug that can result in the first item
				// being hidden so we need to manually handle that
				// https://github.com/kenwheeler/slick/issues/3567
				if ( nextSlide ) {
					// If we're already loading posts, don't do anything.
					if ( loading && ! preloaded ) {
						return;
					}

					// Check if this is the last slide, and we need to loop
					if (
						complete &&
						currentSlide >= lastPosition
					) {
						if ( $$.data( 'carousel_settings' ).loop ) {
							$items.navigateToSlide( 0 );
						}
					// If slidesToScroll is higher than the the number of visible items, go to the last item.
					} else if (
						$$.data( 'widget' ) == 'post' &&
						$$.data( 'carousel_settings' ).theme == 'undefined' &&
						slidesToScroll >= numVisibleItems
					) {
						// There's more slides than items, update Slick settings to allow for scrolling of partially visible items.
						$items.slick( 'slickSetOption', 'slidesToShow', numVisibleItems );
						$items.slick( 'slickSetOption', 'slidesToScroll', numVisibleItems );
						$items.navigateToSlide( 'slickNext' );
					// Check if the number of slides to scroll exceeds lastPosition, go to the last slide, or
					} else if ( currentSlide + slidesToScroll > lastPosition ) {
						$items.setSlideTo( lastPosition );
						$items.navigateToSlide( null );
					// Is the current slide a non-standard slideToScroll?
					} else if ( currentSlide % slidesToScroll !== 0 ) {
						// We need to increase the slidesToScroll temporarily to
						// bring it back line with the slidesToScroll.
						$items.slick( 'slickSetOption', 'slidesToScroll', slidesToScroll + 1 );
						$items.navigateToSlide( 'slickNext' );
						$items.slick( 'slickSetOption', 'slidesToScroll', slidesToScroll );
					} else {
						$items.navigateToSlide( 'slickNext' );
					}

					// Have we just scrolled to the last slide, and is looping disabled?.
					// If so, disable the next button.
					if (
						currentSlide == lastPosition &&
						! $$.data( 'carousel_settings' ).loop
					) {
						navigationContainer.find( '.sow-carousel-next' )
							.addClass( 'sow-carousel-disabled' )
							.attr( 'aria-disabled', 'true' );
					}
				} else {
					let slickPrev = false;
					if ( $$.data( 'widget' ) === 'post' ) {
						if (
							$$.data( 'carousel_settings' ).loop &&
							currentSlide === 0
						) {
							// Determine lastPosition based on the 'complete' flag
							lastPosition = complete ? numItems : lastPosition;
							loadMorePosts = ! complete;
							$items.navigateToSlide( lastPosition );
						} else if ( currentSlide <= slidesToScroll ) {
							$items.navigateToSlide( 0 );
						} else {
							slickPrev = true;
						}
					} else {
						slickPrev = true;
					}

					if ( slickPrev ) {
						$items.navigateToSlide( 'slickPrev' );

						const next = navigationContainer.find( '.sow-carousel-next' );
						if ( next.hasClass( 'sow-carousel-disabled' ) ) {
							next.removeClass( 'sow-carousel-disabled' )
								.removeAttr( 'aria-disabled' );
						}
					}
				}

				// Post Carousel update dot navigation active item.
				if ( carouselSettings.dots && $$.data( 'widget' ) == 'post' ) {
					$$.find( 'li.slick-active' ).removeClass( 'slick-active' );
					$$.find( '.slick-dots li' ).eq( Math.ceil( $$.find( '.sow-carousel-items' ).slick( 'slickCurrentSlide' ) / slidesToScroll ) ).addClass( 'slick-active' );
				}

				// Do we need to load more posts?
				if ( loadMorePosts ) {
					$( sowb ).trigger( 'carousel_load_new_items', [ $$, $items, refocus ] );
				}
			}

			if ( ! carouselSettings.loop && $items.slick( 'slickCurrentSlide' ) == 0 ) {
				const direction = $$.data( 'dir' ) == 'ltr' ? 'previous' : 'next';
				$$.parent().parent().find( `.sow-carousel-${ direction }` )
					.addClass( 'sow-carousel-disabled' )
					.attr( 'aria-disabled', 'true' );
			}

			// Click is used instead of Slick's beforeChange or afterChange events
			// due to the inability to stop a slide from changing.
			$$.parent().parent().find( '.sow-carousel-previous, .sow-carousel-next' ).on( 'click touchend', function( e, refocus ) {
				e.preventDefault();

				if ( ! $( this ).hasClass( 'sow-carousel-disabled' ) ) {
					handleCarouselNavigation(
						$( this ).hasClass( 'sow-carousel-next' ),
						refocus
					)
				}
			} );

			if ( carouselSettings.dots && ( $$.data( 'variable_width' ) || $$.data( 'carousel_settings' ).theme ) ) {
				// Unbind base Slick Dot Navigation as we use a custom event to prevent blank spaces.
				$$.find( '.slick-dots li' ).off( 'click.slick' );
				var carouselDotNavigation = function() {
					$items = $$.find( '.sow-carousel-items' );
					var targetItem = $( this ).index(),
						slidesToScroll = $items.slick( 'slickGetOption', 'slidesToScroll' ),
						numItems = $items.find( '.sow-carousel-item' ).length,
						numVisibleItems = Math.ceil( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) ),
						lastPosition = numItems - numVisibleItems;

					// Check if navigating to the selected item would result in a blank space.
					if ( targetItem + numVisibleItems >= numItems ) {
						// Blank spacing would occur, let's go to the last possible item
						// make it appear as though we navigated to the selected item.
						$items.navigateToSlide( lastPosition );
						$dots = $( this ).parent();
						$dots.find( '.slick-active' ).removeClass( 'slick-active' );
						$dots.children().eq( targetItem ).addClass( 'slick-active' );

					} else {
						if ( $$.data( 'widget' ) == 'post' ) {
							// We need to account for an empty item.
							targetItem = Math.ceil( $( this ).index() * slidesToScroll );
						}
						$items.navigateToSlide( targetItem );
					}

					// Is this a Post Carousel? If so, let's check if we need to load more posts.
					if ( $$.data( 'widget' ) == 'post' ) {
						var complete = numItems >= $$.data( 'item_count' );

						// Check if all items are displayed
						if ( ! complete ) {
							if (
								$items.slick( 'slickCurrentSlide' ) + numVisibleItems >= numItems - 1 ||
								$items.slick( 'slickCurrentSlide' ) + slidesToScroll > lastPosition
							) {
								$( sowb ).trigger( 'carousel_load_new_items', [ $$, $items, false ] );
							}
						}
					}
				};
				$$.find( '.slick-dots li' ).on( 'click touchend', carouselDotNavigation );
				// Setup Slick Dot Navigation again when new posts are added.
				$( sowb ).on( 'carousel_posts_added', function() {
					$$.find( '.slick-dots li' ).on( 'click touchend', carouselDotNavigation );
				} );
			}
		} );

		$( sowb ).trigger( 'carousel_setup' );

		// Keyboard Navigation of carousel navigation.
		$( document ).on( 'keydown', '.sow-carousel-navigation a', function( e ) {
			if ( e.keyCode != 13 && e.keyCode != 32 ) {
				return;
			}
			e.preventDefault();
			$( this ).trigger( 'click' );
		} );

		// Keyboard Navigation of carousel items.
		$( document ).on( 'keyup', '.sow-carousel-item', function( e ) {
			// Was enter pressed?
			if ( e.keyCode == 13 ) {
				$( this ).find( 'h3 a' )[0].click();
			}

			// Ensure left/right key was pressed
			if ( e.keyCode != 37 && e.keyCode != 39 ) {
				return;
			}

			var $wrapper = $( this ).parents( '.sow-carousel-wrapper' ),
				$items = $wrapper.find( '.sow-carousel-items' ),
				numItems = $items.find( '.sow-carousel-item' ).length,
				itemIndex = $( this ).data( 'slick-index' ),
				lastPosition = numItems - ( numItems === $wrapper.data( 'item_count' ) ? 0 : 1 );

			if ( e.keyCode == 37 ) {
				itemIndex--;
				if ( itemIndex < 0 ) {
					itemIndex = lastPosition;
				}
			} else if ( e.keyCode == 39 ) {
				itemIndex++;
				if ( itemIndex >= lastPosition ) {
					if ( $wrapper.data( 'fetching' ) ) {
						return; // Currently loading new items.
					}

					$wrapper.parent().find( '.sow-carousel-next' ).trigger( 'click', true );
				}
			}

			$items.navigateToSlide( itemIndex );

			$wrapper.find( '.sow-carousel-item' ).prop( 'tabindex', -1 );
			$wrapper.find( '.sow-carousel-item[data-slick-index="' + itemIndex + '"]' )
				.trigger( 'focus' )
				.prop( 'tabindex', 0 );
		} );

		var carousel_resizer = function() {
			$( '.sow-carousel-wrapper' ).each( function() {
				var currentCarousel = $( this ),
					$items = currentCarousel.find( '.sow-carousel-items.slick-initialized' );

				// Change Slick Settings on iPad Pro while Landscape
				var responsiveSettings = currentCarousel.data( 'responsive' );
				if ( window.matchMedia( '(min-width: ' + responsiveSettings.tablet_portrait_breakpoint + 'px) and (max-width: ' + responsiveSettings.tablet_landscape_breakpoint + 'px) and (orientation: landscape)' ).matches ) {
					$items.slick( 'slickSetOption', 'slidesToShow', responsiveSettings.tablet_landscape_slides_to_show );
					$items.slick( 'slickSetOption', 'slidesToScroll', responsiveSettings.tablet_landscape_slides_to_scroll );
				}

			} );

			$( '.sow-carousel-item:first-of-type' ).prop( 'tabindex', 0 );
		};

		carousel_resizer();
		$( window ).on( 'resize load', carousel_resizer );
	};

	sowb.setupCarousel();

	$( sowb ).on( 'setup_widgets', sowb.setupCarousel );
} );

window.sowb = sowb;
