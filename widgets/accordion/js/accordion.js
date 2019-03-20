/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	
	sowb.setupAccordion = function() {
		$( '.sow-accordion' ).each( function ( index, element ) {
			var $widget = $( this ).closest( '.so-widget-sow-accordion' );
			if ( $widget.data( 'initialized' ) ) {
				return $( this );
			}

			var $accordionPanels = $( element ).find( '> .sow-accordion-panel' );
			$accordionPanels.not( '.sow-accordion-panel-open' ).find( '.sow-accordion-panel-content' ).hide();
			var openPanels = $accordionPanels.filter( '.sow-accordion-panel-open' ).toArray();

			var updateHash = function () {
				// noop
			};
			
			var scrollToPanel = function ( $panel, smooth ) {
				var navOffset = 90;// Add some magic number offset to make space for possible nav menus etc.
				var scrollTop = $panel.offset().top - navOffset;
				if ( smooth ) {
					$( 'body,html' ).animate( {
						scrollTop: scrollTop,
					}, 200 );
				} else {
					window.scrollTo( 0, scrollTop );
				}
			};

			var openPanel = function ( panel, preventHashChange, keepVisible ) {
				var $panel = $( panel );
				if ( ! $panel.is( '.sow-accordion-panel-open' ) ) {
					$panel.find( '> .sow-accordion-panel-content' ).slideDown( {
						start: function () {
							// Sometimes the content of the panel relies on a window resize to setup correctly.
							// Trigger it here so it's hopefully done before the animation.
							$( window ).trigger( 'resize' );
							$( sowb ).trigger( 'setup_widgets' );
						},
						complete: function() {
							if ( keepVisible && $panel.offset().top < window.scrollY ) {
								scrollToPanel( $panel, true );
							}
							$( this ).trigger( 'show' );
						}
					});
					$panel.find(  '> .sow-accordion-panel-header-container > .sow-accordion-panel-header' ).attr( 'aria-expanded', true );
					$panel.addClass( 'sow-accordion-panel-open' );
					openPanels.push( panel );

					// Check if accordion is within an accordion and if it is, ensure parent is visible
					var $parentPanel = $( panel ).parents( '.sow-accordion-panel' );
					if ( $parentPanel.length && ! $parentPanel.hasClass( 'sow-accordion-panel-open' ) ) {
						openPanel( $parentPanel.get( 0 ), true );
					}
					if ( ! preventHashChange ) {
						updateHash();
					}
				}
			};
			
			var closePanel = function ( panel, preventHashChange ) {
				var $panel = $( panel );
				if ( $panel.is( '.sow-accordion-panel-open' ) ) {
					$panel.find( '> .sow-accordion-panel-content' ).slideUp(
						function() {
							$( this ).trigger( 'hide' );
						}
					);
					$panel.find(  '> .sow-accordion-panel-header-container > .sow-accordion-panel-header' ).attr( 'aria-expanded', false );
					$panel.removeClass( 'sow-accordion-panel-open' );
					openPanels.splice( openPanels.indexOf( panel ), 1 );
					if ( ! preventHashChange ) {
						updateHash();
					}
				}
			};
			
			$accordionPanels.find( '> .sow-accordion-panel-header-container > .sow-accordion-panel-header' ).on( 'click keydown', function( e ) {
				if ( e.type == 'keydown' ) {
					if ( e.keyCode !== 13 && e.keyCode !== 32 ){
						return;
					}
					e.preventDefault();
				}
				var $this = $( this );
				var maxOpenPanels = $widget.data( 'maxOpenPanels' );
				var $panel = $this.closest( '.sow-accordion-panel' );
				if ( $panel.is( '.sow-accordion-panel-open' ) ) {
					closePanel( $panel.get( 0 ) );
				} else {
					openPanel( $panel.get( 0 ), false, true );
				}

				if ( ! isNaN( maxOpenPanels ) && maxOpenPanels > 0 && openPanels.length > maxOpenPanels ) {
					var skippedPanels = 0;
					$.each( openPanels.reverse(), function( index, el ) {
						if ( skippedPanels !== maxOpenPanels ) {
							skippedPanels++;
						} else {
							closePanel( openPanels[ index ] );
						}
					} );
				}
			} );
			
			if ( $widget.data( 'useAnchorTags' ) ) {
				var timeoutId;
				updateHash = function () {
					if ( timeoutId ) {
						clearTimeout( timeoutId );
					}
					timeoutId = setTimeout( function () {
						
						var anchors = [];
						var allOpenPanels = $( '.sow-accordion-panel-open' ).toArray();
						for ( var i = 0; i < allOpenPanels.length; i++ ) {
							var anchor = $( allOpenPanels[ i ] ).data( 'anchor' );
							if ( anchor ) {
								var $parentPanel = $( allOpenPanels[ i ] ).parents( '.sow-accordion-panel' );
								if ( ! $parentPanel.length || ( $parentPanel.length && $parentPanel.hasClass( 'sow-accordion-panel-open' ) ) ) {
									anchors[ i ] = anchor;
								}
							}
						}
						
						if ( anchors && anchors.length ) {
							window.location.hash = anchors.join( ',' );
						} else if ( window.location.hash ) { // This prevents adding a history event if no was present on load
							window.history.pushState( '', document.title, window.location.pathname + window.location.search );
						}
					}, 100 );
				};
				
				var updatePanelStates = function () {
					var panels = $accordionPanels.toArray();
					for ( var i = 0; i < panels.length; i++ ) {
						var panel = panels[ i ];
						var anchor = $( panel ).data( 'anchor' );
						var anchors = window.location.hash.substring(1).split( ',' ); 
						if ( anchor && $.inArray( anchor.toString(), anchors ) > -1 ) {
							openPanel( panel, true );
						} else {
							closePanel( panel, true );
						}
					}
				};
				$( window ).on( 'hashchange', updatePanelStates );
				if ( window.location.hash ) {
					updatePanelStates();
				} else {
					updateHash();
				}
				var initialScrollPanel = $widget.data( 'initialScrollPanel' );
				if ( initialScrollPanel > 0 ) {
					var $initialScrollPanel = initialScrollPanel > $accordionPanels.length ?
						$accordionPanels.last() :
						$accordionPanels.eq( initialScrollPanel - 1 );
					setTimeout( function () {
						scrollToPanel( $initialScrollPanel );
					}, 500 );
				}
			}
			
			$widget.data( 'initialized', true );
		} );
	};
	
	sowb.setupAccordion();
	
	$( sowb ).on( 'setup_widgets', sowb.setupAccordion );
} );

window.sowb = sowb;
