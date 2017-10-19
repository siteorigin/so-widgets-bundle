jQuery( function ( $ ) {
	
	$( '.sow-tabs' ).each( function ( index, element ) {
		var $this = $( element );
		
		var $tabs = $this.find( '> .sow-tabs-tab-container > .sow-tabs-tab' );
		
		var selectedIndex = $this.find( '.sow-tabs-tab-selected' ).index();
		
		var $tabPanels = $this.find( '> .sow-tabs-panel-container > .sow-tabs-panel' );
		$tabPanels.not(':eq(' + selectedIndex + ')').hide();
		
		$tabs.click( function () {
			var $this = $( this );
			if ( $this.is( '.sow-tabs-tab-selected' ) ) {
				return true;
			}
			selectedIndex = $this.index();
			$tabs.removeClass( 'sow-tabs-tab-selected' );
			$tabPanels.not( ':eq(' + selectedIndex + ')' ).fadeOut( 'fast' );
			$this.addClass( 'sow-tabs-tab-selected' );
			$tabPanels.eq( selectedIndex ).fadeIn( 'fast' );
		} );
	} );
} );
