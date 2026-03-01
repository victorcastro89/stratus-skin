# Project Roadmap

> Master backlog and progress tracker. Agents check completed items and pick up next tasks.
> Mark items ✅ when done, 🟡 when in-progress, 🔲 when not started, ❌ when blocked.

---

## Phase 0 — AI Agent Infrastructure

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Create `copilot-instructions.md` | Global context + primary workflow |
| ✅ | Create `DEV_GUIDE.md` | Developer guide for AI workflow |
| ✅ | Create `memory/` files | decisions.md, context.md, roadmap.md |
| ✅ | Create `agents/builder.agent.md` | **Primary agent** — full build cycle |
| ✅ | Create `agents/stylist.agent.md` | Specialist: colors, typography |
| ✅ | Create `agents/templater.agent.md` | Specialist: Roundcube templates |
| ✅ | Create `agents/plugin-dev.agent.md` | Specialist: PHP plugin (Phase 2) |
| ✅ | Create 4 instruction files | LESS, templates, PHP, memory rules |
| ✅ | Create 4 skill knowledge bases | Elastic, templates, colors, LESS build |
| ✅ | Create 4 prompt files | build-next, compile-and-validate, add-color-variant, override-template |

---

## Phase 1 — Skin Foundation

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Create `roundcubemail/skins/stratus/meta.json` | Extends elastic, dark mode, indigo theme-color |
| ✅ | Add `skins/stratus/LICENSE` | Creative Commons Attribution-ShareAlike 3.0 notice |
| ✅ | Add `skins/stratus/README.md` | Credits + CC BY-SA 3.0 notice |
| ✅ | Create `roundcubemail/skins/stratus/composer.json` | Package metadata |
| ✅ | Create `roundcubemail/skins/stratus/styles/styles.less` | Imports _variables → elastic → our partials |
| ✅ | Create `roundcubemail/skins/stratus/styles/_variables.less` | ~110 overrides, full indigo palette |
| ✅ | Create `roundcubemail/skins/stratus/styles/_layout.less` | Taskmenu, header, panels |
| ✅ | Create `roundcubemail/skins/stratus/styles/_components.less` | Buttons, lists, badges, scrollbars |
| ✅ | Create `roundcubemail/skins/stratus/styles/_dark.less` | Supplemental dark rules |
| ✅ | Create `roundcubemail/skins/stratus/styles/_login.less` | Gradient bg + card form |
| ✅ | Compile `styles.min.css` | ~128KB, 0 errors |
| ✅ | Create `templates/includes/layout.html` | Injects stratus CSS link in `<head>` |
| ✅ | Create `templates/login.html` | Inherits elastic login |
| ✅ | Create `watermark.html` | Indigo gradient branding page |
| ✅ | Choose primary color palette | Indigo #5c6bc0 (light) / #7986cb (dark) |
| ✅ | Define dark mode color palette | Navy #1e2432 bg, indigo-300 accents |

---

## Phase 1.5 — Visual Polish

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Custom taskmenu styling | Navy gradient, pill nav, indigo glow selected, gradient compose FAB, top accent line |
| ✅ | Custom toolbar styling | Frosted glass header (backdrop-filter), ghost buttons with indigo hover |
| ✅ | Custom message list styling | Hair-thin separators, card-hover elevation, unread indigo accent, flagged amber glow |
| ✅ | Custom message view styling | Reading pane headers, attachment chips as pills |
| ✅ | Login page visual design | Animated mesh gradient, frosted glass card, dot pattern, dark mode |
| ✅ | Typography adjustments | System-ui font stack, 5 weights, heading hierarchy, letter-spacing |
| ✅ | Icon customizations | Indigo hover tint, smooth transitions on all icons |
| ✅ | Scrollbar styling | 5px semi-transparent capsules, transparent track, thin Firefox |
| ✅ | Animation/transition polish | 150ms transitions on everything, 7 keyframe anims, prefers-reduced-motion |
| ✅ | Calendar ghost grid declutter | Removed vertical lines, faded horizontals, today pill, rounded events, dark mode |
| ✅ | Create `thumbnail.png` | 320×240 preview for skin selector — generated via `scripts/generate-thumbnail.js` (`npm run thumbnail`); indigo/navy stratus layout |

---

## Phase 1.6 — Calendar UI Improvements

> Target file: `roundcubemail/skins/stratus/styles/_calendar.less` (new partial; imported in `styles.less` after elastic).
> All rules scoped under `.task-calendar` to avoid bleed into other views. Dark mode rules added in `_dark.less`.

### 1 — Grid Declutter

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Soft hour-marker lines | Set `.fc-timegrid-slot` border to `1px solid #F0F0F0` (dark: `rgba(255,255,255,.05)`). Remove sub-hour (30 min) lines entirely via `.fc-timegrid-slot-minor { border: none }`. Keep full-hour lines only. |
| ✅ | Remove all vertical column dividers | Set `.fc-timegrid-col` and `.fc-day` column borders to `none`; rely on background contrast between columns instead. |
| ✅ | Floating event cards | Target `.fc-event`: `border-radius: 6px`, `box-shadow: 0 2px 6px rgba(0,0,0,.15)`, remove hard `border`, use `background` + left `border-left: 3px solid <category-color>` accent strip only. Add `padding: 3px 6px`. |
| ✅ | Event card hover lift | On `.fc-event:hover`: `box-shadow: 0 4px 14px rgba(0,0,0,.22)`, `transform: translateY(-1px)`, `transition: 150ms`. |

### 2 — Header & Navigation

| Status | Task | Notes |
|--------|------|-------|

| ✅ | Dynamic date display in header | `.fc-center h2` → `1.25rem/bold/tabular-nums`. Phase 2 JS will split into day-label/number sub-spans. |
| ✅ | Consolidated Toolbar | Create is now primary inline CTA; Print/Import/Export are consolidated into the `toolbar-menu` popup trigger (More button). **Tier B template** — Create lives in `#mp-cal-actions` (outside `.toolbar.menu`) so `toolbar_init()` never detaches it. Fixes header layout-shift on popover open. |
### 3 — Sidebar

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Datepicker promoted to sidebar top | Moved `#datepicker` above `.header` in `#layout-sidebar` template. Flex column layout with order-based positioning. Full-width embedded widget style, dark mode support. |
| 🔲 | Collapsible sidebar (mini-calendar) | Add a `mp-cal-sidebar-toggle` button (chevron icon) to the sidebar header. CSS: sidebar collapsed state hides `.mp-cal-sidebar` with `width:0; overflow:hidden; transition: width 200ms ease`. Main grid expands via flex. JS toggle sets `data-collapsed` attribute. |
| ✅ | High-contrast today in mini-cal | Implemented in `#datepicker .ui-datepicker td.ui-datepicker-today` (26×26 circular chip, primary bg, strong contrast text, shadow) with dark variant in `_dark.less`. |
| ✅ | Mini-cal hover state | Implemented hover chip for non-today mini-cal dates (`fadeout(@color-main, 90%)`, circle radius, transition) with dark variant in `_dark.less`. |

### 4 — Color & Lightness

| Status | Task | Notes |
|--------|------|-------|
| 🔲 | Current-time indicator line | Target `.fc-timegrid-now-indicator-line`: `border-color: #e53935; border-width: 2px`. Target `.fc-timegrid-now-indicator-arrow`: replace with a `6px` filled circle (`width:10px; height:10px; border-radius:50%; background:#e53935; margin-top:-4px`). Remove any full-day yellow background shading (`.fc-day-today { background: none }`). |
| 🔲 | Accent color refresh for calendar | Define LESS vars: `@cal-accent: #3d5afe` (Electric Blue) and `@cal-accent-dark: #7c4dff` (Deep Purple for dark mode). Apply to: selected day column bg (`rgba(@cal-accent,.07)`), selected event border, today column header text, active view-switcher tab. |
| 🔲 | Dark mode calendar overrides | In `_dark.less` under `html.dark-mode .task-calendar`: grid bg `#1e2432`, slot lines `rgba(255,255,255,.05)`, event card shadow `0 2px 8px rgba(0,0,0,.5)`, today indicator `#ef5350`, accent `@cal-accent-dark`. |

### 5 — Dev Environment

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Seed dev user in entrypoint | Adds `victor@example.test` to SQLite if missing |

---

## Phase 2 — Companion Plugin (`stratus_helper`)

| Status | Task | Notes |
|--------|------|-------|
| 🔲 | Create plugin directory structure | `roundcubemail/plugins/stratus_helper/` |
| 🔲 | Create `stratus_helper.php` | Main plugin class |
| 🔲 | Implement color scheme switching | Runtime color changes via user prefs |
| 🔲 | Implement font preference | Google Fonts integration |
| 🔲 | Implement user preferences UI | Settings panel for skin options |
| 🔲 | Implement preference persistence | Save to Roundcube DB |
| 🔲 | Add localization support | Multi-language strings |
| 🔲 | Create plugin config | `config.inc.php.dist` |

---

## Phase 3 — Advanced Features

| Status | Task | Notes |
|--------|------|-------|
| 🔲 | Multiple color presets | Pre-defined color schemes |
| 🔲 | Custom background images | User-selectable backgrounds |
| 🔲 | Compact/comfortable density modes | Layout density options |
| 🔲 | Custom logo support | Per-installation logo override |
| 🔲 | RTL language support | Right-to-left layout adjustments |
| 🔲 | Print stylesheet | Optimized print styles |
| 🔲 | Accessibility audit | WCAG 2.1 AA compliance |
| 🔲 | Performance audit | CSS size, render performance |
| 🔲 | Documentation | User guide, admin guide |
| 🔲 | Release packaging | Zip, versioning, changelog |

---

## Bugs / Issues Found

| Status | Issue | Found By | Notes |
|--------|-------|----------|-------|
| ✅ | LESS `@color-main` override not working — elastic cyan `#37beff` used instead of indigo `#5c6bc0` | user/builder | Fixed: moved `@import "_variables"` to after elastic import in `styles.less` (LESS lazy eval: last def wins) |
| ✅ | About/logout buttons overlapping compose in light mode | user/builder | Fixed: removed `position:relative` from `#taskmenu`. Moved to `#layout-menu`. |
| ✅ | Datepicker select FOUC (white flash on load) | user/builder | Fixed: pre-applied `appearance:none` + `background` + `border` + `color` on `.ui-datepicker .ui-datepicker-header select` in `_components.less`. Dark mode `transition:none` in `_dark.less`. |
| ✅ | Datepicker arrow jumps from left→right on load | user/builder | Fixed: used full `background` shorthand with Bootstrap SVG arrow at `right .75rem center/8px 10px` (was using `background-color` only — no arrow until JS ran). |
| ✅ | Datepicker text jumps on load | user/builder | Fixed: added full `padding: 0.25rem 1.75rem 0.25rem 0.75rem` shorthand + `font-size: @mp-font-size-base` to pre-apply rule (missing `padding-left` and `font-size` caused layout shift when `.form-control` was added). |
| ✅ | Calendar header shifts when More menu clicked | user/builder | Root cause: CSS-only (Tier A) approach forced `#toolbar-menu` visible in header flex; elastic JS detached it on popover open → layout reflow. Fix: Tier B template override — Create button moved to `#mp-cal-actions` outside `.toolbar.menu` so it's never detached. |

---

## Design Inspiration / References

- Elastic default: Clean, blue (#37beff), material-ish
- Outlook+: Professional blue (#0075c8), inverted header, no taskbar icons
- Gmail+: Red accent (#b0263b), material icons, compact
- Target: Modern, professional, distinct identity — NOT a clone of any existing skin
