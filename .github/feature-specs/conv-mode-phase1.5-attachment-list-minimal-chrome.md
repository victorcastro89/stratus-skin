# Feature Spec: Conversation Mode Attachment List Minimal Chrome (Phase 1.5 §2)

**Status:** IMPLEMENTED

## Roadmap Reference
- **Phase:** Conversation Mode Plugin — Phase 1.5: UI Overhaul (Outlook-Grade List & Reading Pane)
- **Section:** 2 — Reading Pane Integration (show conversation in `#layout-content`)
- **Items:** Conversation thread view in reading pane (visual polish for message content elements, including attachment list chrome)
- **Related Foundation:** Phase 1.5 — Visual Polish → Custom message view styling (attachment chips as pills)

## Summary
This feature removes unnecessary visual borders around attachment filename text and the attachment row menu/action icon in message content. The goal is a cleaner, more modern attachment presentation that reduces visual noise while preserving clear affordances through spacing, hover/focus states, and icon contrast.

The update is scoped to visual styling only and does not change attachment behavior, actions, or backend data.

## Goals
- Remove border treatment from attachment filename container.
- Remove border treatment from attachment menu/action icon button container.
- Preserve discoverability and accessibility using hover/focus states.
- Keep spacing and alignment consistent with Stratus visual language.
- Maintain parity in light and dark mode.

## Non-Goals
- No changes to attachment actions (`Download`, preview, menu logic).
- No JS behavior changes in `conversation_mode.js`.
- No template structure changes unless required for accessibility semantics.
- No redesign of attachment metadata content (filename, size, type labels).

## User Experience
- Attachment rows look flatter and less “boxed”.
- Filenames appear as clean text labels without chip-like border outlines.
- Menu/action icons appear as quiet icon controls by default.
- On hover/focus, users still get clear interactive feedback (background tint, focus ring, or subtle emphasis).
- In dark mode, controls remain legible and balanced without high-contrast border clutter.

## Technical Design
- Implement style changes in skin LESS and conversation mode CSS bridge layers, preserving existing token-first approach.
- Replace border-based affordance with:
  - spacing rhythm,
  - hover background emphasis,
  - consistent icon color states,
  - keyboard focus rings.
- If attachment list elements live in plugin CSS, route color/surface values through existing `--mp-conv-*` variables; if missing, define new bridge tokens first in Stratus runtime/dark token files.
- Avoid hardcoded color values in component rules; reference existing LESS variables or add new ones in `_variables.less` first.

## Files Changed
- `.github/feature-specs/conv-mode-phase1.5-attachment-list-minimal-chrome.md` (this spec)
- `skins/stratus/styles/widgets/messages.less` (attachment list visual adjustments)
- `skins/stratus/styles/_variables.less` (only if new spacing/state tokens are required)
- `skins/stratus/styles/_runtime.less` (only if new plugin bridge CSS custom properties are required)
- `skins/stratus/styles/_dark.less` (dark-mode parity for any new tokens/states)
- `plugins/conversation_mode/skins/elastic/conversation_mode.css` (if attachment markup is styled here)

## Dark Mode Considerations
- Keep border removal consistent in `html.dark-mode`.
- Ensure filename text contrast remains at or above existing readable levels.
- Use existing dark tokens for hover/focus surfaces; avoid introducing bright outlines that increase visual clutter.
- Validate focus visibility for keyboard users after border removal.

## Validation Criteria
- [ ] No visible border on attachment filename element in message content.
- [ ] No visible border on attachment menu/action icon container in default state.
- [ ] Hover/focus still clearly indicates interactive controls.
- [ ] Light mode: visual hierarchy is cleaner and actions remain obvious.
- [ ] Dark mode: no contrast regressions or washed-out controls.
- [x] LESS compiles successfully (`Compile Stratus LESS (once)`).
- [ ] No regression in attachment click targets or menu behavior.

## Risks / Open Questions
- **Risk:** Border removal could reduce perceived clickability.
  - **Mitigation:** strengthen hover/focus/active visual states and preserve icon prominence.
- **Risk:** Attachment styles may be split between skin LESS and plugin CSS.
  - **Mitigation:** inspect final selector ownership and apply minimal, scoped overrides.
- **Open Question:** Should hover-only emphasis be shown on desktop only, with persistent subtle emphasis on touch devices?
- **Open Question:** Do we also remove borders for attachment thumbnail wrappers, or strictly filename and menu icon only?
