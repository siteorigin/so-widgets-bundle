const path = require( 'path' );
const fs = require( 'fs' );

const execAsync = require( 'siteorigin-tests-common/utilities/execAsync' );
const startPlayground = require( 'siteorigin-tests-common/playground/startPlayground' );
const { maybeMakeBuild } = require( 'siteorigin-tests-common/utilities/builds' );
const wordPressVersion = require( 'siteorigin-tests-common/playground/wordPressVersion' );
const selectPasses = require( './playwright-passes' );

const run = async () => {
	const isWindows = process.platform === 'win32';

	if ( isWindows && ! process.env.PATH.includes( 'C:\\WINDOWS\\system32' ) ) {
		process.env.PATH = `${ process.env.PATH };C:\\WINDOWS\\system32`;
	}

	// Refuse a value that isn't a version before anything is built or started.
	const version = wordPressVersion();

	const envPath = path.resolve( process.cwd(), 'tests', 'so-tests.env' );
	if ( version && fs.existsSync( envPath ) ) {
		throw new Error(
			`SO_TESTS_WP_VERSION is set to ${ version }, but tests/so-tests.env points the tests at an existing site. The version only applies to the Playground: unset it, or remove tests/so-tests.env.`
		);
	}

	if ( ! fs.existsSync( envPath ) ) {
		const buildSuccessful = await maybeMakeBuild();
		await startPlayground( buildSuccessful );
	}

	// Every chosen pass always runs; any failing fails the run.
	const { run: projects, skipped } = selectPasses( version );
	const passes = projects.map( ( project ) => [
		project.includes( ' ' ) ? `--project="${ project }"` : `--project=${ project }`,
	] );

	if ( skipped.length ) {
		console.log(
			`WordPress ${ version }: skipping the ${ skipped.join( ', ' ) } passes. Below WordPress 6.9 only the Chrome passes run; the Abilities API starts at 6.9.`
		);
	}

	let failed = false;

	for ( const args of passes ) {
		try {
			await execAsync( 'npx', [ 'npm', 'run', 'test:e2e', '--', ...args ] );
		} catch ( error ) {
			console.error( `Playwright pass ${ args.join( ' ' ) } failed:`, error.message );
			failed = true;
		}
	}

	process.exit( failed ? 1 : 0 );
};

run().catch( ( error ) => {
	console.error( 'Error running tests:', error.message );
	process.exit( 1 );
} );
