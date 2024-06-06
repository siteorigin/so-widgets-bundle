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

			var $tabPanelsContainer = $this.find( '> .sow-tabs-panel-container' );

			var $tabs = $this.find( '> .sow-tabs-tab-container > .sow-tabs-tab' );

			var $selectedTab = $this.find( '.sow-tabs-tab-selected' );
			var selectedIndex = $selectedTab.index();

			var $tabPanels = $tabPanelsContainer.find( '> .sow-tabs-panel' );
			$tabPanels.not( ':eq(' + selectedIndex + ')' ).hide();
			var tabAnimation;

			var scrollToTab = function( smooth ) {
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
					$prevTab.attr( 'aria-selected', false );

					// Set new tab as active.
					$tab.attr( 'aria-selected', true );

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
					if ( ! preventHashChange ) {
						$widget.trigger( 'tab_change', [ $tab, $widget ] );
					}
				}
			};

			$tabs.on( 'click', function() {
				selectTab( this );
			} );

			$tabs.on( 'keydown', function( e ) {
				const $currentTab = $( this );

				if ( e.key !== 'ArrowLeft' && e.key !== 'ArrowRight' ){
					return;
				}

				// Prevent browser horizontal scroll.
				e.preventDefault();

				let $newTab;
				// Did the user press left arrow?
				if ( e.key === 'ArrowLeft' ) {
					// Check if there are any additional tabs to the left.
					if ( ! $currentTab.prev().get(0) ) { // No tabs to left.
						$newTab = $currentTab.siblings().last();
					} else {
						$newTab = $currentTab.prev();
					}
				}

				// Did the user press right arrow?
				if ( e.key === 'ArrowRight' ) {
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
				selectTab( $newTab.get( 0 ) );
			} );

			$widget.data( 'initialized', true );
		} );
	};

	sowb.setupTabs();

	$( sowb ).on( 'setup_widgets', sowb.setupTabs );
} );

window.sowb = sowb;
