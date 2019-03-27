/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	
	sowb.setupTabs = function () {
		$( '.sow-tabs' ).each( function ( index, element ) {
			var $this = $( element );
			var $widget = $this.closest( '.so-widget-sow-tabs' );
			if ( $widget.data( 'initialized' ) ) {
				return $( this );
			}
			var useAnchorTags = $widget.data( 'useAnchorTags' );
			
			var $tabPanelsContainer = $this.find( '> .sow-tabs-panel-container' );
			
			var $tabs = $this.find( '> .sow-tabs-tab-container > .sow-tabs-tab' );
			
			var $selectedTab = $this.find( '.sow-tabs-tab-selected' );
			var selectedIndex = $selectedTab.index();
			
			var $tabPanels = $tabPanelsContainer.find( '> .sow-tabs-panel' );
			$tabPanels.not( ':eq(' + selectedIndex + ')' ).hide();
			var tabAnimation;
			
			var selectTab = function ( tab, preventHashChange ) {
				var $tab = $( tab );
				if ( $tab.is( '.sow-tabs-tab-selected' ) ) {
					return true;
				}
				var selectedIndex = $tab.index();
				if ( selectedIndex > -1 ) {
					if (tabAnimation ) {
						tabAnimation.finish();
					}
					
					var $prevTab = $tabs.filter( '.sow-tabs-tab-selected' );
					$prevTab.removeClass( 'sow-tabs-tab-selected' );
					var prevTabIndex = $prevTab.index();
					var prevTabContent = $tabPanels.eq( prevTabIndex ).children();
					var selectedTabContent = $tabPanels.eq( selectedIndex ).children();

					// Set previous tab as inactive
					$prevTab.attr( 'tabindex', -1 );
					$prevTab.attr( 'aria-selected', false );
					prevTabContent.attr( 'tabindex', -1 );
					
					// Set new tab as active
					$tab.attr( 'tabindex', 0 );
					$tab.attr( 'aria-selected', true );
					selectedTabContent.attr( 'tabindex', 0 );
					
					prevTabContent.attr( 'aria-hidden', 'true' );
					tabAnimation = $tabPanels.eq( prevTabIndex ).fadeOut( 'fast',
						function () {
							$( this ).trigger( 'hide' );
							selectedTabContent.removeAttr( 'aria-hidden' );
							$tabPanels.eq( selectedIndex ).fadeIn( {
								duration: 'fast',
								start: function () {
									// Sometimes the content of the panel relies on a window resize to setup correctly.
									// Trigger it here so it's hopefully done before the animation.
									$( window ).trigger( 'resize' );
									$( sowb ).trigger( 'setup_widgets' );
								},
								complete: function() {
									$( this ).trigger( 'show' );
								}
							});
						}
					);
					$tab.addClass( 'sow-tabs-tab-selected' );
					
					if ( useAnchorTags && !preventHashChange ) {
						window.location.hash = $tab.data( 'anchor' );
					}
				}
			};
			
			$tabs.click( function() {
				selectTab( this );
			} );

			$tabs.keyup( function( e ) {
				var $currentTab = $( this );

				if ( e.keyCode !== 37 && e.keyCode !== 39 ){
					return;
				}

				var $newTab;
				// did the user press left arrow?
				if ( e.keyCode === 37 ) {
					// Check if there are any additional tabs to the left
					if( ! $currentTab.prev().get(0) ) { // no tabs to left
						$newTab = $currentTab.siblings().last();
					} else {
						$newTab = $currentTab.prev();
					}
				}

				// did the user press right arrow?
				if ( e.keyCode === 39 ) {
					// Check if there are any additional tabs to the right
					if( ! $currentTab.next().get(0) ) { // no tabs to right
						$newTab = $currentTab.siblings().first();
					} else {
						$newTab = $currentTab.next();
					}
				}
				if ( $currentTab === $newTab ){
					return;
				}
				$newTab.focus();
				selectTab( $newTab.get(0) );
			} );
			
			if ( useAnchorTags ) {
				var updateSelectedTab = function () {
					if ( window.location.hash ) {
						var anchors = window.location.hash.replace( '#', '' ).split( ',' );
						anchors.forEach( function ( anchor ) {
							var tab = $tabs.filter( '[data-anchor="' + anchor + '"]' );
							if ( tab ) {
								selectTab( tab, true );
							}
						} );
					}
				};
				$( window ).on( 'hashchange', updateSelectedTab );
				if ( window.location.hash ) {
					updateSelectedTab();
				} else {
					window.location.hash = $selectedTab.data( 'anchor' );
				}
			}
			
			$widget.data( 'initialized', true );
		} );
	};
	
	sowb.setupTabs();
	
	$( sowb ).on( 'setup_widgets', sowb.setupTabs );
} );

window.sowb = sowb;
