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

// The HTML anchor and Additional CSS classes are block supports, stored next
// to the widget attrs exactly as the block editor stores them.
const buttonBlock = ( extra = {} ) => `<!-- wp:sowb/siteorigin-widget-button-widget ${ escapeBlockAttrs( {
	widgetClass: 'SiteOrigin_Widget_Button_Widget',
	widgetData: {
		text: 'Pricing',
		url: '#',
	},
	...extra,
} ) } /-->`;

const editorBlock = ( extra = {} ) => `<!-- wp:sowb/siteorigin-widget-editor-widget ${ escapeBlockAttrs( {
	widgetClass: 'SiteOrigin_Widget_Editor_Widget',
	widgetData: {
		title: '',
		text: '<p>Hi</p>',
		autop: false,
		text_selected_editor: 'tinymce',
	},
	...extra,
} ) } /-->`;

const VALID = {
	anchor: 'pricing',
	className: 'northfield-btn',
};

const HOSTILE = {
	anchor: 'x" onmouseover="alert(1)',
	className: 'a"><img src=x onerror=alert(1)>',
};

const getRaw = async ( requestUtils, postId ) => {
	const saved = await requestUtils.rest( {
		method: 'GET',
		path: `/wp/v2/posts/${ postId }`,
		params: { context: 'edit' },
	} );

	return saved.content.raw;
};

const deletePost = ( requestUtils, postId ) => requestUtils.rest( {
	method: 'DELETE',
	path: `/wp/v2/posts/${ postId }`,
	params: { force: true },
} ).catch( () => {} );

// Create an author (no unfiltered_html) with an application password, and
// return a POST helper that saves a post as that author.
const createAuthor = async ( requestUtils, slug ) => {
	const suffix = `${ Date.now() }-${ Math.floor( Math.random() * 1e6 ) }`;
	const author = await requestUtils.rest( {
		method: 'POST',
		path: '/wp/v2/users',
		params: {
			username: `wbanchor-${ slug }-${ suffix }`,
			email: `wbanchor-${ slug }-${ suffix }@example.com`,
			password: `wbanchor-pass-${ suffix }!A1`,
			roles: [ 'author' ],
		},
	} );

	const appPassword = await requestUtils.rest( {
		method: 'POST',
		path: `/wp/v2/users/${ author.id }/application-passwords`,
		params: { name: 'wbanchor-e2e' },
	} );

	const createPost = ( body ) => fetch( restUrl( 'wp-json/wp/v2/posts' ), {
		method: 'POST',
		headers: {
			'Content-Type': 'application/json',
			Authorization: 'Basic ' + Buffer.from(
				`${ author.username }:${ appPassword.password }`
			).toString( 'base64' ),
		},
		body: JSON.stringify( body ),
	} );

	return { author, createPost };
};

const deleteUser = ( requestUtils, userId ) => requestUtils.rest( {
	method: 'DELETE',
	path: `/wp/v2/users/${ userId }`,
	params: { force: true, reassign: 1 },
} ).catch( () => {} );

const expectValidSaved = async ( requestUtils, page, post ) => {
	const raw = await getRaw( requestUtils, post.id );
	expect( raw ).toContain( '"anchor":"pricing"' );
	expect( raw ).toContain( '"className":"northfield-btn"' );

	await page.goto( post.link );
	await expect( page.locator( '#pricing.northfield-btn' ) ).toHaveCount( 1 );
};

test(
	'Button widget block keeps a valid anchor and CSS class on save.',
	async ( { page } ) => {
		const requestUtils = await setupRequestUtils();
		const post = await requestUtils.createPost( {
			status: 'publish',
			title: 'WB anchor class - admin button',
			content: buttonBlock( VALID ),
		} );

		try {
			await expectValidSaved( requestUtils, page, post );
		} finally {
			await deletePost( requestUtils, post.id );
		}
	}
);

test(
	'Editor widget block keeps a valid anchor and CSS class in its cached markup.',
	async ( { page } ) => {
		const requestUtils = await setupRequestUtils();
		const post = await requestUtils.createPost( {
			status: 'publish',
			title: 'WB anchor class - admin editor',
			content: editorBlock( VALID ),
		} );

		try {
			await expectValidSaved( requestUtils, page, post );
		} finally {
			await deletePost( requestUtils, post.id );
		}
	}
);

test(
	'A user without unfiltered_html keeps a valid anchor and CSS class on save.',
	async ( { page } ) => {
		const requestUtils = await setupRequestUtils();
		let author = null;
		let postId = null;

		try {
			const session = await createAuthor( requestUtils, 'valid' );
			author = session.author;

			const response = await session.createPost( {
				status: 'publish',
				title: 'WB anchor class - author button',
				content: buttonBlock( VALID ),
			} );
			expect( response.status ).toBe( 201 );

			const saved = await response.json();
			postId = saved.id;

			await expectValidSaved( requestUtils, page, saved );
		} finally {
			if ( postId ) {
				await deletePost( requestUtils, postId );
			}

			if ( author ) {
				await deleteUser( requestUtils, author.id );
			}
		}
	}
);

test(
	'Only the valid tokens of a mixed CSS class value are kept.',
	async ( { page } ) => {
		const requestUtils = await setupRequestUtils();
		const post = await requestUtils.createPost( {
			status: 'publish',
			title: 'WB anchor class - mixed class',
			content: buttonBlock( { className: 'northfield-btn md:flex a.b' } ),
		} );

		try {
			const raw = await getRaw( requestUtils, post.id );
			expect( raw ).toContain( '"className":"northfield-btn"' );

			const response = await page.goto( post.link );
			const html = await response.text();

			await expect( page.locator( '.so-widget-sow-button.northfield-btn' ) ).toHaveCount( 1 );
			expect( html ).not.toContain( 'md:flex' );
			expect( html ).not.toContain( 'mdflex' );
			expect( html ).not.toContain( 'a.b' );
		} finally {
			await deletePost( requestUtils, post.id );
		}
	}
);

const expectHostileDropped = async ( page, saved ) => {
	expect( saved.content.raw ).not.toContain( '"anchor"' );
	expect( saved.content.raw ).not.toContain( '"className"' );
	expect( saved.content.raw ).not.toContain( 'onmouseover' );
	expect( saved.content.raw ).not.toContain( 'onerror' );

	const response = await page.goto( saved.link );
	const html = await response.text();
	expect( html ).not.toContain( 'onmouseover' );
	expect( html ).not.toContain( 'onerror' );
	await expect( page.locator( '[onmouseover], [onerror]' ) ).toHaveCount( 0 );

	// Both widgets still render, with no id on the wrapper.
	for ( const wrapper of [ '.so-widget-sow-button', '.so-widget-sow-editor' ] ) {
		await expect( page.locator( wrapper ) ).toHaveCount( 1 );
		await expect( page.locator( wrapper ) ).not.toHaveAttribute( 'id', /.*/ );
	}
};

test(
	'Hostile anchor and CSS class values are dropped for an author and an admin alike.',
	async ( { page } ) => {
		const requestUtils = await setupRequestUtils();
		const content = buttonBlock( HOSTILE ) + '\n\n' + editorBlock( HOSTILE );
		let author = null;
		const postIds = [];

		try {
			const session = await createAuthor( requestUtils, 'hostile' );
			author = session.author;

			const response = await session.createPost( {
				status: 'publish',
				title: 'WB anchor class - author hostile',
				content,
			} );
			expect( response.status ).toBe( 201 );

			const authorPost = await response.json();
			postIds.push( authorPost.id );
			await expectHostileDropped( page, authorPost );

			// The same payload as admin gives the same result.
			const adminPost = await requestUtils.createPost( {
				status: 'publish',
				title: 'WB anchor class - admin hostile',
				content,
			} );
			postIds.push( adminPost.id );
			await expectHostileDropped( page, {
				...adminPost,
				content: { raw: await getRaw( requestUtils, adminPost.id ) },
			} );
		} finally {
			for ( const postId of postIds ) {
				await deletePost( requestUtils, postId );
			}

			if ( author ) {
				await deleteUser( requestUtils, author.id );
			}
		}
	}
);

test(
	'An anchor or CSS class of "0" is not stored.',
	async () => {
		const requestUtils = await setupRequestUtils();
		const post = await requestUtils.createPost( {
			status: 'publish',
			title: 'WB anchor class - zero',
			content: buttonBlock( { anchor: '0', className: '0' } ),
		} );

		try {
			const raw = await getRaw( requestUtils, post.id );
			expect( raw ).not.toContain( '"anchor"' );
			expect( raw ).not.toContain( '"className"' );
		} finally {
			await deletePost( requestUtils, post.id );
		}
	}
);
