---
applyTo: skins/stratus/styles/**/*.less"
---

# Stratus Skin LESS Rules

- Use LESS syntax only (`@var`), never SCSS (`$var`).
- Keep all custom selectors prefixed with `mp-`.
- Do not hardcode colors in component rules; define/update variables first.
- Add dark-mode variants under `html.dark-mode` for custom colored UI.
- Prefer small overrides and compatibility with elastic defaults.
- Keep `styles/styles.less` as import orchestration (avoid dumping large rule blocks there).
- Validate by compiling after edits.
