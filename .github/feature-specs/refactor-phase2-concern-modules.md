# Feature Spec: Split Smart Bar into Concern Modules

**Status:** DRAFT

## Roadmap Reference

- **Phase:** Refactoring — JS Architecture Debt
- **Section:** Phase 2 of 4 — Extract Concern Modules
- **Items:** Split the monolithic smart-bar.js (extracted in Phase 1) into focused, single-responsibility modules
- **Depends on:** Phase 1 (Extract Pagenav Script) must be complete

## Summary

After Phase 1 extracts the inline `<script>` from `pagenav.html` into `skins/stratus/js/smart-bar.js`, this phase splits that monolith (~770 lines) into 5 focused controller modules with clear responsibilities, standard interfaces, and an event-based communication contract. Each module is a separate file under `skins/stratus/js/` and can be understood, tested, and modified independently.

## Goals

- Split `smart-bar.js` into 5 focused modules: `SelectionManager`, `MassActionBar`, `ActionDispatcher`, `MultiSelectController`, `SortController`
- Define a clear public API for each module (what it exposes, what events it emits/consumes)
- Eliminate the pervasive `if (isConversationMode())` branching by making the `ActionDispatcher` the single point where mode-awareness lives
- Preserve the existing `stratus:conv-*` custom event contract — no changes to `conversation_mode.js`
- Preserve 100% identical runtime behavior

## Non-Goals

- **No changes to `conversation_mode.js`** — that plugin's internal architecture stays as-is.
- **No changes to `stratus_helper.js`** except the script loading mechanism (adding new includes).
- **No changes to CSS/LESS** — visual output is untouched.
- **No new features** — pure structural refactor.
- **No TypeScript or build tooling** — modules are plain ES5 IIFEs that register on a shared namespace.

## User Experience

No visible change. All mail list interactions behave identically.

## Problem Analysis

### What is wrong today (after Phase 1)

Even after extraction, `smart-bar.js` remains a single 770-line function that handles 10+ concerns:

| Concern | Lines (approx) | Description |
|---------|----------------|-------------|
| Selection state tracking | ~60 | `updateSelection()`, `getStandardSelectionState()`, `getSelectedRows()` |
| Multiselect mode | ~50 | `setMultiSelectMode()`, `list.select_row` monkey-patch |
| Mass action UI | ~40 | Active/default state toggling, chip text, button enable/disable |
| Mark/Flag toggle logic | ~60 | `updateMarkFlagToggleState()`, `updateToggleButtonEl()` |
| Archive action | ~80 | `archiveBtn` click handler, `detectArchiveFolder()`, `logArchive()`, tree insertion |
| Delete/Move interception | ~50 | Capture-phase handlers on `.mp-action-btn.delete`, `.mp-action-btn.move` |
| More menu interception | ~70 | Capture-phase handlers on `#mp-massaction-menu` items |
| Select menu interception | ~60 | Capture-phase on `#listselect-menu` for conv mode |
| Folder button management | ~50 | `updateFolderButtons()`, `reapplyFolderDisabledClasses()` |
| Pagination interception | ~25 | Prev/Next capture-phase for conv mode |
| Count display | ~20 | `stratus:conv-count-update` listener |
| Post-action cleanup | ~30 | `schedulePostActionCleanup()`, `closeMassActionPopovers()`, `forceDeselectAll()` |
| Toast suppression | ~15 | `display_message` monkey-patch |
| Accessibility | ~5 | Unread filter button label |

Every one of these concerns has `isConversationMode()` branches scattered through it, creating a combinatorial explosion of code paths.

### Impact of not fixing

- Each bug fix requires understanding the full 770-line context
- Adding features (e.g., a new mass action) requires touching 3-4 different locations in the same file
- The conversation-mode branching makes it impossible to reason about one mode without understanding both
- No unit testability — everything depends on everything else

### The key insight

The `isConversationMode()` check appears **24 times** in the current code. After this refactor, it should appear in exactly **1 place**: the `ActionDispatcher` module. Every other module works with abstract "selected items" and "action requests" without knowing which list mode is active.

## Technical Design

### Module Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    smart-bar.js (orchestrator)                │
│  - Creates module instances                                  │
│  - Wires event listeners between modules                     │
│  - ~50 lines                                                 │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌─────────────────┐  ┌──────────────────┐                  │
│  │ SelectionManager │  │ MultiSelectCtrl  │                  │
│  │                  │  │                  │                  │
│  │ .getCount()      │  │ .isActive()      │                  │
│  │ .getState()      │  │ .enter()         │                  │
│  │ .clearAll()      │  │ .exit()          │                  │
│  │ .onChanged(cb)   │  │ .patchSelectRow()│                  │
│  └───────┬──────────┘  └────────┬─────────┘                  │
│          │                      │                            │
│          ▼                      ▼                            │
│  ┌──────────────────────────────────────────┐                │
│  │           MassActionBar                   │                │
│  │                                           │                │
│  │ .updateState(selectionState)              │                │
│  │ .enableButtons(selectionState)            │                │
│  │ .showActiveState() / .showDefaultState()  │                │
│  └───────────────┬──────────────────────────┘                │
│                  │ action requested                           │
│                  ▼                                            │
│  ┌──────────────────────────────────────────┐                │
│  │         ActionDispatcher                  │                │
│  │                                           │                │
│  │ THE ONLY MODULE THAT KNOWS ABOUT MODES    │                │
│  │                                           │                │
│  │ .dispatch('delete')                       │                │
│  │ .dispatch('archive')                      │                │
│  │ .dispatch('mark', 'read')                 │                │
│  │ .dispatch('flag', 'flagged')              │                │
│  │                                           │                │
│  │ if standard → rcmail.command(...)         │                │
│  │ if conv → CustomEvent('stratus:conv-*')   │                │
│  └──────────────────────────────────────────┘                │
│                                                              │
│  ┌──────────────────────────────────────────┐                │
│  │          SortController                   │                │
│  │                                           │                │
│  │ .updateDisplay()                          │                │
│  │ .openDialog()                             │                │
│  └──────────────────────────────────────────┘                │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### File structure

```
skins/stratus/js/
  smart-bar.js                  ← orchestrator (Phase 1 file, gutted to ~50 lines)
  smart-bar/
    selection-manager.js        ← tracks selected items across both modes
    multi-select-controller.js  ← enter/exit multiselect, monkey-patch select_row
    mass-action-bar.js          ← UI state of the bar (active/default, buttons, chip)
    action-dispatcher.js        ← routes actions to RC core or conv events
    sort-controller.js          ← sort label, dialog opening
```

### Shared namespace

All modules register on `window.StratusSmartBar = {}` (or a similar namespace). This avoids polluting the global scope while allowing cross-module access without a module bundler.

```javascript
// selection-manager.js
(function() {
  'use strict';
  var ns = window.StratusSmartBar = window.StratusSmartBar || {};

  ns.SelectionManager = function(rcmail, list) {
    // ... constructor
  };

  ns.SelectionManager.prototype.getCount = function() { ... };
  ns.SelectionManager.prototype.getState = function() { ... };
  ns.SelectionManager.prototype.clearAll = function() { ... };
  ns.SelectionManager.prototype.onChanged = function(callback) { ... };
})();
```

### Module specifications

#### 1. `SelectionManager` (~80 lines)

**Responsibility:** Track the current selection state across both standard list and conversation mode.

**Public API:**
- `getCount()` → number — count of selected items (messages or conversations)
- `getState()` → `{ count, anyUnread, anyUnflagged }` — aggregated selection state
- `clearAll()` — deselect everything (delegates to list widget or conv events)
- `forceDeselectAll()` — belt-and-suspenders DOM sweep + data model clear
- `onChanged(callback)` — register a callback for selection changes

**Internal details:**
- Listens to `list.addEventListener('select', ...)` for standard mode
- Listens to `stratus:conv-selection-state` DOM event for conversation mode
- Calls registered callbacks whenever selection changes
- Stores `convSelectionState` for conversation mode (populated by event)

**Extracted from current code:**
- `updateSelection()` → split: state computation goes here, UI update goes to MassActionBar
- `getStandardSelectionState()`, `getSelectedRows()`, `isVisibleRow()` → moved here
- `convSelectionState` tracking → moved here
- `forceDeselectAll()` → moved here

#### 2. `MultiSelectController` (~60 lines)

**Responsibility:** Manage the multiselect mode (checkbox toggle for mobile-friendly multi-selection).

**Public API:**
- `isActive()` → boolean
- `enter()` — activate multiselect mode
- `exit()` — deactivate multiselect mode
- `toggle()` — flip state
- `patchSelectRow(list)` — apply the `select_row` monkey-patch to inject CONTROL_KEY

**Internal details:**
- Sets `multiSelectMode` flag
- Toggles `mp-multiselect-mode` class on the bar
- Dispatches `stratus:conv-set-multiselect` event to conversation_mode.js
- The `select_row` monkey-patch lives here (single location, documented)

**Extracted from current code:**
- `multiSelectMode` variable
- `setMultiSelectMode()` function
- `list.select_row` monkey-patch (lines 292-298)
- The multiselect-related branches from `updateSelection()` → `showActive` logic

#### 3. `MassActionBar` (~120 lines)

**Responsibility:** Manage the visual state of the smart bar — toggle between default state (sort + refresh) and active state (action buttons + count chip).

**Public API:**
- `updateState(selectionState, isMultiSelect)` — refresh UI based on selection
- `updateMarkFlagToggles(selectionState)` — set mark/flag button labels
- `enableConvButtons(hasSelection)` — force-enable buttons in conv mode
- `showDefaultState()` — show sort + refresh
- `showActiveState(count)` — show action buttons + count chip

**Internal details:**
- References DOM elements: `bar`, `chip`, `toggle`, `archiveBtn`, `deleteBtn`, `markToggleBtn`, `flagToggleBtn`
- Applies `mp-has-selection` class
- Updates chip text
- Calls `updateToggleButtonEl()` for mark/flag buttons

**Extracted from current code:**
- The UI-update portion of `updateSelection()` → moved here
- `updateMarkFlagToggleState()` → moved here
- `updateToggleButtonEl()` → moved here
- Folder-context disabling: `updateFolderButtons()`, `reapplyFolderDisabledClasses()` → moved here

#### 4. `ActionDispatcher` (~200 lines)

**Responsibility:** Route user actions to either Roundcube core (standard mode) or `stratus:conv-*` custom events (conversation mode). **This is the ONLY module that knows about modes.**

**Public API:**
- `dispatch(action, value)` — execute an action. Actions: `delete`, `archive`, `mark`, `flag`, `move`, `select-action`, `page-prev`, `page-next`
- `isConversationMode()` → boolean — the single source of truth for mode detection
- `onPostAction(callback)` — register cleanup after an action completes

**Internal details:**
- `isConversationMode()` checks `data-conv-mode` attribute (single location)
- For standard mode: calls `rcmail.command(...)` or `rcmail_archive()`
- For conversation mode: dispatches `stratus:conv-massaction`, `stratus:conv-page`, etc.
- Owns the `display_message` monkey-patch (toast suppression)
- Owns `detectArchiveFolder()`
- Owns the `responseaftermove` archive-folder-insertion handler
- Owns `schedulePostActionCleanup()` and `closeMassActionPopovers()`

**Event contract (emits to conversation_mode.js):**
- `stratus:conv-massaction` with `{ action, value }` — for delete, archive, mark, flag
- `stratus:conv-page` with `{ direction }` — for pagination
- `stratus:conv-select-action` with `{ type }` — for select all/none/unread/flagged
- `stratus:conv-set-multiselect` with `{ enabled }` — multiselect mode toggle
- `stratus:conv-clear-selection` — deselect all

**Event contract (consumes from conversation_mode.js):**
- `stratus:conv-selection-state` — forwarded to SelectionManager
- `stratus:conv-count-update` — forwarded to MassActionBar for count display

**Extracted from current code:**
- All `archiveBtn.addEventListener('click', ...)` handler logic
- All `rcDeleteBtn.addEventListener('click', ...)` handler logic
- All `rcMoveBtn.addEventListener('click', ...)` handler logic
- All `markToggleBtn.addEventListener('click', ...)` handler logic
- All `flagToggleBtn.addEventListener('click', ...)` handler logic
- All `selectMenu.addEventListener('click', ...)` handler logic (listselect-menu)
- All `massActionMenu.addEventListener('click', ...)` handler logic (more menu)
- All `prevPageBtn`/`nextPageBtn` interception handlers
- `detectArchiveFolder()`, `logArchive()`, `schedulePostActionCleanup()`, `closeMassActionPopovers()`
- The `display_message` monkey-patch
- The `responseaftermove` handler (archive folder tree insertion)
- `stratus:conv-count-update` listener

#### 5. `SortController` (~60 lines)

**Responsibility:** Manage the sort trigger label/arrow and the list-options dialog opening.

**Public API:**
- `updateDisplay()` — refresh sort label and arrow direction from `rcmail.env.sort_col/sort_order`
- `openDialog(event)` — open the list-options dialog

**Internal details:**
- Sort column label lookup map
- `updateSortDisplay()` function
- Sort trigger click handler → `rcmail.command('menu-open', 'messagelistmenu', ...)`
- `listupdate` listener to refresh display after sort changes

**Extracted from current code:**
- `sortColumnLabels` map and initialization
- `sortByLabel` fallback
- `updateSortDisplay()` function
- `sortTrigger.addEventListener('click', ...)` handler
- `listupdate` listener for sort display refresh

### Orchestrator: `smart-bar.js` (~50 lines)

After extraction, `smart-bar.js` becomes a thin orchestrator:

```javascript
(function() {
  'use strict';
  if (!window.rcmail) return;

  rcmail.addEventListener('init', function() {
    var ns = window.StratusSmartBar;
    var list = rcmail.message_list;
    if (!list) return;

    var bar = document.querySelector('.mp-smart-bar');
    if (!bar) return;

    // Instantiate modules
    var selection = new ns.SelectionManager(rcmail, list, bar);
    var multiSelect = new ns.MultiSelectController(list, bar);
    var massAction = new ns.MassActionBar(bar);
    var dispatcher = new ns.ActionDispatcher(rcmail, bar);
    var sort = new ns.SortController(rcmail, bar);

    // Wire: selection changes → update mass action bar
    selection.onChanged(function(state) {
      massAction.updateState(state, multiSelect.isActive());
    });

    // Wire: action buttons → dispatcher
    massAction.onAction(function(action, value) {
      dispatcher.dispatch(action, value);
    });

    // Wire: post-action cleanup → reset selection + multiselect
    dispatcher.onPostAction(function() {
      multiSelect.exit();
      selection.clearAll();
      massAction.showDefaultState();
    });

    // Initialize
    sort.updateDisplay();
    selection.refresh();
  });
})();
```

## Files Changed

| File | Action | Description |
|------|--------|-------------|
| `skins/stratus/js/smart-bar.js` | **MODIFY** | Gut to ~50-line orchestrator |
| `skins/stratus/js/smart-bar/selection-manager.js` | **CREATE** | Selection state tracking |
| `skins/stratus/js/smart-bar/multi-select-controller.js` | **CREATE** | Multiselect mode management |
| `skins/stratus/js/smart-bar/mass-action-bar.js` | **CREATE** | Bar UI state management |
| `skins/stratus/js/smart-bar/action-dispatcher.js` | **CREATE** | Mode-aware action routing |
| `skins/stratus/js/smart-bar/sort-controller.js` | **CREATE** | Sort trigger + dialog |
| `plugins/stratus_helper/stratus_helper.php` | **MODIFY** | Add `include_script` for each new file |

## Dark Mode Considerations

None. Pure JS structural refactor — no visual changes.

## Validation Criteria

### 1. Module loading
- [ ] All 6 JS files load with HTTP 200 in DevTools → Network
- [ ] All 6 files appear in DevTools → Sources and are debuggable
- [ ] `window.StratusSmartBar` namespace exists and contains all 5 constructors
- [ ] No JS errors on page load

### 2. Functional parity — Standard mode
Same checklist as Phase 1 spec (selection, multiselect, mass actions, sort, pagination, folder switch).

### 3. Functional parity — Conversation mode
Same checklist as Phase 1 spec (conv selection, mass actions, pagination, count display).

### 4. Event contract verification
- [ ] `stratus:conv-massaction` events still fire with correct `{ action, value }` payload
- [ ] `stratus:conv-page` events still fire with correct `{ direction }` payload
- [ ] `stratus:conv-select-action` events still fire with correct `{ type }` payload
- [ ] `stratus:conv-set-multiselect` events still fire with correct `{ enabled }` payload
- [ ] `stratus:conv-clear-selection` events still fire
- [ ] `stratus:conv-selection-state` events are still consumed correctly
- [ ] `stratus:conv-count-update` events are still consumed correctly

Verify by adding temporary `console.log` statements in `conversation_mode.js` event listeners and confirming they fire with the same payloads as before the refactor.

### 5. isConversationMode() consolidation
- [ ] Grep all new files for `isConversationMode` or `data-conv-mode` — should appear ONLY in `action-dispatcher.js`
- [ ] No other module directly checks the current list mode

### 6. Monkey-patch consolidation
- [ ] `list.select_row` is patched in exactly ONE place: `multi-select-controller.js`
- [ ] `rcmail.display_message` is patched in exactly ONE place: `action-dispatcher.js`
- [ ] No other monkey-patches exist in the new code

### 7. Archive flow
- [ ] Archive button works in standard mode (selection → archive → folder appears in sidebar)
- [ ] Archive button works in conversation mode (selection → `stratus:conv-massaction` → conversations removed)
- [ ] `responseaftermove` handler still inserts missing archive folders into treelist

### 8. No regressions in edge cases
- [ ] Switching folders clears stale toasts and updates folder buttons
- [ ] `reapplyFolderDisabledClasses()` still runs after `listupdate` and `responseaftermark`
- [ ] Delete button disabled when in Trash folder
- [ ] Archive button disabled when in Archive folder or no archive configured
- [ ] Unread filter button still gets accessible label

## Risks / Open Questions

1. **Script load order for 6 files**: The 5 sub-modules must load BEFORE `smart-bar.js` orchestrator. Roundcube's `include_script()` should guarantee order, but verify. The implementing agent should add all sub-module includes before the orchestrator include.

2. **Global namespace pollution**: Using `window.StratusSmartBar` is simple but not ideal. If a future build step (e.g., Rollup/esbuild) is introduced, these can be converted to ES modules. For now, the namespace pattern is sufficient and matches Roundcube's own coding style.

3. **Constructor dependencies**: The orchestrator passes `rcmail`, `list`, and `bar` to constructors. If any module needs additional DOM references, it should resolve them internally from `bar` (the smart bar root element).

4. **Circular callbacks**: The wire-up in the orchestrator must avoid infinite loops. E.g., `dispatcher.onPostAction → selection.clearAll → selection.onChanged → massAction.updateState` is fine (linear). But if `massAction.updateState` could trigger another action dispatch, that would loop. The implementing agent should verify there are no feedback cycles.

5. **Partial failure**: If one sub-module fails to load (e.g., 404), the orchestrator will throw on `new ns.SelectionManager(...)`. The agent should add a defensive check: `if (!ns || !ns.SelectionManager) { console.error('...'); return; }`.
