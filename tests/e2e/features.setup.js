const fs = require( 'fs' );
const path = require( 'path' );

const { test: setup } = require( '@playwright/test' );

const { setupRequestUtils } = require( 'siteorigin-tests-common/playwright/common' );

const { INSTALLED_THEMES_FILE, THEMES, ensureTheme } = require( './helpers/features' );

/**
 * Installs the preview themes the Features specs render in, once, before
 * Features spec, so parallel workers never race an install. It never fails:
 * a failed install is logged, the specs still run, and each themed test
 * fails on its own theme check. The themes it installs are added to
 * the list a failed teardown left, so the next teardown still removes them
 * and a persistent test site is left as found.
 */
setup( 'install the Features preview themes', async () => {
	const installed = fs.existsSync( INSTALLED_THEMES_FILE ) ? JSON.parse( fs.readFileSync( INSTALLED_THEMES_FILE, 'utf8' ) ) : [];

	try {
		const requestUtils = await setupRequestUtils();

		for ( const theme of THEMES.filter( ( t ) => t.preview ) ) {
			if ( await ensureTheme( requestUtils, theme.preview ) && ! installed.includes( theme.preview ) ) {
				installed.push( theme.preview );
			}
		}
	} catch ( error ) {
		console.warn( `Features preview themes not installed: ${ error.message }` );
	}

	fs.mkdirSync( path.dirname( INSTALLED_THEMES_FILE ), { recursive: true } );
	fs.writeFileSync( INSTALLED_THEMES_FILE, JSON.stringify( installed ) );
} );
