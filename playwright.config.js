const path = require('path');
const { devices } = require( '@playwright/test' );
const config = require('siteorigin-tests-common/playwright/config');

/**
 * The ability specs share site state: the sowb-e2e-probe fixture plugin they
 * install and remove, its probe counter, and site-wide widget activation.
 * They run in their own project, one file at a time, and the CI runner
 * (tests/scripts/run-playwright.js) runs that project after the others, so
 * no ability spec overlaps another spec.
 */
const ABILITY_SPECS = /[\\/]wb-(?:abilities|abilities-mcp|widget-block-abilities|widget-block-abilities-mcp|widget-block-shortcodes)\.test\.js$/;
const ABILITIES_PROJECT = 'abilities';

/**
 * The Features specs render in a second theme through core's theme preview.
 * They run in projects of their own, which depend on a setup project that
 * installs that theme once, before any of them, and a teardown that removes
 * it when the setup installed it. Firefox and WebKit run only the specs
 * tagged @firefox or @webkit, which are Features specs.
 */
const FEATURES_SPECS = /[\\/]wb-features-[^\\/]+\.test\.js$/;
const FEATURES_PROJECT = 'Chrome Features';
const FEATURES_SETUP_PROJECT = 'features setup';
const FEATURES_TEARDOWN_PROJECT = 'features teardown';

const [ browserProject ] = config.projects;

config.projects = [
	{
		...browserProject,
		name: FEATURES_SETUP_PROJECT,
		testMatch: /[\\/]features\.setup\.js$/,
		teardown: FEATURES_TEARDOWN_PROJECT,
	},
	{
		...browserProject,
		name: FEATURES_TEARDOWN_PROJECT,
		testMatch: /[\\/]features\.teardown\.js$/,
	},
	{
		...browserProject,
		testIgnore: [ ABILITY_SPECS, FEATURES_SPECS ],
	},
	{
		...browserProject,
		name: FEATURES_PROJECT,
		testMatch: FEATURES_SPECS,
		dependencies: [ FEATURES_SETUP_PROJECT ],
	},
	{
		...browserProject,
		name: ABILITIES_PROJECT,
		testMatch: ABILITY_SPECS,
		workers: 1,
	},
	{
		name: 'Firefox',
		use: devices[ 'Desktop Firefox' ],
		grep: /@firefox/,
		testIgnore: ABILITY_SPECS,
		dependencies: [ FEATURES_SETUP_PROJECT ],
	},
	{
		name: 'WebKit',
		use: devices[ 'Desktop Safari' ],
		grep: /@webkit/,
		testIgnore: ABILITY_SPECS,
		dependencies: [ FEATURES_SETUP_PROJECT ],
	},
];

module.exports = config;
