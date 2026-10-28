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
 * Saves a draft Page Builder page holding a Google Maps widget, reloads it
 * so the widget's form scripts are enqueued, and opens the widget's form.
 */
const openGoogleMapsWidgetForm = async ( page ) => {
	await soGoTo( page, 'wp-admin/post-new.php?post_type=page' );

	// A new page opens in the classic editor; switch it to Page Builder.
	await page.locator( '#content-panels' ).click();

	const builder = page.locator( '#siteorigin-panels-metabox' );
	await expect( builder ).toBeVisible( { timeout: 20000 } );

	await builder.locator( '.so-builder-toolbar .so-widget-add' ).click();

	const addDialog = page.locator( '.so-panels-dialog-add-widget' );
	await addDialog.locator( '.so-sidebar-search' ).fill( 'Google Maps' );
	await addDialog.locator( '.widget-type', { hasText: 'SiteOrigin Google Maps' } ).click();

	// Page Builder opens the edit dialog for a new widget. Its form asks for
	// a save and reload first, as the page did not load the widget's scripts.
	const editDialog = page.locator( '.so-panels-dialog-edit-widget' );
	const doneButton = editDialog.getByRole( 'button', { name: 'Done', exact: true } );
	await expect( doneButton ).toBeVisible( { timeout: 20000 } );
	await doneButton.click();
	await expect( doneButton ).toBeHidden();

	await page.locator( '#title' ).fill( 'WB Google Maps marker place' );
	await page.locator( '#save-post' ).click();
	await page.waitForURL( /post\.php\?post=\d+&action=edit/, { timeout: 20000 } );

	const postId = Number( new URL( page.url() ).searchParams.get( 'post' ) );

	await expect( builder ).toBeVisible( { timeout: 20000 } );
	await builder.locator( '.so-widget .title h4' ).click();

	const form = editDialog.locator( '.siteorigin-widget-form-main-siteorigin-widget-googlemap-widget' );
	await expect( form ).toBeVisible( { timeout: 20000 } );

	// The Map center field initialising proves the stubbed API loaded.
	await expect( form.locator( '.siteorigin-widget-field-map_center' ) ).toHaveAttribute( 'data-initialized', 'true', { timeout: 10000 } );

	return {
		form,
		postId,
	};
};

test.beforeEach( async ( { page } ) => {
	await doLogin( page );
} );

test( 'Google Maps: a new marker\'s Place field initialises when expanded and saves the typed place', async ( { page } ) => {
	await stubGoogleMapsApi( page );

	const requestUtils = await setupRequestUtils();
	let postId = 0;

	try {
		const opened = await openGoogleMapsWidgetForm( page );
		postId = opened.postId;

		const markers = await openSection( 'markers', opened.form );

		const repeater = markers.locator( '.siteorigin-widget-field-marker_positions .siteorigin-widget-field-repeater' );
		const items = repeater.locator( '> .siteorigin-widget-field-repeater-items > .siteorigin-widget-field-repeater-item' );
		const before = await items.count();

		await repeater.locator( '> .siteorigin-widget-field-repeater-add' ).click();
		await expect( items ).toHaveCount( before + 1 );

		const item = items.nth( before );
		const itemForm = item.locator( '> .siteorigin-widget-field-repeater-item-form' );
		const place = item.locator( '.siteorigin-widget-field-type-location' );

		// A new item starts collapsed, so its Place field is not set up yet.
		await expect( itemForm ).toBeHidden();
		await expect( place ).not.toHaveAttribute( 'data-initialized', 'true' );

		await item.locator( '> .siteorigin-widget-field-repeater-item-top' ).click();
		await expect( itemForm ).toBeVisible();

		await expect( place ).toHaveAttribute( 'data-initialized', 'true', { timeout: 10000 } );

		const placeInput = place.locator( '.siteorigin-widget-location-input' );
		await placeInput.fill( 'Leeds Dock, Leeds' );
		await placeInput.press( 'Tab' );

		await expect( place.locator( '.location-field-data' ) ).toHaveValue( JSON.stringify( { name: 'Leeds Dock, Leeds' } ) );
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
