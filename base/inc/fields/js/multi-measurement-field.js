/* global jQuery */

( function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-multi-measurement', function( e ) {

		const valField = $( this ).find( '.sow-multi-measurement-input-values' );
		const separator = valField.data( 'separator' );
		const autoFillEnabled = valField.data( 'autofill' );
		const $valInputs = $( this ).find( '.sow-multi-measurement-input' );
		let values = valField.val() === '' ? [] : valField.val().split( separator );
		const $inputContainers = $( this ).find( '.sow-multi-measurement-input-container' );

		// We only want to handle value changes, if there is a state
		// handler or autofill is enabled.
		const hasState = $( this ).attr( 'data-state-handler' ) !== undefined || $( this ).attr( 'data-state' ) !== undefined;

		const updateValue = function( $element ) {
			var vals = valField.val() === '' ? [] : valField.val().split( separator );

			var $unitInput = $element.find( '+ .sow-multi-measurement-select-unit' );

			var index = $valInputs.index( $element );
			vals[ index ] = $element.val() + ( $element.val() === '' ? '' : $unitInput.val() );
			valField.val( vals.join( separator ) );
		};

		// Initial setup of the filed.
		$valInputs.each( function( index, element ) {
			if ( values.length > index ) {
				var valueResult = values[ index ].match( /(\d+\.?\d*)([a-z%]+)*/ );
				if ( valueResult && valueResult.length ) {
					var amount = valueResult[ 1 ];
					var unit = typeof valueResult[ 2 ] != 'undefined' ? valueResult[ 2 ] : 'px';
					$( element ).val( amount );
					$( element ).find( '+ .sow-multi-measurement-select-unit' ).val( unit );
				}
			} else {
				updateValue( $( element ) );
			}
		} );

		const maybeAutoFill = ( $valInput ) => {
			if ( ! autoFillEnabled ) {
				return false;
			}

			let doAutofill = true;
			// Let's check if we need to autofill fields. We only do
			// an autofill, if there is a value in the first input
			// and no values in the rest.
			$valInputs.each( ( index, element ) => {
				// Only want to autofill if it has been enabled and
				// no other inputs have values.
				if (
					$( element ).attr( 'id' ) !== $valInput.eq( 0 ).attr( 'id' )
				) {
					doAutofill = doAutofill && ! ( $( element ).val() );
				}
			} );

			if ( ! doAutofill ) {
				return false;
			}

			// We're good to autofill.
			$valInputs.each( ( index, element ) => {
				$( element ).val( $valInput.val() );
				updateValue( $( element ) );
			} );

			return true;
		}

		$inputContainers.on( 'change', function( event ) {
			var $valInput = $( event.currentTarget ).find( '> .sow-multi-measurement-input' );

			if ( maybeAutoFill( $valInput ) || hasState ) {
				return;
			}

			updateValue( $valInput );
		} );

	} );
} )( jQuery );
