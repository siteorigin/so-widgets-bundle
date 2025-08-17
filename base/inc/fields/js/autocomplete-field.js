/* global jQuery, soWidgets */

( function( $ ) {

	const setupAutocompleteField = function( e ) {
		const $$ = $( this );

		if ( $$.data( 'initialized' ) ) {
			return;
		}

		const $contentSelector = $$.find(' .existing-content-selector' );

		const postId = parseInt( jQuery( '#post_ID' ).val() );

		const getSelectedItems = function() {
			const selectedItems = $$.find( 'input.siteorigin-widget-input' ).val();
			return selectedItems.length === 0 ? [] : selectedItems.split( ',' );
		};

		const updateSelectedItems = function() {
			const selectedItems = getSelectedItems();
			$$.find( 'ul.items > li' ).each( function( index, element ) {
				const $li = $( this );

				if ( selectedItems.indexOf( $li.data( 'value' ) ) > -1 ) {
					$li.addClass( 'selected' );
				} else {
					$li.removeClass( 'selected' );
				}
			} );
		};

		const $itemList = $$.find( 'ul.items' );
		const $noResults = $$.find( '.content-no-results' );
		let request = null;
		const refreshList = () => {
			if ( request !== null ) {
				request.abort();
			}

			const $contentSearchInput = $$.find( '.content-text-search' );
			const query = $contentSearchInput.val();
			const source = $contentSearchInput.data( 'source' );
			const postTypes = $contentSearchInput.data( 'postTypes' );
			const ajaxData = {
				action: 'so_widgets_search_' + source,
				postId: postId,
			};

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

			// Visually prep the field.
			$noResults.addClass( 'hidden' );
			$itemList.empty();
			$itemList.removeClass( 'hidden' )
			$itemList.addClass( 'loading' );

			return $.get(
				window.top.soWidgets.ajaxurl,
				ajaxData,
				( results ) => {
					// If there aren't any results, show a message.
					if ( results.length === 0 ) {
						$noResults.removeClass( 'hidden' );
						$itemList.addClass( 'hidden' );
						$itemList.removeClass( 'loading' );
						return;
					}


					results.forEach( ( item ) => {
						if ( item.label === '' ) {
							item.label = '&nbsp;';
						}
						// Add all the items.
						$itemList.append(
							$( '<li>' )
								.html( item.label + '<span>(' + item.type + ')</span>' )
								.data( item )
						);
					} );
					$itemList.removeClass( 'loading' );
				}
			);
		};

		$$.find( '.siteorigin-widget-autocomplete-input' ).on( 'click', () => {
			$noResults.addClass( 'hidden' );
			$itemList.show();
			$contentSelector.show();

			let refreshPromise = new $.Deferred();
			if( $contentSelector.is( ':visible' ) && $contentSelector.find( 'ul.items li' ).length === 0 ) {
				refreshPromise = refreshList();
			} else {
				refreshPromise.resolve();
			}

			refreshPromise.done( updateSelectedItems );
		} );

		const closeContent = function() {
			$contentSelector.hide();
		};

		$( window ).on( 'mousedown', function( event ) {
			const mouseDownOutside = $$.find( event.target ).length === 0;
			if ( mouseDownOutside ) {
				closeContent();
			}
		} );

		$$.find( '.button-close' ).on( 'click', closeContent );

		// Clicking on one of the url items.
		$$.on( 'click keypress', '.items li', function( e ) {
			e.preventDefault();

			if ( e.type == 'keyup' && ! window.sowbForms.isEnter( e ) ) {
				return;
			}
			const $input = $$.find( 'input.siteorigin-widget-input' );
			const $li = $( this );
			const clickedItem = $li.data( 'value' );

			if ( $contentSelector.data( 'multiple' ) ) {
				const selectedItems = getSelectedItems();

				const curIndex = selectedItems.indexOf( clickedItem );

				if ( curIndex > -1 ) {
					selectedItems.splice( curIndex, 1 );
					$li.removeClass( 'selected' );
				} else {
					selectedItems.push( clickedItem );
					$li.addClass( 'selected' );
				}
				$input.val( selectedItems.join( ',' ) );
			} else {
				$li.parent().find( '.selected' ).removeClass( 'selected' );
				$li.addClass( 'selected' );
				$input.val( clickedItem );
				closeContent();
			}
			$input.trigger( 'change' );
		} );

		let interval = null;
		$$.find( '.content-text-search' ).on( 'keyup', function() {
			if( interval !== null ) {
				clearTimeout( interval );
			}

			interval = setTimeout( function() {
				refreshList();
			}, 500 );
		} );

		$$.data( 'initialized', true );
	}

	 // If the current page isn't the site editor, set up the Autocomplete field now.
	 if (
		 window.top === window.self &&
		 (
			 typeof pagenow === 'string' &&
			 pagenow !== 'site-editor'
		 )
	 ) {
		 $( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-autocomplete', setupAutocompleteField );
	 }

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-autocomplete' ).each( function() {
				setupAutocompleteField.call( this );
			} );
		}
	} );
} )( jQuery );