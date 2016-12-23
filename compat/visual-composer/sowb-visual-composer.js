var sowb = window.sowb || {};
(function($) {
	
	var widget = $('[name="so_widget"]').val();
	
	var ajaxUrl = $('[name="ajaxurl"]').data('ajaxUrl');
	
	var data = {
		'action': 'sowb_vc_widget_render_form',
		'widget': widget,
	};
	
	$.post(
		ajaxUrl,
		data,
		function ( result ) {
			$( '.siteorigin_widget_form' ).html( result );
		},
		'html'
	);
	
})(jQuery);
