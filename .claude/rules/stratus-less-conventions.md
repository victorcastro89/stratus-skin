---
name: Stratus LESS Conventions
description: CSS/LESS authoring rules for the Stratus skin — font-size scaling, unit choices, and runtime theming constraints
type: reference
paths:
  - "skins/stratus/styles/**/*.less"
  - "skins/stratus/styles/_runtime.less"
---

# Stratus LESS Conventions

## Font-size scaling — apply to `html`, not `body`

`--stratus-font-size` **must** be applied to `html` (the root element), never to `body`.

```less
// CORRECT
html {
    font-size: var(--stratus-font-size, 0.875rem);
}

// WRONG — breaks all rem-based elements
body {
    font-size: var(--stratus-font-size, 0.875rem);
}
```

**Why:** Elastic's entire design system uses `rem` units. `rem` resolves against `<html>`, never `body`. Applying the preference to `body` only affects elements using `em`/`%` — all `rem`-valued elements (toolbars, headings, form controls, spacing) silently ignore it. Applying to `html` makes every `rem` unit in the page scale proportionally with no targeted per-element overrides needed.

## Unit guide for font-size overrides

| Use | Unit | When |
|-----|------|------|
| `rem` | root-relative | Default for any new font-size — scales with user preference automatically |
| `em` | parent-relative | When the size should be relative to the *containing element* (e.g. a badge inside a button) |
| `px` | fixed | Only for truly fixed sizes that must never scale (e.g. monospace code blocks) — avoid |
| `%` | parent-relative | Same as `em`, prefer `em` for clarity |

Never hardcode `px` for font-size on UI text elements — it bypasses all scaling.

## Runtime theming (`_runtime.less`)

`_runtime.less` is the bridge between PHP-injected CSS custom properties and the compiled LESS. Rules here:
- Only add things that **must** change at runtime without recompilation (color scheme, font family, font size)
- Use `var(--stratus-*)` with a fallback: `var(--stratus-font-size, 0.875rem)`
- All other styling belongs in component `.less` files
