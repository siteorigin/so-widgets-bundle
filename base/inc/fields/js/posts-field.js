/* global jQuery, soWidgets, sowbForms */

( function( $ ) {

	$( document ).on( 'sowsetupform', '.siteorigin-widget-field-type-posts', function( e ) {
		const $postsField = $( this );
		const hasCount = $postsField.find( '.sow-current-count' ).length > 0;
		const postId = parseInt( jQuery( '#post_ID' ).val() );

		if ( ! hasCount ) {
			return;
		}

		let debounceTimer;
		let currentRequest;
		let lastQuery = '';

		/**
		 * Debounced function to handle the posts count request.
		 *
		 * This function retrieves widget form values, builds a query string,
		 * prevents duplicate requests, and makes an AJAX call to get the posts count.
		 */
		const handlePostsCountRequest = function() {
			const postsValues = sowbForms.getWidgetFormValues( $postsField );
			const queryObj = postsValues.hasOwnProperty( 'posts' ) ? postsValues.posts : null;

			let query = '';
			for ( const key in queryObj ) {
				if ( query !== '' ) {
					query += '&';
				}
				query += key + '=' + queryObj[ key ];
			}

			// Prevent duplicate requests with same query
			if ( query === lastQuery ) {
				return;
			}

			// Abort previous request if still pending
			if ( currentRequest && currentRequest.readyState !== 4 ) {
				currentRequest.abort();
			}

			lastQuery = query;

			currentRequest = $.post(
				soWidgets.ajaxurl,
				{
					action: 'sow_get_posts_count',
					query: query,
					postId: postId,
				},
				function( data ) {
					$postsField.find( '.sow-current-count' ).text( data.posts_count );
				}
			);
		};

		$postsField.on( 'change', function() {
			clearTimeout( debounceTimer );
			debounceTimer = setTimeout( handlePostsCountRequest, 300 );
		} );
	} );

} )( jQuery );
