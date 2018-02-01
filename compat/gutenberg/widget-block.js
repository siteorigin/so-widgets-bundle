( function ( blocks, i18n, element, components ) {
	
	var el = element.createElement;
	var BlockControls = blocks.BlockControls;
	var InspectorControls = blocks.InspectorControls;
	var SelectControl = InspectorControls.SelectControl;
	var withAPIData = components.withAPIData;
	var withState = components.withState;
	var IconButton = components.IconButton;
	var Placeholder = components.Placeholder;
	var __ = i18n.__;
	
	blocks.registerBlockType( 'sowb/widget-block', {
		title: __( 'SiteOrigin Widget', 'so-widgets-bundle' ),
		
		description: __( 'Select a SiteOrigin widget from the dropdown.', 'so-widgets-bundle' ),
		
		icon: 'so-widget-icon',
		
		category: 'widgets',
		
		attributes: {
			widgetClass: {
				type: 'string',
			},
			widgetData: {
				type: 'object',
			}
		},
		
		edit: withState( {
			editing: true,
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
			}
			
			return toGet;
		} )( function ( props ) {
			if ( ! props.widgets.data ) {
				return "loading widgets!";
			}
			
			if ( props.attributes.widgetClass ) {
				
				if ( props.editing && ! props.widgetform.data ) {
					return "loading widget form!";
				}
				
				if ( ! props.editing && ! props.widgetpreview.data ) {
					return "loading widget preview!";
				}
			}
			
			function onWidgetClassChange( newWidgetClass ) {
				props.setAttributes( { widgetClass: newWidgetClass, widgetData: null } );
				props.setState( { formInitialized: false } );
			}
			
			function setupWidgetForm( formContainer ) {
				var $mainForm = $( formContainer ).find( '.siteorigin-widget-form-main' );
				if ( $mainForm.length > 0 && ! props.formInitialized ) {
					var $previewContainer = $mainForm.siblings('.siteorigin-widget-preview');
					$previewContainer.find( '> a' ).on( 'click', function ( event ) {
						event.stopImmediatePropagation();
						props.setState( { editing: false, previewInitialized: false } );
					} );
					$mainForm.sowSetupForm();
					if ( props.attributes.widgetData ) {
						sowbForms.setWidgetFormValues( $mainForm, props.attributes.widgetData );
					}
					$mainForm.on( 'change', function () {
						props.setAttributes( { widgetData: sowbForms.getWidgetFormValues( $mainForm ) } );
					} );
					props.setState( { formInitialized: true } );
				}
			}
			
			function switchToEditing() {
				props.setState( { editing: true, formInitialized: false } );
			}
			
			function setupWidgetPreview() {
				if ( ! props.previewInitialized ) {
					$( window.sowb ).trigger( 'setup_widgets' );
					props.setState( { previewInitialized: true } );
				}
			}
			
			var widgetsOptions = props.widgets.data.map( function ( widget ) {
				return { value: widget.class, label: widget.name };
			} );
			
			if ( props.editing ) {
				var widgetForm = props.widgetform ? props.widgetform.data : '';
				
				return !! focus && el(
					Placeholder,
					{
						key: 'placeholder',
						icon: 'so-widget-icon',
						label: __( 'SiteOrigin Widget', 'so-widgets-bundle' ),
						instructions: __( 'Select the type of widget you want to use:', 'so-widgets-bundle' )
					},
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
				);
			} else {
				return [
					!! focus && el(
						BlockControls,
						{ key: 'controls' },
						el(
							IconButton,
							{
								className: 'components-icon-button components-toolbar__control',
								label: __( 'Edit widget.', 'so-widgets-bundle' ),
								onClick: switchToEditing,
								icon: 'edit'
							}
						)
					),
					el( 'div', {
						key: 'preview',
						className: 'so-widget-gutenberg-preview-container',
						dangerouslySetInnerHTML: { __html: props.widgetpreview.data },
						ref: setupWidgetPreview,
					} )
				];
			}
		} ) ),
		
		save: function () {
			// Render in PHP
			return null;
		}
	} );
} )( window.wp.blocks, window.wp.i18n, window.wp.element, window.wp.components );
