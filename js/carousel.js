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

		// The carousel widget
		$( '.sow-carousel-wrapper' ).each( function () {
			var $$ = $( this ),
				$items = $$.find( '.sow-carousel-items' ),
				responsiveSettings = $$.data( 'responsive' ),
				carouselSettings = $$.data( 'carousel_settings' );

			$items.not( '.slick-initialized' ).slick( {
				arrows: false,
				dots: carouselSettings.dots,
				rows: 0,
				rtl: $$.data( 'dir' ) == 'rtl',
				touchThreshold: 20,
				infinite: ! $$.data( 'ajax-url' )&&  $$.data( 'carousel_settings' ).loop,
				variableWidth: $$.data( 'variable_width' ),
				accessibility: false,
				cssEase: carouselSettings.animation,
				speed: carouselSettings.animation_speed,
				autoplay: carouselSettings.autoplay,
				autoplaySpeed: carouselSettings.autoplaySpeed,
				pauseOnHover: carouselSettings.pauseOnHover,
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

			// click is used rather than Slick's beforeChange or afterChange
			// due to the inability to stop a slide from changing from those events
			$$.parent().parent().find( '.sow-carousel-previous, .sow-carousel-next' ).on( 'click touchend', function( e, refocus ) {
				e.preventDefault();

				var $items = $$.find( '.sow-carousel-items' ),
					numItems = $items.find( '.sow-carousel-item' ).length,
					complete = numItems >= $$.data( 'item_count' ),
					numVisibleItems = Math.ceil( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) ),
					slidesToScroll = $items.slick( 'slickGetOption', 'slidesToScroll' ),
					lastPosition = numItems - numVisibleItems;

				// Post Carousel has a loading indicator so we need to pad the lastPosition.
				if ( $$.data( 'widget' ) == 'post' ) {
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
					}
				}

				// A custom navigation is used due to a Slick limitation that prevents the slide from stopping
				// the slide from changing and wanting to remain consistent with the previous carousel.
				// https://github.com/kenwheeler/slick/pull/2104
				//
				// The Slick Infinite setting has a positioning bug that can result in the first item
				// being hidden so we need to manually handle that
				// https://github.com/kenwheeler/slick/issues/3567
				if ( $( this ).hasClass( 'sow-carousel-next' ) ) {
					// Check if this is the last slide, and we need to loop
					if (
						complete &&
						$items.slick( 'slickCurrentSlide' ) >= lastPosition
					) {
						if ( $$.data( 'carousel_settings' ).loop ) {
							$items.slick( 'slickGoTo', 0 );
						}
					// Check if the number of slides to scroll exceeds lastPosition, go to the last slide.
					} else if ( $items.slick( 'slickCurrentSlide' ) + slidesToScroll > lastPosition ) {
						$items.setSlideTo( lastPosition );
					} else {
						$items.slick( 'slickNext' );
					}
				} else if ( $( this ).hasClass( 'sow-carousel-previous' ) ) {
					if ( $$.data( 'carousel_settings' ).loop && $items.slick( 'slickCurrentSlide' ) == 0 ) {
						$items.slick( 'slickGoTo', lastPosition );
					} else {
						$items.slick( 'slickPrev' );
					}
				}
			} );

			if ( carouselSettings.dots && $$.data( 'variable_width' ) ) {
				// Unbind base Slick Dot Navigation as we use a custom event to prevent blank spaces.
				$$.find( '.slick-dots li' ).off( 'click.slick' );
				$$.find( '.slick-dots li' ).on( 'click touchend', function() {
					var targetItem = $( this ).index(),
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
						$items.slick( 'slickGoTo', targetItem );
					}
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

		$( window ).on( 'resize load', function() {
			$( '.sow-carousel-wrapper' ).each( function() {
				var currentCarousel = $( this ),
					$items = currentCarousel.find( '.sow-carousel-items.slick-initialized' ),
					numVisibleItems = Math.ceil( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) ),
					navigation = currentCarousel.parent().parent().find( '.sow-carousel-navigation' );

				if ( numVisibleItems >= currentCarousel.data( 'item_count' ) ) {
					navigation.hide();
					$items.slick( 'slickSetOption', 'touchMove', false );
					$items.slick( 'slickSetOption', 'draggable', false );
				} else if ( navigation.not( ':visible' ) ) {
					navigation.show();
					$items.slick( 'slickSetOption', 'touchMove', true );
					$items.slick( 'slickSetOption', 'draggable', true );
				}
				// Change Slick Settings on iPad Pro while Landscape
				var responsiveSettings = currentCarousel.data( 'responsive' );
				if ( window.matchMedia( '(min-width: ' + responsiveSettings.tablet_portrait_breakpoint + 'px) and (max-width: ' + responsiveSettings.tablet_landscape_breakpoint + 'px) and (orientation: landscape)' ).matches ) {
					$items.slick( 'slickSetOption', 'slidesToShow', responsiveSettings.tablet_landscape_slides );
					$items.slick( 'slickSetOption', 'slidesToScroll', responsiveSettings.tablet_landscape_slides );
				}

			} );

			$( '.sow-carousel-item:first-of-type' ).prop( 'tabindex', 0 );
		} );
	};

	sowb.setupCarousel();

	$( sowb ).on( 'setup_widgets', sowb.setupCarousel );
} );

window.sowb = sowb;
