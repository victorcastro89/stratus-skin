# Copilot Instructions for Stratus Skin Workspace

## Project Scope

This repository customizes Roundcube with the `stratus` skin and related tooling.

Primary working areas:
- `skins/stratus` (skin source — LESS, templates, compiled CSS)
- `plugins/stratus_helper` (companion plugin — PHP, JS, preferences UI)
- `plugins/conversation_mode` (conversation mode plugin — PHP, JS, CSS)
- `.github/agents/` and `.github/memory/` (agent workflow and shared state)
- `docker/` (Docker dev environment and Roundcube config)
- `scripts/` (setup, seeding, and build helper scripts)


## Core Rules

1. Use **LESS**, not SCSS (`@var`, never `$var`).
2. Prefix custom CSS classes with `mp-`.
3. **Variable-first**: before writing any color, size, or font value in a rule, check `_variables.less` for an existing var. If it doesn't exist, define it there first — never hardcode in component rules.
4. Include dark-mode variants under `html.dark-mode` for custom colored UI.
5. **Never use `@media (prefers-color-scheme: dark)`** — elastic detects dark mode via a JS cookie check and injects `class="dark-mode"` on `<html>` before first render. All dark rules must be scoped to `html.dark-mode { ... }`.
6. **Elastic must be imported FIRST** in `styles.less` before any stratus partials — LESS lazy-evaluates variables, so last definition wins. Importing elastic after `_variables.less` would override all color overrides with elastic's defaults.
7. Preserve Roundcube template structure and tags when overriding templates.
8. Plugin CSS files (`.css`, not LESS) cannot use LESS variables — they must use the `--mp-conv-*` (or `--mp-<plugin>-*`) CSS custom property bridge defined in `_runtime.less` (light) and `_dark.less` (dark). Plugin rules reference `var(--mp-conv-main, #fallback)`. For any new plugin token, define it in `_runtime.less` `:root` block + `_dark.less` `html.dark-mode` block first.

## Build / Validation

- Watch mode is available via workspace task: **Watch & Compile Stratus LESS**.
- One-shot compile task: **Compile Stratus LESS (once)**.
- Validate changes after edits (LESS compilation, template integrity, JSON validity).

## Agent Workflow

When using specialized agents:
- `@builder`: roadmap-driven feature implementation.
- `@bugfix`: bug reproduction, diagnosis, fixing, and verification.
- `@stylist`: palette/visual refinements.
- `@templater`: Roundcube template structure and tags.
- `@plugin-dev`: plugin-side PHP work (`stratus_helper` and `conversation_mode`).
- `@qa`: verification and regressions.
- `@dogfood`: systematic exploratory testing — produces a structured bug report with screenshots.
- `@architect`: structural decisions, meta.json, feature planning.

Before major work, review:
- `.github/memory/context.md`
- `.github/memory/roadmap.md`

After major work, update memory files to reflect completed tasks and next actions.

## Feature Spec Workflow

**All new features from the roadmap require a spec before implementation.**

1. Agent creates a detailed spec in `.github/feature-specs/<phase>-<name>.md`
2. Agent presents a summary and **asks human for approval**
3. Human approves, requests changes, or says "skip spec"
4. Only after approval does the agent begin implementation
5. After implementation, spec status is updated to `IMPLEMENTED`

**Skip spec for:** bug fixes, trivial tweaks, variable renames, or when the human explicitly says "skip spec" or "just do it".

This ensures the human always reviews the plan before code is written. See `.github/instructions/feature-specs.instructions.md` for spec format rules.

## Editing Guidance

- Keep overrides minimal and maintainable.
- Prefer extending existing elastic behavior over large rewrites.
- Do not modify generated/minified artifacts manually unless explicitly required.
- Keep changes scoped to the requested task.
- After any bug fix or feature, run `/update-docs-after-bugfix` to capture learnings in agent docs and instruction files.
