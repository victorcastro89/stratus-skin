# Feature Spec: Move Conversation-Specific Logic out of stratus_helper.js

**Status:** DRAFT

## Roadmap Reference

- **Phase:** Refactoring — JS Architecture Debt
- **Section:** Phase 3 of 4 — Move Conv Logic to conversation_mode.js
- **Items:** Relocate `initConvInListOptions()` and the `set_list_options` monkey-patch from stratus_helper.js to conversation_mode.js
- **Depends on:** Phase 2 (Concern Modules) should be complete, but this phase can technically run independently

## Summary

`stratus_helper.js` contains a function `initConvInListOptions()` (~100 lines, starting at line 680) that creates DOM elements for the "Conversations" option in the list-options dialog, syncs the dialog's mode select when it opens, and monkey-patches `rcmail.set_list_options` to fire `plugin.conv.setmode` when "conversations" is chosen. This is conversation-mode business logic living inside a skin-helper plugin. This spec moves that logic into `conversation_mode.js`, where it belongs.

## Goals

- Move `initConvInListOptions()` from `stratus_helper.js` to `conversation_mode.js`
- Move the `rcmail.set_list_options` monkey-patch from `stratus_helper.js` to `conversation_mode.js`
- Ensure the list-options dialog still works identically (conversations option present, pre-selected when active, save triggers mode change)
- Remove conversation-mode awareness from `stratus_helper.js` entirely
- Reduce coupling between the skin helper plugin and the conversation mode plugin

## Non-Goals

- **No changes to the list-options dialog HTML** — template stays as-is.
- **No changes to CSS** — visual output is untouched.
- **No changes to `conversation_mode.php`** — server-side stays as-is.
- **No refactoring of `conversation_mode.js` internals** — just adding the relocated function.
- **No removal of the `<option value="conversations">` from mail.html template** — the template already has it for when `env:threads` is available. The JS fallback handles the case where it's missing.

## User Experience

No visible change. The list-options dialog continues to show "Conversations" as a mode option, and switching works identically.

## Problem Analysis

### What is wrong today

`stratus_helper.js` is the companion plugin for the **stratus skin**. Its responsibilities are: color scheme switching, font switching, dark mode iframe propagation, hover actions, smart bar controller, and search empty state.

Inside `initSmartBarController()` (line 572), the function `initConvInListOptions()` (line 680) does the following:

1. **Creates DOM elements** — If `#listoptions-threads` or `#mp-listoptions-mode` don't exist, it injects a `<div class="form-group row">` with a `<select>` containing "List" and "Conversations" options into the `#listoptions-menu` popup.

2. **Syncs dialog state** — On `$(document).on('dialogopen', ...)`, it checks if `data-conv-mode` is "conversations" and pre-selects that option in the cloned dialog.

3. **Monkey-patches `rcmail.set_list_options`** — Intercepts the save flow to detect when "conversations" is selected, fires `rcmail.http_post('plugin.conv.setmode', ...)`, and forces `threading = 0`.

This is **conversation_mode business logic**:
- It references `plugin.conv.setmode` (a conversation_mode AJAX action)
- It reads `data-conv-mode` (a conversation_mode attribute)
- It uses `conversation_mode.mode_conversations` (a conversation_mode i18n key)
- It manipulates threading state for conversation mode

A skin helper should not know about conversation-mode internals. If the conversation_mode plugin is disabled, this code runs anyway, injecting UI elements for a feature that doesn't exist.

### Impact of not fixing

- `stratus_helper.js` remains coupled to `conversation_mode`
- If conversation_mode is removed or replaced, stratus_helper still tries to inject conversation UI
- The `set_list_options` monkey-patch in stratus_helper can conflict with other patches (Phase 2's ActionDispatcher may also need to intercept list options)
- Two plugins (stratus_helper + conversation_mode) both manipulate the same dialog, with no clear ownership

### The fix is clean

`conversation_mode.js` already has an `init` handler. The relocated function simply moves inside that handler. The only coordination needed is that `conversation_mode.js` must check whether the DOM elements already exist (template-provided) before creating fallbacks — which it already does for its own containers.

## Technical Design

### Step 1: Copy `initConvInListOptions()` to `conversation_mode.js`

In `conversation_mode.js`, inside the `rcmail.addEventListener('init', function() { ... })` block (around line 264), add a call to a new function `init_conv_in_list_options()`.

The function is a near-exact copy of `initConvInListOptions()` from `stratus_helper.js` (lines 680-778), with these adjustments:

1. **Variable references**: Change `layoutList` to `dom.layout_list` (already available in conversation_mode.js scope).
2. **Label lookups**: Change `rcmail.get_label('conversation_mode.mode_conversations')` to use the existing `label()` helper: `label('mode_conversations')`.
3. **Guard**: Only run if `conv_state.mode` is defined (i.e., plugin is active).
4. **No jQuery dependency**: The current code uses `$(document).on('dialogopen', ...)`. Conversation_mode.js avoids jQuery. Replace with vanilla `document.addEventListener('dialogopen', ...)` or use a `MutationObserver` to detect when the dialog opens. Alternatively, keep the jQuery call since Roundcube always loads jQuery.

The `rcmail.set_list_options` monkey-patch moves as-is. The guard `if (rcmail._stratus_conv_slo_patched) return;` prevents double-patching.

### Step 2: Remove `initConvInListOptions()` from `stratus_helper.js`

In `stratus_helper.js`, inside `initSmartBarController()`:

1. Delete the entire `initConvInListOptions()` function definition (lines ~680-778).
2. Delete the call `initConvInListOptions();` at the bottom of `initSmartBarController()` (line ~778).
3. No other changes to `stratus_helper.js`.

### Step 3: Verify conversation_mode.js doesn't duplicate

The template `mail.html` already provides `<option value="conversations">` inside `#listoptions-threads` (when `env:threads` is active) and `#mp-listoptions-mode` (when it's not). The JS function is a fallback that creates the option if the template didn't provide it. This fallback logic stays the same.

Additionally, `conversation_mode.js` already imports its own CSS and registers its own commands. The list-options integration is a natural extension.

### Step 4: Add defensive guard in conversation_mode.js

Since `conversation_mode.js` only runs when the plugin is enabled (`in_array('conversation_mode', config:plugins)`), the list-options integration will **only exist when the plugin is active**. This is better than the current situation where `stratus_helper.js` injects conversation UI regardless.

Add at the top of the new function:
```javascript
function init_conv_in_list_options() {
  // Only integrate if we're in the mail task
  if (rcmail.env.task !== 'mail') return;
  // ... rest of the function
}
```

## Files Changed

| File | Action | Description |
|------|--------|-------------|
| `plugins/conversation_mode/conversation_mode.js` | **MODIFY** | Add `init_conv_in_list_options()` function + call it from init |
| `plugins/stratus_helper/stratus_helper.js` | **MODIFY** | Remove `initConvInListOptions()` function + its call |

## Dark Mode Considerations

None. The list-options dialog inherits its theme from the parent page. No dark-mode-specific code in the moved function.

## Validation Criteria

### 1. List-options dialog — standard mode
- [ ] Open mail inbox in standard list mode
- [ ] Click the sort trigger → list-options dialog opens
- [ ] The "List mode" select exists with "List" and optionally "Threads" options
- [ ] "Conversations" option is present in the select
- [ ] Select "Conversations" → Save → conversation mode activates
- [ ] Reopen dialog → "Conversations" is pre-selected

### 2. List-options dialog — conversation mode active
- [ ] While in conversation mode, click sort trigger → dialog opens
- [ ] "Conversations" is pre-selected in the mode dropdown
- [ ] Select "List" → Save → standard mode restores
- [ ] Reopen dialog → "List" is pre-selected

### 3. List-options dialog — threads enabled
- [ ] If IMAP server supports threads (`env:threads` is true):
  - [ ] The template-provided `#listoptions-threads` select has all three options: List, Threads, Conversations
  - [ ] Switching between all three modes works correctly

### 4. stratus_helper.js is clean
- [ ] Grep `stratus_helper.js` for `conv`, `conversation`, `plugin.conv`, `setmode` → zero matches
- [ ] Grep `stratus_helper.js` for `set_list_options` → zero matches (monkey-patch removed)
- [ ] Grep `stratus_helper.js` for `initConvInListOptions` → zero matches

### 5. No console errors
- [ ] Navigate mail view, open list-options dialog, switch modes
- [ ] Zero JS errors in DevTools console

### 6. Plugin disabled scenario
- [ ] (If testable) Disable the `conversation_mode` plugin in Roundcube config
- [ ] Open mail view → no "Conversations" option in list-options dialog
- [ ] No JS errors related to conversation mode
- [ ] `stratus_helper.js` loads and works normally without any conversation references

### 7. set_list_options monkey-patch
- [ ] The `rcmail._stratus_conv_slo_patched` guard prevents double-patching
- [ ] Changing sort column/order via the dialog still works (the monkey-patch passes through to `_origSLO`)
- [ ] The monkey-patch only intercepts when `selectedMode === 'conversations'` or when leaving conversation mode

## Risks / Open Questions

1. **jQuery dependency**: The current `$(document).on('dialogopen', ...)` uses jQuery UI's dialog event. `conversation_mode.js` generally avoids jQuery. The implementing agent can either:
   - Keep the jQuery call (jQuery is always available in Roundcube)
   - Replace with a `MutationObserver` watching for `.ui-dialog-content` elements appearing
   - Use `document.addEventListener('click', ...)` on the sort trigger to detect when the dialog will open
   
   Recommendation: Keep the jQuery call for now — it's battle-tested and concise.

2. **Init timing**: `conversation_mode.js` runs its init handler at the same time as `stratus_helper.js`. The list-options integration doesn't depend on stratus_helper's smart bar initialization, so there's no timing issue. The dialog elements are in the DOM from the template.

3. **The `_stratus_conv_slo_patched` flag**: This flag on `rcmail` prevents double-patching. After the move, it should still work because only one file (conversation_mode.js) sets it. If stratus_helper.js is loaded first and the flag already exists from a previous session... no, flags are per-page-load, so this is fine.

4. **Future refactoring**: In Phase 2, the `ActionDispatcher` module may also need to intercept `set_list_options` for mode-awareness. If both conversation_mode.js and ActionDispatcher patch the same function, they'll conflict. The implementing agent should note this and ensure only conversation_mode.js patches `set_list_options`. The ActionDispatcher should read mode state, not intercept save flows.
