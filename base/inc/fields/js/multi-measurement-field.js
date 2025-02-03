/* global jQuery */

( function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-multi-measurement', function( e ) {

		// Only set this field up once.
		if ( $( this ).data( 'sow-multi-measurement-setup' ) ) {
			return;
		}

		const $$ = $( this );
		$$.data( 'sow-multi-measurement-setup', true );

		const valField = $$.find( '.sow-multi-measurement-input-values' );
		const separator = valField.data( 'separator' );
		const autoFillEnabled = valField.data( 'autofill' );
		const $valInputs = $$.find( '.sow-multi-measurement-input' );
		let values = valField.val() === '' ? [] : valField.val().split( separator );
		const $inputContainers = $$.find( '.sow-multi-measurement-input-container' );

		const updateValue = function( $element ) {
			const vals = valField.val() === '' ? [] : valField.val().split( separator );

			const $unitInput = $element.find( '+ .sow-multi-measurement-select-unit' );

			const index = $valInputs.index( $element );
			const fieldValue = $element.val().trim();
			vals[ index ] = fieldValue + ( fieldValue === '' ? '' : $unitInput.val() );
			valField.val( vals.join( separator ) );
		};

		// Initial setup of the field.
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

			if ( maybeAutoFill( $valInput ) ) {
				return;
			}

			updateValue( $valInput );
		} );

	} );
} )( jQuery );
