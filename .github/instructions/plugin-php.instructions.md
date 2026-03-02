---
applyTo: "plugins/stratus_helper/**/*.php"
---

# Stratus Helper Plugin Rules

- Keep plugin-side logic in `stratus_helper` (Phase 2 work).
- Follow Roundcube plugin conventions (`rcube_plugin`, hooks, config handling).
- Prefer additive hooks over invasive core behavior changes.
- Keep user preference handling backward compatible.
- Validate PHP syntax and avoid side effects in hooks.
