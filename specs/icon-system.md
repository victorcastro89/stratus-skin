# Stratus Icon System — Design Spec

Replace every FontAwesome icon in Elastic and Stratus with Google Material Design icons.

---

## 1. Problem

Elastic uses **FontAwesome 5** (`font-family: 'Icons'`, weight 900 = solid, 400 = regular) for all UI icons. There are **118 unique FA icons** referenced across **265 selector rules** in 10 LESS files. Stratus adds **14 more hardcoded FA codepoints** in its own LESS.

Stratus already swaps the font files (Material Icons woff2 behind `font-family: 'Icons'` in `_fonts.less`), but the **unicode codepoints don't match** — FA `\f187` (archive) has no meaning in the Material Icons font. Every icon renders blank or wrong unless the `content:` value is explicitly overridden.

---

## 2. Prior Art: xframework (gmail_plus) Approach

The Roundcube Plus ecosystem solves this with:

- **Custom font** (`RcpIconFont`) — one font file containing 5 icon style variants (solid, traditional, outlined, material, cartoon) packed into 5 unicode ranges of 200 slots each in the Private Use Area
- **SCSS build pipeline** — `_icons.scss` mixin branches on `html.xicons-{style}` class to emit the correct unicode range
- **`_icons_elastic.scss`** — 388 lines of CSS selectors (scoped under `.xskin`) overriding every Elastic icon
- **Runtime class switching** — PHP plugin adds `xicons-material` to `<html>`, user can change icon style in preferences

### Why Stratus Won't Copy This

| xframework | Stratus |
|---|---|
| Custom font build pipeline | Uses Google Material Icons directly (no font build) |
| SCSS preprocessor | LESS only (matches Elastic) |
| 5 icon style variants | One style: Material filled (900) + outlined (400) |
| Runtime style switching via HTML class | No switching needed — one style always |
| `.xskin` body class scoping | No scoping — Stratus IS the skin |
| 157 custom icon IDs | Uses Material Icons' native 2,200+ PUA codepoints |

---

## 3. Solution Architecture

### 3.1 File Structure

```
skins/stratus/styles/
  _fonts.less          ← EXISTS: @font-face swaps FA files → Material Icons files
  _icons.less          ← NEW: variable map + all content: overrides
  styles.less          ← UPDATE: import _icons after _fonts
```

### 3.2 How It Works

```
Elastic compiles:     .menu a.reply:before { content: "\f3e5"; }  ← FA reply
_fonts.less:          font-family 'Icons' now loads material-icons.woff2
_icons.less:          .menu a.reply:before { content: "\e15e"; }  ← MI reply
CSS cascade:          last rule wins → Material icon renders
```

No runtime PHP. No font building. No class switching. Pure CSS override.

### 3.3 Import Order

```less
// styles.less
@import "elastic/styles/styles";   // 1. Elastic base (FA codepoints)
@import "_fonts";                  // 2. Swap font files to Material Icons
@import "_icons";                  // 3. Override all content: values ← NEW
@import "_variables";              // 4. Color/spacing vars
@import "_mixins";                 // 5. Mixins
// ... component files
```

### 3.4 Weight Mapping

Material Icons filled and outlined fonts share the same codepoints (2,183 of 2,195 overlap verified). The font-weight toggle works automatically:

| Elastic | Font File | Material Equivalent |
|---|---|---|
| `font-weight: 900` (`.font-icon-solid`) | `material-icons.woff2` | Filled variant |
| `font-weight: 400` (`.font-icon-regular`) | `material-icons-outlined.woff2` | Outlined variant |

Both already declared in `_fonts.less`. No additional work needed.

---

## 4. Complete FA → Material Icons Codepoint Map

118 unique FontAwesome icons used in Elastic, mapped to Material Icons equivalents.

### 4.1 Variable Definitions (`_icons.less` Section 1)

```less
// ═══════════════════════════════════════════════════════════════════════════
// Material Icons codepoint variables
// Source: Google Material Icons (MaterialIcons-Regular.codepoints)
// Font: material-icons.woff2 (filled, weight 900)
//        material-icons-outlined.woff2 (outlined, weight 400)
// Naming: @mi-{fa-name} for easy cross-reference to Elastic's @fa-var-{name}
// ═══════════════════════════════════════════════════════════════════════════

// ─── Navigation / Arrows ─────────────────────────────────────────────────
@mi-arrow-left:              "\e5c4";  // arrow_back
@mi-arrow-right:             "\e5c8";  // arrow_forward
@mi-chevron-left:            "\e5cb";  // chevron_left
@mi-chevron-right:           "\e5cc";  // chevron_right
@mi-angle-down:              "\e5cf";  // expand_more
@mi-angle-up:                "\e5ce";  // expand_less
@mi-angle-left:              "\e314";  // keyboard_arrow_left
@mi-angle-right:             "\e315";  // keyboard_arrow_right
@mi-angle-double-down:       "\e5cf";  // expand_more (no double variant — use single)
@mi-angle-double-up:         "\e5ce";  // expand_less
@mi-angle-double-left:       "\e5dc";  // first_page
@mi-angle-double-right:      "\e5dd";  // last_page
@mi-caret-down:              "\e5c5";  // arrow_drop_down

// ─── Actions ─────────────────────────────────────────────────────────────
@mi-check:                   "\e5ca";  // check
@mi-check-circle:            "\e86c";  // check_circle
@mi-check-square:            "\e834";  // check_box
@mi-times:                   "\e5cd";  // close
@mi-times-circle:            "\e5c9";  // cancel
@mi-plus:                    "\e145";  // add
@mi-plus-square:             "\e146";  // add_box
@mi-minus-circle:            "\e15b";  // remove  (semantic: minus-circle → remove)
@mi-ban:                     "\e14c";  // clear (semantic: ban → block)
@mi-undo:                    "\e166";  // undo
@mi-redo:                    "\e15a";  // redo
@mi-redo-alt:                "\e15a";  // redo

// ─── Mail ────────────────────────────────────────────────────────────────
@mi-envelope:                "\e158";  // mail
@mi-envelope-open:           "\e151";  // drafts
@mi-paper-plane:             "\e163";  // send
@mi-reply:                   "\e15e";  // reply
@mi-reply-all:               "\e15f";  // reply_all
@mi-inbox:                   "\e156";  // inbox
@mi-archive:                 "\e149";  // archive
@mi-flag:                    "\e153";  // flag
@mi-tag:                     "\e892";  // label
@mi-paperclip:               "\e226";  // attach_file
@mi-spell-check:             "\e8ce";  // spellcheck
@mi-signature:               "\e228";  // draw (gesture)

// ─── Objects ─────────────────────────────────────────────────────────────
@mi-trash-alt:               "\e872";  // delete
@mi-folder:                  "\e2c7";  // folder
@mi-folder-open:             "\e2c8";  // folder_open
@mi-file:                    "\e24d";  // insert_drive_file
@mi-file-alt:                "\e873";  // description
@mi-file-code:               "\e86f";  // code
@mi-file-pdf:                "\e415";  // picture_as_pdf
@mi-file-word:               "\e873";  // description (no exact word equivalent)
@mi-file-excel:              "\e265";  // table_chart
@mi-file-powerpoint:         "\e41b";  // slideshow
@mi-file-archive:            "\e2c4";  // folder_zip
@mi-file-image:              "\e3f4";  // image
@mi-file-audio:              "\eb82";  // audio_file
@mi-file-video:              "\eb87";  // video_file
@mi-sticky-note:             "\f1fc";  // sticky_note_2
@mi-save:                    "\e161";  // save
@mi-print:                   "\e8ad";  // print
@mi-download:                "\f090";  // download
@mi-upload:                  "\f09b";  // upload
@mi-copy:                    "\e14d";  // content_copy
@mi-image:                   "\e3f4";  // image
@mi-link:                    "\e157";  // link
@mi-qrcode:                  "\e00a";  // qr_code_2

// ─── People ──────────────────────────────────────────────────────────────
@mi-user:                    "\e7fd";  // person
@mi-users:                   "\e7ef";  // group
@mi-user-plus:               "\e7fe";  // person_add
@mi-user-times:              "\ef66";  // person_remove
@mi-user-friends:            "\e7ef";  // group
@mi-address-book:            "\e0ba";  // contacts
@mi-address-card:            "\f22e";  // contact_page
@mi-id-card:                 "\f22e";  // contact_page

// ─── UI / Interface ──────────────────────────────────────────────────────
@mi-search:                  "\e8b6";  // search
@mi-search-plus:             "\e8ff";  // zoom_in
@mi-search-minus:            "\e900";  // zoom_out
@mi-filter:                  "\ef4f";  // filter_alt
@mi-cog:                     "\e8b8";  // settings
@mi-sliders-h:               "\e429";  // tune
@mi-wrench:                  "\e869";  // build (no wrench — build is closest)
@mi-bars:                    "\e5d2";  // menu
@mi-ellipsis-h:              "\e5d3";  // more_horiz
@mi-ellipsis-v:              "\e5d4";  // more_vert
@mi-info-circle:             "\e88e";  // info
@mi-question:                "\eb8b";  // question_mark
@mi-question-circle:         "\e887";  // help
@mi-exclamation-triangle:    "\e002";  // warning
@mi-exclamation-circle:      "\e000";  // error
@mi-circle:                  "\ef4a";  // circle (or lens)
@mi-circle-notch:            "\e863";  // hourglass_empty (spinner analog)
@mi-lightbulb:               "\e0f0";  // lightbulb
@mi-eye:                     "\e8f4";  // visibility
@mi-mouse-pointer:           "\e913";  // touch_app
@mi-asterisk:                "\e838";  // star (closest semantic match)

// ─── Security / Auth ─────────────────────────────────────────────────────
@mi-lock:                    "\e897";  // lock
@mi-unlock:                  "\e898";  // lock_open
@mi-key:                     "\e0da";  // vpn_key
@mi-shield-alt:              "\e9e0";  // shield
@mi-sign-in-alt:             "\ea77";  // login

// ─── Communication ───────────────────────────────────────────────────────
@mi-comment:                 "\e0b9";  // comment
@mi-comments:                "\e0bf";  // forum
@mi-share:                   "\e80d";  // share
@mi-share-alt:               "\e80d";  // share
@mi-share-square:            "\e80d";  // share
@mi-bell:                    "\e7f4";  // notifications
@mi-rss-square:              "\e0e5";  // rss_feed

// ─── Misc ────────────────────────────────────────────────────────────────
@mi-calendar:                "\e935";  // calendar_today
@mi-calendar-alt:            "\e878";  // event
@mi-tasks:                   "\e2e6";  // task_alt
@mi-home:                    "\e88a";  // home
@mi-globe:                   "\e894";  // language
@mi-desktop:                 "\e30b";  // desktop_windows
@mi-server:                  "\e875";  // dns
@mi-hdd:                     "\e1db";  // storage
@mi-sun:                     "\e430";  // wb_sunny
@mi-moon:                    "\e51c";  // dark_mode
@mi-fire-alt:                "\ef55";  // local_fire_department
@mi-eraser:                  "\e14c";  // clear (closest semantic match)
@mi-compress-arrows-alt:     "\e5d6";  // unfold_less
@mi-life-ring:               "\e887";  // help (no life-ring — help is semantic equivalent)
@mi-power-off:               "\e646";  // power_off
@mi-edit:                    "\e3c9";  // edit
@mi-pencil-alt:              "\e3c9";  // edit
@mi-pen:                     "\e3c9";  // edit
@mi-external-link-square-alt: "\e89e"; // open_in_new
@mi-window-close:            "\e5cd";  // close
@mi-align-justify:           "\e235";  // format_align_justify
@mi-sort-down:               "\e5db";  // arrow_downward
@mi-sort-up:                 "\e5d8";  // arrow_upward
@mi-sync:                    "\e627";  // sync
```

---

## 5. Override Selectors — Complete Inventory

### 5.1 Elastic Overrides (by source file)

Each section below lists every selector in the corresponding Elastic LESS file that sets a `content: @fa-var-*` value. All must be overridden in `_icons.less`.

#### 5.1.1 `widgets/buttons.less` (36 icon references)

```
a.button.icon, button.btn {
  .sidebar-menu:before, .toolbar-menu-button:before, .toolbar-list-button:before  → @mi-ellipsis-v
  .task-menu-button:before                                                         → @mi-bars
  .back-sidebar-button:before, .back-content-button:before, .back-list-button:before → @mi-chevron-left
  .refresh:before                                                                  → @mi-sync
  .generate:before, .yes:before, .submit:before, .continue:before, .save:before   → @mi-check
  .create:before                                                                   → @mi-plus-square
  .edit:before                                                                     → @mi-pencil-alt
  .qrcode:before                                                                   → @mi-qrcode
  .search:before                                                                   → @mi-search
  .filter:before                                                                   → @mi-filter
  .import:before                                                                   → @mi-upload
  .export:before                                                                   → @mi-download
  .discard:before, .delete:before                                                  → @mi-trash-alt (weight 400)
  .next:before                                                                     → @mi-arrow-right
  .restore:before                                                                  → @mi-undo
  .send:before, .bounce:before                                                     → @mi-paper-plane
  .attach:before                                                                   → @mi-paperclip
  .attach.vcard:before                                                             → @mi-user
  .no:before, .close:before, .cancel:before                                        → @mi-times
  .back:before                                                                     → @mi-chevron-left
  .remove:before                                                                   → @mi-times
  .unlock:before                                                                   → @mi-unlock
  .help:before                                                                     → @mi-life-ring (weight 400)
  .folders:before                                                                  → @mi-folder-open
  .options:before                                                                  → @mi-sliders-h
  .tools:before, .settings:before                                                  → @mi-cog
  .properties:before                                                               → @mi-info-circle
  .selection:before                                                                → @mi-check-square (weight 400)
  .insert.recipient:before                                                         → @mi-user-plus
  .encrypt:before                                                                  → @mi-lock
  .sign:before                                                                     → @mi-signature
  .sso:before                                                                      → @mi-sign-in-alt
  .extwin:before                                                                   → @mi-external-link-square-alt
  (line 177) button dropdown:before                                                → @mi-caret-down
  (line 216) .popover-header a.button:before                                       → @mi-plus
  (line 224) .input-group-append .icon:before                                      → @mi-pen
}
```

#### 5.1.2 `widgets/menu.less` (98 icon references)

```
Taskmenu (#taskmenu a):
  .mail:before            → @mi-envelope
  .contacts:before        → @mi-users
  .options:before         → @mi-sliders-h
  .settings:before        → @mi-cog
  .theme.light:before     → @mi-sun
  .theme.dark:before      → @mi-moon
  .help:before            → @mi-life-ring
  .logout:before          → @mi-power-off
  .about:before           → @mi-question
  .refresh:before         → @mi-sync
  .compose:before         → @mi-edit
  .calendar:before        → @mi-calendar-alt
  .tasklist:before        → @mi-tasks
  .files:before           → @mi-folder
  .notes:before           → @mi-sticky-note

Toolbar menu items (.toolbarmenu li a, .menu a):
  .reply:before                          → @mi-reply
  .reply.all:before, .reply.list:before  → @mi-reply-all
  .forward:before (+ bounce, attachment, inline) → @mi-arrow-right (Elastic uses redo-alt for forward variants)
  .forward:before (email context)        → @mi-reply (mirrored — check Elastic's actual icon)
  .back:before                           → @mi-arrow-left
  .save:before                           → @mi-save (weight 400)
  .delete:before                         → @mi-trash-alt
  .print:before                          → @mi-print
  .download:before (+ .mbox, .eml, .maildir) → @mi-download
  .export.selection:before, .export.all:before → @mi-download
  .create:before, .compose:before        → @mi-plus-square
  .edit:before, .rename:before, .edit.asnew:before → @mi-pencil-alt
  .move:before                           → @mi-folder  (drive_file_move)
  .copy:before                           → @mi-copy
  .search:before                         → @mi-search
  .archive:before                        → @mi-archive
  .markmessage:before                    → @mi-tag
  .flag:before (solid)                   → @mi-flag (weight 900)
  .unflag:before (regular)               → @mi-flag (weight 400)
  .read:before                           → @mi-envelope-open
  .unread:before                         → @mi-envelope
  .junk:before                           → fire-alt context → @mi-fire-alt
  .purge:before                          → @mi-eraser
  .more:before, .ellipsis:before         → @mi-ellipsis-h
  .folder-open:before                    → @mi-folder-open
  .filter:before, .filterlink:before     → @mi-filter
  .dropdown:before                       → @mi-caret-down
  .lock:before, .encrypt:before          → @mi-lock
  .inbox:before                          → @mi-inbox
  .send:before                           → @mi-paper-plane
  .link:before                           → @mi-link
  .signature:before                      → @mi-signature
  .file-code:before, .source:before      → @mi-file-code
  .status:before, .lightbulb:before      → @mi-lightbulb (weight 400)
  .responses:before, .insertresponse:before → @mi-comment
  .settings:before                       → @mi-wrench
  .remove:before                         → @mi-times
  .select:before, .check-square:before   → @mi-check-square
  .threads:before                        → @mi-comments
  .spell-check:before                    → @mi-spell-check
  .addressbook:before                    → @mi-user
  .upload:before, .import:before         → @mi-upload
  .share:before                          → @mi-share
  .user-times:before, .removegroup:before → @mi-user-times
  .user-plus:before, .assigngroup:before  → @mi-user-plus
  .redo:before                           → @mi-redo
  .comment:before                        → @mi-comment
  .redo-alt:before                       → @mi-redo-alt
  .info-circle:before                    → @mi-info-circle
  .search-plus:before                    → @mi-search-plus
  .search-minus:before                   → @mi-search-minus
  .mouse-pointer:before                  → @mi-mouse-pointer
  .asterisk:before                       → @mi-asterisk
  .qrcode:before                         → @mi-qrcode
  .vcard:before                          → @mi-paperclip
  .eraser:before                         → @mi-eraser
  .external-link:before, .extwin:before  → @mi-external-link-square-alt
  .window-close:before                   → @mi-window-close
  .compress-arrows:before                → @mi-compress-arrows-alt

Searchbar:
  form:before                            → @mi-search
  a.options:before                       → @mi-angle-down
  .open a.options:before                 → @mi-angle-up
  a.reset:before, a.unread:before        → @mi-times / @mi-envelope
```

#### 5.1.3 `widgets/lists.less` (85 icon references)

```
Settings sections:
  .sliders-h:before     → @mi-sliders-h
  .folder:before        → @mi-folder
  .comment:before       → @mi-comment
  .id-card:before       → @mi-id-card
  .lock:before          → @mi-lock
  .address-book:before  → @mi-address-book (weight 400)
  .users:before         → @mi-users
  .search:before        → @mi-search
  .filter:before        → @mi-filter
  .clock:before         → @mi-calendar (weight 400) — or use schedule
  .share-square:before  → @mi-share-square
  .key:before           → @mi-key
  .info-circle:before   → @mi-info-circle
  .sign-in-alt:before   → @mi-sign-in-alt
  .life-ring:before     → @mi-life-ring
  .question-circle:before → @mi-question-circle (weight 400)
  .shield-alt:before    → @mi-shield-alt

Contact list:
  td.contact:before     → @mi-user
  td.contactgroup:before → @mi-users
  a.addressbook:before  → @mi-address-book (weight 400)
  a.contactgroup:before → @mi-users

Folder list:
  li a:before (default)      → @mi-folder (weight 400)
  .inbox > a:before          → @mi-inbox
  .trash a:before            → @mi-trash-alt
  .trash.empty > a:before    → @mi-trash-alt (weight 400)
  .drafts a:before           → @mi-pencil-alt
  .sent a:before             → @mi-paper-plane
  .junk a:before             → @mi-fire-alt
  .archive > a:before        → @mi-archive
  .share a:before            → @mi-share-alt

Treelist toggles:
  .treetoggle:before         → @mi-angle-right
  .treetoggle.expanded:before → @mi-angle-down

Message list status icons:
  .msgicon.status:before                      → @mi-circle (weight 400)
  .msgicon.status.unread:before               → @mi-circle (weight 900)
  .msgicon.status.replied:before              → @mi-reply
  .msgicon.status.forwarded:before            → @mi-share (forward analog)
  .msgicon.status.replied.forwarded:before    → @mi-reply + @mi-share
  tr.deleted .msgicon.status:before           → @mi-ban

Attachment indicators:
  span.attachment span:before        → @mi-paperclip
  span.attachment .report:before     → @mi-file-alt (weight 400)
  span.attachment .encrypted:before  → @mi-lock
  span.attachment .vcard:before      → @mi-user (weight 400)

Flags:
  span.flagged:before     → @mi-flag
  span.unflagged:before   → @mi-flag (weight 400)

File type icons (attachment list):
  .file:before            → @mi-file (weight 400)
  .file-alt:before        → @mi-file-alt (weight 400)
  .file-pdf:before        → @mi-file-pdf (weight 400)
  .file-word:before       → @mi-file-word (weight 400)
  .file-excel:before      → @mi-file-excel (weight 400)
  .file-archive:before    → @mi-file-archive (weight 400)
  .file-image:before      → @mi-file-image (weight 400)
  .file-audio:before      → @mi-file-audio (weight 400)
  .file-video:before      → @mi-file-video (weight 400)
  .address-card:before    → @mi-address-card (weight 400)
  .file-code:before       → @mi-file-code (weight 400)
  .file-powerpoint:before → @mi-file-powerpoint (weight 400)

Other list items:
  #identities-table td.mail:before  → @mi-id-card
  #responses-table td.name:before   → @mi-comment
  #filterslist td.name:before       → @mi-filter
  #filtersetslist td.name:before    → @mi-file-alt
  .listing td.action a.pushgroup:before → @mi-chevron-right
```

#### 5.1.4 `widgets/forms.less` (19 icon references)

```
.input-group .icon:
  .user:before    → @mi-user
  .pass:before    → @mi-lock
  .host:before    → @mi-home
  .globe:before   → @mi-globe
  .cancel:before  → @mi-times
  .delete:before  → @mi-trash-alt
  .edit:before    → @mi-pencil-alt
  .add:before     → @mi-plus
  .recipient:before → @mi-users
  .search:before  → @mi-search
  .filter:before  → @mi-filter
  .key:before     → @mi-key

Misc form elements:
  .multi-input a.icon.reset:before    → @mi-trash-alt
  .tagedit-list a:before              → @mi-times
  .googie_list_revert:before          → @mi-plus
  .googie_add_to_dict:before          → @mi-plus
  angle-up / angle-down (spinners)    → @mi-angle-up / @mi-angle-down
```

#### 5.1.5 `widgets/messages.less` (9 icon references)

```
.ui-dialog .messagebox:
  .notice:before    → @mi-info-circle
  .loading:before   → @mi-circle-notch
  .confirmation:before → @mi-check-circle
  .warning:before   → @mi-exclamation-triangle
  .error:before     → @mi-exclamation-circle
  .vcardattachment:before → @mi-address-card
  .enigmaattachment:before → @mi-key
  .enigmamessage:before → @mi-lock
  .chat-notice:before → @mi-comment
```

#### 5.1.6 `widgets/common.less` (10 icon references)

```
#message-header .short-header:
  a.extwin:before       → @mi-external-link-square-alt
  a.download:before     → @mi-download

.quota-widget:
  .bar:before           → @mi-hdd

Table widget:
  td.enabled span:before  → @mi-check
  td.partial span:before  → @mi-check

Table header checkboxes:
  .subscription:before  → @mi-rss-square
  .alarm:before         → @mi-bell (weight 400)
  .read:before          → @mi-eye
  .write:before         → @mi-pencil-alt

Pagination:
  .back:before          → @mi-chevron-left
```

#### 5.1.7 `widgets/editor.less` (8 icon references)

```
TinyMCE dialog:
  .tox-dialog__header .tox-button:before     → @mi-times
  .tox-dialog__footer .tox-button:before     → @mi-check
  .tox-button--secondary:before              → @mi-times
  .tox-search-dialog find:before             → @mi-search
  .tox-search-dialog replace:before          → @mi-signature

Compose toolbar:
  .mce-i-image:before   → @mi-image
  undo:before            → @mi-undo
  insert template:before → @mi-plus-square
```

#### 5.1.8 `widgets/dialogs.less` (2 icon references)

```
.pgpkeyimport .keyid a:before  → @mi-key
.pgpkeyimport li.uid:before    → @mi-user
```

#### 5.1.9 `widgets/jqueryui.less` (2 icon references)

```
.ui-dialog-titlebar-close:before  → @mi-times
.ui-autocomplete .settings:before → @mi-cog
```

#### 5.1.10 `styles.less` (11 icon references)

```
#message-header:
  .subject .extwin:before         → @mi-external-link-square-alt
  .header-links a.envelope:before → @mi-envelope
  .header-links a.html:before     → @mi-file-code (or code)
  .header-links a.plain:before    → @mi-align-justify
  .header-links a.zipdownload:before → @mi-download

blockquote toggle:
  span.blockquote-link:after        → @mi-angle-down
  span.blockquote-link.collapsed:after → @mi-angle-up

.image-attachment:
  a.open:before      → @mi-external-link-square-alt
  a.download:before  → @mi-download

.floating-action-buttons a.button:before → @mi-plus
settings default icon → @mi-cog
```

#### 5.1.11 `embed.less` (1 icon reference)

```
.message-part .extcss-warning:before → @mi-exclamation-triangle
```

### 5.2 Stratus Hardcoded Overrides (14 references)

These are FA codepoints hardcoded directly in Stratus LESS (not via `@fa-var-*` variables). Replace with `@mi-*` variables.

| File | Line | Current (FA) | Replace With |
|---|---|---|---|
| `widgets/common.less` | 313 | `\f0d7` (caret-down) | `@mi-caret-down` |
| `widgets/common.less` | 387 | `\f0dd` (sort-down) | `@mi-sort-down` |
| `widgets/common.less` | 392 | `\f0de` (sort-up) | `@mi-sort-up` |
| `widgets/common.less` | 398 | `\f0dd` (sort-down) | `@mi-sort-down` |
| `widgets/common.less` | 428 | `\f021` (sync) | `@mi-sync` |
| `widgets/common.less` | 623 | `\f021` (sync) | `@mi-sync` |
| `widgets/lists.less` | 223 | `\f187` (archive) | `@mi-archive` |
| `widgets/lists.less` | 228 | `\f2ed` (trash-alt) | `@mi-trash-alt` |
| `widgets/lists.less` | 233 | `\f024` (flag) | `@mi-flag` |
| `widgets/lists.less` | 298 | `\f0e0` (envelope) | `@mi-envelope` |
| `widgets/lists.less` | 310 | `\f002` (search) | `@mi-search` |
| `widgets/lists.less` | 331 | `\f002` (search) | `@mi-search` |
| `widgets/lists.less` | 362 | `\f086` (comments) | `@mi-comments` |
| `_calendar.less` | 739 | `\f141` (ellipsis-h) | `@mi-ellipsis-h` |

---

## 6. Implementation Plan

### Step 1: Create `_icons.less`

Two sections:
1. **Variable declarations** — all `@mi-*` variables from Section 4.1 above
2. **Elastic selector overrides** — every selector from Section 5.1, setting `content: @mi-*` and `font-weight:` where needed

Structure the overrides file organized by source:
```less
// ═══════════════════════════════════════════════════════════════════════════
// SECTION 1: Material Icons codepoint variables
// ═══════════════════════════════════════════════════════════════════════════
@mi-archive: "\e149";
// ... (all ~118 variables)

// ═══════════════════════════════════════════════════════════════════════════
// SECTION 2: Elastic icon overrides
// Override every content: @fa-var-* in Elastic with the MI codepoint.
// Organized by Elastic source file for maintainability.
// ═══════════════════════════════════════════════════════════════════════════

// ─── From: elastic/styles/widgets/buttons.less ───────────────────────────
a.button.icon,
button.btn {
    &.sidebar-menu:before,
    &.toolbar-menu-button:before,
    &.toolbar-list-button:before {
        content: @mi-ellipsis-v;
    }
    // ... all button overrides
}

// ─── From: elastic/styles/widgets/menu.less ──────────────────────────────
// ... etc
```

### Step 2: Update `styles.less`

Add import after `_fonts`:
```less
@import "_fonts";
@import "_icons";    // ← ADD
@import "_variables";
```

### Step 3: Update Stratus hardcoded codepoints

Replace the 14 hardcoded FA codepoints in Stratus LESS files (Section 5.2) with `@mi-*` variable references.

### Step 4: Build & validate

```bash
npm run less:build
```

### Step 5: Visual QA

Compare every screen against expected icons:
- [ ] Mail list (inbox, sent, drafts, trash, junk, archive folders)
- [ ] Message view (reply, forward, delete, archive, flag, mark)
- [ ] Compose (send, attach, signature, spellcheck, save)
- [ ] Contacts (person, group, addressbook)
- [ ] Settings (all section icons, form icons)
- [ ] Calendar (if calendar plugin active)
- [ ] Toolbar buttons (all states)
- [ ] Search bar
- [ ] Dialog boxes (close, confirm, cancel)
- [ ] File type icons in attachment list
- [ ] Dark mode (all of the above)
- [ ] Mobile/phone layout

---

## 7. Risks & Mitigations

| Risk | Mitigation |
|---|---|
| No exact MI equivalent for an FA icon | 6 cases identified (life-ring→help, eraser→clear, mouse-pointer→touch_app, asterisk→star, hdd→storage, file-word→description). Semantic matches documented above. |
| Glyph sizing differs (MI glyphs may appear larger/smaller) | Material Icons are designed at 24px optical size. May need per-selector `font-size` adjustments during QA. |
| Elastic update adds new FA icons | Diff Elastic's icon selectors on each Roundcube update and extend `_icons.less`. |
| Some selectors have higher specificity in Elastic | Since `_icons.less` is imported AFTER Elastic (same specificity, later in cascade), it wins. If not, add parent selector for specificity boost. |
| Outlined and filled codepoints diverge for a few glyphs | 12 of 2,195 outlined glyphs have no filled equivalent. None of the 118 FA icons we map fall in this gap (verified). |

---

## 8. What NOT To Do

- **Don't build a custom font** — Material Icons has 2,200+ glyphs, more than enough
- **Don't use SCSS** — stay in LESS, consistent with Elastic and Stratus
- **Don't add runtime class switching** — one icon style always
- **Don't create utility classes** (`.xi-*`, `.mi-*`) — icons are set via Elastic's existing selectors
- **Don't modify Elastic source** — all overrides in Stratus's own LESS
- **Don't modify `_fonts.less`** — font file swapping is already done there
