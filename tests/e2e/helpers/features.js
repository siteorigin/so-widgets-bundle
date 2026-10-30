const { expect } = require( '@playwright/test' );

const { setupRequestUtils } = require( 'siteorigin-tests-common/playwright/common' );

const { widgetBlock } = require( './widget-block-abilities' );

/**
 * The themes the Features specs render each page in. The first is the
 * active theme. The others render per request through core's block theme
 * preview (`?wp_theme_preview=`), which needs a user who can switch themes,
 * so no site-wide state changes. A preview theme must be installed on the
 * test site; Twenty Twenty-Five ships with WordPress, so Playground has it.
 * A missing preview theme silently renders the active theme, so each visit
 * checks the theme's body class.
 */
const THEMES = [
	{ name: 'active theme' },
	{ name: 'Twenty Twenty-Five', preview: 'twentytwentyfive' },
];

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
		await expect( page.locator( 'body' ), `${ theme.name } renders; it must be installed on the test site` ).toHaveClass( new RegExp( `\\bwp-theme-${ theme.preview }\\b` ) );
	}

	return page;
};

module.exports = {
	THEMES,
	expectNear,
	featuresBlock,
	openFeaturesPage,
	useFeaturesPage,
};
