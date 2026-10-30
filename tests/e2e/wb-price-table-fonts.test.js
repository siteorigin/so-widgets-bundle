const {
	expect,
	test
} = require( '@playwright/test' );

const {
	THEMES,
	openFeaturesPage,
	useFeaturesPage,
} = require( './helpers/features' );
const { widgetBlock } = require( './helpers/widget-block-abilities' );

/**
 * Measures the Price Table font settings on a rendered page: each part uses
 * its own Font and Size, a setting changes no other part, and an untouched
 * table keeps its markup. The page holds one table per case, and each is
 * compared with an untouched control table. Google font requests are aborted:
 * the computed values and the enqueued stylesheet are what is measured.
 */

/**
 * Rules of the kind themes set on the elements a table prints.
 */
const THEME_CSS = `<!-- wp:html --><style>
	.entry-content p, p { font-size: 11px; font-family: fantasy; }
	.entry-content a, a { font-size: 12px; }
	.entry-content div, div { font-family: cursive; }
	.entry-content .ow-pt-subtitle { font-style: italic; }
</style><!-- /wp:html -->`;

const CONTROL = {};

const ALL_PARTS = {
	title: { font: 'Georgia', size: '32px' },
	subtitle: { font: 'Courier New', size: '14px' },
	price: { font: 'Roboto:700', size: '40px' },
	sale_price: { font: 'Arial', size: '50px' },
	original_price: { font: 'Georgia', size: '20px' },
	period: { font: 'Times New Roman', size: '13px' },
	features: { font: 'Arial', size: '18px' },
	button: { font: 'Courier New', size: '16px' },
};

const ONE_PART = { font: 'Courier New', size: '33px' };

const TABLES = {
	control: CONTROL,
	all: ALL_PARTS,
	title: { title: ONE_PART },
	price: { price: ONE_PART },
	salePrice: { sale_price: ONE_PART },
	originalPrice: { original_price: ONE_PART },
	featuresAtContainerSize: { features: { size: '0.925em' } },
	largeFeatures: { features: { size: '24px' } },
	normalSubtitle: { subtitle: { font: 'Arial' } },
	italicSubtitle: { subtitle: { font: 'Roboto:400italic' } },
};

const column = ( overrides ) => ( {
	title: 'Starter',
	subtitle: 'For small teams',
	price: '$19',
	per: 'per month',
	button: 'Choose plan',
	url: '#plan',
	features: [ { text: 'Unlimited projects', icon_new: 'fontawesome-star' } ],
	...overrides,
} );

const priceTableBlock = ( fonts ) => widgetBlock( 'sowb/siteorigin-widget-pricetable-widget', {
	widgetClass: 'SiteOrigin_Widget_PriceTable_Widget',
	widgetData: {
		columns: [ column( {} ), column( { sale_price: '$15' } ) ],
		design: { fonts },
	},
} );

/**
 * Runs in the page: the computed font of each part of each table, by the
 * table's position on the page.
 */
const measure = () => {
	const font = ( el ) => {
		const style = getComputedStyle( el );

		return {
			family: style.fontFamily.split( ',' )[ 0 ].replace( /["']/g, '' ).trim(),
			size: style.fontSize,
			style: style.fontStyle,
			weight: style.fontWeight,
		};
	};

	const table = ( el ) => {
		const [ plain, sale ] = el.querySelectorAll( '.ow-pt-column' );

		return {
			titleWrapper: !! plain.querySelector( '.ow-pt-title-text' ),
			title: font( plain.querySelector( '.ow-pt-title-text' ) || plain.querySelector( '.ow-pt-title' ) ),
			subtitle: font( plain.querySelector( '.ow-pt-subtitle' ) ),
			price: font( plain.querySelector( '.ow-pt-price' ) ),
			salePrice: font( sale.querySelector( '.ow-pt-sale-price' ) ),
			originalPrice: font( sale.querySelector( '.ow-pt-price' ) ),
			period: font( plain.querySelector( '.ow-pt-per' ) ),
			features: font( plain.querySelector( '.sow-feature-text p' ) ),
			icon: font( plain.querySelector( '.sow-feature-icon > *' ) ),
			button: font( plain.querySelector( 'a.ow-pt-link' ) ),
		};
	};

	return {
		tables: Array.from( document.querySelectorAll( '.so-widget-sow-price-table' ) ).map( table ),
		googleFonts: Array.from( document.querySelectorAll( 'link[rel="stylesheet"]' ) )
			.map( ( link ) => decodeURIComponent( link.href ) )
			.filter( ( href ) => href.includes( 'fonts.googleapis.com' ) ),
	};
};

const checkFont = ( measured, expected, message ) => {
	expect.soft( measured.family, `${ message }: family` ).toBe( expected.font.split( ':' )[ 0 ] );
	expect.soft( measured.size, `${ message }: size` ).toBe( expected.size );
};

test.describe( 'Price Table font settings', () => {
	const pricePage = useFeaturesPage(
		test,
		'Price Table fonts (e2e)',
		() => [ THEME_CSS, ...Object.values( TABLES ).map( priceTableBlock ) ].join( '\n\n' )
	);

	const measured = async ( browser, width, theme ) => {
		const page = await openFeaturesPage( browser, pricePage, { width, theme, beforeLoad: ( p ) => p.route( /fonts\.googleapis\.com/, ( route ) => route.abort() ) } );
		const { tables, googleFonts } = await page.evaluate( measure );

		await page.context().close();

		expect( tables ).toHaveLength( Object.keys( TABLES ).length );

		return {
			googleFonts,
			...Object.fromEntries( Object.keys( TABLES ).map( ( name, i ) => [ name, tables[ i ] ] ) ),
		};
	};

	for ( const theme of THEMES ) {
		for ( const width of [ 1280, 390 ] ) {
			const at = `in the ${ theme.name } at ${ width }px`;

			test( `each part uses its own font and size ${ at }`, async ( { browser } ) => {
				const m = await measured( browser, width, theme );

				checkFont( m.all.title, ALL_PARTS.title, 'title' );
				checkFont( m.all.subtitle, ALL_PARTS.subtitle, 'subtitle' );
				checkFont( m.all.price, ALL_PARTS.price, 'price' );
				expect.soft( m.all.price.weight, 'price: weight' ).toBe( '700' );
				checkFont( m.all.salePrice, ALL_PARTS.sale_price, 'sale price' );
				checkFont( m.all.originalPrice, ALL_PARTS.original_price, 'original price' );
				checkFont( m.all.period, ALL_PARTS.period, 'period' );
				checkFont( m.all.features, ALL_PARTS.features, 'feature text' );
				checkFont( m.all.button, ALL_PARTS.button, 'button' );
				expect.soft( m.googleFonts.some( ( href ) => href.includes( 'family=Roboto:' ) && href.includes( '700' ) ), `Roboto 700 is enqueued: ${ m.googleFonts }` ).toBe( true );
			} );

			test( `a setting changes only its own part ${ at }`, async ( { browser } ) => {
				const m = await measured( browser, width, theme );

				checkFont( m.title.title, ONE_PART, 'title' );
				expect.soft( m.title.subtitle, 'subtitle under a changed title' ).toEqual( m.control.subtitle );

				checkFont( m.price.price, ONE_PART, 'price' );
				expect.soft( m.price.salePrice, 'sale price under a changed price' ).toEqual( m.control.salePrice );
				expect.soft( m.price.originalPrice, 'original price under a changed price' ).toEqual( m.control.originalPrice );

				checkFont( m.salePrice.salePrice, ONE_PART, 'sale price' );
				expect.soft( m.salePrice.price, 'price under a changed sale price' ).toEqual( m.control.price );
				expect.soft( m.salePrice.originalPrice, 'original price under a changed sale price' ).toEqual( m.control.originalPrice );

				checkFont( m.originalPrice.originalPrice, ONE_PART, 'original price' );
				expect.soft( m.originalPrice.price, 'price under a changed original price' ).toEqual( m.control.price );
				expect.soft( m.originalPrice.salePrice, 'sale price under a changed original price' ).toEqual( m.control.salePrice );
			} );

			test( `an untouched table keeps its title markup ${ at }`, async ( { browser } ) => {
				const m = await measured( browser, width, theme );

				expect.soft( m.control.titleWrapper, 'control' ).toBe( false );
				expect.soft( m.price.titleWrapper, 'a table with another part set' ).toBe( false );
				expect.soft( m.title.titleWrapper, 'a table with Title set' ).toBe( true );
			} );

			test( `a features size beats the theme and leaves the icon ${ at }`, async ( { browser } ) => {
				const m = await measured( browser, width, theme );

				expect.soft( m.control.features.size, 'the theme rule applies to an untouched table' ).toBe( '11px' );
				expect.soft( parseFloat( m.featuresAtContainerSize.features.size ), 'an entered 0.925em' ).toBeGreaterThan( 11 );
				expect.soft( m.largeFeatures.features.size, 'an entered 24px' ).toBe( '24px' );
				expect.soft( m.largeFeatures.icon, 'icon beside changed text' ).toEqual( m.control.icon );
			} );

			test( `a chosen font sets its own style over the theme's ${ at }`, async ( { browser } ) => {
				const m = await measured( browser, width, theme );

				expect.soft( m.control.subtitle.style, 'the theme rule applies to an untouched table' ).toBe( 'italic' );
				expect.soft( m.normalSubtitle.subtitle.style, 'a normal font' ).toBe( 'normal' );
				expect.soft( m.italicSubtitle.subtitle.style, 'an italic font' ).toBe( 'italic' );
			} );
		}
	}
} );

test.describe( 'Price Table equalized rows with a late web font', () => {
	// Wider than the fallback, so the long title wraps only once it arrives.
	const FONT_PATH = '/wp-content/themes/twentytwentyfive/assets/fonts/fira-code/FiraCode-VariableFont_wght.woff2';
	const FONT_DELAY = 1000;

	const latePage = useFeaturesPage(
		test,
		'Price Table late font (e2e)',
		() => widgetBlock( 'sowb/siteorigin-widget-pricetable-widget', {
			widgetClass: 'SiteOrigin_Widget_PriceTable_Widget',
			widgetData: {
				columns: [
					column( { title: 'Twenty four letters wide' } ),
					column( {} ),
					column( {} ),
				],
				equalize_row_heights: true,
				design: { fonts: { title: { font: 'Roboto', size: '30px' } } },
			},
		} )
	);

	test( 'rows are measured again once the font loads', async ( { browser } ) => {
		const font = await latePage.requestUtils.request.get( new URL( FONT_PATH, latePage.link ).href );

		expect( font.status() ).toBe( 200 );

		const body = await font.body();

		const page = await openFeaturesPage( browser, latePage, {
			width: 1280,
			beforeLoad: async ( p ) => {
				await p.route( /fonts\.googleapis\.com/, ( route ) => route.fulfill( {
					contentType: 'text/css',
					body: `@font-face { font-family: 'Roboto'; src: url('https://fonts.gstatic.com/late.woff2') format('woff2'); font-display: swap; }`,
				} ) );
				await p.route( /fonts\.gstatic\.com/, async ( route ) => {
					await new Promise( ( resolve ) => setTimeout( resolve, FONT_DELAY ) );
					await route.fulfill( {
						contentType: 'font/woff2',
						headers: { 'access-control-allow-origin': '*' },
						body,
					} );
				} );
			},
		} );

		await page.waitForFunction( () => Array.from( document.fonts ).some( ( face ) => face.family.includes( 'Roboto' ) && face.status === 'loaded' ) );
		await page.waitForTimeout( 250 );

		const titles = await page.$$eval( '.ow-pt-title', ( elements ) => elements.map( ( el ) => ( {
			height: el.clientHeight,
			content: el.scrollHeight,
		} ) ) );

		await page.context().close();

		console.log( `Title rows after the font loaded: ${ JSON.stringify( titles ) }` );

		for ( const title of titles ) {
			expect.soft( title.height, 'rows match' ).toBe( titles[ 0 ].height );
			expect.soft( title.content, 'the title fits its row' ).toBeLessThanOrEqual( title.height );
		}
	} );
} );
