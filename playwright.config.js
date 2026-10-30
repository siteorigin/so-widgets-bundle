const path = require('path');
const { devices } = require( '@playwright/test' );
const config = require('siteorigin-tests-common/playwright/config');

/**
 * These specs share site state: the sowb-e2e-probe fixture plugin they install
 * and remove, its probe counter, site-wide widget activation and widget areas.
 * They run in their own project, one file at a time, and the CI runner
 * (tests/scripts/run-playwright.js) runs that project after the others, so
 * no ability spec overlaps another spec.
 */
const ABILITY_SPECS = /[\\/]wb-(?:abilities|abilities-mcp|widget-block-abilities|widget-block-abilities-mcp|widget-block-shortcodes|customizer-widget-blocks)\.test\.js$/;
const ABILITIES_PROJECT = 'abilities';

/**
 * The Features specs run in Chrome in a project of their own, and are the
 * specs tagged @firefox and @webkit, which the Firefox and WebKit projects
 * run. The runner runs each project as a pass of its own.
 */
const FEATURES_SPECS = /[\\/]wb-features-[^\\/]+\.test\.js$/;
const FEATURES_PROJECT = 'Chrome Features';

const [ browserProject ] = config.projects;

config.projects = [
	{
		...browserProject,
		testIgnore: [ ABILITY_SPECS, FEATURES_SPECS ],
	},
	{
		...browserProject,
		name: FEATURES_PROJECT,
		testMatch: FEATURES_SPECS,
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
	},
	{
		name: 'WebKit',
		use: devices[ 'Desktop Safari' ],
		grep: /@webkit/,
		testIgnore: ABILITY_SPECS,
	},
];

module.exports = config;
