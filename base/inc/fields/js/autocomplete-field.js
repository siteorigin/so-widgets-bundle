/* global jQuery, soWidgets */

(function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-autocomplete', function ( e ) {
		var $$ = $(this);

		var getSelectedItems = function() {
			var selectedItems = $$.find( 'input.siteorigin-widget-input' ).val();
			return selectedItems.length === 0 ? [] : selectedItems.split( ',' );
		};
		// Function that refreshes the list of
		var request = null;
		var refreshList = function(){
			if( request !== null ) {
				request.abort();
			}

			var $contentSearchInput = $$.find('.content-text-search');
			var query = $contentSearchInput.val();
			var source = $contentSearchInput.data('source');
			var postTypes = $contentSearchInput.data('postTypes');

			var $ul = $$.find('ul.items').empty().addClass('loading');
			var selectedItems = getSelectedItems();
			$.get(
				soWidgets.ajaxurl,
				{ action: 'so_widgets_search_' + source, query: query, postTypes: postTypes },
				function(results) {
					results.forEach(function (item) {
						if (item.label === '') {
							item.label = '&nbsp;';
						}
						var isSelected = selectedItems.indexOf(item.value) > -1;
						// Add all the items
						$ul.append(
							$('<li>')
								.addClass(isSelected ? 'selected' : '')
								.html(item.label + '<span>(' + item.type + ')</span>')
								.data(item)
						);
					});
					$ul.removeClass('loading');
				}
			);
		};

		$$.find('.siteorigin-widget-autocomplete-input').click(function () {
			var $s = $$.find('.existing-content-selector');
			$s.show();

			if( $s.is(':visible') && $s.find('ul.items li').length === 0 ) {
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
		$$.on( 'click', '.items li', function(e) {
			e.preventDefault();
			var $li = $(this);
			var selectedItems = getSelectedItems();
			var clickedItem = $li.data( 'value' );

			var curIndex = selectedItems.indexOf( clickedItem );

			if ( curIndex > -1 ) {
				selectedItems.splice( curIndex, 1 );
				$li.removeClass( 'selected' );
			} else {
				selectedItems.push( clickedItem );
				$li.addClass( 'selected' );
			}
			$$.find('input.siteorigin-widget-input').val( selectedItems.join(',') );
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
