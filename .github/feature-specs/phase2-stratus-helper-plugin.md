# Phase 2 — Stratus Helper Companion Plugin

> Feature spec for the `stratus_helper` Roundcube plugin.
> Provides runtime customization: folder refresh, color schemes, font preference, and user settings UI.

---

## Overview

The `stratus_helper` plugin is a companion to the stratus skin. It adds server-side features that cannot be achieved with CSS/templates alone:

- **Folder list refresh** — hot-swap `#mailboxlist` HTML after move/archive without full page reload
- **Color scheme switching** — runtime primary color changes via user preference
- **Font preference** — Google Fonts integration with a font picker
- **User preferences UI** — a settings panel under Settings → Stratus for all skin options
- **Preference persistence** — saves choices to Roundcube user DB
- **Localization** — multi-language strings
- **Plugin config** — `config.inc.php.dist` with sensible defaults

---

## Step 1 — Plugin Directory Structure & Docker Mount

### Files to create in `plugins/stratus_helper/`

```
plugins/stratus_helper/
├── stratus_helper.php            # Main plugin class
├── composer.json                 # Package metadata
├── config.inc.php.dist           # Default configuration
├── stratus_helper.js             # Client-side JS (folder refresh, color/font switching)
├── skins/
│   └── elastic/
│       └── stratus_helper.css    # Preferences UI styles (inherits elastic patterns)
├── localization/
│   └── en_US.inc                 # English strings
└── lib/
    └── stratus_helper_prefs.php  # Preference handling helper class
```

### Docker Compose

Add bind mount to `docker/docker-compose.yml` under the roundcube service volumes:

```yaml
- ../plugins/stratus_helper:/var/www/html/plugins/stratus_helper:delegated
```

### Roundcube Config

Add `stratus_helper` to `$config['plugins']` in `docker/config/custom.inc.php`.

---

## Step 2 — Main Plugin Class (`stratus_helper.php`)

### Class: `stratus_helper extends rcube_plugin`

**Properties:**
- `$task = 'mail|settings'` — active in mail (folder refresh) and settings (preferences)

**`init()` method:**
1. `$this->load_config('config.inc.php.dist')` — load defaults
2. `$this->load_config()` — load user overrides
3. `$this->add_texts('localization/', true)` — load translations
4. If `$rcmail->task === 'mail'` → call `init_mail()`
5. If `$rcmail->task === 'settings'` → call `init_settings()`
6. If skin is stratus → inject CSS variables from user prefs into `<head>`

**`init_mail()` method:**
1. `$this->include_script('stratus_helper.js')` — client JS
2. `$this->register_action('plugin.stratus.refresh_folders', [$this, 'action_refresh_folders'])`
3. Push user prefs to JS env:
   - `stratus_color_scheme` → current color scheme name
   - `stratus_font_family` → current font choice
   - `stratus_font_url` → Google Fonts URL (if custom font)

**`init_settings()` method:**
1. Hook into `preferences_sections_list` → add "Stratus" section
2. Hook into `preferences_list` → render stratus pref fields
3. Hook into `preferences_save` → persist stratus prefs

---

## Step 3 — Refresh Folders Action

### Server side (`stratus_helper.php`)

**`action_refresh_folders()` method:**
1. Call `$rcmail->get_storage()->list_folders_subscribed()` to get folder list
2. Use `rcmail_action_mail_index::render_folder_tree_html()` or build the HTML via `$rcmail->output->get_env('mailboxes')` — simplest approach: use `rcmail_output_html::folder_list()` pattern
3. Actually, the cleanest Roundcube way: trigger the template object rendering:
   ```php
   $html = $this->rcmail->output->just('mailboxlist');
   ```
   Since that's not a real method, we use the hook approach:
   ```php
   // Get the mailbox list HTML by re-rendering the template object
   $storage = $this->rcmail->get_storage();
   $mbox_list = rcmail_action_mail_index::folder_selector([
       'folder_filter' => 'mail',
   ]);
   ```
   
   **Simplest approach:** Use `rcmail_output_json` to send a command:
   ```php
   public function action_refresh_folders()
   {
       $rcmail = rcmail::get_instance();
       $storage = $rcmail->get_storage();

       // Force refresh of the folder cache
       $storage->clear_cache('mailboxes', true);

       // Rebuild the mailbox list using Roundcube's built-in method
       // This triggers a client-side list refresh
       $rcmail->output->command('plugin.stratus.replace_folderlist', [
           'action' => 'refresh'
       ]);
       $rcmail->output->send();
   }
   ```

### Client side (`stratus_helper.js`)

Wire in `pagenav.html`:
```js
// Replace the TODO stub in responseaftermove listener:
rcmail.http_request('plugin.stratus.refresh_folders', {});
```

And register a listener:
```js
rcmail.addEventListener('plugin.stratus.replace_folderlist', function(data) {
    // Trigger Roundcube's built-in folder list refresh
    rcmail.command('getunread', '', this);
    // Or use the subscription list refresh
    rcmail.http_request('getunread', '');
});
```

**Refined approach after research:** Roundcube already has `rcmail.command('getunread')` which refreshes unread counts. For a full folder list refresh, the best approach is:
```js
rcmail.addEventListener('plugin.stratus.replace_folderlist', function() {
    // Force a list refresh by requesting a page reload of the folder list
    if (rcmail.gui_objects.mailboxlist) {
        rcmail.http_request('list', '_refresh=1&_mbox=' + encodeURIComponent(rcmail.env.mailbox));
    }
});
```

---

## Step 4 — Color Scheme Switching

### Available Schemes

Define in `config.inc.php.dist`:
```php
$config['stratus_color_schemes'] = [
    'indigo'  => ['primary' => '#5c6bc0', 'primary_dark' => '#7986cb', 'label' => 'Indigo (Default)'],
    'ocean'   => ['primary' => '#0288d1', 'primary_dark' => '#4fc3f7', 'label' => 'Ocean Blue'],
    'emerald' => ['primary' => '#2e7d32', 'primary_dark' => '#66bb6a', 'label' => 'Emerald'],
    'rose'    => ['primary' => '#c62828', 'primary_dark' => '#ef5350', 'label' => 'Rose'],
    'amber'   => ['primary' => '#f57f17', 'primary_dark' => '#ffca28', 'label' => 'Amber'],
    'purple'  => ['primary' => '#7b1fa2', 'primary_dark' => '#ba68c8', 'label' => 'Purple'],
    'teal'    => ['primary' => '#00796b', 'primary_dark' => '#4db6ac', 'label' => 'Teal'],
    'slate'   => ['primary' => '#455a64', 'primary_dark' => '#90a4ae', 'label' => 'Slate'],
];
```

### Implementation

**Server side:**
- In `init_mail()` and on all tasks when skin is stratus, inject a `<style>` block with CSS custom properties based on user's chosen scheme:
  ```php
  $scheme_name = $rcmail->config->get('stratus_color_scheme', 'indigo');
  $schemes = $rcmail->config->get('stratus_color_schemes');
  $scheme = $schemes[$scheme_name] ?? $schemes['indigo'];
  
  $css = ":root { --stratus-primary: {$scheme['primary']}; --stratus-primary-dark: {$scheme['primary_dark']}; }";
  $rcmail->output->add_header("<style id=\"stratus-scheme\">$css</style>");
  ```

**Skin side (LESS/CSS):**
- Add CSS custom properties to `_variables.less` that reference the scheme:
  ```less
  :root {
      --stratus-primary: @color-main;
      --stratus-primary-dark: @color-dark-main;
  }
  ```
- Update key component rules to use `var(--stratus-primary)` where runtime switching is needed (taskmenu glow, buttons, badges, selection highlights)
- Keep LESS `@color-main` as the compile-time default; CSS vars override at runtime

**Client side (`stratus_helper.js`):**
- AJAX endpoint `plugin.stratus.set_scheme` saves preference and returns updated CSS vars
- Apply immediately by updating `document.documentElement.style.setProperty('--stratus-primary', value)`

---

## Step 5 — Font Preference

### Available Fonts

Default config:
```php
$config['stratus_fonts'] = [
    'system'     => ['family' => "system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif", 'url' => null, 'label' => 'System (Default)'],
    'inter'      => ['family' => "'Inter', sans-serif", 'url' => 'https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap', 'label' => 'Inter'],
    'roboto'     => ['family' => "'Roboto', sans-serif", 'url' => 'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap', 'label' => 'Roboto'],
    'open-sans'  => ['family' => "'Open Sans', sans-serif", 'url' => 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap', 'label' => 'Open Sans'],
    'lato'       => ['family' => "'Lato', sans-serif", 'url' => 'https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap', 'label' => 'Lato'],
    'poppins'    => ['family' => "'Poppins', sans-serif", 'url' => 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap', 'label' => 'Poppins'],
    'nunito'     => ['family' => "'Nunito', sans-serif", 'url' => 'https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap', 'label' => 'Nunito'],
];
```

### Implementation

**Server side:**
- On page load (all tasks, stratus skin), check user's font pref
- If font has a URL, inject `<link>` for Google Fonts
- Inject `<style>` with `body { font-family: ... !important; }` override
- Push font info to JS env

**Client side:**
- AJAX endpoint `plugin.stratus.set_font` saves pref
- On change: dynamically load/remove Google Font `<link>`, update `body.style.fontFamily`

---

## Step 6 — User Preferences UI

### Settings → Stratus Section

Add a new preferences section "Stratus" (or "Appearance") with these fields:

| Field | Type | Description |
|-------|------|-------------|
| Color Scheme | `<select>` with color swatches | Dropdown listing all schemes with small color circle preview |
| Font Family | `<select>` with font preview | Each option rendered in its own font (via `style` attr) |
| (Future) Density | `<select>` | Compact / Comfortable / Spacious — placeholder for Phase 3 |

### Implementation

**`preferences_sections_list` hook:**
```php
$args['list']['stratus'] = [
    'id'      => 'stratus',
    'section' => $this->gettext('section_title'),  // "Stratus Appearance"
];
```

**`preferences_list` hook (section=stratus):**
- Build select elements for color scheme and font
- Include a live preview strip (optional: small colored bar that updates via JS)

**`preferences_save` hook (section=stratus):**
- Sanitize and save `stratus_color_scheme` and `stratus_font_family`

---

## Step 7 — Preference Persistence

All preferences stored via Roundcube's standard `$rcmail->user->save_prefs()` mechanism:

| Preference Key | Type | Default | Description |
|---------------|------|---------|-------------|
| `stratus_color_scheme` | string | `'indigo'` | Active color scheme name |
| `stratus_font_family` | string | `'system'` | Active font key |

**`dont_override` support:** Check `$config['dont_override']` before showing/saving each pref (admin can lock prefs).

---

## Step 8 — Localization

### `localization/en_US.inc`

```php
$labels = [];
$labels['section_title'] = 'Stratus Appearance';
$labels['color_scheme'] = 'Color Scheme';
$labels['font_family'] = 'Font';
$labels['scheme_indigo'] = 'Indigo (Default)';
$labels['scheme_ocean'] = 'Ocean Blue';
$labels['scheme_emerald'] = 'Emerald';
$labels['scheme_rose'] = 'Rose';
$labels['scheme_amber'] = 'Amber';
$labels['scheme_purple'] = 'Purple';
$labels['scheme_teal'] = 'Teal';
$labels['scheme_slate'] = 'Slate';
$labels['font_system'] = 'System (Default)';
$labels['font_inter'] = 'Inter';
$labels['font_roboto'] = 'Roboto';
$labels['font_open_sans'] = 'Open Sans';
$labels['font_lato'] = 'Lato';
$labels['font_poppins'] = 'Poppins';
$labels['font_nunito'] = 'Nunito';
$labels['appearance_saved'] = 'Appearance settings saved.';
$labels['folder_refresh'] = 'Refresh folder list';
```

---

## Step 9 — Plugin Config (`config.inc.php.dist`)

```php
<?php
// Default color scheme key (must exist in stratus_color_schemes)
$config['stratus_color_scheme_default'] = 'indigo';

// Available color schemes (admin can add/remove)
$config['stratus_color_schemes'] = [ ... ]; // full list above

// Default font key
$config['stratus_font_default'] = 'system';

// Available fonts (admin can add/remove)
$config['stratus_fonts'] = [ ... ]; // full list above

// Enable folder refresh after move/archive
$config['stratus_folder_refresh'] = true;
```

---

## Integration Points

### 1. `pagenav.html` — Folder Refresh Wiring

Replace the `TODO` console.log in `responseaftermove` listener with:
```js
rcmail.http_request('plugin.stratus.refresh_folders', {});
```

Add handler:
```js
rcmail.addEventListener('plugin.stratus.replace_folderlist', function() {
    rcmail.command('getunread', '', this);
});
```

### 2. `layout.html` — CSS Custom Properties

Add CSS custom property defaults to the `<style>` block or as a new `<style id="stratus-vars">` block injected by the plugin.

### 3. Skin LESS — CSS Variable Bridge

In `_variables.less` add:
```less
:root {
    --stratus-primary: @color-main;
    --stratus-primary-dark: @color-dark-main;
    --stratus-font-family: @mp-font-family;
}
```

Key components that use `var(--stratus-primary)` for runtime switching:
- `.button.primary` background
- `#taskmenu` selected glow
- `.badge` background
- Selection highlight
- Link color
- Input focus border

---

## Implementation Order

1. ✅ Create this spec
2. Plugin directory + `composer.json` + docker mount + config registration
3. `config.inc.php.dist` with all defaults
4. `localization/en_US.inc`
5. `stratus_helper.php` — main class with init, folder refresh action
6. `stratus_helper.js` — client JS (folder refresh + color/font switching)
7. Wire `pagenav.html` integration (replace TODO stub)
8. Add CSS custom property bridge to `_variables.less` + key component updates
9. Color scheme injection (server-side `<style>` block)
10. Font injection (server-side `<link>` + `<style>`)
11. Preferences UI (settings section + fields)
12. Preference persistence (save/load hooks)
13. `skins/elastic/stratus_helper.css` — settings page styles
14. Compile & validate
15. Update memory files
