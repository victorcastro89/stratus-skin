# Feature Spec: Scheduled Send (Standalone Plugin)

**Status:** DRAFT

## Roadmap Reference

- **Phase:** Standalone plugin extension (skin-agnostic)
- **Section:** Compose UX improvements
- **Items:** Gmail/Outlook-style scheduled send added to the existing `undo_send` plugin

## Summary

Create a **new standalone plugin** called `scheduled_send` that provides Gmail/Outlook-style email scheduling. When activated via `config.inc.php`, a split Send button appears on the compose page: `[ Send | ▾ ]`. Clicking the dropdown arrow reveals quick schedule options ("Later today", "Tomorrow morning", etc.) and a "Pick date & time" custom picker. Scheduled messages are stored in a lightweight database table as **encrypted queue payloads** and dispatched by an external cron job using a **server-owned delivery profile** — never with a per-message snapshot of SMTP credentials. The feature supports MySQL, PostgreSQL, and SQLite backends. It is **entirely opt-in** — when disabled, the plugin does not affect normal send behavior.

## Goals

- **Standalone plugin** — `scheduled_send` is completely separated from `undo_send`; no shared code, no shared config
- **Optional activation** — `$config['scheduled_send_enabled'] = false;` by default; zero overhead when off
- **Gmail/Outlook-like UX** — split Send button with dropdown, smart quick-pick times, inline datetime picker
- **All three DB backends** — MySQL, PostgreSQL, and SQLite (using atomic `sending` flag, no `FOR UPDATE`)
- **Skin-agnostic** — works on elastic, larry-based skins, and stratus
- **No external dependencies** — no xframework, no flatpickr CDN; uses `<input type="datetime-local">` (universally supported in modern browsers)
- **Cron-based delivery** — simple `cron.php` entry point, no long-running daemon
- **Scheduled message management** — users can view, cancel, and reschedule pending messages
- **No SMTP secrets in the database** — queue rows never store passwords, OAuth refresh tokens, or raw `server_config` snapshots
- **Encrypted message queue** — scheduled message payload is encrypted at rest with an installation key stored outside the database
- **Least-privilege delivery** — cron sends using an approved server-side delivery profile, local MTA, or relay account, not end-user credentials

## Non-Goals

- **Undo-send integration** — scheduled_send does not depend on or modify undo_send; both plugins can be enabled independently
- **Recurring / repeating emails** — out of scope
- **Read-receipt or delivery-status tracking for scheduled messages** — standard RC behavior after delivery
- **IMAP-based draft storage** — we store the serialized `Mail_mime` in the DB to avoid requiring IMAP credentials at cron time
- **Timezone conversion UI** — we store and compare times in UTC; the browser `datetime-local` input handles local→UTC conversion via JS `Date`
- **Multi-server locking (Redis, file locks)** — the single-row atomic `sending` flag is sufficient for typical deployments
- **Per-user SMTP credential replay** — this design will not store user passwords, SMTP passwords, OAuth refresh tokens, or serialized `server_config` snapshots in queue rows
- **Bypassing server mail policy** — scheduled send must still obey sender identity rules, relay restrictions, and allowed envelope sender policy

---

## User Experience

### 1. Compose Page — Split Send Button

When `schedule_enabled = true`, the Send button transforms from a single button into a **Bootstrap-style split button group**:

```
┌────────┬───┐
│  Send  │ ▾ │
└────────┴───┘
```

- **Left side ("Send"):** Behaves exactly as before — immediate send with undo countdown (if delay > 0).
- **Right side ("▾"):** Opens a dropdown menu below the button.

#### Dropdown Menu

```
┌──────────────────────────────────┐
│  ⏰  Schedule send                │
│  ──────────────────────────────  │
│  🌆  Later today      6:00 PM   │
│  🌅  Tomorrow morning  8:00 AM  │
│  🌇  Tomorrow afternoon 1:00 PM │
│  📅  Monday morning    8:00 AM  │  ← only shown Tue–Sat
│  ──────────────────────────────  │
│  🗓️  Pick date & time…          │
└──────────────────────────────────┘
```

**Smart quick-pick logic:**
- "Later today" only appears if current time is before 4:00 PM; schedules for 6:00 PM same day
- "Tomorrow morning" — always available — 8:00 AM next day
- "Tomorrow afternoon" — always available — 1:00 PM next day
- "Monday morning" — shown Tue–Sat only (on Sunday/Monday it's redundant with "Tomorrow morning")
- All times shown in the user's local timezone (browser-based)

#### "Pick date & time…" — Inline Datetime Picker

Clicking "Pick date & time…" expands a **small popover panel** below the dropdown (not a full-page modal). Contents:

```
┌──────────────────────────────────┐
│  Schedule send                   │
│                                  │
│  Date & Time                     │
│  ┌────────────────────────────┐  │
│  │ 2026-03-15T10:30          │  │  ← <input type="datetime-local">
│  └────────────────────────────┘  │
│                                  │
│  ┌────────────┐  ┌──────────┐   │
│  │  Schedule   │  │  Cancel  │   │
│  └────────────┘  └──────────┘   │
└──────────────────────────────────┘
```

- Uses native `<input type="datetime-local">` — no JS library needed, OS-native picker on every browser
- Minimum selectable time: **now + 5 minutes** (prevents scheduling in the past)
- Maximum selectable time: **now + 90 days** (prevents absurdly distant scheduling)
- "Schedule" button is primary styled; "Cancel" closes the popover

#### After Scheduling

1. Toast notification: **"Message scheduled for Mar 15, 10:30 AM. [View scheduled]"** (notice type, 6s auto-dismiss)
2. Compose window closes (redirects to Inbox, same as a normal send)
3. The message is **not** delivered to SMTP — it is stored in the `undo_send_schedule` DB table as an encrypted queue payload

### 2. Viewing Scheduled Messages

A **"Scheduled"** entry appears in the mailbox list sidebar (like Gmail). Clicking it shows a list of pending scheduled messages.

**Implementation approach — virtual mailbox folder:**
- Plugin registers a hook on `mailboxes_list` (or uses `render_page` + JS injection) to add a "Scheduled" entry to the folder list
- Clicking "Scheduled" triggers a custom AJAX action (`plugin.schedule-list`) that returns the queue contents
- Messages are displayed in a **list view** matching the current skin's message list style:

```
┌─────────────────────────────────────────────────────────┐
│ ☐  To: alice@example.com          Mar 15, 10:30 AM  ▶ │
│     Q1 Budget Report                                    │
│                                                         │
│ ☐  To: team@example.com           Mar 16,  8:00 AM  ▶ │
│     Weekly standup agenda                               │
└─────────────────────────────────────────────────────────┘
```

**Per-message actions (toolbar or context menu):**
- **Cancel send** → removes from queue, optionally saves as draft
- **Reschedule** → opens the datetime picker popover with pre-filled time
- **Edit** → opens compose with the message loaded (cancel + open as draft)

### 3. Settings → Undo Send (Extended)

The existing "Undo Send" preferences section gains a new setting when scheduling is enabled:

| Setting | Options | Default |
|---------|---------|---------|
| Undo send delay | Disabled / 3s / 5s / 10s / 15s / 30s | 5 seconds |
| Default schedule time | Morning (8 AM) / Afternoon (1 PM) / Evening (6 PM) | Morning |

The "Default schedule time" controls the smart quick-pick hours. Administrators can lock any setting via `dont_override`.

### 4. Cron Delivery

A system cron job runs every minute:
```
* * * * * php /path/to/roundcube/plugins/undo_send/cron.php
```

- Scans the queue for messages where `send_at <= NOW()` and `status = 'pending'`
- Claims one message at a time using an atomic `UPDATE ... SET status = 'sending' WHERE status = 'pending'`
- Delivers via `rcube::deliver_message()` using a pre-configured server-side delivery profile or local MTA
- On success: sets `status = 'sent'`, records `sent_at` timestamp
- On failure: sets `status = 'error'`, records `error_message`, increments `retry_count`
- Failed messages retry up to 3 times (configurable), then stay as `error` for manual review
- A health-check flag is stored so the compose UI can warn if cron hasn't run recently
- Queue rows are purged after a retention window once delivery succeeds or the message is cancelled

---

## Technical Design

### Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                     undo_send plugin                     │
│                                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │
│  │  Undo Send   │  │  Schedule    │  │  Schedule    │  │
│  │  (existing)  │  │  Compose UI  │  │  Manager UI  │  │
│  │              │  │  (JS+PHP)    │  │  (JS+PHP)    │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  │
│                           │                │            │
│                    ┌──────┴────────────────┘            │
│                    ▼                                    │
│            ┌───────────────┐     ┌──────────────┐      │
│            │ScheduleService│     │   cron.php   │      │
│            │   (PHP lib)   │◄───►│  (CLI entry) │      │
│            └───────┬───────┘     └──────────────┘      │
│                    │                                    │
│                    ▼                                    │
│          ┌─────────────────┐                            │
│          │  undo_send_     │                            │
│          │  schedule (DB)  │                            │
│          └─────────────────┘                            │
│                    ▲                                    │
│                    │                                    │
│          ┌─────────────────┐                            │
│          │ Install key /   │                            │
│          │ delivery profile│                            │
│          │ (outside DB)    │                            │
│          └─────────────────┘                            │
└─────────────────────────────────────────────────────────┘
```

### File Structure

```
plugins/scheduled_send/
├── scheduled_send.php         # Main plugin class — schedule hooks, AJAX actions
├── scheduled_send.js          # Split button, dropdown, popover, AJAX, scheduled messages panel
├── config.inc.php.dist        # Schedule config, secure delivery settings
├── composer.json              # Plugin metadata
├── localization/
│   └── en_US.inc              # Schedule labels
├── skins/
│   ├── default/
│   │   └── scheduled_send.css # Dropdown + popover baseline
│   └── elastic/
│   │   └── scheduled_send.css # Elastic overrides for schedule UI
├── lib/
│   └── ScheduleService.php    # DB operations (CRUD, claim, encrypt, decrypt, deliver)
├── cron.php                   # CLI cron entry point
└── SQL/
    ├── mysql.initial.sql      # MySQL schema
    ├── postgres.initial.sql   # PostgreSQL schema
    └── sqlite.initial.sql     # SQLite schema
```

### Database Schema

**Table: `undo_send_schedule`**

| Column | MySQL | PostgreSQL | SQLite | Description |
|--------|-------|------------|--------|-------------|
| `id` | `INT AUTO_INCREMENT PRIMARY KEY` | `SERIAL PRIMARY KEY` | `INTEGER PRIMARY KEY AUTOINCREMENT` | Row ID |
| `user_id` | `INT NOT NULL` | `INTEGER NOT NULL` | `INTEGER NOT NULL` | FK → `users.user_id` |
| `message_uid` | `VARCHAR(128) NOT NULL` | `VARCHAR(128) NOT NULL` | `VARCHAR(128) NOT NULL` | Unique message identifier |
| `delivery_profile` | `VARCHAR(64) NOT NULL` | `VARCHAR(64) NOT NULL` | `VARCHAR(64) NOT NULL` | Server-side profile ID, not credentials |
| `address_from` | `VARCHAR(255) NOT NULL` | `VARCHAR(255) NOT NULL` | `VARCHAR(255) NOT NULL` | Sender address |
| `recipients_preview` | `TEXT` | `TEXT` | `TEXT` | Human-readable recipients for list UI |
| `subject_preview` | `TEXT` | `TEXT` | `TEXT` | Subject preview for list UI |
| `payload_nonce` | `VARBINARY(24)` | `BYTEA` | `BLOB` | AEAD nonce |
| `payload_ciphertext` | `LONGBLOB` | `BYTEA` | `BLOB` | Encrypted serialized message payload |
| `key_version` | `VARCHAR(16) NOT NULL` | `VARCHAR(16) NOT NULL` | `VARCHAR(16) NOT NULL` | Install key version used for encryption |
| `send_at` | `DATETIME NOT NULL` | `TIMESTAMP NOT NULL` | `DATETIME NOT NULL` | Scheduled delivery time (UTC) |
| `status` | `ENUM('pending','sending','sent','error','cancelled')` | `VARCHAR(20) NOT NULL DEFAULT 'pending'` | `VARCHAR(20) NOT NULL DEFAULT 'pending'` | Queue status |
| `retry_count` | `TINYINT NOT NULL DEFAULT 0` | `SMALLINT NOT NULL DEFAULT 0` | `INTEGER NOT NULL DEFAULT 0` | Delivery attempt count |
| `error_message` | `TEXT` | `TEXT` | `TEXT` | Last error (NULL if none) |
| `created_at` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` | `TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP` | `DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP` | When scheduled |
| `sent_at` | `DATETIME` | `TIMESTAMP` | `DATETIME` | When actually delivered (NULL if pending) |

**Indexes:**
- `idx_schedule_pending`: `(status, send_at)` — cron query efficiency
- `idx_schedule_user`: `(user_id, status)` — user's scheduled messages list
- `UNIQUE(message_uid)` — prevent duplicate scheduling

**Encrypted payload contents:**
- Serialized `Mail_mime` object
- Canonical recipient list (`to`, `cc`, `bcc`)
- Compose metadata needed for send/draft restoration
- Optional Sent-folder copy blob

**Not stored in DB:**
- SMTP password
- SMTP username snapshot
- OAuth refresh/access tokens
- Raw `server_config` JSON

**No `FOR UPDATE`** — instead, atomic claim via:
```sql
UPDATE undo_send_schedule
   SET status = 'sending'
 WHERE status = 'pending'
   AND send_at <= :now
   AND id = (
       SELECT id FROM undo_send_schedule
        WHERE status = 'pending' AND send_at <= :now
        ORDER BY send_at ASC
        LIMIT 1
   )
```
Returns affected rows = 1 (claimed) or 0 (nothing to do). Works identically on all three backends.

### SQLite Safety Requirements

SQLite support is intended for **single-host deployments** and requires extra safeguards beyond the shared schema.

#### 1. Single cron process lock

Wrap `cron.php` in a process lock using `flock()` on a lock file, for example under `temp/undo-send-schedule.lock`.

- If the lock is already held, the cron process exits immediately
- This guarantees only one scheduler worker is active at a time
- This prevents competing cron runs from claiming and sending the same message concurrently

Example approach:

```php
$lock_path = INSTALL_PATH . 'temp/undo-send-schedule.lock';
$lock_fp = fopen($lock_path, 'c');

if (!$lock_fp || !flock($lock_fp, LOCK_EX | LOCK_NB)) {
    exit(0);
}
```

#### 2. Provider-specific claim strategy

SQLite must not be treated like a multi-worker queue backend.

- **MySQL/PostgreSQL:** may use DB-centric atomic claim logic
- **SQLite:** must use the single cron lock above plus a short transaction that claims one pending row and updates it to `sending`

Therefore, SQLite is supported only for:

- one Roundcube host
- one cron runner
- local SQLite file storage

For multi-node or higher-concurrency deployments, MySQL or PostgreSQL remains the recommended backend.

### PHP — `undo_send.php` Extensions

**New methods added to the existing class:**

```php
// Initialization (in init())
if ($schedule_enabled && $this->rcmail->task === 'mail') {
    $this->add_hook('message_before_send', [$this, 'intercept_for_schedule']);
    $this->register_action('plugin.schedule-list',   [$this, 'ajax_schedule_list']);
    $this->register_action('plugin.schedule-cancel',  [$this, 'ajax_schedule_cancel']);
    $this->register_action('plugin.schedule-update',  [$this, 'ajax_schedule_update']);
    $this->register_action('plugin.schedule-edit',    [$this, 'ajax_schedule_edit']);
}
```

**`intercept_for_schedule` (message_before_send hook):**
- Only fires when the client sends a `_schedule_at` POST parameter
- Sets `$args['abort'] = true; $args['result'] = true;` to prevent immediate delivery
- Resolves a configured delivery profile (`local_mta`, `relay`, etc.)
- Serializes the `Mail_mime` object and encrypts it with the installation key
- Calls `ScheduleService::enqueue($user_id, $message_uid, $delivery_profile, $from, $preview_to, $preview_subject, $encrypted_payload, $send_at)`
- Returns success so the client-side JS receives a "sent" signal and can redirect
- Verifies that the selected sender identity is permitted for the resolved delivery profile

**AJAX actions:**
- `plugin.schedule-list` — returns JSON array of user's pending/error messages
- `plugin.schedule-cancel` — sets status to `cancelled` for given `id` + `user_id`; optionally saves as draft
- `plugin.schedule-update` — updates `send_at` for given `id` + `user_id` (reschedule)
- `plugin.schedule-edit` — deserializes Mail_mime, saves as draft in IMAP Drafts folder, cancels scheduled, returns draft UID for compose redirect

### PHP — `lib/ScheduleService.php`

Encapsulates all DB operations:

```php
class ScheduleService {
    private $db;       // rcube_db instance
    private $table;    // prefixed table name
    private $crypto;   // queue payload encryption helper

    public function __construct(rcube_db $db) { ... }
    public function ensure_schema(): void { ... }       // CREATE TABLE IF NOT EXISTS
    public function enqueue(...): int { ... }            // INSERT, returns id
    public function list_pending(int $user_id): array { ... }
    public function cancel(int $id, int $user_id): bool { ... }
    public function update_send_at(int $id, int $user_id, string $send_at): bool { ... }
    public function claim_next(): ?array { ... }         // atomic UPDATE returning row
    public function mark_sent(int $id): void { ... }
    public function mark_error(int $id, string $error): void { ... }
    public function get_message(int $id, int $user_id): ?array { ... }
    public function cron_health_check(): int { ... }     // timestamp of last successful send
     public function encrypt_payload(array $payload): array { ... }
     public function decrypt_payload(array $row): array { ... }
}
```

**`ensure_schema()`** — called once on first use. Runs the appropriate `SQL/*.initial.sql` file for the detected DB driver (`$this->db->db_provider`). Uses a Roundcube preference flag (`undo_send_schema_version`) to track whether the schema is initialized, avoiding repeated checks.

### Security Model

1. **Installation key outside the DB**
    - Add a required config key such as `$config['undo_send_schedule_key']`
    - Value is a 32-byte base64 key generated once during setup
    - Stored in `config.inc.php` or an environment variable injected into Roundcube, never in the queue table

2. **AEAD encryption for queued payloads**
    - Use `sodium_crypto_aead_xchacha20poly1305_ietf_*` when available
    - Fallback to OpenSSL AES-256-GCM only if Sodium is unavailable
    - Associated data includes `user_id`, `message_uid`, `send_at`, and `key_version` to prevent row swapping

3. **Delivery profile IDs, not credential snapshots**
    - Queue rows store only `delivery_profile = 'default' | 'relay' | 'local_mta'`
    - The actual host, auth method, username, password, client cert, or API token lives only in server config / environment

4. **Profile-level sender enforcement**
    - Before enqueue and before delivery, validate that `address_from` belongs to the user's allowed identities
    - Optionally restrict each delivery profile to an allowlist of sender domains

5. **Retention minimization**
    - Successful or cancelled rows are deleted after a short retention window
    - Error rows can be retained longer for debugging, but payload can be stripped after max retries

### PHP — `cron.php`

```php
#!/usr/bin/env php
<?php
/**
 * Undo Send – Cron entry point
 *
 * Run via system crontab: * * * * * php /path/to/plugins/undo_send/cron.php
 *
 * Bootstraps Roundcube, claims pending messages, delivers via SMTP.
 */

// Prevent web access
if (php_sapi_name() !== 'cli') {
    die('CLI only');
}

// Bootstrap Roundcube
define('INSTALL_PATH', realpath(__DIR__ . '/../../') . '/');
require_once INSTALL_PATH . 'program/include/clisetup.php';

// Load plugin's schedule service
require_once __DIR__ . '/lib/ScheduleService.php';

$rcmail   = rcmail::get_instance();
$db       = $rcmail->get_dbh();
$service  = new ScheduleService($db);
$max_retries = (int) $rcmail->config->get('undo_send_schedule_max_retries', 3);

// Process queue — one message per iteration to keep cron runs fast
while ($row = $service->claim_next()) {
    $payload = $service->decrypt_payload($row);
    $mail_mime = $payload['message'] ?? null;

    if (!$mail_mime || !($mail_mime instanceof Mail_mime)) {
        $service->mark_error($row['id'], 'Failed to deserialize Mail_mime');
        continue;
    }

    // Resolve server-owned delivery profile from config/env, not DB secrets
    $profile = $rcmail->config->get('undo_send_schedule_delivery_profiles', []);
    $selected_profile = $profile[$row['delivery_profile']] ?? null;
    if (!$selected_profile) {
        $service->mark_error($row['id'], 'Missing delivery profile');
        continue;
    }

    $error = '';
    $result = $rcmail->deliver_message(
        $mail_mime,
        $row['address_from'],
        $payload['mailto'],
        $error
    );

    if ($result) {
        $service->mark_sent($row['id']);
    } else {
        if ($row['retry_count'] + 1 >= $max_retries) {
            $service->mark_error($row['id'], $error ?: 'Delivery failed after max retries');
        } else {
            $service->mark_error($row['id'], $error ?: 'Delivery failed');
            // Reset to pending for retry on next cron run
            $service->reset_for_retry($row['id']);
        }
    }
}
```

**SQLite-specific runtime rule:** acquire the cron `flock()` before any queue query or claim attempt. If the lock cannot be acquired, exit successfully without processing.

### JavaScript — `undo_send.js` Extensions

**Split Button injection (when `schedule_enabled = true`):**

The JS transforms the existing `<button class="btn btn-primary send">` into:

```html
<div class="btn-group mp-split-send">
    <button class="btn btn-primary send" command="send">Send</button>
    <button class="btn btn-primary dropdown-toggle dropdown-toggle-split mp-schedule-toggle"
            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="sr-only">Schedule options</span>
    </button>
    <div class="dropdown-menu dropdown-menu-right mp-schedule-dropdown">
        <h6 class="dropdown-header">Schedule send</h6>
        <div class="dropdown-divider"></div>
        <!-- Quick picks inserted dynamically -->
        <a class="dropdown-item mp-quick-pick" data-time="today-evening">
            <span class="mp-pick-icon">🌆</span>
            <span class="mp-pick-label">Later today</span>
            <span class="mp-pick-time">6:00 PM</span>
        </a>
        <!-- ... more quick picks ... -->
        <div class="dropdown-divider"></div>
        <a class="dropdown-item mp-custom-pick">
            <span class="mp-pick-icon">🗓️</span>
            <span class="mp-pick-label">Pick date & time…</span>
        </a>
        <!-- Inline datetime picker (initially hidden) -->
        <div class="mp-schedule-picker" style="display:none">
            <div class="px-3 py-2">
                <label class="form-label">Date & Time</label>
                <input type="datetime-local" class="form-control mp-schedule-datetime" />
                <div class="mt-2 d-flex gap-2">
                    <button class="btn btn-primary btn-sm mp-schedule-confirm">Schedule</button>
                    <button class="btn btn-secondary btn-sm mp-schedule-picker-cancel">Cancel</button>
                </div>
            </div>
        </div>
    </div>
</div>
```

**Key JS methods (added to `undoSend` object):**

```javascript
// Schedule-specific
initSchedule()          // Build split button, bind events
buildQuickPicks()       // Calculate smart times, populate dropdown
handleQuickPick(slot)   // Schedule with pre-defined time
handleCustomPick()      // Show/hide datetime-local picker
confirmSchedule(dt)     // POST _schedule_at to server, handle response
showScheduledToast(dt)  // "Message scheduled for..." toast

// Scheduled messages panel
openSchedulePanel()     // AJAX fetch + render list
cancelScheduled(id)     // Cancel with confirmation
reschedule(id)          // Open picker pre-filled with current time
editScheduled(id)       // Open compose with message content
```

**Scheduling flow:**
1. User clicks quick-pick or custom picker → `confirmSchedule(isoDateString)` called
2. JS sets a hidden input `_schedule_at` in the compose form
3. JS calls `rcmail.command('send')` with `undoSend.bypassing = true` (bypasses undo countdown)
4. Server-side `intercept_for_schedule` hook fires, aborts SMTP, stores in DB
5. Roundcube returns success → JS shows scheduled toast → redirect to Inbox

### Config — `config.inc.php.dist` Extensions

```php
// ── Schedule Send (optional) ───────────────────────────────────────────────
// Enable scheduled send feature. Requires database and cron job.
// Default: false (undo-send only, no DB needed)
$config['undo_send_schedule_enabled'] = false;

// Required 32-byte base64 queue encryption key. Generate once and keep outside the DB.
// Example generation: php -r "echo base64_encode(random_bytes(32)), PHP_EOL;"
// Default: null (feature must refuse to enable without a key)
$config['undo_send_schedule_key'] = null;

// Key version for future rotation support.
$config['undo_send_schedule_key_version'] = 'v1';

// Delivery mode/profile used by cron. Queue rows store only the profile name.
// Supported profile styles:
// - local_mta: local sendmail/postfix submission with no per-user secret
// - relay: authenticated relay account stored only in server config/env
$config['undo_send_schedule_delivery_profile'] = 'default';

// Named delivery profiles. Secrets belong here or in env vars, never in the DB.
$config['undo_send_schedule_delivery_profiles'] = [
    'default' => [
        'mode' => 'local_mta',
    ],
];

// Default quick-pick time slots (24h format, user's local timezone)
$config['undo_send_schedule_morning']   = '08:00';
$config['undo_send_schedule_afternoon'] = '13:00';
$config['undo_send_schedule_evening']   = '18:00';

// Maximum number of delivery retries before marking as error
$config['undo_send_schedule_max_retries'] = 3;

// Maximum days in the future a message can be scheduled
$config['undo_send_schedule_max_days'] = 90;
```

### Localization — `en_US.inc` Extensions

```php
// Schedule Send
$labels['schedule_send']           = 'Schedule send';
$labels['schedule_later_today']    = 'Later today';
$labels['schedule_tomorrow_am']    = 'Tomorrow morning';
$labels['schedule_tomorrow_pm']    = 'Tomorrow afternoon';
$labels['schedule_next_monday']    = 'Monday morning';
$labels['schedule_pick_datetime']  = 'Pick date & time…';
$labels['schedule_confirm']        = 'Schedule';
$labels['schedule_cancel']         = 'Cancel';
$labels['schedule_datetime_label'] = 'Date & Time';
$labels['schedule_scheduled_at']   = 'Message scheduled for $t.';
$labels['schedule_view']           = 'View scheduled';
$labels['schedule_folder']         = 'Scheduled';

// Scheduled messages list
$labels['schedule_empty']          = 'No scheduled messages.';
$labels['schedule_cancel_send']    = 'Cancel send';
$labels['schedule_reschedule']     = 'Reschedule';
$labels['schedule_edit_message']   = 'Edit message';
$labels['schedule_cancel_confirm'] = 'Cancel this scheduled message?';
$labels['schedule_cancelled']      = 'Scheduled message cancelled.';
$labels['schedule_updated']        = 'Message rescheduled for $t.';
$labels['schedule_error']          = 'Failed to schedule message.';
$labels['schedule_past_time']      = 'Please select a future date and time.';
$labels['schedule_cron_warning']   = 'Scheduling is unavailable — cron job is not running.';

// Settings
$labels['schedule_default_time']   = 'Default schedule time';
$labels['schedule_time_morning']   = 'Morning ($t)';
$labels['schedule_time_afternoon'] = 'Afternoon ($t)';
$labels['schedule_time_evening']   = 'Evening ($t)';
```

---

## Files Changed

### New Files
| File | Purpose |
|------|---------|
| `plugins/scheduled_send/lib/ScheduleService.php` | DB operations — enqueue, list, cancel, claim, deliver |
| `plugins/scheduled_send/cron.php` | CLI cron entry point — claims and delivers pending messages |
| `plugins/scheduled_send/SQL/mysql.initial.sql` | MySQL schema for `scheduled_send_queue` table |
| `plugins/scheduled_send/SQL/postgres.initial.sql` | PostgreSQL schema |
| `plugins/scheduled_send/SQL/sqlite.initial.sql` | SQLite schema |

### Modified Files
| File | Changes |
|------|---------|
| `plugins/scheduled_send/scheduled_send.php` | Main plugin class — schedule hooks, AJAX actions |
| `plugins/scheduled_send/scheduled_send.js` | Split button UI, dropdown menu, datetime picker, schedule AJAX, scheduled messages panel |
| `plugins/scheduled_send/config.inc.php.dist` | New `scheduled_send_*` config keys |
| `plugins/scheduled_send/composer.json` | Plugin metadata |
| `plugins/scheduled_send/localization/en_US.inc` | Schedule-related labels |
| `plugins/scheduled_send/skins/default/scheduled_send.css` | Split button, dropdown, popover baseline styles |
| `plugins/scheduled_send/skins/elastic/scheduled_send.css` | Elastic overrides for schedule UI |

---

## Dark Mode Considerations

- The split send button inherits elastic's existing `.btn-primary` dark mode styles
- The dropdown menu uses Bootstrap's `.dropdown-menu` which elastic already themes for dark mode
- The datetime picker (`<input type="datetime-local">`) inherits OS-level dark mode styling automatically
- Custom classes (`.mp-schedule-dropdown`, `.mp-schedule-picker`, `.mp-quick-pick`) use inherited text/background colors from the dropdown context
- No hardcoded colors in plugin CSS — everything uses the skin's existing color variables/tokens
- The "Scheduled" folder entry in the sidebar inherits the mailbox list styling (already dark-mode aware)

---

## Validation Criteria

### Functional
- [ ] When `schedule_enabled = false`: plugin behaves identically to v0.1.0 (undo-send only, no split button, no DB)
- [ ] When `schedule_enabled = true`: Send button becomes a split button group
- [ ] Clicking "Send" (left side) triggers normal send with undo countdown
- [ ] Clicking "▾" (right side) opens the schedule dropdown
- [ ] Quick picks show correct times based on current day/time
- [ ] "Later today" is hidden after 4:00 PM
- [ ] "Monday morning" is hidden on Sunday and Monday
- [ ] "Pick date & time…" reveals the inline datetime picker
- [ ] Selecting a past time shows an error toast
- [ ] Scheduling a message stores it in `undo_send_schedule` with `status = 'pending'`
- [ ] Scheduling a message stores only encrypted payload + metadata, not SMTP credentials
- [ ] Toast shows "Message scheduled for [date]. [View scheduled]" after scheduling
- [ ] Compose window closes after scheduling (redirect to Inbox)
- [ ] Clicking "Scheduled" in the sidebar lists pending messages
- [ ] Cancel removes the message from the queue (sets `status = 'cancelled'`)
- [ ] Reschedule updates `send_at` for the message
- [ ] Edit opens compose with the message content pre-filled
- [ ] Cron job delivers pending messages whose `send_at` has passed
- [ ] Cron marks delivered messages as `status = 'sent'`
- [ ] Failed deliveries retry up to `max_retries` times
- [ ] Settings shows "Default schedule time" preference when scheduling is enabled

### Database
- [ ] Schema creates cleanly on MySQL 5.7+ / 8.0+
- [ ] Schema creates cleanly on PostgreSQL 12+
- [ ] Schema creates cleanly on SQLite 3.x
- [ ] Atomic claim query works without race conditions on all three backends
- [ ] `ensure_schema()` is idempotent (safe to call multiple times)
- [ ] Queue rows cannot be decrypted if copied without the installation key
- [ ] Associated-data tampering causes decrypt failure
- [ ] SQLite cron acquires an exclusive `flock()` before processing due messages
- [ ] When a second SQLite cron starts while one is already running, it exits without sending anything

### Cross-Skin
- [ ] Split button renders correctly in elastic
- [ ] Split button renders correctly in larry-based skins
- [ ] Split button renders correctly in stratus
- [ ] Dropdown menu is positioned correctly (no overflow, no clipping)
- [ ] Dark mode renders correctly in elastic and stratus

### Edge Cases
- [ ] Scheduling with undo_send_delay = 0 (disabled) still works (bypasses countdown)
- [ ] Scheduling with large attachments (verify Mail_mime serialization handles them)
- [ ] Multiple scheduled messages from the same user
- [ ] Concurrent cron executions don't double-deliver (atomic claim)
- [ ] Network error during schedule POST shows appropriate error toast
- [ ] Cron running with no messages in queue exits cleanly
- [ ] SQLite single-host deployment works correctly with repeated cron invocations every minute

### Security
- [ ] Feature refuses to enable if `undo_send_schedule_key` is missing or invalid
- [ ] Queue rows never contain SMTP password, SMTP username snapshot, OAuth token, or `server_config` blob
- [ ] Cron resolves credentials only from server config/environment
- [ ] Delivered/cancelled rows are purged according to retention policy
- [ ] Key rotation path exists via `key_version` and dual-key decrypt support

---

## Risks / Open Questions

1. **Mail_mime serialization size** — Emails with large attachments will produce large BLOBs. Should we set a maximum attachment size for scheduled messages, or rely on the DB's native BLOB limit? (MySQL LONGBLOB = 4GB, Postgres BYTEA = 1GB, SQLite BLOB = 2GB — all far exceed typical email size limits.)

2. **Per-user SMTP auth environments** — If the deployment requires each user to authenticate to SMTP with their own secret, this design intentionally does **not** support scheduled send by replaying that secret later. **Mitigation:** require a relay/service profile, local MTA, or a future broker-based token exchange. **Recommendation:** do not add DB-backed credential snapshots.

3. **SQLite concurrency limits** — SQLite is safe here only with a single cron worker and single host. **Mitigation:** require `flock()` in `cron.php` and document SQLite as a single-host option, not a clustered queue backend.

4. **Cron reliability** — If the cron job isn't running, scheduled messages silently pile up. **Mitigation:** `cron_health_check()` stores last-run timestamp in a system preference; the compose UI checks this and shows a warning banner if cron hasn't run in > 2 minutes.

5. **Sent folder copy** — After cron delivers, the message should be saved to the user's Sent folder. However, cron doesn't have IMAP access (no user session). **Options:** (a) Save to Sent on the user's next login (like xemail_schedule), (b) store IMAP credentials for the cron job, (c) skip Sent folder copy. **Recommendation:** option (a) — set a `needs_sent_copy` flag on delivered rows; when the user next loads the mail task, copy any flagged messages to Sent and clear the flag.

6. **Key management UX** — Admins must generate, store, back up, and rotate the installation key. **Mitigation:** document setup clearly and provide a one-line generation command.

7. **Plugin rename?** — Adding scheduling to a plugin called `undo_send` may be confusing. Consider renaming to `send_tools` or `mail_send` in a future version. For now, keep `undo_send` since the user explicitly requested adding schedule to this plugin.

---

## Appendix: xemail_schedule Comparison

| Aspect | xemail_schedule | Our approach |
|--------|----------------|--------------|
| **Dependencies** | xframework (commercial) | None |
| **License** | Commercial (Roundcube Plus) | GPL-3.0-or-later |
| **SQLite support** | ❌ No (`FOR UPDATE`) | ✅ Yes, single-host only with `flock()` + single cron worker |
| **UI** | Sidebar dropdown + modal dialog | Gmail-style split button + popover |
| **Datetime picker** | flatpickr (via xframework) | Native `<input type="datetime-local">` |
| **SMTP credentials** | Optional per-row storage | Never stored per-row; server-owned profile only |
| **Sent folder** | Copy on next login | Copy on next login (same) |
| **Cron health check** | Last-run check, disables UI | Same approach |
| **Message storage** | Queue blob + optional server config snapshot | Encrypted queue payload + key version |
| **Quick picks** | None (only time selector) | Gmail-style smart suggestions |
| **Undo send integration** | Separate feature (countdown overlay) | Unified plugin (undo + schedule) |
