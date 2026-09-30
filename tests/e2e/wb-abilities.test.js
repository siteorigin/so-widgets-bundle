/**
 * The read-only sowb/widget-list and sowb/widget-describe abilities over the
 * Abilities API REST routes (wp-abilities/v1, WordPress 6.9+).
 *
 * Covers the registration metadata, the GET-only transport, the edit_posts
 * gate for each role, resolution by id, class and block name, and that an
 * inactive widget is never described, listed or activated.
 */
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

// The inactive-widget test changes widget activation for the whole site.
test.describe.configure( { mode: 'serial' } );

const ABILITIES = [ 'sowb/widget-list', 'sowb/widget-describe' ];
const WIDGETS_ADMIN = 'wp-admin/plugins.php?page=so-widgets-plugins';

// Join a REST path to WP_BASE_URL. Normalizing the base to end in a slash
// keeps this correct for a root install and a subdirectory install.
const restUrl = ( relativePath ) => {
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
		baseURL: restUrl( '' ),
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
 * Send a REST request as a logged-in session, or as a visitor when the
 * session is null.
 *
 * @return {Promise<{status: number, body: any}>}
 */
const call = async ( session, method, relativePath, body ) => {
	const context = session ? session.context : visitor;
	const headers = {};

	if ( session ) {
		headers[ 'X-WP-Nonce' ] = session.nonce;
	}

	const response = await context.fetch( restUrl( relativePath ), {
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

const abilityPath = ( name ) => `wp-json/wp-abilities/v1/abilities/${ name }`;

const listPath = () => `${ abilityPath( 'sowb/widget-list' ) }/run`;

const describePath = ( widget ) => {
	const params = new URLSearchParams();
	params.set( 'input[widget]', widget );

	return `${ abilityPath( 'sowb/widget-describe' ) }/run?${ params.toString() }`;
};

const runPath = ( name ) => (
	name === 'sowb/widget-list' ? listPath() : describePath( 'hero' )
);

const HERO = {
	id: 'hero',
	class: 'SiteOrigin_Widget_Hero_Widget',
	block_name: 'sowb/siteorigin-widget-hero-widget',
};

const LOTTIE = {
	id: 'lottie-player',
	class: 'SiteOrigin_Widget_Lottie_Player_Widget',
	block_name: 'sowb/siteorigin-widget-lottie-player-widget',
};

let requestUtils;
let visitor;
const auth = {};
const createdUsers = [];

/**
 * Create a user with the given role and log it in.
 */
const createUserSession = async ( role ) => {
	const suffix = `${ Date.now() }-${ Math.floor( Math.random() * 1e6 ) }`;
	const username = `sowb-abilities-${ role }-${ suffix }`;
	const password = `sowb-abilities-${ suffix }!A1`;
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

test.beforeAll( async () => {
	requestUtils = await setupRequestUtils();

	// This file needs the Abilities API. Fail loudly on an older WordPress.
	const index = await requestUtils.rest( {
		method: 'GET',
		path: '/',
	} );
	expect( index.namespaces ).toContain( 'wp-abilities/v1' );

	visitor = await request.newContext( { ignoreHTTPSErrors: true } );
	auth.admin = await login( process.env.WP_USERNAME, process.env.WP_PASSWORD );
	auth.contributor = await createUserSession( 'contributor' );
	auth.subscriber = await createUserSession( 'subscriber' );
} );

test.afterAll( async () => {
	for ( const session of Object.values( auth ) ) {
		await session.context.dispose();
	}

	if ( visitor ) {
		await visitor.dispose();
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
} );

test( 'both abilities are registered read-only, in REST and public to MCP', async () => {
	for ( const name of ABILITIES ) {
		const { status, body } = await call( auth.admin, 'GET', abilityPath( name ) );

		expect( status, name ).toBe( 200 );
		expect( body.name ).toBe( name );
		expect( body.category ).toBe( 'so-widgets-bundle' );
		expect( body.meta.annotations.readonly ).toBe( true );
		expect( body.meta.annotations.destructive ).toBe( false );
		expect( body.meta.annotations.idempotent ).toBe( true );
		expect( body.meta.show_in_rest ).toBe( true );
		expect( body.meta.mcp?.public ).toBe( true );
		expect( body.meta.readonly ).toBeUndefined();
	}
} );

test( 'both abilities run over GET and refuse POST', async () => {
	for ( const name of ABILITIES ) {
		const post = await call(
			auth.admin,
			'POST',
			`${ abilityPath( name ) }/run`,
			name === 'sowb/widget-list' ? {} : { input: { widget: 'hero' } }
		);
		expect( post.status, name ).toBe( 405 );
		expect( post.body.code ).toBe( 'rest_ability_invalid_method' );

		const get = await call( auth.admin, 'GET', runPath( name ) );
		expect( get.status, name ).toBe( 200 );
	}
} );

test( 'a contributor can list and describe; a subscriber and a visitor cannot', async () => {
	const list = await call( auth.contributor, 'GET', listPath() );
	expect( list.status ).toBe( 200 );
	expect( list.body.widgets ).toContainEqual( expect.objectContaining( HERO ) );

	const describe = await call( auth.contributor, 'GET', describePath( 'hero' ) );
	expect( describe.status ).toBe( 200 );
	expect( describe.body ).toMatchObject( HERO );
	expect( describe.body.schema.properties.frames.type ).toBe( 'array' );

	for ( const name of ABILITIES ) {
		const subscriber = await call( auth.subscriber, 'GET', runPath( name ) );
		expect( subscriber.status, name ).toBe( 403 );
		expect( subscriber.body.code ).toBe( 'sowb_cannot_read_widgets' );
		expect( JSON.stringify( subscriber.body ) ).not.toContain( '"schema"' );

		const visitor = await call( null, 'GET', runPath( name ) );
		expect( visitor.status, name ).toBe( 401 );
		expect( visitor.body.code ).toBe( 'sowb_cannot_read_widgets' );
		expect( JSON.stringify( visitor.body ) ).not.toContain( '"schema"' );
	}
} );

test( 'describe resolves the same widget by id, class and block name', async () => {
	const results = [];

	for ( const key of [ HERO.id, HERO.class, HERO.block_name ] ) {
		const { status, body } = await call( auth.contributor, 'GET', describePath( key ) );
		expect( status, key ).toBe( 200 );
		expect( body ).toMatchObject( HERO );
		results.push( body.schema );
	}

	expect( results[ 1 ] ).toEqual( results[ 0 ] );
	expect( results[ 2 ] ).toEqual( results[ 0 ] );
} );

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

test( 'an inactive widget is never described, listed or activated', async ( { page } ) => {
	await doLogin( page );
	await soGoTo( page, WIDGETS_ADMIN );

	const initial = {
		[ LOTTIE.id ]: await isCardActive( page, LOTTIE.id ),
		button: await isCardActive( page, 'button' ),
	};

	try {
		// Control: while Lottie Player is active, it is described.
		await setWidgetActive( page, LOTTIE.id, true );
		const activeLottie = await call( auth.admin, 'GET', describePath( LOTTIE.id ) );
		expect( activeLottie.status ).toBe( 200 );
		expect( activeLottie.body ).toMatchObject( LOTTIE );

		await setWidgetActive( page, LOTTIE.id, false );
		await setWidgetActive( page, 'button', false );

		// Admin is the highest role, so no role path activates the widget.
		for ( const key of [ LOTTIE.id, LOTTIE.class, LOTTIE.block_name ] ) {
			const { status, body } = await call( auth.admin, 'GET', describePath( key ) );
			expect( status, key ).toBe( 404 );
			expect( body.code ).toBe( 'sowb_widget_unavailable' );
		}

		// Hero keeps the Button class loaded, but Button is not active.
		const button = await call( auth.admin, 'GET', describePath( 'button' ) );
		expect( button.status ).toBe( 404 );
		expect( button.body.code ).toBe( 'sowb_widget_unavailable' );

		const hero = await call( auth.admin, 'GET', describePath( 'hero' ) );
		expect( hero.status ).toBe( 200 );
		const nestedButton = hero.body.schema.properties.frames.items.properties.buttons.items.properties.button;
		expect( nestedButton.type ).toBe( 'object' );
		expect( nestedButton.properties.text ).toBeDefined();

		const list = await call( auth.admin, 'GET', listPath() );
		expect( list.status ).toBe( 200 );
		const ids = list.body.widgets.map( ( widget ) => widget.id );
		expect( ids ).toContain( 'hero' );
		expect( ids ).not.toContain( LOTTIE.id );
		expect( ids ).not.toContain( 'button' );

		const unknown = await call( auth.admin, 'GET', describePath( 'Not_A_Widget' ) );
		expect( unknown.status ).toBe( 404 );
		expect( unknown.body.code ).toBe( 'sowb_widget_unavailable' );

		// The describe calls did not change the stored activation state.
		await page.reload();
		await expect( page.locator( `.so-widget[data-id="${ LOTTIE.id }"]` ) ).toHaveClass( /so-widget-is-inactive/ );
		await expect( page.locator( '.so-widget[data-id="button"]' ) ).toHaveClass( /so-widget-is-inactive/ );
	} finally {
		await soGoTo( page, WIDGETS_ADMIN );
		await setWidgetActive( page, LOTTIE.id, initial[ LOTTIE.id ] );
		await setWidgetActive( page, 'button', initial.button );
	}
} );
