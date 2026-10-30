const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

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
	loginPage,
	makeZip,
} = require( './helpers/widget-block-abilities' );

const THEME_SLUG = 'sowb-e2e-classic-theme';
const THEME_DIR = path.resolve( __dirname, 'fixtures', THEME_SLUG );

const EDITOR_BLOCK = 'sowb/siteorigin-widget-editor-widget';
const CTA_BLOCK = 'sowb/siteorigin-widget-cta-widget';

const uniqueSuffix = () => `${ Date.now() }-${ Math.floor( Math.random() * 1e6 ) }`;

const themeStylesheets = async ( requestUtils, status ) => {
	const themes = await requestUtils.rest( {
		method: 'GET',
		path: '/wp/v2/themes',
		params: { status },
	} );

	return themes.map( ( theme ) => theme.stylesheet );
};

/**
 * Install the classic theme fixture through the real theme upload screen.
 * The Customizer hides its Widgets panel on a block theme.
 */
const installClassicTheme = async ( browser, requestUtils ) => {
	if ( ( await themeStylesheets( requestUtils, 'active,inactive' ) ).includes( THEME_SLUG ) ) {
		return false;
	}

	const entries = fs.readdirSync( THEME_DIR ).map( ( file ) => ( {
		name: `${ THEME_SLUG }/${ file }`,
		data: fs.readFileSync( path.join( THEME_DIR, file ) ),
	} ) );
	const tempDir = fs.mkdtempSync( path.join( os.tmpdir(), 'sowb-e2e-' ) );
	const zipPath = path.join( tempDir, `${ THEME_SLUG }.zip` );
	fs.writeFileSync( zipPath, makeZip( entries ) );

	const page = await browser.newPage();

	try {
		await loginPage( page );
		await soGoTo( page, 'wp-admin/theme-install.php' );
		await page.locator( '.upload-view-toggle' ).click();
		await page.locator( '#themezip' ).waitFor( { state: 'attached' } );
		await page.setInputFiles( '#themezip', zipPath );
		await Promise.all( [
			page.waitForNavigation(),
			page.click( '#install-theme-submit' ),
		] );
		await expect( page.locator( '#wpbody-content' ) ).toContainText( 'Theme installed successfully' );
	} finally {
		await page.close();
		fs.rmSync( tempDir, { recursive: true, force: true } );
	}

	return true;
};

const removeClassicTheme = async ( browser ) => {
	const page = await browser.newPage();

	try {
		await loginPage( page );
		await soGoTo( page, 'wp-admin/themes.php' );
		const deleteLink = await page.evaluate( ( slug ) => window._wpThemeSettings.themes
			.find( ( theme ) => theme.id === slug )?.actions.delete, THEME_SLUG );
		if ( deleteLink ) {
			await page.goto( deleteLink.replace( /&amp;/g, '&' ) );
		}
	} finally {
		await page.close();
	}
};

const serializeWidgetBlock = ( name, widgetClass, widgetData, extraAttributes = {} ) => {
	const attributes = JSON.stringify( { widgetClass, widgetData, ...extraAttributes } )
		.replace( /--/g, '\\u002d\\u002d' )
		.replace( /</g, '\\u003c' )
		.replace( />/g, '\\u003e' )
		.replace( /&/g, '\\u0026' );

	return `<!-- wp:${ name } ${ attributes } /-->`;
};

const editorBlock = ( title, text, extraAttributes ) => serializeWidgetBlock( EDITOR_BLOCK, 'SiteOrigin_Widget_Editor_Widget', {
	title,
	text: `<p>${ text }</p>`,
	autop: true,
	text_selected_editor: 'tinymce',
}, extraAttributes );

const ctaBlock = ( title, extraAttributes ) => serializeWidgetBlock( CTA_BLOCK, 'SiteOrigin_Widget_Cta_Widget', {
	title,
	sub_title: 'Widget area subtitle',
}, extraAttributes );

/**
 * Add block widgets to the first active widget area and create a page that shows it.
 * Core maps each block widget to its first block, so each block is its own widget.
 */
const seedWidgetArea = async ( contents ) => {
	const requestUtils = await setupRequestUtils();
	const sidebars = await requestUtils.rest( {
		method: 'GET',
		path: '/wp/v2/sidebars',
		params: { context: 'edit' },
	} );
	const sidebar = sidebars.find( ( item ) => item.status === 'active' );
	expect( sidebar, 'The classic theme fixture should provide a widget area.' ).toBeTruthy();

	const post = await requestUtils.createPost( {
		status: 'publish',
		title: `Widget area ${ uniqueSuffix() }`,
		content: '<!-- wp:paragraph --><p>Widget area page.</p><!-- /wp:paragraph -->',
	} );
	const widgets = [];

	for ( const content of contents ) {
		widgets.push( await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/widgets',
			data: {
				id_base: 'block',
				sidebar: sidebar.id,
				instance: { raw: { content } },
			},
		} ) );
	}

	await requestUtils.rest( {
		method: 'POST',
		path: `/wp/v2/sidebars/${ sidebar.id }`,
		data: { widgets: [ ...sidebar.widgets, ...widgets.map( ( widget ) => widget.id ) ] },
	} );

	return {
		post,
		sidebar,
		storedContent: async () => {
			const stored = await requestUtils.rest( {
				method: 'GET',
				path: `/wp/v2/widgets/${ widgets[ 0 ].id }`,
				params: { context: 'edit' },
			} );

			return stored.instance.raw.content;
		},
		cleanup: async () => {
			for ( const widget of widgets ) {
				await requestUtils.rest( {
					method: 'DELETE',
					path: `/wp/v2/widgets/${ widget.id }`,
					params: { force: true },
				} );
			}
			await requestUtils.rest( {
				method: 'POST',
				path: `/wp/v2/sidebars/${ sidebar.id }`,
				data: { widgets: sidebar.widgets },
			} );
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} );
		},
	};
};

const openCustomizer = async ( page, area ) => {
	const query = new URLSearchParams( {
		url: area.post.link,
		'autofocus[section]': `sidebar-widgets-${ area.sidebar.id }`,
	} );

	await soGoTo( page, `wp-admin/customize.php?${ query }` );
	await expect( page.locator( '.customize-control-sidebar_block_editor' ).first() )
		.toBeAttached( { timeout: 20000 } );
	const welcome = page.getByRole( 'button', { name: 'Got it', exact: true } );
	await welcome.waitFor( { state: 'visible', timeout: 5000 } ).catch( () => {} );
	if ( await welcome.isVisible() ) {
		await welcome.click();
	}
};

const openWidgetForm = async ( page, blockName, marker ) => {
	let clientId;
	await expect.poll( async () => {
		clientId = await page.evaluate( ( { name, content } ) => {
			const select = window.wp.data.select( 'core/block-editor' );
			return select.getClientIdsWithDescendants().find( ( id ) => select.getBlockName( id ) === name &&
				JSON.stringify( select.getBlockAttributes( id ).widgetData || {} ).includes( content ) );
		}, { name: blockName, content: marker } );
		return !! clientId;
	}, { timeout: 20000 } ).toBe( true );

	await page.evaluate( ( id ) => window.wp.data.dispatch( 'core/block-editor' ).selectBlock( id ), clientId );
	await page.getByRole( 'button', { name: 'Edit widget.', exact: true } ).click();
	const form = page.locator( `[data-block="${ clientId }"] .siteorigin-widget-form-main[data-class]` );
	await expect( form ).toBeVisible( { timeout: 20000 } );
	await expect.poll( () => form.evaluate(
		( element ) => !! window.jQuery( element ).data( 'sow-form-setup' )
	), { timeout: 20000 } ).toBe( true );

	return form;
};

const widgetTitle = ( form ) => form.locator( 'input[name$="[title]"]:not([name*="[button]"])' );

// A real press, so the field loses focus the way it does for a person. A locator
// click would wait for the button and hide what that press does to it.
const pressWithMouse = async ( page, button ) => {
	await button.scrollIntoViewIfNeeded();
	const box = await button.boundingBox();
	await page.mouse.click( box.x + ( box.width / 2 ), box.y + ( box.height / 2 ), { delay: 80 } );
};

const publish = async ( page ) => {
	const saved = page.waitForResponse( ( response ) => {
		const data = response.request().postData();
		return response.url().includes( 'admin-ajax.php' ) && data &&
			new URLSearchParams( data ).get( 'action' ) === 'customize_save';
	} );
	await pressWithMouse( page, page.locator( '#save' ) );
	expect( ( await ( await saved ).json() ).success ).toBe( true );
};

const openWidgetsScreen = async ( page ) => {
	await soGoTo( page, 'wp-admin/widgets.php' );
	await expect( page.locator( '.edit-widgets-header' ) ).toBeVisible( { timeout: 20000 } );
	const welcome = page.getByRole( 'dialog' ).getByRole( 'button', { name: 'Close' } );
	await welcome.waitFor( { state: 'visible', timeout: 3000 } ).catch( () => {} );
	if ( await welcome.isVisible() ) {
		await welcome.click();
	}
};

const updateWidgets = async ( page ) => {
	await pressWithMouse( page, page.getByRole( 'button', { name: 'Update', exact: true } ) );
};

const customizerIsClean = ( page ) => page.evaluate( () => (
	window.wp.customize.state( 'saved' ).get() &&
	Object.keys( window.wp.customize.dirtyValues() ).length === 0
) );

const widgetsScreenIsClean = async ( page ) => (
	await page.getByRole( 'button', { name: 'Update', exact: true } ).getAttribute( 'aria-disabled' ) === 'true'
);

const SCREENS = [
	{ name: 'Customizer', open: openCustomizer, save: publish, isClean: customizerIsClean },
	{ name: 'Widgets screen', open: openWidgetsScreen, save: updateWidgets, isClean: widgetsScreenIsClean },
];

const stylesheetChanges = async ( request, area ) => {
	const [ , count ] = ( await frontEndText( request, area ) ).match( /sowb-e2e-stylesheet-writes:(\d+)/ );

	return Number( count );
};

const expectStylesheetKept = async ( request, area, widgetText ) => {
	const html = await frontEndText( request, area );
	// The widget's wrapper class names its stylesheet.
	const widgetHtml = html.slice( 0, html.indexOf( widgetText ) );
	const [ , name ] = [ ...widgetHtml.matchAll( /so-widget-(sow-cta-[a-z]+-[a-f0-9]{12})/g ) ].pop();
	const stylesheet = html.match( new RegExp( `https?:[^'"\\s]+/${ name }\\.css` ) );
	expect( stylesheet, 'The page should link the widget stylesheet.' ).toBeTruthy();
	expect( ( await request.get( stylesheet[ 0 ] ) ).status() ).toBe( 200 );

	const before = await stylesheetChanges( request, area );
	await frontEndText( request, area );

	expect( await stylesheetChanges( request, area ) ).toBe( before );
};

const frontEndText = async ( request, area ) => {
	const response = await request.get( `${ area.post.link }?nocache=${ uniqueSuffix() }` );
	expect( response.status() ).toBe( 200 );

	return response.text();
};

let previousTheme;
let installedTheme = false;

test.beforeAll( async ( { browser } ) => {
	const requestUtils = await setupRequestUtils();
	[ previousTheme ] = await themeStylesheets( requestUtils, 'active' );
	installedTheme = await installClassicTheme( browser, requestUtils );
	await requestUtils.activateTheme( THEME_SLUG );
} );

test.afterAll( async ( { browser } ) => {
	const requestUtils = await setupRequestUtils();
	await requestUtils.activateTheme( previousTheme );

	// Never remove a copy this run did not install.
	if ( installedTheme ) {
		await removeClassicTheme( browser );
	}
} );

test.beforeEach( async ( { page } ) => {
	page.on( 'dialog', ( dialog ) => dialog.accept() );
} );

test( 'Saved widget blocks open and publish in the Customizer.', async ( { page, request } ) => {
	const suffix = uniqueSuffix();
	const editorText = `Customizer editor ${ suffix }`;
	const ctaTitle = `Customizer CTA ${ suffix }`;
	const editedTitle = `Customizer published CTA ${ suffix }`;
	const area = await seedWidgetArea( [ editorBlock( 'Editor title', editorText ), ctaBlock( ctaTitle ) ] );

	try {
		await doLogin( page );
		await openCustomizer( page, area );

		const editorForm = await openWidgetForm( page, EDITOR_BLOCK, editorText );
		await expect( editorForm.frameLocator( 'iframe' ).first().locator( 'body' ) ).toContainText( editorText );
		await expect( page.getByText( 'include support for' ) ).toHaveCount( 0 );

		const ctaForm = await openWidgetForm( page, CTA_BLOCK, ctaTitle );
		const title = widgetTitle( ctaForm );
		const previewed = page.waitForResponse( '**/sowb/v1/widgets/previews*' );
		await title.fill( editedTitle );
		await title.press( 'Tab' );
		await previewed;
		await publish( page );

		await expect.poll( () => frontEndText( request, area ), { timeout: 20000 } ).toContain( editedTitle );

		await openCustomizer( page, area );
		const reopened = await openWidgetForm( page, CTA_BLOCK, editedTitle );
		await expect( widgetTitle( reopened ) ).toHaveValue( editedTitle );
	} finally {
		await area.cleanup();
	}
} );

for ( const screen of SCREENS ) {
	test( `${ screen.name }: opening a saved block changes nothing.`, async ( { page } ) => {
		const suffix = uniqueSuffix();
		const text = `Clean open editor ${ suffix }`;
		const area = await seedWidgetArea( [ editorBlock( 'Editor title', text ) ] );

		try {
			await doLogin( page );
			const previewed = page.waitForResponse( '**/sowb/v1/widgets/previews*' );
			await screen.open( page, area );
			await previewed;

			const block = page.locator( `[data-type="${ EDITOR_BLOCK }"]` ).filter( { hasText: text } );
			await expect( block ).toBeVisible( { timeout: 20000 } );
			await expect.poll( () => screen.isClean( page ) ).toBe( true );
			// Hold the check long enough for a late attribute write to land.
			await page.waitForTimeout( 1000 );
			expect( await screen.isClean( page ) ).toBe( true );

			// Browsers only ask before leaving a page the user has interacted with.
			await block.click();
			expect( await page.evaluate( () => navigator.userActivation.hasBeenActive ) ).toBe( true );

			const dialogs = [];
			page.on( 'dialog', ( dialog ) => dialogs.push( dialog.type() ) );
			await soGoTo( page, 'wp-admin/index.php' );
			expect( dialogs ).toEqual( [] );
		} finally {
			await area.cleanup();
		}
	} );

	test( `${ screen.name }: a late preview cannot change a saved block.`, async ( { page } ) => {
		const suffix = uniqueSuffix();
		const title = `Late preview CTA ${ suffix }`;
		const editedTitle = `Late preview edited CTA ${ suffix }`;
		const area = await seedWidgetArea( [ ctaBlock( title ) ] );

		try {
			await doLogin( page );
			await screen.open( page, area );
			const form = await openWidgetForm( page, CTA_BLOCK, title );

			let releasePreview;
			const held = new Promise( ( resolve ) => {
				releasePreview = resolve;
			} );
			await page.route( '**/sowb/v1/widgets/previews*', async ( route ) => {
				await held;
				await route.continue().catch( () => {} );
			} );

			await widgetTitle( form ).fill( editedTitle );
			await widgetTitle( form ).press( 'Tab' );
			await page.waitForTimeout( 500 );
			await screen.save( page );
			await expect.poll( () => area.storedContent(), { timeout: 20000 } ).toContain( editedTitle );

			const previewed = page.waitForResponse( '**/sowb/v1/widgets/previews*' );
			releasePreview();
			expect( ( await previewed ).status() ).toBe( 200 );
			// The response handler runs after the response arrives.
			await page.waitForTimeout( 500 );

			expect( await screen.isClean( page ) ).toBe( true );
			expect( await area.storedContent() ).not.toMatch( /"widgetMarkup":"/ );
		} finally {
			await area.cleanup();
		}
	} );

	test( `${ screen.name }: a field edit reaches the front end on the first save.`, async ( { page, request } ) => {
		const suffix = uniqueSuffix();
		const title = `First save CTA ${ suffix }`;
		const editedTitle = `First save edited CTA ${ suffix }`;
		const cached = `Cached CTA ${ suffix }`;
		const area = await seedWidgetArea( [ ctaBlock( title, { widgetMarkup: `<div>${ cached }</div>` } ) ] );

		try {
			await doLogin( page );
			await screen.open( page, area );
			const form = await openWidgetForm( page, CTA_BLOCK, title );
			await widgetTitle( form ).fill( editedTitle );
			await screen.save( page );

			await expect.poll( () => frontEndText( request, area ), { timeout: 20000 } ).toContain( editedTitle );
			expect( await frontEndText( request, area ) ).not.toContain( cached );
			await expectStylesheetKept( request, area, editedTitle );
		} finally {
			await area.cleanup();
		}
	} );

	test( `${ screen.name }: a rich text edit reaches the front end on the first save.`, async ( { page, request } ) => {
		const suffix = uniqueSuffix();
		const text = `First save editor ${ suffix }`;
		const addition = `first save addition ${ suffix }`;
		const cached = `Cached editor ${ suffix }`;
		const area = await seedWidgetArea( [ editorBlock( 'Editor title', text, { widgetMarkup: `<div>${ cached }</div>` } ) ] );

		try {
			await doLogin( page );
			await screen.open( page, area );
			const form = await openWidgetForm( page, EDITOR_BLOCK, text );
			const body = form.frameLocator( 'iframe' ).first().locator( 'body' );
			await expect( body ).toContainText( text );
			await body.click();
			await page.evaluate( () => {
				const editor = window.tinymce.activeEditor;
				editor.selection.select( editor.getBody(), true );
				editor.selection.collapse( false );
			} );
			// Typed at a person's pace: the Customizer refocuses the frame shortly after the first key.
			await page.keyboard.type( ` ${ addition }`, { delay: 60 } );
			await screen.save( page );

			await expect.poll( () => frontEndText( request, area ), { timeout: 20000 } ).toContain( addition );
			const html = await frontEndText( request, area );
			expect( html ).toContain( text );
			expect( html ).not.toContain( cached );
			// An edit keeps the plain title that cached markup prints.
			expect( html ).toContain( 'Editor title' );
			expect( html ).not.toContain( '<h3 class="widget-title">Editor title' );

			await screen.open( page, area );
			const reopened = await openWidgetForm( page, EDITOR_BLOCK, addition );
			await expect( reopened.frameLocator( 'iframe' ).first().locator( 'body' ) ).toContainText( `${ text } ${ addition }` );
		} finally {
			await area.cleanup();
		}
	} );
}
