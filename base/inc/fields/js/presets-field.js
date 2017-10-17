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
				var id = $presetSelect.attr( 'id' );
				var formData = JSON.parse( sessionStorage.getItem( id ) );
				if ( ! formData ) {
					var presetClone = JSON.parse( JSON.stringify( presetValues ) );
					var widgetData = sowbForms.getWidgetFormValues( $formContainer );
					
					var copyValues = function( from, to ) {
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
					};
					// Copy existing widget values for preset properties to a session storage backup to be able to undo.
					copyValues( widgetData, presetClone );
					sessionStorage.setItem( id, JSON.stringify( presetClone ) );
					$undoLink.show();
					$undoLink.click( function ( event ) {
						event.preventDefault();
						//TODO: Revert changes made by presets selection.
					} );
				}
				
				sowbForms.setWidgetFormValues( $formContainer, presetValues, true );
			}
		} );
		
		$presetSelect.data( 'initialized', true );
	} );
})( jQuery );
