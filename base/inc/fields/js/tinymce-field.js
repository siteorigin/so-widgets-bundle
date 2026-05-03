/* global tinymce, switchEditors */

( function( $ ) {

	let mediaFrameOpen = false;
	/**
	 * Opens the WordPress media library for TinyMCE editors in an iframe context.
	 *
	 * We manually handle this rather than relying on the default WordPress
	 * media library behavior because the default behavior does not work
	 * correctly in the Site Editor context.
	 *
	 * This function prevents multiple media frames from opening using
	 * the `mediaFrameOpen` flag.
	 *
	 * @param {string} editorId - The ID of the TinyMCE editor instance.
	 */
	const siteEditorAddMediaOverride = function( editorId ) {
		if ( mediaFrameOpen ) {
			return;
		}
		mediaFrameOpen = true;

		// Open the media frame in the top window context.
		const mediaFrame = window.top.wp.media( {
			title: 'Select or Upload Media',
			button: {
				text: 'Insert Media'
			},
			multiple: false
		} );

		// Add the selected media to the TinyMCE editor.
		mediaFrame.on( 'select', () => {
			const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			const editor = window.tinymce.get( editorId );

			editor.insertContent( `<img src="${ attachment.url }" alt="${ attachment.alt }" />` );
			editor.save();
			editor.fire( 'change' );
		} );

		// Change the mediaFrameOpen flag when the media frame is closed.
		mediaFrame.on( 'close', () => {
			mediaFrameOpen = false;
		} );

		mediaFrame.open();
	};

	/**
	 * Clears any pending TinyMCE setup state from a field.
	 *
	 * @param {jQuery} $field - jQuery object of the field container element.
	 */
	const clearTinyMCEFieldPendingSetup = function( $field ) {
		const visibilityPoll = $field.data( 'sowb-tinymce-visibility-poll' );
		if ( visibilityPoll ) {
			clearInterval( visibilityPoll );
			$field.removeData( 'sowb-tinymce-visibility-poll' );
		}

		$field.removeData( 'sowb-pre-init-bound' );
		$field.removeAttr( 'data-pre-init' );
	};

	/**
	 * Removes an existing TinyMCE editor instance when the runtime exposes a
	 * compatible teardown API.
	 *
	 * WordPress can expose `wp.oldEditor` in iframe contexts for legacy
	 * compatibility, but that object does not always implement `remove()`.
	 * Prefer the active editor API when available and fall back to the
	 * TinyMCE instance directly.
	 *
	 * @param {Object} wpEditor - The resolved WordPress editor API object.
	 * @param {string} id - The editor textarea ID.
	 */
	const removeTinyMCEEditor = function( wpEditor, id ) {
		if ( wpEditor && typeof wpEditor.remove === 'function' ) {
			wpEditor.remove( id );
			return;
		}

		if ( window.wp.editor && typeof window.wp.editor.remove === 'function' ) {
			window.wp.editor.remove( id );
			return;
		}

		if ( window.tinymce ) {
			const editor = window.tinymce.get( id );
			if ( editor && typeof editor.remove === 'function' ) {
				editor.remove();
			}
		}
	};

	/**
	 * Sets up a TinyMCE field within a widget form.
	 * Handles initialization of the TinyMCE editor, event binding, and UI setup.
	 *
	 * @param {jQuery} $field - jQuery object of the field container element.
	 */
	const setupTinyMCEField = function( $field ) {
		if (
			$field.attr( 'data-initialized' ) &&
			$field.find( '.wp-editor-wrap, .mce-tinymce' ).length > 0
		) {
			return;
		}

		clearTinyMCEFieldPendingSetup( $field );
		$field.attr( 'data-initialized', true );

		// If this is in an iframe, copy necessary globals from the parent window.
		if ( frameElement && typeof window.tinyMCEPreInit === 'undefined' ) {
			window.tinyMCEPreInit = window.top.tinyMCEPreInit;
		}

		const wpEditor = wp.oldEditor ? wp.oldEditor : wp.editor;
		if ( wpEditor && wpEditor.hasOwnProperty( 'autop' ) ) {
			wp.editor.autop = wpEditor.autop;
			wp.editor.removep = wpEditor.removep;
			wp.editor.initialize = wpEditor.initialize
		}

		const $container = $field.find( '.siteorigin-widget-tinymce-container' );
		const settings = $container.data( 'editorSettings' );

		if (
			window.top.tinyMCEPreInit.mceInit &&
			window.top.tinyMCEPreInit.mceInit.hasOwnProperty( 'content' )
		) {
			const mainContentSettings = window.top.tinyMCEPreInit.mceInit['content'];
			if ( mainContentSettings.hasOwnProperty( 'content_css' ) && mainContentSettings.content_css ) {
				const mainContentCss = mainContentSettings.content_css.split( ',' );
				if ( settings.tinymce.hasOwnProperty( 'content_css' ) && settings.tinymce.content_css ) {
					for ( let i = 0; i < mainContentCss.length; i++ ) {
						const cssUrl = mainContentCss[ i ];
						if ( settings.tinymce.content_css.indexOf( cssUrl ) === -1 ) {
							settings.tinymce.content_css += ',' + cssUrl;
						}
					}
				} else {
					settings.tinymce.content_css = mainContentCss;
				}
			}
		}

		let $wpautopToggleField;
		if ( settings.wpautopToggleField ) {
			const $widgetForm = $container.closest( '.siteorigin-widget-form' );

			$wpautopToggleField = $widgetForm.find( settings.wpautopToggleField );

			settings.tinymce.wpautop = $wpautopToggleField.is( ':checked' );
		}

		const $textarea = $container.find( 'textarea' );
		// Prevent potential id overlap by appending the textarea field with a random id.
		let id = $textarea.attr( 'data-tinymce-id' ) || $textarea.data( 'tinymce-id' );
		if ( ! id ) {
			id = $textarea.attr( 'id' ) + Math.floor( Math.random() * 1000 );
		}

		$textarea
			.data( 'tinymce-id', id )
			.attr( 'data-tinymce-id', id )
			.attr( 'id', id );

		$( window.document ).one( 'wp-before-tinymce-init', function( event, init ) {
			if ( init.selector !== settings.tinymce.selector ) {
				return;
			}
			const mediaButtons = $container.data( 'mediaButtons' );
			if (
				typeof mediaButtons != 'undefined' &&
				$field.find( '.wp-media-buttons' ).length === 0
			) {
				$field.find( '.wp-editor-tabs' ).before( mediaButtons.html );
			}

			const addMediaButton = $field.find( '.add_media' );
			if ( addMediaButton.length > 0 ) {
				const $textarea = $container.find( 'textarea' );
				const editorId = $textarea.data( 'tinymce-id' );
				addMediaButton.attr( 'data-editor', editorId );

				if ( window.frameElement ) {
					addMediaButton
						.removeClass( 'insert-media add_media' )
						.addClass( 'siteorigin-widget-tinymce-add-media' )
						.on( 'click', () => {
							siteEditorAddMediaOverride( editorId );
						} );
				}
			}
		} );

		$( window.top.document ).one( 'tinymce-editor-setup', function() {
			const $wpEditorWrap = $field.find( '.wp-editor-wrap' );
			if ( $wpEditorWrap.length > 0 && ! $wpEditorWrap.hasClass( settings.selectedEditor + '-active' ) ) {
				setTimeout( function() {
					window.switchEditors.go( id );
				}, 10 );
			}
		} );

		if ( settings.tinymce ) {
			const setupEditor = function( editor ) {
				editor.on( 'change', function() {
					const ed = window.tinymce.get( id );
					ed.save();
					$textarea.trigger( 'change' );
				} );

				if ( $wpautopToggleField ) {
					$wpautopToggleField.off( 'change' );
					$wpautopToggleField.on( 'change', function() {
						removeTinyMCEEditor( window.wp.editor, id );
						settings.tinymce.wpautop = $wpautopToggleField.is( ':checked' );
						window.wp.editor.initialize( id, settings );
					} );
				}
			};

			settings.tinymce = $.extend( {}, settings.tinymce, {
				selector: '#' + id,
				setup: function ( editor ) {
					if ( window.frameElement ) {
						// Fix code tab label in the Site Editor.
						editor.on( 'init', () => {
							const textTab = document.querySelector( `#${id}-html` );
							if ( textTab ) {
								textTab.innerHTML = window.top.wp.i18n.__( 'Code' );
							}
						} );
					}

					setupEditor( editor );
				},
			} );
		}

		removeTinyMCEEditor( wpEditor, id );
		if ( window.tinymce ) {
			window.tinymce.EditorManager.overrideDefaults( { base_url: settings.baseURL, suffix: settings.suffix } );
		}

		// Wait for textarea to be visible before initialization.
		if ( $textarea.is( ':visible' ) ) {
			wpEditor.initialize( id, settings );
		} else {
			const intervalId = setInterval( function() {
				if ( $textarea.is( ':visible' ) ) {
					wpEditor.initialize( id, settings );
					clearInterval( intervalId );
					$field.removeData( 'sowb-tinymce-visibility-poll' );
				}
			}, 500 );

			$field.data( 'sowb-tinymce-visibility-poll', intervalId );
		}

		$field.on( 'click', function( event ) {
			const $target = $( event.target );
			if ( ! $target.is( '.wp-switch-editor' ) ) {
				return;
			}

			const mode = $target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';

			if ( mode === 'tmce' ) {
				const editor = window.tinymce.get( id );
				// Quick bit of sanitization to prevent catastrophic backtracking in TinyMCE HTML parser regex.
				if ( editor !== null ) {
					let content = $textarea.val();
					if ( content.search( '<' ) !== -1 && content.search( '>' ) === -1 ) {
						content = content.replace( /</g, '' );
						$textarea.val( content );
					}
					editor.setContent( window.switchEditors.wpautop( content ) );
				}
			}
			settings.selectedEditor = mode;

			$field.find( 'textarea.wp-editor-area' ).css(
				'visibility', mode === 'tmce' ? 'hidden' : 'visible'
			);


			$field.find( '.siteorigin-widget-tinymce-selected-editor' ).val( mode );
		} );
	};

	/**
	 * Initializes a TinyMCE field within a widget form.
	 *
	 * This function handles the initialization of TinyMCE editors for
	 * fields in widget forms. If the field is visible, it initializes
	 * the editor immediately. Otherwise, it waits for the field to
	 * become visible before setting up the editor.
	 */
	const setupTinyMCEFieldInitializer = function() {
		const $field = $( this );

		if ( $field.attr( 'data-pre-init' ) && ! $field.data( 'sowb-pre-init-bound' ) ) {
			$field.removeAttr( 'data-pre-init' );
		}

		// If the field is visible, initialize the TinyMCE editor immediately.
		if ( $field.is( ':visible' ) ) {
			setupTinyMCEField( $field );
			return;
		}

		if ( $field.data( 'sowb-pre-init-bound' ) ) {
			return;
		}

		// Mark the field for initialization and wait for it to become visible.
		// Once visible, the 'sowsetupformfield' event triggers the editor setup.
		$field
			.data( 'sowb-pre-init-bound', true )
			.one( 'sowsetupformfield', () => {
				setupTinyMCEField( $field );
			} );
	};

	/**
	 * Handles reinitializing TinyMCE fields after sorting.
	 * Ensures TinyMCE editors work properly after being moved in the DOM.
	 *
	 * @param {Event} event - jQuery event object.
	 * @param {Object} ui - jQuery UI sortable object.
	 */
	const sortStopEvent = function( event, ui ) {
		let $form;

		if ( ui.item.is( '.siteorigin-widget-field-repeater-item' ) ) {
			$form = ui.item.find( '> .siteorigin-widget-field-repeater-item-form' );
		} else {
			$form = ui.item.find( '.siteorigin-widget-form' );
		}

		$form.find( '.siteorigin-widget-field-type-tinymce' ).each( function() {
			const $field = $( this );
			clearTinyMCEFieldPendingSetup( $field );
			$field.removeAttr( 'data-initialized' );
			setupTinyMCEField( $field );
		} );
	};

	/**
	 * Initializes TinyMCE fields inside the Site Editor canvas iframe.
	 *
	 * The parent editor can post this request before iframe field scripts have
	 * finished loading, so the iframe also calls this once its own script is
	 * ready.
	 */
	const setupSiteEditorTinyMCEFields = function() {
		if (
			window.wp &&
			window.wp.editor &&
			! window.wp.editor.getDefaultSettings &&
			window.top.wp &&
			window.top.wp.editor
		) {
			window.wp.editor.getDefaultSettings = window.top.wp.editor.getDefaultSettings;
		}

		$( '.siteorigin-widget-field-type-tinymce' ).each( function() {
			setupTinyMCEFieldInitializer.call( this );
		} );

		// Check if the sortstop event is already bound.
		if ( ! $( window.top.document ).data( 'sortstop-bound' ) ) {
			$( window.top.document ).data( 'sortstop-bound', true );
			$( window.top.document ).on( 'sortstop', sortStopEvent );
		}
	};


	/// If the current page isn't the site editor, set up the TinyMCE field now.
	if (
		window.top === window.self &&
		(
			typeof pagenow === 'string' &&
			pagenow !== 'site-editor'
		)
	) {
		$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-tinymce', setupTinyMCEFieldInitializer );
	}

	$( document ).on( 'sortstop', sortStopEvent );

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			setupSiteEditorTinyMCEFields();
		}
	} );

	if ( window.frameElement ) {
		$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-tinymce', setupTinyMCEFieldInitializer );
		$( setupSiteEditorTinyMCEFields );
	}

} )( jQuery );
