# Stratus Skin

**Stratus** is a custom skin for [Roundcube Webmail](https://roundcube.net/) that extends the built-in `elastic` skin with an "Atmospheric Modern" design language — layered depth, fluid motion, and a deep indigo color palette with full dark mode support.

## Requirements

- Roundcube 1.6+
- PHP 7.4+
- `stratus_helper` plugin (required companion — handles runtime theming and Smart Bar)

## Features

### Theming & Color Schemes

- **Runtime color scheme switching** — change palette without a page reload via Settings → Stratus


### Dark Mode

- One-click toggle — applies `html.dark-mode` class, no page reload
- Every component has colocated dark-mode overrides
- Deep navy backgrounds with purple accent instead of indigo
- HTML email bodies rendered on dark surfaces; styled newsletters (`.stratus-styled`) preserve their own white background
- Animated scrollbars, glow effects on badges and flags in dark mode

### Smart Bar

The Smart Bar is the adaptive context bar below the search input in the message list.

**Default state** (no selection):
- Sort button — pill-shaped with label and direction arrow, opens a sort dialog
- Pagination — Previous/Next icon buttons with "N of M" message count
- Refresh button

**Selection state** (one or more messages checked):
- Multiselect checkbox trigger with indeterminate and checked states
- "N selected" pill badge in primary color
- Bulk action buttons: Archive, Delete, Flag (toggle), Mark read/unread, Move to folder
- All buttons disabled-safe (35% opacity when unavailable)

### Message List

- Unread rows: bold subject + 3px indigo left border
- Flagged rows: amber flag icon with glow in dark mode
- Selected rows: primary-alpha wash + highlighted left border
- **Hover quick actions** — frosted glass pill overlay (right edge) with Archive, Delete, Flag buttons; hidden in multiselect mode
- Clean card-style rows with subtle 1px separators
- Empty state: centered icon + friendly message; search empty state uses magnifying glass icon
- Loading spinner: 40px circular in primary color


### Login Page

- Centered 480px card on a minimal background
- Light/dark logo variants (`#logo` / `#logo-dark`) auto-switch per mode
- Username field: primary-color border + focus ring
- Password field: underline-only style with eye toggle
- Full-width primary gradient submit button
- Fully responsive below 480px

### Typography & Font Preferences

- System-ui font stack by default (native fonts on each OS)
- Configurable font family and base font size via Settings → Stratus
- Font size change scales the entire interface via `<html>` rem override
- Size tokens: XS (0.7rem) through XL (1.35rem)
- Weights: Light 300 → Bold 700

### Undo Send

Requires the `undo_send` plugin.

- Gmail-style delayed send (default 5 seconds, configurable 0–30s)
- Toast with countdown: "Sending in X seconds…" plus Undo and Send Now actions
- No database or cron required — client-side only

### Folder List

- Pill-shaped selected item (rounded right, flat left — Gmail style)
- Selected folder: 12% primary-alpha background + accent text
- Unread count badges: capsule pills with glow shadow in dark mode

### Glassmorphism & Motion

- Frosted glass surfaces: `backdrop-filter: blur(12px)` with semi-transparent backgrounds
- Hover quick-action overlay uses frosted glass pill with shadow
- Fluid transitions: 100ms–400ms across buttons, rows, and overlays
- Reduced-motion respected via `@media (prefers-reduced-motion)`

### Scrollbars

- Ultra-thin (5px), capsule-shaped
- Semi-transparent, increases opacity on hover
- Dark mode: primary-dark color tint

## File Structure

```
skins/stratus/
├── meta.json                   skin descriptor (extends elastic)
├── composer.json               Packagist metadata
├── thumbnail.png               preview for skin selector
├── watermark.html              empty reading-pane branding
├── styles/
│   ├── styles.less             entry point (imports elastic first, then overrides)
│   ├── styles.min.css          compiled output (do not edit manually)
│   ├── _variables.less         color, spacing, shadow, typography tokens (~200+ vars)
│   ├── _typography.less        font stack and heading hierarchy
│   ├── _animations.less        transitions, keyframes, reduced-motion
│   ├── _layout.less            task menu, headers, panels
│   ├── _login.less             login page styles
│   ├── _dark.less              supplemental dark-mode rules
│   ├── _runtime.less           CSS custom property bridge (set at runtime by stratus_helper)
│   ├── _components.less        barrel import for widgets/
│   ├── _calendar.less          FullCalendar overrides
│   └── widgets/
│       ├── common.less         quota, scrollbars, smart bar, mass-action bar
│       ├── buttons.less        button variants, toolbar icons, FAB
│       ├── forms.less          inputs, checkboxes, file upload, selects
│       ├── lists.less          message list, folder list, badges
│       ├── menu.less           navigation tabs
│       ├── messages.less       message view, attachments, toasts
│       ├── dialogs.less        dialogs, overlay, popovers
│       ├── editor.less         TinyMCE dark mode and typography
│       └── jqueryui.less       jQuery UI overrides
├── js/
│   └── smart-bar/              Smart Bar JS modules (loaded by stratus_helper)
├── templates/
│   ├── mail.html               mail layout + conversation mode containers
│   ├── login.html              custom login page
│   └── includes/
│       └── layout.html         CSS injection point
└── images/
    ├── logo.svg
    ├── logo-dark.svg
    └── logo-small.svg
```

## Credits

- Stratus skin: Victor Faria
- Built on [Roundcube](https://roundcube.net/) and extends the Elastic skin by The Roundcube Team

## License

Creative Commons Attribution-ShareAlike 3.0 (CC BY-SA 3.0)

The contents of this directory are subject to the Creative Commons
Attribution-ShareAlike License. It is permitted to copy, distribute,
transmit, and adapt the work, provided credits to the original authors
are kept in this README.
See http://creativecommons.org/licenses/by-sa/3.0/ for details.
