# Contributing to Stratus Skin

Thank you for your interest in contributing! This guide covers everything you need to get started.

## Getting Started

1. **Fork & clone** the repository:
   ```bash
   git clone <your-fork-url>
   cd stratus-skin
   ```

2. **First-time setup + start everything:**
   ```bash
   npm start
   ```


3. **Start the LESS watcher** (in a separate terminal):
   ```bash
   npm run less:watch
   ```

4. Open http://localhost:8000 — log in with `victor@example.test` / `password123`.

### Useful Commands

| Command | What it does |
|---|---|
| `npm start` | Setup + build + start containers |
| `npm stop` | Stop all containers |
| `npm run setup` | Re-run setup only (no Docker) |
| `npm run docker:up` | Start containers (skip setup) |
| `npm run docker:down` | Stop and remove containers |
| `npm run docker:restart` | Restart the Roundcube container |
| `npm run docker:logs` | Tail Roundcube logs |
| `npm run docker:logs:mail` | Tail mailserver logs |
| `npm run docker:logs:all` | Tail all container logs |
| `npm run docker:ps` | Show container status |
| `npm run docker:shell` | Open a bash shell in the container |
| `npm run less:build` | One-shot LESS compile |
| `npm run less:watch` | Auto-recompile on save |

## Project Architecture

### File Locations

| What you edit | Where it lives | Where Roundcube sees it |
|---|---|---|
| Skin LESS/templates | `skins/stratus/` | `roundcubemail/skins/stratus/` (symlink) |
| Companion plugin | `plugins/stratus_helper/` | `roundcubemail/plugins/stratus_helper/` (symlink) |
| Config template | `config/config.inc.php.dist` | `roundcubemail/config/config.inc.php` (generated) |
| Docker setup | `docker/` | N/A (used by `docker compose`) |

### LESS Compilation

The skin's entry point is `skins/stratus/styles/styles.less`. It imports in this order:

1. **Elastic's full stylesheet** (`../../elastic/styles/styles`) — provides the base
2. **`_fonts.less`** — redirects font paths to elastic's fonts directory
3. **`_variables.less`** — overrides elastic's `@color-*` variables (LESS lazy eval: last definition wins)
4. **`_typography.less`** → **`_animations.less`** → **`_layout.less`** → **`_components.less`** → **`_calendar.less`** → **`_dark.less`** → **`_login.less`**

**Build commands:**
```bash
npm run less:build     # one-shot compile → styles.min.css
npm run less:watch     # auto-recompile on any .less file change
npm run docker:logs    # tail Roundcube container logs
```

## Coding Conventions

### LESS / CSS

- **Partials** are prefixed with underscore: `_variables.less`, `_dark.less`
- **Custom CSS classes** use the `mp-` prefix: `mp-sidebar`, `mp-cal-actions`
- **Prefer variable overrides** over `!important`. Only use `!important` as a last resort.
- **Color values** must use LESS variables (not hardcoded hex) for dark mode adaptability
- **Dark mode rules** go in `_dark.less` using `html.dark-mode` selectors

### Templates

- Use `<roundcube:include file="..." skinPath="skins/elastic" />` to inherit from elastic
- Only override templates that **need** changes — elastic updates are inherited automatically
- Template overrides go in `skins/stratus/templates/`
- Plugin template overrides go in `skins/stratus/plugins/<plugin>/templates/`

### General

- File names: **lowercase with underscores** (e.g., `_variables.less`, `calendar.html`)
- Add a **brief purpose comment** at the top of every new file
- Commit messages: use [conventional commits](https://www.conventionalcommits.org/) (`feat:`, `fix:`, `style:`, `chore:`)

## Making Changes

### Adding a new LESS partial

1. Create `skins/stratus/styles/_yourpartial.less`
2. Add `@import "_yourpartial";` in `styles.less` (before `_dark.less` and `_login.less`)
3. Run `npm run less:build` to verify compilation
4. Add dark mode rules in `_dark.less` if needed

### Overriding a Roundcube template

1. Find the elastic template in `roundcubemail/skins/elastic/templates/`
2. Copy it to the same relative path under `skins/stratus/templates/`
3. Where possible, include the parent: `<roundcube:include file="..." skinPath="skins/elastic" />`
4. Add your customizations around the include

### Adding a new color variant

The design system variables are in `skins/stratus/styles/_variables.less` (~230 vars). The primary palette starts with `@mp-primary-*` and `@mp-accent-*`.

## Testing

- **Visual testing**: Check both light and dark mode in the browser
- **Dark mode**: Toggle via Roundcube Settings → General → Interface skin (Dark mode option), or use the browser's `prefers-color-scheme` override in DevTools
- **LESS compilation**: `npm run less:build` must exit 0 with no errors
- **Multiple browsers**: Test in Chrome, Firefox, and Safari

## AI-Assisted Workflow (Optional)

This project has AI agent definitions in `.github/agents/`. If you use GitHub Copilot:

```
@builder start Phase 1          ← begin a phase
@builder continue               ← pick up where you left off
/build-next                     ← reusable prompt for roadmap work
/compile-and-validate           ← just compile and run QA checks
```

The AI memory system (`.github/memory/`) tracks project state — agents read it before work and update it after.

## Pull Request Checklist

- [ ] `npm run less:build` compiles with 0 errors
- [ ] Tested in light mode AND dark mode
- [ ] No hardcoded hex colors (use LESS variables)
- [ ] Custom classes use `mp-` prefix
- [ ] New files have a purpose comment at the top
- [ ] Template overrides are minimal (inherit from elastic where possible)
