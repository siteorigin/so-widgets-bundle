/**
 * Canvas asset gate: the clone walk waits for the canvas dependencies and
 * never clones into a canvas that cannot evaluate what it is given.
 *
 * Both tests seed the post with a saved widget, then reload while
 * intercepting the canvas iframe's load-scripts.php request - the
 * concatenated bundle carrying jquery-core. One holds the bundle and
 * requires a clean recovery; the other empties it and requires the gate's
 * diagnostics with nothing cloned.
 *
 * Runs locally via `npm run tests` (#2329 tracks CI; #2361 tracks further
 * coverage).
 */
const {
	expect,
	test
} = require( '@playwright/test' );

const common = require( 'siteorigin-tests-common/playwright/common' );

const {
	addBlock,
	doLogin,
	setupAdminE2E,
	setupRequestUtils,
} = common;

// Not serial: each test builds its own post and route, and the dead-bundle
// test must still report if the slow-bundle test fails.

const CANVAS = 'iframe[name="editor-canvas"]';

const TERMINAL_WARN = 'SiteOrigin Widgets: editor canvas finished loading without';
const STALL_WARN = 'SiteOrigin Widgets: still waiting for the editor canvas';

// The canvas body ships empty; every script with an id in it was appended by
// the clone walk. Counting them is the walk detector - a named id would tie
// the test to whichever handles this WordPress build concatenates.

/**
 * Whether a load-scripts.php request is the one delivering jquery-core.
 *
 * WordPress indexes the query keys (`load[chunk_0]=jquery-core,...`), so the
 * values are collected from every `load`-prefixed param and split on commas.
 */
const isJqueryBundleRequest = ( requestUrl ) => {
	const url = new URL( requestUrl );
	const handles = [];

	url.searchParams.forEach( ( value, key ) => {
		if ( key === 'load' || key.indexOf( 'load[' ) === 0 ) {
			handles.push( ...value.split( ',' ) );
		}
	} );

	return handles.includes( 'jquery-core' );
};

/**
 * Routes the canvas's jquery bundle through the given handler, leaving every
 * other request untouched. Resolves `captured` when the request arrives.
 */
const routeCanvasJqueryBundle = async ( page, handler ) => {
	const state = {
		captures: 0,
	};

	state.captured = new Promise( ( resolve ) => {
		state.onCapture = resolve;
	} );

	await page.route( '**/load-scripts.php*', async ( route ) => {
		const request = route.request();
		const frame = request.frame();

		if (
			frame === page.mainFrame() ||
			frame.name() !== 'editor-canvas' ||
			! isJqueryBundleRequest( request.url() )
		) {
			return route.fallback();
		}

		state.captures++;
		state.onCapture();

		return handler( route );
	} );

	return state;
};

const collectGateWarns = ( page ) => {
	const warns = {
		terminal: [],
		stall: [],
		referenceErrors: [],
	};

	page.on( 'console', ( message ) => {
		const text = message.text();

		if ( text.indexOf( TERMINAL_WARN ) !== -1 ) {
			warns.terminal.push( text );
		}

		if ( text.indexOf( STALL_WARN ) !== -1 ) {
			warns.stall.push( text );
		}
	} );

	page.on( 'pageerror', ( error ) => {
		if ( error.message.indexOf( 'ReferenceError' ) !== -1 ) {
			warns.referenceErrors.push( error.message );
		}
	} );

	return warns;
};

const readCanvasState = ( page ) => page.evaluate( ( args ) => {
	const frame = document.querySelector( args.canvas );

	if ( ! frame ) {
		return { frame: false };
	}

	let out = {
		frame: true,
		timerLive: !! frame.sowbCanvasCloneWaitTimer,
		waitState: !! frame.sowbCanvasCloneWait,
	};

	try {
		const doc = frame.contentDocument;
		const win = frame.contentWindow;

		out.jQuery = !! win.jQuery;
		out.readyState = doc.readyState;
		out.bodyScripts = doc.body ? doc.body.querySelectorAll( 'script[id]' ).length : 0;
		out.walkRan = out.bodyScripts > 0;
		out.retryScript = !! doc.getElementById( 'sowb-editor-js-retry' );
	} catch ( e ) {
		out.err = e.name;
	}

	return out;
}, { canvas: CANVAS } );

test.beforeEach( async ( { page } ) => {
	await doLogin( page );
} );

test( 'the walk waits out a slow canvas bundle and the builder still works', async ( { page } ) => {
	const warns = collectGateWarns( page );

	let releaseBundle;
	const held = new Promise( ( resolve ) => {
		releaseBundle = resolve;
	} );

	const requestUtils = await setupRequestUtils();
	const post = await requestUtils.createPost( {
		status: 'draft',
		title: 'Canvas gate slow bundle',
		content: '',
	} );
	const admin = await setupAdminE2E( page );

	// First load, unrouted and healthy: put a saved widget into the post, so
	// the reload below matches the reported shape - existing widget content
	// whose setup paths ask for the canvas while its bundle is still loading.
	await admin.editPost( post.id );
	await page.locator( CANVAS ).waitFor( { state: 'attached', timeout: 20000 } );
	await admin.editor.canvas.locator( 'body' ).waitFor( { state: 'visible', timeout: 20000 } );
	await addBlock( admin, 'sowb/siteorigin-widget-features-widget' );
	await admin.editor.saveDraft();

	const route = await routeCanvasJqueryBundle( page, async ( routeHandle ) => {
		// Held for seven seconds, then released.
		await held;

		return routeHandle.continue();
	} );

	const opening = admin.editPost( post.id ).catch( () => null );

	await route.captured;

	// Mid hold, with saved widget content present, the gate is being asked
	// for the canvas while its bundle is still in flight. Nothing may be
	// cloned into it through either door and no terminal diagnostic may fire.
	await page.waitForTimeout( 7000 );

	const midStall = await readCanvasState( page );

	expect( midStall.frame ).toBe( true );
	// Still loading proves the held bundle was the editor canvas's own - a
	// held preview-iframe bundle would leave the canvas completing normally.
	expect( midStall.readyState ).not.toBe( 'complete' );
	expect( midStall.walkRan ).toBe( false );
	expect( midStall.retryScript ).toBe( false );
	expect( warns.terminal.length ).toBe( 0 );

	releaseBundle();
	await opening;
	await page.locator( CANVAS ).waitFor( { state: 'attached', timeout: 20000 } );
	await admin.editor.canvas.locator( 'body' ).waitFor( { state: 'visible', timeout: 20000 } );

	await expect.poll( async () => ( await readCanvasState( page ) ).walkRan, {
		timeout: 20000,
	} ).toBe( true );

	// Open Builder binds through soPanelsSetupBuilderWidget, so the canvas
	// registration is what decides whether the button does anything. The full
	// click path is covered by the form-field suite.
	await expect.poll( () => page.evaluate( ( canvas ) => {
		const win = document.querySelector( canvas ).contentWindow;

		return !! ( win.jQuery && win.jQuery.fn && win.jQuery.fn.soPanelsSetupBuilderWidget );
	}, CANVAS ), { timeout: 20000 } ).toBe( true );

	expect( warns.terminal.length ).toBe( 0 );
	// One stall diagnostic is allowed for a hold this long; more means the
	// per-document latch is broken.
	expect( warns.stall.length ).toBeLessThanOrEqual( 1 );
	expect( warns.referenceErrors.length ).toBe( 0 );
	expect( route.captures ).toBeGreaterThan( 0 );
} );

test( 'an emptied canvas bundle is diagnosed once and never cloned into', async ( { page } ) => {
	const warns = collectGateWarns( page );

	const requestUtils = await setupRequestUtils();
	const post = await requestUtils.createPost( {
		status: 'draft',
		title: 'Canvas gate empty bundle',
		content: '',
	} );
	const admin = await setupAdminE2E( page );

	// Same seeding as above: the reload must carry saved widget content so
	// the gate is exercised without any dependency on editor interaction.
	await admin.editPost( post.id );
	await page.locator( CANVAS ).waitFor( { state: 'attached', timeout: 20000 } );
	await admin.editor.canvas.locator( 'body' ).waitFor( { state: 'visible', timeout: 20000 } );
	await addBlock( admin, 'sowb/siteorigin-widget-features-widget' );
	await admin.editor.saveDraft();

	// The bundle request succeeds but delivers nothing, so the document
	// completes with the dependencies genuinely absent.
	const route = await routeCanvasJqueryBundle( page, ( routeHandle ) => {
		return routeHandle.fulfill( {
			status: 200,
			contentType: 'application/javascript',
			body: '/* emptied by the canvas asset gate test */',
		} );
	} );

	const opening = admin.editPost( post.id ).catch( () => null );

	await route.captured;
	await opening;

	// One terminal warn naming the handle. The window extends past the stall
	// threshold, so the stall diagnostic must never appear alongside it.
	await expect.poll( () => warns.terminal.length, { timeout: 20000 } ).toBe( 1 );
	expect( warns.terminal[ 0 ] ).toContain( 'jquery-core-js' );

	await page.waitForTimeout( 6000 );

	expect( warns.terminal.length ).toBe( 1 );
	expect( warns.stall.length ).toBe( 0 );

	// Nothing entered the dependency-less canvas through either door.
	const broken = await readCanvasState( page );

	expect( broken.walkRan ).toBe( false );
	expect( broken.retryScript ).toBe( false );
	expect( route.captures ).toBeGreaterThan( 0 );
} );
