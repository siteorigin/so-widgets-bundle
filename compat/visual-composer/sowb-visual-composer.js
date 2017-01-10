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
		$('[name="so_widget_data"]').val( JSON.stringify( {widget_class: widgetClass, widget_data: widgetData} ) );
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
	
	vc.events.on("shortcodeView:updated:siteorigin_widget", function() {
		
		if( typeof vc.frame_window !== 'undefined' && typeof vc.frame_window.sowb !== 'undefined') {
			var sowb = vc.frame_window.sowb;
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
