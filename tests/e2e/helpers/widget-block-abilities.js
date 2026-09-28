/**
 * Shared helpers for the widget block ability end-to-end tests.
 *
 * - Cookie + REST nonce sessions per role (application passwords are not
 *   available on Playground, which is plain HTTP and not a local environment).
 * - The sowb-e2e-probe fixture plugin: zipped at test time, installed through
 *   the real plugin upload screen, and removed afterwards.
 * - Block content builders and a walk that mirrors the plugin's own, so a
 *   test can find a widget block in stored content by widget_index.
 */
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );
const zlib = require( 'zlib' );

const {
	expect,
	request,
} = require( '@playwright/test' );

const {
	doLogin,
	soGoTo,
} = require( 'siteorigin-tests-common/playwright/common' );

const FIXTURE_SLUG = 'sowb-e2e-probe';
const FIXTURE_PLUGIN = `${ FIXTURE_SLUG }/${ FIXTURE_SLUG }`;
const FIXTURE_DIR = path.resolve( __dirname, '..', 'fixtures', FIXTURE_SLUG );
const WIDGETS_ADMIN = 'wp-admin/plugins.php?page=so-widgets-plugins';

const PROBE_SHORTCODE = /\[\s*\/?\s*sowb_e2e_probe/;
const ENCODED_BRACKET = /&(?:amp;)*(?:#0*91;?|#x0*5b;?|lsqb;|lbrack;)/i;
const PROBE_RAN = 'SOWB-E2E-PROBE-RAN';
const FULL_WIDTH_PROBE = '［sowb_e2e_probe］';

/**
 * Join a path to WP_BASE_URL. Normalizing the base to end in a slash keeps
 * this correct for a root install and a subdirectory install.
 */
const siteUrl = ( relativePath ) => {
	const base = process.env.WP_BASE_URL.endsWith( '/' )
		? process.env.WP_BASE_URL
		: `${ process.env.WP_BASE_URL }/`;

	return new URL( relativePath, base ).toString();
};

/**
 * Log a user in with a cookie session of its own and fetch a REST nonce.
 *
 * @return {Promise<{context: import('@playwright/test').APIRequestContext, nonce: string, username: string}>}
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
		username,
	};
};

/**
 * A request context with no cookies, for visitor requests.
 */
const newVisitor = () => request.newContext( { ignoreHTTPSErrors: true } );

/**
 * Send a REST request as a logged-in session, or as a visitor context.
 *
 * @return {Promise<{status: number, body: any}>}
 */
const call = async ( session, method, relativePath, body ) => {
	const context = session.context || session;
	const headers = {};

	if ( session.nonce ) {
		headers[ 'X-WP-Nonce' ] = session.nonce;
	}

	const response = await context.fetch( siteUrl( relativePath ), {
		method,
		headers,
		data: body,
	} );

	const text = await response.text();
	let json = null;

	try {
		json = JSON.parse( text );
	} catch ( e ) {
		json = text;
	}

	return {
		status: response.status(),
		body: json,
	};
};

/**
 * Create a user with the given role and log it in. Created users are pushed
 * to `created` so the caller can delete them.
 */
const createUserSession = async ( requestUtils, role, created ) => {
	const suffix = `${ Date.now() }-${ Math.floor( Math.random() * 1e6 ) }`;
	const username = `sowb-wb-${ role }-${ suffix }`;
	const password = `sowb-wb-${ suffix }!A1`;
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
	created.push( user );

	const session = await login( username, password );
	session.id = user.id;

	return session;
};

const deleteUsers = async ( requestUtils, users ) => {
	for ( const user of users ) {
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/users/${ user.id }`,
			params: { force: true, reassign: 1 },
		} ).catch( () => {} );
	}
};

/**
 * Build a store-only (uncompressed) zip archive.
 *
 * @param {{name: string, data: Buffer}[]} entries Archive entries.
 *
 * @return {Buffer}
 */
const makeZip = ( entries ) => {
	const locals = [];
	const centrals = [];
	let offset = 0;

	for ( const entry of entries ) {
		const name = Buffer.from( entry.name, 'utf8' );
		const crc = zlib.crc32( entry.data ) >>> 0;
		const size = entry.data.length;

		const local = Buffer.alloc( 30 );
		local.writeUInt32LE( 0x04034b50, 0 );
		local.writeUInt16LE( 20, 4 ); // Version needed.
		local.writeUInt16LE( 0x0800, 6 ); // UTF-8 names.
		local.writeUInt16LE( 0, 8 ); // Stored.
		local.writeUInt16LE( 0, 10 ); // Time.
		local.writeUInt16LE( 0x21, 12 ); // Date: 1980-01-01.
		local.writeUInt32LE( crc, 14 );
		local.writeUInt32LE( size, 18 );
		local.writeUInt32LE( size, 22 );
		local.writeUInt16LE( name.length, 26 );
		local.writeUInt16LE( 0, 28 );
		locals.push( local, name, entry.data );

		const central = Buffer.alloc( 46 );
		central.writeUInt32LE( 0x02014b50, 0 );
		central.writeUInt16LE( 20, 4 ); // Version made by.
		central.writeUInt16LE( 20, 6 ); // Version needed.
		central.writeUInt16LE( 0x0800, 8 );
		central.writeUInt16LE( 0, 10 );
		central.writeUInt16LE( 0, 12 );
		central.writeUInt16LE( 0x21, 14 );
		central.writeUInt32LE( crc, 16 );
		central.writeUInt32LE( size, 20 );
		central.writeUInt32LE( size, 24 );
		central.writeUInt16LE( name.length, 28 );
		central.writeUInt16LE( 0, 30 ); // Extra length.
		central.writeUInt16LE( 0, 32 ); // Comment length.
		central.writeUInt16LE( 0, 34 ); // Disk.
		central.writeUInt16LE( 0, 36 ); // Internal attributes.
		central.writeUInt32LE( 0, 38 ); // External attributes.
		central.writeUInt32LE( offset, 42 );
		centrals.push( central, name );

		offset += local.length + name.length + size;
	}

	const centralSize = centrals.reduce( ( sum, buffer ) => sum + buffer.length, 0 );
	const end = Buffer.alloc( 22 );
	end.writeUInt32LE( 0x06054b50, 0 );
	end.writeUInt16LE( 0, 4 );
	end.writeUInt16LE( 0, 6 );
	end.writeUInt16LE( entries.length, 8 );
	end.writeUInt16LE( entries.length, 10 );
	end.writeUInt32LE( centralSize, 12 );
	end.writeUInt32LE( offset, 16 );
	end.writeUInt16LE( 0, 20 );

	return Buffer.concat( [ ...locals, ...centrals, end ] );
};

const findPlugin = async ( requestUtils, plugin ) => {
	const plugins = await requestUtils.rest( {
		method: 'GET',
		path: '/wp/v2/plugins',
	} );

	return plugins.find( ( item ) => item.plugin === plugin ) || null;
};

const removePlugin = async ( requestUtils, plugin ) => {
	const found = await findPlugin( requestUtils, plugin );

	if ( ! found ) {
		return;
	}

	if ( found.status === 'active' ) {
		await requestUtils.rest( {
			method: 'PUT',
			path: `/wp/v2/plugins/${ plugin }`,
			data: { status: 'inactive' },
		} );
	}

	await requestUtils.rest( {
		method: 'DELETE',
		path: `/wp/v2/plugins/${ plugin }`,
	} );
};

/**
 * Install and activate the probe fixture plugin through the real plugin
 * upload screen. Any copy left by an earlier run is replaced, so the site
 * always runs the current fixture. Fails, never skips.
 */
const installFixture = async ( browser, requestUtils ) => {
	await removePlugin( requestUtils, FIXTURE_PLUGIN );

	const entries = fs.readdirSync( FIXTURE_DIR ).map( ( file ) => ( {
		name: `${ FIXTURE_SLUG }/${ file }`,
		data: fs.readFileSync( path.join( FIXTURE_DIR, file ) ),
	} ) );
	const tempDir = fs.mkdtempSync( path.join( os.tmpdir(), 'sowb-e2e-' ) );
	const zipPath = path.join( tempDir, `${ FIXTURE_SLUG }.zip` );
	fs.writeFileSync( zipPath, makeZip( entries ) );

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
		fs.rmSync( tempDir, { recursive: true, force: true } );
	}

	const activated = await requestUtils.rest( {
		method: 'PUT',
		path: `/wp/v2/plugins/${ FIXTURE_PLUGIN }`,
		data: { status: 'active' },
	} );
	expect( activated.status, 'the probe fixture must activate' ).toBe( 'active' );
};

const removeFixture = ( requestUtils ) => removePlugin( requestUtils, FIXTURE_PLUGIN ).catch( () => {} );

/**
 * Fixture routes, run as the admin session.
 */
const fixture = ( admin ) => ( {
	probeRuns: async () => {
		const { status, body } = await call( admin, 'GET', 'wp-json/sowb-e2e/v1/probe-runs' );
		expect( status ).toBe( 200 );

		return body.runs;
	},
	resetProbe: async () => {
		const { status } = await call( admin, 'DELETE', 'wp-json/sowb-e2e/v1/probe-runs' );
		expect( status ).toBe( 200 );
	},
	seed: async ( content, { status = 'draft', author = 1, title = 'SOWB e2e', date, type } = {} ) => {
		const data = { content, status, author, title };

		if ( date ) {
			data.date = date;
		}

		if ( type ) {
			data.type = type;
		}

		const response = await call( admin, 'POST', 'wp-json/sowb-e2e/v1/seed', data );
		expect( response.status, JSON.stringify( response.body ) ).toBe( 200 );

		return response.body.id;
	},
	stored: async ( id ) => {
		const { status, body } = await call( admin, 'GET', `wp-json/sowb-e2e/v1/blocks/${ id }` );
		expect( status ).toBe( 200 );

		return body;
	},
	executeUpdate: async ( input, idBase = '' ) => {
		const { status, body } = await call( admin, 'POST', 'wp-json/sowb-e2e/v1/execute-update', {
			input,
			id_base: idBase,
		} );
		expect( status, JSON.stringify( body ) ).toBe( 200 );

		return body;
	},
} );

/**
 * Serialize block attributes the way core's serialize_block_attributes()
 * does, so seeded content parses exactly as saved content.
 */
const serializeAttributes = ( attrs ) => JSON.stringify( attrs )
	.replace( /--/g, '\\u002d\\u002d' )
	.replace( /</g, '\\u003c' )
	.replace( />/g, '\\u003e' )
	.replace( /&/g, '\\u0026' )
	.replace( /\\"/g, '\\u0022' );

/**
 * A self-closing widget block.
 */
const widgetBlock = ( blockName, attrs ) => `<!-- wp:${ blockName } ${ serializeAttributes( attrs ) } /-->`;

const blockNameForClass = ( widgetClass ) => `sowb/${ widgetClass.toLowerCase().replace( /[_\\]/g, '-' ) }`;

const paragraph = ( text ) => `<!-- wp:paragraph -->\n<p>${ text }</p>\n<!-- /wp:paragraph -->`;

const group = ( inner ) => `<!-- wp:group -->\n<div class="wp-block-group">${ inner.join( '\n' ) }</div>\n<!-- /wp:group -->`;

/**
 * The qualifying widget blocks of parsed content, in document order, with
 * their paths. Mirrors the plugin's walk: sowb/ blocks qualify, except a
 * legacy sowb/widget-block without a widgetClass; qualifying blocks are not
 * descended; core/block references are skipped.
 */
const widgetEntries = ( blocks, prefix = [], entries = [] ) => {
	blocks.forEach( ( block, index ) => {
		const name = block.blockName || '';

		if ( name === 'core/block' ) {
			return;
		}

		const attrs = block.attrs || {};
		const qualifies = name.startsWith( 'sowb/' ) && ! ( name === 'sowb/widget-block' && ! attrs.widgetClass );

		if ( qualifies ) {
			entries.push( {
				path: [ ...prefix, index ],
				block,
			} );

			return;
		}

		if ( Array.isArray( block.innerBlocks ) && block.innerBlocks.length ) {
			widgetEntries( block.innerBlocks, [ ...prefix, index ], entries );
		}
	} );

	return entries;
};

/**
 * A block by path in parsed content.
 */
const blockAt = ( blocks, blockPath ) => {
	let block = blocks[ blockPath[ 0 ] ];

	for ( const key of blockPath.slice( 1 ) ) {
		block = block.innerBlocks[ key ];
	}

	return block;
};

/**
 * The stored widgetData of a post's widget at an index, as JSON would show
 * it (an empty value is `{}`).
 */
const storedWidgetData = async ( fx, id, index = 0 ) => {
	const stored = await fx.stored( id );
	const entry = widgetEntries( stored.blocks )[ index ];
	expect( entry, `widget ${ index } of post ${ id }` ).toBeDefined();
	const data = entry.block.attrs.widgetData;

	if ( data === undefined || data === null || ( Array.isArray( data ) && data.length === 0 ) ) {
		return {};
	}

	return data;
};

const abilityPath = ( name ) => `wp-json/wp-abilities/v1/abilities/${ name }`;

const widgetGet = ( session, postId ) => call(
	session,
	'GET',
	`${ abilityPath( 'sowb/widget-get' ) }/run?${ new URLSearchParams( { 'input[post_id]': String( postId ) } ).toString() }`
);

const widgetUpdate = ( session, input ) => call(
	session,
	'POST',
	`${ abilityPath( 'sowb/widget-update' ) }/run`,
	{ input }
);

/**
 * Every string in a value, with its path.
 *
 * @return {{path: string, value: string}[]}
 */
const stringLeaves = ( value, prefix = '' ) => {
	if ( typeof value === 'string' ) {
		return [ { path: prefix, value } ];
	}

	if ( value && typeof value === 'object' ) {
		return Object.entries( value ).flatMap( ( [ key, item ] ) => stringLeaves( item, prefix ? `${ prefix }.${ key }` : key ) );
	}

	return [];
};

/**
 * Assert that a value holds no live or encoded probe shortcode, no script
 * and no event handler, in any string.
 */
const expectNeutral = ( value, label ) => {
	for ( const leaf of stringLeaves( value ) ) {
		const where = `${ label } ${ leaf.path }`;
		expect( leaf.value, where ).not.toMatch( PROBE_SHORTCODE );
		expect( leaf.value, where ).not.toMatch( ENCODED_BRACKET );
		expect( leaf.value.toLowerCase(), where ).not.toContain( '<script' );
		expect( leaf.value.toLowerCase(), where ).not.toContain( 'onerror' );
		expect( leaf.value, where ).not.toContain( PROBE_RAN );
	}
};

/**
 * Whether a widget card on the Widgets admin page shows as active.
 */
const isCardActive = async ( page, id ) => {
	const card = page.locator( `.so-widget[data-id="${ id }"]` );
	await expect( card ).toHaveCount( 1 );

	return card.evaluate( ( el ) => el.classList.contains( 'so-widget-is-active' ) );
};

/**
 * Activate or deactivate a widget with its real card button, and wait for
 * the admin-ajax request that stores the change.
 */
const setWidgetActive = async ( page, id, active ) => {
	if ( await isCardActive( page, id ) === active ) {
		return;
	}

	const card = page.locator( `.so-widget[data-id="${ id }"]` );
	const button = card.locator( active ? '.so-widget-activate' : '.so-widget-deactivate' );

	const [ response ] = await Promise.all( [
		page.waitForResponse( ( r ) => r.url().includes( 'so_widgets_bundle_manage' ) ),
		button.click(),
	] );
	expect( response.ok() ).toBe( true );
	await expect( card ).toHaveClass( active ? /so-widget-is-active/ : /so-widget-is-inactive/ );
};

module.exports = {
	ENCODED_BRACKET,
	FULL_WIDTH_PROBE,
	PROBE_RAN,
	PROBE_SHORTCODE,
	WIDGETS_ADMIN,
	abilityPath,
	blockAt,
	blockNameForClass,
	call,
	createUserSession,
	deleteUsers,
	expectNeutral,
	fixture,
	group,
	installFixture,
	isCardActive,
	login,
	newVisitor,
	paragraph,
	removeFixture,
	setWidgetActive,
	siteUrl,
	storedWidgetData,
	stringLeaves,
	widgetBlock,
	widgetEntries,
	widgetGet,
	widgetUpdate,
};
