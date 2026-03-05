/**
 * Stratus Helper – Client-side JS
 *
 * Handles:
 * 1. Live color scheme switching
 * 2. Live font family switching
 * 3. Settings page live preview
 *
 * @version 0.1.0
 */
(function () {
    'use strict';

    if (!window.rcmail) return;

    rcmail.addEventListener('init', function () {

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
        //  4. Dark Mode — iframe propagation
        // ──────────────────────────────────────────

        if (document.documentElement.classList.contains('dark-mode')) {
            initDarkModeFramePropagation();
            initTinyMCEDarkMode();
        }
    });

    // ══════════════════════════════════════════════
    //  Color Scheme Helpers
    // ══════════════════════════════════════════════

    /**
     * Apply color scheme CSS custom properties to the document root.
     */
    function applyScheme(primary, primaryDark) {
        var root = document.documentElement;
        root.style.setProperty('--stratus-primary', primary);
        root.style.setProperty('--stratus-primary-dark', primaryDark);
        root.style.setProperty('--stratus-primary-rgb', hexToRgb(primary));
        root.style.setProperty('--stratus-primary-dark-rgb', hexToRgb(primaryDark));
    }

    /**
     * Convert hex color to comma-separated RGB string.
     */
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

    /**
     * Apply font family to document and manage Google Font stylesheet.
     */
    function applyFont(family, url) {
        // Update CSS custom property
        document.documentElement.style.setProperty('--stratus-font-family', family);

        // Manage the Google Font <link> element
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

    /**
     * Initialize live preview on the Stratus settings page.
     * When user changes the select, immediately apply the visual change
     * (scheme/font) without waiting for form submit.
     */
    function initSettingsPreview() {
        // Color scheme select
        var schemeSelect = document.getElementById('ff_stratus_color_scheme');
        if (schemeSelect) {
            schemeSelect.addEventListener('change', function () {
                var key = this.value;
                // Look up the scheme colors from the options data attributes
                // or do an AJAX call for live preview
                rcmail.http_post('plugin.stratus.set_scheme', { _scheme: key });
            });
        }

        // Font family select
        var fontSelect = document.getElementById('ff_stratus_font_family');
        if (fontSelect) {
            fontSelect.addEventListener('change', function () {
                var key = this.value;
                rcmail.http_post('plugin.stratus.set_font', { _font: key });
            });
        }
    }

    // ══════════════════════════════════════════════
    //  Dark Mode — iframe propagation
    // ══════════════════════════════════════════════

    /**
     * Inject the `dark-mode` class into every Roundcube content iframe so that
     * framed pages (settings/preferences, message reading pane, compose) inherit
     * the dark theme from the parent document.
     *
     * Roundcube loads preferences and messages inside iframes that have their own
     * <html> element — the parent's class is NOT inherited automatically.
     */
    function initDarkModeFramePropagation() {
        function injectDark(frame) {
            try {
                var doc = frame.contentDocument ||
                    (frame.contentWindow && frame.contentWindow.document);
                if (doc && doc.documentElement) {
                    doc.documentElement.classList.add('dark-mode');
                }
            } catch (e) {
                // Cross-origin frame — ignore silently
            }
        }

        // Apply to frames that already exist in the DOM
        var knownIds = ['preferences-frame', 'contentframe', 'messagecontframe'];
        knownIds.forEach(function (id) {
            var frame = document.getElementById(id);
            if (frame) {
                injectDark(frame);
                frame.addEventListener('load', function () { injectDark(this); });
            }
        });

        // Watch for frames added dynamically (Roundcube sometimes creates them lazily)
        var observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.tagName === 'IFRAME') {
                        node.addEventListener('load', function () { injectDark(this); });
                    }
                    // Frames nested inside the added node
                    var nested = node.querySelectorAll && node.querySelectorAll('iframe');
                    if (nested) {
                        Array.prototype.forEach.call(nested, function (f) {
                            f.addEventListener('load', function () { injectDark(this); });
                        });
                    }
                });
            });
        });
        observer.observe(document.body, { childList: true, subtree: true });
    }

    // ══════════════════════════════════════════════
    //  Dark Mode — TinyMCE editor content area
    // ══════════════════════════════════════════════

    /**
     * TinyMCE renders its editing area inside a sandboxed <iframe>.  CSS rules on
     * the parent document don't reach it.  This function injects a minimal dark
     * stylesheet into each editor's document when the page is in dark mode.
     *
     * The outer chrome (toolbar, statusbar) is handled by editor.less CSS rules.
     */
    function initTinyMCEDarkMode() {
        // Resolved from @color-dark-surface / @color-dark-font / @color-dark-main
        var darkCSS =
            'html, body { background-color: #1a1f36 !important; color: #c8d0e8 !important; }' +
            'a { color: #7986cb !important; }' +
            'blockquote { border-left: 3px solid #7986cb; color: #7e8aad; }' +
            'pre, code { background: #212845; color: #c8d0e8; border-color: #2a3050; }' +
            'hr { border-color: #2a3050; }';

        function applyDarkToEditor(editor) {
            var doc = editor.getDoc ? editor.getDoc() : null;
            if (!doc || !doc.head) return;
            if (doc.getElementById('stratus-tinymce-dark')) return; // already applied
            var style = doc.createElement('style');
            style.id = 'stratus-tinymce-dark';
            style.textContent = darkCSS;
            doc.head.appendChild(style);
        }

        function hookTinyMCE() {
            // Future editors
            window.tinymce.on('AddEditor', function (e) {
                e.editor.on('init', function () { applyDarkToEditor(this); });
            });
            // Already-initialised editors (e.g., page reload with compose open)
            var editors = window.tinymce.editors || [];
            for (var i = 0; i < editors.length; i++) {
                applyDarkToEditor(editors[i]);
            }
        }

        if (window.tinymce) {
            hookTinyMCE();
        } else {
            // TinyMCE may load later (compose opened after init)
            var attempts = 0;
            var timer = setInterval(function () {
                attempts++;
                if (window.tinymce) {
                    clearInterval(timer);
                    hookTinyMCE();
                } else if (attempts > 150) { // give up after ~30 s
                    clearInterval(timer);
                }
            }, 200);
        }
    }

})();
