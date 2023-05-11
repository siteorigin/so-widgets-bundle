/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	$( sowb ).on( 'carousel_load_new_items', function( e, carousel, $items, refocus  ) {
		if (  carousel.data( 'widget' ) == 'post' && ! carousel.data( 'fetching' ) ) {
			// Fetch the next batch
			carousel.data( 'fetching', true );
			var page = carousel.data( 'page' ) + 1;

			$items.slick( 'slickAdd', '<div class="sow-carousel-item sow-carousel-loading"></div>' );
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
					carousel.data( 'page', page );

					if ( refocus ) {
						$items.find( '.sow-carousel-item[tabindex="0"]' ).trigger( 'focus' );
					}

					$( sowb ).trigger( 'carousel_posts_added' );
				}
			);
		}
	} );
} );

window.sowb = sowb;
