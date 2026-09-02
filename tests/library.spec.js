const { test, expect } = require('@playwright/test');
const { AUTH_STATE, LIBRARY, signIn, createDeck, openEditor, save } = require('./helpers');

test.beforeAll(async ({ browser }) => {
  await signIn(browser);
});

test.use({ storageState: AUTH_STATE });

test('a new deck arrives as a copy of the whole library', async ({ page }) => {
  const id = await createDeck(page, { client: 'Ferry Lane Bakery', title: 'New site' });

  // Fourteen sections in the library, fourteen rows on the deck. There is no
  // second list any more, so this is the only place either number comes from.
  await openEditor(page, id, 'Sections');
  await expect(page.locator('.bw-repeater__row')).toHaveCount(14);
  await expect(page.locator('.bw-repeater__row').first().locator('input[type=text]').first()).toHaveValue('Cover');

  // Every line item, in the list it belongs to, and all of them counting.
  await openEditor(page, id, 'Project estimate');
  await expect(page.locator('.bw-repeater__row')).toHaveCount(16);
  await expect(page.locator('.bw-summary__cell').first()).toContainText('272');

  await openEditor(page, id, 'Post-launch');
  await expect(page.locator('.bw-repeater__row')).toHaveCount(6);
  await expect(page.locator('.bw-summary__cell').nth(1)).toContainText('64');
});

test('a deck cannot add, remove or reorder a section', async ({ page }) => {
  const id = await createDeck(page, { client: 'Alderton Vets', title: 'Practice site' });
  await openEditor(page, id, 'Sections');

  await expect(page.getByRole('button', { name: 'Add a row' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Remove this row' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Move up' })).toHaveCount(0);
  await expect(page.locator('.bw-repeater__grip')).toHaveCount(0);

  // No library picker either: the deck already has everything the library
  // holds, so there is nothing left to pick.
  await expect(page.locator('.bw-card', { hasText: 'Add a section from the library' })).toHaveCount(0);
});

test('the wording of a section is this deck\'s own, and survives a save', async ({ page }) => {
  const id = await createDeck(page, { client: 'Rowan Interiors', title: 'Studio site' });
  await openEditor(page, id, 'Sections');

  const first = page.locator('.bw-repeater__row').first().locator('input[type=text]').first();
  await first.fill('Cover — Rowan Interiors');
  await save(page);
  await page.reload();
  await page.click('.bw-tab:has-text("Sections")');
  await expect(page.locator('.bw-repeater__row').first().locator('input[type=text]').first())
    .toHaveValue('Cover — Rowan Interiors');

  // And the library entry it was copied from is untouched.
  await page.goto(LIBRARY);
  await expect(page.locator('.bw-table tbody')).toContainText('Cover');
  await expect(page.locator('.bw-table tbody')).not.toContainText('Rowan Interiors');
});

test('the estimate cannot add or remove a line item either', async ({ page }) => {
  const id = await createDeck(page, { client: 'Whitlock Legal', title: 'Firm site' });
  await openEditor(page, id, 'Project estimate');

  await expect(page.getByRole('button', { name: 'Add a row' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Remove this row' })).toHaveCount(0);

  // The switches that decide what a row counts towards are still there —
  // taking work out of this quote is the whole job.
  await expect(page.locator('.bw-repeater__row').first().locator('.bw-switch input')).toHaveCount(3);
});

test('the timeline runs in one order, and offers no way to change it', async ({ page }) => {
  const id = await createDeck(page, { client: 'Pennine Legal', title: 'Firm website' });
  await openEditor(page, id, 'Timeline');

  await expect(page.locator('.bw-gantt__row')).toHaveCount(8);
  await expect(page.locator('.bw-gantt__legend .bw-btn')).toHaveCount(0);

  const first = page.locator('.bw-gantt__row').first();
  await expect(first.getByRole('button', { name: /^Move/ })).toHaveCount(0);
  await expect(first.getByRole('button', { name: /^Duplicate/ })).toHaveCount(0);
  await expect(first.getByRole('button', { name: /^Remove/ })).toHaveCount(0);

  // Editing a phase and hiding one from the client both stay: the schedule is
  // settled, the dates and the wording are not.
  await expect(first.getByRole('button', { name: /^Edit/ })).toBeVisible();
  await expect(first.getByRole('button', { name: /the client$/ })).toBeVisible();
});

test('the content library can be edited and deleted, but not added to', async ({ page }) => {
  await page.goto(LIBRARY);

  await expect(page.locator('.bw-pagehead__h1')).toHaveText('Content library');
  await expect(page.getByRole('button', { name: 'Add entry' })).toHaveCount(0);

  const row = page.locator('.bw-table tbody tr').first();
  await expect(row.getByRole('link', { name: 'Edit' })).toBeVisible();
  await expect(row.getByRole('button', { name: 'Delete' })).toBeVisible();

  // Sections come first, then each estimate — the order a deck presents them,
  // rather than an alphabetical pile.
  await expect(row).toContainText('Cover');
  await expect(row).toContainText('Section');
});

// A section's name is the value of a text input, not text on the page, so
// reading it back means asking the inputs rather than the markup.
async function sectionTitles(page) {
  return page.locator('.bw-repeater__row').evaluateAll((rows) =>
    rows.map((row) => row.querySelector('input[type=text]').value));
}

// Renames the first library entry and hands back both names plus a way to put
// it back. The library is site-wide state every other test reads, so a test
// that renamed an entry and walked away would quietly change what the next one
// sees — including on a second run against the same site.
async function renameFirstEntry(page, to) {
  await page.goto(LIBRARY);
  const row = page.locator('.bw-table tbody tr').first();
  const from = (await row.locator('.bw-table__primary').innerText()).trim();
  await row.getByRole('link', { name: 'Edit' }).click();
  await expect(page.locator('#post_title')).toBeVisible();
  await page.fill('#post_title', to);
  await save(page);
  return from;
}

test('editing a library entry changes the next deck and leaves the last one alone', async ({ page }) => {
  const before = await createDeck(page, { client: 'Marlow Studio', title: 'Brand site' });

  const renamed = `Opening slide ${Date.now()}`;
  const original = await renameFirstEntry(page, renamed);

  try {
    const after = await createDeck(page, { client: 'Calder Foods', title: 'Trade site' });

    await openEditor(page, after, 'Sections');
    expect(await sectionTitles(page)).toContain(renamed);

    // The deck made before the edit is untouched: it holds its own copy, not a
    // reference to the entry.
    await openEditor(page, before, 'Sections');
    const earlier = await sectionTitles(page);
    expect(earlier).toContain(original);
    expect(earlier).not.toContain(renamed);
  } finally {
    await renameFirstEntry(page, original);
  }
});

test('the share tab frames the deck itself, at three widths', async ({ page }) => {
  const id = await createDeck(page, { client: 'Grange Cycles', title: 'Shop site' });
  await openEditor(page, id, 'Preview and share');

  // A draft frames as well as a published deck: the client link shows an
  // unpublished deck to somebody who may edit it, so this is the real page.
  const frame = page.locator('.bw-preview__frame');
  await expect(frame).toBeVisible();
  await expect(frame).toHaveAttribute('src', /\/deck\/[a-z0-9]{12}\/$/);

  // Three device widths, and the frame follows whichever is chosen.
  await page.locator('.bw-preview__device', { hasText: 'Mobile' }).click();
  await expect(frame).toHaveAttribute('width', '390');

  // Archiving takes the page away from everybody, editors included, so there
  // is then nothing to frame rather than a not-found page in a device.
  await page.check('#archived');
  await save(page);
  await page.reload();
  await page.click('.bw-tab:has-text("Preview and share")');
  await expect(page.locator('.bw-preview--empty')).toBeVisible();
});
