/* globals jQuery, sowb */

var sowb = window.sowb || {};

jQuery( function ( $ ) {
	
	sowb.setupAccordion = function() {
		$( '.sow-accordion' ).each( function ( index, element ) {
			var $widget = $( this ).closest( '.so-widget-sow-accordion' );
			var useAnchorTags = $widget.data( 'useAnchorTags' );
			
			var $accordionPanels = $( element ).find( '> .sow-accordion-panel' );
			
			$accordionPanels.not( '.sow-accordion-panel-open' ).find( '.sow-accordion-panel-content' ).hide();
			
			var openPanels = $accordionPanels.filter( '.sow-accordion-panel-open' ).toArray();
			var updateHash = function () {
				// noop
			};
			
			var openPanel = function ( panel, preventHashChange ) {
				var $panel = $( panel );
				if ( ! $panel.is( '.sow-accordion-panel-open' ) ) {
					$panel.find( '> .sow-accordion-panel-content' ).slideDown(
						function() {
							$( this ).trigger( 'show' );
						}
					);
					$panel.addClass( 'sow-accordion-panel-open' );
					openPanels.push( panel );
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
					$panel.removeClass( 'sow-accordion-panel-open' );
					openPanels.splice( openPanels.indexOf( panel ), 1 );
					if ( ! preventHashChange ) {
						updateHash();
					}
				}
			};
			
			$accordionPanels.find( '> .sow-accordion-panel-header' ).click( function () {
				var $this = $( this );
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
			
			if ( useAnchorTags ) {
				updateHash = function () {
					var anchors = [];
					for ( var i = 0; i < openPanels.length; i++ ) {
						var anchor = $( openPanels[ i ] ).data( 'anchor' );
						if ( anchor ) {
							anchors[ i ] = anchor;
						}
					}
					
					if ( anchors && anchors.length ) {
						window.location.hash = anchors.join( ',' );
					} else {
						window.history.pushState( '', document.title, window.location.pathname + window.location.search );
					}
				};
				
				var updatePanelStates = function () {
					var panels = $accordionPanels.toArray();
					for ( var i = 0; i < panels.length; i++ ) {
						panel = panels[ i ];
						var anchor = $( panel ).data( 'anchor' );
						if ( anchor && window.location.hash.indexOf( anchor ) > -1 ) {
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
			}
		} );
	};
	
	sowb.setupAccordion();
	
	$( sowb ).on( 'setup_widgets', sowb.setupAccordion );
} );

window.sowb = sowb;
