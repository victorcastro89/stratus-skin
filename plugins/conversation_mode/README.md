# Conversation Mode Plugin for Roundcube

Groups related messages into conversations and displays the latest activity first. Works with **any** Roundcube skin.

## Features

- **Conversation grouping** using RFC headers (Message-ID, In-Reply-To, References) with a normalized-subject fallback.
- **Latest-first ordering** — conversations sorted by newest message, messages within a conversation shown newest-first.
- **Mode toggle** — switch between Standard list / Threads / Conversations from the toolbar or Settings.
- **Pagination** on conversation rows.
- **Unread / flagged / attachment indicators** aggregated per conversation.
- **Skin-agnostic** — includes default CSS plus Elastic skin overrides.

## Requirements

- Roundcube **1.6+**
- PHP **7.4+**

## Installation

### Manual

1. Copy `conversation_mode/` into your Roundcube `plugins/` directory.
2. Enable the plugin in `config/config.inc.php`:

   ```php
   $config['plugins'] = ['conversation_mode', /* other plugins */];
   ```

3. (Optional) Copy `config.inc.php.dist` to `config.inc.php` and adjust settings.

### Composer

```bash
composer require roundcube/conversation_mode
```

## Configuration

| Option | Default | Description |
|---|---|---|
| `conversation_mode_default` | `'list'` | Default mode for new users: `list`, `threads`, or `conversations` |
| `conversation_mode_page_size` | `50` | Conversations per page |
| `conversation_mode_subject_window_days` | `30` | Time window for subject-based fallback grouping |
| `conversation_mode_subject_fallback` | `true` | Enable subject-based fallback when RFC headers are missing |
| `conversation_mode_cache_ttl` | `300` | Cache TTL in seconds (0 = rebuild every request) |

## How It Works

### Grouping Strategy

1. **Primary:** Links messages by `Message-ID` → `In-Reply-To` → `References` headers using a union-find algorithm.
2. **Fallback:** Messages without header links are merged by normalized subject (stripping `Re:`, `Fwd:`, etc.) within a configurable time window.

### Architecture

```
conversation_mode.php          Main plugin class (hooks, actions, prefs)
├── lib/
│   ├── conversation_mode_service.php   Orchestrator (list, open, refresh)
│   ├── conversation_mode_grouper.php   Grouping algorithm
│   └── conversation_mode_cache.php     Session-based caching
├── conversation_mode.js       Client-side UI
├── skins/
│   ├── default/               Baseline CSS (all skins)
│   └── elastic/               Elastic skin overrides
└── localization/
    └── en_US.inc              English strings
```

### AJAX Endpoints

| Action | Method | Purpose |
|---|---|---|
| `plugin.conv.list` | GET | Paginated conversation list |
| `plugin.conv.open` | GET | Messages in a conversation (newest-first) |
| `plugin.conv.refresh` | GET | Incremental refresh |
| `plugin.conv.setmode` | POST | Toggle mode preference |

## Roadmap

- [ ] Phase 2: Move/delete/mark sync and cache invalidation hardening
- [ ] Phase 3: Keyboard navigation and performance optimization
- [ ] Cross-folder conversation merging
- [ ] Per-conversation snooze/mute

## License

GNU GPLv3+
