# Feature Spec: Smart Context Bar — Unified Message List Controls

**Status:** IMPLEMENTED

## Roadmap Reference
- **Phase:** Conversation Mode Plugin — Phase 1.5 §3: Selection, Actions & Context Menu
- **Related:** Mail List UX Polish (Dogfood Session — 2026-03-05)
- **Related:** Conversation Mode — #layout-list Dogfood (2026-03-04) — hover actions standardization
- **Items:**
  - Standardize hover actions (archive, delete, flag) across both standard and conversation modes
  - Redesign `#messagelist-header` toolbar — merge with mass-action bar into a single adaptive control strip
  - Multi-select support with bulk actions bar

## Summary
Replace the current two-strip control area (`#messagelist-header` toolbar + `.mp-mass-action-bar` footer) with a single **Smart Context Bar** that morphs between a default state (list controls: sort indicator, refresh, pagination) and a selection state (bulk action buttons + selected count). The conversation toggle moves into the sort/options popup (`messagelistmenu`), freeing bar space. Simultaneously, unify the row-level hover actions so that **archive, delete, and flag** appear on hover in both standard list mode and conversation mode, using a single `mp-hover-actions` system implemented in `stratus_helper.js`.

This is a Gmail-style adaptive toolbar pattern: one strip, two visual states, zero redundancy.

## Goals
- **Eliminate the header toolbar entirely** — remove `#messagelist-header` as a separate visual element
- **Merge all controls into one bar** — the existing `.mp-mass-action-bar` evolves to show list controls in its default state
- **Reclaim ~40px vertical space** — one strip instead of two
- **Standardize hover actions** — archive, delete, flag on every message row regardless of mode
- **Remove redundant toolbar buttons** — Archive button (now in hover), Select (already in mass-action), Threads dropdown (merged into sort/options), Conversation toggle (moved into sort/options popup)
- **Keep essential controls accessible** — sort indicator, refresh, pagination always reachable in the bar; conversation toggle one click away in the options popup

## Non-Goals
- Mobile/phone layout changes (separate effort under Phase 1.5 §5)
- Swipe gesture support (Phase 1.5 §5)
- Right-click context menu redesign (Phase 1.5 §3 — separate spec)
- Drag-and-drop reordering (Phase 1.5 §3)
- Changes to the searchbar — it stays exactly where it is, above the context bar
- Changes to the reading pane or message view
- Keyboard shortcut changes (existing shortcuts continue to work)

## User Experience

### Default State (no selection)

**Desktop:**
```
┌───────────────────────────────────────────────────────────────┐
│ ☐    Date ▾ ⇅               ↻         1-25 of 142  ◀  ▶    │
└───────────────────────────────────────────────────────────────┘
```

**Phone (`html.layout-phone`):**
```
┌───────────────────────────────────────────────────────────────┐
│ ☰  ◀  ☐    ⇅  ↻                       1-25 of 142  ◀  ▶    │
└───────────────────────────────────────────────────────────────┘
```

| Element | Position | Behavior |
|---|---|---|
| **Mobile nav buttons** (`.task-menu-button`, `.back-sidebar-button`) | Very first children (far left) | Relocated from the removed `#messagelist-header`. Keep the exact same elastic CSS class names so existing responsive rules (`html.layout-phone .back-sidebar-button { display: block }` etc.) continue to work. On desktop they remain `display:none`. On phone they appear at the far left, pushing the checkbox to the right. No behavior change — just a DOM relocation. |
| **Checkbox** (☐) | Left (after mobile nav on phone) | Click → enters multiselect mode (indeterminate dash). Click again → selects all. Click again → deselects all and exits multiselect. Same 3-state logic as current mass-action bar. |
| **Sort indicator** (`Date ▾`) | Left of center | **Desktop:** Shows current sort column name as text label + direction arrow (e.g. `Date ▾`, `From ▴`). **Phone:** Icon-only (⇅ sort icon). **Click** → opens the existing `messagelistmenu` panel (sort column, sort order, list mode, conversation toggle, thread actions). This replaces the Options button, the Threads dropdown, AND the conversation toggle button. |
| **Refresh** (↻) | Right group | `checkmail` command. Same as current refresh button. |
| **Pagination** (`1-25 of 142 ◀ ▶`) | Far right | Message count text + prev/next arrows. Same as current mass-action bar right side. |

### Selection State (≥1 message selected)

```
┌───────────────────────────────────────────────────────────────┐
│ ☑ 3   🗑  📁  📂  📩  🏳  ⋯    │         3 selected  ◀  ▶  │
└───────────────────────────────────────────────────────────────┘
```

| Element | Position | Behavior |
|---|---|---|
| **Checkbox** (☑ with count) | Far left | Blue checked state. Adjacent count badge shows selected count. Click → deselects all, returns to default state. |
| **Action buttons** | Left group | Delete, Archive, Move, Mark read/unread, Flag/unflag, More (⋯). Same buttons as current mass-action-actions. Exactly the same commands wired. |
| **Selected count chip** | Right area | "3 selected" text chip with subtle background. |
| **Pagination** | Far right | Remains visible for reference, but action buttons take priority. |

### Transition Between States

- **Default → Selection:** When user clicks checkbox (entering multiselect) or Ctrl+clicks a row, the bar smoothly transitions: sort indicator and toggle slide out (opacity + translateX), action buttons slide in from left. CSS transition ~200ms.
- **Selection → Default:** When last item is deselected (or checkbox toggled off), action buttons slide out, sort/toggle slide back in.
- The bar height stays constant (40px) — no layout shift.

### Hover Actions on Message Rows

In both standard and conversation mode, hovering a message row reveals a floating action strip on the right side (overlapping the date):

```
│  ★  John Doe      Meeting tomorrow     [📁] [🗑] [🏳]  │
```

| Button | Icon | Action |
|---|---|---|
| **Archive** | `archive` (box-arrow) | Runs `plugin.archive` (standard) or `plugin.conv.archive` (conversation) |
| **Delete** | `trash-alt` | Runs `delete` command |
| **Flag** | `flag` (outline→solid toggle) | Toggles flagged state |

- Hover actions appear with a subtle fade-in (opacity 0→1, 100ms)
- Background: frosted glass pill (same `@mp-glass-bg-strong` as current `.listing-hover-menu`)
- In conversation mode, replaces the existing `conv-hover-actions`; in standard mode, replaces elastic's flag-only `span.flag` hover behavior

### Options Popup Consolidation

The `messagelistmenu` popup absorbs all mode/thread/view controls. It becomes the single "list settings" panel:

```
┌──────────────────────────┐
│  Sort by                 │
│  ○ Date  ○ From  ○ To   │
│  ○ Subject  ○ Size       │
│  ──────────────────────  │
│  Order                   │
│  ○ Ascending ○ Descending│
│  ──────────────────────  │
│  List mode               │
│  ○ List  ○ Threads       │
│  ──────────────────────  │
│  Conversations            │  ← NEW: toggle switch
│  [ON / OFF]               │
│  ──────────────────────  │
│  Thread actions           │  ← shown only when threads mode
│  Expand unread            │
│  Expand all               │
│  Collapse all             │
└──────────────────────────┘
```

The **conversation toggle** moves here as an ON/OFF switch (or checkbox). When toggled, it fires the same `plugin.conv.toggle` command. The `conv-toggle` button injected by the plugin into `#convtoolbar` is no longer visible in the bar — it's re-parented into this popup by JS.

This consolidation means sort, list mode, conversation mode, and thread controls are all in one panel — one click from the sort indicator opens everything.

## Technical Design

### Architecture Overview

```
BEFORE:
  #layout-list (flex column)
    ├── #messagelist-header (.header)     ← toolbar strip #1
    │     ├── .task-menu-button           (phone only)
    │     ├── .back-sidebar-button        (phone only)
    │     ├── .toolbar.menu               (Select, Threads, Options, plugin containers)
    │     └── .refresh                    (checkmail)
    ├── #mailsearchform (.searchbar)
    ├── #searchmenu
    ├── .mp-mass-action-bar (.pagenav)    ← toolbar strip #2
    └── #messagelist-content (.scroller)

AFTER:
  #layout-list (flex column)
    ├── #mailsearchform (.searchbar)
    ├── #searchmenu
    ├── .mp-smart-bar                     ← single adaptive strip
    │     ├── .task-menu-button           (phone only, display:none on desktop)
    │     ├── .back-sidebar-button        (phone only, display:none on desktop)
    │     ├── .mp-smart-bar-left
    │     │     ├── checkbox trigger
    │     │     ├── .mp-smart-bar-default  (sort indicator, refresh)
    │     │     └── .mp-mass-action-actions (selection state buttons)
    │     ├── .mp-smart-bar-right
    │     │     ├── count / chip
    │     │     └── pagination
    │     └── #mp-plugin-slots (hidden)   (roundcube:container injection points)
    └── #messagelist-content (.scroller)
```

### 1. Template Changes (`skins/stratus/templates/mail.html`)

**Remove `#messagelist-header`** entirely from the template. This div currently contains:
- Mobile menu/back buttons (`.task-menu-button`, `.back-sidebar-button`)
- `.toolbar.menu` with: Select, Threads, Options (listmenulink), `<roundcube:container name="listcontrols">`, `<roundcube:container name="toolbar" id="convtoolbar">`
- Refresh button
- `toolbar-menu-button`

**Mobile nav buttons** (`.task-menu-button`, `.back-sidebar-button`) move into `.mp-smart-bar` as the **very first children**, keeping the exact same elastic CSS class names. Elastic's existing responsive rules (`html.layout-phone .back-sidebar-button { display: block }`) continue to work with zero CSS changes. On desktop they remain `display:none`. On phone they appear at the far left of the smart bar.

**The `<roundcube:container>` tags** (`listcontrols` and `convtoolbar`) are critical — they're injection points for plugins (archive button goes into `listcontrols`, conversation toggle goes into `convtoolbar`). These must be preserved somewhere in the DOM so Roundcube's plugin system can inject buttons. We'll place them in a hidden `#mp-plugin-slots` div inside the smart bar. JS will:
- Extract the archive button and hide it (archive is now a hover action only)
- Extract the conversation toggle and re-parent it into the `messagelistmenu` popup as a toggle switch
- Any other plugin buttons get appended to the smart bar's default section

### 2. Template Changes (`skins/stratus/templates/includes/pagenav.html`)

The existing mass-action bar template evolves into the smart bar. Key changes:

- Rename root class from `mp-mass-action-bar` to `mp-smart-bar` (keep `pagenav` and `menu` classes for Roundcube compatibility)
- Add a **default-state section** (`.mp-smart-bar-default`) containing:
  - Sort indicator button (`.mp-sort-trigger`) — text label on desktop (`Date ▾`), icon-only on phone. Click opens `messagelistmenu`
  - Refresh button (`checkmail` command)
- The existing **selection-state section** (`.mp-mass-action-actions`) stays as-is
- CSS controls which section is visible based on state classes
- The conversation toggle is NOT in the bar — it lives inside the `messagelistmenu` popup (placed there by JS from the plugin container)

### 3. LESS Changes

#### `skins/stratus/styles/widgets/common.less`
- Rename `.mp-mass-action-bar` → `.mp-smart-bar` throughout (or add `.mp-smart-bar` as an alias)
- Add `.mp-smart-bar-default` section: flex layout for sort trigger, toggle, refresh
- Add `.mp-sort-trigger` styles: clickable label showing sort column text + chevron icon
- Add transition rules for the state morph (opacity + transform on `.mp-smart-bar-default` and `.mp-mass-action-actions`)
- Update `.mp-hover-actions` styles (new unified hover action strip)

#### `skins/stratus/styles/widgets/lists.less`
- Remove/replace elastic's native flag-hover behavior override with the new `mp-hover-actions` system
- Style `.mp-hover-actions`: absolute positioned, right-aligned, frosted glass pill, flex row of icon buttons
- Ensure hover actions don't conflict with conversation mode's row layout

#### Dark mode co-located in each file
- `.mp-smart-bar` dark variant in `common.less` dark section
- `.mp-hover-actions` dark variant in `lists.less` dark section

#### `skins/stratus/styles/_runtime.less`
- Add CSS custom properties for hover actions used by plugin CSS: `--mp-hover-actions-bg`, `--mp-hover-actions-shadow`, `--mp-hover-actions-icon-color`, `--mp-hover-actions-icon-hover-bg`

#### `skins/stratus/styles/_dark.less`
- Add dark-mode values for the new `--mp-hover-*` custom properties

### 4. JavaScript Changes

#### `plugins/stratus_helper/stratus_helper.js`
New section: **Unified Hover Actions**

```
On rcmail 'init' (task === 'mail'):
  1. Listen for 'listupdate' / Roundcube list events
  2. For each tr.message in #messagelist (standard mode):
     - If no .mp-hover-actions child exists, inject:
       <span class="mp-hover-actions">
         <a class="mp-hover-btn archive" title="Archive">...</a>
         <a class="mp-hover-btn delete" title="Delete">...</a>
         <a class="mp-hover-btn flag" title="Flag">...</a>
       </span>
     - Wire click handlers to rcmail.command('plugin.archive'), rcmail.command('delete'), toggle flag
  3. MutationObserver on #messagelist tbody to catch dynamically added rows
```

New section: **Smart Bar Controller**

```
On rcmail 'init' (task === 'mail'):
  1. Extract buttons from hidden plugin containers (#convtoolbar, #listcontrols)
  2. Hide the archive button (archive is now hover-only)
  3. Re-parent conversation toggle into #messagelistmenu popup as a toggle switch row
     - Create a new fieldset/section in the popup: "Conversations" with ON/OFF
     - Wire toggle click to existing plugin.conv.toggle command
     - Sync visual state (active/inactive) with conv_state.mode
  4. Re-parent any other plugin buttons into .mp-smart-bar-default
  5. Initialize sort trigger: read current sort from rcmail.env and set label text
     - Sort column label map: {date: 'Date', from: 'From', to: 'To', subject: 'Subject', size: 'Size', ...}
     - On phone (html.layout-phone): hide text label, show icon-only
  6. Listen for sort changes to update the label dynamically
  7. Wire sort trigger click → open messagelistmenu popup
```

#### `plugins/conversation_mode/conversation_mode.js`
- Remove the `conv-hover-actions` injection code (lines ~910-935) — this is now handled by `stratus_helper.js`'s unified system
- The conversation mode continues to build its rows; the hover actions are injected externally
- OR: conversation mode detects that `mp-hover-actions` already exist and skips its own injection

**Backward compatibility approach:** Rather than removing conv-hover-actions entirely (which would break non-stratus skins), add a check: if `.mp-hover-actions` are being injected by stratus_helper, set a flag `window._stratus_hover_actions = true` and have conversation_mode.js skip its own injection when this flag is set.

### 5. Plugin Container Strategy

Roundcube plugins inject buttons via `<roundcube:container>` at render time. We can't eliminate these containers — they must exist in the HTML for PHP to find them. Strategy:

```html
<!-- Hidden injection points — plugins deposit buttons here at render time -->
<div id="mp-plugin-slots" style="display:none">
  <roundcube:container name="listcontrols" id="listcontrols" />
  <roundcube:container name="toolbar" id="convtoolbar" />
</div>
```

On `DOMContentLoaded`, `stratus_helper.js` scans these hidden containers and moves the injected buttons into their visible positions within `.mp-smart-bar-default`.

### 6. Options Popup Consolidation

The existing `messagelistmenu` (Roundcube's `listoptions` popup) already has sort column, sort order, and list mode sections. We add two new sections:

**Conversation toggle section:**
- JS creates a new `<fieldset>` in `#messagelistmenu` containing a labeled toggle switch: "Conversations [ON/OFF]"
- Toggle fires `rcmail.command('plugin.conv.toggle')` and updates visual state
- Always visible (not mode-dependent)

**Thread actions section:**
- JS appends a new `<fieldset>` to `#messagelistmenu` containing three links: Expand Unread, Expand All, Collapse All
- Wire to existing `rcmail.command('expandunread')`, `rcmail.command('expandall')`, `rcmail.command('collapseall')`
- Show this fieldset only when list mode is "Threads" (toggle via JS when mode changes)

This eliminates both the standalone `threadselect-menu` dropdown AND the `conv-toggle` button from the toolbar. One popup for all list settings.

## Files Changed

### Created
| File | Purpose |
|---|---|
| `.github/feature-specs/mail-ux-smart-context-bar.md` | This spec |

### Modified — Templates
| File | Change |
|---|---|
| `skins/stratus/templates/mail.html` | Remove `#messagelist-header` div. Add hidden plugin container slots. Preserve mobile nav buttons (relocated). |
| `skins/stratus/templates/includes/pagenav.html` | Evolve mass-action bar into smart bar. Add `.mp-smart-bar-default` section with sort trigger, toggle slot, refresh. Add hidden thread actions to listmenu. |

### Modified — Styles
| File | Change |
|---|---|
| `skins/stratus/styles/widgets/common.less` | Rename/extend `.mp-mass-action-bar` → `.mp-smart-bar`. Add default-state layout, sort trigger, state transition rules. Add dark mode block. |
| `skins/stratus/styles/widgets/lists.less` | Add `.mp-hover-actions` styles (frosted glass pill, icon buttons, fade-in). Override elastic's flag-only hover. Add dark mode block. |
| `skins/stratus/styles/_runtime.less` | Add `--mp-hover-actions-*` CSS custom properties for plugin CSS bridge. |
| `skins/stratus/styles/_dark.less` | Add dark values for `--mp-hover-actions-*` tokens. |
| `skins/stratus/styles/_variables.less` | Add any new spacing/color vars if needed (e.g. `@mp-hover-actions-radius`, `@mp-sort-trigger-*`). |

### Modified — JavaScript
| File | Change |
|---|---|
| `plugins/stratus_helper/stratus_helper.js` | Add unified hover actions system (inject archive/delete/flag on all message rows). Add smart bar controller (re-parent plugin buttons, sort trigger wiring). |
| `plugins/conversation_mode/conversation_mode.js` | Guard `conv-hover-actions` injection behind `window._stratus_hover_actions` flag. Skip injection when stratus hover system is active. |

### Modified — Plugin CSS
| File | Change |
|---|---|
| `plugins/conversation_mode/skins/default/conversation_mode.css` | Keep `conv-hover-actions` styles for non-stratus skins. |
| `plugins/conversation_mode/skins/elastic/conversation_mode.css` | Keep `conv-hover-actions` styles as fallback. Add `.mp-hover-actions` awareness rules. |

## Dark Mode Considerations

All new visual elements need light + dark variants:

| Element | Light | Dark |
|---|---|---|
| **Smart bar background** | `@color-layout-header-background` (#f8f9fd) | `@color-dark-header-bg` |
| **Smart bar border** | `@color-layout-border` | `@color-dark-border` |
| **Sort trigger text** | `@color-font` | `@color-dark-font` |
| **Sort trigger hover** | `fadeout(@color-main, 92%)` | `fadeout(@color-dark-main, 85%)` |
| **Hover actions bg** | `@mp-glass-bg-strong` (white 85% + blur) | Dark glass (dark surface 90% + blur) — existing pattern in `lists.less` dark section |
| **Hover action icons** | `@color-font-secondary` | `@color-dark-font-secondary` |
| **Hover action icon:hover** | `@color-main` on `fadeout(@color-main, 90%)` bg | `@color-dark-main` on `fadeout(@color-dark-main, 85%)` bg |
| **State transition** | Same opacity/transform animation | Same — no color-dependent animation |

Dark mode rules are co-located in each respective file's `html.dark-mode` block per the established pattern.

## Validation Criteria

### Smart Bar — Default State
- [ ] `#messagelist-header` is no longer rendered in the DOM
- [ ] Single `.mp-smart-bar` strip visible between searchbar and message list
- [ ] Mobile nav buttons (`.task-menu-button`, `.back-sidebar-button`) are first children of `.mp-smart-bar`
- [ ] Mobile nav buttons are `display:none` on desktop, visible on `html.layout-phone`
- [ ] Checkbox trigger works: 3-state cycle (unchecked → multiselect → select all → deselect)
- [ ] Sort indicator shows current sort column as **text label** on desktop (`Date ▾`)
- [ ] Sort indicator shows **icon-only** on phone layout
- [ ] Clicking sort indicator opens `messagelistmenu` popup with sort/order/list-mode/conversations/threads options
- [ ] Refresh button triggers `checkmail` command
- [ ] Pagination shows message count and prev/next arrows work

### Smart Bar — Selection State
- [ ] Selecting ≥1 message transitions bar to selection state (action buttons visible)
- [ ] All action buttons work: delete, archive, move, mark read/unread, flag, more
- [ ] Selected count chip shows correct count and updates live
- [ ] Deselecting all messages transitions bar back to default state
- [ ] Transition animation is smooth (~200ms, no layout shift)

### Options Popup
- [ ] Conversation toggle appears in `messagelistmenu` popup as an ON/OFF switch
- [ ] Conversation toggle fires `plugin.conv.toggle` and syncs visual state
- [ ] When in threads mode, `messagelistmenu` popup shows "Thread actions" section
- [ ] Expand Unread, Expand All, Collapse All commands work from the popup
- [ ] Thread actions section is hidden when not in threads mode

### Unified Hover Actions
- [ ] Standard mode: hovering a message row shows archive, delete, flag buttons
- [ ] Conversation mode: hovering a conversation row shows archive, delete, flag buttons
- [ ] Hover actions use frosted glass pill styling consistent with existing `.listing-hover-menu`
- [ ] Archive action works in both modes (routes to correct command)
- [ ] Delete action works in both modes
- [ ] Flag toggle works in both modes (solid ↔ outline icon)
- [ ] Hover actions don't appear on touch devices (or appear on long-press only)
- [ ] Hover actions fade in smoothly (100ms opacity transition)

### Dark Mode
- [ ] Smart bar renders correctly in dark mode (background, borders, text)
- [ ] Sort trigger is legible in dark mode
- [ ] Hover actions use dark frosted glass variant
- [ ] All action button icons are visible in dark mode
- [ ] State transition looks clean in dark mode

### Regression Safety
- [ ] LESS compiles successfully
- [ ] No console errors on page load
- [ ] Existing keyboard shortcuts still work (j/k navigation, Enter to open, Delete key)
- [ ] Mobile menu/back buttons (`.task-menu-button`, `.back-sidebar-button`) work on phone layout from their new position in `.mp-smart-bar`
- [ ] Plugin buttons (archive hidden, conversation toggle in popup) still render and function
- [ ] Searchbar position and behavior unchanged
- [ ] Mass-action "More" menu still opens with extended actions
- [ ] Pagination updates correctly when navigating pages

## Risks / Open Questions

### Risks

1. **`<roundcube:container>` timing** — Plugin buttons are injected at PHP render time. If the hidden container approach doesn't work (e.g., Roundcube PHP checks element visibility or parent context), buttons may not render. **Mitigation:** Test with archive and conversation_mode plugins; fall back to keeping containers visible but positioned inside the smart bar if needed.

2. **Elastic JS expectations** — Elastic's `ui.js` may expect `#messagelist-header` to exist for certain operations (responsive layout switching, `list_handler()`, etc.). Removing it could break mobile layout toggling. **Mitigation:** Keep a minimal empty `#messagelist-header` in DOM if needed, with `display:none`, or patch the 2-3 Elastic JS references.

3. **Sort indicator label** — Roundcube doesn't expose the current sort column as a simple readable string in JS env. We may need to derive it from `rcmail.env.sort_col` and maintain a label mapping. **Mitigation:** Build a simple `{date: 'Date', from: 'From', ...}` lookup in stratus_helper.js.

4. **Conversation mode hover action deduplication** — If both stratus_helper and conversation_mode try to inject hover actions, rows could get double actions. **Mitigation:** The `window._stratus_hover_actions` flag approach, plus CSS `display:none` on `.conv-hover-actions` when `.mp-hover-actions` exists.

5. **Non-stratus skins** — The conversation_mode plugin must remain skin-agnostic. Changes must not break it when used with elastic or other skins. **Mitigation:** All stratus-specific behavior is gated behind the flag or stratus_helper.js; plugin CSS retains its own hover actions as fallback.

### Resolved Decisions

1. **Sort indicator display:** Text label on desktop (`Date ▾`), icon-only on phone. ✅ DECIDED
2. **Conversation toggle placement:** Moves into the `messagelistmenu` options popup as an ON/OFF toggle switch. Not in the bar. ✅ DECIDED
3. **Refresh button:** Stays in the bar. ✅ DECIDED
4. **4th hover action (mark read/unread):** No. Hover actions are archive, delete, flag only. ✅ DECIDED
5. **Mobile nav buttons:** Move into `.mp-smart-bar` as the very first children, keeping the exact same elastic CSS class names so existing responsive rules continue to work. On desktop `display:none`, on phone appear at far left pushing checkbox right. No behavior change — DOM relocation only. ✅ DECIDED
