# Changelog

All notable changes to this plugin are recorded here. The format is
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project uses
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.7.0] - 2026-09-03

### Added

- Hosting is its own tab in the editor and its own page in the deck: the
  platform, the upkeep behind it — servers, updates, databases and mail — and
  what it costs a month. It carries a price in each of the four currencies, set
  once in Settings and copied onto every new deck. The page stands whether or not
  a fee has been quoted yet; the price appears once it has.
- A Reviews and reverts phase, before QA and testing, so a client sees the
  finished work and asks for changes before anything is tested.
- The content library now arrives already written, from the standard BlueWorx
  deck: every slide's wording, and every line item on both estimates. It updates
  itself on a plugin update, and leaves anything you have edited exactly as it is.

### Changed

- Every slide showing hours or a price now says outright that the figures are
  estimates and are subject to change.
- The timeline is worked out from the estimates rather than typed. A phase lasts
  as long as its hours say it lasts, at four hours of work a day, phases run in
  the order the estimates declare, and a phase appears to the client only when its
  work does. Nothing on the tab is editable, and the calendar-date view is gone —
  a proposal does not know when the project starts.
- The timeline is split at launch, on the client deck and in the editor:
  Development phase, then Post-launch. A project has an end and a retainer does
  not, and one unbroken chart read as the same commitment. Each counts its own
  weeks from week one, and in the editor they are tabs — one plan on screen at a
  time. Launch closes the development phase rather than opening the retainer.
- Disabled dropdowns no longer draw a row of WordPress chevrons across the field.
- Which phase a piece of work belongs to is now set in the content library and
  shown, read-only, on a deck. It decides where the work lands on the timeline, so
  a deck moving one would move its own schedule.
- Project management is now post-launch work rather than project work.
- Competitor research, content and editing, and the accessibility pass have been
  dropped from the project estimate; performance optimisation and SEO from the
  post-launch estimate. Feature improvements is now Post-launch updates.
- Text boxes in wp-admin wear the design system's rounded corner and border again,
  instead of WordPress's own square 2px one.

## [0.6.0] - 2026-09-03

### Changed

- The fourth tile on the Decks screen is now Potential earnings: what every open
  deck's recommended package is worth a month if they all land. It is always in
  pounds, whatever currency a deck displays for its client, and nothing is
  converted — the figure is the pound price somebody actually set on the package.
  Archived decks are left out, and a deck with no recommendation adds nothing.
- Editing a deck, a package, a case study or a library entry no longer offers an
  excerpt, comments, categories and tags, or a parent and template. None of these
  records is a page of the site and none of that applied to them.
- A record's address is now shown in full with a Copy button instead of being
  typed into. Changing it broke links already sent, and a deck's link is made when
  the deck is.
- Rows in Sections, Project estimate and Post-launch now run one field per line at
  full width, each row in its own card, instead of eight controls squeezed across
  one wrapping line.

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
