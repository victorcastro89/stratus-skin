---
name: Roundcube Skin Development
description: Patterns and constraints for building/extending Roundcube skins — meta.json, extending skins, CSS, and skinning plugins
type: reference
paths:
  - "skins/**/*"
  - "**/*.less"
  - "**/meta.json"
---

# Roundcube Skin Development

## Skin structure

A skin lives under `skins/<skinname>/` with at minimum: `meta.json`, CSS, images, and required templates under `templates/`.

## meta.json

Controls skin metadata, asset injection, and inheritance:

```json
{
  "name": "MySkin",
  "extends": "elastic",
  "localization": true,
  "config": [],
  "meta": { "viewport": "width=device-width, initial-scale=1.0" },
  "links": { "stylesheet": ["/branding.css"] }
}
```

- Use `meta`/`links` for branding assets instead of hardcoding `<meta>`/`<link>` tags in templates.
- Set `localization: true` to load labels from `skins/<skinname>/localization/`.
- Unset a parent value by setting it to `false` in your `meta.json`.

## Extending existing skins

When `"extends": "elastic"` is set, the parent skin directory is added to the search path. Only provide templates/includes that differ from the base.

To override a template include while re-including the parent's version:
```html
<roundcube:include file="/includes/links.html" skinPath="skins/elastic" />
<link rel="stylesheet" type="text/css" href="/customstyles.css" />
```

**Do:**
- Prefer `extends` for small brandings/tweaks — keep overrides minimal.
- Start from a current default skin (e.g. `elastic`) and adapt.

**Don't:**
- Copy the entire parent skin unless building a fundamentally different layout — makes upgrades hard.

## Skinning plugins

Skins can provide plugin-specific overrides:
```
skins/<skinname>/plugins/<pluginname>/
  templates/...
  <pluginname>.css
```

Roundcube checks the plugin's own `skins/` directory first, then falls back to the skin's override directory.

## Core templates

Never remove critical objects like `messagebody`, `messages`, `loginform` — leave them present and style/reposition instead. Key templates: `login.html`, `mail.html`, `message.html`, `messagepreview.html`, `compose.html`, `addressbook.html`, `settings.html`, `plugin.html`, `error.html`.

## Skin vs Plugin decision

- **Skin:** pure presentation — restyle, rearrange existing objects, branding, CSS/template overrides.
- **Plugin:** behavior, business rules, dynamic UI, new actions/screens, external integrations.
