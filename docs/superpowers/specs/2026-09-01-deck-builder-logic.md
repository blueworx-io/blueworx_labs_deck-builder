# Data model, state and logic

## Entities

### Deck

| Field | Type | Notes |
|---|---|---|
| `id` | int | Internal only. Never exposed to the client |
| `client` | string | Organisation name |
| `title` | string | Deck title |
| `sub` | text | Supporting statement, one sentence |
| `preparedFor` | string | Prepared-for label |
| `date` | date | Prepared date; also the origin for calendar-mode timeline dates |
| `owner` | user | Internal deck owner |
| `start` | enum | `BlueWorx Retainer Deck` \| `Blank Deck` |
| `logo` | attachment | Client logo |
| `currency` | enum | `ZAR` \| `USD` \| `GBP` \| `EUR` — which price the client sees |
| `status` | enum | `Draft` \| `Published` \| `Archived` |
| `slug` | string | 12-character unguessable client-link identifier |
| `linkEnabled` | bool | False returns a not-found page |
| `passwordOn` | bool | |
| `password` | string | Only when `passwordOn` |
| `override` | package id \| null | Manual package choice |
| `alternatives` | package id[] | Packages shown for comparison |
| `caseStudies` | case-study id[] | Ordered |
| `sections` | Section[] | Ordered |
| `estimate` | LineItem[] | Ordered; project work |
| `post` | LineItem[] | Ordered; post-launch work |
| `customPhases` | string[] × 2 | Custom phase names, per builder |
| `timeline` | Phase[] | Ordered |
| `snapshot` | json | Package name, hours, price and currency, frozen at publish |
| `updatedAt` | datetime | |

### Section

`id` · `name` · `note` (12px description shown in the builder) · `tag` (`Library` |
`Edited for this deck` | `Generated` | `Case study`) · `visible` · `libraryId` (null once
edited for this deck) · `content` (per-section fields).

Section types and their fields: Cover · What we do · Service detail (title, subtitle,
description, key points, estimated hours pulled from the estimate, optional links, optional
image) · Estimate summary (generated) · Recommended support package (generated) · Project
timeline (generated) · Post-launch work (generated) · Process · Past projects intro · Case
study · Call to action.

**Editing a library section inside a deck must clone it**: write the deck-specific copy,
set `tag = 'Edited for this deck'`, clear `libraryId`, and leave the library record untouched.

### LineItem (used by both builders)

`id` · `phase` (category name, default or custom) · `title` · `desc` (client-facing) ·
`hours` (number) · `note` (internal, never rendered client-side) · `inTotal` · `showClient` ·
`inPackage`.

The two lists are structurally identical and share one UI component, but their data and
totals are separate.

### Timeline Phase

`id` · `title` · `desc` · `start` (week number) · `end` · `milestone` (label) ·
`kind` (`pre` | `launch` | `post`, which also sets the bar colour) · `visible`.

Weeks are authored, never derived from hours. Calendar mode renders `deck.date + (week-1)×7`.

### Package (configured manually in the plugin)

`id` · `name` · `hours` (nullable) · `period` (allowance label, e.g. "per month") ·
`commitment` · `order` · `eligible` (for automatic recommendation) · `popular` ·
`prices: { ZAR, USD, GBP, EUR }` (each nullable).

Packages are global, not per deck. A deck can only display a currency that has a price on the
packages it shows.

## Derived values

```
projectTotal = sum(estimate[].hours where inTotal)
postTotal    = sum(post[].hours where inTotal)
packageTotal = sum(estimate[].hours where inPackage) + sum(post[].hours where inPackage)
phaseSubtotal(phase) = sum(items in phase where inTotal)
```

All three appear live in the editor's totals strip and in the builder's stat tiles.
`inTotal` and `inPackage` are independent: an item can be quoted to the client but excluded
from what the support package must cover, or vice versa.

## Package recommendation rule

```
eligible = packages
             .filter(p => p.eligible && p.hours > 0)
             .sort(by hours ascending)

if (deck.override)                    → state = OVERRIDE, package = override
                                        (also compute what automatic would have said)
else if (eligible is empty)           → state = NONE
else find smallest p where p.hours >= packageTotal
     if found and p.hours === total   → state = EXACT
     if found                         → state = OK
     if none found                    → state = CUSTOM
```

- Never recommend a package with fewer hours than `packageTotal`.
- `CUSTOM` — show "Custom package required", flag the deck for manual review, recommend
  nothing automatically.
- `NONE` — show a configuration warning and block publishing the package section until it is
  resolved or the section is hidden for this deck.
- `OVERRIDE` — mark clearly in the editor; the client sees it as the selected recommendation
  with no override messaging.
- Remaining capacity = `package.hours - packageTotal`.
- Reason line: "Smallest eligible package with at least {total} hours."

Recommendation state drives the notice tone, the header badge, the totals strip, the
dashboard's Recommended column, and the readiness check "A package can be recommended".

## Published price behaviour

On publish, snapshot the selected package's name, hours, price and currency onto the deck so
the client view cannot change underneath the administrator. Editing a package afterwards
leaves published decks alone until the administrator republishes ("Update deck").

## Editor state

| State | Notes |
|---|---|
| `screen` | Decks \| Create \| Editor \| Support packages \| Content library \| Case studies \| Settings |
| `tab` | overview \| sections \| estimate \| timeline \| post \| package \| share |
| `deckId` | Open deck |
| `dirty` | Any edit raises the sticky save bar and switches the header note to "Unsaved changes" |
| `query`, `filterStatus`, `filterType` | Dashboard search and filters |
| `timelineMode` | `weeks` \| `dates` |
| `selectedPhase` | Which timeline phase the detail panel edits |
| `dragging` | Section id or line-item id in flight |
| `toast` | Confirmation message, auto-dismissed after ~2.6s, with a manual close |

Actions that mutate the deck: section add/reorder/hide/duplicate/edit/remove · line item
add/edit/duplicate/reorder/remove and its three switches · add custom phase · reorder phase ·
save line item to the library · timeline add/edit/duplicate/reorder/hide/remove and mode
switch · case-study selection · currency · override · alternatives · logo · password · link
enable · regenerate link · publish · archive/restore · duplicate deck.

Every one of these is wired in the prototype — check behaviour there when the wording here is
ambiguous.

## Confirmations

Use a toast for anything that already worked ("Draft saved.", "Client link copied to the
clipboard.", "Published. The client link is live and the package price is locked to this
version."). Use a notice for something that must be read (missing hours, custom package
required, archived deck). Use a modal only for a decision that cannot wait — deleting a
package or a case study that decks currently use, and regenerating a live link.

## Validation

- Client name and deck title are required before publishing.
- A deck with no sections cannot be published.
- Hours are non-negative numbers; blank reads as zero in totals but blank on a package means
  "not set" and makes it ineligible.
- A package needs hours and a price in the deck's currency before it can be recommended or
  shown.
- Publishing is blocked while the package section is visible and the recommendation state is
  `NONE`.
- Line-item descriptions are client-facing: sanitise on save and escape on output.

## Permissions and safety

- Only authorised WordPress users (Administrator, Site manager by default, configurable on
  Settings) may create, edit, publish, archive or configure decks.
- Client-facing rendering runs from a whitelist: only `showClient` line items, only visible
  sections, only visible timeline phases. Internal notes are never serialised into the client
  payload.
- Client links carry no WordPress user IDs, no record IDs and no predictable deck IDs.
- Client decks are excluded from search engines and from WordPress sitemaps.
- Archiving or disabling a link makes the client URL return a not-found page.
- All client-facing content and links are sanitised on save and escaped on output.

## Sample data in the prototypes

Invented, for design purposes only. The admin prototype seeds four decks — a full draft
(Westbrook Padel Club), a blank draft (Northgate Collective), a published deck (Hiraste) and
an archived one (PadlX) — with a 232-hour project estimate, 64 hours of post-launch work,
296 hours in the package calculation, and five packages (Care 120h, Core 240h, Core Plus 360h,
Partner 480h, Launch Cover with no hours set, which drives the missing-hours warning). At
296 hours the rule recommends Core Plus with 64 hours of remaining capacity; switching the
excluded 260-hour item into the calculation pushes the total to 556 and flips the state to
`CUSTOM`, which is the fastest way to see that path.
