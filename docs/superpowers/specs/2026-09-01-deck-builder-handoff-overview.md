# Handoff: BlueWorx Labs Deck Builder (WordPress plugin)

> **Superseded in 0.5.0, in part.** The content library is now the only source of a
> deck's content: a new deck is a copy of the whole library, there is no starting
> point to choose and no create screen, and a deck's sections, estimates and
> timeline are fixed lists — nothing is added, removed, duplicated or reordered on
> a deck. Where this document describes those, read it as history.

Plugin slug: `blueworx_labs_deck-builder`

## Overview

A self-hosted WordPress plugin that lets the BlueWorx team build, configure, publish and
share branded client decks and project briefs. An administrator creates a deck for a named
client, assembles it from reusable sections, builds a project estimate and a separate
post-launch estimate, gets a support-package recommendation derived from those hours, builds
a project timeline, then publishes the deck to an unguessable client link.

Two surfaces:

1. **Admin builder** — plugin screens inside `wp-admin`.
2. **Client deck** — a public, login-free web presentation at the shared link.

## About the design files

The files in `design/` are **design references created in HTML** — prototypes showing
intended look and behaviour. They are not production code to copy. The task is to recreate
them inside the plugin's real environment: PHP admin pages plus a JS/React app for the
builder, and a PHP-rendered template (or a small JS app) for the client deck. Use the
codebase's established patterns; the BlueWorx admin design system ships as one stylesheet
that is enqueued verbatim, so the CSS classes referenced below already exist.

The prototypes run entirely in the browser with in-memory seed data. Every figure, client
name and package price in them is invented sample content — replace it with real data from
the plugin's tables and the manually configured packages.

## Fidelity

**High fidelity.** Colours, type, spacing, control sizes, states, copy and interaction
behaviour are final. Recreate them faithfully using the design system's own classes rather
than re-deriving values. Where a measurement is not stated in these documents, read it off
the prototype markup — it is deliberate.

Two design systems are in play and must not be mixed:

| Surface | System | Feel |
|---|---|---|
| Admin builder | **BlueWorx Admin Design System** (`styles.css`, `.bw-*` classes, Lucide icons, Sora + Inter) | Flat, functional wp-admin product surface |
| Client deck | **BlueWorx (brand) Design System** (navy/indigo/lavender, Sora, curved background shapes) | Premium presentation |

## Documents in this bundle

| File | Contents |
|---|---|
| `README.md` | This file — overview, tokens, assets, build notes, acceptance criteria |
| `SCREENS.md` | Every screen and state, layout by layout, component by component |
| `LOGIC.md` | Data model, state, the package recommendation rule, validation, permissions |
| `design/BlueWorx Deck Builder.dc.html` | Admin builder prototype (all admin screens, clickable) |
| `design/BlueWorx Client Deck.dc.html` | Client deck prototype (15 desktop sections + mobile flow) |
| `design/assets/` | `wp-chrome.css` (recreation of the wp-admin host chrome — do **not** ship), `lucide-icons.js`, `blueworx-logo.png` |
| `design/image-slot.js` | Prototype-only drag-and-drop image placeholder; replace with the WordPress media library |

Open either HTML file directly in a browser. In the admin prototype, use the WordPress
submenu on the left to move between plugin screens, and the row actions in the decks table
to open a deck.

## Design tokens

### Admin (from the design system stylesheet — use the CSS variables, not the literals)

| Token | Value | Use |
|---|---|---|
| `--bw-brand` | `#4F46E5` | The single accent |
| `--bw-brand-hover` | `#4338CA` | Solid-button hover |
| `--bw-brand-wash` | `#EEEDFC` | Accent tint, active nav |
| `--bw-text-heading` | `#0A0C29` | Titles, figures |
| `--bw-text-body` | `#0A0C29` | Body |
| `--bw-text-muted` | `#5B5D74` | Secondary text, labels |
| `--bw-surface-page` | `#F7F8FC` | Screen canvas |
| `--bw-surface-card` | `#FFFFFF` | Panels |
| `--bw-border` | `#ECEDF3` | Hairlines |
| status | WordPress core notice hues via `--bw-success-*`, `--bw-warning-*`, `--bw-danger-*`, `--bw-info-*` | Badges and notices |

Type: Sora for page titles (28/1.15/600), panel titles (16/600) and all figures (tabular
numerals); Inter for everything else — 13px body, 14px in inputs, 12px help text, 11px
uppercase labels at `.08em`, eyebrows at `.14em`. Monospace for links, passwords and IDs.

Spacing: 24px page gutter and panel padding, 20px between stacked panels, 18px between
fields, 34px control height. Radii 8px controls, 12px panels, pill for badges and chips.
Shadow only means floating. Focus is always a 2px `#4F46E5` outline at 1px offset.
Two background colours per screen, maximum.

### Client deck

| Value | Use |
|---|---|
| `#0A0C29` | Dark section background |
| `#FFFFFF` | Light section background |
| `#F5F6FF` | Light-section card fill |
| `#4F46E5` | Accent on light, decorative shapes on dark |
| `#A5A7FF` | Accent on dark, eyebrows, post-launch marker |
| `rgba(255,255,255,.74)` / `#4C4C4C` | Body on dark / on light |
| `#667085`, `#A0AFC0` | Muted, faint |
| `#EFEFF0` | Dividers |

Type: Sora throughout. Sections are authored on a **1600 × 900** stage and scaled to fit
the viewport, so sizes inside are literal px: display 104/84/72/60px at 700 weight with
-1.2 to -2.5px tracking, body 24–30px, eyebrows 20–22px uppercase at `.16em`, footnotes
18–19px. Minimum text size on a slide is 18px at 1600 wide. Radii 16–20px on cards, pill on
chips. Decorative background shapes are plain circles (`border-radius:50%`) in `#A5A7FF` or
`#4F46E5` at 14–22% opacity, sized 620–1000px and pushed off-canvas — no gradients, no
imagery behind text.

Motion: 320ms ease fade-and-rise on section change (`bwIn`), 200ms colour transitions on
controls. Nothing else animates. The deck must remain fully readable with animation
disabled.

## Assets

| Asset | Source | Notes |
|---|---|---|
| BlueWorx logo | `design/assets/blueworx-logo.png` | Full-colour wordmark. On dark sections it sits on a white plate: `background:#fff; border-radius:10px; padding:10px 16px`, logo height 26px |
| Lucide icons | `design/assets/lucide-icons.js` | Real Lucide geometry, self-hosted. In the plugin, enqueue this as a script module for PHP-rendered screens (`<i class="bw-icon" data-lucide="name"></i>`) and use `lucide-react` in the React app — the names are identical. Never hand-draw an SVG |
| Client logo | Uploaded per deck | WordPress media library; prototype fakes the picker |
| Case-study imagery | Client-supplied | Prototype uses `<image-slot>`; production uses the media library. Desktop, tablet and mobile shots per case study |
| `wp-chrome.css` | Prototype only | Recreates the wp-admin sidebar and admin bar so the mock reads correctly. WordPress provides the real chrome — do not ship this file |

## Build notes

- **Full-bleed admin screens.** Each plugin screen fills `#wpcontent` edge to edge: drop
  `.wrap` margin, drop `#wpcontent` left padding, hide `#wpfooter`, exactly as the design
  system's guide sets out. Header, panels and save bar share the 24px gutter.
- **Menu.** One top-level menu, `Deck Builder`, with submenu items in this order: Decks,
  Create New Deck, Content Library, Case Studies, Support Packages, Settings.
- **The deck editor is one screen with tabs**, not seven pages: Overview, Sections, Project
  estimate, Timeline, Post-launch, Support package, Preview and share. The editor header and
  the totals strip persist across every tab.
- **Autosave is not assumed.** Dirty state raises the sticky save bar; the header carries
  Save draft, Preview, Publish/Update and Copy link (once published).
- **Support packages are configured manually in the plugin** — there is no external
  commerce integration. Each package carries four prices (Rand, Dollar, Pound, Euro) and a
  deck chooses which currency it displays.
- **Client links** use an unguessable 12-character slug, require no WordPress login unless
  password protection is on, are excluded from search engines and sitemaps, and stop working
  when the deck is archived or the link is disabled. Never expose record IDs or user IDs.
- **Internal notes and hidden line items must never render in the client view** — filter
  them server-side, not in CSS or JS.
- **Print/PDF export is out of scope** for this phase, as are client editing, comments,
  signatures, payments, analytics and automatic scheduling from hours.

## Acceptance criteria

An authorised administrator can:

1. Create a deck for a named client from the retainer template or a blank deck.
2. Configure and reorder reusable sections, hide, duplicate and remove them, and edit one
   for the current deck without changing the library entry.
3. Build a project estimate with phase subtotals and a project total.
4. Build a separate post-launch estimate with its own total.
5. Choose per line item whether it counts towards the total, shows to the client, and counts
   towards the package calculation.
6. Set up packages manually with hours and four currency prices, and pick the display
   currency per deck.
7. Receive a correct recommendation that never under-allocates hours, and override it.
8. Build a client-facing timeline in project weeks or calendar dates, with a launch
   milestone separating pre- and post-launch work.
9. Select case studies for the deck.
10. Preview the deck on desktop and mobile, publish it to an unguessable link, then update,
    disable, regenerate or password-protect that link.
11. Confirm the client sees no internal notes and no configuration controls.

## Responsive and accessibility requirements

- Both surfaces are fully responsive. Admin panels stack below 1100px; the section nav wraps
  below 782px; the client deck switches to its single-column flow below 900px and never
  shrinks a whole desktop slide into an unreadable image.
- Every control is keyboard reachable with a visible 2px focus ring; the client deck
  supports arrow keys, Page Up/Down, space, Home and End.
- Status is never carried by colour alone — estimate, package, warning and deck-status states
  all pair colour with a label, an icon or both.
- Touch targets on the client deck are at least 44px.
- Contrast meets WCAG AA at every size used.
