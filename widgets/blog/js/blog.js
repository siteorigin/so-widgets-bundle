/* globals jQuery */

jQuery( function ( $ ) {
	if ( soBlogWidget.scrollto ) {
		// Detect if there's a Blog Widget in the URL, and scroll to it.
		if ( window.location.search && window.location.search.includes( 'sow-' ) ) {
			const blogId = window.location.search.match( /sow-([0-9a-f]+)/ )[1];
			const blogWidget = $( `[data-paging-id="${ blogId }"]` );
			if ( blogWidget.length ) {
				$( 'html, body' ).animate( {
					scrollTop: blogWidget.offset().top - soBlogWidget.scrollto_offset,
				}, 200 );
			}
		}
	}
} );
