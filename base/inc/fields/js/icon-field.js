/* global jQuery, soWidgets */

( function( $ ) {

	var iconWidgetCache = {};

	$(document).on( 'sowsetupformfield', '.siteorigin-widget-field-type-icon', function(e) {
		var $$ = $(this),
			$is = $$.find('.siteorigin-widget-icon-selector'),
			$v = $is.find('.siteorigin-widget-icon-icon'),
			$b = $$.find('.siteorigin-widget-icon-selector-current'),
			$remove = $$.find( '.so-icon-remove' ),
			$search = $$.find( '.siteorigin-widget-icon-search' );

		if ( $$.data( 'initialized' ) ) {
			return;
		}

		// Clicking on the button should display the icon selector
		$b.on( 'click keyup', function( e ) {
			if ( e.type == 'keyup' && ! window.sowbForms.isEnter( e ) ) {
				return;
			}

			$is.slideToggle();
			$search.val( '' );
			searchIcons();
		} );

		// Clicking on the remove button
		$remove.on( 'click keyup', function( e ){
			e.preventDefault();

			if ( e.type == 'keyup' && ! window.sowbForms.isEnter( e ) ) {
				return;
			}

			// Trigger a click on the existing icon to remove it.
			$$.find('.siteorigin-widget-active').trigger( 'click' );
		} );

		var searchIcons = function(){
			var q = $search.val().toLowerCase();
			if( q === '' ) {
				$is.find('.siteorigin-widget-icon-icons-icon').show();
			}
			else {
				$is.find('.siteorigin-widget-icon-icons-icon').each( function(){
					var $$ = $( this ),
						value = $$.attr( 'data-value' );

					value = value.replace( /-/, ' ' );
					if( value.indexOf( q ) === -1 ) {
						$$.hide();
					}
					else {
						$$.show();
					}
				} );
			}
		};

		$search.on( 'keyup change', searchIcons );
		
		var renderStylesSelect = function ( init ) {
			var $familySelect = $is.find( 'select.siteorigin-widget-icon-family' );
			var family = $familySelect.val();
			
			if(typeof iconWidgetCache[family] === 'undefined') {
				return;
			}
			
			var $stylesSelect = $is.find( '.siteorigin-widget-icon-family-styles' );
			if ( !init ) {
				$stylesSelect.off( 'change', rerenderIcons );
				$stylesSelect.remove();
				var iconFamily = iconWidgetCache[ family ];
				if ( iconFamily.hasOwnProperty( 'styles' ) && iconFamily.styles ) {
					var options = '';
					for ( var styleClass in iconFamily.styles ) {
						options += '<option value="' + styleClass + '">' + iconFamily.styles[styleClass] + '</option>';
					}
					if ( options ) {
						$stylesSelect = $( '<select class="siteorigin-widget-icon-family-styles"></select>' ).append( options );
						$familySelect.after( $stylesSelect );
						
					}
				}
			}
			$stylesSelect.on( 'change', rerenderIcons );
		};

		var rerenderIcons = function() {
			var $familySelect = $is.find( 'select.siteorigin-widget-icon-family' );
			var family = $familySelect.val();
			var container = $is.find('.siteorigin-widget-icon-icons');
			
			if(typeof iconWidgetCache[family] === 'undefined') {
				return;
			}

			container.empty();
			
			var iconFamily = iconWidgetCache[ family ];
			var icons = iconFamily.icons;
			var style;
			if ( iconFamily.hasOwnProperty( 'styles' ) && iconFamily.styles ) {
				style = $is.find( '.siteorigin-widget-icon-family-styles' ).val();
			}

			if( $('#'+'siteorigin-widget-font-'+family).length === 0) {

				$("<link rel='stylesheet' type='text/css'>")
					.attr('id', 'siteorigin-widget-font-' + family)
					.attr('href', iconWidgetCache[family].style_uri)
					.appendTo('head');
			}

			for ( var i in icons ) {
				var iconData = icons[ i ];
				var unicode = iconData.hasOwnProperty('unicode' ) ? iconData.unicode : iconData;
				if ( iconData.hasOwnProperty( 'styles' ) && iconData.styles.indexOf( style ) === -1 ) {
					continue;
				}
				var familyStyle = 'sow-icon-' + family + ( style ? ' ' + style : '' );
				var familyValue = family + ( style ? '-' + style : '' ) + '-' + i;

				var $icon = $( '<div data-sow-icon="' + unicode + '"></div>' )
					.attr('data-value', familyValue )
					.addClass( familyStyle )
					.addClass( 'siteorigin-widget-icon-icons-icon' )
					.on( 'click keyup', function( e ) {
						if ( e.type == 'keyup' && ! window.sowbForms.isEnter( e ) ) {
							return;
						}

						var $$ = $(this);

						if( $$.hasClass('siteorigin-widget-active') ) {
							// This is being unselected
							$$.removeClass('siteorigin-widget-active');
							$v.val( '' );

							// Hide the button icon
							$b.find('span').hide();

							$remove.hide();
						}
						else {
							// This is being selected
							container.find('.siteorigin-widget-icon-icons-icon').removeClass('siteorigin-widget-active');
							$$.addClass('siteorigin-widget-active');
							$v.val( $$.data( 'value' ) );

							// Also add this to the button
							$b.find('span')
								.show()
								.attr( 'data-sow-icon', $$.attr('data-sow-icon') )
								.attr( 'class', '' )
								.addClass( familyStyle );

							$remove.show();
						}

						$v.trigger( 'change', { isRendering: true });

						// Hide the icon selector
						$is.slideUp();
					});
				
				container.append( $icon );

				if( $v.val() === familyValue ) {
					// Add selected icon to the button.
					$b.find( 'span' )
					.show()
					.attr( 'data-sow-icon', $icon.attr( 'data-sow-icon' ) )
					.attr( 'class', '' )
					.addClass( familyStyle );
					$icon.addClass( 'siteorigin-widget-active' );
				}
			}

			// Move a selected item to the first position
			container.prepend( container.find('.siteorigin-widget-active') );

			searchIcons();
		};

		// Create the function for changing the icon family and call it once
		var changeIconFamily = function( init ){
			// Fetch the family icons from the server
			var family = $is.find('select.siteorigin-widget-icon-family').val();

			var dataIcons = $is.find('select.siteorigin-widget-icon-family option:selected' ).data('icons');
			if( dataIcons !== null ) {
				iconWidgetCache[family] = dataIcons;
			}

			if(typeof family === 'undefined' || family === '') {
				return;
			}

			if(typeof iconWidgetCache[family] === 'undefined') {
				var $container = $is.find('.siteorigin-widget-icon-icons');
				$container.addClass( 'loading' );
				
				$.getJSON(
					soWidgets.ajaxurl,
					{
						'action' : 'siteorigin_widgets_get_icons',
						'family' :  $is.find('select.siteorigin-widget-icon-family').val()
					},
					function(data) {
						iconWidgetCache[family] = data;
						renderStylesSelect( init );
						$container.removeClass( 'loading' );
						rerenderIcons();
					}
				);
			}
			else {
				rerenderIcons();
			}
		};
		changeIconFamily( true );

		$is.find( 'select.siteorigin-widget-icon-family' ).on( 'change', function() {
			$is.find('.siteorigin-widget-icon-icons').empty();
			changeIconFamily();
		});

		$v.on( 'change', function ( event, data ) {
			if ( ! ( data && data.isRendering ) ) {
				rerenderIcons();
			}
		} );

		$$.data( 'initialized', true );
	} );

} )( jQuery );
