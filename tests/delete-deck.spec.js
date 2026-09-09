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

test('any deck can be deleted, and only after confirming', async ({ page }) => {
  const client = 'Delete Test Co ' + Date.now();
  await createDeck(page, { client, title: 'Throwaway deck' });

  // A draft nobody has published is deletable where it sits — archiving it
  // first was busywork on a deck made by mistake.
  await page.goto(DECKS);
  await expect(row(page, client)).toHaveCount(1);

  // Delete is offered — and it asks first.
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

test('deleting a published deck says the client link will stop working', async ({ page }) => {
  const client = 'Published Delete Co ' + Date.now();
  await createDeck(page, { client, title: 'Live deck' });

  await page.goto(DECKS);
  await row(page, client).getByRole('button', { name: 'Publish' }).click();
  await expect(page.locator('.bw-notice')).toContainText(/publish/i);

  // A published deck is one somebody may already hold a link to, so the
  // question says so rather than reading the same as a throwaway draft.
  await row(page, client).getByRole('link', { name: 'Delete' }).click();
  const modal = page.locator('.bw-modal');
  await expect(modal).toBeVisible();
  await expect(modal).toContainText(/anyone holding its link/i);

  await modal.getByRole('button', { name: 'Delete deck' }).click();
  await expect(page.locator('.bw-notice')).toContainText(/deleted|removed/i);
  await expect(row(page, client)).toHaveCount(0);
});
