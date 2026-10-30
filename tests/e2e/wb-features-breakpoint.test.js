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
 * features stack at it and keep their row a pixel above it, at the value
 * the widget's Settings dialog shows (780px, the default, on a fresh site)
 * and at a value saved through that dialog; with responsive off they keep
 * their row at it too. In each theme.
 *
 * The setting is site-wide, so this file runs in order and restores the
 * value it found. The other Features specs measure only at 1280px and
 * 390px, which fall on the same side of both breakpoints. The test runner
 * runs one browser project at a time, so two browsers never run this file
 * at once.
 */

test.describe.configure( { mode: 'serial' } );

const PER_ROW = 3;
// A breakpoint to save, other than the one the dialog shows.
const toSave = ( shownValue ) => ( Number( shownValue ) === 1000 ? 1100 : 1000 );

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
 * Opens the widget's Settings dialog on the Widgets page and passes `use`
 * the dialog's breakpoint field and unit.
 */
const withSettings = async ( browser, featuresPage, use ) => {
	const context = await browser.newContext( {
		ignoreHTTPSErrors: true,
		storageState: await featuresPage.requestUtils.request.storageState(),
	} );

	try {
		const page = await context.newPage();

		await page.goto( new URL( '/wp-admin/plugins.php?page=so-widgets-plugins', featuresPage.link ).toString() );
		await page.locator( 'button.so-widget-settings[data-form-url*="features%2Ffeatures.php"]' ).click();

		const dialog = page.locator( '#sow-settings-dialog' );
		const input = dialog.locator( 'input[name$="[responsive_breakpoint]"]' );

		await input.waitFor();

		return await use( { page, dialog, input, select: dialog.locator( 'select[name$="[responsive_breakpoint_unit]"]' ) } );
	} finally {
		await context.close();
	}
};

const shown = async ( { input, select } ) => ( { value: await input.inputValue(), unit: await select.inputValue() } );

const readBreakpoint = ( browser, featuresPage ) => withSettings( browser, featuresPage, shown );

/**
 * Saves a breakpoint. `next` receives the value and unit the dialog shows,
 * before anything is written, and returns the value and unit to save.
 */
const saveBreakpoint = ( browser, featuresPage, next ) => withSettings( browser, featuresPage, async ( settings ) => {
	const { value, unit } = next( await shown( settings ) );

	await settings.input.fill( String( value ) );
	await settings.select.selectOption( unit );

	const saved = settings.page.waitForResponse( ( response ) => response.url().includes( 'action=so_widgets_setting_save' ) );

	await settings.dialog.locator( '.so-save' ).click();

	expect( ( await saved ).ok(), 'the Settings dialog saves' ).toBe( true );
} );

test.describe( 'Features responsive breakpoint setting', { tag: [ '@firefox', '@webkit' ] }, () => {
	let found;

	const featuresPage = useFeaturesPage(
		test,
		'Features breakpoint (e2e)',
		() => [ featuresBlock( widgetData( true ) ), featuresBlock( widgetData( false ) ) ].join( '\n\n' )
	);

	// A never saved setting is restored as the value its dialog showed,
	// 780px, which is its default and renders the same CSS; the plugin has no
	// way to delete the setting.
	test.afterAll( async ( { browser } ) => {
		if ( found ) {
			await saveBreakpoint( browser, featuresPage, () => found );
		}
	} );

	const measured = async ( browser, width, theme ) => {
		const page = await openFeaturesPage( browser, featuresPage, { width, theme } );
		const [ responsive, responsiveOff ] = await page.evaluate( measure );

		await page.context().close();

		return { responsive, responsiveOff };
	};

	// The media queries are max-width and min-width of the breakpoint, so
	// features stack at it and keep their row a pixel above it.
	const checkBreakpoint = async ( browser, breakpoint ) => {
		for ( const theme of THEMES ) {
			const at = await measured( browser, breakpoint, theme );
			const above = await measured( browser, breakpoint + 1, theme );
			const message = ( width ) => `${ theme.name } at ${ width }px, breakpoint ${ breakpoint }px`;

			checkStacked( at.responsive, message( breakpoint ) );
			checkRow( at.responsiveOff, `responsive off, ${ message( breakpoint ) }` );
			checkRow( above.responsive, message( breakpoint + 1 ) );
		}
	};

	test( 'features stack at the breakpoint the Settings dialog shows', async ( { browser } ) => {
		const breakpoint = await readBreakpoint( browser, featuresPage );

		expect( breakpoint.unit ).toBe( 'px' );

		await checkBreakpoint( browser, Number( breakpoint.value ) );
	} );

	test( 'a breakpoint saved in the Settings dialog moves where features stack', async ( { browser } ) => {
		let saved;

		await saveBreakpoint( browser, featuresPage, ( value ) => {
			found = value;
			saved = toSave( value.value );

			return { value: saved, unit: 'px' };
		} );

		await checkBreakpoint( browser, saved );
	} );
} );
