/* global jQuery, window.top.window.top.soWidgets, sowbForms */

( function( $ ) {

	const sowSetupPostsField = function( e ) {
		const $postsField = $( this );
		const hasCount = $postsField.find( '.sow-current-count' ).length > 0;
		const postId = parseInt( jQuery( '#post_ID' ).val() );

		if ( ! hasCount ) {
			return;
		}

		$postsField.on( 'change', function( event ) {
			const postsValues = sowbForms.getWidgetFormValues( $postsField );
			const queryObj = postsValues.hasOwnProperty( 'posts' ) ? postsValues.posts : null;

			let query = '';
			for ( const key in queryObj ) {
				if ( query !== '' ) {
					query += '&';
				}
				query += key + '=' + queryObj[ key ];
			}

			$.post(
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
		} );
	}

	$( document ).on( 'sowsetupform', '.siteorigin-widget-field-type-posts', sowSetupPostsField );

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-posts' ).each( function() {
				sowSetupPostsField.call( this );
			} );
		}
	} );

} )( jQuery );
