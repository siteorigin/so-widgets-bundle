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

		const editor = window.tinymce.get( editorId );
		// Add the selected media to the TinyMCE editor.
		mediaFrame.on( 'select', () => {
			const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			editor.insertContent( `<img src="${ attachment.url }" alt="${ attachment.alt }" />` );
		} );

		// Change the mediaFrameOpen flag when the media frame is closed.
		mediaFrame.on( 'close', () => {
			mediaFrameOpen = false;
		} );

		mediaFrame.open();
	};

	/**
	 * Sets up a TinyMCE field within a widget form.
	 * Handles initialization of the TinyMCE editor, event binding, and UI setup.
	 *
	 * @param {jQuery} $field - jQuery object of the field container element.
	 */
	const setupTinyMCEField = function( $field ) {
		if ( ! window.frameElement && $field.data( 'initialized' ) ) {
			return;
		}

		const wpEditor = wp.oldEditor ? wp.oldEditor : wp.editor;
		if ( wpEditor && wpEditor.hasOwnProperty( 'autop' ) ) {
			wp.editor.autop = wpEditor.autop;
			wp.editor.removep = wpEditor.removep;
			wp.editor.initialize = wpEditor.initialize
		}

		const currentContext = window.frameElement ? window.parent : window.top;
		const $container = $field.find( '.siteorigin-widget-tinymce-container' );
		const settings = $container.data( 'editorSettings' );

		if (
			currentContext.window.tinyMCEPreInit.mceInit &&
			currentContext.window.tinyMCEPreInit.mceInit.hasOwnProperty( 'content' )
		) {
			const mainContentSettings = currentContext.window.tinyMCEPreInit.mceInit['content'];
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
		let id = $textarea.data( 'tinymce-id' );
		if ( ! id ) {
			id = $textarea.attr( 'id' ) + Math.floor( Math.random() * 1000 );
			$textarea.data( 'tinymce-id', id );
			$textarea.attr( 'id', id );
		}

		const setupEditor = function( editor ) {
			editor.on( 'change', function() {
				const ed = window.tinymce.get( id );
				ed.save();
				$textarea.trigger( 'change' );
			} );

			if ( $wpautopToggleField ) {
				$wpautopToggleField.off( 'change' );
				$wpautopToggleField.on( 'change', function() {
					wp.editor.remove( id );
					settings.tinymce.wpautop = $wpautopToggleField.is( ':checked' );
					wp.editor.initialize( id, settings );
				} );
			}
		};

		if ( settings.tinymce ) {
			settings.tinymce = $.extend( {}, settings.tinymce, {
				selector: '#' + id,
				setup: function ( editor ) {
					if ( window.frameElement ) {
						// Fix code tab label in the Site Editor.
						editor.on( 'init', () => {
							const textTab = document.querySelector( `#${id}-html` );
							if ( textTab ) {
								textTab.innerHTML = wp.i18n.__( 'Code' );
							}
						} );
					}
				},
			} );
		}

		$( document ).on( 'wp-before-tinymce-init', function( event, init ) {
			if ( init.selector === settings.tinymce.selector ) {
				const mediaButtons = $container.data( 'mediaButtons' );
				if ( typeof mediaButtons != 'undefined' && $field.find( '.wp-media-buttons' ).length === 0 ) {
					$field.find( '.wp-editor-tabs' ).before( mediaButtons.html );
				}

				const addMediaButton = $field.find( '.add_media' );
				if ( addMediaButton.length > 0 ) {
					const $textarea = $container.find( 'textarea' );
					const editorId = $textarea.data( 'tinymce-id' );
					addMediaButton.attr( 'data-editor', editorId );

					if ( window.frameElement ) {
						addMediaButton
							.removeClass( 'insert-media' )
							.addClass('siteorigin-widget-tinymce-add-media')
							.on( 'click', () => {
								siteEditorAddMediaOverride( editorId );
							} );
					}
				}


			}
		} );

		$( document ).on( 'tinymce-editor-setup', function() {
			const $wpEditorWrap = $field.find( '.wp-editor-wrap' );
			if ( $wpEditorWrap.length > 0 && ! $wpEditorWrap.hasClass( settings.selectedEditor + '-active' ) ) {
				setTimeout( function() {
					window.switchEditors.go( id );
				}, 10 );
			}
		} );

		wpEditor.remove( id );
		if ( currentContext.window.tinymce ) {
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
				}
			}, 500 );
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

		$field.data( 'initialized', true );
	};

	/**
	 * Initializes a TinyMCE field within a widget form
	 * Handles cases where the field is within a repeater item
	 * Sets up the TinyMCE editor when the field is visible
	 */
	const setupTinyMCEFieldInitializer = function() {
		const $field = $( this );
		const $parentRepeaterItem = $field.closest( '.siteorigin-widget-field-repeater-item-form' );

		if ( $parentRepeaterItem.length > 0 ) {
			if ( $parentRepeaterItem.is( ':visible' ) ) {
				setupTinyMCEField( $field );
			} else {
				$parentRepeaterItem.on( 'slideToggleOpenComplete', function onSlideToggleComplete() {
					if ( $parentRepeaterItem.is( ':visible' ) ) {
						setupTinyMCEField( $field );
						$parentRepeaterItem.off( 'slideToggleOpenComplete' );
					}
				} );
			}
		} else {
			setupTinyMCEField( $field );
		}
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
			$( this ).data( 'initialized', null );
			setupTinyMCEField( $( this ) );
		} );
	};

	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-tinymce', setupTinyMCEFieldInitializer );
	$( document ).on( 'sortstop', sortStopEvent );

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-tinymce' ).each( function() {
				$( this ).data( 'initialized', null );
				setupTinyMCEFieldInitializer.call( this );
			} );

			if ( ! window.wp.editor.getDefaultSettings ) {
				window.wp.editor.getDefaultSettings = window.parent.wp.editor.getDefaultSettings;
			}

			// Check if the sortstop event is already bound.
			if ( ! $( window.top.document ).data( 'sortstop-bound' ) ) {
				$( window.top.document ).data( 'sortstop-bound', true );
				$( window.top.document ).on( 'sortstop', sortStopEvent );
			}
		}
	} );

} )( jQuery );
