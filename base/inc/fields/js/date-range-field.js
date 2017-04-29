(function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-date-range', function ( e ) {

		var valField = $( this ).find( 'input[type="hidden"][class="siteorigin-widget-input"]' );

		var createPikadayInput = function ( inputName, initVal ) {
			var $field = $( this ).find( '.' + inputName + '-picker' );
			var picker = new Pikaday( {
				field: $field[0],
				blurFieldOnSelect: false,
				onSelect: function(date) {
					var curVal = valField.val() === '' ? {} : JSON.parse( valField.val() );
					curVal[inputName] = date.toLocaleDateString({}, {year: 'numeric', month:'2-digit', day:'2-digit'})
					$field.val( curVal[inputName] );
					valField.val( JSON.stringify( curVal ) );
					valField.change();
				},
			} );

			// We trigger the change event on the hidden value field, so prevent 'change' from individual date inputs.
			$field.change( function ( event ) {
				event.preventDefault();
				return false;
			} );

			if ( initVal ) {
				$field.val( initVal );
			}
			return picker;
		}.bind( this );

		var initRange = valField.val() === '' ? {after:'', before:''} : JSON.parse( valField.val() )
		createPikadayInput( 'after', initRange.after );
		createPikadayInput( 'before', initRange.before );
	} );
})( jQuery );
