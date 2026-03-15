/**
 * Stratus Helper – Client-side JS (DEBUG BUILD)
 *
 * @version 0.1.0-debug
 */
(function () {
  'use strict';

  if (!window.rcmail) return;

  // ──────────────────────────────────────────
  // DEBUG CONTROLS (toggle in console if needed)
  // ──────────────────────────────────────────
  // window.STRATUS_HOVER_DEBUG = true/false
  // window.STRATUS_HOVER_DEBUG_LEVEL = 0..3
  if (typeof window.STRATUS_HOVER_DEBUG === 'undefined') window.STRATUS_HOVER_DEBUG = true;
  if (typeof window.STRATUS_HOVER_DEBUG_LEVEL === 'undefined') window.STRATUS_HOVER_DEBUG_LEVEL = 3;

  function dbgEnabled() { return !!window.STRATUS_HOVER_DEBUG; }
  function dbgLevel() { return +window.STRATUS_HOVER_DEBUG_LEVEL || 0; }

  function dbg() {
    if (!dbgEnabled()) return;
    if (dbgLevel() < 1) return;
    try { console.log.apply(console, arguments); } catch (e) {}
  }
  function dbg2() {
    if (!dbgEnabled()) return;
    if (dbgLevel() < 2) return;
    try { console.log.apply(console, arguments); } catch (e) {}
  }
  function dbg3() {
    if (!dbgEnabled()) return;
    if (dbgLevel() < 3) return;
    try { console.log.apply(console, arguments); } catch (e) {}
  }
  // ──────────────────────────────────────────
  // Hook debug wrappers once
  // ──────────────────────────────────────────
  function installDebugHooksOnce() {
    if (!dbgEnabled()) return;
    if (window.__STRATUS_DEBUG_HOOKS_INSTALLED) return;
    window.__STRATUS_DEBUG_HOOKS_INSTALLED = true;

    // Wrap rcmail.command
    if (rcmail && typeof rcmail.command === 'function' && !rcmail.command.__stratusWrapped) {
      var _cmd = rcmail.command;
      var wrapped = function (cmd, prop, obj, evt) {
        try {
          dbg2('%c[STRATUS][COMMAND] →', 'color:#9b59b6', cmd, { prop: prop, obj: obj, evt: evt });
        } catch (e) {}
        var ret;
        try {
          ret = _cmd.apply(rcmail, arguments);
        } catch (err) {
          dbg('%c[STRATUS][COMMAND][ERROR]', 'color:#e74c3c', cmd, err);
          throw err;
        }
        dbg2('%c[STRATUS][COMMAND] ← return', 'color:#9b59b6', cmd, ret);
        return ret;
      };
      wrapped.__stratusWrapped = true;
      rcmail.command = wrapped;
      dbg('%c[STRATUS] Wrapped rcmail.command for debug', 'color:#2ecc71');
    }

    // Wrap rcmail.http_post (Roundcube AJAX)
    if (rcmail && typeof rcmail.http_post === 'function' && !rcmail.http_post.__stratusWrapped) {
      var _post = rcmail.http_post;
      var wrappedPost = function (action, data, lock) {
        dbg2('%c[STRATUS][HTTP_POST] →', 'color:#3498db', action, data, { lock: lock });
        var ret;
        try {
          ret = _post.apply(rcmail, arguments);
        } catch (err) {
          dbg('%c[STRATUS][HTTP_POST][ERROR]', 'color:#e74c3c', action, err);
          throw err;
        }
        dbg2('%c[STRATUS][HTTP_POST] ← return', 'color:#3498db', action, ret);
        return ret;
      };
      wrappedPost.__stratusWrapped = true;
      rcmail.http_post = wrappedPost;
      dbg('%c[STRATUS] Wrapped rcmail.http_post for debug', 'color:#2ecc71');
    }

    // jQuery global AJAX sniffing (Roundcube uses jQuery)
    var $ = window.jQuery || window.$;
    if ($ && $.fn && $.ajax && !window.__STRATUS_JQ_AJAX_HOOKED) {
      window.__STRATUS_JQ_AJAX_HOOKED = true;

      $(document).on('ajaxSend.stratusHoverDebug', function (_e, xhr, settings) {
        try {
          var url = settings && settings.url;
          var data = settings && settings.data;
          // Log everything at max debug, but highlight possible flag-related calls
          var interesting = /flag|mark|_uid|toggle_flag/i.test(String(url)) || /flag|mark|_uid|toggle_flag/i.test(String(data));
          if (dbgLevel() >= 3 || interesting) {
            dbg3('%c[STRATUS][AJAX SEND]', 'color:#f39c12', { url: url, type: settings.type, data: data });
          }
        } catch (e) {}
      });

      $(document).on('ajaxComplete.stratusHoverDebug', function (_e, xhr, settings) {
        try {
          var url = settings && settings.url;
          var status = xhr && xhr.status;
          var interesting = /flag|mark|_uid|toggle_flag/i.test(String(url));
          if (dbgLevel() >= 3 || interesting) {
            dbg3('%c[STRATUS][AJAX DONE]', 'color:#f39c12', { url: url, status: status, response: (xhr && xhr.responseText ? String(xhr.responseText).slice(0, 200) : null) });
          }
        } catch (e) {}
      });

      dbg('%c[STRATUS] Hooked jQuery ajaxSend/ajaxComplete for debug', 'color:#2ecc71');
    }
  }

  // ══════════════════════════════════════════════
  //  TinyMCE Email Composer Configuration
  //  Must run at load time (before editor.js reads window.rcmail_editor_settings)
  // ══════════════════════════════════════════════
  initTinyMCEEmailComposer();

  rcmail.addEventListener('init', function () {

    installDebugHooksOnce();

    // ──────────────────────────────────────────
    //  1. Color Scheme Switching
    // ──────────────────────────────────────────

    rcmail.addEventListener('plugin.stratus.scheme_applied', function (data) {
      if (!data) return;
      applyScheme(data.primary, data.primary_dark);
    });

    // ──────────────────────────────────────────
    //  2. Font Switching
    // ──────────────────────────────────────────

    rcmail.addEventListener('plugin.stratus.font_applied', function (data) {
      if (!data) return;
      applyFont(data.family, data.url);
    });

    // ──────────────────────────────────────────
    //  3. Settings Page Live Preview
    // ──────────────────────────────────────────

    if (rcmail.env.task === 'settings') {
      initSettingsPreview();
    }

    // ──────────────────────────────────────────
    //  4. Dark Mode — iframe propagation + TinyMCE
    // ──────────────────────────────────────────

    initDarkModeFramePropagation();
    if (document.documentElement.classList.contains('dark-mode')) {
      initTinyMCEDarkMode();
    }
    initDarkModeObserver();

    // ──────────────────────────────────────────
    //  5. Unified Hover Actions (mail task)
    // ──────────────────────────────────────────

    if (rcmail.env.task === 'mail') {
      initUnifiedHoverActions();
    }

    // ──────────────────────────────────────────
    //  6. Smart Bar Controller (mail task)
    // ──────────────────────────────────────────

    if (rcmail.env.task === 'mail') {
      initSmartBarController();
    }

    // ──────────────────────────────────────────
    //  7. Search empty-state spinner fix
    // ──────────────────────────────────────────

    if (rcmail.env.task === 'mail') {
      initSearchEmptyState();
    }
  });

  // ══════════════════════════════════════════════
  //  Color Scheme Helpers
  // ══════════════════════════════════════════════

  function applyScheme(primary, primaryDark) {
    var root = document.documentElement;
    root.style.setProperty('--stratus-primary', primary);
    root.style.setProperty('--stratus-primary-dark', primaryDark);
    root.style.setProperty('--stratus-primary-rgb', hexToRgb(primary));
    root.style.setProperty('--stratus-primary-dark-rgb', hexToRgb(primaryDark));
  }

  function hexToRgb(hex) {
    hex = hex.replace(/^#/, '');
    if (hex.length === 3) {
      hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }
    var r = parseInt(hex.substring(0, 2), 16);
    var g = parseInt(hex.substring(2, 4), 16);
    var b = parseInt(hex.substring(4, 6), 16);
    return r + ', ' + g + ', ' + b;
  }

  // ══════════════════════════════════════════════
  //  Font Helpers
  // ══════════════════════════════════════════════

  function applyFont(family, url) {
    document.documentElement.style.setProperty('--stratus-font-family', family);
    var existingLink = document.getElementById('stratus-helper-font');

    if (url) {
      if (existingLink) {
        existingLink.href = url;
      } else {
        var link = document.createElement('link');
        link.id = 'stratus-helper-font';
        link.rel = 'stylesheet';
        link.href = url;
        document.head.appendChild(link);
      }
    } else if (existingLink) {
      existingLink.parentNode.removeChild(existingLink);
    }
  }

  // ══════════════════════════════════════════════
  //  Settings Page Preview
  // ══════════════════════════════════════════════

  function initSettingsPreview() {
    var schemeSelect = document.getElementById('ff_stratus_color_scheme');
    if (schemeSelect) {
      schemeSelect.addEventListener('change', function () {
        rcmail.http_post('plugin.stratus.set_scheme', { _scheme: this.value });
      });
    }

    var fontSelect = document.getElementById('ff_stratus_font_family');
    if (fontSelect) {
      fontSelect.addEventListener('change', function () {
        rcmail.http_post('plugin.stratus.set_font', { _font: this.value });
      });
    }
  }

  // ══════════════════════════════════════════════
  //  TinyMCE Email Composer Configuration
  // ══════════════════════════════════════════════

  function initTinyMCEEmailComposer() {
    var DEFAULT_TEXT_COLOR = '#1a1a1a';
    var DEFAULT_FONT_FAMILY = 'Arial, Helvetica, sans-serif';
    var DEFAULT_FONT_SIZE = '14px';
    var DEFAULT_LINE_HEIGHT = '1.5';
    var isDark = document.documentElement.classList.contains('dark-mode');

    // Email-safe valid elements whitelist
    var validElements = [
      'p[style]', 'div[style]', 'br',
      'a[href|target|style]',
      'img[src|alt|width|height|style]',
      'table[style|width|cellpadding|cellspacing|border]',
      'tr[style]',
      'td[style|width|colspan|rowspan]', 'th[style|width|colspan|rowspan]',
      'b', 'strong', 'i', 'em', 'u', 's',
      'ul[style]', 'ol[style]', 'li[style]',
      'blockquote[style]',
      'h1[style]', 'h2[style]', 'h3[style]',
      'hr', 'span[style|id|class]',
      'font[face|size|color|style]'
    ].join(',');

    var validStyles = {
      '*': 'color,background-color,font-size,font-family,text-align,text-decoration,'
        + 'font-weight,font-style,padding,margin,border,width,height,line-height,'
        + 'border-collapse,vertical-align,list-style-type,border-left,padding-left,'
        + 'margin-left,margin-right,margin-top,margin-bottom,border-top,border-bottom,'
        + 'border-color,border-style,border-width,max-width,float,display'
    };

    // Email-safe fonts only (no web fonts)
    var fontFormats = [
      'Arial=arial, helvetica, sans-serif',
      'Verdana=verdana, geneva, sans-serif',
      'Georgia=georgia, palatino, serif',
      'Tahoma=tahoma, arial, sans-serif',
      'Times New Roman=times new roman, times, serif',
      'Courier New=courier new, courier, monospace',
      'Trebuchet MS=trebuchet ms, arial, sans-serif'
    ].join('; ');

    // Build content_style — when dark mode is active, include dark background
    // directly so TinyMCE applies it at init (no white flash)
    var contentStyle = 'body {'
      + ' font-family: ' + DEFAULT_FONT_FAMILY + ';'
      + ' font-size: ' + DEFAULT_FONT_SIZE + ';'
      + ' color: ' + (isDark ? '#c8d0e8' : DEFAULT_TEXT_COLOR) + ';'
      + ' line-height: ' + DEFAULT_LINE_HEIGHT + ';'
      + ' margin: 8px;'
      + ' background: ' + (isDark ? '#1a1f36' : 'transparent') + ';'
      + '}'
      + ' blockquote {'
      + '   margin: 0 0 0 0.8em;'
      + '   border-left: 2px solid ' + (isDark ? '#7986cb' : '#ccc') + ';'
      + '   padding-left: 0.8em;'
      + '   color: ' + (isDark ? '#7e8aad' : '#555') + ';'
      + ' }'
      + ' img { max-width: 100%; height: auto; }';

    // Paste sanitization: strip non-email-safe markup
    function pastePreprocess(plugin, args) {
      var c = args.content;
      if (!c) return;
      c = c.replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '');
      c = c.replace(/<(meta|link|title)[^>]*\/?>/gi, '');
      c = c.replace(/<\/?[owv]:[^>]*>/gi, '');
      c = c.replace(/\bmso-[^;:"']+:[^;:"']+;?/gi, '');
      c = c.replace(/\s+class\s*=\s*"[^"]*"/gi, '');
      c = c.replace(/\s+class\s*=\s*'[^']*'/gi, '');
      c = c.replace(/\s+id\s*=\s*"[^"]*"/gi, '');
      c = c.replace(/\s+id\s*=\s*'[^']*'/gi, '');
      c = c.replace(/\s+data-[a-z0-9-]+\s*=\s*"[^"]*"/gi, '');
      c = c.replace(/\s+data-[a-z0-9-]+\s*=\s*'[^']*'/gi, '');
      c = c.replace(/\s+xmlns[:\w]*\s*=\s*"[^"]*"/gi, '');
      c = c.replace(/<span\s*>([\s\S]*?)<\/span>/gi, '$1');
      c = c.replace(/<!--[\s\S]*?-->/g, '');
      args.content = c;
    }

    // On-send HTML sanitizer — strip dark mode artifacts, ensure email-safe output
    function sanitizeEmailOutput(editor) {
      var body = editor.getBody();
      if (!body) return;

      body.style.color = DEFAULT_TEXT_COLOR;
      body.style.removeProperty('background-color');
      body.style.removeProperty('background');

      var doc = editor.getDoc();
      var darkStyle = doc && doc.getElementById('stratus-tinymce-dark');
      if (darkStyle) darkStyle.parentNode.removeChild(darkStyle);
      if (doc && doc.documentElement) doc.documentElement.classList.remove('dark-mode');

      // Strip dark-mode color artifacts from elements
      var all = body.querySelectorAll('*');
      for (var i = 0; i < all.length; i++) {
        var el = all[i];
        if (el.parentNode === body && el.style.backgroundColor) {
          if (_isDarkBg(el.style.backgroundColor)) {
            el.style.removeProperty('background-color');
          }
        }
        if (el.style.color && _isLightColor(el.style.color)) {
          el.style.removeProperty('color');
        }
      }

      // Ensure root blocks have default styles
      var roots = body.querySelectorAll(':scope > div, :scope > p');
      for (var j = 0; j < roots.length; j++) {
        var blk = roots[j];
        if (!blk.style.color) blk.style.color = DEFAULT_TEXT_COLOR;
        if (!blk.style.fontFamily) blk.style.fontFamily = DEFAULT_FONT_FAMILY;
        if (!blk.style.fontSize) blk.style.fontSize = DEFAULT_FONT_SIZE;
      }
    }

    function _parseColor(c) {
      if (!c) return null;
      var m = c.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
      if (m) return [+m[1], +m[2], +m[3]];
      var h = c.match(/^#([0-9a-f]{3,8})$/i);
      if (h) {
        var x = h[1];
        if (x.length === 3) x = x[0]+x[0]+x[1]+x[1]+x[2]+x[2];
        return [parseInt(x.substring(0,2),16), parseInt(x.substring(2,4),16), parseInt(x.substring(4,6),16)];
      }
      return null;
    }
    function _lum(rgb) { return (rgb[0]*299 + rgb[1]*587 + rgb[2]*114) / 1000; }
    function _isDarkBg(c) { var rgb = _parseColor(c); return rgb && _lum(rgb) < 50; }
    function _isLightColor(c) { var rgb = _parseColor(c); return rgb && _lum(rgb) > 200; }

    // Setup callback — keyboard shortcuts + on-send sanitizer
    function setupCallback(editor) {
      // Ctrl+Enter / Cmd+Enter = Send
      editor.addShortcut('ctrl+return', 'Send email', function() {
        if (window.rcmail) rcmail.command('send');
      });
      editor.addShortcut('meta+return', 'Send email', function() {
        if (window.rcmail) rcmail.command('send');
      });

      // Ctrl+S / Cmd+S = Save Draft
      editor.addShortcut('ctrl+s', 'Save draft', function() {
        if (window.rcmail) rcmail.command('savedraft');
      });
      editor.addShortcut('meta+s', 'Save draft', function() {
        if (window.rcmail) rcmail.command('savedraft');
      });

      // Ctrl+K / Cmd+K = Insert Link
      editor.addShortcut('ctrl+k', 'Insert link', function() {
        editor.execCommand('mceLink');
      });
      editor.addShortcut('meta+k', 'Insert link', function() {
        editor.execCommand('mceLink');
      });

      // On submit (send): sanitize HTML output
      editor.on('submit', function() { sanitizeEmailOutput(editor); });
      editor.on('SaveContent', function() { sanitizeEmailOutput(editor); });

      // Tab/Shift+Tab: indent/outdent in lists AND normal text
      editor.on('keydown', function(e) {
        if (e.keyCode !== 9) return; // Tab key only
        e.preventDefault();
        if (e.shiftKey) {
          editor.execCommand('Outdent');
        } else {
          editor.execCommand('Indent');
        }
      });

      // Ctrl+] / Ctrl+[ = Indent/Outdent
      editor.addShortcut('ctrl+]', 'Indent', function() { editor.execCommand('Indent'); });
      editor.addShortcut('meta+]', 'Indent', function() { editor.execCommand('Indent'); });
      editor.addShortcut('ctrl+[', 'Outdent', function() { editor.execCommand('Outdent'); });
      editor.addShortcut('meta+[', 'Outdent', function() { editor.execCommand('Outdent'); });
    }

    // Set the global config object that editor.js reads
    window.rcmail_editor_settings = {
      plugins: 'autolink lists link image charmap searchreplace table '
        + 'paste emoticons noneditable',
      toolbar: 'fontselect fontsizeselect | bold italic underline strikethrough | '
        + 'forecolor backcolor | alignleft aligncenter alignright | '
        + 'bullist numlist | outdent indent | link image emoticons | $extra',
      toolbar_drawer: 'sliding',
      menubar: false,
      statusbar: false,
      content_style: contentStyle,
      forced_root_block: 'div',
      forced_root_block_attrs: {
        'style': 'color: ' + DEFAULT_TEXT_COLOR + ';'
          + ' font-family: ' + DEFAULT_FONT_FAMILY + ';'
          + ' font-size: ' + DEFAULT_FONT_SIZE + ';'
      },
      font_formats: fontFormats,
      fontsize_formats: '10px 11px 12px 14px 16px 18px 20px 24px',
      skin: isDark ? 'oxide-dark' : 'oxide',
      valid_elements: validElements,
      valid_styles: validStyles,
      paste_as_text: false,
      paste_word_valid_elements: 'b,strong,i,em,u,s,p,br,a[href],ul,ol,li,'
        + 'table,tr,td,th,h1,h2,h3,img[src|alt|width|height],div,span,blockquote,hr',
      paste_retain_style_properties: 'color,font-size,font-family,font-weight,'
        + 'font-style,text-decoration,text-align,background-color',
      paste_preprocess: pastePreprocess,
      default_link_target: '_blank',
      link_default_protocol: 'https',
      image_advtab: false,
      image_dimensions: true,
      paste_data_images: true,
      browser_spellcheck: true,
      resize: false,
      min_height: 300,
      setup_callback: setupCallback
    };
  }

  // ══════════════════════════════════════════════
  //  Dark Mode — iframe propagation
  // ══════════════════════════════════════════════

  function initDarkModeFramePropagation() {
    function syncDarkMode(frame) {
      try {
        var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
        if (!doc || !doc.documentElement) return;
        var isDark = document.documentElement.classList.contains('dark-mode');
        doc.documentElement.classList[isDark ? 'add' : 'remove']('dark-mode');
      } catch (e) {}
    }

    ['preferences-frame', 'contentframe', 'messagecontframe'].forEach(function (id) {
      var frame = document.getElementById(id);
      if (!frame) return;
      syncDarkMode(frame);
      frame.addEventListener('load', function () { syncDarkMode(this); });
    });

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType !== 1) return;
          if (node.tagName === 'IFRAME') node.addEventListener('load', function () { syncDarkMode(this); });

          var nested = node.querySelectorAll && node.querySelectorAll('iframe');
          if (nested) Array.prototype.forEach.call(nested, function (f) {
            f.addEventListener('load', function () { syncDarkMode(this); });
          });
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  // ══════════════════════════════════════════════
  //  Dark Mode — TinyMCE (iframe content + skin swap)
  // ══════════════════════════════════════════════

  // Dark mode content styles for the editor iframe.
  // Display-only — stripped on send by the sanitizer.
  var _tinymceDarkCSS =
    'html, body {' +
    '  background-color: #1a1f36 !important;' +
    '  color: #c8d0e8 !important;' +
    '}' +
    'a { color: #7986cb !important; }' +
    'blockquote {' +
    '  border-left: 3px solid #7986cb !important;' +
    '  color: #7e8aad !important;' +
    '}' +
    'pre, code {' +
    '  background: #212845 !important;' +
    '  color: #c8d0e8 !important;' +
    '  border-color: #2a3050 !important;' +
    '}' +
    'hr { border-color: #2a3050 !important; }' +
    'table, td, th {' +
    '  border-color: #2a3050 !important;' +
    '}' +
    'div[style*="color: #1a1a1a"], div[style*="color:#1a1a1a"] {' +
    '  color: #c8d0e8 !important;' +
    '}';

  function _applyDarkToEditor(editor) {
    var doc = editor.getDoc ? editor.getDoc() : null;
    if (!doc || !doc.head) return;
    if (doc.documentElement) doc.documentElement.classList.add('dark-mode');
    // Inject dark stylesheet if not already present
    if (!doc.getElementById('stratus-tinymce-dark')) {
      var style = doc.createElement('style');
      style.id = 'stratus-tinymce-dark';
      style.textContent = _tinymceDarkCSS;
      doc.head.appendChild(style);
    }
    // Also set body inline styles (covers toggling from light → dark mid-session)
    var body = doc.body;
    if (body) {
      body.style.backgroundColor = '#1a1f36';
      body.style.color = '#c8d0e8';
    }
  }

  function _applyLightToEditor(editor) {
    var doc = editor.getDoc ? editor.getDoc() : null;
    if (!doc) return;
    if (doc.documentElement) doc.documentElement.classList.remove('dark-mode');
    // Remove injected dark stylesheet
    var el = doc.getElementById('stratus-tinymce-dark');
    if (el) el.parentNode.removeChild(el);
    // Reset body inline styles back to light (overrides content_style from dark init)
    var body = doc.body;
    if (body) {
      body.style.backgroundColor = 'transparent';
      body.style.color = '#1a1a1a';
    }
  }

  function _removeDarkFromEditor(editor) {
    _applyLightToEditor(editor);
  }

  /**
   * Swap the TinyMCE skin stylesheet between oxide and oxide-dark.
   * TinyMCE 5 loads skin.min.css in document.head and in the editor iframe.
   */
  function _swapTinyMCESkin(toDark) {
    // Swap in the main document head (toolbar/chrome skin)
    _swapSkinLinks(document, toDark);

    // Swap in each editor iframe (content skin)
    if (!window.tinymce) return;
    var editors = window.tinymce.editors || [];
    for (var i = 0; i < editors.length; i++) {
      var ed = editors[i];
      if (!ed.initialized) continue;
      var doc = ed.getDoc ? ed.getDoc() : null;
      if (doc) _swapSkinLinks(doc, toDark);
    }
  }

  function _swapSkinLinks(doc, toDark) {
    var links = doc.querySelectorAll('link[rel="stylesheet"]');
    for (var i = 0; i < links.length; i++) {
      var href = links[i].getAttribute('href') || '';
      if (href.indexOf('/skins/ui/oxide') === -1) continue;
      if (toDark && href.indexOf('oxide-dark') === -1) {
        links[i].setAttribute('href', href.replace('/skins/ui/oxide/', '/skins/ui/oxide-dark/'));
      } else if (!toDark && href.indexOf('oxide-dark') !== -1) {
        links[i].setAttribute('href', href.replace('/skins/ui/oxide-dark/', '/skins/ui/oxide/'));
      }
    }
  }

  function initTinyMCEDarkMode() {
    function hookTinyMCE() {
      window.tinymce.on('AddEditor', function (e) {
        e.editor.on('init', function () { _applyDarkToEditor(this); });
      });
      var editors = window.tinymce.editors || [];
      for (var i = 0; i < editors.length; i++) {
        var ed = editors[i];
        if (ed.initialized) {
          _applyDarkToEditor(ed);
        } else {
          ed.on('init', function () { _applyDarkToEditor(this); });
        }
      }
    }

    if (window.tinymce) hookTinyMCE();
    else {
      var attempts = 0;
      var timer = setInterval(function () {
        attempts++;
        if (window.tinymce) { clearInterval(timer); hookTinyMCE(); }
        else if (attempts > 150) clearInterval(timer);
      }, 200);
    }
  }

  // ══════════════════════════════════════════════
  //  Dark Mode — MutationObserver for live toggle
  // ══════════════════════════════════════════════

  function initDarkModeObserver() {
    var html = document.documentElement;
    var wasDark = html.classList.contains('dark-mode');

    var observer = new MutationObserver(function () {
      var isDark = html.classList.contains('dark-mode');
      if (isDark === wasDark) return;
      wasDark = isDark;

      // Propagate dark mode change to all iframes
      var iframes = document.querySelectorAll('iframe');
      Array.prototype.forEach.call(iframes, function (frame) {
        try {
          var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
          if (doc && doc.documentElement) doc.documentElement.classList[isDark ? 'add' : 'remove']('dark-mode');
        } catch (e) {}
      });

      if (!window.tinymce) return;
      var editors = window.tinymce.editors || [];

      if (isDark) {
        // Switching TO dark mode
        _swapTinyMCESkin(true);
        for (var i = 0; i < editors.length; i++) {
          if (editors[i].initialized) _applyDarkToEditor(editors[i]);
        }
      } else {
        // Switching TO light mode
        _swapTinyMCESkin(false);
        for (var j = 0; j < editors.length; j++) {
          if (editors[j].initialized) _removeDarkFromEditor(editors[j]);
        }
      }
    });

    observer.observe(html, { attributes: true, attributeFilter: ['class'] });
  }

  // ══════════════════════════════════════════════
  //  Search Empty-State Spinner Fix
  // ══════════════════════════════════════════════

  function initSearchEmptyState() {
    var container = document.getElementById('messagelist-content');
    if (!container) return;

    var emptyEl = document.createElement('div');
    emptyEl.id = 'mp-search-empty-state';
    emptyEl.style.display = 'none';
    container.appendChild(emptyEl);

    function isSearchActive() {
      return !!(rcmail.env.search_request || rcmail.env.qsearch);
    }
    function getLabel() {
      var txt = rcmail.gettext('stratus_helper.search_no_messages');
      return (!txt || txt === 'stratus_helper.search_no_messages')
        ? 'No messages found for your search.'
        : txt;
    }

    function showEmpty() {
      emptyEl.textContent = getLabel();
      emptyEl.style.display = '';
      container.classList.add('mp-search-empty');
    }
    function hideEmpty() {
      emptyEl.style.display = 'none';
      container.classList.remove('mp-search-empty');
    }

    rcmail.addEventListener('listupdate', function (evt) {
      if (!container) return;
      if (evt && evt.rowcount === 0 && isSearchActive()) showEmpty();
      else hideEmpty();
    });

    rcmail.addEventListener('beforesearch', hideEmpty);
    rcmail.addEventListener('beforelist', hideEmpty);
  }

  // ══════════════════════════════════════════════
  //  Unified Hover Actions (MAX DEBUG)
  // ══════════════════════════════════════════════

  function initUnifiedHoverActions() {
    dbg('%c[STRATUS] initUnifiedHoverActions()', 'color:#2ecc71');

    // Resolve UID in your environment
    var rowIdToUid = Object.create(null);

    function rebuildRowIdUidIndex() {
      rowIdToUid = Object.create(null);
      var list = rcmail.message_list;
      var rows = list && list.rows;

      dbg2('[STRATUS][UID INDEX] rebuildRowIdUidIndex() list=', !!list, 'rows=', rows ? Object.keys(rows).length : null);

      if (!rows) return;
      for (var key in rows) {
        if (!Object.prototype.hasOwnProperty.call(rows, key)) continue;
        var r = rows[key];
        if (!r || !r.id) continue;
        rowIdToUid[r.id] = r.uid || key;
      }

      dbg3('[STRATUS][UID INDEX] sample map:', Object.keys(rowIdToUid).slice(0, 5).reduce(function (acc, k) {
        acc[k] = rowIdToUid[k];
        return acc;
      }, {}));
    }

    function getUidById(rows, id) {
      for (var key in rows) {
        if (!Object.prototype.hasOwnProperty.call(rows, key)) continue;
        if (rows[key] && rows[key].id === id) return rows[key].uid || key;
      }
      return null;
    }

    function getRowUid(row) {
      if (!row) return null;

      var dataUid = row.getAttribute('data-uid');
      if (dataUid) return dataUid;

      var rowId = row.id || '';
      if (!rowId) return null;

      if (rowIdToUid[rowId]) return rowIdToUid[rowId];

      var list = rcmail.message_list;
      var rows = list && list.rows;
      if (rows) {
        var uid = getUidById(rows, rowId);
        if (uid) {
          rowIdToUid[rowId] = uid;
          return uid;
        }
      }
      return null;
    }

    // ──────────────────────────────────────────
    // Synchronous hover action execution
    // ──────────────────────────────────────────

    /**
     * Execute a hover action for a specific row/uid without a race window.
     *
     * Pattern: save → point selection at target → execute → restore, all in
     * one synchronous JS task.  JS is single-threaded so no other click event
     * can fire between save and restore, meaning env.uid / list.selection
     * cannot be corrupted by a concurrent user interaction.
     *
     * clear_selection(null, true) uses RC's no_event flag to suppress the
     * 'select' event during the temporary state swap, preventing the smart bar
     * from briefly flashing a "0 selected" count.
     */
    function executeHoverAction(cmd, uid, row, evt) {
      var list0 = rcmail.message_list;
      if (!list0 || !uid) return;

      dbg2('[STRATUS][HOVER] cmd=', cmd, 'uid=', uid);

      var savedUid          = rcmail.env ? rcmail.env.uid : null;
      var savedSelection    = list0.selection ? list0.selection.slice() : [];
      var savedLastSelected = list0.last_selected || null;

      try {
        if (typeof list0.clear_selection === 'function') list0.clear_selection(null, true);
        list0.selection     = [uid];
        list0.last_selected = uid;
        if (rcmail.env) rcmail.env.uid = uid;
      } catch (e) {}

      try {
        if (cmd === 'archive') {
          if (typeof rcmail_archive === 'function') {
            rcmail_archive();
          } else {
            rcmail.command('plugin.archive', '', row, evt);
          }
        } else if (cmd === 'toggle_flag') {
          // Bypass rcmail.command() which gate-checks commands['toggle_flag'].
          // Call mark_message directly — same path toggle_flag uses internally.
          var rowData = list0.rows && list0.rows[uid];
          var flag = (rowData && rowData.flagged) ? 'unflagged' : 'flagged';
          rcmail.mark_message(flag, uid);
        } else {
          rcmail.command(cmd, '', row, evt);
        }
      } catch (err) {
        dbg('%c[STRATUS][HOVER][ERROR]', 'color:#e74c3c', cmd, err);
      }

      try {
        if (typeof list0.clear_selection === 'function') list0.clear_selection(null, true);
        list0.selection     = savedSelection;
        list0.last_selected = savedLastSelected;
        if (rcmail.env) rcmail.env.uid = savedUid;
        dbg2('[STRATUS][HOVER] restored uid=', savedUid);
      } catch (e) {}
    }

    // ──────────────────────────────────────────
    // Hover action injection
    // ──────────────────────────────────────────

    function getHoverActionHost(row) {
      if (!row || !row.querySelector) return null;
      return row.querySelector('td.flags')
        || row.querySelector('td.flag')
        || row.querySelector('td.date')
        || row.querySelector('td.size')
        || row.lastElementChild
        || null;
    }

    function createHoverActions(row) {
      if (!row || row.querySelector('.mp-hover-actions')) return;

      var host = getHoverActionHost(row);
      if (!host) return;

      host.classList.add('mp-hover-action-host');

      var strip = document.createElement('span');
      strip.className = 'mp-hover-actions';

      var archiveBtn = document.createElement('a');
      archiveBtn.className = 'mp-hover-btn archive';
      archiveBtn.href = '#archive';
      archiveBtn.title = rcmail.get_label('archive.buttontitle') || 'Archive';
      archiveBtn.setAttribute('aria-label', archiveBtn.title);

      var deleteBtn = document.createElement('a');
      deleteBtn.className = 'mp-hover-btn delete';
      deleteBtn.href = '#delete';
      deleteBtn.title = rcmail.get_label('deletemessage') || 'Delete';
      deleteBtn.setAttribute('aria-label', deleteBtn.title);

      var flagBtn = document.createElement('a');
      flagBtn.className = 'mp-hover-btn flag';
      flagBtn.href = '#flag';
      flagBtn.title = 'Flag';
      flagBtn.setAttribute('aria-label', flagBtn.title);

      strip.appendChild(archiveBtn);
      strip.appendChild(deleteBtn);
      strip.appendChild(flagBtn);
      host.appendChild(strip);

      archiveBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var uid = getRowUid(row);
        if (!uid) return;
        executeHoverAction('archive', uid, row, e);
      });

      deleteBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var uid = getRowUid(row);
        if (!uid) return;
        executeHoverAction('delete', uid, row, e);
      });

      flagBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var uid = getRowUid(row);
        if (!uid) return;
        executeHoverAction('toggle_flag', uid, row, e);
      });
    }

    function processRows(container) {
      if (!container) return;
      var rows = container.querySelectorAll('tr');
      for (var i = 0; i < rows.length; i++) {
        if (rows[i].id || rows[i].getAttribute('data-uid')) {
          createHoverActions(rows[i]);
        }
      }
    }

    // Init
    rebuildRowIdUidIndex();

    var stdList = document.getElementById('messagelist');

    if (stdList) processRows(stdList);

    // Refresh on list updates (RC replaces tbody)
    rcmail.addEventListener('listupdate', function () {
      dbg2('[STRATUS] listupdate → rebuild index + process rows');
      rebuildRowIdUidIndex();
      if (stdList) processRows(stdList);
    });

    // Observe dynamic row insertions
    function observeTarget(el) {
      if (!el) return;
      var observer = new MutationObserver(function (mutations) {
        for (var i = 0; i < mutations.length; i++) {
          var added = mutations[i].addedNodes;
          for (var j = 0; j < added.length; j++) {
            var node = added[j];
            if (node.nodeType !== 1) continue;

            if (node.tagName === 'TR' && (node.id || node.getAttribute('data-uid'))) {
              createHoverActions(node);
            }

            if (node.querySelectorAll) {
              var nested = node.querySelectorAll('tr');
              for (var k = 0; k < nested.length; k++) {
                if (nested[k].id || nested[k].getAttribute('data-uid')) {
                  createHoverActions(nested[k]);
                }
              }
            }
          }
        }
      });
      observer.observe(el, { childList: true, subtree: true });
    }

    observeTarget(stdList);
  }

  // ══════════════════════════════════════════════
  //  Smart Bar Controller (unchanged)
  // ══════════════════════════════════════════════

  function initSmartBarController() {
    var pluginSlots = document.getElementById('mp-plugin-slots');
    var smartBar = document.querySelector('.mp-smart-bar');
    var defaultSection = smartBar ? smartBar.querySelector('.mp-smart-bar-default') : null;

    if (pluginSlots && smartBar) {
      var archivePluginBtn = pluginSlots.querySelector('.archive, [data-command="plugin.archive"]');
      if (archivePluginBtn) archivePluginBtn.style.display = 'none';

      if (defaultSection) {
        var remainingBtns = pluginSlots.querySelectorAll('a, button');
        for (var i = 0; i < remainingBtns.length; i++) {
          var btn = remainingBtns[i];
          if (btn.style.display === 'none') continue;
          defaultSection.appendChild(btn);
        }
      }
    }
  }

})();