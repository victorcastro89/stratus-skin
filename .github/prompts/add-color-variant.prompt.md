---
description: Add a new Stratus color variant safely
---

Add a new color variant following project rules:

1. Define/extend palette variables in `roundcubemail/skins/stratus/styles/_variables.less`.
2. Apply variables in component/layout rules (no hardcoded hex in component rules).
3. Add matching `html.dark-mode` variants.
4. Keep custom selectors prefixed with `mp-`.
5. Compile and validate visual regressions.
6. Update memory with what changed.
