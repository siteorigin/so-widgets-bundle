const test = require( 'node:test' );
const assert = require( 'node:assert' );

const selectPasses = require( './playwright-passes' );

const ALL = [ 'Google Chrome', 'Chrome Features', 'Firefox', 'WebKit', 'abilities' ];
const CHROME = [ 'Google Chrome', 'Chrome Features' ];

test( 'runs every pass, in order, when no version is named', () => {
	assert.deepStrictEqual( selectPasses( '' ), { run: ALL, skipped: [] } );
	assert.deepStrictEqual( selectPasses(), { run: ALL, skipped: [] } );
} );

for ( const version of [ '6.6', '6.6.11', '6.8' ] ) {
	test( `runs the Chrome passes only on WordPress ${ version }`, () => {
		assert.deepStrictEqual( selectPasses( version ), {
			run: CHROME,
			skipped: [ 'Firefox', 'WebKit', 'abilities' ],
		} );
	} );
}

for ( const version of [ '6.9', '6.9.1', '6.10', '7.0', '7.1' ] ) {
	test( `runs every pass on WordPress ${ version }`, () => {
		assert.deepStrictEqual( selectPasses( version ), { run: ALL, skipped: [] } );
	} );
}
