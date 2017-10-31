/* global jQuery, soWidgets */

(function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-autocomplete', function ( e ) {
		var $$ = $(this);

		if ( $$.data( 'initialized' ) ) {
			return;
		}

		var getSelectedItems = function() {
			var selectedItems = $$.find( 'input.siteorigin-widget-input' ).val();
			return selectedItems.length === 0 ? [] : selectedItems.split( ',' );
		};

		var updateSelectedItems = function() {
			var selectedItems = getSelectedItems();
			$$.find( 'ul.items > li' ).each( function ( index, element ) {
				var $li = $( this );

				if ( selectedItems.indexOf( $li.data( 'value' ) ) > -1 ) {
					$li.addClass( 'selected' );
				} else {
					$li.removeClass( 'selected' );
				}
			} );
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
			var ajaxData = { action: 'so_widgets_search_' + source };
			if ( source === 'posts' ) {
				ajaxData.query = query;
				ajaxData.postTypes = postTypes;
			} else if ( source === 'terms' ) {
				ajaxData.term = query;
			}
			var $ul = $$.find('ul.items').empty().addClass('loading');
			return $.get(
				soWidgets.ajaxurl,
				ajaxData,
				function(results) {
					results.forEach(function (item) {
						if (item.label === '') {
							item.label = '&nbsp;';
						}
						// Add all the items
						$ul.append(
							$('<li>')
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

			var refreshPromise = new $.Deferred();
			if( $s.is(':visible') && $s.find('ul.items li').length === 0 ) {
				refreshPromise = refreshList();
			} else {
				refreshPromise.resolve();
			}

			refreshPromise.done( updateSelectedItems );
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
			var $input = $$.find('input.siteorigin-widget-input');
			$input.val( selectedItems.join(',') );
			$input.change();
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

		$$.data( 'initialized', true );
	} );

})( jQuery );
