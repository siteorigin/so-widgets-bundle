/* global jQuery */

( function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-multi-measurement', function( e ) {

		var valField = $( this ).find( '.sow-multi-measurement-input-values' );
		var separator = valField.data( 'separator' );
		var autoFillEnabled = valField.data( 'autofill' );
		var $valInputs = $( this ).find( '.sow-multi-measurement-input' );
		var $inputContainers = $( this ).find( '.sow-multi-measurement-input-container' );

		if ( ! autoFillEnabled ) {
			return;
		}

		var updateValue = function( $element ) {
			var vals = valField.val() === '' ? [] : valField.val().split( separator );
			var $unitInput = $element.find( '+ .sow-multi-measurement-select-unit' );
			var index = $valInputs.index( $element );
			vals[ index ] = $element.val() + ( $element.val() === '' ? '' : $unitInput.val() );
			valField.val( vals.join( separator ) );
		};

		$inputContainers.one( 'change', function( event ) {
			var $valInput = $( event.currentTarget ).find( '> .sow-multi-measurement-input' );

			if ( ! autoFillEnabled ) {
				return;
			}

			let doAutofill = true;
			// Let's check if we need to autofill fields. We only do an autofill,
			// if there is a value in the first input and no values in the rest.
			$valInputs.each( ( index, element ) => {
				// Only want to autofill if it has been enabled and no other inputs have values.
				if ( $( element ).attr( 'id' ) !== $valInput.eq( 0 ).attr( 'id' ) ) {
					doAutofill = doAutofill && ! ( $( element ).val() );
				}
			} );

			// We're good to autofill.
			$valInputs.each( ( index, element ) => {
				$( element ).val( $valInput.val() );
				updateValue( $( element ) );
			} );
		} );

	} );
} )( jQuery );
