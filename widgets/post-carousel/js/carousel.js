/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {

	sowb.setupCarousel = function () {
		// The carousel widget
		$( '.sow-carousel-wrapper' ).each( function () {
			var $$ = $( this );
			direction = $$.attr( 'dir' );
			$items = $$.find( '.sow-carousel-items' ),
			$widget = $$.parent().parent();
			instanceHash = $widget.find( 'input[name="instance_hash"]' ).val(),
			numItems = $items.find( '.sow-carousel-item' ).length,
			totalPosts = $$.data( 'post-count' ),
			complete = numItems === totalPosts,
			fetching = false,
			page = 1,
			itemWidth = $items.find( '.sow-carousel-item' ).outerWidth( true );

			$items.not( '.slick-initialized' ).slick( {
				arrows: false,
				infinite: false,
				rows: 0,
				rtl: direction == 'rtl',
				waitForAnimate: false,
				touchThreshold: 20,
				variableWidth: true,
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

			// click is used rather than Slick's beforeChange or afterChange 
			// due to the inability to stop a slide from changing from those events
			$widget.find( '.sow-carousel-previous, .sow-carousel-next' ).on( 'click', function( e ) {
				e.preventDefault();
				$items = $$.find( '.sow-carousel-items' );
				const numVisibleItems = Math.ceil( $items.outerWidth() / itemWidth );
				const lastPosition = numItems - numVisibleItems + 1

				// Check if all posts are displayed
				if ( ! complete ) {
					// Check if we need to fetch the next batch of posts
					if ( $items.slick( 'slickCurrentSlide' ) + numVisibleItems >= numItems - 1 ) {

						if ( ! fetching ) {
							// Fetch the next batch
							fetching = true;
							page++;

							$items.slick( 'slickAdd', '<div class="sow-carousel-item sow-carousel-loading"></div>' );

							$.get(
								$$.data( 'ajax-url' ),
								{
									query: $$.data( 'query' ),
									action: 'sow_carousel_load',
									paged: page,
									instance_hash: instanceHash
								},
								function ( data, status ) {
									$items.find( '.sow-carousel-loading' ).remove();
									$items.slick( 'slickAdd', data.html );
									numItems = $$.find( '.sow-carousel-item' ).length;

									complete = numItems === totalPosts;
									fetching = false;
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
						if ( $$.data( 'loop-posts-enabled' ) && ! fetching ) {
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
