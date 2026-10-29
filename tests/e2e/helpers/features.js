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
 * Where features.setup.js lists the themes it installed, for features.teardown.js.
 */
const INSTALLED_THEMES_FILE = path.resolve( process.cwd(), 'tests/cache/features-installed-themes.json' );

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
 * never activates it. Returns true when this call installed it.
 */
const ensureTheme = async ( requestUtils, slug ) => {
	if ( await themeInstalled( requestUtils, slug ) ) {
		return false;
	}

	const result = await themeAjax( requestUtils, 'install-theme', slug );

	if ( ! await themeInstalled( requestUtils, slug ) ) {
		throw new Error( `Installing ${ slug } failed: ${ JSON.stringify( result ) }` );
	}

	return true;
};

const deleteTheme = async ( requestUtils, slug ) => {
	const result = await themeAjax( requestUtils, 'delete-theme', slug );

	if ( await themeInstalled( requestUtils, slug ) ) {
		throw new Error( `Deleting ${ slug } failed: ${ JSON.stringify( result ) }` );
	}
};

module.exports = {
	INSTALLED_THEMES_FILE,
	THEMES,
	deleteTheme,
	ensureTheme,
	expectNear,
	featuresBlock,
	openFeaturesPage,
	useFeaturesPage,
};
