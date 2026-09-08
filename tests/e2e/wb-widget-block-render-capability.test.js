const {
	expect,
	test
} = require( '@playwright/test' );

const common = require( 'siteorigin-tests-common/playwright/common' );

const {
	setupRequestUtils,
} = common;

// Join a REST path to WP_BASE_URL for the raw fetch() calls that carry their
// own auth. Normalizing the base to end in a slash keeps this correct for both
// a root install (CI default, no trailing slash) and a subdirectory install.
const restUrl = ( relativePath ) => {
	const base = process.env.WP_BASE_URL.endsWith( '/' )
		? process.env.WP_BASE_URL
		: `${ process.env.WP_BASE_URL }/`;

	return new URL( relativePath, base ).toString();
};

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
		const suffix = Date.now();
		let author = null;
		let postId = null;

		try {
			author = await requestUtils.rest( {
				method: 'POST',
				path: '/wp/v2/users',
				params: {
					username: `wbcap-author-${ suffix }`,
					email: `wbcap-author-${ suffix }@example.com`,
					password: `wbcap-pass-${ suffix }!A1`,
					roles: [ 'author' ],
				},
			} );

			const appPassword = await requestUtils.rest( {
				method: 'POST',
				path: `/wp/v2/users/${ author.id }/application-passwords`,
				params: { name: 'wbcap-e2e' },
			} );

			const response = await fetch( restUrl( 'wp-json/wp/v2/posts' ), {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
					Authorization: 'Basic ' + Buffer.from(
						`${ author.username }:${ appPassword.password }`
					).toString( 'base64' ),
				},
				body: JSON.stringify( {
					status: 'draft',
					title: 'WB render capability - author save',
					content: editorWidgetBlock(
						'<p>keep</p><script>alert(1)</script><iframe src="https://example.com/frame"></iframe>'
					),
				} ),
			} );
			expect( response.status ).toBe( 201 );

			const saved = await response.json();
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

			if ( author ) {
				await requestUtils.rest( {
					method: 'DELETE',
					path: `/wp/v2/users/${ author.id }`,
					params: { force: true, reassign: 1 },
				} ).catch( () => {} );
			}
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
		const suffix = Date.now();
		let author = null;

		try {
			author = await requestUtils.rest( {
				method: 'POST',
				path: '/wp/v2/users',
				params: {
					username: `wbcap-prev-${ suffix }`,
					email: `wbcap-prev-${ suffix }@example.com`,
					password: `wbcap-prev-${ suffix }!A1`,
					roles: [ 'author' ],
				},
			} );

			const appPassword = await requestUtils.rest( {
				method: 'POST',
				path: `/wp/v2/users/${ author.id }/application-passwords`,
				params: { name: 'wbcap-preview-e2e' },
			} );

			const auth = 'Basic ' + Buffer.from(
				`${ author.username }:${ appPassword.password }`
			).toString( 'base64' );

			const response = await fetch(
				restUrl( 'wp-json/wp/v2/block-renderer/sowb/siteorigin-widget-editor-widget?context=edit' ),
				{
					method: 'POST',
					headers: { 'Content-Type': 'application/json', Authorization: auth },
					body: JSON.stringify( {
						attributes: {
							widgetClass: 'SiteOrigin_Widget_Editor_Widget',
							widgetData: {
								text: '<script>alert(1)</script>',
								text_selected_editor: 'tinymce',
								autop: false,
							},
						},
					} ),
				}
			);

			// Schema validation rejects the unsaved attributes; render never runs.
			// Pin the actual boundary: the widgetData/widgetClass properties are
			// refused because they are not registered server side.
			expect( response.status ).toBe( 400 );
			const body = await response.json();
			expect( body.code ).toBe( 'rest_invalid_param' );
			expect( body.data?.params?.attributes ).toMatch( /widgetClass/ );
			expect( body.data?.details?.attributes?.code ).toBe(
				'rest_additional_properties_forbidden'
			);
			expect( JSON.stringify( body ) ).not.toContain( '<script>alert(1)' );
		} finally {
			if ( author ) {
				await requestUtils.rest( {
					method: 'DELETE',
					path: `/wp/v2/users/${ author.id }`,
					params: { force: true, reassign: 1 },
				} ).catch( () => {} );
			}
		}
	}
);
