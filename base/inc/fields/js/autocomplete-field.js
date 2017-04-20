/* global jQuery, soWidgets */

(function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-autocomplete', function ( e ) {
		var $$ = $(this);

		var getSelectedPosts = function() {
			var selectedPosts = $$.find( 'input.siteorigin-widget-input' ).val();
			return selectedPosts.length === 0 ? [] : selectedPosts.split( ',' );
		};
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
			var selectedPosts = getSelectedPosts();
			$.get(
				soWidgets.ajaxurl,
				{ action: 'so_widgets_search_posts', query: query, postTypes: postTypes },
				function(posts) {
					posts.forEach(function (post) {
						if (post.post_title === '') {
							post.post_title = '&nbsp;';
						}
						var isSelected = selectedPosts.indexOf(post.ID) > -1;
						// Add all the post items
						$ul.append(
							$('<li>')
								.addClass('post')
								.addClass(isSelected ? 'selected' : '')
								.html(post.post_title + '<span>(' + post.post_type + ')</span>')
								.data(post)
						);
					});
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
			var selectedPosts = getSelectedPosts();
			var clickedPost = $li.data( 'ID' );

			var curIndex = selectedPosts.indexOf( clickedPost );

			if ( curIndex > -1 ) {
				selectedPosts.splice( curIndex, 1 );
				$li.removeClass( 'selected' );
			} else {
				selectedPosts.push( clickedPost );
				$li.addClass( 'selected' );
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
