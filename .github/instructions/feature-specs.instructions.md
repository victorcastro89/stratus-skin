---
applyTo: ".github/feature-specs/**/*.md"
---

# Feature Spec File Rules

Feature specs are detailed design documents created **before** implementation begins. Every new feature from the roadmap MUST have an approved spec before any code is written.

## Required Sections

Every feature spec MUST include these sections:

1. **Title** — `# Feature Spec: <Roadmap Item Name>` with roadmap reference (phase + item number)
2. **Roadmap Reference** — Exact phase, section, and task name(s) from `roadmap.md`
3. **Summary** — 2–3 sentence description of what will be built
4. **Goals** — Bullet list of what this feature achieves
5. **Non-Goals** — What is explicitly out of scope
6. **User Experience** — How the user sees/interacts with the feature
7. **Technical Design** — Architecture, files to create/modify, integration points
8. **Files Changed** — Explicit list of files that will be created or modified
9. **Dark Mode Considerations** — How the feature behaves in dark mode (if visual)
10. **Validation Criteria** — How to verify the feature works (testable checklist)
11. **Risks / Open Questions** — Anything unresolved that needs human input

## File Naming Convention

```
.github/feature-specs/<phase>-<short-kebab-name>.md
```

Examples:
- `phase1.6-collapsible-sidebar.md`
- `phase1.5-reading-pane-integration.md`
- `phase2-color-scheme-switching.md`
- `conv-mode-phase1.5-selection-actions.md`

## Approval Workflow

1. Agent creates the spec file
2. Agent presents a concise summary to the human and asks for approval
3. **Human must explicitly approve** before any implementation begins
4. If human requests changes, agent updates the spec and asks again
5. Once approved, agent proceeds with implementation referencing the spec

## Linking to Roadmap

The spec MUST reference the exact roadmap items it covers, using the format:
```
## Roadmap Reference
- **Phase:** 1.6 — Calendar UI Improvements
- **Section:** 3 — Sidebar
- **Items:** Collapsible sidebar (mini-calendar)
```

## Spec Lifecycle

- `DRAFT` — Just created, awaiting human review
- `APPROVED` — Human approved, ready for implementation
- `IMPLEMENTED` — Code is written, compiled, validated
- `SUPERSEDED` — Replaced by a newer spec (link to replacement)

Add a status badge at the top of the spec: `**Status:** DRAFT | APPROVED | IMPLEMENTED`
