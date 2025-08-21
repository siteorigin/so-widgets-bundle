const {
	addBlock,
	doLogin,
	openSiteEditorCanvas,
	initializeAdmin,
} = require('siteorigin-tests-common/playwright/common');

const {
	getField
} = require('siteorigin-tests-common/playwright/utilities/widgets-bundle');

const {
	uploadImageToMediaLibrary
} = require('siteorigin-tests-common/playwright/utilities/media');

/**
 * Prepares the Site Editor test environment and inserts the specified block.
 *
 * This helper:
 * - Initializes the Admin instance for the given page.
 * - Navigates directly to the Site Editor canvas for speed.
 * - Waits for the editor canvas to be ready.
 * - Inserts the specified block and returns its widget container.
 *
 * @async
 * @param {import('@playwright/test').Page} page - The Playwright page object.
 * @param {string} blockName - The block's full name.
 *
 * @returns {Promise<{admin: Admin, widget: Locator}>} The initialized admin and widget locator.
 */
const testPrep = async( page, blockName ) => {
	const admin = await initializeAdmin( page );
	await openSiteEditorCanvas( page, admin );

	const widget = await addBlock( admin, blockName );

	// Give time for fields to be set up.
	await page.waitForTimeout( 1000 );

	return {
		admin,
		widget,
	};
};

/**
 * Runs before each test to log in to WordPress.
 *
 * Ensures the test user is authenticated before running any test case.
 *
 * @async
 * @param {Object} context - The Playwright test context.
 * @param {import('@playwright/test').Page} context.page - The Playwright page object.
 * @returns {Promise<void>} Resolves when login is complete.
 */
test.beforeEach( async ( { page } ) => {
	await doLogin( page );
} );
