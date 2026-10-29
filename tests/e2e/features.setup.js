const { test: setup } = require( '@playwright/test' );

const { setupRequestUtils } = require( 'siteorigin-tests-common/playwright/common' );

const { THEMES, ensureTheme, readInstalledThemes, writeInstalledThemes } = require( './helpers/features' );

/**
 * Installs the preview themes the Features specs render in, once, before
 * any Features spec, so parallel workers never race an install. It never
 * fails: a failed install is logged, the specs still run, and each themed
 * test fails on its own theme check.
 *
 * A theme is listed under this site before its install starts, so the
 * teardown removes it even when the install's outcome is unknown. Each run
 * starts this site's list afresh: an entry a failed teardown left cannot
 * prove the theme is still the one this setup installed, so it is dropped
 * with a warning and the theme is left in place. The teardown removes only
 * what is listed for the site it runs against.
 */
setup( 'install the Features preview themes', async () => {
	const site = process.env.WP_BASE_URL;
	const installed = readInstalledThemes();

	if ( installed[ site ] ) {
		console.warn( `Not removing ${ installed[ site ].join( ', ' ) } from ${ site }: a teardown did not finish, and the theme may no longer be ours.` );
		delete installed[ site ];
	}

	// Also replaces a list that could not be read.
	writeInstalledThemes( installed );

	try {
		const requestUtils = await setupRequestUtils();

		for ( const theme of THEMES.filter( ( t ) => t.preview ) ) {
			await ensureTheme( requestUtils, theme.preview, ( slug ) => {
				installed[ site ] = [ ...new Set( [ ...( installed[ site ] || [] ), slug ] ) ];
				writeInstalledThemes( installed );
			} );
		}
	} catch ( error ) {
		console.warn( `Features preview themes not installed: ${ error.message }` );
	}
} );
