// @ts-check
const { defineConfig, devices } = require('@playwright/test');

// The suite runs against a real, disposable WordPress that the run provisions
// itself — locally through the foundation's wp-test-env.mjs, in CI through the
// shared workflow's `use_local_wordpress: true`. Never against a hosted staging
// site: a dead staging host is how a suite silently skips itself green.
const baseURL = process.env.PLAYWRIGHT_BASE_URL || process.env.BASE_URL || 'http://127.0.0.1:8881';

module.exports = defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 2 : 0,

  // Keep the json reporter. CI reads it to prove tests actually executed.
  reporter: process.env.CI ? [['list'], ['json', { outputFile: 'playwright-report.json' }]] : 'list',

  // One worker, always. The specs sign in to one site and mutate state that is
  // site-wide, so a second worker makes one spec's "off" another spec's "on".
  workers: 1,

  // A WordPress admin screen is a couple of hundred requests answered by a
  // single-threaded PHP server, so the default 30s is tight for no good reason.
  timeout: 90_000,

  use: {
    baseURL,
    trace: 'on-first-retry',
  },

  projects: [
    {
      name: 'wordpress',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
