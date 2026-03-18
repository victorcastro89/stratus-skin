# Changelog

All notable changes to Stratus are documented here.
Format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).
Versions follow [Semantic Versioning](https://semver.org/).

---

## [1.0.1] — 2026-03-18

### Added
- Two-step release flow with changelog/ history
- Add custom favicons and fix release script changelog

### Changed
- Release workflow now produces a single bundle  -
- Release script: builds CSS before version bump, auto-pushes with rollback 
- undo_send default delay changed from 5s to 3s 

### Fixed
- Removed debug log helpers from Smart Bar action-dispatcher.js and stratus_helper.js

## [1.0.0] — 2026-03-17

### Stratus Skin
- Initial public release extending Roundcube's Elastic skin
- Indigo color palette with CSS custom property theming (`--stratus-primary`, `--stratus-primary-dark`)
- Full dark mode support via Elastic's `html.dark-mode` system
- Frosted glass surfaces (backdrop-filter) on sidebar, toolbar, and message pane
- Fluid 150ms transitions on all interactive elements
- Smart Bar: contextual toolbar with multiselect, mass-action chip, pagination controls, and sort dialog
- Conversation-mode layout: `#conv-list-content` / `#conv-detail` containers in `mail.html`
- TinyMCE email composer integration (custom toolbar, dark mode, Stratus typography)
- Calendar overrides: decluttered ghost grid, floating event cards
- Per-scheme color palette support (indigo default + future variants)
- Custom logo variants: default, dark, small, small-dark

### stratus_helper Plugin
- Companion plugin: loads Smart Bar JS/CSS for the `mail` task
- Runtime injection of CSS custom properties (`--stratus-primary`, `--stratus-font-family`)
- User preference page under Settings → Appearance (color scheme, font size, folder refresh interval)
- Message-list date formatting (smart relative dates)
- Aborts gracefully if active skin is not `stratus`

### undo_send Plugin
- Gmail-style undo-send with configurable delay (default 5 seconds)
- Countdown toast with Undo link; cancels SMTP delivery within the window
- Compatible with Elastic, Larry, and Stratus skins

[1.0.1]: https://github.com/victorcastro89/stratus-skin/releases/tag/v1.0.1
[1.0.0]: https://github.com/victorcastro89/stratus-skin/releases/tag/v1.0.0
