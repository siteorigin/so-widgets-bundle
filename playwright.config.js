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
 * Their setup project installs that theme once, before any spec, and its
 * teardown removes it when the setup installed it.
 */
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
		testIgnore: ABILITY_SPECS,
		dependencies: [ FEATURES_SETUP_PROJECT ],
	},
	{
		...browserProject,
		name: ABILITIES_PROJECT,
		testMatch: ABILITY_SPECS,
		workers: 1,
	},
	// Firefox and Safari run only the specs tagged for them.
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
