/**
 * The Playwright passes, in the order they run. The ability specs share
 * site state, so they run last in their own one-worker project (see
 * playwright.config.js). The Features specs run in Chrome, Firefox and WebKit.
 */
const PASSES = [ 'Google Chrome', 'Chrome Features', 'Firefox', 'WebKit', 'abilities' ];

// The passes that run on a WordPress version older than the Abilities API.
const CHROME_PASSES = [ 'Google Chrome', 'Chrome Features' ];

// The Abilities API first shipped in WordPress 6.9.
const ABILITIES_MAJOR = 6;
const ABILITIES_MINOR = 9;

/**
 * Chooses the Playwright passes for a WordPress version.
 *
 * @param {string} [version=''] A WordPress version such as 6.6 or 6.6.2, or
 *                              empty for the latest.
 *
 * @return {Object} `{ run, skipped }`: the project names to run, in order,
 *                  and the project names left out.
 */
const selectPasses = ( version = '' ) => {
	if ( version === '' ) {
		return { run: [ ...PASSES ], skipped: [] };
	}

	const [ major, minor ] = version.split( '.' ).map( Number );
	const hasAbilities = major > ABILITIES_MAJOR ||
		( major === ABILITIES_MAJOR && minor >= ABILITIES_MINOR );

	if ( hasAbilities ) {
		return { run: [ ...PASSES ], skipped: [] };
	}

	return {
		run: [ ...CHROME_PASSES ],
		skipped: PASSES.filter( ( pass ) => ! CHROME_PASSES.includes( pass ) ),
	};
};

module.exports = selectPasses;
