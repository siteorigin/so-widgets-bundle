/* global jQuery, soWidgets */

( function($){

	$(document).on( 'sowsetupform', function(e) {
		var $form = $(e.target);

		$form.find( '.siteorigin-widget-field-type-order' ).each( function(){
			var $$ = $( this );
			var $valField = $$.find( '.siteorigin-widget-input' );
			var $items = $$.find( '.siteorigin-widget-order-items' );
			$items.sortable( {
				stop: function(){
					var val = $( this ).sortable( 'toArray', { attribute: 'data-value' } );
					$valField.val( val.join(',') );
					$valField.trigger( 'change', { silent: true } );
				}
			} );

			$$.change( function ( event, params ) {
				if ( ! ( params && params.silent ) ) {
					var values = $valField.val() === '' ? [] : $valField.val().split(',');
					if ( values.length ) {
						for ( var i = 0; i < values.length; i++) {
							var val = values[ i ];
							var $item = $$.find( '.siteorigin-widget-order-item[data-value=' + val + ']' );
							$items.append( $item );
						}
					}
				}
			} );
		} );
	});

}( jQuery ) );
