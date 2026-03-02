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
| ✅ | Create `docker/www/skins/stratus/meta.json` | Extends elastic, dark mode, indigo theme-color |
| ✅ | Add `skins/stratus/LICENSE` | Creative Commons Attribution-ShareAlike 3.0 notice |
| ✅ | Add `skins/stratus/README.md` | Credits + CC BY-SA 3.0 notice |
| ✅ | Create `docker/www/skins/stratus/composer.json` | Package metadata |
| ✅ | Create `docker/www/skins/stratus/styles/styles.less` | Imports _variables → elastic → our partials |
| ✅ | Create `docker/www/skins/stratus/styles/_variables.less` | ~110 overrides, full indigo palette |
| ✅ | Create `docker/www/skins/stratus/styles/_layout.less` | Taskmenu, header, panels |
| ✅ | Create `docker/www/skins/stratus/styles/_components.less` | Buttons, lists, badges, scrollbars |
| ✅ | Create `docker/www/skins/stratus/styles/_dark.less` | Supplemental dark rules |
| ✅ | Create `docker/www/skins/stratus/styles/_login.less` | Gradient bg + card form |
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

> Target file: `docker/www/skins/stratus/styles/_calendar.less` (new partial; imported in `styles.less` after elastic).
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
| ✅ | Create plugin directory structure | `plugins/stratus_helper/` + docker-compose mount + Roundcube config |
| ✅ | Create `stratus_helper.php` | Main plugin class — init, appearance injection, AJAX actions, preferences |
| ✅ | Implement `refresh_folders` action | `plugin.stratus.refresh_folders` action clears IMAP cache + `getunread` refresh. Wired in `pagenav.html` `responseaftermove` listener (replaced TODO stub). |
| ✅ | Implement color scheme switching | 8 presets (indigo/ocean/emerald/rose/amber/purple/teal/slate). CSS custom properties injected via `<style id="stratus-helper-vars">`. AJAX `plugin.stratus.set_scheme` for live switching. |
| ✅ | Implement font preference | 7 options (system/inter/roboto/open-sans/lato/poppins/nunito). Google Fonts `<link>` injected server-side. AJAX `plugin.stratus.set_font` for live switching. |
| ✅ | Implement user preferences UI | Settings → Stratus Appearance section with color scheme + font dropdowns. Live preview on change. |
| ✅ | Implement preference persistence | `save_prefs()` for `stratus_color_scheme` + `stratus_font_family`. Respects `dont_override`. |
| ✅ | Add localization support | `localization/en_US.inc` with section/field/scheme/font labels |
| ✅ | Create plugin config | `config.inc.php.dist` with 8 color schemes, 7 fonts, folder refresh toggle |

---

## Conversation Mode Plugin (`plugins/conversation_mode/`)

> Standalone Roundcube plugin — skin-agnostic, works with any skin.
> Spec: `.github/feature-specs/conversation-mode-latest-first.md`

### Phase 1 — MVP

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Plugin scaffold | `conversation_mode.php`, `composer.json`, `config.inc.php.dist` |
| ✅ | Conversation grouping service | Union-find on Message-ID / In-Reply-To / References |
| ✅ | Subject fallback grouping | Normalized subject + time-window heuristic |
| ✅ | Session-based cache layer | Configurable TTL, in-memory + session persistence |
| ✅ | AJAX endpoints | `conv.list`, `conv.open`, `conv.refresh`, `conv.setmode` |
| ✅ | User preference integration | `message_list_mode` in Settings → Mailbox |
| ✅ | Client-side JS | Toggle, conversation list, detail view, pagination |
| ✅ | Skin-agnostic CSS | Default baseline + Elastic overrides |
| ✅ | Localization | English strings (`en_US.inc`) |
| ✅ | v3 Template-binding architecture | `skins/stratus/templates/mail.html` carries conversation containers; JS binds to DOM at init; `data-conv-mode` attr on `#layout-list` drives CSS show/hide (not JS). Plugin's elastic dir has NO `templates/` subdir. |
| 🔲 | Deploy & test in Docker | Install into running Roundcube, verify IMAP grouping |

### Phase 1.5 — UI Overhaul (Outlook-Grade List & Reading Pane)

> **Goal:** Make conversation list rows and message detail feel like Outlook/modern mail clients.
> **Reference:** Roundcube's native `rcube_list_widget` features; Outlook new web app conversation UX.
>
> **What Roundcube's original list provides (must match):**
> - `rcube_list_widget` integration: keyboard nav, multi-select, drag-and-drop, column sort
> - Widescreen 3-column layout (`threads | subject+fromto+date+status | flags`)
> - Flex-wrapped subject cell: subject line + from/to + date + size all in one `td.subject`
> - Status icons via Font Awesome: unread dot, replied, forwarded, replied+forwarded combo
> - Flag toggle on click, attachment icon, thread expand/collapse chevrons
> - Row classes: `.unread` (bold subject), `.flagged` (amber), `.deleted` (strikethrough), `.selected`
> - Hover behavior: date ↔ size swap on hover, flag icon appears on hover (touch)
> - `data-list="message_list"` + ARIA roles for accessibility
> - Sort headers (click column header → sort by that column)
> - Proper page navigation (pagenav.html)
>
> **What Outlook adds beyond Roundcube:**
> - 3-line row: **Line 1** sender + count · **Line 2** subject · **Line 3** preview snippet + timestamp
> - Sender avatar/initials circle (colored) at row left
> - Bold + accent-color dot for unread (not just bold text)
> - Hover action bar (archive, delete, flag, pin) floating on row right
> - Reading pane integration: clicking a conversation shows full thread in `#layout-content` iframe, not replacing the list
> - Collapsible messages in reading pane (latest expanded, older collapsed with one-click expand)
> - Inline reply/forward at bottom of reading pane thread
> - Swipe gestures on mobile (left = delete, right = flag)
> - Conversation-level actions: select-all-in-conversation, move-conversation, mute

#### 1 — Conversation Row Layout (match native `rcube_list_widget`)

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Use `rcube_list_widget` for conv rows | Build conversation rows as proper `rcube_list_widget` entries so keyboard, selection, drag-drop, right-click all work natively. Reuse `add_message_row` pattern with custom col types. |
| ✅ | Widescreen flex row (3-line Outlook style) | Adapt elastic's widescreen template: **Line 1** = sender names + message count badge · **Line 2** = subject (bold if unread) · **Line 3** = snippet (gray) + relative date (right-aligned). Use flex layout in `td.subject` like elastic does. |
| ✅ | Sender avatar / initials circle | Generate colored circle with first-letter initials from sender name. CSS `width:36px; height:36px; border-radius:50%; font-weight:600; text-align:center` in a new `td.conv-avatar` cell. Color derived from sender name hash (19-color deterministic palette). |
| ✅ | Proper status icons (Font Awesome) | Replace emoji flags (📎⚑) with FA icons matching elastic: `.fa-paperclip` (attachment), `.fa-flag` (flagged). Unread indicator is now a dot, not an icon. |
| ✅ | Unread styling: bold + accent dot | Unread conversations get bold sender + bold subject + small colored circle (`.conv-unread-dot`) left of subject, not just a numeric badge. |
| ✅ | Message count badge | Pill badge after sender: `(3)` in muted color, like Outlook's count. Only shown when count > 1. |
| ✅ | Hover action bar | On row hover, show a floating action strip (archive, delete, flag) on the right side over the date. CSS `position:absolute; right:0; display:none → inline-flex on hover`. Icons only, no text. |

> **Architecture note:** Conversation containers live in `skins/stratus/templates/mail.html` (stratus skin override, 267 lines). The plugin has **no** `skins/elastic/templates/` directory. Panel toggling is attribute-driven (`data-conv-mode="list"|"conversations"` on `#layout-list`); CSS rules handle show/hide. JS (v3, 913 lines) calls `resolve_dom()` once at init and only fills/clears pre-existing containers.

#### 2 — Reading Pane Integration (show conversation in `#layout-content`)

| Status | Task | Notes |
|--------|------|-------|
| 🔲 | Load conversation into reading pane | Clicking a conversation row should render messages in `#layout-content` / `#messagecontframe` (the standard reading pane), NOT replace the list. Use `rcmail.show_contentframe()` or inject into iframe. |
| 🔲 | Conversation thread view in reading pane | Render messages as stacked cards (newest first). Latest message expanded, older messages collapsed (show only sender + date + first line). Click to expand/collapse. |
| 🔲 | Message expand/collapse toggle | Each message card has a clickable header. Collapsed = one-line summary. Expanded = full headers + body. Smooth height transition (200ms). |
| 🔲 | Inline reply / forward | At the bottom of the reading-pane thread, add reply/forward action buttons that trigger Roundcube's native compose (`rcmail.command('reply')`). |
| 🔲 | Reading pane empty state | When no conversation is selected, show a branded empty state in the reading pane (matching existing Roundcube watermark pattern). |

#### 3 — Selection, Actions & Context Menu

| Status | Task | Notes |
|--------|------|-------|
| 🔲 | Multi-select support | Checkbox on each row (toggled via click or Ctrl+click). Bulk actions bar appears above list when ≥1 selected. |
| 🔲 | Conversation-level actions | Mark conversation read/unread, flag/unflag, move, delete, archive. These apply to ALL messages in the conversation. Wire to existing Roundcube commands. |
| 🔲 | Right-click context menu | Reuse Roundcube's existing `rcm_messagemenu` popup pattern. Items: Open, Reply, Reply All, Forward, Mark Read/Unread, Flag, Move, Delete. |
| 🔲 | Drag-and-drop | Conversation rows draggable to folder list for move operations. Reuse `rcube_list_widget` drag events. |

#### 4 — Sorting, Search & Pagination

| Status | Task | Notes |
|--------|------|-------|
| 🔲 | Sort controls | Allow sorting conversations by: latest date (default), sender name, subject, unread count. Use Roundcube's sort header click pattern. |
| 🔲 | Search integration | When user searches, conversation mode should filter/group search results into conversations. Wire into Roundcube's `searchform` and `search_filter`. |
| 🔲 | Proper page navigation | Replace custom pagination div with Roundcube's native `pagenav.html` include pattern (`roundcube:object name="pagenavigation"`). Consistent with standard list. |

#### 5 — Mobile & Responsive

| Status | Task | Notes |
|--------|------|-------|
| 🔲 | Touch-friendly rows | Taller row height (≥56px), larger tap targets, no hover-only elements visible on touch. |
| 🔲 | Swipe gestures | Left-swipe = delete (red bg), right-swipe = flag (amber bg). Use `touchstart`/`touchmove`/`touchend`. |
| 🔲 | Mobile conversation detail | Full-screen conversation detail (hide list) on phone layout. Back button returns to list. Use Roundcube's `layout-phone` pattern. |

### Phase 2 — State Correctness

| Status | Task | Notes |
|--------|------|-------|
| 🔲 | Move/delete action sync | Update conversation cache when messages are moved/deleted |
| 🔲 | Mark read/flagged sync | Update aggregated unread/flagged counts |
| 🔲 | Cache invalidation hardening | Incremental refresh via modseq/folder sync |
| 🔲 | DB-backed cache (optional) | Persistent conversation index for large mailboxes |

### Phase 3 — UX Polish

| Status | Task | Notes |
|--------|------|-------|
| 🔲 | Keyboard navigation | Arrow keys, Enter to open, Escape to close. Full `rcube_list_widget` keyboard support should come free from Phase 1.5 §1. |
| 🔲 | Performance optimization | Lazy loading, virtual scrolling for large mailboxes |
| 🔲 | Cross-folder conversations | Optional merge of sent/drafts into conversation |
| 🔲 | Conversation search | Search within conversation mode |
| 🔲 | Additional localizations | Add more languages |

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
