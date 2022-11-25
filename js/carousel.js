/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {

	sowb.setupCarousel = function () {
		$.fn.setSlideTo = function( slide ) {
			$item = $( this );
			// We need to reset the Slick slide settings to avoid https://github.com/kenwheeler/slick/issues/1006.
			var slidesToShow = $item.slick( 'slickGetOption', 'slidesToShow' );
			var slidesToScroll = $item.slick( 'slickGetOption', 'slidesToScroll' );

			$item.slick( 'slickSetOption', 'slidesToShow', 1 );
			$item.slick( 'slickSetOption', 'slidesToScroll', 1 );
			$item.slick( 'slickGoTo', slide );
			$item.slick( 'slickSetOption', 'slidesToShow', slidesToShow );
			$item.slick( 'slickSetOption', 'slidesToScroll', slidesToScroll );
		};

		$( '.sow-carousel-wrapper' ).on( 'init', function( e, slick ) {
			$( this ).css( 'opacity', 1 );
		} );

		// The carousel widget
		$( '.sow-carousel-wrapper' ).each( function () {
			var $$ = $( this ),
				$items = $$.find( '.sow-carousel-items' ),
				responsiveSettings = $$.data( 'responsive' ),
				carouselSettings = $$.data( 'carousel_settings' );

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
							carouselSettings.autoplay_continuous_scroll &&
							carouselSettings.autoplay
						)
					),
				variableWidth: $$.data( 'variable_width' ),
				accessibility: false,
				adaptiveHeight: carouselSettings.adaptive_height,
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
			// autoplay to account for the (sometimes) non-standard nature of our
			// navigation that Slick has trouble accounting for.
			if ( carouselSettings.autoplay ) {
				var interrupted = false;
				var autoplayNav = $$.parent().parent().find( '.sow-carousel-' + ( $$.data( 'dir' ) == 'ltr' ? 'next' : 'prev' ) );
				// Check if this is a Block Editor preview, and if it is, don't autoplay.
				if ( ! $( 'body' ).hasClass( 'block-editor-page' ) ) {
					setInterval( function() {
						if ( ! interrupted ) {
							autoplayNav.trigger( 'click' );
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
			}

			// click is used rather than Slick's beforeChange or afterChange
			// due to the inability to stop a slide from changing from those events
			$$.parent().parent().find( '.sow-carousel-previous, .sow-carousel-next' ).on( 'click touchend', function( e, refocus ) {
				e.preventDefault();

				var $items = $$.find( '.sow-carousel-items' ),
					numItems = $items.find( '.sow-carousel-item' ).length,
					complete = numItems >= $$.data( 'item_count' ),
					numVisibleItems = Math.ceil( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) ),
					numVisibleItemsFloor = Math.floor( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) ),
					slidesToScroll = $items.slick( 'slickGetOption', 'slidesToScroll' ),
					lastPosition = numItems - numVisibleItems,
					loading = false;

				// Post Carousel has a loading indicator so we need to pad the lastPosition.
				if (
					$$.data( 'widget' ) == 'post' &&
					( 
						$$.data( 'carousel_settings' ).theme != 'undefined' && 
						complete
					)
				) {
					lastPosition++;
				}

				// Check if all items are displayed
				if ( ! complete ) {
					// For Ajax Carousels, check if we need to fetch the next batch of items.
					if ( 
						$items.slick( 'slickCurrentSlide' ) + numVisibleItems >= numItems - 1 ||
						$items.slick( 'slickCurrentSlide' ) + slidesToScroll > lastPosition
					) {
						$( sowb ).trigger( 'carousel_load_new_items', [ $$, $items, refocus ] );
						loading = true;
					}
				}

				// A custom navigation is used due to a Slick limitation that prevents the slide from stopping
				// the slide from changing and wanting to remain consistent with the previous carousel.
				// https://github.com/kenwheeler/slick/pull/2104
				//
				// The Slick Infinite setting has a positioning bug that can result in the first item
				// being hidden so we need to manually handle that
				// https://github.com/kenwheeler/slick/issues/3567
				if ( $( this ).hasClass( 'sow-carousel-next' ) && ! loading ) {
					// Check if this is the last slide, and we need to loop
					if (
						complete &&
						$items.slick( 'slickCurrentSlide' ) >= lastPosition
					) {
						if ( $$.data( 'carousel_settings' ).loop ) {
							$items.slick( 'slickGoTo', 0 );
						}
					// If slidesToScroll is higher than the the number of visible items, go to the last item.
					} else if ( $$.data( 'widget' ) == 'post' && $$.data( 'carousel_settings' ).theme == 'undefined' && slidesToScroll >= numVisibleItemsFloor ) {
						// There's more slides than items, update Slick settings to allow for scrolling of partially visible items.
						$items.slick( 'slickSetOption', 'slidesToShow', numVisibleItemsFloor );
						$items.slick( 'slickSetOption', 'slidesToScroll', numVisibleItemsFloor );
						$items.slick( 'slickNext' );
					// Check if the number of slides to scroll exceeds lastPosition, go to the last slide, or
					} else if ( $items.slick( 'slickCurrentSlide' ) + slidesToScroll > lastPosition ) {
						$items.setSlideTo( lastPosition );
					} else {
						$items.slick( 'slickNext' );
					}
				} else if ( $( this ).hasClass( 'sow-carousel-previous' ) ) {
					if ( $$.data( 'carousel_settings' ).loop && $items.slick( 'slickCurrentSlide' ) == 0 ) {
						$items.slick( 'slickGoTo', lastPosition );
					} else if ( $$.data( 'widget' ) == 'post' && $items.slick( 'slickCurrentSlide' ) <= slidesToScroll ) {
						$items.slick( 'slickGoTo', 0 );
					} else {
						$items.slick( 'slickPrev' );
					}
				}

				// Post Carousel update dot navigation active item.
				if ( carouselSettings.dots && $$.data( 'widget' ) == 'post' ) {
					$$.find( 'li.slick-active' ).removeClass( 'slick-active' );
					$$.find( '.slick-dots li' ).eq( Math.ceil( $$.find( '.sow-carousel-items' ).slick( 'slickCurrentSlide' ) / slidesToScroll ) ).addClass( 'slick-active' );
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
						$items.slick( 'slickGoTo', lastPosition );
						$dots = $( this ).parent();
						$dots.find( '.slick-active' ).removeClass( 'slick-active' );
						$dots.children().eq( targetItem ).addClass( 'slick-active' );

					} else {
						if ( $$.data( 'widget' ) == 'post' ) {
							// We need to account for an empty item.
							targetItem = Math.ceil( $( this ).index() * slidesToScroll );
						}
						$items.slick( 'slickGoTo', targetItem );
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

			var $wrapper =  $( this ).parents( '.sow-carousel-wrapper' ),
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

			$items.slick( 'slickGoTo', itemIndex, true );
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
