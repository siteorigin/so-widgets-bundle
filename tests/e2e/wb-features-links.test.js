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
 * Measures the Features link settings on a rendered page: the title, icon
 * and More links, the linked column overlay, a bottom aligned More link,
 * and new window, by their markup, their boxes and real clicks, at desktop
 * and mobile widths, in each theme. Every link points at a destination
 * page the spec publishes, so a click proves where it lands.
 */

const TARGET_TITLE = 'Features link target (e2e)';
const MORE = 'Read more';

const feature = ( url, extra = {} ) => ( {
	icon: 'fontawesome-star',
	container_color: '#404040',
	container_position: 'top',
	title: 'Feature title',
	text: '<p>Text</p>',
	more_text: MORE,
	more_url: url,
	...extra,
} );

const CASES = {
	links: ( url ) => ( { title_link: true, icon_link: true, features: [ feature( url ) ] } ),
	linksNewWindow: ( url ) => ( { title_link: true, icon_link: true, new_window: true, features: [ feature( url ) ] } ),
	overlay: ( url ) => ( { link_feature: true, features: [ feature( url ) ] } ),
	overlayNewWindow: ( url ) => ( { link_feature: true, new_window: true, features: [ feature( url ) ] } ),
	overlayNoMoreText: ( url ) => ( { link_feature: true, features: [ feature( url, { more_text: '' } ) ] } ),
	// The first feature is short; the second, taller one stretches the row.
	bottomMore: ( url ) => ( {
		more_text_bottom_align: true,
		features: [
			feature( url ),
			feature( url, { text: '<p>A taller feature.</p><p>A taller feature.</p><p>A taller feature.</p><p>A taller feature.</p>' } ),
		],
	} ),
	noUrl: () => ( { title_link: true, icon_link: true, link_feature: false, features: [ feature( '' ) ] } ),
};

const NAMES = Object.keys( CASES );

// The default responsive breakpoint.
const BREAKPOINT = 780;

const widgetData = ( settings ) => ( {
	container_shape: 'round',
	container_size: '84px',
	icon_size: '24px',
	per_row: 3,
	responsive: true,
	title_link: false,
	icon_link: false,
	link_feature: false,
	new_window: false,
	more_text_bottom_align: false,
	...settings,
} );

/**
 * Runs in the page: the links of the first feature of each case, and for the
 * bottom aligned case, the boxes of both features and their More links.
 */
const measure = ( names ) => {
	const box = ( el ) => {
		const b = el.getBoundingClientRect();

		return { left: b.left, top: b.top, right: b.right, bottom: b.bottom, width: b.width, height: b.height };
	};

	const anchor = ( a ) => a && {
		href: a.href,
		target: a.getAttribute( 'target' ),
		rel: a.getAttribute( 'rel' ),
	};

	const lists = Array.from( document.querySelectorAll( '.sow-features-list' ) );

	return Object.fromEntries( names.map( ( name, n ) => {
		const items = Array.from( lists[ n ].querySelectorAll( ':scope > li' ) );
		const li = items[ 0 ];
		const overlay = li.querySelector( '.sow-features-feature-linked-column' );
		const out = {
			anchors: Array.from( li.querySelectorAll( 'a' ) ).map( anchor ),
			title: anchor( li.querySelector( '.sow-features-feature-title a' ) ),
			iconTag: li.querySelector( '.sow-icon-container' ).tagName,
			icon: anchor( li.querySelector( 'a.sow-icon-container' ) ),
			more: anchor( li.querySelector( 'p.sow-more-text a' ) ),
			feature: box( li ),
		};

		if ( overlay ) {
			const label = overlay.querySelector( '.so-sr-only' );

			// Instant, as a theme may scroll smoothly.
			li.scrollIntoView( { block: 'center', behavior: 'instant' } );

			const b = li.getBoundingClientRect();
			const hit = document.elementFromPoint( b.right - 4, b.bottom - 4 );

			out.overlay = {
				...anchor( overlay ),
				box: box( overlay ),
				feature: box( li ),
				hitsOverlay: hit === overlay,
				hit: hit && `${ hit.tagName }.${ hit.className }`,
				label: label && box( label ),
			};
		}

		if ( name === 'bottomMore' ) {
			out.rows = items.map( ( item ) => ( {
				feature: box( item ),
				more: box( item.querySelector( 'p.sow-more-text' ) ),
			} ) );
		}

		return [ name, out ];
	} ) );
};

const checkSameWindow = ( a, url, message ) => {
	expect.soft( a, message ).toBeTruthy();
	expect.soft( a?.href, `${ message }: href` ).toBe( url );
	expect.soft( a?.target, `${ message }: target` ).toBeNull();
	expect.soft( a?.rel, `${ message }: rel` ).toBeNull();
};

const checkNewWindow = ( a, url, message ) => {
	expect.soft( a, message ).toBeTruthy();
	expect.soft( a?.href, `${ message }: href` ).toBe( url );
	expect.soft( a?.target, `${ message }: target` ).toBe( '_blank' );
	expect.soft( ( a?.rel || '' ).split( ' ' ), `${ message }: rel` ).toEqual( expect.arrayContaining( [ 'noopener', 'noreferrer' ] ) );
};

const checkOverlay = ( m, url, message, check ) => {
	expect.soft( m.overlay, `${ message }: overlay` ).toBeTruthy();
	check( m.overlay, url, `${ message }: overlay` );
	expectNear( m.overlay.box.left, m.overlay.feature.left, `${ message }: overlay left` );
	expectNear( m.overlay.box.top, m.overlay.feature.top, `${ message }: overlay top` );
	expectNear( m.overlay.box.width, m.overlay.feature.width, `${ message }: overlay width` );
	expectNear( m.overlay.box.height, m.overlay.feature.height, `${ message }: overlay height` );
	expect.soft( m.overlay.hitsOverlay, `${ message }: an empty area of the feature is the overlay, hit ${ m.overlay.hit }` ).toBe( true );
	expect.soft( m.more, `${ message }: no More link anchor` ).toBeNull();
	check( m.title, url, `${ message }: title link` );
};

const CHECKS = {
	links: ( m, url, message ) => {
		check3( m, url, message, checkSameWindow );
	},
	linksNewWindow: ( m, url, message ) => {
		check3( m, url, message, checkNewWindow );
	},
	overlay: ( m, url, message ) => {
		checkOverlay( m, url, message, checkSameWindow );
		expect.soft( m.overlay.label.width <= 1 && m.overlay.label.height <= 1, `${ message }: overlay label is visually hidden` ).toBe( true );
	},
	overlayNewWindow: ( m, url, message ) => {
		checkOverlay( m, url, message, checkNewWindow );
	},
	bottomMore: ( m, url, message, width ) => {
		checkSameWindow( m.more, url, `${ message }: More link` );

		const [ short, tall ] = m.rows;

		if ( width > BREAKPOINT ) {
			// The taller feature stretches the row, so the short one's More
			// link sits at the foot of the row.
			expectNear( short.feature.height, tall.feature.height, `${ message }: the row is stretched` );
		}

		m.rows.forEach( ( row, i ) => expectNear( row.more.bottom, row.feature.bottom, `${ message }: More link ${ i } at the foot` ) );
	},
	noUrl: ( m, url, message ) => {
		expect.soft( m.anchors, `${ message }: anchors` ).toEqual( [] );
		expect.soft( m.iconTag, `${ message }: icon container` ).toBe( 'DIV' );
	},
};

// The title, icon and More links, the same window or a new one.
function check3( m, url, message, check ) {
	check( m.title, url, `${ message }: title link` );
	check( m.icon, url, `${ message }: icon link` );
	check( m.more, url, `${ message }: More link` );
	expect.soft( m.iconTag, `${ message }: icon container` ).toBe( 'A' );
	expect.soft( m.anchors.length, `${ message }: anchors` ).toBe( 3 );
}

// A click that does not navigate fails within this time, naming the link.
const NAVIGATION_TIMEOUT = 15000;

/**
 * The links to click: the case, and the element in its first feature.
 */
const CLICKS = [
	{ name: 'links', target: '.sow-features-feature-title a' },
	{ name: 'links', target: 'a.sow-icon-container' },
	{ name: 'bottomMore', target: 'p.sow-more-text a' },
	{ name: 'overlay', target: 'overlay' },
	{ name: 'linksNewWindow', target: '.sow-features-feature-title a', popup: true },
	{ name: 'linksNewWindow', target: 'a.sow-icon-container', popup: true },
	{ name: 'overlayNewWindow', target: 'overlay', popup: true },
];

test.describe( 'Features link settings', { tag: [ '@firefox', '@webkit' ] }, () => {
	const target = {};

	const featuresPage = useFeaturesPage( test, 'Features links (e2e)', async ( requestUtils ) => {
		const created = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/pages',
			data: { title: TARGET_TITLE, status: 'publish', content: '<!-- wp:paragraph --><p>Target</p><!-- /wp:paragraph -->' },
		} );

		target.id = created.id;
		target.link = created.link;

		return NAMES.map( ( name ) => featuresBlock( widgetData( CASES[ name ]( target.link ) ) ) ).join( '\n\n' );
	} );

	test.afterAll( async () => {
		if ( target.id ) {
			const deleted = await featuresPage.requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/pages/${ target.id }`,
				params: { force: true },
			} );

			expect( deleted.deleted ).toBe( true );
		}
	} );

	// `onPage` checks the open page before it closes.
	const measured = async ( browser, width, theme, onPage = async () => {} ) => {
		const page = await openFeaturesPage( browser, featuresPage, { width, theme } );
		const result = await page.evaluate( measure, NAMES );

		await onPage( page );
		await page.context().close();

		return result;
	};

	// The overlay of a case's first feature.
	const overlayOf = ( page, name ) => page.locator( '.sow-features-list' ).nth( NAMES.indexOf( name ) ).locator( '.sow-features-feature-linked-column' ).first();

	for ( const theme of THEMES ) {
		for ( const width of [ 1280, 390 ] ) {
			test( `links match their settings in the ${ theme.name } at ${ width }px`, async ( { browser } ) => {
				const result = await measured( browser, width, theme, async ( page ) => {
					await expect.soft( overlayOf( page, 'overlay' ), 'overlay name' ).toHaveAccessibleName( MORE );
				} );

				for ( const name of NAMES.filter( ( n ) => n !== 'overlayNoMoreText' ) ) {
					CHECKS[ name ]( result[ name ], target.link, `${ name }, ${ theme.name } at ${ width }px`, width );
				}
			} );

			test( `a linked column with no More text has an accessible name in the ${ theme.name } at ${ width }px`, async ( { browser } ) => {
				test.fail( true, 'https://github.com/siteorigin/so-widgets-bundle/issues/2481' );

				await measured( browser, width, theme, async ( page ) => {
					await expect( overlayOf( page, 'overlayNoMoreText' ) ).toHaveAccessibleName( /\S/, { timeout: 1000 } );
				} );
			} );

			test( `each link lands on its URL in the ${ theme.name } at ${ width }px`, async ( { browser } ) => {
				for ( const click of CLICKS ) {
					const page = await openFeaturesPage( browser, featuresPage, { width, theme } );
					const message = `${ click.name } ${ click.target }, ${ theme.name } at ${ width }px`;
					const list = page.locator( '.sow-features-list' ).nth( NAMES.indexOf( click.name ) );
					const item = list.locator( ':scope > li' ).first();
					const start = page.url();

					// The overlay is clicked where nothing else in the feature is.
					const doClick = async () => {
						if ( click.target === 'overlay' ) {
							const b = await item.boundingBox();
							await item.click( { position: { x: b.width - 4, y: b.height - 4 } } );
						} else {
							await item.locator( click.target ).click();
						}
					};

					if ( click.popup ) {
						const [ popup ] = await Promise.all( [ page.context().waitForEvent( 'page', { timeout: NAVIGATION_TIMEOUT } ), doClick() ] ).catch( ( error ) => {
							throw new Error( `${ message }: no popup. ${ error.message }` );
						} );

						await popup.waitForLoadState();
						await expect.soft( popup, message ).toHaveTitle( new RegExp( TARGET_TITLE.replace( /[()]/g, '\\$&' ) ) );
						expect.soft( page.url(), `${ message }: the page stays` ).toBe( start );
					} else {
						await Promise.all( [ page.waitForURL( target.link, { timeout: NAVIGATION_TIMEOUT } ), doClick() ] ).catch( ( error ) => {
							throw new Error( `${ message }: no navigation. ${ error.message }` );
						} );
						await expect.soft( page, message ).toHaveTitle( new RegExp( TARGET_TITLE.replace( /[()]/g, '\\$&' ) ) );
					}

					await page.context().close();
				}
			} );
		}
	}
} );
