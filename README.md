# Stratus — A Modern Roundcube Skin

Extends Roundcube's built-in `elastic` skin with an indigo color palette, full dark mode, frosted glass surfaces, and a Smart Bar for multiselect and mass actions.

---

## Installation

1. Download `stratus-X.Y.Z.zip` from the [Releases page](https://github.com/victorcastro89/stratus-skin/releases)
2. Unzip into your Roundcube root — it unpacks directly into `skins/` and `plugins/`
3. Add to `config/config.inc.php`:

```php
$config['skin'] = 'stratus';
$config['plugins'] = ['stratus_helper', 'undo_send'];
```

See [INSTALL.md](INSTALL.md) for config templates, upgrade steps, and troubleshooting.

---

## Development

**Requirements:** Docker Desktop, Node.js 18+

```bash
git clone https://github.com/victorcastro89/stratus-skin.git
cd stratus-skin
npm start                # first-time setup + Docker
```

Open http://localhost:8000 — login: `victor@example.test` / `password123`

```bash
npm run less:watch       # recompile LESS on save
npm run less:build       # one-shot compile
npm run release          # patch release (bumps version, tags, ready to push)
npm run release -- minor # minor release
npm run docker:logs      # tail Roundcube logs
npm run docker:shell     # shell into container
```

---

## License

[CC BY-SA 3.0](skins/stratus/LICENSE) — skin
[GPL-3.0](https://www.gnu.org/licenses/gpl-3.0.html) — plugins
