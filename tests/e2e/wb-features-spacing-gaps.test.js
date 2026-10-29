const {
	expect,
	test
} = require( '@playwright/test' );

const {
	expectNear,
	featuresBlock,
	openFeaturesPage,
	useFeaturesPage,
} = require( './helpers/features' );

/**
 * Measures the Features spacing settings on a rendered page: a set "Space
 * between icon and text" is the distance from the icon container to the
 * nearest text box, and a set "Space between title and text" is the distance
 * from the title to the text, in every icon position, at desktop and mobile
 * widths. Every edge is an element's border box; text that starts or ends
 * with bare text is measured from the text box.
 */

const TEXTS = {
	headingAndList: { title: true, text: '<h4>Heading</h4><p>Text</p><ul><li>End</li></ul>' },
	paragraphs: { title: true, text: '<p>Text</p><p>More</p>' },
	bareText: { title: true, text: 'Plain text' },
	titleOnly: { title: true, text: '' },
	textOnly: { title: false, text: '<ul><li>Start</li></ul><p>Text</p><h4>End</h4>' },
	mixedStart: { title: false, text: 'Intro<p>Next</p>' },
	mixedEnd: { title: false, text: '<ul><li>One</li></ul>Outro' },
	// With the More link on, a feature that holds only the link.
	moreOnly: { title: false, text: '' },
};

/**
 * A paragraph top margin, as many themes set; the test theme sets none.
 */
const THEME_MARGINS = '<!-- wp:html --><style>.sow-features-list p { margin-top: 1em; }</style><!-- /wp:html -->';

/**
 * A feature with more text than the others. It sits in the first row, with
 * the top and bottom features, so a bottom aligned row is stretched; the
 * features of the case itself keep the case's content.
 */
const tallFeature = ( more ) => ( {
	icon: 'fontawesome-star',
	container_color: '#404040',
	container_position: 'right',
	title: 'Tall feature',
	text: '<p>A taller feature.</p><p>A taller feature.</p><p>A taller feature.</p><p>A taller feature.</p>',
	more_text: more ? 'Read more' : '',
	more_url: '',
} );

// The first row (at three per row) holds top, bottom and the tall feature.
const FEATURE_ORDER = [ 'top', 'bottom', 'tall', 'right', 'left' ];

/**
 * One Features widget: a feature per position, plus the tall feature.
 */
const widgetData = ( { text, iconSpacing = '', titleSpacing = '', perRow = 3, bottomAlign = false, more = false, responsive = true } ) => ( {
	features: FEATURE_ORDER.map( ( position ) => position === 'tall' ? tallFeature( more ) : {
		icon: 'fontawesome-star',
		container_color: '#404040',
		container_position: position,
		title: TEXTS[ text ].title ? `Feature ${ position }` : '',
		text: TEXTS[ text ].text,
		more_text: more ? 'Read more' : '',
		more_url: '',
	} ),
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
					if ( text === 'moreOnly' && ! more ) {
						continue;
					}

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
	for ( const perRow of [ 3, 1 ] ) {
		for ( const bottomAlign of [ false, true ] ) {
			for ( const more of [ false, true ] ) {
				for ( const text of [ 'headingAndList', 'paragraphs', 'bareText', 'textOnly' ] ) {
					// textOnly has no title; give it one here so the title meets a list.
					titleCases.push( { titleSpacing, perRow, bottomAlign, more, text, forceTitle: text === 'textOnly' } );
				}
			}
		}
	}
}

for ( const bottomAlign of [ false, true ] ) {
	for ( const more of [ false, true ] ) {
		titleCases.push( { titleSpacing: '8px', perRow: 3, bottomAlign, more, text: 'headingAndList', forceTitle: false, responsive: false } );
	}
}

const titleWidgetData = ( c ) => {
	const data = widgetData( c );

	if ( c.forceTitle ) {
		data.features.forEach( ( feature ) => {
			feature.title = feature.title || `Feature ${ feature.container_position }`;
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
	// content starts or ends with bare text. An element with no height, as
	// the content wrapper of a feature with only a More link, is skipped.
	const edgeChild = ( el, first ) => {
		const nodes = Array.from( el.childNodes ).filter( ( node ) => ( node.nodeType === 3 && node.textContent.trim() ) || ( node.nodeType === 1 && box( node ).height > 0 ) );
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
			// Icon above: the next box below it. An empty text column, as in
			// a feature with only a bottom aligned More link, gives the link.
			const below = outerMore && ( box( outerMore ).top < t.top || ! textwidget.children.length ) ? outerMore : textEdge( textwidget, true );
			out.iconGap = box( below ).top - i.bottom;
			out.columnGap = t.top - i.bottom;

			if ( outerMore ) {
				out.moreToFoot = box( li ).bottom - box( outerMore ).bottom;
			}
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

/**
 * The bottom aligned More link of a top icon feature with nothing else sits
 * at the foot of a row that a taller feature stretches, as bottom align
 * intends; there the spacing is the gap to the empty text column. Elsewhere
 * it is the gap to the link.
 */
const atTheFootOfAStretchedRow = ( c, f, width ) => c.text === 'moreOnly' && c.bottomAlign && f.position === 'top' && c.perRow > 1 && width > 520;

const checkIconGap = ( c, f, message, width ) => {
	if ( atTheFootOfAStretchedRow( c, f, width ) ) {
		// The spacing sits above the empty text column, and the link at the
		// foot of the row.
		expectNear( f.columnGap, parseFloat( c.iconSpacing ), message );
		expectNear( f.moreToFoot, 0, message );
		expect.soft( f.iconGap, message ).toBeGreaterThan( parseFloat( c.iconSpacing ) + 1 );
		return;
	}

	expectNear( f.iconGap, parseFloat( c.iconSpacing ), message );
};

const checkTitleGap = ( c, f, message ) => expectNear(
	f.titleGap,
	c.titleSpacing.endsWith( 'em' ) ? parseFloat( c.titleSpacing ) * f.titleFontSize : parseFloat( c.titleSpacing ),
	message
);

// Four pages keep each save within the REST request timeout.
const PAGES = {
	icon: {
		cases: iconCases.filter( ( c ) => c.perRow === 3 ),
		data: widgetData,
		check: checkIconGap,
	},
	'one per row icon': {
		cases: iconCases.filter( ( c ) => c.perRow === 1 ),
		data: widgetData,
		check: checkIconGap,
	},
	title: {
		cases: titleCases.filter( ( c ) => c.perRow === 3 ),
		data: titleWidgetData,
		check: checkTitleGap,
	},
	'one per row title': {
		cases: titleCases.filter( ( c ) => c.perRow === 1 ),
		data: titleWidgetData,
		check: checkTitleGap,
	},
};

for ( const [ name, fixture ] of Object.entries( PAGES ) ) {
	// A describe block per page: after a failed test, the new worker saves
	// only the pages still to measure.
	test.describe( `Features ${ name } spacing setting`, { tag: [ '@firefox', '@webkit' ] }, () => {
		const featuresPage = useFeaturesPage(
			test,
			`Features spacing gaps, ${ name } (e2e)`,
			() => [ THEME_MARGINS, ...fixture.cases.map( ( c ) => featuresBlock( fixture.data( c ) ) ) ].join( '\n\n' )
		);

		for ( const width of [ 1280, 390 ] ) {
			test( `each set ${ name } spacing is the gap it names at ${ width }px`, async ( { browser } ) => {
				const page = await openFeaturesPage( browser, featuresPage, { width } );

				const lists = await page.evaluate( measure );

				expect( lists.length ).toBe( fixture.cases.length );

				lists.forEach( ( features ) => expect( features.length ).toBe( FEATURE_ORDER.length ) );

				fixture.cases.forEach( ( c, n ) => {
					lists[ n ].forEach( ( f ) => fixture.check( c, f, label( c, width, f.position ), width ) );
				} );

				await page.context().close();
			} );
		}
	} );
}
