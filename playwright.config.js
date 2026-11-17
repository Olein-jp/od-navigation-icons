const { defineConfig } = require( '@playwright/test' );
module.exports = defineConfig( {
	testDir: './tests/e2e',
	workers: 1,
	timeout: 60000,
	use: {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8890',
        channel: process.env.ODPI_BROWSER || 'chrome',
		trace: 'retain-on-failure',
	},
} );
