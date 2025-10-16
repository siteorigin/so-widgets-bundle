/* global jQuery, pikaday */

( function( $ ) {
	const setupDateRangeField = function( e ) {
		const $dateRangeField = $( this );
		const valField = $dateRangeField.find( 'input[type="hidden"][class="siteorigin-widget-input"]' );

		if ( $dateRangeField.data( 'initialized' ) ) {
			return;
		}

		if ( $dateRangeField.find( '[class*="sowb-specific-date"]' ).length > 0 ) {

			const createPikadayInput = function( inputName, initVal ) {
				const $field = $dateRangeField.find( '.' + inputName + '-picker' );

				const dateToString = function( date, format ) {
					let dateString = '';
					if ( ! isNaN( date.valueOf() ) ) {
						let day = date.getDate();
						day = day < 10 ? '0' + day.toString() : day.toString();
						let month = date.getMonth() + 1;
						month = month < 10 ? '0' + month.toString() : month.toString();
						const year = date.getFullYear();
						return year + '-' + month + '-' + day;
					}

					return dateString;
				};

				const parse = function( dateString, format ) {
					const parts = dateString.split( '-' );
					const day = parseInt( parts[2] );
					const month = parseInt( parts[1] ) - 1;
					const year = parseInt( parts[0] );
					return new Date( year, month, day );
				};

				const updateValField = function( date ) {
					const curVal = valField.val() === '' ? {} : JSON.parse( valField.val() );
					curVal[ inputName ] = dateToString( date );
					$field.val( curVal[ inputName ] );
					valField.val( JSON.stringify( curVal ) );
					valField.trigger( 'change', { silent: true } );
				};

				const picker = new Pikaday( {
					field: $field[0],
					blurFieldOnSelect: false,
					toString: dateToString,
					parse: parse,
					onSelect: updateValField,
				} );

				$field.on( 'change', function( event ) {
					const dateVal = parse( $field.val() );
					updateValField( dateVal );

					// We trigger the change event on the hidden value field,
					// so prevent 'change' from individual date inputs.
					event.preventDefault();
					return false;
				} );

				if ( initVal ) {
					$field.val( initVal );
				}
				return picker;
			}.bind( this );

			const initRange = ( valField.val() === '' || valField.val() === 'null' ) ? { after: '', before: '' } : JSON.parse( valField.val() );
			const afterPicker = createPikadayInput( 'after', initRange.after );
			const beforePicker = createPikadayInput( 'before', initRange.before );

			valField.on( 'change', function( event, data ) {
				if ( ! ( data && data.silent ) ) {
					const newRange = ( valField.val() === '' || valField.val() === 'null' ) ? { after: '', before: '' } : JSON.parse( valField.val() );
					afterPicker.setDate( newRange.after );
					beforePicker.setDate( newRange.before );
				}
			} );
		} else if ( $dateRangeField.find( '.sowb-relative-date' ).length > 0 ) {

			$dateRangeField.find( '.sowb-relative-date' ).each( function() {
				const $name = $( this ).data( 'name' );

				$( this ).on( 'change', function() {
					let range = valField.val() === '' ? {} : JSON.parse( valField.val() );

					if ( ! range.hasOwnProperty( $name ) ) {
						range[ $name ] = {};
					}

					range[ $name ][ 'value' ] = $( this ).find( '> input' ).val();
					range[ $name ][ 'unit' ] = $( this ).find( '> select' ).val();

					valField.val( JSON.stringify( range ) );
					valField.trigger( 'change', { silent: true } );
				}.bind( this ) );

				valField.on( 'change', function( event, data ) {
					if ( ! ( data && data.silent ) ) {
						const range = valField.val() === '' ? { from: {}, to: {} } : JSON.parse( valField.val() );

						if ( range.hasOwnProperty( $name ) ) {
							$( this ).find( '> input' ).val( range[ $name ][ 'value' ] );
							$( this ).find( '> select' ).val( range[ $name ][ 'unit' ] );
						}
					}
				}.bind( this ) );

			} );
		}
		$dateRangeField.data( 'initialized', true );
	};

	 // If the current page isn't the site editor, set up the Date Range field now.
	 if (
		 window.top === window.self &&
		 (
			 typeof pagenow === 'string' &&
			 pagenow !== 'site-editor'
		 )
	 ) {
		 $( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-date-range', setupDateRangeField );
	 }

	// Add support for the Site Editor.
	window.addEventListener( 'message', function( e ) {
		if ( e.data && e.data.action === 'sowbBlockFormInit' ) {
			$( '.siteorigin-widget-field-type-date-range' ).each( function() {
				setupDateRangeField.call( this );
			} );
		}
	} );
} )( jQuery );