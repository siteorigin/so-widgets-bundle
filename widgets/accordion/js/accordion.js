jQuery( function ( $ ) {

	var $accordionPanels = $( '.sow-accordion .sow-accordion-panel' );

	$accordionPanels.not( '.sow-accordion-panel-open' ).find( '.sow-accordion-panel-content' ).hide();

	$accordionPanels.find( '.sow-accordion-panel-header' ).click( function () {
		var $this = $( this );
		var $panel = $this.closest( '.sow-accordion-panel' );
		if ( $panel.is( '.sow-accordion-panel-open' ) ) {
			$this.siblings( '.sow-accordion-panel-content' ).slideUp();
			$panel.removeClass( 'sow-accordion-panel-open' );
		} else {
			$this.siblings( '.sow-accordion-panel-content' ).slideDown();
			$panel.addClass( 'sow-accordion-panel-open' );
		}
	} );
} );
