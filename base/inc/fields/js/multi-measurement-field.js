/* global jQuery */

(function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-multi-measurement', function ( e ) {
		
		var valField = $( this ).find( 'input[type="hidden"][class="siteorigin-widget-input"]' );
		var separator = valField.data( 'separator' );
		var autoFillEnabled = valField.data( 'autofill' );
		var values = valField.val() === '' ? [] : valField.val().split( separator );
		var $inputs = $( this ).find( '.sow-multi-measurement-input' );
		
		var updateValue = function ( $element ) {
			var vals = valField.val() === '' ? [] : valField.val().split( separator );
			var $unitInput = $element.find( '+ .sow-multi-measurement-select-unit' );
			var index = $inputs.index( $element );
			vals[ index ] = $element.val() + ( $element.val() === '' ? '' : $unitInput.val() );
			valField.val( vals.join( separator ) );
		};
		
		$inputs.each( function ( index, element ) {
			if ( values.length > index ) {
				var valueResult = values[ index ].match( /(\d+\.?\d*)([a-z%]+)*/ );
				if ( valueResult && valueResult.length ) {
					var amount = valueResult[ 1 ];
					var unit = valueResult[ 2 ];
					$( element ).val( amount );
					$( element ).find( '+ .sow-multi-measurement-select-unit' ).val( unit );
				}
			} else {
				updateValue( $( element ) );
			}
		} );
		
		$inputs.change( function ( event ) {
			var doAutofill = autoFillEnabled;
			if ( autoFillEnabled ) {
				$inputs.each( function ( index, element ) {
					// Only want to autofill if it has been enabled and no other inputs have values.
					if ( element !== event.target ) {
						doAutofill = doAutofill && !( $( element ).val() );
					}
				} );
			}
			if ( doAutofill ) {
				$inputs.each( function( index, element ) {
					$( element ).val( $( event.target ).val() );
					updateValue( $( element ) );
				} );
			} else {
				updateValue( $( event.target ) );
			}
		} );
		
	} );
})( jQuery );
