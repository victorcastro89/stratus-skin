# Copilot Instructions for Stratus Skin Workspace

## Project Scope

This repository customizes Roundcube with the `stratus` skin and related tooling.

Primary working areas:
- `skins/stratus` (skin source)
- `.github/agents/` and `.github/memory/` (agent workflow and shared state)
- `docker/` (Docker dev environment and Roundcube config)
- `scripts/` (setup, seeding, and build helper scripts)


## Core Rules

1. Use **LESS**, not SCSS (`@var`, never `$var`).
2. Prefix custom CSS classes with `mp-`.
3. Avoid hardcoded colors in component rules; define values in variables first.
4. Include dark-mode variants under `html.dark-mode` for custom colored UI.
5. Preserve Roundcube template structure and tags when overriding templates.

## Build / Validation

- Watch mode is available via workspace task: **Watch & Compile Stratus LESS**.
- One-shot compile task: **Compile Stratus LESS (once)**.
- Validate changes after edits (LESS compilation, template integrity, JSON validity).

## Agent Workflow

When using specialized agents:
- `@builder`: primary end-to-end implementation agent.
- `@stylist`: palette/visual refinements.
- `@templater`: Roundcube template structure and tags.
- `@plugin-dev`: plugin-side PHP work.
- `@qa`: verification and regressions.

Before major work, review:
- `.github/memory/context.md`
- `.github/memory/roadmap.md`
- `.github/memory/decisions.md` (if present)

After major work, update memory files to reflect completed tasks and next actions.

## Feature Spec Workflow

**All new features from the roadmap require a spec before implementation.**

1. Agent creates a detailed spec in `.github/feature-specs/<phase>-<name>.md`
2. Agent presents a summary and **asks human for approval**
3. Human approves, requests changes, or says "skip spec"
4. Only after approval does the agent begin implementation
5. After implementation, spec status is updated to `IMPLEMENTED`

This ensures the human always reviews the plan before code is written. See `.github/instructions/feature-specs.instructions.md` for spec format rules.

## Editing Guidance

- Keep overrides minimal and maintainable.
- Prefer extending existing elastic behavior over large rewrites.
- Do not modify generated/minified artifacts manually unless explicitly required.
- Keep changes scoped to the requested task.
