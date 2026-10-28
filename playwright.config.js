const path = require('path');
const config = require('siteorigin-tests-common/playwright/config');

/**
 * These specs share site state: widget areas, the sowb-e2e-probe fixture
 * plugin and its probe counter, and site-wide widget activation.
 * They run in their own project, one file at a time, and the CI runner
 * (tests/scripts/run-playwright.js) runs that project after the others, so
 * no ability spec overlaps another spec.
 */
const ABILITY_SPECS = /[\\/]wb-(?:abilities|abilities-mcp|widget-block-abilities|widget-block-abilities-mcp|widget-block-shortcodes|customizer-widget-blocks)\.test\.js$/;
const ABILITIES_PROJECT = 'abilities';

const [ browserProject ] = config.projects;

config.projects = [
	{
		...browserProject,
		testIgnore: ABILITY_SPECS,
	},
	{
		...browserProject,
		name: ABILITIES_PROJECT,
		testMatch: ABILITY_SPECS,
		workers: 1,
	},
];

module.exports = config;
