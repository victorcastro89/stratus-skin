# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Stratus is a custom Roundcube webmail skin that extends Elastic (Roundcube's default modern skin). It consists of a custom skin (`skins/stratus/`) and a companion plugin (`plugins/stratus_helper/`).

## Common Commands

### Docker Dev Environment
```bash
npm run start          # First-time setup: runs setup.sh then starts Docker
npm run docker:up      # Start containers (builds if needed)
npm run docker:down    # Stop containers
npm run docker:rebuild # Force recreate containers
npm run docker:shell   # Shell into the roundcube container
npm run docker:logs    # Tail roundcube logs
```

### CSS (LESS)
```bash
npm run less:build     # Compile LESS → skins/stratus/styles/styles.min.css
npm run less:watch     # Watch and recompile on changes
```

### PHP Tests
```bash
npm test               # Run PHPUnit suite (93 tests)
npm run test:watch     # Re-run on file changes (requires fswatch)
composer install       # Install PHPUnit (first time only)
```

### Test Emails
```bash
npm run mail:seed      # Seed test emails into the dev mailbox
npm run mail:clean     # Remove seeded emails
```

## Architecture

### Skin (`skins/stratus/`)
- Extends Elastic via `meta.json` `"extends": "elastic"` — Elastic templates/assets are the fallback search path; only override what differs
- **LESS**: `styles/styles.less` imports `elastic/styles/styles` first, then overrides
- **Compiled output**: `styles/styles.min.css` — always run `less:build` after editing `.less` files
- LESS import order matters: variables → mixins → component overrides → `_runtime.less`
- `_runtime.less` exposes CSS custom properties (`--stratus-primary`, `--stratus-primary-dark`) set at runtime by `stratus_helper`
- **Templates** live in `skins/stratus/templates/` and override Elastic's templates using Roundcube's `<roundcube:...>` template engine (see `.claude/rules/roundcube-templates.md` for tag reference)

### JS: Smart Bar (`skins/stratus/js/smart-bar/`)
Plain ES5 IIFEs, no build step. Modules register on `window.StratusSmartBar`. Load order enforced by `stratus_helper.php`:

| File | Module | Responsibility |
|------|--------|----------------|
| `selection-manager.js` | `SelectionManager` | Tracks selected items, fires `onChanged` callbacks |
| `multi-select-controller.js` | `MultiSelectController` | Enter/exit multiselect; owns `select_row` monkey-patch |
| `mass-action-bar.js` | `MassActionBar` | UI state: chip, toggles, folder buttons |
| `action-dispatcher.js` | `ActionDispatcher` | **Sole reader of `data-conv-mode`**; routes actions; owns button click listeners, `display_message` patch, `responseaftermove` |
| `sort-controller.js` | `SortController` | Sort label/arrow display + sort dialog |
| `smart-bar.js` | Orchestrator | Instantiates modules, wires callbacks |

Key patterns:
- `dispatcher.isConversationMode.bind(dispatcher)` is injected into other modules — `data-conv-mode` DOM check exists only in `action-dispatcher.js`
- `onPostAction` callbacks run inside `setTimeout(0)` via `_schedulePostAction()`

### Plugins
- **`plugins/stratus_helper/`** — Companion plugin. Loads all Smart Bar JS/CSS for the `mail` task, injects runtime CSS variables for color scheme/font, adds Settings → Stratus preferences section, handles message-list date formatting
- **`plugins/conversation_mode/`** — Conversation list view; communicates with Smart Bar via `stratus:conv-*` DOM events (see memory for full event contract)
- **`plugins/undo_send/`** — Undo send functionality

### Docker Dev Environment
- `docker/docker-compose.yml` — Roundcube + mailserver services
- `docker/config/custom.inc.php` — Roundcube config (SQLite DB, Docker service hostnames for IMAP/SMTP)
- Skin and plugin source directories are mounted directly into the container — changes are reflected without rebuild
- Logs written to `docker/logs/`

### Roundcube Source
- `roundcubemail/` — Roundcube core (cloned via `npm run rc:clone`, not modified directly)
- `--include-path=roundcubemail/skins` is passed to `lessc` so elastic's LESS can be imported

## Roundcube Development Rules

Path-scoped rules under `.claude/rules/` provide API reference and best practices for:
- **Skin development** — `meta.json`, extending skins, skinning plugins
- **Template engine** — `<roundcube:...>` tags, containers, buttons, conditionals
- **Plugin PHP API** — hooks, actions, templates, config, localization
- **Plugin JS API** — `rcmail` events, commands, buttons, AJAX patterns

These load automatically when working with matching files.
