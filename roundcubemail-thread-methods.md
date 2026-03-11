# Roundcubemail — Thread-Related Public Methods

> Generated from codebase search of `/roundcubemail/`
> Covers PHP and JavaScript public methods whose names contain "thread".

---

## PHP Methods

### `rcube_storage` — Abstract Base Class
**File:** `program/lib/Roundcube/rcube_storage.php`

| Line | Visibility | Signature |
|------|-----------|-----------|
| 285 | `public` | `set_threading(bool $enable = false): void` |
| 304 | `public` | `get_threading(): bool` |

---

### `rcube_imap` — extends rcube_storage
**File:** `program/lib/Roundcube/rcube_imap.php`

| Line | Visibility | Signature |
|------|-----------|-----------|
| 933  | `protected` | `list_thread_messages(string $folder, int $page, int $slice = 0): array` |
| 953  | `public`    | `threads(string $folder): rcube_result_thread` |
| 980  | `public`    | `threads_direct(string $folder): rcube_result_thread` |
| 1001 | `protected` | `fetch_thread_headers(string $folder, rcube_result_thread $threads, int $page, int $slice = 0): array` |
| 1038 | `protected` | `set_thread_flags(array &$headers, rcube_result_thread $threads): void` |
| 1267 | `protected` | `list_search_thread_messages(string $folder, int $page, int $slice = 0): array` |
| 1558 | `public`    | `thread_index(string $folder = '', ?string $sort_field = null, ?string $sort_order = null): rcube_result_thread` |
| 1585 | `protected` | `sort_threads(rcube_result_thread $threads): rcube_result_thread` |

---

### `rcube_imap_generic` — Low-level IMAP protocol
**File:** `program/lib/Roundcube/rcube_imap_generic.php`

| Line | Visibility | Signature |
|------|-----------|-----------|
| 1953 | `public` | `thread(string $mailbox, string $algorithm = 'REFERENCES', string $criteria = '', bool $return_uid = false, string $encoding = 'US-ASCII'): rcube_result_thread\|false` |

---

### `rcube_imap_cache` — Thread caching layer
**File:** `program/lib/Roundcube/rcube_imap_cache.php`

| Line | Visibility | Signature |
|------|-----------|-----------|
| 282  | `public`  | `get_thread(string $mailbox): rcube_result_thread\|null` |
| 649  | `public`  | `remove_thread(?string $mailbox = null): void` |
| 746  | `private` | `get_thread_row(string $mailbox): array\|null` |
| 810  | `private` | `add_thread_row(string $mailbox, mixed $data, array $mbox_data = [], bool $exists = false): void` |
| 1251 | `private` | `get_thread_data(string $mailbox, array $mbox_data = []): rcube_result_thread` |

---

### `rcube_result_thread` — Thread result/data structure
**File:** `program/lib/Roundcube/rcube_result_thread.php`

| Line | Visibility | Signature |
|------|-----------|-----------|
| 536  | `public`    | `get_thread_data(): array` |
| 550  | `protected` | `build_thread_data(mixed $data, array &$depth, array &$children, int $level = 0): void` |
| 565  | `protected` | `build_thread(array $items, int $level = 1, int &$pos = 0): void` |
| 587  | `protected` | `parse_thread(string $str, int $begin = 0, int $end = 0, int $depth = 0): void` |

---

### `rcube_imap_search` — Search with optional threading
**File:** `program/lib/Roundcube/rcube_imap_search.php`

These methods accept a `$threading` parameter (not named "thread" themselves, but directly relevant):

| Line | Visibility | Signature |
|------|-----------|-----------|
| 65  | `public` | `exec(array $folders, string $str, ?string $charset = null, ?string $sort_field = null, ?bool $threading = null): rcube_result_index\|rcube_result_thread` |
| 167 | `public` | `__construct(string $folder, string $str, ?string $charset = null, ?string $sort_field = null, bool $threading = false)` |

---

## JavaScript Methods

All methods are defined on the global `rcmail` application instance (`this`).
**File:** `program/js/app.js`

### Email Thread UI

| Line | Signature | Description |
|------|-----------|-------------|
| 2451 | `rcmail.calculate_thread_padding(level: number): number` | Returns pixel padding for a thread nesting level |
| 2868 | `rcmail.expand_threads(): void` | Expands all collapsed threads in the message list |
| 2880 | `rcmail.init_threads(roots: Object, mbox: string): void` | Initializes thread expand/collapse indicators |
| 2977 | `rcmail.update_thread_root(uid: string, flag: string): void` | Updates the root/parent row of a thread when flags change |
| 3013 | `rcmail.update_thread(uid: string): void` | Refreshes thread indicators for all messages in a thread |
| 3092 | `rcmail.delete_excessive_thread_rows(): void` | Removes extra thread rows when navigating pages |

### Multi-threaded HTTP (parallel XHR, unrelated to email threads)

| Line | Signature | Description |
|------|-----------|-------------|
| 9425 | `rcmail.multi_thread_http_request(prop: Object): string` | Starts a group of parallel HTTP requests; returns request ID |
| 9456 | `rcmail.multi_thread_send_request(prop: Object, item: any): void` | Sends a single request within a multi-thread group |
| 9486 | `rcmail.multi_thread_http_response(data: any, reqid: string): void` | Handles response callback for a multi-thread request |
| 9521 | `rcmail.multi_thread_request_abort(reqid: string): void` | Aborts all pending requests in a multi-thread group |

---

## Test Coverage

**File:** `tests/Framework/ResultThread.php`

| Line | Method | Description |
|------|--------|-------------|
| 24   | `test_parse_thread()` | Unit test for `rcube_result_thread::parse_thread()` |

---

## Summary

| Category | Count |
|----------|-------|
| PHP public methods (thread in name) | 9 |
| PHP protected/private (thread in name) | 9 |
| JavaScript (email thread UI) | 6 |
| JavaScript (multi-thread HTTP) | 4 |
| **Total** | **28** |

### Core API entry points for threading

```php
// Enable threading mode
$imap->set_threading(true);

// Get full thread index for a folder
$threads = $imap->thread_index($folder, $sort_field, $sort_order);

// Get threads (basic / direct children only)
$threads = $imap->threads($folder);
$threads = $imap->threads_direct($folder);

// Low-level IMAP THREAD command
$threads = $imap_generic->thread($mailbox, 'REFERENCES', 'ALL', true, 'UTF-8');

// Cache access
$threads = $imap_cache->get_thread($mailbox);
$imap_cache->remove_thread($mailbox);

// Thread result structure
$data = $result_thread->get_thread_data(); // ['depth'=>[], 'children'=>[]]
```

 How can I leverage conversation mode in roundcube withoug reiveinging the wheel by  using current trhead mode and changing it a bit? Suggest at     
                                                                                                                                                      
least 3 solutions. Our current source code has a conversation_mode plugin do not take that into account, it does not work well. Answer as a           
roundcube,mail and architect software engieneer                                                                                                       

⏺ Leveraging Roundcube Thread Mode for Conversation View

  The core insight: Roundcube's thread mode already does 80% of the work. IMAP THREAD REFERENCES correctly groups messages into conversations. The gap
   between "thread mode" and "conversation mode" is narrow — it's mostly about sort order (latest activity first vs thread root date), display
  (collapsed single row vs indented tree), and interaction model (expand-in-place vs open detail pane).

  Here are 3 approaches, ranked by invasiveness:

  ---
  Solution 1 — Pure JS/CSS Layer on Top of Thread Mode (Least Invasive)

  The idea: Enable threading mode server-side, let RC fetch all thread data normally, then intercept the JS rendering to collapse the tree into
  conversation rows.

  How RC thread mode already helps you:
  - rcmail.env.threading = 1 is set → RC fetches THREAD REFERENCES from IMAP
  - rcmail.env.thread_roots is populated with root UIDs
  - Each <tr> row already has data-level and data-thread-root attributes
  - init_threads(), expand_threads(), update_thread() are all available

  What you add (JS only):
  // After list renders, collapse all non-root rows
  // Root rows already carry the thread child count in the DOM
  rcmail.addEventListener('listupdate', function() {
    // Hide all child rows
    $('tr[data-level]:not([data-level="0"])').hide();

    // Augment root rows: inject unread count badge, participants snippet
    // (data already in DOM — just restructure it visually)
    $('tr[data-thread-root]').each(function() {
      var root = $(this);
      var childCount = root.data('children'); // set by init_threads()
      root.find('.subject').append('<span class="conv-count">' + childCount + '</span>');
    });

    // Re-sort: RC sorts by thread root date; you sort DOM rows by latest child timestamp
    // Each child row has a timestamp — read max per thread and reorder DOM
  });

  Sort problem fix: RC sorts threads by root date. You need latest-activity sort. Two options:
  - Add a messages_list hook to inject a latest_timestamp data attribute on root rows, then sort the DOM client-side
  - Or set $_SESSION['sort_col'] = 'arrival' + use threads_direct() instead of threads() (direct children only, avoids deep nesting)

  Pros: No PHP changes, no custom AJAX, uses RC's own caching. Threading cache (rcube_imap_cache::get_thread) is already warm.
  Cons: Sort is done in DOM (not IMAP-level), so pagination is incorrect if you re-order after the fact. Only works cleanly if you load all
  conversations before paginating — fine for small/medium mailboxes.

  ---
  Solution 2 — messages_list Hook: Collapse + Re-Sort Server-Side (Middle Ground)

  The idea: Enable threading, hook messages_list on the PHP side to filter out child rows and inject conversation metadata (unread count, participant
  list, latest date) onto root rows. Return a flat, pre-sorted list of conversation heads.

  PHP hook:
  public function hook_messages_list(array $args): array
  {
      $storage = rcmail::get_instance()->get_storage();

      // RC already ran thread_index() — result is in the storage layer
      $threads = $storage->thread_index($args['folder']);
      $thread_data = $threads->get_thread_data();
      // $thread_data['depth'][$uid] = nesting level
      // $thread_data['children'][$uid] = child count

      $by_root = []; // root_uid => [child_uids]
      foreach ($args['messages'] as $msg) {
          $depth = $thread_data['depth'][$msg->uid] ?? 0;
          if ($depth === 0) {
              $by_root[$msg->uid] = ['root' => $msg, 'children' => [], 'latest_ts' => $msg->timestamp];
          }
      }
      // Second pass: assign children to roots, track latest timestamp
      foreach ($args['messages'] as $msg) {
          $depth = $thread_data['depth'][$msg->uid] ?? 0;
          if ($depth > 0) {
              $root_uid = $this->find_root($thread_data, $msg->uid);
              if (isset($by_root[$root_uid])) {
                  $by_root[$root_uid]['children'][] = $msg->uid;
                  $by_root[$root_uid]['latest_ts'] = max($by_root[$root_uid]['latest_ts'], $msg->timestamp);
                  if (!($msg->flags['SEEN'] ?? true)) {
                      $by_root[$root_uid]['root']->unread_children++;
                  }
              }
          }
      }

      // Sort by latest_ts DESC
      uasort($by_root, fn($a, $b) => $b['latest_ts'] <=> $a['latest_ts']);

      // Replace message list with only root rows, augmented
      $result = [];
      foreach ($by_root as $entry) {
          $root = $entry['root'];
          $root->extra_flags['conv_count']   = count($entry['children']) + 1;
          $root->extra_flags['conv_latest']  = $entry['latest_ts'];
          $result[] = $root;
      }

      $args['messages'] = $result;
      return $args;
  }

  Key advantage: Pagination now works correctly because RC paginates after your hook runs — each page contains $page_size conversations, not messages.

  CSS: Hide the thread indentation, style root rows as conversation cards. All the thread expand/collapse JS can be stripped or repurposed.           
  
  Pros: Correct server-side pagination. Reuses thread_index() + IMAP cache. No custom AJAX endpoints needed.                                          
  Cons: messages_list hook receives already-fetched headers — you don't control the initial IMAP fetch, so you may receive one page of messages that
  belong to only a fraction of full conversations. You need to disable RC's pagination ($storage->set_pagesize(9999) for the folder) or accept that   
  conversations spanning page boundaries are truncated. This is manageable with a moderate fetch limit (e.g. 2000 messages like your current service
  does) combined with RC's thread cache warmth.                                                                                                       
                  
  ---
  Solution 3 — Override thread_index() Sort via a Storage Hook + Thin JS Adapter (Best Architecture)
                                                                                                                                                      
  The idea: Roundcube's thread mode already calls $storage->thread_index($folder, $sort_field, $sort_order). The limitation is that sort is by the
  root message's date, not the latest message's date. Fix this at the IMAP layer by post-processing the rcube_result_thread result before RC uses it —
   then the existing RC threading UI renders correctly with minimal JS changes.
                                                                                                                                                      
  Step 1 — Hook storage_get_index or wrap thread_index output:                                                                                        
  
  Roundcube doesn't have a direct hook for thread_index(), but rcube_imap fetches thread data via rcube_imap_cache. You hook storage_init to inject   
  your own sort wrapper:
                                                                                                                                                      
  // In plugin init()
  $this->add_hook('storage_init', [$this, 'hook_storage_init']);
                                                                                                                                                      
  public function hook_storage_init(array $args): array
  {                                                                                                                                                   
      // Fetch threading headers so you can compute latest-in-thread date
      $args['fetch_headers'] = trim(($args['fetch_headers'] ?? '') . ' DATE MESSAGE-ID REFERENCES');                                                  
      return $args;                                                                                                                                   
  }                                                                                                                                                   
                                                                                                                                                      
  Step 2 — Intercept after messages_list, re-sort thread roots by latest child date:                                                                  
  
  public function hook_messages_list(array $args): array                                                                                              
  {               
      $storage = rcmail::get_instance()->get_storage();                                                                                               
                                                                                                                                                      
      // thread_index() result is already cached by RC (rcube_imap_cache)                                                                             
      // Just re-order the root entries by max(child dates)                                                                                           
      $threads  = $storage->thread_index($args['folder']);                                                                                            
      $order    = $this->compute_latest_date_order($threads, $args['messages']);
                                                                                                                                                      
      // Reorder $args['messages'] by $order                                                                                                          
      // RC will render them in this order with threading indentation intact                                                                          
      usort($args['messages'], fn($a, $b) => ($order[$b->uid] ?? 0) <=> ($order[$a->uid] ?? 0));                                                      
                                                                                                                                                      
      return $args;                                                                                                                                   
  }                                                                                                                                                   
                                                                                                                                                      
  private function compute_latest_date_order(rcube_result_thread $threads, array $headers): array                                                     
  {                                                                                                                                                   
      $thread_data = $threads->get_thread_data();                                                                                                     
      $ts_by_uid   = array_column($headers, 'timestamp', 'uid');                                                                                      
  
      // For each thread root, find max timestamp among all descendants                                                                               
      $root_latest = [];
      foreach ($headers as $h) {                                                                                                                      
          $root_uid = $this->resolve_root($thread_data, $h->uid);
          $root_latest[$root_uid] = max($root_latest[$root_uid] ?? 0, $h->timestamp);                                                                 
      }           
                                                                                                                                                      
      // Return timestamp keyed by uid (for all messages, propagate root's latest ts)
      $result = [];
      foreach ($headers as $h) {                                                                                                                      
          $root_uid = $this->resolve_root($thread_data, $h->uid);
          $result[$h->uid] = $root_latest[$root_uid] ?? $h->timestamp;                                                                                
      }                                                                                                                                               
      return $result;                                                                                                                                 
  }                                                                                                                                                   
                  
  Step 3 — JS: minimal adapter, no custom AJAX:                                                                                                       
                  
  // stratus_helper or conversation_mode.js                                                                                                           
  rcmail.addEventListener('listupdate', function() {                                                                                                  
    if (rcmail.env.threading) {
      // Collapse child rows by default (thread mode already has the data)                                                                            
      // Just hide depth > 0 rows initially and add expand affordance                                                                                 
      document.querySelectorAll('#messagelist tr[data-level]').forEach(function(row) {                                                                
        var level = parseInt(row.dataset.level || 0);                                                                                                 
        if (level > 0) row.classList.add('conv-child', 'conv-collapsed');                                                                             
      });                                                                                                                                             
      // Clicking a root row expands its children — reuse RC's existing expand_threads()                                                              
    }                                                                                                                                                 
  });             
                                                                                                                                                      
  Pros:           
  - rcube_imap_cache keeps rcube_result_thread warm — zero extra IMAP round trips
  - No custom AJAX, no custom pagination, no build_from_imap() fetching 2000 headers                                                                  
  - RC's existing update_thread(), update_thread_root(), delete_excessive_thread_rows() all continue to work
  - Mass actions (mark, delete, archive) work because RC already knows thread membership via thread_index()                                           
  - The sort re-ordering is O(n) on already-fetched headers — negligible cost                                                                         
                                                                                                                                                      
  Cons: You're sorting on what RC already fetched for the current page. Cross-page thread ordering still has edge cases (a thread whose root is on    
  page 2 but latest reply is on page 1). Solvable by fetching 2–3 pages of thread index data and only displaying page 1 worth of conversation heads — 
  this is exactly how Gmail handles it too.                                                                                                           
                                                                                                                                                      
  ---             
  Comparison
            
  ┌────────────────────────┬──────────────────────┬───────────────────────┬───────────────────────────────────┐
  │                        │ Solution 1 (JS only) │ Solution 2 (PHP hook) │ Solution 3 (thread_index re-sort) │                                       
  ├────────────────────────┼──────────────────────┼───────────────────────┼───────────────────────────────────┤                                       
  │ PHP changes            │ None                 │ Medium                │ Small                             │                                       
  ├────────────────────────┼──────────────────────┼───────────────────────┼───────────────────────────────────┤                                       
  │ JS changes             │ Medium               │ Small                 │ Small                             │
  ├────────────────────────┼──────────────────────┼───────────────────────┼───────────────────────────────────┤                                       
  │ Pagination correctness │ Poor                 │ Good                  │ Good                              │
  ├────────────────────────┼──────────────────────┼───────────────────────┼───────────────────────────────────┤                                       
  │ IMAP round trips       │ 0 extra              │ 0 extra               │ 0 extra                           │
  ├────────────────────────┼──────────────────────┼───────────────────────┼───────────────────────────────────┤                                       
  │ Uses RC thread cache   │ Yes                  │ Yes                   │ Yes                               │
  ├────────────────────────┼──────────────────────┼───────────────────────┼───────────────────────────────────┤                                       
  │ Mass actions work      │ Yes (RC handles)     │ Yes                   │ Yes                               │
  ├────────────────────────┼──────────────────────┼───────────────────────┼───────────────────────────────────┤                                       
  │ Custom AJAX needed     │ No                   │ No                    │ No                                │
  ├────────────────────────┼──────────────────────┼───────────────────────┼───────────────────────────────────┤                                       
  │ Complexity             │ Low                  │ Medium                │ Low                               │
  └────────────────────────┴──────────────────────┴───────────────────────┴───────────────────────────────────┘                                       
                  
  Recommended path: Start with Solution 3 — it's the least code, reuses every layer of RC's threading stack, and the sort post-processing is trivial. 
  The only real work is CSS to render collapsed conversation rows attractively and deciding how to handle the cross-page edge case (a configurable
  over-fetch of N×page_size thread roots, slice to page_size after sort).                                                                             
                  
✻ Worked for 1m 25s

❯ I option 3 , I dont understand the edge case, I thougth roundcube would get all messages from  a trhead no matter if they were old                  
                                                                                                                                      
⏺ Good question. Let me clarify exactly what happens under the hood.                                                                                  
                                                                                                                                                      
  What Roundcube Actually Fetches Per Page                                                                                                            
                                                                                                                                                      
  The IMAP THREAD command returns the full thread tree for the entire mailbox — all threads, all their child UIDs. This gets cached in                
  rcube_imap_cache. That part is global and complete.                                                                                                 
                                                                                                                                                      
  But message headers (date, subject, from, flags) are only fetched for the current page's threads. The flow is:                                      
  
  thread_index()          → full UID tree (global, cached)                                                                                            
                                    ↓                                                                                                                 
  RC sorts roots by       → root message date DESC                                                                                                    
  root date                                                                                                                                           
                                    ↓                                                                                                                 
  slice page 1            → UIDs for roots 1–20                                                                                                       
                                    ↓                                                                                                                 
  fetch_thread_headers()  → IMAP FETCH only for those 20 roots + their children
                                                                                                                                                      
  The Actual Edge Case
                                                                                                                                                      
  Say you have 200 threads, page size = 20:                                                                                                           
  
  Page 1 roots (sorted by ROOT date):                                                                                                                 
    Thread A — root: 3 days ago, latest reply: 3 days ago  ✓                                                                                          
    Thread B — root: 5 days ago, latest reply: yesterday   ← should be #1!                                                                            
    ...                                                                                                                                               
                                                                                                                                                      
  Page 2 roots:                                                                                                                                       
    Thread C — root: 40 days ago, latest reply: 1 hour ago ← should be #1!!
                                                                                                                                                      
  When you re-sort page 1 by latest reply date, Thread C doesn't even exist in your data — its headers were never fetched. You're re-sorting within a 
  pre-sliced window, not globally.                                                                                                                    
                                                                                                                                                      
  Why It Doesn't Matter as Much as It Sounds                                                                                                          
  
  The UID is your救生圈 (lifesaver) here. IMAP assigns UIDs in arrival order — higher UID = more recent message. The full thread tree already contains
   all UIDs globally (no header fetch needed). So you can compute latest activity per thread purely from the UID tree:
                                                                                                                                                      
  $threads   = $storage->thread_index($folder);           // full tree, cached
  $tree      = $threads->get_thread_data();               // all UIDs                                                                                 
  $children  = $tree['children'];                         // root_uid => child_uids[]
                                                                                                                                                      
  // Sort ALL thread roots by max UID (= most recently arrived message)                                                                               
  // Zero IMAP fetches — UID data is already in the cached thread index                                                                               
  $roots = $threads->get();  // flat list of root UIDs                                                                                                
  usort($roots, function($a, $b) use ($children) {                                                                                                    
      $max_a = empty($children[$a]) ? $a : max($a, ...$children[$a]);                                                                                 
      $max_b = empty($children[$b]) ? $b : max($b, ...$children[$b]);                                                                                 
      return $max_b <=> $max_a;                                                                                                                       
  });                                                                                                                                                 
                                                                                                                                                      
  // NOW slice for pagination — page 1 = roots[0..19]                                                                                                 
  $page_roots = array_slice($roots, ($page-1) * $page_size, $page_size);                                                                              
                                                                                                                                                      
  // Only THEN fetch headers for this page's 20 threads                                                                                               
                                                                                                                                                      
  Result: Globally correct sort order, correct pagination, zero extra IMAP round trips because the thread UID tree is already cached.                 
                  
  Summary                                                                                                                                             
                  
  ┌────────────────────┬────────────────────┬───────────────────────────┬───────────────────────┐                                                     
  │                    │ Roundcube default  │       Naive re-sort       │   UID-based re-sort   │
  ├────────────────────┼────────────────────┼───────────────────────────┼───────────────────────┤                                                     
  │ Sort by            │ Thread root date   │ Latest reply (page-local) │ Latest reply (global) │
  ├────────────────────┼────────────────────┼───────────────────────────┼───────────────────────┤
  │ Extra IMAP fetches │ 0                  │ 0                         │ 0                     │                                                     
  ├────────────────────┼────────────────────┼───────────────────────────┼───────────────────────┤                                                     
  │ Pagination correct │ Yes (by root date) │ No                        │ Yes                   │                                                     
  ├────────────────────┼────────────────────┼───────────────────────────┼───────────────────────┤                                                     
  │ Uses RC cache      │ Yes                │ Yes                       │ Yes                   │
  └────────────────────┴────────────────────┴───────────────────────────┴───────────────────────┘                                                     
                  
  The UID approach is the clean solution — it turns the edge case into a non-issue by doing the global sort before slicing for pagination, using only 
  data that's already in the thread index cache.