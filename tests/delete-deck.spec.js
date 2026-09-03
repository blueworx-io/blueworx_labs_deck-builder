const { test, expect } = require('@playwright/test');
const { AUTH_STATE, DECKS, signIn, createDeck } = require('./helpers');

test.beforeAll(async ({ browser }) => {
  await signIn(browser);
});

test.use({ storageState: AUTH_STATE });

// The row for one client, found by the name typed into the deck.
function row(page, client) {
  return page.locator('.bw-table tbody tr').filter({ hasText: client });
}

test('a deck can only be deleted once it is archived, and only after confirming', async ({ page }) => {
  const client = 'Delete Test Co ' + Date.now();
  await createDeck(page, { client, title: 'Throwaway deck' });

  // A live deck has no way to be deleted: archiving is the step that says
  // nobody is using it any more.
  await page.goto(DECKS);
  await expect(row(page, client)).toHaveCount(1);
  await expect(row(page, client).getByRole('button', { name: 'Delete' })).toHaveCount(0);

  await row(page, client).getByRole('button', { name: 'Archive' }).click();
  await expect(page.locator('.bw-notice')).toContainText(/archived/i);

  // Archived, so Delete is offered — and it asks first.
  await row(page, client).getByRole('link', { name: 'Delete' }).click();
  const modal = page.locator('.bw-modal');
  await expect(modal).toBeVisible();
  await expect(modal).toContainText(client);

  // Backing out changes nothing.
  await modal.getByRole('link', { name: 'Cancel' }).click();
  await expect(page.locator('.bw-modal')).toHaveCount(0);
  await expect(row(page, client)).toHaveCount(1);

  // Confirming removes it for good.
  await row(page, client).getByRole('link', { name: 'Delete' }).click();
  await page.locator('.bw-modal').getByRole('button', { name: 'Delete deck' }).click();
  await expect(page.locator('.bw-notice')).toContainText(/deleted|removed/i);
  await expect(row(page, client)).toHaveCount(0);
});
