# Feature Spec: Conversation Mode (Latest-First)

## Summary
Implement a new **Conversation Mode** in Roundcube/Stratus that groups related messages into conversations and shows the **latest activity first**.

This mode is separate from native IMAP threading and does not alter existing threaded behavior.

## Goals
- Provide a modern conversation UX.
- Sort conversations by latest message date (`DESC`).
- Show messages inside a conversation newest to oldest.
- Keep existing IMAP thread mode fully intact.
- Make the mode user-configurable and reversible.

## Non-Goals
- Replacing or changing IMAP `THREAD` protocol behavior.
- Breaking existing Roundcube list mode, sorting, or plugin compatibility.
- Building full cross-folder global search in Phase 1.

## User Experience
- New toggle in mail list UI: `Threads` / `Conversations`.
- In Conversations mode, list rows represent conversations (not single messages).
- Row fields:
  - Subject (normalized)
  - Participants summary
  - Latest snippet
  - Latest timestamp
  - Unread count
  - Flag/attachment indicators (aggregated)
- Opening a conversation displays messages in newest-first order.

## Functional Requirements
1. Add user preference `message_list_mode` with values:
   - `threads` (default existing behavior)
   - `conversations`
2. Preserve current behavior when mode is `threads`.
3. In `conversations` mode:
   - Group messages into conversation buckets.
   - Sort buckets by latest message datetime descending.
   - Support pagination on conversation rows.
4. Maintain unread and flagged state consistency after actions (mark, move, delete).
5. Respect mailbox context and existing permissions.

## Technical Design

### Architecture
- **Plugin-first implementation** (e.g., `stratus_helper`):
  - Owns conversation grouping, ordering, and cache/index.
  - Provides backend actions/endpoints for conversation list and expansion.
- **Skin/UI integration** (Stratus):
  - Toggle, row rendering, expanded conversation presentation.

### Conversation Grouping Strategy
1. Primary linkage:
   - `Message-ID`
   - `In-Reply-To`
   - `References`
2. Fallback linkage:
   - Normalized subject (`Re:`, `Fwd:` stripped)
   - Participant fingerprint
   - Time-window heuristic

### Data Model (Plugin Cache)
Suggested table/entity fields:
- `conversation_id`
- `mailbox`
- `root_uid` (optional)
- `latest_uid`
- `latest_date`
- `subject_norm`
- `participants_hash`
- `message_count`
- `unread_count`
- `flagged_count`
- `has_attachments`
- `updated_at`

### Integration Points
- Preferences hooks:
  - `preferences_list`
  - `preferences_save`
- Mail list shaping:
  - `messages_list` (initial bridge)
- Custom plugin actions (recommended for full behavior):
  - `conversation.list`
  - `conversation.open`
  - `conversation.refresh`

### Pagination & Sorting
- Pagination must operate on conversations, not raw messages.
- Default sort in conversation mode: latest date descending.
- Existing per-message sort controls remain for thread/list modes.

## Risks and Mitigations
- **Risk:** Hook-only path limited by core pagination order.
  - **Mitigation:** Add custom plugin list endpoints for conversation-mode requests.
- **Risk:** Heuristic grouping false positives/negatives.
  - **Mitigation:** Prefer RFC headers first; log/debug mismatch cases.
- **Risk:** Cache staleness after IMAP updates.
  - **Mitigation:** Incremental refresh via modseq/folder sync and periodic rebuild.

## Rollout Plan
1. **Phase 0 (Design/Spike)**
   - Validate hooks/endpoints and data shape.
   - Define API contract for UI.
2. **Phase 1 (MVP)**
   - Per-mailbox conversation list.
   - Latest-first ordering.
   - Expand conversation newest-first.
   - Basic unread aggregation.
3. **Phase 2 (State correctness)**
   - Move/delete/mark sync.
   - Cache invalidation hardening.
4. **Phase 3 (UX polish)**
   - Keyboard navigation
   - Performance optimization
   - Edge-case handling (large threads, sent/drafts variants)

## Acceptance Criteria
- User can switch between Threads and Conversations without data loss.
- Conversations list is correctly sorted by latest message descending.
- Opening a conversation shows messages newest-first.
- Unread count and flags remain accurate after common actions.
- Existing thread mode remains unchanged.

## Test Plan
- Unit tests for grouping logic and subject normalization.
- Integration tests for:
  - Mode toggle
  - Conversation pagination
  - Sorting correctness
  - Unread/flag updates after move/delete/mark
- Manual tests on inboxes with:
  - Reply chains
  - Forward chains
  - Missing `References` headers
  - Multi-folder workflows

## Open Questions
- Should conversation mode be mailbox-only in MVP or include optional cross-folder view?
- How should draft/sent variants be merged into one conversation in MVP?
- Should per-conversation snooze/mute be included later?

## Implementation Checklist
- [ ] Create plugin scaffold for conversation mode.
- [ ] Add preference key and settings UI.
- [ ] Build grouping + cache/index service.
- [ ] Add conversation list/open endpoints.
- [ ] Wire Stratus UI toggle and conversation rows.
- [ ] Add action sync for mark/move/delete.
- [ ] Add tests and performance benchmarks.
- [ ] Document config and migration notes.
