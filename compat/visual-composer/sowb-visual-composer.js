var sowb = window.sowb || {};
var sowForms = window.sowForms;
sowb.setupVcWidgetForm = function() {
	var $ = jQuery;
	var $widgetDropdown = $('[name="so_widget_class"]');
	var ajaxUrl = $('[name="ajaxurl"]').data('ajaxUrl');
	var $formContainer = $('.siteorigin_widget_form_container');
	
	$formContainer.on('change', '.siteorigin-widget-field', function() {
		var widgetData = sowForms.getWidgetFormValues( $formContainer );
		var widgetClass = $widgetDropdown.val();
		$('[name="so_widget_data"]').val( JSON.stringify( {widget_class: widgetClass, widget_data: widgetData} ) );
	});
	
	$widgetDropdown.on('change', function() {
		var widget = $widgetDropdown.val();
		// var widgetData = JSON.parse( sowb.getWidgetFormValues() );
		
		var data = {
			'action': 'sowb_vc_widget_render_form',
			'widget': widget,
			// 'instance': widgetData,
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
	
};

jQuery(function ($) {
	sowb.setupVcWidgetForm();
});
