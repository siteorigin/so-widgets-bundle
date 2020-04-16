/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {

	sowb.setupCarousel = function () {
		// The carousel widget
		$( '.sow-carousel-wrapper' ).each( function () {
			var $$ = $( this ).children( '.sow-carousel-items' ), // = $itemsContainer
			$wrapper = $( '.sow-carousel-wrapper' ),
			instanceHash = $( this ).parent().parent().find( 'input[name="instance_hash"]' ).val(),
			numItems = $$.find( '.sow-carousel-item' ).length,
			totalPosts = $wrapper.data( 'post-count' ),
			complete = numItems === totalPosts,
			fetching = false,
			page = 1,
			itemWidth = $$.find( '.sow-carousel-item' ).outerWidth( true );

			$$.not('.slick-initialized').slick( {
				infinite: false,
				variableWidth: true,
				slidesToScroll: 1,
				slidesToShow: 1,
				rows: 0,
				prevArrow: $( 'a.sow-carousel-previous' ),
				nextArrow: $( 'a.sow-carousel-next' ),
			} );

			// click is used rather than Slick's beforeChange or afterChange 
			// due to the inability to stop a slide from changing from those events
			$( this ).parents( '.so-widget-sow-post-carousel' ).find( '.slick-arrow' ).on( 'click', function( e ){
				const numVisibleItems = Math.ceil( $$.outerWidth() / itemWidth );
				const lastPosition = numItems - numVisibleItems + 1

				// Check if all posts are displayed
				if ( ! complete ) {
					// Check if we need to fetch the next batch of posts
					if ( $$.slick( 'slickCurrentSlide' ) + numVisibleItems >= numItems - 1 ) {

						if ( ! fetching ) {
							// Fetch the next batch
							fetching = true;
							page++;

							$$.slick( 'slickAdd', '<div class="sow-carousel-item sow-carousel-loading"></div>' );

							$.get(
								$wrapper.data( 'ajax-url' ),
								{
									query: $wrapper.data( 'query' ),
									action: 'sow_carousel_load',
									paged: page,
									instance_hash: instanceHash
								},
								function ( data, status ) {
									var $items = $( data.html );
									$$.find( '.sow-carousel-loading' ).remove();
									$$.slick( 'slickAdd', data.html );
									numItems = $wrapper.find( '.sow-carousel-item' ).length;

									complete = numItems === totalPosts;
									fetching = false;
								}
							);
						} else if ( $(this).hasClass( 'sow-carousel-next' ) ) {
							// Don't allow the user to navigate after loading item
							if ( $$.slick( 'slickCurrentSlide' ) >= lastPosition ) {
								e.stopImmediatePropagation();
							}
						}
					}
				}

			} );

		} );
	};

	sowb.setupCarousel();

	$( sowb ).on( 'setup_widgets', sowb.setupCarousel );
} );

window.sowb = sowb;
