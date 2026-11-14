const test = require( 'node:test' );
const assert = require( 'node:assert' );

const previewTheme = require( '../e2e/helpers/preview-theme' );

for ( const version of [ '', '6.7', '6.7.2', '6.10', '7.0', '7.1' ] ) {
	test( `previews Twenty Twenty-Five on WordPress ${ version || 'latest' }`, () => {
		const theme = previewTheme( version );

		assert.strictEqual( theme.preview, 'twentytwentyfive' );
		assert.strictEqual( theme.name, 'Twenty Twenty-Five' );
		assert.match( theme.font, /^\/wp-content\/themes\/twentytwentyfive\/.+\.woff2$/ );
		assert.strictEqual( theme.fontDescriptors, '' );
	} );
}

for ( const version of [ '6.6', '6.6.11' ] ) {
	test( `previews Twenty Twenty-Four on WordPress ${ version }`, () => {
		const theme = previewTheme( version );

		assert.strictEqual( theme.preview, 'twentytwentyfour' );
		assert.strictEqual( theme.name, 'Twenty Twenty-Four' );
		assert.match( theme.font, /^\/wp-content\/themes\/twentytwentyfour\/.+\.woff2$/ );
		assert.match( theme.fontDescriptors, /^size-adjust: \d+%;$/ );
	} );
}

test( 'reads the version from SO_TESTS_WP_VERSION when none is passed', () => {
	const before = process.env.SO_TESTS_WP_VERSION;

	try {
		process.env.SO_TESTS_WP_VERSION = '6.6';
		assert.strictEqual( previewTheme().preview, 'twentytwentyfour' );

		delete process.env.SO_TESTS_WP_VERSION;
		assert.strictEqual( previewTheme().preview, 'twentytwentyfive' );
	} finally {
		if ( before === undefined ) {
			delete process.env.SO_TESTS_WP_VERSION;
		} else {
			process.env.SO_TESTS_WP_VERSION = before;
		}
	}
} );
