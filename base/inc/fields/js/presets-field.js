/* global jQuery, sowbForms */

(function ( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-presets', function ( e ) {
		
		var $presetSelect = $( this ).find( 'select[class="siteorigin-widget-input"]' );
		if ( $presetSelect.data( 'initialized' ) ) {
			return;
		}
		
		var $undoLink = $presetSelect.find( '+ .sowb-presets-field-undo' );
		$undoLink.hide();
		
		var presets = $presetSelect.data( 'presets' );
		$presetSelect.change( function () {
			
			var selectedPreset = $presetSelect.val();
			if ( selectedPreset && presets.hasOwnProperty( selectedPreset ) ) {
				
				var presetValues = presets[ selectedPreset ].values;
				
				var $formContainer = $presetSelect.closest( '.siteorigin-widget-form-main' );
				var previousValues = $presetSelect.data( 'previousValues' );
				if ( ! previousValues ) {
					var presetClone = JSON.parse( JSON.stringify( presetValues ) );
					var widgetData = sowbForms.getWidgetFormValues( $formContainer );
					var recurseDepth = 0;
					var copyValues = function( from, to ) {
						if ( ++recurseDepth > 10 ) {
							return to;
						}
						for ( var key in to ) {
							if ( from.hasOwnProperty( key ) ) {
								var fromItem = from[ key ];
								var toItem = to[ key ];
								if ( fromItem !== null && toItem !== null && typeof fromItem === 'object' ) {
									copyValues( fromItem, toItem );
								} else {
									to[ key ] = fromItem;
								}
							}
						}
						return to;
					};
					// Copy existing widget values for preset properties to allow for undo.
					previousValues = copyValues( widgetData, presetClone );
					$presetSelect.data( 'previousValues', previousValues );
				}
				if ( $undoLink.not( ':visible' ) ) {
					$undoLink.show();
					$undoLink.click( function ( event ) {
						event.preventDefault();
						$undoLink.hide();
						sowbForms.setWidgetFormValues( $formContainer, previousValues, true );
						$presetSelect.removeData( 'previousValues' );
						$presetSelect.val( '' );
					} );
				}
				
				sowbForms.setWidgetFormValues( $formContainer, presetValues, true );
			}
		} );
		
		$presetSelect.data( 'initialized', true );
	} );
})( jQuery );
