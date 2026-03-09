# Feature Spec: Undo Send

**Status:** IMPLEMENTED

## Roadmap Reference

- **Phase:** Standalone plugin (skin-agnostic)
- **Section:** Compose UX improvements
- **Items:** Undo-send delay with messagestack toast notification

## Summary

A standalone Roundcube plugin (`undo_send`) that provides Gmail-style "undo send" functionality. When the user clicks Send, message delivery is delayed by a configurable number of seconds (default: 5). During this window, a Roundcube-native `#messagestack` toast notification shows a countdown with an "Undo" link. If the user clicks Undo, sending is cancelled and the compose form stays open. If the timer expires, the message is sent normally. The plugin is **skin-agnostic** — it works with elastic, larry-based skins, and stratus. No database, no cron job, no external dependencies, no API key — purely client-side with a thin server-side preference layer.

## Goals

- Provide a safety net for accidental sends (wrong recipient, missing attachment, typos)
- Use Roundcube's native `#messagestack` notification bar — no modal dialogs, no overlays
- **Skin-agnostic:** works on elastic, larry-based skins, and stratus out of the box
- Zero external dependencies — no xframework, no API key, no license
- Configurable delay (disable / 3 / 5 / 10 / 15 / 30 seconds) via Settings → Undo Send
- Works in both light and dark mode (elastic/stratus)
- Works in normal compose window and external (popup) compose window

## Non-Goals

- **Email scheduling** (send at a future time) — that's a separate feature
- **Server-side message queuing** — no database, no cron
- **Undo after the timer expires** — once SMTP delivery starts, it's final
- **Skin-specific visual customizations beyond basic styling** — the plugin relies on native `#messagestack` which every skin already styles

## User Experience

### Compose → Send Flow

1. User composes an email and clicks **Send** (or presses Ctrl+Enter)
2. Instead of immediate SMTP delivery:
   - The compose form **locks** (inputs disabled, send button disabled) to prevent edits
   - A `#messagestack` toast appears: **"Sending in 5s… [Undo]"** 
   - The countdown ticks: 5… 4… 3… 2… 1…
   - Toast text updates each second: "Sending in 4s… [Undo]"
3. **If timer expires:** The toast changes to "Sending…" (loading type), the real `rcmail.command('send')` fires, normal Roundcube send flow takes over (redirect to inbox, "Message sent" toast)
4. **If user clicks "Undo":** Timer is cancelled, compose form is unlocked, toast shows "Sending cancelled" (notice type, auto-dismiss after 3s), user can continue editing

### Settings → Undo Send

A new settings section under "Preferences":

| Setting | Options | Default |
|---------|---------|---------|
| Undo send delay | Disabled, 3 seconds, 5 seconds, 10 seconds, 15 seconds, 30 seconds | 5 seconds |

This section appears in the standard Roundcube Settings → Preferences panel, accessible from any skin.

### Edge Cases

- **Undo send disabled (0):** Send button works exactly as stock Roundcube — no interception
- **External compose window:** Toast appears in the popup's own `#messagestack` (Roundcube propagates `display_message` to parent via `is_framed()`)
- **Draft auto-save during countdown:** Blocked — we prevent auto-save from firing while countdown is active
- **User closes browser/tab during countdown:** Message is NOT sent (compose data is lost) — this is acceptable and matches Gmail behavior
- **Multiple rapid Send clicks:** Ignored after first click (send button is disabled)

## Technical Design

### Architecture: Pure Client-Side Interception

The entire feature is client-side JavaScript running in the compose page. The only server-side component is the preferences UI (standard Roundcube `rcube_plugin` pattern).

```
┌─────────────────────────────────────────────────┐
│  Compose Page                                   │
│                                                 │
│  [Send] click                                   │
│    │                                            │
│    ▼                                            │
│  undo_send.js: interceptSend()                  │
│    │                                            │
│    ├─ delay == 0? ──▶ rcmail.command('send')    │
│    │                                            │
│    ├─ Lock compose form                         │
│    ├─ Show #messagestack toast with countdown   │
│    ├─ Start setInterval(1s)                     │
│    │    │                                       │
│    │    ├─ tick: update toast text               │
│    │    └─ expired: fire real send               │
│    │                                            │
│    └─ "Undo" clicked:                           │
│         ├─ clearInterval                        │
│         ├─ Unlock compose form                  │
│         ├─ Hide countdown toast                 │
│         └─ Show "Sending cancelled" toast       │
└─────────────────────────────────────────────────┘
```

### Plugin Structure

```
plugins/undo_send/
├── undo_send.php              # Main plugin class (extends rcube_plugin)
├── undo_send.js               # Client-side JS (send interception + countdown)
├── config.inc.php.dist        # Default config
├── composer.json               # Package metadata
├── localization/
│   └── en_US.inc              # English labels
└── skins/
    ├── default/
    │   └── undo_send.css      # Baseline CSS (works with any skin, including larry)
    └── elastic/
        └── undo_send.css      # Elastic/stratus overrides (dark mode, modern styling)
```

This follows the same pattern as `conversation_mode`: `skins/default/` for baseline, `skins/elastic/` for elastic-family overrides (elastic + stratus, since stratus extends elastic).

### Skin Compatibility

| Skin family | How it works |
|-------------|-------------|
| **Elastic** | Loads `skins/default/undo_send.css` + `skins/elastic/undo_send.css`. Modern button styles, dark mode via `html.dark-mode`. |
| **Stratus** | Same as elastic — stratus extends elastic, so `local_skin_path()` resolves to `skins/elastic/`. Stratus's existing `#messagestack` theming applies automatically. |
| **Larry / larry-based** | Loads `skins/default/undo_send.css` only. Larry has its own `#messagestack` styling; the baseline CSS is skin-neutral. No dark mode (larry doesn't support it). |

The PHP loader uses the standard Roundcube pattern:

```php
// Always load baseline
$this->include_stylesheet('skins/default/undo_send.css');
// Layer skin-specific override on top if it exists
$skin_css = $this->local_skin_path() . '/undo_send.css';
if ($skin_css !== 'skins/default/undo_send.css'
    && file_exists($this->home . '/' . $skin_css)) {
    $this->include_stylesheet($skin_css);
}
```

### Implementation Detail: JS Send Interception

**How xemail_schedule does it (reference):**
```js
// Replaces onclick attribute on send buttons:
$("#messagetoolbar a.button.send, #compose-content button.btn.send, #button-send")
    .attr("onclick", "return xemailSchedule.sendClick(this, event)");
```

**How we'll do it (cleaner):**
```js
// Override rcmail.command for 'send' — this is the standard Roundcube
// plugin pattern and catches all send triggers (button, Ctrl+Enter, etc.)
var _origCommand = rcmail.command;
rcmail.command = function(command, props, obj, event) {
    if (command === 'send' && !undoSend.bypassing) {
        return undoSend.intercept(props, obj, event);
    }
    return _origCommand.apply(this, arguments);
};
```

This intercepts ALL send triggers — button click, keyboard shortcut, menu action — without fragile DOM selector patching. Works identically on elastic, larry, and stratus because `rcmail.command` is skin-agnostic.

### Implementation Detail: Messagestack Toast

Roundcube's `rcmail.display_message(msg, type, timeout, key)` creates a `<div>` inside `#messagestack`. This API is **identical across all skins** — `#messagestack` is a core Roundcube GUI element. We use it with:

```js
// Show the countdown toast (type 'notice' = blue/neutral)
var msgId = rcmail.display_message(
    'Sending in ' + seconds + 's… <a href="#" onclick="undoSend.cancel(); return false;" class="mp-undo-link">Undo</a>',
    'notice',
    0,  // timeout=0 means persistent (we manage removal ourselves)
    'undo-send-countdown'  // unique key to update in-place
);
```

**Updating the toast each second:** Roundcube's `display_message` with the same `key` replaces the existing toast's content — perfect for countdown updates.

**Removing the toast:** `rcmail.hide_message('undo-send-countdown')`.

### Implementation Detail: Compose Form Lock/Unlock

Both elastic and larry use `#compose-content` as the compose form container. The lock/unlock targets are skin-agnostic:

```js
// Lock: disable form inputs and send button
$('#compose-content input, #compose-content select, #compose-content textarea').prop('disabled', true);
// Elastic: <button class="btn send">, Larry: <a class="button send">
$('.button.send, .btn.send, #button-send').addClass('disabled').prop('disabled', true);

// Unlock: reverse all of the above
```

### Implementation Detail: PHP Plugin Class

The plugin extends `rcube_plugin` directly (no xframework dependency):

```php
class undo_send extends rcube_plugin
{
    public $task = 'mail|settings';

    private $rcmail;

    public function init()
    {
        $this->rcmail = rcmail::get_instance();
        $this->load_config('config.inc.php.dist');
        $this->load_config();
        $this->add_texts('localization/', true);

        if ($this->rcmail->task === 'mail' && $this->rcmail->action === 'compose') {
            $this->init_compose();
        }

        if ($this->rcmail->task === 'settings') {
            $this->init_settings();
        }
    }

    private function init_compose()
    {
        // Load CSS: default baseline + skin-specific overlay
        $this->include_stylesheet('skins/default/undo_send.css');
        $skin_css = $this->local_skin_path() . '/undo_send.css';
        if ($skin_css !== 'skins/default/undo_send.css'
            && file_exists($this->home . '/' . $skin_css)) {
            $this->include_stylesheet($skin_css);
        }

        $this->include_script('undo_send.js');

        // Push delay preference to client
        $delay = (int) $this->rcmail->config->get('undo_send_delay', 5);
        $this->rcmail->output->set_env('undo_send_delay', $delay);

        // Push localized labels to client
        $this->rcmail->output->add_label(
            'undo_send.sending_in',
            'undo_send.undo',
            'undo_send.sending_cancelled',
            'undo_send.sending'
        );
    }

    private function init_settings()
    {
        $this->add_hook('preferences_sections_list', [$this, 'prefs_section']);
        $this->add_hook('preferences_list',          [$this, 'prefs_list']);
        $this->add_hook('preferences_save',          [$this, 'prefs_save']);
    }

    // ... standard prefs_section/prefs_list/prefs_save methods
}
```

### Implementation Detail: CSS for Undo Link

**`skins/default/undo_send.css`** (baseline — works with larry and any skin):

```css
/* Undo link inside the messagestack toast */
#messagestack .mp-undo-link {
    font-weight: bold;
    text-decoration: underline;
    margin-left: 0.5em;
    cursor: pointer;
}
```

**`skins/elastic/undo_send.css`** (elastic/stratus overrides):

```css
/* Elastic: inherit toast text color, add hover effect */
#messagestack .mp-undo-link {
    color: inherit;
    font-weight: 600;
    text-decoration: underline;
    margin-left: 0.5em;
    cursor: pointer;
    transition: opacity 0.15s ease;
}

#messagestack .mp-undo-link:hover {
    opacity: 0.8;
}

/* Dark mode handled automatically: #messagestack styling is already themed
   by elastic/stratus. color:inherit picks up the correct text color. */
```

No LESS variables needed — the link inherits its color from the `#messagestack` toast, which every skin already themes.

## Files Changed

### New Files (plugin)

| File | Purpose |
|------|---------|
| `plugins/undo_send/undo_send.php` | Main plugin: init, asset loading, preferences section/list/save, push env to client |
| `plugins/undo_send/undo_send.js` | Client-side: `rcmail.command` override, countdown state machine, messagestack toast, form lock/unlock |
| `plugins/undo_send/config.inc.php.dist` | Default config: `$config['undo_send_delay'] = 5;` |
| `plugins/undo_send/composer.json` | Package metadata (`roundcube-plugin` type) |
| `plugins/undo_send/localization/en_US.inc` | English labels: `undo_send_delay`, `sending_in`, `undo`, `sending_cancelled`, delay option labels |
| `plugins/undo_send/skins/default/undo_send.css` | Baseline CSS (skin-neutral, works with larry) |
| `plugins/undo_send/skins/elastic/undo_send.css` | Elastic/stratus overrides (dark mode, transitions) |

### Modified Files (integration)

| File | Change |
|------|--------|
| `docker/config/roundcube.config.inc.php` | Add `'undo_send'` to `$config['plugins']` array |

### No Changes to `stratus_helper` or skin LESS files

The plugin is completely self-contained.

## Dark Mode Considerations

- The `#messagestack` toast already has proper dark mode styling in elastic/stratus
- The "Undo" link uses `color: inherit` so it automatically matches the toast's text color in both modes
- No additional dark mode rules needed — `#messagestack .notice`, `.error`, `.confirmation` divs are already themed by every skin
- Larry-based skins have no dark mode — no special handling required

## Validation Criteria

- [ ] **Pref disabled (0):** Send button sends immediately with no interception
- [ ] **Pref enabled (5s):** Clicking Send shows messagestack toast "Sending in 5s… Undo"
- [ ] **Countdown ticks:** Toast updates each second: 5→4→3→2→1
- [ ] **Timer expires:** Message is sent, "Message sent" toast appears, compose closes
- [ ] **Undo clicked at 3s:** Timer cancels, "Sending cancelled" toast, compose stays open and unlocked
- [ ] **Ctrl+Enter trigger:** Same interception as button click
- [ ] **External compose window:** Toast appears correctly in popup window
- [ ] **Multiple send clicks:** Only first click starts countdown
- [ ] **Settings section:** "Undo Send" section appears in Settings → Preferences
- [ ] **Setting persists:** Changing delay to 10s, refreshing, composing → shows 10s countdown
- [ ] **Elastic skin:** Toast and Undo link render correctly (light + dark mode)
- [ ] **Stratus skin:** Toast inherits stratus theming, Undo link visible
- [ ] **Larry skin:** Toast uses larry's `#messagestack` styling, functional
- [ ] **No console errors:** Clean JS console during entire flow
- [ ] **No external dependencies:** Plugin works standalone, no xframework, no API key

## Risks / Open Questions

1. **`rcmail.command` override safety:** Other plugins may also override `rcmail.command`. Our override wraps the existing one, so it chains correctly. But if another plugin's override doesn't call through, ours could be skipped. Mitigation: We wrap at `init` event time, which runs after all plugins are loaded.

2. **Compose form lock granularity:** Disabling all inputs might interfere with TinyMCE (rich text editor). We may need to also call `tinymce.activeEditor.getBody().setAttribute('contenteditable', 'false')` during the countdown. Needs testing.

3. **`is_framed()` behavior:** In Roundcube, the compose page runs inside an iframe. `display_message` auto-propagates to parent. We need to ensure the toast appears in the **compose iframe** (so the user sees it), not just the parent. May need to call `display_message` directly on the compose frame's rcmail instance.

4. **Race condition on rapid Undo → Send → Undo:** Need a clean state machine to prevent the bypass flag from getting stuck. The implementation should use a simple state enum: `idle` → `counting` → `sending` / `cancelled`.

5. **Larry-specific selectors:** Larry uses `<a class="button send">` vs elastic's `<button class="btn send">`. The JS lock/unlock logic targets both patterns. Verify on a larry skin instance.

## Appendix: xemail_schedule Reverse Engineering Notes

### What We Learned

The `xemail_schedule` plugin from RoundcubePlus (v1.2.7) implements two features:

1. **Email Scheduling** — Intercepts `message_before_send` hook, serializes the full `Mail_mime` object to a MySQL/Postgres DB table (`xemail_schedule_queue`), and a cron job (`cron.php`) sends due messages every minute. Stores SMTP credentials (encrypted) with the message. This is complex and we do NOT need it.

2. **Countdown Timer** — Purely client-side. On send click, shows a modal overlay (`#xes-countdown-mask`, `position: fixed; z-index: 999; background: rgba(0,0,0,.7)`) with a countdown circle and "Send now" / "Cancel" buttons. No server interaction during countdown. This is the feature we're reimagining.

### Skin Support in xemail_schedule

xemail_schedule handles multi-skin support via `.xlarry` / `.xelastic` CSS class selectors that xframework injects on the `<html>` element. This couples the plugin to xframework. Our plugin uses Roundcube's standard `local_skin_path()` + `skins/` directory convention instead, which is framework-free.

### Key Differences from Our Approach

| Aspect | xemail_schedule | Our undo_send |
|--------|----------------|---------------|
| UI pattern | Full-screen semi-transparent modal overlay | Native `#messagestack` toast (non-blocking) |
| Dependency | Requires `xframework` (commercial) | Zero dependencies |
| License | Commercial, API key required | Free, GPLv3+ |
| Server component | DB table + cron for scheduling | None (prefs only) |
| Skin support | `.xlarry` / `.xelastic` classes (xframework) | Standard `skins/default/` + `skins/elastic/` (native RC) |
| Send interception | `$().attr("onclick", ...)` on specific buttons | `rcmail.command` override (catches all triggers) |
| Toast replacement | MutationObserver on `#messagestack` | Direct `display_message()` API call |
