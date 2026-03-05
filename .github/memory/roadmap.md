# Project Roadmap

> Master backlog and progress tracker. Agents check completed items and pick up next tasks.
> Mark items ✅ when done, 🟡 when in-progress, 🔲 when not started, ❌ when blocked.

---

## Phase 0 — AI Agent Infrastructure

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Create `copilot-instructions.md` | Global context + primary workflow |
| ✅ | Create `DEV_GUIDE.md` | Developer guide for AI workflow |
| ✅ | Create `memory/` files | context.md, roadmap.md (decisions consolidated into context.md) |
| ✅ | Create `agents/builder.agent.md` | **Primary agent** — full build cycle |
| ✅ | Create `agents/stylist.agent.md` | Specialist: colors, typography |
| ✅ | Create `agents/templater.agent.md` | Specialist: Roundcube templates |
| ✅ | Create `agents/plugin-dev.agent.md` | Specialist: PHP plugin (Phase 2) |
| ✅ | Create 4 instruction files | LESS, templates, PHP, memory rules |
| ✅ | Create 4 skill knowledge bases | Consolidated into agent files (elastic, templates, colors, LESS build) |
| ✅ | Create 4 prompt files | build-next, compile-and-validate, add-color-variant, override-template |

---

## Phase 1 — Skin Foundation

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Create `skins/stratus/meta.json` | Extends elastic, dark mode, indigo theme-color |
| ✅ | Add `skins/stratus/LICENSE` | Creative Commons Attribution-ShareAlike 3.0 notice |
| ✅ | Add `skins/stratus/README.md` | Credits + CC BY-SA 3.0 notice |
| ✅ | Create `skins/stratus/composer.json` | Package metadata |
| ✅ | Create `skins/stratus/styles/styles.less` | Imports elastic first, then stratus partials |
| ✅ | Create `skins/stratus/styles/_variables.less` | ~180+ overrides, full indigo palette |
| ✅ | Create `skins/stratus/styles/_layout.less` | Taskmenu, header, panels |
| ✅ | Create `skins/stratus/styles/_components.less` | Buttons, lists, badges, scrollbars |
| ✅ | Create `skins/stratus/styles/_dark.less` | Supplemental dark rules |
| ✅ | Create `skins/stratus/styles/_login.less` | Gradient bg + card form |
| ✅ | Compile `styles.min.css` | ~189KB, compiles successfully |
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

> Target file: `skins/stratus/styles/_calendar.less` (imported in `styles.less` after elastic).
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

## Mail List UX Polish (Dogfood Session — 2026-03-05)

> Dogfood pass on `alice@example.test` inbox. 6 issues identified and fixed.

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Unread badge pill shape | `_components.less` + `_dark.less`: override elastic's `.folderlist li.mailbox .unreadcount { border-radius: 0.4em }` with `@mp-radius-pill` (12px). Both light + dark modes fixed. |
| ✅ | Hide duplicate conv-toggle in content pane | `_components.less`: `#layout-content .toolbar a.button.conv-toggle { display: none !important; }` — elastic adds a disabled copy in the message toolbar; hide it. |
| ✅ | Add icon to conv-toggle button | `_components.less`: `::before { content: \"\\f086\"; font-family: 'Icons'; font-weight: 900; }` — fa-comments icon, matches other toolbar buttons. |
| ✅ | Always show date in message list | `_components.less`: `span.date { display: block !important; }` + `span.size { display: none !important; }` inside `td.subject`. Fixes elastic hover rule that hides date. |
| ✅ | Dark mode badge correct color | `_dark.less`: explicit `background: @color-dark-list-badge-background; color: @color-dark-list-badge; border-radius: @mp-radius-pill` to override elastic dark's `#4d6066` grey. |
| ✅ | Fix JS TypeError on message click | `conversation_mode.js`: changed `load_message_preview()` and the open-link fallback to use `rcmail.get_frame_window()` instead of `rcmail.get_frame_element()`. `location_href()` needs a Window object; `get_frame_element` returns an HTMLIFrameElement. All verified `window._testErrors = []`. |

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
| ✅ | Skin-agnostic CSS | Default baseline + Elastic overrides. MVP bug-fix pass resolved icon/pagination regressions; dark-mode polish remains in backlog. |
| ✅ | Localization | English strings (`en_US.inc`) |
| ✅ | v3 Template-binding architecture | Template + JS binding stabilized (pagination/toggle/empty-state regressions fixed in MVP bug-fix pass). |
| 🔲 | Deploy & test in Docker | Install into running Roundcube, verify IMAP grouping |
| ✅ | **MVP bug-fix pass** | Fixed all 8 bugs: icons, snippets, pagination, unread, toggle, Open button, empty icon, flagged border |

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

> **Architecture note:** Conversation containers live in `skins/stratus/templates/mail.html` (stratus skin override). The plugin has **no** `skins/elastic/templates/` directory. Panel toggling is attribute-driven (`data-conv-mode="list"|"conversations"` on `#layout-list`); CSS rules handle show/hide. JS calls `resolve_dom()` once at init and only fills/clears pre-existing containers.

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

## LESS Architecture — Tech Debt

> Foundational refactors to improve UI consistency, maintainability, and DRY-ness of the stratus LESS codebase.
> These should be tackled before major new feature work to reduce merge friction and cascade bugs.
>
> **Key files:** `skins/stratus/styles/` — all `.less` partials.
> **Reference:** Elastic's `roundcubemail/skins/elastic/styles/` structure (especially `widgets/` and `mixins.less`).

### 1 — Split `_components.less` into `widgets/` Directory

> **Problem:** Stratus puts all component overrides in a single `_components.less` (~hundreds of rules). Elastic splits into 9 focused files under `styles/widgets/` (`buttons.less`, `lists.less`, `dialogs.less`, `forms.less`, `menu.less`, `messages.less`, `common.less`, `editor.less`, `jqueryui.less`). The monolith makes it hard to find/modify a specific component, risks cascade side effects, has no ownership boundary per UI element, and is harder to review in PRs.
>
> **Remedy:** Create `skins/stratus/styles/widgets/` directory mirroring Elastic's structure. Extract rules from `_components.less` into per-widget files. Update `styles.less` imports to pull from `widgets/` instead of `_components.less`. Preserve the existing `_components.less` as an empty barrel file (or remove it) to avoid import breakage.
>
> **Implementation notes:**
> - Map each block in `_components.less` to the corresponding Elastic widget file by CSS selector (e.g. `.btn`, `#toolbar` → `buttons.less`; `.listing`, `#message-list` → `lists.less`; `.popover`, `.modal` → `dialogs.less`).
> - Any rules that don't map to a specific Elastic widget go into `widgets/common.less`.
> - Maintain the same import order as Elastic (`common` → `buttons` → `forms` → `lists` → `menu` → `messages` → `dialogs` → `editor` → `jqueryui`).
> - Ensure LESS compilation still produces identical CSS output after the split (diff `styles.min.css` before/after).

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Audit `_components.less` and categorize rules | Mapped all rule blocks across 9 widget files |
| ✅ | Create `styles/widgets/` directory with 9 files | `buttons.less`, `lists.less`, `dialogs.less`, `forms.less`, `menu.less`, `messages.less`, `common.less`, `editor.less`, `jqueryui.less` |
| ✅ | Extract rules from `_components.less` into widget files | All rules moved; `_components.less` now a barrel comment file |
| ✅ | Update `styles.less` imports | `@import "_components"` replaced with 9 `@import "widgets/..."` lines |
| ✅ | Verify CSS output is identical | Diff: same 189,785-byte output — zero rules added/removed, only expected reorder |

### 2 — Create `_mixins.less` for Reusable Patterns

> **Problem:** Elastic has `mixins.less` with reusable style patterns (clearfix, flex helpers, responsive utilities). Stratus has no mixins file. This means repeated patterns like border-radius combos, flex layouts, text truncation, focus rings, and dark-mode utility snippets are hardcoded inline across the `widgets/` files, `_layout.less`, `_dark.less`, and `_login.less`. Copy-paste drift leads to inconsistency.
>
> **Remedy:** Create `skins/stratus/styles/_mixins.less`. Extract common patterns from all stratus LESS files into named mixins. Import `_mixins.less` early in `styles.less` (after `_variables.less`, before component files) so all partials can use them.
>
> **Candidate patterns to extract (audit for these):**
> - `.mp-truncate()` — text-overflow ellipsis (single-line truncation)
> - `.mp-flex-center()` — flex display + center alignment
> - `.mp-focus-ring()` — consistent focus outline for accessibility
> - `.mp-frosted-glass()` — backdrop-filter blur + translucent bg (used in toolbar, login card)
> - `.mp-pill-shape()` — border-radius + padding for pill badges/tags
> - `.mp-transition(@props)` — standard 150ms ease transition
> - `.mp-scrollbar()` — thin capsule scrollbar styles (currently duplicated)
> - `.mp-card-hover()` — hover elevation (shadow + translateY, used in message list, calendar events)
> - Dark-mode helper: `.mp-dark(@prop, @value)` or a guard pattern for `html.dark-mode` scoping
>
> **Implementation notes:**
> - After creating mixins, do a second pass replacing inline patterns with mixin calls.
> - Elastic's `mixins.less` is a good reference for structure but stratus mixins should be prefixed with `.mp-` per coding rules.

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Audit all LESS files for repeated patterns | Grepped for `backdrop-filter`, `0 0 0 3px fadeout`, `border-radius.*pill`, `text-overflow`, `transition` clusters across all 14 LESS files |
| ✅ | Create `_mixins.less` with extracted mixins | 11 mixins: `.mp-truncate`, `.mp-flex-center`, `.mp-flex-row`, `.mp-focus-ring`, `.mp-frosted-glass`, `.mp-frosted-glass-dark`, `.mp-pill-shape`, `.mp-transition`, `.mp-scrollbar`, `.mp-card-hover`, `.mp-card-hover-dark` |
| ✅ | Update `styles.less` import order | Added `@import "_mixins"` after `_variables.less`, before component imports (step 4 in import chain) |
| ✅ | Replace inline patterns with mixin calls | Substituted across `widgets/forms.less`, `widgets/editor.less`, `widgets/common.less`, `_layout.less`, `_dark.less`, `_login.less` — focus-ring + frosted-glass patterns replaced |
| ✅ | Verify CSS output and visual result | Compiled cleanly; 189,982 bytes (+197 from added `outline:none` on focus rings — intentional improvement) |

### 3 — Dark Mode Audit & Consolidation

> **Problem:** Dark-mode overrides are spread across multiple files: `_dark.less` (dedicated file), plus `html.dark-mode` blocks scattered inside the `widgets/` files, `_layout.less`, `_login.less`, `_calendar.less`, and possibly others. There's no single source of truth. This makes it easy to miss a component when updating the dark palette, hard to audit dark-mode completeness, and creates conflicting override patterns (some components have co-located dark rules, others rely on `_dark.less`).
>
> **Remedy:** Audit every LESS file for `html.dark-mode` selectors. Decide on ONE consistent pattern and apply it everywhere:
> - **Option A — Centralize:** Move all dark-mode rules into `_dark.less`, organized by component section. Pro: one file to audit. Con: rules are far from their light counterparts.
> - **Option B — Co-locate:** Keep dark rules adjacent to their light counterparts in each file, and reduce `_dark.less` to only global/cross-cutting dark overrides. Pro: easier to maintain per-component. Con: harder to audit completeness.
> - **Recommended:** Option B (co-locate) with `_dark.less` reserved for global dark tokens (body bg, text color, scrollbar, selection) and cross-cutting resets only.
>
> **Implementation notes:**
> - First, produce an audit report: list every `html.dark-mode` block, which file it's in, and which component it targets.
> - Then, migrate blocks to the chosen pattern.
> - Verify no dark-mode rules are duplicated or conflicting (same selector in multiple files with different values).
> - After consolidation, verify dark mode visually on all major views (login, mail list, message view, settings, calendar).

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Audit all files for `html.dark-mode` blocks | 3 source files had `html.dark-mode`: `_dark.less` (centralized, 934 lines), `_login.less` (already co-located), `_runtime.less` (already co-located). Widget files and `_layout.less` had no dark rules. |
| ✅ | Decide consolidation pattern (centralize vs co-locate) | Option B (co-locate) chosen per roadmap recommendation: dark rules in each component file, `_dark.less` reserved for global tokens only |
| ✅ | Migrate dark-mode rules to chosen pattern | 23 sections extracted from `_dark.less` (934 lines → 66 lines). Dark blocks appended to: `_layout.less`, `widgets/buttons.less`, `widgets/forms.less`, `widgets/lists.less`, `widgets/messages.less`, `widgets/dialogs.less`, `widgets/menu.less`, `widgets/common.less`, `widgets/jqueryui.less`, `_calendar.less`. Compiled: 189,935 bytes, 597 `html.dark-mode` occurrences. |
| ✅ | Remove conflicting/duplicate dark overrides | No duplicates found. Each component has exactly one dark-mode rule set in its own file. `_dark.less` now contains only: body bg, headings, scrollbars, selection, focus-ring. |
| ✅ | Visual verification of dark mode on all views | Login, mail, message, settings, calendar, compose, contacts — see dogfood-output/report.md (2026-03-04) |

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

### Skin / Calendar

| Status | Issue | Found By | Notes |
|--------|-------|----------|-------|
| ✅ | LESS `@color-main` override not working — elastic cyan `#37beff` used instead of indigo `#5c6bc0` | user/builder | Fixed: moved `@import "_variables"` to after elastic import in `styles.less` (LESS lazy eval: last def wins) |
| ✅ | About/logout buttons overlapping compose in light mode | user/builder | Fixed: removed `position:relative` from `#taskmenu`. Moved to `#layout-menu`. |
| ✅ | Datepicker select FOUC (white flash on load) | user/builder | Fixed: pre-applied `appearance:none` + `background` + `border` + `color` on `.ui-datepicker .ui-datepicker-header select` in `_components.less`. Dark mode `transition:none` in `_dark.less`. |
| ✅ | Datepicker arrow jumps from left→right on load | user/builder | Fixed: used full `background` shorthand with Bootstrap SVG arrow at `right .75rem center/8px 10px` (was using `background-color` only — no arrow until JS ran). |
| ✅ | Datepicker text jumps on load | user/builder | Fixed: added full `padding: 0.25rem 1.75rem 0.25rem 0.75rem` shorthand + `font-size: @mp-font-size-base` to pre-apply rule (missing `padding-left` and `font-size` caused layout shift when `.form-control` was added). |
| ✅ | Calendar header shifts when More menu clicked | user/builder | Root cause: CSS-only (Tier A) approach forced `#toolbar-menu` visible in header flex; elastic JS detached it on popover open → layout reflow. Fix: Tier B template override — Create button moved to `#mp-cal-actions` outside `.toolbar.menu` so it's never detached. |

### Conversation Mode — Bugs in "completed" MVP / Phase 1.5 §1

| Status | Issue | Found By | Notes |
|--------|-------|----------|-------|
| ✅ | **Icons render blank** — CSS `font-family: "Font Awesome 5 Free"` is wrong; elastic uses `'Icons'` | builder | Fixed (twice): first attempt changed to `"Font Awesome 5 Free"` which also doesn't exist in elastic. Correct fix: changed all `font-family` to `'Icons'` (the name elastic registers FA 5 glyphs under). Also replaced `<i class="fa">` template elements with CSS `::before` pattern. Files: `skins/default/conversation_mode.css`, `mail.html`, `conversation_mode.js`. |
| ✅ | **Snippets always empty (Line 3 blank)** — `extract_snippet()` relies on `$header->body_structure_text` | builder | Fixed: rewrote `extract_snippet()` to use `rcube_message::first_text_part()` for fetching actual message body text from IMAP. Added `clean_snippet()` helper for whitespace normalization + truncation. |
| ✅ | **Pagination entirely missing** — `mail.html` has no `#conv-pagination` element | builder | Fixed: added `#conv-pagination` with prev/next buttons + page info to both `ensure_conv_structure()` in JS and the Stratus `mail.html` template. |
| ✅ | **Unread count unreliable** — 3 overlapping detection paths in grouper | builder | Fixed: replaced 3 overlapping checks with single canonical `empty($h->flags['SEEN'])` check in `build_summaries()`. |
| ✅ | **Toggle button may not render in Stratus** — `add_button('toolbar')` | builder | Fixed: added `<roundcube:container name="toolbar" id="convtoolbar" />` to the Stratus `mail.html` toolbar div. |
| ✅ | **"Open" button calls non-existent API** — `rcmail.show_message(uid)` | builder | Fixed: replaced with cascading fallback: `rcmail.open_message()` → `rcmail.show_message()` → URL-based `rcmail.location_href()` into contentframe. |
| ✅ | **Empty state icon missing** — CSS for `.conv-empty-icon` exists but DOM never created | builder | Fixed: added icon span with `<i class="fa fa-comments">` to `ensure_conv_structure()` in JS. Template already had it. Also added `.conv-empty-icon .fa` CSS rule for Font Awesome rendering. |
| ✅ | **Flagged row border-left misaligns content** — 3px border only on `.flagged` | builder | Fixed: added `border-left: 3px solid transparent` to all `.conv-list.conv-outlook tbody tr.message` in both default and elastic CSS. |

### Conversation Mode — #layout-list Dogfood (2026-03-04) — NEXT PRIORITY

> **Scope:** `#layout-list` section only, conversation mode active. All fixes touch `plugins/conversation_mode/` CSS and/or JS.
> **Styling rule:** Never hardcode colors or sizes. Map to existing LESS variables in `_variables.less` (e.g. `@color-main`, `@color-dark-bg`, `@color-font-secondary`) or define new vars there first.

| Status | Issue | Severity | File(s) | Fix Notes |
|--------|-------|----------|---------|-----------|
| ✅ | **Conv list icons all invisible** — all 7 icon types (expand arrow, paperclip, flag, archive/delete/flag actions, open button, empty-state, back button) render blank | high | `plugins/conversation_mode/skins/default/conversation_mode.css`, `skins/stratus/templates/mail.html`, `plugins/conversation_mode/conversation_mode.js` | Root cause: `font-family: "Font Awesome 5 Free"` doesn't exist in elastic/stratus (the font is registered as `'Icons'`). Fixed all 6 occurrences to `'Icons'`. Replaced `<i class="fa">` elements with CSS `::before` + `conv-icon` pattern. |
| ✅ | **Decide/add Stratus-specific conversation override CSS** — no `skins/stratus/conversation_mode.css` exists, so plugin CSS falls back to Elastic | medium | `skins/stratus/styles/_runtime.less`, `skins/stratus/styles/_dark.less`, `plugins/conversation_mode/skins/elastic/conversation_mode.css` | Resolved via CSS token bridge: 22 `--mp-conv-*` custom properties defined in `_runtime.less` (light) and `_dark.less` (dark). Plugin CSS uses `var(--mp-conv-*, fallback)`. No separate stratus override file needed. |
| ✅ | **Dark mode text invisible** — `.conv-sender`, `.conv-subject-text`, `.conv-date`, `.conv-line3`, `.conv-count` all fall back to hardcoded `#111`/`#333`/`#888` which are unreadable on the dark navy background | high | `plugins/conversation_mode/skins/elastic/conversation_mode.css` | Resolved via `--mp-conv-*` token bridge. All text/color rules in elastic CSS now use `var(--mp-conv-text, fallback)` etc. Dark-mode `html.dark-mode` rules reference same tokens, which are set to `@color-dark-*` values in `_dark.less`. |
| 🔲 | **Read state never updates visually** — after opening a conversation, the row keeps the `unread` CSS class and the blue `.conv-unread-dot` stays visible; only the folder badge count decrements | medium | `plugins/conversation_mode/conversation_mode.js` | The JS plugin must listen for the `rcmail` `message_read` event (or post-`conv.open` response) and toggle the `unread` class + hide `.conv-unread-dot` on the affected row. Current `load_message_preview()` changes the pane but does not call back into the list to update row state. |
| 🔲 | **No read/unread text weight differentiation** — all rows are always bold (`font-weight: 700`/`600`) regardless of read state | medium | `plugins/conversation_mode/skins/elastic/conversation_mode.css` (and `default/`) | Add `.conv-list tr.message:not(.unread) .conv-sender { font-weight: 400 }`, `.conv-list tr.message:not(.unread) .conv-subject-text { font-weight: 400 }`, `.conv-list tr.message:not(.unread) .conv-line3 { font-weight: 400 }` — matching the elastic standard list pattern. Unread keeps bold; read becomes normal weight. Use `@mp-font-weight-normal` / `@mp-font-weight-bold` LESS vars. |
| 🔲 | **Footer archive button shows `[buttontext]` / `[buttontitle]`** — raw Roundcube template tags unresolved on `#mp-action-archive` | medium | `skins/stratus/templates/mail.html` | The `<a id="mp-action-archive">` element uses literal `[buttontext]`/`[buttontitle]` placeholders instead of a `<roundcube:button>` tag or hardcoded localized string. Replace with `<roundcube:button name="archive" ...>` or copy the correct label string from the elastic `mail.html` template. |
| 🔲 | **Status icons area sometimes empty** — `.conv-status-icons` span exists but is not always populated (attachment/flag data may not be passed from server for all conversations) | low | `plugins/conversation_mode/conversation_mode.js` | Icons now render correctly (font-family fix applied). Issue is server-side: `has_attachments` and `flagged_count` fields may not be populated for all conversations. Verify data flow from `conversation_mode_service.php` → JS. |
| ✅ | **Expanded child rows have no hover quick-actions** — parent rows show Archive/Delete/Flag on hover; `.conv-flags-cell` in child rows is always empty | low | `plugins/conversation_mode/conversation_mode.js` + `skins/*/conversation_mode.css` | Fixed: child rows now get Reply/Delete/Flag hover actions via `child_action_btn()`. CSS hover-show rule extended to `tr.conv-child-row:hover .conv-hover-actions`. |
| ✅ | **Date format inconsistency** — messages older than ~1 year display as raw ISO `2025-03-03` instead of a friendly `Mar 3, 2025` | low | `plugins/conversation_mode/conversation_mode.js` | Fixed: `format_date()` now uses `MMM D, YYYY` for dates older than current year. |
| 🔲 | **Expanded thread has no visual connector** — child rows have `border-left: 2px solid transparent`; parent has blue 3px left border but children are visually disconnected | low | `plugins/conversation_mode/skins/elastic/conversation_mode.css` | Give child rows a continuous visual thread line: `.conv-child-indent { border-left: 2px solid @color-main; }` (use LESS var, mapped via CSS). In dark mode use `@color-dark-accent`. Matches the parent's left-border accent color. |

### Dark Mode — Visual Verification Issues (2026-03-04)

> Found during dogfood dark-mode pass across all 7 views. Full report: `dogfood-output/report.md`.
> **Styling rule for all fixes:** never hardcode color values — use existing `_variables.less` dark tokens or resolve from them. Plugin CSS files (`.css`, not LESS) must use the resolved hex values in explicit `html.dark-mode` blocks.

| Status | Task | Severity | View | Notes |
|--------|------|----------|------|-------|
| ✅ | **`.conv-sender` text invisible in dark mode** — contrast ~1.7:1 | high | Mail list | Resolved via `--mp-conv-*` token bridge. `_dark.less` sets `--mp-conv-text: @color-dark-font`, `--mp-conv-text-secondary: @color-dark-font-secondary`. Plugin CSS uses `var(--mp-conv-text, #fallback)`. |
| 🔲 | **Settings preferences iframe missing `dark-mode` class** — form renders white | high | Settings | JS fix in `stratus_helper.js`: after `#preferences-frame` loads, inject `dark-mode` class onto its `contentDocument.documentElement`. Or PHP fix: `stratus_helper.php` detects `_framed=1` requests and outputs `class="... dark-mode"` on `<html>` based on the user's saved preference. No LESS vars involved. |
| 🔲 | **TinyMCE editor area white in dark mode** — `.tox-toolbar-overlord` + edit iframe white | high | Compose | Two-part fix: (1) `widgets/editor.less` dark block: `html.dark-mode .tox-toolbar-overlord { background-color: @color-dark-surface-raised; }`. (2) `stratus_helper.js` TinyMCE `init` options: add `skin: 'oxide-dark', content_css: 'dark'` when `document.documentElement.classList.contains('dark-mode')`. |
| 🔲 | **FullCalendar toolbar buttons light gray in dark mode** — `.fc-button` unthemed | medium | Calendar | `_calendar.less` dark section (`html.dark-mode .task-calendar`): `.fc-button { background-color: @color-dark-surface-raised; color: @color-dark-btn; border-color: @color-dark-border; }` and `.fc-button-active, .fc-button:active { background-color: @color-dark-btn-primary-background; color: #fff; border-color: @color-dark-main; }`. |
| ✅ | **Conversation row separators too bright in dark mode** — `#e5e5e5` unchanged | low | Mail list | Resolved via `--mp-conv-row-border` token. `_dark.less` sets it to `@color-dark-list-border`. Plugin CSS uses `var(--mp-conv-row-border, #e5e5e5)`. |

### Conversation Mode — Parent + Child Row UX Improvements

> **Scope:** JS row-building (`conversation_mode.js`), plugin CSS (`skins/*/conversation_mode.css`), token bridge (`_runtime.less`, `_dark.less`).
> Fixes friction in sender display, snippet content, child row structure, hover actions, and unread visual cues.

| Status | Task | Notes |
|--------|------|-------|
| ✅ | Fix sender display — strip `@domain` | `format_sender_name()` applies display_name \|\| local-part logic. Used in both `format_participants()` and `build_child_row()`. |
| ✅ | Smart snippet for parent — filter quoted replies | `clean_snippet()` truncates at `\n>`, `On … wrote:`, forwarded headers, `From:`, and `___` separators. |
| ✅ | Add snippet to child rows | Child rows now show snippet preview in `conv-line3` instead of redundant subject. `display:none` removed from default CSS. |
| ✅ | Add per-child hover actions | Reply, Delete, Flag injected into child `.conv-flags-cell` via `child_action_btn()`. Actions apply to single message (uid), not thread. |
| ✅ | Add "Mark as read/unread" to parent hover | 4th button (`envelope-open`/`envelope`) in parent hover actions. `cmd_mark_read()` toggles read/unread state. |
| ✅ | Improve unread visual on children | Unread child rows get `border-left-color: --mp-conv-child-unread-accent` + bold sender + `conv-unread-dot`. Token bridge in `_runtime.less` + `_dark.less`. |
| ✅ | Direction indicator on child rows | `conv-direction-indicator` with `conv-dir-sent` (→ green) / `conv-dir-received` (← blue). Derived from `is_own_identity()` checking `rcmail.env.identities`. |

### Conversation Mode — UI Gaps (not yet in roadmap)

| Status | Issue | Found By | Notes |
|--------|-------|----------|-------|
| 🔲 | **No visual loading skeleton** — list disappears during AJAX | builder | Only `rcmail.set_busy()` spinner shown. No skeleton rows or shimmer animation for perceived performance. |
| 🔲 | **No conversation total count displayed** — user can't see "42 conversations" | builder | After load, no UI element shows the total. Add count indicator in list header or pagination area. |
| ✅ | **Dark mode incomplete for detail panel** — several elements lack dark rules | builder | Resolved via `--mp-conv-*` token bridge. All detail-panel elements (`.conv-detail-header`, `.conv-back-btn`, `.conv-message-card`, `.conv-pagination`, `.conv-empty`) now use token vars with dark-mode overrides in `_dark.less`. |
| 🔲 | **Detail panel shows no message body** — only headers + "Open" button | builder | `open_conversation()` fetches headers only. Cards show from/date/subject but no body excerpt. Users must click "Open" per message. Add body preview (first ~200 chars). |
| 🔲 | **No visual transition between modes** — instant attribute swap | builder | Switching list↔conversations is a hard cut. Add CSS transition (opacity/transform) or minimal fade for smoother UX. |
| 🔲 | **Single-click should open in reading pane** — currently requires double-click | builder | Modern mail UX: single click = preview in reading pane, double-click = full view. Current: single click = select row only. Partially blocked on Phase 1.5 §2 reading pane work. |
| 🔲 | **Toggle only cycles list↔conversations** — `threads` mode orphaned | builder | `cmd_toggle()` hard-codes 2-way toggle. Settings allows 3 modes but toolbar only cycles 2. Consider a dropdown or 3-state toggle. |
| 🔲 | **Sent folder shows user as sender for all messages** — misleading avatars/participants | builder | Conversations in Sent show the logged-in user as primary participant. Fix: detect Sent/Drafts and prefer `To:` recipients for display instead. |
| 🔲 | **Server still renders standard list when in conversation mode** — wasteful | builder | `hook_messages_list` is a no-op that returns `$args` unchanged. IMAP query + HTML render for the standard list still execute, then JS hides it. Fix: suppress list output server-side when mode is conversations. |
| 🔲 | **Session cache bloat risk for large mailboxes** — full conversation data in `$_SESSION` | builder | 2000-message mailbox produces ~50-100 conversation summaries stored in `$_SESSION`. For users with many folders this can bloat session serialization. Consider pruning stored fields or using Roundcube's `rcube_cache`. |

---

## Design Inspiration / References

- Elastic default: Clean, blue (#37beff), material-ish
- Outlook+: Professional blue (#0075c8), inverted header, no taskbar icons
- Gmail+: Red accent (#b0263b), material icons, compact
- Target: Modern, professional, distinct identity — NOT a clone of any existing skin
