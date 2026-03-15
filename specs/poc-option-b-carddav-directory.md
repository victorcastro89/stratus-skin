# POC Spec — Option B: Per-Domain Global Directory via CardDAV IDirectory

## Goal

Every user belonging to a domain (e.g. `@company1.com`) automatically sees a
**read-only, searchable** domain directory in Roundcube — similar to a global LDAP
address book. The directory is backed by a custom sabre/dav server that implements
`Sabre\CardDAV\IDirectory`. No Davis. No third-party DAV server.

This is fundamentally different from Option A: the directory is **not a personal or
shared writable address book** — it is a curated, centrally-managed read-only contact
store exposed as a CardDAV Directory extension understood by iOS, macOS, and
RCMCardDAV.

---

## How It Works

### Core idea

sabre/dav's `Sabre\CardDAV\IDirectory` interface marks a CardDAV address book node as
a **global directory**. The `Sabre\CardDAV\Plugin::$directories` property tells the
server where these directories live so clients can discover them.

One directory node is created per domain. Each node:

- Implements `IDirectory` (marks it as a directory)
- Implements `Sabre\DAVACL\IACL` (restricts access by domain via ACL)
- Is registered in `Plugin::$directories`
- Is backed by the standard sabre/dav CardDAV PDO backend under a dedicated principal

RCMCardDAV mounts the directory using an `extra_addressbooks` preset pointing directly
at the directory URL, authenticated as a read-only service account.

```
victor@company1.com  ──►  /directories/company1.com/  ──►  company1 contacts (read-only)
alice@company1.com   ──►  /directories/company1.com/  ──►  company1 contacts (read-only)
bob@company2.com     ──►  /directories/company2.com/  ──►  company2 contacts (read-only)
```

---

## System Architecture

```
┌──────────────────────────────────────────────────────────┐
│  Roundcube + RCMCardDAV plugin                           │
│                                                          │
│  Admin preset in carddav/config.inc.php                  │
│  extra_addressbooks url: https://dav.example.com/        │
│                          directories/%d/                 │
│  auth: reader@%d / <reader_password>                     │
└──────────────────────┬───────────────────────────────────┘
                       │ HTTP/S  CardDAV
                       ▼
┌──────────────────────────────────────────────────────────┐
│  Custom sabre/dav server  (dav.example.com)              │
│                                                          │
│  server.php                                              │
│  ├─ Auth:       IMAP for real users + static reader@*    │
│  ├─ Principals: PDO backend                              │
│  ├─ CardDAV:    PDO backend                              │
│  └─ Tree:                                                │
│      /principals/                                        │
│      /addressbooks/         ← personal books             │
│      /directories/                                       │
│          company1.com/      ← DomainDirectory node       │
│          company2.com/      ← DomainDirectory node       │
└──────────────────────────────────────────────────────────┘
                       │
                       ▼
                SQLite / MySQL DB
```

---

## Directory Structure

```
sabre-directory-dav/
├── composer.json
├── server.php
├── bootstrap.php               # DB init + directory principal seeding
├── src/
│   ├── Auth/
│   │   └── Backend.php         # IMAP for real users + static for reader@*
│   ├── CardDAV/
│   │   ├── DirectoryRoot.php   # DAV collection: lists all DomainDirectory nodes
│   │   └── DomainDirectory.php # single domain directory node (IDirectory + IACL)
└── vendor/                     # managed by composer
```

---

## Implementation

### composer.json

```json
{
    "name": "yourorg/directory-dav",
    "description": "Per-domain read-only CardDAV directories",
    "require": {
        "sabre/dav": "~4.6.0"
    },
    "autoload": {
        "psr-4": {
            "DirDav\\": "src/"
        }
    }
}
```

Run `composer install`.

---

### src/Auth/Backend.php

Identical in structure to Option A. Validates real users via IMAP and `reader@domain`
service accounts against a static map.

```php
<?php

namespace DirDav\Auth;

use Sabre\DAV\Auth\Backend\AbstractBasic;

class Backend extends AbstractBasic {

    private array $readerAccounts; // domain => password
    private array $imapConfig;

    public function __construct(array $readerAccounts, array $imapConfig) {
        $this->readerAccounts = $readerAccounts;
        $this->imapConfig     = $imapConfig;
    }

    protected function validateUserPass(string $username, string $password): bool {
        [$local, $domain] = array_pad(explode('@', $username, 2), 2, '');

        if ($local === 'reader' && isset($this->readerAccounts[$domain])) {
            return hash_equals($this->readerAccounts[$domain], $password);
        }

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
        if ($conn) { imap_close($conn); return true; }
        return false;
    }
}
```

---

### src/CardDAV/DomainDirectory.php

This is the central custom class. It wraps a standard sabre/dav address book node and
layers on `IDirectory` and `IACL` so the CardDAV plugin treats it as a global
searchable directory.

```php
<?php

namespace DirDav\CardDAV;

use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\CardDAV\AddressBook;
use Sabre\CardDAV\IDirectory;
use Sabre\DAVACL\IACL;

/**
 * A per-domain read-only CardDAV directory.
 *
 * Extends the standard AddressBook node and adds:
 *  - IDirectory  → tells the CardDAV plugin this is a global directory
 *  - IACL        → restricts access to principals matching the domain
 */
class DomainDirectory extends AddressBook implements IDirectory, IACL {

    private string $domain;

    public function __construct(
        BackendInterface $carddavBackend,
        array $addressBookInfo,
        string $domain
    ) {
        parent::__construct($carddavBackend, $addressBookInfo);
        $this->domain = $domain;
    }

    // ── IACL ─────────────────────────────────────────────────────────────────

    public function getOwner(): ?string {
        // The directory is owned by the domain's reader principal
        return 'principals/reader@' . $this->domain;
    }

    public function getGroup(): ?string {
        return null;
    }

    /**
     * ACL: any authenticated principal whose email matches the domain gets
     * read access.  The reader@ service account also gets read access so
     * RCMCardDAV's preset credentials work.
     */
    public function getACL(): array {
        return [
            // Owner (reader service account) — full read
            [
                'privilege' => '{DAV:}read',
                'principal' => 'principals/reader@' . $this->domain,
                'protected' => true,
            ],
            // All authenticated principals — read (enforced at the server level;
            // refine to domain-only by overriding checkPrivileges() if needed)
            [
                'privilege' => '{DAV:}read',
                'principal' => '{DAV:}authenticated',
                'protected' => true,
            ],
        ];
    }

    public function setACL(array $acl): void {
        throw new \Sabre\DAV\Exception\Forbidden('ACL is read-only');
    }

    public function getSupportedPrivilegeSet(): ?array {
        // null = use sabre/dav default privilege set
        return null;
    }

    // ── Read-only guard ───────────────────────────────────────────────────────

    public function createFile(string $name, $data = null): string {
        throw new \Sabre\DAV\Exception\Forbidden('This directory is read-only');
    }

    public function delete(): void {
        throw new \Sabre\DAV\Exception\Forbidden('This directory is read-only');
    }
}
```

> **Note on domain-scoped ACL:** The ACL above grants read to all authenticated users.
> To restrict strictly to the matching domain, inject the current principal URI inside a
> custom `Plugin::beforeMethod` hook and compare domains there, or override
> `checkPrivileges()` in a custom DAVACL plugin. For a multi-tenant setup this
> refinement is recommended.

---

### src/CardDAV/DirectoryRoot.php

A simple `DAV\Collection` that lists all domain directory nodes under `/directories/`.

```php
<?php

namespace DirDav\CardDAV;

use Sabre\DAV\Collection;
use Sabre\CardDAV\Backend\BackendInterface;

class DirectoryRoot extends Collection {

    private BackendInterface $cardDavBackend;
    private array $domains;  // ['company1.com', 'company2.com']

    public function __construct(BackendInterface $cardDavBackend, array $domains) {
        $this->cardDavBackend = $cardDavBackend;
        $this->domains        = $domains;
    }

    public function getName(): string {
        return 'directories';
    }

    public function getChildren(): array {
        $nodes = [];
        foreach ($this->domains as $domain) {
            $principal = 'principals/reader@' . $domain;

            // Fetch address books for the reader principal
            $books = $this->cardDavBackend->getAddressBooksForUser($principal);

            foreach ($books as $book) {
                if ($book['uri'] === 'directory') {
                    $nodes[] = new DomainDirectory(
                        $this->cardDavBackend,
                        $book,
                        $domain
                    );
                }
            }
        }
        return $nodes;
    }

    public function getChild(string $name): \Sabre\DAV\INode {
        foreach ($this->getChildren() as $child) {
            if ($child->getName() === $name) return $child;
        }
        throw new \Sabre\DAV\Exception\NotFound("Directory {$name} not found");
    }
}
```

---

### server.php

```php
<?php

require 'vendor/autoload.php';

// ── Config ────────────────────────────────────────────────────────────────────

$dsn     = 'sqlite:' . __DIR__ . '/data/carddav.sqlite';
$imap    = ['host' => 'mail.example.com', 'port' => 993];
$domains = ['company1.com', 'company2.com'];

// Read-only service account per domain: domain => password
$readerAccounts = [
    'company1.com' => 'reader-secret-company1',
    'company2.com' => 'reader-secret-company2',
];

// ── Backends ──────────────────────────────────────────────────────────────────

$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$authBackend      = new DirDav\Auth\Backend($readerAccounts, $imap);
$principalBackend = new Sabre\DAVACL\PrincipalBackend\PDO($pdo);
$cardDavBackend   = new Sabre\CardDAV\Backend\PDO($pdo);

// ── Tree ──────────────────────────────────────────────────────────────────────

$tree = [
    new Sabre\CalDAV\Principal\Collection($principalBackend),
    new Sabre\CardDAV\AddressBookRoot($principalBackend, $cardDavBackend),
    new DirDav\CardDAV\DirectoryRoot($cardDavBackend, $domains),
];

// ── Server ────────────────────────────────────────────────────────────────────

$server = new Sabre\DAV\Server($tree);
$server->setBaseUri('/');

$server->addPlugin(new Sabre\DAV\Auth\Plugin($authBackend));
$server->addPlugin(new Sabre\DAV\Browser\Plugin());

$aclPlugin = new Sabre\DAVACL\Plugin();
$server->addPlugin($aclPlugin);

$cardDavPlugin = new Sabre\CardDAV\Plugin();

// Register each domain directory path — required for client discovery
foreach ($domains as $domain) {
    $cardDavPlugin->directories[] = 'directories/' . $domain;
}

$server->addPlugin($cardDavPlugin);

$server->exec();
```

---

### bootstrap.php — seed reader principals and directory address books

```php
<?php

require 'vendor/autoload.php';

$dsn     = 'sqlite:' . __DIR__ . '/data/carddav.sqlite';
$domains = ['company1.com', 'company2.com'];

$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Run sabre/dav SQL schema first:
//   vendor/sabre/dav/examples/sql/sqlite.principals.sql
//   vendor/sabre/dav/examples/sql/sqlite.addressbooks.sql

foreach ($domains as $domain) {
    $principal = "principals/reader@{$domain}";

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM principals WHERE uri = ?");
    $stmt->execute([$principal]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->prepare(
            "INSERT INTO principals (uri, displayname, email) VALUES (?, ?, ?)"
        )->execute([$principal, "Directory ({$domain})", "reader@{$domain}"]);
        echo "Created principal: {$principal}\n";
    }

    // Address book uri must be 'directory' — matched in DirectoryRoot::getChildren()
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM addressbooks WHERE principaluri = ? AND uri = 'directory'"
    );
    $stmt->execute([$principal]);
    if ((int)$stmt->fetchColumn() === 0) {
        $pdo->prepare(
            "INSERT INTO addressbooks
                (principaluri, displayname, uri, description, synctoken)
             VALUES (?, ?, 'directory', ?, 1)"
        )->execute([
            $principal,
            ucfirst(explode('.', $domain)[0]) . ' Directory',
            "Global read-only directory for {$domain}",
        ]);
        echo "Created directory: {$principal}/directory\n";
    }
}

echo "Done.\n";
```

---

## Populating the Directory

The directory address book is managed like any other PDO-backed CardDAV address book.
Contacts are vCards stored in the `cards` table. Population options:

**Option 1 — Admin writes directly via CardDAV client**
Connect as `reader@company1.com` in any CardDAV client (e.g. Thunderbird, macOS
Contacts) and add/edit contacts. The address book is writable for the `reader` account
even though it is read-only for everyone else (enforced by `DomainDirectory::createFile`
for unauthenticated callers — you can relax this for `reader` by checking the current
principal in the override).

**Option 2 — Import from LDAP / HR system**
Write a CLI import script that reads from your LDAP or HR API and upserts vCards
directly into the DB using `$cardDavBackend->createCard()` and
`$cardDavBackend->updateCard()`.

**Option 3 — Sync from Roundcube "Collected recipients"**
If Roundcube collects sent-to addresses, a cron job can mirror those into the directory
address book via the same backend methods.

---

## RCMCardDAV Configuration

In `plugins/carddav/config.inc.php`:

```php
<?php

$prefs['DomainDirectory'] = [
    'accountname' => 'Company Directory',
    'username'    => 'reader@%d',
    'password'    => 'reader-secret',   // same password for all reader@* accounts
    'discovery_url' => null,
    'extra_addressbooks' => [
        [
            'url'      => 'https://dav.example.com/directories/%d/',
            'readonly' => true,
            'active'   => true,
            'require_always_email' => true,  // only show contacts with email addresses
            'fixed'    => ['url', 'readonly', 'active'],
        ],
    ],
    'fixed' => ['username', 'password'],
    'hide'  => true,
];
```

---

## End-User Experience

1. User logs in to Roundcube — "Company Directory" appears automatically in Contacts.
2. The address book is **read-only** — no add/edit/delete for regular users.
3. When composing an email, auto-complete searches this directory alongside personal
   contacts.
4. On iOS/macOS (native Contacts app), the directory appears as a searchable global
   address book (same behaviour as an LDAP directory).
5. An admin updates a contact in the directory → all users see the change on next sync.

---

## Key Differences from Option A

| | Option A (Shared Account) | Option B (IDirectory) |
|---|---|---|
| Writable by users | Yes (optional) | No — read-only for all |
| Who manages contacts | Any domain user | Admin / import script only |
| Suitable for | Team-maintained shared contacts | Central directory (HR, LDAP mirror) |
| Custom PHP classes needed | No | Yes (`DomainDirectory`, `DirectoryRoot`) |
| iOS/macOS native search | No | Yes (`IDirectory` extension) |
| sabre/dav PDO backend | Standard, unmodified | Standard, unmodified |

---

## Pros and Cons

| | |
|---|---|
| **Pros** | Native iOS/macOS directory search experience |
| | Clear read-only semantics — users cannot accidentally corrupt shared data |
| | Backed by standard sabre/dav PDO — no exotic storage |
| | Works with any CardDAV client supporting the directory extension |
| **Cons** | Requires two custom PHP classes (`DomainDirectory`, `DirectoryRoot`) |
| | Directory content must be managed by an admin or import process |
| | Domain-scoped ACL requires additional hardening (see note in `DomainDirectory`) |
| | Not suitable if users need to write shared contacts |

---

## Deployment Checklist

- [ ] `composer install` in `sabre-directory-dav/`
- [ ] Create SQLite/MySQL DB and run sabre/dav schema SQL files
- [ ] Run `php bootstrap.php` to seed reader principals and directory address books
- [ ] Configure Nginx/Apache to route `dav.example.com` → `server.php`
- [ ] Enable PHP `imap` extension
- [ ] Populate directory contacts (admin CardDAV client or import script)
- [ ] Add `$prefs['DomainDirectory']` to `plugins/carddav/config.inc.php`
- [ ] Test: log in as `victor@company1.com` → verify "Company Directory" appears
- [ ] Test: search for a contact in Roundcube compose → verify directory results appear
- [ ] Test on iOS: Settings → Contacts → Accounts → add CardDAV → verify directory searchable
