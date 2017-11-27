/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	
	sowb.setupTabs = function() {
		$( '.sow-tabs' ).each( function ( index, element ) {
			var $this = $( element );
			var $widget = $this.closest( '.so-widget-sow-tabs' );
			var useAnchorTags = $widget.data( 'useAnchorTags' );
			
			var $tabPanelsContainer = $this.find( '> .sow-tabs-panel-container' );
			
			var $tabs = $this.find( '> .sow-tabs-tab-container > .sow-tabs-tab' );
			
			var $selectedTab = $this.find( '.sow-tabs-tab-selected' );
			var selectedIndex = $selectedTab.index();
			
			var $tabPanels = $tabPanelsContainer.find( '> .sow-tabs-panel' );
			$tabPanels.not( ':eq(' + selectedIndex + ')' ).hide();
			
			var setContainerHeight = function () {
				var selTab = $this.find( '.sow-tabs-tab-selected' );
				
				setTimeout( function () {
					$tabPanelsContainer.height( $tabPanels.eq( selTab.index() ).outerHeight() );
				}, 100 );
			};
			
			setContainerHeight();
			
			$( window ).on( 'resize', setContainerHeight );
			
			var selectTab = function ( tab, preventHashChange ) {
				var $tab = $( tab );
				if ( $tab.is( '.sow-tabs-tab-selected' ) ) {
					return true;
				}
				var selectedIndex = $tab.index();
				if ( selectedIndex > -1 ) {
					var $prevTab = $tabs.filter( '.sow-tabs-tab-selected' );
					$prevTab.removeClass( 'sow-tabs-tab-selected' );
					var prevTabIndex = $prevTab.index();
					$tabPanels.eq( prevTabIndex ).fadeOut( 'fast',
						function () {
							$( this ).trigger( 'hide' );
						}
					);
					$tab.addClass( 'sow-tabs-tab-selected' );
					$tabPanels.eq( selectedIndex ).fadeIn( 'fast',
						function () {
							$( this ).trigger( 'show' );
						}
					);
					
					setContainerHeight();
					
					if ( useAnchorTags && !preventHashChange ) {
						window.location.hash = $tab.data( 'anchor' );
					}
				}
			};
			
			$tabs.click( function () {
				selectTab( this );
			} );
			
			if ( useAnchorTags ) {
				var updateSelectedTab = function () {
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
	};
	
	sowb.setupTabs();
	
	$( sowb ).on( 'setup_widgets', sowb.setupTabs );
} );

window.sowb = sowb;
