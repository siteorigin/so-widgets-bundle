const {
	expect,
	test
} = require( '@playwright/test' );

const {
	doLogin,
	setupRequestUtils,
} = require( 'siteorigin-tests-common/playwright/common' );

const { widgetBlock } = require( './helpers/widget-block-abilities' );

/**
 * Measures a ticked widget form checkbox in the block editor. The form sits in
 * the editor canvas with the theme's editor styles, so each theme is opened
 * through core's theme preview and checked before the checkbox is measured: a
 * theme that isn't installed silently renders the active theme.
 */

const THEMES = [
	{
		// Styles and centres a ticked checkbox's tick in its editor styles.
		name: 'Snapshot',
		preview: 'siteorigin-snapshot',
		rendered: ( { snapshotStyles } ) => snapshotStyles,
	},
	{
		// Leaves the checkbox to core.
		name: 'Twenty Twenty-Five',
		preview: 'twentytwentyfive',
		rendered: ( { snapshotStyles, manropePreset } ) => ! snapshotStyles && manropePreset,
	},
];

const CHECKBOX = '.siteorigin-widget-field-equalize_row_heights input[type="checkbox"]';

/**
 * Runs in the editor canvas: the theme that reached it, and the computed
 * styles of the box and its tick.
 */
const measure = ( selector ) => {
	const input = document.querySelector( selector );
	const box = getComputedStyle( input );
	const tick = getComputedStyle( input, '::before' );

	return {
		theme: {
			snapshotStyles: !! document.querySelector( 'link[href*="/themes/siteorigin-snapshot/"]' ),
			manropePreset: !! getComputedStyle( document.body ).getPropertyValue( '--wp--preset--font-family--manrope' ),
		},
		box: {
			appearance: box.appearance,
			background: box.backgroundColor,
			border: box.borderTopColor,
			width: box.width,
			height: box.height,
		},
		tick: {
			position: tick.position,
			float: tick.float,
			transform: tick.transform,
			marginTop: tick.marginTop,
			marginLeft: tick.marginLeft,
			width: tick.width,
			height: tick.height,
		},
	};
};

test.describe( 'Widget form checkbox in the block editor', () => {
	let requestUtils;
	let pageId;

	test.beforeAll( async () => {
		requestUtils = await setupRequestUtils();

		const created = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/pages',
			data: {
				title: 'Widget form checkbox (e2e)',
				status: 'publish',
				content: widgetBlock( 'sowb/siteorigin-widget-pricetable-widget', {
					widgetClass: 'SiteOrigin_Widget_PriceTable_Widget',
					widgetData: {
						columns: [ { title: 'One', price: '1' } ],
						equalize_row_heights: true,
					},
				} ),
			},
		} );

		pageId = created.id;
	} );

	test.afterAll( async () => {
		if ( pageId ) {
			const deleted = await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/pages/${ pageId }`,
				params: { force: true },
			} );

			expect( deleted.deleted ).toBe( true );
		}
	} );

	for ( const theme of THEMES ) {
		test( `a ticked checkbox is a white box with the tick core places in ${ theme.name }`, async ( { page } ) => {
			await doLogin( page );
			await page.goto( `/wp-admin/post.php?post=${ pageId }&action=edit&wp_theme_preview=${ theme.preview }` );
			await page.locator( 'iframe[name="editor-canvas"]' ).waitFor( { timeout: 30000 } );

			const canvas = page.frameLocator( 'iframe[name="editor-canvas"]' );
			const block = canvas.locator( '[data-type="sowb/siteorigin-widget-pricetable-widget"]' ).first();

			await block.waitFor( { timeout: 30000 } );

			const guide = page.locator( '.components-guide' );

			if ( await guide.isVisible() ) {
				await page.getByRole( 'button', { name: 'Close' } ).first().click();
			}

			await block.click( { position: { x: 40, y: 40 } } );
			await page.getByRole( 'button', { name: 'Edit widget.' } ).click();
			await expect( block.locator( CHECKBOX ) ).toBeChecked( { timeout: 30000 } );

			const frame = page.frames().find( ( f ) => f.name() === 'editor-canvas' );
			const m = await frame.evaluate( measure, CHECKBOX );

			expect( theme.rendered( m.theme ), `${ theme.name } reached the canvas: ${ JSON.stringify( m.theme ) }` ).toBeTruthy();

			expect.soft( m.box ).toEqual( {
				appearance: 'none',
				background: 'rgb(255, 255, 255)',
				border: 'rgb(140, 143, 148)',
				width: '16px',
				height: '16px',
			} );
			expect.soft( m.tick ).toEqual( {
				position: 'static',
				float: 'left',
				transform: 'none',
				marginTop: '-3px',
				marginLeft: '-4px',
				width: '21px',
				height: '21px',
			} );
		} );
	}
} );
