/**
 * TinyMCE toolbar rows in the SiteOrigin Editor, in each place its form opens.
 *
 * Advanced Editor Tools sets wordpress_adv_hidden to false when its first row
 * has no Toolbar Toggle, so the second and later rows stay visible. The
 * sowb-e2e-probe fixture reproduces that without the plugin, in three modes:
 * - off: the field's own toolbars, with the Toolbar Toggle in row 1.
 * - no-toggle: three rows, no Toolbar Toggle, wordpress_adv_hidden false.
 * - toggle-kept: three rows, the Toolbar Toggle kept, wordpress_adv_hidden false.
 *
 * The mode is a site-wide option and so is the user's hidetb setting, so this
 * file runs in the serialized abilities project. Each test sets hidetb to '0'
 * first and checks the effective editor settings before the rows, so a site
 * with another TinyMCE plugin active fails clearly instead of passing wrongly.
 */
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const {
	expect,
	test,
} = require( '@playwright/test' );

const {
	neutralizeSiteEditorStickyHeader,
	openSiteEditorCanvas,
	setupAdminE2E,
	setupRequestUtils,
	soGoTo,
} = require( 'siteorigin-tests-common/playwright/common' );

const {
	switchWidgetMode,
} = require( 'siteorigin-tests-common/playwright/utilities/widgets-bundle' );

const {
	WIDGETS_ADMIN,
	call,
	installFixture,
	isCardActive,
	login,
	loginPage,
	makeZip,
	removeFixture,
	setWidgetActive,
} = require( './helpers/widget-block-abilities' );

test.describe.configure( { mode: 'serial' } );

const EDITOR_BLOCK = 'sowb/siteorigin-widget-editor-widget';
const CLASSIC_THEME = 'sowb-e2e-classic-theme';
const CLASSIC_THEME_DIR = path.resolve( __dirname, 'fixtures', CLASSIC_THEME );
const BLOCK_THEME_FALLBACK = 'twentytwentyfive';
const MODES = [ 'off', 'no-toggle', 'toggle-kept' ];

let admin;
let requestUtils;
let startingHidetb;
let startingTheme;
let installedClassicTheme = false;
let editorWasInactive = false;

const uniqueSuffix = () => `${ Date.now() }-${ Math.floor( Math.random() * 1e6 ) }`;

const setMode = async ( mode ) => {
	const set = await call( admin, 'POST', 'wp-json/sowb-e2e/v1/tinymce-rows', { mode } );
	expect( set.status ).toBe( 200 );
	const get = await call( admin, 'GET', 'wp-json/sowb-e2e/v1/tinymce-rows' );
	expect( get.body.mode, 'the toolbar rows mode must be set' ).toBe( mode );
};

/**
 * Read hidetb on an admin page, where WordPress's user settings script runs.
 */
const readHidetb = async ( page ) => {
	await soGoTo( page, 'wp-admin/index.php' );
	await page.waitForFunction( () => typeof window.getUserSetting === 'function' );

	return page.evaluate( () => {
		const all = window.getAllUserSettings ? window.getAllUserSettings() : {};

		return Object.prototype.hasOwnProperty.call( all, 'hidetb' ) ? all.hidetb : null;
	} );
};

const resetHidetb = async ( page ) => {
	await soGoTo( page, 'wp-admin/index.php' );
	await page.waitForFunction( () => typeof window.setUserSetting === 'function' );
	await page.evaluate( () => window.setUserSetting( 'hidetb', '0' ) );
	expect( await page.evaluate( () => window.getUserSetting( 'hidetb' ) ) ).toBe( '0' );
};

/**
 * The editor in a TinyMCE field, read in the field's own window so iframe
 * canvases work: its settings, and which toolbar rows are visible.
 */
const editorState = ( field ) => field.evaluate( ( element ) => {
	const textarea = element.querySelector( 'textarea.wp-editor-area' );
	const win = element.ownerDocument.defaultView;
	const editor = textarea && win.tinymce ? win.tinymce.get( textarea.id ) : null;

	return {
		initialized: !! ( editor && editor.initialized ),
		advHidden: editor && Object.prototype.hasOwnProperty.call( editor.settings, 'wordpress_adv_hidden' ) ?
			editor.settings.wordpress_adv_hidden :
			'absent',
		toolbar1: editor ? String( editor.settings.toolbar1 || '' ) : '',
		rows: Array.from( element.querySelectorAll( '.mce-toolbar-grp .mce-toolbar' ) )
			.map( ( row ) => row.offsetParent !== null ),
	};
} );

const waitForEditor = async ( field ) => {
	await expect.poll( async () => ( await editorState( field ) ).initialized, { timeout: 20000 } ).toBe( true );
};

// The Toolbar Toggle's label differs by screen ("Toolbar Toggle", "Extended
// Toolbar"), so find it by its icon.
const clickToggle = async ( field ) => {
	await field.locator( '.mce-btn:has(.mce-i-wp_adv)' ).first().click();
};

/**
 * Check the effective settings for the mode, then the rows, then the toggle.
 */
const assertRows = async ( field, mode ) => {
	await waitForEditor( field );
	const state = await editorState( field );

	if ( mode === 'off' ) {
		expect( state.advHidden, 'no plugin may set wordpress_adv_hidden in the off mode' ).toBe( 'absent' );
		expect( state.toolbar1 ).toContain( 'wp_adv' );
		expect( state.rows ).toEqual( [ true, false ] );

		await clickToggle( field );
		await expect.poll( async () => ( await editorState( field ) ).rows ).toEqual( [ true, true ] );
		await clickToggle( field );
		await expect.poll( async () => ( await editorState( field ) ).rows ).toEqual( [ true, false ] );

		return;
	}

	expect( state.advHidden ).toBe( false );

	if ( mode === 'no-toggle' ) {
		expect( state.toolbar1 ).not.toContain( 'wp_adv' );
		expect( state.rows ).toEqual( [ true, true, true ] );

		return;
	}

	expect( state.toolbar1 ).toContain( 'wp_adv' );
	expect( state.rows ).toEqual( [ true, true, true ] );

	await clickToggle( field );
	await expect.poll( async () => ( await editorState( field ) ).rows ).toEqual( [ true, false, false ] );
	await clickToggle( field );
	await expect.poll( async () => ( await editorState( field ) ).rows ).toEqual( [ true, true, true ] );
};

const tinymceField = ( scope ) => scope.locator( '.siteorigin-widget-field-type-tinymce' ).first();

const activeTheme = async () => {
	const [ theme ] = await requestUtils.rest( {
		method: 'GET',
		path: '/wp/v2/themes',
		params: { status: 'active' },
	} );

	return theme;
};

const installClassicTheme = async ( browser ) => {
	const themes = await requestUtils.rest( { method: 'GET', path: '/wp/v2/themes' } );

	if ( themes.some( ( theme ) => theme.stylesheet === CLASSIC_THEME ) ) {
		return false;
	}

	const entries = fs.readdirSync( CLASSIC_THEME_DIR ).map( ( file ) => ( {
		name: `${ CLASSIC_THEME }/${ file }`,
		data: fs.readFileSync( path.join( CLASSIC_THEME_DIR, file ) ),
	} ) );
	const tempDir = fs.mkdtempSync( path.join( os.tmpdir(), 'sowb-e2e-' ) );
	const zipPath = path.join( tempDir, `${ CLASSIC_THEME }.zip` );
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
			.find( ( theme ) => theme.id === slug )?.actions.delete, CLASSIC_THEME );

		if ( deleteLink ) {
			await page.goto( deleteLink.replace( /&amp;/g, '&' ) );
		}
	} finally {
		await page.close();
	}
};

const editorBlockMarkup = ( marker ) => {
	const attributes = JSON.stringify( {
		widgetClass: 'SiteOrigin_Widget_Editor_Widget',
		widgetData: {
			title: marker,
			text: `<p>${ marker }</p>`,
			autop: true,
			text_selected_editor: 'tinymce',
		},
	} )
		.replace( /--/g, '\\u002d\\u002d' )
		.replace( /</g, '\\u003c' )
		.replace( />/g, '\\u003e' )
		.replace( /&/g, '\\u0026' );

	return `<!-- wp:${ EDITOR_BLOCK } ${ attributes } /-->`;
};

/**
 * Put an Editor block widget in the first active widget area.
 */
const seedWidgetArea = async ( marker ) => {
	const sidebars = await requestUtils.rest( {
		method: 'GET',
		path: '/wp/v2/sidebars',
		params: { context: 'edit' },
	} );
	const sidebar = sidebars.find( ( item ) => item.status === 'active' );
	expect( sidebar, 'the classic theme fixture must provide a widget area' ).toBeTruthy();

	const post = await requestUtils.createPost( {
		status: 'publish',
		title: `Toolbar rows ${ marker }`,
		content: '<!-- wp:paragraph --><p>Widget area page.</p><!-- /wp:paragraph -->',
	} );
	const widget = await requestUtils.rest( {
		method: 'POST',
		path: '/wp/v2/widgets',
		data: {
			id_base: 'block',
			sidebar: sidebar.id,
			instance: { raw: { content: editorBlockMarkup( marker ) } },
		},
	} );
	await requestUtils.rest( {
		method: 'POST',
		path: `/wp/v2/sidebars/${ sidebar.id }`,
		data: { widgets: [ ...sidebar.widgets, widget.id ] },
	} );

	return {
		post,
		sidebar,
		cleanup: async () => {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/widgets/${ widget.id }`,
				params: { force: true },
			} );
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

const openBlockWidgetForm = async ( page, marker ) => {
	let clientId;
	await expect.poll( async () => {
		clientId = await page.evaluate( ( { name, content } ) => {
			const select = window.wp.data.select( 'core/block-editor' );

			return select.getClientIdsWithDescendants().find( ( id ) => select.getBlockName( id ) === name &&
				JSON.stringify( select.getBlockAttributes( id ).widgetData || {} ).includes( content ) );
		}, { name: EDITOR_BLOCK, content: marker } );

		return !! clientId;
	}, { timeout: 20000 } ).toBe( true );

	await page.evaluate( ( id ) => window.wp.data.dispatch( 'core/block-editor' ).selectBlock( id ), clientId );
	await page.getByRole( 'button', { name: 'Edit widget.', exact: true } ).click();
	const form = page.locator( `[data-block="${ clientId }"] .siteorigin-widget-form-main[data-class]` );
	await expect( form ).toBeVisible( { timeout: 20000 } );

	return form;
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

const openCustomizer = async ( page, area ) => {
	const query = new URLSearchParams( {
		url: area.post.link,
		'autofocus[section]': `sidebar-widgets-${ area.sidebar.id }`,
	} );

	await soGoTo( page, `wp-admin/customize.php?${ query }` );
	await expect( page.locator( '.customize-control-sidebar_block_editor' ).first() ).toBeAttached( { timeout: 20000 } );
	const welcome = page.getByRole( 'button', { name: 'Got it', exact: true } );
	await welcome.waitFor( { state: 'visible', timeout: 5000 } ).catch( () => {} );

	if ( await welcome.isVisible() ) {
		await welcome.click();
	}
};

/**
 * A draft Page Builder page with one SiteOrigin Editor widget, saved and
 * reloaded so the widget's form scripts are enqueued.
 */
const createPageBuilderPage = async ( page ) => {
	await soGoTo( page, 'wp-admin/post-new.php?post_type=page' );
	await page.locator( '#content-panels' ).click();
	const builder = page.locator( '#siteorigin-panels-metabox' );
	await expect( builder ).toBeVisible( { timeout: 20000 } );

	await builder.locator( '.so-builder-toolbar .so-widget-add' ).click();
	const addDialog = page.locator( '.so-panels-dialog-add-widget' );
	await addDialog.locator( '.so-sidebar-search' ).fill( 'SiteOrigin Editor' );
	await addDialog.locator( '.widget-type', { hasText: 'SiteOrigin Editor' } ).first().click();

	const done = page.locator( '.so-panels-dialog-edit-widget' ).getByRole( 'button', { name: 'Done', exact: true } );
	await expect( done ).toBeVisible( { timeout: 20000 } );
	await done.click();
	await expect( done ).toBeHidden();

	await page.locator( '#title' ).fill( `Toolbar rows ${ uniqueSuffix() }` );
	const navigated = page.waitForEvent( 'framenavigated', ( frame ) => frame === page.mainFrame() );
	await page.locator( '#save-post' ).click();
	await navigated;
	await page.waitForLoadState();

	return Number( new URL( page.url() ).searchParams.get( 'post' ) );
};

const openPageBuilderEditorWidget = async ( page ) => {
	const builder = page.locator( '#siteorigin-panels-metabox' );
	await expect( builder ).toBeVisible( { timeout: 20000 } );
	await builder.locator( '.so-widget .title h4' ).first().click();
	// Page Builder keeps closed dialogs in the page, hidden, so use the open one.
	const form = page.locator( '.so-panels-dialog-edit-widget .siteorigin-widget-form-main' ).filter( { visible: true } );
	await expect( form ).toBeVisible( { timeout: 20000 } );

	return form;
};

const closePageBuilderDialog = async ( page ) => {
	const done = page.locator( '.so-panels-dialog-edit-widget' ).getByRole( 'button', { name: 'Done', exact: true } ).filter( { visible: true } );
	await done.click();
	await expect( done ).toBeHidden();
};

test.beforeAll( async ( { browser } ) => {
	test.setTimeout( 240_000 );
	requestUtils = await setupRequestUtils();
	await installFixture( browser, requestUtils );
	admin = await login( process.env.WP_USERNAME, process.env.WP_PASSWORD );
	startingTheme = ( await activeTheme() ).stylesheet;

	const page = await browser.newPage();

	try {
		await loginPage( page );
		startingHidetb = await readHidetb( page );
		await soGoTo( page, WIDGETS_ADMIN );

		if ( ! await isCardActive( page, 'editor' ) ) {
			editorWasInactive = true;
			await setWidgetActive( page, 'editor', true );
		}
	} finally {
		await page.close();
	}
} );

test.afterAll( async ( { browser } ) => {
	await setMode( 'off' ).catch( () => {} );
	await requestUtils.activateTheme( startingTheme );

	if ( installedClassicTheme ) {
		await removeClassicTheme( browser );
	}

	const page = await browser.newPage();

	try {
		await loginPage( page );
		await soGoTo( page, 'wp-admin/index.php' );
		await page.waitForFunction( () => typeof window.setUserSetting === 'function' );

		if ( startingHidetb === null ) {
			await page.evaluate( () => window.deleteUserSetting( 'hidetb' ) );
		} else {
			await page.evaluate( ( value ) => window.setUserSetting( 'hidetb', value ), startingHidetb );
		}

		if ( editorWasInactive ) {
			await soGoTo( page, WIDGETS_ADMIN );
			await setWidgetActive( page, 'editor', false );
		}
	} finally {
		await page.close();
	}

	await removeFixture( requestUtils );
} );

test.describe( 'Block theme contexts', () => {
	test.beforeAll( async () => {
		if ( ! ( await activeTheme() ).is_block_theme ) {
			await requestUtils.activateTheme( BLOCK_THEME_FALLBACK );
		}

		expect( ( await activeTheme() ).is_block_theme, 'these contexts need a block theme' ).toBe( true );
	} );

	for ( const mode of MODES ) {
		test( `Editor block in the post editor, ${ mode }`, async ( { page } ) => {
			await setMode( mode );
			await loginPage( page );
			await resetHidetb( page );

			// A widget block already in the post, so its form scripts load with the
			// editor. A newly inserted widget asks for a save and reload first.
			const marker = `Rows post ${ uniqueSuffix() }`;
			const post = await requestUtils.createPost( {
				status: 'draft',
				title: `Toolbar rows ${ mode }`,
				content: editorBlockMarkup( marker ),
			} );

			try {
				const e2e = await setupAdminE2E( page );
				await e2e.editPost( post.id );
				const widget = e2e.editor.canvas.locator( `.wp-block[data-type="${ EDITOR_BLOCK }"]` ).first();
				await expect( widget ).toBeVisible( { timeout: 20000 } );
				await e2e.editor.selectBlocks( widget );

				const form = widget.locator( '.siteorigin-widget-form-main[data-class]' );

				if ( ! await form.isVisible().catch( () => false ) ) {
					const formLoaded = page.waitForResponse(
						( response ) => response.url().includes( '/wp-json/sowb/v1/widgets/forms' ) && response.status() === 200,
						{ timeout: 20000 }
					);
					await switchWidgetMode( e2e, widget, 'edit' );
					await formLoaded;
				}

				await expect( form ).toBeVisible( { timeout: 20000 } );
				await assertRows( tinymceField( widget ), mode );
			} finally {
				await requestUtils.rest( { method: 'DELETE', path: `/wp/v2/posts/${ post.id }`, params: { force: true } } );
			}
		} );

		test( `Site editor Editor block, ${ mode }`, async ( { page } ) => {
			await setMode( mode );
			await loginPage( page );
			await resetHidetb( page );

			const e2e = await setupAdminE2E( page );
			await openSiteEditorCanvas( page, e2e );
			await neutralizeSiteEditorStickyHeader( e2e );

			await expect.poll( () => page.evaluate( ( name ) => !! window.wp?.blocks?.getBlockType( name ), EDITOR_BLOCK ), { timeout: 20000 } ).toBe( true );
			const formLoaded = page.waitForResponse(
				( response ) => response.url().includes( '/wp-json/sowb/v1/widgets/forms' ) && response.status() === 200,
				{ timeout: 20000 }
			);
			await e2e.editor.insertBlock( { name: EDITOR_BLOCK } );
			await formLoaded;

			const widget = e2e.editor.canvas.locator( `.wp-block[data-type="${ EDITOR_BLOCK }"]` ).first();
			await expect( widget ).toBeVisible( { timeout: 20000 } );
			await assertRows( tinymceField( widget ), mode );
			// The inserted block is never saved: the site editor template is left as it was.
		} );
	}
} );

test.describe( 'Classic theme contexts', () => {
	test.beforeAll( async ( { browser } ) => {
		installedClassicTheme = await installClassicTheme( browser );
		await requestUtils.activateTheme( CLASSIC_THEME );
	} );

	for ( const mode of MODES ) {
		test( `Page Builder classic tab, ${ mode }`, async ( { page } ) => {
			await setMode( mode );
			await loginPage( page );
			await resetHidetb( page );
			page.on( 'dialog', ( dialog ) => dialog.accept() );

			const postId = await createPageBuilderPage( page );

			try {
				const form = await openPageBuilderEditorWidget( page );
				await assertRows( tinymceField( form ), mode );
				await closePageBuilderDialog( page );
			} finally {
				await requestUtils.rest( { method: 'DELETE', path: `/wp/v2/pages/${ postId }`, params: { force: true } } );
			}
		} );

		test( `Block Widgets screen, ${ mode }`, async ( { page } ) => {
			await setMode( mode );
			await loginPage( page );
			await resetHidetb( page );
			page.on( 'dialog', ( dialog ) => dialog.accept() );

			const marker = `Rows widgets ${ uniqueSuffix() }`;
			const area = await seedWidgetArea( marker );

			try {
				await openWidgetsScreen( page );
				const form = await openBlockWidgetForm( page, marker );
				await assertRows( tinymceField( form ), mode );
			} finally {
				await area.cleanup();
			}
		} );

		test( `Customizer block widgets, ${ mode }`, async ( { page } ) => {
			await setMode( mode );
			await loginPage( page );
			await resetHidetb( page );
			page.on( 'dialog', ( dialog ) => dialog.accept() );

			const marker = `Rows customizer ${ uniqueSuffix() }`;
			const area = await seedWidgetArea( marker );

			try {
				await openCustomizer( page, area );
				const form = await openBlockWidgetForm( page, marker );
				await assertRows( tinymceField( form ), mode );
			} finally {
				await area.cleanup();
			}
		} );
	}
} );
