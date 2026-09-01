# Screens

Every admin screen uses the same shell: full-bleed page on `--bw-surface-page`, a
`.bw-pagehead` (white, hairline bottom border, 24px gutter) carrying an 11px uppercase
accent eyebrow, a 28px Sora title, a 15px muted lede and right-aligned actions, then the
body at 24px gutter with 20px between panels. Panels are `.bw-card`: white, 1px `#ECEDF3`,
12px radius, one small shadow, 24px body padding, optional sunken footer bar.

---

## 1. Decks dashboard

**Purpose** — find a deck, see its state at a glance, start a new one.

- Header: eyebrow "Deck Builder", title "Decks", lede "Client decks and project briefs built
  from the BlueWorx section library." Actions: secondary "Support packages", primary
  "Create new deck" (plus icon).
- Four stat tiles (`auto-fit, minmax(200px, 1fr)`, 14px gap): Decks · Live client links ·
  Hours estimated · Need attention. Each is an 11px uppercase label with a 14px Lucide icon,
  a 28px Sora figure with tabular numerals, and a 12px faint footnote.
- Warning notice when any package eligible for recommendation has no hours: warning tone,
  `triangle-alert`, title "One package has no included hours", body naming the package, and
  a link action "Set its hours" that navigates to Support packages.
- Table panel, `flush` so the table meets the card edges:
  - Toolbar: search input ("Search client or deck title"), status select (All statuses /
    Draft / Published / Archived), starting-point select (All / BlueWorx Retainer Deck /
    Blank Deck), spacer, and an "N of M decks" count.
  - Columns: Client (primary line + deck title beneath) · Starting point · Hours (right,
    tabular) · Recommended (badge: accent when a package fits, danger when custom is
    required, neutral when nothing is estimated) · Status (badge with dot: warning=Draft,
    success=Published, neutral=Archived) · Updated · Actions (right, fade in on row hover).
  - Row actions: Edit · Preview · Duplicate · Archive/Restore · Copy link (label reads
    "Link off" when the deck has no live link).
  - Empty state when filters match nothing: dashed panel, `search` icon at 28px, title "No
    decks match those filters", body, and a "Clear filters" button.
  - Footer bar: the count, and "Archived decks keep their content but their client link stops
    working."

**States to build** — populated, filtered-empty, and rows for draft, published and archived
decks (archived shows Restore and a dead link).

---

## 2. Create new deck

**Purpose** — name the client and choose a starting point.

- Header actions: secondary "Cancel".
- Below the header, a stepper row: 1 Create (current) → 2 Configure → 3 Preview and share.
  Pills, 20px numbered discs, 16px separators.
- Panel "Who is this deck for" (eyebrow "Client"), two-column field grid (`.bw-fields`,
  18px gap, single column below 782px):
  Client or organisation (required) · Deck title (required) · Subtitle (full width,
  textarea, 2 rows) · Prepared-for label · Prepared date (date input) · Display currency
  (Rand / Dollar / Pound / Euro) · Internal deck owner (select) · Client logo (media field:
  56px thumb or dashed empty box, "Choose image"/"Replace image", "Remove" when set, hint
  "PNG or SVG, at least 320px wide.").
- Panel "Where the content comes from" (eyebrow "Starting point"): two selectable cards in
  an `auto-fit, minmax(280px,1fr)` grid. Each card shows the name in 16px Sora 600, an icon,
  a description and a faint meta line; the selected card takes the active section-nav
  treatment (accent wash, accent border, accent text).
  - "BlueWorx Retainer Deck" — "Sixteen sections, the standard process, estimate summary and
    package comparison. Everything stays editable per deck." · "Used for 12 of the last 14 decks"
  - "Blank Deck" — "An empty deck. Add sections from the content library in whatever order
    this client needs." · "For one-off briefs"
- Card footer: "Cancel" and primary "Create deck and configure" (arrow-right). Creating opens
  the editor on Overview with the chosen starting point's content already loaded (or nothing,
  for a blank deck).

---

## 3. Deck editor shell

Present on every editor tab.

- **Header**: breadcrumb-style eyebrow — a link "Decks" then " / " then the client name —
  above the deck title as the page title, then a status badge and a 12px note reading either
  "Unsaved changes" or "All changes saved · updated {date}". Actions: Save draft · Preview
  (opens the client deck in a new tab, carrying the deck's currency) · primary Publish, which
  becomes "Update deck" once published · ghost "Copy link", shown only when published.
- **Totals strip**: white band under the header, hairline bottom border, 24px gutter. Five
  cells separated by 1px vertical rules, each an 11px uppercase label, a 24px Sora value and
  a 12px faint footnote: Project estimate · Post-launch work · In package calculation ·
  Recommended package · Readiness (percentage). The strip updates live as hours change.
- **Tabs**: `.bw-tabs`, uppercase 12px, 2px accent underline on the active tab, with a faint
  count suffix per tab — Overview · Sections (count) · Project estimate (hours) · Timeline
  (count) · Post-launch (hours) · Support package · Preview and share.
- **Warnings**, stacked above the tab content, in this order when they apply:
  1. danger, `triangle-alert`, "Flagged for manual review" — the calculated work exceeds the
     largest eligible package.
  2. warning, `triangle-alert`, "{Package} has no included hours".
  3. info, `info`, "This deck is empty" — no sections yet.
  4. warning, `archive`, "This deck is archived".
- **Sticky save bar** whenever the deck is dirty: `info` icon, "You have unsaved changes.",
  ghost "Discard", primary "Save changes".

---

## 4. Overview tab

Two-column: main column plus a 300px sticky side column (stacks below 1100px).

- Panel "Deck details" (eyebrow "Overview", right-aligned note "Shown on the cover"):
  Client · Deck title · Supporting statement (full-width textarea) · Prepared for ·
  Prepared date · Display currency, with help "Used for every package price this client sees."
- Panel "Past projects shown to this client" (eyebrow "Case studies"), note "Pick the work
  closest to this client's sector. Order follows the section list." Selectable checkbox cards
  in an `auto-fit, minmax(240px,1fr)` grid; each has a 16px checkbox with accent tint, the
  project name in 600, sector at 12px muted, and the services line at 12px faint. Selected
  cards take the active section-nav treatment.
- Side panel "Readiness" (eyebrow "N of 7 done"): progress bar (8px pill track, accent fill,
  300ms width transition) with the percentage in Sora accent, then a seven-item checklist —
  `circle-check` in success green when done with struck-through muted text, `circle` faint
  when not. Footer: full-width "Go to preview and share".
  Checks: client name and title set · at least one section · estimate has line items ·
  post-launch work planned · timeline has a launch milestone · a package can be recommended ·
  case studies selected.
- Side panel "Internal" (eyebrow "Owner"): description list — Deck owner · Starting point ·
  Last updated · Currency · Client link (Live / Not published yet / Disabled).

---

## 5. Sections tab — full width

- Panel "Presentation order", `flush`, eyebrow "N sections · N hidden", header action
  "Add from library".
- Each section is a 12px-padded row with a hairline beneath, laid out: drag grip
  (`grip-vertical`, faint, `cursor:grab`) · two-digit index in Sora faint · name in 600 with
  a state badge · 12px muted description · right-aligned icon buttons.
  Badges: neutral "Library", warning "Edited for this deck", info "Generated", accent
  "Case study", neutral "Hidden" when hidden.
  Icon buttons: show/hide (`eye` when visible, `lock` when hidden, with a matching title) ·
  duplicate · edit · remove (danger hover).
  Rows are `draggable`; dragging drops the row at the target index and the dragged row sits
  at 40% opacity while in flight.
- Editing a library section marks it "Edited for this deck" and toasts "…is now a
  deck-specific copy. The library entry is unchanged." The library record must not change.
- Empty state (blank deck): dashed panel, `file-text` at 28px, "This deck has no sections
  yet", body about the retainer set, actions "Add a section" and primary "Load the retainer
  set" (which loads sections, estimate, post-launch and timeline together).
- Panel "Reusable sections" (eyebrow "Content library") below the list, with the note
  "Editing a library section here creates a deck-specific copy" in the header, and library
  entries as insert buttons in an `auto-fill, minmax(240px,1fr)` grid — each shows the name
  and a faint meta word, and appends that section to the deck.

**Section library** — Cover · What we do · Service detail · Our process · Past projects
intro · Hosting and support · Call to action · Estimate summary · Standard introduction.

**Retainer set order** — Cover, What we do, Design services, Development services, Support
services, Hosting services, Estimate summary, Recommended support package, Project timeline,
Post-launch work, Our process, Past projects intro, three case studies, Call to action.

---

## 6. Project estimate tab and 7. Post-launch tab — full width

**One component, two data sets.** The tabs share layout, controls and behaviour; only the
data, the eyebrow, the title and the total label differ. Totals stay separate.

- Four stat tiles across the top: Project estimate · Post-launch work · In package
  calculation (accent figure) · Client sees (visible count, with "N hidden · N internal
  notes" beneath).
- Panel, `flush`. Header eyebrow "Project estimate" / "Post-launch work"; title "Work
  required before launch" / "Work planned after launch". Header actions: secondary "Add
  phase", primary "Add line item".
- A 12px legend row above the table: `check` In total · `eye` Shown to client ·
  `shopping-cart` In package calculation.
- Table, fixed layout, columns: grip 26px · Work item (fluid) · Hours 78px right-aligned ·
  Total 56px · Client 56px · Package 62px · actions 84px.
  - **Phase group header row** — sunken background, 8px/24px padding: move-up and move-down
    icon buttons, the phase name in 11px uppercase accent 600, and right-aligned
    "Phase subtotal N hrs" in tabular numerals.
  - **Line item row** — draggable for reorder (dropping into another phase reassigns it):
    - Title: borderless 600-weight input.
    - Description: borderless 12px muted input (client-facing).
    - A "Phase" label plus a compact select of every phase category, including custom ones.
    - Internal note, when present: 12px warning-coloured line prefixed by a `lock` icon,
      reading "Internal: {note}". Never sent to the client.
    - Hours: right-aligned number input, 62px.
    - Three bare switches (40×22 track, accent when on, 200ms thumb): include in total ·
      show to client · include in package calculation. Each has an aria-label.
    - Actions: save to content library (`download`) · duplicate · remove (danger).
  - **Total row**: "Project total" / "Post-launch total" in Sora 600, the figure, and the
    note "Excluded items and hidden items are left out of this figure."
- Slim sunken bar below: "Only items with the package switch on count towards the
  recommendation, so you decide exactly what the support package has to cover." plus a
  "See the recommendation" button.
- Empty state: dashed panel, `file-spreadsheet` at 28px, "No estimate yet" / "No post-launch
  work yet", body, actions "Add a line item" and primary "Load standard phases".

**Default project phases** — Discovery · Research · Strategy · Content · UX and wireframes ·
UI design · Prototyping · Development · Integrations · Migration · QA and testing · Project
management · Launch and deployment · Training and handover.

**Default post-launch phases** — Launch monitoring · Content updates · Performance
optimisation · Search optimisation · Feature improvements · Ongoing development · Support and
maintenance · Reporting and reviews · Training.

"Add phase" creates a named custom phase and seeds one line item in it. Custom phases appear
in every row's phase select.

---

## 8. Timeline tab

- Panel titled by the active mode — "Project weeks" or "Calendar dates". Header action: a
  two-pill segmented control switching modes. Note: "Dates are never derived from estimated
  hours — set them to match the team's real availability."
- Ruler row indented past the label column, showing every fourth week ("Week 1, Week 5, …")
  or the matching date in calendar mode.
- One row per phase: a 236px label column (two-digit index, phase title, a `lock` icon when
  hidden from the client, and a 12px muted range line reading "Weeks 4–7 · Design sign-off"
  or the date equivalent), a 30px sunken track with the phase bar positioned by
  `left = (start-1)/maxWeek` and `width = (end-start+1)/maxWeek` (minimum 3.5%), carrying the
  milestone label or description in 11px white and dropping to 45% opacity when hidden, then
  icon buttons: move up · move down · edit · duplicate · show/hide.
  Bar colours: pre-launch `--bw-brand`, launch `#0A0C29`, post-launch `#8B84EA`.
- Card footer: a legend for the three colours, plus "Add phase".
- Panel "Phase detail", titled with the selected phase: Phase title · Milestone label ·
  Start week · End week · Phase marker (Pre-launch / Launch milestone / Post-launch, with
  help "The launch milestone separates project work from post-launch work.") · Client
  visibility switch · Client-facing description (full width).

**Default timeline** — Discovery and research W1–2 · UX and content W2–4 · UI design W4–7
(milestone "Design sign-off") · Development W7–13 · QA and UAT W13–15 · Launch W15
(milestone "Launch") · Launch monitoring W16–18 · Optimisation and growth W18–26.

Calendar mode counts weeks forward from the deck's prepared date and formats as "8 Sep".

---

## 9. Support package tab

Two-column with a 300px side column.

- **State notice** at the top, tone and copy driven by the recommendation state:
  - success / `circle-check` — "This package covers the calculated work", naming the package,
    its hours and the calculated hours.
  - warning / `triangle-alert` — "This recommendation is a manual override", naming what you
    chose, what the automatic answer was, and that the client sees your choice.
  - danger / `triangle-alert` — "The estimate is above the largest package", naming the total,
    the largest package and its hours, and stating that the deck is flagged for manual review
    and nothing is recommended automatically.
  - danger / `triangle-alert` — "No package can be recommended" when nothing has hours; the
    package section cannot be published until resolved or hidden.
- **Recommendation panel**, titled with the package name (or "Custom package required" /
  "No eligible package"). Header badge: accent "Automatic" / accent "Exact match" / warning
  "Manual override" / danger "Manual review" / danger "Configuration needed".
  Four shadowless figure tiles: In calculation · Package hours · Remaining capacity · Price
  (with "per month · 6 month minimum · GBP" beneath). Then a divider and two fields:
  - Manual override select — "Use the automatic recommendation" plus every package; help
    "An override is marked here but shows to the client as the selected recommendation."
  - Display currency select — Rand (R) / Dollar ($) / Pound (£) / Euro (€); help "Every price
    in this deck, admin and client, is shown in this currency."
  - Full-width "Shown for comparison" — a chip per package, filled with a `check` when on and
    plain with a `plus` when off; help "Alternatives sit alongside the recommendation, with
    less emphasis."
  Footer: the reason sentence on the left, "Edit packages" on the right.
- **Panel "Sorted by included hours"**, `flush`: table of eligible packages — Package (name
  plus commitment beneath) · Hours · Price · Fit badge (danger "Too small", accent
  "Recommended", neutral "Covers the work").
- Side panel "How this was worked out" (eyebrow "Calculation") — the four rule steps as an
  ordered list, with the live figures substituted.
- Side panel "Set-up" (eyebrow "Packages") — note "Packages are set up once and reused by
  every deck", then Packages · Hours set · This deck shows · Price snapshot. Footer:
  "Edit support packages".

---

## 10. Support packages screen

**Purpose** — configure packages manually, once, for every deck.

- Header lede "Set up each package once — hours, prices in every currency, and what the
  client sees." Actions: secondary "Add package", primary "Save packages".
- Info notice: "Every package carries four prices" — "Rand, Dollar, Pound and Euro. Each deck
  picks which one the client sees, so a package is set up once and reused everywhere."
- Warning notice when an eligible package has no hours, naming it, with "A package without
  hours cannot be recommended. Set its hours below, or turn off automatic recommendation
  for it."
- One panel per package, eyebrow "Support package N", title the package name, header badge:
  danger "Hours not set" / warning "N prices missing" / accent "Most popular" / success
  "Ready", plus duplicate and delete icon buttons.
  Fields: Package name · Included hours (number, placeholder "Not set", help switching
  between "Required before this package can be recommended." and "Used by the recommendation
  rule.") · Allowance period · Minimum commitment · Display order · full-width "Price per
  currency" — a four-up `auto-fit, minmax(150px,1fr)` grid, each cell an 11px uppercase
  "Rand · ZAR" label above a 22px symbol and a number input placeholdered "Not set", with the
  help line "A deck can only show a currency that has a price here."
  Two switches: "Eligible for automatic recommendation" · "Most popular".
  Footer: "Display order N · per month" on the left, "Used by every deck that recommends it"
  on the right.

**States to build** — a fully configured package, one missing hours, one missing some
currency prices, and the most-popular flag.

---

## 11. Content library / Case studies / Settings screens

One shared list shape: header with a lede and a primary "Add …" action, then a `flush` panel
whose table has columns Item (name plus note) · Type · Used in · Updated · Actions
(right-aligned, hover-revealed): "Add to deck" (inserts that item into the open deck's
sections; "Edit" on Settings) · Duplicate · Delete.

- **Content library** — service descriptions, benefit lists, process sections, estimate
  line-item presets, timeline phase presets, calls to action, standard introduction copy,
  hosting and support content. Saving an estimate line item from the builder adds a preset here.
- **Case studies** — Hiraste (travel and accommodation), PadlX (sports and community),
  CAN SAKHARA (luxury villa). Each carries project number, name, industry, services, summary,
  website link, desktop/tablet/mobile imagery and an optional accent colour.
- **Settings** — default starting point, who can publish, link format, search engine
  visibility.

---

## 12. Preview and share tab

- Panel "The client experience" (eyebrow "Preview"), header action "Open full size" which
  opens the client deck in a new tab with the deck's currency in the link.
  Body is a `minmax(0,1fr) 220px` grid: a 16:9 desktop frame and a 396px-tall mobile frame,
  each an overflow-hidden 12px/16px-radius box on `#0A0C29` with the real client deck inside,
  scaled by measuring the frame and dividing by the source width — the desktop frame renders
  the deck at 1600px wide and scales down, the mobile frame at 390px. Never let the desktop
  frame drop below the deck's 900px mobile breakpoint. Each frame is labelled in 11px
  uppercase.
- Panel "Share" (eyebrow "Client link"), header badge showing deck status:
  - Read-only link field with a mono value and a Copy button.
  - Help text switching on state: "This link is disabled and returns a not-found page." /
    "No WordPress login needed. Excluded from search engines and sitemaps." / "The link starts
    working when you publish."
  - When password protection is on, a "Deck password" field with its own Copy action and the
    help "Send this separately from the link."
  - Two switches: "Password protection — Asks for a password before the deck loads." and
    "Link enabled — Turning this off returns a not-found page."
  - Footer: ghost "Regenerate link" on the left (new slug, old one dies), then "Save draft"
    and primary "Publish"/"Update deck".
- Side panel "Checks" — the same readiness checklist as Overview.
- Side panel "What is exposed" (eyebrow "Client privacy") — four success-ticked lines: no
  internal notes or hidden line items · no WordPress login needed · excluded from search
  engines and sitemaps · no record IDs in the link.

---

## 13. Client deck — desktop

A presentation, not a page. Fixed dark viewport, one section shown at a time on a 1600×900
stage scaled by `min(vw/1600, (vh-96)/900)` and centred, with 88px of bottom padding for the
navigation. Sections fade and rise 320ms on change.

Navigation, fixed and centred at the bottom: a round previous button, a pill holding one
26×4px dot per section (accent when current, each labelled with its section name), and a
round next button. Keyboard: left/right, Page Up/Down, space, Home, End. The current section
index persists across reloads.

Section order and treatment (alternating dark and light, two background colours only):

1. **Cover** — dark. Two off-canvas circles. Logo on a white plate top-left, "Support
   proposal" top-right. Eyebrow "Integrated", 104px title, 30px supporting line, then
   Prepared for / Date labelled pairs and the section counter.
2. **What we do** — light. 72px heading, 26px intro, logo top-right, then four numbered
   pillar cards on `#F5F6FF` in a 2×2 grid: Website design and development · Secure hosting
   and infrastructure · Long-term digital partnership · Continuous optimisation.
3–6. **Service detail** — dark, one layout reused four times, two columns. Left: eyebrow,
   84px title, 26px lede, an hours pill (accent-tinted, 40px figure plus label) fed by the
   estimate, and a 34px accent strapline. Right: four numbered points on hairline rules, then
   a faint "01 / 04" step marker. The four are Design (30 hrs, "Design-first. Every time."),
   Development (80 hrs, "Built to scale. Built to last."), Support (64 hrs, "Always on.
   Always with you."), Hosting (included, "Fast, secure, always available.").
7. **Estimate summary** — light. Heading left, "Total project estimate" and a 64px figure
   right, then phase rows in two columns: phase name and subtotal on one line, the included
   work items in 18px muted beneath. Footnote: "Estimates cover the work described above. Two
   rounds of revisions are included at each stage." Only client-visible items appear; hidden
   items and internal notes never do.
8. **Recommended support package** — dark. Eyebrow, 60px headline naming the recommended
   package, 24px lede stating the planned hours. Then a `1.25fr 1fr 1fr` grid: the
   recommendation as a solid white card (Recommended eyebrow, "Recommended for you" pill,
   42px name, price plus period, hours included, divider, ticked benefits, commitment) and up
   to two alternatives as translucent outlined cards with less emphasis. No internal
   calculation controls, no override messaging.
9. **Project timeline** — light. Heading, a week ruler, then one row per visible phase: a
   276px label column (title plus range) and a `#F5F6FF` track with the phase bar carrying
   its description. Legend for pre-launch, launch and post-launch, plus "Weeks are indicative
   and confirmed at kick-off."
10. **Post-launch work** — light. Heading and 24px lede left, "Ongoing estimate" and a 64px
    figure right, then six cards in a three-up grid, each with an accent phase label, title,
    description and hours.
11. **Our process** — dark. 72px heading, 26px lede, five columns each with a 2px accent top
    rule, a 44px number, a 30px title and a description: Discovery · Design · Development ·
    Launch · Support. Footnote: "Every project is different — we adapt this process to fit
    your timeline, team and goals."
12. **Past projects** — light. Eyebrow "Selected work", 96px heading, 30px paragraph.
13–14. **Case study** — dark, `0.9fr 1.1fr`. Left: a large translucent project number, 64px
    name, accent sector, service chips, 24px summary, and an accent link with a ↗ prefix.
    Right: a 520px-tall desktop image, with tablet (220×290) and mobile (150×210) images
    stacked beside it.
15. **Call to action** — dark, centred, one large circle behind. Logo plate, 104px "Let's
    build something great.", 30px line naming the client, a white "Get in touch" button and
    the email address.

Every section except the cover carries the client name bottom-left and "07 / 15" bottom-right
at 19–20px in a faint tint of the section's text colour. Empty or disabled fields are omitted
cleanly rather than left as gaps.

---

## 14. Client deck — mobile

Below 900px the deck becomes a single-column readable flow in the same content order — never
a scaled-down slide. 22px side padding, alternating dark and light section blocks.

Order: header block (logo plate, eyebrow, 34px title, 17px lede, Prepared for / Date) ·
What we do (four cards) · Services (four hairline-separated blocks with their hours pills) ·
Project estimate (phase rows, then a dark total block) · Recommended package (white card plus
outlined alternatives on `#F5F6FF`) · Project timeline (vertical, coloured dot per phase) ·
After launch (dark, hairline rows with hours) · Our process (five accent-ruled blocks) ·
Past projects (image card per case study with sector, name, summary and link) · Call to
action (dark, centred, a full-width 52px-tall button).

Headings are 26–34px, body 15–17px at 1.55–1.6 line height, touch targets at least 44px.
