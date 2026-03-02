# Project Context — Current State

> This file tracks the current state of the project. Every agent reads this before starting work
> and updates it after completing work. Keep it concise and factual.

**Last Updated:** 2026-03-02 (Feature Spec Workflow — System-Wide Update)
**Last Agent:** GitHub Copilot (@builder)

---

## Current Phase

**Phase 2 — Companion Plugin** ✅ Complete
**Phase 1.6 — Calendar UI Improvements** 🟡 In Progress (Grid ✅, Header Tier B ✅)
**Conversation Mode Plugin** ✅ Phase 1 MVP Complete
**Conversation Mode Phase 1.5 §1** ✅ Row Layout Complete + Template Architecture

## What Exists

### `.github/` — AI Agent System ✅ Complete
- All agents, instructions, skills, prompts in place

### `docker/www/skins/stratus/` — The Skin ✅ Foundation + Polish
- ✅ `meta.json` — extends elastic, dark_mode_support, indigo theme-color
- ✅ `LICENSE` — CC BY-SA 3.0 notice for `skins/stratus`
- ✅ `README.md` — credits + CC BY-SA 3.0 notice for `skins/stratus`
- ✅ `composer.json` — package metadata
- ✅ `watermark.html` — branded splash page
- ✅ `styles/styles.less` — entry: elastic FIRST → _variables → _typography → _animations → _layout → _components → **_calendar** → _dark → _login
- ✅ `styles/_variables.less` — full design system (~230 vars)
- ✅ `styles/_typography.less` — system font stack, heading hierarchy
- ✅ `styles/_animations.less` — transitions, 7 keyframes, reduced-motion
- ✅ `styles/_layout.less` — taskmenu gradient+pill nav, frosted glass headers
- ✅ `styles/_components.less` — gradient buttons, card-hover lists, glassmorphic dialogs
- ✅ `styles/_calendar.less` — ghost grid + **Tier B toolbar** (Create in `#mp-cal-actions`, More triggers popover)
- ✅ `styles/_dark.less` — comprehensive dark mode incl. calendar toolbar dark variants
- ✅ `styles/_login.less` — animated mesh gradient bg, frosted glass card
- ✅ `styles/styles.min.css` — compiled (0 errors, ~177KB)
- ✅ `templates/includes/layout.html` — injects stratus CSS
- ✅ `templates/login.html` — inherits elastic login
- ✅ `plugins/calendar/templates/calendar.html` — **NEW Tier B override**: Create button outside `#calendartoolbar`
- ✅ `thumbnail.png` — 320×240 preview

### `plugins/stratus_helper/` — Companion Plugin ✅ Complete
- ✅ `stratus_helper.php` — main class: appearance injection, folder refresh, color/font AJAX, preferences
- ✅ `stratus_helper.js` — client JS: folder refresh handler, live scheme/font switching, settings preview
- ✅ `config.inc.php.dist` — 8 color schemes, 7 Google Fonts, folder refresh toggle
- ✅ `localization/en_US.inc` — English strings for all labels
- ✅ `skins/elastic/stratus_helper.css` — preferences page styles
- ✅ `composer.json` — package metadata
- ✅ Runtime theming: CSS custom properties bridge in `_variables.less` + `_runtime.less` partial
- ✅ Integration: `pagenav.html` TODO stub replaced with plugin AJAX call
- ✅ Docker mount + Roundcube config wired

### `plugins/conversation_mode/` — Conversation Mode Plugin ✅ MVP
- ✅ `conversation_mode.php` — main plugin: hooks, 4 AJAX actions, preferences
- ✅ `lib/conversation_mode_service.php` — orchestrator (list, open, refresh)
- ✅ `lib/conversation_mode_grouper.php` — union-find grouping (RFC headers + subject fallback)
- ✅ `lib/conversation_mode_cache.php` — session-backed cache with configurable TTL
- ✅ `conversation_mode.js` — **v3 template-binding**: binds to mail.html containers, `rcube_list_widget`, Outlook 3-line rows, avatar circles, FA icons, hover actions
- ✅ `skins/default/conversation_mode.css` — baseline CSS with `data-conv-mode` toggle, 3-line layout, avatar, hover action bar, responsive
- ✅ `skins/elastic/conversation_mode.css` — Elastic skin overrides with CSS custom properties + dark mode
- ✅ `localization/en_US.inc` — English strings
- ✅ `config.inc.php.dist` — configurable defaults
- ✅ `composer.json` — package metadata

## What Was Just Done

- **Feature Spec Workflow** — Added mandatory spec-before-implementation gate across all agents:
  - Created `.github/instructions/feature-specs.instructions.md` — rules for spec format, naming, required sections, lifecycle
  - Updated `builder.agent.md` — new Step 2.5 (Feature Spec) between Plan and Build; Step 4 updates spec status
  - Updated `stylist.agent.md` — added Feature Spec Gate to Before You Start section
  - Updated `templater.agent.md` — added Feature Spec Gate to Before You Start section
  - Updated `plugin-dev.agent.md` — added Feature Spec Gate to Critical Rules
  - Updated `qa.agent.md` — verify implementation matches approved spec
  - Updated `build-next.prompt.md` — added spec gate step
  - Updated `copilot-instructions.md` — new Feature Spec Workflow section
  - Updated `DEV_GUIDE.md` — added feature-specs to file tables, memory system, and development flows
  - Spec lifecycle: DRAFT → APPROVED → IMPLEMENTED (human must approve before code is written)

## What's Next

1. **Conversation Mode Phase 1.5 §2: Reading pane integration** (5 tasks — all 🔲)
2. Conversation Mode Phase 1.5 §3: Selection, actions & context menu
3. Deploy & test conversation_mode + stratus_helper plugins in Docker
4. Phase 1.6 remaining items (collapsible sidebar, current-time indicator, accent color refresh)
5. Phase 3: Advanced features (density modes, custom backgrounds, etc.)

## Active Blockers

- None

## Recent Fixes

- **LESS import order** — elastic imported after `_variables` caused `@color-main` to be elastic's cyan. Fixed: elastic first, then `_variables`.
- **Datepicker FOUC trilogy** — Three cascading bugs in `.ui-datepicker-header select` caused by calendar JS using `setTimeout(25ms)` to add `.form-control`/`.custom-select`. Fix pattern: pre-apply every property that would change (`appearance`, full `background` shorthand with SVG arrow, full `padding` shorthand, `font-size`, `border`, `color`) so no layout shift occurs between first paint and class addition. Dark mode also needs `transition:none` to prevent white→dark animation flash.

- Design system vars: ~230 (colors + typography + spacing + radius + elevation + transitions + glass)
- Compiled CSS: ~180KB minified
- Templates overridden: 2 skin (layout.html, login.html) + 1 plugin (calendar/calendar.html)
- LESS partials: 9 (_variables, _typography, _animations, _layout, _components, _calendar, _dark, _login, _runtime)
- Keyframe animations: 7
- 0 compile errors
- Plugins: 2 — `stratus_helper` (companion, 1 PHP + 1 JS + 1 CSS + 1 l10n) + `conversation_mode` (standalone, 4 PHP + 1 JS + 2 CSS + 1 l10n)
