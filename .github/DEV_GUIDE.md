# Developer Guide — AI-Assisted Workflow

## Quick Start

```
1. Open this project in VS Code with GitHub Copilot enabled
2. Open Copilot chat (Ctrl+Shift+I / Cmd+Shift+I)
3. Just start talking — global rules are already loaded
4. Use @agent-name when you want a specialist
5. Use /prompt-name when you want a guided task template
```

---

## How Things Get Loaded (READ THIS FIRST)

There are 5 types of files in `.github/`. Each loads differently:

### 🟢 AUTOMATIC — You do nothing, Copilot loads it

| File | Trigger | What happens |
|------|---------|-------------|
| `copilot-instructions.md` | **Every single chat message** | Copilot always knows the project rules, constraints, and file map. You never need to explain the project. |
| `instructions/skin-styles.instructions.md` | **You open/edit a file in `skins/stratus/styles/`** | Copilot auto-loads LESS coding rules (use `@` not `$`, `mp-` prefix, dark mode patterns). Triggered by the `applyTo` glob in the file's frontmatter. |
| `instructions/skin-templates.instructions.md` | **You open/edit a file in `skins/stratus/templates/`** | Copilot auto-loads Roundcube template tag rules. |
| `instructions/plugin-php.instructions.md` | **You open/edit a file in `plugins/stratus_helper/`** | Copilot auto-loads PHP plugin API rules. |
| `instructions/feature-specs.instructions.md` | **You open/edit a file in `.github/feature-specs/`** | Copilot auto-loads feature spec format rules and required sections. |
| `instructions/memory-format.instructions.md` | **You open/edit a file in `.github/memory/`** | Copilot auto-loads memory formatting rules. |

**You don't call these. You don't reference these. They just work based on which file you have open.**

### 🟡 ON DEMAND — You call these explicitly

| File | How to invoke | What happens |
|------|---------------|-------------|
| `agents/builder.agent.md` | Type **`@builder`** in chat | **Primary agent.** Reads roadmap, builds everything, compiles, validates, updates memory. |
| `agents/stylist.agent.md` | Type **`@stylist`** in chat | Specialist for color palettes, typography, visual design exploration. |
| `agents/templater.agent.md` | Type **`@templater`** in chat | Specialist for complex Roundcube template overrides. |
| `agents/plugin-dev.agent.md` | Type **`@plugin-dev`** in chat | Specialist for the PHP companion plugin (Phase 2). |
| `prompts/build-next.prompt.md` | Type **`/build-next`** in chat | Runs the full build cycle: roadmap → build → compile → validate → update memory. |
| `prompts/compile-and-validate.prompt.md` | Type **`/compile-and-validate`** in chat | Runs compilation + QA audit only (no building). |
| `prompts/add-color-variant.prompt.md` | Type **`/add-color-variant`** in chat | Guided flow to add a new color scheme. |
| `prompts/override-template.prompt.md` | Type **`/override-template`** in chat | Guided flow to override a specific elastic template. |

###  MANUAL — Agents read/write these, you can too

| File | What it is |
|------|-----------|
| `memory/context.md` | Current project state, styling rules, architectural decisions. Agents read before work, update after. |
| `memory/roadmap.md` | Task backlog with ✅/🔲 status. Agents update progress here. |
| `feature-specs/*.md` | Feature spec documents. Agents create before implementing new features, human approves before code is written. |

**Memory is the shared brain.** If you start a new chat session, the agent reads memory to know where you left off. You can also read these yourself to see project status.

**Feature specs are the approval gate.** Before any new roadmap feature is built, a spec must be written and approved by the human. This prevents wasted work and ensures alignment.

---

## The Agents

| Agent | Role | When to use |
|-------|------|-------------|
| `@builder` | **Primary — does everything** | *"Start Phase 1"*, *"continue"*, *"build next"* |
| `@stylist` | Specialist — colors, typography, visual polish | *"I want to explore indigo vs teal palettes"* |
| `@templater` | Specialist — complex Roundcube templates | *"Help me override the compose page template"* |
| `@plugin-dev` | Specialist — PHP plugin (Phase 2) | *"Add a color preference to settings"* |

**90% of the time, use `@builder`.** It reads the roadmap, builds files, compiles LESS, validates, and updates memory — all in one shot. The specialists are for when you want focused, exploratory work on a specific domain.

---

## Development Flows

### Flow 1: Build a Whole Phase

```
You:  @builder start Phase 1.6
      → reads roadmap → identifies next 🔲 items
      → creates feature spec in .github/feature-specs/phase1.6-<name>.md
      → presents summary and ASKS FOR YOUR APPROVAL
You:  approved (or: change X, Y, Z)
      → builds files → compiles → validates → updates memory + spec status
      → reports what was built and what's next
```

### Flow 2: Continue Where You Left Off

```
You:  @builder continue
      → reads memory → picks up next uncompleted items
      → checks if feature spec exists and is approved
      → if no spec: creates one and asks for approval first
      → if approved: builds → compiles → done
```

### Flow 3: Specific Task

```
You:  @builder override the login page with a centered card layout and add styles for it
      → reads elastic's login.html → creates feature spec → asks for approval
      → after approval: creates template override → writes _login.less → compiles → done
```

### Flow 4: Skip Spec (When You Already Know What You Want)

```
You:  @builder fix the sidebar dark mode colors, skip spec
      → reads context + dark mode skill → fixes _dark.less → compiles → validates → done
      (no spec needed for bug fixes or when human says "skip spec")
```

### Flow 5: Focused Style Exploration

```
You:  @stylist I want to explore a warm earth-tone palette vs a cool slate palette — show me both
      → shows two palettes with contrast ratios and dark mode variants
You:  @builder apply the slate palette
      → writes _variables.less → compiles → done
```

### Flow 6: Fix a Bug

```
You:  @builder the sidebar looks wrong in dark mode
      → reads context + dark mode skill → fixes _dark.less → compiles → validates → done
      (no spec needed for bug fixes)
```

---

## Reusable Prompts (Slash Commands)

Type `/` in Copilot chat to see these prompt templates:

| Prompt | What it does |
|--------|-------------|
| **`/build-next`** | **Primary — pick up next roadmap items, create spec if needed, get approval, build, compile, validate, update memory** |
| `/compile-and-validate` | Just compile and run QA checks (no building) |
| `/add-color-variant` | Guided flow to add a new color scheme |
| `/override-template` | Step-by-step template override |
| `/update-docs-after-bugfix` | After fixing bugs — update memory, roadmap, enrich agent docs and instruction files with learnings |

**`/build-next` is equivalent to `@builder continue`.** Use whichever you prefer.

---

## Memory System

Agents share state across sessions via three files:

| File | Purpose | You should |
|------|---------|-----------|
| `.github/memory/context.md` | What exists, what was just done, styling rules, what's next | Read if resuming after a break |
| `.github/memory/roadmap.md` | Full task backlog with status | Check what's done and what's remaining |
| `.github/feature-specs/*.md` | Detailed feature plans with approval status | Check before implementing any new feature |

Agents update these automatically after each task. If you notice they're stale, ask: *"Update the memory files to reflect current state."*

**Feature specs** live in `.github/feature-specs/` and follow a lifecycle: `DRAFT` → `APPROVED` → `IMPLEMENTED`. Agents create them before building new features and won't proceed without human approval.

---

## Key Commands

```bash
# Compile LESS → minified CSS (one-shot)
npm run less:build

# Compile LESS in watch mode (auto-recompiles on save)
npm run less:watch

# Check JSON validity
python3 -c "import json; json.load(open('skins/stratus/meta.json'))"

# Search for our custom classes in compiled output
grep "mp-" skins/stratus/styles/styles.min.css

# Check CSS file size
wc -c skins/stratus/styles/styles.min.css
```

---

## Rules to Remember

1. **LESS, not SCSS** — Variables are `@color-main`, not `$color-main`
2. **`mp-` prefix** — Every custom class: `.mp-sidebar`, `.mp-header`, etc.
3. **No hardcoded colors** — Use `@color-main` in rules, define hex only in `_variables.less`
4. **Dark mode** — Every custom style needs an `html.dark-mode` variant
5. **Minimal overrides** — Override elastic variables first, selectors second, `!important` never (almost)

---

## File Structure (Current)

```
skins/stratus/
├── meta.json                ← skin config (extends elastic)
├── composer.json            ← package info
├── thumbnail.png            ← preview for skin selector
├── watermark.html           ← branding page (reading pane empty state)
├── styles/
│   ├── styles.less          ← main entry (only @imports — elastic FIRST)
│   ├── _variables.less      ← color + dimension overrides (~180+ vars)
│   ├── _typography.less     ← font stack, heading hierarchy
│   ├── _animations.less     ← transitions, keyframes, reduced-motion
│   ├── _layout.less         ← taskmenu, headers, panels
│   ├── widgets/             ← component files (mirrors Elastic structure)
│   │   ├── common.less      ← quota, scrollbars, mass-action bar
│   │   ├── buttons.less     ← button variants, toolbar icons, FAB
│   │   ├── forms.less       ← form controls, switches, recipient chips
│   │   ├── lists.less       ← message list, folder list, badges
│   │   ├── menu.less        ← navigation tabs
│   │   ├── messages.less    ← message view, attachments, toasts
│   │   ├── dialogs.less     ← dialogs, overlay, popovers
│   │   ├── editor.less      ← TinyMCE editor
│   │   └── jqueryui.less    ← jQuery UI overrides
│   ├── _components.less     ← barrel file (no rules — see widgets/)
│   ├── _calendar.less       ← calendar/FullCalendar overrides
│   ├── _dark.less           ← dark mode extras (html.dark-mode rules)
│   ├── _login.less          ← login page styles
│   ├── _runtime.less        ← CSS custom properties bridge (for JS theming)
│   └── styles.min.css       ← compiled output (don't edit manually)
├── templates/
│   ├── includes/
│   │   └── layout.html      ← CSS injection point
│   ├── login.html           ← login page override
│   └── mail.html            ← mail layout + conversation mode containers
└── plugins/
    └── calendar/
        └── templates/
            └── calendar.html  ← Tier B calendar toolbar override

plugins/stratus_helper/      ← companion plugin (appearance prefs, folder refresh)
plugins/conversation_mode/   ← conversation mode plugin (grouping, Outlook-style rows)
```
