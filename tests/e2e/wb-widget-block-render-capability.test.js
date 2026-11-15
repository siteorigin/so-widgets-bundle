const {
	expect,
	test
} = require( '@playwright/test' );

const common = require( 'siteorigin-tests-common/playwright/common' );

const {
	setupRequestUtils,
} = common;

const {
	call,
	createUserSession,
	deleteUsers,
} = require( './helpers/widget-block-abilities' );

// Mirror serialize_block_attributes() so seeded content matches what the
// block editor stores. Quotes stay as JSON escapes; none of these can
// produce the comment terminator.
const escapeBlockAttrs = ( attrs ) => JSON.stringify( attrs )
	.replace( /--/g, '\\u002d\\u002d' )
	.replace( /</g, '\\u003c' )
	.replace( />/g, '\\u003e' )
	.replace( /&/g, '\\u0026' );

const editorWidgetBlock = ( text ) => `<!-- wp:sowb/siteorigin-widget-editor-widget ${ escapeBlockAttrs( {
	widgetClass: 'SiteOrigin_Widget_Editor_Widget',
	widgetData: {
		title: '',
		text,
		autop: false,
		text_selected_editor: 'tinymce',
	},
} ) } /-->`;

// Create an author (no unfiltered_html) and log it in. Application passwords
// need HTTPS or a local environment, so the author acts through its login
// cookie and a REST nonce, which work on any test site. The created user is
// pushed to `created` for the caller to delete.
const createAuthorSession = async ( requestUtils, created ) => {
	const session = await createUserSession( requestUtils, 'author', created );

	// The requests that follow really are the author's.
	const me = await call( session, 'GET', 'wp-json/wp/v2/users/me?context=edit' );
	expect( me.status ).toBe( 200 );
	expect( me.body.id ).toBe( session.id );
	expect( me.body.roles ).toEqual( [ 'author' ] );

	return session;
};

test(
	'Editor widget block keeps an admin authored iframe for logged out visitors.',
	async ( { browser } ) => {
		const requestUtils = await setupRequestUtils();
		const post = await requestUtils.createPost( {
			status: 'publish',
			title: 'WB render capability - admin iframe',
			content: editorWidgetBlock(
				'<p>before</p><iframe src="https://example.com/frame" width="400" height="200"></iframe><p>after</p>'
			),
		} );

		try {
			// The saving admin has unfiltered_html, so the iframe must reach storage.
			const saved = await requestUtils.rest( {
				method: 'GET',
				path: `/wp/v2/posts/${ post.id }`,
				params: { context: 'edit' },
			} );
			expect( saved.content.raw ).toContain( 'example.com/frame' );

			// A fresh context has no auth cookies — this is a logged out visitor.
			const anonContext = await browser.newContext();
			const anonPage = await anonContext.newPage();
			await anonPage.goto( post.link );

			const widget = anonPage.locator( '.siteorigin-widget-tinymce' );
			await expect( widget ).toContainText( 'before' );
			await expect(
				widget.locator( 'iframe[src="https://example.com/frame"]' )
			).toHaveCount( 1 );

			// The REST save pre-renders widgetMarkup, so the GET above serves the
			// stored markup. A POST forces the live render path — the one that
			// re-sanitized stored values with the viewer's capability. Assert an
			// actual iframe element, not just the string, so escaped output such
			// as &lt;iframe cannot pass.
			const slowPath = await anonContext.request.post( post.link, {
				form: { wb_render_capability_probe: '1' },
			} );
			const slowPathHtml = await slowPath.text();
			expect( slowPathHtml ).toMatch(
				/<iframe[^>]*\ssrc="https:\/\/example\.com\/frame"/
			);

			await anonContext.close();
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
	'Editor widget block content from a user without unfiltered_html is sanitized at save.',
	async () => {
		const requestUtils = await setupRequestUtils();
		const created = [];
		let session = null;
		let postId = null;

		try {
			session = await createAuthorSession( requestUtils, created );

			const response = await call( session, 'POST', 'wp-json/wp/v2/posts', {
				status: 'draft',
				title: 'WB render capability - author save',
				content: editorWidgetBlock(
					'<p>keep</p><script>alert(1)</script><iframe src="https://example.com/frame"></iframe>'
				),
			} );
			expect( response.status ).toBe( 201 );

			const saved = response.body;
			postId = saved.id;

			// Core kses runs at save under the saving user: tags are stripped
			// from the decoded attributes, inner text survives.
			expect( saved.content.raw ).toContain( 'keep' );
			expect( saved.content.raw ).toContain( 'alert(1)' );
			expect( saved.content.raw ).not.toContain( 'example.com/frame' );
			expect( saved.content.raw ).not.toMatch( /script/ );
		} finally {
			if ( postId ) {
				await requestUtils.rest( {
					method: 'DELETE',
					path: `/wp/v2/posts/${ postId }`,
					params: { force: true },
				} ).catch( () => {} );
			}

			if ( session ) {
				await session.context.dispose();
			}

			await deleteUsers( requestUtils, created );
		}
	}
);

test(
	'Block renderer preview cannot supply unsaved widgetData to the render path.',
	async () => {
		// render_widget_block() sets the render flag that skips sanitization, so
		// any render caller that carries unsaved request attributes must not
		// reach it. The block does not register widgetData/widgetClass server
		// side, so the core block-renderer schema rejects them before render.
		const requestUtils = await setupRequestUtils();
		const created = [];
		let session = null;

		try {
			session = await createAuthorSession( requestUtils, created );

			const response = await call(
				session,
				'POST',
				'wp-json/wp/v2/block-renderer/sowb/siteorigin-widget-editor-widget?context=edit',
				{
					attributes: {
						widgetClass: 'SiteOrigin_Widget_Editor_Widget',
						widgetData: {
							text: '<script>alert(1)</script>',
							text_selected_editor: 'tinymce',
							autop: false,
						},
					},
				}
			);

			// Schema validation rejects the unsaved attributes; render never runs.
			// Pin the actual boundary: the widgetData/widgetClass properties are
			// refused because they are not registered server side.
			expect( response.status ).toBe( 400 );
			const body = response.body;
			expect( body.code ).toBe( 'rest_invalid_param' );
			expect( body.data?.params?.attributes ).toMatch( /widgetClass/ );
			expect( body.data?.details?.attributes?.code ).toBe(
				'rest_additional_properties_forbidden'
			);
			expect( JSON.stringify( body ) ).not.toContain( '<script>alert(1)' );
		} finally {
			if ( session ) {
				await session.context.dispose();
			}

			await deleteUsers( requestUtils, created );
		}
	}
);
