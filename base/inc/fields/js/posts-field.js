/* global jQuery, soWidgets */

(function( $ ) {

	$( document ).on( 'sowsetupform', '.siteorigin-widget-field-type-posts', function ( e ) {
		var $postsField = $( this );
		$postsField.change( function ( event ) {
			var queryVars = $postsField.find( ':input' ).serializeArray();
			var queryObj = {};
			queryVars.forEach( function ( queryInput ) {
				if ( queryInput.value !== null && queryInput.value !== '' ) {
					var nameMatch = queryInput.name.match( /\[([^\[\]]+)\]$/ );
					if ( nameMatch !== null ) {
						var name = nameMatch[ 1 ];
						if ( queryObj.hasOwnProperty( name ) ) {
							queryObj[ name ] += ',' + queryInput.value;
						} else {
							queryObj[ name ] = queryInput.value;
						}
					}
				}
			} );

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
