const {
	expect,
	test
} = require( '@playwright/test' );

const {
	addBlock,
	doLogin,
	handleDialog,
	openSiteEditorCanvas,
	initializeAdmin,
	waitForRequestToFinish,
} = require( 'siteorigin-tests-common/playwright/common' );

const {
	getField
} = require( 'siteorigin-tests-common/playwright/utilities/widgets-bundle' );

const {
	uploadImageToMediaLibrary
} = require( 'siteorigin-tests-common/playwright/utilities/media' );

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

		const iconField = await getField( widget, 'icon', true );

		// Validate Icon field works as expected.
		const iconFieldSelector = iconField.locator( '.siteorigin-widget-icon-selector-current' );
		await expect( iconFieldSelector ).toBeVisible();
		await page.waitForTimeout( 1000 ); // Wait for the animation to complete.
		await iconFieldSelector.click( { force: true } );

		const iconFieldContainer = iconField.locator( '.siteorigin-widget-icon-selector' );
		await expect( iconFieldContainer ).toHaveCSS( 'display', 'block' );

		const iconFieldIcons = iconFieldContainer.locator( '.siteorigin-widget-icon-icons' );
		await expect( iconFieldIcons ).toBeVisible();

		// Switch to Material Icons to validate icons are loading correctly.
		const fontFamilySelector = iconFieldContainer.locator( '.siteorigin-widget-icon-family' );
		await expect( fontFamilySelector ).toBeVisible();
		await fontFamilySelector.selectOption( 'materialicons' );

		// Wait for icons to download.
		await waitForRequestToFinish(
			page,
			'siteorigin_widgets_get_icons'
		);

		// Search for the Home icon.
		const iconSearch = iconFieldContainer.locator( '.siteorigin-widget-icon-search' );
		await expect( iconSearch ).toBeVisible( { timeout: 10000 } );
		await iconSearch.fill( 'home' );

		await expect( iconFieldIcons ).not.toHaveClass( 'loading' );
		// Due to the large amount of icons being processed, wait 1s to allow for rendering.
		await page.waitForTimeout( 1000 );

		// Click the `Add Home` icon.
		const iconOption = iconFieldIcons.locator( '[data-value="materialicons-sowm-regular-add_home"]' );
		await expect( iconOption ).toBeVisible( { timeout: 10000 } );

		await iconOption.click( { force: true } );
		await iconOption.click( { force: true } );

		// Confirm icon has been set.
		const icon = iconFieldSelector.locator( '.siteorigin-widget-icon span' );
		await expect( icon ).toHaveClass( /sow-icon-materialicons/ );

		// Validate Color field works as expected.
		const colorField = widget.locator( '.siteorigin-widget-field-type-color' );
		await expect( colorField ).toBeVisible();

		const colorFieldButton = colorField.locator( 'button.wp-color-result' );
		await expect( colorFieldButton ).toBeVisible();
		await colorFieldButton.click( { force: true } );

		// Wait until the color picker has opened.
		await expect( colorFieldButton ).toHaveClass( /wp-picker-open/ );

		// Select the first color from the palette.
		const palette = colorField.locator( '.iris-palette' ).first();
		await expect( palette ).toBeVisible();
		await palette.click();

		// Verify the color has been applied.
		const colorPreview = colorField.locator( '.wp-color-result' );
		await expect( colorPreview ).toBeVisible();
		await expect( colorPreview ).toHaveCSS( 'background-color', 'rgb(0, 0, 0)' );

		// Validate autocomplete field works as expected.
		const linkField = widget.locator( '.siteorigin-widget-field-type-link' );
		await expect( linkField ).toBeVisible();
		const linkButton = linkField.locator( '.select-content-button' );
		await expect( linkButton ).toBeVisible();
		await linkButton.click( { force: true } );

		// Select the first post from the list.
		const postsList = linkField.locator( '.posts' );
		await expect( postsList ).toBeVisible();
		const firstPost = postsList.locator( '.post' ).first();
		await expect( firstPost ).toBeVisible();
		await firstPost.click( { force: true } );

		// Validate link text has been set.
		const linkInput = linkField.locator( '.siteorigin-widget-input' );
		await expect( linkInput ).toBeVisible();
		await expect( linkInput ).toHaveValue( /.+/ );
	}
);

/**
 * Validates the following for the SiteOrigin Editor widget in the Site Editor:
 * 1. TinyMCE field: media upload and image insertion.
 * 2. Ensures the TinyMCE editor is focused before media insertion.
 * 3. Confirms the <img> tag appears in the TinyMCE editor iframe after upload.
 *
 * @param {Object} page The Playwright page object.
 */
test(
	'Test the Editor widget.',
	async ( { page } ) => {
		const { admin, widget } = await testPrep(
			page,
			'sowb/siteorigin-widget-editor-widget'
		);

		const tinymceField = await getField( widget, 'tinymce', true );

		const visualModeButton = tinymceField.locator( '.wp-editor-tabs .switch-tmce' );
		const textModeButton = tinymceField.locator( '.wp-editor-tabs .switch-html' );
		await expect( textModeButton ).toBeVisible();
		await expect( visualModeButton ).toBeVisible();

		// Confirm mode switching works as expected.
		await textModeButton.click();
		await expect( visualModeButton ).toHaveAttribute( 'aria-pressed', 'false', { timeout: 10000 } );
		await expect( textModeButton ).toHaveAttribute( 'aria-pressed', 'true', { timeout: 10000 } );
		await visualModeButton.click();
		await expect( visualModeButton ).toHaveAttribute( 'aria-pressed', 'true' );
		await expect( textModeButton ).toHaveAttribute( 'aria-pressed', 'false' );

		// Confirm the "Add Media" button is visible and enabled.
		const addMediaButton = tinymceField.locator( '.siteorigin-widget-tinymce-add-media' );
		await expect( addMediaButton ).toBeVisible( { timeout: 10000 } );
		await expect( addMediaButton ).toHaveAttribute( 'data-editor', /.+/, { timeout: 10000 });

		await addMediaButton.click();

		await uploadImageToMediaLibrary(
			admin
		);

		// Wait for the <img> tag to appear in the TinyMCE editor iframe
		const iframe = tinymceField.frameLocator( 'iframe' );
		await expect( iframe.locator( 'img' ) ).toBeVisible( { timeout: 10000 } );
	}
);

/**
 * Validates the following for the Image widget in the Site Editor:
 * 1. Image field: initialization, visibility, and value setting.
 * 2. Image importer: modal interaction, image search, import, and prompt handling.
 * 3. Image clearing: Remove Image button functionality and value reset.
 * 4. Image field Media Library: uploading and inserting an image via media modal.
 * 5. Image field: verifies correct value after each image operation.
 * 6. Shape field: Validate searching/filtering for shapes.
 * 7. Shape field: Validates shape selection.
 * 8. Ensures correct UI state and value after each operation.
 *
 * @param {Object} page The Playwright page object.
 */
test(
	'Test the Image widget.',
	async ( { page } ) => {
		const { admin, widget } = await testPrep(
			page,
			'sowb/siteorigin-widget-image-widget'
		);

		const imageField = await getField( widget, 'media' );

		// Open Image Search modal.
		const mediaSearchButton = imageField.getByText( 'Image Search' );
		await expect( mediaSearchButton ).toBeVisible();
		await mediaSearchButton.click( { force: true } );

		// Ensure the importer modal is visible.
		await expect( imageField ).toHaveClass( /so-importing-image/, { timeout: 5000 } );

		const mediaSearchModal = page.locator( '#so-widgets-image-search' );
		const loadingIndicator = mediaSearchModal.locator( '.so-widgets-results-loading' );
		const mediaSearchModalInput = mediaSearchModal.locator( '.so-widgets-search-input' );
		const mediaSearchModalResults = mediaSearchModal.locator( '.so-widgets-image-results' );

		await expect( mediaSearchModalInput ).toBeVisible();

		// Search for an image.
		await mediaSearchModalInput.fill( 'test' );
		await mediaSearchModalInput.press( 'Enter' );

		await waitForRequestToFinish(
			page,
			'so_widgets_image_search'
		);

		// Select the first search result once it's visible.
		const firstResult = mediaSearchModalResults.locator( '.so-widgets-result' ).first().locator( '.so-widgets-result-image' );
		await expect( firstResult ).toBeVisible( { timeout: 10000 } );
		firstResult.click();

		// After clicking the image, a browser prompt will appear.
		// Accept it, and then wait for the loader to be visible.
		await handleDialog( page, 'accept', async ( dialog ) => {
			await expect( loadingIndicator ).toBeVisible( { timeout: 5000 } );
		} );

		// The importer is now running. Wait for the transfer to finish.
		await waitForRequestToFinish(
			page,
			'so_widgets_image_import'
		);

		// Ensure modal has closed.
		await expect( imageField ).not.toHaveClass( /so-importing-image/ );

		// Validate that the image has been set.
		const imageValue = imageField.locator( '.siteorigin-widget-input[type="hidden"]' );
		await expect( imageValue ).toHaveValue( /.+/ );

		// Clear the image.
		const clearButton = imageField.locator( '.media-remove-button' );
		await expect( clearButton ).toBeVisible();
		await expect( clearButton ).not.toHaveClass( 'remove-hide' );
		await clearButton.click();

		// Validate the image has been cleared.
		await expect( imageValue ).toHaveValue( '' );

		// Now try to upload an image.
		const addMediaButton = imageField.locator( '.media-upload-button' );
		await addMediaButton.click( { force: true } );

		await uploadImageToMediaLibrary( admin );

		// Validate the image has been set in the widget.
		await expect( imageValue ).toHaveValue( /.+/ );

		// Test the Shape field.
		const shapeSection = widget.locator( '.siteorigin-widget-field-image_shape' );
		const shapeEnableSetting = shapeSection.locator( '.siteorigin-widget-field-enable .siteorigin-widget-input' );
		await shapeSection.click();

		// Enable the shape field.
		await expect( shapeEnableSetting ).toBeVisible();
		await shapeEnableSetting.check();

		const shapeField = await getField( widget, 'image_shape', true );
		const shapeOpenButton = shapeField.locator( '.siteorigin-widget-shape-current' );
		const shapeList = shapeField.locator( '.siteorigin-widget-shapes' );
		await expect( shapeOpenButton ).toBeVisible();

		// Open the shape list.
		shapeOpenButton.click();
		await expect( shapeList ).toHaveClass( /siteorigin-widget-shapes-open/ );

		// Search for a shape.
		const shapeSearch = shapeField.locator( '.siteorigin-widget-shape-search' );
		await expect( shapeSearch ).toBeVisible();
		await shapeSearch.fill( 'Diamond' );
		await shapeSearch.press( 'Enter' );

		// Confirm there's only one shape visible.
		await expect( shapeList.locator( '.siteorigin-widget-shape:visible' ) ).toHaveCount( 1 );

		// Select that one shape.
		await shapeList.locator( '.siteorigin-widget-shape:visible' ).click();

		// Confirm the shape has been set by checking shapeField .siteorigin-widget-input
		await expect( shapeField.locator( '.siteorigin-widget-input' ) ).toHaveValue( 'diamond' );
	}
);

/**
 * Validates the following for the Blog widget in the Site Editor:
 * 1. Test that sections are able to be opened.
 * 2. That state emitters (checkbox and select) are working as expected and update related fields.
 * 3. That the Image Size field's custom size feature work as expected.
 * 4. That the preset field updates the featured image checkbox and triggers correct UI changes.
 * 5. Validates the date picker field is working as expected.
 *
 * @param {Object} page The Playwright page object.
 */
test(
	'Test the Blog widget.',
	async ( { page } ) => {
		const { admin, widget } = await testPrep(
			page,
			'sowb/siteorigin-widget-blog-widget'
		);

		// Open the Settings Section.
		const settingsSection = widget.locator( '.siteorigin-widget-field-settings' );
		const settingsSectionLabel = settingsSection.locator( ' > .siteorigin-widget-field-label' );
		await expect( settingsSection ).toBeVisible();
		await expect( settingsSectionLabel ).toBeVisible();

		await settingsSection.click();

		// Validate the Settings Section is open.
		await expect( settingsSectionLabel ).toHaveClass( /siteorigin-widget-section-visible/ );

		const featuredImageSetting = settingsSection.locator( '.siteorigin-widget-field-featured_image .siteorigin-widget-input' );
		const featuredImageSizeSetting = settingsSection.locator( '.siteorigin-widget-field-featured_image_size .siteorigin-widget-input-select' );
		await expect( featuredImageSizeSetting ).toBeVisible();
		await expect( featuredImageSetting ).toBeChecked();

		// Validate that the Image Size Custom Size setting works as expected.
		const customSizeWidth = settingsSection.locator( '.custom-size-wrapper .custom-image-size-width' );

		await expect( customSizeWidth ).toBeHidden();
		await featuredImageSizeSetting.selectOption( 'custom_size' );
		await expect( featuredImageSizeSetting ).toHaveValue( 'custom_size' );
		await expect( customSizeWidth ).toBeVisible();

		// Validate Checkbox field works as expected.
		await featuredImageSetting.uncheck();
		await expect( featuredImageSetting ).not.toBeChecked();
		await expect( featuredImageSizeSetting ).toBeHidden();

		// Validate Presets field by changing the preset to a preset that
		// will tick the featuredImageSetting checkbox.
		const presetsField = widget.locator( '.siteorigin-widget-field-template .siteorigin-widget-input' );
		await presetsField.selectOption( 'grid' );

		// Validate that featuredImageSetting is checked.
		await expect( featuredImageSetting ).toBeChecked();

		// Open the Post Query section.
		const postQuerySection = widget.locator( '.siteorigin-widget-field-posts' );
		await postQuerySection.click();

		const postQueryDateFrom = postQuerySection.locator( '.sowb-specific-date-after .after-picker' );
		await expect( postQueryDateFrom ).toBeVisible();

		// Activate the Dates From field so that the date picker pops up.
		await postQueryDateFrom.click( { force: true } );
		await postQueryDateFrom.focus();
		await expect( postQueryDateFrom ).toBeFocused();

		// Validate that that date picker is present, and then select the 15th.
		const datePicker = admin.editor.canvas.locator( '.pika-single:not(.is-hidden)' );
		await expect( datePicker ).toBeVisible();
		const day15Button = datePicker.locator( '.pika-button[data-pika-day="15"]' );
		await expect( day15Button ).toBeVisible();
		await day15Button.click();

		// Validate that a date has been set.
		await expect( postQueryDateFrom ).toHaveValue( /.+/ );
	}
);

/**
 * Validates the following for the Headline widget in the Site Editor:
 * 1. Test that sections are able to be opened.
 * 2. That state emitters (checkbox and select) are working as expected and update related fields.
 * 3. That the preset field updates the featured image checkbox and triggers correct UI changes.
 *
 * @param {Object} page The Playwright page object.
 */
test(
	'Test the Headline widget.',
	async ( { page } ) => {
		const { widget } = await testPrep(
			page,
			'sowb/siteorigin-widget-headline-widget'
		);

		const dividerSection = widget.locator( '.siteorigin-widget-field-divider' );
		await dividerSection.click();

		// Increase Divider Thickness to the maximum amount.
		const dividerThickness = dividerSection.locator( '.siteorigin-widget-field-thickness' );
		await expect( dividerThickness ).toBeVisible();
		const dividerThicknessTrack = dividerThickness.locator( '.ui-slider' );

		const dividerThicknessBoundingBox = await dividerThicknessTrack.boundingBox();
		await page.mouse.move(
			dividerThicknessBoundingBox.x + 1,
			dividerThicknessBoundingBox.y + dividerThicknessBoundingBox.height / 2
		);

		await page.mouse.down();

		// Drag to the far right of the slider track.
		await page.mouse.move(
			dividerThicknessBoundingBox.x + dividerThicknessBoundingBox.width - 1,
			dividerThicknessBoundingBox.y + dividerThicknessBoundingBox.height / 2,
			{
				steps: 20
			}
		);
		await page.mouse.up();

		// Validate the slider value.
		const sliderInput = dividerThickness.locator( '.siteorigin-widget-input-slider' );
		await expect( sliderInput ).toHaveValue( '20' );

		// Try adjusting the order field by moving the Divider field first.
		const orderField = widget.locator( '.siteorigin-widget-field-order' );
		const orderFieldItems = orderField.locator( '.siteorigin-widget-order-items' );
		const orderFieldValue = orderField.locator( '.siteorigin-widget-input' );

		const dividerField = orderFieldItems.locator( '.siteorigin-widget-order-item' ).getByText( 'Divider' );
		const firstOrderItem = orderFieldItems.locator( '.siteorigin-widget-order-item' ).first();

		// Drag Divider to the first item.
		await dividerField.dragTo( firstOrderItem );

		// Validate new ordering.
		await expect( orderFieldValue ).toHaveValue( 'divider,headline,sub_headline' );
	}
);

/**
 * Validates the following for the Hero widget in the Site Editor:
 * 1. The repeater is able to have multiple frames added.
 * 2. The TinyMCE field works as expected in repeaters.
 * 3. Frames are able to be opened.
 * 4. Frames are able to be re-ordered.
 *
 * @param {Object} page The Playwright page object.
 */
test(
	'Test the Hero widget.',
	async ( { page } ) => {
		const { widget } = await testPrep(
			page,
			'sowb/siteorigin-widget-hero-widget'
		);

		const frames = widget.locator( '.siteorigin-widget-field-frames' );
		await expect( frames ).toBeVisible();

		// Add three frames.
		const addFrameButton = frames.locator( '> div > .siteorigin-widget-field-repeater-add' );

		await expect( addFrameButton ).toBeVisible();
		await addFrameButton.click();
		await addFrameButton.click();
		await addFrameButton.click();

		// Validate that three frames have been added.
		let frameItems = frames.locator( '.siteorigin-widget-field-repeater-item' );
		await expect( frameItems ).toHaveCount( 3 );

		// Open the first frame.
		const firstFrame = frameItems.first();
		await firstFrame.click();

		// Validate that the TinyMCE field rendered correctly inside of the repeater.
		const tinyMCEField = firstFrame.locator( '.siteorigin-widget-field-content ' );
		await expect( tinyMCEField ).toBeVisible();

		// Tick the Automatically add paragraphs setting.
		const automaticallyAddParagraphsSetting = firstFrame.locator( '.siteorigin-widget-field-autop .siteorigin-widget-input' );
		await expect( automaticallyAddParagraphsSetting ).toBeVisible();
		await automaticallyAddParagraphsSetting.check();

		// Close the first frame, and validate.
		const firstFrameCloser = firstFrame.locator( '.siteorigin-widget-field-repeater-item-top' );
		const firstFrameForm = firstFrame.locator( '.siteorigin-widget-field-repeater-item-form' );
		await firstFrameCloser.click();
		await expect( firstFrameForm ).not.toBeVisible();

		// Open the last frame.
		const lastFrame = frameItems.last();
		await lastFrame.click();

		const lastFrameTop = lastFrame.locator( '.siteorigin-widget-field-repeater-item-top' );

		// Drag the last frame to the top of the frames list.
		const firstFrameBoundingBox = await firstFrame.boundingBox();
		const lastFrameTopBoundingBox = await lastFrameTop.boundingBox();

		// Click on the top of the last frame.
		await page.mouse.move(
			lastFrameTopBoundingBox.x + lastFrameTopBoundingBox.width / 2,
			lastFrameTopBoundingBox.y + lastFrameTopBoundingBox.height / 2
		);
		await page.mouse.down();

		// Move the cursor 100px higher than the top edge of the first frame.
		await page.mouse.move(
			firstFrameBoundingBox.x + firstFrameBoundingBox.width / 2,
			firstFrameBoundingBox.y - 100, // 100px above the top edge of the first frame.
			{ steps: 10 }
		);

		// Release the left click.
		await page.mouse.up();

		// Validate the last frame is now positioned first by checking the autop setting.
		frameItems = frames.locator( '.siteorigin-widget-field-repeater-item' );
		const lastAutomaticallyAddParagraphsSetting = frameItems.first().locator( '.siteorigin-widget-field-autop .siteorigin-widget-input' );
		await expect( lastAutomaticallyAddParagraphsSetting ).toBeVisible();
		await expect( lastAutomaticallyAddParagraphsSetting ).not.toBeChecked();
	}
);

/**
 * Validates the following for the Image Grid widget in the Site Editor:
 * 1. Ensures the padding field is visible and accessible by default.
 * 2. Validates that the multi-measurement field is working as expected.
 *
 * @param {Object} page The Playwright page object.
 */
test(
	'Test the Image Grid widget.',
	async ( { page } ) => {
		const { widget } = await testPrep(
			page,
			'sowb/siteorigin-widgets-imagegrid-widget'
		);

		const imagePaddingSetting = widget.locator( '.siteorigin-widget-field-padding' );
		await expect( imagePaddingSetting ).toBeVisible();

		// Clear the widget's default values.
		const imagePaddingFields = imagePaddingSetting.locator( '.sow-multi-measurement-input' );
		for ( let i = 0; i < 4; i++ ) {
			const field = imagePaddingFields.nth( i );
			await field.fill( '' ); // Clear the value.
		}

		const firstImage = imagePaddingFields.first();

		await expect( firstImage ).toHaveValue( '' );

		// Validate the multi-measurement field is autofilling.
		await firstImage.press( '5' );
		await page.keyboard.press( 'Tab' );
		await page.waitForTimeout( 100 );
		for ( let i = 0; i < 4; i++ ) {
			const field = imagePaddingFields.nth( i );
			await expect( field ).toHaveValue( '5' );
		}
	}
);
