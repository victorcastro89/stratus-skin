# Feature Spec: Unify Hover Action Ownership

**Status:** DRAFT

## Roadmap Reference

- **Phase:** Refactoring — JS Architecture Debt
- **Section:** Phase 4 of 4 — Unify Hover Action Ownership
- **Items:** Eliminate `window._stratus_hover_actions` global flag; establish single owner for hover actions across both standard and conversation mode
- **Depends on:** Phase 3 (Move Conv Logic) should be complete; Phase 1–2 recommended but not strictly required

## Summary

Hover actions (archive, delete, flag) on message rows are currently implemented **twice**: once by `stratus_helper.js` (`initUnifiedHoverActions()`, ~200 lines) and once by `conversation_mode.js` (`action_btn()` / `conv-hover-actions`, ~70 lines). The two systems are coordinated via a global flag: `window._stratus_hover_actions = true` (set by stratus_helper.js), which conversation_mode.js checks before injecting its own hover buttons.

This flag-based handshake is brittle — it depends on load order, it's invisible to TypeScript / linters, and it means two codepaths exist for the same feature. This spec eliminates the duplication by making `stratus_helper.js` the single owner for all hover actions (standard list **and** conversation rows), and removing the parallel implementation from `conversation_mode.js`.

## Goals

- **Single owner**: `stratus_helper.js` handles all hover action injection via `initUnifiedHoverActions()`, for both standard list rows and conversation rows.
- **Remove `window._stratus_hover_actions`**: No global flag needed when there's only one provider.
- **Remove `conv-hover-actions`** DOM creation from `conversation_mode.js`: The `if (!window._stratus_hover_actions)` block and associated `action_btn()` calls in `build_conv_row()` are deleted.
- **Keep `action_btn()` function**: It's still used for child-row actions (reply, delete, flag on expanded messages). Only remove the parent-row hover action creation from `build_conv_row()`.
- **Keep `child_action_btn()`**: Expanded child rows have their own action buttons — these are not duplicated by stratus_helper and must stay.
- **Preserve CSS**: `.mp-hover-actions` styling already works for conversation rows (the `MutationObserver` in stratus_helper.js already injects into conv rows).

## Non-Goals

- **No changes to child-row hover actions** — expanded message actions (reply, delete, flag) inside a conversation stay in conversation_mode.js.
- **No changes to CSS** — `.mp-hover-actions` and `.mp-hover-btn` styles already handle both standard and conversation rows.
- **No new event system** — this is a deletion, not a new architecture.
- **No changes to the smart bar or mass actions** — only hover row actions are affected.

## User Experience

No visible change. Hover actions on both standard list rows and conversation parent rows display archive / delete / flag buttons on hover, and they execute the same commands.

## Problem Analysis

### What is wrong today

**Two parallel hover action systems exist:**

| Aspect | stratus_helper.js (`mp-hover-actions`) | conversation_mode.js (`conv-hover-actions`) |
|--------|---------------------------------------|---------------------------------------------|
| Injection target | Standard list `<tr>` + conv `<tr>` | Conv parent `<tr>` only |
| CSS class | `.mp-hover-actions` | `.conv-hover-actions` |
| Button class | `.mp-hover-btn` | `.conv-action-btn` |
| Icons | CSS-only (background-image sprites) | `.conv-icon-*` pseudo-FA spans |
| Click handler | Direct `rcmail.command()` calls | Internal `cmd_archive()` / `cmd_delete()` |
| Flag toggle | Optimistic UI + `rcmail.mark_message()` | Via `cmd_flag()` which calls `rcmail.http_post()` |

**The coordination mechanism is a global flag:**

```
stratus_helper.js line 366:  window._stratus_hover_actions = true;
conversation_mode.js line 1034: if (!window._stratus_hover_actions) { ... }
```

**Problems:**

1. **Load-order dependent**: If conversation_mode.js loads before stratus_helper.js, the flag won't be set yet and conversation_mode will inject its own hover actions. Then stratus_helper's MutationObserver will inject a *second* set. (Currently this doesn't happen because stratus_helper loads first, but it's fragile.)

2. **Invisible coupling**: The flag doesn't appear in any interface, type definition, or plugin API. It's a handshake in the dark.

3. **Inconsistent behavior**: The two systems handle flag toggle differently:
   - stratus_helper: optimistic UI update + `rcmail.mark_message()` (native, uid-aware)
   - conversation_mode: `cmd_flag()` which triggers a batch operation on all messages in the conversation, then refreshes
   
   When stratus_helper's hover actions are active on a conv row, clicking "flag" only flags the conversation's latest message (via `getRowUid` → `data-uid`), not all messages in the conversation. The conv-native `cmd_flag()` correctly handles multi-message flagging.

4. **Duplicate CSS**: Two sets of hover action styles exist — `.mp-hover-actions` in the stratus LESS and `.conv-hover-actions` in conversation_mode's CSS.

### Impact of not fixing

- Two separate code paths for the same visual feature, diverging over time
- Flag/archive/delete behavior may differ subtly between standard and conv hover actions
- Any new hover action (e.g., "snooze", "label") must be added in two places
- The global flag remains a footgun for load-order bugs

### Correct ownership model

**stratus_helper.js owns the *visual injection* (DOM creation + positioning) of hover actions on ALL row types**, because:
- It's the skin's companion plugin — visual presentation is its job
- It already handles standard rows + conv rows via MutationObserver
- It uses the consistent `.mp-hover-actions` CSS system

**conversation_mode.js owns the *command execution* for conversation-specific actions**, because:
- `cmd_archive()`, `cmd_delete()`, `cmd_flag()` know about multi-message conversations
- Only conv-mode knows which UIDs belong to a conversation
- Standard mode can use direct `rcmail.command()` calls

**The solution**: stratus_helper.js injects the buttons; button click handlers detect whether the row is a conversation row and dispatch accordingly.

## Technical Design

### Step 1: Update stratus_helper.js click handlers to be conv-aware

In `initUnifiedHoverActions()` → `createHoverActions()`, update the archive / delete / flag click handlers to detect conversation rows:

```javascript
// Archive
archiveBtn.addEventListener('click', function(e) {
    e.preventDefault();
    e.stopPropagation();
    if (isConvRow(row)) {
        // Dispatch to conversation_mode's command
        selectConvRow(row);
        rcmail.command('plugin.conv.archive');
    } else {
        var uid = getRowUid(row);
        if (!uid) return;
        selectSingleRow(uid);
        rcmail.command('plugin.archive', '', row);
    }
});
```

Add a helper:
```javascript
function isConvRow(row) {
    return row && row.hasAttribute('data-conv-id');
}

function selectConvRow(row) {
    var id = row.id; // e.g. "rcmrowconv-<hash>"
    if (id && conv_state && conv_state.list_widget) {
        conv_state.list_widget.select(id);
    }
}
```

**But wait** — `conv_state` is private inside conversation_mode.js's IIFE. stratus_helper.js cannot access it directly.

**Resolution**: Instead of trying to reach into conversation_mode internals, stratus_helper's click handlers for conv rows should fire the existing **custom events** that pagenav.html / smart-bar already uses:

```javascript
if (isConvRow(row)) {
    // Ensure the conv row is selected first
    var listWidget = rcmail.message_list || window._conv_list_widget;
    if (listWidget) listWidget.select(row.id);
    // Dispatch via conversation_mode's registered command
    rcmail.command('plugin.conv.archive');
    return;
}
```

Actually, the simplest approach: conversation_mode.js registers Roundcube commands (`plugin.conv.archive`, `plugin.conv.delete`, `plugin.conv.flag`). These commands are globally available via `rcmail.command()`. stratus_helper.js just needs to:
1. Detect conv row
2. Ensure the row is selected in the list widget
3. Call `rcmail.command('plugin.conv.archive')` (or delete, flag)

The conversation_mode commands already handle multi-message logic.

### Step 2: Add conv-row selection helper to stratus_helper.js

```javascript
function selectConvRow(row) {
    // conversation_mode registers a list widget that we can access
    // The conv list uses rcmrowconv-<id> format
    var list = rcmail.message_list;
    if (!list) return;
    if (list.selection && list.selection.length === 1 && list.selection[0] === row.id) return;
    list.select(row.id);
}
```

Note: conversation_mode.js sets `conv_state.list_widget` as a custom `rcube_list_widget`. It does NOT overwrite `rcmail.message_list`. The implementing agent should check how conversation_mode exposes its list widget. Options:
- Use `row.closest('table')?.parentElement?.__conv_list_widget` — fragile
- Fire a click event on the row to trigger natural selection — simpler
- Have conversation_mode.js expose the list widget on a known key (e.g., `rcmail.conv_list_widget`)

**Recommended approach**: Have conversation_mode.js expose a minimal API:

```javascript
// In conversation_mode.js init:
rcmail.conv_api = {
    select_conv: function(conv_id) { /* select in list widget */ },
    get_list_widget: function() { return conv_state.list_widget; }
};
```

stratus_helper.js then uses:
```javascript
if (isConvRow(row) && rcmail.conv_api) {
    var convId = row.getAttribute('data-conv-id');
    rcmail.conv_api.select_conv(convId);
    rcmail.command('plugin.conv.archive');
}
```

### Step 3: Remove parent-row hover actions from conversation_mode.js

In `build_conv_row()` (around line 1034), delete:

```javascript
// Current code to DELETE:
if (!window._stratus_hover_actions) {
    var actions = document.createElement('span');
    actions.className = 'conv-hover-actions';
    actions.appendChild(action_btn('archive', 'archive', label('archive')));
    actions.appendChild(action_btn('delete', 'trash-alt', label('delete')));
    actions.appendChild(action_btn('flag', is_flagged ? 'flag' : 'flag-regular', ...));
    td_flags.appendChild(actions);
}
```

The `td_flags` cell remains — it still holds `.conv-flag-indicator`. Only the `conv-hover-actions` span is removed.

### Step 4: Remove the global flag

- In `stratus_helper.js`: Remove `window._stratus_hover_actions = true;` (line 366)
- In `conversation_mode.js`: Remove the `if (!window._stratus_hover_actions)` guard (now unnecessary since the hover actions block is deleted)

### Step 5: Clean up unused CSS

In `conversation_mode`'s CSS (`plugins/conversation_mode/skins/stratus/conversation_mode.css`), the `.conv-hover-actions` rules can be **kept** if child rows still use a similar pattern, or **removed** if only parent rows used them.

Check: child rows use `.conv-child-actions` (from `child_action_btn()`), not `.conv-hover-actions`. So `.conv-hover-actions` CSS rules can be safely removed.

### Step 6: Verify `update_parent_action_icons()` adapts

`conversation_mode.js` has `update_parent_action_icons()` (line 1376) which updates flag icons on parent rows. It looks for `.conv-action-flag` and `.conv-action-mark_read` selectors. After the change, parent rows will have `.mp-hover-btn.flag` instead.

Options:
a) Update `update_parent_action_icons()` to look for `.mp-hover-btn.flag` instead
b) Have stratus_helper.js handle flag state updates via its own MutationObserver / event listener
c) Have conversation_mode.js fire a custom event that stratus_helper responds to

**Recommendation**: Option (a) — simplest. Update the selector in `update_parent_action_icons()` to check for both `.conv-action-flag` and `.mp-hover-btn.flag`:

```javascript
var flag_btn = row.querySelector('.conv-action-flag, .mp-hover-btn.flag');
```

This keeps backward compatibility and requires minimal change.

## Files Changed

| File | Action | Description |
|------|--------|-------------|
| `plugins/stratus_helper/stratus_helper.js` | **MODIFY** | Update click handlers in `createHoverActions()` to detect conv rows and dispatch via `rcmail.command('plugin.conv.*')`; add `isConvRow()` helper; remove `window._stratus_hover_actions = true` |
| `plugins/conversation_mode/conversation_mode.js` | **MODIFY** | Remove parent-row `conv-hover-actions` injection from `build_conv_row()`; remove `window._stratus_hover_actions` check; expose minimal `rcmail.conv_api`; update `update_parent_action_icons()` selectors |
| `plugins/conversation_mode/skins/stratus/conversation_mode.css` | **MODIFY** | Remove `.conv-hover-actions` parent-row CSS rules (keep `.conv-child-actions` rules) |

## Dark Mode Considerations

None. Hover action styling uses CSS custom properties (`--mp-hover-*`) already defined in `_runtime.less` and `_dark.less`. The `.mp-hover-actions` buttons inherit the correct theme in both modes. Removing `conv-hover-actions` removes one fewer dark-mode variant to maintain.

## Validation Criteria

### 1. Standard list hover actions still work
- [ ] Hover over a standard message row → archive / delete / flag buttons appear
- [ ] Click archive → message is archived
- [ ] Click delete → message is deleted
- [ ] Click flag → flag toggles (flagged ↔ unflagged) with optimistic UI
- [ ] No console errors

### 2. Conversation row hover actions work
- [ ] Switch to conversation mode
- [ ] Hover over a conversation parent row → archive / delete / flag buttons appear
- [ ] Buttons use `.mp-hover-actions` / `.mp-hover-btn` classes (not `conv-hover-actions` / `conv-action-btn`)
- [ ] Click archive → entire conversation is archived (all messages move to Archive)
- [ ] Click delete → entire conversation is deleted
- [ ] Click flag → conversation flag toggles (all messages in conversation)
- [ ] Verify multi-message handling: flag a 5-message conversation → all 5 messages become flagged

### 3. No duplicate hover actions
- [ ] In conversation mode, inspect a parent `<tr>` in DevTools
- [ ] There should be exactly ONE `.mp-hover-actions` strip
- [ ] There should be NO `.conv-hover-actions` elements anywhere in the DOM
- [ ] Grep `conversation_mode.js` for `conv-hover-actions` → zero matches in DOM creation code

### 4. Global flag eliminated
- [ ] Grep entire codebase for `_stratus_hover_actions` → zero matches
- [ ] No `window._stratus_hover_actions` in any JS file

### 5. Child row actions unaffected
- [ ] Expand a conversation with 3+ messages
- [ ] Each child message row still has reply / delete / flag action buttons
- [ ] Buttons use `.conv-child-actions` / `child_action_btn` (unchanged)
- [ ] Child reply → opens compose for that specific message
- [ ] Child delete → deletes that specific message (not the whole conversation)
- [ ] Child flag → toggles flag on that specific message

### 6. Flag state sync
- [ ] In conversation mode, flag a conversation via hover action
- [ ] The flag indicator icon updates (filled star / empty star)
- [ ] `update_parent_action_icons()` correctly finds the flag button (new `.mp-hover-btn.flag` selector or dual selector)
- [ ] Unflag the conversation → icon reverts

### 7. Dark mode
- [ ] Switch to dark mode
- [ ] Hover actions on conv rows render correctly (proper colors, no invisible buttons)
- [ ] Same visual appearance as hover actions on standard list rows

### 8. Plugin disabled scenario
- [ ] (If testable) Disable `conversation_mode` plugin
- [ ] Standard hover actions still work on standard list rows
- [ ] No errors about missing `rcmail.conv_api` or `plugin.conv.*` commands
- [ ] stratus_helper.js `isConvRow()` returns false for all rows → standard path used

### 9. Performance
- [ ] MutationObserver in stratus_helper.js handles new conv rows efficiently
- [ ] No double-injection (check: each row processed once by `createHoverActions()` guard: `if (row.querySelector('.mp-hover-actions')) return`)

## Risks / Open Questions

### 1. Conversation command availability (MEDIUM)
When conversation_mode is disabled, `rcmail.command('plugin.conv.archive')` will fail silently (Roundcube's command system returns false for unknown commands). stratus_helper.js should guard:
```javascript
if (isConvRow(row) && rcmail.commands['plugin.conv.archive']) {
    rcmail.command('plugin.conv.archive');
} else {
    // Fallback to standard action
}
```
But if conversation_mode is disabled, there won't be conv rows in the DOM, so `isConvRow()` will never return true. The guard is defensive-only.

### 2. List widget selection for conv rows (MEDIUM)
stratus_helper.js needs to select the conv row before dispatching commands. conversation_mode's list widget is private. The recommended `rcmail.conv_api` approach requires conversation_mode.js to expose a small API. The implementing agent must ensure this API is set up before stratus_helper tries to use it. Since both plugins init on the same `init` event, the agent should verify init order or use a deferred pattern.

### 3. `update_parent_action_icons()` selector change (LOW)
After the migration, `update_parent_action_icons()` looks for `.mp-hover-btn.flag` instead of `.conv-action-flag`. If stratus_helper changes its class names in the future, this selector will break. Consider having stratus_helper fire a `stratus:hover-action-update` event that any consumer can listen to. But this is over-engineering for now — the dual selector approach is fine.

### 4. CSS specificity (LOW)
`.mp-hover-actions` in stratus LESS targets `td.flags .mp-hover-actions`. Conv rows use `td.conv-flags-cell` (which also has class `flags`). The existing CSS should match. The implementing agent should verify that `.mp-hover-actions` positioning (absolute, right-aligned) renders correctly inside `td.conv-flags-cell`.

### 5. Conversation mode's `action_btn()` function (LOW)
After removing the parent-row usage, `action_btn()` may become unused if no other code calls it. Check: parent rows were the only caller. `child_action_btn()` is a separate function. So `action_btn()` can be deleted entirely if parent-row hover actions are removed. The implementing agent should verify this and clean up if safe.
