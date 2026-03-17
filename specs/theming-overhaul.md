# Theming Overhaul — Color Schemes, Fonts & Dark Mode

**Status:** Draft
**Date:** 2026-03-16
**Scope:** Color scheme system, font preferences, dark mode — no layout/density UI

---

## Table of Contents

1. [Problem Statement](#1-problem-statement)
2. [Design Principles](#2-design-principles)
3. [Full Palette Token System](#3-full-palette-token-system)
4. [Phase 1 — Immediate: Fix Broken Runtime Switching & Text Colors](#4-phase-1--immediate)
5. [Phase 2 — Short-term: Full Palette Schemes & Sidebar Theming](#5-phase-2--short-term)
6. [Phase 3 — Longer-term: Font Size Preference & Auto-Derivation Engine](#6-phase-3--longer-term)
7. [Contrast Validation](#7-contrast-validation)
8. [Migration Guide](#8-migration-guide)
9. [Files Affected](#9-files-affected)

---

## 1. Problem Statement

### 1.1 Text colors tied to primary accent

`_runtime.less` sets `color: var(--stratus-primary)` on all `<a>` links, toolbar hover text, secondary button hover text, and listing marks. When a user picks Amber (`#f57f17`), links become orange-yellow on white — **failing WCAG AA** (contrast ratio ~2.5:1, requirement is 4.5:1). When they pick Slate (`#455a64`), links lose visual affordance entirely.

Professional webmail clients (Gmail, Outlook, Fastmail) never use the user's accent color for body text. The accent colors backgrounds, borders, badges, and icons — not readable text.

### 1.2 Compile-time vs runtime split (the "split-brain" bug)

Two independent color systems don't align:

- **Runtime** (`_runtime.less`): ~15 rules use `var(--stratus-primary)` — these respond to scheme switching
- **Compile-time** (widget `.less` files): ~30 rules use `@color-main` — these are baked to `#5c6bc0` at build time and **never change**

When a user picks "Ocean Blue", buttons and badges turn blue, but tab navigation, sort arrows, folder highlights, unread borders, and smart bar actions stay Indigo. The same split exists in dark mode with `@color-dark-main` vs `var(--stratus-primary-dark)`.

**Affected compile-time properties (not responding to scheme switching):**

| File | Count | Elements |
|------|-------|----------|
| `widgets/common.less` | 10 | Smart bar actions, sort arrows, border accents, toggle |
| `widgets/lists.less` | 4 | Unread borders, hover action text, selected folder |
| `widgets/menu.less` | 3 | Tab active/hover text + border |
| `widgets/buttons.less` | 1 | Secondary button hover text |
| `widgets/forms.less` | 5 | Input focus border, checkbox bg/border |
| `widgets/editor.less` | 2 | TinyMCE focus border |
| `widgets/dialogs.less` | 1 | Popover menu hover text |
| `_layout.less` | 5 | Toolbar hover text, gradient backgrounds |

### 1.3 Two-variable system is too narrow

Each scheme defines only `primary` + `primary_dark`. This single color is used for text, backgrounds, borders, badges, shadows, and sidebar — roles with fundamentally different contrast requirements. There is no way to make Amber work for both a button background AND link text.

### 1.4 Hardcoded sidebar

The taskmenu (`#1a1f36` navy) never changes regardless of scheme. It visually clashes with warm schemes (Rose, Amber) and offers no personalization of the most prominent UI surface.

### 1.5 No font size control

Font family is switchable but font size and line-height are fixed. Users with different screen sizes and visual preferences have no control.

---

## 2. Design Principles

1. **Accent colors never appear as readable text.** Text uses dedicated `text_accent` tokens validated for WCAG AA (4.5:1 on white, 4.5:1 on dark surface). The primary accent is for backgrounds, borders, badges, icons, and shadows only.

2. **Single source of truth.** All color consumption goes through CSS custom properties (`var(--stratus-*)`). No LESS variable (`@color-main`) should appear in property values in widget/layout files — only in `_variables.less` definitions and as fallback defaults in `:root`.

3. **Full palette, preset-only.** Each scheme is a complete, hand-validated token set. No color picker. Admins can add schemes in config with just a primary color and the PHP auto-derivation engine fills in the rest.

4. **WCAG AA baseline.** All text-on-surface combinations must meet 4.5:1 for normal text, 3:1 for large text and UI components.

5. **Dark mode is a first-class axis.** Every token has a light and dark variant. The same migration that fixes light mode fixes dark mode.6
6. **Color codes,fonts styles, should never be hardcoded, always use variables 
---

## 3. Full Palette Token System

### 3.1 Token definitions

Each scheme produces the following CSS custom properties:

```
/* ── Accent (backgrounds, borders, badges, icons, shadows) ── */
--stratus-primary              /* Main accent — buttons, badges, checkbox bg */
--stratus-primary-rgb          /* RGB triplet for rgba() usage */

/* ── Text accent (links, interactive labels — WCAG AA validated) ── */
--stratus-text-accent          /* Accessible text color on white/light surface */
--stratus-text-accent-dark     /* Accessible text color on dark surface */

/* ── Sidebar / Taskmenu ── */
--stratus-sidebar-bg           /* Sidebar solid background (darkened primary tint) */
--stratus-sidebar-gradient     /* Sidebar gradient (top → bottom) */
--stratus-sidebar-text         /* Sidebar icon/label default color */
--stratus-sidebar-text-hover   /* Sidebar icon/label hover */
--stratus-sidebar-text-active  /* Sidebar selected item text */
--stratus-sidebar-active-bg    /* Sidebar selected item background */

/* ── Surface tints ── */
--stratus-surface-tint         /* Subtle primary tint for headers/panels (2-5% opacity) */
--stratus-hover-bg             /* Hover state background (primary @ 6-8%) */
--stratus-selected-bg          /* Selected state background (primary @ 12-15%) */
--stratus-focus-ring           /* Focus ring color (primary @ 25-30%) */

/* ── Dark mode equivalents (injected when html.dark-mode) ── */
--stratus-primary-dark
--stratus-primary-dark-rgb
/* (all other tokens have dark equivalents auto-derived or explicit) */
```

### 3.2 Scheme config structure (expanded)

```php
// config.inc.php.dist — full palette per scheme
$config['stratus_color_schemes'] = [
    'indigo' => [
        'label'        => 'Indigo',

        // ── Core (required — auto-derivation uses these as seed) ──
        'primary'      => '#5c6bc0',
        'primary_dark' => '#7986cb',

        // ── Text accent (WCAG AA validated) ──
        'text_accent'      => '#3949ab',   // 4.5:1+ on #fff
        'text_accent_dark' => '#9fa8da',   // 4.5:1+ on #1a1f36

        // ── Sidebar ──
        'sidebar_bg'          => '#1a1f36',
        'sidebar_gradient'    => 'linear-gradient(180deg, #1e2444 0%, #151a2e 100%)',
        'sidebar_text'        => '#8892b8',
        'sidebar_text_hover'  => '#ffffff',
        'sidebar_text_active' => '#ffffff',
        'sidebar_active_bg'   => 'rgba(92, 107, 192, 0.20)',

        // ── Surfaces ──
        'surface_tint'  => 'rgba(92, 107, 192, 0.03)',
        'hover_bg'      => 'rgba(92, 107, 192, 0.06)',
        'selected_bg'   => 'rgba(92, 107, 192, 0.13)',
        'focus_ring'    => 'rgba(92, 107, 192, 0.25)',
    ],
    'ocean' => [
        'label'        => 'Ocean Blue',
        'primary'      => '#0288d1',
        'primary_dark' => '#4fc3f7',
        'text_accent'      => '#01579b',   // 7.2:1 on #fff
        'text_accent_dark' => '#81d4fa',   // 5.1:1 on #1a1f36
        'sidebar_bg'          => '#0d1b2a',
        'sidebar_gradient'    => 'linear-gradient(180deg, #112240 0%, #091728 100%)',
        'sidebar_text'        => '#6b9cc2',
        'sidebar_text_hover'  => '#ffffff',
        'sidebar_text_active' => '#ffffff',
        'sidebar_active_bg'   => 'rgba(2, 136, 209, 0.22)',
        'surface_tint'  => 'rgba(2, 136, 209, 0.03)',
        'hover_bg'      => 'rgba(2, 136, 209, 0.06)',
        'selected_bg'   => 'rgba(2, 136, 209, 0.13)',
        'focus_ring'    => 'rgba(2, 136, 209, 0.25)',
    ],
    // ... remaining schemes follow same structure
];
```

### 3.3 Minimal admin scheme (auto-derivation)

An admin can add a scheme with just the required fields:

```php
'corporate' => [
    'label'   => 'Corporate Blue',
    'primary' => '#1565c0',
],
```

`stratus_helper.php` auto-derives all missing tokens using the derivation engine (Phase 3). `primary_dark` is lightened 20%. `text_accent` is darkened until WCAG AA passes. Sidebar is a desaturated dark tint. Surface alphas follow standard ratios.

---

## 4. Phase 1 — Immediate

**Goal:** Fix the two critical bugs — broken runtime switching and unsafe text colors.

### 4.1 Remove primary color from text

**Replace all `color: var(--stratus-primary)` on text elements** with a new `var(--stratus-text-accent)` token.

In `_runtime.less`, change:

```less
// BEFORE (broken)
a { color: var(--stratus-primary); }

// AFTER
a { color: var(--stratus-text-accent); }
```

Affected rules in `_runtime.less`:
- `a` — link color (line 139)
- `.btn-secondary:hover` — text color (line 101)
- `.listing .listing-mark` — mark color (line 126)
- `.toolbar a.button:hover` — hover text (line 175)
- Dark mode equivalents (lines 232, 267, 276)

**Interim approach** (before full palette is ready): Add two new CSS custom properties with a safe fixed fallback:

```less
:root {
    --stratus-text-accent:      #3949ab;  // Indigo, 5.6:1 on white
    --stratus-text-accent-dark: #9fa8da;  // Indigo, 5.2:1 on #1a1f36
}
```

The PHP injection will set these per scheme. For schemes where we haven't yet validated a text accent, use the fixed accessible fallback (`#3949ab` / `#9fa8da`).

### 4.2 Migrate all @color-main to var(--stratus-primary)

Every widget/layout `.less` file that sets a CSS property to `@color-main` must be changed to `var(--stratus-primary)`. This is a mechanical find-and-replace, scoped to **property values only** (not variable definitions).

Rules:
- `color: @color-main` → depends on context:
  - If it's text/icon color → `color: var(--stratus-text-accent)`
  - If it's a decorative indicator → `color: var(--stratus-primary)`
- `background-color: @color-main` → `background-color: var(--stratus-primary)`
- `border-color: @color-main` → `border-color: var(--stratus-primary)`
- `@color-main-gradient` → needs a new `var(--stratus-primary-gradient)` or inline calc

**Text vs decorative distinction:**

| Context | Token to use |
|---------|-------------|
| Link text, label text, interactive text the user reads | `var(--stratus-text-accent)` |
| Icon color on hover (toolbar buttons) | `var(--stratus-text-accent)` |
| Active tab label + border | `var(--stratus-text-accent)` + `var(--stratus-primary)` |
| Unread accent border (left/top strip) | `var(--stratus-primary)` |
| Checkbox/radio fill | `var(--stratus-primary)` |
| Badge background | `var(--stratus-primary)` |
| Button background | `var(--stratus-primary)` |
| Focus ring / border | `var(--stratus-primary)` |
| Selected row background | `var(--stratus-selected-bg)` (Phase 2, use alpha for now) |

### 4.3 Same migration for dark mode

Mirror the light-mode migration for all `@color-dark-main` usages in `_dark.less` and dark sections of widget files:
- `@color-dark-main` → `var(--stratus-primary-dark)`
- Text contexts → `var(--stratus-text-accent-dark)`

### 4.4 Remove duplicate overrides in _runtime.less

After migration, many rules in `_runtime.less` will be redundant because the widget files themselves now use `var()`. Audit and remove duplicates. `_runtime.less` should only contain:
- `:root` token definitions
- `html.dark-mode` token overrides
- Rules that genuinely need a runtime-only override (e.g., the font-family body rule)

### 4.5 Deliverables

| Task | Files |
|------|-------|
| Add `--stratus-text-accent` + `--stratus-text-accent-dark` to `:root` | `_variables.less`, `_runtime.less` |
| Replace text `color` uses of primary with text-accent | `_runtime.less` |
| Migrate ~30 `@color-main` property values to `var()` | `widgets/*.less`, `_layout.less` |
| Migrate ~30 `@color-dark-main` property values to `var()` | `_dark.less`, widget dark sections |
| Update PHP injection to include text-accent tokens | `stratus_helper.php` |
| Update config with text-accent per scheme | `config.inc.php.dist` |
| Add text-accent to JS `applyScheme()` | `stratus_helper.js` |
| Remove redundant _runtime.less overrides | `_runtime.less` |
| Rebuild CSS | `styles.min.css` |

---

## 5. Phase 2 — Short-term

**Goal:** Full palette per scheme, sidebar theming, surface tokens.

### 5.1 Expand scheme config to full palette

Update `config.inc.php.dist` with the complete token set for all 8 schemes (see Section 3.2). Each scheme is hand-crafted with validated contrast ratios documented in comments.

Scheme palette worksheet (to be validated):

| Scheme | primary | text_accent (light) | text_accent (dark) | sidebar_bg |
|--------|---------|--------------------|--------------------|------------|
| Indigo | `#5c6bc0` | `#3949ab` (5.6:1) | `#9fa8da` (5.2:1) | `#1a1f36` |
| Ocean | `#0288d1` | `#01579b` (7.2:1) | `#81d4fa` (5.1:1) | `#0d1b2a` |
| Emerald | `#2e7d32` | `#1b5e20` (6.8:1) | `#81c784` (4.8:1) | `#0d1f12` |
| Rose | `#c62828` | `#b71c1c` (5.0:1) | `#ef9a9a` (5.4:1) | `#2a0f0f` |
| Amber | `#f57f17` | `#e65100` (4.6:1) | `#ffe082` (9.1:1) | `#2a1f0d` |
| Purple | `#7b1fa2` | `#6a1b9a` (6.1:1) | `#ce93d8` (4.9:1) | `#1a0d26` |
| Teal | `#00796b` | `#00695c` (5.1:1) | `#80cbc4` (5.8:1) | `#0d1f1c` |
| Slate | `#455a64` | `#37474f` (7.5:1) | `#b0bec5` (5.9:1) | `#1a1f24` |

> Ratios are target estimates. Final values to be validated with tooling during implementation.

### 5.2 Sidebar theming

**Add new CSS custom properties for the taskmenu:**

```less
:root {
    --stratus-sidebar-bg:           #1a1f36;
    --stratus-sidebar-gradient:     linear-gradient(180deg, #1e2444 0%, #151a2e 100%);
    --stratus-sidebar-text:         #8892b8;
    --stratus-sidebar-text-hover:   #ffffff;
    --stratus-sidebar-text-active:  #ffffff;
    --stratus-sidebar-active-bg:    rgba(92, 107, 192, 0.20);
}
```

**Migrate `_layout.less` taskmenu rules** from hardcoded values to `var()`:

```less
// BEFORE
#taskmenu {
    background: linear-gradient(180deg, #1e2444 0%, #151a2e 100%);
}
#taskmenu a { color: #8892b8; }

// AFTER
#taskmenu {
    background: var(--stratus-sidebar-gradient);
}
#taskmenu a { color: var(--stratus-sidebar-text); }
```

Affected properties in `_layout.less`:
- `@color-taskmenu-background` and `@mp-taskmenu-gradient`
- `@color-taskmenu-button`, `-selected`, `-hover`, `-action`
- `@color-taskmenu-button-*-background`
- Dark mode: `@mp-taskmenu-dark-gradient` and dark button colors

### 5.3 Surface tint tokens

Replace hardcoded alpha fades with named tokens:

```less
:root {
    --stratus-surface-tint:  rgba(var(--stratus-primary-rgb), 0.03);
    --stratus-hover-bg:      rgba(var(--stratus-primary-rgb), 0.06);
    --stratus-selected-bg:   rgba(var(--stratus-primary-rgb), 0.13);
    --stratus-focus-ring:    rgba(var(--stratus-primary-rgb), 0.25);
}
```

Migrate widget files that use `fadeout(@color-main, X%)` or `rgba(var(--stratus-primary-rgb), 0.XX)` inline to use these named tokens instead. This makes the opacity values scheme-configurable (some colors need higher opacity to be visible).

### 5.4 Update PHP injection

`stratus_helper.php::inject_appearance()` expands to inject all new tokens:

```php
$css .= "  --stratus-text-accent: {$scheme['text_accent']};\\n";
$css .= "  --stratus-sidebar-bg: {$scheme['sidebar_bg']};\\n";
$css .= "  --stratus-sidebar-gradient: {$scheme['sidebar_gradient']};\\n";
// ... etc.
```

`applyScheme()` in JS updates accordingly.

### 5.5 Update Settings UI

The scheme selector swatch should show the sidebar color + primary color together (a small two-tone preview) instead of just a single dot.

### 5.6 Deliverables

| Task | Files |
|------|-------|
| Expand all 8 schemes to full palette in config | `config.inc.php.dist` |
| Add sidebar CSS custom properties to `:root` | `_variables.less`, `_runtime.less` |
| Migrate taskmenu from hardcoded to `var()` | `_layout.less` |
| Add surface tint tokens | `_runtime.less` |
| Migrate inline `rgba(primary-rgb, X)` to named tokens | `_runtime.less`, `widgets/*.less` |
| Expand PHP injection for all tokens | `stratus_helper.php` |
| Expand JS `applyScheme()` for all tokens | `stratus_helper.js` |
| Update settings swatch UI | `stratus_helper.php` (prefs_list), `stratus_helper.js` |
| Dark mode: mirror all new tokens | `_dark.less`, `_runtime.less` dark section |
| Validate contrast ratios for all 8 schemes | Manual + tooling |
| Rebuild CSS | `styles.min.css` |

---

## 6. Phase 3 — Longer-term

**Goal:** Auto-derivation engine for admin-created schemes, font size preference.

### 6.1 Auto-derivation engine (PHP)

Add a method to `stratus_helper.php` that takes a minimal scheme (`primary` + optional `primary_dark`) and derives the full palette:

```php
private function derive_full_palette(array $scheme): array
{
    $primary = $scheme['primary'];
    $hsl = $this->hex_to_hsl($primary);

    // primary_dark: lighten by 15-20% for dark surfaces
    if (empty($scheme['primary_dark'])) {
        $scheme['primary_dark'] = $this->hsl_to_hex(
            $hsl[0], $hsl[1], min($hsl[2] + 18, 75)
        );
    }

    // text_accent: darken until WCAG AA passes on white
    if (empty($scheme['text_accent'])) {
        $scheme['text_accent'] = $this->derive_text_accent(
            $primary, '#ffffff', 4.5
        );
    }

    // text_accent_dark: lighten until WCAG AA passes on dark surface
    if (empty($scheme['text_accent_dark'])) {
        $scheme['text_accent_dark'] = $this->derive_text_accent(
            $scheme['primary_dark'], '#1a1f36', 4.5
        );
    }

    // sidebar: desaturate and darken to ~10% lightness
    if (empty($scheme['sidebar_bg'])) {
        $scheme['sidebar_bg'] = $this->hsl_to_hex(
            $hsl[0], max($hsl[1] * 0.4, 15), 10
        );
    }

    // sidebar gradient: slight variation of sidebar_bg
    if (empty($scheme['sidebar_gradient'])) {
        $top = $this->hsl_to_hex($hsl[0], max($hsl[1] * 0.4, 15), 13);
        $bot = $this->hsl_to_hex($hsl[0], max($hsl[1] * 0.4, 15), 7);
        $scheme['sidebar_gradient'] = "linear-gradient(180deg, {$top} 0%, {$bot} 100%)";
    }

    // ... derive remaining tokens with standard ratios
    return $scheme;
}
```

**Contrast validation helper:**

```php
private function derive_text_accent(string $color, string $surface, float $target_ratio): string
{
    $hsl = $this->hex_to_hsl($color);
    $surface_lum = $this->relative_luminance($surface);

    // Determine direction: darken for light surfaces, lighten for dark
    $direction = ($surface_lum > 0.5) ? -1 : 1;

    for ($i = 0; $i < 40; $i++) {
        $candidate = $this->hsl_to_hex($hsl[0], $hsl[1], $hsl[2]);
        $ratio = $this->contrast_ratio($candidate, $surface);
        if ($ratio >= $target_ratio) {
            return $candidate;
        }
        $hsl[2] = max(0, min(100, $hsl[2] + ($direction * 2)));
    }

    // Fallback: guaranteed accessible neutral
    return ($surface_lum > 0.5) ? '#3949ab' : '#9fa8da';
}
```

### 6.2 Integration with config loading

In `init()`, after loading config, run derivation on any schemes missing tokens:

```php
$schemes = $this->rcmail->config->get('stratus_color_schemes', []);
foreach ($schemes as $key => &$scheme) {
    if (empty($scheme['text_accent']) || empty($scheme['sidebar_bg'])) {
        $scheme = $this->derive_full_palette($scheme);
    }
}
// Cache derived schemes for the request
$this->rcmail->config->set('stratus_color_schemes', $schemes);
```

### 6.3 Font size preference

**Three presets:** Small / Default / Large

| Preset | `--stratus-font-size` | `--stratus-line-height` | Description |
|--------|-----------------------|------------------------|-------------|
| Small | `0.8125rem` (13px) | 1.4 | Compact, information-dense |
| Default | `0.875rem` (14px) | 1.5 | Current baseline |
| Large | `1rem` (16px) | 1.6 | Comfortable reading |

**Implementation:**

1. Add `stratus_font_size` user preference (stored as `small` / `default` / `large`)
2. PHP injects `--stratus-font-size` and `--stratus-line-height` CSS custom properties
3. Body rule updated:

```less
body {
    font-family: var(--stratus-font-family);
    font-size: var(--stratus-font-size, 0.875rem);
    line-height: var(--stratus-line-height, 1.5);
}
```

4. Settings UI: dropdown or segmented control in Settings → Stratus, below font family

**Config:**

```php
$config['stratus_font_sizes'] = [
    'small'   => ['size' => '0.8125rem', 'line_height' => '1.4', 'label' => 'Small'],
    'default' => ['size' => '0.875rem',  'line_height' => '1.5', 'label' => 'Default'],
    'large'   => ['size' => '1rem',      'line_height' => '1.6', 'label' => 'Large'],
];
$config['stratus_font_size_default'] = 'default';
```

### 6.4 Deliverables

| Task | Files |
|------|-------|
| Implement `derive_full_palette()` with HSL utilities | `stratus_helper.php` |
| Implement `derive_text_accent()` with WCAG contrast check | `stratus_helper.php` |
| Auto-derive on config load for minimal schemes | `stratus_helper.php` |
| Add font size preset config | `config.inc.php.dist` |
| Add `stratus_font_size` preference persistence | `stratus_helper.php` |
| Add font size CSS custom properties | `_variables.less`, `_runtime.less` |
| Add font size to Settings UI | `stratus_helper.php`, `stratus_helper.js` |
| Add font size to JS live-switch | `stratus_helper.js` |
| Add localization keys for font size labels | `localization/en_US.inc` |

---

## 7. Contrast Validation

### 7.1 Standard

All text-on-surface color combinations must meet **WCAG 2.1 AA**:
- **Normal text** (< 18px, or < 14px bold): 4.5:1 minimum
- **Large text** (≥ 18px, or ≥ 14px bold) and UI components: 3:1 minimum

### 7.2 Surfaces to validate against

Each `text_accent` must be checked against:

| Surface | Light mode | Dark mode |
|---------|-----------|-----------|
| Main content background | `#ffffff` | `#1a1f36` |
| Header/panel background | `#f8f9fd` | `#1a1f36` |
| Selected row background | primary @ 13% on white | primary_dark @ 15% on dark |
| Sidebar | N/A (no text accent on sidebar) | N/A |

### 7.3 Validation process

**Pre-computed schemes** (the 8 defaults): validate manually with a contrast checker during implementation. Document ratios in config comments.

**Auto-derived schemes** (admin-added): the PHP derivation engine adjusts lightness until the target ratio is met, with a guaranteed-safe fallback.

### 7.4 Elements exempt from text_accent

These use `--stratus-primary` directly (not text) and only need 3:1 against their surface:
- Badge text (white on primary bg — always passes with solid bg)
- Checkbox/radio fill (3:1 against white/dark surface)
- Focus ring (decorative, no minimum)
- FAB/button backgrounds (white text on primary bg)

---

## 8. Migration Guide

### 8.1 For LESS widget files

**Pattern: text color**
```less
// BEFORE
color: @color-main;
// AFTER — if this is readable text/icon
color: var(--stratus-text-accent);
// AFTER — if this is a decorative indicator
color: var(--stratus-primary);
```

**Pattern: background**
```less
// BEFORE
background-color: @color-main;
// AFTER
background-color: var(--stratus-primary);
```

**Pattern: border**
```less
// BEFORE
border-color: @color-main;
// AFTER
border-color: var(--stratus-primary);
```

**Pattern: alpha fade**
```less
// BEFORE
background: fadeout(@color-main, 87%);
// AFTER (Phase 1 — inline)
background: rgba(var(--stratus-primary-rgb), 0.13);
// AFTER (Phase 2 — named token)
background: var(--stratus-selected-bg);
```

**Pattern: gradient**
```less
// BEFORE
background: @color-main-gradient;
// AFTER
background: var(--stratus-primary-gradient,
    linear-gradient(135deg, var(--stratus-primary) 0%, #7c4dff 100%));
```

### 8.2 For dark mode

Same patterns, but:
- `@color-dark-main` → `var(--stratus-primary-dark)`
- Text contexts → `var(--stratus-text-accent-dark)`

### 8.3 For _runtime.less cleanup

After migration, `_runtime.less` should contain:
1. `:root { }` block with all token defaults
2. `html.dark-mode { }` block with dark overrides
3. `body { font-family; font-size; line-height; }` rule
4. **No component-specific rules** — those now live in the widget files via `var()`

---

## 9. Files Affected

### Phase 1

| File | Change type |
|------|-------------|
| `skins/stratus/styles/_variables.less` | Add text-accent defaults to `:root` |
| `skins/stratus/styles/_runtime.less` | Replace primary→text-accent for text; remove redundant overrides |
| `skins/stratus/styles/widgets/common.less` | Migrate 10 `@color-main` → `var()` |
| `skins/stratus/styles/widgets/lists.less` | Migrate 4 `@color-main` → `var()` |
| `skins/stratus/styles/widgets/menu.less` | Migrate 3 `@color-main` → `var()` |
| `skins/stratus/styles/widgets/buttons.less` | Migrate 1 `@color-main` → `var()` |
| `skins/stratus/styles/widgets/forms.less` | Migrate 5 `@color-main` → `var()` |
| `skins/stratus/styles/widgets/editor.less` | Migrate 2 `@color-main` → `var()` |
| `skins/stratus/styles/widgets/dialogs.less` | Migrate 1 `@color-main` → `var()` |
| `skins/stratus/styles/_layout.less` | Migrate 5 `@color-main` → `var()` |
| `skins/stratus/styles/_dark.less` | Mirror all migrations for `@color-dark-main` |
| `plugins/stratus_helper/config.inc.php.dist` | Add text_accent per scheme |
| `plugins/stratus_helper/stratus_helper.php` | Inject text-accent tokens |
| `plugins/stratus_helper/stratus_helper.js` | Update `applyScheme()` with text-accent |
| `skins/stratus/styles/styles.min.css` | Rebuild |

### Phase 2

All Phase 1 files plus:

| File | Change type |
|------|-------------|
| `skins/stratus/styles/_layout.less` | Migrate taskmenu to `var()` sidebar tokens |
| `skins/stratus/styles/_variables.less` | Add sidebar + surface token defaults |
| `plugins/stratus_helper/config.inc.php.dist` | Full palette for all 8 schemes |

### Phase 3

All above plus:

| File | Change type |
|------|-------------|
| `plugins/stratus_helper/stratus_helper.php` | Auto-derivation engine, font size pref |
| `plugins/stratus_helper/stratus_helper.js` | Font size live switching |
| `plugins/stratus_helper/config.inc.php.dist` | Font size presets |
| `plugins/stratus_helper/localization/en_US.inc` | Font size labels |
