# Project Context — Current State

> This file tracks the current state of the project. Every agent reads this before starting work
> and updates it after completing work. Keep it concise and factual.

**Last Updated:** 2026-03-04 (Conversation mode CSS token bridge — `--mp-conv-*` vars in `_runtime.less` + `_dark.less`)
**Last Agent:** GitHub Copilot

---

## Current Phase

**Phase 2 — Companion Plugin** ✅ Complete
**Phase 1.6 — Calendar UI Improvements** 🟡 In Progress (Grid ✅, Header Tier B ✅)
**Conversation Mode Plugin** ✅ Phase 1 MVP Complete
**Conversation Mode Phase 1.5 §1** ✅ Row Layout Complete + Template Architecture

## What Exists

### `.github/` — AI Agent System ✅ Complete
- All agents, instructions, skills, prompts in place

### `skins/stratus/` — The Skin ✅ Foundation + Polish
- ✅ `meta.json` — extends elastic, dark_mode_support, indigo theme-color
- ✅ `LICENSE` — CC BY-SA 3.0 notice for `skins/stratus`
- ✅ `README.md` — credits + CC BY-SA 3.0 notice for `skins/stratus`
- ✅ `composer.json` — package metadata
- ✅ `watermark.html` — branded splash page
- ✅ `styles/styles.less` — entry: elastic FIRST → _fonts → _variables → **_mixins** → _typography → _animations → _layout → **widgets/** (9 files) → **_calendar** → _dark → _login → _runtime
- ✅ `styles/_variables.less` — full design system (~180+ vars)
- ✅ `styles/_mixins.less` — 11 reusable `.mp-` mixins: `.mp-truncate`, `.mp-flex-center`, `.mp-flex-row`, `.mp-focus-ring`, `.mp-frosted-glass`, `.mp-frosted-glass-dark`, `.mp-pill-shape`, `.mp-transition`, `.mp-scrollbar`, `.mp-card-hover`, `.mp-card-hover-dark`
- ✅ `styles/_typography.less` — system font stack, heading hierarchy
- ✅ `styles/_animations.less` — transitions, 7 keyframes, reduced-motion
- ✅ `styles/_layout.less` — taskmenu gradient+pill nav, frosted glass headers
- ✅ `styles/_components.less` — **barrel file only** (comments listing 9 widget imports; no rules)
- ✅ `styles/widgets/` — 9 component files: `common.less` (quota, scrollbars, mass-action bar, contacts, file-upload, hover-menu), `buttons.less`, `forms.less`, `lists.less`, `menu.less`, `messages.less`, `dialogs.less`, `editor.less`, `jqueryui.less`
- ✅ `styles/_calendar.less` — ghost grid + **Tier B toolbar** (Create in `#mp-cal-actions`, More triggers popover)
- ✅ `styles/_dark.less` — **global tokens + conversation bridge** (~100 lines): body bg, headings, scrollbars, selection, focus-ring, `--mp-conv-*` dark overrides. All component dark rules co-located in component files.
- ✅ `styles/_login.less` — animated mesh gradient bg, frosted glass card
- ✅ `styles/styles.min.css` — compiled (~189KB)
- ✅ `templates/includes/layout.html` — injects stratus CSS
- ✅ `templates/login.html` — inherits elastic login
- ✅ `templates/mail.html` — Stratus mail layout override + conversation containers
- ✅ `plugins/calendar/templates/calendar.html` — **NEW Tier B override**: Create button outside `#calendartoolbar`
- ✅ `thumbnail.png` — 320×240 preview

### `plugins/stratus_helper/` — Companion Plugin ✅ Complete
- ✅ `stratus_helper.php` — main class: appearance injection, folder refresh, color/font AJAX, preferences
- ✅ `stratus_helper.js` — client JS: folder refresh handler, live scheme/font switching, settings preview
- ✅ `config.inc.php.dist` — 8 color schemes, 7 Google Fonts, folder refresh toggle
- ✅ `localization/en_US.inc` — English strings for all labels
- ✅ `skins/elastic/stratus_helper.css` — preferences page styles
- ✅ `composer.json` — package metadata
- ✅ Runtime theming: CSS custom properties bridge in `_variables.less` + `_runtime.less` partial (includes `--mp-conv-*` conversation mode tokens)
- ✅ Integration: `pagenav.html` TODO stub replaced with plugin AJAX call
- ✅ Docker mount + Roundcube config wired

### `plugins/conversation_mode/` — Conversation Mode Plugin ✅ MVP
- ✅ `conversation_mode.php` — main plugin: hooks, 4 AJAX actions, preferences
- ✅ `lib/conversation_mode_service.php` — orchestrator (list, open, refresh)
- ✅ `lib/conversation_mode_grouper.php` — union-find grouping (RFC headers + subject fallback)
- ✅ `lib/conversation_mode_cache.php` — session-backed cache with configurable TTL
- ✅ `conversation_mode.js` — **v3 template-binding**: binds to mail.html containers, `rcube_list_widget`, Outlook 3-line rows, avatar circles, FA icons, hover actions
- ✅ `skins/default/conversation_mode.css` — baseline CSS with `data-conv-mode` toggle, 3-line layout, avatar, hover action bar, responsive
- ✅ `skins/elastic/conversation_mode.css` — Elastic skin overrides with `--mp-conv-*` token bridge (Stratus) + hardcoded fallbacks (non-Stratus) + dark mode
- ✅ `localization/en_US.inc` — English strings
- ✅ `config.inc.php.dist` — configurable defaults
- ✅ `composer.json` — package metadata

## What Was Just Done

- **LESS architecture refactor — `_components.less` split into `widgets/` directory (LESS Tech Debt §1):**
  - Created `skins/stratus/styles/widgets/` with 9 files mirroring Elastic’s structure.
  - Mapped all 1048 lines of `_components.less` to per-widget files: `common.less` (scrollbars, quota, mass-action bar, file-upload, hover-menu, contacts), `buttons.less`, `forms.less`, `lists.less`, `menu.less`, `messages.less`, `dialogs.less`, `editor.less`, `jqueryui.less` (stub).
  - `_components.less` converted to comments-only barrel file.
  - `styles.less` updated: `@import "_components"` replaced with 9 individual `@import "widgets/..."` lines in Elastic order (`common → buttons → forms → lists → menu → messages → dialogs → editor → jqueryui`).
  - Compiled: zero errors. CSS diff: identical 189,785-byte output — zero rules added or removed, only expected reorder.

- **Conversation mode icon font-family bug — all 7 icons invisible:**
  - **Root cause:** `conversation_mode.css` used `font-family: "Font Awesome 5 Free"` — a name that does **not** exist in elastic/stratus. Elastic registers FontAwesome 5 glyphs under `font-family: 'Icons'`. Every icon was silently invisible because the browser couldn't find the font.
  - **Scope:** All 7 icon types across the `#conv-messagelist` component were affected: expand arrow, paperclip, flag, archive action, delete action, flag action, open-message button, empty-state comments icon, and back button arrow.
  - **Fix applied in 3 files:**
    1. `plugins/conversation_mode/skins/default/conversation_mode.css` — Changed all `font-family: "Font Awesome 5 Free"` → `font-family: 'Icons'` (6 occurrences across `.conv-icon::before`, `.conv-expand-arrow::before`, `.conv-empty-icon i::before`, `.conv-back-btn::before`). Added new `.conv-icon-comments::before` rule. Added `.conv-back-btn::before` rule (chevron-left `\f053`, matches elastic back buttons) so the `<i class="fa">` element is no longer needed. Fixed `.conv-icon-trash-alt` weight to `400` (regular/outline, matching elastic's delete button).
    2. `skins/stratus/templates/mail.html` — Replaced `<i class="fa fa-comments">` with `<span class="conv-icon conv-icon-comments">`. Removed `<i class="fa fa-arrow-left">` from back button (CSS `::before` handles it now).
    3. `plugins/conversation_mode/conversation_mode.js` — Updated JS fallback `ensure_conv_structure()` to create `<span class="conv-icon conv-icon-comments">` instead of `<i class="fa fa-comments">`.
  - LESS compiled successfully after all changes ✅

## Styling Rule (Critical)

> **Always rely on existing LESS variables — never hardcode colors, sizes, or font weights.**
> Before writing any color, radius, font-weight, or spacing value in a LESS/CSS file:
> 1. Check `_variables.less` for an existing var (e.g. `@color-main`, `@color-font-secondary`, `@mp-radius-pill`, `@mp-font-weight-bold`).
> 2. If the value doesn't exist, **define a new var in `_variables.less` first**, then reference it.
> 3. For dark mode overrides: define a matching `@color-dark-*` var and add the rule in `_dark.less`.
> 4. For plugin CSS (`conversation_mode.css`, `stratus_helper.css`): use the `--mp-conv-*` CSS custom property bridge from `_runtime.less` (light) and `_dark.less` (dark). Plugin CSS references `var(--mp-conv-main, #fallback)` — Stratus sets real values, non-Stratus skins get the fallback.
> Hardcoded values like `#111`, `#e5e5e5`, `700`, `0.4em` are a maintenance hazard — they won't respond to dark mode or theme switching.

## What's Next

1. **🔴 #layout-list Dogfood Fixes (5 remaining: 3 medium + 3 low, dark-mode text/separators resolved)** — see roadmap
   - MEDIUM: Read state never updates visually; no read/unread font-weight difference; footer `[buttontext]` placeholder
   - LOW: Empty status icons; no child row hover actions; ISO date format on old messages; no thread connector line
2. **Conversation Mode Phase 1.5 §2: Reading pane integration** (5 tasks — all 🔲)
3. Phase 1.6 remaining items (collapsible sidebar, current-time indicator, accent color refresh)
4. Phase 3: Advanced features (density modes, custom backgrounds, etc.)

## Active Blockers

- None

## Recent Fixes

- **Plugin CSS token bridge pattern** — Plugin `.css` files cannot use LESS variables. Bridge pattern: define `--mp-conv-*` CSS custom properties in `_runtime.less` (`:root` block, sourced from LESS vars) and dark overrides in `_dark.less` (`html.dark-mode` block). Plugin CSS references `var(--mp-conv-main, #fallback)`. Stratus sets real values at compile time; non-Stratus skins get the hardcoded fallback. This pattern supports runtime theme switching via `stratus_helper` because `--mp-conv-main` references `var(--stratus-primary, @color-main)`. 22 tokens defined: accent, surfaces, text, borders, hover, shadows, child rows.
- **Icon font-family must be `'Icons'`, never `"Font Awesome 5 Free"`** — Elastic/stratus registers FontAwesome 5 glyphs under `font-family: 'Icons'` (weight 900 = solid, 400 = regular). The CSS name `"Font Awesome 5 Free"` does not exist and silently fails. Plugin CSS must match this. Also: avoid `<i class="fa fa-*">` elements in templates — elastic doesn't define `.fa` classes. Use CSS `::before` with `font-family: 'Icons'` + glyph content codes instead, or use the `conv-icon conv-icon-*` class pattern.
- **LESS import order** — elastic imported after `_variables` caused `@color-main` to be elastic's cyan. Fixed: elastic first, then `_variables`.
- **Datepicker FOUC trilogy** — Three cascading bugs in `.ui-datepicker-header select` caused by calendar JS using `setTimeout(25ms)` to add `.form-control`/`.custom-select`. Fix pattern: pre-apply every property that would change (`appearance`, full `background` shorthand with SVG arrow, full `padding` shorthand, `font-size`, `border`, `color`) so no layout shift occurs between first paint and class addition. Dark mode also needs `transition:none` to prevent white→dark animation flash.

- Design system vars: ~180+ LESS vars (colors + typography + spacing + radius + elevation + transitions + glass) + 22 `--mp-conv-*` CSS custom properties (plugin token bridge)
- Compiled CSS: ~189KB minified
- Templates overridden: 3 skin (layout.html, login.html, mail.html) + 1 plugin (calendar/calendar.html)
- LESS partials: 10 root + 9 widgets = 19 (_variables, _mixins, _typography, _animations, _layout, _components[barrel], _calendar, _dark, _login, _runtime; widgets: common, buttons, forms, lists, menu, messages, dialogs, editor, jqueryui)
- Keyframe animations: 7
- 0 compile errors
- Plugins: 2 — `stratus_helper` (companion, 1 PHP + 1 JS + 1 CSS + 1 l10n) + `conversation_mode` (standalone, 4 PHP + 1 JS + 2 CSS + 1 l10n)
