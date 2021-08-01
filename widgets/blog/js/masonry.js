/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {

	sowb.setupBlogMasonry = function () {
		$( '.sow-blog-layout-masonry' ).each( function () {
			$( this ).masonry( {
				itemSelector: '.sow-masonry-item',
				columnWidth: '.sow-masonry-item'
			} );
		} );
	};

	sowb.setupBlogMasonry();

	$( sowb ).on( 'setup_widgets', sowb.setupBlogMasonry );
} );

window.sowb = sowb;
