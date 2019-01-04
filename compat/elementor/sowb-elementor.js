var sowb = window.sowb || {};

( function($){

	// To ensure necessary scripts are executed again when settings are changed
	$( window ).on( 'elementor:init', function() {
		elementor.on( 'preview:loaded', function () {
			var preview_window = elementor.$preview.get( 0 ).contentWindow;
			var $sowb = preview_window.jQuery( preview_window.sowb );
			var timeoutId;
			elementorFrontend.hooks.addAction( 'frontend/element_ready/widget', function(){
				// Debounce
				if ( timeoutId ) {
					clearTimeout( timeoutId );
				}
				timeoutId = setTimeout( function () {
					// Trigger Widgets Bundle widgets to setup
					$sowb.trigger( 'setup_widgets', { preview: true } );
					timeoutId = null;
				}, 300 );
			} );
		} );
	} );

})(jQuery);
