/* global jQuery */

(function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-multi-measurement', function ( e ) {
		
		var valField = $( this ).find( 'input[type="hidden"][class="siteorigin-widget-input"]' );
		var separator = valField.data( 'separator' );
		var autoFillEnabled = valField.data( 'autofill' );
		var values = valField.val() === '' ? [] : valField.val().split( separator );
		var $valInputs = $( this ).find( '.sow-multi-measurement-input' );
		var $inputContainers = $( this ).find( '.sow-multi-measurement-input-container' );
		
		var updateValue = function ( $element ) {
			var vals = valField.val() === '' ? [] : valField.val().split( separator );
			var $unitInput = $element.find( '+ .sow-multi-measurement-select-unit' );
			var index = $valInputs.index( $element );
			vals[ index ] = $element.val() + ( $element.val() === '' ? '' : $unitInput.val() );
			valField.val( vals.join( separator ) );
		};
		
		$valInputs.each( function ( index, element ) {
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
		
		$inputContainers.on( 'change', function( event ) {
			var $valInput = $( event.currentTarget ).find( '> .sow-multi-measurement-input' );
			var doAutofill = autoFillEnabled;
			if ( autoFillEnabled ) {
				$valInputs.each( function ( index, element ) {
					// Only want to autofill if it has been enabled and no other inputs have values.
					if ( $( element ).attr( 'id' ) !== $valInput.eq( 0 ).attr( 'id' ) ) {
						doAutofill = doAutofill && !( $( element ).val() );
					}
				} );
			}
			if ( doAutofill ) {
				$valInputs.each( function( index, element ) {
					$( element ).val( $valInput.val() );
					updateValue( $( element ) );
				} );
			} else {
				updateValue( $valInput );
			}
		} );
		
	} );
})( jQuery );
