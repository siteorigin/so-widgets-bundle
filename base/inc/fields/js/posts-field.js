/* global jQuery, soWidgets, sowbForms */

(function( $ ) {

	$( document ).on( 'sowsetupform', '.siteorigin-widget-field-type-posts', function ( e ) {
		var $postsField = $( this );
		$postsField.on( 'change', function( event ) {
			var postsValues = sowbForms.getWidgetFormValues( $postsField );
			var queryObj = postsValues.hasOwnProperty( 'posts' ) ? postsValues.posts : null;

			var query = '';
			for ( var key in queryObj ) {
				if ( query !== '' ) {
					query += '&';
				}
				query += key + '=' + queryObj[ key ];
			}

			$.post(
				soWidgets.ajaxurl,
				{ action: 'sow_get_posts_count', query: query },
				function(data){
					$postsField.find( '.sow-current-count' ).text( data.posts_count );

				}
			);
		} );
	} );

})( jQuery );
