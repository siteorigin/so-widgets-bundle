jQuery( function ( $ ) {
	
	$( '.sow-accordion' ).each( function ( index, element ) {
		var $accordionPanels = $( element ).find( '> .sow-accordion-panel' );
		
		$accordionPanels.not( '.sow-accordion-panel-open' ).find( '.sow-accordion-panel-content' ).hide();
		
		var openPanels = $accordionPanels.filter( '.sow-accordion-panel-open' ).toArray();
		
		var openPanel = function ( panel ) {
			$( panel ).find( '> .sow-accordion-panel-content' ).slideDown();
			$( panel ).addClass( 'sow-accordion-panel-open' );
			openPanels.push( panel );
		};
		
		var closePanel = function ( panel ) {
			$( panel ).find( '> .sow-accordion-panel-content' ).slideUp();
			$( panel ).removeClass( 'sow-accordion-panel-open' );
			openPanels.splice( openPanels.indexOf( panel ), 1 );
		};
		
		$accordionPanels.find( '> .sow-accordion-panel-header' ).click( function () {
			var $this = $( this );
			var $widget = $this.closest( '.so-widget-sow-accordion' );
			var maxOpenPanels = $widget.data( 'maxOpenPanels' );
			var $panel = $this.closest( '.sow-accordion-panel' );
			if ( $panel.is( '.sow-accordion-panel-open' ) ) {
				closePanel( $panel.get( 0 ) );
			} else {
				openPanel( $panel.get( 0 ) );
			}
			if ( ! isNaN( maxOpenPanels ) && maxOpenPanels > 0 && openPanels.length > maxOpenPanels ) {
				closePanel( openPanels[ 0 ] );
			}
		} );
	} );
} );
