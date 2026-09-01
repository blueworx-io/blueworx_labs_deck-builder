const { test, expect } = require('@playwright/test');
const path = require('node:path');

// The harness ships this admin account; CI passes the same pair in. Anything
// that reaches wp-admin needs them, so a run without them would sign in as
// nobody and assert nothing.
const USER = process.env.WP_ADMIN_USER || 'admin';
const PASS = process.env.WP_ADMIN_PASS || 'wptest-admin-pw';
const LOGIN_PATH = process.env.WP_LOGIN_PATH || '/wp-login.php';

// Anchored to this file, not process.cwd() — a bare relative path writes the
// wrong file the moment the suite is run from anywhere but the repo root.
const AUTH_STATE = path.join(__dirname, '.auth-state.json');

test.beforeAll(async ({ browser }) => {
  // Every test below loads AUTH_STATE, including — by default — the context
  // that goes and creates it. Overriding it back to "no state yet" breaks that
  // chicken-and-egg loop.
  const context = await browser.newContext({ storageState: undefined });
  const page = await context.newPage();

  await page.goto(LOGIN_PATH);
  await page.fill('#user_login', USER);
  await page.fill('#user_pass', PASS);
  await page.click('#wp-submit');
  await expect(page.locator('#wpadminbar')).toBeVisible();

  await context.storageState({ path: AUTH_STATE });
  await context.close();
});

test.use({ storageState: AUTH_STATE });

test('the Deck Builder screen loads and is built from the design system', async ({ page }) => {
  await page.goto('/wp-admin/admin.php?page=blueworx-labs-deck-builder');

  // The page header is the design system's, not a hand-written h1 — asserting
  // the class is what makes this a test of the shared UI rather than of any
  // heading that happens to say "Decks".
  const heading = page.locator('.bw-pagehead__h1');
  await expect(heading).toHaveText('Decks');

  await expect(page.locator('.bw-empty__title')).toHaveText('No decks yet');

  // The stylesheet has to actually arrive, or the screen renders unstyled while
  // every assertion above still passes.
  const styled = await heading.evaluate((el) => getComputedStyle(el).fontSize);
  expect(styled).not.toBe('');
  await expect(page.locator('link[href*="blueworx-admin-design.css"]')).toHaveCount(1);
});

test('the plugin is active, and its menu entry is in wp-admin', async ({ page }) => {
  await page.goto('/wp-admin/plugins.php');
  await expect(page.locator('tr[data-slug="blueworx-labs-deck-builder"].active')).toHaveCount(1);

  await expect(
    page.locator('#adminmenu a[href="admin.php?page=blueworx-labs-deck-builder"]').first()
  ).toBeVisible();
});
