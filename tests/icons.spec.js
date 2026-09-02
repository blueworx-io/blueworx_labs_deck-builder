const { test, expect } = require('@playwright/test');
const { AUTH_STATE, DECKS, signIn, createDeck, openEditor } = require('./helpers');

test.beforeAll(async ({ browser }) => {
  await signIn(browser);
});

test.use({ storageState: AUTH_STATE });

// An icon name the design system does not ship renders as an empty box. The
// only thing that says so is a console warning from the icon loader, which is
// why three of these shipped: the screens looked finished. Half of them are
// passed through a variable, so reading the source cannot find them all —
// the screen has to be opened and asked.
function watchIcons(page) {
  const missing = new Set();
  page.on('console', (message) => {
    const match = /\[bw-icon\] unknown Lucide icon: (.+)$/.exec(message.text());
    if (match) {
      missing.add(match[1].trim());
    }
  });
  return missing;
}

test('every screen draws every one of its icons', async ({ page }) => {
  const missing = watchIcons(page);

  // A deck with content, so the screens that only have icons once they have
  // rows are actually exercised.
  const id = await createDeck(page, { client: 'Ostler & Fen', title: 'Icon sweep' });

  const screens = [
    DECKS,
    `${DECKS}-create`,
    `${DECKS}-packages`,
    `${DECKS}-case-studies`,
    `${DECKS}-library`,
    `${DECKS}-settings`,
  ];
  for (const screen of screens) {
    await page.goto(screen);
    await expect(page.locator('.bw-pagehead__h1, .bw-page__title').first()).toBeVisible();
    // The loader upgrades every [data-lucide] after paint, so give it the tick.
    await page.waitForTimeout(300);
  }

  // Every tab of the deck editor, which is where most of the icons live.
  for (const tab of ['Overview', 'Sections', 'Project estimate', 'Timeline', 'Post-launch', 'Support package', 'Preview and share', 'Publish']) {
    await openEditor(page, id, tab);
    await page.waitForTimeout(300);
  }

  // This walks the screens as they are, so an icon that only appears on an
  // empty state is not reached once the site has records. Those names are
  // written literally in the source, and the foundation's adherence check
  // covers them; between the two, both kinds are seen.
  expect([...missing].sort()).toEqual([]);
});

test('the admin menu carries the design system icon, not a WordPress one', async ({ page }) => {
  await page.goto(DECKS);

  // WordPress paints a data-URI menu icon as a background image on a
  // div.wp-menu-image.svg, and a dashicon as a div carrying a dashicons-
  // class. Which of the two is there says whose icon set the menu uses.
  const item = page.locator('#adminmenu #toplevel_page_blueworx-labs-deck-builder');
  const image = item.locator('.wp-menu-image').first();

  await expect(image).toHaveClass(/\bsvg\b/);
  await expect(image).not.toHaveClass(/dashicons/);

  // And it is the icon the design system ships, not merely some SVG.
  const style = await image.getAttribute('style');
  const encoded = /base64,([A-Za-z0-9+/=]+)/.exec(style);
  expect(encoded, 'the menu icon is not a base64 data URI').not.toBeNull();

  const svg = Buffer.from(encoded[1], 'base64').toString('utf8');
  expect(svg).toContain('M12.65 7.65');
});
