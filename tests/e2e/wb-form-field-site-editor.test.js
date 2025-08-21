const {
	expect,
	test
} = require('@playwright/test');

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

/**
 * Validates the following for the Icon Widget in the Site Editor:
 * 1. Icon field: selection and rendering of Material Icons.
 * 2. Color field: color picker interaction and color application.
 * 3. Link field: link selection and value assignment.
 *
 * @param {Object} page The Playwright page object.
 */
test(
	'Test the Icon Widget',
async ( { page } ) => {
	const { widget } = await testPrep(
		page,
		'sowb/siteorigin-widget-icon-widget'
	);

	const iconField = await getField( widget, 'icon' );

	// Validate Icon field works as expected.
	const iconFieldSelector = iconField.locator('.siteorigin-widget-icon-selector-current');
	await expect(iconFieldSelector).toBeVisible();
	await iconFieldSelector.click({ force: true });

	const iconFieldContainer = iconField.locator('.siteorigin-widget-icon-selector');
	await expect(iconFieldContainer).toHaveCSS('display', 'block');

	const iconFieldIcons = iconFieldContainer.locator('.siteorigin-widget-icon-icons');
	await expect(iconFieldIcons).toBeVisible();

	// Switch to Material Icons to validate icons are loading correctly.
	const fontFamilySelector = iconFieldContainer.locator('.siteorigin-widget-icon-family');
	await expect(fontFamilySelector).toBeVisible();
	await fontFamilySelector.selectOption('materialicons');

	await expect(iconFieldIcons).not.toHaveClass('loading');

	// Search for the Home icon.
	const iconSearch = iconFieldContainer.locator('.siteorigin-widget-icon-search');
	await expect(iconSearch).toBeVisible({ timeout: 10000 });
	await iconSearch.fill('home');

	// Click the `Add Home` icon.
	// The timeout is required due to give time for finding the icon.
	const iconOption = iconFieldContainer.locator('[data-value="materialicons-sowm-regular-add_home"]');
	await expect(iconOption).toBeVisible({ timeout: 10000 });
	await iconOption.click();

	// Confirm icon has been set.
	const icon = iconField.locator('.siteorigin-widget-icon span');
	await expect(icon).toBeVisible();
	await expect(icon).toHaveClass(/sow-icon-materialicons/);

	// Validate Color field works as expected.
	const colorField = widget.locator('.siteorigin-widget-field-type-color');
	await expect(colorField).toBeVisible();

	const colorFieldButton = colorField.getByRole('button', { name: 'Select Colour' });
	await expect(colorFieldButton).toBeVisible();
	await colorFieldButton.click({ force: true });

	// Wait until the color picker has opened.
	await expect(colorFieldButton).toHaveClass(/wp-picker-open/);

	// Select the first color from the palette.
	const palette = colorField.locator('.iris-palette').first();
	await expect(palette).toBeVisible();
	await palette.click();

	// Verify the color has been applied.
	const colorPreview = colorField.locator('.wp-color-result');
	await expect(colorPreview).toBeVisible();
	await expect(colorPreview).toHaveCSS('background-color', 'rgb(0, 0, 0)');

	// Validate autocomplete field works as expected.
	const linkField = widget.locator('.siteorigin-widget-field-type-link');
	await expect(linkField).toBeVisible();
	const linkButton = linkField.locator('.select-content-button');
	await expect(linkButton).toBeVisible();
	await linkButton.click({ force: true });

	// Select the first post from the list.
	const postsList = linkField.locator('.posts');
	await expect(postsList).toBeVisible();
	const firstPost = postsList.locator('.post').first();
	await expect(firstPost).toBeVisible();
	await firstPost.click({ force: true });

	// Validate link text has been set.
	const linkInput = linkField.locator('.siteorigin-widget-input');
	await expect(linkInput).toBeVisible();
	await expect(linkInput).toHaveValue(/.+/);
} );
