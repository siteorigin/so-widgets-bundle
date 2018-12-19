/* globals jQuery, sowbForms, confirm, soWidgetsVC */
var sowbForms = window.sowbForms || {};

sowbForms.setupVcWidgetForm = function () {
	var $ = jQuery;
	var $widgetDropdown = $( '[name="so_widget_class"]' );
	var $formContainer = $( '.siteorigin_widget_form_container' );
	var formDirty = false;
	$formContainer.on( 'change', '.siteorigin-widget-field', function () {
		formDirty = true;
		var widgetData = sowbForms.getWidgetFormValues( $formContainer );
		var widgetClass = $widgetDropdown.val();

		var jsonString = JSON.stringify( { widget_class: widgetClass, widget_data: widgetData } );
		jsonString = jsonString.replace( /\\/g, '\\\\' );
		$( '[name="so_widget_data"]' ).val( jsonString );
	} );

	var prevWidget;
	$widgetDropdown.mousedown( function () {
		prevWidget = $widgetDropdown.find( 'option:selected' );
	} );

	$widgetDropdown.on( 'change', function ( event ) {
		if ( formDirty && !confirm( soWidgetsVC.confirmChangeWidget ) ) {
			prevWidget.attr( 'selected', true );
			return;
		}

		formDirty = false;

		var widget = $widgetDropdown.val();

		var data = {
			'action': 'sowb_vc_widget_render_form',
			'widget': widget,
		};

		$.post(
			soWidgetsVC.ajaxUrl,
			data,
			function ( result ) {
				$formContainer.html( result );
				// To ensure data is updated.
				$formContainer.trigger( 'change' );
			},
			'html'
		);
	} );
	vc.atts.sowb_json_escaped = {
		parse: function ( param ) {
			var $field = this.content().find( '.wpb_vc_param_value[name=' + param.param_name + ']' );
			// We double encode in the front end to prevent accidental decoding when the content is set on the
			// WP visual editor.
			return _.escape( _.escape( $field.val().toString() ).replace( /\[/g, '&#91;' ).replace( /\]/g, '&#93;' ) );
		},
		render: function ( param, value ) {
			return _.unescape( _.unescape( value ) ).replace( /&#91;/g, '[' ).replace( /&#93;/g, ']' );
		}
	};
	vc.events.on( "shortcodeView:updated:siteorigin_widget_vc", function () {

		if ( typeof vc.frame_window !== 'undefined' && typeof vc.frame_window.sowb !== 'undefined' ) {

			// Have to use jQuery from iframe window for triggered events to be detected there.
			var $ = vc.frame_window.jQuery;
			var sowb = vc.frame_window.sowb;

			// Trigger Widgets Bundle widgets to setup
			$( sowb ).trigger( 'setup_widgets', { preview: true } );
		}
	} );

};

jQuery( function ( $ ) {
	sowbForms.setupVcWidgetForm();
} );

window.sowbForms = sowbForms;
