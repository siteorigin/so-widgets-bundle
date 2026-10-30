const {
	expect,
	test
} = require( '@playwright/test' );

const { doLogin } = require( 'siteorigin-tests-common/playwright/common' );

const { useFeaturesPage } = require( './helpers/features' );
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
		rendered: ( { snapshotStyles, twentyTwentyFiveAssets } ) => ! snapshotStyles && twentyTwentyFiveAssets,
	},
];

const TICKED = '.siteorigin-widget-field-equalize_row_heights input[type="checkbox"]';
const UNTICKED = '.siteorigin-widget-field-button_new_window input[type="checkbox"]';

const WHITE_BOX = {
	appearance: 'none',
	background: 'rgb(255, 255, 255)',
	border: 'rgb(140, 143, 148)',
	width: '16px',
	height: '16px',
};

/**
 * Runs in the editor canvas: the theme that reached it, and the computed
 * styles of a ticked box, its tick, and an unticked box.
 */
const measure = ( { ticked, unticked } ) => {
	const box = ( selector ) => {
		const style = getComputedStyle( document.querySelector( selector ) );

		return {
			appearance: style.appearance,
			background: style.backgroundColor,
			border: style.borderTopColor,
			width: style.width,
			height: style.height,
		};
	};
	const tick = getComputedStyle( document.querySelector( ticked ), '::before' );

	return {
		theme: {
			snapshotStyles: !! document.querySelector( 'link[href*="/themes/siteorigin-snapshot/"]' ),
			twentyTwentyFiveAssets: document.documentElement.outerHTML.includes( '/themes/twentytwentyfive/' ),
		},
		ticked: box( ticked ),
		unticked: box( unticked ),
		tickContent: tick.content,
		tick: {
			display: tick.display,
			visibility: tick.visibility,
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
	const checkboxPage = useFeaturesPage(
		test,
		'Widget form checkbox (e2e)',
		() => widgetBlock( 'sowb/siteorigin-widget-pricetable-widget', {
			widgetClass: 'SiteOrigin_Widget_PriceTable_Widget',
			widgetData: {
				columns: [ { title: 'One', price: '1' } ],
				equalize_row_heights: true,
			},
		} )
	);

	for ( const theme of THEMES ) {
		test( `a ticked checkbox is a white box with the tick core places in ${ theme.name }`, async ( { page } ) => {
			await doLogin( page );
			await page.goto( `/wp-admin/post.php?post=${ checkboxPage.id }&action=edit&wp_theme_preview=${ theme.preview }` );
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
			await expect( block.locator( TICKED ) ).toBeChecked( { timeout: 30000 } );
			await expect( block.locator( UNTICKED ) ).not.toBeChecked();

			const frame = page.frames().find( ( f ) => f.name() === 'editor-canvas' );
			const m = await frame.evaluate( measure, { ticked: TICKED, unticked: UNTICKED } );

			expect( theme.rendered( m.theme ), `${ theme.name } reached the canvas: ${ JSON.stringify( m.theme ) }` ).toBeTruthy();

			expect.soft( m.ticked, 'ticked box' ).toEqual( WHITE_BOX );
			expect.soft( m.unticked, 'unticked box' ).toEqual( WHITE_BOX );
			expect.soft( m.tickContent, 'the tick is the blue SVG' ).toContain( 'fill%3D%27%233582c4%27' );
			expect.soft( m.tick ).toEqual( {
				display: 'block',
				visibility: 'visible',
				position: 'static',
				float: 'left',
				transform: 'none',
				marginTop: '-3px',
				marginLeft: '-4px',
				width: '21px',
				height: '21px',
			} );

			await page.emulateMedia( { forcedColors: 'active' } );

			const forced = await frame.evaluate( measure, { ticked: TICKED, unticked: UNTICKED } );

			expect.soft( forced.ticked.appearance, 'a forced colors palette draws the native checkbox' ).toBe( 'auto' );
			expect.soft( forced.tickContent, 'the fixed color tick is hidden in forced colors' ).toBe( 'none' );
		} );
	}
} );
