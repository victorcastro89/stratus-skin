# Feature Spec: Extract Pagenav Inline Script into External JS Module

**Status:** DRAFT

## Roadmap Reference

- **Phase:** Refactoring — JS Architecture Debt
- **Section:** Phase 1 of 4 — Extract the Pagenav God Script
- **Items:** Move inline `<script>` from `pagenav.html` into a proper external JS file

## Summary

The `skins/stratus/templates/includes/pagenav.html` template contains ~770 lines of inline JavaScript inside a `<script>` tag. This single block handles 10+ independent concerns (selection management, multiselect, mass actions, archive handling, folder-context buttons, pagination interception, count display, toast suppression, popover lifecycle, and more). This spec extracts that code into an external JS file loaded by `stratus_helper`, leaving `pagenav.html` as pure HTML markup.

## Goals

- Move all inline JS from `pagenav.html` into `skins/stratus/js/smart-bar.js`
- Load `smart-bar.js` via `stratus_helper.php` (server-side include) so it is available on the mail task
- Preserve 100% identical runtime behavior — zero functional changes
- Enable source maps, linting, and debugger breakpoints on the extracted code
- Eliminate the largest single maintenance burden in the codebase

## Non-Goals

- **No refactoring of the JS logic itself** — this phase is a pure extraction/move. The code structure, variable names, and flow remain identical.
- **No splitting into sub-modules** — that is Phase 2.
- **No changes to `conversation_mode.js` or `stratus_helper.js`** beyond the include mechanism.
- **No changes to CSS/LESS** — visual output is untouched.
- **No changes to the HTML structure** of `pagenav.html` — only the `<script>` block is removed.

## User Experience

No visible change. The mail list, smart bar, selection, mass actions, pagination, and all conversation-mode interactions behave identically before and after this change.

## Problem Analysis

### What is wrong today

1. **Untestable**: Inline `<script>` blocks in Roundcube templates cannot be linted, source-mapped, or breakpointed in DevTools without manual effort.
2. **Unmaintainable**: 770 lines in a single inline block, mixing HTML template context with complex JS logic. Every edit requires scanning the entire block.
3. **Bug-prone**: The inline script has `if (isConversationMode())` branches in nearly every function. A bug in standard-mode logic silently regresses conversation mode because there are no clear boundaries.
4. **Load-order fragility**: The inline script runs when the template is parsed. If it runs before `conversation_mode.js` registers its event listeners, events are lost. An external file loaded via `include_script()` has deterministic ordering.
5. **Duplicate monkey-patches**: `list.select_row` is monkey-patched both in this inline script AND in `conversation_mode.js`. Without source files, tracing which patch runs first is guesswork.

### Impact of not fixing

Every new feature or bug fix in the smart bar / selection / mass-action area requires editing a 770-line inline block inside an HTML template. The risk of introducing regressions grows with each change. Dark-mode, conversation-mode, and standard-mode code paths are interleaved with no separation.

## Technical Design

### Architecture

```
BEFORE:
  pagenav.html
    ├── HTML markup (~75 lines)
    └── <script> ... 770 lines of JS ... </script>

AFTER:
  pagenav.html
    └── HTML markup only (~75 lines, no <script>)

  skins/stratus/js/smart-bar.js
    └── (function() { ... 770 lines, identical logic ... })();

  stratus_helper.php  (already loads stratus_helper.js for mail task)
    └── $this->include_script('../../skins/stratus/js/smart-bar.js');
        OR register as a skin script via meta.json
```

### Step-by-step implementation

#### Step 1: Create `skins/stratus/js/smart-bar.js`

1. Copy the entire content of the `<script>` block from `pagenav.html` (lines 78–848) into a new file `skins/stratus/js/smart-bar.js`.
2. Wrap the content in an IIFE if not already wrapped: `(function() { ... })();`
3. Verify the IIFE self-invokes on `rcmail.addEventListener('init', ...)` — the existing code already does this.
4. No other changes to the code.

#### Step 2: Remove `<script>` block from `pagenav.html`

1. In `skins/stratus/templates/includes/pagenav.html`, delete everything from `<script>` (line 77) through `</script>` (line 849).
2. Leave all HTML markup, `<roundcube:*>` tags, and `<roundcube:add_label>` declarations intact.
3. The `<roundcube:add_label>` tags at the top of the file MUST remain — they export i18n strings that the extracted JS still needs.

#### Step 3: Load `smart-bar.js` in the mail task

Option A (preferred): Add a `<roundcube:script>` tag in `pagenav.html` where the inline script used to be:
```html
<roundcube:script file="/skins/stratus/js/smart-bar.js" />
```

Option B: Load via `stratus_helper.php` in `init_mail()`:
```php
$this->include_script('../../skins/stratus/js/smart-bar.js');
```

Option C: Register in `meta.json` under `scripts` so it loads automatically with the skin. This is the cleanest approach if Roundcube's skin loader supports it.

**Decision**: The implementing agent should try Option A first (template-based include). If Roundcube's template engine doesn't support `<roundcube:script>` at that location, fall back to Option B. Document the choice.

#### Step 4: Verify load order

The JS in `smart-bar.js` relies on:
- `window.rcmail` existing (guaranteed — Roundcube core loads first)
- `rcmail.message_list` existing (guaranteed — list widget initializes before plugin `init` events)
- DOM elements from `pagenav.html` existing (guaranteed — template is parsed before script runs)
- `conversation_mode.js` event listeners being registered (guaranteed — both fire on `rcmail.addEventListener('init', ...)` and communicate via DOM events, not direct calls)

The agent MUST verify that `smart-bar.js` loads and initializes **after** the DOM elements it references exist. If using Option A (`<roundcube:script>`), the script tag should appear after the HTML markup in `pagenav.html`. If using Option B (PHP include), it loads after the page template is fully parsed.

#### Step 5: Validate

See Validation Criteria below.

## Files Changed

| File | Action | Description |
|------|--------|-------------|
| `skins/stratus/js/smart-bar.js` | **CREATE** | New file — extracted JS from pagenav.html |
| `skins/stratus/templates/includes/pagenav.html` | **MODIFY** | Remove `<script>...</script>` block; optionally add `<roundcube:script>` tag |
| `plugins/stratus_helper/stratus_helper.php` | **MODIFY** (if Option B) | Add `include_script` call for smart-bar.js |
| `skins/stratus/meta.json` | **MODIFY** (if Option C) | Add script reference |

## Dark Mode Considerations

None. This is a pure code extraction — no visual/CSS changes.

## Validation Criteria

All validation assumes the Docker dev environment is running (`npm run docker:up`).

### 1. Compilation check
- [ ] `npm run less:build` completes with zero errors (no LESS changes, but verify nothing broke)
- [ ] No JS syntax errors in `smart-bar.js` (open in browser DevTools → Sources → verify no red underlines)

### 2. Functional parity — Standard mode
- [ ] Open mail inbox in standard (non-conversation) list mode
- [ ] Click a message → preview loads in reading pane
- [ ] Click the checkbox toggle → multiselect mode activates, row clicks toggle selection
- [ ] Select multiple messages → mass-action bar appears with count chip
- [ ] Click Delete → messages are deleted, bar resets
- [ ] Click Archive → messages are archived, folder appears in sidebar if new
- [ ] Click Mark read/unread toggle → messages toggle read state
- [ ] Click Flag/Unflag toggle → messages toggle flagged state
- [ ] Click "More" → popover menu opens, items work
- [ ] Click Sort trigger → list options dialog opens, changing sort works
- [ ] Click Refresh → `checkmail` fires
- [ ] Prev/Next page buttons work
- [ ] Switch folders → count display updates, stale toasts clear

### 3. Functional parity — Conversation mode
- [ ] Switch to conversation mode via list options dialog
- [ ] Conversation list renders correctly
- [ ] Click checkbox toggle → multiselect mode, select all/none/unread/flagged work
- [ ] Select conversations → mass-action bar shows count
- [ ] Delete/Archive/Mark/Flag mass actions dispatch to conversation_mode.js via `stratus:conv-*` events
- [ ] Prev/Next page buttons dispatch `stratus:conv-page` events
- [ ] Count display shows "N conversations"
- [ ] Switch back to standard mode → list restores normally

### 4. No console errors
- [ ] Open browser DevTools Console
- [ ] Navigate through mail, switch modes, perform actions
- [ ] **Zero** new JS errors or warnings (existing ones from Roundcube core are acceptable)

### 5. Script loading verification
- [ ] In DevTools → Network tab, verify `smart-bar.js` loads with HTTP 200
- [ ] In DevTools → Sources tab, verify `smart-bar.js` appears and is debuggable (can set breakpoints)

## Risks / Open Questions

1. **Script loading mechanism**: Which of Options A/B/C works best with Roundcube's template engine? The implementing agent should try Option A first, document if it fails, and fall back.
2. **Load order race condition**: If `smart-bar.js` fires before the DOM elements from `pagenav.html` are in the DOM, `document.querySelector('.mp-smart-bar')` returns null. The existing code already guards against this (`if (!bar) return;`), but verify.
3. **Roundcube's `<roundcube:script>` tag**: Verify this tag is supported in included templates (not just top-level). Elastic uses it in some templates but not in `pagenav.html`.
4. **Caching**: After deployment, browsers may cache the old inline-script version. The external JS file will be a new URL, so this should self-resolve. No cache-busting needed for dev.
5. **Other skins**: This change only affects the stratus skin. Stock elastic's `pagenav.html` has no inline script of this size — it's a stratus-only override.
