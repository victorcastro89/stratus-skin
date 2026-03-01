# Project Context — Current State

> This file tracks the current state of the project. Every agent reads this before starting work
> and updates it after completing work. Keep it concise and factual.

**Last Updated:** 2026-03-01 (CC BY-SA 3.0 license docs)
**Last Agent:** GitHub Copilot

---

## Current Phase

**Phase 1.6 — Calendar UI Improvements** 🟡 In Progress (Grid ✅, Header Tier B ✅)

## What Exists

### `.github/` — AI Agent System ✅ Complete
- All agents, instructions, skills, prompts in place

### `roundcubemail/skins/stratus/` — The Skin ✅ Foundation + Polish
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

## What Was Just Done

- Added `skins/stratus/LICENSE` with Creative Commons Attribution-ShareAlike 3.0 notice
- Added `skins/stratus/README.md` credits + license notice text
- Updated root `README.md` license section to point to `skins/stratus/LICENSE`
- Updated `skins/stratus/meta.json` license string to "Creative Commons Attribution-ShareAlike 3.0"

## What's Next

1. Test Tier B template against running Roundcube (Docker)
2. Phase 1.6 remaining items (collapsible sidebar, color/lightness refresh)
3. Fine-tune mobile responsive styles
4. Phase 2: companion plugin (`stratus_helper`)

## Active Blockers

- None

## Recent Fixes

- **LESS import order** — elastic imported after `_variables` caused `@color-main` to be elastic's cyan. Fixed: elastic first, then `_variables`.
- **Datepicker FOUC trilogy** — Three cascading bugs in `.ui-datepicker-header select` caused by calendar JS using `setTimeout(25ms)` to add `.form-control`/`.custom-select`. Fix pattern: pre-apply every property that would change (`appearance`, full `background` shorthand with SVG arrow, full `padding` shorthand, `font-size`, `border`, `color`) so no layout shift occurs between first paint and class addition. Dark mode also needs `transition:none` to prevent white→dark animation flash.

- Design system vars: ~230 (colors + typography + spacing + radius + elevation + transitions + glass)
- Compiled CSS: ~180KB minified
- Templates overridden: 2 skin (layout.html, login.html) + 1 plugin (calendar/calendar.html)
- LESS partials: 8 (_variables, _typography, _animations, _layout, _components, _calendar, _dark, _login)
- Keyframe animations: 7
- 0 compile errors
