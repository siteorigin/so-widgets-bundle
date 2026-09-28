/**
 * Text written by sowb/widget-update never becomes a running shortcode.
 *
 * The post content filter runs do_shortcode() after the blocks render, so any
 * widget that prints text is in scope, not only the widgets that call
 * do_shortcode() themselves. This file:
 *
 * - Writes a probe string, holding the probe shortcode in every bracket
 *   encoding a browser decodes, to every free-text field of every active
 *   widget that has a text field, and checks stored data, stored markup and
 *   the rendered post.
 * - Proves each widget field that calls do_shortcode() on its value (the
 *   sinks) one by one: first with a human save as a positive control, then
 *   with every probe variant written through the ability.
 * - Ends with a positive control that the probe detects a running shortcode.
 *
 * Uses the sowb-e2e-probe fixture plugin, installed and removed by this file.
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
	blockNameForClass,
	call,
	fixture,
	installFixture,
	isCardActive,
	login,
	removeFixture,
	setWidgetActive,
	storedWidgetData,
	stringLeaves,
	widgetBlock,
	widgetEntries,
	widgetUpdate,
} = require( './helpers/widget-block-abilities' );

test.describe.configure( { mode: 'serial' } );

const PROBE_STRING = 'SOWB [sowb_e2e_probe] &#91;sowb_e2e_probe] &#x5B;sowb_e2e_probe] &#91sowb_e2e_probe] &amp;#91;sowb_e2e_probe] &lsqb;sowb_e2e_probe] <script>x()</script><img src=x onerror=y()>';

const PROBE_VARIANTS = [
	'[sowb_e2e_probe]',
	'&#91;sowb_e2e_probe]',
	'&#x5B;sowb_e2e_probe]',
	'&#91sowb_e2e_probe]',
	'&amp;#91;sowb_e2e_probe]',
	'&lsqb;sowb_e2e_probe]',
];

const MIN_TEXT_WIDGETS = 20;

/**
 * Widgets whose update cannot succeed when every string field holds the
 * probe string, and the narrowest change that lets them succeed. Each entry
 * keeps the probe in every other string field.
 *
 * - price-table: get_less_variables() passes the button colour fields to
 *   SiteOrigin_Widgets_Color_Object, which throws on a value that is not a
 *   hex colour. The colour field keeps a non-hex value, so the probe string
 *   in a colour field makes update() throw and the ability decline. Colour
 *   fields take their schema default instead.
 */
const COLOR_FIELDS_TAKE_DEFAULTS = [ 'price-table' ];

let requestUtils;
let admin;
let fx;
let page;
const initialCards = {};

/**
 * Whether a schema has a free-text property at any depth.
 */
const hasTextField = ( schema ) => {
	if ( ! schema || typeof schema !== 'object' ) {
		return false;
	}

	if ( schema[ 'x-sowb-text' ] ) {
		return true;
	}

	return Object.values( schema.properties || {} ).some( hasTextField ) ||
		hasTextField( schema.items );
};

/**
 * Build widget data from a schema. In probe mode every string property
 * without an enum gets the probe string, and repeaters get one item. Other
 * properties take the schema default when present.
 */
const buildData = ( schema, probe, options = {} ) => {
	if ( schema.type === 'object' ) {
		if ( ! schema.properties ) {
			return undefined;
		}

		const data = {};

		for ( const [ key, property ] of Object.entries( schema.properties ) ) {
			const value = buildData( property, probe, options );

			if ( value !== undefined ) {
				data[ key ] = value;
			}
		}

		return data;
	}

	if ( schema.type === 'array' ) {
		if ( probe && schema.items && schema.items.type === 'object' ) {
			return [ buildData( schema.items, probe, options ) ];
		}

		return undefined;
	}

	if (
		probe &&
		schema.type === 'string' &&
		! schema.enum &&
		! ( options.colorDefaults && schema.format === 'color' )
	) {
		return PROBE_STRING;
	}

	return schema.default;
};

/**
 * Assert that a value holds no live or encoded probe shortcode, no script and
 * no event handler, in any string.
 */
const expectStoredNeutral = ( value, label ) => {
	for ( const leaf of stringLeaves( value ) ) {
		const where = `${ label } ${ leaf.path }`;
		expect.soft( leaf.value, where ).not.toMatch( PROBE_SHORTCODE );
		expect.soft( leaf.value, where ).not.toMatch( ENCODED_BRACKET );
		expect.soft( leaf.value.toLowerCase(), where ).not.toContain( '<script' );
		expect.soft( leaf.value.toLowerCase(), where ).not.toContain( 'onerror' );
	}
};

/**
 * Assert rendered output: the probe never ran and no probe shortcode is left
 * in a form the parser or a browser would turn back into one.
 */
const expectRenderedNeutral = ( html, label ) => {
	expect.soft( html, label ).not.toContain( PROBE_RAN );
	expect.soft( html, label ).not.toMatch( PROBE_SHORTCODE );
	expect.soft( html, label ).not.toMatch( ENCODED_BRACKET );
};

/**
 * The post as rendered by the_content, through core REST and through the
 * front-end preview in the admin's browser session.
 *
 * @return {Promise<{rest: string, preview: string, page: string}>}
 */
const renderPost = async ( postId ) => {
	const rest = await call( admin, 'GET', `wp-json/wp/v2/posts/${ postId }?context=edit` );
	expect( rest.status ).toBe( 200 );

	await soGoTo( page, `?p=${ postId }&preview=true` );
	const content = page.locator( '.entry-content' ).first();
	await expect( content ).toBeAttached();

	return {
		rest: rest.body.content.rendered,
		preview: await content.innerHTML(),
		page: await page.content(),
	};
};

const expectPostRenderedNeutral = async ( postId, label ) => {
	const rendered = await renderPost( postId );
	expectRenderedNeutral( rendered.rest, `${ label } REST` );
	expectRenderedNeutral( rendered.preview, `${ label } preview` );
	expect.soft( rendered.page, `${ label } page` ).not.toContain( PROBE_RAN );

	return rendered;
};

const storedBlockAttrs = async ( postId ) => {
	const entry = widgetEntries( ( await fx.stored( postId ) ).blocks )[ 0 ];
	expect( entry ).toBeDefined();

	return entry.block.attrs;
};

const valueAt = ( data, path ) => path.reduce( ( value, key ) => ( value === undefined || value === null ? undefined : value[ key ] ), data );

/**
 * A patch that sets one path.
 */
const patchAt = ( path, value ) => path.reduceRight( ( inner, key ) => ( { [ key ]: inner } ), value );

test.beforeAll( async ( { browser } ) => {
	test.setTimeout( 300_000 );
	requestUtils = await setupRequestUtils();

	const index = await requestUtils.rest( {
		method: 'GET',
		path: '/',
	} );
	expect( index.namespaces ).toContain( 'wp-abilities/v1' );

	await installFixture( browser, requestUtils );

	admin = await login( process.env.WP_USERNAME, process.env.WP_PASSWORD );
	fx = fixture( admin );

	page = await browser.newPage();
	await doLogin( page );
	await soGoTo( page, WIDGETS_ADMIN );

	// Activate every widget, recording the initial state to restore later.
	const ids = await page.locator( '.so-widget[data-id]' ).evaluateAll(
		( cards ) => cards.map( ( card ) => card.getAttribute( 'data-id' ) )
	);
	expect( ids.length ).toBeGreaterThan( 0 );

	for ( const id of ids ) {
		initialCards[ id ] = await isCardActive( page, id );
	}

	for ( const id of ids ) {
		await setWidgetActive( page, id, true );
	}
} );

test.afterAll( async () => {
	test.setTimeout( 300_000 );

	if ( page ) {
		await soGoTo( page, WIDGETS_ADMIN ).catch( () => {} );

		for ( const [ id, active ] of Object.entries( initialCards ) ) {
			await setWidgetActive( page, id, active ).catch( () => {} );
		}

		await page.close();
	}

	if ( admin ) {
		await admin.context.dispose();
	}

	if ( requestUtils ) {
		await removeFixture( requestUtils );
	}
} );

test( 'probe text in every text field of every widget never becomes a running shortcode', async () => {
	test.setTimeout( 900_000 );
	await fx.resetProbe();

	const list = await call( admin, 'GET', `${ abilityPath( 'sowb/widget-list' ) }/run` );
	expect( list.status ).toBe( 200 );

	const widgets = [];

	for ( const entry of list.body.widgets ) {
		const params = new URLSearchParams( { 'input[widget]': entry.class } );
		const described = await call( admin, 'GET', `${ abilityPath( 'sowb/widget-describe' ) }/run?${ params.toString() }` );
		expect( described.status, entry.class ).toBe( 200 );

		if ( hasTextField( described.body.schema ) ) {
			widgets.push( { ...entry, schema: described.body.schema } );
		}
	}

	console.log( `Text widgets (${ widgets.length }): ${ widgets.map( ( widget ) => widget.id ).join( ', ' ) }` );
	expect( widgets.length ).toBeGreaterThanOrEqual( MIN_TEXT_WIDGETS );

	for ( const widget of widgets ) {
		await test.step( widget.id, async () => {
			const blockName = widget.block_name || blockNameForClass( widget.class );
			const postId = await fx.seed( widgetBlock( blockName, {
				widgetClass: widget.class,
				widgetData: buildData( widget.schema, false ) || {},
			} ) );

			const response = await widgetUpdate( admin, {
				post_id: postId,
				widget_data: buildData( widget.schema, true, {
					colorDefaults: COLOR_FIELDS_TAKE_DEFAULTS.includes( widget.id ),
				} ),
			} );
			expect.soft( response.status, widget.id ).toBe( 200 );
			expect.soft( response.body.status, `${ widget.id } ${ JSON.stringify( response.body ) }` ).toBe( 'ok' );

			if ( response.body.status !== 'ok' ) {
				return;
			}

			const attrs = await storedBlockAttrs( postId );
			expect.soft( response.body.widget_data ).toEqual( await storedWidgetData( fx, postId ) );
			expectStoredNeutral( attrs.widgetData, `${ widget.id } widgetData` );
			expectStoredNeutral( attrs.widgetMarkup || '', `${ widget.id } widgetMarkup` );

			const texts = stringLeaves( attrs.widgetData ).map( ( leaf ) => leaf.value );
			expect.soft(
				texts.some( ( text ) => text.includes( FULL_WIDTH_PROBE ) ),
				`${ widget.id } stores the neutralised probe`
			).toBe( true );

			await expectPostRenderedNeutral( postId, widget.id );
		} );
	}

	expect( await fx.probeRuns(), 'the probe shortcode must never run' ).toBe( 0 );
} );

/**
 * Each widget field that passes its value to do_shortcode() when rendering.
 */
const SINKS = [
	{
		name: 'Editor text',
		widgetClass: 'SiteOrigin_Widget_Editor_Widget',
		seed: { title: 'Sink', text: '<p>Seed</p>', text_selected_editor: 'html', autop: true },
		path: [ 'text' ],
		text: true,
	},
	{
		name: 'Hero frame content',
		widgetClass: 'SiteOrigin_Widget_Hero_Widget',
		seed: { frames: [ { content: '<p>Seed</p>', autop: true, buttons: [] } ] },
		path: [ 'frames', 0, 'content' ],
		text: true,
	},
	{
		name: 'Features feature text',
		widgetClass: 'SiteOrigin_Widget_Features_Widget',
		seed: { features: [ { title: 'Feature', text: 'Seed' } ] },
		path: [ 'features', 0, 'text' ],
		text: true,
	},
	{
		name: 'Price Table feature text',
		widgetClass: 'SiteOrigin_Widget_PriceTable_Widget',
		seed: { columns: [ { title: 'Column', price: '10', features: [ { text: 'Seed' } ] } ] },
		path: [ 'columns', 0, 'features', 0, 'text' ],
		text: true,
	},
	{
		name: 'Button URL',
		widgetClass: 'SiteOrigin_Widget_Button_Widget',
		seed: { text: 'Click', url: 'https://example.com/' },
		path: [ 'url' ],
		text: false,
		extraVariants: [
			'https://example.com/?q=[sowb_e2e_probe]',
			'https://example.com/?q=&#91;sowb_e2e_probe]',
		],
	},
];

/**
 * Save a widget block through core REST, as the block editor does.
 */
const humanSave = async ( sink, data ) => {
	const content = widgetBlock( blockNameForClass( sink.widgetClass ), {
		widgetClass: sink.widgetClass,
		widgetData: data,
	} );
	const saved = await call( admin, 'POST', 'wp-json/wp/v2/posts', {
		title: `SOWB sink ${ sink.name }`,
		status: 'draft',
		content,
	} );
	expect( saved.status, JSON.stringify( saved.body ) ).toBe( 201 );

	return saved.body.id;
};

for ( const sink of SINKS ) {
	test( `${ sink.name }: a supplied probe never runs, in any encoding`, async () => {
		test.setTimeout( 300_000 );

		// Positive control: a human save of the probe in this field runs it.
		await fx.resetProbe();
		const controlData = structuredClone( sink.seed );
		const controlPath = sink.path.slice( 0, -1 ).reduce( ( value, key ) => value[ key ], controlData );
		controlPath[ sink.path[ sink.path.length - 1 ] ] = sink.text ? '[sowb_e2e_probe]' : 'https://example.com/?q=[sowb_e2e_probe]';
		const controlId = await humanSave( sink, controlData );
		const control = await renderPost( controlId );
		expect( await fx.probeRuns(), `${ sink.name } positive control` ).toBeGreaterThan( 0 );

		if ( sink.text ) {
			expect( control.rest ).toContain( PROBE_RAN );
		}

		await fx.resetProbe();

		const variants = [ ...PROBE_VARIANTS, ...( sink.extraVariants || [] ) ];

		for ( const variant of variants ) {
			await test.step( variant, async () => {
				const postId = await fx.seed( widgetBlock( blockNameForClass( sink.widgetClass ), {
					widgetClass: sink.widgetClass,
					widgetData: sink.seed,
				} ) );

				const response = await widgetUpdate( admin, {
					post_id: postId,
					widget_data: patchAt( sink.path, variant ),
				} );
				expect( response.status ).toBe( 200 );
				expect( response.body.status, JSON.stringify( response.body ) ).toBe( 'ok' );

				const attrs = await storedBlockAttrs( postId );
				const stored = valueAt( attrs.widgetData, sink.path );
				const label = `${ sink.name } ${ variant }`;

				expect( typeof stored, label ).toBe( 'string' );
				expect( stored, label ).toContain( 'sowb_e2e_probe' );
				expect( stored, label ).not.toMatch( PROBE_SHORTCODE );
				expect( stored, label ).not.toMatch( ENCODED_BRACKET );

				if ( sink.text ) {
					expect( stored, label ).toContain( FULL_WIDTH_PROBE );
				} else {
					console.log( `Stored ${ sink.name } for ${ variant }: ${ stored }` );
				}

				expectStoredNeutral( attrs.widgetMarkup || '', `${ label } widgetMarkup` );

				const rendered = await expectPostRenderedNeutral( postId, label );

				if ( ! sink.text ) {
					const probeHrefs = await page.locator( '.entry-content .ow-button-base a[href]' ).evaluateAll(
						( links ) => links.map( ( link ) => link.getAttribute( 'href' ) )
					);
					expect( probeHrefs.length, `${ label } renders the link` ).toBeGreaterThan( 0 );
					console.log( `Rendered ${ sink.name } href for ${ variant }: ${ probeHrefs.join( ' ' ) }` );

					for ( const href of probeHrefs ) {
						expect( href, label ).not.toContain( PROBE_RAN );
						expect( href, label ).not.toMatch( PROBE_SHORTCODE );
						expect( href, label ).not.toMatch( ENCODED_BRACKET );
					}

					expect( rendered.rest ).not.toContain( 'sowb-e2e-probe' );
				}

				expect( await fx.probeRuns(), `${ label } probe runs` ).toBe( 0 );
			} );
		}
	} );
}

test( 'positive control: a human-saved probe shortcode runs and is detected', async () => {
	await fx.resetProbe();

	const postId = await humanSave( SINKS[ 0 ], { ...SINKS[ 0 ].seed, text: '<p>[sowb_e2e_probe]</p>' } );
	const rendered = await renderPost( postId );

	expect( rendered.rest ).toContain( PROBE_RAN );
	expect( await fx.probeRuns() ).toBeGreaterThan( 0 );

	await fx.resetProbe();
} );
