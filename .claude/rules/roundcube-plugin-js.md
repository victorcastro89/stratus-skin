---
name: Roundcube JavaScript API
description: JS client API — rcmail events, commands, buttons, containers, AJAX patterns for plugin development
type: reference
paths:
  - "plugins/**/*.js"
  - "skins/**/*.js"
---

# Roundcube JavaScript API

The JS side revolves around `window.rcmail` — methods for events, commands, buttons, and AJAX.

## Event listeners

```js
rcmail.addEventListener('event', callback);
rcmail.removeEventListener('event', callback);
```

### Key events
- `init` — first event on page load, use this to register UI elements
- `selectfolder` — folder selection changed
- `listupdate` — message list or folder list updated
- `insertrow` — row inserted into message list
- `before<command>` / `after<command>` — fire around commands only (not arbitrary events)

Always use `init` to register UI elements, commands, and listeners.

## Commands and buttons

```js
// Register a command with handler
rcmail.register_command('plugin.mycmd', handlerFn, true);

// Toggle command + linked buttons enabled/disabled
rcmail.enable_command('plugin.mycmd', true);

// Associate a DOM element with a command (auto-enables/disables with command)
rcmail.register_button('plugin.mycmd', 'DOM_ID', 'link', actClass, selClass, overClass);

// Add element to a named skin container (taskbar, toolbar, etc.)
rcmail.add_element(node, 'toolbar');
```

### Full button pattern
```js
rcmail.addEventListener('init', function() {
  var btn = $('<a>')
    .attr('id', 'rcmMyButton')
    .addClass('button')
    .html(rcmail.gettext('mybtn', 'my_plugin'))
    .on('click', function() {
      return rcmail.command('plugin.mycmd', this);
    });

  rcmail.add_element(btn.get(0), 'toolbar');
  rcmail.register_button('plugin.mycmd', 'rcmMyButton', 'link');
  rcmail.register_command('plugin.mycmd', function() {
    rcmail.http_post('plugin.mycmd', {});
    return true;
  }, true);
});
```

## AJAX communication

```js
// Send request to plugin action
rcmail.http_post('plugin.someaction', { foo: 'bar' });
rcmail.http_get('plugin.someaction', { foo: 'bar' });

// Receive response from server
rcmail.addEventListener('plugin.somecallback', function(response) {
  rcmail.display_message(response.message, 'confirmation');
});
```

Server sends responses via `$rcmail->output->command('plugin.somecallback', $data)`.

## Rules

**Do:**
- Always guard with `if (window.rcmail) { ... }`.
- Register everything inside `rcmail.addEventListener('init', ...)`.
- Use `register_command` + `register_button` + `enable_command` — treat commands as the main UI abstraction.
- Use `add_element(node, container)` to respect skin structure.
- Use `selectfolder` and `listupdate` instead of manually polling DOM.
- Prefer event-based callbacks (`plugin.*` events + `addEventListener`) over adding methods to `rcube_webmail.prototype`.

**Don't:**
- Depend on a specific skin's internal DOM layout beyond named containers and objects.
- Assume `before*`/`after*` events exist for arbitrary event names — they wrap commands only.
- Hardcode IDs/classes from a specific core skin beyond named containers.
