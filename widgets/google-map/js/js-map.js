/**
 * (c) SiteOrigin, freely distributable under the terms of the GPL 2.0 license.
 */

var SiteOriginGoogleMap = {
    loadMap: function($) {
        $('.sow-google-map-canvas').each(function () {
            var $$ = $(this);
            var mapCenter = $$.data('address');
            if(!mapCenter) {
                var markers = $$.data('marker-positions');
                if(markers && markers.length) {
                    mapCenter = markers[0].place;
                }
            }
            // We use the geocoder
            var geocoder = new google.maps.Geocoder();
            geocoder.geocode({'address': mapCenter}, function (results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    var zoom = Number($$.data('zoom'));
                    if ( !zoom ) zoom = 14;

                    var userMapTypeId = 'user_map_style';

                    var mapOptions = {
                        zoom: zoom,
                        scrollwheel: Boolean( $$.data('scroll-zoom') ),
                        draggable: Boolean( $$.data('draggable') ),
                        disableDefaultUI: Boolean( $$.data('disable-ui') ),
                        center: results[0].geometry.location,
                        mapTypeControlOptions: {
                            mapTypeIds: [google.maps.MapTypeId.ROADMAP, userMapTypeId]
                        }
                    };

                    var map = new google.maps.Map($$.get(0), mapOptions);

                    var userMapOptions = {
                        name: $$.data('map-name')
                    };

                    var userMapStyles = $$.data('map-styles');

                    if ( userMapStyles ) {
                        var userMapType = new google.maps.StyledMapType(userMapStyles, userMapOptions);

                        map.mapTypes.set(userMapTypeId, userMapType);
                        map.setMapTypeId(userMapTypeId);
                    }

                    if ( Boolean( $$.data('marker-at-center' ) ) ) {

                        new google.maps.Marker({
                            position: results[0].geometry.location,
                            map: map,
                            draggable: Boolean( $$.data('markers-draggable') ),
                            icon: $$.data('marker-icon'),
                            title: ''
                        });
                    }
                    var markerPositions = $$.data('marker-positions');
                    if ( markerPositions && markerPositions.length ) {
                        markerPositions.forEach(
                            function(mrkr) {
                                var geocodeMarker = function () {
                                    geocoder.geocode({'address': mrkr.place}, function (res, status) {
                                        if (status == google.maps.GeocoderStatus.OK) {

                                            var marker = new google.maps.Marker({
                                                position: res[0].geometry.location,
                                                map: map,
                                                draggable: Boolean($$.data('markers-draggable')),
                                                icon: $$.data('marker-icon'),
                                                title: ''
                                            });

                                            if(mrkr.hasOwnProperty('info') && mrkr.info) {
                                                var infoWindowOptions = { content: mrkr.info };

                                                if(mrkr.hasOwnProperty('info_max_width') && mrkr.info_max_width) {
                                                    infoWindowOptions.maxWidth = mrkr.info_max_width;
                                                }

                                                var infoDisplay = $$.data('marker-info-display');
                                                infoWindowOptions.disableAutoPan = infoDisplay == 'always';
                                                var infoWindow = new google.maps.InfoWindow(infoWindowOptions);
                                                if( infoDisplay == 'always') {
                                                    infoWindow.open(map, marker);
                                                } else {
                                                    marker.addListener(infoDisplay, function() {
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

                    var directions = $$.data('directions');
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
                                avoidHighways: Boolean( directions.avoidHighways ),
                                avoidTolls: Boolean( directions.avoidTolls ),
                                waypoints: directions.waypoints,
                                optimizeWaypoints: Boolean( directions.optimizeWaypoints )
                            },
                            function(result, status) {
                                if (status == google.maps.DirectionsStatus.OK) {
                                    directionsRenderer.setDirections(result);
                                }
                            });
                    }

                    if(Boolean( $$.data('keep-centered') )) {
                        var center;
                        google.maps.event.addDomListener(map, 'idle', function () {
                            center = map.getCenter();
                        });
                        google.maps.event.addDomListener(window, 'resize', function () {
                            map.setCenter(center);
                        });
                    }
                }
                else if (status == google.maps.GeocoderStatus.ZERO_RESULTS) {
                    $$.append('<div><p><strong>There were no results for the place you entered. Please try another.</strong></p></div>');
                }
            });
        });
    }
};

function soGoogleMapInitialize() {
    SiteOriginGoogleMap.loadMap(window.jQuery);
}

jQuery(function ($) {
    if (window.google && window.google.maps) {
        SiteOriginGoogleMap.loadMap($);
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
