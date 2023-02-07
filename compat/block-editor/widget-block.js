( function ( editor, blocks, i18n, element, components, compose, blockEditor ) {

	var el = element.createElement;
	var registerBlockType = blocks.registerBlockType;
	var BlockControls = blockEditor.BlockControls;
	var ComboboxControl = components.ComboboxControl;
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

	registerBlockType( 'sowb/widget-block', {
		title: __( 'SiteOrigin Widget', 'so-widgets-bundle' ),

		description: __( 'Select a SiteOrigin widget from the dropdown.', 'so-widgets-bundle' ),

		icon: function() {
			return el(
				'span',
				{
					className: 'widget-icon so-widget-icon so-block-editor-icon'
				}
			)
		},

		category: 'widgets',

		keywords: [sowbBlockEditorAdmin.widgets.reduce( function ( keywords, widgetObj ) {
			if ( keywords.length > 0 ) {
				keywords += ',';
			}
			return keywords + widgetObj.name;
		}, '' )],

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
			widgetHtml: {
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
		} )( function ( props ) {

			function onWidgetClassChange( newWidgetClass ) {
				if ( newWidgetClass !== '' ) {
					if ( props.widgetSettingsChanged && ! confirm( sowbBlockEditorAdmin.confirmChangeWidget ) ) {
						return false;
					}
					props.setAttributes( { widgetClass: newWidgetClass, widgetData: null } );
					props.setState( {
						editing: true,
						widgetFormHtml: null,
						formInitialized: false,
						widgetSettingsChanged: false,
						widgetPreviewHtml: null,
						previewInitialized: false
					} );
				}
			}

			function generateWidgetPreview( widgetData = false) {
				if (
					typeof wp.data.select( 'core/editor' ) == 'object' &&
					typeof wp.data.dispatch( 'core/editor' ) == 'object'
				) {
					wp.data.dispatch( 'core/editor' ).lockPostSaving();
				}

				jQuery.post( {
					url: sowbBlockEditorAdmin.restUrl + 'sowb/v1/widgets/previews',
					beforeSend: function( xhr ) {
						xhr.setRequestHeader( 'X-WP-Nonce', sowbBlockEditorAdmin.nonce );
					},
					data: {
						anchor: props.attributes.anchor,
						widgetClass: props.attributes.widgetClass,
						widgetData: widgetData ? widgetData : props.attributes.widgetData || {}
					}
				} )
				.done( function( widgetPreview ) {
					props.setState( {
						widgetPreviewHtml: widgetPreview.html,
						previewInitialized: false,
					} );

					props.setAttributes( {
						widgetHtml: widgetPreview.html,
						widgetIcons: widgetPreview.icons
					} );

					if (
						typeof wp.data.select( 'core/editor' ) == 'object' &&
						typeof wp.data.dispatch( 'core/editor' ) == 'object'
					) {
						wp.data.dispatch( 'core/editor' ).unlockPostSaving();
					}
				} )
				.fail( function( response ) {
					props.setState( { widgetFormHtml: '<div>' + getAjaxErrorMsg( response ) + '</div>', } );
				} );
			}

			function switchToEditing() {
				props.setState( { editing: true, formInitialized: false } );
			}

			function switchToPreview() {
				props.setState( { editing: false, previewInitialized: false } );
			}

			function setupWidgetForm( formContainer ) {
				var $mainForm = jQuery( formContainer ).find( '.siteorigin-widget-form-main' );

				if ( $mainForm.length > 0 && ! props.formInitialized ) {
					var $previewContainer = $mainForm.siblings('.siteorigin-widget-preview');
					$previewContainer.find( '> a' ).on( 'click', function ( event ) {
						event.stopImmediatePropagation();
						switchToPreview();
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
					$mainForm.on( 'change', function () {
						props.setState( {
							widgetSettingsChanged: true,
							widgetPreviewHtml: null,
							previewInitialized: false
						} );
						
						// As setAttributes doesn't support callbacks, we have to manully pass the widgetData to the preview.
						var widgetData = sowbForms.getWidgetFormValues( $mainForm );
						props.setAttributes( { widgetData: widgetData } );
						generateWidgetPreview( widgetData );
					} );
					props.setState( { formInitialized: true } );
				}
			}

			function setupWidgetPreview() {
				if ( ! props.previewInitialized ) {
					jQuery( window.sowb ).trigger( 'setup_widgets', { preview: true } );
					props.setState( { previewInitialized: true } );
				}
			}

			if ( props.editing || ! props.attributes.widgetClass || ! props.attributes.widgetData ) {
				var widgetsOptions = [];
				if ( sowbBlockEditorAdmin.widgets ) {
					widgetsOptions = sowbBlockEditorAdmin.widgets.map( function ( widget ) {
						return { value: widget.class, label: widget.name };
					} );
				}

				var loadWidgetForm = props.attributes.widgetClass && ! props.widgetFormHtml;
				if ( loadWidgetForm ) {
					jQuery.post( {
						url: sowbBlockEditorAdmin.restUrl + 'sowb/v1/widgets/forms',
						beforeSend: function ( xhr ) {
							xhr.setRequestHeader( 'X-WP-Nonce', sowbBlockEditorAdmin.nonce );
						},
						data: {
							widgetClass: props.attributes.widgetClass,
							widgetData: props.attributes.widgetData,
						}
					} )
					.done( function( widgetForm ) {
						props.setState( { widgetFormHtml: widgetForm } );
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
									onClick: switchToPreview,
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
							label: __( 'SiteOrigin Widget', 'so-widgets-bundle' ),
							instructions: __( 'Select the type of widget you want to use:', 'so-widgets-bundle' )
						},
						( props.loadingWidgets || loadWidgetForm ?
							el( Spinner ) :
							el(
								'div',
								{ className: 'so-widget-block-container' },
								el(
									ComboboxControl,
									{
										className: 'so-widget-autocomplete-field',
										label: __( 'Widget type', 'so-widgets-bundle' ),
										value: props.attributes.widgetClass,
										onFilterValueChange: function ( value ) {}, // Avoid React notice and onChange potentially not triggering.
										onChange: onWidgetClassChange,
										options: widgetsOptions,
									}
								),
								el( 'div', {
									className: 'so-widget-block-form-container',
									dangerouslySetInnerHTML: { __html: widgetForm },
									ref: setupWidgetForm,
								} )
							)
						)
					)
				];
			} else {

				var loadWidgetPreview = ! props.loadingWidgets &&
					! props.editing &&
					! props.widgetPreviewHtml &&
					props.attributes.widgetClass &&
					props.attributes.widgetData;
				if ( loadWidgetPreview ) {
					props.setAttributes( {
						widgetHtml: null,
						widgetIcons: null
					} );
					generateWidgetPreview();
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
									onClick: switchToEditing,
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
								ref: setupWidgetPreview,
							} )
						)
					)
				];
			}
		} ),

		save: function ( context ) {
			return null;
		}
	} );
} )( window.wp.editor, window.wp.blocks, window.wp.i18n, window.wp.element, window.wp.components, window.wp.compose, window.wp.blockEditor );

// Setup SiteOrigin Widgets Block Validation.
var sowbTimeoutSetup = false;
if (
	typeof adminpage != 'undefined' &&
	adminpage != 'widgets-php' &&
	typeof wp.data.select == 'function'
) {
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
						if ( sowbCurrentBlocks[ i ].name == 'sowb/widget-block' && sowbCurrentBlocks[ i ].isValid ) {
							$form = jQuery( '#block-' + sowbCurrentBlocks[ i ].clientId ).find( '.so-widget-placeholder ');
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
