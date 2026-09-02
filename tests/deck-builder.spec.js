const { test, expect } = require('@playwright/test');
const { AUTH_STATE, DECKS, PACKAGES, signIn, createDeck, openEditor, save } = require('./helpers');

test.beforeAll(async ({ browser }) => {
  await signIn(browser);
});

test.use({ storageState: AUTH_STATE });

test('a new deck arrives with an estimate, a timeline and its own client link', async ({ page }) => {
  const id = await createDeck(page, { client: 'Harbour Rowing Club', title: 'Website and support' });

  // The summary strip is the deck's own arithmetic, worked out in the browser
  // as somebody types. If these are right, the estimate and the post-launch
  // list both loaded and both know which rows count.
  await expect(page.locator('.bw-summary__cell').first()).toContainText('272');
  await expect(page.locator('.bw-summary__cell').nth(1)).toContainText('64');
  await expect(page.locator('.bw-summary__cell').nth(3)).toContainText('8');

  // A deck is not usable without a link, and a link is minted on create — not
  // on publish, and never from the record id.
  await openEditor(page, id, 'Preview and share');
  const link = page.locator('.bw-copyfield input');
  await expect(link).toHaveValue(/\/deck\/[a-z0-9]{12}\/$/);
  await expect(link).not.toHaveValue(new RegExp(String(id)));
});

test('there is no starting point left to choose', async ({ page }) => {
  await page.goto(DECKS);

  // One button, and it makes the deck rather than opening a form. The old
  // create screen is gone, and so is the column and the filter that asked
  // which of two starting points a deck had used.
  await expect(page.locator('.bw-pagehead__actions a.bw-btn--primary')).toHaveText(/Create new deck/);
  await expect(page.locator('select[name="start"]')).toHaveCount(0);
  await expect(page.locator('.bw-table thead')).not.toContainText('Starting point');

  await page.goto('/wp-admin/admin.php?page=blueworx-labs-deck-builder-create');
  await expect(page.locator('.bw-radiogroup')).toHaveCount(0);
});

test('the actions column is headed, and the table scrolls rather than overflowing', async ({ page }) => {
  await createDeck(page, { client: 'Northgate Collective', title: 'Discovery brief' });
  await page.goto(DECKS);

  // The header shares a class with the row actions, which fade in on hover.
  // Left as it was, the column had no readable heading at all.
  const header = page.locator('.bw-table thead th.bw-table__actions');
  await expect(header).toHaveText('Actions');
  await expect(header).toHaveCSS('opacity', '1');

  // Narrow enough that the actions would otherwise sit outside the card.
  await page.setViewportSize({ width: 900, height: 800 });
  const scroller = page.locator('.bw-tablescroll');
  await expect(scroller).toHaveCSS('overflow-x', 'auto');

  const overflow = await scroller.evaluate((el) => ({
    inside: el.scrollWidth > el.clientWidth ? el.scrollLeft >= 0 : true,
    bodyScrolls: document.documentElement.scrollWidth > document.documentElement.clientWidth,
  }));
  expect(overflow.inside).toBe(true);
  expect(overflow.bodyScrolls).toBe(false);
});

test('a dropdown draws one arrow, not two', async ({ page }) => {
  await page.goto(DECKS);

  // WordPress puts its own chevron on every select in wp-admin as a
  // background image, beside the design system's own. It also caps a select
  // at 25rem, which is what pushed the second arrow clear of the field.
  const select = page.locator('.bw-select .bw-select__el').first();
  await expect(select).toHaveCSS('background-image', 'none');

  const fits = await select.evaluate((el) => {
    const wrap = el.closest('.bw-select');
    return Math.abs(el.getBoundingClientRect().width - wrap.getBoundingClientRect().width) < 1;
  });
  expect(fits).toBe(true);
});

test('the estimate groups its rows by phase and subtotals each group', async ({ page }) => {
  const id = await createDeck(page, { client: 'Ridgeway Trust', title: 'Site rebuild' });
  await openEditor(page, id, 'Project estimate');

  const groups = page.locator('.bw-repeater .bw-table__group-title, .bw-repeater__group');
  await expect(groups.first()).toBeVisible();

  // Discovery is one 12-hour line item in the library, so its group header
  // has to say 12 — a subtotal that ignored the group would say 272.
  await expect(page.getByText('12 hrs').first()).toBeVisible();
});

test('changing hours moves the total, and the change survives a save', async ({ page }) => {
  const id = await createDeck(page, { client: 'Calder Foods', title: 'Trade site' });
  await openEditor(page, id, 'Project estimate');

  const hours = page.locator('.bw-repeater__row input[type=number]').first();
  await hours.fill('20');
  await hours.blur();

  // 272 with Discovery at 12; 280 with it at 20.
  await expect(page.locator('.bw-summary__cell').first()).toContainText('280');

  await save(page);
  await page.reload();
  await expect(page.locator('.bw-summary__cell').first()).toContainText('280');
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

  // 272 project hours plus 64 post-launch is 336, which Care (120) and Core
  // (240) cannot cover and Core Plus (360) can.
  const panel = page.locator('.bw-card').filter({ hasText: 'In calculation' });
  await expect(panel).toContainText('336');
  await expect(panel).toContainText('Core Plus');

  // The remaining capacity has to be the difference, not the package's hours.
  await expect(panel).toContainText('24');
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
