/**
 * Deleting generated stylesheets tells cache plugins to clear.
 *
 * A widget's stylesheet is named from its settings, so one file can be linked
 * from many pages, and nothing records which. A page cache that keeps a page
 * after its stylesheet is deleted serves the widget unstyled. So whenever the
 * weekly cleanup deletes a stylesheet, it fires
 * siteorigin_widgets_stylesheet_cleared once, which the cache compatibility
 * code answers by clearing every supported cache.
 *
 * The cleanup runs on admin_init, at most once in seven days. Each test ages
 * its own entries in the stylesheet folder, lets the cleanup run by loading an
 * admin page, and reads what was deleted and which actions fired.
 *
 * The last group covers the requests that run a widget's settings through its
 * save routine and save nothing: a widget block's form and preview, the widget
 * preview action, and Page Builder opening a page. None deletes a stylesheet,
 * while saving a post still does.
 *
 * Uses the sowb-e2e-probe fixture plugin, installed and removed by this file.
 */
const {
	expect,
	test,
} = require( '@playwright/test' );

const {
	setupRequestUtils,
	soGoTo,
} = require( 'siteorigin-tests-common/playwright/common' );

const {
	call,
	installFixture,
	login,
	loginPage,
	removeFixture,
	siteUrl,
} = require( './helpers/widget-block-abilities' );

test.describe.configure( { mode: 'serial' } );

const ROUTE = '/sowb-e2e/v1/stylesheets';
const OLD = 8;
const NEW = 1;

let requestUtils;
let admin;
let page;
let written = [];

const uniqueName = ( label, extension ) => `sowb-e2e-${ label }-${ Date.now() }-${ Math.floor( Math.random() * 1e6 ) }${ extension }`;

/**
 * Write entries to the stylesheet folder, forget earlier events and let the
 * cleanup run on the next admin request.
 */
const arrange = async ( entries ) => {
	written.push( ...entries.map( ( entry ) => entry.name ) );

	await requestUtils.rest( {
		method: 'POST',
		path: ROUTE,
		data: { entries },
	} );
};

const folder = () => requestUtils.rest( {
	method: 'GET',
	path: ROUTE,
} );

const loadAdminPage = () => soGoTo( page, 'wp-admin/index.php' );

const eventsNamed = ( events, event ) => events.filter( ( entry ) => entry.event === event );

test.beforeAll( async ( { browser } ) => {
	test.setTimeout( 300_000 );
	requestUtils = await setupRequestUtils();

	await installFixture( browser, requestUtils );

	admin = await login( process.env.WP_USERNAME, process.env.WP_PASSWORD );

	page = await browser.newPage();
	await loginPage( page );
} );

test.afterEach( async () => {
	if ( written.length ) {
		await requestUtils.rest( {
			method: 'DELETE',
			path: ROUTE,
			data: { names: written },
		} ).catch( () => {} );
		written = [];
	}
} );

test.afterAll( async () => {
	test.setTimeout( 300_000 );

	if ( page ) {
		await page.close();
	}

	if ( requestUtils ) {
		await removeFixture( requestUtils );
	}
} );

test( 'an old stylesheet is deleted and announced, with one full clear for the run', async () => {
	const first = uniqueName( 'old-a', '.css' );
	const second = uniqueName( 'old-b', '.css' );
	const recent = uniqueName( 'recent', '.css' );

	await arrange( [
		{ name: first, days: OLD },
		{ name: second, days: OLD },
		{ name: recent, days: NEW },
	] );
	await loadAdminPage();

	const { names, events } = await folder();

	expect( names ).not.toContain( first );
	expect( names ).not.toContain( second );
	expect( names ).toContain( recent );

	const deleted = eventsNamed( events, 'deleted' ).map( ( entry ) => entry.name );
	expect( deleted ).toEqual( expect.arrayContaining( [ first, second ] ) );
	expect( deleted ).not.toContain( recent );
	expect( eventsNamed( events, 'cleared' ) ).toHaveLength( 1 );
} );

test( 'the cleanup does nothing more for the rest of the week', async () => {
	const first = uniqueName( 'old', '.css' );

	await arrange( [ { name: first, days: OLD } ] );
	await loadAdminPage();
	expect( eventsNamed( ( await folder() ).events, 'cleared' ) ).toHaveLength( 1 );

	// A second old stylesheet arrives after the cleanup has run. The cleanup's
	// own timer is left as it stands.
	const later = uniqueName( 'later', '.css' );
	written.push( later );
	await requestUtils.rest( {
		method: 'POST',
		path: ROUTE,
		data: { entries: [ { name: later, days: OLD } ], keep_timer: true },
	} );
	await loadAdminPage();

	const { names, events } = await folder();

	expect( names ).toContain( later );
	expect( events ).toEqual( [] );
} );

test( 'with no stylesheet old enough, nothing is deleted and nothing is cleared', async () => {
	const recent = uniqueName( 'recent', '.css' );

	await arrange( [ { name: recent, days: NEW } ] );
	await loadAdminPage();

	const { names, events } = await folder();

	expect( names ).toContain( recent );
	expect( events ).toEqual( [] );
} );

test( 'an old file that is not a stylesheet is removed without a full clear', async () => {
	const other = uniqueName( 'other', '.txt' );

	await arrange( [ { name: other, days: OLD } ] );
	await loadAdminPage();

	const { names, events } = await folder();

	expect( names ).not.toContain( other );
	expect( eventsNamed( events, 'cleared' ) ).toEqual( [] );
} );

test( 'a stylesheet that could not be deleted causes no full clear', async () => {
	// A folder holding a file: the cleanup's delete fails on it.
	const stuck = uniqueName( 'stuck', '.css' );

	await arrange( [ { name: stuck, days: OLD, folder: true } ] );
	await loadAdminPage();

	const { names, events } = await folder();

	expect( names ).toContain( stuck );
	expect( eventsNamed( events, 'cleared' ) ).toEqual( [] );
} );

test( 'an old, empty folder named like a stylesheet is removed without a full clear', async () => {
	const emptyFolder = uniqueName( 'folder', '.css' );

	await arrange( [ { name: emptyFolder, days: OLD, folder: true, empty: true } ] );
	await loadAdminPage();

	const { names, events } = await folder();

	expect( names ).not.toContain( emptyFolder );
	expect( eventsNamed( events, 'cleared' ) ).toEqual( [] );
} );

test.describe( 'Cleaning a widget block\'s settings keeps its stylesheet', () => {
	const widget = {
		widgetClass: 'SiteOrigin_Widget_Editor_Widget',
		widgetData: { title: 'Stylesheet cleanup', text: '<p>Stylesheet cleanup</p>' },
	};

	test( 'loading a widget block\'s form deletes no stylesheet', async () => {
		await arrange( [] );

		const form = await requestUtils.rest( {
			method: 'POST',
			path: '/sowb/v1/widgets/forms',
			data: widget,
		} );

		expect( JSON.stringify( form ) ).toContain( 'siteorigin-widget-form' );
		expect( eventsNamed( ( await folder() ).events, 'deleted' ) ).toEqual( [] );
	} );

	test( 'loading a widget block\'s preview deletes no stylesheet', async () => {
		await arrange( [] );

		const preview = await requestUtils.rest( {
			method: 'POST',
			path: '/sowb/v1/widgets/previews',
			data: widget,
		} );

		expect( JSON.stringify( preview ) ).toContain( 'Stylesheet cleanup' );
		expect( eventsNamed( ( await folder() ).events, 'deleted' ) ).toEqual( [] );
	} );

	test( 'saving a post with a widget block still deletes the block\'s stylesheet', async () => {
		await arrange( [] );

		const post = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				title: 'Stylesheet cleanup',
				status: 'draft',
				content: `<!-- wp:sowb/siteorigin-widget-editor-widget ${ JSON.stringify( widget ) } /-->`,
			},
		} );

		try {
			expect( eventsNamed( ( await folder() ).events, 'deleted' ).length ).toBeGreaterThan( 0 );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	} );

	test( 'the widget preview action deletes no stylesheet', async () => {
		await arrange( [] );

		const { status, body } = await call( admin, 'GET', 'wp-json/sowb-e2e/v1/widgets-nonce' );
		expect( status ).toBe( 200 );

		const response = await admin.context.fetch( siteUrl( 'wp-admin/admin-ajax.php' ), {
			method: 'POST',
			form: {
				action: 'so_widgets_preview',
				_widgets_nonce: body.nonce,
				class: widget.widgetClass,
				data: JSON.stringify( widget.widgetData ),
			},
		} );

		expect( response.status() ).toBe( 200 );
		expect( await response.text() ).toContain( 'Stylesheet cleanup' );
		expect( eventsNamed( ( await folder() ).events, 'deleted' ) ).toEqual( [] );
	} );

	test( 'opening a Page Builder page in the editor deletes no stylesheet', async () => {
		const seeded = await call( admin, 'POST', 'wp-json/sowb-e2e/v1/panels-page', { marker: `Stylesheet cleanup ${ Date.now() }`, nested: 0 } );
		expect( seeded.status ).toBe( 200 );

		try {
			await arrange( [] );

			// Page Builder asks for the layout's content as the editor opens,
			// which runs every widget in it through update().
			const built = page.waitForResponse(
				( response ) => response.url().includes( 'admin-ajax.php' ) &&
					( response.request().postData() || '' ).includes( 'so_panels_builder_content' )
			);
			await soGoTo( page, `wp-admin/post.php?post=${ seeded.body.id }&action=edit` );
			expect( ( await built ).status() ).toBe( 200 );

			expect( eventsNamed( ( await folder() ).events, 'deleted' ) ).toEqual( [] );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/pages/${ seeded.body.id }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	} );
} );
