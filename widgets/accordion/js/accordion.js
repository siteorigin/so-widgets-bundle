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
			var openPanels = $accordionPanels.filter( '.sow-accordion-panel-open' ).toArray();

			var scrollToPanel = function ( $panel, smooth ) {
				// Add some magic number offset to make space for possible nav menus etc.
				var navOffset = sowAccordion.scrollto_offset ? sowAccordion.scrollto_offset : 80;
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
							if ( sowAccordion.scrollto_after_change ) {
								// It's possible a resize may result in a scroll so we put it behind a check.
								$( window ).trigger( 'resize' );
							}
							$( sowb ).trigger( 'setup_widgets' );
						},
						complete: function() {
							if (
								keepVisible &&
								sowAccordion.scrollto_after_change &&
								(
									$panel.offset().top < window.scrollY ||
									$panel.offset().top + $panel.height() > window.scrollY
								)
							) {
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
						$widget.trigger( 'accordion_open', [ panel, $widget ] );
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
						$widget.trigger( 'accordion_close', [ panel, $widget ] );
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

			$widget.data( 'initialized', true );
		} );
	};

	sowb.setupAccordion();

	$( sowb ).on( 'setup_widgets', sowb.setupAccordion );
} );

window.sowb = sowb;
