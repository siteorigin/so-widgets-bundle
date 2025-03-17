/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	// We remove animations if the user has motion disabled.
	const reduceMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/**
	 * Fix container height to prevent layout shifts
	 *
	 * Calculates and sets optimal container height based on the tallest
	 * carousel item:
	 * - Stores current height to compare with calculated height.
	 * - Efficiently measures all items to find maximum height.
	 * - Adds margin-bottom to final height calculation.
	 * - Only updates DOM if height difference exceeds 1px threshold.
	 * - Prevents unnecessary reflows by checking height before setting.
	 */
	$.fn.fixContainerHeight = function() {
		const $$ = $( this );
		const currentHeight = $$.height();
		const $items = $$.find( '.sow-carousel-item' );

		if ( ! $items.length ) {
			return;
		}

		// Find the tallest item
		let maxHeight = 0;
		$items.each( function() {
			const $item = $( this );
			const height = $item.outerHeight();
			if ( height > maxHeight ) {
				maxHeight = height;
			}
		} );

		const margin_height = parseFloat( $items.first().css( 'margin-bottom' ) );
		const newHeight = maxHeight + margin_height;

		// Only change height if necessary and avoid unnecessary reflows.
		if ( Math.abs( currentHeight - newHeight ) > 1 ) {
			$$.css( 'height', newHeight );
		}
	};

	/**
	 * Navigate to a specific slide in the carousel, and then
	 * (optionally) adapts the height of the carousel.
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

		$$.adaptiveHeight();
	};

	let carouselLoading = true;

	/**
	 * Adjust carousel height to fit tallest visible slide.
	 *
	 * Extends jQuery with an adaptive height function that:
	 * - Measures all visible slides (not just active one like Slick does).
	 * - Adjusts container height to match tallest slide.
	 * - Handles initial loading state differently to avoid navigation jump.
	 * - Adds smooth transition animation for subsequent height changes.
	 * - Accounts for margin-bottom spacing between slides.
	 * - Sets consistent height for all visible slides.
	 */
	$.fn.adaptiveHeight = function() {
		const $$ = $( this );
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

		$slickList = $$.find( '.slick-list' );

		// Check if the carousel has been loaded before.
		if ( $slickList.hasClass( 'sow-loaded' ) ) {

			$slickList.animate( {
				height: maxHeight + marginBottom,
			}, $$.data( 'adaptive_height' ) || 150 );
		} else {
			// Prevent the navigation from moving on load.
			$slickList.css( 'height', maxHeight + marginBottom );

			if ( carouselLoading ) {
				setTimeout( function() {
					$slickList.addClass( 'sow-loaded' )
					carouselLoading = false;
				}, 150 );
			}
		}

		visibleSlides.css( 'height', maxHeight );
	}

	$.fn.carouselDotNavigation = function( e ) {
		const $$ = $( this );
		const $items = $$.find( '.sow-carousel-items' );
		const slidesToScroll = $items.slick( 'slickGetOption', 'slidesToScroll' );
		const numItems = $items.find( '.sow-carousel-item' ).length;
		const numVisibleItems = Math.ceil( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) );
		const lastPosition = numItems - numVisibleItems;

		let targetItem = $( e.currentTarget ).index();

		// Check if navigating to the selected item would result in a blank space.
		if ( targetItem + numVisibleItems >= numItems ) {
			// Blank spacing would occur, let's go to the last possible item
			// make it appear as though we navigated to the selected item.
			$items.navigateToSlide( lastPosition );
			$dots = $$.parent();
			$dots.find( '.slick-active' ).removeClass( 'slick-active' );
			$dots.children().eq( targetItem ).addClass( 'slick-active' );
		} else {
			if ( $$.data( 'widget' ) == 'post' ) {
				// We need to account for an empty item.
				targetItem = Math.ceil( targetItem + 1 * slidesToScroll );
			}
			$items.navigateToSlide( targetItem );
		}

		// Is this a Post Carousel? If so, let's check if we need to load more posts.
		if ( $$.data( 'widget' ) == 'post' ) {
			const complete = numItems >= $$.data( 'item_count' );

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

		triggerResize(
			$items,
			$$.data( 'carousel_settings' )
		);
	};

	/**
	 * Trigger resize adjustments for carousel items.
	 *
	 * Handles adaptive height adjustments and container height fixes.
	 * for carousel items. Only applies height adjustments if:
	 * - Adaptive height is enabled.
	 * - Theme is 'cards'.
	 * - Dynamic navigation is disabled.
	 *
	 * @param {jQuery} $items Carousel items jQuery element.
	 * @param {Object} settings Carousel settings object.
	 */
	const triggerResize = ( $items, settings ) => {
		if ( ! $items.data( 'adaptive_height' ) ) {
			return;
		}

		$items.adaptiveHeight();

		if ( settings.theme !== 'cards' || settings.dynamic_navigation ) {
			return;
		}

		$items.fixContainerHeight();
	}

	sowb.setupCarousel = function () {
		$.fn.setSlideTo = function( slide ) {
			$items = $( this );
			// We need to reset the Slick slide settings to avoid https://github.com/kenwheeler/slick/issues/1006.
			const slidesToShow = $items.slick( 'slickGetOption', 'slidesToShow' );
			const slidesToScroll = $items.slick( 'slickGetOption', 'slidesToScroll' );

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

			$items.on( 'init', function(e, slick) {
				const $wrapper = $(this).closest('.sow-carousel-wrapper');

				setTimeout( function() {
					if (
						carouselSettings.theme === 'cards' &&
						! carouselSettings.dynamic_navigation &&
						! $wrapper.hasClass( 'fixed-navigation' )
					) {
						$wrapper.addClass( 'fixed-navigation' );
						$items.fixContainerHeight();
					}

					$items.adaptiveHeight();

					$wrapper.css( 'opacity', 1 );
				}, 50 );
			} );

			if ( carouselSettings.theme === 'cards' ) {
				// To prevent a sizing issue, we need to check if the Cards Carousel
				// is inside of a Layout Builder, and if so, set the parent container
				// to overflow hidden.
				if ( $$.closest( '.widget_siteorigin-panels-builder' ).length ) {
					const $cell = $$.closest( '.so-panel' ).parent().css( 'overflow', 'hidden' );
				}
			}

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
				$$.find( '.slick-dots li' ).on( 'click touchend', function( e ) {
					$$.carouselDotNavigation( e );
				} );

				// Setup Slick Dot Navigation again when new posts are added.
				$( sowb ).on( 'carousel_posts_added', function( e, carousel) {
					const $$ = $( carousel );
					const $dots = $$.find( '.slick-dots li' );

					if ( $dots ) {
						$dots
							.off( 'click touchend' )
							.on( 'click touchend', function( e ) {
								$$.carouselDotNavigation( e );
							} );
					}

					triggerResize(
						$$.find( '.sow-carousel-items.slick-initialized' ),
						$$.data( 'carousel_settings' )
					);
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

		/**
		 * Updates Slick carousel based on current resolution, and
		 * conditionally triggers a fixed container height refresh if needed.
		 *
		 * @this {jQuery} The current carousel wrapper.
		 */
		const handleCarouselResize = function() {
			const $$ = $( this );
			const $items = $$.find( '.sow-carousel-items.slick-initialized' );

			if ( ! $items.length ) {
				return;
			}

			const responsive = $$.data( 'responsive' );
			const settings = $$.data( 'carousel_settings' );
			const breakpoints = [
				{
					query: `(min-width: ${ responsive.tablet_landscape_breakpoint }px)`,
					show: responsive.desktop_slides_to_show,
					scroll: responsive.desktop_slides_to_scroll
				},
				{
					query: `(min-width: ${ responsive.tablet_portrait_breakpoint }px) and (max-width: ${ responsive.tablet_landscape_breakpoint }px) and (orientation: landscape)`,
					show: responsive.tablet_landscape_slides_to_show,
					scroll: responsive.tablet_landscape_slides_to_scroll
				},
				{
					query: `(min-width: ${ responsive.mobile_breakpoint }px) and (max-width: ${responsive.tablet_portrait_breakpoint}px)`,
					show: responsive.tablet_portrait_slides_to_show,
					scroll: responsive.tablet_portrait_slides_to_scroll
				},
				{
					query: `(max-width: ${ responsive.mobile_breakpoint }px)`,
					show: responsive.mobile_slides_to_show,
					scroll: responsive.mobile_slides_to_scroll
				}
			];

			// Conditionally update Slick settings based on current resolution.
			breakpoints.some( breakpoint => {
				if ( window.matchMedia( breakpoint.query ).matches ) {
					$items.slick( 'slickSetOption', 'slidesToShow', breakpoint.show );
					$items.slick( 'slickSetOption', 'slidesToScroll', breakpoint.scroll );

					return true;
				}

				return false;
			} );

			if ( $items.data( 'adaptive_height' ) ) {
				window.requestAnimationFrame(() => {
					$items.adaptiveHeight();

					if ( settings.theme === 'cards' && ! settings.dynamic_navigation ) {
						$items.fixContainerHeight();
					}
				} );
			}
		};

		let resizeTimeout;
		$( window ).on( 'resize load', () => {
			clearTimeout( resizeTimeout );
			resizeTimeout = setTimeout( () => {
				$( '.sow-carousel-wrapper' ).each( handleCarouselResize );
			}, 100 );

			$( '.sow-carousel-item:first-of-type' ).prop( 'tabindex', 0 );
		} ).trigger( 'resize' );
	};

	sowb.setupCarousel();

	$( sowb ).on( 'setup_widgets', sowb.setupCarousel );
} );

window.sowb = sowb;
