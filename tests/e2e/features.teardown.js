const { test: teardown } = require( '@playwright/test' );

const { setupRequestUtils } = require( 'siteorigin-tests-common/playwright/common' );

const { deleteTheme, readInstalledThemes, writeInstalledThemes } = require( './helpers/features' );

/**
 * Removes the preview themes that features.setup.js listed for this site,
 * and only those. A theme leaves the list once it is removed.
 */
teardown( 'remove the Features preview themes the setup installed', async () => {
	const site = process.env.WP_BASE_URL;
	const installed = readInstalledThemes();

	if ( ! installed[ site ] ) {
		return;
	}

	const requestUtils = await setupRequestUtils();

	for ( const slug of [ ...installed[ site ] ] ) {
		await deleteTheme( requestUtils, slug );

		installed[ site ] = installed[ site ].filter( ( s ) => s !== slug );

		if ( ! installed[ site ].length ) {
			delete installed[ site ];
		}

		writeInstalledThemes( installed );
	}
} );
