/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {

	sowb.setupCarousel = function () {
		// The carousel widget
		$( '.sow-carousel-wrapper' ).each( function () {

				$postsContainer = $$.closest( '.sow-carousel-container' ),
				$container = $$.closest( '.sow-carousel-container' ).parent(),
				$itemsContainer = $$.find( '.sow-carousel-items' ),
				$items = $$.find( '.sow-carousel-item' ),
				$firstItem = $items.eq( 0 );

			var position = 0,
				page = 1,
				fetching = false,
				numItems = $items.length,
				totalPosts = $$.data( 'post-count' ),
				loopPostsEnabled = $$.data( 'loop-posts-enabled' ),
				complete = numItems === totalPosts,
				itemWidth = ( $firstItem.width() + parseInt( $firstItem.css( 'margin-right' ) ) ),
				isRTL = $postsContainer.hasClass( 'js-rtl' ),
				updateProp = isRTL ? 'margin-right' : 'margin-left';

			var updatePosition = function () {
				const numVisibleItems = Math.ceil( $$.outerWidth() / itemWidth );
				const lastPosition = totalPosts - numVisibleItems + 1;
				const shouldLoop = loopPostsEnabled && !fetching && complete;
				const hasPosts = numItems !== null && !isNaN(numItems);

				if (position < 0) {
					position = (shouldLoop && hasPosts) ? lastPosition : 0;
				} else if (position > Math.min(numItems, lastPosition) ) {
					position = shouldLoop ? 0 : Math.min(numItems, lastPosition);
				}

				// Offset position by numVisibleItems to trigger the next fetch before the view is empty.
				if ( position + numVisibleItems >= numItems - 1 ) {
					// Fetch the next batch
					if ( !fetching && !complete ) {
						fetching = true;
						page++;
						$itemsContainer.append( '<li class="sow-carousel-item sow-carousel-loading"></li>' );
						var instanceHash = $container.find( 'input[name="instance_hash"]' ).val();

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
								$items.appendTo( $itemsContainer ).hide().fadeIn();
								$$.find( '.sow-carousel-loading' ).remove();
								numItems = $$.find( '.sow-carousel-item' ).length;
								complete = numItems === totalPosts;
								fetching = false;
							}
						);
					}
				}
				$itemsContainer.css( 'transition-duration', "0.45s" );
				$itemsContainer.css( updateProp, -( itemWidth * position) + 'px' );
			};

			$container.on( 'click', 'a.sow-carousel-previous',
				function ( e ) {
					e.preventDefault();
					position -= isRTL ? -1 : 1;
					updatePosition();
				}
			);

			$container.on( 'click', 'a.sow-carousel-next',
				function ( e ) {
					e.preventDefault();
					position += isRTL ? -1 : 1;
					updatePosition();
				}
			);

		} );
	};

	sowb.setupCarousel();

	$( sowb ).on( 'setup_widgets', sowb.setupCarousel );
} );

window.sowb = sowb;
