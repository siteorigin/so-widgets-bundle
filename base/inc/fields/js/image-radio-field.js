/* global jQuery, soWidgets */

( function($){

	$(document).on( 'sowsetupform', function(e) {
		var $form = $(e.target);

		$form.find( '.siteorigin-widget-field-type-image-radio' ).each( function(){
			var $$ = $( this );
			$$.find('input[type="radio"]:checked').parent().addClass('so-selected');
			$$.find('input[type="radio"]').on('change', function(){
				$$.find('input[type="radio"]').parent().removeClass('so-selected');
				$$.find('input[type="radio"]:checked').parent().addClass('so-selected');
			});
		} );
	});

}( jQuery ) );
