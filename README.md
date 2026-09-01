# Deck Builder

A BlueWorx Labs WordPress plugin. Build a deck once, then present it from
anywhere on the site.

The site is built **as a plugin, in code** — never in a page builder, never as a
loose theme.

## Install it on a site

Build the artifact and install the zip through **Plugins → Add New → Upload**:

```bash
npm run build:zip     # writes ../blueworx-labs-deck-builder-<version>.zip
```

After the first install the site updates itself from GitHub Releases. A private
repo needs a token in `wp-config.php`:

```php
define( 'BLUEWORX_PLUGIN_UPDATE_TOKEN', 'github_pat_...' );
```

## Work on it

```bash
composer install
composer lint                              # PHPCS, WordPress standard

npm install
node ../bluegroup_core_foundation/scripts/wp-test-env.mjs up --plugin .
PLAYWRIGHT_BASE_URL=http://127.0.0.1:8881 WP_ADMIN_USER=admin WP_ADMIN_PASS=wptest-admin-pw \
  npx playwright test --workers=1
```

The tests run against a disposable WordPress the run provisions itself — PHP and
SQLite, no Docker, no hosting, and never a shared staging site.

## Rules that are not this repo's to change

- `CLAUDE.md` is the shared BlueWorx rules, carried in from
  [`bluegroup_core_foundation`](https://github.com/blueworx-io/bluegroup_core_foundation).
- `.claude/skills/blueworx-admin-design/` and the copies of it in `assets/` are
  the shared admin design system. CI compares them against the foundation on
  every pull request, so they stay verbatim — every wp-admin screen is built
  from that system rather than hand-written.
- CI and releases come from the foundation's reusable workflows. Pinning a later
  `foundation_ref` is a deliberate pull request, not a re-run.
