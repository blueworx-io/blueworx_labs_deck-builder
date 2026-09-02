# Changelog

All notable changes to this plugin are recorded here. The format is
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.5.0] - 2026-09-02

### Changed

- The content library is now the only place a deck's content comes from. Every
  new deck arrives as a copy of the whole library — every section and every line
  item, all switched on — and from then on the copy is that deck's own. Editing a
  library entry changes what the next deck starts with and leaves existing decks
  untouched. The separate hardcoded retainer set is gone, so there is no second
  list to disagree with the first.
- Creating a deck is one click. The starting-point screen has gone, along with the
  choice between a retainer deck and a blank one: "Create new deck" makes the deck
  and opens it, and the client, title and currency are filled in on the deck
  itself.
- A deck's sections, its two estimates and its timeline are fixed lists. Nothing
  can be added, removed, duplicated or reordered, and the library pickers have
  gone with them. Every section presents in the same order every time. What each
  one says, and whether the client sees it, are still per deck.
- The content library can be browsed, edited and deleted from, but not added to.
  A new entry is a change to what the business offers, so it is made in code.

### Fixed

- Dropdowns drew two arrows — WordPress puts its own chevron on every select in
  wp-admin, and it was landing beside the design system's. They also wore
  WordPress's height, border and corners instead of ours.
- Screens and panels ended flush against the bottom of the window, with no
  breathing room under the last thing on them.
- The actions column on every table had no heading, because it shared the class
  that fades row actions in on hover. It now says "Actions", and a table too wide
  for its card scrolls inside the card instead of spilling past it.

## [0.4.1] - 2026-09-02

### Fixed

- Four icons were missing from the design system, so they drew nothing at all:
  the link on the “Live client links” tile, the two content library and
  package empty states, and the device buttons on the deck preview.

### Changed

- The Deck Builder menu item carries a BlueWorx icon rather than a WordPress
  one. It does not brighten on hover the way WordPress's own icons do —
  WordPress paints it as a background image, which cannot take a colour.

## [0.4.0] - 2026-09-02

### Added

- A **BlueWorx: Sales Agent** role. It can do everything in the deck builder
  and nothing else on the site — no posts, pages, comments, users, settings
  or plugins. Pick it when adding a user.

### Changed

- The deck builder is behind a permission of its own rather than the site's
  settings permission. Administrators and sales agents have it; nobody else
  does, including editors.
- Decks, packages, case studies and library entries are behind the same
  permission, so WordPress's own checks agree with the plugin's screens.

Client links are unaffected. A published deck stays open to anyone with the
link, with no WordPress account needed.

### Fixed

- Archiving or restoring took the record id on trust. It checks the record is
  a deck first, and says so when it is not.

## [0.3.0] - 2026-09-02

### Added

- Build a deck from things you have written before: tick a section or a line
  item from the content library, save, and it is copied into the deck. The
  copy is the deck's own, so editing it there leaves the library alone.
- Keep a line item for next time. Turn on "Save to library" on any estimate
  row and saving files it in the library, without its internal note.
- The content library now holds line items as well as sections, and a fresh
  install starts with six of the ones that turn up on most quotes.
- See the client deck without leaving the builder: the share tab frames the
  real page at desktop, tablet and phone widths. A deck can be checked this
  way before it is ever published.

### Changed

- The plugin is listed in WordPress as "BlueWorx Labs | Deck Builder".
- The two "in package" figures above the tabs are now one, because that is
  the single number the package recommendation is worked out from.

## [0.2.0] - 2026-09-02

### Added

- Build a deck for a client: name them, pick a starting point, and the deck
  arrives with its sections, a project estimate, post-launch work and a
  timeline already in it.
- A decks screen showing every deck, its hours, its recommended package and
  whether its client link is live, with search and filters.
- One editor for a deck, in tabs — overview, sections, project estimate,
  timeline, post-launch, support package, and preview and share — with the
  running totals kept above the tabs.
- A support package recommendation that never suggests fewer hours than the
  work needs, says why it chose what it chose, and can be overridden by hand.
- Support packages, case studies and reusable sections, each set up once and
  reused by every deck. Packages carry a price in all four currencies and each
  deck picks which one its client sees.
- A client deck at its own private link: no WordPress login, no record ids in
  the address, out of search engines, and optionally behind a password. It can
  be turned off or archived at any point, and it reads as a presentation on a
  laptop and as a document on a phone.
- The price a client sees is frozen when the deck is published, so editing a
  package afterwards cannot change a quote already sent.

### Security

- Internal notes and line items held back from the client are filtered on the
  server. They are never sent to the browser, so nothing is hidden only by CSS.

## [0.1.0] - 2026-09-01

### Added

- First release: the plugin skeleton, the shared BlueWorx guardrails, and a
  Deck Builder admin screen built from the shared admin design system.
- Auto-updates from GitHub Releases, so a site running this plugin is offered
  each new version from wp-admin.
- `npm run build:zip` builds and verifies the installable plugin zip.

[Unreleased]: https://github.com/blueworx-io/blueworx_labs_deck-builder/compare/v0.4.1...HEAD
[0.4.1]: https://github.com/blueworx-io/blueworx_labs_deck-builder/releases/tag/v0.4.1
[0.4.0]: https://github.com/blueworx-io/blueworx_labs_deck-builder/releases/tag/v0.4.0
[0.3.0]: https://github.com/blueworx-io/blueworx_labs_deck-builder/releases/tag/v0.3.0
[0.2.0]: https://github.com/blueworx-io/blueworx_labs_deck-builder/releases/tag/v0.2.0
[0.1.0]: https://github.com/blueworx-io/blueworx_labs_deck-builder/releases/tag/v0.1.0
