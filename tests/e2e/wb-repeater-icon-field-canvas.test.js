const {
	expect,
	test
} = require( '@playwright/test' );

const common = require( 'siteorigin-tests-common/playwright/common' );

const {
	addBlock,
	doLogin,
	setupAdminE2E,
	setupRequestUtils,
} = common;

const {
	openSection,
} = require( 'siteorigin-tests-common/playwright/utilities/widgets-bundle' );

test.describe.configure( { mode: 'serial' } );

/**
 * A repeater item added inside the block editor canvas iframe holds fields
 * that only the top-window widget block script can initialise, through the
 * sowbBlockFormInit message it sends when it hears sowrepeaterfieldsadded.
 * The icon field is one: its picker is bound by that message alone when the
 * form is in an iframe, so the field inside a freshly added Hero button, or
 * a freshly added Price Table feature, must end up initialised and its
 * Choose Icon button must open the picker.
 */
const waitForPostEditorCanvas = async ( page, admin ) => {
	await page.locator( 'iframe[name="editor-canvas"]' ).waitFor( { state: 'attached', timeout: 20000 } );
	await admin.editor.canvas.locator( 'body' ).waitFor( { state: 'visible', timeout: 20000 } );
};

/**
 * The bug only exists when the editor canvas is an iframe, which the post
 * editor uses under a block theme. With a classic theme the form renders in
 * the top document and every field initialises through the ordinary
 * sowsetupformfield path, so a run there proves nothing. Fail loudly rather
 * than pass on the wrong path.
 */
const expectBlockThemeAndIframedCanvas = async ( page, requestUtils ) => {
	const themes = await requestUtils.rest( {
		path: '/wp/v2/themes',
		params: { status: 'active' },
	} );
	const active = Array.isArray( themes ) ? themes[ 0 ] : null;

	expect( active && active.is_block_theme, 'the active theme must be a block theme so the editor canvas is an iframe' ).toBe( true );

	await expect( page.locator( 'iframe[name="editor-canvas"]' ) ).toHaveCount( 1 );
};

const setupPublishedPostEditor = async ( page, title ) => {
	const requestUtils = await setupRequestUtils();
	const post = await requestUtils.createPost( {
		status: 'publish',
		title,
		content: '',
	} );
	const admin = await setupAdminE2E( page );

	await admin.editPost( post.id );
	await waitForPostEditorCanvas( page, admin );
	await expectBlockThemeAndIframedCanvas( page, requestUtils );

	return {
		admin,
		post,
		requestUtils,
	};
};

/**
 * The widget form under test must live inside the canvas iframe's document,
 * not the top document. A block can hold more than one form node (a widget
 * field inside a repeater template renders its own), so the count in the
 * canvas is at least one and the count in the top document is zero.
 */
const expectFormInsideCanvas = async ( page, blockName ) => {
	const placement = await page.evaluate( ( name ) => {
		const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
		const inCanvas = iframe && iframe.contentDocument
			? iframe.contentDocument.querySelectorAll( `.wp-block[data-type="${ name }"] .siteorigin-widget-form-main` ).length
			: 0;
		const inTop = document.querySelectorAll( `.wp-block[data-type="${ name }"] .siteorigin-widget-form-main` ).length;

		return { inCanvas, inTop };
	}, blockName );

	expect( placement.inTop ).toBe( 0 );
	expect( placement.inCanvas ).toBeGreaterThanOrEqual( 1 );
};

/**
 * Clicks a repeater's Add control and returns the item it appended, expanded.
 */
const addRepeaterItem = async ( repeater ) => {
	const items = repeater.locator( '> .siteorigin-widget-field-repeater-items > .siteorigin-widget-field-repeater-item' );
	const before = await items.count();

	await repeater.locator( '> .siteorigin-widget-field-repeater-add' ).click();
	await expect( items ).toHaveCount( before + 1, { timeout: 10000 } );

	const item = items.nth( before );
	const top = item.locator( '> .siteorigin-widget-field-repeater-item-top' );
	const form = item.locator( '> .siteorigin-widget-field-repeater-item-form' );

	if ( ! ( await form.isVisible().catch( () => false ) ) ) {
		await top.click();
	}
	await expect( form ).toBeVisible( { timeout: 10000 } );

	return item;
};

/**
 * The icon field inside a newly added repeater item must be initialised,
 * and a real click on Choose Icon must open the picker.
 */
const expectIconFieldToOpen = async ( scope ) => {
	const iconField = scope.locator( '.siteorigin-widget-field-type-icon' ).first();

	await expect( iconField ).toBeVisible( { timeout: 10000 } );
	await expect( iconField ).toHaveAttribute( 'data-initialized', 'true', { timeout: 10000 } );

	const selector = iconField.locator( '.siteorigin-widget-icon-selector' );
	await expect( selector ).toBeHidden();

	await iconField.locator( '.siteorigin-widget-icon-selector-current' ).click();

	await expect( selector ).toBeVisible( { timeout: 10000 } );
	await expect( selector.locator( '.siteorigin-widget-icon-family' ) ).toBeVisible();
};

test.beforeEach( async ( { page } ) => {
	await doLogin( page );
} );

test.describe( 'Icon field inside a repeater item added in the editor canvas', () => {
	test( 'Hero: a button added to a new frame opens its icon picker', async ( { page } ) => {
		const blockName = 'sowb/siteorigin-widget-hero-widget';
		const { requestUtils, post, admin } = await setupPublishedPostEditor( page, 'WB canvas icon field: Hero' );

		try {
			const widget = await addBlock( admin, blockName, 120 );
			await expectFormInsideCanvas( page, blockName );
			const form = widget.locator( '.siteorigin-widget-form.siteorigin-widget-form-main' );

			const frames = form.locator( '.siteorigin-widget-field-frames .siteorigin-widget-field-repeater' ).first();
			const frame = await addRepeaterItem( frames );

			const buttons = frame.locator( '.siteorigin-widget-field-buttons .siteorigin-widget-field-repeater' ).first();
			const button = await addRepeaterItem( buttons );

			await openSection( 'button_icon', button );
			await expectIconFieldToOpen( button );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	} );

	test( 'Price Table: a feature added to a new column opens its icon picker', async ( { page } ) => {
		const blockName = 'sowb/siteorigin-widget-pricetable-widget';
		const { requestUtils, post, admin } = await setupPublishedPostEditor( page, 'WB canvas icon field: Price Table' );

		try {
			const widget = await addBlock( admin, blockName, 120 );
			await expectFormInsideCanvas( page, blockName );
			const form = widget.locator( '.siteorigin-widget-form.siteorigin-widget-form-main' );

			const columns = form.locator( '.siteorigin-widget-field-columns .siteorigin-widget-field-repeater' ).first();
			const column = await addRepeaterItem( columns );

			const features = column.locator( '.siteorigin-widget-field-features .siteorigin-widget-field-repeater' ).first();
			const feature = await addRepeaterItem( features );

			await expectIconFieldToOpen( feature );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	} );
} );
