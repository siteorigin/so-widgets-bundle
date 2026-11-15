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

const wordPressVersion = require( 'siteorigin-tests-common/playground/wordPressVersion' );

const path = require( 'path' );

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
			.find( '.siteorigin-widget-form.siteorigin-widget-form-main[data-class]' );

		return {
			formCount: $form.length,
			values: frameForms.getWidgetFormValues( $form ),
		};
	}, clientId );
};

const setIframeImageFieldValue = async ( page, clientId, attachmentId ) => {
	return page.evaluate(
		( { blockClientId, imageId } ) => {
			const iframe = document.querySelector( 'iframe[name="editor-canvas"], .edit-site-visual-editor__editor-canvas' );
			const frameWindow = iframe && iframe.contentWindow ? iframe.contentWindow : window;
			const frameDocument = frameWindow.document;
			const form = frameDocument
				.querySelector( `[data-block="${ blockClientId }"] .siteorigin-widget-form.siteorigin-widget-form-main[data-class]` );

			if ( ! form ) {
				return null;
			}

			const imageInput = Array.from(
				form.querySelectorAll( 'input.siteorigin-widget-input[type="hidden"]' )
			).find( ( input ) => /\[image\]$/.test( input.name ) );

			if ( ! imageInput ) {
				return null;
			}

			imageInput.value = String( imageId );

			return imageInput.value;
		},
		{
			blockClientId: clientId,
			imageId: attachmentId,
		}
	);
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

const insertDirectWidgetBlock = async ( admin, blockName ) => {
	const formRequest = waitForRequestToFinish(
		admin.page,
		'/wp-json/sowb/v1/widgets/forms',
		20000
	);

	await admin.editor.insertBlock( { name: blockName } );
	await formRequest;

	const widget = getWidgetBlock( admin, blockName );
	await expect( widget ).toBeVisible( { timeout: 20000 } );

	return widget;
};

const reopenSavedWidgetForm = async ( page, admin, blockName ) => {
	await waitForPostEditorCanvas( page, admin );

	const widget = getWidgetBlock( admin, blockName );
	await expect( widget ).toBeVisible( { timeout: 20000 } );
	await admin.editor.selectBlocks( widget );
	await widget.click();

	const form = widget.locator( '.siteorigin-widget-form.siteorigin-widget-form-main[data-class]' );
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

test.describe( 'TinyMCE serializer init guard', () => {
	test( 'serializer keeps tinymce content when editor init is incomplete', async ( { page } ) => {
		const blockName = 'sowb/siteorigin-widget-editor-widget';
		const marker = `WB serializer guard ${ Date.now() }`;
		const { admin, post, requestUtils } = await setupPublishedPostEditor( page, 'WB serializer guard' );
		try {
			const widget = await addBlock( admin, blockName, 120 );
			expect( await findDirectBlockState( page, blockName ) ).not.toBeNull();

			const tinymceField = await getField( widget, 'tinymce', true );
			const visualBody = tinymceField.frameLocator( 'iframe' ).locator( 'body' );
			await visualBody.click();
			await visualBody.pressSequentially( marker );
			await expect( visualBody ).toContainText( marker );

			// Sync editor -> textarea, then force the crash state (visible
			// instance, empty content, init incomplete) exactly as the
			// serializer sees it.
			let mutation;
			try {
				mutation = await page.evaluate( () => {
					const canvas = document.querySelector( 'iframe[name="editor-canvas"]' );
					const doc = canvas && canvas.contentDocument ? canvas.contentDocument : document;
					// Scope to the widget block's own form, not the whole
					// document — metabox forms (e.g. Page Builder's) can also
					// contain wp-editor-area textareas.
					const blockEl = doc.querySelector( '.wp-block[data-type="sowb/siteorigin-widget-editor-widget"]' );
					const scope = blockEl || doc;
					const ta = scope.querySelector( 'textarea.wp-editor-area[id^="widget-sow-editor"]' );
					if ( ! ta ) {
						return { ok: false, reason: 'lookup returned no textarea in the widget block scope' };
					}
					const win = ta.ownerDocument.defaultView;
					const tmce = win.tinymce || window.tinymce;
					const editor = tmce ? tmce.get( ta.id ) : null;
					if ( ! editor ) {
						return { ok: false, reason: 'lookup returned undefined editor for id ' + ta.id };
					}
					editor.save(); // Persist typed content into the textarea (the saved-truth source).
					try {
						editor.initialized = false;
						editor.getContent = function () {
							return '';
						};
					} catch ( e ) {
						return { ok: false, reason: 'assignment threw: ' + e.message };
					}
					const refetch = tmce.get( ta.id );
					if ( refetch !== editor || refetch.getContent() !== '' || refetch.initialized !== false ) {
						return { ok: false, reason: 're-fetch shadow not visible: getContent()=' + refetch.getContent() + ' initialized=' + refetch.initialized };
					}
					return { ok: true, taValue: ta.value };
				} );
			} catch ( e ) {
				mutation = { ok: false, reason: 'evaluate threw: ' + e.message };
			}
			// Conditional skip (documented stable API in the installed
			// @playwright/test 1.55.1): aborts the test as SKIPPED before any
			// expect() so a failed mutation never surfaces as an assertion
			// failure.
			test.skip( ! mutation.ok, 'reason: ' + ( mutation.reason || '' ) );
			expect( mutation.taValue ).toContain( marker );

			// Any other field's change event triggers full form serialization.
			const form = widget.locator( '.siteorigin-widget-form.siteorigin-widget-form-main[data-class]' );
			const titleInput = form.locator( 'input[name$="[title]"]' ).first();
			test.skip( ( await titleInput.count() ) === 0, 'reason: no [title] input in the Editor widget form' );
			await titleInput.fill( 'changed title' );
			await titleInput.dispatchEvent( 'change' );

			const postChange = await findDirectBlockState( page, blockName );
			expect( postChange.attributes.widgetData.text ).toContain( marker );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: {
					force: true,
				},
			} ).catch( () => {} );
		}
	} );

	test( 'flusher keeps the textarea intact when editor init is incomplete', async ( { page } ) => {
		const blockName = 'sowb/siteorigin-widget-editor-widget';
		const marker = `WB flusher guard ${ Date.now() }`;
		const { admin, post, requestUtils } = await setupPublishedPostEditor( page, 'WB flusher guard' );
		try {
			const widget = await addBlock( admin, blockName, 120 );
			const tinymceField = await getField( widget, 'tinymce', true );
			const visualBody = tinymceField.frameLocator( 'iframe' ).locator( 'body' );
			await visualBody.click();
			await visualBody.pressSequentially( marker );
			await expect( visualBody ).toContainText( marker );

			// Sync editor -> textarea, force the crash state, then run the
			// snapshot/flush path (the pre-save bridge's route) and assert the
			// flusher did NOT overwrite the textarea with the crashed editor's
			// empty content.
			let result;
			try {
				result = await page.evaluate( () => {
					const canvas = document.querySelector( 'iframe[name="editor-canvas"]' );
					const doc = canvas && canvas.contentDocument ? canvas.contentDocument : document;
					const blockEl = doc.querySelector( '.wp-block[data-type="sowb/siteorigin-widget-editor-widget"]' );
					const scope = blockEl || doc;
					const ta = scope.querySelector( 'textarea.wp-editor-area[id^="widget-sow-editor"]' );
					if ( ! ta ) {
						return { ok: false, reason: 'lookup returned no textarea in the widget block scope' };
					}
					const win = ta.ownerDocument.defaultView;
					const tmce = win.tinymce || window.tinymce;
					const editor = tmce ? tmce.get( ta.id ) : null;
					if ( ! editor ) {
						return { ok: false, reason: 'lookup returned undefined editor for id ' + ta.id };
					}
					editor.save();
					const taBefore = ta.value;
					try {
						editor.initialized = false;
						editor.getContent = function () {
							return '';
						};
					} catch ( e ) {
						return { ok: false, reason: 'assignment threw: ' + e.message };
					}
					const form = scope.querySelector( '.siteorigin-widget-form.siteorigin-widget-form-main[data-class]' );
					const sowbFormsRef = win.sowbForms || window.sowbForms;
					if ( ! form || ! sowbFormsRef || typeof sowbFormsRef.getWidgetFormSnapshot !== 'function' ) {
						return { ok: false, reason: 'form or sowbForms.getWidgetFormSnapshot unavailable' };
					}
					return sowbFormsRef.getWidgetFormSnapshot( form ).then( function ( snapshot ) {
						return { ok: true, taBefore: taBefore, taAfter: ta.value, snapshotText: snapshot ? snapshot.text : null };
					} );
				} );
			} catch ( e ) {
				result = { ok: false, reason: 'evaluate threw: ' + e.message };
			}
			test.skip( ! result.ok, 'reason: ' + ( result.reason || '' ) );
			expect( result.taBefore ).toContain( marker );
			// The flusher must not have wiped the textarea...
			expect( result.taAfter ).toContain( marker );
			// ...and the snapshot must carry the preserved content.
			expect( result.snapshotText ).toContain( marker );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: {
					force: true,
				},
			} ).catch( () => {} );
		}
	} );

	test( 'serializer captures live unsaved visual-mode typing from a healthy editor', async ( { page } ) => {
		const blockName = 'sowb/siteorigin-widget-editor-widget';
		const marker = `WB live typing ${ Date.now() }`;
		const { admin, post, requestUtils } = await setupPublishedPostEditor( page, 'WB live typing' );
		try {
			const widget = await addBlock( admin, blockName, 120 );
			const tinymceField = await getField( widget, 'tinymce', true );
			const visualBody = tinymceField.frameLocator( 'iframe' ).locator( 'body' );
			await visualBody.click();
			await visualBody.pressSequentially( marker );
			await expect( visualBody ).toContainText( marker );

			// NO editor.save() here: the textarea is deliberately stale. The
			// editor-first read must still capture the live typed content.
			const form = widget.locator( '.siteorigin-widget-form.siteorigin-widget-form-main[data-class]' );
			const titleInput = form.locator( 'input[name$="[title]"]' ).first();
			test.skip( ( await titleInput.count() ) === 0, 'reason: no [title] input in the Editor widget form' );
			await titleInput.fill( 'changed title' );
			await titleInput.dispatchEvent( 'change' );

			const postChange = await findDirectBlockState( page, blockName );
			expect( postChange.attributes.widgetData.text ).toContain( marker );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: {
					force: true,
				},
			} ).catch( () => {} );
		}
	} );
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
	'Image widget iframe media value is saved when Save immediately follows field update.',
	async ( { page }, testInfo ) => {
		const blockName = 'sowb/siteorigin-widget-image-widget';
		const {
			admin,
			post,
			requestUtils,
		} = await setupPublishedPostEditor( page, 'WB direct image save bridge' );
		let attachment = null;

		try {
			attachment = await requestUtils.uploadMedia(
				path.resolve(
					process.cwd(),
					'node_modules/siteorigin-tests-common/playwright/utilities/assets/test-image.png'
				)
			);
			const widget = await insertDirectWidgetBlock( admin, blockName );
			const clientId = await widget.getAttribute( 'data-block' );

			expect( clientId ).toBeTruthy();

			await page.evaluate( () => {
				window.wp.data.dispatch( 'core/editor' ).editPost( {
					title: 'WB direct image save bridge updated',
				} );
			} );

			const attachmentId = await setIframeImageFieldValue( page, clientId, attachment.id );
			expect( attachmentId ).toBe( String( attachment.id ) );

			const formSnapshot = await getIframeWidgetFormValues( page, clientId );
			expect( formSnapshot.formCount ).toBeGreaterThan( 0 );
			expect( String( formSnapshot.values.image ) ).toBe( attachmentId );

			const preSaveBlockState = await findDirectBlockState( page, blockName );
			await attachBlockDiagnostics( testInfo, 'pre-save-image-widget-attrs.json', preSaveBlockState );

			// The field's value is set without a change event, so the block's
			// attributes are stale and only the save itself can carry the value.
			const preSaveWidgetData = preSaveBlockState.attributes.widgetData || {};
			expect( String( preSaveWidgetData.image || '' ) ).not.toBe( attachmentId );

			// The form reads a hidden input's value as text, so the ID is saved
			// quoted; an unquoted ID is the same image.
			const savedContent = await clickSaveAndCaptureContent( page, post.id );
			expect( savedContent ).toMatch( new RegExp( `"image":"?${ attachmentId }"?[,}]` ) );

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
			if ( attachment ) {
				await requestUtils.deleteMedia( attachment.id ).catch( () => {} );
			}
		}
	}
);

test(
	'Editor widget content saves correctly when TinyMCE init is still in-flight at save time.',
	async ( { page }, testInfo ) => {
		const blockName = 'sowb/siteorigin-widget-editor-widget';
		const marker = `WB tinymce flush race ${ Date.now() }`;
		const {
			admin,
			post,
			requestUtils,
		} = await setupPublishedPostEditor( page, 'WB tinymce flush race' );

		try {
			const widget = await insertDirectWidgetBlock( admin, blockName );
			const blockState = await findDirectBlockState( page, blockName );

			expect( blockState ).not.toBeNull();

			// Wait for TinyMCE to fully initialize so we can type content into it.
			const tinymceField = await getField( widget, 'tinymce', true );
			const visualIframe = tinymceField.locator( 'iframe' ).first();
			await ensureElementVisible( visualIframe, 120, 20000 );

			const visualBody = tinymceField.frameLocator( 'iframe' ).locator( 'body' );
			await visualBody.click();
			await visualBody.pressSequentially( marker );
			await expect( visualBody ).toContainText( marker );

			// Retrieve the editor ID from the canvas iframe.
			const editorId = await page.evaluate( ( clientId ) => {
				const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
				if ( ! iframe ) {
					return null;
				}
				const textarea = iframe.contentDocument.querySelector(
					`[data-block="${ clientId }"] textarea.wp-editor-area`
				);
				return textarea
					? ( textarea.getAttribute( 'data-tinymce-id' ) || textarea.id )
					: null;
			}, blockState.clientId );

			expect( editorId ).toBeTruthy();

			// Simulate TinyMCE being mid-init by replacing sowbGetTinyMCEInitPromise
			// in the canvas iframe with a version that imposes a 1.5 s delay for this
			// specific editor ID. The flusher must await this promise before calling
			// editor.save(), so the REST request should not fire until it resolves.
			await page.evaluate( ( eid ) => {
				const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
				const frameWindow = iframe.contentWindow;
				const original = frameWindow.sowbGetTinyMCEInitPromise;
				frameWindow.sowbGetTinyMCEInitPromise = function( editorId ) {
					if ( editorId === eid ) {
						return new Promise( ( resolve ) => setTimeout( resolve, 1500 ) );
					}
					return original ? original( editorId ) : Promise.resolve();
				};
			}, editorId );

			// Measure how long after clicking Save it takes for the REST request to
			// fire (not the full round-trip). With the 1.5 s fake init delay in place
			// the flusher must await it before editor.save() is called, so the
			// request must not fire until at least 1.2 s have elapsed.
			const updatePath = getPostUpdatePath( post.id );
			const isUpdateRequest = ( req ) =>
				req.method() === 'POST' &&
				new URL( req.url() ).pathname.endsWith( updatePath );
			let requestFiredTime = null;
			const requestFiredPromise = page.waitForRequest( ( req ) => {
				if ( isUpdateRequest( req ) ) {
					requestFiredTime = Date.now();
					return true;
				}
				return false;
			}, { timeout: 30000 } );

			const saveStartTime = Date.now();
			const savedContent = await clickSaveAndCaptureContent( page, post.id );
			await requestFiredPromise;
			const requestFireElapsedMs = ( requestFiredTime || Date.now() ) - saveStartTime;

			await attachBlockDiagnostics( testInfo, 'flush-race-save-attrs.json', {
				editorId,
				requestFireElapsedMs,
				postSaveBlockState: await findDirectBlockState( page, blockName ),
			} );

			// The flusher awaited the 1.5 s fake pending init before calling
			// editor.save(). The REST request must therefore have been delayed.
			expect( requestFireElapsedMs ).toBeGreaterThanOrEqual( 1200 );

			// The marker text typed before the fake delay was injected must appear
			// in the saved post content, proving editor.save() ran correctly.
			expect( savedContent ).toContain( marker );

			const postSaveBlockState = await findDirectBlockState( page, blockName );
			expect( postSaveBlockState ).not.toBeNull();
			expect( postSaveBlockState.attributes.widgetData ).toBeTruthy();
			expect( postSaveBlockState.attributes.widgetData.text ).toContain( marker );
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

// ---------------------------------------------------------------------------
// Area 1 — admin.js snapshot API: shared blast radius
// Proves registerFieldFlusher, flushWidgetForm, getWidgetFormSnapshot and the
// TinyMCE flusher are available and callable from inside the editor-canvas
// iframe, and that they do not throw in a context where the standard TinyMCE
// init flow has run.
// ---------------------------------------------------------------------------

test(
	'sowbForms snapshot API is available in the editor-canvas iframe and flushWidgetForm resolves.',
	async ( { page }, testInfo ) => {
		const blockName = 'sowb/siteorigin-widget-editor-widget';
		const {
			admin,
			post,
			requestUtils,
		} = await setupPublishedPostEditor( page, 'WB snapshot API availability' );

		try {
			const widget = await insertDirectWidgetBlock( admin, blockName );
			const blockState = await findDirectBlockState( page, blockName );
			expect( blockState ).not.toBeNull();

			// Wait for TinyMCE to initialise so there is a live editor to flush.
			const tinymceField = await getField( widget, 'tinymce', true );
			const visualIframe = tinymceField.locator( 'iframe' ).first();
			await ensureElementVisible( visualIframe, 120, 20000 );

			// Probe the iframe window for the required API surface.
			const apiReport = await page.evaluate( ( clientId ) => {
				const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
				if ( ! iframe || ! iframe.contentWindow ) {
					return { error: 'no canvas iframe' };
				}

				const fw = iframe.contentWindow;
				const forms = fw.sowbForms;

				if ( ! forms ) {
					return { error: 'sowbForms not present in iframe window' };
				}

				return {
					hasRegisterFieldFlusher: typeof forms.registerFieldFlusher === 'function',
					hasFlushWidgetForm: typeof forms.flushWidgetForm === 'function',
					hasGetWidgetFormSnapshot: typeof forms.getWidgetFormSnapshot === 'function',
					hasGetWidgetFormValues: typeof forms.getWidgetFormValues === 'function',
					flushIsThenable: ( () => {
						try {
							const $form = fw.jQuery( iframe.contentDocument )
								.find( `[data-block="${ clientId }"]` )
								.find( '.siteorigin-widget-form-main' );
							const result = forms.flushWidgetForm( $form );
							return result && typeof result.then === 'function';
						} catch ( e ) {
							return 'threw: ' + e.message;
						}
					} )(),
				};
			}, blockState.clientId );

			await attachBlockDiagnostics( testInfo, 'snapshot-api-report.json', apiReport );

			expect( apiReport.error ).toBeUndefined();
			expect( apiReport.hasRegisterFieldFlusher ).toBe( true );
			expect( apiReport.hasFlushWidgetForm ).toBe( true );
			expect( apiReport.hasGetWidgetFormSnapshot ).toBe( true );
			expect( apiReport.hasGetWidgetFormValues ).toBe( true );
			// flushWidgetForm must return a thenable (Promise) — not throw.
			expect( apiReport.flushIsThenable ).toBe( true );

			// Call getWidgetFormSnapshot end-to-end and confirm it resolves to a
			// non-empty object — proving the TinyMCE flusher ran without error.
			const snapshot = await page.evaluate( async ( clientId ) => {
				const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
				const fw = iframe.contentWindow;
				const forms = fw.sowbForms;
				const $form = fw.jQuery( iframe.contentDocument )
					.find( `[data-block="${ clientId }"]` )
					.find( '.siteorigin-widget-form-main' );

				try {
					const result = await forms.getWidgetFormSnapshot( $form );
					return {
						success: true,
						isObject: result !== null && typeof result === 'object',
						keys: Object.keys( result || {} ),
					};
				} catch ( e ) {
					return { success: false, error: e.message };
				}
			}, blockState.clientId );

			await attachBlockDiagnostics( testInfo, 'snapshot-result.json', snapshot );

			expect( snapshot.success ).toBe( true );
			expect( snapshot.isObject ).toBe( true );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	}
);

test(
	'registerFieldFlusher rejects non-string fieldType and non-function callback without throwing.',
	async ( { page }, testInfo ) => {
		const blockName = 'sowb/siteorigin-widget-editor-widget';
		const {
			admin,
			post,
			requestUtils,
		} = await setupPublishedPostEditor( page, 'WB registerFieldFlusher guard' );

		try {
			await insertDirectWidgetBlock( admin, blockName );

			// Exercise the guard branch: invalid arguments must be silently ignored
			// and must not overwrite an existing valid flusher or throw.
			const guardReport = await page.evaluate( () => {
				const iframe = document.querySelector( 'iframe[name="editor-canvas"]' );
				const fw = iframe && iframe.contentWindow;
				const forms = fw && fw.sowbForms;

				if ( ! forms || typeof forms.registerFieldFlusher !== 'function' ) {
					return { error: 'API not available' };
				}

				try {
					// All of these should be silently ignored.
					forms.registerFieldFlusher( null, () => {} );
					forms.registerFieldFlusher( 42, () => {} );
					forms.registerFieldFlusher( 'valid-type', 'not-a-function' );
					forms.registerFieldFlusher( 'valid-type', null );

					// Register a valid sentinel flusher and confirm it was stored.
					let called = false;
					forms.registerFieldFlusher( '__test_sentinel__', () => { called = true; } );

					// The tinymce flusher registered at boot must still be a function
					// (not overwritten by the invalid calls above).
					const tinymceFlusherType = typeof ( fw._widgetFieldFlushers
						? fw._widgetFieldFlushers[ 'tinymce' ]
						: 'unavailable' );

					return {
						noThrow: true,
						tinymceFlusherType,
					};
				} catch ( e ) {
					return { noThrow: false, error: e.message };
				}
			} );

			await attachBlockDiagnostics( testInfo, 'register-flusher-guard-report.json', guardReport );

			expect( guardReport.error ).toBeUndefined();
			expect( guardReport.noThrow ).toBe( true );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	}
);

// ---------------------------------------------------------------------------
// Area 3 — Save bridge correctness: mounted vs unmounted form handling
// Proves that:
// (a) A block in edit mode (form mounted) has its current iframe values
//     captured by the bridge and written into the saved content.
// (b) A block in preview mode (form unmounted) does NOT have its widgetData
//     blanked — the last-saved value is preserved in the REST payload.
// ---------------------------------------------------------------------------

test(
	'Save bridge captures iframe form values for a block in edit mode.',
	async ( { page }, testInfo ) => {
		// Covered by the existing Editor / Image save tests — this test
		// explicitly asserts the condition from the other side: after switching
		// to preview mode and back to edit mode, new content is still captured.
		const blockName = 'sowb/siteorigin-widget-editor-widget';
		const markerA = `WB bridge edit-mode A ${ Date.now() }`;
		const markerB = `WB bridge edit-mode B ${ Date.now() + 1 }`;
		const {
			admin,
			post,
			requestUtils,
		} = await setupPublishedPostEditor( page, 'WB bridge edit-mode capture' );

		try {
			const widget = await insertDirectWidgetBlock( admin, blockName );
			const blockStateA = await findDirectBlockState( page, blockName );
			expect( blockStateA ).not.toBeNull();

			// Type first marker.
			const tinymceField = await getField( widget, 'tinymce', true );
			const visualIframe = tinymceField.locator( 'iframe' ).first();
			await ensureElementVisible( visualIframe, 120, 20000 );

			const visualBody = tinymceField.frameLocator( 'iframe' ).locator( 'body' );
			await visualBody.click();
			await visualBody.pressSequentially( markerA );
			await expect( visualBody ).toContainText( markerA );

			// Save with markerA in edit mode — bridge must capture it.
			const savedContentA = await clickSaveAndCaptureContent( page, post.id );
			expect( savedContentA ).toContain( markerA );

			// Switch to preview mode so the form is unmounted.
			await admin.editor.selectBlocks( widget );
			await switchWidgetMode( admin, widget, 'preview' );

			// Confirm the form is no longer in the DOM.
			const formVisibleAfterPreview = await widget
				.locator( '.siteorigin-widget-form-main' )
				.isVisible()
				.catch( () => false );
			expect( formVisibleAfterPreview ).toBe( false );

			// Switch back to edit mode and type a new marker.
			const formRequestB = page.waitForResponse(
				( response ) => response.url().includes( '/wp-json/sowb/v1/widgets/forms' ) &&
					response.status() === 200,
				{ timeout: 20000 }
			).catch( () => null );

			await switchWidgetMode( admin, widget, 'edit' );
			await formRequestB;

			const tinymceFieldB = await getField( widget, 'tinymce', true );
			const visualIframeB = tinymceFieldB.locator( 'iframe' ).first();
			await ensureElementVisible( visualIframeB, 120, 20000 );

			const visualBodyB = tinymceFieldB.frameLocator( 'iframe' ).locator( 'body' );
			await visualBodyB.click();
			// Select all and replace so only markerB is present.
			await visualBodyB.press( 'Control+a' );
			await visualBodyB.pressSequentially( markerB );
			await expect( visualBodyB ).toContainText( markerB );

			const savedContentB = await clickSaveAndCaptureContent( page, post.id );
			expect( savedContentB ).toContain( markerB );

			const postSaveBlockState = await findDirectBlockState( page, blockName );
			await attachBlockDiagnostics( testInfo, 'bridge-edit-mode-attrs.json', postSaveBlockState );

			expect( postSaveBlockState ).not.toBeNull();
			expect( postSaveBlockState.attributes.widgetData ).toBeTruthy();
			expect( postSaveBlockState.attributes.widgetData.text ).toContain( markerB );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	}
);

test(
	'Save bridge preserves existing widgetData for a block in preview mode (form unmounted).',
	async ( { page }, testInfo ) => {
		const blockName = 'sowb/siteorigin-widget-editor-widget';
		const marker = `WB bridge preview-mode ${ Date.now() }`;
		const {
			admin,
			post,
			requestUtils,
		} = await setupPublishedPostEditor( page, 'WB bridge preview-mode preserve' );

		try {
			// Insert block and type marker text.
			const widget = await insertDirectWidgetBlock( admin, blockName );
			const blockState = await findDirectBlockState( page, blockName );
			expect( blockState ).not.toBeNull();

			const tinymceField = await getField( widget, 'tinymce', true );
			const visualIframe = tinymceField.locator( 'iframe' ).first();
			await ensureElementVisible( visualIframe, 120, 20000 );

			const visualBody = tinymceField.frameLocator( 'iframe' ).locator( 'body' );
			await visualBody.click();
			await visualBody.pressSequentially( marker );
			await expect( visualBody ).toContainText( marker );

			// First save: block is in edit mode — bridge captures the marker.
			const savedContentEdit = await clickSaveAndCaptureContent( page, post.id );
			expect( savedContentEdit ).toContain( marker );

			// Switch to preview mode (form unmounted).
			await admin.editor.selectBlocks( widget );
			await switchWidgetMode( admin, widget, 'preview' );

			const formStillVisible = await widget
				.locator( '.siteorigin-widget-form-main' )
				.isVisible()
				.catch( () => false );
			expect( formStillVisible ).toBe( false );

			// Second save: block is in preview mode — bridge must skip this block
			// (sowbGetBlockForm returns empty set) and edits.content must still
			// contain the marker from the previous save, not an empty text field.
			const savedContentPreview = await clickSaveAndCaptureContent( page, post.id );

			await attachBlockDiagnostics( testInfo, 'bridge-preview-mode-attrs.json', {
				savedContentPreview: savedContentPreview.slice( 0, 500 ),
				containsMarker: savedContentPreview.includes( marker ),
			} );

			expect( savedContentPreview ).toContain( marker );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	}
);

// ---------------------------------------------------------------------------
// Area 5 — WP_Error propagation: PHP validation surface
// Proves that:
// (a) A valid save with a known good widget completes with HTTP 200.
// (b) A save containing a sowb block with an unrecognised widgetClass returns
//     HTTP 400 and a structured WP_Error body, not a silent pass-through.
// ---------------------------------------------------------------------------

test(
	'REST save returns HTTP 200 for a valid Editor widget block.',
	async ( { page }, testInfo ) => {
		const blockName = 'sowb/siteorigin-widget-editor-widget';
		const {
			admin,
			post,
			requestUtils,
		} = await setupPublishedPostEditor( page, 'WB valid save 200' );

		try {
			await insertDirectWidgetBlock( admin, blockName );

			const updatePath = getPostUpdatePath( post.id );
			const isUpdateRequest = ( req ) =>
				req.method() === 'POST' &&
				new URL( req.url() ).pathname.endsWith( updatePath );

			const responsePromise = page.waitForResponse(
				( res ) => isUpdateRequest( res.request() ),
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

			const response = await responsePromise;
			await attachBlockDiagnostics( testInfo, 'valid-save-response.json', {
				status: response.status(),
				url: response.url(),
			} );

			expect( response.status() ).toBe( 200 );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	}
);

test(
	'REST save returns HTTP 400 when a sowb block contains an unrecognised widgetClass.',
	async ( { page }, testInfo ) => {
		const {
			post,
			requestUtils,
		} = await setupPublishedPostEditor( page, 'WB invalid widgetClass 400' );

		try {
			// Craft a post body containing a sowb block with a deliberately
			// invalid widgetClass and send it directly via the REST API.
			// This bypasses the block editor UI to isolate the PHP validation.
			const invalidContent = `<!-- wp:sowb/siteorigin-widget-editor-widget {"widgetClass":"NonExistentWidget_DoesNotExist","widgetData":{}} /-->`;

			const updatePath = `/wp/v2/posts/${ post.id }`;
			const response = await requestUtils.rest( {
				method: 'POST',
				path: updatePath,
				data: {
					content: invalidContent,
				},
			} ).catch( ( err ) => err );

			await attachBlockDiagnostics( testInfo, 'invalid-widget-class-response.json', {
				isError: !! response.code,
				code: response.code,
				status: response.data && response.data.status,
			} );

			// The server_side_validation hook must surface this as a WP_Error,
			// which the REST API converts to a 400 response.
			expect( response.code ).toBeTruthy();
			expect( response.data && response.data.status ).toBe( 400 );
		} finally {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} ).catch( () => {} );
		}
	}
);

/**
 * What the editor does with the values the save bridge sends. The bridge reads
 * each open widget form when a post is saved. If nothing in the editor changed
 * while it read them, it gives each block its form's values and leaves the
 * post clean. If anything changed, it writes nothing to the editor.
 */
const IMAGE_BLOCK = 'sowb/siteorigin-widget-image-widget';
const EDITOR_BLOCK = 'sowb/siteorigin-widget-editor-widget';
const TEST_IMAGE = path.resolve(
	process.cwd(),
	'node_modules/siteorigin-tests-common/playwright/utilities/assets/test-image.png'
);

// A block's attributes and the values its form last reported to it.
const readWidgetBlock = ( page, clientId ) => page.evaluate( ( id ) => {
	const block = window.wp.data.select( 'core/block-editor' ).getBlock( id );
	const frameWindow = document.querySelector( 'iframe[name="editor-canvas"]' ).contentWindow;
	const $form = frameWindow.jQuery( frameWindow.document )
		.find( `[data-block="${ id }"]` )
		.find( '.siteorigin-widget-form-main[data-class]' )
		.first();
	const record = $form.data( 'sowb-block-last-widget-data-json' );

	return {
		attributeKeys: block ? Object.keys( block.attributes ) : [],
		widgetData: block ? block.attributes.widgetData || {} : null,
		record: record ? JSON.parse( record ) : null,
	};
}, clientId );

const readPost = ( page ) => page.evaluate( () => {
	const editor = window.wp.data.select( 'core/editor' );

	return {
		dirty: editor.isEditedPostDirty(),
		content: editor.getEditedPostContent(),
		title: editor.getEditedPostAttribute( 'title' ),
	};
} );

// Set a form field's value, with or without the change event the form listens for.
const setIframeFieldValue = ( page, clientId, fieldName, value, withChange = false ) => page.evaluate(
	( { id, name, fieldValue, change } ) => {
		const frameWindow = document.querySelector( 'iframe[name="editor-canvas"]' ).contentWindow;
		const form = frameWindow.document
			.querySelector( `[data-block="${ id }"] .siteorigin-widget-form.siteorigin-widget-form-main[data-class]` );
		const input = Array.from( form.querySelectorAll( '.siteorigin-widget-input' ) )
			.find( ( field ) => field.name.endsWith( `[${ name }]` ) );

		input.value = String( fieldValue );

		if ( change ) {
			frameWindow.jQuery( input ).trigger( 'change' );
		}

		return input.value;
	},
	{ id: clientId, name: fieldName, fieldValue: value, change: withChange }
);

// A widget's own preview request sets an attribute when it returns, which is
// an edit of its own. A form asks for one 300ms after it reports a change,
// which can be as it opens. Count the requests in flight and note when a form
// loads, so a test can wait until nothing has been pending for a while and
// then see only what the save does.
const PREVIEW_DEBOUNCE_MS = 500;
const PREVIEW_QUIET_MS = 1500;

const trackWidgetPreviews = ( page ) => {
	const previews = { pending: 0, lastChange: Date.now() };
	const isPreview = ( request ) => request.url().includes( '/sowb/v1/widgets/previews' );
	const settle = ( request ) => {
		if ( isPreview( request ) ) {
			previews.pending--;
			previews.lastChange = Date.now();
		}
	};

	page.on( 'request', ( request ) => {
		if ( isPreview( request ) ) {
			previews.pending++;
			previews.lastChange = Date.now();
		}
	} );
	page.on( 'requestfinished', ( request ) => {
		if ( request.url().includes( '/sowb/v1/widgets/forms' ) ) {
			previews.lastChange = Date.now();
		}
	} );
	page.on( 'requestfinished', settle );
	page.on( 'requestfailed', settle );

	return previews;
};

const waitForWidgetPreviews = async ( page, previews ) => {
	// Longer than the debounce, so a change just made has asked for its preview.
	await page.waitForTimeout( PREVIEW_DEBOUNCE_MS );
	await expect.poll(
		() => previews.pending === 0 && Date.now() - previews.lastChange >= PREVIEW_QUIET_MS,
		{ timeout: 30000 }
	).toBe( true );
	// A preview holds the post's save lock until it has been applied.
	await page.waitForFunction( () => ! window.wp.data.select( 'core/editor' ).isPostSavingLocked() );
};

const insertWidget = async ( admin, blockName ) => {
	const widget = await insertDirectWidgetBlock( admin, blockName );

	return widget.getAttribute( 'data-block' );
};

const waitForSaveToEnd = async ( page ) => {
	await page.waitForFunction( () => ! window.wp.data.select( 'core/editor' ).isSavingPost() );
};

const saveAndCapture = async ( page, fixture ) => {
	await waitForWidgetPreviews( page, fixture.previews );

	const content = await clickSaveAndCaptureContent( page, fixture.post.id );

	await waitForSaveToEnd( page );

	return content;
};

const expectNoClientIdAttributes = ( block ) => {
	expect( block.attributeKeys.filter( ( key ) => /^[0-9a-f-]{36}$/.test( key ) ) ).toEqual( [] );
};

// Hold the bridge at the nth form it reads, until the test releases it.
const holdSnapshot = ( page, nth ) => page.evaluate( ( holdAt ) => {
	const forms = document.querySelector( 'iframe[name="editor-canvas"]' ).contentWindow.sowbForms;
	const original = forms.getWidgetFormSnapshot;
	let calls = 0;

	window.sowbTestHold = { held: false, release: null };
	forms.getWidgetFormSnapshot = async function() {
		calls++;

		if ( calls === holdAt ) {
			window.sowbTestHold.held = true;
			await new Promise( ( resolve ) => {
				window.sowbTestHold.release = resolve;
			} );
			forms.getWidgetFormSnapshot = original;
		}

		return original.apply( this, arguments );
	};
}, nth );

// The bridge is a filter on editor.preSavePost, which WordPress has from 6.7.
const hasSaveFilter = () => {
	const version = wordPressVersion();

	if ( version === '' ) {
		return true;
	}

	const [ major, minor ] = version.split( '.' ).map( Number );

	return major > 6 || ( major === 6 && minor >= 7 );
};

const useBridgePost = ( title ) => {
	const fixture = {};

	test.skip( ! hasSaveFilter(), 'WordPress below 6.7 has no editor.preSavePost, so the save bridge does not run (#2523).' );

	test.beforeEach( async ( { page } ) => {
		fixture.previews = trackWidgetPreviews( page );
		Object.assign( fixture, await setupPublishedPostEditor( page, title ) );
		fixture.attachment = await fixture.requestUtils.uploadMedia( TEST_IMAGE );
	} );

	test.afterEach( async () => {
		await fixture.requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/posts/${ fixture.post.id }`,
			params: { force: true },
		} ).catch( () => {} );
		await fixture.requestUtils.deleteMedia( fixture.attachment.id ).catch( () => {} );
	} );

	return fixture;
};

test.describe( 'Save bridge and the editor, when nothing else changes during the save', () => {
	const fixture = useBridgePost( 'WB bridge undisturbed' );

	test( 'the block holds what was saved, and the post is clean', async ( { page } ) => {
		const { admin, post, attachment } = fixture;
		const imageId = String( attachment.id );
		const clientId = await insertWidget( admin, IMAGE_BLOCK );
		const form = getWidgetBlock( admin, IMAGE_BLOCK ).locator( '.siteorigin-widget-form-main[data-class]' );
		const formRequests = [];

		page.on( 'request', ( request ) => {
			if ( request.url().includes( '/sowb/v1/widgets/forms' ) ) {
				formRequests.push( request.url() );
			}
		} );

		// Let the forms finish loading before their fields are set.
		await waitForWidgetPreviews( page, fixture.previews );
		const formNode = await form.elementHandle();
		formRequests.length = 0;

		await setIframeFieldValue( page, clientId, 'image', imageId );
		expect( ( await readWidgetBlock( page, clientId ) ).widgetData.image ).not.toBe( imageId );

		const sent = await saveAndCapture( page, fixture );
		expect( sent ).toMatch( new RegExp( `"image":"?${ imageId }"?[,}]` ) );

		const block = await readWidgetBlock( page, clientId );
		expect( String( block.widgetData.image ) ).toBe( imageId );
		expectNoClientIdAttributes( block );

		const saved = await readPost( page );
		expect( saved.dirty ).toBe( false );
		expect( saved.content ).toBe( sent );

		// The open form is left alone.
		expect( formRequests ).toEqual( [] );
		expect( await formNode.evaluate( ( node ) => node.isConnected ) ).toBe( true );
		expect( await form.evaluate( ( node, original ) => node === original, formNode ) ).toBe( true );
	} );

	test( 'two widget blocks, one inside a Group, each hold their own values', async ( { page } ) => {
		const { admin, post, attachment } = fixture;
		const imageId = String( attachment.id );
		const editorId = await insertWidget( admin, EDITOR_BLOCK );
		// The Image widget goes inside a core Group.
		const nestedId = await page.evaluate( ( name ) => {
			const { insertBlock } = window.wp.data.dispatch( 'core/block-editor' );
			const widget = window.wp.blocks.createBlock( name );

			// A Group made with a block inside it skips its layout chooser.
			insertBlock( window.wp.blocks.createBlock( 'core/group', { layout: { type: 'constrained' } }, [ widget ] ) );

			return widget.clientId;
		}, IMAGE_BLOCK );

		await admin.editor.canvas
			.locator( `[data-block="${ nestedId }"] .siteorigin-widget-form-main[data-class]` )
			.waitFor( { timeout: 20000 } );

		expect( await page.evaluate(
			( id ) => window.wp.data.select( 'core/block-editor' ).getBlockName(
				window.wp.data.select( 'core/block-editor' ).getBlockRootClientId( id )
			),
			nestedId
		) ).toBe( 'core/group' );

		// Let the forms finish loading before their fields are set.
		await waitForWidgetPreviews( page, fixture.previews );
		await setIframeFieldValue( page, editorId, 'title', 'Bridge title' );
		await setIframeFieldValue( page, nestedId, 'image', imageId );

		const sent = await saveAndCapture( page, fixture );
		expect( sent ).toContain( '"title":"Bridge title"' );

		const editorBlock = await readWidgetBlock( page, editorId );
		const imageBlock = await readWidgetBlock( page, nestedId );

		expect( editorBlock.widgetData.title ).toBe( 'Bridge title' );
		expect( editorBlock.widgetData.image ).toBeUndefined();
		expect( String( imageBlock.widgetData.image ) ).toBe( imageId );
		expect( imageBlock.widgetData.title ).not.toBe( 'Bridge title' );
		expectNoClientIdAttributes( editorBlock );
		expectNoClientIdAttributes( imageBlock );
		expect( ( await readPost( page ) ).dirty ).toBe( false );
	} );

	test( 'a field changed back to its earlier value after a save still reaches the block', async ( { page } ) => {
		const { admin, post } = fixture;
		const clientId = await insertWidget( admin, EDITOR_BLOCK );

		// Let the forms finish loading before their fields are set.
		await waitForWidgetPreviews( page, fixture.previews );
		await setIframeFieldValue( page, clientId, 'title', 'First', true );
		expect( ( await readWidgetBlock( page, clientId ) ).widgetData.title ).toBe( 'First' );

		await setIframeFieldValue( page, clientId, 'title', 'Second' );
		await saveAndCapture( page, fixture );
		expect( ( await readWidgetBlock( page, clientId ) ).widgetData.title ).toBe( 'Second' );

		await setIframeFieldValue( page, clientId, 'title', 'First', true );
		expect( ( await readWidgetBlock( page, clientId ) ).widgetData.title ).toBe( 'First' );
	} );

	test( 'a save adds no undo step of its own', async ( { page } ) => {
		const { admin } = fixture;
		const clientId = await insertWidget( admin, EDITOR_BLOCK );
		const title = async () => ( await readWidgetBlock( page, clientId ) ).widgetData.title;

		// Press Undo until the edit is gone, and say how many presses it took.
		const undoTheEdit = async () => {
			let presses = 0;

			while ( await title() === 'Edited' && presses < 10 ) {
				await page.evaluate( () => window.wp.data.dispatch( 'core' ).undo() );
				presses++;
			}

			return presses;
		};

		// Let the forms finish loading before their fields are set.
		await waitForWidgetPreviews( page, fixture.previews );
		await setIframeFieldValue( page, clientId, 'title', 'Edited', true );
		expect( await title() ).toBe( 'Edited' );
		await waitForWidgetPreviews( page, fixture.previews );

		// The widget's own preview can record a step after the edit, so count
		// what it takes before the save, then put the edit back.
		const pressesBefore = await undoTheEdit();
		expect( pressesBefore ).toBeGreaterThan( 0 );
		expect( await title() ).not.toBe( 'Edited' );

		for ( let i = 0; i < pressesBefore; i++ ) {
			await page.evaluate( () => window.wp.data.dispatch( 'core' ).redo() );
		}
		expect( await title() ).toBe( 'Edited' );

		await saveAndCapture( page, fixture );
		expect( ( await readPost( page ) ).dirty ).toBe( false );

		// The same number of presses undoes the edit after the save.
		expect( await undoTheEdit() ).toBe( pressesBefore );
		expect( await title() ).not.toBe( 'Edited' );
		expect( ( await readPost( page ) ).dirty ).toBe( true );
	} );

	test( 'an autosave sends the form values and leaves the editor alone', async ( { page } ) => {
		const { admin, post, attachment } = fixture;
		const imageId = String( attachment.id );
		const clientId = await insertWidget( admin, IMAGE_BLOCK );

		// Let the forms finish loading before their fields are set.
		await waitForWidgetPreviews( page, fixture.previews );
		await setIframeFieldValue( page, clientId, 'image', imageId );

		await waitForWidgetPreviews( page, fixture.previews );

		const before = await readWidgetBlock( page, clientId );
		const autosaveRequest = page.waitForRequest(
			( request ) => request.method() === 'POST' &&
				new URL( request.url() ).pathname.includes( `/wp/v2/posts/${ post.id }` ),
			{ timeout: 30000 }
		);

		await page.evaluate( () => window.wp.data.dispatch( 'core/editor' ).autosave() );

		const sent = getPayloadContent( parseRequestPayload( await autosaveRequest ) );
		expect( sent ).toMatch( new RegExp( `"image":"?${ imageId }"?[,}]` ) );

		await page.waitForFunction( () => ! window.wp.data.select( 'core/editor' ).isAutosavingPost() );

		const after = await readWidgetBlock( page, clientId );
		expect( after.widgetData ).toEqual( before.widgetData );
		expect( after.record ).toEqual( before.record );
		expectNoClientIdAttributes( after );
	} );
} );

test.describe( 'Save bridge and the editor, when something changes during the save', () => {
	const fixture = useBridgePost( 'WB bridge disturbed' );

	// Each is done while the bridge is held at the second form it reads.
	const DISTURBANCES = [
		{
			name: 'the first widget changes with a change event',
			run: ( page, ids ) => setIframeFieldValue( page, ids.editor, 'title', 'Changed during save', true ),
		},
		{
			name: 'the first widget changes with no change event',
			run: ( page, ids ) => setIframeFieldValue( page, ids.editor, 'title', 'Changed during save' ),
		},
		{
			name: 'a paragraph is typed in, not yet recorded as a content edit',
			run: ( page, ids ) => page.evaluate( ( id ) => {
				const dispatch = window.wp.data.dispatch( 'core/block-editor' );

				dispatch.__unstableMarkNextChangeAsNotPersistent();
				dispatch.updateBlockAttributes( id, { content: 'Typed during save' } );
			}, ids.paragraph ),
		},
		{
			name: 'the post title is edited',
			run: ( page ) => page.evaluate( () => {
				window.wp.data.dispatch( 'core/editor' ).editPost( { title: 'Retitled during save' } );
			} ),
		},
		{
			name: 'the first widget block is removed, taking its form with it',
			run: ( page, ids ) => page.evaluate( ( id ) => {
				window.wp.data.dispatch( 'core/block-editor' ).removeBlock( id );
			}, ids.editor ),
		},
	];

	for ( const disturbance of DISTURBANCES ) {
		test( `nothing is written to the editor when ${ disturbance.name }`, async ( { page } ) => {
			const { admin, post, attachment } = fixture;
			const imageId = String( attachment.id );
			const ids = {
				paragraph: await page.evaluate( () => {
					const block = window.wp.blocks.createBlock( 'core/paragraph', { content: 'Paragraph' } );

					window.wp.data.dispatch( 'core/block-editor' ).insertBlock( block );

					return block.clientId;
				} ),
				editor: await insertWidget( admin, EDITOR_BLOCK ),
				image: await insertWidget( admin, IMAGE_BLOCK ),
			};

			// Let the forms finish loading before their fields are set.
			await waitForWidgetPreviews( page, fixture.previews );
			await setIframeFieldValue( page, ids.image, 'image', imageId );

			await waitForWidgetPreviews( page, fixture.previews );

			const before = await readWidgetBlock( page, ids.image );
			await holdSnapshot( page, 2 );

			const save = clickSaveAndCaptureContent( page, post.id );
			await page.waitForFunction( () => window.sowbTestHold.held );
			await disturbance.run( page, ids );
			await page.evaluate( () => window.sowbTestHold.release() );

			const sent = await save;
			await waitForSaveToEnd( page );

			// The save still carries the form's values.
			expect( sent ).toMatch( new RegExp( `"image":"?${ imageId }"?[,}]` ) );

			// The editor is left to its own edits.
			const after = await readWidgetBlock( page, ids.image );
			expect( after.widgetData ).toEqual( before.widgetData );
			expect( after.record ).toEqual( before.record );
			expectNoClientIdAttributes( after );
			expect( ( await readPost( page ) ).dirty ).toBe( true );
		} );
	}

	// Each is made by a store subscriber, as soon as the widget block holds the image.
	const SUBSCRIBER_EDITS = [
		{
			name: 'a paragraph',
			edit: 'paragraph',
			kept: ( saved ) => expect( saved.content ).toContain( 'Edited by a subscriber' ),
		},
		{
			name: 'the post title',
			edit: 'title',
			kept: ( saved ) => expect( saved.title ).toBe( 'Retitled by a subscriber' ),
		},
	];

	for ( const subscriber of SUBSCRIBER_EDITS ) {
		test( `an edit to ${ subscriber.name } by other code while the blocks are updated is kept, and the post stays dirty`, async ( { page } ) => {
			const { admin, attachment } = fixture;
			const imageId = String( attachment.id );
			const paragraphId = await page.evaluate( () => {
				const block = window.wp.blocks.createBlock( 'core/paragraph', { content: 'Paragraph' } );

				window.wp.data.dispatch( 'core/block-editor' ).insertBlock( block );

				return block.clientId;
			} );
			const clientId = await insertWidget( admin, IMAGE_BLOCK );

			// Let the forms finish loading before their fields are set.
			await waitForWidgetPreviews( page, fixture.previews );
			await setIframeFieldValue( page, clientId, 'image', imageId );

			await page.evaluate( ( { widgetId, paragraph, image, edit } ) => {
				const select = window.wp.data.select( 'core/block-editor' );
				let done = false;

				window.wp.data.subscribe( () => {
					const block = select.getBlock( widgetId );

					if ( done || ! block || String( ( block.attributes.widgetData || {} ).image ) !== image ) {
						return;
					}

					done = true;

					if ( edit === 'title' ) {
						window.wp.data.dispatch( 'core/editor' ).editPost( { title: 'Retitled by a subscriber' } );
						return;
					}

					window.wp.data.dispatch( 'core/block-editor' )
						.updateBlockAttributes( paragraph, { content: 'Edited by a subscriber' } );
				} );
			}, { widgetId: clientId, paragraph: paragraphId, image: imageId, edit: subscriber.edit } );

			await saveAndCapture( page, fixture );

			// The block holds what was saved; nothing more was written.
			const block = await readWidgetBlock( page, clientId );
			expect( String( block.widgetData.image ) ).toBe( imageId );
			expect( block.record ).toBeNull();

			const saved = await readPost( page );
			subscriber.kept( saved );
			expect( saved.dirty ).toBe( true );
		} );
	}

	for ( const failing of [ 'updateBlockAttributes', 'editPost' ] ) {
		test( `the save still carries the form values when ${ failing } throws`, async ( { page } ) => {
			const { admin } = fixture;
			const paragraphId = await page.evaluate( () => {
				const block = window.wp.blocks.createBlock( 'core/paragraph', { content: 'Paragraph' } );

				window.wp.data.dispatch( 'core/block-editor' ).insertBlock( block );

				return block.clientId;
			} );
			const clientId = await insertWidget( admin, EDITOR_BLOCK );

			// Let the forms finish loading before their fields are set.
			await waitForWidgetPreviews( page, fixture.previews );
			await setIframeFieldValue( page, clientId, 'title', 'Reported', true );
			await setIframeFieldValue( page, clientId, 'title', 'Sent in the save' );

			await page.evaluate( ( name ) => {
				const store = name === 'editPost' ? 'core/editor' : 'core/block-editor';
				const dispatch = window.wp.data.dispatch( store );
				const original = dispatch[ name ];

				dispatch[ name ] = function( ...args ) {
					const fromBridge = name === 'editPost' ?
						args[ 0 ] && typeof args[ 0 ].content === 'string' && args[ 0 ].content.includes( 'Sent in the save' ) :
						args[ 2 ] === true;

					if ( fromBridge ) {
						dispatch[ name ] = original;
						throw new Error( `${ name } failed for the test` );
					}

					return original.apply( this, args );
				};
			}, failing );

			const sent = await saveAndCapture( page, fixture );
			expect( sent ).toContain( '"title":"Sent in the save"' );

			const block = await readWidgetBlock( page, clientId );
			expect( block.record ).toBeNull();

			if ( failing === 'editPost' ) {
				expect( block.widgetData.title ).toBe( 'Sent in the save' );
				expect( ( await readPost( page ) ).dirty ).toBe( true );
			}

			// The first edit after the failure is still an ordinary one: recorded, and undone by one Undo.
			const paragraphContent = () => page.evaluate(
				( id ) => String( window.wp.data.select( 'core/block-editor' ).getBlockAttributes( id ).content ),
				paragraphId
			);

			await page.evaluate( ( id ) => {
				window.wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( id, { content: 'Edited after the failure' } );
			}, paragraphId );
			expect( await paragraphContent() ).toBe( 'Edited after the failure' );
			expect( ( await readPost( page ) ).dirty ).toBe( true );

			await page.evaluate( () => window.wp.data.dispatch( 'core' ).undo() );
			expect( await paragraphContent() ).toBe( 'Paragraph' );

			// With no record, a change back to the value last reported still reaches the block.
			await setIframeFieldValue( page, clientId, 'title', 'Reported', true );
			expect( ( await readWidgetBlock( page, clientId ) ).widgetData.title ).toBe( 'Reported' );
		} );
	}
} );
