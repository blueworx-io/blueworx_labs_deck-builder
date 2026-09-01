const { test, expect } = require('@playwright/test');
const { AUTH_STATE, DECKS, signIn } = require('./helpers');

test.beforeAll(async ({ browser }) => {
  await signIn(browser);
});

test.use({ storageState: AUTH_STATE });

test('the Deck Builder screen loads and is built from the design system', async ({ page }) => {
  await page.goto(DECKS);

  // The page header is the design system's, not a hand-written h1 — asserting
  // the class is what makes this a test of the shared UI rather than of any
  // heading that happens to say "Decks".
  const heading = page.locator('.bw-pagehead__h1');
  await expect(heading).toHaveText('Decks');

  // The stat tiles are the design system's too, and there are four of them.
  await expect(page.locator('.bw-stats .bw-stat')).toHaveCount(4);

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

test('the menu carries the six screens the design asks for, in order', async ({ page }) => {
  await page.goto(DECKS);

  const items = await page
    .locator('#adminmenu li.toplevel_page_blueworx-labs-deck-builder .wp-submenu li a')
    .allInnerTexts();

  expect(items.map((text) => text.trim())).toEqual([
    'Decks',
    'Create new deck',
    'Content library',
    'Case studies',
    'Support packages',
    'Settings',
  ]);
});
