var sowb = window.sowb || {};

( function($){

	// To ensure necessary scripts are executed again when settings are changed
	$( window ).on( 'elementor:init', function() {
		elementor.on( 'preview:loaded', function () {
			var preview_window = elementor.$preview.get( 0 ).contentWindow;
			elementorFrontend.hooks.addAction( 'frontend/element_ready/widget', function() {
				// Trigger Widgets Bundle widgets to setup
				preview_window.jQuery( preview_window.sowb ).trigger( 'setup_widgets' );
			} );
		} );
	} );

})(jQuery);
