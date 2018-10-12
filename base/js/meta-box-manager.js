/* globals jQuery, sowbForms */

//Catch Update button click and transform widgets post meta data before event is propagated.
(function ($) {
	$('#post').on( 'submit',
		function ( event ) {
			var $el = $( '#siteorigin-widgets-meta-box' );
			var data = sowbForms.getWidgetFormValues( $el );
			
			$el.find( 'input[name="widget_post_meta"]' ).val( JSON.stringify( data ) );
		}
	);
})(jQuery);
