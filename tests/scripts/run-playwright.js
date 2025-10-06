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

    await execAsync( 'npx', [ 'npm', 'run', 'test:e2e' ] );

    process.exit( 0 );
};

run().catch( ( error ) => {
    console.error( 'Error running tests:', error.message );
    process.exit( 1 );
} );
