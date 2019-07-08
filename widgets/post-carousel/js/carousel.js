/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	
	sowb.setupCarousel = function () {
		// The carousel widget
		$( '.sow-carousel-wrapper' ).each( function () {
	
			var $$ = $( this ),
				$postsContainer = $$.closest( '.sow-carousel-container' ),
				$container = $$.closest( '.sow-carousel-container' ).parent(),
				$itemsContainer = $$.find( '.sow-carousel-items' ),
				$items = $$.find( '.sow-carousel-item' ),
				$firstItem = $items.eq( 0 );
	
			var position = 0,
				page = 1,
				fetching = false,
				numItems = $items.length,
				totalPosts = $$.data( 'found-posts' ),
				complete = numItems === totalPosts,
				itemWidth = ( $firstItem.width() + parseInt( $firstItem.css( 'margin-right' ) ) ),
				isRTL = $postsContainer.hasClass( 'js-rtl' ),
				updateProp = isRTL ? 'margin-right' : 'margin-left';
	
			var updatePosition = function () {
				if (position < 0) {
					position = 0;
					return;
				}
				if (position === numItems) {
					position--;
					return;
				}
				var numVisibleItems = Math.ceil( $$.outerWidth() / itemWidth );
				// Offset position by numVisibleItems to trigger the next fetch before the view is empty.
				if ( position + numVisibleItems >= $$.find( '.sow-carousel-item' ).length - 1 ) {
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
								instance_hash: instanceHash,
							},
							function ( data, status ) {
								var $items = $( data.html );
								$items.appendTo( $itemsContainer ).hide().fadeIn();
								$$.find( '.sow-carousel-loading' ).remove();
								numItems = $$.find( '.sow-carousel-item' ).length;
								complete = numItems === totalPosts;
								fetching = false;
							}
						)
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
	
			// Verify "swipe" method exists prior to invoking it.
			if ( 'function' === typeof $$.swipe ) {
				var validSwipe = false;
				var prevDistance = 0;
				var startPosition = 0;
				var velocity = 0;
				var prevTime = 0;
				var posInterval;
				var negativeDirection = isRTL ? 'right' : 'left';
	
				var setNewPosition = function ( newPosition ) {
					if ( newPosition < 50 && newPosition > -( itemWidth * numItems ) ) {
						$itemsContainer.css( 'transition-duration', "0s" );
						$itemsContainer.css( updateProp, newPosition + 'px' );
						return true;
					}
					return false;
				};
	
				var setFinalPosition = function () {
					var finalPosition = parseInt( $itemsContainer.css( updateProp ) );
					position = Math.abs( Math.round( finalPosition / itemWidth ) );
					updatePosition();
				};
	
				$$.on( 'click', '.sow-carousel-item a',
					function ( event ) {
						if ( validSwipe ) {
							event.preventDefault();
							validSwipe = false;
						}
					}
				);
	
				$$.swipe( {
					excludedElements: "",
					triggerOnTouchEnd: true,
					threshold: 75,
					swipeStatus: function ( event, phase, direction, distance, duration, fingerCount, fingerData ) {
						if ( direction === 'up' || direction === 'down' ) {
							return false;
						}
	
						if ( phase === "start" ) {
							startPosition = -( itemWidth * position);
							prevTime = new Date().getTime();
							clearInterval( posInterval );
						}
						else if ( phase === "move" ) {
							if ( direction === negativeDirection ) distance *= -1;
							setNewPosition( startPosition + distance );
							var newTime = new Date().getTime();
							var timeDelta = (newTime - prevTime) / 1000;
							velocity = (distance - prevDistance) / timeDelta;
							prevTime = newTime;
							prevDistance = distance;
						}
						else if ( phase === "end" ) {
							validSwipe = true;
							if ( direction === negativeDirection ) distance *= -1;
							if ( Math.abs( velocity ) > 400 ) {
								velocity *= 0.1;
								var startTime = new Date().getTime();
								var cumulativeDistance = 0;
								posInterval = setInterval( function () {
									var time = (new Date().getTime() - startTime) / 1000;
									cumulativeDistance += velocity * time;
									var newPos = startPosition + distance + cumulativeDistance;
									var decel = 30;
									var end = (Math.abs( velocity ) - decel) < 0;
									if ( direction === negativeDirection ) {
										velocity += decel;
									} else {
										velocity -= decel;
									}
									if ( end || !setNewPosition( newPos ) ) {
										clearInterval( posInterval );
										setFinalPosition();
									}
								}, 20 );
							} else {
								setFinalPosition();
							}
						}
						else if ( phase === "cancel" ) {
							updatePosition();
						}
					}
				} );
			}
		} );
	};
	
	sowb.setupCarousel();
	
	$( sowb ).on( 'setup_widgets', sowb.setupCarousel );
} );

window.sowb = sowb;
