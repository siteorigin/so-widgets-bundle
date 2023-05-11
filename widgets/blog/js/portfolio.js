/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {

	sowb.setupBlogPortfolio = function () {
		$( '.sow-blog-layout-portfolio' ).each( function () {
			var $$ = $( this ),
				$buttons = $$.find( '.sow-portfolio-filter-terms button' ),
				$container = $$.find( '.sow-blog-posts' );

			$container.isotope( {
				itemSelector: '.sow-portfolio-item',
				filter: '*',
				layoutMode: 'fitRows',
				resizable: true,
			} );

			$buttons.on( 'click', function() {
				var selector = $( this ).attr( 'data-filter' );
				$container.isotope( {
					filter: selector,
				} );
				$buttons.removeClass( 'active' );
				$( this ).addClass( 'active' );
				return false;
			} );
		} );
	};

	sowb.setupBlogPortfolio();

	$( sowb ).on( 'setup_widgets', sowb.setupBlogPortfolio );
} );

window.sowb = sowb;
