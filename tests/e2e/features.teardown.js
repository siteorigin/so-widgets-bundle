const fs = require( 'fs' );

const { test: teardown } = require( '@playwright/test' );

const { setupRequestUtils } = require( 'siteorigin-tests-common/playwright/common' );

const { INSTALLED_THEMES_FILE, deleteTheme } = require( './helpers/features' );

/**
 * Removes the preview themes that features.setup.js installed, and only
 * those. The list is removed only once every theme on it is.
 */
teardown( 'remove the Features preview themes the setup installed', async () => {
	if ( ! fs.existsSync( INSTALLED_THEMES_FILE ) ) {
		return;
	}

	const installed = JSON.parse( fs.readFileSync( INSTALLED_THEMES_FILE, 'utf8' ) );

	if ( installed.length ) {
		const requestUtils = await setupRequestUtils();

		for ( const slug of installed ) {
			await deleteTheme( requestUtils, slug );
		}
	}

	fs.unlinkSync( INSTALLED_THEMES_FILE );
} );
