const {
	expect,
	test
} = require( '@playwright/test' );

const {
	THEMES,
	expectNear,
	featuresBlock,
	openFeaturesPage,
	useFeaturesPage,
} = require( './helpers/features' );

/**
 * Measures the Features Responsive Breakpoint setting on a rendered page:
 * features stack below it and keep their row above it, at the default
 * (780px) and at a value saved through the widget's Settings dialog; with
 * responsive off they keep their row below it too. In each theme.
 *
 * The setting is site-wide, so this file runs in order and restores the
 * value it found. The other Features specs measure only at 1280px and
 * 390px, which fall on the same side of both breakpoints. The test runner
 * runs one browser project at a time, so two browsers never run this file
 * at once.
 */

test.describe.configure( { mode: 'serial' } );

const PER_ROW = 3;
const SAVED = 1000;

const widgetData = ( responsive ) => ( {
	features: Array.from( { length: PER_ROW }, ( _, i ) => ( {
		icon: 'fontawesome-star',
		container_color: '#404040',
		container_position: 'top',
		title: `Feature ${ i + 1 }`,
		text: '<p>Text</p>',
		more_text: '',
		more_url: '',
	} ) ),
	container_shape: 'round',
	container_size: '84px',
	icon_size: '24px',
	per_row: PER_ROW,
	responsive,
	feature_spacing: '25px 25px',
	feature_spacing_mobile: '25px 25px',
} );

/**
 * Runs in the page: per list, its width and its features' boxes.
 */
const measure = () => Array.from( document.querySelectorAll( '.sow-features-list' ) ).map( ( list ) => ( {
	width: list.getBoundingClientRect().width,
	features: Array.from( list.querySelectorAll( ':scope > li' ) ).map( ( li ) => {
		const b = li.getBoundingClientRect();

		return { top: b.top, width: b.width };
	} ),
} ) );

const checkStacked = ( list, message ) => {
	list.features.forEach( ( f, i ) => {
		expectNear( f.width, list.width, `${ message }: feature ${ i + 1 } is full width` );

		if ( i > 0 ) {
			expect.soft( f.top, `${ message }: feature ${ i + 1 } is below the one before` ).toBeGreaterThan( list.features[ i - 1 ].top + 1 );
		}
	} );
};

const checkRow = ( list, message ) => {
	list.features.forEach( ( f, i ) => {
		expectNear( f.top, list.features[ 0 ].top, `${ message }: feature ${ i + 1 } is in the row` );
		expect.soft( f.width, `${ message }: feature ${ i + 1 } is narrower than the list` ).toBeLessThan( list.width / PER_ROW );
	} );
};

/**
 * Opens the widget's Settings dialog on the Widgets page, saves the
 * breakpoint, and returns the value and unit the form showed before.
 */
const saveBreakpoint = async ( browser, featuresPage, { value, unit } ) => {
	const context = await browser.newContext( { storageState: await featuresPage.requestUtils.request.storageState() } );
	const page = await context.newPage();

	await page.goto( new URL( '/wp-admin/plugins.php?page=so-widgets-plugins', featuresPage.link ).toString() );
	await page.locator( 'button.so-widget-settings[data-form-url*="features%2Ffeatures.php"]' ).click();

	const dialog = page.locator( '#sow-settings-dialog' );
	const input = dialog.locator( 'input[name$="[responsive_breakpoint]"]' );
	const select = dialog.locator( 'select[name$="[responsive_breakpoint_unit]"]' );

	await input.waitFor();

	const before = { value: await input.inputValue(), unit: await select.inputValue() };

	await input.fill( String( value ) );
	await select.selectOption( unit );

	const saved = page.waitForResponse( ( response ) => response.url().includes( 'action=so_widgets_setting_save' ) );

	await dialog.locator( '.so-save' ).click();

	expect( ( await saved ).ok(), 'the Settings dialog saves' ).toBe( true );

	await context.close();

	return before;
};

test.describe( 'Features responsive breakpoint setting', { tag: [ '@firefox', '@webkit' ] }, () => {
	let found;

	const featuresPage = useFeaturesPage(
		test,
		'Features breakpoint (e2e)',
		() => [ featuresBlock( widgetData( true ) ), featuresBlock( widgetData( false ) ) ].join( '\n\n' )
	);

	// A never saved setting is restored as the value its form showed, 780px,
	// which is its default and renders the same CSS.
	test.afterAll( async ( { browser } ) => {
		if ( found ) {
			await saveBreakpoint( browser, featuresPage, found );
		}
	} );

	const measured = async ( browser, width, theme ) => {
		const page = await openFeaturesPage( browser, featuresPage, { width, theme } );
		const [ responsive, responsiveOff ] = await page.evaluate( measure );

		await page.context().close();

		return { responsive, responsiveOff };
	};

	const checkBreakpoint = async ( browser, breakpoint ) => {
		for ( const theme of THEMES ) {
			const below = await measured( browser, breakpoint - 10, theme );
			const above = await measured( browser, breakpoint + 20, theme );
			const message = ( width ) => `${ theme.name } at ${ width }px, breakpoint ${ breakpoint }px`;

			checkStacked( below.responsive, message( breakpoint - 10 ) );
			checkRow( below.responsiveOff, `responsive off, ${ message( breakpoint - 10 ) }` );
			checkRow( above.responsive, message( breakpoint + 20 ) );
		}
	};

	test( 'the default breakpoint is 780px', async ( { browser } ) => {
		await checkBreakpoint( browser, 780 );
	} );

	test( 'a breakpoint saved in the Settings dialog moves where features stack', async ( { browser } ) => {
		found = await saveBreakpoint( browser, featuresPage, { value: SAVED, unit: 'px' } );

		await checkBreakpoint( browser, SAVED );
	} );
} );
