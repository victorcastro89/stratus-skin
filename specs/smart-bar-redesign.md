# Feature Spec: Smart Bar Redesign — Unified Sort & Select Controls

**Status:** PROPOSED
**Supersedes:** `mail-ux-smart-context-bar.md` (default state layout only — selection state, hover actions unchanged)

## Summary

Redesign the smart bar's **default state** layout to clearly separate five control zones: select, sort, refresh, message count, and pagination. The sort zone uses a **merged dropdown** pattern — a single clickable area showing `↓ Sent date` that opens a popup containing sort column options + direction radio. Changes apply immediately on click (no Save button). The select zone splits the checkbox from the batch-select caret so each has a distinct click target.

Selection state (action buttons when messages are selected) remains unchanged.

## Layout

### Default State

```
┌──────────────────────────────────────────────────────────────────┐
│ ☐ ▾ │  ↓ Sent date  │  ⟳  │           Messages 1-3 of 3  ‹ › │
│     │                │     │                                    │
│ (A) │      (B)       │ (C) │              (D)                   │
└──────────────────────────────────────────────────────────────────┘
```

| Zone | Elements | Behavior |
|------|----------|----------|
| **(A) Select** | Checkbox + dropdown caret (▾) | Checkbox click → enter/exit multiselect mode. Caret click → opens batch select popup (all, none, current page, unread, flagged, invert). |
| **(B) Sort** | Direction arrow (↓ or ↑) + column label ("Sent date") | Entire zone is one clickable trigger. Opens sort popup with column options + direction radio. Arrow reflects current direction. Label reflects current sort column. |
| **(C) Refresh** | Refresh icon button | Calls `rcmail.command('checkmail')`. |
| **(D) Right** | Message count text + prev/next arrows | `messageCountDisplay` + `previouspage`/`nextpage` buttons. |

### Selection State (unchanged from current)

```
┌──────────────────────────────────────────────────────────────────┐
│ ☑ ▾ │  🗑  📁  📂  📩  🏳  ⋯  │              3 selected  ‹ › │
└──────────────────────────────────────────────────────────────────┘
```

Sort zone and refresh are hidden. Action buttons slide in. Chip count replaces message count.

### Mobile (phone layout)

```
┌──────────────────────────────────────────────────────────────────┐
│ ☰ ◀ │ ☐ ▾ │  ↓ Sent date  │  ⟳  │       Messages 1-3 of 3  ‹ ›│
└──────────────────────────────────────────────────────────────────┘
```

- Mobile nav buttons (`.task-menu-button`, `.back-sidebar-button`) appear as first children
- Sort label may truncate on narrow screens; arrow always visible
- Message count text can hide on very narrow widths; pagination arrows remain

---

## Sort Popup Design

When user clicks the sort zone (B), a popup opens anchored below the trigger:

```
┌──────────────────────┐
│  ✓ Sent date         │
│    Arrival           │
│    From              │
│    To                │
│    From/To           │
│    Subject           │
│    Size              │
│ ──────────────────── │
│    Ascending      ○  │
│    Descending     ●  │
└──────────────────────┘
```

- Current sort column shows a checkmark (✓)
- Current direction shows a filled radio (●)
- Clicking a column → applies sort immediately via `rcmail.set_list_options()`, closes popup
- Clicking a direction → applies immediately via `rcmail.set_list_options()`, closes popup
- Clicking outside the popup → closes without action

### Why `set_list_options()` and NOT `command('sort')`

`rcmail.command('sort', col)` has built-in toggle logic: if same column is clicked, it flips ASC/DESC. This is designed for column header clicks, not for a dropdown where column and direction are independent controls.

`rcmail.set_list_options(cols, sort_col, sort_order, threads, layout)` sets both explicitly without side effects:
- Column change: `rcmail.set_list_options([], newCol, rcmail.env.sort_order)`
- Direction change: `rcmail.set_list_options([], rcmail.env.sort_col, newOrder)`

This is the canonical Roundcube API for list options. It calls `set_list_sorting()` internally (updates env + DOM classes) then reloads the list via `list_mailbox()`.

---

## Select Zone Design

### Current Problem

The existing `#mp-mass-select-toggle` is a single `<a>` element with `data-popup="listselect-menu"`. Clicking anywhere on the checkbox area opens the popup AND enters multiselect. The user wants these separated.

### New Design

Split into two adjacent elements:

```html
<!-- Checkbox: click enters/exits multiselect mode -->
<span class="mp-mass-action-checkbox" id="mp-mass-select-checkbox"
      role="checkbox" aria-checked="false" tabindex="0"
      title="Select messages"></span>

<!-- Caret: click opens batch select popup -->
<a href="#select" id="mp-mass-select-caret" class="mp-mass-select-caret"
   data-popup="listselect-menu"
   title="Select options" aria-haspopup="true">
  <span class="mp-select-caret-icon" aria-hidden="true"></span>
</a>
```

The batch select popup uses Roundcube's native `listselect-menu` (defined in Elastic's `mail.html`). Items use native commands:

| Item | Roundcube Command |
|------|-------------------|
| All | `rcmail.command('select-all')` — sets `select_all_mode = true` |
| None | `rcmail.command('select-none')` |
| Current page | `rcmail.command('select-all', 'page')` |
| Unread | `rcmail.command('select-all', 'unread')` |
| Flagged | `rcmail.command('select-all', 'flagged')` |
| Invert | `rcmail.command('select-all', 'invert')` |

**Note:** The `listselect-menu` popup already exists in Stratus's `mail.html` (inherited from Elastic) with these exact commands. No new HTML needed for the popup content itself — only the trigger element changes.

**Note:** The `listselect-menu` popup includes a "Selection" toggle button (`#list-toggle-button`) that calls `UI.toggle_list_selection()`. This duplicates the checkbox's multiselect toggle and must be hidden via CSS: `#listselect-menu .selection { display: none; }`. Do not remove it from the template — Elastic's JS references it by ID.

---

## Technical Design

### 1. Template Changes — `pagenav.html`

```html
<!-- Labels for JS (add asc/desc for sort popup) -->
<roundcube:add_label name="asc" />
<roundcube:add_label name="desc" />
<!-- existing labels: markread, markunread, etc. stay -->

<roundcube:if condition="template:name == 'mail'" />
<div id="mail-list-navbar" class="pagenav menu footer small mp-smart-bar"
     role="toolbar" aria-label="...">

  <!-- Mobile nav (display:none on desktop) -->
  <a class="button icon task-menu-button" href="#menu">...</a>
  <a class="button icon back-sidebar-button folders" href="#sidebar">...</a>

  <div class="mp-smart-bar-left">
    <!-- (A) Select zone: checkbox + caret -->
    <span class="mp-mass-action-checkbox" id="mp-mass-select-checkbox"
          role="checkbox" aria-checked="false" tabindex="0"
          title="<roundcube:label name='select' />"></span>
    <a href="#select" id="mp-mass-select-caret" class="mp-mass-select-caret"
       data-popup="listselect-menu"
       title="<roundcube:label name='select' />">
      <span class="mp-select-caret-icon" aria-hidden="true"></span>
      <span class="inner"><roundcube:label name="select" /></span>
    </a>

    <!-- (B) Sort zone: direction arrow + label (default state only) -->
    <div class="mp-smart-bar-default" id="sort-bar-mail-list">
      <a href="#sort" id="mp-sort-trigger" class="mp-sort-trigger"
         aria-haspopup="true" aria-expanded="false"
         title="<roundcube:label name='listsorting' />">
        <span class="mp-sort-arrow" aria-hidden="true"></span>
        <span class="mp-sort-label"></span>
      </a>

      <!-- (C) Refresh -->
      <roundcube:button command="checkmail" type="link"
        class="mp-action-btn refresh"
        label="refresh" title="checkmail" innerclass="inner" />
    </div>

    <!-- Action buttons (selection state — unchanged) -->
    <div class="mp-mass-action-actions" aria-label="Message actions">
      <!-- ... existing action buttons stay exactly as-is ... -->
    </div>
  </div>

  <div class="mp-smart-bar-right">
    <span id="mp-selected-count" class="mp-mass-action-chip"
          aria-live="polite"></span>
    <roundcube:object name="messageCountDisplay"
      class="pagenav-text mp-mass-action-count"
      aria-live="polite" aria-relevant="text" />
    <roundcube:button command="previouspage" type="link"
      class="prevpage disabled" classAct="prevpage"
      title="previouspage" label="previous" innerclass="inner" />
    <roundcube:button command="nextpage" type="link"
      class="nextpage disabled" classAct="nextpage"
      title="nextpage" label="next" innerclass="inner" />
  </div>
</div>

<!-- Sort popup (anchored by JS to sort trigger) -->
<div id="mp-sort-menu" class="popupmenu" aria-label="Sort options">
  <ul class="menu listing" role="listbox">
    <li role="option"><a href="#sort" data-sort-col="date">
      <roundcube:label name="sentdate" /></a></li>
    <li role="option"><a href="#sort" data-sort-col="arrival">
      <roundcube:label name="arrival" /></a></li>
    <li role="option"><a href="#sort" data-sort-col="from">
      <roundcube:label name="from" /></a></li>
    <li role="option"><a href="#sort" data-sort-col="to">
      <roundcube:label name="to" /></a></li>
    <li role="option"><a href="#sort" data-sort-col="fromto">
      <roundcube:label name="fromto" /></a></li>
    <li role="option"><a href="#sort" data-sort-col="subject">
      <roundcube:label name="subject" /></a></li>
    <li role="option"><a href="#sort" data-sort-col="size">
      <roundcube:label name="size" /></a></li>
  </ul>
  <div class="mp-sort-direction" role="radiogroup"
       aria-label="Sort direction">
    <a href="#sort-dir" data-sort-order="ASC" class="mp-sort-dir-option"
       role="radio" aria-checked="false">
      <roundcube:label name="asc" /></a>
    <a href="#sort-dir" data-sort-order="DESC" class="mp-sort-dir-option"
       role="radio" aria-checked="true">
      <roundcube:label name="desc" /></a>
  </div>
</div>
<roundcube:else />
<!-- Non-mail templates: standard elastic paging (unchanged) -->
<!-- ... -->
<roundcube:endif />
```

### 2. JS Changes — `sort-controller.js`

The sort controller gains popup management responsibility.

```
Current behavior:
  - Click sort trigger → rcmail.command('menu-open', 'messagelistmenu', ...)
  - Opens the full Elastic listoptions dialog (with Save button)

New behavior:
  - Click sort trigger → rcmail.command('menu-open', 'mp-sort-menu', ...)
  - Opens lightweight popup defined in pagenav.html
  - Click sort column → rcmail.set_list_options([], col, currentOrder)
  - Click direction → rcmail.set_list_options([], currentCol, newOrder)
  - Both close popup after applying
```

**Key changes:**

| Method | Current | New |
|--------|---------|-----|
| `_onTriggerClick()` | Opens `messagelistmenu` dialog | Opens `mp-sort-menu` popup via `rcmail.command('menu-open')` |
| `updateDisplay()` | Updates label + arrow class | Same + syncs popup checkmark/radio state |
| NEW: `_onSortColumnClick(e)` | — | Reads `data-sort-col`, calls `set_list_options`, closes popup |
| NEW: `_onSortDirectionClick(e)` | — | Reads `data-sort-order`, calls `set_list_options`, closes popup |
| NEW: `_syncPopupState()` | — | Adds `.active` to current column, sets `aria-checked` on direction |

**Roundcube popup system integration:**

The `mp-sort-menu` div uses class `popupmenu`, which Roundcube's popup system handles — positioning, outside-click-to-close, escape key, etc. No custom popup logic needed. Popup is opened via `rcmail.command('menu-open', 'mp-sort-menu', event.target, event)`. Note: the 3rd argument is unused by the `menu-open` handler — positioning is derived from `event.target` inside `show_menu()`.

After `set_list_options()` is called, Roundcube fires `listupdate` event. The sort controller already listens for this to call `updateDisplay()`, so the bar label/arrow updates automatically. If the user clicks the already-active column or direction, `set_list_options()` is a no-op (no reload, no `listupdate`). The click handler must still close the popup via `hide_menu()` regardless.

**Important:** Roundcube's `show_menu()` relocates the popup element to `document.body` on first open. All CSS for `#mp-sort-menu` must use standalone selectors (ID or class) — never descendant selectors from `.mp-smart-bar` or parent containers, as they won't match after relocation.

### 3. JS Changes — `multi-select-controller.js` & Orchestrator

**Checkbox/caret split:**

Currently the orchestrator binds a click handler on `#mp-mass-select-toggle` that calls `multiSelect.enter()`. The same element has `data-popup="listselect-menu"` so RC also opens the popup.

New wiring:
- `#mp-mass-select-checkbox` click → `multiSelect.enter()` or toggle (no popup)
- `#mp-mass-select-caret` has `data-popup="listselect-menu"` → RC opens popup natively
- Popup items trigger RC's native `select-all`/`select-none` commands (unchanged)

The multi-select-controller itself doesn't change. Only the orchestrator's event binding changes targets.

### 4. Roundcube API Usage Map

Every feature maps to a native Roundcube method — no custom AJAX or DOM state hacking:

| Feature | Roundcube API | Notes |
|---------|---------------|-------|
| **Sort by column** | `rcmail.set_list_options([], col, order)` | Canonical API. Sets env, calls `set_list_sorting()`, reloads list |
| **Sort direction** | `rcmail.set_list_options([], col, order)` | Same API, change only the order param |
| **Read sort state** | `rcmail.env.sort_col`, `rcmail.env.sort_order` | Source of truth, updated by RC after list reload |
| **Update display after sort** | `rcmail.addEventListener('listupdate', fn)` | Already used by sort-controller |
| **Select all/none/page/unread/flagged/invert** | `rcmail.command('select-all', prop)`, `rcmail.command('select-none')` | Native commands, used by `listselect-menu` buttons |
| **Enter multiselect** | Monkey-patched `select_row()` in multi-select-controller | Existing pattern, unchanged |
| **Refresh** | `rcmail.command('checkmail')` | Native command via `<roundcube:button>` |
| **Pagination** | `rcmail.command('previouspage')`, `rcmail.command('nextpage')` | Native commands via `<roundcube:button>` |
| **Open popup** | `rcmail.command('menu-open', id, event.target, event)` | Native popup system handles positioning via `event.target` + dismissal |
| **Close popup** | `rcmail.hide_menu(id, event)` | Native popup close |
| **Localized labels** | `rcmail.get_label(key)` | For sort column names in the bar |

### 5. CSS/LESS Changes

**Files affected:**
- `skins/stratus/styles/widgets/common.less` — sort popup styles, select caret, updated sort trigger
- `skins/stratus/styles/widgets/lists.less` — minor adjustments if sort popup overlaps list

**New CSS classes:**

| Class | Purpose |
|-------|---------|
| `.mp-mass-select-caret` | Small dropdown arrow next to checkbox. ~20px wide, subtle border-left separator. |
| `.mp-select-caret-icon` | The ▾ icon inside the caret button |
| `#mp-sort-menu` | Popup container. Standard `.popupmenu` styling from Elastic, with Stratus overrides. Use standalone selectors only — `show_menu()` relocates it to `document.body` |
| `#mp-sort-menu .active` | Checkmark on current sort column (via `::before` pseudo-element or icon class) |
| `.mp-sort-direction` | Section within popup for ASC/DESC radio-style options. Separated by a top border |
| `.mp-sort-dir-option` | Individual direction option. Highlight when `aria-checked="true"` |
| `.mp-sort-trigger .mp-sort-arrow.mp-sort-asc` | ↑ arrow for ascending |
| `.mp-sort-trigger .mp-sort-arrow.mp-sort-desc` | ↓ arrow for descending |

**State classes (unchanged):**
- `.mp-has-selection` on `.mp-smart-bar` — toggles between default and selection states
- `.mp-multiselect-mode` on `.mp-smart-bar` — visual indicator of multiselect active

---

## Interaction Flows

### Flow 1: Sort by a different column

1. User clicks sort zone `[↓ Sent date]`
2. `sort-controller._onTriggerClick()` fires → `rcmail.command('menu-open', 'mp-sort-menu', e.target, e)`
3. Popup opens. `_syncPopupState()` adds `.active` to "Sent date", marks "Descending" as checked
4. User clicks "From"
5. `_onSortColumnClick()` reads `data-sort-col="from"` → calls `rcmail.set_list_options([], 'from', 'DESC')`
6. Popup closes via `rcmail.hide_menu('mp-sort-menu')`
7. Roundcube reloads list sorted by From DESC
8. `listupdate` event fires → `updateDisplay()` sets label to "From", arrow to ↓

### Flow 2: Change sort direction only

1. User clicks sort zone
2. Popup opens. "Sent date" is active, "Descending" is checked
3. User clicks "Ascending"
4. `_onSortDirectionClick()` reads `data-sort-order="ASC"` → calls `rcmail.set_list_options([], 'date', 'ASC')`
5. Popup closes
6. List reloads sorted by Date ASC
7. Bar updates: `[↑ Sent date]`

### Flow 3: Enter multiselect and batch select

1. User clicks checkbox (zone A, the checkbox element)
2. Orchestrator calls `multiSelect.enter()` → bar adds `.mp-multiselect-mode` class
3. Checkbox shows indeterminate state (dash)
4. User clicks caret (▾) next to checkbox
5. `listselect-menu` popup opens (native RC popup)
6. User clicks "Unread"
7. RC native handler runs `rcmail.command('select-all', 'unread')`
8. All unread messages get selected → selection state kicks in → action buttons appear

### Flow 4: Refresh

1. User clicks refresh icon (zone C)
2. `<roundcube:button command="checkmail">` fires `rcmail.command('checkmail')`
3. Roundcube fetches new messages, updates list
4. `listupdate` event fires → sort display refreshes, selection state resets

---

## Edge Cases & Pitfalls

### `arrival` vs `date` sort column
Roundcube treats `arrival` (IMAP internal date) and `date` (message Date header) as separate sort columns. The sort-controller already has a `_sortColumnLabels` map that handles this. The popup must list both as distinct options. `rcmail.env.sort_col` can be either `'date'` or `'arrival'`, and our popup must highlight the correct one.

### `fromto` column
The `fromto` sort column shows "From" in received folders and "To" in sent folders. It's a valid sort option. Include it in the popup. Roundcube handles the display logic server-side.

### Sort popup state sync
After `set_list_options()` reloads the list, `rcmail.env.sort_col` and `rcmail.env.sort_order` are updated by the server response. The popup's `.active` state and radio state must be re-synced on every open (in `_syncPopupState()`), NOT cached from the last click.

### `listselect-menu` popup ownership
The `listselect-menu` popup is defined in Elastic's `mail.html`, not in `pagenav.html`. Stratus inherits it because `skins/stratus/templates/mail.html` extends Elastic's template. The caret trigger references it via `data-popup="listselect-menu"`. If Stratus overrides `mail.html` and removes the popup definition, the caret will fail silently. Ensure the popup definition is preserved.

### `set_list_options()` first parameter
The first parameter `cols` controls visible columns. Pass `[]` to leave columns unchanged — only sort/order/thread params matter.

### `cc` sort column not in popup
The popup omits `cc` (present in the full `listoptions-menu` and in `sort-controller._sortColumnLabels`). If a user has `cc` sort active via server config or the full dialog, the popup won't highlight any column. The bar label still displays correctly because `_sortColumnLabels` handles `cc` — only the popup checkmark is absent. This is an acceptable trade-off to keep the popup clean.

### Popup z-index
The sort popup appears between the smart bar and the message list. Roundcube's popup system sets z-index automatically for `.popupmenu` elements. No manual z-index needed unless Stratus has custom stacking overrides.

### Keyboard accessibility
- Sort trigger: focusable via tab, Enter/Space opens popup
- Popup items: arrow keys navigate, Enter selects, Escape closes
- Roundcube's popup system handles Escape-to-close natively
- Checkbox: focusable via tab, Enter/Space toggles multiselect
- Caret: focusable via tab, Enter/Space opens popup

---

## Files Changed

### Modified

| File | Change |
|------|--------|
| `skins/stratus/templates/includes/pagenav.html` | Split checkbox/caret, replace sort trigger click target, add `#mp-sort-menu` popup HTML, add `asc`/`desc` labels |
| `skins/stratus/js/smart-bar/sort-controller.js` | Replace `messagelistmenu` dialog with `mp-sort-menu` popup. Add column/direction click handlers using `set_list_options()`. Add `_syncPopupState()` |
| `skins/stratus/js/smart-bar.js` | Update checkbox event binding: `#mp-mass-select-checkbox` for multiselect toggle, leave caret to RC popup system |
| `skins/stratus/styles/widgets/common.less` | Add `.mp-mass-select-caret` styles, `#mp-sort-menu` popup styles, `.mp-sort-direction` section, hide `#listselect-menu .selection` |

### Not Changed

| File | Reason |
|------|--------|
| `selection-manager.js` | Selection tracking logic unchanged |
| `multi-select-controller.js` | Multiselect enter/exit logic unchanged, only the trigger element ID changes in orchestrator |
| `mass-action-bar.js` | Selection state UI unchanged |
| `action-dispatcher.js` | Action routing unchanged |
| `stratus_helper.php` | Script loading order unchanged, no new files |
| `stratus_helper.js` | Hover actions & other helper logic unchanged |

---

## Validation Criteria

### Sort
- [ ] Sort trigger displays current column name and direction arrow
- [ ] Clicking sort trigger opens popup anchored below it
- [ ] Popup shows checkmark on current sort column
- [ ] Popup shows filled radio on current direction
- [ ] Clicking a column sorts the list by that column (keeps direction), closes popup
- [ ] Clicking a direction changes order (keeps column), closes popup
- [ ] Bar label and arrow update after sort change
- [ ] `arrival` and `date` are listed as separate options and highlighted correctly
- [ ] Outside click closes popup without action
- [ ] Escape key closes popup

### Select
- [ ] Clicking checkbox enters multiselect mode (no popup opens)
- [ ] Clicking caret (▾) opens batch select popup
- [ ] All popup items work: all, none, current page, unread, flagged, invert
- [ ] Selecting items via popup enters multiselect mode and shows selection state
- [ ] Checkbox shows correct visual state: unchecked, indeterminate (some selected), checked (all selected)

### Refresh
- [ ] Refresh icon triggers `checkmail` command
- [ ] List updates with new messages

### Pagination
- [ ] Message count displays correctly
- [ ] Prev/next arrows work and disable at boundaries

### Responsive
- [ ] Mobile nav buttons appear on phone layout, hidden on desktop
- [ ] Sort label readable on tablet, may truncate on phone
- [ ] All controls functional on touch devices

### Regression
- [ ] Selection state (action buttons) works exactly as before
- [ ] LESS compiles without errors
- [ ] No console errors on page load
- [ ] Hover actions on message rows unchanged
- [ ] Conversation mode integration unchanged
