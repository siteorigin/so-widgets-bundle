/* global jQuery, soWidgets */

(function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-autocomplete', function ( e ) {
		var $$ = $(this);

		// Function that refreshes the list of
		var request = null;
		var refreshList = function(){
			if( request !== null ) {
				request.abort();
			}

			var $contentSearchInput = $$.find('.content-text-search');
			var query = $contentSearchInput.val();
			var postTypes = $contentSearchInput.data('postTypes');

			var $ul = $$.find('ul.posts').empty().addClass('loading');
			$.get(
				soWidgets.ajaxurl,
				{ action: 'so_widgets_search_posts', query: query, postTypes: postTypes },
				function(data){
					for( var i = 0; i < data.length; i++ ) {
						if( data[i].post_title === '' ) {
							data[i].post_title = '&nbsp;';
						}

						// Add all the post items
						$ul.append(
							$('<li>')
								.addClass('post')
								.html( data[i].post_title + '<span>(' + data[i].post_type + ')</span>' )
								.data( data[i] )
						);
					}
					$ul.removeClass('loading');
				}
			);
		};

		$$.find('.siteorigin-widget-autocomplete-input').click(function () {
			var $s = $$.find('.existing-content-selector');
			$s.show();

			if( $s.is(':visible') && $s.find('ul.posts li').length === 0 ) {
				refreshList();
			}
		});

		var closeContent = function () {
			$$.find('.existing-content-selector').hide();
		};

		$(window).mousedown(function (event) {
			var mouseDownOutside = $$.find(event.target).length === 0;
			if ( mouseDownOutside ) {
				closeContent();
			}
		});

		$$.find('.button-close').click( closeContent );

		// Clicking on one of the url items
		$$.on( 'click', '.posts li', function(e) {
			e.preventDefault();
			var $li = $(this);
			var selectedPosts = $$.find( 'input.siteorigin-widget-input' ).val();
			selectedPosts = selectedPosts.length === 0 ? [] : selectedPosts.split( ',' );
			var clickedPost = $li.data( 'ID' );

			var curIndex = selectedPosts.indexOf( clickedPost );

			if ( curIndex > -1 ) {
				selectedPosts.splice( curIndex, 1 );
			} else {
				selectedPosts.push( clickedPost );
			}
			$$.find('input.siteorigin-widget-input').val( selectedPosts.join(',') );
		} );

		var interval = null;
		$$.find('.content-text-search').keyup( function(){
			if( interval !== null ) {
				clearTimeout(interval);
			}

			interval = setTimeout(function(){
				refreshList();
			}, 500);
		} );
	} );

})( jQuery );
