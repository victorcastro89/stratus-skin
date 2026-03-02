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

})();
