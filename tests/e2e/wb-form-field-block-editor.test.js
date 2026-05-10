const {
	expect,
	test
} = require( '@playwright/test' );

const common = require( 'siteorigin-tests-common/playwright/common' );

const {
	addBlock,
	doLogin,
	ensureElementVisible,
	setupAdminE2E,
	setupRequestUtils,
	waitForRequestToFinish,
} = common;

const {
	getField,
	switchWidgetMode,
} = require( 'siteorigin-tests-common/playwright/utilities/widgets-bundle' );

const {
	uploadImageToMediaLibrary
} = require( 'siteorigin-tests-common/playwright/utilities/media' );

test.describe.configure( { mode: 'serial' } );

const getPostUpdatePath = ( postId ) => `/wp-json/wp/v2/posts/${ postId }`;

const waitForPostEditorCanvas = async ( page, admin ) => {
	await page.locator( 'iframe[name="editor-canvas"]' ).waitFor( { state: 'attached', timeout: 20000 } );
	await admin.editor.canvas.locator( 'body' ).waitFor( { state: 'visible', timeout: 20000 } );
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

	return {
		admin,
		post,
		requestUtils,
	};
};

const parseRequestPayload = ( request ) => {
	try {
		return request.postDataJSON();
	} catch ( error ) {
		const postData = request.postData();

		if ( ! postData ) {
			return {};
		}

		return Object.fromEntries( new URLSearchParams( postData ) );
	}
};

const getPayloadContent = ( payload ) => {
	if ( typeof payload.content === 'string' ) {
		return payload.content;
	}

	if (
		payload.content &&
		typeof payload.content.raw === 'string'
	) {
		return payload.content.raw;
	}

	return JSON.stringify( payload.content || payload );
};

const clickSaveAndCaptureContent = async ( page, postId ) => {
	const updatePath = getPostUpdatePath( postId );
	const isPostUpdateRequest = ( request ) => {
		const url = new URL( request.url() );

		return request.method() === 'POST' &&
			url.pathname.endsWith( updatePath );
	};
	const updateRequestPromise = page.waitForRequest( isPostUpdateRequest, { timeout: 30000 } );
	const updateResponsePromise = page.waitForResponse(
		( response ) => isPostUpdateRequest( response.request() ) &&
			response.status() >= 200 &&
			response.status() < 300,
		{ timeout: 30000 }
	);

	const topBar = page.getByRole( 'region', { name: 'Editor top bar' } );
	const saveButton = topBar.getByRole( 'button', { name: /^(Save|Update)$/ } ).first();

	await expect( saveButton ).toBeEnabled( { timeout: 20000 } );
	await saveButton.click();

	const publishRegionSaveButton = page
		.getByRole( 'region', { name: 'Editor publish' } )
		.getByRole( 'button', { name: /^(Save|Update)$/ } )
		.first();

	if ( await publishRegionSaveButton.isVisible().catch( () => false ) ) {
		await publishRegionSaveButton.click();
	}

	const updateRequest = await updateRequestPromise;
	await updateResponsePromise;

	return getPayloadContent( parseRequestPayload( updateRequest ) );
};

const findDirectBlockState = async ( page, blockName ) => {
	return page.evaluate( ( targetBlockName ) => {
		const findBlock = ( blocks ) => {
			for ( const block of blocks ) {
				if ( block.name === targetBlockName ) {
					return block;
				}

				const foundInnerBlock = findBlock( block.innerBlocks || [] );
				if ( foundInnerBlock ) {
					return foundInnerBlock;
				}
			}

			return null;
		};

		const block = findBlock( window.wp.data.select( 'core/block-editor' ).getBlocks() );

		if ( ! block ) {
			return null;
		}

		return {
			attributes: block.attributes,
			clientId: block.clientId,
			name: block.name,
		};
	}, blockName );
};

const getIframeWidgetFormValues = async ( page, clientId ) => {
	return page.evaluate( ( blockClientId ) => {
		const iframe = document.querySelector( 'iframe[name="editor-canvas"], .edit-site-visual-editor__editor-canvas' );
		const frameWindow = iframe && iframe.contentWindow ? iframe.contentWindow : window;
		const frameDocument = frameWindow.document;
		const frameJQuery = frameWindow.jQuery || window.jQuery;
		const frameForms = frameWindow.sowbForms || window.sowbForms;

		if (
			! frameJQuery ||
			! frameForms ||
			typeof frameForms.getWidgetFormValues !== 'function'
		) {
			return {
				formCount: 0,
				values: null,
			};
		}

		const $form = frameJQuery( frameDocument )
			.find( `[data-block="${ blockClientId }"]` )
			.find( '.siteorigin-widget-form.siteorigin-widget-form-main' );

		return {
			formCount: $form.length,
			values: frameForms.getWidgetFormValues( $form ),
		};
	}, clientId );
};

const attachBlockDiagnostics = async ( testInfo, name, state ) => {
	await testInfo.attach( name, {
		body: JSON.stringify( state, null, 2 ),
		contentType: 'application/json',
	} );
};

const getWidgetBlock = ( admin, blockName ) => {
	return admin.editor.canvas.locator( `.wp-block[data-type="${ blockName }"]` ).first();
};

const reopenSavedWidgetForm = async ( page, admin, blockName ) => {
	await waitForPostEditorCanvas( page, admin );

	const widget = getWidgetBlock( admin, blockName );
	await expect( widget ).toBeVisible( { timeout: 20000 } );
	await admin.editor.selectBlocks( widget );
	await widget.click();

	const form = widget.locator( '.siteorigin-widget-form.siteorigin-widget-form-main' );
	if ( await form.isVisible().catch( () => false ) ) {
		return widget;
	}

	const formRequest = page.waitForResponse(
		( response ) => response.url().includes( '/wp-json/sowb/v1/widgets/forms' ) &&
			response.status() === 200,
		{ timeout: 20000 }
	).catch( () => null );

	await switchWidgetMode( admin, widget, 'edit' );
	await formRequest;
	await form.waitFor( { state: 'visible', timeout: 20000 } );

	return widget;
};

test.beforeEach( async ( { page } ) => {
	await doLogin( page );
} );

test(
	'Editor widget iframe content is saved when Save immediately follows TinyMCE typing.',
	async ( { page }, testInfo ) => {
		const blockName = 'sowb/siteorigin-widget-editor-widget';
		const marker = `WB direct editor save ${ Date.now() }`;
		const {
			admin,
			post,
			requestUtils,
		} = await setupPublishedPostEditor( page, 'WB direct editor save bridge' );

		try {
			const widget = await addBlock( admin, blockName, 120 );
			const blockState = await findDirectBlockState( page, blockName );

			expect( blockState ).not.toBeNull();

			const tinymceField = await getField( widget, 'tinymce', true );
			const visualIframe = tinymceField.locator( 'iframe' ).first();
			await ensureElementVisible( visualIframe, 120, 20000 );

			const visualBody = tinymceField.frameLocator( 'iframe' ).locator( 'body' );
			await visualBody.click();
			await visualBody.pressSequentially( marker );
			await expect( visualBody ).toContainText( marker );

			const formSnapshot = await getIframeWidgetFormValues( page, blockState.clientId );
			expect( formSnapshot.formCount ).toBeGreaterThan( 0 );
			expect( formSnapshot.values.text ).toContain( marker );

			const preSaveBlockState = await findDirectBlockState( page, blockName );
			await attachBlockDiagnostics( testInfo, 'pre-save-editor-widget-attrs.json', preSaveBlockState );

			const savedContent = await clickSaveAndCaptureContent( page, post.id );
			expect( savedContent ).toContain( marker );

			const postSaveBlockState = await findDirectBlockState( page, blockName );
			expect( postSaveBlockState.attributes.widgetData.text ).toContain( marker );

			await admin.editPost( post.id );
			const reloadedWidget = await reopenSavedWidgetForm( page, admin, blockName );
			const reloadedBlockState = await findDirectBlockState( page, blockName );
			const reloadedSnapshot = await getIframeWidgetFormValues( page, reloadedBlockState.clientId );

			expect( reloadedSnapshot.values.text ).toContain( marker );
			await expect(
				( await getField( reloadedWidget, 'tinymce', true ) )
					.locator( 'textarea.wp-editor-area' )
			).toHaveValue( new RegExp( marker ) );

			await page.goto( post.link );
			await expect( page.locator( '.siteorigin-widget-tinymce' ) ).toContainText( marker );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: {
					force: true,
				},
			} ).catch( () => {} );
		}
	}
);

test(
	'Image widget iframe media selection is saved when Save immediately follows modal selection.',
	async ( { page }, testInfo ) => {
		const blockName = 'sowb/siteorigin-widget-image-widget';
		const {
			admin,
			post,
			requestUtils,
		} = await setupPublishedPostEditor( page, 'WB direct image save bridge' );

		try {
			const widget = await addBlock( admin, blockName, 120 );
			const blockState = await findDirectBlockState( page, blockName );

			expect( blockState ).not.toBeNull();

			const imageField = await getField( widget, 'media' );
			const imageValue = imageField.locator( '.siteorigin-widget-input[type="hidden"]' );
			const addMediaButton = imageField.locator( '.media-upload-button' );

			await ensureElementVisible( addMediaButton, 120, 10000 );
			await addMediaButton.click( { force: true } );
			await uploadImageToMediaLibrary( admin );
			await expect( page.locator( '.media-modal' ) ).toBeHidden( { timeout: 10000 } );
			await expect( imageValue ).toHaveValue( /.+/ );

			const attachmentId = await imageValue.inputValue();
			const formSnapshot = await getIframeWidgetFormValues( page, blockState.clientId );
			expect( formSnapshot.formCount ).toBeGreaterThan( 0 );
			expect( String( formSnapshot.values.image ) ).toBe( attachmentId );

			const preSaveBlockState = await findDirectBlockState( page, blockName );
			await attachBlockDiagnostics( testInfo, 'pre-save-image-widget-attrs.json', preSaveBlockState );

			const savedContent = await clickSaveAndCaptureContent( page, post.id );
			expect( savedContent ).toContain( `"image":${ attachmentId }` );

			const postSaveBlockState = await findDirectBlockState( page, blockName );
			expect( String( postSaveBlockState.attributes.widgetData.image ) ).toBe( attachmentId );

			await admin.editPost( post.id );
			const reloadedWidget = await reopenSavedWidgetForm( page, admin, blockName );
			const reloadedBlockState = await findDirectBlockState( page, blockName );
			const reloadedSnapshot = await getIframeWidgetFormValues( page, reloadedBlockState.clientId );

			expect( String( reloadedSnapshot.values.image ) ).toBe( attachmentId );
			await expect(
				( await getField( reloadedWidget, 'media', true ) )
					.locator( '.siteorigin-widget-input[type="hidden"]' )
			).toHaveValue( attachmentId );

			await page.goto( post.link );
			const frontendImage = page.locator( '.sow-image-container img' ).first();
			await expect( frontendImage ).toBeVisible( { timeout: 20000 } );
			await expect( frontendImage ).toHaveAttribute( 'src', /test-image/ );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: {
					force: true,
				},
			} ).catch( () => {} );
		}
	}
);
