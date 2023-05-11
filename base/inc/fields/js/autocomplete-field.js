/* global jQuery, soWidgets */

(function( $ ) {

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-autocomplete', function ( e ) {
		var $$ = $( this );
		var $contentSelector = $$.find(' .existing-content-selector' );

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

			// If WPML is enabled for this page, include page language for filtering.
			if ( typeof icl_this_lang == 'string' ) {
				ajaxData.language = icl_this_lang;
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

		$$.find( '.siteorigin-widget-autocomplete-input' ).on( 'click', function () {
			$contentSelector.show();

			var refreshPromise = new $.Deferred();
			if( $contentSelector.is(':visible') && $contentSelector.find('ul.items li').length === 0 ) {
				refreshPromise = refreshList();
			} else {
				refreshPromise.resolve();
			}

			refreshPromise.done( updateSelectedItems );
		});

		var closeContent = function () {
			$contentSelector.hide();
		};

		$( window ).on( 'mousedown', function( event ) {
			var mouseDownOutside = $$.find(event.target).length === 0;
			if ( mouseDownOutside ) {
				closeContent();
			}
		});

		$$.find('.button-close').on( 'click', closeContent );

		// Clicking on one of the url items
		$$.on( 'click keypress', '.items li', function( e ) {
			e.preventDefault();

			if ( e.type == 'keyup' && ! window.sowbForms.isEnter( e ) ) {
				return;
			}
			var $input = $$.find('input.siteorigin-widget-input');
			var $li = $( this );
			var clickedItem = $li.data( 'value' );

			if ( $contentSelector.data( 'multiple' ) ) {
				var selectedItems = getSelectedItems();

				var curIndex = selectedItems.indexOf( clickedItem );

				if ( curIndex > -1 ) {
					selectedItems.splice( curIndex, 1 );
					$li.removeClass( 'selected' );
				} else {
					selectedItems.push( clickedItem );
					$li.addClass( 'selected' );
				}				
				$input.val( selectedItems.join(',') );
			} else {
				$li.parent().find( '.selected' ).removeClass( 'selected' );
				$li.addClass( 'selected' );
				$input.val( clickedItem );
				closeContent();
			}
			$input.trigger( 'change' );
		} );

		var interval = null;
		$$.find('.content-text-search').on( 'keyup', function() {
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
