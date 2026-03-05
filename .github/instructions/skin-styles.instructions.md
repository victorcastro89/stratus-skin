---
applyTo: "skins/stratus/styles/**/*.less"
---

# Stratus Skin LESS Rules

- Use LESS syntax only (`@var`), never SCSS (`$var`).
- Keep all custom selectors prefixed with `mp-`.
- Do not hardcode colors in component rules; define/update variables first.
- Add dark-mode variants under `html.dark-mode` for custom colored UI.
- Prefer small overrides and compatibility with elastic defaults.
- Keep `styles/styles.less` as import orchestration (avoid dumping large rule blocks there).
- Validate by compiling after edits.
- **Icons must use `font-family: 'Icons'`** — elastic/stratus registers FontAwesome 5 glyphs under `'Icons'` (solid weight 900, regular weight 400). Never use `"Font Awesome 5 Free"` — that name does not exist in the skin and icons will render blank.
- **Plugin CSS token bridge** — Plugin `.css` files cannot use LESS variables. To share Stratus tokens with plugin CSS: define `--mp-conv-*` (or `--mp-<plugin>-*`) CSS custom properties in `_runtime.less` (`:root` block, value from LESS vars) and dark overrides in `_dark.less` (`html.dark-mode` block). Plugin CSS then uses `var(--mp-conv-main, #hardcoded-fallback)`. This enables runtime theme switching and proper dark mode for plugins.
