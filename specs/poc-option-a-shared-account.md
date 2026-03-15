# POC Spec — Option A: Per-Domain Shared Address Book via Service Account

## Goal

Every user belonging to a domain (e.g. `@company1.com`) automatically sees a shared
address book in Roundcube. Contacts and groups written to that book by any authorised
user are visible to all other users of the same domain. No Davis. No third-party DAV
server. A minimal custom sabre/dav server owns the data.

---

## How It Works

### Core idea

A dedicated **service principal** is created per domain — e.g. `shared@company1.com`.
That principal owns one CardDAV address book called `shared`. Its credentials are
static and known only to the administrator.

RCMCardDAV is configured with an **admin preset** that uses the `%d` placeholder
(domain part of the logged-in user's email). For every user that logs in to Roundcube,
RCMCardDAV silently mounts the corresponding shared address book using the service
account credentials — no user interaction required.

```
victor@company1.com  ──►  connects as shared@company1.com  ──►  /addressbooks/shared@company1.com/shared/
alice@company1.com   ──►  connects as shared@company1.com  ──►  /addressbooks/shared@company1.com/shared/
bob@company2.com     ──►  connects as shared@company2.com  ──►  /addressbooks/shared@company2.com/shared/
```

Users never see or interact with the service account. They only see "Company Contacts"
appear alongside their personal address book in Roundcube.

---

## System Architecture

```
┌─────────────────────────────────────────────────────┐
│  Roundcube + RCMCardDAV plugin                      │
│  (existing installation)                            │
│                                                     │
│  Admin preset in carddav/config.inc.php             │
│  username: shared@%d                                │
│  password: <shared_secret>                          │
│  url:      https://dav.example.com/addressbooks/    │
│            shared@%d/shared/                        │
└────────────────────┬────────────────────────────────┘
                     │ HTTP/S  CardDAV
                     ▼
┌─────────────────────────────────────────────────────┐
│  Custom sabre/dav server  (dav.example.com)         │
│                                                     │
│  server.php                                         │
│  ├─ Auth:      IMAP credentials for real users      │
│  │             + static credentials for shared@*    │
│  ├─ Principals: PDO backend                         │
│  └─ CardDAV:   PDO backend  (SQLite / MySQL)        │
└────────────────────┬────────────────────────────────┘
                     │
                     ▼
              SQLite / MySQL DB
              (principals + address books)
```

---

## Directory Structure

```
sabre-shared-dav/
├── composer.json
├── server.php
├── bootstrap.php             # DB init + shared principal seeding
├── src/
│   └── Auth/
│       └── Backend.php       # custom auth: IMAP for real users, static for shared@*
├── data/
│   └── carddav.sqlite        # or configure MySQL DSN
└── vendor/                   # managed by composer
```

---

## Implementation

### composer.json

```json
{
    "name": "yourorg/shared-dav",
    "description": "Per-domain shared CardDAV address books",
    "require": {
        "sabre/dav": "~4.6.0"
    },
    "autoload": {
        "psr-4": {
            "SharedDav\\": "src/"
        }
    }
}
```

Run `composer install` after creating this file.

---

### src/Auth/Backend.php

Handles two classes of user:

- **Real users** (`victor@company1.com`) — validated against IMAP.
- **Service accounts** (`shared@company1.com`) — validated against a static map in config.

```php
<?php

namespace SharedDav\Auth;

use Sabre\DAV\Auth\Backend\AbstractBasic;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Backend extends AbstractBasic {

    /** @var array<string,string>  domain => shared_password */
    private array $sharedAccounts;

    /** @var array  IMAP config */
    private array $imapConfig;

    public function __construct(array $sharedAccounts, array $imapConfig) {
        $this->sharedAccounts = $sharedAccounts;
        $this->imapConfig     = $imapConfig;
    }

    protected function validateUserPass(string $username, string $password): bool {
        // Service account login (shared@domain.com)
        [$local, $domain] = array_pad(explode('@', $username, 2), 2, '');
        if ($local === 'shared' && isset($this->sharedAccounts[$domain])) {
            return hash_equals($this->sharedAccounts[$domain], $password);
        }

        // Real user login — validate against IMAP
        return $this->validateImap($username, $password);
    }

    private function validateImap(string $username, string $password): bool {
        $host = $this->imapConfig['host'];
        $port = $this->imapConfig['port'] ?? 993;
        $conn = @imap_open(
            "{{$host}:{$port}/imap/ssl/novalidate-cert}INBOX",
            $username,
            $password,
            OP_HALFOPEN
        );
        if ($conn) {
            imap_close($conn);
            return true;
        }
        return false;
    }
}
```

> **Note:** replace `imap_open` with a socket-based check or your IMAP library if the
> `imap` PHP extension is unavailable.

---

### server.php

```php
<?php

require 'vendor/autoload.php';

// ── Config ────────────────────────────────────────────────────────────────────

$dsn  = 'sqlite:' . __DIR__ . '/data/carddav.sqlite';
$imap = ['host' => 'mail.example.com', 'port' => 993];

// One entry per domain.  Key = domain, value = shared account password.
$sharedAccounts = [
    'company1.com' => 'secret-company1',
    'company2.com' => 'secret-company2',
];

// ── Backends ──────────────────────────────────────────────────────────────────

$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$authBackend      = new SharedDav\Auth\Backend($sharedAccounts, $imap);
$principalBackend = new Sabre\DAVACL\PrincipalBackend\PDO($pdo);
$cardDavBackend   = new Sabre\CardDAV\Backend\PDO($pdo);

// ── Tree ──────────────────────────────────────────────────────────────────────

$tree = [
    new Sabre\CalDAV\Principal\Collection($principalBackend),
    new Sabre\CardDAV\AddressBookRoot($principalBackend, $cardDavBackend),
];

// ── Server ────────────────────────────────────────────────────────────────────

$server = new Sabre\DAV\Server($tree);
$server->setBaseUri('/');

$server->addPlugin(new Sabre\DAV\Auth\Plugin($authBackend));
$server->addPlugin(new Sabre\DAV\Browser\Plugin());
$server->addPlugin(new Sabre\DAVACL\Plugin());
$server->addPlugin(new Sabre\CardDAV\Plugin());

$server->exec();
```

---

### bootstrap.php — seed shared principals and address books

Run once (or on each new domain addition) from CLI:

```php
<?php

require 'vendor/autoload.php';

$dsn = 'sqlite:' . __DIR__ . '/data/carddav.sqlite';
$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create sabre/dav schema (runs only if tables don't exist)
$schemaFiles = [
    __DIR__ . '/vendor/sabre/dav/lib/DAVACL/xml/principal.xml',
    __DIR__ . '/vendor/sabre/dav/lib/CardDAV/xml/addressbooks.xml',
];
// sabre/dav ships SQL files — run them via your migration tool or manually.
// For SQLite, the files are at:
//   vendor/sabre/dav/examples/sql/sqlite.principals.sql
//   vendor/sabre/dav/examples/sql/sqlite.addressbooks.sql

$domains = ['company1.com', 'company2.com'];

foreach ($domains as $domain) {
    $principal = "principals/shared@{$domain}";

    // Insert principal if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM principals WHERE uri = ?");
    $stmt->execute([$principal]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->prepare("INSERT INTO principals (uri, displayname, email) VALUES (?, ?, ?)")
            ->execute([$principal, "Shared ({$domain})", "shared@{$domain}"]);
        echo "Created principal: {$principal}\n";
    }

    // Create shared address book for the principal
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM addressbooks WHERE principaluri = ? AND uri = 'shared'");
    $stmt->execute([$principal]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->prepare(
            "INSERT INTO addressbooks (principaluri, displayname, uri, description, synctoken)
             VALUES (?, 'Company Contacts', 'shared', 'Shared domain address book', 1)"
        )->execute([$principal]);
        echo "Created address book: {$principal}/shared\n";
    }
}

echo "Done.\n";
```

---

## RCMCardDAV Configuration

In `plugins/carddav/config.inc.php` on the Roundcube server:

```php
<?php

// Shared domain address book — auto-mounted for every user on login.
// %d is replaced with the domain part of the logged-in user's email.
$prefs['SharedDomainContacts'] = [
    'accountname'   => 'Company Contacts',
    'username'      => 'shared@%d',
    'password'      => 'secret-%d',   // NOTE: see Password Strategy below
    'discovery_url' => null,           // disable discovery — use extra_addressbooks
    'extra_addressbooks' => [
        [
            'url'      => 'https://dav.example.com/addressbooks/shared@%d/shared/',
            'readonly' => false,       // set true for read-only
            'active'   => true,
            'fixed'    => ['url', 'active'],
        ],
    ],
    'fixed' => ['username', 'password'],
    'hide'  => true,   // hide from CardDAV settings UI
];
```

### Password strategy

RCMCardDAV does **not** support per-domain password substitution via `%d`. Two options:

**Option A1 — Same password for all shared accounts (simplest)**

Set all `shared@*` Davis principals to share one password:

```php
'password' => 'one-shared-secret',
```

**Option A2 — One preset per domain (more secure)**

```php
$prefs['SharedCompany1'] = [
    'username' => 'shared@company1.com',
    'password' => 'secret-company1',
    'extra_addressbooks' => [[
        'url' => 'https://dav.example.com/addressbooks/shared@company1.com/shared/',
    ]],
    // no way to restrict this preset to company1 users only — see Limitation below
];
```

> **Limitation:** RCMCardDAV has no per-domain preset filtering. With multiple presets,
> every user on the Roundcube instance sees every domain's shared book. For a single-
> domain instance or a setup where cross-domain visibility is acceptable, this is fine.
> For strict per-domain isolation, Option A1 (same password + `%d`) is the only clean
> approach.

---

## End-User Experience

1. User logs in to Roundcube — nothing extra to do.
2. "Company Contacts" address book appears in the Contacts sidebar alongside their
   personal address book.
3. Victor adds a contact or group to "Company Contacts".
4. Alice opens Contacts — she sees the same entry on her next RCMCardDAV sync
   (default sync interval: 1 hour; can be set lower via `refresh_time`).
5. If `readonly` is `false`, Alice can also add, edit or delete entries in the shared
   book.

---

## Pros and Cons

| | |
|---|---|
| **Pros** | No custom sabre/dav PHP classes needed — standard PDO backend |
| | Works with any CardDAV client (iOS, macOS, Thunderbird) not just Roundcube |
| | Per-domain isolation via `%d` (with same-password strategy) |
| | Read-only or read/write configurable per preset |
| **Cons** | Not true user-to-user sharing — Victor cannot share *his personal* address book |
| | All domain users see everything in the shared book (no sub-group permissions) |
| | Same password for all shared service accounts is a minor security tradeoff |
| | Workflow change required: shared contacts must be placed in "Company Contacts", not personal book |

---

## Deployment Checklist

- [ ] `composer install` in `sabre-shared-dav/`
- [ ] Create SQLite/MySQL DB and run sabre/dav schema SQL files
- [ ] Run `php bootstrap.php` to seed shared principals and address books
- [ ] Configure Nginx/Apache to route `dav.example.com` → `server.php`
- [ ] Enable PHP `imap` extension (or replace with socket auth)
- [ ] Add `$prefs['SharedDomainContacts']` to `plugins/carddav/config.inc.php`
- [ ] Test: log in as `victor@company1.com` → verify "Company Contacts" appears
- [ ] Test: add a contact as victor → log in as alice → verify contact visible
