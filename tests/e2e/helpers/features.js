const fs = require( 'fs' );
const path = require( 'path' );

const { expect } = require( '@playwright/test' );

const { setupRequestUtils } = require( 'siteorigin-tests-common/playwright/common' );

const { widgetBlock } = require( './widget-block-abilities' );

/**
 * The themes the Features specs render each page in. The first is the
 * active theme. The others render per request through core's block theme
 * preview (`?wp_theme_preview=`), which needs a user who can switch themes,
 * so no site-wide state changes. A missing preview theme silently renders
 * the active theme, so each visit checks the theme's body class.
 */
const THEMES = [
	{ name: 'active theme' },
	{ name: 'Twenty Twenty-Five', preview: 'twentytwentyfive' },
];

/**
 * Where features.setup.js lists the themes it installs, per site, for
 * features.teardown.js: `{ "<site URL>": [ "<slug>" ] }`.
 */
const INSTALLED_THEMES_FILE = path.resolve( process.cwd(), 'tests/cache/features-installed-themes.json' );

/**
 * An unreadable list reads as empty, with a warning: a theme it named is
 * left installed rather than removed on a guess.
 */
const readInstalledThemes = () => {
	if ( ! fs.existsSync( INSTALLED_THEMES_FILE ) ) {
		return {};
	}

	try {
		const installed = JSON.parse( fs.readFileSync( INSTALLED_THEMES_FILE, 'utf8' ) );

		if ( installed && typeof installed === 'object' && ! Array.isArray( installed ) ) {
			return installed;
		}
	} catch ( error ) {
		// Reported below.
	}

	console.warn( `Ignoring an unreadable ${ INSTALLED_THEMES_FILE }` );

	return {};
};

// Written to a temporary file and renamed, so a reader never sees a partial list.
const writeInstalledThemes = ( installed ) => {
	fs.mkdirSync( path.dirname( INSTALLED_THEMES_FILE ), { recursive: true } );

	if ( Object.keys( installed ).length ) {
		fs.writeFileSync( `${ INSTALLED_THEMES_FILE }.tmp`, JSON.stringify( installed ) );
		fs.renameSync( `${ INSTALLED_THEMES_FILE }.tmp`, INSTALLED_THEMES_FILE );
	} else if ( fs.existsSync( INSTALLED_THEMES_FILE ) ) {
		fs.unlinkSync( INSTALLED_THEMES_FILE );
	}
};

const featuresBlock = ( widgetData ) => widgetBlock( 'sowb/siteorigin-widget-features-widget', {
	widgetClass: 'SiteOrigin_Widget_Features_Widget',
	widgetData,
} );

const expectNear = ( value, expected, message ) => {
	expect.soft( value, message ).toBeGreaterThanOrEqual( expected - 1 );
	expect.soft( value, message ).toBeLessThanOrEqual( expected + 1 );
};

/**
 * Publishes a page before the describe block's tests and deletes it after.
 * `content` returns the page content, and may be async; it receives the
 * request utils, for fixtures such as an uploaded image. The returned object
 * holds `id`, `link` and `requestUtils` once `beforeAll` has run.
 */
const useFeaturesPage = ( test, title, content ) => {
	const fixture = {};

	test.beforeAll( async () => {
		fixture.requestUtils = await setupRequestUtils();

		const created = await fixture.requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/pages',
			data: {
				title,
				status: 'publish',
				content: await content( fixture.requestUtils ),
			},
		} );

		fixture.id = created.id;
		fixture.link = created.link;
	} );

	test.afterAll( async () => {
		if ( fixture.id ) {
			const deleted = await fixture.requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/pages/${ fixture.id }`,
				params: { force: true },
			} );

			expect( deleted.deleted ).toBe( true );
		}
	} );

	return fixture;
};

const withPreview = ( link, theme ) => {
	if ( ! theme.preview ) {
		return link;
	}

	const url = new URL( link );
	url.searchParams.set( 'wp_theme_preview', theme.preview );

	return url.toString();
};

/**
 * Opens `link` in a fresh context at the given width and theme. A preview
 * theme needs the login of the request utils; the active theme is viewed
 * logged out, with no admin bar. `beforeLoad` receives the page before it
 * loads, to route requests. The caller closes `page.context()`.
 */
const openFeaturesPage = async ( browser, fixture, { width, theme = THEMES[ 0 ], height = 900, beforeLoad } ) => {
	const context = await browser.newContext( {
		// As the shared config's `use`, which a new context does not inherit.
		ignoreHTTPSErrors: true,
		viewport: { width, height },
		storageState: theme.preview ? await fixture.requestUtils.request.storageState() : undefined,
	} );
	const page = await context.newPage();

	if ( beforeLoad ) {
		await beforeLoad( page );
	}

	await page.goto( withPreview( fixture.link, theme ) );

	if ( theme.preview ) {
		await expect( page.locator( 'body' ), `${ theme.name } renders` ).toHaveClass( new RegExp( `\\bwp-theme-${ theme.preview }\\b` ) );
	}

	return page;
};

/**
 * Reads the `updates` nonce that core's theme install and delete AJAX
 * actions check, from the page that installs themes.
 */
const updatesNonce = async ( requestUtils ) => {
	const response = await requestUtils.request.get( '/wp-admin/theme-install.php' );
	const nonce = ( await response.text() ).match( /"ajax_nonce":"([a-z0-9]+)"/ );

	if ( ! nonce ) {
		throw new Error( `No updates nonce on theme-install.php (status ${ response.status() })` );
	}

	return nonce[ 1 ];
};

const themeInstalled = async ( requestUtils, slug ) => {
	const themes = await requestUtils.rest( { path: '/wp/v2/themes', params: { context: 'edit' } } );

	return themes.some( ( theme ) => theme.stylesheet === slug );
};

const themeAjax = async ( requestUtils, action, slug ) => {
	const response = await requestUtils.request.post( '/wp-admin/admin-ajax.php', {
		form: {
			action,
			slug,
			_ajax_nonce: await updatesNonce( requestUtils ),
		},
	} );

	return response.json();
};

/**
 * Installs a theme the way Themes > Add New does, when it is missing. It
 * never activates it. `installing` runs before the install starts, so the
 * caller can record it even if the install's outcome is unknown.
 */
const ensureTheme = async ( requestUtils, slug, installing = () => {} ) => {
	if ( await themeInstalled( requestUtils, slug ) ) {
		return;
	}

	installing( slug );

	const result = await themeAjax( requestUtils, 'install-theme', slug );

	if ( ! await themeInstalled( requestUtils, slug ) ) {
		throw new Error( `Installing ${ slug } failed: ${ JSON.stringify( result ) }` );
	}
};

/**
 * Deletes a theme when it is installed.
 */
const deleteTheme = async ( requestUtils, slug ) => {
	if ( ! await themeInstalled( requestUtils, slug ) ) {
		return;
	}

	const result = await themeAjax( requestUtils, 'delete-theme', slug );

	if ( await themeInstalled( requestUtils, slug ) ) {
		throw new Error( `Deleting ${ slug } failed: ${ JSON.stringify( result ) }` );
	}
};

module.exports = {
	THEMES,
	deleteTheme,
	ensureTheme,
	expectNear,
	featuresBlock,
	openFeaturesPage,
	readInstalledThemes,
	useFeaturesPage,
	writeInstalledThemes,
};
