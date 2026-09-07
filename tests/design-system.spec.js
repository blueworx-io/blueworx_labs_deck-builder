const { test, expect } = require('@playwright/test');
const { AUTH_STATE, signIn } = require('./helpers');

test.beforeAll(async ({ browser }) => {
  await signIn(browser);
});

test.use({ storageState: AUTH_STATE });

// The deck editor's line items stack one field per line. They only do that if
// the screen is wearing this plugin's own design system rather than another
// BlueWorx plugin's older copy — which is exactly what used to happen, with
// nothing on the page to say so.
test('the deck editor loads this plugin\'s design system', async ({ page }) => {
  await page.goto('/wp-admin/admin.php?page=blueworx-labs-deck-builder');

  const editorLink = page.locator('a[href*="page=blueworx-deck-editor"]').first();
  await expect(editorLink).toBeVisible();
  await editorLink.click();

  const href = await page.locator('link#blueworx-admin-design-css').getAttribute('href');
  expect(href).toContain('/plugins/blueworx-labs-deck-builder/assets/blueworx-admin-design.css');

  // The repeater only renders rows on the Sections tab, which is seeded from
  // the content library — the Overview tab that opens first has none.
  await page.click('.bw-tab:has-text("Sections")');

  const direction = await page
    .locator('.bw-repeater__fields')
    .first()
    .evaluate((el) => getComputedStyle(el).flexDirection);
  expect(direction).toBe('column');
});
