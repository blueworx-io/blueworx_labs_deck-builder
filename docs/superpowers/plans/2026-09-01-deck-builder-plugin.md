# Deck Builder plugin — implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: `superpowers:executing-plans`.

**Goal:** ship an installable Deck Builder plugin — the wp-admin builder and the public
client deck — against the design handoff in `docs/superpowers/specs/`.

**Architecture:** records are post types; every record editor is the shared page editor
library, never hand-written markup. The list screens, the dashboard and the create screen
are the only hand-built admin screens, and they are built from design system classes. The
client deck is its own document: no theme, no other plugin's CSS, one plugin-owned
stylesheet.

**Tech stack:** WordPress 6.5+, PHP 8.2, the BlueWorx admin design system and its page
editor library (`blueworx-page-editor/`), Playwright against the local WP harness.

**Spec:** `docs/superpowers/specs/2026-09-01-deck-builder-handoff-overview.md`,
`-logic.md`, `-screens.md`.

## Global constraints

- Every admin screen uses design system classes only. No inline `style=`, no hand-drawn
  `<svg>`, no hand-written colour or size — the adherence check is an error, not a warning.
- The only stylesheet a plugin keeps of its own for wp-admin is the documented full-bleed
  chrome override. The client deck's stylesheet is front-end and is not governed by that.
- Records are post types. The page editor library refuses a record editor whose post type
  nobody registered, and it edits records — it never creates them.
- Client-facing rendering is a whitelist, server-side: visible sections, `show_client`
  line items, visible timeline phases. Internal notes are never serialised.
- Client links carry no record ids and no user ids; decks are `noindex` and out of sitemaps.
- British English, sentence case, no exclamation marks, no emoji.

---

## File structure

| File | Responsibility |
|---|---|
| `blueworx-labs-deck-builder.php` | Bootstrap: constants, requires, update checker |
| `includes/class-blueworx-deck-builder-plugin.php` | Boots every part in one place |
| `includes/class-blueworx-deck-builder-types.php` | The four post types and their meta |
| `includes/class-blueworx-deck-builder-deck.php` | One deck: read, totals, readiness, publish, slug |
| `includes/class-blueworx-deck-builder-packages.php` | Packages, and the recommendation rule |
| `includes/class-blueworx-deck-builder-editor.php` | Every page editor screen definition |
| `includes/class-blueworx-deck-builder-admin.php` | Menu, chrome, shared screen furniture |
| `includes/class-blueworx-deck-builder-decks-screen.php` | Decks dashboard and "create new deck" |
| `includes/class-blueworx-deck-builder-list-screen.php` | Packages, case studies, content library lists |
| `includes/class-blueworx-deck-builder-settings.php` | Settings screen |
| `includes/class-blueworx-deck-builder-link.php` | `/deck/<slug>/` routing, password, noindex |
| `includes/class-blueworx-deck-builder-render.php` | The client deck document |
| `assets/deck.css`, `assets/deck.js` | The client deck's own styling and navigation |

---

## Tasks

### Task 1: Post types and the deck model
Register `bw_deck`, `bw_deck_package`, `bw_case_study`, `bw_library_item`. Deck reads its
own values, derives `projectTotal`, `postTotal`, `packageTotal`, phase subtotals and the
seven readiness checks. Slug is 12 unguessable characters, minted on create.
**Test:** PHPUnit-free — Playwright creates a deck and sees the totals move.

### Task 2: The recommendation rule
`Packages::recommend( $package_total )` returns state (`OVERRIDE`/`NONE`/`EXACT`/`OK`/
`CUSTOM`), the package, remaining capacity and the reason line, exactly as `LOGIC.md`
states it. Never returns a package with fewer hours than the total.

### Task 3: The deck editor screen
Seven tabs through the page editor library: Overview, Sections, Project estimate, Timeline,
Post-launch, Support package, Preview and share. The estimate tabs are grouped repeaters
with subtotals; the timeline is a `gantt`; the summary strip carries the live figures.

### Task 4: Package, case study and library editors
One page editor screen each, and one shared list screen shape.

### Task 5: The decks dashboard and create screen
Stat tiles, filters, the table with its badges and row actions, the empty state, and a
create screen that mints a deck and redirects into the editor.

### Task 6: The client deck
A rewrite rule at `/deck/<slug>/`, its own document, its own stylesheet, every section from
the deck's own data, keyboard navigation, and the mobile flow below 900px.

### Task 7: Tests, then the artifact
Playwright against the local harness covering create → estimate → recommendation → publish
→ the client link, plus the privacy whitelist. Then version, changelog, zip, verify.
