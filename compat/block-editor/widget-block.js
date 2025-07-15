( async function( blocks, i18n, element, components, blockEditor ) {

	const el = element.createElement;
	const registerBlockType = blocks.registerBlockType;
	const BlockControls = blockEditor.BlockControls;
	const {
		Component,
		useMemo
	} = element;

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
	}

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
	 */
	const sowbGenerateWidgetPreview = ( props, loadingWidgetPreview, setState, widgetData = false, widgetClass = false ) => {
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

		jQuery.post( {
			url: sowbBlockEditorAdmin.restUrl + 'sowb/v1/widgets/previews',
			beforeSend: ( xhr ) => {
				xhr.setRequestHeader( 'X-WP-Nonce', sowbBlockEditorAdmin.nonce );
			},
			data: {
				anchor: props.attributes.anchor,
				widgetClass: widgetClass,
				widgetData: widgetData ? widgetData : props.attributes.widgetData || {}
			}
		} )
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
			setState( { widgetFormHtml: '<div>' + getAjaxErrorMsg( response ) + '</div>' } );
		} )
		.always( () => {
			if ( canLockPostSaving ) {
				wp.data.dispatch( 'core/editor' ).unlockPostSaving();
			}
			setState( { loadingWidgetPreview: false } );
		} );
	}

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
	 */
	const sowbSetupWidgetForm = ( props, state, setState ) => {
		const $mainForm = jQuery( '[data-block="' + props.clientId + '"]' ).find( '.siteorigin-widget-form-main' );

		if ( $mainForm.length > 0 && ! state.formInitialized ) {
			const $previewContainer = $mainForm.siblings( '.siteorigin-widget-preview' );
			$previewContainer.find( '> a' ).on( 'click', function( event ) {
				event.stopImmediatePropagation();

				setState( {
					editing: false,
					previewInitialized: false,
					widgetPreviewHtml: false,
				} );
			} );

			$mainForm.data( 'backupDisabled', true );

			if ( props.attributes.widgetData ) {
				// If we call `setWidgetFormValues` with the last parameter
				// ( `triggerChange` ) set to false, it won't show the correct values
				// for some fields e.g. color and media fields.
				sowbForms.setWidgetFormValues( $mainForm, props.attributes.widgetData );
			} else {
				props.setAttributes( { widgetData: sowbForms.getWidgetFormValues( $mainForm ) } );
			}

			$mainForm.sowSetupForm();

			$mainForm.on( 'change', function() {
				// As setAttributes doesn't support callbacks, we have to manually
				// pass the widgetData to the preview.
				var widgetData = sowbForms.getWidgetFormValues( $mainForm );
				props.setAttributes( { widgetData: widgetData } );

				// Set up a preview debounce timer to prevent multiple requests.
				clearTimeout( state.previewDebounceTimer );

				state.previewDebounceTimer = setTimeout( () => {
					sowbGenerateWidgetPreview(
						props,
						state.loadingWidgetPreview,
						setState,
						widgetData,
						props.widget.class
					);
				}, 300 );
			} );
			setState( { formInitialized: true } );
		}
	}

	/**
	 * WidgetBlockEdit component.
	 *
	 * This component handles the editing and previewing of SiteOrigin
	 * Widget Blocks. It manages:
	 * - the state of the widget form.
	 * - the state of the widget preview.
	 * - the initialization and loading of the widget form and preview.
	 *
	 */
	class WidgetBlockEdit extends Component {

		constructor( props ) {
			super( props );

			this.initialState = {
				// If this widget was just added, show the form.
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

			this.state = {
				... this.initialState,
				isStillMounted: true
			};

			// Store the widget class if it's not already set.
			if ( ! props.attributes.widgetClass ) {
				this.props.setAttributes( { widgetClass: props.widget.class } );
			}
		}

		componentDidMount() {
			this.setState( {
				...this.initialState,
				isStillMounted: true
			} );

			this.loadWidgetData();
		}

		componentWillUnmount() {
			this.setState( {
				...this.initialState,
				isStillMounted: false
			} );
		}

		componentDidUpdate( prevProps, prevState ) {
			if ( ! this.state.isStillMounted ) {
				return;
			}

			if (
				this.state.editing !== prevState.editing ||
				this.props.attributes.widgetData !== prevProps.attributes.widgetData
			) {
				// If there's been an update, clear the preview.
				this.setState( {
					widgetSettingsChanged: true,
					widgetPreviewHtml: null,
					previewInitialized: false
				} );

				this.loadWidgetData();
			}
		}

		loadWidgetData() {
			if ( ! this.state.isStillMounted ) {
				return;
			}

			const {
				editing,
				widgetFormHtml,
				loadingForm,
				loadingWidgetPreview
			} = this.state;
			const { attributes } = this.props;

			if (
				editing ||
				! attributes.widgetData
			) {
				const loadWidgetForm = ! widgetFormHtml.length;

				if ( loadWidgetForm && ! loadingForm ) {
					this.setState( { loadingForm: true });
					jQuery.post( {
						url: sowbBlockEditorAdmin.restUrl + 'sowb/v1/widgets/forms',
						beforeSend: (xhr) => {
							xhr.setRequestHeader( 'X-WP-Nonce', sowbBlockEditorAdmin.nonce );
						},
						data: {
							widgetClass: this.props.widget.class,
							widgetData: attributes.widgetData,
						}
					} )
					.done( ( widgetForm ) => {
						this.setState( {
							widgetFormHtml: widgetForm
						} );

						setTimeout( () => {
							this.setState( {
								loadingForm: false,
								formInitialized: false,
							} );
						}, 0 );
					} )
					.fail( ( response) => {
						this.setState( { widgetFormHtml: '<div>' + getAjaxErrorMsg( response ) + '</div>' } );
					} );
				}
				return;
			}

			const loadWidgetPreview = ! loadingWidgetPreview && ! editing;

			if ( loadWidgetPreview ) {
				this.props.setAttributes( {
					widgetMarkup: null,
					widgetIcons: null
				} );

				sowbGenerateWidgetPreview(
					this.props,
					this.state.loadingWidgetPreview,
					this.setState.bind( this ),
					false,
					this.props.widget.class
				);
			}
		}

		render() {
			const { editing, widgetFormHtml, loadingForm, widgetPreviewHtml, loadingWidgetPreview, previewInitialized } = this.state;
			const { attributes } = this.props;

			return el(
				'div',
				null,
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
									onClick: () => this.setState( {
										editing: false,
									} ),
									icon: 'visibility'
								}
							)
						)
					),
					el(
						Placeholder,
						{
							key: 'placeholder',
							className: 'so-widget-block-form',
							label: this.props.widget.name,
							instructions: this.props.widget.description
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
							className: 'so-widget-block-container',
							dangerouslySetInnerHTML: { __html: widgetFormHtml },
							ref: () => sowbSetupWidgetForm(
								this.props,
								this.state,
								this.setState.bind( this ),
								this.props.widget.class
							)
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
									onClick: () => this.setState( {
										editing: true,
										loadingForm: false,
										widgetFormHtml: '',
										formInitialized: false,
									} ),
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
							ref: () => {
								if ( ! previewInitialized ) {
									jQuery( window.sowb ).trigger( 'setup_widgets', { preview: true } );
									this.setState( { previewInitialized: true } );
								}
							}
						} )
					)
				]
			);
		}
	}


	registerBlockType( 'sowb/widget-block', {
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
								jQuery.post( ajaxurl, {
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
	}

	// Register all Widget Bundle widgets, and build `sowbManuallyRegisteredBlocks`.
	await Promise.all(
		Object.entries( sowbWidgets ).map( async ( [ key, widget ] ) => {
			identifyBlocksThatNeedManualRegistration( widget, key );
		} )
	);

	// Register all of our manually registered blocks.
	await soRegisterWidgetBlocks( sowbManuallyRegisteredBlocks );

	// Modify all of the manually registered blocks with additional properties.
	Object.entries( sowbManuallyRegisteredBlocks ).forEach( ( [ key, widget ] ) => {
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
		registerBlockType( 'sowb/' + widget.blockName, {
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
	await sowbWidgets.forEach( setupSoWidgetBlock );

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
} )( window.wp.blocks, window.wp.i18n, window.wp.element, window.wp.components, window.wp.blockEditor );

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
	return sowbBlockEditorAdmin.widgets.find(widget => widget.class === widgetClass)
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

		setTimeout( () => {
			sowbUpdateInactiveBlocksMessage( inactiveBlocks );
		}, 0 );

		sowbHandleInactiveWidgets();
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
							$form = jQuery( '#block-' + sowbCurrentBlocks[ i ].clientId ).find( '.so-widget-block-form' );
							if ( ! sowbForms.validateFields( $form, showPrompt) ) {
							 	showPrompt = false;
							}
							$form.find( '.siteorigin-widget-field-is-required input' ).on( 'change', function() {
								sowbForms.validateFields( $form );
							} );
						}
					}
					sowbTimeoutSetup = false;
				}
			}, 250 );
		}
	} );
}
