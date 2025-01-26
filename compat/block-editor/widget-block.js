( function( blocks, i18n, element, components, blockEditor ) {

	const el = element.createElement;
	const registerBlockType = blocks.registerBlockType;
	const BlockControls = blockEditor.BlockControls;
	const {
		Component,
		useMemo
	} = element;

	const {
		Toolbar,
		ToolbarButton,
		Placeholder,
		Button,
		Spinner
	} = components;

	const __ = i18n.__;

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
	 *
	 * @returns {void}
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
		 * @returns {boolean} - Returns true if the HTML contains text or images, false otherwise.
		 */
		const checkHtmlForContent = ( html ) => {
			const tempElement = jQuery( '<div>' + html + '</div>' );
			let renderPreviewHtml = false;

			const widgetContent = tempElement.find( 'div:first-of-type' );
			if ( widgetContent.length > 0 ) {
				console.log(widgetContent);
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
				widgetIcons: widgetPreview.icons
			} );

			if ( canLockPostSaving ) {
				wp.data.dispatch( 'core/editor' ).unlockPostSaving();
			}
		} )
		.fail( ( response ) => {
			setState( { widgetFormHtml: '<div>' + getAjaxErrorMsg( response ) + '</div>' } );
		} )
		.always( () => {
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
			$mainForm.sowSetupForm();

			if ( props.attributes.widgetData ) {
				// If we call `setWidgetFormValues` with the last parameter ( `triggerChange` ) set to false,
				// it won't show the correct values for some fields e.g. color and media fields.
				sowbForms.setWidgetFormValues( $mainForm, props.attributes.widgetData );
			} else {
				props.setAttributes( { widgetData: sowbForms.getWidgetFormValues( $mainForm ) } );
			}

			$mainForm.on( 'change', () => {
				// As setAttributes doesn't support callbacks, we have to manually pass the widgetData to the preview.
				var widgetData = sowbForms.getWidgetFormValues( $mainForm );
				props.setAttributes( { widgetData: widgetData } );
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
								loadingForm: false
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
							Toolbar,
							{ label: __( 'Preview widget.' + editing, 'so-widgets-bundle' ) },
							el(
								ToolbarButton,
								{
									className: 'components-icon-button components-toolbar__control',
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
							className: 'so-widget-placeholder',
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
							Toolbar,
							{ label: __( 'Edit widget.', 'so-widgets-bundle' ) },
							el(
								ToolbarButton,
								{
									className: 'components-icon-button components-toolbar__control',
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

	/**
	 * Register a SiteOrigin Widget Block.
	 *
	 * @param {Object} widget - The widget configuration object.
	 * @param {string} widget.class - The class of the widget.
	 * @param {string} widget.blockName - The block name.
	 * @param {string} widget.name - The display name of the widget.
	 * @param {string} widget.description - The description of the widget.
	 * @param {Array} [widget.keywords] - An array of keywords for the widget.
	 */
	const setupSoWidgetBlock = function( widget ) {
		registerBlockType( 'sowb/' + widget.blockName, {
			title: __( widget.name, 'so-widgets-bundle' ),
			description: __( widget.description, 'so-widgets-bundle' ),
			icon: function() {
				return widget.icon ?
				el(
					'img',
					{
						className: 'widget-icon so-widget-icon so-block-editor-icon',
						src: widget.icon,
						alt: widget.name
					}
				)
				: el(
					'span',
					{
						className: 'widget-icon so-widget-icon so-block-editor-icon so-widget-icon-default'
					}
				)
			},
			category: 'widgets',
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
				return null; // This block is dynamic and rendered on the server.
			},
		} );
	};

	// Register all SiteOrigin Blocks.
	sowbBlockEditorAdmin.widgets.forEach( setupSoWidgetBlock );


	registerBlockType( 'sowb/widget-block', {
		title: __( 'SiteOrigin Widget', 'so-widgets-bundle' ),
		description: __( 'Select a SiteOrigin widget from the dropdown.', 'so-widgets-bundle' ),
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
		icon: function() {
			return el(
				'span',
				{
					className: 'widget-icon so-widget-icon so-block-editor-icon'
				}
			)
		},
		edit: function () {
			const isAdmin = wp.data.select( 'core' ).getCurrentUser().is_super_admin;
			return el(
				Placeholder,
				{
					label: __( 'SiteOrigin Widget', 'so-widgets-bundle' ),
					instructions: __( 'This block is using the legacy format. Please migrate to the new block format.', 'so-widgets-bundle' )
				},
				isAdmin ?
				el(
					Button,
					{
						isPrimary: true,
						onClick: () => {
							jQuery.post( ajaxurl, {
								action: 'so_widgets_block_migration_notice_consent',
								nonce: sowbBlockMigration.nonce
							} );

							migrateOldBlocks();
						},
					},
					__( 'Migrate to New Block Format', 'so-widgets-bundle' )
				) :

				el(
					'span',
					null,
					__( 'Please contact your site administrator to migrate this block.', 'so-widgets-bundle' )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp.blocks, window.wp.i18n, window.wp.element, window.wp.components, window.wp.blockEditor );

/**
 * Migrate SiteOrigin Widget Blocks to their dedicated widget block.
 *
 * This function subscribes to the block editor data store and
 * migrates any legacy 'sowb/widget-block' blocks to their new block types.
 * After migration, it removes the legacy widget block and unsubscribes
 * from the data store.
 */
const migrateOldBlocks = () => {
	const blocks = wp.data.select( 'core/block-editor' ).getBlocks();
	if ( blocks.length === 0 ) {
		return;
	}

	// Find any legacy WB blocks.
	const widgetBlocks = blocks.filter( block => block.name === 'sowb/widget-block' );
	if ( widgetBlocks.length === 0 ) {
		return;
	}

	// Confirm consent, or admin status.
	if ( ! sowbBlockEditorAdmin.consent && ! wp.data.select( 'core' ).getCurrentUser().is_super_admin ) {
		migrateOldBlocks();
		return;
	}

	// Migrate the blocks.
	widgetBlocks.forEach( currentBlock => {
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
	} );

	return removeLegacyWidgetBlock();
};

/**
 * Remove the legacy widget block, and prevent further migration attempts.
 *
 * This function prevents further migration attempts by unsubscribing
 * the migration process.
 *
 * @returns {boolean} Returns false to prevent further execution.
 */
const removeLegacyWidgetBlock = () => {
	setTimeout( () => {
		migrateOldBlocks();
	}, 0 );

	return false;
};

if (
	typeof adminpage != 'undefined' &&
	adminpage != 'widgets-php' &&
	typeof wp.data.select == 'function'
) {
	if ( sowbBlockEditorAdmin.consent ) {
		wp.data.subscribe( migrateOldBlocks );
	}

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
							$form = jQuery( '#block-' + sowbCurrentBlocks[ i ].clientId ).find( '.so-widget-placeholder' );
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
