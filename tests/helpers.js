const path = require('node:path');
const { expect } = require('@playwright/test');

// The harness ships this admin account; CI passes the same pair in. Anything
// that reaches wp-admin needs them, so a run without them would sign in as
// nobody and assert nothing.
const USER = process.env.WP_ADMIN_USER || 'admin';
const PASS = process.env.WP_ADMIN_PASS || 'wptest-admin-pw';
const LOGIN_PATH = process.env.WP_LOGIN_PATH || '/wp-login.php';

// Anchored to this file, not process.cwd() — a bare relative path writes the
// wrong file the moment the suite is run from anywhere but the repo root.
const AUTH_STATE = path.join(__dirname, '.auth-state.json');

const DECKS = '/wp-admin/admin.php?page=blueworx-labs-deck-builder';
const CREATE = '/wp-admin/admin.php?page=blueworx-labs-deck-builder-create';
const PACKAGES = '/wp-admin/admin.php?page=blueworx-labs-deck-builder-packages';
const EDITOR = '/wp-admin/admin.php?page=blueworx-deck-editor&id=';

// Sign in as somebody other than the admin, and hand back the context so the
// caller can close it. Kept separate from signIn(), which caches the admin
// state to a file every other spec then reuses — a second account must never
// overwrite that.
async function signInAs(browser, user, pass) {
  const context = await browser.newContext({ storageState: undefined });
  const page = await context.newPage();
  await page.goto(LOGIN_PATH);
  await page.fill("#user_login", user);
  await page.fill("#user_pass", pass);
  await page.click("#wp-submit");
  return { context, page };
}

async function signIn(browser) {
  // Every test loads AUTH_STATE, including — by default — the context that
  // goes and creates it. Overriding it back to "no state yet" breaks that
  // chicken-and-egg loop.
  const context = await browser.newContext({ storageState: undefined });
  const page = await context.newPage();

  await page.goto(LOGIN_PATH);
  await page.fill('#user_login', USER);
  await page.fill('#user_pass', PASS);
  await page.click('#wp-submit');
  await expect(page.locator('#wpadminbar')).toBeVisible();

  await context.storageState({ path: AUTH_STATE });
  await context.close();
}

// Make a deck through the screen a person would use, and hand back the id the
// editor opened on. Creating it through the UI rather than through the
// database is deliberate: it is the only way the "create" path is ever
// exercised, and it is where a new deck's client link gets minted.
async function createDeck(page, { client, title, start = 'retainer' }) {
  await page.goto(CREATE);
  await page.fill('#bw-client', client);
  await page.fill('#bw-title', title);
  await page.check(`input[name="start"][value="${start}"]`);
  await Promise.all([
    page.waitForURL(/page=blueworx-deck-editor/),
    page.click('button[type=submit].bw-btn--primary'),
  ]);

  const id = new URL(page.url()).searchParams.get('id');
  expect(Number(id)).toBeGreaterThan(0);
  return Number(id);
}

// The editor is a React app behind a REST call, so "the page loaded" is not the
// same thing as "the record is on screen".
async function openEditor(page, id, tab) {
  await page.goto(EDITOR + id);
  await expect(page.locator('.bw-pagehead__h1')).toBeVisible();
  if (tab) {
    await page.click(`.bw-tab:has-text("${tab}")`);
  }
}

// The one save bar, whatever the tab. It is always on screen — what changes is
// what it says and whether its buttons are live — so "saved" is the hint going
// back to clean, never the bar disappearing.
async function save(page) {
  const bar = page.locator('.bw-savebar');
  await expect(bar).toBeVisible();
  await bar.getByRole('button', { name: 'Save changes' }).click();
  await expect(bar.locator('.bw-savebar__hint')).toHaveText(/everything is saved/i, { timeout: 30000 });
}

module.exports = { AUTH_STATE, DECKS, CREATE, PACKAGES, EDITOR, signIn, signInAs, createDeck, openEditor, save };
