---
description: Create a safe Roundcube template override
---

Override a template with minimum risk:

1. Locate and review elastic base template first.
2. Create Stratus override preserving Roundcube tags/objects.
3. Keep core containers/structure required by JS.
4. Use include pattern with `skinPath="skins/elastic"` when suitable.
5. Add only required style hooks (`mp-` prefixed classes).
6. Validate rendering and update memory notes.
