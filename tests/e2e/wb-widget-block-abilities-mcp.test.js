/**
 * The sowb/widget-get and sowb/widget-update abilities through the WordPress
 * MCP adapter's default server (HTTP transport).
 *
 * The adapter exposes an ability only when its meta marks it public to MCP,
 * so this file fails if either ability loses its `mcp.public` flag.
 *
 * The adapter is not on wordpress.org. When the site does not have it, this
 * file downloads the pinned release, installs it through the plugin upload
 * screen, and removes it again afterwards. The sowb-e2e-probe fixture plugin
 * is installed and removed the same way. An install or activation failure
 * fails the file; it never skips.
 */
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const {
	expect,
	test,
} = require( '@playwright/test' );

const {
	setupRequestUtils,
	soGoTo,
} = require( 'siteorigin-tests-common/playwright/common' );

const {
	FULL_WIDTH_PROBE,
	PROBE_SHORTCODE,
	blockNameForClass,
	createUserSession,
	deleteUsers,
	fixture,
	installFixture,
	login,
	loginPage,
	removeFixture,
	siteUrl,
	storedWidgetData,
	widgetBlock,
} = require( './helpers/widget-block-abilities' );

test.describe.configure( { mode: 'serial' } );

const ADAPTER_ZIP = 'https://github.com/WordPress/mcp-adapter/releases/download/v0.6.1/mcp-adapter.zip';
const ADAPTER_PLUGIN = 'mcp-adapter/mcp-adapter';
const MCP_ENDPOINT = 'wp-json/mcp/mcp-adapter-default-server';
const EDITOR = 'SiteOrigin_Widget_Editor_Widget';

/**
 * Open an MCP session on the default server as the given user.
 */
const openMcpSession = async ( session ) => {
	let sessionId = null;
	let nextId = 1;

	const send = async ( method, message ) => {
		const headers = {
			'Content-Type': 'application/json',
			Accept: 'application/json, text/event-stream',
			'X-WP-Nonce': session.nonce,
		};

		if ( sessionId ) {
			headers[ 'Mcp-Session-Id' ] = sessionId;
		}

		return session.context.fetch( siteUrl( MCP_ENDPOINT ), {
			method,
			headers,
			data: message,
		} );
	};

	const initialize = await send( 'POST', {
		jsonrpc: '2.0',
		id: nextId++,
		method: 'initialize',
		params: {
			protocolVersion: '2025-11-25',
			capabilities: {},
			clientInfo: {
				name: 'sowb-e2e',
				version: '1.0.0',
			},
		},
	} );
	expect( initialize.status() ).toBe( 200 );
	sessionId = initialize.headers()[ 'mcp-session-id' ];
	expect( sessionId ).toBeTruthy();

	const initialized = await send( 'POST', {
		jsonrpc: '2.0',
		method: 'notifications/initialized',
	} );
	expect( initialized.status() ).toBeLessThan( 300 );

	return {
		/**
		 * Call a tool. Resolves to the raw JSON-RPC response body.
		 */
		callTool: async ( name, args ) => {
			const response = await send( 'POST', {
				jsonrpc: '2.0',
				id: nextId++,
				method: 'tools/call',
				params: {
					name,
					arguments: args,
				},
			} );

			return response.json();
		},
		close: async () => {
			await send( 'DELETE' );
		},
	};
};

/**
 * The tool payload from a tools/call result: the structured content when
 * present, else the JSON text content. For the execute tool this is the
 * adapter's wrapper { success, data?, error? }, not the ability output.
 */
const toolPayload = ( rpc ) => {
	expect( rpc.error, JSON.stringify( rpc.error ) ).toBeUndefined();
	expect( rpc.result.isError, JSON.stringify( rpc.result ) ).toBeFalsy();

	if ( rpc.result.structuredContent ) {
		return rpc.result.structuredContent;
	}

	return JSON.parse( rpc.result.content[ 0 ].text );
};

const execute = ( mcp, abilityName, parameters ) => mcp.callTool( 'mcp-adapter-execute-ability', {
	ability_name: abilityName,
	parameters,
} );

/**
 * Assert that a tools/call failed and carried no ability data. The v0.6.1
 * adapter turns a failed execute into a tool error (isError with the message
 * as text); a JSON-RPC error and the { success: false } wrapper are accepted
 * too.
 *
 * @return {string} The raw response, for message checks.
 */
const expectToolFailure = ( rpc ) => {
	const raw = JSON.stringify( rpc );
	let failed = !! rpc.error || rpc.result?.isError === true;

	if ( ! failed ) {
		const payload = rpc.result.structuredContent || JSON.parse( rpc.result.content[ 0 ].text );
		expect( payload.success, raw ).not.toBe( true );
		expect( payload.data, raw ).toBeUndefined();
		failed = payload.success === false;
	} else if ( rpc.result ) {
		expect( rpc.result.structuredContent ?? null, raw ).toBeNull();
	}

	expect( failed, raw ).toBe( true );

	return raw;
};

let requestUtils;
let fx;
let postId;
const sessions = {};
const createdUsers = [];
const adapterState = {
	installedByTest: false,
	priorStatus: null,
};

const findAdapter = async () => {
	const plugins = await requestUtils.rest( {
		method: 'GET',
		path: '/wp/v2/plugins',
	} );

	return plugins.find( ( plugin ) => plugin.plugin === ADAPTER_PLUGIN ) || null;
};

/**
 * Download the pinned adapter release and install it through the real
 * plugin upload screen.
 */
const installAdapter = async ( browser ) => {
	const download = await fetch( ADAPTER_ZIP );
	expect( download.ok, `download ${ ADAPTER_ZIP }` ).toBe( true );

	const zipPath = path.join( fs.mkdtempSync( path.join( os.tmpdir(), 'sowb-mcp-' ) ), 'mcp-adapter.zip' );
	fs.writeFileSync( zipPath, Buffer.from( await download.arrayBuffer() ) );

	const page = await browser.newPage();

	try {
		await loginPage( page );
		await soGoTo( page, 'wp-admin/plugin-install.php?tab=upload' );
		await page.setInputFiles( '#pluginzip', zipPath );
		await Promise.all( [
			page.waitForNavigation(),
			page.click( '#install-plugin-submit' ),
		] );
		await expect( page.locator( '#wpbody-content' ) ).toContainText( 'Plugin installed successfully' );
	} finally {
		await page.close();
		fs.rmSync( path.dirname( zipPath ), { recursive: true, force: true } );
	}
};

test.beforeAll( async ( { browser } ) => {
	test.setTimeout( 240_000 );
	requestUtils = await setupRequestUtils();

	let adapter = await findAdapter();

	if ( ! adapter ) {
		await installAdapter( browser );
		adapterState.installedByTest = true;
		adapter = await findAdapter();
	}

	expect( adapter, 'the MCP adapter must be installed' ).not.toBeNull();
	adapterState.priorStatus = adapterState.installedByTest ? 'inactive' : adapter.status;

	if ( adapter.status !== 'active' ) {
		const activated = await requestUtils.rest( {
			method: 'PUT',
			path: `/wp/v2/plugins/${ ADAPTER_PLUGIN }`,
			data: { status: 'active' },
		} );
		expect( activated.status ).toBe( 'active' );
	}

	await installFixture( browser, requestUtils );

	sessions.admin = await login( process.env.WP_USERNAME, process.env.WP_PASSWORD );
	sessions.contributor = await createUserSession( requestUtils, 'contributor', createdUsers );
	sessions.subscriber = await createUserSession( requestUtils, 'subscriber', createdUsers );
	fx = fixture( sessions.admin );

	postId = await fx.seed( widgetBlock( blockNameForClass( EDITOR ), {
		widgetClass: EDITOR,
		widgetData: { title: 'MCP seed', text: '<p>Seed</p>', text_selected_editor: 'html', autop: true },
	} ), { author: sessions.contributor.id } );
} );

test.afterAll( async () => {
	for ( const session of Object.values( sessions ) ) {
		await session.context.dispose();
	}

	if ( ! requestUtils ) {
		return;
	}

	await deleteUsers( requestUtils, createdUsers );
	await removeFixture( requestUtils );

	if ( adapterState.priorStatus && adapterState.priorStatus !== 'active' ) {
		await requestUtils.rest( {
			method: 'PUT',
			path: `/wp/v2/plugins/${ ADAPTER_PLUGIN }`,
			data: { status: adapterState.priorStatus },
		} ).catch( () => {} );
	}

	if ( adapterState.installedByTest ) {
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/plugins/${ ADAPTER_PLUGIN }`,
		} ).catch( () => {} );
	}
} );

test( 'a contributor discovers, reads and updates their own draft over MCP', async () => {
	const mcp = await openMcpSession( sessions.contributor );

	try {
		const discovered = toolPayload( await mcp.callTool( 'mcp-adapter-discover-abilities', {} ) );
		const names = discovered.abilities.map( ( ability ) => ability.name );
		expect( names ).toContain( 'sowb/widget-get' );
		expect( names ).toContain( 'sowb/widget-update' );

		const get = toolPayload( await execute( mcp, 'sowb/widget-get', { post_id: postId } ) );
		expect( get.success ).toBe( true );
		expect( get.data.widgets ).toContainEqual( expect.objectContaining( {
			widget_index: 0,
			widget_class: EDITOR,
			block_name: blockNameForClass( EDITOR ),
		} ) );

		const update = toolPayload( await execute( mcp, 'sowb/widget-update', {
			post_id: postId,
			widget_data: { title: '<script>x()</script>[sowb_e2e_probe]' },
		} ) );
		expect( update.success, JSON.stringify( update ) ).toBe( true );
		expect( update.data.status, JSON.stringify( update.data ) ).toBe( 'ok' );

		const stored = await storedWidgetData( fx, postId );
		expect( update.data.widget_data ).toEqual( stored );
		expect( stored.title.toLowerCase() ).not.toContain( '<script' );
		expect( stored.title ).toContain( FULL_WIDTH_PROBE );
		expect( stored.title ).not.toMatch( PROBE_SHORTCODE );
	} finally {
		await mcp.close();
	}
} );

test( 'a subscriber cannot update a widget over MCP', async () => {
	const before = await fx.stored( postId );
	const mcp = await openMcpSession( sessions.subscriber );

	try {
		const raw = expectToolFailure( await execute( mcp, 'sowb/widget-update', {
			post_id: postId,
			widget_data: { title: 'Subscriber' },
		} ) );

		// Denied by the ability's own edit_post check.
		expect( raw ).toContain( 'not allowed to update the widgets of this post' );
		expect( raw ).not.toContain( '"widget_data"' );
	} finally {
		await mcp.close();
	}

	const after = await fx.stored( postId );
	expect( after.content ).toBe( before.content );
} );
