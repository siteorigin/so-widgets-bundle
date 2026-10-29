const {
	expect,
	test
} = require( '@playwright/test' );

const common = require( 'siteorigin-tests-common/playwright/common' );

const {
	setupRequestUtils,
} = common;

/**
 * Measures the Features spacing settings on a rendered page: a set "Space
 * between icon and text" is the distance from the icon container to the
 * nearest text box, and a set "Space between title and text" is the distance
 * from the title to the text, in every icon position, at desktop and mobile
 * widths. Every edge is an element's border box; text that starts or ends
 * with bare text is measured from the text box.
 */

// Mirror serialize_block_attributes() so the seeded content matches what the
// block editor stores.
const escapeBlockAttrs = ( attrs ) => JSON.stringify( attrs )
	.replace( /--/g, '\\u002d\\u002d' )
	.replace( /</g, '\\u003c' )
	.replace( />/g, '\\u003e' )
	.replace( /&/g, '\\u0026' );

const featuresBlock = ( widgetData ) => `<!-- wp:sowb/siteorigin-widget-features-widget ${ escapeBlockAttrs( {
	widgetClass: 'SiteOrigin_Widget_Features_Widget',
	widgetData,
} ) } /-->`;

const POSITIONS = [ 'top', 'right', 'bottom', 'left' ];

const TEXTS = {
	headingAndList: { title: true, text: '<h4>Heading</h4><p>Text</p><ul><li>End</li></ul>' },
	paragraphs: { title: true, text: '<p>Text</p><p>More</p>' },
	bareText: { title: true, text: 'Plain text' },
	titleOnly: { title: true, text: '' },
	textOnly: { title: false, text: '<ul><li>Start</li></ul><p>Text</p><h4>End</h4>' },
	mixedStart: { title: false, text: 'Intro<p>Next</p>' },
	mixedEnd: { title: false, text: '<ul><li>One</li></ul>Outro' },
};

const TALL = '<p>A taller feature.</p><p>A taller feature.</p><p>A taller feature.</p>';

/**
 * One Features widget: a feature per position. The right feature is the
 * tallest, so a bottom aligned row is stretched.
 */
const widgetData = ( { text, iconSpacing = '', titleSpacing = '', perRow = 3, bottomAlign = false, more = false, responsive = true } ) => ( {
	features: POSITIONS.map( ( position ) => ( {
		icon: 'fontawesome-star',
		container_color: '#404040',
		container_position: position,
		title: TEXTS[ text ].title ? `Feature ${ position }` : '',
		text: position === 'right' ? TEXTS[ text ].text + TALL : TEXTS[ text ].text,
		more_text: more ? 'Read more' : '',
		more_url: '',
	} ) ),
	fonts: {
		title_options: {
			tag: 'h5',
			size: '28px',
			margin_bottom: titleSpacing,
		},
	},
	container_shape: 'round',
	container_size: '84px',
	icon_size: '28px',
	icon_spacing: iconSpacing,
	per_row: perRow,
	responsive,
	more_text_bottom_align: bottomAlign,
	feature_spacing: '25px 25px',
	feature_spacing_mobile: '25px 25px',
	title_link: false,
	icon_link: false,
	new_window: false,
} );

const iconCases = [];

for ( const iconSpacing of [ '20px', '0px' ] ) {
	for ( const perRow of [ 3, 1 ] ) {
		for ( const bottomAlign of [ false, true ] ) {
			for ( const more of [ false, true ] ) {
				for ( const text of Object.keys( TEXTS ) ) {
					iconCases.push( { iconSpacing, perRow, bottomAlign, more, text } );
				}
			}
		}
	}
}

for ( const bottomAlign of [ false, true ] ) {
	for ( const more of [ false, true ] ) {
		iconCases.push( { iconSpacing: '20px', perRow: 3, bottomAlign, more, text: 'headingAndList', responsive: false } );
	}
}

const titleCases = [];

for ( const titleSpacing of [ '8px', '0px', '0.5em' ] ) {
	for ( const bottomAlign of [ false, true ] ) {
		for ( const text of [ 'headingAndList', 'paragraphs', 'bareText', 'textOnly' ] ) {
			// textOnly has no title; give it one here so the title meets a list.
			titleCases.push( { titleSpacing, bottomAlign, text, forceTitle: text === 'textOnly' } );
		}
	}
}

const titleWidgetData = ( c ) => {
	const data = widgetData( c );

	if ( c.forceTitle ) {
		data.features.forEach( ( feature ) => {
			feature.title = `Feature ${ feature.container_position }`;
		} );
	}

	return data;
};

/**
 * Runs in the page: the measured gaps of every feature of every list, in
 * document order. Each entry is { gap, title, fontSize } or null where the
 * case has no such gap.
 */
const measure = () => {
	const box = ( el ) => el.getBoundingClientRect();

	// The first or last element child, or the element itself when its
	// content starts or ends with bare text.
	const edgeChild = ( el, first ) => {
		const nodes = Array.from( el.childNodes ).filter( ( node ) => ! ( node.nodeType === 3 && ! node.textContent.trim() ) && node.nodeType !== 8 );
		const node = first ? nodes[ 0 ] : nodes[ nodes.length - 1 ];

		return node && node.nodeType === 1 ? node : null;
	};

	const textEdge = ( textwidget, first ) => {
		let el = textwidget;

		while ( /\b(textwidget|sow-features-feature-content|sow-features-feature-text)\b/.test( el.className ) ) {
			const child = edgeChild( el, first );

			if ( ! child ) {
				break;
			}

			el = child;
		}

		return el;
	};

	return Array.from( document.querySelectorAll( '.sow-features-list' ) ).map( ( list ) => Array.from( list.querySelectorAll( ':scope > li' ) ).map( ( li ) => {
		const icon = li.querySelector( '.sow-icon-container' );
		const textwidget = li.querySelector( '.textwidget' );
		const outerMore = li.querySelector( ':scope > p.sow-more-text' );
		const i = box( icon );
		const out = { position: li.className.match( /position-(\w+)/ )[ 1 ] };

		// Which side of the icon the text column is on, from the boxes.
		const t = box( textwidget );

		if ( i.bottom <= t.top + 1 ) {
			// Icon above: the next box below it.
			const below = outerMore && box( outerMore ).top < t.top ? outerMore : textEdge( textwidget, true );
			out.iconGap = box( below ).top - i.bottom;
		} else if ( i.top >= t.bottom - 1 ) {
			// Icon below: the box right above it.
			const above = outerMore ? outerMore : textEdge( textwidget, false );
			out.iconGap = i.top - box( above ).bottom;
		} else if ( i.right <= t.left + 1 ) {
			out.iconGap = t.left - i.right;
		} else {
			out.iconGap = i.left - t.right;
		}

		const title = li.querySelector( '.sow-features-feature-title' );
		const text = li.querySelector( '.sow-features-feature-text' );

		if ( title && text ) {
			out.titleGap = box( textEdge( text, true ) ).top - box( title ).bottom;
			out.titleFontSize = parseFloat( getComputedStyle( title ).fontSize );
		}

		return out;
	} ) );
};

const label = ( c, width, position ) => `${ JSON.stringify( c ) } at ${ width }, ${ position } icon`;

test.describe( 'Features spacing settings', () => {
	let requestUtils;
	let page;
	let pageId;
	let pageLink;

	test.beforeAll( async ( { browser }, testInfo ) => {
		requestUtils = await setupRequestUtils( { browser }, testInfo );

		const content = [
			...iconCases.map( ( c ) => featuresBlock( widgetData( c ) ) ),
			...titleCases.map( ( c ) => featuresBlock( titleWidgetData( c ) ) ),
		].join( '\n\n' );

		const created = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/pages',
			data: {
				title: 'Features spacing gaps (e2e)',
				status: 'publish',
				content,
			},
		} );

		pageId = created.id;
		pageLink = created.link;
	} );

	test.afterAll( async () => {
		if ( pageId ) {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/pages/${ pageId }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	} );

	for ( const width of [ 1280, 390 ] ) {
		test( `each set spacing is the gap it names at ${ width }px`, async ( { browser } ) => {
			page = await browser.newPage( { viewport: { width, height: 900 } } );
			await page.goto( pageLink );

			const lists = await page.evaluate( measure );

			expect( lists.length ).toBe( iconCases.length + titleCases.length );

			lists.forEach( ( features ) => expect( features.length ).toBe( 4 ) );

			iconCases.forEach( ( c, n ) => {
				const expected = parseFloat( c.iconSpacing );

				lists[ n ].forEach( ( f ) => {
					expect.soft( f.iconGap, label( c, width, f.position ) ).toBeGreaterThanOrEqual( expected - 1 );
					expect.soft( f.iconGap, label( c, width, f.position ) ).toBeLessThanOrEqual( expected + 1 );
				} );
			} );

			titleCases.forEach( ( c, n ) => {
				lists[ iconCases.length + n ].forEach( ( f ) => {
					const expected = c.titleSpacing.endsWith( 'em' ) ? parseFloat( c.titleSpacing ) * f.titleFontSize : parseFloat( c.titleSpacing );

					expect.soft( f.titleGap, label( c, width, f.position ) ).toBeGreaterThanOrEqual( expected - 1 );
					expect.soft( f.titleGap, label( c, width, f.position ) ).toBeLessThanOrEqual( expected + 1 );
				} );
			} );

			await page.close();
		} );
	}
} );
