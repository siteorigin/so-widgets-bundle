( async function( blocks, i18n, element, components, blockEditor ) {

	const el = element.createElement;
	const registerBlockType = blocks.registerBlockType;
	const BlockControls = blockEditor.BlockControls;
	const { useMemo } = element;

	const {
		ToolbarGroup,
		ToolbarButton,
		Placeholder,
		Button,
		Spinner
	} = components;

	const { __, sprintf } = i18n;

	const { updateCategory } = blocks;

	const getAjaxErrorMsg = ( response ) => {
		let errorMessage = '';
		if ( response.hasOwnProperty( 'responseJSON' ) ) {
			errorMessage = response.responseJSON.message;
		} else if ( response.hasOwnProperty( 'responseText' ) ) {
			errorMessage = response.responseText;
		}
		return errorMessage;
	};

	const sowbSerializeWidgetData = ( widgetData ) => {
		try {
			return JSON.stringify( widgetData || {} );
		} catch ( error ) {
			return null;
		}
	};

	// Certain widgets are excluded from the content check as
	// they don't contain "standard" content indicators.
	const widgetsExcludedFromContentCheck = [
		'sowb/siteorigin-widget-googlemap-widget',
		'sowb/siteorigin-widget-icon-widget',
	];

	/**
	 * Sets up the icon for a SiteOrigin Widget block.
	 *
	 * Checks if the widget has an icon set. If set, returns a span element with the icon.
	 * For SVG icons, uses dangerouslySetInnerHTML. For image icons, returns an img element.
	 * Returns a default icon span if no icon is set.
	 *
	 * @param {Object} widget - The widget object containing icon and name properties.
	 *
	 * @returns {HTMLSpanElement|HTMLImageElement} The icon element.
	 */
	const sowbSetupIcon = ( widget ) => {
		return widget.icon ?
			widget.icon.trim().startsWith( '<svg' ) ?
				el(
					'span',
					{
						className: 'widget-icon so-widget-icon so-block-editor-icon',
						dangerouslySetInnerHTML: { __html: widget.icon }
					}
				)
				:
				el(
					'img',
					{
						className: 'widget-icon so-widget-icon so-block-editor-icon',
						src: widget.icon,
						alt: widget.name
					}
				)

			// Widget doesn't have icon set. Add default icon.
			: el(
				'span',
				{
					className: 'widget-icon so-widget-icon so-block-editor-icon',
					dangerouslySetInnerHTML: { __html: sowbBlockEditorAdmin.defaultIcon }
				}
			);
	};

	/**
	 * Generate a widget preview.
	 *
	 * This function generates a preview for the widget by making
	 * an AJAX request to the server. It sets the loading state,
	 * sends the request, and updates the state with the preview
	 * HTML or an error message based on the response.
	 *
	 * @param {Object} props - The properties passed to the function.
	 * @param {boolean} loadingWidgetPreview - Indicates if the widget preview is currently loading.
	 * @param {Function} setState - The setState function to update the component's state.
	 * @param {Object} [widgetData=false] - The data for the widget. Defaults to false.
	 * @param {string} [widgetClass=false] - The class of the widget. Defaults to false.
	 * @param {Object} activeRequestRef - React ref to track the active request for this block instance.
	 */
	const sowbGenerateWidgetPreview = ( props, loadingWidgetPreview, setState, widgetData = false, widgetClass = false, activeRequestRef ) => {
		// If there is an active request, abort it before starting a new one.
		if (
			activeRequestRef.current &&
			typeof activeRequestRef.current.abort === 'function'
		) {
			activeRequestRef.current.abort();
			activeRequestRef.current = null;
		}

		if ( loadingWidgetPreview ) {
			return;
		}

		setState( {
			loadingWidgetPreview: true,
			widgetPreviewHtml: null,
		} );

		const canLockPostSaving = typeof wp.data.select( 'core/editor' ) == 'object' &&
			typeof wp.data.dispatch( 'core/editor' ) == 'object';

		if ( canLockPostSaving ) {
			wp.data.dispatch( 'core/editor' ).lockPostSaving();
		}

		/**
		 * Check if the provided HTML contains any text or images.
		 *
		 * This function creates a temporary DOM element with the provided HTML,
		 * checks if there is any text content or images present, and returns
		 * a boolean indicating whether the HTML should be rendered.
		 *
		 * @param {string} html - The HTML string to check for content.
		 *
		 * @returns {boolean} - Returns true if the HTML contains text or images,
		 * false otherwise.
		 */
		const checkHtmlForContent = ( html ) => {
			const tempElement = jQuery( '<div>' + html + '</div>' );
			let renderPreviewHtml = false;

			const widgetContent = tempElement.find( 'div:first-of-type' );
			if ( widgetContent.length > 0 ) {
				// Is there any text present?
				if ( widgetContent.text().trim() !== '' ) {
					renderPreviewHtml = true;

				// No text is present. Check for anything that
				// could be considered content.
				} else if ( widgetContent.find( 'img, video, a' ).length > 0 ) {
					renderPreviewHtml = true;
				}
			}

			tempElement.remove();

			return renderPreviewHtml;
		};

		const previewRequest = jQuery.post( {
			url: sowbBlockEditorAdmin.restUrl + 'sowb/v1/widgets/previews',
			beforeSend: ( xhr ) => {
				xhr.setRequestHeader( 'X-WP-Nonce', sowbBlockEditorAdmin.nonce );
			},
			data: {
				anchor: props.attributes.anchor,
				widgetClass: widgetClass,
				widgetData: widgetData ? widgetData : props.attributes.widgetData || {}
			}
		} );

		activeRequestRef.current = previewRequest;

		previewRequest
			.done( ( widgetPreview ) => {
				let renderPreviewHtml = false;

				// Is the preview empty?
				if ( widgetPreview.html ) {
					// Is this widget excluded from the content check?
					if ( widgetsExcludedFromContentCheck.includes( props.name ) ) {
						renderPreviewHtml = true;
					} else {
						renderPreviewHtml = checkHtmlForContent( widgetPreview.html );
					}
				}

				if ( ! renderPreviewHtml ) {
					widgetPreview.html = '<div class="so-widget-preview-empty">' + __( 'No widget preview available.', 'so-widgets-bundle' ) + '</div>';
				}

				setState( {
					widgetPreviewHtml: widgetPreview.html,
					previewInitialized: false,
				} );

				props.setAttributes( {
					widgetMarkup: widgetPreview.html,
					widgetIcons: widgetPreview.widgetIcons,
				} );
			} )
			.fail( ( response ) => {
				if ( response && response.statusText === 'abort' ) {
					return;
				}

				setState( { widgetFormHtml: '<div>' + getAjaxErrorMsg( response ) + '</div>' } );
			} )
			.always( () => {
				activeRequestRef.current = null;

				if ( canLockPostSaving ) {
					wp.data.dispatch( 'core/editor' ).unlockPostSaving();
				}

				setState( { loadingWidgetPreview: false } );
			} );
	};

	/**
	 * Memoized component for WidgetBlockEdit.
	 *
	 * This function memoizes the WidgetBlockEdit component to
	 * prevent unnecessary re-renders. It uses the useMemo hook to
	 * only re-render the component when the props or widget change.
	 *
	 * @param {Object} params - The parameters passed to the function.
	 * @param {Object} params.props - The properties passed to the WidgetBlockEdit component.
	 * @param {Object} params.widget - The widget data passed to the WidgetBlockEdit component.
	 *
	 * @returns {Object} The memoized WidgetBlockEdit component.
	 */
	const memoizedWidgetBlockEdit = ( { props, widget } ) => {
		return useMemo( () =>
			el(
				WidgetBlockEdit,
				{ ...props, widget }
			),
			[ props, widget ]
		);
	};

	/**
	 * Set up the widget form.
	 *
	 * This function sets up the widget form by initializing the
	 * form, setting widget data, and handles triggering
	 * componentDidUpdate. It ensures that the form is only set up
	 * once and prevents unnecessary re-renders.
	 *
	 * @param {Object} props - The properties passed to the function.
	 * @param {Object} state - The current state of the component.
	 * @param {Function} setState - The setState function to update the component's state.
	 * @param {Object} activeRequestRef - React ref tracking the active preview request.
	 */
	const sowbSetupWidgetForm = async ( props, state, setState, activeRequestRef ) => {
		let $mainForm = sowbGetBlockForm( props.clientId );

		if ( $mainForm.length === 0 || state.formInitialized ) {
			return;
		}

		// Re-wrap the form node with the jQuery instance that owns it. In the
		// iframe path this is the iframe's jQuery; in the non-iframe path it is
		// the parent jQuery. sowSetupForm(), wpColorPicker, and all other field
		// plugins are bound to whichever jQuery ran their defining script, using
		// the same instance here ensures event handlers fire correctly.
		const formWindow = sowbGetElementWindow( $mainForm[ 0 ] );
		const formJQuery = formWindow.jQuery || jQuery;
		const formForms = formWindow.sowbForms || sowbForms;
		$mainForm = formJQuery( $mainForm[ 0 ] );

		const switchToBlockPreview = async function() {
			const oldTimer = $mainForm.data( 'sowb-preview-timer' );
			if ( oldTimer ) {
				clearTimeout( oldTimer );
			}

			const widgetData = await sowbSnapshotBlockFormToAttributes( props, $mainForm );

			setState( {
				editing: false,
				previewInitialized: false,
				widgetPreviewHtml: false,
			} );

			if ( widgetData ) {
				sowbGenerateWidgetPreview(
					props,
					false,
					setState,
					widgetData,
					props.widget.class,
					activeRequestRef
				);
			}
		};

		const bindBlockPreviewHandler = function() {
			// sowSetupForm() binds the legacy widget preview modal handler. Direct
			// widget blocks have their own React preview path, so take ownership of
			// this link after setup and prevent the legacy form/iframe submit path
			// from navigating the editor canvas.
			$mainForm
				.siblings( '.siteorigin-widget-preview' )
				.find( '> a' )
				.off( 'click' )
				.on( 'click.sowbBlockPreview', function( event ) {
					event.preventDefault();
					event.stopImmediatePropagation();

					switchToBlockPreview();
				} );
		};

		if ( $mainForm.data( 'sowb-block-form-initializing' ) ) {
			return;
		}

		if ( $mainForm.data( 'sow-form-setup' ) === true ) {
			bindBlockPreviewHandler();
			setState( { formInitialized: true } );
			return;
		}

		const setupFrame = sowbGetEditorCanvasFrame();
		if ( ! $mainForm.data( 'sowb-form-setup-assets-cloned' ) ) {
			sowbMaybeSetupSiteEditorAssets();
			$mainForm.data( 'sowb-form-setup-assets-cloned', true );
		}

		const dependencyStatus = sowbEnsureFormSetupDependencies( setupFrame, formWindow, $mainForm );
		if ( ! dependencyStatus.ready ) {

			const oldRetryTimer = $mainForm.data( 'sowb-form-setup-dependency-retry' );
			if ( oldRetryTimer ) {
				clearTimeout( oldRetryTimer );
			}

			const retryCount = ( $mainForm.data( 'sowb-form-setup-dependency-retry-count' ) || 0 ) + 1;
			$mainForm.data( 'sowb-form-setup-dependency-retry-count', retryCount );

			if ( retryCount <= 40 ) {
				$mainForm.data(
					'sowb-form-setup-dependency-retry',
					setTimeout( function() {
						sowbSetupWidgetForm(
							props,
							state,
							setState,
							activeRequestRef
						);
					}, 250 )
				);
			}

			return;
		}
		$mainForm.removeData( 'sowb-form-setup-dependency-retry' );
		$mainForm.removeData( 'sowb-form-setup-dependency-retry-count' );

		$mainForm.data( 'sowb-block-form-initializing', true );
		$mainForm.data( 'backupDisabled', true );

		let currentWidgetDataJson = sowbSerializeWidgetData( props.attributes.widgetData );

		if ( props.attributes.widgetData ) {
			// If we call `setWidgetFormValues` with the last parameter
			// ( `triggerChange` ) set to false, it won't show the correct values
			// for some fields e.g. color and media fields.
			formForms.setWidgetFormValues( $mainForm, props.attributes.widgetData );
		} else {
			const initialWidgetData = formForms.getWidgetFormValues( $mainForm );
			currentWidgetDataJson = sowbSerializeWidgetData( initialWidgetData );
			props.setAttributes( { widgetData: initialWidgetData } );
		}

		try {
			$mainForm.sowSetupForm();
		} catch ( error ) {
			$mainForm.removeData( 'sowb-block-form-initializing' );
			throw error;
		}
		bindBlockPreviewHandler();
		$mainForm.removeData( 'sowb-block-form-initializing' );
		$mainForm.data( 'sowb-block-last-widget-data-json', currentWidgetDataJson );

		// Namespace the change event so multiple sowbSetupWidgetForm calls for
		// the same block (React double-render / stale closures) don't stack up
		// duplicate handlers on the same DOM node.
		const changeNs = '.sowbBlock-' + props.clientId.replace( /-/g, '' );
		$mainForm
			.off( 'change' + changeNs )
			.on( 'change' + changeNs, function() {
				// Only react to changes once the form has been fully set up.
				if ( ! $mainForm.data( 'sow-form-setup' ) ) {
					return;
				}

				// As setAttributes doesn't support callbacks, we have to manually
				// pass the widgetData to the preview.
				var widgetData = formForms.getWidgetFormValues( $mainForm );
				const widgetDataJson = sowbSerializeWidgetData( widgetData );

				if (
					widgetDataJson !== null &&
					widgetDataJson === $mainForm.data( 'sowb-block-last-widget-data-json' )
				) {
					return;
				}

				$mainForm.data( 'sowb-block-last-widget-data-json', widgetDataJson );
				props.setAttributes( { widgetData: widgetData } );

				// Set up a preview debounce timer to prevent multiple requests.
				var oldTimer = $mainForm.data( 'sowb-preview-timer' );
				if ( oldTimer ) {
					clearTimeout( oldTimer );
				}

				var sowb_preview_timer = setTimeout( () => {
					sowbGenerateWidgetPreview(
						props,
						false,
						setState,
						widgetData,
						props.widget.class,
						activeRequestRef
					);
				}, 300 );

				$mainForm.data( 'sowb-preview-timer', sowb_preview_timer );
			} );

		setState( { formInitialized: true } );
	};

	/**
	 * Initializes WB Form Fields in the Site Editor iframe.
	 *
	 * Unlike the regular editor, the Site Editor uses an iframe to display
	 * blocks. This function resolves the iframe window, builds a postMessage
	 * payload targeting this block's form, sends it immediately, and attaches
	 * a persistent document-level listener so that a fresh message is sent
	 * whenever a repeater item is added inside this block.
	 *
	 * @param {string} clientId - The block's client ID.
	 *
	 * @returns {Function|null} Cleanup function that removes the repeater
	 *                          listener, or null if no iframe was found.
	 */
	const initializeFormFieldsInIframe = ( clientId ) => {
		const iframeElement = sowbResolveSiteEditorFrame();

		if ( ! iframeElement ) {
			return null;
		}

		let iframeWindow;
		let targetOrigin = window.location && window.location.origin ?
			window.location.origin :
			'*';

		try {
			iframeWindow = iframeElement.contentWindow;

			const iframeDocument = iframeElement.contentDocument || (
				iframeWindow &&
				iframeWindow.document
			);

			if (
				iframeDocument &&
				iframeDocument.location &&
				iframeDocument.location.origin &&
				iframeDocument.location.origin !== 'null'
			) {
				targetOrigin = iframeDocument.location.origin;
			}
		} catch ( e ) {
			// Keep the parent origin fallback when same-origin document access
			// is unavailable but the iframe window reference is still usable.
		}

		if ( ! iframeWindow ) {
			return null;
		}

		const blockSelector = clientId ? `[data-block="${ clientId }"]` : '';
		const formSelector = blockSelector ?
			`${ blockSelector } .siteorigin-widget-form.siteorigin-widget-form-main` :
			'';
		// Built once from stable closure values (clientId, blockSelector, formSelector
		// are all derived from props/state that do not change during this effect's
		// lifetime) and reused by every sendInitMessage() call.
		const message = {
			action: 'sowbBlockFormInit',
			clientId,
			blockSelector,
			formSelector
		};

		const sendInitMessage = () => {
			try {
				sowbEnsureIframeOldEditorApi( iframeElement );
				iframeWindow.postMessage( message, targetOrigin );
			} catch ( e ) {
				console.error( 'SiteOrigin Widgets: Failed to send postMessage to iframe:', e );
			}
		};

		// Send once immediately: by the time this effect runs, React has already
		// rendered the form HTML and setWidgetFormValues has restored any saved
		// repeater items, so the iframe DOM is stable.
		sendInitMessage();

		// Persistent event-driven listener: send a fresh postMessage whenever any
		// repeater item is added inside this block's form, at any point in time.
		const repeaterEventNamespace = '.sowbFormInit-' + clientId.replace( /-/g, '' );
		jQuery( document )
			.off( 'sowrepeaterfieldsadded' + repeaterEventNamespace )
			.on( 'sowrepeaterfieldsadded' + repeaterEventNamespace, ( event, $fields, originClientId ) => {
				// $fields (the newly added field elements) is provided by admin.js for the
				// benefit of other listeners. Not needed here — we send a full-form postMessage
				// rather than targeting individual fields.
				if ( originClientId && originClientId !== clientId ) {
					return;
				}
				sendInitMessage();
			} );

		return () => {
			jQuery( document ).off( 'sowrepeaterfieldsadded' + repeaterEventNamespace );
		};
	};

	/**
	 * WidgetBlockEdit functional component.
	 *
	 * This component handles the editing and previewing of SiteOrigin
	 * Widget Blocks. It manages:
	 * - the state of the widget form.
	 * - the state of the widget preview.
	 * - the initialization and loading of the widget form and preview.
	 */
	function WidgetBlockEdit( props ) {
		const initialState = {
			editing: props.attributes.widgetData === undefined,
			formInitialized: false,
			loadingForm: false,
			loadingWidgetPreview: false,
			previewInitialized: false,
			widgetFormHtml: '',
			widgetPreviewHtml: '',
			widgetSettingsChanged: false,
			previewDebounceTimer: null,
		};

		const [ state, setState ] = element.useState( {
			...initialState,
			isStillMounted: true,
			devModeRemount: false,
		} );

		/**
		 * Merge partial state updates into the current state.
		 *
		 * Mirrors the class-style `this.setState` behavior by shallow-merging
		 * the provided `updates` object onto the previous state object.
		 *
		 * @param {Object} updates Partial state object to shallow-merge.
		 */
		const mergeState = ( updates ) => {
			setState( ( prev ) => ( { ...prev, ...updates } ) );
		};

		const isMountedRef = element.useRef( true );
		const activeRequestRef = element.useRef( null );
		const iframeFormInitKeyRef = element.useRef( null );

		const switchToBlockPreview = async () => {
			const widgetData = await sowbSnapshotBlockFormToAttributes( props );

			mergeState( {
				editing: false,
				previewInitialized: false,
				widgetPreviewHtml: false,
			} );

			if ( widgetData ) {
				sowbGenerateWidgetPreview(
					props,
					false,
					mergeState,
					widgetData,
					props.widget.class,
					activeRequestRef
				);
			}
		};

		// Ensure widgetClass attribute is set once (was done in constructor).
		element.useEffect( () => {
			if ( ! props.attributes.widgetClass ) {
				props.setAttributes( { widgetClass: props.widget.class } );
			}
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [] );

		const loadWidgetData = () => {
			const {
				editing,
				widgetFormHtml,
				loadingForm,
				loadingWidgetPreview
			} = state;
			const { attributes } = props;

			if ( editing || ! attributes.widgetData ) {
				const loadWidgetForm = ! widgetFormHtml.length;

				if ( loadWidgetForm && ! loadingForm ) {
					mergeState( { loadingForm: true } );
					jQuery.post( {
						url: sowbBlockEditorAdmin.restUrl + 'sowb/v1/widgets/forms',
						beforeSend: (xhr) => {
							xhr.setRequestHeader( 'X-WP-Nonce', sowbBlockEditorAdmin.nonce );
						},
						data: {
							widgetClass: props.widget.class,
							widgetData: attributes.widgetData,
						}
					} )
					.done( ( widgetForm ) => {
						mergeState( {
							widgetFormHtml: widgetForm
						} );

						setTimeout( () => {
							mergeState( {
								loadingForm: false,
								formInitialized: false,
							} );
						}, 0 );
					} )
					.fail( ( response ) => {
						mergeState( { widgetFormHtml: '<div>' + getAjaxErrorMsg( response ) + '</div>' } );
					} );
				}
				return;
			}

			const loadWidgetPreview = ! loadingWidgetPreview && ! editing;

			if ( loadWidgetPreview ) {
				props.setAttributes( {
					widgetMarkup: null,
					widgetIcons: null
				} );

				sowbGenerateWidgetPreview(
					props,
					state.loadingWidgetPreview,
					mergeState,
					false,
					props.widget.class,
					activeRequestRef
				);
			}
		};

		// componentDidMount / componentWillUnmount replacement
		element.useEffect( () => {
			mergeState( {
				...initialState,
				isStillMounted: true,
			} );

			loadWidgetData();

			return () => {
				isMountedRef.current = false;
				mergeState( {
					...initialState,
					isStillMounted: false,
					devModeRemount: true,
				} );
			};
			// eslint-disable-next-line react-hooks/exhaustive-deps
		}, [] );

		// componentDidUpdate replacement watching relevant props/state.
		element.useEffect( () => {
			if ( ! state.isStillMounted ) {
				return;
			}

			// If editing flag changed or widgetData changed, refresh preview.
			// We compare the state.editing via previous closure by using effect deps.
			loadWidgetData();
			// We only need to run this effect when the widgetData attribute changes
			// or when the editing flag flips. Using props.attributes.widgetData and state.editing.
		}, [ props.attributes.widgetData, state.editing ] );

		element.useEffect( () => {
			if ( ! state.editing || ! state.widgetFormHtml || ! state.formInitialized ) {
				iframeFormInitKeyRef.current = null;
				return;
			}

			// Use a compact fingerprint rather than the full HTML blob to avoid storing
			// and comparing large strings on every effect run. Sample both ends to
			// reduce collision risk for forms with identical boilerplate wrappers.
			const formFingerprint = state.widgetFormHtml.length + ':' + state.widgetFormHtml.slice( 0, 32 ) + state.widgetFormHtml.slice( -32 );
			const initKey = `${ props.clientId }::${ formFingerprint }`;
			if ( iframeFormInitKeyRef.current === initKey ) {
				return;
			}

			// In dev mode, blocks are rendered twice in quick succession. Wait
			// for the remount pass before sending iframe field initialization.
			if ( ! sowbBlockEditorAdmin.wpScriptDebug || state.devModeRemount ) {
				const cleanup = initializeFormFieldsInIframe( props.clientId );
				if ( cleanup ) {
					// Record the key only on success so that a failed attempt
					// (no iframe window yet) is retried on the next render cycle.
					iframeFormInitKeyRef.current = initKey;
					return cleanup;
				}
				// initializeFormFieldsInIframe returned null: the iframe window is
				// not reachable yet. iframeFormInitKeyRef is intentionally left at
				// its current value so the effect runs again on the next render.
			}
		}, [
			props.clientId,
			state.editing,
			state.widgetFormHtml,
			state.formInitialized,
			state.devModeRemount
		] );

		// Use block props hook to provide wrapper attributes/classes for API v3.
		const blockProps = blockEditor && blockEditor.useBlockProps ? blockEditor.useBlockProps() : {};

		const {
			editing,
			widgetFormHtml,
			loadingForm,
			widgetPreviewHtml,
			loadingWidgetPreview,
			previewInitialized
		} = state;
		const { attributes } = props;

		return el(
			'div',
			blockProps,
			editing || ! attributes.widgetData ? [
				!! widgetFormHtml && el(
					BlockControls,
					{ key: 'controls' },
					el(
						ToolbarGroup,
						{ label: __( 'Widget Preview Controls', 'so-widgets-bundle' ) },
						el(
							ToolbarButton,
							{
								label: __( 'Preview widget.', 'so-widgets-bundle' ),
								onClick: ( event ) => {
									if ( event ) {
										event.preventDefault();
										event.stopPropagation();
									}

									switchToBlockPreview();
								},
								icon: 'visibility'
							}
						)
					)
				),
				el(
					Placeholder,
					{
						key: 'placeholder',
						className: 'so-widget-block-form siteorigin-widget-form wp-core-ui',
						label: props.widget.name,
						instructions: props.widget.description
					},
					loadingForm ?
					el( 'div',
						{
							className: 'so-widgets-spinner-container'
						},
						el(
							'span',
							null,
							el( Spinner )
						)
					) :
					el( 'div', {
						className: 'so-widget-block-container siteorigin-widget-form siteorigin-widget-form-main wp-core-ui',
						dangerouslySetInnerHTML: { __html: widgetFormHtml },
						ref: () => {
							sowbSetupWidgetForm(
								props,
								state,
								mergeState,
								activeRequestRef
							);
						}
					} )
				)
			] : [
				el(
					BlockControls,
					{ key: 'controls' },
					el(
						ToolbarGroup,
						{ label: __( 'Widget Edit Controls', 'so-widgets-bundle' ) },
						el(
							ToolbarButton,
							{
								label: __( 'Edit widget.', 'so-widgets-bundle' ),
								onClick: ( event ) => {
									if ( event ) {
										event.preventDefault();
										event.stopPropagation();
									}

									mergeState( {
										editing: true,
										loadingForm: false,
										widgetFormHtml: '',
										formInitialized: false,
									} );
								},
								icon: 'edit'
							}
						)
					)
				),
				el(
					'div',
					{
						key: 'preview',
						className: 'so-widget-preview-container'
					},
					loadingWidgetPreview ?
					el( 'div',
						{ className: 'so-widgets-spinner-container' },
						el(
							'span',
							null,
							el( Spinner )
						)
					) :
					el( 'div', {
						dangerouslySetInnerHTML: {
							__html: widgetPreviewHtml
						},
						ref: ( previewElement ) => {
							if ( ! previewInitialized ) {
								sowbSetupPreviewWidgets( previewElement );
								mergeState( { previewInitialized: true } );
							}
						}
					} )
				)
			]
		);
	}


	registerBlockType( 'sowb/widget-block', {
		apiVersion: 3,
		title: __( 'SiteOrigin Widgets Block', 'so-widgets-bundle' ),
		description: __( 'This block is intended as a legacy placeholder.', 'so-widgets-bundle' ),
		attributes: {
			widgetClass: {
				type: 'string',
			},
			anchor: {
				type: 'string',
			},
			widgetData: {
				type: 'object',
			},
			widgetMarkup: {
				type: 'string',
			},
			widgetIcons: {
				type: 'array',
			},
			widgetNotFound: {
				type: 'boolean',
			}
		},
		supports: {
			inserter: false,
		},
		icon: function() {
			return el(
				'span',
				{
					className: 'widget-icon so-widget-icon so-block-editor-icon so-widget-icon-default'
				}
			)
		},
		edit: function( props ) {
			const [ isAdmin, setIsAdmin ] = element.useState( false );
			const [ isLoading, setIsLoading ] = element.useState( true );

			element.useEffect( () => {
				doesUserHaveAdminPermissions().then( hasPermission => {
					setIsAdmin( hasPermission );
					setIsLoading( false );
				} );
			}, [] );

			if ( props.attributes.widgetNotFound ) {
				return el(
					Placeholder,
					{
						label: __( 'SiteOrigin Widget', 'so-widgets-bundle' ),
						className: 'so-widget-block-form'
					},
					el(
						'p',
						null,
						sprintf(
							__( 'The widget for %s cannot be found.', 'so-widgets-bundle' ),
							props.attributes.widgetClass
						)
					)
				);
			}

			if ( isLoading ) {
				return el( 'div',
					{
						className: 'so-widget-block-form so-widgets-spinner-container'
					},
					el(
						'span',
						null,
						el( Spinner )
					)
				);
			}

			return el(
				'div',
				{
					className: 'so-widget-block-form'
				},
				el(
					Placeholder,
					{
						label: __( 'Legacy SiteOrigin Widget', 'so-widgets-bundle' ),
					},
					el( 'p', {
						dangerouslySetInnerHTML: {
							__html: sowbBlockEditorAdmin.legacyNotice
						}
					} ),
					isAdmin ?
					el(
						Button,
						{
							isPrimary: true,
							onClick: () => {
								setIsLoading(true);

								// Migrate the blocks.
								setTimeout( () => {
									sowbBlockEditorAdmin.consent = true;
									sowbBlockEditorAdmin.consentGiven = true;
									sowbMigrateOldBlocks();
								}, 0 );

								// Log the user's consent.
								jQuery.post( window.top.ajaxurl, {
									action: 'so_widgets_block_migration_notice_consent',
									nonce: sowbBlockEditorAdmin.migrationNotice
								} );
							},
						},
						__( 'Migrate to New Block Format', 'so-widgets-bundle' )
					) :
					el(
						'span',
						null,
						__( 'Please contact your site administrator to migrate this block.', 'so-widgets-bundle' )
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	let adminPermissionCheck = null;
	/**
	 * Checks if current user has admin permissions to migrate widgets.
	 * Uses a Promise to cache the result and prevent multiple API calls.
	 *
	 * @return {Promise<boolean>} Promise that resolves to true if user has permissions.
	 */
	const doesUserHaveAdminPermissions = () => {
		// If we already have a permission check in progress, return that promise.
		if ( adminPermissionCheck !== null ) {
			return adminPermissionCheck;
		}

		adminPermissionCheck = new Promise( ( resolve, reject ) => {
			jQuery.post( {
				url: sowbBlockEditorAdmin.restUrl + 'sowb/v1/widgets/permission',
				beforeSend: ( xhr ) => {
					xhr.setRequestHeader( 'X-WP-Nonce', sowbBlockEditorAdmin.nonce );
				},
			} )
			.done( ( canMigrateWidgets ) => {
				resolve(canMigrateWidgets);
			} )
			.fail( ( error ) => {
				console.error('Failed to check admin permissions:', error);
				resolve( false );
			} );
		} );

		return adminPermissionCheck;
	};

	const sowbManuallyRegisteredBlocks = {};
	const sowbWidgets = [ ...Object.values( sowbBlockEditorAdmin.widgets ) ];

	/**
	 * Identifies widgets that need manual block registration.
	 *
	 * This function examines each widget to determine if it needs special handling:
	 * 1. Skips and removes widgets without a blockName.
	 * 2. Identifies widgets marked for manual registration and adds them to the
	 *    sowbManuallyRegisteredBlocks object.
	 * 3. Removes manually registered widgets from the general widgets list to
	 *    prevent duplicate registration.
	 *
	 * @param {Object} widget - The widget configuration object.
	 * @param {string} widget.blockName - Block identifier name.
	 * @param {boolean} [widget.manuallyRegister] - Whether widget needs manual registration.
	 * @param {string} widget.class - PHP class of the widget.
	 * @param {key} key - The key of the widget in the widgets list.
	 *
	 * @return {void}
	 */
	const identifyBlocksThatNeedManualRegistration = async ( widget, key ) => {
		// Don't register any blocks that don't have a blockName.
		if ( ! widget.blockName ) {
			delete sowbWidgets[ key ];
			return;
		}

		// Skip any blocks that are manually registered.
		if (
			widget.manuallyRegister !== undefined &&
			widget.manuallyRegister
		) {
			sowbManuallyRegisteredBlocks[ widget.blockName ] = widget;
			delete sowbWidgets[ key ];
			return;
		}
	};

	// Register all Widget Bundle widgets, and build `sowbManuallyRegisteredBlocks`.
	await Promise.all(
		Object.entries( sowbWidgets ).map( async ( [ key, widget ] ) => {
			identifyBlocksThatNeedManualRegistration( widget, key );
		} )
	);

	// Modify all of the manually registered blocks with additional properties.
	Object.entries( sowbManuallyRegisteredBlocks ).forEach( ( [ key, widget ] ) => {
		if ( ! widget.blockName ) {
			return;
		}

		wp.hooks.addFilter(
			'blocks.registerBlockType',
			'sowb/' + widget.blockName,
			function ( settings, name ) {
				if ( name !== 'sowb/' + widget.blockName ) {
					return settings;
				}

				return {
					...settings,
					icon: sowbSetupIcon( widget ),
					keywords: widget.keywords ? widget.keywords : '',
					category: 'siteorigin',
					supports: {
						html: false,
						anchor: true,
					},
					edit: ( props ) => el(
						memoizedWidgetBlockEdit, { props, widget }
					)
				};
			}
		);
	} );

	// Register all of our manually registered blocks after the filters above are
	// in place so they receive the same edit component as regular widget blocks.
	await soRegisterWidgetBlocks( sowbManuallyRegisteredBlocks );

	/**
	 * Registers a SiteOrigin Widget as a block.
	 *
	 * This function takes a widget configuration object and registers it as
	 * a block using the block editor API.
	 *
	 * @param {Object} widget - The widget configuration object
	 * @param {string} widget.class - PHP class name of the widget
	 * @param {string} widget.blockName - Block identifier (without the 'sowb/' prefix)
	 * @param {string} widget.name - Display name shown in the block inserter
	 * @param {string} widget.description - Block description text
	 * @param {string} [widget.icon] - URL to the widget's icon image
	 * @param {Array} [widget.keywords] - Search keywords for the block inserter
	 * @return {void}
	 */
	const setupSoWidgetBlock = ( widget ) => {
		if ( ! widget.blockName ) {
			return;
		}

		registerBlockType( 'sowb/' + widget.blockName, {
			apiVersion: 3,
			title: widget.name,
			description: widget.description,
			icon: sowbSetupIcon( widget ),
			category: 'siteorigin',
			keywords: widget.keywords ? widget.keywords : '',
			supports: {
				html: false,
				anchor: true,
			},
			attributes: {
				widgetClass: {
					type: 'string',
				},
				anchor: {
					type: 'string',
				},
				widgetData: {
					type: 'object',
				},
				widgetMarkup: {
					type: 'string',
				},
				widgetIcons: {
					type: 'array',
				},
			},
			edit: ( props ) => el( memoizedWidgetBlockEdit, { props, widget } ),
			save: function( context ) {
				// This block is dynamic and rendered on the server.
				return null;
			},
		} );
	};

	// Register all blocks that haven't been manually registered.
	sowbWidgets.forEach( setupSoWidgetBlock );

	// Add SiteOrigin Widgets Bundle Block Category Meta.
	updateCategory( 'siteorigin', {
		icon: el( 'img', {
			src: sowbBlockEditorAdmin.categoryIcon,
			alt: __( 'SiteOrigin Widgets Bundle Blocks Category', 'so-widgets-bundle' ),
			style: {
				height: '20px',
				width: '20px',
			}
		} )
	} );

	// Copy over assets to the editor iframe asap. When this script is itself
	// running inside the editor iframe, the parent setup paths own asset
	// cloning; self-cloning from the iframe can race with those paths.
	if ( ! window.frameElement ) {
		sowbMaybeSetupSiteEditorAssets();
	}
} )( window.wp.blocks, window.wp.i18n, window.wp.element, window.wp.components, window.wp.blockEditor );

let sowbSiteEditorCanvas = false;

/**
 * Returns the window that owns the given DOM element.
 *
 * @param {Element} element
 * @returns {Window}
 */
const sowbGetElementWindow = ( element ) => {
	return element && element.ownerDocument && element.ownerDocument.defaultView ?
		element.ownerDocument.defaultView :
		window;
};

const sowbSetupPreviewWidgets = ( previewElement ) => {
	sowbMaybeSetupSiteEditorAssets();

	const previewWindow = sowbGetElementWindow( previewElement );
	const previewJQuery = previewWindow.jQuery || jQuery;
	const previewSowb = previewWindow.sowb || window.sowb;

	if ( ! previewSowb ) {
		return;
	}

	previewJQuery( previewSowb ).trigger( 'setup_widgets', { preview: true } );
};

/**
 * Returns the editor canvas iframe element, or null if not found / not accessible.
 *
 * Checks the Site Editor canvas, the post-editor canvas, and — when this script
 * is running inside the iframe itself — window.frameElement.
 *
 * @returns {HTMLIFrameElement|null}
 */
const sowbGetEditorCanvasFrame = () => {
	const siteEditorCanvas = document.querySelector( '.edit-site-visual-editor__editor-canvas' );
	if ( siteEditorCanvas && siteEditorCanvas.contentDocument ) {
		return siteEditorCanvas;
	}

	const editorCanvas = document.querySelector( 'iframe[name="editor-canvas"]' );
	if ( editorCanvas && editorCanvas.contentDocument ) {
		return editorCanvas;
	}

	if ( window.frameElement && window.frameElement.contentDocument ) {
		return window.frameElement;
	}

	return null;
};

/**
 * Resolves the best available WordPress editor API object.
 *
 * Prefers `wp.oldEditor` (present in iframe contexts for legacy compatibility)
 * over `wp.editor` so that teardown and initialization use the same object.
 * Requires the resolved object to expose `initialize()` before returning it,
 * so callers always receive a fully usable API or null.
 *
 * Exposed as `window.sowbResolveWpEditor` so shared infrastructure
 * (e.g. `tinymce-field.js`) and third-party code can call the same
 * implementation without duplicating the guard logic.
 *
 * @returns {Object|null} The resolved editor API, or null if unavailable.
 */
const sowbResolveWpEditor = ( targetWindow = window ) => {
	if ( ! targetWindow.wp ) {
		return null;
	}
	const candidate = targetWindow.wp.oldEditor && typeof targetWindow.wp.oldEditor.initialize === 'function'
		? targetWindow.wp.oldEditor
		: ( targetWindow.wp.editor || null );
	return candidate && typeof candidate.initialize === 'function' ? candidate : null;
};
window.sowbResolveWpEditor = sowbResolveWpEditor;

const sowbGetFormSetupDependencyStatus = ( formWindow, $form ) => {
	const formJQuery = formWindow && formWindow.jQuery ? formWindow.jQuery : null;
	let isIframeForm = false;
	try {
		isIframeForm = !! (
			formWindow &&
			(
				formWindow !== window ||
				formWindow.frameElement
			)
		);
	} catch ( e ) {
		isIframeForm = formWindow !== window;
	}
	const hasRepeater = !! (
		$form &&
		$form.length &&
		$form.find( '.siteorigin-widget-field-repeater, .siteorigin-widget-field-type-repeater' ).length
	);
	const hasSlider = !! (
		$form &&
		$form.length &&
		$form.find( '.siteorigin-widget-field-type-slider' ).length
	);
	const hasTinyMCE = !! (
		$form &&
		$form.length &&
		$form.find( '.siteorigin-widget-field-type-tinymce' ).length
	);
	const hasWpEditorApi = !! ( formWindow && sowbResolveWpEditor( formWindow ) );

	return {
		hasJQuery: !! formJQuery,
		hasSowSetupForm: !! ( formJQuery && formJQuery.fn && typeof formJQuery.fn.sowSetupForm === 'function' ),
		hasJQueryUi: !! ( formJQuery && formJQuery.ui ),
		hasMouse: !! ( formJQuery && formJQuery.ui && formJQuery.ui.mouse ),
		hasSortable: !! ( formJQuery && formJQuery.fn && typeof formJQuery.fn.sortable === 'function' ),
		hasSliderApi: !! ( formJQuery && formJQuery.fn && typeof formJQuery.fn.slider === 'function' ),
		hasRepeater,
		hasSlider,
		hasTinyMCE,
		isIframeForm,
		hasWpEditorApi,
		ready: !! (
			formJQuery &&
			formJQuery.fn &&
			typeof formJQuery.fn.sowSetupForm === 'function' &&
			( ! hasRepeater || typeof formJQuery.fn.sortable === 'function' ) &&
			( ! hasSlider || typeof formJQuery.fn.slider === 'function' ) &&
			( ! hasTinyMCE || ! isIframeForm || hasWpEditorApi )
		),
	};
};

const sowbEnsureFormSetupDependencies = ( frame, formWindow, $form ) => {
	if ( frame ) {
		sowbEnsureIframeOldEditorApi( frame );
	}

	const dependencyStatus = sowbGetFormSetupDependencyStatus( formWindow, $form );

	if ( dependencyStatus.ready ) {
		return dependencyStatus;
	}

	return dependencyStatus;
};

const sowbResolveSiteEditorFrame = ( frame = null ) => {
	if ( frame ) {
		if ( frame.jquery ) {
			return frame.length > 0 ? frame[0] : null;
		}

		return typeof frame[0] !== 'undefined' ? frame[0] : frame;
	}

	if ( window.frameElement ) {
		sowbSiteEditorCanvas = window.frameElement;
		return window.frameElement;
	}

	let $canvas = jQuery( '.edit-site-visual-editor__editor-canvas' );
	if ( $canvas.length === 0 ) {
		$canvas = jQuery( 'iframe[name="editor-canvas"]' );
	}

	if ( $canvas.length > 0 ) {
		sowbSiteEditorCanvas = $canvas;
		return $canvas[0];
	}

	return null;
};

/**
 * Gets the widget form inside a specific block in either the main editor, or iframe.
 *
 * Locates the widget form element by client ID, handling both standard Block Editor
 * and Site Editor iframe contexts appropriately.
 *
 * @param {string} clientId - The block's client ID
 *
 * @returns {jQuery} jQuery reference to the widget form
 */
const sowbGetBlockForm = ( clientId ) => {
	const frame = sowbGetEditorCanvasFrame();

	if ( frame && frame.contentDocument && frame.contentWindow ) {
		// Update the cached canvas reference for sowbMaybeSetupSiteEditorAssets().
		if ( frame !== window.frameElement ) {
			sowbSiteEditorCanvas = jQuery( frame );
		}

		// Use the iframe's own jQuery so the returned object is already in the
		// correct jQuery instance — sowSetupForm(), wpColorPicker, and all other
		// field plugins are bound to this same jQuery when their scripts run.
		const frameJQuery = frame.contentWindow.jQuery || jQuery;
		const $iframeForm = frameJQuery( frame.contentDocument )
			.find( '[data-block="' + clientId + '"]' )
			.find( '.siteorigin-widget-form-main[data-class]' )
			.first();

		if ( $iframeForm.length > 0 ) {
			return $iframeForm;
		}
	}

	// No iframe (e.g. widgets.php Block Widgets screen, classic editor).
	const $mainDocumentForm = jQuery( document )
		.find( '[data-block="' + clientId + '"]' )
		.find( '.siteorigin-widget-form-main[data-class]' )
		.first();

	return $mainDocumentForm;
};

const sowbIsDirectWidgetBlock = ( block ) => {
	return block &&
		typeof block.name === 'string' &&
		block.name.indexOf( 'sowb/' ) === 0 &&
		block.name !== 'sowb/widget-block' &&
		block.isValid !== false;
};

const sowbFindDirectWidgetBlocks = ( blocks ) => {
	return blocks.reduce( ( foundBlocks, block ) => {
		if ( sowbIsDirectWidgetBlock( block ) ) {
			foundBlocks.push( block );
		}

		if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
			foundBlocks.push( ...sowbFindDirectWidgetBlocks( block.innerBlocks ) );
		}

		return foundBlocks;
	}, [] );
};

const sowbHasWidgetDataSnapshot = ( widgetData ) => {
	return widgetData &&
		typeof widgetData === 'object' &&
		! Array.isArray( widgetData ) &&
		Object.keys( widgetData ).length > 0;
};

const sowbGetBlockFormSnapshot = async ( $form ) => {
	if ( ! $form || $form.length === 0 ) {
		return null;
	}

	const formWindow = sowbGetElementWindow( $form[0] );
	const formJQuery = formWindow.jQuery || jQuery;
	const formForms = formWindow.sowbForms || sowbForms;
	const $ownerForm = formJQuery( $form[0] );

	if (
		formForms &&
		typeof formForms.getWidgetFormSnapshot === 'function'
	) {
		const widgetData = await formForms.getWidgetFormSnapshot( $ownerForm, {
			awaitAsync: true,
			triggerChange: false,
		} );
		return widgetData;
	}

	if (
		formForms &&
		typeof formForms.getWidgetFormValues === 'function'
	) {
		const widgetData = formForms.getWidgetFormValues( $ownerForm );
		return widgetData;
	}

	return null;
};

const sowbSnapshotBlockFormToAttributes = async ( props, $form = null ) => {
	const $snapshotForm = $form && $form.length ?
		$form :
		sowbGetBlockForm( props.clientId );

	if ( ! $snapshotForm || $snapshotForm.length === 0 ) {
		return null;
	}

	const widgetData = await sowbGetBlockFormSnapshot( $snapshotForm );

	if ( ! sowbHasWidgetDataSnapshot( widgetData ) ) {
		return null;
	}

	props.setAttributes( { widgetData } );

	return widgetData;
};

const sowbCloneBlocksWithWidgetData = ( blocks, widgetDataByClientId ) => {
	return blocks.map( ( block ) => {
		const clonedBlock = {
			...block,
			attributes: {
				...( block.attributes || {} ),
			},
			innerBlocks: block.innerBlocks ?
				sowbCloneBlocksWithWidgetData( block.innerBlocks, widgetDataByClientId ) :
				[],
		};

		if ( Object.prototype.hasOwnProperty.call( widgetDataByClientId, block.clientId ) ) {
			clonedBlock.attributes.widgetData = widgetDataByClientId[ block.clientId ];
		}

		return clonedBlock;
	} );
};

wp.hooks.addFilter(
	'editor.preSavePost',
	'sowb/direct-widget-block-save-bridge',
	async function( edits ) {
		try {
			const blockEditorSelect = wp.data.select( 'core/block-editor' );
			const blocks = blockEditorSelect.getBlocks();
			const directBlocks = sowbFindDirectWidgetBlocks( blocks );

			if ( directBlocks.length === 0 ) {
				return edits;
			}

			const widgetDataByClientId = {};

			for ( const block of directBlocks ) {
				try {
					const $form = sowbGetBlockForm( block.clientId );

					if ( $form.length === 0 ) {
						continue;
					}

					const widgetData = await sowbGetBlockFormSnapshot( $form );

					if ( ! sowbHasWidgetDataSnapshot( widgetData ) ) {
						continue;
					}

					widgetDataByClientId[ block.clientId ] = widgetData;
				} catch ( blockErr ) {
					console.error( 'SiteOrigin Widgets: failed to snapshot block ' + block.clientId, blockErr );
				}
			}

			const clientIds = Object.keys( widgetDataByClientId );
			if ( clientIds.length === 0 ) {
				return edits;
			}

			const clonedBlocks = sowbCloneBlocksWithWidgetData( blocks, widgetDataByClientId );
			const nextEdits = {
				...( edits || {} ),
				content: wp.blocks.serialize( clonedBlocks ),
			};
			const attrsByClientId = clientIds.reduce( ( attributes, clientId ) => {
				attributes[ clientId ] = {
					widgetData: widgetDataByClientId[ clientId ],
				};

				return attributes;
			}, {} );
			const blockEditorDispatch = wp.data.dispatch( 'core/block-editor' );

			if (
				blockEditorDispatch &&
				typeof blockEditorDispatch.updateBlockAttributes === 'function'
			) {
				blockEditorDispatch.updateBlockAttributes(
					clientIds,
					attrsByClientId
				);
			}

			return nextEdits;
		} catch ( err ) {
			console.error( 'SiteOrigin Widgets: preSavePost bridge failed, returning original edits', err );
			return edits;
		}
	}
);

const sowbCanvasCloneElements = [
	// WP Scripts and assets.
	'#jquery-core-js',
	'#jquery-migrate-js',
	'#underscore-js',
	'#editor-js-after',
	'#wp-tinymce-js',
	'#utils-js',
	'#editor-js-extra',
	'#editor-js',
	'#quicktags-js-extra',
	'#quicktags-js',
	'#wp-block-library-js-before',
	'#jquery-ui-core-js',
	'#jquery-ui-mouse-js',
	'#jquery-ui-slider-js',
	'#jquery-ui-sortable-js',
	'#jquery-ui-resizable-js',
	'#jquery-ui-draggable-js',
	'#jquery-touch-punch-js',
	'#iris-js',
	'#wp-color-picker-js',
	'#wplink-js-extra',
	'#wplink-js',
	'#buttons-css',
	'#dashicons-css',

	// Widget frontend preview scripts.
	'#dessandro-imagesLoaded-js',
	'#sow-image-grid-js',

	// Load all styles imported using load-styles.php.
	'link[href*="wp-admin/load-styles.php"]',

	// WP Templates.
	'#tmpl-attachment-details',
	'#tmpl-attachment-display-settings',
	'#tmpl-attachment',
	'#tmpl-embed-link-settings',
	'#tmpl-image-editor',
	'#tmpl-media-frame',
	'#tmpl-media-modal',
	'#tmpl-media-selection',
	'#tmpl-uploader-editor',
	'#tmpl-uploader-inline',
	'#tmpl-uploader-status-error',
	'#tmpl-uploader-status',
	'#tmpl-uploader-window',

	// Stylesheets.
	'#editor-buttons-css',
	'#forms-css',
	'#media-views-css',
	'#select2-css',

	// WB.
	'#siteorigin-widget-admin-css',
	'#siteorigin-widget-admin-js-extra',
	'#siteorigin-widget-admin-js',
	'#so-widgets-bundle-tpl-image-search-dialog',
	'#so-widgets-bundle-tpl-image-search-result-sponsored',
	'#so-widgets-bundle-tpl-image-search-result',
	'#sowb-pikaday-js',
	'#sowb-pikaday-css',
	'#wp-color-picker-alpha-js',
	'#so-autocomplete-field-js',
	'#so-code-field-js',
	'#so-date-range-field-js',
	'#so-date-range-field-css',
	'#so-icon-field-js',
	'#so-image-radio-field-js',
	'#so-image-size-js',
	'#so-media-field-js',
	'#so-multi-measurement-field-js',
	'#so-multiple-image-shape-field-js',
	'#so-multiple-media-field-js',
	'#so-order-field-js',
	'#so-posts-selector-field-js',
	'#so-presets-field-js',
	'#so-select-field-js',
	'#so-tabs-field-js',
	'#so-tinymce-field-js',
	'#so-toggle-field-js',
];

const sowbNormalizeAssetUrl = ( value, baseHref ) => {
	if ( ! value ) {
		return '';
	}

	try {
		return new URL(
			value,
			baseHref || window.location.href
		).href;
	} catch ( e ) {
		return '';
	}
};

const sowbGetDocumentHref = ( doc ) => {
	return doc && doc.location && doc.location.href ?
		doc.location.href :
		window.location.href;
};

const sowbGetElementById = ( doc, id ) => {
	if ( ! doc || ! id || typeof doc.getElementById !== 'function' ) {
		return null;
	}

	return doc.getElementById( id );
};

const sowbIsScriptHandleLoadedInWindow = ( targetWindow, scriptId ) => {
	if ( ! targetWindow || ! scriptId ) {
		return false;
	}

	switch ( scriptId ) {
		case 'jquery-core-js':
			return !! targetWindow.jQuery;
		case 'jquery-migrate-js':
			return !! (
				targetWindow.jQuery &&
				targetWindow.jQuery.migrateVersion
			);
		case 'jquery-ui-core-js':
			return !! (
				targetWindow.jQuery &&
				targetWindow.jQuery.ui
			);
		case 'jquery-ui-mouse-js':
			return !! (
				targetWindow.jQuery &&
				targetWindow.jQuery.ui &&
				targetWindow.jQuery.ui.mouse
			);
		case 'jquery-ui-slider-js':
			return !! (
				targetWindow.jQuery &&
				targetWindow.jQuery.fn &&
				typeof targetWindow.jQuery.fn.slider === 'function'
			);
		case 'jquery-ui-sortable-js':
			return !! (
				targetWindow.jQuery &&
				targetWindow.jQuery.fn &&
				typeof targetWindow.jQuery.fn.sortable === 'function'
			);
		case 'jquery-ui-resizable-js':
			return !! (
				targetWindow.jQuery &&
				targetWindow.jQuery.fn &&
				typeof targetWindow.jQuery.fn.resizable === 'function'
			);
		case 'jquery-ui-draggable-js':
			return !! (
				targetWindow.jQuery &&
				targetWindow.jQuery.fn &&
				typeof targetWindow.jQuery.fn.draggable === 'function'
			);
		case 'iris-js':
			return !! (
				targetWindow.jQuery &&
				targetWindow.jQuery.fn &&
				typeof targetWindow.jQuery.fn.iris === 'function'
			);
		case 'wp-color-picker-js':
			return !! (
				targetWindow.jQuery &&
				targetWindow.jQuery.fn &&
				typeof targetWindow.jQuery.fn.wpColorPicker === 'function'
			);
		case 'siteorigin-widget-admin-js-extra':
			return !! targetWindow.soWidgets;
		case 'siteorigin-widget-admin-js':
			return !! (
				targetWindow.sowbWidgetAdminScriptLoaded ||
				(
					targetWindow.sowbForms &&
					targetWindow.jQuery &&
					targetWindow.jQuery.fn &&
					typeof targetWindow.jQuery.fn.sowSetupForm === 'function'
				)
			);
		case 'so-tinymce-field-js':
			return !! (
				targetWindow.sowbTinyMCEFieldScriptLoaded ||
				typeof targetWindow.sowbGetTinyMCEInitPromise === 'function'
			);
		case 'editor-js':
			return !! sowbResolveWpEditor( targetWindow );
		case 'wp-tinymce-js':
			return !! targetWindow.tinymce;
		case 'quicktags-js':
			return !! targetWindow.QTags;
		case 'underscore-js':
			return !! targetWindow._;
		default:
			return false;
	}
};

const sowbGetEditorAssetSourceDocument = () => {
	try {
		if (
			window.frameElement &&
			window.parent &&
			window.parent.document
		) {
			return window.parent.document;
		}
	} catch ( e ) {
	}

	return document;
};

const sowbEnsureIframeEditorGlobals = ( frame ) => {
	if (
		! frame ||
		! frame.contentWindow
	) {
		return;
	}

	const iframeWindow = frame.contentWindow;
	const topWindow = window.top || window;
	const globals = [
		'ajaxurl',
		'userSettings',
		'wpCookies',
		'getUserSetting',
		'setUserSetting',
		'deleteUserSetting',
		'getAllUserSettings',
	];

	globals.forEach( ( globalName ) => {
		if (
			typeof iframeWindow[ globalName ] === 'undefined' &&
			typeof topWindow[ globalName ] !== 'undefined'
		) {
			iframeWindow[ globalName ] = topWindow[ globalName ];
		}
	} );
};

const sowbEnsureIframeOldEditorApi = ( frame ) => {
	if (
		! frame ||
		! frame.contentDocument ||
		! frame.contentWindow ||
		! frame.contentDocument.body
	) {
		return;
	}

	sowbEnsureIframeEditorGlobals( frame );

	const iframeWindow = frame.contentWindow;
	const resolvedEditor = sowbResolveWpEditor( iframeWindow );
	if ( resolvedEditor ) {
		if ( ! ( iframeWindow.wp.oldEditor && typeof iframeWindow.wp.oldEditor.initialize === 'function' ) ) {
			iframeWindow.wp.oldEditor = resolvedEditor;
		}
		return;
	}

	if ( frame.contentDocument.getElementById( 'sowb-editor-js-retry' ) ) {
		return;
	}

	if (
		iframeWindow.sowbEditorJsRetryPending ||
		(
			iframeWindow.sowbScriptClonePending &&
			iframeWindow.sowbScriptClonePending[ 'id:editor-js' ]
		)
	) {
		return;
	}

	const sourceDoc = sowbGetEditorAssetSourceDocument();
	const editorScript = sowbGetElementById( sourceDoc, 'editor-js' );
	if ( ! editorScript || ! editorScript.src ) {
		return;
	}

	const previousEditor = iframeWindow.wp && iframeWindow.wp.editor;
	const retryScript = frame.contentDocument.createElement( 'script' );
	retryScript.id = 'sowb-editor-js-retry';
	retryScript.src = editorScript.src;
	retryScript.async = false;
	retryScript.onload = () => {
		iframeWindow.sowbEditorJsRetryPending = false;
		if (
			! iframeWindow.wp ||
			! iframeWindow.wp.editor ||
			typeof iframeWindow.wp.editor.initialize !== 'function'
		) {
			return;
		}

		const oldEditor = iframeWindow.wp.editor;
		iframeWindow.wp.oldEditor = oldEditor;

		if (
			previousEditor &&
			previousEditor !== oldEditor
		) {
			Object.assign( previousEditor, oldEditor );
			iframeWindow.wp.editor = previousEditor;
		}

	};
	retryScript.onerror = ( event ) => {
		iframeWindow.sowbEditorJsRetryPending = false;
	};

	iframeWindow.sowbEditorJsRetryPending = true;
	frame.contentDocument.body.appendChild( retryScript );
};

/**
 * Gets the editor settings for a TinyMCE container.
 *
 * @param {Element} element TinyMCE container element.
 *
 * @returns {Object} Parsed editor settings.
 */
const sowbGetEditorSettingsFromContainer = ( element ) => {
	const $element = jQuery( element );
	const editorSettings = $element.data( 'editorSettings' );

	if ( editorSettings && typeof editorSettings === 'object' ) {
		return editorSettings;
	}

	const rawSettings = $element.attr( 'data-editor-settings' );
	if ( ! rawSettings ) {
		return {};
	}

	try {
		const parsedSettings = JSON.parse( rawSettings );
		return parsedSettings && typeof parsedSettings === 'object' ?
			parsedSettings :
			{};
	} catch ( e ) {
		return {};
	}
};

/**
 * Derives the asset root for a TinyMCE external plugin URL.
 *
 * @param {string} pluginUrl External plugin URL.
 *
 * @returns {string} Normalized asset root URL.
 */
const sowbGetTinyMCEExternalPluginAssetRoot = ( pluginUrl ) => {
	const normalizedPluginUrl = sowbNormalizeAssetUrl(
		pluginUrl,
		window.location.href
	);

	if ( ! normalizedPluginUrl ) {
		return '';
	}

	const assetRoot = new URL( normalizedPluginUrl );
	assetRoot.search = '';
	assetRoot.hash = '';

	const jsPathIndex = assetRoot.pathname.indexOf( '/js/' );
	if ( jsPathIndex !== -1 ) {
		assetRoot.pathname = assetRoot.pathname.substring( 0, jsPathIndex + 4 );
		return assetRoot.href;
	}

	const lastSlashIndex = assetRoot.pathname.lastIndexOf( '/' );
	assetRoot.pathname = assetRoot.pathname.substring( 0, lastSlashIndex + 1 );

	return assetRoot.href;
};

/**
 * Gets TinyMCE external plugin asset roots from mounted iframe widget forms.
 *
 * @param {jQuery} $canvasBody The iframe body.
 *
 * @returns {Set} Set of normalized asset root URLs.
 */
const sowbGetTinyMCEExternalPluginAssetRoots = ( $canvasBody ) => {
	const assetRoots = new Set();

	$canvasBody.find( '.siteorigin-widget-tinymce-container' ).each( function() {
		const settings = sowbGetEditorSettingsFromContainer( this );
		const externalPlugins = settings &&
			settings.tinymce &&
			settings.tinymce.external_plugins ?
			settings.tinymce.external_plugins :
			null;

		if ( ! externalPlugins || typeof externalPlugins !== 'object' ) {
			return;
		}

		Object.values( externalPlugins ).forEach( ( pluginUrl ) => {
			if ( typeof pluginUrl !== 'string' || pluginUrl.length === 0 ) {
				return;
			}

			const assetRoot = sowbGetTinyMCEExternalPluginAssetRoot( pluginUrl );
			if ( assetRoot ) {
				assetRoots.add( assetRoot );
			}
		} );
	} );

	return assetRoots;
};

/**
 * Appends a cloned source element to the canvas when it is not already present.
 *
 * @param {jQuery}  $canvasBody The iframe body.
 * @param {Element} element     The source element to clone.
 * @param {jQuery}  $source     Source document wrapper.
 *
 * @returns {boolean} Whether an element was cloned.
 */
const sowbCloneElementToCanvas = ( $canvasBody, element, $source ) => {
	if ( ! element || ! element.outerHTML ) {
		return false;
	}

	const canvasDoc = $canvasBody[0] && $canvasBody[0].ownerDocument;
	const sourceDoc = $source && $source[0] && $source[0].nodeType === 9 ?
		$source[0] :
		element.ownerDocument;
	const canvasWindow = canvasDoc && canvasDoc.defaultView;
	const tagName = element.tagName ? element.tagName.toLowerCase() : '';
	const assetAttribute = element.hasAttribute( 'src' ) ? 'src' : (
		element.hasAttribute( 'href' ) ? 'href' : ''
	);
	const assetUrl = assetAttribute ?
		sowbNormalizeAssetUrl(
			element.getAttribute( assetAttribute ),
			sowbGetDocumentHref( sourceDoc )
		) :
		'';
	const scriptCloneKey = tagName === 'script' ?
		( element.id ? 'id:' + element.id : ( assetUrl ? 'url:' + assetUrl : '' ) ) :
		'';

	if ( scriptCloneKey && canvasWindow ) {
		canvasWindow.sowbScriptClonePending = canvasWindow.sowbScriptClonePending || {};

		if (
			canvasWindow.sowbScriptClonePending[ scriptCloneKey ] ||
			sowbIsScriptHandleLoadedInWindow( canvasWindow, element.id )
		) {
			return false;
		}
	}

	if (
		element.id &&
		sowbGetElementById( canvasDoc, element.id )
	) {
		return false;
	}

	if ( assetUrl ) {
		const selector = assetAttribute === 'src' ? 'script[src]' : 'link[href]';
		const existingAsset = $canvasBody.find( selector ).toArray().some( ( candidate ) => {
			return sowbNormalizeAssetUrl(
				candidate.getAttribute( assetAttribute ),
				sowbGetDocumentHref( candidate.ownerDocument )
			) === assetUrl;
		} );

		if ( existingAsset ) {
			return false;
		}
	}

	let clonedElement;
	if ( tagName === 'script' ) {
		clonedElement = canvasDoc.createElement( 'script' );
		for ( const attribute of element.attributes ) {
			clonedElement.setAttribute( attribute.name, attribute.value );
		}
		clonedElement.textContent = element.textContent;
		clonedElement.async = false;
		if ( scriptCloneKey && canvasWindow ) {
			clonedElement.addEventListener( 'load', () => {
				delete canvasWindow.sowbScriptClonePending[ scriptCloneKey ];
			}, { once: true } );
			clonedElement.addEventListener( 'error', () => {
				delete canvasWindow.sowbScriptClonePending[ scriptCloneKey ];
			}, { once: true } );
		}
	} else {
		clonedElement = element.cloneNode( true );
	}
	if ( scriptCloneKey && canvasWindow ) {
		canvasWindow.sowbScriptClonePending[ scriptCloneKey ] = true;
	}
	$canvasBody[0].appendChild( clonedElement );
	if (
		tagName === 'script' &&
		! assetUrl &&
		scriptCloneKey &&
		canvasWindow
	) {
		delete canvasWindow.sowbScriptClonePending[ scriptCloneKey ];
	}
	return true;
};

/**
 * Clones the inline localization script related to a source script handle.
 *
 * @param {jQuery} $canvasBody The iframe body.
 * @param {jQuery} $source     Source document wrapper.
 * @param {string} scriptId    Source script element id.
 *
 * @returns {boolean} Whether an inline script was cloned.
 */
const sowbCloneRelatedInlineScript = ( $canvasBody, $source, scriptId ) => {
	if ( ! scriptId ) {
		return false;
	}

	const inlineScriptId = scriptId + '-extra';
	const sourceDoc = $source && $source[0] && $source[0].nodeType === 9 ?
		$source[0] :
		document;
	const canvasDoc = $canvasBody[0] && $canvasBody[0].ownerDocument;
	const inlineScript = sowbGetElementById( sourceDoc, inlineScriptId );

	if (
		! inlineScript ||
		sowbGetElementById( canvasDoc, inlineScriptId )
	) {
		return false;
	}

	return sowbCloneElementToCanvas( $canvasBody, inlineScript, $source );
};

/**
 * Appends elements to the canvas body.
 *
 * @param {jQuery}   $canvasBody  The jQuery object representing the canvas body.
 * @param {Document} [sourceDoc]  The document to read source nodes from. When
 *                                this helper runs from inside the editor iframe,
 *                                the source `<script>`/`<link>` tags live in the
 *                                parent admin document, not in the iframe's own
 *                                document. Defaults to the current document for
 *                                backwards compatibility with parent-side calls.
 */
const sowbCloneElementsToCanvas = ( $canvasBody, sourceDoc ) => {
	// jQuery( document ) is ambiguous when this script runs inside an iframe
	// (it picks the iframe's own document). Passing the source doc explicitly
	// removes the ambiguity. The default fallback to `document` preserves the
	// existing parent-side caller path.
	const $source = sourceDoc ? jQuery( sourceDoc ) : jQuery( document );

	for ( const selector of sowbCanvasCloneElements ) {
		const $element = $source.find( selector );
		if ( $element.length === 0 ) {
			continue;
		}

		const element = $element[0];
		if ( ! element ) {
			continue;
		}

		sowbCloneElementToCanvas( $canvasBody, element, $source );
	}
};

/**
 * Clones parent-document assets related to mounted TinyMCE external plugins.
 *
 * @param {jQuery}   $canvasBody The iframe body.
 * @param {Document} sourceDoc   Source document to read assets from.
 */
const sowbCloneTinyMCEExternalPluginAssets = ( $canvasBody, sourceDoc ) => {
	const assetRoots = sowbGetTinyMCEExternalPluginAssetRoots( $canvasBody );
	if ( assetRoots.size === 0 ) {
		return;
	}

	const $source = sourceDoc ? jQuery( sourceDoc ) : jQuery( document );
	const sourceBaseHref = sowbGetDocumentHref(
		sourceDoc || document
	);
	const assetRootList = [ ...assetRoots ];

	const cloneAssetElement = ( element ) => {
		if ( element.tagName.toLowerCase() === 'script' ) {
			sowbCloneRelatedInlineScript( $canvasBody, $source, element.id );
		}

		sowbCloneElementToCanvas( $canvasBody, element, $source );
	};

	if ( assetRootList.some( ( assetRoot ) => assetRoot.indexOf( '/web-font-selector/js/' ) !== -1 ) ) {
		$source
			.find( '#web-font-loader-js, script[src*="ajax.googleapis.com/ajax/libs/webfont/"]' )
			.each( function() {
				cloneAssetElement( this );
			} );
	}

	$source.find( 'script[src], link[rel="stylesheet"][href]' ).each( function() {
		const tagName = this.tagName.toLowerCase();
		const assetUrl = sowbNormalizeAssetUrl(
			this.getAttribute( tagName === 'script' ? 'src' : 'href' ),
			sourceBaseHref
		);
		const matchedRoot = assetUrl ?
			assetRootList.find( ( assetRoot ) => assetUrl.indexOf( assetRoot ) === 0 ) :
			'';

		if ( matchedRoot ) {
			cloneAssetElement( this );
		}
	} );
};

/**
 * Sets up assets required for the Site Editor iframe.
 *
 * This function ensures that necessary HTML templates, scripts, and styles are copied
 * from the main document to the Site Editor iframe. It also sets the `ajaxurl`
 * variable in the iframe's `contentWindow` if it is not already defined.
 *
 * The function performs the following steps:
 * 1. Resolves the current Site Editor iframe and confirms it is accessible.
 * 2. Copies elements specified in `sowbCanvasCloneElements` to the iframe's canvas body.
 * 3. Copies elements specified in `sowbCanvasRemoveElements` to the iframe's canvas body
 *    and removes the originals from the main document.
 * 4. Sets the `ajaxurl` variable in the iframe's `contentWindow` for AJAX requests if it
 *    is not already defined.
 */
const sowbMaybeSetupSiteEditorAssets = ( frame = null ) => {
	const currentFrame = sowbResolveSiteEditorFrame( frame );

	if ( ! currentFrame ) {
		return;
	}

	let iframeDocument;
	try {
		iframeDocument = currentFrame.contentDocument || (
			currentFrame.contentWindow &&
			currentFrame.contentWindow.document
		);
	} catch ( e ) {
		return;
	}

	const $canvasBody = jQuery( iframeDocument ).find( 'body' );

	if ( $canvasBody.length === 0 ) {
		return;
	}

	// When this helper runs from INSIDE the iframe (the IIFE branch at the
	// bottom of widget-block.js), the source <script>/<link> nodes live in
	// the parent admin document, not in this script's own document. Read
	// them from the parent. When this helper runs from the parent (Site
	// Editor / post editor parent path), our own document is already
	// correct.
	let sourceDoc;
	try {
		sourceDoc = window.frameElement && window.parent && window.parent.document
			? window.parent.document
			: document;
	} catch ( e ) {
		// Cross-origin guard. Should not trigger for the same-origin
		// editor canvas, but if it does we fall back to the local document.
		sourceDoc = document;
	}

	sowbEnsureIframeEditorGlobals( currentFrame );
	sowbCloneElementsToCanvas( $canvasBody, sourceDoc );
	sowbCloneTinyMCEExternalPluginAssets( $canvasBody, sourceDoc );
	sowbEnsureIframeOldEditorApi( currentFrame );

	// Is ajaxurl set?
	try {
		if (
			currentFrame.contentWindow &&
			typeof currentFrame.contentWindow.ajaxurl === 'undefined'
		) {
			currentFrame.contentWindow.ajaxurl = window.top.ajaxurl;
		}
	} catch ( e ) {
		// Ignore cross-window errors (e.g. iframe detached between the
		// initial check and the assignment).
	}
};

/**
 * Find all legacy SiteOrigin widget blocks in the editor.
 *
 * Recursively traverses blocks and their inner blocks to find all legacy
 * SiteOrigin widget blocks. Handles widget areas differently by directly
 * accessing their blocks through the block editor store.
 *
 * @param {Array} blocks Array of blocks to check.
 *
 * @returns {Array} Array of found legacy widget blocks.
 */
const sowbFindLegacyBlocks = ( blocks ) => {
	return blocks.reduce( ( legacyBlocks, block ) => {
		 // If the current block is widget area, we need to handle
		 // things slightly different.
		if ( block.name === 'core/widget-area' ) {
			const innerBlocks = wp.data.select( 'core/block-editor' ).getBlocks( block.clientId );

			innerBlocks.forEach( widget => {
				if ( widget.name === 'sowb/widget-block' ) {
					legacyBlocks.push( widget );
				}
			} );

			return legacyBlocks;
		}

		if ( block.name === 'sowb/widget-block' ) {
			legacyBlocks.push( block );
		}

		// Recursively check innerBlocks if they exist.
		if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
			legacyBlocks.push( ...sowbFindLegacyBlocks( block.innerBlocks ) );
		}

		return legacyBlocks;
	}, [] );
};

const sowbIsWidgetActive = ( widgetClass ) => {
	return sowbBlockEditorAdmin.widgets.find(widget => widget.class === widgetClass);
};

let sowbMigrateBlockSubscribe = false;
let sowbMigrationInProgress = false;
/**
 * Migrate SiteOrigin Widget Blocks to their dedicated widget block.
 *
 * This function subscribes to the block editor data store and
 * migrates any legacy 'sowb/widget-block' blocks to their new block types.
 * After migration, it removes the legacy widget block and unsubscribes
 * from the data store.
 */
const sowbMigrateOldBlocks = () => {
	if ( sowbMigrationInProgress === true ) {
		return;
	}

	const blocks = wp.data.select( 'core/block-editor' ).getBlocks();
	if ( blocks.length === 0 ) {
		return;
	}

	// Find any legacy WB blocks.
	const legacyBlocks = sowbFindLegacyBlocks( blocks );
	if ( legacyBlocks.length === 0 ) {
		return;
	}

	// Confirm consent, or admin status.
	if ( ! sowbBlockEditorAdmin.consent ) {
		// If this is the initial check, we might be able to stop
		// further attempts to process this.
		if ( typeof sowbMigrateBlockSubscribe === 'function' ) {
			sowbMigrateBlockSubscribe();
		}
		return;
	}

	sowbMigrationInProgress = true;

	try {
		legacyBlocks.forEach( currentBlock => {
			try {
				// Before migrating widget, confirm the widget is active.
				if ( ! sowbIsWidgetActive( currentBlock.attributes.widgetClass ) ) {
					// We need to update the widgetNotFound flag to indicate
					// the widget is no longer available.
					const attributes = { ...currentBlock.attributes };
					attributes.widgetNotFound = true;
					wp.data.dispatch( 'core/block-editor' ).updateBlock(
						currentBlock.clientId,
						{ attributes }
					);
					return;
				}

				const newBlock = wp.blocks.createBlock(
					'sowb/' + currentBlock.attributes.widgetClass.toLowerCase().replace( /_/g, '-' ),
					currentBlock.attributes
				);

				if ( newBlock ) {
					wp.data.dispatch( 'core/block-editor' ).replaceBlock(
						currentBlock.clientId,
						newBlock
					);
				}
			} catch ( err ) {
				console.error( 'SiteOrigin Widget Block migration failed:', err );
			}
		} );
	} finally {
		// Finished migrating, reset the flag.
		setTimeout( () => {
			sowbMigrationInProgress = false;
		}, 100 );
	}

	if ( sowbBlockEditorAdmin.consentGiven ) {
		return false;
	}

	return sowbRemoveLegacyWidgetBlock();
};

/**
 * Remove the legacy widget block, and prevent further migration attempts.
 *
 * This function prevents further migration attempts by unsubscribing
 * the migration process.
 *
 * @returns {boolean} Returns false to prevent further execution.
 */
const sowbRemoveLegacyWidgetBlock = () => {
	setTimeout( () => {
		if ( typeof sowbMigrateBlockSubscribe === 'function' ) {
			sowbMigrateBlockSubscribe();
		}
	}, 0 );

	return false;
};

/**
 * Check if a block is a missing SiteOrigin widget block.
 *
 * @param {Object} block Block to check.
 *
 * @return {boolean} True if block is a missing SiteOrigin widget.
 */
const sowbIsMissingBlockSowb = ( block ) => {
	return block.name === 'core/missing' &&
		block.isValid &&
		block.attributes &&
		block.attributes.originalName.startsWith( 'sowb/' )
};

/**
 * Find all missing SiteOrigin widget blocks in the editor.
 *
 * Recursively traverses blocks and their inner blocks to find missing
 * SiteOrigin widgets. Handles widget areas differently by directly
 * accessing their blocks through the block editor store.
 *
 * @param {Array} inactiveBlocks Array of blocks to check.
 *
 * @return {Array} Array of found missing SiteOrigin widget blocks.
 */
const sowbFindInactiveBlock = ( inactiveBlocks ) => {
	return inactiveBlocks.reduce( ( blocks, block ) => {
		 // If the current block is widget area, we need to handle
		 // things slightly different.
		if ( block.name === 'core/widget-area' ) {
			const innerBlocks = wp.data.select( 'core/block-editor' ).getBlocks( block.clientId );

			innerBlocks.forEach( block => {
				if ( sowbIsMissingBlockSowb( block ) ) {
					blocks.push( block );
				}
			} );

			return blocks;
		}

		if ( sowbIsMissingBlockSowb( block ) ) {
			blocks.push( block );
		}

		// Recursively check innerBlocks if present.
		if ( block.innerBlocks && block.innerBlocks.length > 0 ) {
			blocks.push( ...sowbFindInactiveBlock( block.innerBlocks ) );
		}

		return blocks;
	}, [] );
};

jQuery( function( $ ) {
	if ( ! $( 'body.block-editor-page' ).length ) {
		return;
	}

	if ( sowbBlockEditorAdmin.consent ) {
		sowbMigrateBlockSubscribe = wp.data.subscribe( sowbMigrateOldBlocks );
	}

	/**
	 * Update warning messages for inactive SiteOrigin widget blocks.
	 *
	 * @param {Array} blocks Array of inactive blocks to update.
	 */
	const sowbUpdateInactiveBlocksMessage = ( blocks ) => {
		blocks.forEach( block => {
			const message = document.querySelector( `[data-block="${ block.clientId }"] .block-editor-warning__message` );
			if ( ! message ) {
				return;
			}

			message.innerHTML = sprintf(
				wp.i18n.__( 'The "%s" block is currently not available. The plugin or theme that powers the block might be deactivated or not installed. You can leave it as is or remove it. %sRead our troubleshooting guide for more details%s.', 'so-widgets-bundle' ),
				`<strong>${block.attributes.originalName}</strong>`,
				'<a href="https://siteorigin.com/widgets-bundle/troubleshooting/" target="_blank" rel="noopener noreferrer">',
				'</a>'
			);
		} );
	};

	/**
	 * Handle inactive SiteOrigin widget blocks in the editor.
	 *
	 * Sets up subscription to monitor for missing widget
	 * blocks and updates their warning messages with
	 * helpful information. Uses setTimeout to
	 * ensure DOM is ready before modifying messages.
	 *
	 * @return {Function} Cleanup function that unsubscribes from block editor.
	*/
	const sowbHandleInactiveWidgets = wp.data.subscribe( () => {
		// Are we good to start checking?
		const blocks = wp.data.select( 'core/block-editor' ).getBlocks();
		if ( blocks.length === 0 ) {
			return;
		}

		const inactiveBlocks = sowbFindInactiveBlock( blocks );
		if ( ! inactiveBlocks.length ) {
			return;
		}

		// Unsubscribe inside the deferred callback so we don't fire again
		// before the DOM update has had a chance to apply.
		setTimeout( () => {
			sowbHandleInactiveWidgets();
			sowbUpdateInactiveBlocksMessage( inactiveBlocks );
		}, 0 );
	} );
} );

if (
	typeof adminpage != 'undefined' &&
	adminpage != 'widgets-php' &&
	typeof wp.data.select == 'function'
) {

	let sowbTimeoutSetup = false;
	// Setup SiteOrigin Widgets Block Validation.
	wp.data.subscribe( function() {
		if (
			! sowbTimeoutSetup &&
			typeof wp.data.select( 'core/editor' ) == 'object' &&
			wp.data.select( 'core/editor' ).isSavingPost()
		) {
			sowbTimeoutSetup = true;
			var saveCheck = setInterval( function() {

				if (
					! wp.data.select( 'core/editor' ).isSavingPost() &&
					! wp.data.select( 'core/editor' ).isAutosavingPost() &&
					wp.data.select( 'core/editor' ).didPostSaveRequestSucceed()
				) {
					clearInterval( saveCheck );
					var showPrompt = true;
					var sowbCurrentBlocks = wp.data.select( 'core/block-editor' ).getBlocks();
					for ( var i = 0; i < sowbCurrentBlocks.length; i++ ) {
						if ( sowbCurrentBlocks[ i ].name.startsWith( 'sowb/' ) && sowbCurrentBlocks[ i ].isValid ) {
							// Use sowbGetBlockForm so the form is found in both the
							// main document and the Site Editor iframe canvas.
							var $form = sowbGetBlockForm( sowbCurrentBlocks[ i ].clientId );
							if ( ! $form.length ) {
								$form = jQuery( '#block-' + sowbCurrentBlocks[ i ].clientId ).find( '.so-widget-block-form' );
							}
							if ( ! sowbForms.validateFields( $form, showPrompt) ) {
								showPrompt = false;
							}
							( function( $capturedForm ) {
								$capturedForm.find( '.siteorigin-widget-field-is-required input' ).on( 'change', function() {
									sowbForms.validateFields( $capturedForm );
								} );
							} )( $form );
						}
					}
					sowbTimeoutSetup = false;
				}
			}, 250 );
		}
	} );
}
