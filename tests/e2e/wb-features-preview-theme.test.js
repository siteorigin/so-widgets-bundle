const {
	expect,
	test
} = require( '@playwright/test' );

const {
	THEMES,
	isThemeRendered,
	openFeaturesPage,
	useFeaturesPage,
} = require( './helpers/features' );

/**
 * Checks the check the themed specs rely on: a preview theme that isn't
 * installed renders the active theme without an error, so each themed visit
 * must tell the two apart, even on a page that names the theme's folder.
 */

const MISSING = { name: 'A theme that is not installed', preview: 'sowb-not-installed' };
const BLOCK_THEME = THEMES.find( ( theme ) => theme.preview );

test.describe( 'Features preview theme check', () => {
	const fixture = useFeaturesPage(
		test,
		'Features preview theme check (e2e)',
		() => `<!-- wp:paragraph --><p>/themes/${ MISSING.preview }/style.css and /themes/${ BLOCK_THEME.preview }/style.css</p><!-- /wp:paragraph --><!-- wp:image --><figure class="wp-block-image"><img src="/wp-content/themes/${ MISSING.preview }/screenshot.png" alt=""/></figure><!-- /wp:image -->`
	);

	test( 'a preview theme that is not installed is refused, though the page names its folder', async ( { browser } ) => {
		await expect(
			openFeaturesPage( browser, fixture, { width: 1280, theme: MISSING } )
		).rejects.toThrow( /it must be installed on the test site/ );
	} );

	test( 'the active theme is not taken for the block theme, though the page names its folder', async ( { browser } ) => {
		const page = await openFeaturesPage( browser, fixture, { width: 1280 } );

		expect( await page.content() ).toContain( `/themes/${ BLOCK_THEME.preview }/` );
		expect( await isThemeRendered( page, BLOCK_THEME.preview ) ).toBe( false );

		await page.context().close();
	} );

	test( `${ BLOCK_THEME.name } is recognised when it is previewed`, async ( { browser } ) => {
		const page = await openFeaturesPage( browser, fixture, { width: 1280, theme: BLOCK_THEME } );

		expect( await isThemeRendered( page, BLOCK_THEME.preview ) ).toBe( true );
		expect( await isThemeRendered( page, MISSING.preview ) ).toBe( false );

		await page.context().close();
	} );
} );
