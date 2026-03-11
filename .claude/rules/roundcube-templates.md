---
name: Roundcube Template Engine
description: Template tags (<roundcube:...>), environment variables, containers, buttons, and conditional blocks
type: reference
paths:
  - "**/templates/**/*.html"
  - "skins/**/*.html"
  - "plugins/**/*.html"
---

# Roundcube Template Engine

Templates are XHTML files with `<roundcube:...>` tags replaced during rendering.

## Core tags

### Include
```html
<roundcube:include file="/includes/head.html" />
```
`file` is skin-rooted and must start with `/`.

### Variables and expressions
```html
<roundcube:var name="env:task" />
<roundcube:exp expression="..." />
```
Sources: `session:`, `config:`, `env:`, `cookie:`, `request:`, `browser:`.

### Labels (i18n)
```html
<roundcube:label name="subject" />
<roundcube:label name="mykey" noshow="true" />  <!-- register without printing -->
```

### Buttons
```html
<roundcube:button command="compose" type="link" label="compose" />
```
UI manager tracks enabled/disabled/selected states and binds to client commands.

### Containers
```html
<roundcube:container name="taskbar" id="thetaskbar" />
```
Declares a hook point where plugins inject HTML and JS attaches elements via `rcmail.add_element()`.

### Content objects
```html
<roundcube:object name="messageContentFrame" id="messagecontframe" />
```
Placeholder for dynamic content from core or plugins. Many objects only exist in specific templates.

### Conditionals
```html
<roundcube:if condition="count(env:address_sources) > 1">
  ...
<roundcube:elseif condition="config:identities_level:0 < 2" />
  ...
<roundcube:else />
  ...
<roundcube:endif />
```

## Rules

**Do:**
- Use `container` + `button` for extensible UI, not hardwired `<a>`/`<button>`.
- Use `roundcube:label` and skin localization instead of hardcoded strings.
- Use `roundcube:if` and env variables to keep templates logic-light.
- Respect required content objects (`messagebody`, `messages`, `loginform`, etc.).

**Don't:**
- Change template object names (`name="messages"`, `name="mailboxlist"`) unless you know every plugin using them.
- Remove containers or objects that core or plugins rely on (`taskbar`, `toolbar`, `userprefs`).
- Use inline PHP unless `skin_include_php` is enabled and truly needed — hurts portability.
