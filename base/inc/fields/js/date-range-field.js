(function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-date-range', function ( e ) {
		var afterPicker = new Pikaday( { field: $( this ).find( '[name="after"]' )[0] } );
		var beforePicker = new Pikaday( { field: $( this ).find( '[name="before"]' )[0] } );
	} );
})( jQuery );
