/* global jQuery, sowbForms */

(function ( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-presets', function ( e ) {
		
		var $presetSelect = $( this ).find( 'select[class="siteorigin-widget-input"]' );
		if ( $presetSelect.data( 'initialized' ) ) {
			return;
		}
		
		var $undoLink = $presetSelect.find( '+ .sowb-presets-field-undo' );
		$undoLink.hide();

		var onLoadTrigger = false;
		var addingDefault = false;
		var presets = $presetSelect.data( 'presets' );
		$presetSelect.on( 'change', function() {
			var selectedPreset = $presetSelect.val();
			if ( selectedPreset && presets.hasOwnProperty( selectedPreset ) ) {
				var presetValues = presets[ selectedPreset ].values;
				var $formContainer = $presetSelect.closest( '.siteorigin-widget-form-main' );

				// If we're adding defaults, don't show undo.
				if ( addingDefault || ! onLoadTrigger) {
					var previousValues = $presetSelect.data( 'previousValues' );
					if ( ! addingDefault ) {
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
							$undoLink.on( 'click', function ( event ) {
								event.preventDefault();
								$undoLink.hide();
								sowbForms.setWidgetFormValues( $formContainer, previousValues, false, 'preset' );
								$presetSelect.removeData( 'previousValues' );
								$presetSelect.val( '' );
							} );
						}
					} else {
						addingDefault = false;
					}
					sowbForms.setWidgetFormValues( $formContainer, presetValues, false, 'preset' );
				}
				onLoadTrigger = false;
			}
		} );

		if ( $presetSelect.data( 'default-preset' ) != undefined ) {
			// If no value is selected, and there's a default-preset set, load it.
			if ( $presetSelect.val() == 'default' ) {
				addingDefault = true;
				$presetSelect.val( $presetSelect.data( 'default-preset' ) );
			}
			// There's a default preset set, remove the empty default.
			$( this ).find( 'select[class="siteorigin-widget-input"] option[value="default"]' ).remove();
		}
		onLoadTrigger = true;
		$presetSelect.trigger( 'change' );

		$presetSelect.data( 'initialized', true );
	} );
})( jQuery );
