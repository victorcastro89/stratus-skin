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
  //  Dark Mode — iframe propagation
  // ══════════════════════════════════════════════

  function initDarkModeFramePropagation() {
    function injectDark(frame) {
      try {
        var doc = frame.contentDocument || (frame.contentWindow && frame.contentWindow.document);
        if (doc && doc.documentElement) doc.documentElement.classList.add('dark-mode');
      } catch (e) {}
    }

    ['preferences-frame', 'contentframe', 'messagecontframe'].forEach(function (id) {
      var frame = document.getElementById(id);
      if (!frame) return;
      injectDark(frame);
      frame.addEventListener('load', function () { injectDark(this); });
    });

    var observer = new MutationObserver(function (mutations) {
      mutations.forEach(function (mutation) {
        mutation.addedNodes.forEach(function (node) {
          if (node.nodeType !== 1) return;
          if (node.tagName === 'IFRAME') node.addEventListener('load', function () { injectDark(this); });

          var nested = node.querySelectorAll && node.querySelectorAll('iframe');
          if (nested) Array.prototype.forEach.call(nested, function (f) {
            f.addEventListener('load', function () { injectDark(this); });
          });
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  }

  // ══════════════════════════════════════════════
  //  Dark Mode — TinyMCE
  // ══════════════════════════════════════════════

  function initTinyMCEDarkMode() {
    var darkCSS =
      'html, body { background-color: #1a1f36 !important; color: #c8d0e8 !important; }' +
      'a { color: #7986cb !important; }' +
      'blockquote { border-left: 3px solid #7986cb; color: #7e8aad; }' +
      'pre, code { background: #212845; color: #c8d0e8; border-color: #2a3050; }' +
      'hr { border-color: #2a3050; }';

    function applyDarkToEditor(editor) {
      var doc = editor.getDoc ? editor.getDoc() : null;
      if (!doc || !doc.head) return;
      if (doc.documentElement) doc.documentElement.classList.add('dark-mode');
      if (doc.getElementById('stratus-tinymce-dark')) return;
      var style = doc.createElement('style');
      style.id = 'stratus-tinymce-dark';
      style.textContent = darkCSS;
      doc.head.appendChild(style);
    }

    function hookTinyMCE() {
      window.tinymce.on('AddEditor', function (e) {
        e.editor.on('init', function () { applyDarkToEditor(this); });
      });
      // Handle editors that already exist: if initialized apply now,
      // if not yet initialized attach an init listener (doc.head may be null yet).
      var editors = window.tinymce.editors || [];
      for (var i = 0; i < editors.length; i++) {
        var ed = editors[i];
        if (ed.initialized) {
          applyDarkToEditor(ed);
        } else {
          ed.on('init', function () { applyDarkToEditor(this); });
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
          var ret = rcmail.command('toggle_flag', '', row, evt);
          if (ret === false) rcmail.command('toggle_flag', uid, row, evt);
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