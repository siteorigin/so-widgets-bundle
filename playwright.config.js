const path = require('path');
process.env.SO_TESTS_ENV_PATH = path.join(__dirname, 'tests', 'so-tests.env');
const config = require('siteorigin-tests-common/playwright/config');

module.exports = config;
