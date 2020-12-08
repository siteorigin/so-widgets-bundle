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
				responsiveSettings = $$.data( 'responsive' );

			$items.not( '.slick-initialized' ).slick( {
				arrows: false,
				infinite: false,
				rows: 0,
				rtl: $$.data( 'dir' ) == 'rtl',
				touchThreshold: 20,
				variableWidth: true,
				accessibility: false,
				slidesToScroll: responsiveSettings.desktop_slides,
				slidesToShow: responsiveSettings.desktop_slides,
				responsive: [
					{
						breakpoint: responsiveSettings.tablet_portrait_breakpoint,
						settings: {
							slidesToScroll: responsiveSettings.tablet_portrait_slides,
							slidesToShow: responsiveSettings.tablet_portrait_slides,
						}
					},
					{
						breakpoint: responsiveSettings.mobile_breakpoint,
						settings: {
							slidesToScroll: responsiveSettings.mobile_slides,
							slidesToShow: responsiveSettings.mobile_slides,
						}
					},
				],
			} );

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
					complete = numItems === $$.data( 'post-count' ),
					numVisibleItems = Math.ceil( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) ),
					lastPosition = numItems - numVisibleItems + 1,
					slidesToScroll = $items.slick( 'slickGetOption', 'slidesToScroll' );

				// Check if all posts are displayed
				if ( ! complete ) {
					// Check if we need to fetch the next batch of posts
					if ( 
						$items.slick( 'slickCurrentSlide' ) + numVisibleItems >= numItems - 1 ||
						$items.slick( 'slickCurrentSlide' ) + slidesToScroll > lastPosition - 1
					) {

						if ( ! $$.data( 'fetching' ) ) {
							// Fetch the next batch
							$$.data( 'fetching', true );
							var page = $$.data( 'page' ) + 1;

							$items.slick( 'slickAdd', '<div class="sow-carousel-item sow-carousel-loading"></div>' );
							$.get(
								$$.data( 'ajax-url' ),
								{
									action: 'sow_carousel_load',
									paged: page,
									instance_hash: $$.parent().parent().find( 'input[name="instance_hash"]' ).val()
								},
								function ( data, status ) {
									$items.find( '.sow-carousel-loading' ).remove();
									$items.slick( 'slickAdd', data.html );
									numItems = $$.find( '.sow-carousel-item' ).length;
									$$.data( 'fetching', false );
									$$.data( 'page', page );

									if ( refocus ) {
										$items.find( '.sow-carousel-item[tabindex="0"]' ).trigger( 'focus' );
									}
								}
							);
						}
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
						if ( $$.data( 'loop-posts-enabled' ) ) {
							$items.slick( 'slickGoTo', 0 );
						}
					// Check if the next slide is the last slide and prevent blank spacing.
					} else if ( complete && $items.slick( 'slickCurrentSlide' ) + numVisibleItems >= lastPosition ) {
						$items.setSlideTo( lastPosition );

					// Check if the number of slides to scroll exceeds lastPosition, go to the last slide.
					} else if ( $items.slick( 'slickCurrentSlide' ) + slidesToScroll > lastPosition - 1 ) {
						$items.setSlideTo( lastPosition );
					} else {
						$items.slick( 'slickNext' );
					}
				} else if ( $( this ).hasClass( 'sow-carousel-previous' ) ) {
					if ( $$.data( 'loop-posts-enabled' ) && $items.slick( 'slickCurrentSlide' ) == 0 ) {
						$items.slick( 'slickGoTo', lastPosition );
					} else {
						$items.slick( 'slickPrev' );
					}
				}
			} );

		} );

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
				lastPosition = numItems - ( numItems === $wrapper.data( 'post-count' ) ? 0 : 1 );

			if ( e.keyCode == 37 ) {
				itemIndex--;
				if ( itemIndex < 0 ) {
					itemIndex = lastPosition;
				}
			} else if ( e.keyCode == 39 ) {
				itemIndex++;
				if ( itemIndex >= lastPosition ) {
					if ( $wrapper.data( 'fetching' ) ) {
						return; // Currently loading new post
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

				if ( numVisibleItems >= currentCarousel.data( 'post-count' ) ) {
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
