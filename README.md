# Stratus — A Modern Roundcube Skin

**Stratus** is a custom skin for [Roundcube Webmail](https://roundcube.net/) that extends the built-in `elastic` skin with an "Atmospheric Modern" design language — layered elevation, fluid transitions, and an indigo color palette with full dark mode support.


## Features


- 🌙 **Full dark mode** (uses elastic's native `html.dark-mode` system)
- ✨ **Frosted glass** effects (backdrop-filter) on key surfaces
- 💫 **Fluid 150ms transitions** on all interactive elements
- 📱 **Responsive** — inherits elastic's mobile/tablet layout
- 📅 **Calendar polish** — decluttered ghost grid, floating event cards


## Quick Start

### Prerequisites

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (for the dev environment)
- [Node.js](https://nodejs.org/) v18+ (for LESS compilation)
- Git

### Setup (one command)

```bash
git clone <your-repo-url>
cd stratus-skin
npm start               # setup + build + start Docker containers
```

Open http://localhost:8000 and log in:

| Account | Password |
|---|---|
| `victor@example.test` | `password123` |
| `alice@example.test` | `password123` |
| `bob@example.test` | `password123` |

### LESS Development

```bash
npm run less:watch       # auto-recompile on save
npm run less:build       # one-shot compile
```

The compiled CSS lands in `skins/stratus/styles/styles.min.css`.

### All Commands

```bash
npm start                # setup + start containers (plugins auto-install)
npm stop                 # stop all containers
npm run setup            # re-run setup only (pull image, extract elastic)
npm run docker:up        # start containers (skip setup)
npm run docker:down      # stop and remove containers
npm run docker:restart   # restart Roundcube container
npm run docker:logs      # tail Roundcube logs
npm run docker:logs:mail # tail mailserver logs
npm run docker:logs:all  # tail all container logs
npm run docker:ps        # show container status
npm run docker:shell     # bash into the container
npm run less:build       # one-shot LESS compile
npm run less:watch       # auto-recompile on save
```



## How It Works

Stratus extends elastic via `"extends": "elastic"` in `meta.json`. This means:

1. **Templates** — Elastic's templates are inherited. We only override `layout.html` (to inject our CSS) and `login.html` (custom login page). Everything else comes from elastic automatically.
2. **Styles** — Our `styles.less` imports elastic's full stylesheet first, then layers our variable overrides and custom partials on top.
3. **Dark mode** — Uses elastic's native `html.dark-mode` class + `@color-dark-*` variables. Our `_dark.less` adds supplemental rules.

## Plugin Dependency Map

- **`stratus_helper` → `stratus` skin (hard dependency)**
	- The plugin exits early unless active skin is `stratus`.
	- It injects runtime CSS variables (`--stratus-primary`, `--stratus-font-family`) used by Stratus styles.
- **`conversation_mode` ↔ `stratus` skin (integration dependency in this repo)**
	- Plugin logic is skin-agnostic (ships default + elastic CSS).
	- In this workspace, the Stratus `mail.html` override provides conversation containers (`#conv-list-content`, `#conv-detail`) used by `conversation_mode.js`.
	- Without this template integration, full Stratus conversation UI is not available.
- **`stratus_helper` ↔ `conversation_mode`**
	- No direct dependency. They can run independently.

## Database Support (Plugin Level)

- **`stratus_helper`**
	- No plugin-specific tables.
	- Stores user preferences through Roundcube preference storage.
- **`conversation_mode`**
	- No plugin-specific tables.
	- Builds conversation data from IMAP headers and caches in PHP session (`$_SESSION`).

### Supported DB Engines

Both plugins rely on Roundcube core APIs and do not require their own schema, so they work with any DB backend supported by Roundcube (SQLite, MySQL/MariaDB, PostgreSQL).

Dev environment default in this repo: **SQLite** (`db_dsnw = sqlite:////var/roundcube/db/sqlite.db`).


## AI-Assisted Development

This project includes AI agent definitions in `.github/agents/` for use with GitHub Copilot:

- **`@builder`** — Primary agent: reads roadmap, builds, compiles, validates, updates memory
- **`@stylist`** — Color palettes, typography, visual polish
- **`@templater`** — Roundcube template overrides
- **`@plugin-dev`** — PHP companion plugin (Phase 2)

See [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

Creative Commons Attribution-ShareAlike 3.0 (CC BY-SA 3.0) — see [skins/stratus/LICENSE](skins/stratus/LICENSE).
