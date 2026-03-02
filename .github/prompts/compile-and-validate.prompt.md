---
description: Compile and validate Stratus skin changes
---

Run validation-only workflow:

1. Compile Stratus LESS (watch task or one-shot).
2. Check for compile errors/warnings.
3. Validate changed templates for Roundcube tag/structure integrity.
4. Validate JSON files touched by the change.
5. Summarize issues with file paths and suggested fixes.

Do not introduce feature work in this flow unless needed to fix a build break.
