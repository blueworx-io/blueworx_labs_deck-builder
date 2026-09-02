const { test, expect } = require('@playwright/test');
const { AUTH_STATE, signIn, createDeck, openEditor, save } = require('./helpers');

test.beforeAll(async ({ browser }) => {
  await signIn(browser);
});

test.use({ storageState: AUTH_STATE });

// Publishing is a row action on the dashboard, and it is also what freezes the
// package price onto the deck — so every test that needs a live link goes
// through it rather than writing post_status directly.
async function publish(page, client) {
  await page.goto('/wp-admin/admin.php?page=blueworx-labs-deck-builder');
  // Decks outlive a run locally, so the same client can appear more than
  // once. The list is newest-changed first and this deck was just made, so
  // the first match is always this test's own.
  const row = page.locator('.bw-table tbody tr').filter({ hasText: client }).first();
  await row.getByRole('button', { name: 'Publish' }).click();
  await expect(page.locator('.bw-notice--success')).toBeVisible();
}

async function linkFor(page, id) {
  await openEditor(page, id, 'Preview and share');
  return page.locator('.bw-copyfield input').inputValue();
}

test('a draft deck is not on the web, and a published one is', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Fenwick Homes', title: 'Sales site' });
  const link = await linkFor(page, id);

  // A client has no WordPress session, so the check has to be made in one.
  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();

  const draft = await guestPage.goto(link);
  expect(draft.status()).toBe(404);

  await publish(page, 'Fenwick Homes');

  const live = await guestPage.goto(link);
  expect(live.status()).toBe(200);
  await expect(guestPage.locator('h1.bwd-display')).toContainText('Sales site');

  await guest.close();
});

test('the client deck is its own document, with no theme and no other plugin in it', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Trent Valley Wines', title: 'Cellar door' });
  await publish(page, 'Trent Valley Wines');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  // Exactly one stylesheet, and it is this plugin's own. A theme stylesheet or
  // another plugin's would both show up here, and either could change what a
  // client sees.
  const sheets = await guestPage.locator('link[rel=stylesheet]').evaluateAll((els) => els.map((el) => el.getAttribute('href')));
  expect(sheets).toHaveLength(1);
  expect(sheets[0]).toContain('/assets/deck.css');

  // wp_head() was never called, so none of WordPress's own front-end furniture
  // is here either.
  await expect(guestPage.locator('#wpadminbar')).toHaveCount(0);
  await expect(guestPage.locator('link[href*="wp-includes"]')).toHaveCount(0);

  await expect(guestPage.locator('meta[name=robots]')).toHaveAttribute('content', /noindex/);

  await guest.close();
});

test('the client never receives an internal note or a hidden line item', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Ellery Dental', title: 'Practice site' });

  await openEditor(page, id, 'Project estimate');
  const row = page.locator('.bw-repeater__row').first();
  await row.locator('input[type=text]').nth(2).fill('Contingency we have not told them about');
  // A toggle is a checkbox; the row's three are in total, shown to client, in package.
  await row.locator('input[type=checkbox]').nth(1).uncheck();
  await save(page);

  await publish(page, 'Ellery Dental');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  // The whole document, not the rendered text: a note hidden with CSS would
  // still be sitting in the HTML, and this is the assertion that says it never
  // reached the browser at all.
  const html = await guestPage.content();
  expect(html).not.toContain('Contingency we have not told them about');
  expect(html).not.toContain('Discovery workshop');

  await guest.close();
});

test('turning the link off takes the deck off the web', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Barrow Cycles', title: 'Shop site' });
  await publish(page, 'Barrow Cycles');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  expect((await guestPage.goto(link)).status()).toBe(200);

  await openEditor(page, id, 'Preview and share');
  await page.locator('#link_enabled').uncheck();
  await save(page);

  expect((await guestPage.goto(link)).status()).toBe(404);

  await guest.close();
});

test('a password-protected deck asks before it shows anything', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Halvard Marine', title: 'Fleet site' });
  await publish(page, 'Halvard Marine');

  await openEditor(page, id, 'Preview and share');
  await page.locator('#password_on').check();
  await page.locator('#password').fill('harbour-lights');
  await save(page);

  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  await expect(guestPage.locator('.bwd-gate')).toBeVisible();
  const gated = await guestPage.content();
  expect(gated).not.toContain('Fleet site — deck content');

  await guestPage.fill('#bwd-password', 'harbour-lights');
  await guestPage.click('.bwd-gate__btn');
  await expect(guestPage.locator('h1.bwd-display')).toContainText('Fleet site');

  await guest.close();
});

test('the deck reads as a presentation on a desktop and as a document on a phone', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Kestrel Aviation', title: 'Charter site' });
  await publish(page, 'Kestrel Aviation');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined, viewport: { width: 1440, height: 900 } });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  // One slide at a time, and the arrow keys move between them.
  await expect(guestPage.locator('.bwd-slide.is-current')).toHaveCount(1);
  const first = await guestPage.locator('.bwd-slide.is-current').getAttribute('aria-label');
  await guestPage.keyboard.press('ArrowRight');
  await expect(guestPage.locator('.bwd-slide.is-current')).not.toHaveAttribute('aria-label', first);

  // Below 900px every section is on the page at once — a whole slide shrunk
  // onto a phone would be unreadable, and this is the assertion that says it
  // is not what happens.
  await guestPage.setViewportSize({ width: 390, height: 844 });
  await guestPage.reload();
  const shown = await guestPage.locator('.bwd-slide.is-current').count();
  expect(shown).toBeGreaterThan(1);
  await expect(guestPage.locator('.bwd-nav')).toBeHidden();

  await guest.close();
});
