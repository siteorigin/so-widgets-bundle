jQuery( function ( $ ) {
	
	$( '.sow-tabs' ).each( function ( index, element ) {
		var $this = $( element );
		var $widget = $this.closest( '.so-widget-sow-tabs' );
		var useAnchorTags = $widget.data( 'useAnchorTags' );
		
		var $tabPanelsContainer = $this.find( '> .sow-tabs-panel-container' );
		
		var $tabs = $this.find( '> .sow-tabs-tab-container > .sow-tabs-tab' );
		
		var $selectedTab = $this.find( '.sow-tabs-tab-selected' );
		var selectedIndex = $selectedTab.index();
		
		var $tabPanels = $tabPanelsContainer.find( '> .sow-tabs-panel' );
		$tabPanels.not(':eq(' + selectedIndex + ')').hide();
		
		setTimeout( function () {
			$tabPanelsContainer.height( $tabPanels.eq( selectedIndex ).outerHeight() );
		}, 100 );
		
		var selectTab = function( tab, preventHashChange ) {
			var $tab = $( tab );
			if ( $tab.is( '.sow-tabs-tab-selected' ) ) {
				return true;
			}
			var selectedIndex = $tab.index();
			if ( selectedIndex > -1 ) {
				$tabs.removeClass( 'sow-tabs-tab-selected' );
				$tabPanels.not( ':eq(' + selectedIndex + ')' ).fadeOut( 'fast' );
				$tab.addClass( 'sow-tabs-tab-selected' );
				$tabPanels.eq( selectedIndex ).fadeIn( 'fast' );
				setTimeout( function () {
					$tabPanelsContainer.height( $tabPanels.eq( selectedIndex ).outerHeight() );
				}, 100 );
				if ( useAnchorTags && !preventHashChange ) {
					window.location.hash = $tab.data( 'anchor' );
				}
			}
		};
		
		$tabs.click( function () {
			selectTab( this );
		} );
		
		if ( useAnchorTags ) {
			var updateSelectedTab = function() {
				if ( window.location.hash ) {
					var tab = $tabs.filter( '[data-anchor="' + window.location.hash.replace( '#', '' ) + '"]' );
					if ( tab ) {
						selectTab( tab, true );
					}
				}
			};
			$( window ).on( 'hashchange', updateSelectedTab );
			if ( window.location.hash ) {
				updateSelectedTab();
			} else {
				window.location.hash = $selectedTab.data( 'anchor' );
			}
		}
	} );
} );
