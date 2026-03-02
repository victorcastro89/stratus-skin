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
