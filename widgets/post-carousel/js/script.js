/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	$( sowb ).on( 'carousel_load_new_items', function( e, carousel, $items, refocus ) {
		if ( carousel.data( 'widget' ) !== 'post' ) {
			return;
		}

		if ( carousel.data( 'fetching' ) ) {
			if ( carousel.data( 'preloaded' ) ) {
				// preloaded is set to false, to indicate to the carousel that
				// we're not loading early anymore - don't allow further scrolls.
				carousel.data( 'preloaded', false );
			}

			return;
		}

		// Add loading indicator to the carousel.
		$items.slick( 'slickAdd', '<div class="sow-carousel-item sow-carousel-loading"></div>' );

		// Fetch the next batch of posts.
		carousel.data( 'fetching', true );
		carousel.data( 'preloaded', true );
		const page = carousel.data( 'page' ) + 1;

		$.get(
			carousel.data( 'ajax-url' ),
			{
				action: 'sow_carousel_load',
				paged: page,
				instance_hash: carousel.parent().parent().find( 'input[name="instance_hash"]' ).val()
			},
			function ( data ) {
				$items.find( '.sow-carousel-loading' ).remove();
				$items.slick( 'slickAdd', data.html );
				carousel.data( 'fetching', false );
				carousel.data( 'preloaded', false );
				carousel.data( 'page', page );

				if ( refocus ) {
					$items.find( '.sow-carousel-item[tabindex="0"]' ).trigger( 'focus' );
				}

				$( sowb ).trigger( 'carousel_posts_added' );
			}
		);
	} );
} );

window.sowb = sowb;
