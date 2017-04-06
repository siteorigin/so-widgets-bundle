/* globals jQuery, sowbForms */
var sowbForms = window.sowbForms || {};

sowbForms.setupVcWidgetForm = function() {
	var $ = jQuery;
	var $widgetDropdown = $('[name="so_widget_class"]');
	var ajaxUrl = $('[name="ajaxurl"]').data('ajaxUrl');
	var $formContainer = $('.siteorigin_widget_form_container');
	$formContainer.on('change', '.siteorigin-widget-field', function() {
		var widgetData = sowbForms.getWidgetFormValues( $formContainer );
		var widgetClass = $widgetDropdown.val();

		var jsonString = JSON.stringify({widget_class: widgetClass, widget_data: widgetData});
		jsonString = jsonString.replace(/\\/g, '\\\\');
		$('[name="so_widget_data"]').val( jsonString );
	});

	$widgetDropdown.on('change', function() {
		var widget = $widgetDropdown.val();

		var data = {
			'action': 'sowb_vc_widget_render_form',
			'widget': widget,
		};

		$.post(
			ajaxUrl,
			data,
			function(result) {
				$formContainer.html(result);
				// To ensure data is updated.
				$formContainer.trigger('change');
			},
			'html'
		);
	});
    vc.atts.sowb_json_escaped = {
        parse: function (param) {
            var $field = this.content().find('.wpb_vc_param_value[name=' + param.param_name + ']'),
                new_value = $field.val();
            return _.escape(new_value.toString()).replace(/\[/g, '&#91;').replace(/\]/g, '&#93;');
        },
        render: function (param, value) {
            return _.unescape(value).replace(/&#91;/g, '[').replace(/&#93;/g, ']');
        }
    };

	vc.events.on("shortcodeView:updated:siteorigin_widget_vc", function() {

		if( typeof vc.frame_window !== 'undefined' && typeof vc.frame_window.sowb !== 'undefined') {
			var sowb = vc.frame_window.sowb;

			// Trigger Widgets Bundle widgets to setup
			// This isn't working for some reason, so keep calling the functions directly for now.
			$( sowb ).trigger( 'setup_widgets' );

			if( typeof sowb.setupGoogleMaps !== 'undefined' ) {
				sowb.setupGoogleMaps();
			}

			if( typeof sowb.setupSlider !== 'undefined' ) {
				sowb.setupSlider();
			}

			if( typeof sowb.setupImageGrid !== 'undefined' ) {
				sowb.setupImageGrid();
			}

			if( typeof sowb.setupSimpleMasonry !== 'undefined' ) {
				sowb.setupSimpleMasonry();
			}

			if( typeof sowb.setupVideoPlayer !== 'undefined' ) {
				sowb.setupVideoPlayer();
			}
		}
	});

};

jQuery(function ($) {
	sowbForms.setupVcWidgetForm();
});

window.sowbForms = sowbForms;
