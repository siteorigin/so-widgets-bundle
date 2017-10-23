jQuery( function ( $ ) {
	
	$( '.sow-tabs' ).each( function ( index, element ) {
		var $this = $( element );
		
		var $tabPanelsContainer = $this.find( '> .sow-tabs-panel-container' );
		
		var $tabs = $this.find( '> .sow-tabs-tab-container > .sow-tabs-tab' );
		
		var selectedIndex = $this.find( '.sow-tabs-tab-selected' ).index();
		
		var $tabPanels = $tabPanelsContainer.find( '> .sow-tabs-panel' );
		$tabPanels.not(':eq(' + selectedIndex + ')').hide();
		
		setTimeout( function () {
			$tabPanelsContainer.height( $tabPanels.eq( selectedIndex ).outerHeight() );
		}, 100 );
		
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
			$tabPanelsContainer.height( $tabPanels.eq( selectedIndex ).outerHeight() );
		} );
	} );
} );
