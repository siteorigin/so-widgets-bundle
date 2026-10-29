const {
	expect,
	test
} = require( '@playwright/test' );

const {
	THEMES,
	featuresBlock,
	openFeaturesPage,
	useFeaturesPage,
} = require( './helpers/features' );

/**
 * Measures the Features font settings on a rendered page: the title tag,
 * and the font, size and colour of the title, the text and the More link,
 * against theme rules that style the same elements, at desktop and mobile
 * widths, in each theme. Google font requests are aborted: the computed
 * values and the enqueued stylesheet are what is measured.
 */

/**
 * Rules of the kind themes set: heading, paragraph and list item fonts,
 * sizes and colours, and a link colour and underline.
 */
const THEME_CSS = `<!-- wp:html --><style>
	.entry-content h3, h3 { color: #990099; font-size: 50px; font-family: fantasy; }
	.entry-content p, p { color: #333333; font-size: 11px; }
	.entry-content li, li { color: #444444; font-size: 13px; font-family: cursive; }
	.entry-content a, a { color: #ff6600; text-decoration: underline; }
</style><!-- /wp:html -->`;

const TITLE = { family: 'Georgia', size: '30px', color: 'rgb(170, 0, 0)' };
const TEXT = { family: 'Courier New', size: '18px', color: 'rgb(0, 170, 0)' };
const MORE = { family: 'Roboto', size: '14px', color: 'rgb(0, 0, 170)', weight: '700' };

const widgetData = {
	features: [ {
		icon: 'fontawesome-star',
		container_color: '#404040',
		container_position: 'top',
		title: 'Feature title',
		text: '<p>Paragraph</p>Bare text<ul><li>List item</li></ul>',
		more_text: 'Read more',
		more_url: '#more',
	} ],
	fonts: {
		title_options: { tag: 'h3', font: 'Georgia', size: '30px', color: '#aa0000' },
		text_options: { font: 'Courier New', size: '18px', color: '#00aa00' },
		more_text_options: { font: 'Roboto:700', size: '14px', color: '#0000aa' },
	},
	title_link: true,
	container_shape: 'round',
	container_size: '84px',
	icon_size: '24px',
	per_row: 3,
	responsive: true,
};

/**
 * Runs in the page: the computed font of each text element of the feature.
 */
const measure = () => {
	const li = document.querySelector( '.sow-features-list > li' );
	const font = ( el ) => {
		const style = getComputedStyle( el );

		return {
			tag: el.tagName,
			family: style.fontFamily.split( ',' )[ 0 ].replace( /["']/g, '' ).trim(),
			size: style.fontSize,
			color: style.color,
			weight: style.fontWeight,
		};
	};

	return {
		title: font( li.querySelector( '.sow-features-feature-title' ) ),
		titleLink: font( li.querySelector( '.sow-features-feature-title a' ) ),
		paragraph: font( li.querySelector( '.sow-features-feature-text p' ) ),
		// The text wrapper holds the bare text.
		bareText: font( li.querySelector( '.sow-features-feature-text' ) ),
		listItem: font( li.querySelector( '.sow-features-feature-text li' ) ),
		more: font( li.querySelector( 'p.sow-more-text' ) ),
		moreLink: font( li.querySelector( 'p.sow-more-text a' ) ),
		googleFonts: Array.from( document.querySelectorAll( 'link[rel="stylesheet"]' ) )
			.map( ( link ) => decodeURIComponent( link.href ) )
			.filter( ( href ) => href.includes( 'fonts.googleapis.com' ) ),
	};
};

const checkFont = ( measured, expected, message ) => {
	expect.soft( measured.family, `${ message }: family` ).toBe( expected.family );
	expect.soft( measured.size, `${ message }: size` ).toBe( expected.size );
	expect.soft( measured.color, `${ message }: colour` ).toBe( expected.color );
};

test.describe( 'Features font settings', { tag: [ '@firefox', '@webkit' ] }, () => {
	const featuresPage = useFeaturesPage(
		test,
		'Features fonts (e2e)',
		() => [ THEME_CSS, featuresBlock( widgetData ) ].join( '\n\n' )
	);

	const measured = async ( browser, width, theme ) => {
		const page = await openFeaturesPage( browser, featuresPage, { width, theme, beforeLoad: ( p ) => p.route( /fonts\.googleapis\.com/, ( route ) => route.abort() ) } );
		const result = await page.evaluate( measure );

		await page.context().close();

		return result;
	};

	for ( const theme of THEMES ) {
		for ( const width of [ 1280, 390 ] ) {
			const at = `in the ${ theme.name } at ${ width }px`;

			test( `the title, text and More link use their font settings over the theme's ${ at }`, async ( { browser } ) => {
				const m = await measured( browser, width, theme );

				expect.soft( m.title.tag, 'title tag' ).toBe( 'H3' );
				checkFont( m.title, TITLE, 'title' );
				checkFont( m.titleLink, TITLE, 'title link' );
				checkFont( m.paragraph, TEXT, 'text paragraph' );
				checkFont( m.bareText, TEXT, 'bare text' );
				checkFont( m.more, MORE, 'More paragraph' );
				expect.soft( m.more.weight, 'More paragraph: weight' ).toBe( MORE.weight );
				checkFont( m.moreLink, MORE, 'More link' );
				expect.soft( m.googleFonts.some( ( href ) => href.includes( 'family=Roboto:700' ) ), `Roboto 700 is enqueued: ${ m.googleFonts }` ).toBe( true );
			} );

			test( `list items in the text use the text font settings over the theme's ${ at }`, async ( { browser } ) => {
				test.fail( true, 'https://github.com/siteorigin/so-widgets-bundle/issues/2480' );

				checkFont( ( await measured( browser, width, theme ) ).listItem, TEXT, 'list item' );
			} );

			test( `the More link has the More font weight ${ at }`, async ( { browser } ) => {
				test.fail( true, 'https://github.com/siteorigin/so-widgets-bundle/issues/2479' );

				expect( ( await measured( browser, width, theme ) ).moreLink.weight ).toBe( MORE.weight );
			} );
		}
	}
} );
