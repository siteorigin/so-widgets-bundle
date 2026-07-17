/* global tinymce, switchEditors */

( function( $ ) {

	if ( window.sowbTinyMCEFieldScriptLoaded ) {
		return;
	}

	window.sowbTinyMCEFieldScriptLoaded = true;
	window.sowbTinyMCEFieldScriptLoadedAt = Date.now();

	let mediaFrameOpen = false;
	const sowbTinyMCEEventNamespace = '.sowTinymce';
	let _tinymceDomIdSeq = 0;
	let _tinymcePreInitSeq = 0;

	/**
	 * Tracks in-flight TinyMCE editor initializations.
	 *
	 * Keyed by editor ID. Each entry holds `{ promise, resolve }` where `promise`
	 * resolves (with no value) once the editor's `init` event has fired or the
	 * 5 s safety timeout has elapsed. Entries are removed on resolution so the
	 * map never grows unboundedly.
	 *
	 * Consumed by `window.sowbGetTinyMCEInitPromise`, which allows external code
	 * (e.g. the save-bridge TinyMCE flusher) to await full editor readiness
	 * before calling `editor.save()`.
	 */
	const _tinymceInitPending = {};

	/**
	 * Filters a jQuery collection to only those TinyMCE fields that are eligible
	 * for editor initialization.
	 *
	 * Excludes fields that live inside a repeater item template
	 * (`.siteorigin-widget-field-repeater-item-html`) and fields whose textarea ID
	 * contains `_id_`, which indicates a placeholder that has not yet been cloned
	 * into a real repeater row.
	 *
	 * @param {jQuery|Array} $fields - jQuery collection or array of field elements.
	 *
	 * @returns {jQuery} Filtered jQuery collection of eligible TinyMCE field elements.
	 */
	const getEligibleTinyMCEFields = function( $fields ) {
		const $collection = $fields && $fields.jquery ?
			$fields :
			$( $fields || [] );

		return $collection.filter( function() {
			const $field = $( this );
			const $textarea = $field.find( '.siteorigin-widget-tinymce-container textarea' ).first();
			const textareaId = $textarea.attr( 'id' ) || '';

			if ( $field.closest( '.siteorigin-widget-field-repeater-item-html' ).length ) {
				return false;
			}

			if ( textareaId.indexOf( '_id_' ) !== -1 ) {
				return false;
			}

			return true;
		} );
	};

	/**
	 * Selects the best matching widget form from a set of candidates.
	 *
	 * Scores each form on multiple signals and returns the highest-scoring one.
	 * Scoring weights (highest to lowest priority):
	 *   - Connected to the live document       +10000
	 *   - Visible (not hidden by CSS)          +5000
	 *   - Hidden by aria-hidden               -2500
	 *   - Inside a repeater item template     -5000
	 *   - TinyMCE field count                 ×100 per field
	 *   - Total field count                   +1 per field
	 *   - DOM order tie-breaker               -index/1000 (earlier wins)
	 *
	 * @param {jQuery} $forms - jQuery collection of candidate form elements.
	 *
	 * @returns {jQuery} Single-element jQuery collection containing the best form,
	 *                   or an empty jQuery object if no forms are provided.
	 */
	const selectBestFormInstance = function( $forms ) {
		if ( ! $forms || ! $forms.length ) {
			return $( [] );
		}

		if ( $forms.length === 1 ) {
			return $forms;
		}

		let bestScore = Number.NEGATIVE_INFINITY;
		let bestForm = null;

		$forms.each( function( index ) {
			const $form = $( this );
			const fieldCount = $form.find( '.siteorigin-widget-field' ).length;
			const tinymceFieldCount = $form.find( '.siteorigin-widget-field-type-tinymce' ).length;
			const visible = $form.is( ':visible' );
			const hiddenByAria = $form.is( '[aria-hidden="true"]' ) || $form.closest( '[aria-hidden="true"]' ).length > 0;
			// Use ownerDocument rather than the closure `document` so this signal is
			// accurate whether selectBestFormInstance is called from the parent window
			// (resolvePostMessageForms path) or from inside the iframe.
			const connected = ( this.ownerDocument && this.ownerDocument.documentElement )
				? this.ownerDocument.documentElement.contains( this )
				: false;
			const inRepeaterTemplate = $form.closest( '.siteorigin-widget-field-repeater-item-html' ).length > 0;

			const score =
				( connected ? 10000 : 0 ) +
				( visible ? 5000 : 0 ) +
				( hiddenByAria ? -2500 : 0 ) +
				( inRepeaterTemplate ? -5000 : 0 ) +
				( tinymceFieldCount * 100 ) +
				fieldCount -
				( index / 1000 );

			if ( score > bestScore ) {
				bestScore = score;
				bestForm = this;
			}
		} );

		return bestForm ? $( bestForm ) : $forms.first();
	};

	/**
	 * Returns all eligible TinyMCE fields within a set of widget forms.
	 *
	 * When more than one form is supplied, delegates to `selectBestFormInstance`
	 * to pick the most appropriate one before searching for fields. Within the
	 * resolved form, visible forms are preferred over hidden ones.
	 *
	 * @param {jQuery} $forms - jQuery collection of widget form elements.
	 *
	 * @returns {jQuery} jQuery collection of eligible TinyMCE field elements.
	 */
	const getTinyMCEFieldsFromForms = function( $forms ) {
		if ( ! $forms || ! $forms.length ) {
			return $( [] );
		}

		// When multiple forms are present, delegate to selectBestFormInstance which
		// scores on connected/visible/aria-hidden/repeater-template signals.
		const $scopedForms = $forms.length > 1 ?
			selectBestFormInstance( $forms ) :
			$forms;

		const $visibleForms = $scopedForms.filter( ':visible' );
		const $activeForms = $visibleForms.length ? $visibleForms : $scopedForms;

		return getEligibleTinyMCEFields(
			$activeForms.find( '.siteorigin-widget-field-type-tinymce' )
		);
	};

	/**
	 * Resolves the target widget form(s) from a `sowbBlockFormInit` postMessage payload.
	 *
	 * Queries `messageData.formSelector` against this iframe's document and, when
	 * more than one form matches, delegates to `selectBestFormInstance` to pick the
	 * best candidate based on visibility, DOM connectivity, and field count.
	 *
	 * Note: block-editor selection attributes (`is-selected`, `data-block`) live on
	 * elements in the parent document and are never present in the iframe DOM, so no
	 * attempt is made to narrow by clientId here.
	 *
	 * @param {Object} messageData              - The postMessage data object.
	 * @param {string} messageData.formSelector - CSS selector targeting the form(s).
	 *
	 * @returns {jQuery} jQuery collection of the resolved form element(s).
	 */
	const resolvePostMessageForms = function( messageData ) {
		if ( ! messageData || ! messageData.formSelector ) {
			return $( [] );
		}

		// formSelector targets elements in this iframe document. Block-editor
		// attributes such as `is-selected` and `data-block` belong to the parent
		// document's block list and are never present here, so we go straight to
		// selectBestFormInstance when more than one form matches.
		let $forms = $( messageData.formSelector );

		if ( $forms.length > 1 ) {
			$forms = selectBestFormInstance( $forms );
		}

		return $forms;
	};

	/**
	 * Sanitizes a string for use as a segment of a TinyMCE editor DOM ID.
	 *
	 * Replaces bracket notation (e.g. `[0]`) with hyphens, strips all characters
	 * that are not alphanumeric, underscores, or hyphens, collapses consecutive
	 * hyphens, and trims leading/trailing hyphens.
	 *
	 * @param {string} value - The raw string to sanitize.
	 *
	 * @returns {string} The sanitized string, or an empty string if the input is
	 *                   not a non-empty string.
	 */
	const sanitizeIdSegment = function( value ) {
		if ( typeof value !== 'string' || value.length === 0 ) {
			return '';
		}

		return value
			.replace( /\[[^\]]*\]/g, '-' )
			.replace( /[^A-Za-z0-9_-]+/g, '-' )
			.replace( /-+/g, '-' )
			.replace( /^-+|-+$/g, '' );
	};

	/**
	 * Resolves the best available WordPress editor API object.
	 *
	 * Delegates to `window.sowbResolveWpEditor` (defined in widget-block.js) when
	 * available so the guard logic lives in one place. Falls back to an inline
	 * implementation for contexts where widget-block.js has not yet been evaluated
	 * (e.g. classic Page Builder or widgets screen).
	 *
	 * Prefers `wp.oldEditor` (present in iframe contexts for legacy compatibility)
	 * over `wp.editor`, and requires the resolved object to expose `initialize()`.
	 *
	 * @returns {Object|null} The resolved editor API, or null if unavailable.
	 */
	const resolveWpEditor = function() {
		if ( typeof window.sowbResolveWpEditor === 'function' ) {
			return window.sowbResolveWpEditor();
		}
		if ( ! window.wp ) {
			return null;
		}
		const candidate = window.wp.oldEditor && typeof window.wp.oldEditor.initialize === 'function'
			? window.wp.oldEditor
			: ( window.wp.editor || null );
		return candidate && typeof candidate.initialize === 'function' ? candidate : null;
	};

	/**
	 * Checks whether an element is still attached to its owning document.
	 *
	 * @param {Element|null} element - Element to inspect.
	 *
	 * @returns {boolean} True when the element is connected.
	 */
	const isConnectedElement = function( element ) {
		return !! (
			element &&
			element.ownerDocument &&
			element.ownerDocument.documentElement &&
			element.ownerDocument.documentElement.contains( element )
		);
	};

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
			const editor = window.tinymce ? window.tinymce.get( editorId ) : null;

			if ( editor ) {
				editor.insertContent( `<img src="${ attachment.url }" alt="${ attachment.alt }" />` );
				editor.save();
				editor.fire( 'change' );
			}
		} );

		// Change the mediaFrameOpen flag when the media frame is closed.
		mediaFrame.on( 'close', () => {
			mediaFrameOpen = false;
		} );

		mediaFrame.open();
	};

	/**
	 * Clears all pending TinyMCE setup state from a field element.
	 *
	 * Cancels any in-progress timers and intervals attached to the field and
	 * removes the associated jQuery data keys:
	 *   - `sowb-pre-init-visibility-poll`  — pre-init visibility interval
	 *   - `sowb-tinymce-visibility-poll`   — post-init visibility interval
	 *   - `sowb-tinymce-init-timeout`      — 5 s safety unlock timeout
	 *   - `sowb-tinymce-initializing`      — initializing lock flag
	 *   - `sowb-tinymce-initializing-id`   — ID recorded during initialization
	 *   - `sowb-pre-init-bound`            — pre-init event listener flag
	 *   - `sowb-pre-init-namespace`        — namespaced event suffix for the pre-init listener/poll pair
	 *   - `data-pre-init` attribute
	 *
	 * @param {jQuery} $field - jQuery object of the field container element.
	 */
	const clearTinyMCEFieldPendingSetup = function( $field ) {
		const preInitPoll = $field.data( 'sowb-pre-init-visibility-poll' );
		if ( preInitPoll ) {
			clearInterval( preInitPoll );
			$field.removeData( 'sowb-pre-init-visibility-poll' );
		}

		const visibilityPoll = $field.data( 'sowb-tinymce-visibility-poll' );
		if ( visibilityPoll ) {
			clearInterval( visibilityPoll );
			$field.removeData( 'sowb-tinymce-visibility-poll' );
		}

		const initTimeout = $field.data( 'sowb-tinymce-init-timeout' );
		if ( initTimeout ) {
			clearTimeout( initTimeout );
			$field.removeData( 'sowb-tinymce-init-timeout' );
		}

		// If an init was in flight, resolve (and remove) its pending promise now.
		// The safety timeout was the only other resolver; cancelling it above without
		// resolving here would leave sowbGetTinyMCEInitPromise() waiting forever.
		const pendingId = $field.data( 'sowb-tinymce-initializing-id' );

		$field.removeData( 'sowb-tinymce-initializing' );
		$field.removeData( 'sowb-tinymce-initializing-id' );

		if ( pendingId && _tinymceInitPending[ pendingId ] ) {
			_tinymceInitPending[ pendingId ].resolve();
		}
		$field.removeData( 'sowb-pre-init-bound' );
		$field.removeAttr( 'data-pre-init' );

		const preInitNamespace = $field.data( 'sowb-pre-init-namespace' );
		if ( preInitNamespace ) {
			$field.off( 'sowsetupformfield' + preInitNamespace );
			$field.removeData( 'sowb-pre-init-namespace' );
		}
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

		if ( window.wp && window.wp.editor && typeof window.wp.editor.remove === 'function' ) {
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
	 * Resolve the textarea and editor id for a TinyMCE field.
	 *
	 * @param {jQuery} $field - jQuery object of the field container element.
	 *
	 * @returns {Object} The textarea and current editor id.
	 */
	const getTinyMCEFieldEditor = function( $field ) {
		const $textarea = $field
			.find( '.siteorigin-widget-tinymce-container textarea.wp-editor-area, .siteorigin-widget-tinymce-container textarea' )
			.first();

		return {
			$textarea,
			id: $textarea.attr( 'data-tinymce-id' ) || $textarea.data( 'tinymce-id' ) || $textarea.attr( 'id' )
		};
	};

	/**
	 * Checks whether a field has a usable editor instance or TinyMCE chrome.
	 *
	 * @param {jQuery} $field - jQuery object of the field container element.
	 * @param {string} id - The editor textarea ID.
	 *
	 * @returns {boolean} True when the TinyMCE field is already healthy.
	 */
	const hasHealthyTinyMCEEditor = function( $field, id ) {
		if ( ! id ) {
			return false;
		}

		const fieldElement = $field.get( 0 );
		if ( ! isConnectedElement( fieldElement ) ) {
			return false;
		}

		if ( window.tinymce && window.tinymce.get( id ) ) {
			const editor = window.tinymce.get( id );
			const editorContainer = editor && typeof editor.getContainer === 'function' ?
				editor.getContainer() :
				null;
			const editorIframe = editor && editor.iframeElement ?
				editor.iframeElement :
				document.getElementById( id + '_ifr' );

			if (
				editorContainer &&
				isConnectedElement( editorContainer ) &&
				fieldElement.contains( editorContainer )
			) {
				return true;
			}

			if (
				editorIframe &&
				isConnectedElement( editorIframe ) &&
				fieldElement.contains( editorIframe ) &&
				editorIframe.contentWindow &&
				editorIframe.contentDocument
			) {
				return true;
			}
		}

		const editorIframe = document.getElementById( id + '_ifr' );
		if (
			editorIframe &&
			isConnectedElement( editorIframe ) &&
			fieldElement.contains( editorIframe ) &&
			editorIframe.contentWindow &&
			editorIframe.contentDocument
		) {
			return true;
		}

		const editorWrap = document.getElementById( 'wp-' + id + '-wrap' );
		if (
			editorWrap &&
			isConnectedElement( editorWrap ) &&
			fieldElement.contains( editorWrap ) &&
			$( editorWrap ).find( '.mce-tinymce, .mce-toolbar-grp' ).length > 0
		) {
			return true;
		}

		return false;
	};

	/**
	 * Removes partial TinyMCE markup while keeping the textarea/content intact.
	 *
	 * @param {jQuery} $field - jQuery object of the field container element.
	 * @param {string} id - The editor textarea ID.
	 */
	const removeStaleTinyMCEFieldState = function( $field, id ) {
		if ( ! id ) {
			return;
		}

		removeTinyMCEEditor( resolveWpEditor(), id );

		const editorWrap = document.getElementById( 'wp-' + id + '-wrap' );
		if ( editorWrap ) {
			const $textarea = $( document.getElementById( id ) );
			if ( $textarea.length ) {
				$( editorWrap ).after( $textarea );
			}
			$( editorWrap ).remove();
		}
	};

	/**
	 * Sets up a TinyMCE editor for a single widget form field.
	 *
	 * Handles the full initialization lifecycle:
	 *   - Skips if an initialization is already in progress.
	 *   - Tears down and replaces any stale editor state if re-initializing.
	 *   - Resolves the WordPress editor API via `resolveWpEditor`; bails early
	 *     if no editor API is available.
	 *   - Assigns or reuses a stable unique ID for the textarea.
	 *   - Listens for `wp-before-tinymce-init` to inject media buttons.
	 *   - Waits for the textarea to become visible before calling
	 *     `wpEditor.initialize`, using a 500 ms interval poll if it is hidden.
	 *   - Sets a 5 s safety timeout to release the initializing lock if the
	 *     TinyMCE `init` event never fires.
	 *
	 * @param {jQuery} $field - jQuery object of the field container element.
	 */
	const setupTinyMCEField = function( $field ) {
		if ( $field.data( 'sowb-tinymce-initializing' ) ) {
			return;
		}

		if ( $field.attr( 'data-initialized' ) ) {
			const initializedEditor = getTinyMCEFieldEditor( $field );

			if ( hasHealthyTinyMCEEditor( $field, initializedEditor.id ) ) {
				return;
			}

			// Tear down stale editor markup. Timers/locks are cleared unconditionally
			// by the clearTinyMCEFieldPendingSetup call below.
			removeStaleTinyMCEFieldState( $field, initializedEditor.id );
			$field.removeAttr( 'data-initialized' );
		}

		// Always clear any lingering state from a pre-init visibility poll or
		// a prior partial setup, even when data-initialized was not set.
		clearTinyMCEFieldPendingSetup( $field );

		// If this is in an iframe, copy necessary globals from the parent window.
		if ( frameElement && typeof window.tinyMCEPreInit === 'undefined' ) {
			window.tinyMCEPreInit = window.top.tinyMCEPreInit;
		}

		const wpEditor = resolveWpEditor();
		if ( ! wpEditor ) {
			return;
		}

		// Mark as initialized only after confirming the editor API is available, so a
		// transient null wpEditor does not leave the field stranded with the attr set.
		$field.attr( 'data-initialized', true );

		// In iframe contexts wp.oldEditor is the authoritative API. Copy its text-processing
		// methods onto wp.editor so both references stay in sync.
		if ( window.wp.oldEditor && window.wp.oldEditor.hasOwnProperty( 'autop' ) ) {
			window.wp.editor.autop = window.wp.oldEditor.autop;
			window.wp.editor.removep = window.wp.oldEditor.removep;
			window.wp.editor.initialize = window.wp.oldEditor.initialize;
		}

		const $container = $field.find( '.siteorigin-widget-tinymce-container' );
		const settings = $.extend( true, {}, $container.data( 'editorSettings' ) || {} );

		// Two separate invariants gate visual-editor setup:
		//
		// 1. hasTinyMCE — does this field even have a `tinymce` config? Guards the
		//    two places that dereference `settings.tinymce.*` directly (the
		//    content_css merge and the wpautop read). Those only need the settings
		//    object; they do not touch the TinyMCE library.
		//
		// 2. canInitEditor — can we actually initialise a visual editor here? When a
		//    host filters `user_can_richedit()` to false (e.g. Mesmerize Companion on
		//    a maintainable page), WordPress core does not print the TinyMCE library
		//    (`window.tinymce` stays undefined) even though `wp.editor` still loads
		//    and `settings.tinymce` is still present. Calling `wpEditor.initialize()`
		//    then reaches core editor.js which does `window.tinymce.translate(...)`
		//    and throws `Cannot read properties of undefined (reading 'translate')`.
		//    So the initialize() gate must additionally confirm the library is
		//    actually loaded and usable — mirroring exactly what core dereferences.
		//    Note `window.tinymce` can arrive late (footer scripts), so the poll
		//    branch below re-evaluates this at poll time rather than trusting only
		//    this initial reading.
		const hasTinyMCE = !! ( settings && settings.tinymce );
		const isTinyMCELibraryReady = function() {
			return typeof window.tinymce !== 'undefined' &&
				window.tinymce &&
				typeof window.tinymce.translate === 'function';
		};
		const canInitEditor = hasTinyMCE && isTinyMCELibraryReady();

		if (
			hasTinyMCE &&
			window.top.tinyMCEPreInit &&
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
		if ( hasTinyMCE && settings.wpautopToggleField ) {
			const $widgetForm = $container.closest( '.siteorigin-widget-form' );

			$wpautopToggleField = $widgetForm.find( settings.wpautopToggleField );

			settings.tinymce.wpautop = $wpautopToggleField.is( ':checked' );
		}

		const $textarea = $container.find( 'textarea' );
		// Prevent potential id overlap by appending the textarea field with a random id.
		let id = $textarea.attr( 'data-tinymce-id' ) || $textarea.data( 'tinymce-id' );
		const fieldElement = $field.get( 0 );
		const textareaElement = $textarea.get( 0 );

		if ( id ) {
			const existingTextarea = document.getElementById( id );
			if ( existingTextarea && textareaElement && existingTextarea !== textareaElement ) {
				id = '';
			}

			const existingEditor = window.tinymce && window.tinymce.get( id ) ? window.tinymce.get( id ) : null;
			const existingEditorContainer = existingEditor && typeof existingEditor.getContainer === 'function' ?
				existingEditor.getContainer() :
				null;

			if (
				existingEditor &&
				(
					! existingEditorContainer ||
					! isConnectedElement( existingEditorContainer ) ||
					( fieldElement && ! fieldElement.contains( existingEditorContainer ) )
				)
			) {
				if ( typeof existingEditor.remove === 'function' ) {
					existingEditor.remove();
				}
				id = '';
			}
		}

		if ( ! id ) {
			const baseId = sanitizeIdSegment( $textarea.attr( 'id' ) || $textarea.attr( 'name' ) || 'sowb-tinymce' ) || 'sowb-tinymce';
			id = baseId + '-' + ( ++_tinymceDomIdSeq );
		}

		$textarea
			.data( 'tinymce-id', id )
			.attr( 'data-tinymce-id', id )
			.attr( 'id', id );

		if ( textareaElement ) {
			textareaElement.sowbTinyMCELastSyncedContent = $textarea.val();
		}

		_tinymceInitPending[ id ] = ( function( entryId ) {
			let storedResolve;
			const promise = new Promise( function( resolve ) {
				storedResolve = resolve;
			} );
			return {
				promise: promise,
				resolve: function() {
					storedResolve();
					delete _tinymceInitPending[ entryId ];
				},
			};
		} )( id );

		$field
			.data( 'sowb-tinymce-initializing', true )
			.data( 'sowb-tinymce-initializing-id', id );

		const fieldEventNamespace = '.sowTinymceField-' + ( sanitizeIdSegment( id ) || 'unknown' );

		$( window.document )
			.off( 'wp-before-tinymce-init' + fieldEventNamespace )
			.on( 'wp-before-tinymce-init' + fieldEventNamespace, function( event, init ) {
				if ( init.selector !== settings.tinymce.selector ) {
					return;
				}

				$( window.document ).off( 'wp-before-tinymce-init' + fieldEventNamespace );

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
							.off( 'click.sowbMedia' )
							.on( 'click.sowbMedia', () => {
								siteEditorAddMediaOverride( editorId );
							} );
					}
				}
			} );

		$( window.top.document )
			.off( 'tinymce-editor-setup' + fieldEventNamespace )
			.on( 'tinymce-editor-setup' + fieldEventNamespace, function() {
				const $wpEditorWrap = $field.find( '.wp-editor-wrap' );
				if ( $wpEditorWrap.length > 0 && ! $wpEditorWrap.hasClass( settings.selectedEditor + '-active' ) ) {
					setTimeout( function() {
						window.switchEditors.go( id );
					}, 10 );
				}

				$( window.top.document ).off( 'tinymce-editor-setup' + fieldEventNamespace );
			} );

		if ( settings.tinymce ) {
			const setupEditor = function( editor ) {
				editor.on( 'init', function() {
					const initTimeout = $field.data( 'sowb-tinymce-init-timeout' );
					if ( initTimeout ) {
						clearTimeout( initTimeout );
						$field.removeData( 'sowb-tinymce-init-timeout' );
					}

					if ( _tinymceInitPending[ id ] ) {
						_tinymceInitPending[ id ].resolve();
					}

					$field.removeData( 'sowb-tinymce-initializing' );
					$field.removeData( 'sowb-tinymce-initializing-id' );
				} );
				editor.on( 'change', function() {
					const ed = window.tinymce.get( id );
					if ( ed ) {
						const textareaNode = $textarea.get( 0 );
						const previousContent = textareaNode ?
							textareaNode.sowbTinyMCELastSyncedContent :
							undefined;
						ed.save();

						const currentContent = $textarea.val();
						if ( textareaNode && previousContent === currentContent ) {
							return;
						}

						if ( textareaNode ) {
							textareaNode.sowbTinyMCELastSyncedContent = currentContent;
						}
						$textarea.trigger( 'change' );
					}
				} );

				if ( $wpautopToggleField ) {
					$wpautopToggleField.off( 'change' + fieldEventNamespace );
					$wpautopToggleField.on( 'change' + fieldEventNamespace, function() {
						const currentEditor = resolveWpEditor();
						if ( ! currentEditor ) {
							return;
						}
						removeTinyMCEEditor( currentEditor, id );
						settings.tinymce.wpautop = $wpautopToggleField.is( ':checked' );
						currentEditor.initialize( id, settings );
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

		const initTimeoutId = setTimeout( function() {
			if ( _tinymceInitPending[ id ] ) {
				_tinymceInitPending[ id ].resolve();
			}

			$field.removeData( 'sowb-tinymce-initializing' );
			$field.removeData( 'sowb-tinymce-initializing-id' );
			// If the TinyMCE init event never fired (e.g. field removed from DOM),
			// clean up the top-document listener so it doesn't accumulate.
			$( window.top.document ).off( 'tinymce-editor-setup' + fieldEventNamespace );
		}, 5000 );

		$field.data( 'sowb-tinymce-init-timeout', initTimeoutId );

		// Runs when we decide NOT to initialise a visual editor for this field (the
		// TinyMCE library is unavailable, so calling wpEditor.initialize() would throw
		// inside core's `window.tinymce.translate(...)`). Because initialize() is never
		// called, TinyMCE's `init` event — the success-path resolver at ~line 713 —
		// will never fire, so we must mirror that handler's cleanup exactly: resolve
		// the init-pending promise, release the initializing lock, clear the 5 s safety
		// timeout, cancel any visibility poll, and drop the top-document setup listener.
		// Without this, sowbGetTinyMCEInitPromise() would hang forever and stall the
		// save-bridge TinyMCE flusher. The Code textarea is left in place, so the field
		// still works in Code mode.
		const finishWithoutEditor = function() {
			const pendingPoll = $field.data( 'sowb-tinymce-visibility-poll' );
			if ( pendingPoll ) {
				clearInterval( pendingPoll );
				$field.removeData( 'sowb-tinymce-visibility-poll' );
			}

			const skipInitTimeout = $field.data( 'sowb-tinymce-init-timeout' );
			if ( skipInitTimeout ) {
				clearTimeout( skipInitTimeout );
				$field.removeData( 'sowb-tinymce-init-timeout' );
			}

			if ( _tinymceInitPending[ id ] ) {
				_tinymceInitPending[ id ].resolve();
			}

			$field.removeData( 'sowb-tinymce-initializing' );
			$field.removeData( 'sowb-tinymce-initializing-id' );

			// The top-document setup listener bound at ~line 698 is keyed to the same
			// namespace and only fires on a real TinyMCE init; remove it too so it
			// does not accumulate across re-setups of a rich-editing-disabled field.
			$( window.top.document ).off( 'tinymce-editor-setup' + fieldEventNamespace );
		};

		// Wait for textarea to be visible before initialization.
		if ( $textarea.is( ':visible' ) ) {
			if ( canInitEditor ) {
				wpEditor.initialize( id, settings );
			} else {
				// Visible now, but the TinyMCE library is not loaded (host disabled
				// rich editing). Don't call initialize() — go straight to Code-only.
				finishWithoutEditor();
			}
		} else {
			const intervalId = setInterval( function() {
				if ( ! $textarea.is( ':visible' ) ) {
					return;
				}

				// The textarea is now visible. window.tinymce can load late (footer
				// scripts), so re-evaluate library readiness at poll time rather than
				// trusting the up-front reading. If it is ready, initialise; if it is
				// still unavailable (this bug: richedit off suppresses it permanently),
				// give up and clean up rather than spinning forever.
				clearInterval( intervalId );
				$field.removeData( 'sowb-tinymce-visibility-poll' );

				const pollEditor = resolveWpEditor();
				if ( pollEditor && hasTinyMCE && isTinyMCELibraryReady() ) {
					pollEditor.initialize( id, settings );
				} else {
					finishWithoutEditor();
				}
			}, 500 );

			$field.data( 'sowb-tinymce-visibility-poll', intervalId );
		}

		$field
			.off( 'click.sowTinymceSwitchEditor' )
			.on( 'click.sowTinymceSwitchEditor', function( event ) {
				const $target = $( event.target );
				if ( ! $target.is( '.wp-switch-editor' ) ) {
					return;
				}

				const mode = $target.hasClass( 'switch-tmce' ) ? 'tmce' : 'html';
				const $selectedEditor = $field.find( '.siteorigin-widget-tinymce-selected-editor' );
				if ( $selectedEditor.val() === mode ) {
					return;
				}

				const fieldNode = $field.get( 0 );
				const now = Date.now();
				const lastSwitchClick = fieldNode && fieldNode.sowbTinyMCELastSwitchClick ?
					fieldNode.sowbTinyMCELastSwitchClick :
					null;
				if (
					lastSwitchClick &&
					lastSwitchClick.mode === mode &&
					now - lastSwitchClick.time < 50
				) {
					return;
				}
				if ( fieldNode ) {
					fieldNode.sowbTinyMCELastSwitchClick = {
						mode,
						time: now,
					};
				}

				if ( mode === 'tmce' ) {
					const editor = window.tinymce.get( id );
					let content = $textarea.val() || '';

					// Quick bit of sanitization to prevent catastrophic backtracking in TinyMCE HTML parser regex.
					if ( content.search( '<' ) !== -1 && content.search( '>' ) === -1 ) {
						content = content.replace( /</g, '' );
						$textarea.val( content );
					}

					// In classic Customizer/widgets screens, WordPress core owns the
					// Visual/Code switch and restores selection bookmarks during the
					// same click event. Avoid touching the hidden editor before core's
					// document handler runs, otherwise stale iframe selections in WP 7
					// can throw inside TinyMCE's selection code.
					if ( editor !== null && window.frameElement ) {
						editor.setContent( window.switchEditors.wpautop( content ) );
					}
				}

				window.setTimeout( function() {
					settings.selectedEditor = mode;

					$field.find( 'textarea.wp-editor-area' ).css(
						'visibility', mode === 'tmce' ? 'hidden' : 'visible'
					);

					$selectedEditor.val( mode );
				}, 0 );
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
		const $textarea = $field.find( '.siteorigin-widget-tinymce-container textarea' ).first();

		if ( $field.closest( '.siteorigin-widget-field-repeater-item-html' ).length || ( $textarea.attr( 'id' ) || '' ).indexOf( '_id_' ) !== -1 ) {
			return;
		}

		if ( $field.attr( 'data-pre-init' ) && ! $field.data( 'sowb-pre-init-bound' ) ) {
			$field.removeAttr( 'data-pre-init' );
		}

		if ( $field.attr( 'data-initialized' ) ) {
			const initializedEditor = getTinyMCEFieldEditor( $field );
			// If the textarea ID cannot be resolved the data-initialized attr is stale;
			// clear it and fall through to re-initialize rather than passing undefined
			// to hasHealthyTinyMCEEditor (which would call tinymce.get(undefined)).
			if ( ! initializedEditor.id ) {
				$field.removeAttr( 'data-initialized' );
			} else if ( hasHealthyTinyMCEEditor( $field, initializedEditor.id ) ) {
				return;
			} else if ( $field.data( 'sowb-tinymce-initializing' ) ) {
				// An init is in flight: data-initialized was set when setupTinyMCEField
				// started but TinyMCE hasn't fired 'init' yet. Clearing the attr here
				// would leave the field unmarked after the pending init completes,
				// causing the next setup event to tear down a healthy editor.
				return;
			} else {
				// Stale data-initialized would cause setupTinyMCEField to attempt
				// teardown of an editor that no longer exists; clear it now.
				$field.removeAttr( 'data-initialized' );
			}
		}

		// If the field is visible, initialize the TinyMCE editor immediately.
		if ( $field.is( ':visible' ) ) {
			setupTinyMCEField( $field );
			return;
		}

		if ( $field.data( 'sowb-pre-init-bound' ) ) {
			return;
		}

		// Use a per-field namespace so the poll can cancel the listener if it fires
		// first, and the listener can cancel the poll if it fires first. This prevents
		// both paths from racing to call setupTinyMCEField on the same field.
		const preInitEventNamespace = '.sowbPreInit-' + ( ++_tinymcePreInitSeq );
		$field.data( 'sowb-pre-init-namespace', preInitEventNamespace );

		const preInitVisibilityPoll = setInterval( function() {
			if ( $field.is( ':visible' ) ) {
				clearInterval( preInitVisibilityPoll );
				$field.removeData( 'sowb-pre-init-visibility-poll' );
				$field.removeData( 'sowb-pre-init-bound' );
				// Cancel the sowsetupformfield listener so it doesn't re-trigger
				// initialization after the poll has already started it.
				$field.off( 'sowsetupformfield' + preInitEventNamespace );
				setupTinyMCEField( $field );
			}
		}, 250 );

		$field.data( 'sowb-pre-init-visibility-poll', preInitVisibilityPoll );

		// Mark the field for initialization and wait for it to become visible.
		// Once visible, the 'sowsetupformfield' event triggers the editor setup.
		$field
			.data( 'sowb-pre-init-bound', true )
			.off( 'sowsetupformfield' + preInitEventNamespace )
			.one( 'sowsetupformfield' + preInitEventNamespace, () => {
				const existingPreInitPoll = $field.data( 'sowb-pre-init-visibility-poll' );
				if ( existingPreInitPoll ) {
					clearInterval( existingPreInitPoll );
					$field.removeData( 'sowb-pre-init-visibility-poll' );
				}
				$field.removeData( 'sowb-pre-init-bound' );
				setupTinyMCEFieldInitializer.call( $field.get( 0 ) );
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
	const setupSiteEditorTinyMCEFields = function( $targetFields ) {
		const hasTargetedFields = !! ( $targetFields && $targetFields.jquery && $targetFields.length );
		const $rawFields = hasTargetedFields ?
			$targetFields :
			$( '.siteorigin-widget-field-type-tinymce' );
		const $fields = getEligibleTinyMCEFields( $rawFields );

		if (
			window.wp &&
			window.wp.editor &&
			! window.wp.editor.getDefaultSettings &&
			window.top.wp &&
			window.top.wp.editor
		) {
			window.wp.editor.getDefaultSettings = window.top.wp.editor.getDefaultSettings;
		}

		$fields.each( function() {
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
		$( document )
			.off( 'sowsetupformfield' + sowbTinyMCEEventNamespace, '.siteorigin-widget-field-type-tinymce' )
			.on( 'sowsetupformfield' + sowbTinyMCEEventNamespace, '.siteorigin-widget-field-type-tinymce', setupTinyMCEFieldInitializer );
	}

	$( document )
		.off( 'sortstop' + sowbTinyMCEEventNamespace )
		.on( 'sortstop' + sowbTinyMCEEventNamespace, sortStopEvent );

	// Add support for the Site Editor.
	// Store the handler reference on window so it can be removed and replaced on
	// script re-evaluation (e.g. HMR or plugin updates) rather than accumulating.
	if ( window._sowbTinyMCEMessageHandler ) {
		window.removeEventListener( 'message', window._sowbTinyMCEMessageHandler );
	}
	window._sowbTinyMCEMessageHandler = function( e ) {
		if ( ! e || e.origin !== window.location.origin ) {
			return;
		}

		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			if ( ! e.data.formSelector ) {
				return;
			}

			const $form = resolvePostMessageForms( e.data );
			if ( ! $form.length ) {
				return;
			}

			// Full form setup (sowSetupForm, setWidgetFormValues) is handled by the
			// polling interval in sowbSetupWidgetForm (widget-block.js), which waits
			// for jQuery UI to be ready before calling sowSetupForm. This handler
			// covers only TinyMCE fields, including those added by repeater items.
			const $tinymceFields = getTinyMCEFieldsFromForms( $form );
			if ( $tinymceFields.length ) {
				setupSiteEditorTinyMCEFields( $tinymceFields );
			}
		}
	};
	window.addEventListener( 'message', window._sowbTinyMCEMessageHandler );

	if ( window.frameElement ) {
		$( document )
			.off( 'sowsetupformfield' + sowbTinyMCEEventNamespace, '.siteorigin-widget-field-type-tinymce' )
			.on( 'sowsetupformfield' + sowbTinyMCEEventNamespace, '.siteorigin-widget-field-type-tinymce', function( e ) {
				setupTinyMCEFieldInitializer.call( this, e );
			} );

		$( document )
			.off( 'sowsetupform' + sowbTinyMCEEventNamespace )
			.on( 'sowsetupform' + sowbTinyMCEEventNamespace, function( e, $form ) {
				const $setupTarget = $form && $form.jquery ?
					$form :
					$( $form || [] );
				if ( ! $setupTarget.length ) {
					return;
				}

				setupSiteEditorTinyMCEFields(
					$setupTarget
						.filter( '.siteorigin-widget-field-type-tinymce' )
						.add( $setupTarget.find( '.siteorigin-widget-field-type-tinymce' ) )
				);
			} );

		// sowrepeaterfieldsadded is fired by admin.js on the parent document, not the iframe's document,
		// because admin.js always runs in the parent window context. widget-block.js handles this event
		// in the parent and re-dispatches it as a sowbBlockFormInit postMessage to the iframe, covering
		// all field types. No separate listener is needed here.

		setupSiteEditorTinyMCEFields();
	}

	/**
	 * Returns a Promise that resolves once the TinyMCE editor for the given ID
	 * has finished initialising, or immediately if it is already ready.
	 *
	 * The promise always resolves (never rejects). If initialisation stalls the
	 * safety timeout in `setupTinyMCEField` will resolve it after 5 seconds.
	 *
	 * Intended for use by the save-bridge TinyMCE flusher in admin.js so it can
	 * await full editor readiness before calling `editor.save()`. The flusher
	 * retrieves this via the field element's `ownerDocument.defaultView` (i.e.
	 * the iframe's `window` in a Site Editor context) so that each frame's
	 * pending-init map is consulted correctly.
	 *
	 * @param {string} editorId - The TinyMCE editor / textarea ID.
	 * @return {Promise<void>}
	 */
	window.sowbGetTinyMCEInitPromise = function( editorId ) {
		return _tinymceInitPending[ editorId ]
			? _tinymceInitPending[ editorId ].promise
			: Promise.resolve();
	};

} )( jQuery );
