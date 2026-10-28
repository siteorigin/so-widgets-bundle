const {
	expect,
	test,
} = require( '@playwright/test' );

const {
	doLogin,
	setupRequestUtils,
	soGoTo,
} = require( 'siteorigin-tests-common/playwright/common' );

const EDITOR_BLOCK = 'sowb/siteorigin-widget-editor-widget';
const CTA_BLOCK = 'sowb/siteorigin-widget-cta-widget';

const publishChanges = async ( page, keyboard = false ) => {
	const publish = page.locator( '#save' );
	await expect( publish ).toBeEnabled();
	const saved = page.waitForResponse( ( response ) => {
		const data = response.request().postData();
		return response.url().includes( 'admin-ajax.php' ) && data &&
			new URLSearchParams( data ).get( 'action' ) === 'customize_save';
	} );
	if ( keyboard ) {
		await page.keyboard.press( 'ControlOrMeta+S' );
	} else {
		await publish.click();
	}
	expect( ( await ( await saved ).json() ).success ).toBe( true );
	await expect( publish ).toBeDisabled( { timeout: 20000 } );
};

const serializeWidgetBlock = ( name, widgetClass, widgetData, extraAttributes = {} ) => {
	const attributes = JSON.stringify( { widgetClass, widgetData, ...extraAttributes } )
		.replace( /--/g, '\\u002d\\u002d' )
		.replace( /</g, '\\u003c' )
		.replace( />/g, '\\u003e' )
		.replace( /&/g, '\\u0026' );

	return `<!-- wp:${ name } ${ attributes } /-->`;
};

const openCustomizer = async ( page, sidebar, previewUrl ) => {
	const query = new URLSearchParams( {
		url: previewUrl,
		'autofocus[section]': `sidebar-widgets-${ sidebar.id }`,
	} );

	await soGoTo( page, `wp-admin/customize.php?${ query }` );
	await expect( page.locator( '.customize-control-sidebar_block_editor' ).first() )
		.toBeAttached( { timeout: 20000 } );
	const welcome = page.getByRole( 'button', { name: 'Got it', exact: true } );
	await welcome.waitFor( { state: 'visible', timeout: 5000 } ).catch( () => {} );
	if ( await welcome.isVisible() ) {
		await welcome.click();
	}

	// Check registration explicitly so this fails at the missing asset gate,
	// instead of timing out later while looking for an unsupported block's form.
	await expect.poll( () => page.evaluate( ( names ) => names.every(
		( name ) => !! window.wp?.blocks?.getBlockType( name )
	), [ EDITOR_BLOCK, CTA_BLOCK ] ), { timeout: 20000 } ).toBe( true );
};

const openWidgetForm = async ( page, blockName, marker ) => {
	let clientId;
	await expect.poll( async () => {
		clientId = await page.evaluate( ( { name, content } ) => {
			const blocks = window.wp.data.select( 'core/block-editor' ).getBlocks();
			return blocks.find( ( block ) => block.name === name &&
				JSON.stringify( block.attributes.widgetData || {} ).includes( content ) )?.clientId;
		}, { name: blockName, content: marker } );
		return !! clientId;
	}, { timeout: 20000 } ).toBe( true );
	const block = page.locator( `[data-block="${ clientId }"]` );
	await block.click();
	await expect.poll( () => page.evaluate( () =>
		window.wp.data.select( 'core/block-editor' ).getSelectedBlockClientId() ) ).toBe( clientId );
	await expect( page.getByRole( 'toolbar', { name: 'Block tools' } ).getByRole( 'button', {
		name: blockName === CTA_BLOCK ? 'SiteOrigin Call To Action' : 'SiteOrigin Editor', exact: true,
	} ) ).toBeVisible();
	// The narrow Customizer panel can place the toolbar under its sticky header.
	await page.getByRole( 'button', { name: 'Edit widget.', exact: true } ).click( { force: true } );
	const form = block.locator( '.siteorigin-widget-form-main[data-class]' );
	await expect( form ).toBeVisible( { timeout: 20000 } );

	return { block, form };
};

test( 'Saved SiteOrigin widget blocks can be edited and published in Customizer widget areas.', async ( { page } ) => {
	const requestUtils = await setupRequestUtils();
	const sidebars = await requestUtils.rest( {
		method: 'GET',
		path: '/wp/v2/sidebars',
		params: { context: 'edit' },
	} );
	const sidebar = sidebars.find( ( item ) => item.status === 'active' );
	test.skip( ! sidebar, 'Requires a classic theme with a registered widget area.' );

	const suffix = `${ Date.now() }-${ Math.floor( Math.random() * 1e6 ) }`;
	const originalText = `Customizer original editor ${ suffix }`;
	const originalCta = `Customizer original CTA ${ suffix }`;
	const editedText = `Customizer published editor ${ suffix }`;
	const codeText = `Customizer published code ${ suffix }`;
	const editedCta = `Customizer published CTA ${ suffix }`;
	const originalDirectCta = `Customizer original direct CTA ${ suffix }`;
	const directCta = `Customizer direct CTA ${ suffix }`;
	const silentSubtitle = `Customizer silent subtitle ${ suffix }`;
	const preservedTitle = `Customizer preserved title ${ suffix }`;
	let post;
	const widgets = [];
	let releasePreview;

	try {
		post = await requestUtils.createPost( {
			status: 'publish',
			title: `WB Customizer regression ${ suffix }`,
			content: '<!-- wp:paragraph --><p>Widget area preview.</p><!-- /wp:paragraph -->',
		} );
		// Core maps each block widget to its first block in the Customizer.
		// Seed separate widgets, as the Widgets screen does when inserting them.
		for ( const content of [
			serializeWidgetBlock( EDITOR_BLOCK, 'SiteOrigin_Widget_Editor_Widget', {
				title: preservedTitle,
				text: `<p>${ originalText }</p>`,
				autop: true,
				text_selected_editor: 'tinymce',
			}, { widgetMarkup: '<div>Stale cached widget markup</div>' } ),
			serializeWidgetBlock( CTA_BLOCK, 'SiteOrigin_Widget_Cta_Widget', {
				title: originalCta,
				sub_title: 'Preserved CTA subtitle',
			}, { widgetMarkup: '<div>Stale cached CTA markup</div>', widgetIcons: [ 'stale-icon' ] } ),
			serializeWidgetBlock( CTA_BLOCK, 'SiteOrigin_Widget_Cta_Widget', {
				title: originalDirectCta,
				sub_title: 'Preserved CTA subtitle',
			} ),
		] ) {
			widgets.push( await requestUtils.rest( {
				method: 'POST',
				path: '/wp/v2/widgets',
				data: {
					id_base: 'block',
					sidebar: sidebar.id,
					instance: { raw: { content } },
				},
			} ) );
		}
		await requestUtils.rest( {
			method: 'POST',
			path: `/wp/v2/sidebars/${ sidebar.id }`,
			data: { widgets: [ ...sidebar.widgets, ...widgets.map( ( widget ) => widget.id ) ] },
		} );

		await doLogin( page );
		await openCustomizer( page, sidebar, post.link );
		const editor = await openWidgetForm( page, EDITOR_BLOCK, originalText );
		await expect( editor.form.locator( 'input[name$="[title]"]' ) ).toHaveValue( preservedTitle );
		const visualBody = editor.form.locator( '.siteorigin-widget-field-type-tinymce' )
			.frameLocator( 'iframe' ).locator( 'body' );
		await expect( visualBody ).toContainText( originalText );
		await expect( page.locator( '#save' ) ).toBeDisabled();
		// Hold the preview response so publishing happens while it is in flight.
		// Fetch it first so the request has reached the server before we publish.
		let previewStarted;
		const previewInFlight = new Promise( ( resolve ) => { previewStarted = resolve; } );
		const previewReleased = new Promise( ( resolve ) => { releasePreview = resolve; } );
		let previewFinished;
		const previewDone = new Promise( ( resolve ) => { previewFinished = resolve; } );
		await page.route( '**/sowb/v1/widgets/previews*', async ( route ) => {
			const data = new URLSearchParams( route.request().postData() || '' );
			if ( ! ( data.get( 'widgetData[text]' ) || '' ).includes( editedText ) ) {
				await route.continue();
				return;
			}
			try {
				const response = await route.fetch();
				previewStarted();
				await previewReleased;
				await route.fulfill( { response } );
				previewFinished();
			} catch ( error ) {
				previewFinished( error );
			}
		} );
		await page.evaluate( ( marker ) => {
			window.sowbTestPreviewCompleted = false;
			window.jQuery( document ).on( 'ajaxSuccess.sowbTest', ( event, xhr, settings ) => {
				const data = new URLSearchParams( settings.data || '' );
				if ( settings.url.includes( 'sowb/v1/widgets/previews' ) &&
					( data.get( 'widgetData[text]' ) || '' ).includes( marker ) ) {
					window.sowbTestPreviewCompleted = true;
					window.jQuery( document ).off( 'ajaxSuccess.sowbTest' );
				}
			} );
		}, editedText );
		await visualBody.fill( editedText );
		await Promise.race( [ previewInFlight, page.waitForTimeout( 20000 ).then( () => {
			throw new Error( 'Edited widget preview did not start.' );
		} ) ] );
		// Publish directly from the focused rich-text editor without opening Preview.
		await publishChanges( page );
		releasePreview();
		expect( await previewDone ).toBeUndefined();
		await page.waitForFunction( () => window.sowbTestPreviewCompleted );
		const editorClientId = await editor.block.getAttribute( 'data-block' );
		const attributesAfterPreview = await page.evaluate( ( clientId ) =>
			window.wp.data.select( 'core/block-editor' ).getBlock( clientId ).attributes, editorClientId );
		expect( attributesAfterPreview.widgetMarkup ).toBeFalsy();
		await expect( page.locator( '#save' ) ).toBeDisabled();
		await page.unroute( '**/sowb/v1/widgets/previews*' );
		const editorSaved = await requestUtils.rest( {
			path: `/wp/v2/widgets/${ widgets[ 0 ].id }`,
			params: { context: 'edit' },
		} );
		expect( editorSaved.instance.raw.content ).toContain( editedText );

		const cta = await openWidgetForm( page, CTA_BLOCK, originalCta );
		let ctaClientId = await cta.block.getAttribute( 'data-block' );
		const getCtaAttributes = () => page.evaluate( ( clientId ) =>
			window.wp.data.select( 'core/block-editor' ).getBlock( clientId ).attributes, ctaClientId );
		expect( ( await getCtaAttributes() ).widgetMarkup ).toContain( 'Stale cached CTA markup' );
		// Simulate a field that updates its DOM value without notifying the form.
		await cta.form.getByRole( 'textbox', { name: 'Title', exact: true } )
			.evaluate( ( input, value ) => { input.value = value; }, editedCta );
		expect( ( await getCtaAttributes() ).widgetData.title ).toBe( originalCta );
		await expect( page.locator( '#save' ) ).toBeDisabled();
		await page.getByRole( 'button', { name: 'Preview widget.', exact: true } ).click( { force: true } );
		await expect.poll( async () => ( await getCtaAttributes() ).widgetData.title ).toBe( editedCta );
		const ctaPreviewAttributes = await getCtaAttributes();
		expect( ctaPreviewAttributes.widgetMarkup ).toBeFalsy();
		expect( ctaPreviewAttributes.widgetIcons ).toBeFalsy();
		await publishChanges( page );

		// Enable Publish with a normal edit, then silently change another field.
		// Saving must snapshot the open form without switching to Preview.
		const directCtaForm = await openWidgetForm( page, CTA_BLOCK, originalDirectCta );
		ctaClientId = await directCtaForm.block.getAttribute( 'data-block' );
		await directCtaForm.form.getByRole( 'textbox', { name: 'Title', exact: true } ).fill( directCta );
		await expect.poll( async () => ( await getCtaAttributes() ).widgetData.title ).toBe( directCta );
		await directCtaForm.form.getByRole( 'textbox', { name: 'Subtitle', exact: true } )
			.evaluate( ( input, value ) => { input.value = value; }, silentSubtitle );
		expect( ( await getCtaAttributes() ).widgetData.sub_title ).toBe( 'Preserved CTA subtitle' );

		// A rejected snapshot must stop the Customizer save and tell the user.
		await page.evaluate( () => {
			window.sowbTestOriginalSnapshot = window.sowbForms.getWidgetFormSnapshot;
			window.sowbForms.getWidgetFormSnapshot = () => Promise.reject( new Error( 'Snapshot test failure' ) );
		} );
		let saveRequests = 0;
		const countSaveRequest = ( request ) => {
			const data = request.postData();
			if ( request.url().includes( 'admin-ajax.php' ) && data &&
				new URLSearchParams( data ).get( 'action' ) === 'customize_save' ) {
				saveRequests++;
			}
		};
		page.on( 'request', countSaveRequest );
		await page.locator( '#save' ).click();
		await expect( page.locator( '#customize-notifications-area' ).getByText( 'The widget could not be prepared for saving. Please try again.' ) ).toBeVisible();
		expect( saveRequests ).toBe( 0 );
		page.off( 'request', countSaveRequest );
		const afterRejectedSave = await requestUtils.rest( {
			path: `/wp/v2/widgets/${ widgets[ 2 ].id }`,
			params: { context: 'edit' },
		} );
		expect( afterRejectedSave.instance.raw.content ).not.toContain( silentSubtitle );
		await page.evaluate( () => { window.sowbForms.getWidgetFormSnapshot = window.sowbTestOriginalSnapshot; } );
		await publishChanges( page );

		const stored = await Promise.all( widgets.map( ( widget ) => requestUtils.rest( {
			method: 'GET',
			path: `/wp/v2/widgets/${ widget.id }`,
			params: { context: 'edit' },
		} ) ) );
		const savedContent = stored.map( ( item ) => item.instance.raw.content ).join( '\n' );
		for ( const marker of [ editedText, directCta, preservedTitle, silentSubtitle ] ) {
			expect( savedContent ).toContain( marker );
		}
		expect( savedContent ).not.toContain( originalText );
		expect( savedContent ).not.toContain( originalCta );

		await page.goto( post.link );
		await expect( page.locator( '.so-widget-sow-editor' ).filter( { hasText: editedText } ) ).toBeVisible();
		await expect( page.locator( '.so-widget-sow-cta' ).filter( { hasText: directCta } ) ).toContainText( silentSubtitle );
		await openCustomizer( page, sidebar, post.link );
		const reopened = await openWidgetForm( page, EDITOR_BLOCK, editedText );
		await expect( reopened.form.locator( 'input[name$="[title]"]' ) ).toHaveValue( preservedTitle );
		await expect( reopened.form.locator( '.siteorigin-widget-field-type-tinymce' )
			.frameLocator( 'iframe' ).locator( 'body' ) ).toContainText( editedText );
		await reopened.form.locator( 'button.switch-html' ).click();
		await reopened.form.locator( 'textarea.wp-editor-area' ).fill( codeText );
		// Use the save shortcut while focus remains in the edited field.
		await publishChanges( page, true );
		const codeSaved = await requestUtils.rest( {
			path: `/wp/v2/widgets/${ widgets[ 0 ].id }`,
			params: { context: 'edit' },
		} );
		expect( codeSaved.instance.raw.content ).toContain( codeText );
		await page.goto( post.link );
		await expect( page.locator( '.so-widget-sow-editor' ).filter( { hasText: codeText } ) ).toBeVisible();
		await openCustomizer( page, sidebar, post.link );
		const codeReopened = await openWidgetForm( page, EDITOR_BLOCK, codeText );
		await expect( codeReopened.form.locator( 'textarea.wp-editor-area' ) ).toHaveValue( codeText );
	} finally {
		if ( releasePreview ) {
			releasePreview();
		}
		for ( const widget of widgets ) {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/widgets/${ widget.id }`,
				params: { force: true },
			} );
		}
		if ( post ) {
			await requestUtils.rest( {
				method: 'DELETE',
				path: `/wp/v2/posts/${ post.id }`,
				params: { force: true },
			} );
		}
	}
} );
