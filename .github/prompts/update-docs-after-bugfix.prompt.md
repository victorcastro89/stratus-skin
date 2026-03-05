```prompt
---
description: Update documentation after fixing bugs — memory, roadmap, agent docs, and capture learnings
---

After fixing bugs, update all relevant documentation and **improve agent/instruction files with learnings** so future work benefits from what was discovered.

## What to update and when

### 1. `.github/memory/context.md` — ALWAYS update

- **"Last Updated"**: Set to today's date with a short summary of what was fixed.
- **"Last Agent"**: Set to the agent that did the work.
- **"What Was Just Done"**: Replace with a summary of the bugs fixed — include: bug description, root cause, file(s) changed, and how it was verified.
- **"What's Next"**: Remove fixed items, promote the next priority.
- **"Recent Fixes"**: If the fix revealed a **reusable pattern** (e.g., specificity trick, elastic override technique, JS API gotcha), add a brief note here so future agents avoid the same mistake.
- **"Styling Rule"**: If the fix exposed a new constraint that applies broadly (e.g., "never use X because Y"), add or update this section.
- **Stats at bottom**: Update counts if files were added/removed (templates, LESS partials, plugins, compiled CSS size).

### 2. `.github/memory/roadmap.md` — ALWAYS update

- Mark fixed bugs **✅** in the relevant section (Bugs/Issues table or phase checklist).
- If the fix uncovered new bugs, add them as **🔲** rows with severity, file(s), and fix notes.
- If a phase milestone is now complete (all items ✅), note it.

### 3. Agent docs — Fix mistakes AND enrich with learnings

Review each agent file and ask: *"Would this fix have been easier or avoided entirely if the doc had included what I just learned?"*

| File | Fix if wrong | Improve with learnings |
|------|-------------|----------------------|
| `.github/agents/builder.agent.md` | Build workflow, validation checklist, or file references are wrong | Add new validation steps, diagnosis tips, or common pitfall warnings based on the bug |
| `.github/agents/stylist.agent.md` | Color variable names, LESS patterns, or dark mode instructions are outdated | Add new "gotcha" notes to dark mode section, update variable reference with newly created vars, add CSS specificity tips |
| `.github/agents/templater.agent.md` | Template tag syntax, include patterns, or layout IDs are incorrect | Add notes about template tag edge cases, layout ID dependencies, or `skinPath` quirks discovered |
| `.github/agents/plugin-dev.agent.md` | Plugin API usage, hook names, directory structure, or pref patterns are wrong | Add new hook usage examples, JS↔PHP API patterns, or Roundcube API gotchas found during the fix |
| `.github/agents/qa.agent.md` | Validation commands or checklist items are outdated | Add new checklist items that would catch this bug class, update grep patterns |

**What counts as an improvement (not just a fix):**
- A **new "avoid this" warning** — e.g., *"Never use `rcmail.get_frame_element()` when you need a Window — use `rcmail.get_frame_window()` instead"*
- A **new checklist item** — e.g., *"After adding dark mode vars, verify plugin CSS also has `html.dark-mode` overrides"*
- A **new example pattern** — e.g., showing the correct way to do something that was done wrong in the bug
- A **new entry in a reference table** — e.g., adding a missing Roundcube API method to the hook reference
- An **updated file path or count** — e.g., if new LESS partials or templates were created during the fix
- A **"lessons learned" note** near the relevant section — e.g., *"Elastic's 3-class specificity on `.folderlist li.mailbox .unreadcount` requires matching specificity to override"*

### 4. Instruction files — Add missing rules from learnings

Review each instruction file and ask: *"Is there a coding rule that, if it had existed, would have prevented this bug?"*

| File | Add rules if... |
|------|----------------|
| `.github/instructions/skin-styles.instructions.md` | The bug was a LESS/CSS pattern issue — add the rule that prevents it (e.g., specificity requirements, variable naming, dark mode coverage) |
| `.github/instructions/skin-templates.instructions.md` | The bug was a template structure issue — add the constraint (e.g., required IDs, conditional tag patterns, `skinPath` rules) |
| `.github/instructions/plugin-php.instructions.md` | The bug was a PHP pattern issue — add the rule (e.g., hook return types, pref key patterns, `dont_override` checks) |

**Examples of rules worth adding:**
- *"Plugin CSS that uses CSS custom properties (`var(--name)`) must include explicit `html.dark-mode` fallbacks — Roundcube does not define these custom properties natively."*
- *"When overriding elastic styles, match or exceed elastic's selector specificity — adding `!important` is a last resort."*
- *"Always use `rcmail.get_frame_window()` (not `get_frame_element()`) when passing targets to `rcmail.location_href()`."*

### 5. Feature specs — ONLY if the fix changes an approved spec's design

- If the bug required changing architecture described in a `.github/feature-specs/*.md` file, update the spec to match the actual implementation and set status to `IMPLEMENTED`.

## Checklist

- [ ] `context.md` — updated "Last Updated", "What Was Just Done", "What's Next", "Recent Fixes"
- [ ] `roadmap.md` — bugs marked ✅, any new bugs added as 🔲
- [ ] Agent docs — checked for stale info AND enriched with learnings from the fix
- [ ] Instruction files — checked for missing rules that would prevent this bug class
- [ ] Feature specs — updated if architecture changed
- [ ] All referenced file paths and counts in docs still match reality
- [ ] LESS compiled successfully after all changes (`npm run less:build`)

```
