( function(blocks, i18n, element, components, compose, blockEditor ) {

	var el = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var BlockControls = blockEditor.BlockControls;
	var withState = compose.withState;
	var Toolbar = components.Toolbar;
	var ToolbarButton = components.ToolbarButton;
	var Placeholder = components.Placeholder;
	var Spinner = components.Spinner;
	var __ = i18n.__;

	var getAjaxErrorMsg = function( response ) {
		var errorMessage = '';
		if ( response.hasOwnProperty( 'responseJSON' ) ) {
			errorMessage = response.responseJSON.message;
		} else if ( response.hasOwnProperty( 'responseText' ) ) {
			errorMessage = response.responseText;
		}
		return errorMessage;
	}

	var sowPreviewRequest = false;
	function sowbGenerateWidgetPreview( props, widgetData = false, widgetClass = false ) {
		if ( sowPreviewRequest ) {
			return;
		}
		props.setState( { loadingWidgetPreview: true } );
		sowPreviewRequest = true;
		setTimeout( () => sowPreviewRequest = false, 1000 )
		const canLockPostSaving = typeof wp.data.select( 'core/editor' ) == 'object' &&
			typeof wp.data.dispatch( 'core/editor' ) == 'object';

		if ( canLockPostSaving ) {
			wp.data.dispatch( 'core/editor' ).lockPostSaving();
		}

		jQuery.post( {
			url: sowbBlockEditorAdmin.restUrl + 'sowb/v1/widgets/previews',
			beforeSend: function( xhr ) {
				xhr.setRequestHeader( 'X-WP-Nonce', sowbBlockEditorAdmin.nonce );
			},
			data: {
				anchor: props.attributes.anchor,
				widgetClass: widgetClass,
				widgetData: widgetData ? widgetData : props.attributes.widgetData || {}
			}
		} )
		.done( function( widgetPreview ) {
			props.setState( {
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
		.fail( function( response ) {
			props.setState( { widgetFormHtml: '<div>' + getAjaxErrorMsg( response ) + '</div>', } );
		} )
		.always( function() {
			props.setState( { loadingWidgetPreview: false } );
		} );
	}

	function sowbSetupWidgetForm( props, widgetClass ) {
		var $mainForm = jQuery( '[data-block="' + props.clientId + '"]' ).find( '.siteorigin-widget-form-main' );
		if ( $mainForm.length > 0 && ! props.formInitialized ) {
			var $previewContainer = $mainForm.siblings( '.siteorigin-widget-preview' );
			$previewContainer.find( '> a' ).on( 'click', function( event ) {
				event.stopImmediatePropagation();
				props.setState( { editing: false, previewInitialized: false } );
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
			$mainForm.on( 'change', function() {
				props.setState( {
					widgetSettingsChanged: true,
					widgetPreviewHtml: null,
					previewInitialized: false
				} );

				// As setAttributes doesn't support callbacks, we have to manually pass the widgetData to the preview.
				var widgetData = sowbForms.getWidgetFormValues( $mainForm );
				props.setAttributes( { widgetData: widgetData } );
				sowbGenerateWidgetPreview( props, widgetData, widgetClass );
			} );
			props.setState( { formInitialized: true } );
		}
	}

	const setupSoWidgetBlock = function( widget ) {
		registerBlockType( 'sowb/' + widget.blockName, {
			title: __( 'SiteOrigin ' + widget.name, 'so-widgets-bundle' ),
			description: __( widget.description, 'so-widgets-bundle' ),
			icon: function() {
				return el(
					'span',
					{
						className: 'widget-icon so-widget-icon so-block-editor-icon'
					}
				)
			},
			category: 'widgets',
			keywords: widget.keywords ?? '',
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
			edit: withState( {
				editing: false,
				formInitialized: false,
				previewInitialized: false,
				widgetFormHtml: '',
				widgetSettingsChanged: false,
				widgetPreviewHtml: '',
				loadingWidgetPreview: false,
				loadingForm: false,
			} )( function( props ) {
				if ( props.editing || ! props.attributes.widgetData ) {
					var loadWidgetForm = ! props.widgetFormHtml.length;
					if ( loadWidgetForm && ! props.loadingForm ) {
						props.setState( { loadingForm: true } );
						jQuery.post( {
							url: sowbBlockEditorAdmin.restUrl + 'sowb/v1/widgets/forms',
							beforeSend: function( xhr ) {
								xhr.setRequestHeader( 'X-WP-Nonce', sowbBlockEditorAdmin.nonce );
							},
							data: {
								widgetClass: widget.class,
								widgetData: props.attributes.widgetData,
							}
						} )
						.done( function( widgetForm ) {
							props.setState( { widgetFormHtml: widgetForm, loadingForm: false } );
						} )
						.fail( function( response ) {
							props.setState( { widgetFormHtml: '<div>' + getAjaxErrorMsg( response ) + '</div>', } );
						} );
					}

					var widgetForm = props.widgetFormHtml ? props.widgetFormHtml : '';

					return [
						!! widgetForm && el(
							BlockControls,
							{
								key: 'controls',
							},
							el(
								Toolbar,
								{
									label: __( 'Preview widget.', 'so-widgets-bundle' ),
								},
								el(
									ToolbarButton,
									{
										className: 'components-icon-button components-toolbar__control',
										label: __( 'Preview widget.', 'so-widgets-bundle' ),
										onClick: ( () => props.setState( {
											editing: false,
											previewInitialized: false
										} ) ),
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
								label: widget.name,
								instructions: widget.description
							},
							( props.loadingWidgets || loadWidgetForm ?
								el( 'div', {
										className: 'so-widgets-spinner-container'
									},
									el(
										'span',
										null,
										el( Spinner )
									)
								) :
								el(
									'div',
									{ className: 'so-widget-block-container' },
									el( 'div', {
										className: 'so-widget-block-form-container',
										dangerouslySetInnerHTML: { __html: widgetForm },
										ref: ( () => sowbSetupWidgetForm( props, widget.class ) ),
									} )
								)
							)
						)
					];
				} else {
					var loadWidgetPreview = ! props.loadingWidgets &&
						! props.editing &&
						! props.widgetPreviewHtml &&
						props.attributes.widgetData &&
						! props.loadingWidgetPreview;

					if ( loadWidgetPreview ) {
						props.setAttributes( {
							widgetMarkup: null,
							widgetIcons: null
						} );
						sowbGenerateWidgetPreview( props, false, widget.class );
					}
					var widgetPreview = props.widgetPreviewHtml ? props.widgetPreviewHtml : '';
					return [
						el(
							BlockControls,
							{ key: 'controls' },
							el(
								Toolbar,
								{
									label: __( 'Preview widget.', 'so-widgets-bundle' ),
								},
								el(
									ToolbarButton,
									{
										className: 'components-icon-button components-toolbar__control',
										label: __( 'Edit widget.', 'so-widgets-bundle' ),
										onClick: ( () => props.setState( {
											editing: true,
											formInitialized: false
										} ) ),
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
							( loadWidgetPreview ?
								el( 'div', {
										className: 'so-widgets-spinner-container'
									},
									el(
										'span',
										null,
										el( Spinner )
									)
								) :
								el( 'div', {
									dangerouslySetInnerHTML: { __html: widgetPreview },
									ref: ( () => {
										if ( ! props.previewInitialized ) {
											jQuery( window.sowb ).trigger( 'setup_widgets', { preview: true } );
											props.setState( { previewInitialized: true } );
										}
									} ),
								} )
							)
						)
					];
				}
			} ),

			save: function( context ) {
				return null;
			}
		} );
	}

	// Add all SiteOrigin Blocks.
	sowbBlockEditorAdmin.widgets.forEach( setupSoWidgetBlock );


	// Register a stripped back version of our old block to allow for migration.
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
		edit: function () {
			return null;
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.i18n, window.wp.element, window.wp.components, window.wp.compose, window.wp.blockEditor );

if (
	typeof adminpage != 'undefined' &&
	adminpage != 'widgets-php' &&
	typeof wp.data.select == 'function'
	) {
	let soIsEditorReady = false;
	const migrateOldBlocks = wp.data.subscribe( () => {
		if (
			! soIsEditorReady &&
			wp.data.select( 'core/block-editor' ).getBlocks().length > 0
		) {
			soIsEditorReady = true;
			migrateOldBlocks();

			// Find any legacy WB blocks.
			const widgetBlocks = wp.data.select( 'core/block-editor' ).getBlocks()
				.filter( block => block.name === 'sowb/widget-block' );

			if ( widgetBlocks.length === 0 ) {
				return;
			}

			widgetBlocks.forEach( currentBlock => {
				const newBlock = wp.blocks.createBlock(
					'sowb/' + currentBlock.attributes.widgetClass.toLowerCase().replace( /_/g, '-' ),
					currentBlock.attributes
				);

				if ( newBlock ) {
					wp.data.dispatch('core/block-editor' ).replaceBlock(
						currentBlock.clientId,
						newBlock
					);
				}
			} );
		}
	} );

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
