/* globals jQuery, google, sowb */

var sowb = window.sowb || {};

sowb.SiteOriginGoogleMap = function($) {
	return {
		// So that we can always display something, even if no location or address was entered.
		DEFAULT_LOCATIONS: [
			'Addo Elephant National Park, R335, Addo',
			'Cape Town, Western Cape, South Africa',
			'San Francisco Bay Area, CA, United States',
			'New York, NY, United States',
		],
		showMap: function(element, location, options) {

			var zoom = Number(options.zoom);

			if ( !zoom ) zoom = 14;

			var userMapTypeId = 'user_map_style';

			var mapOptions = {
				zoom: zoom,
				scrollwheel: options.scrollZoom,
				draggable: options.draggable,
				disableDefaultUI: options.disableUi,
				zoomControl: options.zoomControl,
				panControl: options.panControl,
				center: location,
				mapTypeControlOptions: {
					mapTypeIds: [google.maps.MapTypeId.ROADMAP, userMapTypeId]
				}
			};

			var map = new google.maps.Map(element, mapOptions);

			var userMapOptions = {
				name: options.mapName
			};

			var userMapStyles = options.mapStyles;

			if ( userMapStyles ) {
				var userMapType = new google.maps.StyledMapType(userMapStyles, userMapOptions);

				map.mapTypes.set(userMapTypeId, userMapType);
				map.setMapTypeId(userMapTypeId);
			}

			if (options.markerAtCenter) {
				this.centerMarker = new google.maps.Marker({
					position: location,
					map: map,
					draggable: options.markersDraggable,
					icon: options.markerIcon,
					title: ''
				});
			}

			if(options.keepCentered) {
				var center;
				google.maps.event.addDomListener(map, 'idle', function () {
					center = map.getCenter();
				});
				google.maps.event.addDomListener(window, 'resize', function () {
					map.setCenter(center);
				});
			}

			this.linkAutocompleteField(options.autocomplete, options.autocompleteElement, map, options);
			this.showMarkers(options.markerPositions, map, options);
			this.showDirections(options.directions, map, options);

			// If the Google Maps element is hidden it won't display properly. This is an attempt to make it display by
			// calling resize when a custom 'show' event is fired. The 'show' event is something we fire in a few widgets
			// like Accordion and Tabs and in future any widgets which might show and hide content using `display:none;`.
			if ( $( element ).is( ':hidden' ) ) {
				var $visParent = $( element ).closest( ':visible' );
				$visParent.find( '> :hidden' ).on( 'show', function () {
					google.maps.event.trigger(map, 'resize');
					map.setCenter(location);
				} );
			}

		},

		linkAutocompleteField: function (autocomplete, autocompleteElement, map, options) {
			if( autocomplete && autocompleteElement ) {

				var updateMapLocation = function ( address ) {
					if ( this.inputAddress !== address ) {
						this.inputAddress = address;
						this.getLocation( this.inputAddress ).done(
							function ( location ) {
								map.setZoom( 15 );
								map.setCenter( location );
								if( this.centerMarker ) {
									this.centerMarker.setPosition( location );
									this.centerMarker.setTitle( this.inputAddress );
								}
							}.bind( this )
						);
					}
				}.bind( this );

				var $autocompleteElement = $( autocompleteElement );
				autocomplete.addListener( 'place_changed', function () {
					var place = autocomplete.getPlace();
					map.setZoom( 15 );
					if ( place.geometry ) {
						map.setCenter( place.geometry.location );
						if( this.centerMarker ) {
							this.centerMarker.setPosition(place.geometry.location);
						}
					}
				}.bind( this ) );

				google.maps.event.addDomListener( autocompleteElement, 'keypress', function ( event ) {
					var key = event.keyCode || event.which;
					if ( key === '13' ) {
						event.preventDefault();
					}
				} );

				$autocompleteElement.focusin( function () {
					if ( !this.resultsObserver ) {
						var autocompleteResultsContainer = document.querySelector( '.pac-container' );
						this.resultsObserver = new MutationObserver( function () {
							var $topResult = $( $( '.pac-item' ).get( 0 ) );
							var queryPartA = $topResult.find( '.pac-item-query' ).text();
							var queryPartB = $topResult.find( 'span' ).not( '[class]' ).text();
							var topQuery = queryPartA + ( queryPartB ? (', ' + queryPartB) : '' );
							if ( topQuery ) {
								updateMapLocation( topQuery );
							}
						} );

						var config = { attributes: true, childList: true, characterData: true };

						this.resultsObserver.observe( autocompleteResultsContainer, config );
					}
				}.bind( this ) );

				var revGeocode = function ( latLng ) {
					this.getGeocoder().geocode( { location: latLng }, function ( results, status ) {
						if ( status === google.maps.GeocoderStatus.OK ) {
							if ( results.length > 0 ) {
								var addr = results[ 0 ].formatted_address;
								$autocompleteElement.val( addr );
								if( this.centerMarker ) {
									this.centerMarker.setPosition(latLng);
									this.centerMarker.setTitle(addr);
								}
							}
						}
					}.bind( this ) );
				}.bind( this );

				map.addListener( 'click', function ( event ) {
					revGeocode( event.latLng );
				} );

				this.centerMarker.addListener( 'dragend', function ( event ) {
					revGeocode( event.latLng );
				} );
			}
		},

		showMarkers: function(markerPositions, map, options) {
			if ( markerPositions && markerPositions.length ) {
				this.infoWindows = [];
				var markerBatches = [];
				var BATCH_SIZE = 10;
				// Group markers into batches of 10 in attempt to avoid query limits
				for ( var i = 0; i < markerPositions.length; i++ ) {
					var batchIndex = parseInt( i / BATCH_SIZE ); // truncate decimals
					if ( markerBatches.length === batchIndex ) {
						markerBatches[ batchIndex ] = [];
					}
					markerBatches[ batchIndex ][ i % BATCH_SIZE ] = markerPositions[ i ];
				}

				var geocodeMarkerBatch = function ( markerBatchHead, markerBatchTail ) {
					var doneCount = 0;
					markerBatchHead.forEach( function ( mrkr ) {
						this.getLocation( mrkr.place ).done( function ( location ) {
							var mrkerIcon = options.markerIcon;
							if(mrkr.custom_marker_icon) {
								mrkerIcon = mrkr.custom_marker_icon;
							}

							var marker = new google.maps.Marker( {
								position: location,
								map: map,
								draggable: options.markersDraggable,
								icon: mrkerIcon,
								title: ''
							} );

							if ( mrkr.hasOwnProperty( 'info' ) && mrkr.info ) {
								var infoWindowOptions = { content: mrkr.info };

								if ( mrkr.hasOwnProperty( 'info_max_width' ) && mrkr.info_max_width ) {
									infoWindowOptions.maxWidth = mrkr.info_max_width;
								}

								var infoDisplay = options.markerInfoDisplay;
								infoWindowOptions.disableAutoPan = infoDisplay === 'always';
								var infoWindow = new google.maps.InfoWindow( infoWindowOptions );
								this.infoWindows.push( infoWindow );
								var openEvent = infoDisplay;
								if ( infoDisplay === 'always' ) {
									openEvent = 'click';
									infoWindow.open( map, marker );
								}
								marker.addListener( openEvent, function () {
									infoWindow.open( map, marker );
									if ( infoDisplay !== 'always' && !options.markerInfoMultiple ) {
										this.infoWindows.forEach( function ( iw ) {
											if ( iw !== infoWindow ) {
												iw.close();
											}
										} );
									}
								}.bind( this ) );
								if ( infoDisplay === 'mouseover' ) {
									marker.addListener( 'mouseout', function () {
										setTimeout( function () {
											infoWindow.close();
										}, 100 );
									} );
								}
							}
							if ( ++doneCount === markerBatchHead.length && markerBatchTail.length ) {
								geocodeMarkerBatch( markerBatchTail.shift(), markerBatchTail );
							}
						}.bind( this ) );
					}.bind( this ) );
				}.bind( this );
				geocodeMarkerBatch( markerBatches.shift(), markerBatches );

			}
		},
		showDirections: function(directions, map) {
			if ( directions ) {
				if ( directions.waypoints && directions.waypoints.length ) {
					directions.waypoints.map(
						function (wypt) {
							wypt.stopover = Boolean(wypt.stopover);
						}
					);
				}

				var directionsRenderer = new google.maps.DirectionsRenderer();
				directionsRenderer.setMap(map);

				var directionsService = new google.maps.DirectionsService();
				directionsService.route({
						origin: directions.origin,
						destination: directions.destination,
						travelMode: directions.travelMode.toUpperCase(),
						avoidHighways: directions.avoidHighways,
						avoidTolls: directions.avoidTolls,
						waypoints: directions.waypoints,
						optimizeWaypoints: directions.optimizeWaypoints,
					},
					function(result, status) {
						if (status === google.maps.DirectionsStatus.OK) {
							directionsRenderer.setOptions( { preserveViewport: directions.preserveViewport } );
							directionsRenderer.setDirections(result);
						}
					});
			}
		},
		initMaps: function() {
			// Init any autocomplete fields first.
			var $autoCompleteFields = $( '.sow-google-map-autocomplete' );
			var autoCompleteInit = new $.Deferred();
			if( $autoCompleteFields.length === 0 ) {
				autoCompleteInit.resolve();
			} else {
				$autoCompleteFields.each(function (index, element) {

					if ( typeof google.maps.places === 'undefined' ) {
						autoCompleteInit.reject('Sorry, we couldn\'t load the "places" library due to another plugin, so the autocomplete feature is not available.');
						return;
					}

					var autocomplete = new google.maps.places.Autocomplete(
						element,
						{types: ['address']}
					);

					var $mapField = $(element).siblings('.sow-google-map-canvas');

					if ($mapField.length > 0) {
						var options = $mapField.data('options');
						options.autocomplete = autocomplete;
						options.autocompleteElement = element;
						this.getLocation(options.address).done(
							function (location) {
								this.showMap($mapField.get(0), location, options);
								$mapField.data('initialized', true);
								autoCompleteInit.resolve();
							}.bind(this)
						).fail(function () {
							$mapField.append('<div><p><strong>' + soWidgetsGoogleMap.geocode.noResults + '</strong></p></div>');
							autoCompleteInit.reject();
						});
					}
				}.bind(this));
			}

			autoCompleteInit.always(function(){
				$('.sow-google-map-canvas').each(function (index, element) {
					var $$ = $(element);

					if( $$.data( 'initialized' ) ) {
						// Already initialized so continue to next element.
						return true;
					}

					var options = $$.data( 'options' );
					var address = options.address;
					// If no address was specified, but we have markers, use the first marker as the map center.
					if(!address) {
						var markers = options.markerPositions;
						if(markers && markers.length) {
							address = markers[0].place;
						}
					}

					this.getLocation( address ).done(
						function ( location ) {
							this.showMap( $$.get( 0 ), location, options );
							$$.data( 'initialized' );
						}.bind( this )
					).fail( function () {
						$$.append( '<div><p><strong>' + soWidgetsGoogleMap.geocode.noResults + '</strong></p></div>' );
					} );

				}.bind(this));
			}.bind(this))
			.fail(function(error){
				console.log(error);
			});
		},
		getGeocoder: function () {
			if ( !this._geocoder ) {
				this._geocoder = new google.maps.Geocoder();
			}
			return this._geocoder;
		},
		getLocation: function ( inputLocation ) {
			var locationPromise = new $.Deferred();
			var location = { address: inputLocation };
			//check if address is actually a valid latlng
			var latLng;
			if ( inputLocation && inputLocation.indexOf( ',' ) > -1 ) {
				var vals = inputLocation.split( ',' );
				// A latlng value should be of the format 'lat,lng'
				if ( vals && vals.length === 2 ) {
					latLng = new google.maps.LatLng( vals[ 0 ], vals[ 1 ] );
					// Let the API decide if we have a valid latlng
					// This should fail if the input is an address containing a comma
					// e.g. 123 Sesame Street, Middleburg, FL, United States
					if ( !(isNaN( latLng.lat() ) || isNaN( latLng.lng() )) ) {
						location = { location: { lat: latLng.lat(), lng: latLng.lng() } };
					}
				}
			}

			if ( location.hasOwnProperty( 'location' ) ) {
				// We're using entered latlng coordinates directly
				locationPromise.resolve( location.location );
			} else if ( location.hasOwnProperty( 'address' ) ) {

				// Either user entered an address, or fall back to defaults and use the geocoder.
				if ( !location.address ) {
					var rndIndx = parseInt( Math.random() * this.DEFAULT_LOCATIONS.length );
					location.address = this.DEFAULT_LOCATIONS[ rndIndx ];
				}
				var onGeocodeResults = function ( results, status ) {
					if ( status === google.maps.GeocoderStatus.OK ) {
						locationPromise.resolve( results[ 0 ].geometry.location );
					} else if ( status === google.maps.GeocoderStatus.OVER_QUERY_LIMIT ) {
						//try again please
						setTimeout( function () {
							this.getGeocoder().geocode.call( this, location, onGeocodeResults );
						}.bind( this ), 100 );
					} else if ( status === google.maps.GeocoderStatus.ZERO_RESULTS ) {
						locationPromise.reject( status );
					}
				}.bind( this );

				this.getGeocoder().geocode( location, onGeocodeResults );
			}
			return locationPromise;
		},
	};
};

// Called by Google Maps API when it has loaded.
function soGoogleMapInitialize() {
	new sowb.SiteOriginGoogleMap(jQuery).initMaps();
}

jQuery(function ($) {

	sowb.setupGoogleMaps = function() {
		var libraries = [];
		var apiKey;
		$('.sow-google-map-canvas').each(function(index, element) {
			var $this = $(element);
			var mapOptions = $this.data( 'options' );
			if ( mapOptions) {
				if( typeof mapOptions.libraries !== 'undefined' && mapOptions.libraries !== null ) {
					libraries = libraries.concat(mapOptions.libraries);
				}
				if( !apiKey && mapOptions.apiKey ) {
					apiKey = mapOptions.apiKey;
				}
			}
		});

		var mapsApiLoaded = typeof window.google !== 'undefined' && typeof window.google.maps !== 'undefined';
		if ( mapsApiLoaded ) {
			soGoogleMapInitialize();
		} else {
			var apiUrl = 'https://maps.googleapis.com/maps/api/js?callback=soGoogleMapInitialize';

			if ( libraries && libraries.length ) {
				apiUrl += '&libraries=' + libraries.join(',');
			}

			if ( apiKey ) {
				apiUrl += '&key=' + apiKey;
			}

			// This allows us to "catch" Google Maps JavaScript API errors and do a bit of custom handling. In this case,
			// we display a user-specified fallback image if there is one.
			if ( window.console && window.console.error ) {
				var errLog = window.console.error;

				sowb.onLoadMapsApiError = function ( error ) {
					var matchError = error.match( /^Google Maps API (error|warning): ([^\s]*)\s([^\s]*)(?:\s(.*))?/ );
					if ( matchError && matchError.length && matchError[0] ) {
						$( '.sow-google-map-canvas' ).each( function ( index, element ) {
							var $this = $( element );
							if ( $this.data( 'fallbackImage' ) ) {
								var imgData = $this.data( 'fallbackImage' );
								if ( imgData.hasOwnProperty( 'img' ) ) {
									$this.append( imgData.img );
								}
							}
						} );
					}
					errLog.apply( window.console, arguments );
				};

				window.console.error = sowb.onLoadMapsApiError;
			}

			$( 'body' ).append( '<script async type="text/javascript" src="' + apiUrl + '">' );
		}
	};
	sowb.setupGoogleMaps();

	$( sowb ).on( 'setup_widgets', sowb.setupGoogleMaps );

});

window.sowb = sowb;
