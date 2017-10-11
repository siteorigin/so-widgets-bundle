/* global jQuery, sowbForms */

(function ( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-presets', function ( e ) {
		
		var $presetSelect = $( this ).find( 'select[class="siteorigin-widget-input"]' );
		if ( $presetSelect.data( 'initialized' ) ) {
			return;
		}
		
		var presets = $presetSelect.data( 'presets' );
		$presetSelect.change( function ( event ) {
			var selectedPreset = $presetSelect.val();
			if ( selectedPreset && presets.hasOwnProperty( selectedPreset ) ) {
				var presetValues = presets[ selectedPreset ].values;
				sowbForms.setWidgetFormValues( $presetSelect.closest( '.siteorigin-widget-form-main' ), presetValues );
			}
		} );
		
		$presetSelect.data( 'initialized', true );
	} );
})( jQuery );
