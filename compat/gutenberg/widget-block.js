( function ( blocks, i18n, element, components ) {
	
	var el = element.createElement;
	var BlockControls = blocks.BlockControls;
	var SelectControl = components.SelectControl;
	var withAPIData = components.withAPIData;
	var withState = components.withState;
	var Toolbar = components.Toolbar;
	var IconButton = components.IconButton;
	var Placeholder = components.Placeholder;
	var Spinner  = components.Spinner;
	var __ = i18n.__;
	
	blocks.registerBlockType( 'sowb/widget-block', {
		title: __( 'SiteOrigin Widget' ),
		
		description: __( 'Select a SiteOrigin widget from the dropdown.' ),
		
		icon: function() {
			return el(
				'span',
				{
					className: 'widget-icon so-widget-icon so-gutenberg-icon'
				}
			)
		},
		
		category: 'widgets',
		
		supports: {
			html: false,
		},
		
		attributes: {
			widgetClass: {
				type: 'string',
			},
			widgetData: {
				type: 'object',
			}
		},
		
		edit: withState( {
			editing: false,
			formInitialized: false,
			previewInitialized: false,
		} )( withAPIData( function( props ) {
			var toGet = {
				widgets: '/sowb/v1/widgets'
			};
			
			if ( props.attributes.widgetClass ) {
				if ( props.editing ) {
					toGet.widgetform = '/sowb/v1/widgets/forms?widgetClass=' + props.attributes.widgetClass;
				} else {
					var data = props.attributes.widgetData || {};
					toGet.widgetpreview = '/sowb/v1/widgets/previews?widgetClass=' +
						props.attributes.widgetClass +
						'&widgetData=' +
						encodeURIComponent( JSON.stringify( data ) );
				}
			} else if ( ! props.editing ) {
				props.setState( { editing: true } );
			}
			
			return toGet;
		} )( function ( props ) {
			var loadingWidgets = !props.widgets.data;
			var loadingWidgetForm = props.attributes.widgetClass && props.widgetform && !props.widgetform.data;
			var loadingWidgetPreview = props.attributes.widgetClass && props.widgetpreview && !props.widgetpreview.data;
			
			function onWidgetClassChange( newWidgetClass ) {
				if ( newWidgetClass !== '' ) {
					props.setAttributes( { widgetClass: newWidgetClass, widgetData: null } );
					props.setState( { formInitialized: false } );
				}
			}
			
			function switchToEditing() {
				props.setState( { editing: true, formInitialized: false } );
			}
			
			function switchToPreview() {
				props.setState( { editing: false, previewInitialized: false } );
			}
			
			function setupWidgetForm( formContainer ) {
				var $mainForm = $( formContainer ).find( '.siteorigin-widget-form-main' );
				if ( $mainForm.length > 0 && ! props.formInitialized ) {
					var $previewContainer = $mainForm.siblings('.siteorigin-widget-preview');
					$previewContainer.find( '> a' ).on( 'click', function ( event ) {
						event.stopImmediatePropagation();
						switchToPreview();
					} );
					$mainForm.data( 'backupDisabled', true );
					$mainForm.sowSetupForm();
					if ( props.attributes.widgetData ) {
						sowbForms.setWidgetFormValues( $mainForm, props.attributes.widgetData, false, false );
					}
					$mainForm.on( 'change', function () {
						props.setAttributes( { widgetData: sowbForms.getWidgetFormValues( $mainForm ) } );
					} );
					props.setState( { formInitialized: true } );
				}
			}
			
			function setupWidgetPreview() {
				if ( ! props.previewInitialized ) {
					$( window.sowb ).trigger( 'setup_widgets' );
					props.setState( { previewInitialized: true } );
				}
			}
			
			if ( props.editing ) {
				
				var widgetsOptions = [];
				if ( props.widgets && props.widgets.data ) {
					props.widgets.data.sort( function ( a, b ) {
						if ( a.name < b.name ) {
							return -1;
						} else if ( a.name > b.name ) {
							return 1;
						}
						return 0;
					} );
					widgetsOptions = props.widgets.data.map( function ( widget ) {
						return { value: widget.class, label: widget.name };
					} );
					widgetsOptions.unshift( { value: '', label: __( 'Select widget type' ) } );
				}
				
				var widgetForm = props.widgetform ? props.widgetform.data : '';
				
				return [
					!! props.focus && !! widgetForm && el(
						BlockControls,
						{ key: 'controls' },
						el(
							Toolbar,
							null,
							el(
								IconButton,
								{
									className: 'components-icon-button components-toolbar__control',
									label: __( 'Preview widget.' ),
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
							label: __( 'SiteOrigin Widget' ),
							instructions: __( 'Select the type of widget you want to use:' )
						},
						( loadingWidgets || loadingWidgetForm ?
							el( Spinner ) :
							el(
								'div',
								{ className: 'so-widget-gutenberg-container' },
								el(
									SelectControl,
									{
										options: widgetsOptions,
										value: props.attributes.widgetClass,
										onChange: onWidgetClassChange,
									}
								),
								el( 'div', {
									className: 'so-widget-gutenberg-form-container',
									dangerouslySetInnerHTML: { __html: widgetForm },
									ref: setupWidgetForm,
								} )
							)
						)
					)
				];
			} else {
				var widgetPreview = props.widgetpreview ? props.widgetpreview.data : '';
				return [
					!! props.focus && el(
						BlockControls,
						{ key: 'controls' },
						el(
							Toolbar,
							null,
							el(
								IconButton,
								{
									className: 'components-icon-button components-toolbar__control',
									label: __( 'Edit widget.' ),
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
							className: 'so-widget-gutenberg-preview-container'
						},
						( loadingWidgetPreview ?
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
		} ) ),
		
		save: function () {
			// Render in PHP
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.i18n, window.wp.element, window.wp.components );
