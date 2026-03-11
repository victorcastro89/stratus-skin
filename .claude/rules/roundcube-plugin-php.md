---
name: Roundcube Plugin PHP API
description: PHP plugin structure, hooks, actions, templates, config, localization — rules for writing rcube_plugin subclasses
type: reference
paths:
  - "plugins/**/*.php"
---

# Roundcube Plugin PHP API

## Plugin structure

Plugins are `rcube_plugin` subclasses at `plugins/<name>/<name>.php`. Class name, file name, and directory name must match.

```php
class my_plugin extends rcube_plugin
{
    public $task = 'mail'; // limit to specific tasks for performance

    function init()
    {
        $this->add_hook('message_part_after', [$this, 'handler']);
        $this->include_script('client.js');
        $this->include_stylesheet($this->local_skin_path() . '/style.css');
        $this->add_texts('localization/', true);
        $this->register_action('plugin.myaction', [$this, 'action_handler']);
    }
}
```

- `init()` runs when Roundcube is fully initialized — register hooks, actions, scripts here.
- `$task` (string or array) restricts activation to specific tasks.
- Activate by adding plugin name to `$config['plugins']` in Roundcube config.

## Hooks

Register via `$this->add_hook('hook-name', [$this, 'method'])`. Callback gets `$args` array, returns modified array or `null`.

### Major hook families

- **Global:** `startup`, `ready`, `config_get`, `render_mailboxlist`, `refresh`
- **Login:** `authenticate`, `login_after`, `login_failed`, `user_create`, `session_destroy`
- **Mail:** `storage_init`, `message_load`, `message_read`, `message_part_before/after`, `message_headers_output`, `message_compose`, `message_compose_body`, `message_ready`, `message_before_send`, `message_sent`, `messages_list`, `check_recent`, `new_messages`
- **Addressbook:** `addressbooks_list`, `addressbook_get`, `contact_*`, `group_*`
- **Settings:** `settings_actions`, `preferences_*`, `identities_*`, `folders_list`
- **Template:** `template_object_*`, `template_container`, `render_page`, `send_page`

### Hook rules

- Use the most specific hook available (e.g. `message_compose_body` over `render_page`).
- Use `message_ready`/`message_before_send` instead of deprecated `message_outgoing_headers`.
- Make hooks idempotent and cheap — many run on every request.
- Only change documented keys in `$args`.
- In `template_container`, append to `content` — don't fully replace it unless intentional.
- Use `refresh` to push periodic updates to the JS client.

## Plugin templates

### Using generic `plugin.html`
```php
$this->register_handler('plugin.body', [$this, 'render_body']);
// in action handler:
$rcmail->output->set_pagetitle('My Plugin');
$rcmail->output->send('plugin');
```

### Custom templates
Place at `plugins/my_plugin/skins/default/templates/mytemplate.html`, then:
```php
$this->register_handler('plugin.my_content', [$this, 'content_handler']);
$rcmail->output->send('my_plugin.mytemplate');
```

Always go through output + templates — never echo HTML directly in action handlers.

## Config and localization

```php
$this->load_config('config.inc.php.dist'); // defaults
$this->load_config('config.inc.php');      // site-local overrides
$this->add_texts('localization/', true);   // true = register for JS too
```

- Always have `en_US.inc` as baseline.
- PHP: `$this->gettext('key')` — JS: `rcmail.gettext('key', 'plugin_name')`.

## AJAX action pattern

```php
function init() {
    $this->register_action('plugin.myaction', [$this, 'handle']);
}

function handle() {
    $rcmail = rcmail::get_instance();
    // process...
    $rcmail->output->command('plugin.myaction_done', ['status' => 'ok']);
    $rcmail->output->send(); // no template name for AJAX
}
```
