const { test, expect } = require('@playwright/test');
const { AUTH_STATE, signIn, createDeck, openEditor, save } = require('./helpers');

const LIBRARY = '/wp-admin/admin.php?page=blueworx-labs-deck-builder-library';

test.beforeAll(async ({ browser }) => {
  await signIn(browser);
});

test.use({ storageState: AUTH_STATE });

test('a section picked from the library arrives as a new row, and the tick clears', async ({ page }) => {
  const id = await createDeck(page, { client: 'Ferry Lane Bakery', title: 'New site' });
  await openEditor(page, id, 'Sections');

  const rows = page.locator('.bw-repeater__row');
  const before = await rows.count();

  await page.locator('.bw-check', { hasText: 'Standard introduction' }).locator('input').check();
  await save(page);

  // The row is in the list, and the entry it came from is untouched — the
  // deck holds a copy, not a reference.
  await expect(rows).toHaveCount(before + 1);
  await expect(rows.last().locator('input[type=text]').first()).toHaveValue('Standard introduction');

  // The tick is an instruction, not a setting: once carried out it goes back
  // to nothing, so the next save does not add the section a second time.
  await expect(page.locator('.bw-check', { hasText: 'Standard introduction' }).locator('input')).not.toBeChecked();

  await page.reload();
  await expect(page.locator('.bw-tab:has-text("Sections")')).toBeVisible();
});

test('a line item picked from the library counts towards the totals straight away', async ({ page }) => {
  const id = await createDeck(page, { client: 'Alderton Vets', title: 'Practice site' });
  await openEditor(page, id, 'Project estimate');

  // The retainer set is 232 hours before anything is added.
  await expect(page.locator('.bw-summary__cell').first()).toContainText('232');

  await page.locator('.bw-check', { hasText: 'Accessibility pass' }).locator('input').check();
  await save(page);

  // Accessibility pass is 12 hours, and a row arriving from the library counts
  // in every total rather than landing switched off.
  await expect(page.locator('.bw-summary__cell').first()).toContainText('244');
  await expect(page.locator('.bw-summary__cell').nth(2)).toContainText('308');
});

test('the in-package figure is one number covering both lists', async ({ page }) => {
  await createDeck(page, { client: 'Selby Print', title: 'Brochure site' });

  // 232 before launch plus 64 after it. Shown as one figure, because that is
  // what the recommendation is worked out from.
  const cell = page.locator('.bw-summary__cell').nth(2);
  await expect(cell).toContainText('In package calculation');
  await expect(cell).toContainText('296');
});

test('a line item saved to the library turns up there, and the switch turns itself off', async ({ page }) => {
  const id = await createDeck(page, { client: 'Whitlock Legal', title: 'Firm site' });
  await openEditor(page, id, 'Project estimate');

  // The last switch on a row is "Save to library".
  const row = page.locator('.bw-repeater__row').first();
  const keep = row.locator('.bw-switch input').last();
  await keep.check();
  await save(page);

  await expect(keep).not.toBeChecked();

  await page.goto(LIBRARY);
  await expect(page.locator('.bw-table tbody')).toContainText('Discovery workshop');
  await expect(page.locator('.bw-table tbody tr', { hasText: 'Discovery workshop' }).first()).toContainText('Line item');
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
