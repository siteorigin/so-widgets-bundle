const {
	expect,
	test
} = require( '@playwright/test' );

const { setupRequestUtils } = require( 'siteorigin-tests-common/playwright/common' );

const { widgetBlock } = require( './helpers/widget-block-abilities' );

/**
 * A widget block's stored markup is built on the server when its content is
 * saved over REST: for posts and pages, and for the content types whose saves
 * must never be rejected over a widget block (synced patterns, template parts
 * and templates).
 */

const EDITOR_BLOCK = 'sowb/siteorigin-widget-editor-widget';
const EDITOR_CLASS = 'SiteOrigin_Widget_Editor_Widget';
const STALE = '<div class="sowb-stale-markup">Stale markup</div>';

const editorWidget = ( text, extra = {}, title = 'Saved widget title' ) => widgetBlock( EDITOR_BLOCK, {
	widgetClass: EDITOR_CLASS,
	widgetData: {
		title,
		text: `<p>${ text }</p>`,
		autop: false,
		text_selected_editor: 'tinymce',
	},
	...extra,
} );

// The attributes of the first widget block in stored content.
const storedAttrs = ( raw ) => JSON.parse( raw.match( /<!-- wp:sowb\/[a-z-]+ (\{.*\}) \/-->/ )[ 1 ] );

// Each content type the block editor saves on its own, and how REST names it.
const LENIENT_TYPES = [
	{
		name: 'a synced pattern',
		path: '/wp/v2/blocks',
		data: ( content ) => ( { title: 'WB save markup pattern', status: 'publish', content } ),
	},
	{
		name: 'a template part',
		path: '/wp/v2/template-parts',
		data: ( content ) => ( { slug: `wb-save-markup-part-${ Date.now() }`, title: 'WB save markup part', content } ),
	},
	{
		name: 'a template',
		path: '/wp/v2/templates',
		data: ( content ) => ( { slug: `wb-save-markup-template-${ Date.now() }`, title: 'WB save markup template', content } ),
	},
];

test.describe( 'Stored widget block markup is built on the server at save', () => {
	let requestUtils;
	const created = [];

	const create = async ( path, data ) => {
		const record = await requestUtils.rest( { method: 'POST', path, data } );

		created.push( `${ path }/${ record.id }` );

		return record;
	};

	test.beforeAll( async () => {
		requestUtils = await setupRequestUtils();
	} );

	test.afterAll( async () => {
		for ( const path of created.reverse() ) {
			await requestUtils.rest( { method: 'DELETE', path, params: { force: true } } ).catch( () => {} );
		}
	} );

	for ( const type of LENIENT_TYPES ) {
		test( `${ type.name } gets markup for its widget's data, anchor and class, and keeps its other attributes`, async () => {
			const record = await create( type.path, type.data( editorWidget( 'Built at save', {
				anchor: 'pricing',
				className: 'northfield-widget',
				widgetMarkup: STALE,
				lock: { move: true, remove: true },
			} ) ) );
			const attrs = storedAttrs( record.content.raw );

			expect( attrs.widgetMarkup ).toContain( 'Built at save' );
			expect( attrs.widgetMarkup ).toContain( 'pricing' );
			expect( attrs.widgetMarkup ).toContain( 'northfield-widget' );
			expect( attrs.widgetMarkup ).not.toContain( 'Stale markup' );
			expect( attrs.lock ).toEqual( { move: true, remove: true } );
			expect( attrs.anchor ).toBe( 'pricing' );
			expect( attrs.className ).toBe( 'northfield-widget' );
		} );

		test( `${ type.name } still saves when a widget block cannot be rendered`, async () => {
			const record = await create( type.path, type.data( widgetBlock( EDITOR_BLOCK, {
				widgetClass: 'SiteOrigin_Widget_That_Does_Not_Exist',
				widgetData: { text: 'Kept as sent' },
				widgetMarkup: STALE,
			} ) ) );
			const attrs = storedAttrs( record.content.raw );

			expect( attrs.widgetMarkup ).toBe( '' );
			expect( attrs.widgetData ).toEqual( { text: 'Kept as sent' } );
			expect( attrs.widgetClass ).toBe( 'SiteOrigin_Widget_That_Does_Not_Exist' );
		} );
	}

	test( 'a post still refuses a widget block that cannot be rendered', async () => {
		const refused = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/posts',
			data: {
				title: 'WB save markup refused',
				status: 'draft',
				content: widgetBlock( EDITOR_BLOCK, {
					widgetClass: 'SiteOrigin_Widget_That_Does_Not_Exist',
					widgetData: { text: 'x' },
				} ),
			},
		} ).then(
			( record ) => ( { id: record.id } ),
			( error ) => ( { status: error.data && error.data.status } )
		);

		if ( refused.id ) {
			created.push( `/wp/v2/posts/${ refused.id }` );
		}

		expect( refused.status ).toBe( 400 );
	} );

	test( 'a titled widget with no class attribute in a synced pattern is built from its block name, and prints no title wrapper', async () => {
		const pattern = await create( '/wp/v2/blocks', {
			title: 'WB save markup classless',
			status: 'publish',
			content: widgetBlock( EDITOR_BLOCK, {
				widgetData: {
					title: 'Classless widget title',
					text: '<p>Classless text</p>',
					autop: false,
					text_selected_editor: 'tinymce',
				},
			} ),
		} );
		const attrs = storedAttrs( pattern.content.raw );

		expect( attrs.widgetMarkup ).toContain( 'Classless text' );

		const page = await create( '/wp/v2/pages', {
			title: 'WB save markup classless page',
			status: 'publish',
			content: `<!-- wp:block {"ref":${ pattern.id }} /-->`,
		} );
		const html = await ( await requestUtils.request.get( page.link ) ).text();

		expect( html ).toContain( 'Classless text' );
		// The stored markup is what is printed: a live render would add this.
		expect( html ).not.toContain( '<h3 class="widget-title">Classless widget title</h3>' );
	} );

	for ( const type of LENIENT_TYPES ) {
		test( `${ type.name } holding an active widget's block with data that is not an object is still saved`, async () => {
			const record = await create( type.path, type.data( [
				widgetBlock( EDITOR_BLOCK, { widgetClass: EDITOR_CLASS, widgetData: 'not data', widgetMarkup: STALE } ),
				editorWidget( 'Sound text' ),
			].join( '\n\n' ) ) );

			const attrs = storedAttrs( record.content.raw );

			// Kept as sent, with nothing stale left to print.
			expect( attrs.widgetData ).toBe( 'not data' );
			expect( attrs.widgetClass ).toBe( EDITOR_CLASS );
			expect( attrs.widgetMarkup ).toBe( '' );
			expect( attrs.widgetIcons ).toEqual( [] );
			expect( record.content.raw ).not.toContain( 'sowb-stale-markup' );
			expect( record.content.raw ).toContain( 'Sound text' );
		} );
	}

	test( 'a page using a synced pattern whose widget block has data that is not an object still loads', async () => {
		const pattern = await create( '/wp/v2/blocks', {
			title: 'WB save markup malformed',
			status: 'publish',
			content: [
				widgetBlock( EDITOR_BLOCK, { widgetClass: EDITOR_CLASS, widgetData: 'not data' } ),
				editorWidget( 'Text after the malformed block' ),
			].join( '\n\n' ),
		} );
		const page = await create( '/wp/v2/pages', {
			title: 'WB save markup malformed page',
			status: 'publish',
			content: `<!-- wp:block {"ref":${ pattern.id }} /-->`,
		} );
		const response = await requestUtils.request.get( page.link );

		expect( response.status() ).toBe( 200 );
		expect( await response.text() ).toContain( 'Text after the malformed block' );
	} );

	test( 'each widget block with an icon is saved with its icon font', async () => {
		const pattern = await create( '/wp/v2/blocks', {
			title: 'WB save markup two blocks',
			status: 'publish',
			content: [
				widgetBlock( 'sowb/siteorigin-widget-icon-widget', {
					widgetClass: 'SiteOrigin_Widget_Icon_Widget',
					widgetData: { icon: 'materialicons-sowm-regular-add_home', color: '#404040', size: '2em', align: 'center' },
				} ),
				editorWidget( 'No icon here' ),
				// The same font again, after another block has rendered.
				widgetBlock( 'sowb/siteorigin-widget-icon-widget', {
					widgetClass: 'SiteOrigin_Widget_Icon_Widget',
					widgetData: { icon: 'materialicons-sowm-regular-add_home', color: '#404040', size: '3em', align: 'left' },
				} ),
			].join( '\n\n' ),
		} );
		const blocks = pattern.content.raw
			.match( /<!-- wp:sowb\/[a-z-]+ \{.*?\} \/-->/g )
			.map( ( block ) => JSON.parse( block.match( /(\{.*\})/ )[ 1 ] ) );

		expect( blocks ).toHaveLength( 3 );
		expect( blocks[ 0 ].widgetIcons ).toEqual( [ 'siteorigin-widget-icon-font-materialicons' ] );
		expect( blocks[ 2 ].widgetIcons ).toEqual( [ 'siteorigin-widget-icon-font-materialicons' ] );
	} );

	test( 'a widget with an icon in a synced pattern has its icon font on the front end', async () => {
		const pattern = await create( '/wp/v2/blocks', {
			title: 'WB save markup icon',
			status: 'publish',
			content: widgetBlock( 'sowb/siteorigin-widget-icon-widget', {
				widgetClass: 'SiteOrigin_Widget_Icon_Widget',
				widgetData: { icon: 'materialicons-sowm-regular-add_home', color: '#404040', size: '2em', align: 'center' },
			} ),
		} );
		const page = await create( '/wp/v2/pages', {
			title: 'WB save markup icon page',
			status: 'publish',
			content: `<!-- wp:block {"ref":${ pattern.id }} /-->`,
		} );
		const html = await ( await requestUtils.request.get( page.link ) ).text();

		expect( html ).toMatch( /sow-icon-materialicons/ );
		expect( html ).toMatch( /id='siteorigin-widget-icon-font-materialicons-css'/ );
	} );
} );
