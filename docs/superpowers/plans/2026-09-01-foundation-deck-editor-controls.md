# Foundation: Deck Editor Controls Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the three controls the Deck Builder editor needs — a persistent totals strip, a phase-grouped line-item table, and a timeline gantt — to the shared BlueWorx admin design system and page editor library, so the Deck Builder editor can be built the house way instead of hand-written.

**Architecture:** Two layers, in order. First the CSS patterns land in `.claude/skills/blueworx-admin-design/styles.css` with matching React components and readme entries, because the adherence check reads its vocabulary out of that stylesheet and will reject any class the system does not define. Then the page editor library gains the ability to *use* those patterns: `repeater` learns to group its rows and show subtotals (which covers the line-item builder without a new kind), a new `gantt` kind covers the timeline, and a screen may declare a `summary` strip. Everything is validated in PHP and drawn in `blueworx-page-editor.js`, and CI hash-checks both copies, so nothing here can be done in the plugin instead.

**Tech Stack:** PHP 8.2, PHPUnit 11, plain CSS (no build step), React via `wp.element.createElement` (no JSX, no bundler), Playwright.

**Spec:**
- `docs/superpowers/specs/2026-09-01-deck-builder-screens.md` — §3 (editor shell / totals strip), §6–7 (estimate and post-launch tabs), §8 (timeline tab)
- `docs/superpowers/specs/2026-09-01-deck-builder-logic.md` — derived values, Timeline Phase entity, LineItem entity

**Repo:** Every task in this plan executes in `blueworx-io/bluegroup_core_foundation`, **not** in the Deck Builder plugin repo. Clone it beside the plugin and work on a branch there.

## Global Constraints

- **The design system stylesheet is one self-contained file.** `styles.css` at the skill root. No `@import`, no build step, no minifying. Every plugin copies it verbatim and CI hash-checks the copy.
- **Never write a raw value.** Every colour, size, shadow and font in new CSS uses an existing `--bw-*` token, or declares a new token in the `:root` block first. The adherence check fails a hand-written hex, a bare `px` outside a `@media` query, a hand-written `box-shadow`, and any `font-family` that is not `var(--bw-font-…)`.
- **No hand-drawn SVG.** Icons are Lucide, via `<i class="bw-icon" data-lucide="name">` in plain HTML or `<Icon name="…" />` in React. If an icon is missing from `assets/icons/lucide-icons.js`, copy its geometry from the Lucide repo and note it in the readme.
- **`KINDS` in `Schema.php` is closed on purpose.** Adding to it is the deliberate act this plan performs. `REPEATER_KINDS` may only gain a kind once `Repeater()` in `blueworx-page-editor.js` has a case for it — a test enforces this.
- **British English, sentence case, no exclamation marks, no emoji** in every label, help string, error message and comment.
- **Copy rules for new error messages:** name the fix, never just the fault. Follow the existing shape in `Schema.php`, e.g. `'… which is not a control the design system has. Use one of: %s. If you need something else, add it to the design system first.'`
- **Timeline bar colours:** pre-launch `var(--bw-brand)`, launch `#0A0C29` (declare as `--bw-timeline-launch`), post-launch `#8B84EA` (declare as `--bw-timeline-post`). Source: spec §8.
- **Totals strip value type:** Sora (`--bw-font-display`), 24px, weight 600, line-height 1.2, `font-variant-numeric: tabular-nums`. Source: the prototype's own stylesheet, which is the only rule it needed that the system lacked.
- **The spacing scale is closed, and it has no 14px or 28px step.** It runs 2·4·6·8·10·12·16·20·24·32·40·48 as `--bw-space-1` … `--bw-space-12`. The prototype's summary strip asks for 14px and 28px; **use `--bw-space-7` (16px) and `--bw-space-10` (32px) instead**. The two-pixel difference is invisible, and it keeps the scale closed — which matters more, because a one-off step is how a scale stops being one. Do not "fix" this back to a literal.
- **Token names verified against `styles.css` on 2026-09-01.** The ones this plan uses: `--bw-pad` (24px, the page gutter and panel padding), `--bw-size-xs` (11px, the uppercase label size), `--bw-size-sm` (12px), `--bw-size-body` (13px), `--bw-size-title` (24px), `--bw-track-label` (.08em), `--bw-weight-regular` (400), `--bw-weight-semibold` (600), `--bw-text-on-accent` (white on a filled control), `--bw-radius-xs` (4px), `--bw-radius-sm` (6px), `--bw-control-radius`. There is **no** `--bw-page-gutter`, `--bw-size-label`, `--bw-weight-normal` or `--bw-text-on-brand` — those names do not exist, so do not reach for them.
- **Version and changelog:** the foundation bumps its version and updates `CHANGELOG.md` on the branch, in the PR. Do not push a tag — moving `v1` is a release decision and Luke's alone.

---

## Design decision this plan makes, for review at the approval gate

The obvious reading of the spec is "add three new field kinds: `lineitems`, `gantt`, `sectionlist`." This plan deliberately adds **one** new kind and **two** options on things that already exist, because most of what those three screens need is already in the library:

| Screen need | How this plan meets it | Why not a new kind |
|---|---|---|
| Estimate / post-launch line items — drag reorder, per-row title, description, phase select, hours, internal note, three toggles | `repeater` gains `group_by` and `subtotal_of` options | A repeater already draws every one of those cells, drags rows and duplicates them. Only the phase group header and the subtotal are missing. |
| Sections list — ordered, badged, show/hide, duplicate, edit, remove | `repeater` with a `toggle` cell for visibility, plus a new read-only `badge` repeater cell | Same reasoning. Insert-from-library is plugin behaviour, not a control. |
| Timeline — week ruler, positioned bars, three bar kinds, detail panel | **New kind `gantt`** | Nothing in the library draws a bar against a scale. This one is genuinely new. |
| Totals strip under the editor header | Screen-level `summary` declaration, not a field | It shows derived values and saves nothing, so it is not a field. |

**If you would rather have three explicit kinds, say so at the gate** — it is roughly twice the work in the foundation and gives the plugin less freedom, but it makes each screen's schema read more literally.

---

## File Structure

Every path is relative to the `bluegroup_core_foundation` repo root.

**Design system — the CSS and its React mirror**

| File | Responsibility |
|---|---|
| `.claude/skills/blueworx-admin-design/styles.css` | Modify. Three new blocks — `.bw-summary*`, `.bw-table__group*` / `.bw-table__total*`, `.bw-gantt*` — plus three new `:root` tokens. |
| `.claude/skills/blueworx-admin-design/components/data/SummaryStrip.jsx` `.d.ts` `.prompt.md` | Create. The totals strip. |
| `.claude/skills/blueworx-admin-design/components/data/Gantt.jsx` `.d.ts` `.prompt.md` | Create. The timeline. |
| `.claude/skills/blueworx-admin-design/components/data/data.card.html` | Modify. Add a live sample of each new component to the inventory. |
| `.claude/skills/blueworx-admin-design/components/data/DataTable.prompt.md` | Modify. Document the group header, subtotal and total rows. |
| `.claude/skills/blueworx-admin-design/readme.md` | Modify. Add the three patterns to the component inventory and the "which control for which job" table. |
| `.claude/skills/blueworx-admin-design/_ds_manifest.json` | Modify. Register the two new component names so `rendersDsComponent()` recognises them. |

**Page editor library — validation, storage and rendering**

| File | Responsibility |
|---|---|
| `.claude/skills/blueworx-admin-design/editor/php/v1/Schema.php` | Modify. Add `gantt` to `KINDS`, `badge` to `REPEATER_KINDS`, validate `group_by` / `subtotal_of` on a repeater, validate a screen's `summary` block, and supply defaults for all of it. |
| `.claude/skills/blueworx-admin-design/editor/php/v1/Sanitise.php` | Modify. Clean a `gantt` value (a list of phase rows) and a `badge` cell (read-only, so it is dropped on save). |
| `.claude/skills/blueworx-admin-design/editor/php/v1/Validate.php` | Modify. A gantt phase needs `end >= start`, both `>= 1`; exactly one phase may be `kind = 'launch'`. |
| `.claude/skills/blueworx-admin-design/editor/blueworx-page-editor.js` | Modify. Draw the grouped repeater, the `badge` cell, the gantt control and its detail panel, and the summary strip. |
| `.claude/skills/blueworx-admin-design/editor/php/v1/Screen.php` | Modify. Pass the screen's `summary` block into the bootstrap payload. |

**Tests**

| File | Responsibility |
|---|---|
| `tests/php/SchemaTest.php` | Modify. Accepts `gantt`; rejects a bad `group_by`; rejects a `summary` cell with no label. |
| `tests/php/SanitiseTest.php` | Modify. A gantt value round-trips; a `badge` cell never survives a save. |
| `tests/php/ValidateTest.php` | Modify. Gantt week and launch-milestone rules. |
| `.wp-test/example-plugin/blueworx-editor-example.php` | Modify. Add a grouped repeater, a gantt field and a summary strip to the worked example, so the Playwright suite has something real to drive. |
| `.wp-test/tests/page-editor.spec.js` | Modify. Specs for the three new behaviours. |

---

## Task 1: The totals strip pattern

**Files:**
- Modify: `.claude/skills/blueworx-admin-design/styles.css`
- Create: `.claude/skills/blueworx-admin-design/components/data/SummaryStrip.jsx`
- Create: `.claude/skills/blueworx-admin-design/components/data/SummaryStrip.d.ts`
- Create: `.claude/skills/blueworx-admin-design/components/data/SummaryStrip.prompt.md`
- Modify: `.claude/skills/blueworx-admin-design/components/data/data.card.html`
- Modify: `.claude/skills/blueworx-admin-design/_ds_manifest.json`
- Modify: `.claude/skills/blueworx-admin-design/readme.md`

**Interfaces:**
- Consumes: nothing.
- Produces: the classes `bw-summary`, `bw-summary__cell`, `bw-summary__label`, `bw-summary__value`, `bw-summary__foot`, and the React component `SummaryStrip({ cells })` where `cells` is `Array<{ label: string, value: string, foot?: string }>`. Task 10 renders exactly these classes from the page editor library.

- [ ] **Step 1: Add the CSS block**

Append to `styles.css`, in the data-components section, immediately after the `.bw-stat*` block so related patterns stay together:

```css
/* Summary strip — the persistent band of derived figures under a page header.
   Unlike .bw-stats, which is a grid of cards on the canvas, this is one white
   band flush with the header above it, divided by hairlines. */
.bw-summary{display:flex;flex-wrap:wrap;gap:0;background:var(--bw-surface-card);border-bottom:1px solid var(--bw-border);padding:0 var(--bw-pad)}
.bw-summary__cell{display:flex;flex-direction:column;gap:var(--bw-space-2);min-width:168px;padding:var(--bw-space-7) var(--bw-space-10) var(--bw-space-7) 0;margin-right:var(--bw-space-10);border-right:1px solid var(--bw-border)}
.bw-summary__cell:last-child{border-right:0;margin-right:0}
.bw-summary__label{font-size:var(--bw-size-xs);line-height:1.3;letter-spacing:var(--bw-track-label);text-transform:uppercase;color:var(--bw-text-muted);font-weight:var(--bw-weight-semibold)}
.bw-summary__value{font-family:var(--bw-font-display);font-size:var(--bw-size-title);font-weight:var(--bw-weight-semibold);line-height:var(--bw-lh-tight);color:var(--bw-text-heading);font-variant-numeric:tabular-nums}
.bw-summary__foot{font-size:var(--bw-size-sm);color:var(--bw-text-faint)}
```

The `min-width:168px` is the one literal in this block. It is a wrapping threshold, not a design value — the width below which a cell's figure and footnote stop reading as one column — so declare it as `--bw-summary-min` in the `:root` block rather than leaving it inline, and the adherence rule passes. `line-height:1.3` on the label is likewise raw; the system has `--bw-lh-tight` (1.15) and `--bw-lh-body`. Use `--bw-lh-tight` and accept the small difference rather than adding a third line-height token for one label.

- [ ] **Step 2: Verify the vocabulary picks the new classes up**

The adherence check reads its allowed class list out of this stylesheet, so a class that does not parse is a class that fails every screen using it.

Run, from the foundation root:

The script has to be written into the repo root, not a temp directory — its import of `./scripts/lib/design-system.mjs` is resolved relative to the file, so a scratch copy elsewhere cannot find it.

```bash
cat > ./_vocab.mjs <<'EOF'
import {vocabulary} from './scripts/lib/design-system.mjs';
import {readFileSync} from 'fs';
const skill='.claude/skills/blueworx-admin-design';
const v=vocabulary({css:readFileSync(skill+'/styles.css','utf8'),manifest:JSON.parse(readFileSync(skill+'/_ds_manifest.json','utf8')),markup:readFileSync(skill+'/readme.md','utf8')});
for (const c of ['bw-summary','bw-summary__cell','bw-summary__label','bw-summary__value','bw-summary__foot']) {
  console.log((v.classes.has(c) ? 'ok   ' : 'MISS ') + c);
}
EOF
node ./_vocab.mjs; rm -f ./_vocab.mjs
```

Expected: five `ok` lines. A `MISS` means the selector did not parse — check for a stray newline inside the rule.

- [ ] **Step 3: Write the React mirror**

`components/data/SummaryStrip.jsx`:

```jsx
import React from 'react';

/** The persistent band of derived figures under an editor's page header. */
export function SummaryStrip({ cells = [], className = '' }) {
  if (cells.length === 0) return null;
  return (
    <div className={`bw-summary ${className}`}>
      {cells.map((cell) => (
        <div className="bw-summary__cell" key={cell.label}>
          <span className="bw-summary__label">{cell.label}</span>
          <span className="bw-summary__value">{cell.value}</span>
          {cell.foot ? <span className="bw-summary__foot">{cell.foot}</span> : null}
        </div>
      ))}
    </div>
  );
}
```

`components/data/SummaryStrip.d.ts`:

```ts
export interface SummaryCell {
  label: string;
  value: string;
  foot?: string;
}

export interface SummaryStripProps {
  cells?: SummaryCell[];
  className?: string;
}

export declare function SummaryStrip(props: SummaryStripProps): JSX.Element | null;
```

`components/data/SummaryStrip.prompt.md`:

```md
The band of derived figures that stays put while an editor's tabs change beneath it. Flush
with the page header, hairline underneath, one cell per figure divided by vertical rules.

Figures only. Nothing in this strip is editable, and nothing in it is a link.

```jsx
<SummaryStrip cells={[
  { label: 'Project estimate', value: '232 hrs', foot: '18 line items' },
  { label: 'In package calculation', value: '296 hrs', foot: 'Across both builders' },
]} />
```
```

- [ ] **Step 4: Add it to the inventory and the readme**

In `components/data/data.card.html`, add a live sample using the markup above with two or three cells of invented content.

In `readme.md`, add `SummaryStrip` to the `components/data/` line of the component inventory, and add a row to the "Which control for which job" table:

```md
| Derived figures that stay put while tabs change | `SummaryStrip` |
```

In `_ds_manifest.json`, add `"SummaryStrip"` to the component name list.

- [ ] **Step 5: Commit**

```bash
git add .claude/skills/blueworx-admin-design
git commit -m "Add the summary strip to the admin design system"
```

---

## Task 2: Grouped and totalled table rows

**Files:**
- Modify: `.claude/skills/blueworx-admin-design/styles.css`
- Modify: `.claude/skills/blueworx-admin-design/components/data/DataTable.jsx`
- Modify: `.claude/skills/blueworx-admin-design/components/data/DataTable.d.ts`
- Modify: `.claude/skills/blueworx-admin-design/components/data/DataTable.prompt.md`
- Modify: `.claude/skills/blueworx-admin-design/components/data/data.card.html`

**Interfaces:**
- Consumes: the existing `.bw-table` block.
- Produces: the classes `bw-table__group`, `bw-table__group-title`, `bw-table__group-total`, `bw-table__total`, `bw-table__total-label`, `bw-table__note`, `bw-table__legend`. Task 6 renders `bw-table__group` and `bw-table__group-total` from the grouped repeater.

- [ ] **Step 1: Add the CSS block**

Append to the `.bw-table` block in `styles.css`:

```css
/* A table whose rows fall into named groups — an estimate's phases, say. The
   group header is a full-width row on the sunken surface carrying the group
   name and its subtotal; the total row closes the table off. Both are table
   rows, not headers, so a screen reader reads them in document order with the
   rows they belong to. */
.bw-table__group>td{background:var(--bw-surface-sunken);padding:var(--bw-space-4) var(--bw-pad)}
.bw-table__group-title{font-size:var(--bw-size-xs);letter-spacing:var(--bw-track-label);text-transform:uppercase;font-weight:var(--bw-weight-semibold);color:var(--bw-brand)}
.bw-table__group-total{float:right;font-variant-numeric:tabular-nums;color:var(--bw-text-muted);font-weight:var(--bw-weight-regular);text-transform:none;letter-spacing:normal}
.bw-table__total>td{border-top:1px solid var(--bw-border);padding:var(--bw-space-7) var(--bw-pad)}
.bw-table__total-label{font-family:var(--bw-font-display);font-weight:var(--bw-weight-semibold);color:var(--bw-text-heading)}
.bw-table__note{display:flex;align-items:center;gap:var(--bw-space-3);font-size:var(--bw-size-sm);color:var(--bw-warning-deep)}
.bw-table__legend{display:flex;flex-wrap:wrap;gap:var(--bw-space-7);font-size:var(--bw-size-sm);color:var(--bw-text-muted);padding:var(--bw-space-4) var(--bw-pad)}
```

The internal-note line uses `--bw-warning-deep` because the spec calls it "warning-coloured" — it is a caution to the administrator, not an error, and it never reaches the client.

- [ ] **Step 2: Fold in the cell padding the prototype needed**

The design prototype had to override `.bw-table` cell padding to fit seven columns. That belongs in the system, not in a plugin. In the existing `.bw-table td,.bw-table th` rule, set the horizontal padding to `var(--bw-space-7)` (16px), and give the first cell in each row `padding-left: var(--bw-space-8); padding-right: 0` so a drag grip sits tight against the edge.

Read the current values first and change only the horizontal padding — the vertical rhythm is already right and is used by every existing table. **This rule is shared by every table in every plugin**, so after changing it, open `components/data/data.card.html` and `ui_kits/plugin_admin/index.html` in a browser and check the existing tables still look right. A regression here is invisible in this repo and obvious on four client sites.

- [ ] **Step 3: Verify the vocabulary**

Run the `_vocab.mjs` snippet from Task 1 Step 2, with the seven class names from this task. Expected: seven `ok` lines.

- [ ] **Step 4: Extend the React component**

Add an optional `groups` prop to `DataTable`. When present, each group renders a header row before its rows:

```jsx
{groups.map((group) => (
  <React.Fragment key={group.id}>
    <tr className="bw-table__group">
      <td colSpan={columns.length}>
        <span className="bw-table__group-title">{group.title}</span>
        {group.subtotal != null
          ? <span className="bw-table__group-total">{group.subtotalLabel}</span>
          : null}
      </td>
    </tr>
    {group.rows.map(renderRow)}
  </React.Fragment>
))}
```

Add the matching types to `DataTable.d.ts` and document both new rows in `DataTable.prompt.md`, including the sentence: **"A group's subtotal and the table's total are figures the caller computes — the table never sums anything itself."**

- [ ] **Step 5: Add a sample to the inventory**

In `data.card.html`, add a two-group table with a subtotal on each group and a total row, using invented content.

- [ ] **Step 6: Commit**

```bash
git add .claude/skills/blueworx-admin-design
git commit -m "Let a data table carry named groups, subtotals and a total row"
```

---

## Task 3: The gantt pattern

**Files:**
- Modify: `.claude/skills/blueworx-admin-design/styles.css`
- Create: `.claude/skills/blueworx-admin-design/components/data/Gantt.jsx`
- Create: `.claude/skills/blueworx-admin-design/components/data/Gantt.d.ts`
- Create: `.claude/skills/blueworx-admin-design/components/data/Gantt.prompt.md`
- Modify: `.claude/skills/blueworx-admin-design/components/data/data.card.html`
- Modify: `.claude/skills/blueworx-admin-design/_ds_manifest.json`
- Modify: `.claude/skills/blueworx-admin-design/readme.md`

**Interfaces:**
- Consumes: nothing.
- Produces: the classes `bw-gantt`, `bw-gantt__ruler`, `bw-gantt__tick`, `bw-gantt__rows`, `bw-gantt__row`, `bw-gantt__label`, `bw-gantt__n`, `bw-gantt__title`, `bw-gantt__range`, `bw-gantt__track`, `bw-gantt__bar`, `bw-gantt__bar--pre`, `bw-gantt__bar--launch`, `bw-gantt__bar--post`, `bw-gantt__actions`, `bw-gantt__legend`, `bw-gantt__key`, and the state class `is-hidden`. Also the tokens `--bw-timeline-launch`, `--bw-timeline-post`, `--bw-gantt-label-w`, `--bw-gantt-track-h`, `--bw-gantt-bar-inset`, `--bw-gantt-key-size`, `--bw-gantt-hidden-opacity`. Task 7 renders exactly these from the page editor library.

- [ ] **Step 1: Declare the new tokens**

In the `:root` block of `styles.css`, beside the other colour tokens:

```css
--bw-timeline-launch:#0A0C29;
--bw-timeline-post:#8B84EA;
--bw-gantt-label-w:236px;
--bw-gantt-track-h:30px;
--bw-gantt-bar-inset:3px;
--bw-gantt-key-size:10px;
--bw-gantt-hidden-opacity:.45;
```

`--bw-timeline-launch` is the same ink as `--bw-text-heading`; it is declared separately because it is carrying a bar's meaning here, not text colour, and the two must be free to diverge. `--bw-timeline-post` (`#8B84EA`) is a new colour in the system — a lighter indigo that reads as "the same family, later in time" beside `--bw-brand`. It is the only new hue this plan introduces, and the readme's colour section needs a line for it.

- [ ] **Step 2: Add the CSS block**

```css
/* Gantt — phases positioned against a week scale. The bar's left and width are
   the one thing a caller sets inline, because they are data, not styling: the
   library computes them from each phase's start and end week. Everything else
   about the bar comes from its kind class. */
.bw-gantt{display:flex;flex-direction:column;gap:var(--bw-space-5)}
.bw-gantt__ruler{display:flex;gap:var(--bw-space-6);font-size:var(--bw-size-xs);letter-spacing:var(--bw-track-label);text-transform:uppercase;color:var(--bw-text-faint);font-weight:var(--bw-weight-semibold);padding-left:calc(var(--bw-gantt-label-w) + var(--bw-space-6))}
.bw-gantt__tick{flex:1 1 0;min-width:0}
.bw-gantt__rows{display:flex;flex-direction:column;gap:var(--bw-space-3)}
.bw-gantt__row{display:flex;align-items:center;gap:var(--bw-space-6)}
.bw-gantt__label{width:var(--bw-gantt-label-w);flex:none;display:flex;flex-direction:column;gap:var(--bw-space-1)}
.bw-gantt__n{font-family:var(--bw-font-display);font-size:var(--bw-size-sm);color:var(--bw-text-faint)}
.bw-gantt__title{font-weight:var(--bw-weight-semibold);color:var(--bw-text-heading);font-size:var(--bw-size-body);display:flex;align-items:center;gap:var(--bw-space-3)}
.bw-gantt__range{font-size:var(--bw-size-sm);line-height:var(--bw-lh-body);color:var(--bw-text-muted)}
.bw-gantt__track{flex:1 1 auto;height:var(--bw-gantt-track-h);border-radius:var(--bw-control-radius);background:var(--bw-surface-sunken);border:1px solid var(--bw-border);position:relative}
.bw-gantt__bar{position:absolute;top:var(--bw-gantt-bar-inset);bottom:var(--bw-gantt-bar-inset);border-radius:var(--bw-radius-sm);color:var(--bw-text-on-accent);font-size:var(--bw-size-xs);display:flex;align-items:center;padding:0 var(--bw-space-4);overflow:hidden;white-space:nowrap;background:var(--bw-brand)}
.bw-gantt__bar--pre{background:var(--bw-brand)}
.bw-gantt__bar--launch{background:var(--bw-timeline-launch)}
.bw-gantt__bar--post{background:var(--bw-timeline-post)}
.bw-gantt__bar.is-hidden{opacity:var(--bw-gantt-hidden-opacity)}
.bw-gantt__actions{flex:none;display:flex;gap:var(--bw-space-2)}
.bw-gantt__legend{display:flex;gap:var(--bw-space-7);align-items:center;flex-wrap:wrap;font-size:var(--bw-size-sm);color:var(--bw-text-muted)}
.bw-gantt__key{display:flex;gap:var(--bw-space-3);align-items:center}
.bw-gantt__key::before{content:"";width:var(--bw-gantt-key-size);height:var(--bw-gantt-key-size);border-radius:var(--bw-radius-xs);background:currentColor}
```

Every value above is a token, deliberately — the adherence rule fails a raw `px`, a raw hex and a raw `box-shadow` in any declaration that does not also reference a real token, and this block is copied verbatim into a plugin's stylesheet where it *is* judged. That means Step 1 must also declare `--bw-gantt-bar-inset:3px`, `--bw-gantt-key-size:10px` and `--bw-gantt-hidden-opacity:.45` alongside the four tokens named there. `--bw-text-on-accent` already exists and is white; do not add a second name for it.

- [ ] **Step 3: Verify the vocabulary**

Run the `_vocab.mjs` snippet from Task 1 Step 2 with all seventeen class names. Expected: seventeen `ok` lines.

- [ ] **Step 4: Verify the block does not fail the adherence rules it will be judged by**

The design system exempts itself from `classifyAdminFile`, but every plugin screen that copies this markup is judged. Prove the block is copyable:

```bash
cat > ./_adh.mjs <<'EOF'
import {findViolations} from './scripts/lib/admin-ui.mjs';
import {vocabulary} from './scripts/lib/design-system.mjs';
import {readFileSync} from 'fs';
const skill='.claude/skills/blueworx-admin-design';
const css=readFileSync(skill+'/styles.css','utf8');
const v=vocabulary({css,manifest:JSON.parse(readFileSync(skill+'/_ds_manifest.json','utf8')),markup:readFileSync(skill+'/readme.md','utf8')});
const block=css.slice(css.indexOf('.bw-gantt{'), css.indexOf('.bw-gantt__key::before')+200);
console.log(findViolations({path:'sample.css',kind:'css',content:block,vocab:v}));
EOF
node ./_adh.mjs; rm -f ./_adh.mjs
```

Expected: an empty array once the literals in Step 2 are behind tokens. Any `raw-color` or `raw-size` finding names a literal still to be tokenised.

- [ ] **Step 5: Write the React mirror**

`components/data/Gantt.jsx`:

```jsx
import React from 'react';

const KINDS = { pre: 'bw-gantt__bar--pre', launch: 'bw-gantt__bar--launch', post: 'bw-gantt__bar--post' };

/** Phases positioned against a week scale. `span` is the scale's total width in weeks. */
export function Gantt({ phases = [], span = 1, ticks = [], legend = true, className = '' }) {
  return (
    <div className={`bw-gantt ${className}`}>
      {ticks.length > 0 ? (
        <div className="bw-gantt__ruler">
          {ticks.map((tick) => <span className="bw-gantt__tick" key={tick}>{tick}</span>)}
        </div>
      ) : null}
      <div className="bw-gantt__rows">
        {phases.map((phase, index) => (
          <div className="bw-gantt__row" key={phase.id}>
            <span className="bw-gantt__label">
              <span className="bw-gantt__title">
                <span className="bw-gantt__n">{String(index + 1).padStart(2, '0')}</span>
                {phase.title}
              </span>
              <span className="bw-gantt__range">{phase.range}</span>
            </span>
            <span className="bw-gantt__track">
              <span
                className={`bw-gantt__bar ${KINDS[phase.kind] || KINDS.pre}${phase.visible === false ? ' is-hidden' : ''}`}
                style={{
                  left: `${((phase.start - 1) / span) * 100}%`,
                  width: `${Math.max(3.5, ((phase.end - phase.start + 1) / span) * 100)}%`,
                }}
              >
                {phase.label}
              </span>
            </span>
          </div>
        ))}
      </div>
      {legend ? (
        <div className="bw-gantt__legend">
          <span className="bw-gantt__key" style={{ color: 'var(--bw-brand)' }}>Pre-launch</span>
          <span className="bw-gantt__key" style={{ color: 'var(--bw-timeline-launch)' }}>Launch milestone</span>
          <span className="bw-gantt__key" style={{ color: 'var(--bw-timeline-post)' }}>Post-launch</span>
        </div>
      ) : null}
    </div>
  );
}
```

The three `style` props here are the documented exception: a bar's position is data, and the legend keys carry a token reference rather than a literal, so the adherence rule's `hasHardcodedStyleValue()` passes them. Say exactly that in the prompt file, or the next person will "fix" it into a class and break the positioning.

Write `Gantt.d.ts` with `GanttPhase { id, title, range, start, end, kind: 'pre'|'launch'|'post', label?, visible? }` and `GanttProps { phases, span, ticks, legend, className }`.

- [ ] **Step 6: Add it to the inventory and the readme**

Add a sample to `data.card.html` using the default timeline from the spec (Discovery and research W1–2 · UX and content W2–4 · UI design W4–7 · Development W7–13 · QA and UAT W13–15 · Launch W15 · Launch monitoring W16–18 · Optimisation and growth W18–26). Add `Gantt` to the `components/data/` inventory line in `readme.md`, add `"Gantt"` to `_ds_manifest.json`, and add a job row:

```md
| Phases against a date or week scale | `Gantt` |
```

- [ ] **Step 7: Commit**

```bash
git add .claude/skills/blueworx-admin-design
git commit -m "Add the timeline gantt to the admin design system"
```

---

## Task 4: Design system sync stays green

**Files:**
- Modify: `CHANGELOG.md`

**Interfaces:**
- Consumes: Tasks 1–3.
- Produces: nothing. This is the gate that proves the three CSS tasks did not break every consuming plugin.

- [ ] **Step 1: Run the foundation's own checks**

```bash
node --test scripts/lib/*.test.mjs
```

Expected: all pass. `admin-ui.test.mjs` and `checks.test.mjs` exercise the vocabulary and the adherence rules against the real stylesheet, so a malformed rule surfaces here.

- [ ] **Step 2: Confirm a consuming plugin can still sync**

The Deck Builder plugin already carries a copy of the system at `v1`. Prove the drift is visible and explainable rather than silent:

```bash
cd ../blueworx_labs_deck-builder
FOUNDATION_DIR=../bluegroup_core_foundation FOUNDATION_REF=<this-branch> \
  node ../bluegroup_core_foundation/scripts/check-design-system-sync.mjs
```

Expected: it fails, naming `styles.css` and the two new component files as differing. That is correct and expected — a plugin takes a later design system deliberately, by moving its `--branch` and `foundation_ref` together in one PR. Record the output in the foundation PR description so whoever pulls it knows what to expect.

- [ ] **Step 3: Update the changelog**

Add to `CHANGELOG.md` under `## [Unreleased]`, `### Added`:

```md
- Three admin patterns the deck-style editors needed: a summary strip of derived
  figures, data tables that carry named groups with subtotals, and a gantt for
  phases on a week scale.
```

- [ ] **Step 4: Commit**

```bash
git add CHANGELOG.md
git commit -m "Record the three new admin patterns in the changelog"
```

---

## Task 5: `gantt` becomes a field kind the schema accepts

**Files:**
- Modify: `.claude/skills/blueworx-admin-design/editor/php/v1/Schema.php`
- Test: `tests/php/SchemaTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `Schema::KINDS` contains `'gantt'`; `Schema::defaultForKind()` returns `[]` for it; a gantt field is `wide` by default. Tasks 6, 7 and 8 rely on all three.

- [ ] **Step 1: Write the failing test**

Add to `tests/php/SchemaTest.php`:

```php
public function test_a_gantt_field_is_accepted_and_defaults_to_an_empty_list() {
    $screen = Schema::screen( [
        'slug'        => 'bwx-timeline',
        'title'       => 'Timeline',
        'stores'      => 'option',
        'option_name' => 'bwx_timeline',
        'tabs'        => [ [
            'id'     => 'plan',
            'label'  => 'Plan',
            'panels' => [ [
                'id'     => 'phases',
                'label'  => 'Phases',
                'fields' => [ [ 'id' => 'timeline', 'kind' => 'gantt', 'label' => 'Project timeline' ] ],
            ] ],
        ] ],
    ] );

    $field = $screen['tabs'][0]['panels'][0]['fields'][0];
    $this->assertSame( 'gantt', $field['kind'] );
    $this->assertSame( [], $field['default'] );
    $this->assertTrue( $field['wide'] );
}
```

- [ ] **Step 2: Run it and watch it fail**

```bash
vendor/bin/phpunit --filter test_a_gantt_field_is_accepted
```

Expected: FAIL with `InvalidArgumentException … asks for "gantt", which is not a control the design system has`.

- [ ] **Step 3: Make it pass**

In `Schema.php`:
1. Add `'gantt'` to the `KINDS` array, after `'table'`.
2. In `defaultForKind()`, add `case 'gantt':` to the arm that already returns `[]` for `checkboxes`, `scrolllist`, `tokens` and `repeater`.
3. In `defaultMatchesKind()`, add `case 'gantt':` to the arm that requires an array.
4. In the `$field['wide']` line, add `'gantt'` to the list of kinds that are wide by default. A gantt is never half a row.

- [ ] **Step 4: Run it and watch it pass**

```bash
vendor/bin/phpunit --filter test_a_gantt_field_is_accepted
```

Expected: PASS.

- [ ] **Step 5: Prove a gantt cannot be put inside a repeater**

`REPEATER_KINDS` is unchanged by this task, so this should already hold — assert it so a later widening cannot do it by accident:

```php
public function test_a_gantt_cannot_live_inside_a_repeater() {
    $this->expectException( InvalidArgumentException::class );
    $this->expectExceptionMessageMatches( '/A repeater row can only hold/' );
    Schema::screen( [
        'slug' => 'bwx-bad', 'title' => 'Bad', 'stores' => 'option', 'option_name' => 'bwx_bad',
        'tabs' => [ [ 'id' => 't', 'label' => 'T', 'panels' => [ [ 'id' => 'p', 'label' => 'P', 'fields' => [ [
            'id' => 'rows', 'kind' => 'repeater', 'label' => 'Rows',
            'fields' => [ [ 'id' => 'chart', 'kind' => 'gantt', 'label' => 'Chart' ] ],
        ] ] ] ] ] ],
    ] );
}
```

Run `vendor/bin/phpunit --filter test_a_gantt_cannot_live`. Expected: PASS immediately.

- [ ] **Step 6: Commit**

```bash
git add .claude/skills/blueworx-admin-design/editor/php/v1/Schema.php tests/php/SchemaTest.php
git commit -m "Accept a gantt field in an editor screen schema"
```

---

## Task 6: A gantt value is sanitised and validated

**Files:**
- Modify: `.claude/skills/blueworx-admin-design/editor/php/v1/Sanitise.php`
- Modify: `.claude/skills/blueworx-admin-design/editor/php/v1/Validate.php`
- Test: `tests/php/SanitiseTest.php`
- Test: `tests/php/ValidateTest.php`

**Interfaces:**
- Consumes: Task 5's `'gantt'` kind.
- Produces: a saved gantt value is `array<int, array{id:string,title:string,desc:string,start:int,end:int,milestone:string,kind:string,visible:bool}>`. Task 7 draws exactly this shape, and the Deck Builder plugin reads it.

- [ ] **Step 1: Write the failing sanitise test**

```php
public function test_a_gantt_value_keeps_only_known_phase_columns() {
    $out = Sanitise::field(
        [ 'id' => 'timeline', 'kind' => 'gantt', 'label' => 'Timeline' ],
        [
            [
                'id' => 'p1', 'title' => '<b>Discovery</b>', 'desc' => 'Research and interviews',
                'start' => '1', 'end' => '2', 'milestone' => '', 'kind' => 'pre', 'visible' => '1',
                'internalOnly' => 'should not survive',
            ],
            'not an array',
        ]
    );

    $this->assertCount( 1, $out );
    $this->assertSame( 'Discovery', $out[0]['title'] );
    $this->assertSame( 1, $out[0]['start'] );
    $this->assertSame( 2, $out[0]['end'] );
    $this->assertSame( 'pre', $out[0]['kind'] );
    $this->assertTrue( $out[0]['visible'] );
    $this->assertArrayNotHasKey( 'internalOnly', $out[0] );
}
```

- [ ] **Step 2: Run it and watch it fail**

```bash
vendor/bin/phpunit --filter test_a_gantt_value_keeps_only
```

Expected: FAIL — `Sanitise::field()` falls through to its default arm and returns a string.

- [ ] **Step 3: Make it pass**

Add a `case 'gantt':` arm to `Sanitise::field()`, beside the `repeater` arm it most resembles:

```php
case 'gantt':
    $rows = is_array( $value ) ? $value : [];
    $out  = [];
    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }
        $kind = isset( $row['kind'] ) && in_array( $row['kind'], [ 'pre', 'launch', 'post' ], true )
            ? $row['kind']
            : 'pre';
        $out[] = [
            'id'        => sanitize_key( $row['id'] ?? '' ),
            'title'     => sanitize_text_field( $row['title'] ?? '' ),
            'desc'      => sanitize_text_field( $row['desc'] ?? '' ),
            'start'     => max( 1, (int) ( $row['start'] ?? 1 ) ),
            'end'       => max( 1, (int) ( $row['end'] ?? 1 ) ),
            'milestone' => sanitize_text_field( $row['milestone'] ?? '' ),
            'kind'      => $kind,
            'visible'   => ! empty( $row['visible'] ),
        ];
    }
    return $out;
```

An unknown `kind` falls back to `pre` rather than throwing: a phase that arrives with a nonsense marker is still a real phase, and dropping it would lose the administrator's work.

- [ ] **Step 4: Run it and watch it pass**

```bash
vendor/bin/phpunit --filter test_a_gantt_value_keeps_only
```

Expected: PASS.

- [ ] **Step 5: Write the failing validation tests**

```php
public function test_a_phase_that_ends_before_it_starts_is_rejected() {
    $errors = Validate::field(
        [ 'id' => 'timeline', 'kind' => 'gantt', 'label' => 'Timeline' ],
        [ [ 'id' => 'p1', 'title' => 'Discovery', 'start' => 6, 'end' => 2, 'kind' => 'pre', 'visible' => true ] ]
    );
    $this->assertNotEmpty( $errors );
    $this->assertStringContainsString( 'ends before it starts', $errors[0] );
}

public function test_only_one_phase_may_be_the_launch_milestone() {
    $errors = Validate::field(
        [ 'id' => 'timeline', 'kind' => 'gantt', 'label' => 'Timeline' ],
        [
            [ 'id' => 'p1', 'title' => 'Launch', 'start' => 15, 'end' => 15, 'kind' => 'launch', 'visible' => true ],
            [ 'id' => 'p2', 'title' => 'Relaunch', 'start' => 20, 'end' => 20, 'kind' => 'launch', 'visible' => true ],
        ]
    );
    $this->assertNotEmpty( $errors );
    $this->assertStringContainsString( 'one launch milestone', $errors[0] );
}
```

- [ ] **Step 6: Run them and watch them fail**

```bash
vendor/bin/phpunit --filter "test_a_phase_that_ends|test_only_one_phase"
```

Expected: both FAIL, returning no errors.

- [ ] **Step 7: Make them pass**

Add to `Validate::field()`:

```php
if ( 'gantt' === $field['kind'] ) {
    $errors  = [];
    $launches = 0;
    foreach ( is_array( $value ) ? $value : [] as $phase ) {
        $title = $phase['title'] ?? '';
        if ( (int) ( $phase['end'] ?? 1 ) < (int) ( $phase['start'] ?? 1 ) ) {
            $errors[] = sprintf(
                /* translators: %s: the phase's title. */
                __( '"%s" ends before it starts. Set its end week to the same week or later.', 'blueworx' ),
                $title
            );
        }
        if ( 'launch' === ( $phase['kind'] ?? '' ) ) {
            $launches++;
        }
    }
    if ( $launches > 1 ) {
        $errors[] = __( 'A timeline has one launch milestone. It is what separates project work from post-launch work, so mark exactly one phase as the launch.', 'blueworx' );
    }
    return $errors;
}
```

Match the surrounding file's text domain and translator-comment style rather than copying `'blueworx'` blindly — read a neighbouring string first.

- [ ] **Step 8: Run them and watch them pass**

```bash
vendor/bin/phpunit
```

Expected: the whole suite passes, not just the two new tests.

- [ ] **Step 9: Commit**

```bash
git add .claude/skills/blueworx-admin-design/editor/php/v1 tests/php
git commit -m "Sanitise and validate a gantt field's phases"
```

---

## Task 7: The gantt draws in the browser

**Files:**
- Modify: `.claude/skills/blueworx-admin-design/editor/blueworx-page-editor.js`

**Interfaces:**
- Consumes: Task 3's classes, Task 6's saved value shape.
- Produces: `Control()` returns a gantt for `field.kind === 'gantt'`. Task 9's Playwright spec drives it.

- [ ] **Step 1: Add the control**

In the `switch (field.kind)` inside `Control()`, add a `case 'gantt':` returning `h(GanttField, { field, value: value || [], onChange: set })`.

Write `GanttField` beside `Repeater()`, following that function's conventions exactly — `wp().element.createElement`, no JSX, `props.onChange` receiving a whole new array. It renders:

1. A mode control (`bw-steps` with two buttons, "Project weeks" and "Calendar dates"). Mode is component state, not a saved value — it changes how weeks are *labelled*, never what is stored.
2. A ruler (`bw-gantt__ruler`) with a tick every fourth week up to `span`, where `span = Math.max(...phases.map(p => p.end), 1)`.
3. One `bw-gantt__row` per phase, exactly as `Gantt.jsx` in Task 3 renders it, plus a `bw-gantt__actions` group of six `bw-iconbtn bw-iconbtn--sm` buttons: move up (`chevron-up`), move down (`chevron-down`), edit (`pencil`), duplicate (`copy`), show/hide (`eye` / `lock`), remove (`trash-2`, with `bw-iconbtn--danger`). Every one carries an `aria-label`.
4. A legend (`bw-gantt__legend`) and an "Add phase" button.
5. A detail panel below, editing the selected phase: title, milestone label, start week, end week, phase marker (a select of Pre-launch / Launch milestone / Post-launch, with the help "The launch milestone separates project work from post-launch work."), a client-visibility toggle, and a wide client-facing description.

Calendar mode formats week *n* as `deck date + (n - 1) × 7` in `d MMM` form. The library does not know a deck's date, so read it from `field.origin` — a field option holding an ISO date string, defaulting to today when unset.

- [ ] **Step 2: Add `origin` to the schema**

Back in `Schema.php`, in the gantt handling from Task 5, accept and default the option:

```php
if ( 'gantt' === $field['kind'] ) {
    $field['origin'] = isset( $field['origin'] ) ? (string) $field['origin'] : '';
}
```

Add a test to `SchemaTest.php` asserting `origin` defaults to `''`, run it, and see it pass.

- [ ] **Step 3: Check the file still parses**

```bash
node --check .claude/skills/blueworx-admin-design/editor/blueworx-page-editor.js
```

Expected: no output.

- [ ] **Step 4: Commit**

```bash
git add .claude/skills/blueworx-admin-design/editor
git commit -m "Draw the gantt field in the page editor"
```

---

## Task 8: A repeater can group its rows and subtotal them

**Files:**
- Modify: `.claude/skills/blueworx-admin-design/editor/php/v1/Schema.php`
- Modify: `.claude/skills/blueworx-admin-design/editor/blueworx-page-editor.js`
- Test: `tests/php/SchemaTest.php`

**Interfaces:**
- Consumes: Task 2's `bw-table__group` classes.
- Produces: a repeater field may carry `group_by` (the id of one of its own `select` cells) and `subtotal_of` (the id of one of its own `number` cells). Both are optional; a repeater without them behaves exactly as it does today.

- [ ] **Step 1: Write the failing test**

```php
public function test_a_repeater_may_group_by_one_of_its_own_select_cells() {
    $screen = Schema::screen( [
        'slug' => 'bwx-est', 'title' => 'Estimate', 'stores' => 'option', 'option_name' => 'bwx_est',
        'tabs' => [ [ 'id' => 't', 'label' => 'T', 'panels' => [ [ 'id' => 'p', 'label' => 'P', 'fields' => [ [
            'id' => 'items', 'kind' => 'repeater', 'label' => 'Line items',
            'group_by' => 'phase', 'subtotal_of' => 'hours',
            'fields' => [
                [ 'id' => 'title', 'kind' => 'text', 'label' => 'Work item' ],
                [ 'id' => 'phase', 'kind' => 'select', 'label' => 'Phase', 'options' => [ 'discovery' => 'Discovery' ] ],
                [ 'id' => 'hours', 'kind' => 'number', 'label' => 'Hours' ],
            ],
        ] ] ] ] ] ],
    ] );

    $field = $screen['tabs'][0]['panels'][0]['fields'][0];
    $this->assertSame( 'phase', $field['group_by'] );
    $this->assertSame( 'hours', $field['subtotal_of'] );
}

public function test_grouping_by_a_cell_that_is_not_there_is_rejected() {
    $this->expectException( InvalidArgumentException::class );
    $this->expectExceptionMessageMatches( '/group_by/' );
    Schema::screen( [
        'slug' => 'bwx-bad', 'title' => 'Bad', 'stores' => 'option', 'option_name' => 'bwx_bad',
        'tabs' => [ [ 'id' => 't', 'label' => 'T', 'panels' => [ [ 'id' => 'p', 'label' => 'P', 'fields' => [ [
            'id' => 'items', 'kind' => 'repeater', 'label' => 'Items', 'group_by' => 'nope',
            'fields' => [ [ 'id' => 'title', 'kind' => 'text', 'label' => 'Title' ] ],
        ] ] ] ] ] ],
    ] );
}
```

- [ ] **Step 2: Run them and watch them fail**

```bash
vendor/bin/phpunit --filter "test_a_repeater_may_group|test_grouping_by_a_cell"
```

Expected: the first fails on a missing key, the second fails because no exception is thrown.

- [ ] **Step 3: Make them pass**

In `Schema::field()`, inside the block that already walks a top-level repeater's sub-fields — after `$repeater_scopes[ $field['id'] ] = $sub_seen;`, where every sub-field id is known:

```php
foreach ( [ 'group_by' => 'select', 'subtotal_of' => 'number' ] as $option => $wants ) {
    if ( ! array_key_exists( $option, $field ) || null === $field[ $option ] ) {
        $field[ $option ] = '';
        continue;
    }
    $target = null;
    foreach ( $field['fields'] as $sub_field ) {
        if ( $sub_field['id'] === $field[ $option ] ) {
            $target = $sub_field;
            break;
        }
    }
    if ( null === $target ) {
        throw new InvalidArgumentException( sprintf(
            'The repeater "%s" on the "%s" editor screen sets %s to "%s", which is not one of its own cells. Use the id of a cell inside this repeater.',
            $field['id'], $slug, $option, $field[ $option ]
        ) );
    }
    if ( $target['kind'] !== $wants ) {
        throw new InvalidArgumentException( sprintf(
            'The repeater "%s" on the "%s" editor screen sets %s to "%s", which is a "%s" cell. It has to be a "%s" cell.',
            $field['id'], $slug, $option, $field[ $option ], $target['kind'], $wants
        ) );
    }
    $field[ $option ] = (string) $field[ $option ];
}
```

Set both to `''` for every non-repeater field too, so a consumer can read the key unconditionally.

- [ ] **Step 4: Run them and watch them pass**

```bash
vendor/bin/phpunit
```

Expected: the whole suite passes.

- [ ] **Step 5: Draw the groups**

In `Repeater()` in `blueworx-page-editor.js`: when `props.field.group_by` is set, render rows in group order rather than flat order. Before each group, emit the header row from Task 2:

```js
h('div', { className: 'bw-table__group' },
  h('span', { className: 'bw-table__group-title' }, groupLabel),
  field.subtotal_of
    ? h('span', { className: 'bw-table__group-total' }, subtotal + ' ' + (field.subtotal_suffix || ''))
    : null
)
```

The group label is the `select` cell's own option label, not its stored key. Rows keep their existing drag behaviour, and **dropping a row into another group sets that row's `group_by` cell to the target group's key** — that is what "dropping into another phase reassigns it" means in the spec. A row whose group cell is empty falls into a final group labelled from `field.group_empty_label`, defaulting to "Ungrouped".

Add `subtotal_suffix` and `group_empty_label` as plain string options in Task 8 Step 3's loop, both defaulting to `''` and `'Ungrouped'`.

- [ ] **Step 6: Check the file still parses**

```bash
node --check .claude/skills/blueworx-admin-design/editor/blueworx-page-editor.js
```

Expected: no output.

- [ ] **Step 7: Commit**

```bash
git add .claude/skills/blueworx-admin-design tests/php/SchemaTest.php
git commit -m "Let a repeater group its rows and show a subtotal per group"
```

---

## Task 9: A screen can declare a summary strip

**Files:**
- Modify: `.claude/skills/blueworx-admin-design/editor/php/v1/Schema.php`
- Modify: `.claude/skills/blueworx-admin-design/editor/php/v1/Screen.php`
- Modify: `.claude/skills/blueworx-admin-design/editor/blueworx-page-editor.js`
- Test: `tests/php/SchemaTest.php`

**Interfaces:**
- Consumes: Task 1's `bw-summary` classes.
- Produces: a screen may carry `summary`, an array of `[ 'id' => string, 'label' => string, 'compute' => callable, 'foot' => string ]`. `compute` receives the screen's current values and returns a display string. The strip renders between the page header and the tabs.

- [ ] **Step 1: Write the failing test**

```php
public function test_a_screen_summary_cell_needs_a_label_and_a_compute() {
    $this->expectException( InvalidArgumentException::class );
    $this->expectExceptionMessageMatches( '/summary/' );
    Schema::screen( [
        'slug' => 'bwx-sum', 'title' => 'Sum', 'stores' => 'option', 'option_name' => 'bwx_sum',
        'summary' => [ [ 'id' => 'total' ] ],
        'tabs' => [ [ 'id' => 't', 'label' => 'T', 'panels' => [ [ 'id' => 'p', 'label' => 'P',
            'fields' => [ [ 'id' => 'x', 'kind' => 'text', 'label' => 'X' ] ] ] ] ] ],
    ] );
}
```

- [ ] **Step 2: Run it and watch it fail**

```bash
vendor/bin/phpunit --filter test_a_screen_summary_cell_needs
```

Expected: FAIL — no exception thrown, because `summary` is currently ignored.

- [ ] **Step 3: Make it pass**

In `Schema::screen()`, after the tabs are walked:

```php
$screen['summary'] = [];
foreach ( $raw['summary'] ?? [] as $cell ) {
    if ( ! is_array( $cell ) || empty( $cell['id'] ) || empty( $cell['label'] ) ) {
        throw new InvalidArgumentException( sprintf(
            'A summary cell on the "%s" editor screen needs an id and a label. A summary is the strip of derived figures under the header; every cell in it is labelled.',
            $slug
        ) );
    }
    if ( ! isset( $cell['compute'] ) || ! is_callable( $cell['compute'] ) ) {
        throw new InvalidArgumentException( sprintf(
            'The summary cell "%s" on the "%s" editor screen has no compute callback. A summary cell shows a figure worked out from the screen\'s values, so it needs one.',
            $cell['id'], $slug
        ) );
    }
    $screen['summary'][] = [
        'id'      => sanitize_key( $cell['id'] ),
        'label'   => (string) $cell['label'],
        'compute' => $cell['compute'],
        'foot'    => isset( $cell['foot'] ) ? (string) $cell['foot'] : '',
    ];
}
```

- [ ] **Step 4: Run it and watch it pass**

```bash
vendor/bin/phpunit --filter test_a_screen_summary_cell_needs
```

Expected: PASS.

- [ ] **Step 5: Put the computed cells in the bootstrap payload**

In `Screen.php`, where the payload is assembled, call each cell's `compute` with the screen's current values and add the result:

```php
$summary = [];
foreach ( $screen['summary'] as $cell ) {
    $summary[] = [
        'id'    => $cell['id'],
        'label' => $cell['label'],
        'value' => (string) call_user_func( $cell['compute'], $values ),
        'foot'  => $cell['foot'],
    ];
}
```

The callback is never serialised — only its result reaches the browser.

- [ ] **Step 6: Render it, and keep it live**

In `blueworx-page-editor.js`, render the strip from Task 1's markup between the page header and `bw-tabs`. Recompute on every value change by calling the existing save/read REST route in `preview` mode — or, simpler and the recommendation here: have `compute` also be expressed as a small declarative sum the browser can repeat (`{ sum: 'items.hours', where: 'items.inTotal' }`) so the strip updates without a round trip. The spec requires "the strip updates live as hours change", which a round trip per keystroke would not deliver.

Decide between the two at implementation time and record the choice in `Screen.php`'s docblock.

- [ ] **Step 7: Check the file still parses, and run the suite**

```bash
node --check .claude/skills/blueworx-admin-design/editor/blueworx-page-editor.js
vendor/bin/phpunit
```

Expected: no output, then all tests pass.

- [ ] **Step 8: Commit**

```bash
git add .claude/skills/blueworx-admin-design tests/php/SchemaTest.php
git commit -m "Let an editor screen declare a summary strip of derived figures"
```

---

## Task 10: The worked example exercises all three, end to end

**Files:**
- Modify: `.wp-test/example-plugin/blueworx-editor-example.php`
- Modify: `.wp-test/tests/page-editor.spec.js`

**Interfaces:**
- Consumes: Tasks 5–9.
- Produces: the proof. Foundation CI runs this suite, so a break in any of the three controls blocks a merge from here on.

- [ ] **Step 1: Extend the example plugin's schema**

Add a "Delivery" tab to the example screen carrying:
- a grouped repeater `items` with cells `title` (text), `phase` (select of Discovery / Design / Development), `hours` (number), `inTotal` (toggle), `showClient` (toggle), `inPackage` (toggle), `group_by => 'phase'`, `subtotal_of => 'hours'`, `subtotal_suffix => 'hrs'`
- a `gantt` field `timeline`
- a screen `summary` with two cells: "Estimated hours" summing `items.hours` where `inTotal`, and "Phases" counting `timeline`

- [ ] **Step 2: Write the failing specs**

Add to `.wp-test/tests/page-editor.spec.js`, following the existing `freshScreen()` fixture pattern in that file:

```js
test('a grouped repeater shows one header per phase, with its subtotal', async ({ page }) => {
  await page.goto(await freshScreen(page));
  await page.getByRole('tab', { name: 'Delivery' }).click();

  const groups = page.locator('.bw-table__group-title');
  await expect(groups).toHaveText(['Discovery', 'Design', 'Development']);
  await expect(page.locator('.bw-table__group-total').first()).toContainText('hrs');
});

test('a phase bar is positioned from its start and end week', async ({ page }) => {
  await page.goto(await freshScreen(page));
  await page.getByRole('tab', { name: 'Delivery' }).click();

  const bar = page.locator('.bw-gantt__bar').first();
  await expect(bar).toBeVisible();
  const left = await bar.evaluate((el) => el.style.left);
  expect(left).toMatch(/^\d/);
});

test('the summary strip updates as hours change, without a save', async ({ page }) => {
  await page.goto(await freshScreen(page));
  await page.getByRole('tab', { name: 'Delivery' }).click();

  const total = page.locator('.bw-summary__cell', { hasText: 'Estimated hours' }).locator('.bw-summary__value');
  const before = await total.textContent();
  await page.locator('input[type=number]').first().fill('40');
  await expect(total).not.toHaveText(before);
});
```

- [ ] **Step 3: Run them and watch them fail**

```bash
npm install --no-save --prefix .wp-test/.pw @playwright/test
.wp-test/.pw/node_modules/.bin/playwright install chromium
node scripts/wp-test-env.mjs up --plugin .wp-test/example-plugin
PLAYWRIGHT_BASE_URL=http://127.0.0.1:8881 WP_ADMIN_USER=admin WP_ADMIN_PASS=wptest-admin-pw \
  .wp-test/.pw/node_modules/.bin/playwright test --workers=1
```

Expected: the three new specs fail; the existing ones pass.

- [ ] **Step 4: Fix whatever they catch, and re-run until green**

Re-run the command from Step 3. Expected: every spec passes. Then `node scripts/wp-test-env.mjs down`.

- [ ] **Step 5: Update the changelog and the readme**

Add to `CHANGELOG.md` under `## [Unreleased]`, `### Added`:

```md
- Editor screens can group a repeater's rows under a subtotal, draw a timeline
  of phases, and show a strip of derived figures that stays put as tabs change.
```

In the design system readme's "Custom editor screens" section, add the three to the editor-controls table:

```md
| Line items that fall into named groups, each with a subtotal | `repeater` with `group_by` and `subtotal_of` |
| Phases on a week or date scale | `gantt` |
| Derived figures under the header, live as values change | the screen's `summary` |
```

- [ ] **Step 6: Commit and open the pull request**

```bash
git add .
git commit -m "Exercise the grouped repeater, gantt and summary strip in the worked example"
git push -u origin <branch>
gh pr create --base main --title "Add the deck editor's three missing controls"
```

The PR body says what the three controls are, that Deck Builder is the first consumer, and that the design system sync check will now fail for every plugin still pinned to the older ref until each one deliberately moves — which is the documented behaviour, not a regression.

**Do not push a tag.** Moving `v1` onto this work is Luke's decision, and Deck Builder cannot pin the new ref until he makes it.

---

## Self-Review

**Spec coverage.** §3's totals strip is Tasks 1 and 9. §6–7's phase-grouped line-item table with subtotals, three switches per row and a total is Tasks 2 and 8 — the three switches are plain `toggle` cells a repeater already draws, and the internal-note line is a `textarea` cell styled by `bw-table__note`. §8's timeline is Tasks 3, 5, 6 and 7, including weeks/dates mode, the three bar kinds and the detail panel.

**Two gaps I am leaving open deliberately**, both belonging to the plugin plan rather than this one:
- **"Save to content library" on a line item** is plugin behaviour — the library has no concept of a content library, and giving it one would be inventing a feature for one consumer.
- **Insert-from-library on the sections list**, for the same reason. The sections list itself is a `repeater` with a `toggle` cell; the badge showing "Library" / "Edited for this deck" is derived from the row's own data, and if it turns out to need a real control, that is a small follow-up to Task 8, not a blocker.

**Type consistency.** The gantt phase shape is defined once in Task 6 Step 3 and used unchanged in Task 7's renderer and Task 10's spec. `group_by` and `subtotal_of` are named identically in Task 8's schema, renderer and test. `SummaryStrip`'s cell shape (`label`, `value`, `foot`) is identical in Task 1's React component, Task 9's PHP payload and Task 10's spec.

**One thing the implementer must decide, not guess:** Task 9 Step 6 offers two ways to keep the summary strip live. Take the declarative one unless it proves unworkable, and write down why in the docblock either way.
