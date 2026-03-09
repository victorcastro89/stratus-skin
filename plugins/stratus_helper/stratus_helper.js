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

    // ══════════════════════════════════════════════
    //  Unified Hover Actions
    // ══════════════════════════════════════════════

    /**
     * Inject archive / delete / flag hover action buttons on every message row.
     * Works in both standard list mode and conversation mode.
     * Sets window._stratus_hover_actions flag so conversation_mode.js skips
     * its own hover action injection.
     */
    function initUnifiedHoverActions() {
        // Signal to conversation_mode.js that stratus handles hover actions
        window._stratus_hover_actions = true;

        /**
         * Create the hover action strip for a single table row.
         */
        function createHoverActions(row) {
            if (!row || row.querySelector('.mp-hover-actions')) return;

            var host = getHoverActionHost(row);
            if (!host) return;

            host.classList.add('mp-hover-action-host');

            var strip = document.createElement('span');
            strip.className = 'mp-hover-actions';

            // Archive button
            var archiveBtn = document.createElement('a');
            archiveBtn.className = 'mp-hover-btn archive';
            archiveBtn.href = '#archive';
            archiveBtn.title = rcmail.get_label('archive.buttontitle') || 'Archive';
            archiveBtn.setAttribute('aria-label', archiveBtn.title);

            // Delete button
            var deleteBtn = document.createElement('a');
            deleteBtn.className = 'mp-hover-btn delete';
            deleteBtn.href = '#delete';
            deleteBtn.title = rcmail.get_label('deletemessage') || 'Delete';
            deleteBtn.setAttribute('aria-label', deleteBtn.title);

            // Flag button
            var flagBtn = document.createElement('a');
            flagBtn.className = 'mp-hover-btn flag';
            flagBtn.href = '#flag';
            flagBtn.title = rcmail.get_label('markflagged') || 'Flag';
            flagBtn.setAttribute('aria-label', flagBtn.title);

            strip.appendChild(archiveBtn);
            strip.appendChild(deleteBtn);
            strip.appendChild(flagBtn);

            // Append to the right-side host cell — avoids invalid DOM inside <tr>
            host.appendChild(strip);

            // Wire click handlers
            archiveBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var uid = getRowUid(row);
                if (!uid) return;
                selectSingleRow(uid);
                if (typeof rcmail_archive === 'function') {
                    rcmail_archive();
                } else {
                    rcmail.command('plugin.archive', '', row);
                }
            });

            deleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var uid = getRowUid(row);
                if (!uid) return;
                selectSingleRow(uid);
                rcmail.command('delete', '');
            });

            flagBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var uid = getRowUid(row);
                if (!uid) return;
                var isFlagged = row.classList.contains('flagged');
                var targetFlag = isFlagged ? 'unflagged' : 'flagged';

                // Optimistic UI update so the user sees feedback immediately.
                if (targetFlag === 'flagged') {
                    row.classList.add('flagged');
                    flagBtn.title = rcmail.get_label('markunflagged') || 'Unflag';
                } else {
                    row.classList.remove('flagged');
                    flagBtn.title = rcmail.get_label('markflagged') || 'Flag';
                }
                flagBtn.setAttribute('aria-label', flagBtn.title);

                // Use Roundcube's native uid-aware mark flow.
                // This avoids selection races and builds valid post payloads.
                if (typeof rcmail.mark_message === 'function') {
                    rcmail.mark_message(targetFlag, uid);
                    return;
                }

                // Fallback for older flows.
                selectSingleRow(uid);
                rcmail.command('mark', targetFlag);
            });
        }

        /**
         * Extract message UID from a table row.
         */
        function getRowUid(row) {
            if (!row) return null;
            // Standard Roundcube: row id is "rcmrowXXX"
            var id = row.id || '';
            if (id.indexOf('rcmrow') === 0) return id.replace('rcmrow', '');
            // Conversation mode rows use data-uid
            return row.getAttribute('data-uid') || row.getAttribute('data-conv-id') || null;
        }

        /**
         * Find the cell that should host the absolute-positioned hover actions.
         */
        function getHoverActionHost(row) {
            if (!row || !row.querySelector) return null;

            return row.querySelector('td.flags')
                || row.querySelector('td.flag')
                || row.querySelector('td.date')
                || row.querySelector('td.size')
                || row.lastElementChild
                || null;
        }

        /**
         * Select a single row by UID (for hover action commands).
         */
        function selectSingleRow(uid) {
            var list = rcmail.message_list;
            if (!list) return;
            if (list.selection && list.selection.length === 1 && list.selection[0] == uid) return;
            list.select(uid);
        }

        /**
         * Process all visible rows in a container, injecting hover actions.
         */
        function processRows(container) {
            if (!container) return;
            var rows = container.querySelectorAll('tr');
            for (var i = 0; i < rows.length; i++) {
                if (rows[i].id || rows[i].getAttribute('data-uid') || rows[i].getAttribute('data-conv-id')) {
                    createHoverActions(rows[i]);
                }
            }
        }

        // Process standard message list
        var stdList = document.getElementById('messagelist');
        if (stdList) {
            processRows(stdList);
        }

        // Process conversation list
        var convList = document.getElementById('conv-messagelist');
        if (convList) {
            processRows(convList);
        }

        // Watch for dynamically added rows via MutationObserver
        var observeTarget = function(el) {
            if (!el) return;
            var observer = new MutationObserver(function(mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    var added = mutations[i].addedNodes;
                    for (var j = 0; j < added.length; j++) {
                        var node = added[j];
                        if (node.nodeType !== 1) continue;
                        if (node.tagName === 'TR' && (node.id || node.getAttribute('data-uid') || node.getAttribute('data-conv-id'))) {
                            createHoverActions(node);
                        }
                        // Check nested rows (e.g., tbody replacement)
                        if (node.querySelectorAll) {
                            var nested = node.querySelectorAll('tr');
                            for (var k = 0; k < nested.length; k++) {
                                if (nested[k].id || nested[k].getAttribute('data-uid') || nested[k].getAttribute('data-conv-id')) {
                                    createHoverActions(nested[k]);
                                }
                            }
                        }
                    }
                }
            });
            observer.observe(el, { childList: true, subtree: true });
        };

        observeTarget(stdList);
        observeTarget(convList);

        // Re-process on list updates (Roundcube replaces tbody content)
        rcmail.addEventListener('listupdate', function() {
            if (stdList) processRows(stdList);
        });
    }

    // ══════════════════════════════════════════════
    //  Smart Bar Controller
    // ══════════════════════════════════════════════

    /**
     * Initialize the smart bar: sort trigger, plugin button re-parenting,
     * conversation toggle relocation to the options popup.
     */
    function initSmartBarController() {
        var sortTrigger = document.getElementById('mp-sort-trigger');
        var sortLabel   = sortTrigger ? sortTrigger.querySelector('.mp-sort-label') : null;
        var sortMenu    = document.getElementById('listoptions-menu');

        // Sort column label lookup — maps rcmail.env.sort_col to display text
        // Labels exported via <roundcube:add_label> in pagenav.html
        var defaultLabels = {
            'date': 'Date', 'arrival': 'Arrival', 'from': 'From',
            'to': 'To', 'fromto': 'From/To', 'subject': 'Subject',
            'size': 'Size', 'cc': 'Cc'
        };
        var sortColumnLabels = {};
        Object.keys(defaultLabels).forEach(function(key) {
            var label = rcmail.get_label(key === 'date' ? 'sentdate' : key);
            // If get_label returns the key itself, use our English fallback
            sortColumnLabels[key] = (label && label !== (key === 'date' ? 'sentdate' : key))
                ? label : defaultLabels[key];
        });

        // "Sort by" fallback — try translated label first
        var sortByLabel = (function() {
            var t = rcmail.get_label('listsorting');
            return (t && t !== 'listsorting') ? t : 'Sort by';
        }());

        /**
         * Update the sort trigger label and direction indicator.
         * - No sort_col  → neutral arrow (mp-sort-none) + "Sort by" text
         * - sort_col set → coloured arrow pointing the right way + column name
         */
        function updateSortDisplay() {
            var col   = rcmail.env.sort_col || '';
            var order = (rcmail.env.sort_order || 'DESC').toUpperCase();

            // Label
            if (sortLabel) {
                sortLabel.textContent = col ? (sortColumnLabels[col] || col) : sortByLabel;
            }

            // Arrow direction
            if (sortTrigger) {
                sortTrigger.classList.remove('mp-sort-asc', 'mp-sort-desc', 'mp-sort-none');
                if (col) {
                    sortTrigger.classList.add(order === 'ASC' ? 'mp-sort-asc' : 'mp-sort-desc');
                } else {
                    sortTrigger.classList.add('mp-sort-none');
                }
            }
        }

        // Initial display
        updateSortDisplay();

        // Update on sort changes — Roundcube fires 'listupdate' after sort
        rcmail.addEventListener('listupdate', updateSortDisplay);

        // Wire sort trigger click — open list options dialog via elastic's messagelistmenu
        // handler which pre-populates current sort values and saves via rcmail.set_list_options()
        if (sortTrigger) {
            sortTrigger.setAttribute('aria-haspopup', 'dialog');
            sortTrigger.setAttribute('aria-expanded', 'false');
            sortTrigger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // 'messagelistmenu' routes through elastic's menu_toggle → menu_messagelist(),
                // which clones #listoptions-menu, pre-populates sort selects, shows a
                // proper Save dialog, and on save calls rcmail.set_list_options() so that
                // listupdate fires and updateSortDisplay() refreshes the sort bar label/arrow.
                rcmail.command('menu-open', 'messagelistmenu', sortTrigger, e);

                sortTrigger.setAttribute('aria-expanded', 'true');
            });

            // Reset aria-expanded when the dialog closes (listupdate fires after save)
            rcmail.addEventListener('listupdate', function() {
                sortTrigger.setAttribute('aria-expanded', 'false');
            });
        }

        // ── Re-parent plugin buttons from hidden containers ──

        var pluginSlots = document.getElementById('mp-plugin-slots');
        var smartBar = document.querySelector('.mp-smart-bar');
        var defaultSection = smartBar ? smartBar.querySelector('.mp-smart-bar-default') : null;

        if (pluginSlots && smartBar) {
            // Find and handle the archive button (hide it — archive is now hover-only)
            var archivePluginBtn = pluginSlots.querySelector('.archive, [data-command="plugin.archive"]');
            if (archivePluginBtn) {
                archivePluginBtn.style.display = 'none';
            }

            // Find and handle the conversation toggle button
            var convToggle = pluginSlots.querySelector('.conv-toggle, [data-command="plugin.conv.toggle"]');
            if (convToggle) {
                relocateConvToggleToPopup(convToggle);
            }

            // Move any remaining plugin buttons into the default section
            if (defaultSection) {
                var remainingBtns = pluginSlots.querySelectorAll('a, button');
                for (var i = 0; i < remainingBtns.length; i++) {
                    var btn = remainingBtns[i];
                    if (btn.style.display === 'none') continue; // skip hidden (archive)
                    if (btn.classList.contains('conv-toggle')) continue; // skip conv toggle (relocated)
                    defaultSection.appendChild(btn);
                }
            }
        }

        /**
         * Relocate the conversation toggle into the listoptions-menu popup.
         * Creates a new form-group row with a toggle switch.
         */
        function relocateConvToggleToPopup(convToggleBtn) {
            var popup = document.getElementById('listoptions-menu');
            if (!popup) return;

            // Create a form group row for the conversation toggle
            var formGroup = document.createElement('div');
            formGroup.className = 'form-group row mp-conv-toggle-row';
            formGroup.id = 'mp-listoptions-conv-toggle';

            var label = document.createElement('label');
            label.className = 'col-form-label col-sm-4';
            label.textContent = rcmail.get_label('conversation_mode.conversations') || 'Conversations';

            var colDiv = document.createElement('div');
            colDiv.className = 'col-sm-8';

            var toggleSwitch = document.createElement('label');
            toggleSwitch.className = 'mp-toggle-switch';

            var checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.id = 'mp-conv-toggle-checkbox';

            // Sync initial state — check if conversation mode is currently active
            var layoutList = document.getElementById('layout-list');
            if (layoutList && layoutList.getAttribute('data-conv-mode') === 'conversations') {
                checkbox.checked = true;
            }

            var slider = document.createElement('span');
            slider.className = 'mp-toggle-slider';

            toggleSwitch.appendChild(checkbox);
            toggleSwitch.appendChild(slider);
            colDiv.appendChild(toggleSwitch);
            formGroup.appendChild(label);
            formGroup.appendChild(colDiv);

            // Insert before the container element (listoptions) or at end
            var containerEl = popup.querySelector('[id="listoptionsmenu"]');
            if (containerEl) {
                popup.insertBefore(formGroup, containerEl);
            } else {
                popup.appendChild(formGroup);
            }

            // Wire toggle behavior
            checkbox.addEventListener('change', function() {
                rcmail.command('plugin.conv.toggle');
            });

            // Listen for conversation mode state changes to sync the checkbox
            document.addEventListener('stratus:conv-mode-changed', function(e) {
                var detail = e && e.detail ? e.detail : {};
                checkbox.checked = detail.mode === 'conversations';
            });

            // Also sync when layout-list data attribute changes
            var layoutObserver = new MutationObserver(function(mutations) {
                for (var i = 0; i < mutations.length; i++) {
                    if (mutations[i].attributeName === 'data-conv-mode') {
                        var mode = layoutList.getAttribute('data-conv-mode');
                        checkbox.checked = (mode === 'conversations');
                    }
                }
            });
            if (layoutList) {
                layoutObserver.observe(layoutList, { attributes: true, attributeFilter: ['data-conv-mode'] });
            }

            // Hide the original button
            convToggleBtn.style.display = 'none';
        }
    }

})();