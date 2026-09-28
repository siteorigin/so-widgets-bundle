/**
 * The sowb/widget-get and sowb/widget-update abilities, and the read-only
 * sowb/v1/posts/<id>/widgets route, over REST against real WordPress.
 *
 * Covers registration, transport methods, the edit_post gate per role,
 * post status and lock rules, target resolution, surgical writes, the patch
 * merge, the string floor and shortcode neutralising, preservation of saved
 * content, parity with a core save, object values, the throw path, inactive
 * widgets and the read-back of the saved post.
 *
 * Uses the sowb-e2e-probe fixture plugin (tests/e2e/fixtures), which this
 * file installs and removes. A [sowb_e2e_probe] shortcode counts its runs,
 * so every case that is not a positive control asserts that it never ran.
 */
const {
	expect,
	test,
} = require( '@playwright/test' );

const {
	doLogin,
	setupRequestUtils,
	soGoTo,
} = require( 'siteorigin-tests-common/playwright/common' );

const {
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
	storedWidgetData,
	widgetBlock,
	widgetEntries,
	widgetGet,
	widgetUpdate,
} = require( './helpers/widget-block-abilities' );

// Cases change site-wide state (widget activation, the probe counter).
test.describe.configure( { mode: 'serial' } );

const EDITOR = 'SiteOrigin_Widget_Editor_Widget';
const HEADLINE = 'SiteOrigin_Widget_Headline_Widget';
const HERO = 'SiteOrigin_Widget_Hero_Widget';

const editorBlock = ( widgetData, extra = {} ) => widgetBlock( blockNameForClass( EDITOR ), {
	widgetClass: EDITOR,
	widgetData,
	...extra,
} );

const headlineBlock = ( text = 'Headline' ) => widgetBlock( blockNameForClass( HEADLINE ), {
	widgetClass: HEADLINE,
	widgetData: {
		headline: { text },
		sub_headline: { text: 'Sub headline' },
	},
} );

const heroBlock = ( frames ) => widgetBlock( blockNameForClass( HERO ), {
	widgetClass: HERO,
	widgetData: { frames },
} );

// The seeded Editor text uses the HTML editor, so the widget's update() does
// not re-run wpautop on it and saved text is compared byte for byte.
const SEEDED_TEXT = '<p>Intro</p><iframe src="https://www.youtube.com/embed/x"></iframe>[sowb_e2e_probe]';
const editorSeed = ( overrides = {} ) => editorBlock( {
	title: 'Seed',
	text: '<p>Seed text</p>',
	text_selected_editor: 'html',
	autop: true,
	...overrides,
} );

let requestUtils;
let fx;
let visitor;
let adminId;
let positiveControl = false;
const auth = {};
const createdUsers = [];

/**
 * Assert an `ok` update, and that the returned widget_data is the stored
 * widgetData of the target block.
 */
const expectOk = async ( response, postId, index = 0 ) => {
	expect( response.status, JSON.stringify( response.body ) ).toBe( 200 );
	expect( response.body.status, JSON.stringify( response.body ) ).toBe( 'ok' );
	expect( response.body.updated ).toBe( true );
	expect( response.body.widget_index ).toBe( index );
	expect( response.body.widget_data ).toEqual( await storedWidgetData( fx, postId, index ) );

	return response.body.widget_data;
};

/**
 * Assert a declined update that left the post unchanged.
 */
const expectDeclined = async ( response, status, postId, before ) => {
	expect( response.status, JSON.stringify( response.body ) ).toBe( 200 );
	expect( response.body.status, JSON.stringify( response.body ) ).toBe( status );
	expect( response.body.updated ).toBe( false );
	expect( response.body.widget_data ).toEqual( {} );
	expect( typeof response.body.message ).toBe( 'string' );
	expect( response.body.message.length ).toBeGreaterThan( 0 );

	const after = await fx.stored( postId );
	expect( after.content ).toBe( before.content );
	expect( after.blocks ).toEqual( before.blocks );
};

const futureDate = () => {
	const date = new Date( Date.now() + 365 * 24 * 60 * 60 * 1000 );

	return date.toISOString().slice( 0, 19 ).replace( 'T', ' ' );
};

test.beforeAll( async ( { browser } ) => {
	test.setTimeout( 180_000 );
	requestUtils = await setupRequestUtils();

	// This file needs the Abilities API. Fail loudly on an older WordPress.
	const index = await requestUtils.rest( {
		method: 'GET',
		path: '/',
	} );
	expect( index.namespaces ).toContain( 'wp-abilities/v1' );

	await installFixture( browser, requestUtils );

	visitor = await newVisitor();
	auth.admin = await login( process.env.WP_USERNAME, process.env.WP_PASSWORD );
	auth.contributor = await createUserSession( requestUtils, 'contributor', createdUsers );
	auth.subscriber = await createUserSession( requestUtils, 'subscriber', createdUsers );
	fx = fixture( auth.admin );

	const me = await call( auth.admin, 'GET', 'wp-json/wp/v2/users/me' );
	adminId = me.body.id;
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

	await deleteUsers( requestUtils, createdUsers );
	await removeFixture( requestUtils );
} );

test.beforeEach( async () => {
	positiveControl = false;
	await fx.resetProbe();
} );

test.afterEach( async () => {
	if ( ! positiveControl ) {
		expect( await fx.probeRuns(), 'the probe shortcode must never run' ).toBe( 0 );
	}
} );

test( 'both abilities are registered with their annotations, in REST and public to MCP', async () => {
	const expected = {
		'sowb/widget-get': { readonly: true, destructive: false, idempotent: true },
		'sowb/widget-update': { readonly: false, destructive: true, idempotent: false },
	};

	for ( const [ name, annotations ] of Object.entries( expected ) ) {
		const { status, body } = await call( auth.admin, 'GET', abilityPath( name ) );

		expect( status, name ).toBe( 200 );
		expect( body.name ).toBe( name );
		expect( body.category ).toBe( 'so-widgets-bundle' );
		expect( body.meta.annotations ).toMatchObject( annotations );
		expect( body.meta.show_in_rest ).toBe( true );
		expect( body.meta.mcp?.public ).toBe( true );
		expect( body.meta.readonly ).toBeUndefined();
	}
} );

test( 'widget-get runs over GET only, widget-update over POST only', async () => {
	const postId = await fx.seed( editorSeed(), { author: adminId } );

	const getPost = await call( auth.admin, 'POST', `${ abilityPath( 'sowb/widget-get' ) }/run`, { input: { post_id: postId } } );
	expect( getPost.status ).toBe( 405 );
	expect( getPost.body.code ).toBe( 'rest_ability_invalid_method' );

	const getGet = await widgetGet( auth.admin, postId );
	expect( getGet.status ).toBe( 200 );
	expect( getGet.body.widget_count ).toBe( 1 );

	const params = new URLSearchParams( {
		'input[post_id]': String( postId ),
		'input[widget_data][title]': 'Via GET',
	} );
	const updateGet = await call( auth.admin, 'GET', `${ abilityPath( 'sowb/widget-update' ) }/run?${ params.toString() }` );
	expect( updateGet.status ).toBe( 405 );
	expect( updateGet.body.code ).toBe( 'rest_ability_invalid_method' );

	await expectOk( await widgetUpdate( auth.admin, { post_id: postId, widget_data: { title: 'Via POST' } } ), postId );
} );

test( 'edit_post gates both abilities and the REST route for every role', async () => {
	const own = await fx.seed( editorSeed(), { author: auth.contributor.id } );
	const other = await fx.seed( editorSeed(), { author: adminId } );
	const route = ( id ) => `wp-json/sowb/v1/posts/${ id }/widgets`;

	// A contributor reads and updates their own draft.
	const get = await widgetGet( auth.contributor, own );
	expect( get.status ).toBe( 200 );
	expect( get.body.widgets[ 0 ] ).toMatchObject( {
		widget_index: 0,
		block_name: blockNameForClass( EDITOR ),
		widget_class: EDITOR,
		active: true,
	} );

	const viaRoute = await call( auth.contributor, 'GET', route( own ) );
	expect( viaRoute.status ).toBe( 200 );
	expect( viaRoute.body ).toEqual( get.body );

	await expectOk( await widgetUpdate( auth.contributor, { post_id: own, widget_data: { title: 'Mine' } } ), own );

	// The route and the ability still agree after the write.
	expect( ( await call( auth.contributor, 'GET', route( own ) ) ).body ).toEqual( ( await widgetGet( auth.contributor, own ) ).body );

	const otherBefore = await fx.stored( other );

	// Another author's draft, a subscriber and a visitor are refused.
	const denied = [
		[ auth.contributor, other, 403 ],
		[ auth.subscriber, own, 403 ],
		[ visitor, own, 401 ],
	];

	for ( const [ session, postId, code ] of denied ) {
		const label = `${ session.username || 'visitor' } on ${ postId }`;

		const deniedGet = await widgetGet( session, postId );
		expect( deniedGet.status, label ).toBe( code );
		expect( deniedGet.body.code ).toBe( 'sowb_cannot_read_widgets' );
		expect( JSON.stringify( deniedGet.body ) ).not.toContain( 'widget_data' );

		const deniedRoute = await call( session, 'GET', route( postId ) );
		expect( deniedRoute.status, label ).toBe( code );
		expect( JSON.stringify( deniedRoute.body ) ).not.toContain( 'widget_data' );

		const deniedUpdate = await widgetUpdate( session, { post_id: postId, widget_data: { title: 'Nope' } } );
		expect( deniedUpdate.status, label ).toBe( code );
		expect( deniedUpdate.body.code ).toBe( 'sowb_cannot_update_widget' );
	}

	const otherAfter = await fx.stored( other );
	expect( otherAfter.content ).toBe( otherBefore.content );
} );

test( 'only draft and pending posts are updated', async () => {
	for ( const status of [ 'draft', 'pending' ] ) {
		const postId = await fx.seed( editorSeed(), { status, author: adminId } );
		await expectOk( await widgetUpdate( auth.admin, { post_id: postId, widget_data: { title: status } } ), postId );
	}

	for ( const status of [ 'publish', 'future', 'private' ] ) {
		const postId = await fx.seed( editorSeed(), {
			status,
			author: adminId,
			date: status === 'future' ? futureDate() : undefined,
		} );
		const before = await fx.stored( postId );
		expect( before.status ).toBe( status );

		await expectDeclined(
			await widgetUpdate( auth.admin, { post_id: postId, widget_data: { title: status } } ),
			'post-status-not-allowed',
			postId,
			before
		);
	}
} );

test( 'a post another user has open in the editor is declined as locked', async ( { page } ) => {
	const postId = await fx.seed( editorSeed(), { author: auth.contributor.id } );

	// A real editor page load sets the admin's edit lock.
	await doLogin( page );
	await soGoTo( page, `wp-admin/post.php?post=${ postId }&action=edit` );
	await page.waitForLoadState( 'load' );
	await page.close();

	const before = await fx.stored( postId );
	await expectDeclined(
		await widgetUpdate( auth.contributor, { post_id: postId, widget_data: { title: 'Locked' } } ),
		'post-locked',
		postId,
		before
	);

	// The lock holder can update the post it has open.
	await expectOk( await widgetUpdate( auth.admin, { post_id: postId, widget_data: { title: 'Own lock' } } ), postId );
} );

test( 'ambiguous targets are declined, never guessed', async () => {
	const multi = await fx.seed( [ editorSeed(), editorSeed() ].join( '\n' ), { author: adminId } );
	const multiBefore = await fx.stored( multi );

	await expectDeclined( await widgetUpdate( auth.admin, { post_id: multi, widget_data: { title: 'X' } } ), 'widget-ambiguous', multi, multiBefore );
	await expectDeclined( await widgetUpdate( auth.admin, { post_id: multi, widget_index: 2, widget_data: { title: 'X' } } ), 'widget-ambiguous', multi, multiBefore );

	const single = await fx.seed( editorSeed(), { author: adminId } );
	const singleBefore = await fx.stored( single );

	await expectDeclined( await widgetUpdate( auth.admin, { post_id: single, widget_index: 1, widget_data: { title: 'X' } } ), 'widget-ambiguous', single, singleBefore );

	const mismatch = await widgetUpdate( auth.admin, { post_id: single, widget_class: HERO, widget_data: { title: 'X' } } );
	await expectDeclined( mismatch, 'unsupported', single, singleBefore );
	expect( mismatch.body.message ).toContain( EDITOR );
} );

test( 'an update changes only the target block', async () => {
	const legacyBlock = widgetBlock( 'sowb/widget-block', {
		widgetClass: EDITOR,
		widgetData: { title: 'Legacy', text: '<p>Legacy text</p>', text_selected_editor: 'html', autop: true },
	} );
	const content = [
		group( [
			paragraph( 'Before' ),
			editorSeed( { title: 'In group' } ).replace( '"widgetClass"', '"anchor":"grp-anchor","className":"grp-class","widgetClass"' ),
			paragraph( 'After' ),
		] ),
		headlineBlock(),
		legacyBlock,
	].join( '\n' );
	const postId = await fx.seed( content, { author: adminId } );

	const initial = await fx.stored( postId );
	const entries = widgetEntries( initial.blocks );
	// Whitespace between top-level blocks parses as freeform blocks, so the
	// top-level widgets sit at [2] and [4].
	expect( entries.map( ( entry ) => entry.path ) ).toEqual( [ [ 0, 1 ], [ 2 ], [ 4 ] ] );
	expect( entries[ 0 ].block.attrs.anchor ).toBe( 'grp-anchor' );

	const roundTrip = 'C:\\path "quote"';
	const patches = [
		{ title: roundTrip },
		{ headline: { text: 'New headline' } },
		{ title: 'Legacy new' },
	];

	for ( const [ index, patch ] of patches.entries() ) {
		const before = await fx.stored( postId );
		const data = await expectOk( await widgetUpdate( auth.admin, { post_id: postId, widget_index: index, widget_data: patch } ), postId, index );
		const after = await fx.stored( postId );

		// Only the target block's attributes changed.
		const target = widgetEntries( after.blocks )[ index ];
		expect( target.path ).toEqual( entries[ index ].path );
		const expected = structuredClone( before.blocks );
		blockAt( expected, target.path ).attrs = target.block.attrs;
		expect( after.blocks ).toEqual( expected );

		if ( index === 0 ) {
			expect( target.block.attrs.anchor ).toBe( 'grp-anchor' );
			expect( target.block.attrs.className ).toBe( 'grp-class' );
			expect( data.title ).toBe( roundTrip );
		}
	}

	expect( ( await storedWidgetData( fx, postId, 1 ) ).headline.text ).toBe( 'New headline' );
	expect( ( await storedWidgetData( fx, postId, 2 ) ).title ).toBe( 'Legacy new' );
	expect( widgetEntries( ( await fx.stored( postId ) ).blocks )[ 2 ].block.blockName ).toBe( 'sowb/widget-block' );
} );

test( 'a patch merges field by field; invalid patches are declined', async () => {
	const postId = await fx.seed( heroBlock( [
		{ content: '<p>A1</p>', autop: true, buttons: [] },
		{ content: '<p>B1</p>', autop: true, buttons: [] },
	] ), { author: adminId } );

	const first = await expectOk( await widgetUpdate( auth.admin, { post_id: postId, widget_data: { frames: { 1: { content: 'B1x' } } } } ), postId );
	expect( first.frames[ 0 ].content ).toContain( 'A1' );
	expect( first.frames[ 1 ].content ).toContain( 'B1x' );

	const second = await expectOk( await widgetUpdate( auth.admin, { post_id: postId, widget_data: { frames: { 1: { content: 'B2' } } } } ), postId );
	expect( second.frames[ 0 ] ).toEqual( first.frames[ 0 ] );
	expect( second.frames[ 1 ].content ).toContain( 'B2' );
	expect( second.frames[ 1 ].content ).not.toContain( 'B1x' );

	const { content: firstContent, ...firstRest } = first.frames[ 1 ];
	const { content: secondContent, ...secondRest } = second.frames[ 1 ];
	expect( secondRest ).toEqual( firstRest );

	const cleared = await expectOk( await widgetUpdate( auth.admin, { post_id: postId, widget_data: { frames: [] } } ), postId );
	expect( Object.keys( cleared.frames || {} ) ).toHaveLength( 0 );

	const before = await fx.stored( postId );
	let deep = { leaf: 'x' };

	for ( let i = 0; i < 20; i++ ) {
		deep = { a: deep };
	}

	for ( const widgetData of [ { 'bad key': 'x' }, deep, {} ] ) {
		await expectDeclined( await widgetUpdate( auth.admin, { post_id: postId, widget_data: widgetData } ), 'unsupported', postId, before );
	}
} );

test( 'every supplied string is floored, even for an admin', async () => {
	const postId = await fx.seed( editorSeed(), { author: adminId } );

	const data = await expectOk( await widgetUpdate( auth.admin, {
		post_id: postId,
		widget_data: {
			text: '<p>ok</p><script>x()</script><img src=x onerror=y()><iframe src="https://example.com"></iframe>',
		},
	} ), postId );

	expect( data.text ).toContain( '<p>ok</p>' );

	const stored = widgetEntries( ( await fx.stored( postId ) ).blocks )[ 0 ].block.attrs;

	for ( const value of [ data.text, stored.widgetMarkup ] ) {
		expect( value.toLowerCase() ).not.toContain( '<script' );
		expect( value.toLowerCase() ).not.toContain( 'onerror' );
		expect( value.toLowerCase() ).not.toContain( '<iframe' );
	}
} );

test( 'saved content in fields the patch omits is kept byte for byte', async () => {
	positiveControl = true;
	const postId = await fx.seed( editorSeed( { text: SEEDED_TEXT } ), { author: adminId } );

	const data = await expectOk( await widgetUpdate( auth.admin, { post_id: postId, widget_data: { title: 'New' } } ), postId );
	expect( data.title ).toBe( 'New' );
	expect( data.text ).toBe( SEEDED_TEXT );

	// Positive control: the saved shortcode is live, so the probe detects runs.
	await fx.resetProbe();
	const rendered = await call( auth.admin, 'GET', `wp-json/wp/v2/posts/${ postId }?context=edit` );
	expect( rendered.status ).toBe( 200 );
	expect( rendered.body.content.rendered.split( PROBE_RAN ) ).toHaveLength( 2 );
} );

test( 'a value the caller supplies is floored, even when it matches stored content', async () => {
	// A shortcode in the saved title does not make the same text safe in
	// another field.
	const moved = await fx.seed( editorSeed( { title: '[sowb_e2e_probe]' } ), { author: adminId } );
	const movedData = await expectOk( await widgetUpdate( auth.admin, { post_id: moved, widget_data: { text: '[sowb_e2e_probe]' } } ), moved );
	expect( movedData.text ).toContain( FULL_WIDTH_PROBE );
	expect( movedData.text ).not.toMatch( PROBE_SHORTCODE );
	expect( movedData.title ).toBe( '[sowb_e2e_probe]' );

	// Echoing the stored value of the same field is floored too.
	const echoed = await fx.seed( editorSeed( { text: SEEDED_TEXT } ), { author: adminId } );
	const echoedData = await expectOk( await widgetUpdate( auth.admin, { post_id: echoed, widget_data: { text: SEEDED_TEXT } } ), echoed );
	expect( echoedData.text.toLowerCase() ).not.toContain( '<iframe' );
	expect( echoedData.text ).toContain( FULL_WIDTH_PROBE );
	expect( echoedData.text ).not.toMatch( PROBE_SHORTCODE );
} );

test( 'for a user without unfiltered_html, an update matches a core save', async () => {
	const seeded = editorSeed( { text: '<p>Intro</p><iframe src="https://www.youtube.com/embed/x"></iframe>' } );
	const draftA = await fx.seed( seeded, { author: auth.contributor.id } );
	const draftB = await fx.seed( seeded, { author: auth.contributor.id } );

	const update = await widgetUpdate( auth.contributor, { post_id: draftA, widget_data: { title: 'T' } } );
	const dataA = await expectOk( update, draftA );

	const storedB = await fx.stored( draftB );
	const save = await call( auth.contributor, 'POST', `wp-json/wp/v2/posts/${ draftB }`, { content: storedB.content } );
	expect( save.status, JSON.stringify( save.body ) ).toBe( 200 );

	const textA = ( await storedWidgetData( fx, draftA ) ).text;
	const textB = ( await storedWidgetData( fx, draftB ) ).text;
	expect( textA ).toBe( textB );
	expect( textA.toLowerCase() ).not.toContain( '<iframe' );
	expect( textA ).toContain( '<p>Intro</p>' );

	// The response reports the value after core's save filter.
	expect( dataA ).toEqual( await storedWidgetData( fx, draftA ) );
	expect( dataA.text ).toBe( textA );
} );

test( 'object values are normalised and floored', async () => {
	const postId = await fx.seed( editorSeed(), { author: adminId } );

	const data = await expectOk( await widgetUpdate( auth.admin, { post_id: postId, widget_data: { text: 'SOWB_E2E_NESTED_OBJECT' } } ), postId );
	const object = data.sowb_e2e_obj;
	expect( object ).toBeDefined();
	expect( Array.isArray( object ) ).toBe( false );
	expect( typeof object.a ).toBe( 'string' );
	expect( typeof object.b.c ).toBe( 'string' );
	expect( object.a ).toContain( FULL_WIDTH_PROBE );
	expectNeutral( object, 'sowb_e2e_obj' );

	// In-process object input: widget_data is a nested stdClass.
	const heroId = await fx.seed( heroBlock( [ { content: '<p>A</p>', autop: true, buttons: [] } ] ), { author: adminId } );
	const executed = await fx.executeUpdate( {
		post_id: heroId,
		widget_data: { frames: { 0: { content: '<script>x()</script>&#x5B;sowb_e2e_probe]' } } },
	} );
	expect( executed.result.status, JSON.stringify( executed.result ) ).toBe( 'ok' );

	const stored = await storedWidgetData( fx, heroId );
	expect( executed.result.widget_data ).toEqual( stored );
	expect( stored.frames[ 0 ].content ).toContain( FULL_WIDTH_PROBE );
	expectNeutral( stored, 'hero' );
} );

test( 'a widget that throws is declined and leaves no global state behind', async () => {
	const postId = await fx.seed( editorSeed().replace( '"widgetClass"', '"anchor":"throw-anchor","widgetClass"' ), { author: adminId } );
	const before = await fx.stored( postId );

	const executed = await fx.executeUpdate( {
		post_id: postId,
		widget_data: { text: 'SOWB_E2E_THROW' },
	}, 'sow-editor' );

	expect( executed.result.updated ).toBe( false );
	expect( executed.result.status ).toBe( 'unsupported' );
	expect( executed.result.message ).not.toContain( 'RuntimeException' );
	expect( executed.result.message ).not.toContain( 'internal detail' );
	expect( executed.preview_flag_set ).toBe( false );
	expect( executed.anchor_filter ).toBe( false );
	expect( executed.ob_delta ).toBe( 0 );

	const after = await fx.stored( postId );
	expect( after.content ).toBe( before.content );
} );

test( 'an inactive widget is never updated or activated', async ( { page } ) => {
	await doLogin( page );
	await soGoTo( page, WIDGETS_ADMIN );
	const initial = await isCardActive( page, 'headline' );

	try {
		await setWidgetActive( page, 'headline', true );
		const postId = await fx.seed( headlineBlock(), { author: adminId } );

		// Control: while active, the widget is updated.
		await expectOk( await widgetUpdate( auth.admin, { post_id: postId, widget_data: { headline: { text: 'Active' } } } ), postId );

		await setWidgetActive( page, 'headline', false );
		const before = await fx.stored( postId );

		await expectDeclined(
			await widgetUpdate( auth.admin, { post_id: postId, widget_data: { headline: { text: 'Inactive' } } } ),
			'widget-unavailable',
			postId,
			before
		);

		const get = await widgetGet( auth.admin, postId );
		expect( get.status ).toBe( 200 );
		expect( get.body.widgets[ 0 ].active ).toBe( false );
		expect( get.body.widgets[ 0 ].widget_name ).toBeNull();

		// The update did not activate the widget.
		await page.reload();
		await expect( page.locator( '.so-widget[data-id="headline"]' ) ).toHaveClass( /so-widget-is-inactive/ );
	} finally {
		await soGoTo( page, WIDGETS_ADMIN );
		await setWidgetActive( page, 'headline', initial );
	}
} );

test( 'a write the saved content does not confirm is reported as readback-failed', async () => {
	const variants = [
		{
			// The target is removed and a different block shifts into its path.
			content: [ editorSeed(), headlineBlock() ].join( '\n' ),
			remaining: [ blockNameForClass( HEADLINE ) ],
		},
		{
			// A same-type sibling shifts into the target's path.
			content: [ editorSeed( { title: 'A' } ), editorSeed( { title: 'B' } ) ].join( '\n' ),
			remaining: [ blockNameForClass( EDITOR ) ],
		},
		{
			// The only widget block is removed.
			content: editorSeed(),
			remaining: [],
		},
	];

	for ( const variant of variants ) {
		const postId = await fx.seed( variant.content, { author: adminId } );
		const response = await widgetUpdate( auth.admin, {
			post_id: postId,
			widget_index: 0,
			widget_data: { title: 'SOWB_E2E_DROP_BLOCK' },
		} );

		expect( response.status ).toBe( 200 );
		expect( response.body.updated ).toBe( true );
		expect( response.body.status, JSON.stringify( response.body ) ).toBe( 'readback-failed' );
		expect( response.body.widget_data ).toEqual( {} );

		// The write happened: the marked block is gone.
		const entries = widgetEntries( ( await fx.stored( postId ) ).blocks );
		expect( entries.map( ( entry ) => entry.block.blockName ) ).toEqual( variant.remaining );
	}
} );

test( 'stored data never holds an encoded bracket after an update', async () => {
	const postId = await fx.seed( editorSeed(), { author: adminId } );
	const data = await expectOk( await widgetUpdate( auth.admin, {
		post_id: postId,
		widget_data: { title: '&#91;sowb_e2e_probe] &#x5B;sowb_e2e_probe] &lsqb;sowb_e2e_probe]' },
	} ), postId );

	expect( data.title ).not.toMatch( ENCODED_BRACKET );
	expect( data.title.split( FULL_WIDTH_PROBE ) ).toHaveLength( 4 );
} );
