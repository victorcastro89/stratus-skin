---
applyTo: "skins/stratus/templates/**/*.html"
---

# Stratus Template Rules

- Preserve Roundcube template tags and object calls.
- Keep structure compatible with elastic where possible.
- Prefer minimal overrides over full template rewrites.
- When including elastic templates, use:
  - `<roundcube:include file="..." skinPath="skins/elastic" />`
- Keep `#layout` and core containers intact to avoid JS regressions.
- Validate template integrity after edits.
- **Do not use `<i class="fa fa-*">` for icons** — elastic does not define `.fa` classes. Instead, create a `<span>` element and style it via CSS `::before` with `font-family: 'Icons'` + the appropriate glyph code. For the conversation mode plugin, use the `conv-icon conv-icon-{name}` class pattern.
