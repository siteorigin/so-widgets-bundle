const {
	expect,
	test
} = require( '@playwright/test' );

const common = require( 'siteorigin-tests-common/playwright/common' );

const {
	doLogin,
	setupRequestUtils,
	soGoTo,
} = common;

const {
	openSection,
} = require( 'siteorigin-tests-common/playwright/utilities/widgets-bundle' );

/**
 * A marker's Place field sits inside a repeater item that is collapsed when
 * the form opens, and the location field only initialises once it is
 * visible. Expanding the item must set the field up, so the place the user
 * types is written to the value that is saved.
 *
 * The form runs in Page Builder's widget dialog, in the top document.
 */

const MAPS_API_URL = /^https:\/\/maps\.googleapis\.com\/maps\/api\/js/;

/**
 * Serves a minimal Google Maps API in place of maps.googleapis.com, so the
 * spec needs no API key or network access. It defines what the location
 * field and the front end's map script call, records what they ask for, then
 * runs the callback named in the script URL.
 *
 * In the form the geocoder finds nothing, so a typed place stays as typed.
 * Pass `{ geocode: 'OK' }` for the front end, where the map only draws once
 * its center is found.
 */
const stubGoogleMapsApi = async ( page, { geocode = 'ZERO_RESULTS' } = {} ) => {
	await page.route( MAPS_API_URL, ( route ) => {
		const callback = new URL( route.request().url() ).searchParams.get( 'callback' );

		route.fulfill( {
			contentType: 'application/javascript',
			body: `
				( function () {
					var noop = function () {};

					window.__geocodeRequests = [];
					window.__directionsRequests = [];

					// Text is never a coordinate: only numbers are accepted.
					var LatLng = function ( lat, lng ) {
						var latitude = Number( lat );
						var longitude = Number( lng );
						if (
							String( lat ).trim() === '' || String( lng ).trim() === '' ||
							! isFinite( latitude ) || ! isFinite( longitude )
						) {
							throw new Error( 'LatLng: not a number' );
						}
						this.lat = function () { return latitude; };
						this.lng = function () { return longitude; };
					};

					var Marker = function () {
						this.addListener = noop;
					};

					window.google = window.google || {};
					window.google.maps = {
						places: {
							Autocomplete: function () {
								this.addListener = function () {};
								this.getPlace = function () {
									return {};
								};
							},
						},
						Geocoder: function () {
							this.geocode = function ( request, done ) {
								window.__geocodeRequests.push( request.address );
								if ( ${ JSON.stringify( geocode ) } === 'OK' ) {
									done( [ { geometry: { location: new LatLng( 51.5, -0.16 ) } } ], 'OK' );
								} else {
									done( [], 'ZERO_RESULTS' );
								}
							};
						},
						GeocoderStatus: {
							OK: 'OK',
							OVER_QUERY_LIMIT: 'OVER_QUERY_LIMIT',
							ZERO_RESULTS: 'ZERO_RESULTS',
							OVER_DAILY_LIMIT: 'OVER_DAILY_LIMIT',
						},
						LatLng: LatLng,
						MapTypeId: { ROADMAP: 'roadmap', SATELLITE: 'satellite' },
						Map: function ( element ) {
							this.element = element;
							this.mapTypes = { set: noop };
							this.setMapTypeId = noop;
							this.setCenter = noop;
							this.setZoom = noop;
							this.fitBounds = noop;
							this.addListener = noop;
						},
						StyledMapType: function () {},
						Marker: Marker,
						marker: { AdvancedMarkerElement: Marker },
						InfoWindow: function ( options ) {
							this.open = function ( target ) {
								var map = target.map || target;
								var info = document.createElement( 'div' );
								info.className = 'sowb-test-info';
								info.innerHTML = options.content;
								map.element.appendChild( info );
							};
							this.close = noop;
						},
						DirectionsService: function () {
							this.route = function ( request, done ) {
								window.__directionsRequests.push( {
									origin: request.origin,
									destination: request.destination,
									waypoints: request.waypoints,
								} );
								done( {}, 'OK' );
							};
						},
						DirectionsRenderer: function () {
							this.setMap = noop;
							this.setOptions = noop;
							this.setDirections = noop;
						},
						DirectionsStatus: { OK: 'OK' },
						event: { addListener: noop },
					};

					window[ ${ JSON.stringify( callback ) } ]();
				} )();
			`,
		} );
	} );
};

/**
 * Clicks a button that saves the page and waits for the editor to load again.
 */
const saveAndReload = async ( page, button ) => {
	const navigated = page.waitForEvent( 'framenavigated', ( frame ) => frame === page.mainFrame() );
	await page.locator( button ).click();
	await navigated;
	await page.waitForLoadState();
};

/**
 * Saves the page as a draft and waits for the editor to load again.
 */
const saveDraft = ( page ) => saveAndReload( page, '#save-post' );

/**
 * Publishes the page and waits for the editor to load again.
 */
const publish = ( page ) => saveAndReload( page, '#publish' );

/**
 * Opens the form of the page's one Google Maps widget, with the stubbed API
 * loaded.
 */
const openGoogleMapsWidgetForm = async ( page ) => {
	const builder = page.locator( '#siteorigin-panels-metabox' );
	await expect( builder ).toBeVisible( { timeout: 20000 } );
	await builder.locator( '.so-widget .title h4' ).click();

	const form = page.locator( '.so-panels-dialog-edit-widget .siteorigin-widget-form-main-siteorigin-widget-googlemap-widget' );
	await expect( form ).toBeVisible( { timeout: 20000 } );

	// The Map center field initialising proves the stubbed API loaded.
	await expect( form.locator( '.siteorigin-widget-field-map_center' ) ).toHaveAttribute( 'data-initialized', 'true', { timeout: 10000 } );

	return form;
};

/**
 * Closes the open widget dialog with its Done button.
 */
const closeWidgetDialog = async ( page ) => {
	const doneButton = page.locator( '.so-panels-dialog-edit-widget' ).getByRole( 'button', { name: 'Done', exact: true } );
	await expect( doneButton ).toBeVisible( { timeout: 20000 } );
	await doneButton.click();
	await expect( doneButton ).toBeHidden();
};

/**
 * Creates a draft Page Builder page holding a Google Maps widget and returns
 * its ID. The draft is saved and reloaded, so the widget's form scripts are
 * enqueued; until then the form only asks for a save and reload.
 */
const createGoogleMapsPage = async ( page ) => {
	await soGoTo( page, 'wp-admin/post-new.php?post_type=page' );

	// A new page opens in the classic editor; switch it to Page Builder.
	await page.locator( '#content-panels' ).click();

	const builder = page.locator( '#siteorigin-panels-metabox' );
	await expect( builder ).toBeVisible( { timeout: 20000 } );

	await builder.locator( '.so-builder-toolbar .so-widget-add' ).click();

	const addDialog = page.locator( '.so-panels-dialog-add-widget' );
	await addDialog.locator( '.so-sidebar-search' ).fill( 'Google Maps' );
	await addDialog.locator( '.widget-type', { hasText: 'SiteOrigin Google Maps' } ).click();

	// Page Builder opens the edit dialog for a new widget.
	await closeWidgetDialog( page );

	await page.locator( '#title' ).fill( 'WB Google Maps marker place' );
	await saveDraft( page );

	return Number( new URL( page.url() ).searchParams.get( 'post' ) );
};

/**
 * Returns the marker positions stored for the page's Google Maps widget, as
 * Page Builder loaded them into the editor.
 */
const getStoredMarkerPositions = async ( page ) => {
	const panelsData = JSON.parse( await page.locator( '#siteorigin-panels-metabox .siteorigin-panels-data-field' ).inputValue() );
	const widget = panelsData.widgets.find( ( item ) => item.panels_info.class === 'SiteOrigin_Widget_GoogleMap_Widget' );

	return widget.markers.marker_positions;
};

/**
 * Returns the repeater items of the open form's Marker positions field.
 */
const getMarkerItems = async ( form ) => {
	const markers = await openSection( 'markers', form );

	return markers.locator( '.siteorigin-widget-field-marker_positions .siteorigin-widget-field-repeater > .siteorigin-widget-field-repeater-items > .siteorigin-widget-field-repeater-item' );
};

/**
 * Expands a collapsed marker item with a click and returns its Place field,
 * after checking that the field was not set up while the item was collapsed.
 */
const expandMarker = async ( item ) => {
	const itemForm = item.locator( '> .siteorigin-widget-field-repeater-item-form' );
	const place = item.locator( '.siteorigin-widget-field-type-location' );

	await expect( itemForm ).toBeHidden();
	await expect( place ).not.toHaveAttribute( 'data-initialized', 'true' );

	await item.locator( '> .siteorigin-widget-field-repeater-item-top' ).click();
	await expect( itemForm ).toBeVisible();

	return place;
};

test.beforeEach( async ( { page } ) => {
	await doLogin( page );
} );

test( 'Google Maps: a marker\'s Place field initialises when its item is expanded, and the typed place is saved', async ( { page } ) => {
	const placeName = 'Leeds Dock, Leeds';

	await stubGoogleMapsApi( page );

	const requestUtils = await setupRequestUtils();
	let postId = 0;

	try {
		postId = await createGoogleMapsPage( page );

		// A newly added marker.
		let form = await openGoogleMapsWidgetForm( page );
		let items = await getMarkerItems( form );

		await form.locator( '.siteorigin-widget-field-marker_positions .siteorigin-widget-field-repeater > .siteorigin-widget-field-repeater-add' ).click();
		await expect( items ).toHaveCount( 1 );

		let place = await expandMarker( items.first() );
		await expect( place ).toHaveAttribute( 'data-initialized', 'true', { timeout: 10000 } );

		const placeInput = place.locator( '.siteorigin-widget-location-input' );
		await placeInput.fill( placeName );
		await placeInput.press( 'Tab' );

		await expect( place.locator( '.location-field-data' ) ).toHaveValue( JSON.stringify( { name: placeName } ) );

		await closeWidgetDialog( page );
		await saveDraft( page );

		expect( await getStoredMarkerPositions( page ) ).toEqual( [
			expect.objectContaining( { place: { name: placeName } } ),
		] );

		// The saved marker, now an existing item that is collapsed when the
		// form opens.
		form = await openGoogleMapsWidgetForm( page );
		items = await getMarkerItems( form );
		await expect( items ).toHaveCount( 1 );

		place = await expandMarker( items.first() );
		await expect( place ).toHaveAttribute( 'data-initialized', 'true', { timeout: 10000 } );
		await expect( place.locator( '.location-field-data' ) ).toHaveValue( JSON.stringify( { name: placeName } ) );
	} finally {
		if ( postId ) {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/pages/${ postId }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	}
} );

test( 'Google Maps: text typed with an ampersand reaches the map as typed', async ( { page } ) => {
	// No address has exactly one comma: the map script reads such a value as
	// coordinates before it geocodes it.
	const center = 'Marks & Spencer, 458 Oxford Street, London W1C 1AP';
	const markerPlace = 'Fortnum & Mason, 181 Piccadilly, London';
	const origin = 'King\'s Cross & St Pancras, London';
	// A browser reads the numeric reference 128 as a euro sign and PHP's own
	// decoder leaves it as typed, so this value shows which one the map got.
	const waypoint = 'Harrods &#128;5 Tea, 87 Brompton Road, London';
	const waypointAsRead = 'Harrods \u20ac5 Tea, 87 Brompton Road, London';
	const info = '<span data-font-info="{&quot;font&quot;:&quot;DynaPuff&quot;}">Tea &amp; cake</span>';

	await stubGoogleMapsApi( page );

	const requestUtils = await setupRequestUtils();
	let postId = 0;

	try {
		postId = await createGoogleMapsPage( page );

		const form = await openGoogleMapsWidgetForm( page );

		const centerInput = form.locator( '.siteorigin-widget-field-map_center .siteorigin-widget-location-input' );
		await centerInput.fill( center );
		await centerInput.press( 'Tab' );

		const items = await getMarkerItems( form );
		await form.locator( '.siteorigin-widget-field-marker_positions .siteorigin-widget-field-repeater > .siteorigin-widget-field-repeater-add' ).click();
		await expect( items ).toHaveCount( 1 );

		const marker = items.first();
		const place = await expandMarker( marker );
		await expect( place ).toHaveAttribute( 'data-initialized', 'true', { timeout: 10000 } );

		const placeInput = place.locator( '.siteorigin-widget-location-input' );
		await placeInput.fill( markerPlace );
		await placeInput.press( 'Tab' );

		// Info Window Content, entered as HTML.
		const infoField = marker.locator( '.siteorigin-widget-field-info' );
		await infoField.locator( '.wp-switch-editor.switch-html' ).click();
		const infoTextarea = infoField.locator( 'textarea.wp-editor-area' );
		await expect( infoTextarea ).toBeVisible();
		await infoTextarea.fill( info );
		await infoTextarea.press( 'Tab' );

		await form.locator( '.siteorigin-widget-field-info_display' ).getByLabel( 'Always', { exact: true } ).check();

		const directions = await openSection( 'directions', form );
		const originInput = directions.locator( '.siteorigin-widget-field-origin input[type="text"]' );
		await originInput.fill( origin );
		await originInput.press( 'Tab' );
		const destinationInput = directions.locator( '.siteorigin-widget-field-destination input[type="text"]' );
		await destinationInput.fill( center );
		await destinationInput.press( 'Tab' );

		const waypoints = directions.locator( '.siteorigin-widget-field-waypoints' );
		await waypoints.locator( '.siteorigin-widget-field-repeater > .siteorigin-widget-field-repeater-add' ).click();
		const waypointItem = waypoints.locator( '.siteorigin-widget-field-repeater > .siteorigin-widget-field-repeater-items > .siteorigin-widget-field-repeater-item' ).first();
		const waypointForm = waypointItem.locator( '> .siteorigin-widget-field-repeater-item-form' );
		if ( ! await waypointForm.isVisible() ) {
			await waypointItem.locator( '> .siteorigin-widget-field-repeater-item-top' ).click();
		}
		const waypointInput = waypointForm.locator( '.siteorigin-widget-field-location textarea' );
		await waypointInput.fill( waypoint );
		await waypointInput.press( 'Tab' );

		await closeWidgetDialog( page );
		await publish( page );

		// The front end: the map only draws once its center is found.
		await page.unroute( MAPS_API_URL );
		await stubGoogleMapsApi( page, { geocode: 'OK' } );
		await soGoTo( page, `?page_id=${ postId }` );

		const canvas = page.locator( '.sow-google-map-canvas' );
		await expect( canvas.locator( '.sowb-test-info' ) ).toHaveCount( 1, { timeout: 20000 } );
		await expect.poll( () => page.evaluate( () => window.__directionsRequests.length ) ).toBe( 1 );

		const seen = await page.evaluate( () => ( {
			optionsType: typeof jQuery( '.sow-google-map-canvas' ).data( 'options' ),
			geocodeRequests: window.__geocodeRequests.slice().sort(),
			directions: window.__directionsRequests[ 0 ],
			info: document.querySelector( '.sow-google-map-canvas .sowb-test-info' ).innerHTML,
			fontInfo: document.querySelector( '.sow-google-map-canvas .sowb-test-info span' ).getAttribute( 'data-font-info' ),
		} ) );

		expect( seen.optionsType ).toBe( 'object' );
		expect( seen.geocodeRequests ).toEqual( [ markerPlace, center ].sort() );
		expect( seen.directions.origin ).toBe( origin );
		expect( seen.directions.destination ).toBe( center );
		expect( seen.directions.waypoints.map( ( item ) => item.location ) ).toEqual( [ waypointAsRead ] );
		expect( seen.info ).toContain( 'Tea &amp; cake' );
		expect( seen.fontInfo ).toBe( '{"font":"DynaPuff"}' );
	} finally {
		if ( postId ) {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/pages/${ postId }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	}
} );
