/**
 * (c) SiteOrigin, freely distributable under the terms of the GPL 2.0 license.
 */

var SiteOriginGoogleMap = function($) {
	return {
		showMap: function(element, location, options) {
			var zoom = Number(options.zoom);
			if ( !zoom ) zoom = 14;

			var userMapTypeId = 'user_map_style';

			var mapOptions = {
				zoom: zoom,
				scrollwheel: options.scrollZoom,
				draggable: options.draggable,
				disableDefaultUI: options.disableUi,
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
				new google.maps.Marker({
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

			this.showMarkers(options.markerPositions, map, options);
			this.showDirections(options.directions, map, options);
		},
		showMarkers: function(markerPositions, map, options) {
			if ( markerPositions && markerPositions.length ) {
				var geocoder = new google.maps.Geocoder();
				markerPositions.forEach(
					function (mrkr) {
						var geocodeMarker = function () {
							geocoder.geocode({'address': mrkr.place}, function (res, status) {
								if (status == google.maps.GeocoderStatus.OK) {

									var marker = new google.maps.Marker({
										position: res[0].geometry.location,
										map: map,
										draggable: options.markersDraggable,
										icon: options.markerIcon,
										title: ''
									});

									if (mrkr.hasOwnProperty('info') && mrkr.info) {
										var infoWindowOptions = {content: mrkr.info};

										if (mrkr.hasOwnProperty('info_max_width') && mrkr.info_max_width) {
											infoWindowOptions.maxWidth = mrkr.info_max_width;
										}

										var infoDisplay = options.markerInfoDisplay;
										infoWindowOptions.disableAutoPan = infoDisplay == 'always';
										var infoWindow = new google.maps.InfoWindow(infoWindowOptions);
										if (infoDisplay == 'always') {
											infoWindow.open(map, marker);
											marker.addListener('click', function () {
												infoWindow.open(map, marker);
											});
										} else {
											marker.addListener(infoDisplay, function () {
												infoWindow.open(map, marker);
											});
										}
									}
								} else if (status == google.maps.GeocoderStatus.OVER_QUERY_LIMIT) {
									//try again please
									setTimeout(geocodeMarker, Math.random() * 1000, mrkr);
								}
							});
						};
						//set random delays of 0 - 1 seconds when geocoding markers to try avoid hitting the query limit
						setTimeout(geocodeMarker, Math.random() * 1000, mrkr);
					}
				);
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
						if (status == google.maps.DirectionsStatus.OK) {
							directionsRenderer.setDirections(result);
						}
					});
			}
		},
		loadMaps: function() {
			$('.sow-google-map-canvas').each(function (index, element) {
				var $$ = $(element);

				var options = $$.data('options');
				var address = options.address;
				// If no address was specified, but we have markers, use the first marker as the map center.
				if(!address) {
					var markers = options.markerPositions;
					if(markers && markers.length) {
						address = markers[0].place;
					}
				}
				var args = {'address': address};

				//check if address is actually a valid latlng
				var latLng;
				if(address && address.indexOf(',') > -1) {
					var vals = address.split(',');
					// A latlng value should be of the format 'lat,lng'
					if(vals && vals.length == 2) {
						latLng = new google.maps.LatLng(vals[0], vals[1]);
						// If we have a valid latlng
						if(!(isNaN(latLng.lat()) || isNaN(latLng.lng()))) {
							args = {'location': {lat:latLng.lat(), lng:latLng.lng()}};
						}
					}
				}

				// We're using entered latlng coordinates directly
				if(args.hasOwnProperty('location')) {
					this.showMap(element, args.location, options);
				} else if(args.hasOwnProperty('address')) {
					// User entered an address so use the geocoder.
					var geocoder = new google.maps.Geocoder();
					geocoder.geocode(args, function (results, status) {
						if (status == google.maps.GeocoderStatus.OK) {
							this.showMap(element, results[0].geometry.location, options);
						}
						else if (status == google.maps.GeocoderStatus.ZERO_RESULTS) {
							$$.append('<div><p><strong>There were no results for the place you entered. Please try another.</strong></p></div>');
						}
					}.bind(this));
				}

			}.bind(this));
		}
	};
};

function soGoogleMapInitialize() {
    new SiteOriginGoogleMap(window.jQuery).loadMaps();
}

jQuery(function ($) {
    if (window.google && window.google.maps) {
        new SiteOriginGoogleMap($).loadMaps();
    } else {
        var apiKey = $('.sow-google-map-canvas').data('api-key');

        var apiUrl = 'https://maps.googleapis.com/maps/api/js?v=3.exp&callback=soGoogleMapInitialize';
        if(apiKey) {
            apiUrl += '&key=' + apiKey;
        }
        var script = $('<script type="text/javascript" src="' + apiUrl + '">');
        $('body').append(script);
    }
});
