const { test, expect } = require('@playwright/test');
const { AUTH_STATE, DECKS, PACKAGES, signIn, createDeck, openEditor, save } = require('./helpers');

test.beforeAll(async ({ browser }) => {
  await signIn(browser);
});

test.use({ storageState: AUTH_STATE });

test('a new retainer deck arrives with an estimate, a timeline and its own client link', async ({ page }) => {
  const id = await createDeck(page, { client: 'Harbour Rowing Club', title: 'Website and support' });

  // The summary strip is the deck's own arithmetic, worked out in the browser
  // as somebody types. If these are right, the estimate and the post-launch
  // list both loaded and both know which rows count.
  await expect(page.locator('.bw-summary__cell').first()).toContainText('232');
  await expect(page.locator('.bw-summary__cell').nth(1)).toContainText('64');
  await expect(page.locator('.bw-summary__cell').nth(3)).toContainText('8');

  // A deck is not usable without a link, and a link is minted on create — not
  // on publish, and never from the record id.
  await openEditor(page, id, 'Preview and share');
  const link = page.locator('.bw-copyfield input');
  await expect(link).toHaveValue(/\/deck\/[a-z0-9]{12}\/$/);
  await expect(link).not.toHaveValue(new RegExp(String(id)));
});

test('a blank deck starts genuinely empty', async ({ page }) => {
  const id = await createDeck(page, { client: 'Northgate Collective', title: 'Discovery brief', start: 'blank' });

  await openEditor(page, id);
  await expect(page.locator('.bw-summary__cell').first()).toContainText('0');
  await expect(page.locator('.bw-summary__cell').nth(3)).toContainText('0');
});

test('the estimate groups its rows by phase and subtotals each group', async ({ page }) => {
  const id = await createDeck(page, { client: 'Ridgeway Trust', title: 'Site rebuild' });
  await openEditor(page, id, 'Project estimate');

  const groups = page.locator('.bw-repeater .bw-table__group-title, .bw-repeater__group');
  await expect(groups.first()).toBeVisible();

  // Discovery is one 12-hour line item in the retainer set, so its group
  // header has to say 12 — a subtotal that ignored the group would say 232.
  await expect(page.getByText('12 hrs').first()).toBeVisible();
});

test('changing hours moves the total, and the change survives a save', async ({ page }) => {
  const id = await createDeck(page, { client: 'Calder Foods', title: 'Trade site' });
  await openEditor(page, id, 'Project estimate');

  const hours = page.locator('.bw-repeater__row input[type=number]').first();
  await hours.fill('20');
  await hours.blur();

  // 232 with Discovery at 12; 240 with it at 20.
  await expect(page.locator('.bw-summary__cell').first()).toContainText('240');

  await save(page);
  await page.reload();
  await expect(page.locator('.bw-summary__cell').first()).toContainText('240');
});

test('the timeline saves as a list of phases rather than as text', async ({ page }) => {
  const id = await createDeck(page, { client: 'Selby Group', title: 'Programme brief' });
  await openEditor(page, id, 'Timeline');

  await expect(page.locator('.bw-gantt__row')).toHaveCount(8);

  // Saving and reloading is the whole point of this one: a field kind the
  // store does not know about saves as the string "Array", and every phase is
  // gone the next time the screen opens. Nothing short of a round trip catches
  // it — so the timeline is changed, saved, and read back.
  const title = page.locator('.bw-gantt__title').first();
  await expect(title).toContainText('Discovery and research');

  const first = page.locator('.bw-gantt__row').first();
  await first.getByRole('button', { name: /hide from the client/i }).click();
  await expect(first.locator('.bw-gantt__bar')).toHaveClass(/is-hidden/);

  await save(page);
  await page.reload();
  await page.click('.bw-tab:has-text("Timeline")');
  await expect(page.locator('.bw-gantt__row')).toHaveCount(8);
  await expect(page.locator('.bw-gantt__title').first()).toContainText('Discovery and research');
  await expect(page.locator('.bw-gantt__row').first().locator('.bw-gantt__bar')).toHaveClass(/is-hidden/);
});

test('the deck recommends the smallest package that covers the work', async ({ page }) => {
  const id = await createDeck(page, { client: 'Pennine Legal', title: 'Firm website' });
  await openEditor(page, id, 'Support package');

  // 232 project hours plus 64 post-launch is 296, which Care (120) and Core
  // (240) cannot cover and Core Plus (360) can.
  const panel = page.locator('.bw-card').filter({ hasText: 'In calculation' });
  await expect(panel).toContainText('296');
  await expect(panel).toContainText('Core Plus');

  // The remaining capacity has to be the difference, not the package's hours.
  await expect(panel).toContainText('64');
});

test('the decks dashboard counts what is there and filters it', async ({ page }) => {
  await createDeck(page, { client: 'Marlow Studio', title: 'Brand site' });

  await page.goto(DECKS);
  await expect(page.locator('.bw-pagehead__h1')).toHaveText('Decks');
  await expect(page.locator('.bw-table tbody tr').first()).toBeVisible();

  const rows = await page.locator('.bw-table tbody tr').count();
  expect(rows).toBeGreaterThan(0);

  // A filter that matches nothing has to say so, rather than showing an empty
  // table that reads as a broken screen.
  await page.goto(`${DECKS}&q=nothingmatchesthis`);
  await expect(page.locator('.bw-empty__title')).toHaveText('No decks match those filters');
});

test('the support packages screen lists what a deck can recommend', async ({ page }) => {
  await page.goto(PACKAGES);
  await expect(page.locator('.bw-pagehead__h1')).toHaveText('Support packages');
  await expect(page.locator('.bw-table tbody tr')).toHaveCount(4);
  await expect(page.locator('.bw-table')).toContainText('Core Plus');
});

test('the record editors are reachable but are not in the menu', async ({ page }) => {
  const id = await createDeck(page, { client: 'Ashcombe Partners', title: 'Advisory site' });

  // Reachable: an editor removed from the menu the wrong way answers 403.
  await openEditor(page, id);
  await expect(page.locator('.bw-pagehead__h1')).toHaveText('Advisory site');

  // Not in the menu: the design's menu is six items, and "Edit deck" is not
  // one of them — an editor opened with no record has nothing to edit.
  await expect(page.locator('#adminmenu a[href*="page=blueworx-deck-editor"]')).toHaveCount(0);
  await expect(page.locator('#adminmenu a[href$="page=blueworx-labs-deck-builder-settings"]')).toHaveCount(1);
});
