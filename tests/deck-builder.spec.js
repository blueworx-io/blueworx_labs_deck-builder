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
  await expect(page.locator('.bw-summary__cell').first()).toContainText('242');
  await expect(page.locator('.bw-summary__cell').nth(1)).toContainText('100');
  // Both lists together, which is what the package recommendation covers.
  await expect(page.locator('.bw-summary__cell').nth(2)).toContainText('342');

  // A deck is not usable without a link, and a link is minted on create — not
  // on publish, and never from the record id.
  await openEditor(page, id, 'Preview and share');
  const link = page.locator('.bw-copyfield input');
  await expect(link).toHaveValue(/\/deck\/[a-z0-9]{12}\/$/);

  // The slug is not the record id. Checking the whole link for those digits
  // instead used to fail whenever a random slug happened to contain them.
  const slug = (await link.inputValue()).match(/\/deck\/([a-z0-9]{12})\//)[1];
  expect(slug).not.toBe(String(id));
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

test('a read-only dropdown draws one arrow too', async ({ page }) => {
  const id = await createDeck(page, { client: 'Girvan Marine', title: 'Charter site' });
  await openEditor(page, id, 'Project estimate');

  // WordPress puts its chevron back for the disabled state specifically, from a
  // rule that outweighs the one that took it away — and it arrives tiled rather
  // than once, because the shared input rule sets `background` as a shorthand
  // and so resets repeat to its default. Forty chevrons across the field.
  const phase = page.locator('.bw-repeater__row .bw-select__el').first();
  await expect(phase).toBeDisabled();
  await expect(phase).toHaveCSS('background-image', 'none');
  await expect(phase).toHaveCSS('background-repeat', 'no-repeat');
});

test('wp-admin does not square off the design system controls', async ({ page }) => {
  const id = await createDeck(page, { client: 'Ashcombe Press', title: 'Catalogue site' });
  await openEditor(page, id);

  // WordPress styles inputs by attribute — input[type="text"] and a dozen
  // siblings — which outweighs a single class and puts its own 2px corner and
  // grey border on ours. Every control the design system draws on a real input
  // is checked, not just the one that was noticed: this went wrong one class at
  // a time, and a test that named one would have kept doing so.
  await expect(page.locator('.bw-titleinput')).toHaveCSS('border-radius', '12px');
  await expect(page.locator('.bw-input').first()).toHaveCSS('border-radius', '8px');
  await expect(page.locator('.bw-textarea').first()).toHaveCSS('border-radius', '8px');
  await expect(page.locator('.bw-select__el').first()).toHaveCSS('border-radius', '8px');

  // WordPress's border is #949494 in every case, so the colour is the other
  // half of the same claim — a rounded box wearing WordPress's grey is still
  // wearing WordPress's rule.
  await expect(page.locator('.bw-input').first())
    .not.toHaveCSS('border-top-color', 'rgb(148, 148, 148)');
});

test('the estimate groups its rows by phase and subtotals each group', async ({ page }) => {
  const id = await createDeck(page, { client: 'Ridgeway Trust', title: 'Site rebuild' });
  await openEditor(page, id, 'Project estimate');

  const groups = page.locator('.bw-repeater .bw-table__group-title, .bw-repeater__group');
  await expect(groups.first()).toBeVisible();

  // Discovery is one 12-hour line item in the library, so its group header
  // has to say 12 — a subtotal that ignored the group would say 242.
  await expect(page.getByText('12 hrs').first()).toBeVisible();
});

test('changing hours moves the total, and the change survives a save', async ({ page }) => {
  const id = await createDeck(page, { client: 'Calder Foods', title: 'Trade site' });
  await openEditor(page, id, 'Project estimate');

  const hours = page.locator('.bw-repeater__row input[type=number]').first();
  await hours.fill('20');
  await hours.blur();

  // 242 with Discovery at 12; 250 with it at 20.
  await expect(page.locator('.bw-summary__cell').first()).toContainText('250');

  await save(page);
  await page.reload();
  await expect(page.locator('.bw-summary__cell').first()).toContainText('250');
});

test('the timeline follows the estimate rather than being typed', async ({ page }) => {
  const id = await createDeck(page, { client: 'Selby Group', title: 'Programme brief' });
  await openEditor(page, id, 'Timeline');

  // Two stretches, one on screen at a time. A project has an end and a
  // retainer does not, so they are separate plans rather than one long one.
  const tabs = page.locator('.bw-schedule .bw-step');
  await expect(tabs).toHaveCount(2);
  await expect(tabs.nth(0)).toHaveText('Development phase');
  await expect(tabs.nth(1)).toHaveText('Post-launch');

  // Eleven project phases, and the launch closing them out — it is what the
  // project delivers, so it ends the first stretch rather than opening the
  // second. Nothing on the screen edits any of it, which is the point.
  const rows = page.locator('.bw-schedule__row');
  await expect(rows).toHaveCount(12);
  await expect(rows.first()).toContainText('Discovery');
  await expect(rows.last()).toContainText('Launch');
  await expect(page.locator('.bw-schedule__bar--launch')).toHaveCount(1);
  await expect(page.locator('.bw-panels input, .bw-panels select, .bw-panels textarea')).toHaveCount(0);

  // Discovery is 12 hours, which at four hours a day is three days — inside
  // week one. Doubling it pushes the phase into a second week, and every
  // phase after it moves along with it.
  await expect(rows.first()).toContainText('Week 1');

  // The retainer counts its own weeks from one. Numbered on from the build it
  // would read as one job that never ends.
  await tabs.nth(1).click();
  await expect(page.locator('.bw-schedule__row')).toHaveCount(8);
  await expect(page.locator('.bw-schedule__row').first()).toContainText('Week 1');
  await expect(page.locator('.bw-schedule__bar--launch')).toHaveCount(0);

  await openEditor(page, id, 'Project estimate');
  const hours = page.locator('.bw-repeater__row input[type=number]').first();
  await hours.fill('40');
  await hours.blur();
  await save(page);

  await openEditor(page, id, 'Timeline');
  await expect(page.locator('.bw-schedule__row').first()).toContainText('Weeks 1–2');
});

test('the deck recommends the smallest package that covers the work', async ({ page }) => {
  const id = await createDeck(page, { client: 'Pennine Legal', title: 'Firm website' });
  await openEditor(page, id, 'Support package');

  // 242 project hours plus 100 post-launch is 342, which Care (120) and Core
  // (240) cannot cover and Core Plus (360) can.
  const panel = page.locator('.bw-card').filter({ hasText: 'In calculation' });
  await expect(panel).toContainText('342');
  await expect(panel).toContainText('Core Plus');

  // The remaining capacity has to be the difference, not the package's hours.
  await expect(panel).toContainText('18');
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

// The fourth tile used to count decks above the largest package — a number that
// was zero on a healthy site and told nobody anything. What a sales team wants
// off this screen is what the open decks are worth if they all land.
test('the dashboard totals what the recommended packages are worth', async ({ page }) => {
  await page.goto(DECKS);

  const tile = page.locator('.bw-stat').filter({ hasText: 'Potential earnings' });
  await expect(tile.locator('.bw-stat__foot')).toHaveText('Total monthly potential');

  const asNumber = async () => Number((await tile.locator('.bw-stat__value').innerText()).replace(/[^0-9]/g, ''));
  const before = await asNumber();
  await expect(tile.locator('.bw-stat__value')).toContainText('£');

  // A new deck's 342 hours land on Core Plus, which is £1,600 a month. The
  // figure is read either side of creating it rather than asserted outright:
  // every other test in this file leaves decks behind, so the only stable
  // claim is what one more deck adds.
  await createDeck(page, { client: 'Wray & Co', title: 'Practice site' });

  await page.goto(DECKS);
  expect(await asNumber()).toBe(before + 1600);
});

// A deck is not a page of the site: it has no excerpt, no comments, no
// categories and no parent, and its address is something to copy rather than
// something to retype.
test('a deck cannot have its address retyped, and carries no page settings', async ({ page }) => {
  const id = await createDeck(page, { client: 'Ledbury Mills', title: 'Trade site' });
  await openEditor(page, id, 'Publish');

  await expect(page.locator('#post_status')).toBeVisible();
  await expect(page.locator('#post_excerpt')).toHaveCount(0);
  await expect(page.locator('#comment_status')).toHaveCount(0);
  await expect(page.locator('.bw-card__title:text-is("Categories and tags")')).toHaveCount(0);
  await expect(page.locator('.bw-card__title:text-is("Parent and template")')).toHaveCount(0);

  const slug = page.locator('#post_name');
  await expect(slug).toHaveJSProperty('readOnly', true);
  await expect(slug).toHaveValue(/^https?:\/\/.+/);
  await expect(page.locator('.bw-copyfield').getByRole('button', { name: 'Copy' })).toBeVisible();
});

// Every cell on its own line, at the width of the row. They used to share a
// wrapping flex line, which put eight controls across the screen at whatever
// width was left over.
test('a section row stacks its fields one per line, each the full width', async ({ page }) => {
  const id = await createDeck(page, { client: 'Calder Foods', title: 'Brand site' });
  await openEditor(page, id, 'Sections');

  const row = page.locator('.bw-repeater__row').first();
  const name = await row.locator('.bw-field').nth(0).boundingBox();
  const type = await row.locator('.bw-field').nth(1).boundingBox();

  expect(name.x).toBeCloseTo(type.x, 0);
  expect(type.y).toBeGreaterThan(name.y + name.height - 1);

  const fields = await row.locator('.bw-repeater__fields').boundingBox();
  expect(name.width).toBeCloseTo(fields.width, 0);

  // The panel's heading and description sit in their own card, and each row is
  // its own card rather than a list nested inside one more.
  const intro = page.locator('.bw-card:has(.bw-card__title:text-is("Sections"))');
  await expect(intro).toHaveClass(/bw-card--intro/);
  await expect(intro.locator('.bw-repeater')).toHaveCount(0);
  await expect(page.locator('.bw-panel__loose .bw-repeater')).toHaveCount(1);
});
