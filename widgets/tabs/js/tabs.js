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
			var anchorId = $widget.data( 'anchor-id' ) ? $widget.data( 'anchor-id' ) : false;
			var $tabPanelsContainer = $this.find( '> .sow-tabs-panel-container' );

			var $tabs = $this.find( '> .sow-tabs-tab-container > .sow-tabs-tab' );

			var $selectedTab = $this.find( '.sow-tabs-tab-selected' );
			var selectedIndex = $selectedTab.index();

			var $tabPanels = $tabPanelsContainer.find( '> .sow-tabs-panel' );
			$tabPanels.not( ':eq(' + selectedIndex + ')' ).hide();
			var tabAnimation;

			var scrollToTab = function ( smooth ) {
				// Add offset to make space for possible nav menus etc.
				var navOffset = sowTabs.scrollto_offset ? sowTabs.scrollto_offset : 90;
				var scrollTop = $widget.offset().top - navOffset;
				if ( smooth ) {
					$( 'body,html' ).animate( {
						scrollTop: scrollTop,
					}, 200 );
				} else {
					window.scrollTo( 0, scrollTop );
				}
			};

			var shouldScroll = function( $tab ) {
				return sowTabs.scrollto_after_change &&
				(
					$tab.offset().top < window.scrollY ||
					$tab.offset().top + $tab.height() > window.scrollY
				);
			}

			var selectTab = function ( tab, preventHashChange ) {
				var $tab = $( tab );
				if ( $tab.is( '.sow-tabs-tab-selected' ) ) {
					if ( shouldScroll( $tab ) ) {
						scrollToTab( true );
					}
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

					// Set previous tab as inactive.
					$prevTab.attr( 'tabindex', -1 );
					$prevTab.attr( 'aria-selected', false );
					prevTabContent.attr( 'tabindex', -1 );

					// Set new tab as active.
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
									if ( shouldScroll( $tab ) || sowTabs.always_scroll ) {
										// It's possible a resize may result in a scroll so we put it behind a check.
										$( window ).trigger( 'resize' );
									}
									$( sowb ).trigger( 'setup_widgets' );
								},
								complete: function() {
									$( this ).trigger( 'show' );

									if ( preventHashChange || shouldScroll( $tab ) ) {
										scrollToTab( true );
									}
								}
							});
						}
					);
					$tab.addClass( 'sow-tabs-tab-selected' );

					if ( ! preventHashChange && ( anchorId || $widget.data( 'use-anchor-tags' ) ) ) {
						if ( ! anchorId ) {
							window.location.hash = $tab.data( 'anchor' );
						} else {
							var anchor = $tab.data( 'anchor' );
							if ( $widget.data( 'anchor-id' ) != 1 ) {
								anchor = $widget.data( 'anchor-id' ) + '-' + anchor;
							}
							window.location.hash = anchor;
						}
					}
				}
			};

			$tabs.on( 'click', function() {
				selectTab( this );
			} );

			$tabs.on( 'keyup', function( e ) {
				var $currentTab = $( this );

				if ( e.keyCode !== 37 && e.keyCode !== 39 ){
					return;
				}

				var $newTab;
				// Did the user press left arrow?
				if ( e.keyCode === 37 ) {
					// Check if there are any additional tabs to the left.
					if ( ! $currentTab.prev().get(0) ) { // No tabs to left.
						$newTab = $currentTab.siblings().last();
					} else {
						$newTab = $currentTab.prev();
					}
				}

				// Did the user press right arrow?
				if ( e.keyCode === 39 ) {
					// Check if there are any additional tabs to the right.
					if ( ! $currentTab.next().get(0) ) { // No tabs to right.
						$newTab = $currentTab.siblings().first();
					} else {
						$newTab = $currentTab.next();
					}
				}
				if ( $currentTab === $newTab ){
					return;
				}
				$newTab.trigger( 'focus' );
				selectTab( $newTab.get(0) );
			} );

			if ( $widget.data( 'anchor-id' ) || $widget.data( 'use-anchor-tags' ) ) {
				var updateSelectedTab = function () {
					if ( window.location.hash ) {
						var anchors = window.location.hash.substring(1).split( ',' );
						anchors.forEach( function ( anchor ) {
							var tab = $tabs.filter( function ( index, element ) {
								var tabAnchor = $( element ).data( 'anchor' );
								if ( $widget.data( 'anchor-id' ) && $widget.data( 'anchor-id' ) != 1 ) {
									tabAnchor = $widget.data( 'anchor-id' ) + '-' + tabAnchor;
								}
								return decodeURI( anchor ) === decodeURI( tabAnchor );
							} );
							if ( tab.length > 0 ) {
								selectTab( tab, true );
							}
						} );
					}
				};
				$( window ).on( 'hashchange', updateSelectedTab );
				if ( window.location.hash ) {
					updateSelectedTab();
				}
			}

			$widget.data( 'initialized', true );
		} );
	};

	sowb.setupTabs();

	$( sowb ).on( 'setup_widgets', sowb.setupTabs );
} );

window.sowb = sowb;
