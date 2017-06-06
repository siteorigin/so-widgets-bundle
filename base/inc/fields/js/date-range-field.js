/* global jQuery, soWidgets */

(function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-date-range', function ( e ) {

		var valField = $( this ).find( 'input[type="hidden"][class="siteorigin-widget-input"]' );

		if ( $( this ).find( '[class*="sowb-specific-date"]' ).length > 0 ) {
			var createPikadayInput = function ( inputName, initVal ) {
				var $field = $( this ).find( '.' + inputName + '-picker' );
				var picker = new Pikaday( {
					field: $field[ 0 ],
					blurFieldOnSelect: false,
					onSelect: function ( date ) {
						var curVal = valField.val() === '' ? {} : JSON.parse( valField.val() );
						curVal[ inputName ] = date.toLocaleDateString( {}, {
							year: 'numeric',
							month: '2-digit',
							day: '2-digit'
						} );
						$field.val( curVal[ inputName ] );
						valField.val( JSON.stringify( curVal ) );
						valField.trigger( 'change', { silent: true } );
					},
				} );

				// We trigger the change event on the hidden value field, so prevent 'change' from individual date inputs.
				$field.change( function ( event ) {
					event.preventDefault();
					return false;
				} );

				if ( initVal ) {
					$field.val( initVal );
				}
				return picker;
			}.bind( this );

			var initRange = valField.val() === '' ? { after: '', before: '' } : JSON.parse( valField.val() );
			var afterPicker = createPikadayInput( 'after', initRange.after );
			var beforePicker = createPikadayInput( 'before', initRange.before );

			valField.change( function ( event, data ) {
				if ( ! ( data && data.silent ) ) {
					var newRange = valField.val() === '' ? { after: '', before: '' } : JSON.parse( valField.val() );
					afterPicker.setDate( newRange.after );
					beforePicker.setDate( newRange.before );
				}
			} );
		} else if ( $( this ).find( '.sowb-relative-date' ).length > 0 ) {

			$( this ).find( '.sowb-relative-date' ).each( function () {
				var $name = $( this ).data( 'name' );

				$( this ).change( function () {
					var range = valField.val() === '' ? {} : JSON.parse( valField.val() );

					if ( ! range.hasOwnProperty( $name ) ) {
						range[ $name ] = {};
					}

					range[ $name ][ 'value' ] = $( this ).find( '> input' ).val();
					range[ $name ][ 'unit' ] = $( this ).find( '> select' ).val();

					valField.val( JSON.stringify( range ) );
					valField.trigger( 'change', { silent: true } );
				}.bind( this ) );

				valField.change( function ( event, data ) {
					if ( ! ( data && data.silent ) ) {
						var range = valField.val() === '' ? { from: {}, to: {} } : JSON.parse( valField.val() );

						if ( range.hasOwnProperty( $name ) ) {
							$( this ).find( '> input' ).val( range[ $name ][ 'value' ] );
							$( this ).find( '> select' ).val( range[ $name ][ 'unit' ] );
						}
					}
				}.bind( this ) );

			} );
		}
	} );
})( jQuery );
