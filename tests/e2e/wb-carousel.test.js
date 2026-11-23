const {
	expect,
	test
} = require( '@playwright/test' );

const { setupRequestUtils } = require( 'siteorigin-tests-common/playwright/common' );

const { widgetBlock } = require( './helpers/widget-block-abilities' );

/**
 * The Anything and Post Carousels render on the front end: each starts, is
 * visible and shows its slides, and the Post Carousel's load-more address is
 * the site's own host, so the script will use it. A page with both carousels is
 * published and loaded with no hostile markup.
 */

const ANYTHING_BLOCK = 'sowb/siteorigin-widget-anything-carousel-widget';
const ANYTHING_CLASS = 'SiteOrigin_Widget_Anything_Carousel_Widget';
const POST_BLOCK = 'sowb/siteorigin-widget-postcarousel-widget';
const POST_CLASS = 'SiteOrigin_Widget_PostCarousel_Widget';

const anythingItems = () => [ 'One', 'Two', 'Three', 'Four', 'Five', 'Six' ].map( ( label, i ) => ( {
	title: `Item ${ label }`,
	content_type: 'text',
	content_text: `Anything carousel item ${ label } text. `.repeat( i % 3 === 2 ? 12 : 2 ),
	autop: true,
} ) );

const wrapper = ( page, widget ) => page.locator( `.so-widget-sow-${ widget } .sow-carousel-wrapper` ).first();
const realSlides = ( root ) => root.evaluate( ( el ) => el.querySelectorAll( '.slick-slide:not(.slick-cloned)' ).length );

test.describe( 'Carousels on the front end', () => {
	let requestUtils;
	const created = [];
	let pageLink;

	test.beforeAll( async () => {
		requestUtils = await setupRequestUtils();

		// Enough posts that the Post Carousel has a further page it could load.
		for ( let i = 1; i <= 14; i++ ) {
			const post = await requestUtils.rest( {
				method: 'POST',
				path: '/wp/v2/posts',
				data: { title: `Carousel e2e post ${ i }`, status: 'publish', content: `<p>Post ${ i } body.</p>` },
			} );
			created.push( `/wp/v2/posts/${ post.id }` );
		}

		const content = [
			widgetBlock( ANYTHING_BLOCK, { widgetClass: ANYTHING_CLASS, widgetData: { title: 'E2E anything carousel', items: anythingItems() } } ),
			widgetBlock( POST_BLOCK, { widgetClass: POST_CLASS, widgetData: { title: 'E2E post carousel', carousel_settings: { loop: false } } } ),
		].join( '\n\n' );

		const pageRecord = await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/pages',
			data: { title: 'Carousel e2e page', status: 'publish', content },
		} );
		created.push( `/wp/v2/pages/${ pageRecord.id }` );
		pageLink = pageRecord.link;
	} );

	test.afterAll( async () => {
		for ( const path of created.reverse() ) {
			await requestUtils.rest( { method: 'DELETE', path, params: { force: true } } ).catch( () => {} );
		}
	} );

	test( 'the Anything and Post Carousels start, are visible and show their slides', async ( { page } ) => {
		const errors = [];
		page.on( 'pageerror', ( e ) => errors.push( e.message ) );
		await page.goto( pageLink );

		for ( const widget of [ 'anything-carousel', 'post-carousel' ] ) {
			const root = wrapper( page, widget );
			await expect( root.locator( '.slick-initialized' ) ).toHaveCount( 1 );
			await expect( root ).toHaveCSS( 'opacity', '1' );
			expect( await realSlides( root ) ).toBeGreaterThan( 0 );
		}

		expect( errors ).toEqual( [] );
	} );

	test( "the Post Carousel's load-more address is the site's own host", async ( { page } ) => {
		await page.goto( pageLink );

		const root = wrapper( page, 'post-carousel' );
		await expect( root.locator( '.slick-initialized' ) ).toHaveCount( 1 );

		// The script fetches the next page from data-ajax-url only when it
		// resolves to the page's own host; the printed address must be same-host.
		const sameHost = await root.evaluate( ( el ) => {
			const resolved = new URL( el.getAttribute( 'data-ajax-url' ), document.baseURI );
			return ( resolved.protocol === 'http:' || resolved.protocol === 'https:' )
				&& resolved.hostname === new URL( document.baseURI ).hostname;
		} );
		expect( sameHost ).toBe( true );
	} );
} );
