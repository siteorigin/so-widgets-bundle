/* global jQuery, pikaday */

(function( $ ) {
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-date-range', function ( e ) {
		var $dateRangeField = $( this );
		var valField = $dateRangeField.find( 'input[type="hidden"][class="siteorigin-widget-input"]' );
		
		if ( $dateRangeField.data( 'initialized' ) ) {
			return;
		}

		if ( $dateRangeField.find( '[class*="sowb-specific-date"]' ).length > 0 ) {
			var createPikadayInput = function ( inputName, initVal ) {
				var $field = $dateRangeField.find( '.' + inputName + '-picker' );
				var dateToString = function ( date, format ) {
					var dateString = '';
					if ( ! isNaN( date.valueOf() ) ) {
						var day = date.getDate();
						day = day < 10 ? '0' + day.toString() : day.toString();
						var month = date.getMonth() + 1;
						month = month < 10 ? '0' + month.toString() : month.toString();
						var year = date.getFullYear();
						return year + '-' + month + '-' + day;
					}
					
					return dateString;
				};
				var parse = function ( dateString, format ) {
					var parts = dateString.split( '-' );
					var day = parseInt( parts[ 2 ] );
					var month = parseInt( parts[ 1 ] ) - 1;
					var year = parseInt( parts[ 0 ] );
					return new Date( year, month, day );
				};
				var updateValField = function ( date ) {
					var curVal = valField.val() === '' ? {} : JSON.parse( valField.val() );
					curVal[ inputName ] = dateToString( date );
					$field.val( curVal[ inputName ] );
					valField.val( JSON.stringify( curVal ) );
					valField.trigger( 'change', { silent: true } );
				};
				var picker = new Pikaday( {
					field: $field[ 0 ],
					blurFieldOnSelect: false,
					toString: dateToString,
					parse: parse,
					onSelect: updateValField,
				} );

				$field.change( function ( event ) {
					var dateVal = parse( $field.val() );
					updateValField( dateVal );
					
					// We trigger the change event on the hidden value field, so prevent 'change' from individual date inputs.
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
		} else if ( $dateRangeField.find( '.sowb-relative-date' ).length > 0 ) {

			$dateRangeField.find( '.sowb-relative-date' ).each( function () {
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
		$dateRangeField.data( 'initialized', true );
	} );
})( jQuery );
