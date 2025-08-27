/* global jQuery, sowbForms */

( function( $ ) {

	const setupPresetsField = function( e ) {

		const $presetSelect = $( this ).find( 'select[class="siteorigin-widget-input"]' );
		if ( $presetSelect.data( 'initialized' ) ) {
			return;
		}

		const $undoLink = $presetSelect.find( '+ .sowb-presets-field-undo' );
		$undoLink.hide();

		let onLoadTrigger = false;
		let addingDefault = false;
		const presets = $presetSelect.data( 'presets' );
		$presetSelect.on( 'change', function() {
			const selectedPreset = $presetSelect.val();
			if ( selectedPreset && presets.hasOwnProperty( selectedPreset ) ) {
				const presetValues = presets[ selectedPreset ].values;
				const $formContainer = $presetSelect.closest( '.siteorigin-widget-form-main' );

				// If we're adding defaults, don't show undo.
				if ( addingDefault || ! onLoadTrigger) {
					let previousValues = $presetSelect.data( 'previousValues' );
					if ( ! addingDefault ) {
						if ( ! previousValues ) {
							const presetClone = JSON.parse( JSON.stringify( presetValues ) );
							const widgetData = sowbForms.getWidgetFormValues( $formContainer );
							let recurseDepth = 0;
							const copyValues = function( from, to ) {
								if ( ++recurseDepth > 10 ) {
									return to;
								}
								for ( const key in to ) {
									if ( from.hasOwnProperty( key ) ) {
										const fromItem = from[ key ];
										const toItem = to[ key ];
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
							$undoLink.on( 'click', function( event ) {
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
	};

	 // If the current page isn't the site editor, set up the Presets field now.
	 if (
		 window.top === window.self &&
		 (
			 typeof pagenow === 'string' &&
			 pagenow !== 'site-editor'
		 )
	 ) {
		 $( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-presets', setupPresetsField );
	 }

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-presets' ).each( function() {
				setupPresetsField.call( this );
			} );
		}
	} );
} )( jQuery );
