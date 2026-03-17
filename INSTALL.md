# Installing Stratus

Stratus consists of:
- **`skins/stratus/`** — the skin (required)
- **`plugins/stratus_helper/`** — runtime theming, font prefs, Smart Bar (required)
- **`plugins/undo_send/`** — undo-send countdown toast (optional)

## Requirements

| Dependency | Minimum version |
|---|---|
| Roundcube | 1.6.0 |
| PHP | 7.4 |
| Elastic skin | bundled with Roundcube 1.6+ |

---

## Install

1. Download the latest release zip from the [Releases page](https://github.com/victorcastro89/stratus-skin/releases).

2. Extract and copy directories into your Roundcube installation:

   ```
   skins/stratus/              →  <roundcube>/skins/stratus/
   plugins/stratus_helper/     →  <roundcube>/plugins/stratus_helper/
   plugins/undo_send/          →  <roundcube>/plugins/undo_send/   (optional)
   ```

3. Copy the config templates:

   ```bash
   cp plugins/stratus_helper/config.inc.php.dist  plugins/stratus_helper/config.inc.php
   cp plugins/undo_send/config.inc.php.dist        plugins/undo_send/config.inc.php
   ```

   Edit each `config.inc.php` to match your preferences (all settings have documented defaults).

4. Add to your Roundcube `config/config.inc.php`:

   ```php
   $config['skin'] = 'stratus';

   $config['plugins'] = [
       'stratus_helper',
       'undo_send',      // optional
       // ... your other plugins
   ];
   ```

---

## Verifying the install

1. Open Roundcube in your browser and log in.
2. The login page should display the Stratus indigo theme.
3. Navigate to **Settings → Appearance** to confirm the Stratus preferences panel is visible.
4. Toggle dark mode via the moon icon in the toolbar.

---

## Upgrading

Replace the `skins/stratus/` and `plugins/*/` directories with the new release. Your `config.inc.php` files in each plugin directory are preserved — only compare against the updated `.dist` file if the release notes mention config changes.

---

## Troubleshooting

**Skin not appearing in Settings → Preferences → User Interface**
Check that `skins/stratus/meta.json` exists and is valid JSON.

**Smart Bar or dark mode not working**
Confirm `stratus_helper` is listed in `$config['plugins']` and that the skin is set to `stratus`. The plugin exits silently if the active skin is not Stratus.

**CSS looks broken after upgrade**
The compiled CSS (`styles/styles.min.css`) is bundled in the release — no build step is needed. If you edited LESS source files, re-run `npm run less:build`.
