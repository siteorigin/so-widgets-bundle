const path = require( 'path' );

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
 * Measures the Features icon settings on a rendered page: the container
 * shape and size, the icon size and colours, and image icons, for a top
 * and a side icon, at desktop and mobile widths, in each theme.
 */

const CONTAINER = 100;
const CONTAINER_COLOR = 'rgb(18, 52, 86)';
const ICON = 40;
const ICON_COLOR = 'rgb(255, 0, 0)';

const IMAGE = path.resolve( process.cwd(), 'node_modules/siteorigin-tests-common/playwright/utilities/assets/test-image.png' );

// The value and unit are stored apart, as the widget form saves them.
const REM_ICON = { icon_size: '2', icon_size_unit: 'rem' };

const CASES = {
	round: { container_shape: 'round' },
	none: { container_shape: '' },
	image: { container_shape: 'round', image: true },
	imageIconSize: { container_shape: 'round', image: true, icon_size_custom: true },
	imageNone: { container_shape: '', image: true },
	rem: { container_shape: 'round', ...REM_ICON },
};

const widgetData = ( { image, ...settings }, attachment ) => ( {
	features: [ 'top', 'right' ].map( ( position ) => ( {
		icon: 'fontawesome-star',
		icon_color: '#ff0000',
		icon_image: image ? attachment.id : '',
		icon_image_size: image ? 'full' : '',
		container_color: '#123456',
		container_position: position,
		title: `Feature ${ position }`,
		text: '<p>Text</p>',
		more_text: '',
		more_url: '',
	} ) ),
	container_size: `${ CONTAINER }px`,
	icon_size: `${ ICON }px`,
	icon_size_custom: false,
	per_row: 2,
	responsive: true,
	...settings,
} );

/**
 * Runs in the page: for each case, the container, icon and image of each
 * feature, in document order.
 */
const measure = async ( names ) => {
	const box = ( el ) => {
		const b = el.getBoundingClientRect();

		return { left: b.left, top: b.top, width: b.width, height: b.height, cx: b.left + b.width / 2, cy: b.top + b.height / 2 };
	};

	const lists = Array.from( document.querySelectorAll( '.sow-features-list' ) );
	const out = {};

	for ( const [ n, name ] of names.entries() ) {
		out[ name ] = await Promise.all( Array.from( lists[ n ].querySelectorAll( ':scope > li' ) ).map( async ( li ) => {
			const container = li.querySelector( '.sow-icon-container' );
			const before = getComputedStyle( container, '::before' );
			const icon = container.querySelector( '[class^="sow-icon-"]' );
			const image = container.querySelector( '.sow-icon-image' );
			const f = {
				position: li.className.match( /position-(\w+)/ )[ 1 ],
				feature: box( li ),
				container: box( container ),
				glyph: before.content,
				glyphColor: before.color,
			};

			if ( icon ) {
				f.icon = box( icon );
				f.iconFontSize = parseFloat( getComputedStyle( icon ).fontSize );
				f.iconColor = getComputedStyle( icon ).color;
			}

			if ( image ) {
				const style = getComputedStyle( image );
				const url = style.backgroundImage.match( /url\("?(.*?)"?\)/ );

				f.image = box( image );
				f.backgroundSize = style.backgroundSize;
				f.imageUrl = url ? url[ 1 ] : null;
				f.imageWidth = url ? await new Promise( ( resolve ) => {
					const img = new Image();
					img.onload = () => resolve( img.naturalWidth );
					img.onerror = () => resolve( 0 );
					img.src = url[ 1 ];
				} ) : 0;
			}

			f.rootFontSize = parseFloat( getComputedStyle( document.documentElement ).fontSize );

			return f;
		} ) );
	}

	return out;
};

const hasGlyph = ( content ) => ! [ 'none', 'normal', '' ].includes( content );

// The container is the container size. Below the breakpoint a side icon
// stacks above the text, and its container spans the feature to centre the
// shape and icon in it.
const checkContainer = ( f, message, stacked ) => {
	expectNear( f.container.width, stacked ? f.feature.width : CONTAINER, `${ message }: container width` );
	expectNear( f.container.height, CONTAINER, `${ message }: container height` );
	if ( stacked || f.position === 'top' ) {
		expectNear( f.container.cx, f.feature.cx, `${ message }: container centre` );
	}
};

const checkIcon = ( f, message, size = ICON ) => {
	expect.soft( f.icon, `${ message }: icon` ).toBeTruthy();
	expectNear( f.iconFontSize, size, `${ message }: icon size` );
	expect.soft( f.iconColor, `${ message }: icon colour` ).toBe( ICON_COLOR );
};

// A shaped container draws its glyph in the container colour, and centres
// the icon on it.
const checkShaped = ( f, message, stacked ) => {
	checkContainer( f, message, stacked );
	expect.soft( hasGlyph( f.glyph ), `${ message }: glyph ${ f.glyph }` ).toBe( true );
	expect.soft( f.glyphColor, `${ message }: glyph colour` ).toBe( CONTAINER_COLOR );
};

// No shape draws no glyph; the icon stays inside the container box.
const checkNone = ( f, message, stacked ) => {
	checkContainer( f, message, stacked );
	expect.soft( hasGlyph( f.glyph ), `${ message }: glyph ${ f.glyph }` ).toBe( false );
};

const checkCentred = ( f, message ) => {
	expectNear( f.icon.cx, f.container.cx, `${ message }: icon centre x` );
	expectNear( f.icon.cy, f.container.cy, `${ message }: icon centre y` );
};

const checkInside = ( inner, outer, message ) => {
	expect.soft( inner.left, message ).toBeGreaterThanOrEqual( outer.left - 1 );
	expect.soft( inner.top, message ).toBeGreaterThanOrEqual( outer.top - 1 );
	expect.soft( inner.left + inner.width, message ).toBeLessThanOrEqual( outer.left + outer.width + 1 );
	expect.soft( inner.top + inner.height, message ).toBeLessThanOrEqual( outer.top + outer.height + 1 );
};

// An image icon fills the container, at its own size or the icon size, and
// is the uploaded image, which loads.
const checkImage = ( f, message, attachment, size ) => {
	expect.soft( f.image, `${ message }: image` ).toBeTruthy();
	expectNear( f.image.width, CONTAINER, `${ message }: image width` );
	expectNear( f.image.height, CONTAINER, `${ message }: image height` );
	expectNear( f.image.cx, f.container.cx, `${ message }: image centre x` );
	expectNear( f.image.cy, f.container.cy, `${ message }: image centre y` );
	checkInside( f.image, f.container, `${ message }: image inside the container` );
	expect.soft( f.backgroundSize, `${ message }: background size` ).toBe( size );
	expect.soft( f.imageUrl, `${ message }: image URL` ).toBe( attachment.source_url );
	expect.soft( f.imageWidth, `${ message }: image loads` ).toBeGreaterThan( 0 );
};

const CHECKS = {
	round: ( f, m, attachment, stacked ) => {
		checkShaped( f, m, stacked );
		checkIcon( f, m );
		checkCentred( f, m );
	},
	none: ( f, m, attachment, stacked ) => {
		checkNone( f, m, stacked );
		checkIcon( f, m );
		checkInside( f.icon, f.container, `${ m }: icon inside the container` );
	},
	image: ( f, m, attachment, stacked ) => {
		checkShaped( f, m, stacked );
		checkImage( f, m, attachment, 'contain' );
	},
	imageIconSize: ( f, m, attachment, stacked ) => {
		checkShaped( f, m, stacked );
		checkImage( f, m, attachment, `${ ICON }px ${ ICON }px` );
	},
	imageNone: ( f, m, attachment, stacked ) => {
		checkNone( f, m, stacked );
		checkImage( f, m, attachment, 'contain' );
	},
	rem: ( f, m, attachment, stacked ) => {
		checkShaped( f, m, stacked );
		checkIcon( f, m, 2 * f.rootFontSize );
	},
};

// The default responsive breakpoint.
const BREAKPOINT = 780;

const NAMES = Object.keys( CASES );

test.describe( 'Features icon settings', { tag: [ '@firefox', '@webkit' ] }, () => {
	let attachment;

	const featuresPage = useFeaturesPage( test, 'Features icons (e2e)', async ( requestUtils ) => {
		attachment = await requestUtils.uploadMedia( IMAGE );

		return NAMES.map( ( name ) => featuresBlock( widgetData( CASES[ name ], attachment ) ) ).join( '\n\n' );
	} );

	test.afterAll( async () => {
		if ( attachment ) {
			await featuresPage.requestUtils.deleteMedia( attachment.id );
		}
	} );

	const run = async ( browser, width, theme, names ) => {
		const page = await openFeaturesPage( browser, featuresPage, { width, theme } );
		const measured = await page.evaluate( measure, NAMES );

		await page.context().close();

		for ( const name of names ) {
			expect( measured[ name ].map( ( f ) => f.position ), name ).toEqual( [ 'top', 'right' ] );

			measured[ name ].forEach( ( f ) => CHECKS[ name ](
				f,
				`${ name }, ${ f.position } icon, ${ theme.name } at ${ width }px`,
				attachment,
				width <= BREAKPOINT && f.position !== 'top'
			) );
		}
	};

	for ( const theme of THEMES ) {
		for ( const width of [ 1280, 390 ] ) {
			test( `containers, icons and image icons match their settings in the ${ theme.name } at ${ width }px`, async ( { browser } ) => {
				await run( browser, width, theme, NAMES.filter( ( name ) => name !== 'rem' ) );
			} );

			test( `an icon size in rem is that many root font sizes in the ${ theme.name } at ${ width }px`, async ( { browser } ) => {
				test.fail( true, 'https://github.com/siteorigin/so-widgets-bundle/issues/2478' );

				await run( browser, width, theme, [ 'rem' ] );
			} );
		}
	}
} );
