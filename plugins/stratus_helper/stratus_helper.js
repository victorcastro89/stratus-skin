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
  function group(label) {
    if (!dbgEnabled() || dbgLevel() < 1) return;
    try { console.group(label); } catch (e) {}
  }
  function groupEnd() {
    if (!dbgEnabled() || dbgLevel() < 1) return;
    try { console.groupEnd(); } catch (e) {}
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
      var editors = window.tinymce.editors || [];
      for (var i = 0; i < editors.length; i++) applyDarkToEditor(editors[i]);
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
    // Row activation / selection (the real issue)
    // ──────────────────────────────────────────

    function snapshotState(tag) {
      var list = rcmail.message_list;
      var sel = list && list.selection ? list.selection.slice() : null;
      dbg2('[STRATUS][STATE]', tag, {
        env_uid: rcmail.env && rcmail.env.uid,
        selection: sel,
        last_selected: list && list.last_selected,
        focused: (document.activeElement && document.activeElement.id) || document.activeElement
      });
    }

    function focusMessageList() {
      var list = rcmail.message_list;
      if (!list) return;
      if (typeof list.focus === 'function') {
        try { list.focus(); } catch (e) {}
      }
      // also focus DOM table if needed
      var tbl = document.getElementById('messagelist');
      if (tbl && typeof tbl.focus === 'function') {
        try { tbl.focus(); } catch (e) {}
      }
    }

    function selectSingle(uid, row) {
      var list = rcmail.message_list;
      if (!list || !uid) return false;

      // Clear existing selection (removes CSS highlights only, no callback fired)
      if (typeof list.clear_selection === 'function') {
        try { list.clear_selection(); } catch (e) {}
      }

      // Set selection state directly WITHOUT calling list.select().
      // list.select() fires the onselect callback → show_message(), which would
      // load the flagged message into the preview and corrupt env.uid — causing
      // subsequent row clicks to not refresh the preview panel.
      try {
        list.selection = [uid];
        list.last_selected = uid;
      } catch (e) {}

      // If the list stores rows by row-id rather than uid, also expose the row-id form
      if (row && row.id && row.id !== String(uid)) {
        try { list.selection = [row.id]; list.last_selected = row.id; } catch (e) {}
      }

      // Update env.uid used by command handlers (toggle_flag, delete, etc.)
      try {
        if (rcmail.env) rcmail.env.uid = uid;
      } catch (e) {}

      return true;
    }

    /**
     * Activate row like a real click (without opening message)
     * - focus list
     * - select row (robust)
     * - set env.uid + last_selected
     */
    function activateRowForCommand(row, uid) {
      group('%c[STRATUS][ACTIVATE ROW]', 'color:#16a085');
      dbg('row.id=', row && row.id, 'uid=', uid, 'row=', row);
      snapshotState('before');

      focusMessageList();

      // Important: do NOT trigger a native click (that may open message)
      // We only replicate the minimum state needed for commands to work.
      selectSingle(uid, row);

      snapshotState('after selectSingle()');
      groupEnd();
    }

    /**
     * Execute a command after activation, with fallback variants.
     * For toggle_flag, try (1) selection-based call, (2) uid-prop call if needed.
     */
    function runAfterActivate(cmd, row, uid, evt) {
      // Save selection state before we touch anything, so we can restore it after
      // the command. This ensures toggle_flag (and others) don't leave env.uid /
      // list.last_selected pointing at the actioned row, which would prevent the
      // next user click from refreshing the preview panel.
      var list0 = rcmail.message_list;
      var savedUid          = (rcmail.env && rcmail.env.uid) || null;
      var savedLastSelected = (list0 && list0.last_selected) || null;
      var savedSelection    = (list0 && list0.selection) ? list0.selection.slice() : [];

      activateRowForCommand(row, uid);

      window.setTimeout(function () {
        group('%c[STRATUS][RUN COMMAND]', 'color:#8e44ad');
        snapshotState('pre-command');

        var ret1, ret2;
        try {
          if (cmd === 'toggle_flag') {
            dbg2('[STRATUS][FLAG] calling selection-based:', "rcmail.command('toggle_flag')");
            ret1 = rcmail.command('toggle_flag', '', row, evt);

            // If Roundcube command() returns false/undefined in your build,
            // try the UID-as-prop fallback *only if ret1 is explicitly false*.
            if (ret1 === false) {
              dbg2('[STRATUS][FLAG] selection-based returned false; trying uid-prop fallback');
              ret2 = rcmail.command('toggle_flag', uid, row, evt);
            } else {
              dbg2('[STRATUS][FLAG] selection-based return:', ret1, '(not attempting fallback)');
            }
          } else {
            ret1 = rcmail.command(cmd, '', row, evt);
          }
        } catch (err) {
          dbg('%c[STRATUS][RUN COMMAND][ERROR]', 'color:#e74c3c', cmd, err);
        }

        snapshotState('post-command');
        dbg2('[STRATUS][RUN COMMAND] returns:', { primary: ret1, fallback: ret2 });
        groupEnd();

        // Restore the selection state that was active before the hover action.
        // Without this, env.uid stays on the actioned row and clicking another
        // message may not trigger a preview refresh in some Roundcube builds.
        window.setTimeout(function () {
          try {
            var list2 = rcmail.message_list;
            if (list2) {
              list2.selection    = savedSelection;
              list2.last_selected = savedLastSelected;
            }
            if (rcmail.env) rcmail.env.uid = savedUid;
            dbg2('[STRATUS][RESTORE STATE] uid=', savedUid, 'last_selected=', savedLastSelected);
          } catch (e) {}
        }, 0);
      }, 0);
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

        group('%c[STRATUS][ARCHIVE CLICK]', 'color:#2980b9');
        dbg('row.id=', row.id);
        groupEnd();

        var uid = getRowUid(row);
        dbg2('[STRATUS][ARCHIVE] resolved uid=', uid, 'from row.id=', row.id);
        if (!uid) return;

        if (typeof rcmail_archive === 'function') {
          activateRowForCommand(row, uid);
          window.setTimeout(function () { rcmail_archive(); }, 0);
        } else {
          runAfterActivate('plugin.archive', row, uid, e);
        }
      });

      deleteBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        group('%c[STRATUS][DELETE CLICK]', 'color:#c0392b');
        dbg('row.id=', row.id);
        groupEnd();

        var uid = getRowUid(row);
        dbg2('[STRATUS][DELETE] resolved uid=', uid, 'from row.id=', row.id);
        if (!uid) return;

        runAfterActivate('delete', row, uid, e);
      });

      flagBtn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        group('%c[STRATUS][FLAG CLICK]', 'color:#f1c40f');
        dbg('row.id=', row.id);
        dbg('toggle_flag enabled?', (rcmail.command_enabled && rcmail.command_enabled('toggle_flag')));
        groupEnd();

        var uid = getRowUid(row);
        dbg2('[STRATUS][FLAG] resolved uid=', uid, 'from row.id=', row.id);
        if (!uid) return;

        // This is the key: activate row fully, then call toggle_flag.
        runAfterActivate('toggle_flag', row, uid, e);
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