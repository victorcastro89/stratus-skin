/**
 * Stratus TinyMCE Email Composer Configuration
 *
 * Configures TinyMCE for email-grade composition:
 * - Email-safe HTML output (inline styles only, whitelisted elements)
 * - Default text color and typography for cross-client rendering
 * - Dark mode: editor UI follows theme, output always light/neutral
 * - Toolbar stripped to email-relevant actions only
 * - Keyboard shortcuts (Ctrl+Enter=Send, Ctrl+S=Draft, Ctrl+K=Link)
 * - Paste sanitization (strips Word/mso markup, classes, style blocks)
 * - Email-safe font picker (no web fonts)
 *
 * Hooks into Roundcube's editor.js via `window.rcmail_editor_settings`
 * which is $.extend()'d into the TinyMCE init config.
 *
 * @requires TinyMCE 5.x (ships with Roundcube)
 * @requires rcmail global
 */
(function() {
  'use strict';

  // ──────────────────────────────────────────────────────────────────────────
  // Constants
  // ──────────────────────────────────────────────────────────────────────────

  var DEFAULT_TEXT_COLOR = '#1a1a1a';
  var DEFAULT_FONT_FAMILY = 'Arial, Helvetica, sans-serif';
  var DEFAULT_FONT_SIZE = '14px';
  var DEFAULT_LINE_HEIGHT = '1.5';

  // ──────────────────────────────────────────────────────────────────────────
  // Dark mode detection
  // ──────────────────────────────────────────────────────────────────────────

  var isDarkMode = document.documentElement.classList.contains('dark-mode');

  // ──────────────────────────────────────────────────────────────────────────
  // Email-safe valid elements whitelist
  // ──────────────────────────────────────────────────────────────────────────

  var validElements = [
    'p[style]', 'div[style]', 'br',
    'a[href|target|style]',
    'img[src|alt|width|height|style]',
    'table[style|width|cellpadding|cellspacing|border]',
    'tr[style]',
    'td[style|width|colspan|rowspan]',
    'th[style|width|colspan|rowspan]',
    'b', 'strong', 'i', 'em', 'u', 's',
    'ul[style]', 'ol[style]', 'li[style]',
    'blockquote[style]',
    'h1[style]', 'h2[style]', 'h3[style]',
    'hr',
    'span[style|id|class]',
    // Roundcube needs font tag for legacy support and signature elements
    'font[face|size|color|style]'
  ].join(',');

  // ──────────────────────────────────────────────────────────────────────────
  // Valid inline styles whitelist
  // ──────────────────────────────────────────────────────────────────────────

  var validStyles = {
    '*': 'color,background-color,font-size,font-family,text-align,text-decoration,' +
         'font-weight,font-style,padding,margin,border,width,height,line-height,' +
         'border-collapse,vertical-align,list-style-type,border-left,padding-left,' +
         'margin-left,margin-right,margin-top,margin-bottom,border-top,border-bottom,' +
         'border-color,border-style,border-width,max-width,float,display'
  };

  // ──────────────────────────────────────────────────────────────────────────
  // Email-safe font list (no web fonts)
  // ──────────────────────────────────────────────────────────────────────────

  var fontFormats = [
    'Arial=arial, helvetica, sans-serif',
    'Verdana=verdana, geneva, sans-serif',
    'Georgia=georgia, palatino, serif',
    'Tahoma=tahoma, arial, sans-serif',
    'Times New Roman=times new roman, times, serif',
    'Courier New=courier new, courier, monospace',
    'Trebuchet MS=trebuchet ms, arial, sans-serif'
  ].join('; ');

  var fontSizeFormats = '10px 11px 12px 14px 16px 18px 20px 24px';

  // ──────────────────────────────────────────────────────────────────────────
  // Toolbar layout
  // ──────────────────────────────────────────────────────────────────────────

  // Primary toolbar: email-relevant actions only
  // TinyMCE 5.x button names
  var toolbar = 'fontselect fontsizeselect | bold italic underline strikethrough | '
    + 'forecolor backcolor | alignleft aligncenter alignright | '
    + 'bullist numlist | outdent indent | link image emoticons | $extra';

  // Plugins — email-relevant only
  var plugins = 'autolink lists link image charmap searchreplace table '
    + 'paste tabfocus emoticons noneditable';

  // ──────────────────────────────────────────────────────────────────────────
  // Content style (what the user sees while editing)
  // ──────────────────────────────────────────────────────────────────────────

  var contentStyle = 'body {'
    + ' font-family: ' + DEFAULT_FONT_FAMILY + ';'
    + ' font-size: ' + DEFAULT_FONT_SIZE + ';'
    + ' color: ' + DEFAULT_TEXT_COLOR + ';'
    + ' line-height: ' + DEFAULT_LINE_HEIGHT + ';'
    + ' margin: 8px;'
    + ' background: transparent;'
    + '}'
    + ' blockquote {'
    + '   margin: 0 0 0 0.8em;'
    + '   border-left: 2px solid #ccc;'
    + '   padding-left: 0.8em;'
    + '   color: #555;'
    + ' }'
    + ' img { max-width: 100%; height: auto; }';

  // ──────────────────────────────────────────────────────────────────────────
  // Paste sanitization
  // ──────────────────────────────────────────────────────────────────────────

  /**
   * Pre-process pasted content to strip non-email-safe markup.
   * Runs before TinyMCE's internal paste processing.
   */
  function pastePreprocess(plugin, args) {
    var content = args.content;
    if (!content) return;

    // Strip <style> blocks entirely
    content = content.replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '');

    // Strip <meta>, <link>, <title> tags
    content = content.replace(/<(meta|link|title)[^>]*\/?>/gi, '');

    // Strip Word/Office XML tags (<o:p>, <w:*>, etc.)
    content = content.replace(/<\/?[owv]:[^>]*>/gi, '');

    // Strip mso-* styles from inline style attributes
    content = content.replace(/\bmso-[^;:"']+:[^;:"']+;?/gi, '');

    // Strip class and id attributes
    content = content.replace(/\s+class\s*=\s*"[^"]*"/gi, '');
    content = content.replace(/\s+class\s*=\s*'[^']*'/gi, '');
    content = content.replace(/\s+id\s*=\s*"[^"]*"/gi, '');
    content = content.replace(/\s+id\s*=\s*'[^']*'/gi, '');

    // Strip data-* attributes
    content = content.replace(/\s+data-[a-z0-9-]+\s*=\s*"[^"]*"/gi, '');
    content = content.replace(/\s+data-[a-z0-9-]+\s*=\s*'[^']*'/gi, '');

    // Strip XML namespace declarations
    content = content.replace(/\s+xmlns[:\w]*\s*=\s*"[^"]*"/gi, '');

    // Strip empty spans left behind
    content = content.replace(/<span\s*>([\s\S]*?)<\/span>/gi, '$1');

    // Strip comments (including conditional IE comments)
    content = content.replace(/<!--[\s\S]*?-->/g, '');

    args.content = content;
  }

  // ──────────────────────────────────────────────────────────────────────────
  // On-send HTML sanitizer
  // ──────────────────────────────────────────────────────────────────────────

  /**
   * Sanitize editor HTML before sending. Strips dark-mode artifacts,
   * ensures root text color is set, removes background-color from root.
   */
  function sanitizeEmailOutput(editor) {
    var body = editor.getBody();
    if (!body) return;

    // Ensure root color is set
    body.style.color = DEFAULT_TEXT_COLOR;

    // Remove dark-mode background from body
    body.style.removeProperty('background-color');
    body.style.removeProperty('background');

    // Remove stratus dark mode stylesheet if present
    var doc = editor.getDoc();
    var darkStyle = doc && doc.getElementById('stratus-tinymce-dark');
    if (darkStyle) {
      darkStyle.parentNode.removeChild(darkStyle);
    }

    // Remove dark-mode class from iframe html
    if (doc && doc.documentElement) {
      doc.documentElement.classList.remove('dark-mode');
    }

    // Walk all elements and strip dark-mode artifacts
    var allElements = body.querySelectorAll('*');
    for (var i = 0; i < allElements.length; i++) {
      var el = allElements[i];
      var style = el.style;

      // Remove any background-color on root-level divs that looks dark
      if (el.parentNode === body && style.backgroundColor) {
        var bg = style.backgroundColor;
        if (isDarkBackground(bg)) {
          style.removeProperty('background-color');
        }
      }

      // Ensure text with explicitly dark-mode light colors gets reset
      // Only touch colors that are clearly light-on-dark artifacts
      if (style.color) {
        var c = style.color;
        if (isLightColor(c)) {
          style.removeProperty('color');
        }
      }
    }

    // Force the root block style for consistency
    var rootBlocks = body.querySelectorAll(':scope > div, :scope > p');
    for (var j = 0; j < rootBlocks.length; j++) {
      var block = rootBlocks[j];
      if (!block.style.color) {
        block.style.color = DEFAULT_TEXT_COLOR;
      }
      if (!block.style.fontFamily) {
        block.style.fontFamily = DEFAULT_FONT_FAMILY;
      }
      if (!block.style.fontSize) {
        block.style.fontSize = DEFAULT_FONT_SIZE;
      }
    }
  }

  /**
   * Check if a CSS color value is a dark background (likely dark-mode artifact).
   */
  function isDarkBackground(color) {
    var rgb = parseColor(color);
    if (!rgb) return false;
    // Luminance threshold: anything below 50 is "dark"
    var lum = (rgb[0] * 299 + rgb[1] * 587 + rgb[2] * 114) / 1000;
    return lum < 50;
  }

  /**
   * Check if a CSS color value is very light (likely dark-mode text artifact).
   */
  function isLightColor(color) {
    var rgb = parseColor(color);
    if (!rgb) return false;
    var lum = (rgb[0] * 299 + rgb[1] * 587 + rgb[2] * 114) / 1000;
    return lum > 200;
  }

  /**
   * Parse a CSS color string into [r, g, b] or null.
   */
  function parseColor(color) {
    if (!color) return null;

    // rgb(r, g, b) or rgba(r, g, b, a)
    var rgbMatch = color.match(/rgba?\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/);
    if (rgbMatch) {
      return [parseInt(rgbMatch[1], 10), parseInt(rgbMatch[2], 10), parseInt(rgbMatch[3], 10)];
    }

    // #hex
    var hexMatch = color.match(/^#([0-9a-f]{3,8})$/i);
    if (hexMatch) {
      var hex = hexMatch[1];
      if (hex.length === 3) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
      }
      return [
        parseInt(hex.substring(0, 2), 16),
        parseInt(hex.substring(2, 4), 16),
        parseInt(hex.substring(4, 6), 16)
      ];
    }

    return null;
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Setup callback — keyboard shortcuts + on-send sanitizer
  // ──────────────────────────────────────────────────────────────────────────

  function setupCallback(editor) {
    // --- Ctrl+Enter / Cmd+Enter = Send ---
    editor.addShortcut('ctrl+return', 'Send email', function() {
      if (window.rcmail) rcmail.command('send');
    });
    editor.addShortcut('meta+return', 'Send email', function() {
      if (window.rcmail) rcmail.command('send');
    });

    // --- Ctrl+S / Cmd+S = Save Draft ---
    editor.addShortcut('ctrl+s', 'Save draft', function() {
      if (window.rcmail) rcmail.command('savedraft');
    });
    editor.addShortcut('meta+s', 'Save draft', function() {
      if (window.rcmail) rcmail.command('savedraft');
    });

    // --- Ctrl+K / Cmd+K = Insert Link ---
    editor.addShortcut('ctrl+k', 'Insert link', function() {
      editor.execCommand('mceLink');
    });
    editor.addShortcut('meta+k', 'Insert link', function() {
      editor.execCommand('mceLink');
    });

    // --- On submit (send): sanitize HTML output ---
    editor.on('submit', function() {
      sanitizeEmailOutput(editor);
    });

    // Also hook into Roundcube's send command as a safety net
    editor.on('SaveContent', function(e) {
      // SaveContent fires when TinyMCE copies content back to the textarea
      // This ensures sanitization happens before the form submits
      sanitizeEmailOutput(editor);
    });

    // --- Tab behavior: indent in lists, move focus outside lists ---
    editor.on('keydown', function(e) {
      if (e.keyCode !== 9) return; // Tab

      var node = editor.selection.getNode();
      var inList = false;
      var parent = node;
      while (parent && parent !== editor.getBody()) {
        if (parent.nodeName === 'UL' || parent.nodeName === 'OL') {
          inList = true;
          break;
        }
        parent = parent.parentNode;
      }

      if (inList) {
        // Let TinyMCE handle tab for list indent/outdent
        return;
      }

      // Outside list: let tab move focus to next field (default browser behavior)
      // Don't prevent default — tabfocus plugin handles this
    });

    // --- Ctrl+] / Ctrl+[ = Indent/Outdent ---
    editor.addShortcut('ctrl+]', 'Indent', function() {
      editor.execCommand('Indent');
    });
    editor.addShortcut('meta+]', 'Indent', function() {
      editor.execCommand('Indent');
    });
    editor.addShortcut('ctrl+[', 'Outdent', function() {
      editor.execCommand('Outdent');
    });
    editor.addShortcut('meta+[', 'Outdent', function() {
      editor.execCommand('Outdent');
    });
  }

  // ──────────────────────────────────────────────────────────────────────────
  // Assemble the settings object
  // ──────────────────────────────────────────────────────────────────────────

  window.rcmail_editor_settings = {
    // Plugins — email-relevant only
    plugins: plugins,

    // Toolbar — email-focused layout
    toolbar: toolbar,
    toolbar_drawer: 'sliding',
    menubar: false,
    statusbar: false,

    // Typography defaults
    content_style: contentStyle,
    forced_root_block: 'div',
    forced_root_block_attrs: {
      'style': 'color: ' + DEFAULT_TEXT_COLOR + ';'
        + ' font-family: ' + DEFAULT_FONT_FAMILY + ';'
        + ' font-size: ' + DEFAULT_FONT_SIZE + ';'
    },

    // Email-safe fonts only
    font_formats: fontFormats,
    fontsize_formats: fontSizeFormats,

    // Dark mode skin
    skin: isDarkMode ? 'oxide-dark' : 'oxide',

    // HTML output safety
    valid_elements: validElements,
    valid_styles: validStyles,

    // Paste handling
    paste_as_text: false,
    paste_word_valid_elements: 'b,strong,i,em,u,s,p,br,a[href],ul,ol,li,'
      + 'table,tr,td,th,h1,h2,h3,img[src|alt|width|height],div,span,blockquote,hr',
    paste_retain_style_properties: 'color,font-size,font-family,font-weight,'
      + 'font-style,text-decoration,text-align,background-color',
    paste_preprocess: pastePreprocess,

    // Links
    default_link_target: '_blank',
    link_default_protocol: 'https',

    // Images
    image_advtab: false,
    image_dimensions: true,
    paste_data_images: true,

    // Spellcheck
    browser_spellcheck: true,

    // Behavior
    resize: false,
    min_height: 300,

    // Setup callback for shortcuts and sanitizer
    setup_callback: setupCallback
  };

})();
