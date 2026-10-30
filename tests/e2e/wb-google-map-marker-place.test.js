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

/**
 * Serves a minimal Google Maps API in place of maps.googleapis.com, so the
 * spec needs no API key or network access. It defines what the location
 * field calls, then runs the callback named in the script URL.
 */
const stubGoogleMapsApi = async ( page ) => {
	await page.route( /^https:\/\/maps\.googleapis\.com\/maps\/api\/js/, ( route ) => {
		const callback = new URL( route.request().url() ).searchParams.get( 'callback' );

		route.fulfill( {
			contentType: 'application/javascript',
			body: `
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
							done( [], 'ZERO_RESULTS' );
						};
					},
					GeocoderStatus: {
						OK: 'OK',
						OVER_QUERY_LIMIT: 'OVER_QUERY_LIMIT',
					},
				};
				window[ ${ JSON.stringify( callback ) } ]();
			`,
		} );
	} );
};

/**
 * Saves the page as a draft and waits for the editor to load again.
 */
const saveDraft = async ( page ) => {
	const navigated = page.waitForEvent( 'framenavigated', ( frame ) => frame === page.mainFrame() );
	await page.locator( '#save-post' ).click();
	await navigated;
	await page.waitForLoadState();
};

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
