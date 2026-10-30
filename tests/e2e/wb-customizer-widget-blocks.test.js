const {
	expect,
	test,
} = require( '@playwright/test' );

const {
	doLogin,
	setupRequestUtils,
	soGoTo,
} = require( 'siteorigin-tests-common/playwright/common' );

const EDITOR_BLOCK = 'sowb/siteorigin-widget-editor-widget';
const CTA_BLOCK = 'sowb/siteorigin-widget-cta-widget';

const uniqueSuffix = () => `${ Date.now() }-${ Math.floor( Math.random() * 1e6 ) }`;

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
	expect( sidebar, 'These tests need a classic theme with a widget area.' ).toBeTruthy();

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
	// The narrow Customizer panel can place the toolbar under its sticky header.
	await page.getByRole( 'button', { name: 'Edit widget.', exact: true } ).click( { force: true } );
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
	await page.mouse.click( box.x + ( box.width / 2 ), box.y + ( box.height / 2 ) );
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

const SCREENS = [
	{ name: 'Customizer', open: openCustomizer, save: publish },
	{ name: 'Widgets screen', open: openWidgetsScreen, save: updateWidgets },
];

const expectStylesheetKept = async ( page, request, area ) => {
	const html = await frontEndText( request, area );
	const stylesheet = html.match( /https?:[^'"\s]+\/sow-cta-[^'"\s]+\.css/ );
	expect( stylesheet, 'The page should link the widget stylesheet.' ).toBeTruthy();

	const modified = async () => {
		const response = await request.get( stylesheet[ 0 ] );
		expect( response.status() ).toBe( 200 );

		return response.headers()[ 'last-modified' ];
	};
	const before = await modified();
	expect( before ).toBeTruthy();

	// File times have a one second resolution.
	await page.waitForTimeout( 1100 );
	await frontEndText( request, area );

	expect( await modified() ).toBe( before );
};

const frontEndText = async ( request, area ) => {
	const response = await request.get( `${ area.post.link }?nocache=${ uniqueSuffix() }` );
	expect( response.status() ).toBe( 200 );

	return response.text();
};

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
			await expectStylesheetKept( page, request, area );
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
			await page.keyboard.press( 'ControlOrMeta+End' );
			await page.keyboard.type( ` ${ addition }` );
			await screen.save( page );

			await expect.poll( () => frontEndText( request, area ), { timeout: 20000 } ).toContain( addition );
			expect( await frontEndText( request, area ) ).not.toContain( cached );
		} finally {
			await area.cleanup();
		}
	} );
}
