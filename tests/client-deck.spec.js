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

test('hosting gets its own page, and the fee lands on it when it is set', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Loxley Foods', title: 'Trade site' });
  await publish(page, 'Loxley Foods');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  // One hosting page, always — it describes the platform whether or not this
  // client has been quoted for it yet. It used to be two slides saying the
  // same thing from either side.
  const slide = guestPage.locator('.bwd-slide--hosting');
  await expect(slide).toHaveCount(1);
  await expect(guestPage.locator('.bwd-slide--service')).toHaveCount(3);
  await expect(slide.locator('.bwd-fee__n')).toHaveCount(0);

  // The fee has its own tab now, not a panel on the post-launch estimate. A
  // deck shows one currency, so the price has to be set in that one; the
  // default is sterling.
  await openEditor(page, id, 'Hosting');
  await page.fill('#hosting_price_gbp', '450');
  await save(page);

  await guestPage.goto(link);
  await expect(slide.locator('.bwd-fee__n')).toHaveText('£450');
  await expect(slide).toContainText('per month');

  // The upkeep hours behind the fee are ours, not the client's — printing
  // them under the price invited dividing one by the other.
  await expect(slide).not.toContainText('managed upkeep');

  await guest.close();
});

test('every slide carrying a number says the number is an estimate', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Thwaite Legal', title: 'Firm site' });
  await publish(page, 'Thwaite Legal');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  // The estimate, both halves of the timeline, the work after launch, hosting
  // and the package: every slide quoting hours or money carries the caveat,
  // rather than it being said once at the back of the deck.
  await expect(guestPage.locator('.bwd-note--estimate')).toHaveCount(6);
  await expect(guestPage.locator('.bwd-slide--estimate .bwd-note--estimate'))
    .toContainText(/estimates and are subject to change/i);

  await guest.close();
});

test('the client timeline is split at launch, one slide each side', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Coledale Trust', title: 'Charity site' });
  await publish(page, 'Coledale Trust');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  // A project has an end and a retainer does not. Drawn as one unbroken run of
  // bars they read as the same commitment, which is the wrong thing for a
  // proposal to say — and sixteen rows on one slide were too small to read
  // anyway. So they are a slide each.
  const before = guestPage.locator('.bwd-slide--timeline');
  const after = guestPage.locator('.bwd-slide--timeline-post');
  await expect(before).toHaveCount(1);
  await expect(after).toHaveCount(1);

  // Each slide carries only its own stretch. A post-launch bar on the build
  // slide is what a shared chart used to do.
  await expect(before.locator('.bwd-tl__bar--post')).toHaveCount(0);
  await expect(before.locator('.bwd-tl__bar--launch')).toHaveCount(1);
  await expect(after.locator('.bwd-tl__bar--pre, .bwd-tl__bar--launch')).toHaveCount(0);
  await expect(after.locator('.bwd-tl__bar--post')).not.toHaveCount(0);

  // And each counts its own weeks from week one, so the shorter stretch is not
  // drawn as a stub of the longer one.
  await expect(before.locator('.bwd-tl__row').first()).toContainText('Week 1');
  await expect(after.locator('.bwd-tl__row').first()).toContainText('Week 1');

  // The legend says only what that slide shows.
  await expect(after.locator('.bwd-legend')).not.toContainText('Before launch');

  await guest.close();
});

test('the deck ends on a call to action the client can read', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Marlow Joinery', title: 'Workshop site' });
  await publish(page, 'Marlow Joinery');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  // The last slide is dark and its button is a white pill, so the label has to
  // carry the deck's ink. The base "a { color: inherit }" reset outweighed the
  // button's own colour once, which painted the label white on white — a
  // button nobody could read, on the one slide asking the client to act.
  const button = guestPage.locator('.bwd-btn');
  await expect(button).toHaveText('Get in touch');
  const paint = await button.evaluate((el) => {
    const style = getComputedStyle(el);
    return { color: style.color, background: style.backgroundColor };
  });
  expect(paint.color).not.toBe(paint.background);
  expect(paint.color).toBe('rgb(10, 12, 41)');

  await guest.close();
});

test('a deck shows one past projects slide, not a page per project', async ({ page, browser }) => {
  const id = await createDeck(page, { client: 'Selby Timber', title: 'Trade site' });
  await publish(page, 'Selby Timber');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  // The lead-in stays and reads as a slide of its own.
  await expect(guestPage.locator('.bwd-slide--projects')).toHaveCount(1);

  // The per-project pages are gone, and a deck made while they existed still
  // holds its copy of that section — so the check is that nothing renders it,
  // not merely that the library stopped handing it out.
  await expect(guestPage.locator('.bwd-slide--casestudy')).toHaveCount(0);
  await expect(guestPage.locator('.bwd-two--study, .bwd-shot, .bwd-studyn')).toHaveCount(0);

  await guest.close();
});

test('a deck no longer asks which past projects to show', async ({ page }) => {
  const id = await createDeck(page, { client: 'Ainsley Roofing', title: 'Company site' });
  await openEditor(page, id, 'Overview');

  await expect(page.locator('.bw-panels .bw-card__title')).toHaveCount(1);
  await expect(page.locator('.bw-panels')).toContainText('Deck details');
  await expect(page.locator('.bw-panels')).not.toContainText('Past projects shown to this client');
});

test('the client logo is off the cover and the What we do slide, and the lede runs the full column', async ({
  page,
  browser,
}) => {
  const id = await createDeck(page, { client: 'Harlow Joinery', title: 'Members site' });
  await publish(page, 'Harlow Joinery');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  // The client already knows whose deck this is, so the logo comes off the
  // two slides that only repeated it. Asserting the slot is gone rather than
  // that no logo rendered: a deck with no logo uploaded would pass the
  // second check whatever the markup said.
  await expect(guestPage.locator('.bwd-slide--cover .bwd-cover__top > *')).toHaveCount(1);
  await expect(guestPage.locator('.bwd-slide--cover .bwd-cover__kind')).toBeVisible();
  await expect(guestPage.locator('.bwd-slide--what .bwd-head > *')).toHaveCount(1);

  // A service slide's description was capped narrower than the heading above
  // it, so it wrapped a third of the way across an empty column. Only the
  // slide on screen has a size to measure, so walk to the first service
  // slide rather than reading a hidden one.
  const service = guestPage.locator('.bwd-slide--service.is-current .bwd-two__left');
  for (let i = 0; i < 20 && (await service.count()) === 0; i++) {
    await guestPage.keyboard.press('ArrowRight');
  }
  await expect(service).toHaveCount(1);

  const heading = await service.locator('.bwd-h2, .bwd-h1').first().boundingBox();
  const lede = await service.locator('.bwd-lede').first().boundingBox();
  expect(lede.width).toBeGreaterThan(heading.width * 0.9);

  await guest.close();
});

test('the estimate slide gives both totals, and every total says hours', async ({
  page,
  browser,
}) => {
  const id = await createDeck(page, { client: 'Marlow Interiors', title: 'Trade site' });
  await publish(page, 'Marlow Interiors');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  // A bare number left the client to guess the unit.
  const totals = guestPage.locator('.bwd-slide--estimate .bwd-total');
  await expect(totals.first().locator('.bwd-total__unit')).toHaveText('hours');

  // Both figures on the one slide: what the build costs, and what carries on
  // afterwards. The top right used to repeat the slide's own title instead.
  await expect(totals).toHaveCount(2);
  await expect(totals.nth(0)).toContainText('Project estimate');
  await expect(totals.nth(1)).toContainText('Post launch');
  await expect(guestPage.locator('.bwd-slide--estimate')).not.toContainText(
    'Total project estimate'
  );

  await guest.close();
});

test('changing the recommendation on a published deck reaches the client', async ({
  page,
  browser,
}) => {
  const id = await createDeck(page, { client: 'Thornbury Legal', title: 'Firm site' });
  await publish(page, 'Thornbury Legal');
  const link = await linkFor(page, id);

  const guest = await browser.newContext({ storageState: undefined });
  const guestPage = await guest.newPage();
  await guestPage.goto(link);

  const recommended = guestPage.locator('.bwd-pkg--main');
  const before = await recommended.innerText();

  // Override the recommendation to a different package. The client view was
  // frozen at publish and never looked at again, so this change used to stop
  // at the editor: the deck went on showing whatever was picked the day it
  // was published, with nothing saying so.
  await openEditor(page, id, 'Support package');
  const options = await page.locator('#override option').evaluateAll((els) =>
    els.map((el) => ({ value: el.value, label: el.textContent.trim(), selected: el.selected }))
  );
  const current = options.find((o) => o.selected);
  // A package that is neither "use the automatic recommendation" nor the one
  // already showing, so the save has something to save and the deck has
  // something to change to.
  const other = options.find(
    (o) => o.value !== '0' && o.value !== current.value && !before.includes(o.label.split(' · ')[0])
  );
  await page.selectOption('#override', other.value);
  await save(page);

  await guestPage.reload();
  await expect(recommended).toContainText(other.label.split(' · ')[0]);
  expect(await recommended.innerText()).not.toBe(before);

  await guest.close();
});
