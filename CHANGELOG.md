# Changelog

All notable changes to this plugin are recorded here. The format is
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

[Unreleased]: https://github.com/blueworx-io/blueworx_labs_deck-builder/compare/v0.3.0...HEAD
[0.3.0]: https://github.com/blueworx-io/blueworx_labs_deck-builder/releases/tag/v0.3.0
[0.2.0]: https://github.com/blueworx-io/blueworx_labs_deck-builder/releases/tag/v0.2.0
[0.1.0]: https://github.com/blueworx-io/blueworx_labs_deck-builder/releases/tag/v0.1.0
