/* global jQuery, window.top.soWidgets */
( function( $ ) {
	if ( window.top.soWidgets === undefined ) {
		window.top.soWidgets = {};
	}
	window.top.soWidgets.icons = [];

	const setupIconField = function( e ) {
		var $$ = $( this ),
			$is = $$.find( '.siteorigin-widget-icon-selector' ),
			$v = $is.find( '.siteorigin-widget-icon-icon' ),
			$b = $$.find( '.siteorigin-widget-icon-selector-current' ),
			$remove = $$.find( '.so-icon-remove' ),
			$search = $$.find( '.siteorigin-widget-icon-search' ),
			$iconContainer = $is.find( '.siteorigin-widget-icon-icons' );

		if ( $$.attr( 'data-initialized' ) ) {
			return;
		}
		$$.attr( 'data-initialized', true );

		// Clear the base icon to prevent a potential duplicate icon.
		$b.find( '.sow-icon-clear' ).remove();

		// Clicking on the button should display the icon selector.
		$b.on( 'click keyup', function( e ) {
			if ( e.type == 'keyup' && ! window.top.sowbForms.isEnter( e ) ) {
				return;
			}

			$is.slideToggle();
			$search.val( '' );
			searchIcons();
		} );

		// Clicking on the remove button.
		$remove.on( 'click keyup', function( e ) {
			e.preventDefault();

			if ( e.type == 'keyup' && ! window.top.sowbForms.isEnter( e ) ) {
				return;
			}

			// Trigger a click on the existing icon to remove it.
			$$.find('.siteorigin-widget-active').trigger( 'click' );
		} );

		var searchIcons = function() {
			var q = $search.val().toLowerCase();
			if ( q === '' ) {
				$is.find('.siteorigin-widget-icon-icons-icon').show();
			} else {
				$iconContainer.addClass( 'loading' );
				$is.find('.siteorigin-widget-icon-icons-icon').each( function() {
					var $$ = $( this ),
						value = $$.attr( 'data-value' );

					value = value.replace( /-/, ' ' );
					if ( value.indexOf( q ) === -1 ) {
						$$.hide();
					} else {
						$$.show();
					}
				} );
				$iconContainer.removeClass( 'loading' );
			}
		};

		$search.on( 'keyup change', searchIcons );

		const renderStylesSelect = function() {
			const $familySelect = $is.find( 'select.siteorigin-widget-icon-family' );
			const family = $familySelect.val();
			const selectedStyle = $is.find( '.siteorigin-widget-icon-family-styles' ).val();

			if ( typeof window.top.soWidgets.icons[ family ] === 'undefined' ) {
				return;
			}

			let $stylesSelect = $is.find( '.siteorigin-widget-icon-family-styles' );
			const iconFamily = window.top.soWidgets.icons[ family ];

			// Check if the selected icon family has associated styles.
			if ( ! iconFamily.hasOwnProperty( 'styles' ) || ! iconFamily.styles ) {
				$stylesSelect.off( 'change', rerenderIcons );
				$stylesSelect.remove();
				return;
			}

			$stylesSelect.off( 'change', rerenderIcons );
			$stylesSelect.remove();

			let options = '';
			for ( const styleClass in iconFamily.styles ) {
				options += '<option value="' + styleClass + '">' + iconFamily.styles[ styleClass ] + '</option>';
			}

			if ( options ) {
				$stylesSelect = $( '<select class="siteorigin-widget-icon-family-styles"></select>' ).append( options );
				$familySelect.after( $stylesSelect );

				// Set the selected style if it exists.
				if ( selectedStyle && iconFamily.styles.hasOwnProperty( selectedStyle ) ) {
					$stylesSelect.val( selectedStyle );
				}
			}

			$stylesSelect.on( 'change', rerenderIcons );
		};

		const rerender = () => {
			renderStylesSelect();
			rerenderIcons();
		}

		const rerenderIcons = () => {
			const $familySelect = $is.find( 'select.siteorigin-widget-icon-family' );
			const family = $familySelect.val();
			const container = $is.find('.siteorigin-widget-icon-icons');

			if ( typeof window.top.soWidgets.icons[ family ] === 'undefined' ) {
				// Font hasn't been loaded yet. Render it after
				// it's finished loading.
				fetchIconFamily();
				return;
			}

			container.empty();

			const iconFamily = window.top.soWidgets.icons[ family ];
			const icons = iconFamily.icons;
			let style;
			if ( iconFamily.hasOwnProperty( 'styles' ) && iconFamily.styles ) {
				style = $is.find( '.siteorigin-widget-icon-family-styles' ).val();
			}

			for ( var i in icons ) {
				var iconData = icons[ i ];
				var unicode = iconData.hasOwnProperty( 'unicode' ) ? iconData.unicode : iconData;

				if ( iconData.hasOwnProperty( 'styles' ) && iconData.styles.indexOf( style ) === -1 ) {
					continue;
				}

				var familyStyle = 'sow-icon-' + family + ( style ? ' ' + style : '' );
				var familyValue = family + ( style ? '-' + style : '' ) + '-' + i;

				var $icon = $( '<div data-sow-icon="' + unicode + '"></div>' )
					.attr( 'data-value', familyValue )
					.addClass( familyStyle )
					.addClass( 'siteorigin-widget-icon-icons-icon' )
					.on( 'click keyup', function( e ) {
						if ( e.type == 'keyup' && ! window.top.sowbForms.isEnter( e ) ) {
							return;
						}

						var $$ = $( this );

						if ( $$.hasClass( 'siteorigin-widget-active' ) ) {
							// This is being unselected.
							$$.removeClass( 'siteorigin-widget-active' );
							$v.val( '' );

							// Hide the button icon.
							$b.find( 'span' ).hide();

							$remove.hide();
						} else {
							// This is being selected.
							container.find( '.siteorigin-widget-icon-icons-icon' ).removeClass( 'siteorigin-widget-active' );
							$$.addClass( 'siteorigin-widget-active' );
							$v.val( $$.data( 'value' ) );

							// Also add this to the button.
							$b.find( 'span' )
								.show()
								.attr( 'data-sow-icon', $$.attr( 'data-sow-icon' ) )
								.attr( 'class', '' )
								.addClass( familyStyle );

							$remove.show();
						}

						$v.trigger( 'change', { isRendering: true } );

						// Hide the icon selector.
						$is.slideUp();
					} );

				container.append( $icon );

				if ( $v.val() === familyValue ) {
					// Add selected icon to the button.
					$b.find( 'span' )
					.show()
					.attr( 'data-sow-icon', $icon.attr( 'data-sow-icon' ) )
					.attr( 'class', '' )
					.addClass( familyStyle );
					$icon.addClass( 'siteorigin-widget-active' );
				}
			}

			// Move a selected item to the first position.
			container.prepend( container.find('.siteorigin-widget-active') );

			searchIcons();
		};

		const addStylesheet = ( family, uri ) => {
			if ( $( `#siteorigin-widget-font-${ family }` ).length ) {
				return;
			}

			$( "<link rel='stylesheet' type='text/css' />" )
				.attr('id', `siteorigin-widget-font-${ family }`)
				.attr('href', encodeURI( uri ) )
				.appendTo( 'head' );
		};

		const fetchIconFamily = () => {
			// Fetch the family icons from the server if needed.
			const family = $is.find( 'select.siteorigin-widget-icon-family' ).val();

			if (
				typeof family === 'undefined' ||
				family === ''
			) {
				return;
			}

			if ( typeof window.top.soWidgets.icons[ family ] !== 'undefined' ) {
				rerender();
				return;
			}

			const selectedEl = $is.find( 'select.siteorigin-widget-icon-family option[value="' + family + '"]' );

			// Was this icon added using the `icon_callback`?
			// If so, we can skip the AJAX request.
			if ( selectedEl.attr( 'data-icons' ) ) {
				const icons = JSON.parse( selectedEl.attr( 'data-icons' ) );
				window.top.soWidgets.icons[ family ] = icons;

				addStylesheet( family, icons.style_uri );

				rerender();
				return;
			}

			$iconContainer.addClass( 'loading' );

			$.getJSON(
				window.top.soWidgets.ajaxurl,
				{
					'action' : 'siteorigin_widgets_get_icons',
					'family' :  family,
				},
				( data ) => {
					window.top.soWidgets.icons[ family ] = data;

					addStylesheet( family, data.style_uri );

					rerender();
					$iconContainer.removeClass( 'loading' );
				}
			);
		};
		fetchIconFamily();

		$is.find( 'select.siteorigin-widget-icon-family' ).on( 'change', function() {
			$is.find( '.siteorigin-widget-icon-icons' ).empty();
			rerender();
		} );

		$v.on( 'change', function( event, data ) {
			if ( ! ( data && data.isRendering ) ) {
				rerender();
			}
		} );
	}

	// If the current page isn't the site editor, set up the Icon field now.
	if (
		window.top === window.self &&
		(
			typeof pagenow === 'string' &&
			pagenow !== 'site-editor'
		)
	) {
		$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-icon', setupIconField );
	}

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			ensureIconFieldsInitialized();
		}
	} );

	const initializeIconFields = () => {
		let found = false;
		let pending = false;

		$( '.siteorigin-widget-field-type-icon' ).each( function() {
			found = true;
			if ( ! $( this ).attr( 'data-initialized' ) ) {
				pending = true;
				setupIconField.call( this );
			}
		} );

		return {
			found,
			pending,
		};
	};

	const ensureIconFieldsInitialized = ( attempts = 20 ) => {
		const { found, pending } = initializeIconFields();

		if ( attempts <= 0 ) {
			return;
		}

		const schedule = typeof window.requestAnimationFrame === 'function'
			? window.requestAnimationFrame
			: ( callback ) => setTimeout( callback, 0 );

		if ( ! found || pending ) {
			schedule( () => ensureIconFieldsInitialized( attempts - 1 ) );
		}
	};

	const initWhenReady = () => ensureIconFieldsInitialized();

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initWhenReady );
	} else {
		initWhenReady();
	}

	if ( window.top !== window.self ) {
		const runIframeInitialization = () => ensureIconFieldsInitialized();

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', runIframeInitialization );
		} else {
			runIframeInitialization();
		}
	}

	const shouldObserveIconFields = () => {
		if ( window.top === window.self ) {
			return typeof pagenow === 'string' && pagenow === 'site-editor';
		}

		return true;
	};

	if ( shouldObserveIconFields() && typeof MutationObserver !== 'undefined' ) {
		const observer = new MutationObserver( ( mutations ) => {
			const processNode = ( node ) => {
				if ( node.nodeType === 11 ) {
					Array.from( node.childNodes ).forEach( processNode );
					return;
				}

				if ( node.nodeType !== 1 ) {
					return;
				}

				const $node = $( node );

				if ( $node.is( '.siteorigin-widget-field-type-icon' ) ) {
					setupIconField.call( node );
				}

				$node.find( '.siteorigin-widget-field-type-icon' ).each( function() {
					setupIconField.call( this );
				} );
			};

			mutations.forEach( ( mutation ) => {
				mutation.addedNodes.forEach( processNode );
			} );
		} );

		const startObserving = () => {
			if ( ! document.body ) {
				return;
			}

			observer.observe( document.body, {
				childList: true,
				subtree: true,
			} );
		};

		if ( document.readyState === 'loading' ) {
			document.addEventListener( 'DOMContentLoaded', startObserving );
		} else {
			startObserving();
		}
	}
} )( jQuery );
