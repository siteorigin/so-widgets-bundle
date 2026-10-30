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
 * Measures the Features layout settings on a rendered page: features per
 * row, centred items, the space between features and its mobile value, and
 * responsive off, at desktop and mobile widths, in each theme. Each widget
 * has seven alike features, so rows hold equal boxes and the last row is
 * short.
 */

const COUNT = 7;

// Spacing values are "vertical horizontal": the row gap, then the column gap.
const SPACING = { row: 30, column: 40 };
const MOBILE_SPACING = { row: 10, column: 12 };

const CASES = {
	perRow1: { per_row: 1 },
	perRow2: { per_row: 2 },
	perRow3: { per_row: 3 },
	perRow4: { per_row: 4 },
	centred: { per_row: 3, center_items: true },
	responsiveOff: { per_row: 3, responsive: false },
	// https://github.com/siteorigin/so-widgets-bundle/issues/2403
	responsiveOffNoMobileSpacing: { per_row: 3, responsive: false, feature_spacing: '50px 50px', feature_spacing_mobile: '' },
};

const NAMES = Object.keys( CASES );

const widgetData = ( settings ) => ( {
	features: Array.from( { length: COUNT }, ( _, i ) => ( {
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
	center_items: false,
	responsive: true,
	feature_spacing: `${ SPACING.row }px ${ SPACING.column }px`,
	feature_spacing_mobile: `${ MOBILE_SPACING.row }px ${ MOBILE_SPACING.column }px`,
	...settings,
} );

// The default responsive breakpoint.
const BREAKPOINT = 780;

/**
 * Runs in the page: the list box and its feature boxes, per case.
 */
const measure = ( names ) => {
	const box = ( el ) => {
		const b = el.getBoundingClientRect();

		return { left: b.left, top: b.top, right: b.right, bottom: b.bottom, width: b.width };
	};

	const lists = Array.from( document.querySelectorAll( '.sow-features-list' ) );

	return Object.fromEntries( names.map( ( name, n ) => [ name, {
		list: box( lists[ n ] ),
		features: Array.from( lists[ n ].querySelectorAll( ':scope > li' ) ).map( box ),
	} ] ) );
};

// Features grouped into rows by their top edge.
const rowsOf = ( features ) => features.reduce( ( rows, f ) => {
	const row = rows.find( ( r ) => Math.abs( r[ 0 ].top - f.top ) <= 1 );

	if ( row ) {
		row.push( f );
	} else {
		rows.push( [ f ] );
	}

	return rows;
}, [] );

const rowSizes = ( perRow ) => {
	const sizes = Array( Math.floor( COUNT / perRow ) ).fill( perRow );

	return COUNT % perRow ? [ ...sizes, COUNT % perRow ] : sizes;
};

/**
 * Checks the rows: their sizes, the gap between features in a row and
 * between rows, equal widths, and where the last row starts.
 */
const checkRows = ( m, { perRow, spacing, centred, fullWidth, featureWidth }, message ) => {
	const rows = rowsOf( m.features );

	expect.soft( m.features.length, `${ message }: features` ).toBe( COUNT );
	expect.soft( rows.map( ( r ) => r.length ), `${ message }: features per row` ).toEqual( rowSizes( perRow ) );

	rows.forEach( ( row, r ) => {
		row.slice( 1 ).forEach( ( f, i ) => expectNear( f.left - row[ i ].right, spacing.column, `${ message }: column gap in row ${ r + 1 }` ) );

		if ( r > 0 ) {
			const above = Math.max( ...rows[ r - 1 ].map( ( f ) => f.bottom ) );

			expectNear( row[ 0 ].top - above, spacing.row, `${ message }: gap above row ${ r + 1 }` );
		}
	} );

	m.features.forEach( ( f, i ) => expectNear( f.width, fullWidth ? m.list.width : featureWidth( m.list.width ), `${ message }: width of feature ${ i + 1 }` ) );

	const last = rows[ rows.length - 1 ];
	const lastLeft = last[ 0 ].left;
	const lastRight = last[ last.length - 1 ].right;

	if ( centred ) {
		expectNear( ( lastLeft + lastRight ) / 2, ( m.list.left + m.list.right ) / 2, `${ message }: last row centred` );
	} else {
		expectNear( lastLeft, m.list.left, `${ message }: last row starts at the list's left` );
	}
};

/**
 * The expected layout of a case at a width. Below the breakpoint a
 * responsive list stacks its features at full width with the mobile row
 * gap. A list with responsive off keeps its row size; with no mobile
 * spacing, its gaps are the feature spacing (#2403). A feature in a row is
 * its share of the list less the column gap of the feature spacing, as
 * calculate_feature_width() sets it.
 */
const expected = ( name, width ) => {
	const settings = CASES[ name ];
	const spacing = settings.feature_spacing ? { row: 50, column: 50 } : SPACING;
	const mobileSpacing = settings.feature_spacing_mobile === '' ? spacing : MOBILE_SPACING;
	const share = Math.round( 100000 / settings.per_row ) / 1000;
	const featureWidth = ( listWidth ) => listWidth * share / 100 - spacing.column;

	if ( width > BREAKPOINT ) {
		return { perRow: settings.per_row, spacing, centred: !! settings.center_items, featureWidth };
	}

	if ( settings.responsive !== false ) {
		return { perRow: 1, spacing: mobileSpacing, fullWidth: true };
	}

	return { perRow: settings.per_row, spacing: mobileSpacing, centred: !! settings.center_items, featureWidth };
};

test.describe( 'Features layout settings', { tag: [ '@firefox', '@webkit' ] }, () => {
	const featuresPage = useFeaturesPage(
		test,
		'Features layout (e2e)',
		() => NAMES.map( ( name ) => featuresBlock( widgetData( CASES[ name ] ) ) ).join( '\n\n' )
	);

	const measured = async ( browser, width, theme ) => {
		const page = await openFeaturesPage( browser, featuresPage, { width, theme } );
		const result = await page.evaluate( measure, NAMES );

		await page.context().close();

		return result;
	};

	for ( const theme of THEMES ) {
		for ( const width of [ 1280, 390 ] ) {
			test( `rows, gaps and widths match the layout settings in the ${ theme.name } at ${ width }px`, async ( { browser } ) => {
				const result = await measured( browser, width, theme );

				for ( const name of NAMES ) {
					// Responsive off below the breakpoint is a test of its own (#2403).
					if ( CASES[ name ].responsive === false && width <= BREAKPOINT ) {
						continue;
					}

					checkRows( result[ name ], expected( name, width ), `${ name }, ${ theme.name } at ${ width }px` );
				}
			} );
		}

		// The feature width subtracts the desktop column gap, so a smaller
		// mobile gap fits one more feature in the row.
		test( `with responsive off, small screens keep the features per row in the ${ theme.name }`, async ( { browser } ) => {
			test.fail( true, 'https://github.com/siteorigin/so-widgets-bundle/issues/2403' );

			const result = await measured( browser, 390, theme );

			expect( rowsOf( result.responsiveOff.features ).map( ( r ) => r.length ) ).toEqual( rowSizes( CASES.responsiveOff.per_row ) );
		} );

		test( `with responsive off and no mobile spacing, small screens keep the feature spacing in the ${ theme.name }`, async ( { browser } ) => {
			test.fail( true, 'https://github.com/siteorigin/so-widgets-bundle/issues/2403' );

			const result = await measured( browser, 390, theme );

			checkRows( result.responsiveOffNoMobileSpacing, expected( 'responsiveOffNoMobileSpacing', 390 ), `responsiveOffNoMobileSpacing, ${ theme.name } at 390px` );
		} );
	}
} );
