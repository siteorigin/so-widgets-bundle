/**
 * The sowb/widget-list and sowb/widget-describe abilities through the
 * WordPress MCP adapter's default server (HTTP transport).
 *
 * The adapter exposes an ability only when its meta marks it public to MCP,
 * so this file fails if the abilities lose their `mcp.public` flag.
 *
 * The adapter is not on wordpress.org. When the site does not have it, this
 * file downloads the pinned release, installs it through the plugin upload
 * screen, and removes it again afterwards. An install or activation failure
 * fails the file; it never skips.
 */
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const {
	expect,
	request,
	test
} = require( '@playwright/test' );

const common = require( 'siteorigin-tests-common/playwright/common' );

const {
	doLogin,
	setupRequestUtils,
	soGoTo,
} = common;

test.describe.configure( { mode: 'serial' } );

const ADAPTER_ZIP = 'https://github.com/WordPress/mcp-adapter/releases/download/v0.6.1/mcp-adapter.zip';
const ADAPTER_PLUGIN = 'mcp-adapter/mcp-adapter';
const MCP_ENDPOINT = 'wp-json/mcp/mcp-adapter-default-server';

// Join a path to WP_BASE_URL. Normalizing the base to end in a slash keeps
// this correct for a root install and a subdirectory install.
const siteUrl = ( relativePath ) => {
	const base = process.env.WP_BASE_URL.endsWith( '/' )
		? process.env.WP_BASE_URL
		: `${ process.env.WP_BASE_URL }/`;

	return new URL( relativePath, base ).toString();
};

/**
 * Log a user in with a cookie session of its own and fetch a REST nonce.
 * Cookie auth works on every environment; application passwords are
 * unavailable on a non-HTTPS site that is not a local environment, such as
 * Playground.
 *
 * @return {Promise<{context: import('@playwright/test').APIRequestContext, nonce: string}>}
 */
const login = async ( username, password ) => {
	const context = await request.newContext( {
		baseURL: siteUrl( '' ),
		ignoreHTTPSErrors: true,
	} );

	// The login form needs the test cookie set by a first visit.
	await context.get( 'wp-login.php' );
	const response = await context.post( 'wp-login.php', {
		form: {
			log: username,
			pwd: password,
			testcookie: '1',
			'wp-submit': 'Log In',
		},
		maxRedirects: 0,
	} );
	expect( response.status(), `login ${ username }` ).toBe( 302 );

	const nonceResponse = await context.get( 'wp-admin/admin-ajax.php?action=rest-nonce' );
	expect( nonceResponse.ok(), `nonce ${ username }` ).toBe( true );

	return {
		context,
		nonce: ( await nonceResponse.text() ).trim(),
	};
};

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
 * The error message of a failed tools/call. The v0.6.1 adapter turns the
 * execute tool's { success: false, error } wrapper into a tool error
 * (isError with the message as text); the wrapper form is accepted too. A
 * failure never carries ability data.
 */
const toolFailure = ( rpc ) => {
	expect( rpc.error, JSON.stringify( rpc.error ) ).toBeUndefined();

	if ( rpc.result.isError === true ) {
		expect( rpc.result.structuredContent ?? null ).toBeNull();

		return rpc.result.content[ 0 ].text;
	}

	const payload = toolPayload( rpc );
	expect( payload.success ).toBe( false );
	expect( payload.data ).toBeUndefined();

	return payload.error;
};

let requestUtils;
const sessions = {};
const createdUsers = [];
const adapterState = {
	installedByTest: false,
	priorStatus: null,
};

/**
 * Create a user with the given role and log it in.
 */
const createUserSession = async ( role ) => {
	const suffix = `${ Date.now() }-${ Math.floor( Math.random() * 1e6 ) }`;
	const username = `sowb-mcp-${ role }-${ suffix }`;
	const password = `sowb-mcp-${ suffix }!A1`;
	const user = await requestUtils.rest( {
		method: 'POST',
		path: '/wp/v2/users',
		params: {
			username,
			email: `${ username }@example.com`,
			password,
			roles: [ role ],
		},
	} );
	createdUsers.push( user );

	return login( username, password );
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
		await doLogin( page );
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
	test.setTimeout( 180_000 );
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

	sessions.contributor = await createUserSession( 'contributor' );
	sessions.subscriber = await createUserSession( 'subscriber' );
} );

test.afterAll( async () => {
	for ( const session of Object.values( sessions ) ) {
		await session.context.dispose();
	}

	if ( ! requestUtils ) {
		return;
	}

	for ( const user of createdUsers ) {
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/users/${ user.id }`,
			params: { force: true, reassign: 1 },
		} ).catch( () => {} );
	}

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

test( 'a contributor discovers and executes both abilities over MCP', async () => {
	const mcp = await openMcpSession( sessions.contributor );

	try {
		const discovered = toolPayload( await mcp.callTool( 'mcp-adapter-discover-abilities', {} ) );
		const names = discovered.abilities.map( ( ability ) => ability.name );
		expect( names ).toContain( 'sowb/widget-list' );
		expect( names ).toContain( 'sowb/widget-describe' );

		const list = toolPayload( await execute( mcp, 'sowb/widget-list', {} ) );
		expect( list.success ).toBe( true );
		expect( list.data.widgets ).toContainEqual( expect.objectContaining( {
			id: 'hero',
			class: 'SiteOrigin_Widget_Hero_Widget',
		} ) );

		const describe = toolPayload( await execute( mcp, 'sowb/widget-describe', { widget: 'hero' } ) );
		expect( describe.success ).toBe( true );
		expect( describe.data.schema.properties.frames ).toBeDefined();

		// The describe callback's WP_Error reaches the client as a failure
		// carrying its message.
		const unknown = toolFailure( await execute( mcp, 'sowb/widget-describe', { widget: 'Not_A_Widget' } ) );
		expect( typeof unknown ).toBe( 'string' );
		expect( unknown ).toContain( 'sowb/widget-list' );
	} finally {
		await mcp.close();
	}
} );

test( 'a subscriber cannot describe a widget over MCP', async () => {
	const mcp = await openMcpSession( sessions.subscriber );

	try {
		const rpc = await execute( mcp, 'sowb/widget-describe', { widget: 'hero' } );

		let failed = !! rpc.error || rpc.result?.isError === true;

		if ( ! failed ) {
			const payload = rpc.result.structuredContent || JSON.parse( rpc.result.content[ 0 ].text );
			expect( payload.success ).not.toBe( true );
			failed = payload.success === false;
		}

		expect( failed, JSON.stringify( rpc ) ).toBe( true );
		// Denied by the ability's own edit_posts check.
		expect( JSON.stringify( rpc ) ).toContain( 'not allowed to read SiteOrigin widgets' );
		expect( JSON.stringify( rpc ) ).not.toContain( '"schema"' );
		expect( JSON.stringify( rpc ) ).not.toContain( '"frames"' );
	} finally {
		await mcp.close();
	}
} );
