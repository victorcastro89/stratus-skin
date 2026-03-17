/**
 * Stratus Helper – Client-side JS
 *
 * @version 0.1.0
 */
(function () {
  'use strict';

  if (!window.rcmail) return;

  // ══════════════════════════════════════════════
  //  TinyMCE Email Composer Configuration
  //  Must run at load time (before editor.js reads window.rcmail_editor_settings)
  // ══════════════════════════════════════════════
  initTinyMCEEmailComposer();

  // ══════════════════════════════════════════════
  //  Dark Mode — Email content classification
  // ══════════════════════════════════════════════

  function _parseColorSimple(c) {
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
  function _lumSimple(rgb) { return (rgb[0]*299 + rgb[1]*587 + rgb[2]*114) / 1000; }

  // Returns true if color is white, near-white (luminance > 200), transparent, or unset
  function _isWhiteOrLight(colorStr) {
    if (!colorStr || colorStr === 'transparent' || colorStr === 'initial' || colorStr === 'inherit') return true;
    var rgb = _parseColorSimple(colorStr);
    if (!rgb) return true; // unparseable → treat as default/white
    return _lumSimple(rgb) > 200;
  }

  // Classify a .message-htmlpart element: 'darkable' or 'styled'
  function _classifyEmailPart(htmlpartEl) {
    var rcmBody = htmlpartEl.querySelector('div.rcmBody');
    if (!rcmBody) return 'darkable'; // plain text or no body → safe to darken

    // Check rcmBody's own background (set by Roundcube from <body bgcolor>)
    var bodyBg = rcmBody.style.backgroundColor;
    if (bodyBg && !_isWhiteOrLight(bodyBg)) return 'styled';

    // Check first ~8 direct children for explicit background colors
    var children = rcmBody.children;
    var limit = Math.min(children.length, 8);
    for (var i = 0; i < limit; i++) {
      var childBg = children[i].style.backgroundColor;
      if (childBg && !_isWhiteOrLight(childBg)) return 'styled';
    }

    return 'darkable';
  }

  // Process all .message-htmlpart elements — add .stratus-styled to styled emails
  function _processEmailParts(doc) {
    if (!doc) return;
    var parts = doc.querySelectorAll('.message-htmlpart');
    for (var i = 0; i < parts.length; i++) {
      var result = _classifyEmailPart(parts[i]);
      if (result === 'styled') {
        parts[i].classList.add('stratus-styled');
      } else {
        parts[i].classList.remove('stratus-styled');
      }
    }
  }

  // Remove .stratus-styled from all .message-htmlpart elements
  function _clearEmailPartsStyled(doc) {
    if (!doc) return;
    var parts = doc.querySelectorAll('.message-htmlpart.stratus-styled');
    for (var i = 0; i < parts.length; i++) {
      parts[i].classList.remove('stratus-styled');
    }
  }

  rcmail.addEventListener('init', function () {

    // ──────────────────────────────────────────
    //  1. Color Scheme Switching
    // ──────────────────────────────────────────

    rcmail.addEventListener('plugin.stratus.scheme_applied', function (data) {
      if (!data) return;
      applyScheme(data);
    });

    // ──────────────────────────────────────────
    //  2. Font Switching
    // ──────────────────────────────────────────

    rcmail.addEventListener('plugin.stratus.font_applied', function (data) {
      if (!data) return;
      applyFont(data.family, data.url);
    });

    // ──────────────────────────────────────────
    //  2b. Font Size Switching
    // ──────────────────────────────────────────

    rcmail.addEventListener('plugin.stratus.fontsize_applied', function (data) {
      if (!data) return;
      applyFontSize(data.size, data.line_height);
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
      _processEmailParts(document);
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

  // Collect all document roots that should receive CSS variable updates.
  // When called from inside an iframe (settings preferences frame), include
  // the parent frame so its shell (sidebar, toolbar, etc.) also updates live.
  function _schemeRoots() {
    var roots = [document.documentElement];
    try {
      if (window.parent && window.parent !== window && window.parent.document) {
        roots.push(window.parent.document.documentElement);
      }
    } catch (e) {}
    return roots;
  }

  function applyScheme(data) {
    var roots = _schemeRoots();
    for (var ri = 0; ri < roots.length; ri++) {
      var root = roots[ri];

      // Core accent
      root.style.setProperty('--stratus-primary', data.primary);
      root.style.setProperty('--stratus-primary-dark', data.primary_dark);
      root.style.setProperty('--stratus-primary-rgb', hexToRgb(data.primary));
      root.style.setProperty('--stratus-primary-dark-rgb', hexToRgb(data.primary_dark));

      // Text accent
      root.style.setProperty('--stratus-text-accent', data.text_accent || '#3949ab');
      root.style.setProperty('--stratus-text-accent-dark', data.text_accent_dark || '#9fa8da');

      // Sidebar tokens
      if (data.sidebar_bg) root.style.setProperty('--stratus-sidebar-bg', data.sidebar_bg);
      if (data.sidebar_gradient) root.style.setProperty('--stratus-sidebar-gradient', data.sidebar_gradient);
      if (data.sidebar_text) root.style.setProperty('--stratus-sidebar-text', data.sidebar_text);
      if (data.sidebar_text_hover) root.style.setProperty('--stratus-sidebar-text-hover', data.sidebar_text_hover);
      if (data.sidebar_text_active) root.style.setProperty('--stratus-sidebar-text-active', data.sidebar_text_active);
      if (data.sidebar_active_bg) root.style.setProperty('--stratus-sidebar-active-bg', data.sidebar_active_bg);

      // Surface tint tokens
      if (data.surface_tint) root.style.setProperty('--stratus-surface-tint', data.surface_tint);
      if (data.hover_bg) root.style.setProperty('--stratus-hover-bg', data.hover_bg);
      if (data.selected_bg) root.style.setProperty('--stratus-selected-bg', data.selected_bg);
      if (data.focus_ring) root.style.setProperty('--stratus-focus-ring', data.focus_ring);

      // Gradient tokens
      if (data.gradient) root.style.setProperty('--stratus-gradient', data.gradient);
      if (data.gradient_hover) root.style.setProperty('--stratus-gradient-hover', data.gradient_hover);

      // Dark mode complementary light
      if (data.primary_dark_light) root.style.setProperty('--stratus-primary-dark-light', data.primary_dark_light);

      // Typography & border — scheme-derived hue-tinted values
      if (data.font)           root.style.setProperty('--stratus-font', data.font);
      if (data.font_secondary) root.style.setProperty('--stratus-font-secondary', data.font_secondary);
      if (data.border)         root.style.setProperty('--stratus-border', data.border);

      // Dark mode palette — scheme-aware dark surfaces, text, borders
      if (data.dark_background) {
        root.style.setProperty('--stratus-dark-background', data.dark_background);
        root.style.setProperty('--stratus-dark-background-rgb', hexToRgb(data.dark_background));
      }
      if (data.dark_surface) {
        root.style.setProperty('--stratus-dark-surface', data.dark_surface);
        root.style.setProperty('--stratus-dark-surface-rgb', hexToRgb(data.dark_surface));
      }
      if (data.dark_surface_raised) {
        root.style.setProperty('--stratus-dark-surface-raised', data.dark_surface_raised);
      }
      if (data.dark_font) {
        root.style.setProperty('--stratus-dark-font', data.dark_font);
      }
      if (data.dark_font_secondary) {
        root.style.setProperty('--stratus-dark-font-secondary', data.dark_font_secondary);
      }
      if (data.dark_border) {
        root.style.setProperty('--stratus-dark-border', data.dark_border);
        root.style.setProperty('--stratus-dark-border-rgb', hexToRgb(data.dark_border));
      }

      // Dark utility tokens — pre-computed lighten() replacements
      if (data.dark_background) {
        root.style.setProperty('--stratus-dark-input-bg-focus', _lightenHex(data.dark_background, 3));
        root.style.setProperty('--stratus-dark-message-loading-bg', _lightenHex(data.dark_background, 10));
      }
      if (data.dark_surface_raised) {
        root.style.setProperty('--stratus-dark-input-addon-focus-bg', _lightenHex(data.dark_surface_raised, 8));
      }

      // Re-apply dark styles to open TinyMCE editors when scheme changes
      if (root.classList.contains('dark-mode') && window.tinymce) {
        var editors = window.tinymce.editors || [];
        for (var ei = 0; ei < editors.length; ei++) {
          if (editors[ei].initialized) _applyDarkToEditor(editors[ei]);
        }
      }
    }
  }

  /**
   * Lighten a hex color by a percentage (simple HSL approach).
   * Used for computing utility tokens client-side.
   */
  function _lightenHex(hex, percent) {
    hex = hex.replace(/^#/, '');
    if (hex.length === 3) hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
    var r = parseInt(hex.substring(0,2),16)/255;
    var g = parseInt(hex.substring(2,4),16)/255;
    var b = parseInt(hex.substring(4,6),16)/255;
    var max = Math.max(r,g,b), min = Math.min(r,g,b);
    var h, s, l = (max+min)/2;
    if (max === min) { h = s = 0; }
    else {
      var d = max - min;
      s = l > 0.5 ? d/(2-max-min) : d/(max+min);
      if (max === r) h = (g-b)/d + (g<b?6:0);
      else if (max === g) h = (b-r)/d + 2;
      else h = (r-g)/d + 4;
      h /= 6;
    }
    l = Math.min(1, l + percent/100);
    if (s === 0) { r = g = b = l; }
    else {
      function hue2rgb(p,q,t) {
        if (t<0) t+=1; if (t>1) t-=1;
        if (t<1/6) return p+(q-p)*6*t;
        if (t<1/2) return q;
        if (t<2/3) return p+(q-p)*(2/3-t)*6;
        return p;
      }
      var q2 = l<0.5 ? l*(1+s) : l+s-l*s;
      var p2 = 2*l-q2;
      r = hue2rgb(p2,q2,h+1/3);
      g = hue2rgb(p2,q2,h);
      b = hue2rgb(p2,q2,h-1/3);
    }
    var rr = Math.round(r*255).toString(16);
    var gg = Math.round(g*255).toString(16);
    var bb = Math.round(b*255).toString(16);
    if (rr.length === 1) rr = '0'+rr;
    if (gg.length === 1) gg = '0'+gg;
    if (bb.length === 1) bb = '0'+bb;
    return '#'+rr+gg+bb;
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
    var roots = _schemeRoots();
    for (var ri = 0; ri < roots.length; ri++) {
      roots[ri].style.setProperty('--stratus-font-family', family);
    }

    // Inject (or remove) the Google Fonts <link> in every document that uses
    // the font — both the current frame and the parent shell frame.
    var docs = [document];
    try {
      if (window.parent && window.parent !== window && window.parent.document) {
        docs.push(window.parent.document);
      }
    } catch (e) {}

    for (var di = 0; di < docs.length; di++) {
      var doc = docs[di];
      var existingLink = doc.getElementById('stratus-helper-font');
      if (url) {
        if (existingLink) {
          existingLink.href = url;
        } else {
          var link = doc.createElement('link');
          link.id = 'stratus-helper-font';
          link.rel = 'stylesheet';
          link.href = url;
          doc.head.appendChild(link);
        }
      } else if (existingLink) {
        existingLink.parentNode.removeChild(existingLink);
      }
    }
  }

  // ══════════════════════════════════════════════
  //  Font Size Helpers
  // ══════════════════════════════════════════════

  function applyFontSize(size, lineHeight) {
    var roots = _schemeRoots();
    for (var ri = 0; ri < roots.length; ri++) {
      roots[ri].style.setProperty('--stratus-font-size', size);
      roots[ri].style.setProperty('--stratus-line-height', lineHeight);
    }
  }

  // ══════════════════════════════════════════════
  //  Settings Page Preview
  // ══════════════════════════════════════════════

  function initSettingsPreview() {
    // All three controls apply their change immediately as a live preview using
    // client-side CSS variable updates (no AJAX, no save).
    // Persistence happens only when the user clicks Save (standard form POST → prefs_save).

    // Color scheme — each radio carries the full token set in data-scheme JSON
    var schemeRadios = document.querySelectorAll('input[name="_stratus_color_scheme"]');
    for (var i = 0; i < schemeRadios.length; i++) {
      schemeRadios[i].addEventListener('change', function () {
        if (!this.checked) return;
        try {
          var data = JSON.parse(this.getAttribute('data-scheme') || '{}');
          if (data) applyScheme(data);
        } catch (e) {}
      });
    }

    // Font family — lookup map from rcmail.env (set by init_settings PHP)
    var fontSelect = document.getElementById('ff_stratus_font_family');
    if (fontSelect) {
      var fontData = (rcmail.env && rcmail.env.stratus_fonts_data) || {};
      fontSelect.addEventListener('change', function () {
        var f = fontData[this.value];
        if (f) applyFont(f.family, f.url);
      });
    }

    // Font size — lookup map from rcmail.env (set by init_settings PHP)
    var fontSizeSelect = document.getElementById('ff_stratus_font_size');
    if (fontSizeSelect) {
      var sizeData = (rcmail.env && rcmail.env.stratus_font_sizes_data) || {};
      fontSizeSelect.addEventListener('change', function () {
        var s = sizeData[this.value];
        if (s) applyFontSize(s.size, s.line_height);
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
      + ' color: ' + (isDark ? getCSSVar('--stratus-text-accent-dark') : DEFAULT_TEXT_COLOR) + ';'
      + ' line-height: ' + DEFAULT_LINE_HEIGHT + ';'
      + ' margin: 8px;'
      + ' background: ' + (isDark ? getCSSVar('--stratus-dark-surface') : 'transparent') + ';'
      + '}'
      + ' blockquote {'
      + '   margin: 0 0 0 0.8em;'
      + '   border-left: 2px solid ' + (isDark ? getCSSVar('--stratus-primary-dark') : '#ccc') + ';'
      + '   padding-left: 0.8em;'
      + '   color: ' + (isDark ? getCSSVar('--stratus-dark-font-secondary') : '#555') + ';'
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
      frame.addEventListener('load', function () {
        syncDarkMode(this);
        // Classify email parts for dark mode after frame loads
        try {
          var doc = this.contentDocument || (this.contentWindow && this.contentWindow.document);
          var isDark = document.documentElement.classList.contains('dark-mode');
          if (isDark && doc) {
            setTimeout(function() { _processEmailParts(doc); }, 50);
          }
        } catch (e) {}
      });
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

  function getCSSVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  }

  // Dark mode content styles for the editor iframe.
  // Display-only — stripped on send by the sanitizer.
  // Built at call-time so it always reflects the current CSS custom property values.
  function _buildTinymceDarkCSS() {
    var bg     = getCSSVar('--stratus-dark-surface');
    var text   = getCSSVar('--stratus-text-accent-dark');
    var accent = getCSSVar('--stratus-primary-dark');
    var muted  = getCSSVar('--stratus-dark-font-secondary');
    var raised = getCSSVar('--stratus-dark-surface-raised');
    var border = getCSSVar('--stratus-dark-border');
    return 'html, body { background-color: ' + bg + ' !important; color: ' + text + ' !important; }'
      + 'a { color: ' + accent + ' !important; }'
      + 'blockquote { border-left: 3px solid ' + accent + ' !important; color: ' + muted + ' !important; }'
      + 'pre, code { background: ' + raised + ' !important; color: ' + text + ' !important; border-color: ' + border + ' !important; }'
      + 'hr { border-color: ' + border + ' !important; }'
      + 'table, td, th { border-color: ' + border + ' !important; }'
      + 'div[style*="color: #1a1a1a"], div[style*="color:#1a1a1a"] { color: ' + text + ' !important; }';
  }

  function _applyDarkToEditor(editor) {
    var doc = editor.getDoc ? editor.getDoc() : null;
    if (!doc || !doc.head) return;
    if (doc.documentElement) doc.documentElement.classList.add('dark-mode');
    // Inject dark stylesheet if not already present
    if (!doc.getElementById('stratus-tinymce-dark')) {
      var style = doc.createElement('style');
      style.id = 'stratus-tinymce-dark';
      style.textContent = _buildTinymceDarkCSS();
      doc.head.appendChild(style);
    }
    // Also set body inline styles (covers toggling from light → dark mid-session)
    var body = doc.body;
    if (body) {
      body.style.backgroundColor = getCSSVar('--stratus-dark-surface');
      body.style.color = getCSSVar('--stratus-text-accent-dark');
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

      // Classify/unclassify email parts for dark mode
      var msgFrame = document.getElementById('messagecontframe');
      if (msgFrame) {
        try {
          var msgDoc = msgFrame.contentDocument || (msgFrame.contentWindow && msgFrame.contentWindow.document);
          if (isDark) {
            _processEmailParts(msgDoc);
          } else {
            _clearEmailPartsStyled(msgDoc);
          }
        } catch (e) {}
      }

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
  //  Unified Hover Actions
  // ══════════════════════════════════════════════

  function initUnifiedHoverActions() {
    // Resolve UID in your environment
    var rowIdToUid = Object.create(null);

    function rebuildRowIdUidIndex() {
      rowIdToUid = Object.create(null);
      var list = rcmail.message_list;
      var rows = list && list.rows;

      if (!rows) return;
      for (var key in rows) {
        if (!Object.prototype.hasOwnProperty.call(rows, key)) continue;
        var r = rows[key];
        if (!r || !r.id) continue;
        rowIdToUid[r.id] = r.uid || key;
      }
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
        // ignore
      }

      try {
        if (typeof list0.clear_selection === 'function') list0.clear_selection(null, true);
        list0.selection     = savedSelection;
        list0.last_selected = savedLastSelected;
        if (rcmail.env) rcmail.env.uid = savedUid;
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