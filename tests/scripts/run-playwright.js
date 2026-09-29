const path = require( 'path' );
const fs = require( 'fs' );

const execAsync = require( 'siteorigin-tests-common/utilities/execAsync' );
const startPlayground = require( 'siteorigin-tests-common/playground/startPlayground' );
const { maybeMakeBuild } = require( 'siteorigin-tests-common/utilities/builds' );

const run = async () => {
	const isWindows = process.platform === 'win32';

	if ( isWindows && ! process.env.PATH.includes( 'C:\\WINDOWS\\system32' ) ) {
		process.env.PATH = `${ process.env.PATH };C:\\WINDOWS\\system32`;
	}

	const envPath = path.resolve( process.cwd(), 'tests', 'so-tests.env' );
	if ( ! fs.existsSync( envPath ) ) {
		const buildSuccessful = await maybeMakeBuild();
		await startPlayground( buildSuccessful );
	}

	// The ability specs share site state, so they run as a second pass in
	// their own one-worker project (see playwright.config.js), after the
	// other specs. Both passes always run; either failing fails the run.
	const passes = [
		[ '--project="Google Chrome"' ],
		[ '--project=abilities' ],
	];
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
