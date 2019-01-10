/* global jQuery, sowbForms */

window.sowbForms = window.sowbForms || {};

sowbForms.LocationField = function () {
	return {
		init: function ( element ) {
			
			if ( typeof google.maps.places === 'undefined' ) {
				console.error( 'Failed to load the places library.' );
				return;
			}
			
			var $inputField = $( element ).find( '.siteorigin-widget-location-input' );
			var $valueField = $( element ).find( '.siteorigin-widget-input' );
			var autocomplete = new google.maps.places.Autocomplete( $inputField.get( 0 ) );
			
			var getSimplePlace = function ( place ) {
				var promise = new $.Deferred();
				var simplePlace = { name: place.name };
				simplePlace.address = place.hasOwnProperty( 'formatted_address' ) ? place.formatted_address : '';
				if ( place.hasOwnProperty( 'geometry' ) ) {
					simplePlace.location = place.geometry.location.toString();
					promise.resolve( simplePlace );
				} else {
					var addr = { address: place.hasOwnProperty( 'formatted_address' ) ? place.formatted_address : place.name };
					new google.maps.Geocoder().geocode( addr,
						function ( results, status ) {
							if ( status === google.maps.GeocoderStatus.OK ) {
								simplePlace.location = results[ 0 ].geometry.location.toString();
								promise.resolve( simplePlace );
							} else {
								promise.reject( status );
							}
						} );
				}
				return promise;
			};
			
			var onPlaceChanged = function () {
				var place = autocomplete.getPlace();
				
				getSimplePlace( place )
				.done( function ( simplePlace ) {
					$valueField.val( JSON.stringify( simplePlace ) )
				} )
				.fail( function ( status ) {
					console.warn( 'Geocoding failed for "' + place.name + '" with status: ' + status );
				} );
			};

			autocomplete.addListener( 'place_changed', onPlaceChanged );
		}
	};
};

sowbForms.setupLocationFields = function () {
	if ( google && google.maps && google.maps.places ) {
		$( '.siteorigin-widget-field-type-location' ).each( function ( index, element ) {
			if ( ! $( element ).data( 'initialized' ) ) {
				new sowbForms.LocationField().init( element );
				$( element ).data( 'initialized', true );
			}
		} );
	}
};

// Called by Google Maps API when it has loaded.
function sowbAdminGoogleMapInit() {
	sowbForms.setupLocationFields();
}

( function ( $ ) {
	
	$( document ).on( 'sowsetupformfield', '.siteorigin-widget-field-type-location', function () {
		
		if ( sowbForms.mapsInitialized ) {
			sowbForms.setupLocationFields();
			return;
		}
		
		var $apiKeyField = $( this ).closest( '.siteorigin-widget-form' ).find( 'input[type="text"][name*="api_key"]' ).first();
		var apiKey = $apiKeyField.val();
		if ( ! apiKey ) {
			console.warn( 'Could not find API key. Google Maps API key is required.' );
		}
		
		var apiUrl = 'https://maps.googleapis.com/maps/api/js?key=' + apiKey + '&libraries=places&callback=sowbAdminGoogleMapInit';
		
		$( 'body' ).append( '<script async type="text/javascript" src="' + apiUrl + '">' );
		
		sowbForms.mapsInitialized = true;
	} );

} )( jQuery );
