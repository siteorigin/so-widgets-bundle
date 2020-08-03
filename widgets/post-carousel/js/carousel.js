/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {

	sowb.setupCarousel = function () {
		// The carousel widget
		$( '.sow-carousel-wrapper' ).each( function () {
			var $$ = $( this );
			$items = $$.find( '.sow-carousel-items' );

			$items.not( '.slick-initialized' ).slick( {
				arrows: false,
				infinite: false,
				rows: 0,
				rtl: $$.data( 'dir' ) == 'rtl',
				touchThreshold: 20,
				variableWidth: true,
				accessibility: false,
				responsive: [
					{
						breakpoint: carouselBreakpoints.tablet_portrait,
						settings: {
							slidesToScroll: 2,
							slidesToShow: 2,
						}
					},
					{
						breakpoint: carouselBreakpoints.mobile,
						settings: {
							slidesToScroll: 1,
							slidesToShow: 1,
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
			$$.parent().parent().find( '.sow-carousel-previous, .sow-carousel-next' ).on( 'click touchend', function( e ) {
				e.preventDefault();
				$items = $$.find( '.sow-carousel-items' );
				var numItems = $items.find( '.sow-carousel-item' ).length,
				complete = numItems === $$.data( 'post-count' ),
				numVisibleItems = Math.ceil( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) ),
				lastPosition = numItems - numVisibleItems + 1;

				// Check if all posts are displayed
				if ( ! complete ) {
					// Check if we need to fetch the next batch of posts
					if ( $items.slick( 'slickCurrentSlide' ) + numVisibleItems >= numItems - 1 ) {

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

									$items.find( '.sow-carousel-item[tabindex="0"]' ).trigger( 'focus' );
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
					if ( $items.slick( 'slickCurrentSlide' ) >= lastPosition ) {
						if ( $$.data( 'loop-posts-enabled' ) && ! $$.data( 'fetching' ) ) {
							$items.slick( 'slickGoTo', 0 );
						}
					} else {
						$items.slick( 'slickNext' );
					}
				} else if ( $( this ).hasClass( 'sow-carousel-previous' ) ) {
					if ( $$.data( 'loop-posts-enabled' ) && $items.slick( 'slickCurrentSlide' ) == 0 ) {
						// Navigate to the second last slide to prevent blank spacing
						$items.slick( 'slickGoTo', lastPosition - ( complete ? 0 : 1) );
					} else {
						$items.slick( 'slickPrev' );
					}
				}
			} );

			// Hide/disable scroll if number of visible items is less than total posts.
			$( window ).on( 'resize load', function() {
				$items = $$.find( '.sow-carousel-items' );
				var numVisibleItems = Math.ceil( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) );
				var navigation = $$.parent().parent().find( '.sow-carousel-navigation' );
				if ( numVisibleItems >= $items.find( '.sow-carousel-item' ).length ) {
					navigation.hide();
					'touchMove'
					$items.slick( 'slickSetOption', 'touchMove', false );
					$items.slick( 'slickSetOption', 'draggable', false );
				} else if ( navigation.not( ':visible' ) ) {
					navigation.show();
					$items.slick( 'slickSetOption', 'touchMove', true );
					$items.slick( 'slickSetOption', 'draggable', true );
				}

				$items.find( '.sow-carousel-item' ).first().prop( 'tabindex', 0 );
			} );

			$$.find( '.sow-carousel-item' ).on( 'keyup', function( e ) {
				// Ensure left/right key was pressed
				if ( e.keyCode != 37 && e.keyCode != 39 ) {
					return;
				}

				var $items = $$.find( '.sow-carousel-items' ),
				numItems = $items.find( '.sow-carousel-item' ).length,
				numVisibleItems = Math.ceil( $items.outerWidth() / $items.find( '.sow-carousel-item' ).outerWidth( true ) ),
				itemIndex = $( this ).data( 'slick-index' ),
				lastPosition = numItems - ( numItems === $$.data( 'post-count' ) ? 0 : 1 );

				if ( e.keyCode == 37 ) {
					itemIndex--;
					if ( itemIndex < 0 ) {
						itemIndex = lastPosition;
					}
				} else if ( e.keyCode == 39 ) {
					if ( itemIndex >= lastPosition ) {
						$( '.sow-carousel-next' ).trigger( 'click' );
					} else {
						itemIndex++;
					}
				}

				$items.slick( 'slickGoTo', itemIndex, true );
				$$.find( '.sow-carousel-item' ).prop( 'tabindex', -1 );
				$$.find( '.sow-carousel-item[data-slick-index="' + itemIndex + '"]' )
					.trigger( 'focus' )
					.prop( 'tabindex', 0 );
			} );
		} );

		// Change Slick Settings on iPad Pro while Landscape
		$( window ).resize( function() {
			if ( window.matchMedia( '(min-width: ' + carouselBreakpoints.tablet_portrait + 'px) and (max-width: ' + carouselBreakpoints.tablet_landscape + 'px) and (orientation: landscape)' ).matches ) {				
				$( '.sow-carousel-items' ).slick( 'slickSetOption', 'slidesToShow', 3 );
				$( '.sow-carousel-items' ).slick( 'slickSetOption', 'slidesToScroll', 3 );
			}
		} );
	};

	sowb.setupCarousel();

	$( sowb ).on( 'setup_widgets', sowb.setupCarousel );
} );

window.sowb = sowb;
