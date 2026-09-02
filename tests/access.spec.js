const { test, expect } = require('@playwright/test');
const { AUTH_STATE, DECKS, signIn, signInAs, createDeck, openEditor } = require('./helpers');

const NEW_USER = '/wp-admin/user-new.php';
const PASS = 'deck-builder-test-pw-1';

test.beforeAll(async ({ browser }) => {
  await signIn(browser);
});

test.use({ storageState: AUTH_STATE });

// Users outlive a run, so each one is made fresh. Making them through the
// screen an administrator would use is deliberate: it is the only way the role
// is proved to be pickable, which is the difference between a role existing in
// the database and a role somebody can actually assign.
async function makeUser(page, role) {
  const login = `bw${Date.now().toString(36)}${Math.floor(Math.random() * 1000)}`;

  await page.goto(NEW_USER);
  await page.fill('#user_login', login);
  await page.fill('#email', `${login}@example.test`);
  await page.fill('#pass1', PASS);
  await page.selectOption('#role', role);
  await page.locator('#createusersub').click();
  await expect(page.locator('#message')).toContainText(/New user created/i);

  return login;
}

test('the sales agent role is there to pick when adding a user', async ({ page }) => {
  await page.goto(NEW_USER);
  await expect(page.locator('#role option[value="blueworx_sales_agent"]')).toHaveText('BlueWorx: Sales Agent');
});

test('a sales agent can build a deck', async ({ page, browser }) => {
  const login = await makeUser(page, 'blueworx_sales_agent');

  const agent = await signInAs(browser, login, PASS);

  // The menu is the first thing they need, and the screen behind it has to
  // answer rather than refuse.
  await agent.page.goto(DECKS);
  await expect(agent.page.locator('.bw-pagehead__h1')).toBeVisible();
  await expect(agent.page.locator('#adminmenu')).toContainText('Deck Builder');

  // And the whole of it, not just the dashboard: a deck they make, opened in
  // the editor, with its record read back.
  const id = await createDeck(agent.page, { client: 'Marsden Joinery', title: 'Workshop site' });
  await openEditor(agent.page, id, 'Project estimate');
  await expect(agent.page.locator('.bw-summary__cell').first()).toContainText('232');

  await agent.context.close();
});

test('an editor cannot reach the deck builder at all', async ({ page, browser }) => {
  const login = await makeUser(page, 'editor');

  const editor = await signInAs(browser, login, PASS);

  await editor.page.goto('/wp-admin/');
  await expect(editor.page.locator('#adminmenu')).not.toContainText('Deck Builder');

  // Not on the menu is not the same as not reachable. An editor has
  // edit_others_posts, which is exactly the capability that would have let
  // them at every deck on the site if the post types had been left on the
  // built-in ones.
  await editor.page.goto(DECKS);
  await expect(editor.page.locator('body')).toContainText(/not allowed to access this page/i);

  await editor.context.close();
});

test('a locked builder does not lock the client link', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Halstead Dairy', title: 'Farm shop' });

  await page.goto(DECKS);
  const row = page.locator('.bw-table tbody tr').filter({ hasText: String(id) });
  const target = (await row.count()) ? row : page.locator('.bw-table tbody tr').first();
  await target.getByRole('button', { name: 'Publish' }).click();
  await expect(page.locator('.bw-notice--success')).toBeVisible();

  await openEditor(page, id, 'Preview and share');
  const link = await page.locator('.bw-copyfield input').inputValue();

  // Nobody signed in at all. This is the whole point of the client link, and
  // it is the thing a permissions change is most likely to break by accident.
  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  const response = await guestPage.goto(link);

  expect(response.status()).toBe(200);
  await expect(guestPage.locator('h1.bwd-display')).toContainText('Farm shop');

  await guest.close();
});


test('a row action does nothing to an id that is not a deck', async ({ page }) => {
  // The handler took its id on trust. That was academic while only an
  // administrator could reach it; a sales agent reaches it now, and an
  // unchecked id is a way to write this plugin's meta onto any post on the
  // site. Post 1 is WordPress's own "Hello world", which is not a deck.
  await createDeck(page, { client: 'Kestrel Signs', title: 'Trade site' });

  await page.goto(`${DECKS}&status=archived`);
  const archived = await page.locator('.bw-bulk__count').textContent();

  await page.goto(DECKS);
  const nonce = await page.locator('input[name="_wpnonce"]').first().inputValue();

  await page.evaluate(async (wpnonce) => {
    const body = new URLSearchParams({
      action: 'blueworx_deck_action',
      deck_action: 'archive',
      deck_id: '1',
      _wpnonce: wpnonce,
    });
    await fetch('/wp-admin/admin-post.php', { method: 'POST', body, credentials: 'same-origin' });
  }, nonce);

  // It says so rather than reporting an archive that never happened, and no
  // deck moved into the archived filter.
  await page.goto(`${DECKS}&done=missing`);
  await expect(page.locator('.bw-notice--warning')).toContainText('could not be found');

  await page.goto(`${DECKS}&status=archived`);
  await expect(page.locator('.bw-bulk__count')).toHaveText(archived);
});
