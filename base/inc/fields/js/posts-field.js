/* global jQuery, window.top.window.top.soWidgets, sowbForms */

( function( $ ) {

	const sowSetupPostsField = function( e ) {
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

			// Prevent duplicate requests with same query.
			if ( query === lastQuery ) {
				return;
			}

			// Abort previous request if still pending.
			if ( currentRequest && currentRequest.readyState !== 4 ) {
				currentRequest.abort();
			}

			lastQuery = query;

			currentRequest = $.post(
				window.top.soWidgets.ajaxurl,
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
	}

	 // If the current page isn't the site editor, set up the Posts field now.
	 if (
		 window.top === window.self &&
		 (
			 typeof pagenow === 'string' &&
			 pagenow !== 'site-editor'
		 )
	 ) {
		 $( document ).on( 'sowsetupform', '.siteorigin-widget-field-type-posts', sowSetupPostsField );
	 }

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-posts' ).each( function() {
				sowSetupPostsField.call( this );
			} );
		}
	} );

} )( jQuery );
