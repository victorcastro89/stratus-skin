# Feature Spec: TinyMCE Email Composer — MVP Evaluation & Refactoring

**Product:** Premium Email (PE2)  
**Component:** RoundCube Plus — Email Composer  
**Status:** Draft  
**Priority:** High — MVP Blocker  
**Target:** GA / Founding Partner Release  

---

## 1. Objective

Evaluate RoundCube Plus's current TinyMCE integration and refactor it into an email-grade composer that meets MVP quality standards. The default RoundCube TinyMCE setup is built for generic web content — not email. We need to strip it down, harden it for email HTML, fix text color defaults, add dark mode support, and wire up email-specific behaviors.

**This is not about building a new editor.** It's about configuring and extending TinyMCE correctly for email within the existing RoundCube Plus codebase.

---

## 2. Current State Assessment

### What needs to be evaluated in the current RoundCube Plus implementation:

| Area | What to Check |
|------|--------------|
| TinyMCE version | Which version ships with RoundCube Plus? Is it current? Security patches? |
| Plugin list | Which plugins are loaded? Are non-email plugins active (media, code, pagebreak)? |
| Toolbar layout | Is the toolbar cluttered with irrelevant buttons? |
| Default font/color | Does the editor default to a readable dark text color or inherit broken values? |
| Dark mode behavior | Does the editor content area adapt to dark mode themes? Do composed emails look correct in light-mode clients when composed in dark mode? |
| Paste handling | What happens when pasting from Word, Google Docs, or web pages? Is markup sanitized? |
| HTML output | Does the editor output email-safe HTML with inline styles? Or does it emit `<style>` blocks and CSS classes? |
| Signature insertion | How does the current signature system work? Can users accidentally edit/delete it? |
| Reply/forward quoting | How is quoted text inserted? Is it styled? Is the cursor positioned correctly? |
| Image handling | Can users drag-drop images? Are they inlined or attached? Size limits? |
| Keyboard shortcuts | Does Ctrl+Enter send? Do standard formatting shortcuts work? |
| Mobile rendering | How does the toolbar behave on small screens? |
| Indent behavior | Does Tab/Shift+Tab indent properly in lists and blockquotes? |

### Deliverable from evaluation:
A short audit report (can be a Confluence page or Jira comment) listing:
- Current TinyMCE version
- Active plugins list
- Each area above rated: OK / Needs Fix / Missing
- Recommended changes

---

## 3. MVP Requirements

### 3.1 Default Text Color Fix

**Problem:** TinyMCE may default to no explicit text color, inheriting from CSS. This means composed emails can render as invisible/light text in some clients, or as white-on-white in dark mode contexts.

**Requirements:**
- Default text color MUST be `#1a1a1a` (near-black, softer than pure `#000000`)
- Default font: `Arial, Helvetica, sans-serif` at `14px`
- The default color must be explicitly set as an inline style on the `<body>` or root `<div>` of the composed email
- When the email leaves the editor, text color must always be present as inline CSS — never rely on class-based or stylesheet-based color
- Line-height default: `1.5` for readability

**Config reference:**
```javascript
content_style: `
  body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 14px;
    color: #1a1a1a;
    line-height: 1.5;
    margin: 8px;
  }
`,
forced_root_block: 'div',
forced_root_block_attrs: {
  'style': 'color: #1a1a1a; font-family: Arial, Helvetica, sans-serif; font-size: 14px;'
}
```

### 3.2 Dark Mode Support

**Two separate problems to solve:**

**A) Editor UI in dark mode (what the user sees while composing):**
- The TinyMCE editor chrome (toolbar, borders, menus) must follow the RoundCube Plus dark theme
- The content editing area (iframe body) should have a dark background with light text while composing
- Use TinyMCE's `skin` and `content_css` options to load dark mode stylesheets
- Toolbar icons, dropdowns, and dialogs must be legible in dark mode

**B) Email output in any client (what the recipient sees):**
- The composed email HTML must NOT include dark-mode background colors
- On send, strip any dark-mode-specific styles from the output HTML
- Output must always use the `#1a1a1a` text color and transparent/white background
- This means the editor needs a "display mode" (dark) vs "output mode" (light/neutral)

**Implementation approach:**
- Load a dark `content_css` for the editor iframe when dark mode is active
- On send/save, run a sanitizer that:
  - Removes `background-color` on body/root elements
  - Ensures `color` is set to `#1a1a1a` on root
  - Strips any class or style referencing dark mode

**Config reference:**
```javascript
// Detect dark mode from RoundCube theme
const isDarkMode = document.body.classList.contains('dark-theme') ||
                   window.matchMedia('(prefers-color-scheme: dark)').matches;

{
  skin: isDarkMode ? 'oxide-dark' : 'oxide',
  content_css: isDarkMode ? '/skins/dark-email-editor.css' : '/skins/light-email-editor.css',
}
```

### 3.3 Toolbar Configuration

**Primary toolbar (always visible):**

```
FontFamily | FontSize | Bold Italic Underline Strikethrough | ForeColor BackColor | AlignLeft AlignCenter AlignRight | BulletList NumberedList | Outdent Indent | Link Image Emoticons | SignatureInsert
```

**Overflow / "More" menu:**
- Table (simple)
- Horizontal rule
- Blockquote
- Clear formatting
- Find & replace
- Source code view (power users only)

**Remove entirely — these are not email-safe:**
- Media embed (video/audio)
- Code blocks / code sample
- Anchor links
- Word count
- Page break
- Table of contents
- Any layout plugin (columns, flexbox, etc.)
- Full-screen mode (use RoundCube's own maximize)

### 3.4 Keyboard Shortcuts

| Shortcut | Action | Priority |
|----------|--------|----------|
| `Ctrl+Enter` / `Cmd+Enter` | Send email | Must have |
| `Ctrl+S` / `Cmd+S` | Save draft | Must have |
| `Ctrl+B` | Bold | Built-in |
| `Ctrl+I` | Italic | Built-in |
| `Ctrl+U` | Underline | Built-in |
| `Ctrl+K` / `Cmd+K` | Insert link | Must have |
| `Ctrl+Z` / `Cmd+Z` | Undo | Built-in |
| `Ctrl+Shift+Z` / `Cmd+Shift+Z` | Redo | Built-in |
| `Tab` (in list) | Increase indent level | Must have |
| `Shift+Tab` (in list) | Decrease indent level | Must have |
| `Tab` (outside list) | Move to next field (To/CC/Subject) OR insert indent — configurable | Must have |
| `Ctrl+]` / `Cmd+]` | Indent paragraph | Should have |
| `Ctrl+[` / `Cmd+[` | Outdent paragraph | Should have |
| `Escape` | Exit editor focus / close dialogs | Should have |

**Indent behavior details:**
- Inside bulleted/numbered lists: `Tab` increases nesting, `Shift+Tab` decreases
- Outside lists: `Tab` inserts a paragraph indent (configurable — some users expect it to move focus to next form field)
- Recommendation for MVP: `Tab` indents inside lists, moves to next field outside lists. Add a user setting post-MVP to change this behavior
- `Ctrl+]` / `Ctrl+[` always indent/outdent the current block regardless of context

**Implementation:**
```javascript
setup: (editor) => {
  // Ctrl+Enter = Send
  editor.addShortcut('ctrl+return', 'Send email', () => {
    // Trigger RoundCube's send action
    rcmail.command('send');
  });

  // Ctrl+S = Save draft
  editor.addShortcut('ctrl+s', 'Save draft', () => {
    rcmail.command('savedraft');
  });

  // Ctrl+K = Insert link
  editor.addShortcut('ctrl+k', 'Insert link', () => {
    editor.execCommand('mceLink');
  });
}
```

### 3.5 Paste Sanitization

Pasting from external sources (Word, Google Docs, web pages, Excel) is one of the top sources of broken email HTML.

**Requirements:**
- Strip all CSS classes and IDs
- Strip `<style>` blocks entirely
- Convert Word-specific XML markup (`mso-*` styles, `<o:p>`, `<w:*>`) to clean HTML
- Preserve only: bold, italic, underline, lists, links, basic alignment
- Convert pasted tables from Excel/Sheets to simple HTML tables
- Strip background colors and images
- Convert all remaining CSS to inline styles
- Max image size on paste: auto-resize to 600px width

**Config:**
```javascript
paste_as_text: false,  // We want to preserve basic formatting
paste_word_valid_elements: 'b,strong,i,em,u,s,p,br,a[href],ul,ol,li,table,tr,td,th,h1,h2,h3,img[src|alt|width|height]',
paste_retain_style_properties: 'color,font-size,font-family,font-weight,font-style,text-decoration,text-align',
paste_preprocess: (plugin, args) => {
  // Strip mso-* styles
  // Strip <style> blocks
  // Strip classes and IDs
  // Enforce inline styles
},
```

**Plugin recommendation:** If RoundCube Plus doesn't ship with TinyMCE Premium, use the open-source `paste` plugin with a custom `paste_preprocess` function. If TinyMCE Premium is available, use **PowerPaste** — it handles Word/Google Docs cleanup far better than the free plugin.

### 3.6 Email-Safe HTML Output

Everything the editor produces must render correctly in Gmail, Outlook (desktop + web), Apple Mail, Yahoo Mail, and Thunderbird.

**Valid elements whitelist:**
```javascript
valid_elements: `
  p[style],div[style],br,
  a[href|target|style],
  img[src|alt|width|height|style],
  table[style|width|cellpadding|cellspacing|border],
  tr[style],td[style|width|colspan|rowspan],th[style|width|colspan|rowspan],
  b,strong,i,em,u,s,
  ul[style],ol[style],li[style],
  blockquote[style],
  h1[style],h2[style],h3[style],
  hr,
  span[style]
`,
```

**Valid inline styles whitelist:**
```javascript
valid_styles: {
  '*': 'color,background-color,font-size,font-family,text-align,text-decoration,font-weight,font-style,padding,margin,border,width,height,line-height,border-collapse,vertical-align'
},
```

**On send — run a CSS inliner:**
Any `<style>` blocks that survive editing must be converted to inline styles before the email is sent. Use a library like Juice (Node) or equivalent PHP library in RoundCube's send pipeline.

### 3.7 Reply / Forward Quote Handling

**Requirements:**
- On Reply: insert quoted text wrapped in `<blockquote>` with left border styling
- Attribution line above the quote: `On {date}, {sender name} <{email}> wrote:`
- Cursor positioned above the quote with a blank line separator
- Signature inserted between cursor position and the quote
- Forward: full original message included below a separator line

**Quote styling:**
```html
<blockquote style="margin: 0 0 0 0.8em; border-left: 2px solid #ccc; padding-left: 0.8em; color: #555;">
  <!-- quoted content -->
</blockquote>
```

### 3.8 Signature Handling

**Requirements:**
- Auto-insert on compose, reply, and forward
- Position: above the quote on reply/forward, at bottom on new compose
- Standard `-- ` delimiter line (two dashes + space) above signature
- Signature container should be wrapped in a `noneditable` or clearly demarcated block to prevent accidental edits
- If user has multiple signatures: dropdown selector in toolbar (if supported by RoundCube Plus, otherwise defer to post-MVP)

### 3.9 Image Handling

**Requirements:**
- Drag-and-drop images into the editor
- Paste from clipboard (screenshots)
- Auto-resize to max 600px width on insert
- File size limit: 5MB per image, with clear error message
- Images sent as CID-attached inline images (not base64 in HTML — base64 is blocked by many clients)
- Alt text: prompt or auto-generate placeholder

### 3.10 Autosave / Draft Recovery

**Requirements:**
- Autosave every 30 seconds (or on blur/tab switch)
- Save to RoundCube's existing draft mechanism
- On re-open of an interrupted compose: prompt "You have an unsaved draft — restore?"
- Store: HTML body, recipients, subject, CC/BCC state

**Config:**
```javascript
autosave_interval: '30s',
autosave_retention: '1440m',  // 24 hours
autosave_ask_before_unload: true,
```

### 3.11 Font Picker

**Email-safe fonts only. Do not allow web fonts.**

```javascript
font_family_formats: `
  Arial=arial, helvetica, sans-serif;
  Verdana=verdana, geneva, sans-serif;
  Georgia=georgia, palatino, serif;
  Tahoma=tahoma, arial, sans-serif;
  Times New Roman=times new roman, times, serif;
  Courier New=courier new, courier, monospace;
  Trebuchet MS=trebuchet ms, arial, sans-serif;
`,
font_size_formats: '10px 11px 12px 14px 16px 18px 20px 24px',
```

### 3.12 Mobile Composer UX

**Requirements:**
- Single-row toolbar with horizontal scroll or overflow menu
- Larger touch targets (min 44px tap area)
- Auto-grow editor height (no fixed-height box on mobile)
- Bottom-anchored toolbar option (thumb-reachable)
- No hover-dependent interactions

---

## 4. Plugin Recommendations

### Required (Free / Open-Source TinyMCE plugins)

| Plugin | Purpose | Notes |
|--------|---------|-------|
| `autolink` | Auto-detect and convert typed URLs to links | Essential for email |
| `lists` | Bulleted and numbered lists with indent/outdent | Core formatting |
| `link` | Insert/edit hyperlinks | Core |
| `image` | Insert images (drag-drop, upload) | Core — needs customization for CID handling |
| `emoticons` | Emoji picker | Expected in modern email |
| `searchreplace` | Find & replace in composer | Overflow menu |
| `table` | Simple table insertion | Overflow menu — lock down to simple tables only |
| `autosave` | Periodic draft saving | Core |
| `noneditable` | Protect signature blocks from accidental editing | Core |
| `charmap` | Special characters | Overflow menu |
| `paste` (free) | Basic paste sanitization | Minimum viable — see PowerPaste below |

### Recommended (TinyMCE Premium — evaluate licensing)

| Plugin | Purpose | Why it matters |
|--------|---------|---------------|
| **PowerPaste** | Superior paste cleaning from Word, Google Docs, web | The free `paste` plugin is mediocre. PowerPaste is significantly better at stripping Office markup. High impact on user experience. |
| **Spell Checker Pro** | Real-time spellcheck with grammar hints | Browser-native spellcheck works for MVP but lacks grammar. Evaluate for post-MVP. |
| **Enhanced Image Editing** | Resize, crop, rotate images inline | Nice to have. Defer to post-MVP unless licensing is already covered. |

### Custom Plugins to Build

| Plugin | Purpose | Priority |
|--------|---------|----------|
| `signature-insert` | Toolbar button to insert/switch signatures | MVP |
| `send-shortcut` | Ctrl+Enter to trigger RoundCube send | MVP |
| `email-sanitizer` | On-send HTML cleanup (strip dark mode, force inline styles, validate output) | MVP |
| `ai-compose-hook` | Placeholder UI for future AI writing assist (empty shell, no functionality yet) | Post-MVP but design hook now |

### Plugins to Explicitly Disable

| Plugin | Why |
|--------|-----|
| `media` | Video/audio won't render in email |
| `codesample` | Code blocks are for developers, not email |
| `anchor` | Anchor links don't work in email |
| `pagebreak` | Not an email concept |
| `fullscreen` | Use RoundCube's window management |
| `toc` | Table of contents is not relevant |
| `wordcount` | Unnecessary for email composition |
| `preview` | Email preview should be a separate feature, not TinyMCE's |

---

## 5. Full Recommended TinyMCE Configuration (MVP)

```javascript
tinymce.init({
  selector: '#compose-editor',

  // Plugins
  plugins: [
    'autolink', 'lists', 'link', 'image', 'charmap',
    'searchreplace', 'table', 'autosave', 'noneditable', 'emoticons'
  ],

  // Toolbar
  toolbar: [
    'fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright | bullist numlist | outdent indent | link image emoticons | signature_insert'
  ],
  toolbar_mode: 'sliding',  // Overflow to sliding row on small screens
  menubar: false,
  statusbar: false,

  // Typography defaults
  content_style: `
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 14px;
      color: #1a1a1a;
      line-height: 1.5;
      margin: 8px;
      background: transparent;
    }
    blockquote {
      margin: 0 0 0 0.8em;
      border-left: 2px solid #ccc;
      padding-left: 0.8em;
      color: #555;
    }
    img { max-width: 100%; height: auto; }
  `,
  forced_root_block: 'div',
  forced_root_block_attrs: {
    'style': 'color: #1a1a1a; font-family: Arial, Helvetica, sans-serif; font-size: 14px;'
  },

  // Fonts
  font_family_formats: 'Arial=arial,helvetica,sans-serif; Verdana=verdana,geneva,sans-serif; Georgia=georgia,palatino,serif; Tahoma=tahoma,arial,sans-serif; Times New Roman=times new roman,times,serif; Courier New=courier new,courier,monospace; Trebuchet MS=trebuchet ms,arial,sans-serif',
  font_size_formats: '10px 11px 12px 14px 16px 18px 20px 24px',

  // Dark mode
  skin: isDarkMode ? 'oxide-dark' : 'oxide',
  content_css: isDarkMode ? 'dark-email-editor' : 'default',

  // Paste
  paste_as_text: false,
  paste_word_valid_elements: 'b,strong,i,em,u,s,p,br,a[href],ul,ol,li,table,tr,td,th,h1,h2,h3,img[src|alt|width|height]',
  paste_retain_style_properties: 'color,font-size,font-family,font-weight,font-style,text-decoration,text-align',

  // HTML output safety
  valid_elements: 'p[style],div[style],br,a[href|target|style],img[src|alt|width|height|style],table[style|width|cellpadding|cellspacing|border],tr[style],td[style|width|colspan|rowspan],th[style|width|colspan|rowspan],b,strong,i,em,u,s,ul[style],ol[style],li[style],blockquote[style],h1[style],h2[style],h3[style],hr,span[style]',
  valid_styles: {
    '*': 'color,background-color,font-size,font-family,text-align,text-decoration,font-weight,font-style,padding,margin,border,width,height,line-height,border-collapse,vertical-align,list-style-type'
  },

  // Links
  default_link_target: '_blank',
  link_default_protocol: 'https',
  link_assume_external_targets: true,

  // Images
  image_advtab: false,
  image_dimensions: true,
  images_upload_handler: emailImageUploadHandler,  // Custom CID handler
  automatic_uploads: true,

  // Autosave
  autosave_interval: '30s',
  autosave_retention: '1440m',
  autosave_ask_before_unload: true,

  // Spellcheck
  browser_spellcheck: true,

  // Behavior
  resize: false,
  min_height: 300,
  autoresize_bottom_margin: 50,

  // Setup
  setup: (editor) => {
    // --- Ctrl+Enter = Send ---
    editor.addShortcut('ctrl+return', 'Send', () => rcmail.command('send'));
    editor.addShortcut('meta+return', 'Send', () => rcmail.command('send'));

    // --- Ctrl+S = Save Draft ---
    editor.addShortcut('ctrl+s', 'Draft', () => rcmail.command('savedraft'));
    editor.addShortcut('meta+s', 'Draft', () => rcmail.command('savedraft'));

    // --- Ctrl+K = Insert Link ---
    editor.addShortcut('ctrl+k', 'Link', () => editor.execCommand('mceLink'));
    editor.addShortcut('meta+k', 'Link', () => editor.execCommand('mceLink'));

    // --- Signature insert button ---
    editor.ui.registry.addButton('signature_insert', {
      icon: 'edit-block',
      tooltip: 'Insert Signature',
      onAction: () => insertSignature(editor)
    });

    // --- On send: sanitize HTML for email ---
    editor.on('submit', () => {
      sanitizeEmailOutput(editor);
    });
  }
});
```

---

## 6. Acceptance Criteria

- [ ] Default composed email text is `#1a1a1a` on transparent/white background — verified in Gmail, Outlook, Apple Mail
- [ ] Dark mode: editor UI follows RoundCube dark theme; composed email output renders correctly in light-mode email clients
- [ ] Toolbar contains only email-relevant actions (no media embed, code blocks, anchors, etc.)
- [ ] Pasting from Word/Google Docs produces clean HTML without `mso-*` styles or `<style>` blocks
- [ ] All HTML output uses inline styles only — no CSS classes or `<style>` blocks in sent email
- [ ] `Ctrl+Enter` / `Cmd+Enter` sends the email
- [ ] `Ctrl+S` / `Cmd+S` saves draft
- [ ] `Tab` / `Shift+Tab` indent/outdent in lists; `Tab` moves focus outside lists
- [ ] `Ctrl+]` / `Ctrl+[` indent/outdent paragraphs
- [ ] Reply/forward inserts quoted text in styled `<blockquote>` with attribution line
- [ ] Cursor positioned above quote on reply
- [ ] Signature auto-inserted in correct position (above quote on reply, bottom on compose)
- [ ] Drag-drop and clipboard-paste images work, auto-resized to max 600px width
- [ ] Images sent as CID attachments, not base64
- [ ] Autosave fires every 30 seconds; draft recovery works on interrupted compose
- [ ] Only email-safe fonts available in picker (no web fonts)
- [ ] Font size picker offers preset sizes only
- [ ] Source code view accessible from overflow menu for power users
- [ ] Mobile: toolbar is usable on 375px-wide screens (single row + overflow)
- [ ] No irrelevant plugins loaded (media, codesample, anchor, pagebreak, etc.)

---

## 7. Out of Scope (Post-MVP)

- AI Smart Compose / autocomplete suggestions
- AI rewrite / tone adjustment
- Scheduled send
- Email templates library
- Markdown mode toggle
- Multiple signature switcher (unless RoundCube Plus supports it natively)
- Read receipt toggle in composer
- Grammar checker (TinyMCE Premium Spell Checker Pro)
- Enhanced image editing (crop, rotate in editor)
- Link preview cards
- Collapsible quote blocks
- Undo send

---

## 8. Dependencies & Open Questions

### Dependencies
- RoundCube Plus current TinyMCE version and plugin bundle — need audit first
- Tecorama (RoundCube Plus vendor) — confirm which TinyMCE plugins are included in OEM license
- TinyMCE Premium licensing cost if PowerPaste is needed — get quote for our expected seat volume
- RoundCube dark mode theme — confirm CSS class names/selectors for dark mode detection
- CID image attachment pipeline — confirm RoundCube's current approach

### Open Questions
1. **PowerPaste licensing:** Is TinyMCE Premium included in RoundCube Plus OEM bundle, or do we need a separate license? Cost?
2. **TinyMCE version:** Can we upgrade to latest (6.x+) if RoundCube ships an older version, or are there compatibility risks?
3. **Tab behavior default:** Should `Tab` indent or move focus when outside a list? Need user testing input.
4. **Dark mode detection:** Does RoundCube Plus use `prefers-color-scheme`, a body class, or a custom mechanism?
5. **Image pipeline:** Does RoundCube Plus already handle CID attachments for inline images, or do we need to build this?
6. **On-send sanitizer:** Should this live in TinyMCE (JavaScript, client-side) or in RoundCube's PHP send pipeline (server-side)? Server-side is more reliable but adds latency. Recommendation: both — client-side for UX, server-side as safety net.

---

