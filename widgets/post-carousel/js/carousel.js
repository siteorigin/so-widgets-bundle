/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {

	sowb.setupCarousel = function () {
		// The carousel widget
		$( '.sow-carousel-wrapper' ).each( function () {
			var $$ = $( this );
			$items = $$.children( '.sow-carousel-items' ), // = $itemsContainer
			$widget = $$.parent().parent();
			$$ = $$,
			instanceHash = $widget.find( 'input[name="instance_hash"]' ).val(),
			numItems = $items.find( '.sow-carousel-item' ).length,
			totalPosts = $$.data( 'post-count' ),
			complete = numItems === totalPosts,
			fetching = false,
			page = 1,
			itemWidth = $items.find( '.sow-carousel-item' ).outerWidth( true );

			$items.not('.slick-initialized').slick( {
				infinite: false,
				variableWidth: true,
				slidesToScroll: 1,
				slidesToShow: 1,
				rows: 0,
				prevArrow: $widget.find( '.sow-carousel-previous' ),
				nextArrow: $widget.find( '.sow-carousel-next' ),
			} );

			// click is used rather than Slick's beforeChange or afterChange 
			// due to the inability to stop a slide from changing from those events
			$( this ).parents( '.so-widget-sow-post-carousel' ).find( '.slick-arrow' ).on( 'click', function( e ){
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
									var $items = $( data.html );
									$items.find( '.sow-carousel-loading' ).remove();
									$items.slick( 'slickAdd', data.html );
									numItems = $$.find( '.sow-carousel-item' ).length;

									complete = numItems === totalPosts;
									fetching = false;
								}
							);
						} else if ( $(this).hasClass( 'sow-carousel-next' ) ) {
							// Don't allow the user to navigate after loading item
							if ( $items.slick( 'slickCurrentSlide' ) >= lastPosition ) {
								e.stopImmediatePropagation();
							}
						}
					}
				}

				// The Slick Infinite setting has a positioning bug that can result in the first item being hidden.
				// https://github.com/kenwheeler/slick/issues/3567
				if ( $$.data( 'loop-posts-enabled' ) ) {
					if ( $(this).hasClass( 'sow-carousel-next' )  && $items.slick( 'slickCurrentSlide' ) >= lastPosition ) {
						$items.slick( 'slickGoTo', 0 )
					} else if ( $(this).hasClass( 'sow-carousel-previous' ) && $items.slick( 'slickCurrentSlide' ) == 0 ) {
						// We need to navigate to a different slide to prevent blank spacing
						$items.slick( 'slickGoTo', lastPosition - ( complete ? 0 : 1) );
					}
				}

			} );

		} );
	};

	sowb.setupCarousel();

	$( sowb ).on( 'setup_widgets', sowb.setupCarousel );
} );

window.sowb = sowb;
