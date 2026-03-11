/**
 * Conversation Mode – Client-side logic (v3: template-binding architecture)
 *
 * Binds to pre-existing containers when a skin template override provides
 * them (e.g. Stratus mail.html), and injects fallback containers at runtime
 * for default Elastic / compatible skins.
 *
 * The expected panel structure inside #layout-list is:
 *
 *   #messagelist-content  → standard message list  (data-conv-mode="list")
 *   #conv-list-content    → conversation list      (data-conv-mode="conversations")
 *
 * And inside #layout-content:
 *
 *   .conv-standard-preview → standard iframe
 *   #conv-detail           → conversation detail panel
 *
 * Visibility is toggled via [data-conv-mode] on #layout-list; CSS handles
 * show/hide so the JS never touches display properties directly.
 *
 * Row layout follows the Outlook 3-line pattern:
 *   Line 1: [Avatar] Sender(s) (count)    Date
 *   Line 2: Subject                        Flags
 *   Line 3: Snippet preview…
 *
 * @license GNU GPLv3+
 */

(function () {
  'use strict';

  if (!window.rcmail) return;

  // ──────────────────────────────────────────────
  //  Constants
  // ──────────────────────────────────────────────

  var AVATAR_COLORS = [
    '#e53935', '#d81b60', '#8e24aa', '#5e35b1',
    '#3949ab', '#1e88e5', '#039be5', '#00acc1',
    '#00897b', '#43a047', '#7cb342', '#c0ca33',
    '#fdd835', '#ffb300', '#fb8c00', '#f4511e',
    '#6d4c41', '#757575', '#546e7a'
  ];

  // ──────────────────────────────────────────────
  //  State
  // ──────────────────────────────────────────────

  var conv_state = {
    mode: 'list',           // current mode: list | threads | conversations
    page: 1,                // current conversation page
    total: 0,
    pages: 0,
    loaded_mailbox: null,   // mailbox for which conv list was last fetched
    open_conv_id: null,     // currently viewed conversation
    conversations: [],      // current page of conversation rows
    conv_map: {},           // conv_id → conversation data (for quick lookup)
    expanded: {},           // conv_id → true if inline-expanded
    loading: false,
    list_widget: null       // rcube_list_widget instance for conversation list
  };

  // Guard to prevent on_conv_select from re-firing when open_conversation
  // programmatically selects a row via list_widget.select().
  var _conv_opening = false;

  // Lock ID returned by rcmail.set_busy(true, ...) — needed to clear it later.
  var _busy_lock = null;

  // ──────────────────────────────────────────────
  //  DOM references (resolved once on init)
  // ──────────────────────────────────────────────

  var dom = {};

  function resolve_dom() {
    dom.layout_list    = document.getElementById('layout-list');
    dom.layout_content = document.getElementById('layout-content');
    dom.standard_list  = document.getElementById('messagelist-content');
    dom.conv_list      = document.getElementById('conv-list-content');
    dom.conv_table     = document.getElementById('conv-messagelist');
    dom.conv_empty     = document.getElementById('conv-empty');
    dom.conv_pagination = document.getElementById('conv-pagination');
    dom.conv_prev_btn  = document.getElementById('conv-prev-btn');
    dom.conv_next_btn  = document.getElementById('conv-next-btn');
    dom.conv_page_info = document.getElementById('conv-page-info');
    dom.conv_detail    = document.getElementById('conv-detail');
    dom.conv_back_btn  = document.getElementById('conv-back-btn');
    dom.conv_subject   = document.getElementById('conv-detail-subject');
    dom.conv_meta      = document.getElementById('conv-detail-meta');
    dom.conv_messages  = document.getElementById('conv-messages');
    dom.standard_preview = dom.layout_content
      ? (dom.layout_content.querySelector('.conv-standard-preview')
        || dom.layout_content.querySelector('.iframe-wrapper')) : null;
  }

  /**
   * Ensure conversation containers exist even when a skin doesn't provide
   * a mail.html template override (e.g. stock Elastic).
   */
  function ensure_conv_structure() {
    if (!dom.layout_list || !dom.layout_content) return;

    if (!dom.conv_list) {
      var conv_list = document.createElement('div');
      conv_list.id = 'conv-list-content';
      conv_list.className = 'scroller conv-conversation-list';
      conv_list.tabIndex = -1;

      var conv_heading = document.createElement('h2');
      conv_heading.id = 'aria-label-convlist';
      conv_heading.className = 'voice';
      conv_heading.textContent = label('plugin_name');

      var conv_table = document.createElement('table');
      conv_table.id = 'conv-messagelist';
      conv_table.className = 'listing messagelist conv-list conv-outlook';
      conv_table.setAttribute('role', 'listbox');
      conv_table.setAttribute('aria-labelledby', 'aria-label-convlist');
      conv_table.appendChild(document.createElement('tbody'));

      var conv_empty = document.createElement('div');
      conv_empty.id = 'conv-empty';
      conv_empty.className = 'conv-empty';

      var conv_empty_icon = document.createElement('span');
      conv_empty_icon.className = 'conv-empty-icon';
      var conv_empty_fa = document.createElement('span');
      conv_empty_fa.className = 'conv-icon conv-icon-comments';
      conv_empty_fa.setAttribute('aria-hidden', 'true');
      conv_empty_icon.appendChild(conv_empty_fa);
      conv_empty.appendChild(conv_empty_icon);

      var conv_empty_text = document.createElement('span');
      conv_empty_text.className = 'conv-empty-text';
      conv_empty_text.textContent = label('no_conversations');
      conv_empty.appendChild(conv_empty_text);

      // Pagination container
      var conv_pagination = document.createElement('div');
      conv_pagination.id = 'conv-pagination';
      conv_pagination.className = 'conv-pagination';
      conv_pagination.style.display = 'none';

      var conv_prev_btn = document.createElement('a');
      conv_prev_btn.id = 'conv-prev-btn';
      conv_prev_btn.className = 'conv-page-btn conv-prev';
      conv_prev_btn.href = '#';
      conv_prev_btn.textContent = label('previouspage');

      var conv_page_info = document.createElement('span');
      conv_page_info.id = 'conv-page-info';
      conv_page_info.className = 'conv-page-info';

      var conv_next_btn = document.createElement('a');
      conv_next_btn.id = 'conv-next-btn';
      conv_next_btn.className = 'conv-page-btn conv-next';
      conv_next_btn.href = '#';
      conv_next_btn.textContent = label('nextpage');

      conv_pagination.appendChild(conv_prev_btn);
      conv_pagination.appendChild(conv_page_info);
      conv_pagination.appendChild(conv_next_btn);

      conv_list.appendChild(conv_heading);
      conv_list.appendChild(conv_table);
      conv_list.appendChild(conv_empty);
      conv_list.appendChild(conv_pagination);

      var pagenav = null;
      for (var i = dom.layout_list.children.length - 1; i >= 0; i--) {
        var child = dom.layout_list.children[i];
        if (child.classList
          && child.classList.contains('footer')
          && child.classList.contains('pagenav')) {
          pagenav = child;
          break;
        }
      }
      if (pagenav) {
        dom.layout_list.insertBefore(conv_list, pagenav);
      } else {
        dom.layout_list.appendChild(conv_list);
      }
    }

    if (!dom.conv_detail) {
      var detail_wrap = document.createElement('div');
      detail_wrap.id = 'conv-detail';
      detail_wrap.className = 'conv-detail-wrapper';
      detail_wrap.style.display = 'none';

      var detail_header = document.createElement('div');
      detail_header.className = 'conv-detail-header';

      var back_btn = document.createElement('a');
      back_btn.id = 'conv-back-btn';
      back_btn.className = 'conv-back-btn button';
      back_btn.href = '#';

      var back_inner = document.createElement('span');
      back_inner.className = 'inner';
      back_inner.textContent = label('back_to_list');
      back_btn.appendChild(back_inner);

      var detail_subject = document.createElement('h2');
      detail_subject.id = 'conv-detail-subject';
      detail_subject.className = 'conv-detail-subject';

      var detail_meta = document.createElement('div');
      detail_meta.id = 'conv-detail-meta';
      detail_meta.className = 'conv-detail-meta';

      var detail_messages = document.createElement('div');
      detail_messages.id = 'conv-messages';
      detail_messages.className = 'conv-messages scroller';

      detail_header.appendChild(back_btn);
      detail_header.appendChild(detail_subject);
      detail_header.appendChild(detail_meta);

      detail_wrap.appendChild(detail_header);
      detail_wrap.appendChild(detail_messages);

      dom.layout_content.appendChild(detail_wrap);
    }

    resolve_dom();
  }

  // ──────────────────────────────────────────────
  //  Initialization
  // ──────────────────────────────────────────────

  rcmail.addEventListener('init', function () {
    resolve_dom();
    ensure_conv_structure();

    conv_state.mode = rcmail.env.conversation_mode || 'list';
    conv_state.loaded_mailbox = rcmail.env.mailbox || 'INBOX';

    // Register commands
    rcmail.register_command('plugin.conv.toggle', cmd_toggle, true);
    rcmail.register_command('plugin.conv.archive', cmd_archive, false);
    rcmail.register_command('plugin.conv.delete', cmd_delete, false);
    rcmail.register_command('plugin.conv.flag', cmd_flag, false);

    // Expose minimal API for stratus_helper.js hover action dispatch
    rcmail.conv_api = {
      select_conv: function(conv_id) {
        if (!conv_state.list_widget) return;
        _conv_opening = true;
        conv_state.list_widget.select('rcmrowconv-' + conv_id);
        _conv_opening = false;
      }
    };

    // Register response handlers
    rcmail.addEventListener('plugin.conv.render_list', on_render_list);
    rcmail.addEventListener('plugin.conv.render_open', on_render_open);
    rcmail.addEventListener('plugin.conv.render_refresh', on_render_refresh);
    rcmail.addEventListener('plugin.conv.mode_changed', on_mode_changed);

    // Bind template back button
    if (dom.conv_back_btn) {
      dom.conv_back_btn.addEventListener('click', function (e) {
        e.preventDefault();
        close_conversation_detail();
      });
    }

    // Bind pagination buttons
    if (dom.conv_prev_btn) {
      dom.conv_prev_btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (conv_state.page > 1) {
          request_conversation_list(conv_state.page - 1);
        }
      });
    }
    if (dom.conv_next_btn) {
      dom.conv_next_btn.addEventListener('click', function (e) {
        e.preventDefault();
        if (conv_state.page < conv_state.pages) {
          request_conversation_list(conv_state.page + 1);
        }
      });
    }

    // Update toggle button state
    update_toggle_button();

    // Apply initial mode to DOM
    set_conv_mode(conv_state.mode);

    // If already in conversation mode on load, fetch the list
    if (conv_state.mode === 'conversations' && rcmail.env.mailbox) {
      request_conversation_list(1);
    }

    // Hook into mailbox switches
    // NOTE: do not fetch conversation rows on `selectfolder` directly.
    // `selectfolder` fires before the folder `list` command completes; fetching
    // here sets `rcmail.busy` too early and can swallow the first folder click.
    rcmail.addEventListener('selectfolder', function () {
      if (conv_state.mode === 'conversations') {
        conv_state.page = 1;
        conv_state.open_conv_id = null;
      }
    });

    // Fetch conversation rows after the standard list actually switched mailbox
    // OR when a search response arrives (mailbox unchanged but results filtered).
    rcmail.addEventListener('listupdate', function () {
      if (conv_state.mode !== 'conversations') return;

      var mailbox  = rcmail.env.mailbox || 'INBOX';
      var isSearch = !!(rcmail.env.search_request || rcmail.env.qsearch);

      if (isSearch) {
        conv_state._was_searching = true;
        // Collect matching UIDs from the standard message list (populated by
        // Roundcube's own search response, even though the list is hidden).
        var matchUids = [];
        if (rcmail.message_list && rcmail.message_list.rows) {
          for (var uid in rcmail.message_list.rows) {
            // In multi-folder view UIDs carry a '-mailbox' suffix; strip it.
            var n = parseInt(String(uid).split('-')[0], 10);
            if (n) matchUids.push(n);
          }
        }

        conv_state.page = 1;
        conv_state.open_conv_id = null;

        if (matchUids.length === 0) {
          // Zero matches – render empty list immediately, no round-trip needed.
          render_conversation_list([]);
          render_pagination();
          update_conv_empty_label();
          emit_conv_selection_state();
          emit_conv_count_update();
        } else {
          request_conversation_list(1, matchUids);
        }
        return;
      }

      // Not in a search – refresh when mailbox changed or after search was reset.
      if (conv_state.loaded_mailbox !== mailbox || conv_state._was_searching) {
        conv_state._was_searching = false;
        conv_state.page = 1;
        conv_state.open_conv_id = null;
        request_conversation_list(1);
      }
    });

    // Hook into check-recent (auto-refresh)
    rcmail.addEventListener('aftercheckmail', function () {
      if (conv_state.mode === 'conversations') {
        request_refresh();
      }
    });

    // Integrate conversation mode into the list-options dialog mode select
    init_conv_in_list_options();
  });

  // ──────────────────────────────────────────────
  //  List-Options Dialog Integration
  // ──────────────────────────────────────────────

  /**
   * Integrate conversation mode into the "List mode" select inside the list
   * options dialog.
   *
   * Responsibilities:
   *  1. Ensure a 'conversations' <option> is present in #listoptions-threads
   *     (fallback for when the template already provides it, this is a no-op).
   *  2. When the list-options dialog opens, pre-select 'conversations' in the
   *     cloned mode <select> if that is the current active mode.
   *  3. Monkey-patch rcmail.set_list_options once so that picking 'conversations'
   *     from the dialog fires plugin.conv.setmode instead of being silently
   *     treated as 'list' (threading=0).  Switching away from conversations
   *     while in that mode also fires setmode to deactivate it.
   */
  function init_conv_in_list_options() {
    // Only integrate if we're in the mail task
    if (rcmail.env.task !== 'mail') return;

    // ── 1. Ensure 'conversations' option exists in the source select ──
    // The template adds it inside env:threads; if threads is disabled we
    // inject it here so the dialog always shows the option.
    var modeSelect = document.getElementById('listoptions-threads')
                  || document.getElementById('mp-listoptions-mode');
    if (!modeSelect) {
      var popup = document.getElementById('listoptions-menu');
      if (popup) {
        var row = document.createElement('div');
        row.className = 'form-group row';
        row.id = 'mp-listoptions-mode-row';
        var lmodeLabel = rcmail.get_label('lmode') || 'List mode';
        var listLabel  = rcmail.get_label('list') || 'List';
        var convLabel  = label('mode_conversations') || 'Conversations';
        row.innerHTML =
          '<label for="mp-listoptions-mode" class="col-form-label col-sm-4">' + lmodeLabel + '</label>' +
          '<div class="col-sm-8">' +
            '<select id="mp-listoptions-mode" name="mode">' +
              '<option value="list">' + listLabel + '</option>' +
              '<option value="conversations">' + convLabel + '</option>' +
            '</select>' +
          '</div>';
        var containerEl = popup.querySelector('#listoptionsmenu');
        if (containerEl) {
          popup.insertBefore(row, containerEl);
        } else {
          popup.appendChild(row);
        }
        modeSelect = row.querySelector('select');
      }
    } else if (!modeSelect.querySelector('option[value="conversations"]')) {
      // Template select exists but missing the conversations option — inject it
      var convOpt = document.createElement('option');
      convOpt.value = 'conversations';
      convOpt.textContent = label('mode_conversations') || 'Conversations';
      modeSelect.appendChild(convOpt);
    }

    // ── 2. Sync dialog mode select when the list-options dialog opens ──
    // elastic's menu_messagelist() sets the cloned select to 'threads' or
    // 'list'; we override to 'conversations' when that is the active mode.
    $(document).on('dialogopen', function(e) {
      var dlg = $(e.target);
      var modeSel = dlg.find('select[name="mode"]');
      if (!modeSel.length || !modeSel.find('option[value="conversations"]').length) return;

      if (dom.layout_list && dom.layout_list.getAttribute('data-conv-mode') === 'conversations') {
        modeSel.val('conversations');
      }
    });

    // ── 3. Monkey-patch set_list_options (once) ──
    if (rcmail._stratus_conv_slo_patched) return;
    rcmail._stratus_conv_slo_patched = true;

    var _origSLO = rcmail.set_list_options;
    rcmail.set_list_options = function(list, col, ord, threading, page) {
      // Read the currently-open dialog's mode select value (if any)
      var modeEl = document.querySelector('.ui-dialog-content select[name="mode"]');
      var selectedMode = modeEl ? modeEl.value : null;
      var currentMode  = dom.layout_list ? dom.layout_list.getAttribute('data-conv-mode') : 'list';

      if (selectedMode === 'conversations') {
        // Activate conversation mode and let set_list_options keep threading=0
        if (currentMode !== 'conversations') {
          rcmail.http_post('plugin.conv.setmode', { _mode: 'conversations' });
        }
        threading = 0;
      } else if (selectedMode !== null && currentMode === 'conversations') {
        // Leaving conversation mode — deactivate it server-side
        rcmail.http_post('plugin.conv.setmode', { _mode: 'list' });
      }

      return _origSLO.call(rcmail, list, col, ord, threading, page);
    };
  }

  // ──────────────────────────────────────────────
  //  Mode Toggling (data-conv-mode attribute)
  // ──────────────────────────────────────────────

  /**
   * Set the active panel mode via data attribute.
   * CSS rules in conversation_mode.css handle show/hide.
   */
  function set_conv_mode(mode) {
    if (dom.layout_list) {
      dom.layout_list.setAttribute('data-conv-mode', mode);
    }

    // Toggle detail / standard preview
    if (mode !== 'conversations') {
      hide_detail_panel();
    }
  }

  // ──────────────────────────────────────────────
  //  Commands
  // ──────────────────────────────────────────────

  function cmd_toggle() {
    var new_mode = (conv_state.mode === 'conversations') ? 'list' : 'conversations';
    rcmail.http_post('plugin.conv.setmode', { _mode: new_mode });
  }

  function cmd_archive() {
    var sel = get_selected_conv_ids();
    var has_child_sel = get_selected_child_uids().length > 0;
    if (!sel.length && !has_child_sel) return;

    var archive_folder = rcmail.env.archive_folder;
    if (!archive_folder) {
      // Try to detect from mailbox classes
      var mboxes = rcmail.env.mailboxes || {};
      for (var key in mboxes) {
        if (mboxes.hasOwnProperty(key) && mboxes[key]['class'] === 'archive') {
          archive_folder = key;
          break;
        }
      }
    }
    if (!archive_folder) {
      rcmail.display_message('No archive folder configured', 'warning');
      return;
    }

    var uids = get_all_selected_uids();
    if (!uids.length) return;

    var mbox = rcmail.env.mailbox || 'INBOX';
    rcmail.http_post('move', {
      _uid: uids.join(','),
      _target_mbox: archive_folder,
      _mbox: mbox,
      _from: 'list'
    });

    // Remove fully-selected conversations from view
    if (sel.length) remove_conversations_from_view(sel);
    clear_child_selections();
    // If individual children were selected, refresh to sync partial changes
    if (has_child_sel) {
      setTimeout(function() { request_conversation_list(conv_state.page); }, 500);
    }
  }

  function cmd_delete() {
    var sel = get_selected_conv_ids();
    var has_child_sel = get_selected_child_uids().length > 0;
    if (!sel.length && !has_child_sel) return;

    var uids = get_all_selected_uids();
    if (!uids.length) return;

    var trash_folder = rcmail.env.trash_mailbox;
    var mbox = rcmail.env.mailbox || 'INBOX';

    if (trash_folder && mbox === trash_folder) {
      // Already in trash — permanently delete
      rcmail.http_post('delete', {
        _uid: uids.join(','),
        _mbox: mbox,
        _from: 'list'
      });
    } else if (trash_folder) {
      // Move to trash
      rcmail.http_post('move', {
        _uid: uids.join(','),
        _target_mbox: trash_folder,
        _mbox: mbox,
        _from: 'list'
      });
    } else {
      // No trash folder — permanently delete
      rcmail.http_post('delete', {
        _uid: uids.join(','),
        _mbox: mbox,
        _from: 'list'
      });
    }

    // Remove fully-selected conversations from view
    if (sel.length) remove_conversations_from_view(sel);
    clear_child_selections();
    // If individual children were selected, refresh to sync partial changes
    if (has_child_sel) {
      setTimeout(function() { request_conversation_list(conv_state.page); }, 500);
    }
  }

  function cmd_flag() {
    var sel = get_selected_conv_ids();
    var child_uids = get_selected_child_uids();
    if (!sel.length && !child_uids.length) return;

    var state = get_conversation_selection_state();
    var target_flag = state.anyUnflagged ? 'flagged' : 'unflagged';

    // Parent selections: use optimistic per-conv logic
    if (sel.length) apply_flag_to_selected_conversations(target_flag);

    // Handle child-only selections (not covered by parent convs)
    if (child_uids.length) {
      var parent_uids = {};
      for (var i = 0; i < sel.length; i++) {
        var c = conv_state.conv_map[sel[i]];
        if (c && c._messages) {
          for (var j = 0; j < c._messages.length; j++) parent_uids[String(c._messages[j].uid)] = true;
        }
        if (c && c.latest_uid) parent_uids[String(c.latest_uid)] = true;
      }
      var orphan_uids = child_uids.filter(function(u) { return !parent_uids[u]; });
      if (orphan_uids.length) {
        rcmail.http_post('mark', {
          _uid: orphan_uids.join(','),
          _flag: target_flag,
          _mbox: rcmail.env.mailbox || 'INBOX',
          _from: 'list'
        });
      }
      clear_child_selections();
      setTimeout(function() { request_conversation_list(conv_state.page); }, 500);
    }
  }

  function cmd_mark_read() {
    var sel = get_selected_conv_ids();
    var child_uids = get_selected_child_uids();
    if (!sel.length && !child_uids.length) return;

    var state = get_conversation_selection_state();
    var target_flag = state.anyUnread ? 'read' : 'unread';

    // Parent selections: use optimistic per-conv logic
    if (sel.length) apply_read_state_to_selected_conversations(target_flag);

    // Handle child-only selections (not covered by parent convs)
    if (child_uids.length) {
      var parent_uids = {};
      for (var i = 0; i < sel.length; i++) {
        var c = conv_state.conv_map[sel[i]];
        if (c && c._messages) {
          for (var j = 0; j < c._messages.length; j++) parent_uids[String(c._messages[j].uid)] = true;
        }
        if (c && c.latest_uid) parent_uids[String(c.latest_uid)] = true;
      }
      var orphan_uids = child_uids.filter(function(u) { return !parent_uids[u]; });
      if (orphan_uids.length) {
        rcmail.http_post('mark', {
          _uid: orphan_uids.join(','),
          _flag: target_flag,
          _mbox: rcmail.env.mailbox || 'INBOX',
          _from: 'list'
        });
      }
      clear_child_selections();
      setTimeout(function() { request_conversation_list(conv_state.page); }, 500);
    }
  }

  function apply_read_state_to_selected_conversations(target_flag) {
    var sel = get_selected_conv_ids();
    if (!sel.length) return;

    for (var i = 0; i < sel.length; i++) {
      var conv = conv_state.conv_map[sel[i]];
      if (!conv) continue;

      // Collect UIDs to act on
      var uids_to_mark = [];

      if (target_flag === 'read') {
        // Mark as read — collect unread message UIDs
        if (conv._messages) {
          for (var j = 0; j < conv._messages.length; j++) {
            var m = conv._messages[j];
            if (!m.flags || m.flags.indexOf('seen') === -1) {
              uids_to_mark.push(m.uid);
            }
          }
        }
        if (!uids_to_mark.length && conv.latest_uid) {
          uids_to_mark.push(conv.latest_uid);
        }
      } else {
        // Mark as unread — latest message only
        if (conv.latest_uid) uids_to_mark.push(conv.latest_uid);
      }

      if (!uids_to_mark.length) continue;

      // Use http_post directly — rcmail.command('mark') needs list selection
      // which isn't set in conversation mode.
      rcmail.http_post('mark', {
        _uid: uids_to_mark.join(','),
        _flag: target_flag,
        _mbox: rcmail.env.mailbox || 'INBOX',
        _from: 'list'
      });

      // Optimistically update local state
      conv.unread_count = (target_flag === 'read') ? 0 : 1;
      var parent_row = document.getElementById('rcmrowconv-' + sel[i]);
      if (parent_row) {
        if (target_flag === 'read') {
          parent_row.classList.remove('unread');
        } else {
          parent_row.classList.add('unread');
        }
      }
      update_parent_action_icons(sel[i]);
    }

    emit_conv_selection_state();
  }

  function apply_flag_to_selected_conversations(target_flag) {
    var sel = get_selected_conv_ids();
    if (!sel.length) return;

    for (var i = 0; i < sel.length; i++) {
      var conv = conv_state.conv_map[sel[i]];
      if (!conv) continue;

      var uids_to_mark = [];

      if (conv._messages) {
        for (var j = 0; j < conv._messages.length; j++) {
          var m = conv._messages[j];
          var is_flagged = m.flags && m.flags.indexOf('flagged') !== -1;
          if (target_flag === 'flagged' && !is_flagged) {
            uids_to_mark.push(m.uid);
          }
          if (target_flag === 'unflagged' && is_flagged) {
            uids_to_mark.push(m.uid);
          }
        }
      }

      if (!uids_to_mark.length && conv.latest_uid) {
        uids_to_mark.push(conv.latest_uid);
      }

      if (!uids_to_mark.length) continue;

      rcmail.http_post('mark', {
        _uid: uids_to_mark.join(','),
        _flag: target_flag,
        _mbox: rcmail.env.mailbox || 'INBOX',
        _from: 'list'
      });

      conv.flagged_count = (target_flag === 'flagged')
        ? (conv._messages ? conv._messages.length : Math.max(1, conv.flagged_count || 0))
        : 0;

      // Update flags in local message data model
      if (conv._messages) {
        for (var k = 0; k < conv._messages.length; k++) {
          var mflags = conv._messages[k].flags || [];
          if (target_flag === 'flagged' && mflags.indexOf('flagged') === -1) {
            mflags.push('flagged');
          } else if (target_flag === 'unflagged') {
            var fIdx = mflags.indexOf('flagged');
            if (fIdx !== -1) mflags.splice(fIdx, 1);
          }
          conv._messages[k].flags = mflags;
        }
      }

      var parent_row = document.getElementById('rcmrowconv-' + sel[i]);
      if (parent_row) {
        if (target_flag === 'flagged') {
          parent_row.classList.add('flagged');
        } else {
          parent_row.classList.remove('flagged');
        }
      }

      // Update expanded child rows if any are visible
      if (dom.conv_table) {
        var child_rows = dom.conv_table.querySelectorAll(
          'tr.conv-child-row[data-parent-conv="' + sel[i] + '"]'
        );
        for (var cr = 0; cr < child_rows.length; cr++) {
          if (target_flag === 'flagged') {
            child_rows[cr].classList.add('flagged');
          } else {
            child_rows[cr].classList.remove('flagged');
          }
          // Update persistent flag indicator icon in child row
          var cIcons = child_rows[cr].querySelector('.conv-status-icons');
          if (cIcons) {
            var cFlagIcon = cIcons.querySelector('.conv-status-flag-icon');
            if (target_flag === 'flagged' && !cFlagIcon) {
              var new_c_flag_icon = fa_icon('flag', label('flagged'));
              new_c_flag_icon.classList.add('conv-status-flag-icon');
              cIcons.appendChild(new_c_flag_icon);
            } else if (target_flag === 'unflagged' && cFlagIcon) {
              cFlagIcon.parentNode.removeChild(cFlagIcon);
            }
          }
          // Update child hover action button state
          var cFlagBtn = child_rows[cr].querySelector('.conv-action-flag');
          if (cFlagBtn) {
            var cNextFlag = (target_flag === 'flagged') ? 'unflagged' : 'flagged';
            var cNextIcon = (target_flag === 'flagged') ? 'flag' : 'flag-regular';
            var cNextTitle = (target_flag === 'flagged') ? label('markunflagged') : label('markflagged');
            cFlagBtn.setAttribute('data-action-value', cNextFlag);
            cFlagBtn.setAttribute('title', cNextTitle);
            var cBtnIcon = cFlagBtn.querySelector('.conv-icon');
            if (cBtnIcon) cBtnIcon.className = 'conv-icon conv-icon-' + cNextIcon;
          }
        }
      }

      update_parent_action_icons(sel[i]);
    }

    emit_conv_selection_state();
  }

  // ──────────────────────────────────────────────
  //  AJAX Requests
  // ──────────────────────────────────────────────

  function request_conversation_list(page, matchUids) {
    if (conv_state.loading) return;
    conv_state.loading = true;
    show_loading(true);

    var params = {
      _mbox: rcmail.env.mailbox || 'INBOX',
      _page: page || 1
    };

    if (matchUids && matchUids.length) {
      params._match_uids = matchUids.join(',');
    }

    rcmail.http_request('plugin.conv.list', params);
  }

  function request_open_conversation(conv_id) {
    rcmail.http_request('plugin.conv.open', {
      _mbox: rcmail.env.mailbox || 'INBOX',
      _conv_id: conv_id
    });
  }

  function request_refresh() {
    rcmail.http_request('plugin.conv.refresh', {
      _mbox: rcmail.env.mailbox || 'INBOX'
    });
  }

  // ──────────────────────────────────────────────
  //  Response Handlers
  // ──────────────────────────────────────────────

  function on_render_list(data) {
    conv_state.loading = false;
    show_loading(false);
    conv_state.loaded_mailbox = rcmail.env.mailbox || 'INBOX';

    if (!data || !data.conversations) return;

    conv_state.conversations = data.conversations;
    conv_state.total = data.total || 0;
    conv_state.page = data.page || 1;
    conv_state.pages = data.pages || 1;
    conv_state.open_conv_id = null;
    conv_state.conv_map = {};
    conv_state.expanded = {};

    for (var i = 0; i < data.conversations.length; i++) {
      var c = data.conversations[i];
      conv_state.conv_map[c.conversation_id] = c;
    }

    render_conversation_list(data.conversations);
    render_pagination();
    update_conv_empty_label();
    emit_conv_selection_state();
    emit_conv_count_update();
  }

  function on_render_open(data) {
    if (!data || !data.messages) return;

    var conv_id = data.conversation_id;
    conv_state.open_conv_id = conv_id;

    // Cache messages in conv_map for instant re-expand
    if (conv_state.conv_map[conv_id]) {
      conv_state.conv_map[conv_id]._messages = data.messages;
    }

    // Only render child rows if the conversation is still expanded
    if (conv_state.expanded[conv_id]) {
      insert_child_rows(conv_id, data.messages);
      update_expand_arrow(conv_id, true);
    }
  }

  function on_render_refresh(data) {
    on_render_list(data);
  }

  function on_mode_changed(data) {
    conv_state.mode = data.mode;
    update_toggle_button();
    set_conv_mode(data.mode);

    if (data.mode === 'conversations') {
      request_conversation_list(1);
    } else {
      // Reset multiselect mode and child selections when leaving conversation mode
      _conv_multiselect_mode = false;
      clear_child_selections();
      restore_standard_list();
    }

    emit_conv_selection_state();
    emit_conv_count_update();
  }

  // ──────────────────────────────────────────────
  //  Conversation List Rendering (rcube_list_widget)
  // ──────────────────────────────────────────────

  /**
   * Render the conversation list into the template's #conv-messagelist table.
   * Uses a real rcube_list_widget for keyboard nav, multi-select, drag-drop.
   */
  function render_conversation_list(conversations) {
    if (!dom.conv_table) return;

    // Tear down previous widget (but keep the table element)
    destroy_conv_widget();

    // Clear existing rows
    var tbody = dom.conv_table.querySelector('tbody');
    if (!tbody) {
      tbody = document.createElement('tbody');
      dom.conv_table.appendChild(tbody);
    }
    tbody.innerHTML = '';

    // Show/hide empty state.
    // Use 'flex' (not '') when showing: the CSS default is #conv-empty{display:none}
    // (ID selector beats .conv-empty{display:flex}), so clearing the inline style
    // leaves it hidden.  We must set an explicit value to win the cascade.
    if (dom.conv_empty) {
      dom.conv_empty.style.display = conversations.length ? 'none' : 'flex';
    }

    if (!conversations.length) return;

    // Create the list widget
    var list = new rcube_list_widget(dom.conv_table, {
      multiselect: true,
      multiexpand: false,
      draggable: true,
      keyboard: true,
      column_movable: false,
      checkbox_selection: false,
      list_role: 'listbox'
    });

    list.addEventListener('select', on_conv_select);
    list.addEventListener('dblclick', on_conv_dblclick);
    list.addEventListener('keypress', on_conv_keypress);
    list.addEventListener('dragstart', function (o) { rcmail.drag_start && rcmail.drag_start(o); });
    list.addEventListener('dragmove', function (e) { rcmail.drag_move && rcmail.drag_move(e); });
    list.addEventListener('dragend', function (e) { rcmail.drag_end && rcmail.drag_end(e); });

    list.init();
    conv_state.list_widget = list;

    // Re-apply multiselect monkey-patch if mode is active (Bug 5)
    if (_conv_multiselect_mode) {
      apply_conv_multiselect_patch();
    }

    // Insert rows
    for (var i = 0; i < conversations.length; i++) {
      var row = build_conv_row(conversations[i]);
      list.insert_row(row);
    }
  }

  /**
   * Build a DOM <tr> for a conversation, matching the 3-line Outlook layout.
   *
   * Structure per row:
   *   <td class="conv-avatar">  → initials circle
   *   <td class="subject">      → 3 flex-wrapped lines (sender+count, subject, snippet)
   *   <td class="flags">        → FA icons + hover actions
   */
  function build_conv_row(conv) {
    var is_unread = conv.unread_count > 0;
    var is_flagged = conv.flagged_count > 0;
    var is_multi = conv.message_count > 1;

    var row_class = 'message conv-row'
      + (is_unread ? ' unread' : '')
      + (is_flagged ? ' flagged' : '')
      + (is_multi ? ' conv-has-children' : '');

    var tr = document.createElement('tr');
    tr.id = 'rcmrowconv-' + conv.conversation_id;
    tr.uid = 'conv-' + conv.conversation_id;
    tr.className = row_class;
    tr.setAttribute('data-conv-id', conv.conversation_id);

    // ─── Column 1: Expand arrow ───
    var td_expand = document.createElement('td');
    td_expand.className = 'conv-expand-cell';
    if (is_multi) {
      var arrow = document.createElement('span');
      arrow.className = 'conv-expand-arrow';
      arrow.setAttribute('role', 'button');
      arrow.setAttribute('aria-label', label('expand_conversation'));
      arrow.setAttribute('tabindex', '0');
      (function (cid) {
        // Prevent list-widget row selection handlers (mousedown/click) from
        // auto-expanding first, which can otherwise immediately collapse again
        // when the explicit expander handler runs.
        td_expand.addEventListener('mousedown', function (e) {
          e.stopPropagation();
        });
        td_expand.addEventListener('click', function (e) {
          if (e.target !== td_expand) return;
          e.preventDefault();
          e.stopPropagation();
          toggle_expand(cid);
        });
        td_expand.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            e.stopPropagation();
            toggle_expand(cid);
          }
        });

        arrow.addEventListener('mousedown', function (e) {
          e.stopPropagation();
        });
        arrow.addEventListener('click', function (e) {
          e.preventDefault();
          e.stopPropagation();
          toggle_expand(cid);
        });
        arrow.addEventListener('keydown', function (e) {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            e.stopPropagation();
            toggle_expand(cid);
          }
        });
      })(conv.conversation_id);
      td_expand.appendChild(arrow);
    }
    tr.appendChild(td_expand);

    // ─── Column 2: Avatar ───
    var td_avatar = document.createElement('td');
    td_avatar.className = 'conv-avatar';
    var sender_name = (conv.participants && conv.participants.length)
      ? conv.participants[0] : '?';
    td_avatar.appendChild(build_avatar(sender_name));
    tr.appendChild(td_avatar);

    // ─── Column 3: Subject (3-line flex cell) ───
    var td_subject = document.createElement('td');
    td_subject.className = 'subject';

    // Line 1: sender(s) + count + date (all in one flex row)
    var line1 = document.createElement('span');
    line1.className = 'conv-line1 skip-on-drag';

    var sender_el = document.createElement('span');
    sender_el.className = 'conv-sender';
    sender_el.textContent = format_participants(conv.participants || []);
    line1.appendChild(sender_el);

    if (is_multi) {
      var count_el = document.createElement('span');
      count_el.className = 'conv-count';
      count_el.textContent = conv.message_count;
      line1.appendChild(count_el);
    }

    // Date in line1 — right-aligned via flex
    var date_el = document.createElement('span');
    date_el.className = 'conv-date skip-on-drag';
    date_el.textContent = format_date(conv.latest_timestamp);
    line1.appendChild(date_el);

    td_subject.appendChild(line1);

    // Line 2: subject + status icons
    var line2 = document.createElement('span');
    line2.className = 'conv-line2';

    if (is_unread) {
      var dot = document.createElement('span');
      dot.className = 'conv-unread-dot';
      dot.setAttribute('aria-label', label('unread_count').replace('$n', conv.unread_count));
      line2.appendChild(dot);
    }

    var subj_el = document.createElement('span');
    subj_el.className = 'conv-subject-text';
    subj_el.textContent = conv.subject || label('nosubject');
    line2.appendChild(subj_el);

    // Inline status icons (attachment only — flag indicator is in column 4)
    var icons = document.createElement('span');
    icons.className = 'conv-status-icons skip-on-drag';

    if (conv.has_attachments) {
      icons.appendChild(fa_icon('paperclip', label('withattachment')));
    }

    line2.appendChild(icons);
    td_subject.appendChild(line2);

    // Line 3: snippet (with quoted content stripped)
    var cleaned_snippet = clean_snippet(conv.snippet);
    if (cleaned_snippet) {
      var line3 = document.createElement('span');
      line3.className = 'conv-line3 skip-on-drag';
      line3.textContent = cleaned_snippet;
      td_subject.appendChild(line3);
    }

    tr.appendChild(td_subject);

    // ─── Column 4: Flag indicator + hover action bar ───
    // Narrow cell (~2.5em) matching Elastic's native td.flags width.
    // Date and count are now in td.subject .conv-line1.
    var td_flags = document.createElement('td');
    td_flags.className = 'flags conv-flags-cell';

    // Persistent flag indicator (visible only when row has .flagged class)
    var flag_ind = document.createElement('span');
    flag_ind.className = 'conv-flag-indicator conv-icon conv-icon-flag';
    flag_ind.setAttribute('aria-label', label('flagged'));
    td_flags.appendChild(flag_ind);

    // Hover action bar for parent rows is injected by stratus_helper.js
    // via initUnifiedHoverActions() / MutationObserver (mp-hover-actions).
    tr.appendChild(td_flags);

    return tr;
  }

  // ──────────────────────────────────────────────
  //  List Widget Event Handlers
  // ──────────────────────────────────────────────

  function on_conv_select(o) {
    // In single-select mode, clear child selections when parent changes.
    // In multiselect mode, preserve child selections for combined actions.
    if (!_conv_multiselect_mode) {
      clear_child_selections();
    }

    var sel = get_selected_conv_ids();
    var has_sel = sel.length > 0;

    rcmail.enable_command('plugin.conv.archive', has_sel);
    rcmail.enable_command('plugin.conv.delete', has_sel);
    rcmail.enable_command('plugin.conv.flag', has_sel);
    emit_conv_selection_state();

    // Single-click: load latest message in preview + expand inline
    if (sel.length === 1 && !_conv_opening) {
      var conv = conv_state.conv_map[sel[0]];
      if (conv) {
        // Load latest message in the standard preview iframe
        if (conv.latest_uid) {
          load_message_preview(conv.latest_uid);
        }
        // Auto-expand multi-message conversations
        if (conv.message_count > 1 && !conv_state.expanded[sel[0]]) {
          expand_conversation(sel[0]);
        }
      }
    }
  }

  function on_conv_dblclick(o) {
    // Double-click: no special action (single-click already expands + previews)
  }

  function on_conv_keypress(o) {
    if (o.key_pressed === 13) { // Enter
      var sel = conv_state.list_widget.get_selection();
      if (sel.length === 1) {
        var tr = document.getElementById(sel[0]);
        if (tr) {
          var conv_id = tr.getAttribute('data-conv-id');
          if (conv_id) {
            var conv = conv_state.conv_map[conv_id];
            if (conv && conv.latest_uid) {
              load_message_preview(conv.latest_uid);
            }
            if (conv && conv.message_count > 1) {
              toggle_expand(conv_id);
            }
          }
        }
      }
    }
  }

  // ──────────────────────────────────────────────
  //  Detail View Rendering (conversation opened)
  // ──────────────────────────────────────────────

  /**
   * Populate the template's #conv-detail panel with conversation data.
   */
  function render_conversation_detail(data) {
    if (!dom.conv_detail) return;

    // Fill header
    if (dom.conv_subject) {
      dom.conv_subject.textContent = data.subject || label('nosubject');
    }
    if (dom.conv_meta) {
      dom.conv_meta.textContent = (data.message_count || 0) + ' messages · ' +
        (data.participants ? data.participants.join(', ') : '');
    }

    // Fill message list
    if (dom.conv_messages) {
      dom.conv_messages.innerHTML = '';

      if (data.messages && data.messages.length > 0) {
        for (var i = 0; i < data.messages.length; i++) {
          dom.conv_messages.appendChild(create_message_card(data.messages[i]));
        }
      } else {
        dom.conv_messages.innerHTML = '<div class="conv-empty">' +
          label('no_conversations') + '</div>';
      }
    }

    show_detail_panel();
  }

  /**
   * Show the conversation detail panel, hide the list scroller and preview.
   * Must set an explicit display value to override the CSS default of
   * `#conv-detail { display: none }`.
   */
  function show_detail_panel() {
    if (dom.conv_detail) dom.conv_detail.style.display = 'flex';
    if (dom.standard_preview) dom.standard_preview.style.display = 'none';

    // On mobile, hide the list panel and show the content panel
    if (dom.conv_list) dom.conv_list.style.display = 'none';
    if (dom.layout_list) dom.layout_list.classList.remove('selected');
    if (dom.layout_content) dom.layout_content.classList.add('selected');
  }

  /**
   * Hide the detail panel, restore the list view.
   */
  function hide_detail_panel() {
    if (dom.conv_detail) dom.conv_detail.style.display = 'none';
    if (dom.standard_preview) dom.standard_preview.style.display = '';

    // On mobile, show the list panel
    if (dom.conv_list && conv_state.mode === 'conversations') {
      dom.conv_list.style.display = '';
    }
  }

  function close_conversation_detail() {
    conv_state.open_conv_id = null;
    hide_detail_panel();

    // Restore list panel focus on mobile
    if (dom.layout_list) dom.layout_list.classList.add('selected');
    if (dom.layout_content) dom.layout_content.classList.remove('selected');
  }

  /**
   * Create a message card element for the detail view.
   */
  function create_message_card(msg) {
    var card = document.createElement('div');
    card.className = 'conv-message-card';
    var is_unread = msg.flags && msg.flags.indexOf('seen') === -1;
    if (is_unread) card.classList.add('unread');

    var card_header = document.createElement('div');
    card_header.className = 'conv-message-header';

    var from_wrap = document.createElement('div');
    from_wrap.className = 'conv-msg-from-wrap';

    var avatar = build_avatar(msg.from || '?');
    avatar.classList.add('conv-msg-avatar');
    from_wrap.appendChild(avatar);

    var from_el = document.createElement('span');
    from_el.className = 'conv-msg-from';
    from_el.textContent = msg.from || '';
    from_wrap.appendChild(from_el);

    var date_el = document.createElement('span');
    date_el.className = 'conv-msg-date';
    date_el.textContent = format_date(msg.timestamp);

    card_header.appendChild(from_wrap);
    card_header.appendChild(date_el);

    var subject_el = document.createElement('div');
    subject_el.className = 'conv-msg-subject';
    subject_el.textContent = msg.subject || '';

    var actions = document.createElement('div');
    actions.className = 'conv-msg-actions';

    var open_link = document.createElement('a');
    open_link.className = 'conv-msg-open button btn-sm';
    open_link.href = '#';
    open_link.appendChild(fa_icon('external-link-square-alt', label('open_message')));
    open_link.appendChild(document.createTextNode(' ' + label('open_message')));
    open_link.addEventListener('click', function (e) {
      e.preventDefault();
      var uid = msg.uid;
      if (uid) {
        // Use Roundcube's standard message loading into the content frame
        if (rcmail.open_message) {
          rcmail.open_message(uid);
        } else if (rcmail.show_message) {
          rcmail.show_message(uid);
        } else {
          // Fallback: navigate to the message view URL
          var url = rcmail.url('show', { _uid: uid, _mbox: rcmail.env.mailbox });
          if (rcmail.env.contentframe) {
            var _win = rcmail.get_frame_window(rcmail.env.contentframe);
            rcmail.location_href(url, _win || window);
          } else {
            rcmail.location_href(url);
          }
        }
      }
    });
    actions.appendChild(open_link);

    card.appendChild(card_header);
    card.appendChild(subject_el);
    card.appendChild(actions);

    return card;
  }

  // ──────────────────────────────────────────────
  //  Pagination
  // ──────────────────────────────────────────────

  /**
   * Update the template's #conv-pagination element with current page info.
   */
  function render_pagination() {
    if (!dom.conv_pagination) return;

    if (conv_state.pages <= 1) {
      dom.conv_pagination.style.display = 'none';
      return;
    }

    dom.conv_pagination.style.display = '';

    // Update page indicator
    if (dom.conv_page_info) {
      dom.conv_page_info.textContent = label('page_of')
        .replace('$current', conv_state.page)
        .replace('$total', conv_state.pages);
    }

    // Toggle prev/next button state
    if (dom.conv_prev_btn) {
      toggle_class(dom.conv_prev_btn, 'disabled', conv_state.page <= 1);
    }
    if (dom.conv_next_btn) {
      toggle_class(dom.conv_next_btn, 'disabled', conv_state.page >= conv_state.pages);
    }
  }

  // ──────────────────────────────────────────────
  //  Avatar Builder
  // ──────────────────────────────────────────────

  /**
   * Generate a colored circle with first-letter initials from a name.
   * Color is deterministic based on a simple hash of the name.
   */
  function build_avatar(name) {
    var el = document.createElement('span');
    el.className = 'conv-avatar-circle';

    // Extract initials (first letter, or first two words)
    var initials = '?';
    if (name) {
      // Remove email brackets
      var clean = name.replace(/<[^>]+>/g, '').trim();
      if (clean) {
        var parts = clean.split(/\s+/);
        if (parts.length >= 2) {
          initials = (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        } else {
          initials = clean[0].toUpperCase();
        }
      }
    }

    el.textContent = initials;
    el.style.backgroundColor = avatar_color(name || '');
    el.setAttribute('aria-hidden', 'true');

    return el;
  }

  /**
   * Deterministic color from a string hash.
   */
  function avatar_color(str) {
    var hash = 0;
    for (var i = 0; i < str.length; i++) {
      hash = str.charCodeAt(i) + ((hash << 5) - hash);
      hash |= 0; // to 32-bit int
    }
    return AVATAR_COLORS[Math.abs(hash) % AVATAR_COLORS.length];
  }

  // ──────────────────────────────────────────────
  //  UI Helpers
  // ──────────────────────────────────────────────

  /**
   * Create a Font Awesome icon span.
   */
  function fa_icon(icon_name, title) {
    var span = document.createElement('span');
    span.className = 'conv-icon conv-icon-' + icon_name;
    if (title) span.setAttribute('title', title);
    span.setAttribute('aria-hidden', 'true');
    return span;
  }

  function update_parent_action_icons(conv_id) {
    var row = document.getElementById('rcmrowconv-' + conv_id);
    if (!row) return;

    var conv = conv_state.conv_map[conv_id];
    if (!conv) return;

    var is_unread = conv.unread_count > 0;
    var is_flagged = conv.flagged_count > 0;

    var mark_btn = row.querySelector('.conv-action-mark_read');
    if (mark_btn) {
      mark_btn.setAttribute('title', is_unread ? label('markread') : label('markunread'));
      var mark_icon = mark_btn.querySelector('.conv-icon');
      if (mark_icon) {
        mark_icon.className = 'conv-icon conv-icon-' + (is_unread ? 'envelope-open' : 'envelope');
      }
    }

    var flag_btn = row.querySelector('.conv-action-flag, .mp-hover-btn.flag');
    if (flag_btn) {
      flag_btn.setAttribute('title', is_flagged ? label('markunflagged') : label('markflagged'));
      var flag_icon = flag_btn.querySelector('.conv-icon');
      if (flag_icon) {
        flag_icon.className = 'conv-icon conv-icon-' + (is_flagged ? 'flag' : 'flag-regular');
      }
    }

    // Toggle persistent flag indicator + row class
    toggle_class(row, 'flagged', is_flagged);
  }

  /**
   * Create a hover-action button for child message rows.
   * Actions apply to the individual message (uid), not the whole conversation.
   */
  function child_action_btn(action, icon_name, title, uid, action_value) {
    var btn = document.createElement('a');
    btn.className = 'conv-action-btn conv-action-' + action;
    btn.href = '#';
    btn.setAttribute('title', title || '');
    btn.setAttribute('role', 'button');
    if (action_value) btn.setAttribute('data-action-value', action_value);
    btn.appendChild(fa_icon(icon_name, title));

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();

      if (action === 'reply') {
        // Navigate to compose in reply mode for this specific message.
        // Use the content frame if available so the reply opens inline.
        var url = rcmail.url('compose', {
          _reply_uid: uid,
          _mbox: rcmail.env.mailbox || 'INBOX'
        });
        if (rcmail.env.contentframe) {
          var win = rcmail.get_frame_window(rcmail.env.contentframe);
          if (win) { rcmail.location_href(url, win, true); return; }
        }
        rcmail.location_href(url);

      } else if (action === 'delete') {
        // Post delete directly so it applies to this uid only
        rcmail.http_post('delete', {
          _uid: uid,
          _mbox: rcmail.env.mailbox || 'INBOX',
          _from: 'show'
        });

      } else if (action === 'flag') {
        var target_flag = btn.getAttribute('data-action-value') || 'flagged';

        // Toggle flagged via http_post — rcmail.command('mark') needs list selection
        rcmail.http_post('mark', {
          _uid: uid,
          _flag: target_flag,
          _mbox: rcmail.env.mailbox || 'INBOX',
          _from: 'show'
        });

        var row = btn.closest('tr.conv-child-row');
        if (row) {
          if (target_flag === 'flagged') {
            row.classList.add('flagged');
          } else {
            row.classList.remove('flagged');
          }

          // Update persistent flag indicator in conv-status-icons
          var status_icons = row.querySelector('.conv-status-icons');
          if (status_icons) {
            var existing_flag_icon = status_icons.querySelector('.conv-status-flag-icon');
            if (target_flag === 'flagged' && !existing_flag_icon) {
              var new_flag_icon = fa_icon('flag', label('flagged'));
              new_flag_icon.classList.add('conv-status-flag-icon');
              status_icons.appendChild(new_flag_icon);
            } else if (target_flag === 'unflagged' && existing_flag_icon) {
              existing_flag_icon.parentNode.removeChild(existing_flag_icon);
            }
          }
        }

        // Update local message data model so re-renders stay in sync
        var parent_conv_id = row ? row.getAttribute('data-parent-conv') : null;
        var conv = parent_conv_id ? conv_state.conv_map[parent_conv_id] : null;
        if (conv && conv._messages) {
          for (var mi = 0; mi < conv._messages.length; mi++) {
            if (String(conv._messages[mi].uid) === String(uid)) {
              var m_flags = conv._messages[mi].flags || [];
              if (target_flag === 'flagged' && m_flags.indexOf('flagged') === -1) {
                m_flags.push('flagged');
              } else if (target_flag === 'unflagged') {
                var fi = m_flags.indexOf('flagged');
                if (fi !== -1) m_flags.splice(fi, 1);
              }
              conv._messages[mi].flags = m_flags;
              break;
            }
          }

          // Recalculate parent flagged_count and update parent row
          var new_flagged = 0;
          for (var mj = 0; mj < conv._messages.length; mj++) {
            var mf = conv._messages[mj].flags;
            if (mf && mf.indexOf('flagged') !== -1) new_flagged++;
          }
          conv.flagged_count = new_flagged;
          update_parent_action_icons(parent_conv_id);
        }

        var next_flag = target_flag === 'flagged' ? 'unflagged' : 'flagged';
        var next_icon = target_flag === 'flagged' ? 'flag' : 'flag-regular';
        var next_title = target_flag === 'flagged' ? label('markunflagged') : label('markflagged');
        btn.setAttribute('data-action-value', next_flag);
        btn.setAttribute('title', next_title);
        var icon = btn.querySelector('.conv-icon');
        if (icon) {
          icon.className = 'conv-icon conv-icon-' + next_icon;
        }
      }
    });

    return btn;
  }

  /**
   * Format participants list for display (max 3 names, then "+N").
   * Applies display_name || local-part logic — never shows raw email addresses.
   */
  function format_participants(participants) {
    if (!participants || !participants.length) return '';

    var names = participants.map(function (p) {
      return format_sender_name(p);
    });

    if (names.length <= 3) {
      return names.join(', ');
    }

    return names.slice(0, 3).join(', ') + ' +' + (names.length - 3);
  }

  /**
   * Extract a clean display name from a sender string.
   * Input forms: "John Doe <john@example.com>", "john@example.com", "John Doe"
   * Returns: display name if present, otherwise the local part before @.
   */
  function format_sender_name(sender) {
    if (!sender) return '?';

    function ucfirst(value) {
      if (!value) return value;
      return value.charAt(0).toUpperCase() + value.slice(1);
    }

    // Try to extract display name from "Name <email>" format
    var match = sender.match(/^\s*"?([^"<]+?)"?\s*<[^>]+>\s*$/);
    if (match && match[1] && match[1].trim()) {
      return ucfirst(match[1].trim());
    }

    // Strip any angle-bracket email, keep what's left
    var clean = sender.replace(/<[^>]+>/g, '').trim();
    // Only return clean if it's a plain name (no @) — a bare email still needs local-part extraction
    if (clean && clean.indexOf('@') === -1) return ucfirst(clean);

    // Bare email address — extract local part before @
    var at_pos = (clean || sender).indexOf('@');
    if (at_pos > 0) {
      return ucfirst((clean || sender).substring(0, at_pos).replace(/^["<\s]+/, ''));
    }

    return ucfirst(clean || sender);
  }

  /**
   * Clean a snippet by stripping quoted reply content.
   * Works on both multi-line raw text and pre-normalized single-line strings.
   */
  function clean_snippet(text) {
    if (!text) return '';

    // Multi-line: process line by line, stop at any quote boundary
    if (text.indexOf('\n') !== -1) {
      var lines = text.split('\n');
      var out = [];
      for (var i = 0; i < lines.length; i++) {
        var l = lines[i];
        if (/^\s*>/.test(l)) break;                           // quoted line
        if (/^On\s.{10,}wrote:\s*$/i.test(l.trim())) break;  // attribution line
        if (/^-{3,}\s*(Forwarded|Original)/i.test(l)) break;  // separator
        if (/^From:\s+\S+@\S+/i.test(l)) break;              // forwarded header
        if (/^_{3,}\s*$/.test(l)) break;                      // underscore separator
        out.push(l);
      }
      text = out.join(' ');
    }

    // Single-line / post-join: strip any inline quoted fragments
    // Handle " > quote" that wasn't at a line boundary
    text = text.replace(/\s>\s.*$/, '');
    // Handle " On ... wrote: " attribution baked into one line
    text = text.replace(/\bOn\s.{10,200}wrote:\s*.*$/i, '');
    // Handle forwarded markers
    text = text.replace(/\s-{3,}\s*(Forwarded|Original).*/i, '');

    // Collapse whitespace, trim, and cap at 140 chars
    return text.replace(/\s+/g, ' ').trim().slice(0, 140);
  }

  /**
   * Check if a sender matches one of the user's own identities.
   * Returns true if the email in `sender` matches any identity email.
   * Falls back to rcmail.env.sender (always set in list view) when
   * rcmail.env.identities is absent (compose-view-only).
   */
  function is_own_identity(sender) {
    if (!sender) return false;

    // Extract email from sender string (handles "Name <email>" and bare addresses)
    var email_match = sender.match(/<([^>]+)>/);
    var email = email_match ? email_match[1].toLowerCase() : sender.toLowerCase().trim();
    if (!email) return false;

    // Primary: rcmail.env.identities (populated in compose, sometimes in list view)
    var identities = rcmail.env && rcmail.env.identities;
    if (identities) {
      for (var key in identities) {
        if (identities.hasOwnProperty(key)) {
          var ident = identities[key];
          var ident_email = (typeof ident === 'string') ? ident :
            (ident.email || ident['email-value'] || ident.reply_to || '');
          if (ident_email && ident_email.toLowerCase() === email) {
            return true;
          }
        }
      }
    }

    // Fallback: rcmail.env.sender — the logged-in user's primary email,
    // reliably set in the mail list view.
    if (rcmail.env && rcmail.env.sender) {
      var sender_addr = rcmail.env.sender.replace(/^.*<([^>]+)>.*$/, '$1').toLowerCase().trim();
      if (sender_addr === email) return true;
      // Also try direct comparison in case env.sender is a bare address
      if (rcmail.env.sender.toLowerCase().trim() === email) return true;
    }

    return false;
  }

  // ──────────────────────────────────────────────
  //  Conversation Action Helpers
  // ──────────────────────────────────────────────

  /**
   * Collect all message UIDs from the given conversation IDs.
   * Falls back to latest_uid when _messages aren't cached.
   */
  function collect_uids_from_conversations(conv_ids) {
    var uids = [];
    for (var i = 0; i < conv_ids.length; i++) {
      var conv = conv_state.conv_map[conv_ids[i]];
      if (!conv) continue;
      if (conv._messages && conv._messages.length) {
        for (var j = 0; j < conv._messages.length; j++) {
          if (conv._messages[j].uid) uids.push(conv._messages[j].uid);
        }
      } else if (conv.latest_uid) {
        uids.push(conv.latest_uid);
      }
    }
    return uids;
  }

  /**
   * Select conversation rows in the list widget that satisfy matchFn.
   * Mirrors rcube_list_widget.select_all() but with a custom predicate.
   *
   * @param {Object}   widget  – rcube_list_widget instance
   * @param {Function} matchFn – receives conv_id string, returns boolean
   */
  function conv_select_by_filter(widget, matchFn) {
    if (!widget || !widget.rowcount) return;

    var select_before = widget.selection.join(',');
    widget.selection = [];

    for (var uid in widget.rows) {
      if (!widget.rows[uid]) continue;
      var conv_id = uid.replace(/^conv-/, '');
      if (matchFn(conv_id)) {
        widget.last_selected = uid;
        widget.highlight_row(uid, true, true); // multiple=true, norecur=true
      } else {
        $(widget.rows[uid].obj).removeClass('selected').removeAttr('aria-selected');
      }
    }

    if (widget.selection.join(',') !== select_before) {
      widget.triggerEvent('select');
    }
    if (widget.focus) widget.focus();
  }

  /**
   * Remove conversations from the local view after delete/archive/move.
   * Clears selection, removes rows, and refreshes the list if needed.
   */
  function remove_conversations_from_view(conv_ids) {
    for (var i = 0; i < conv_ids.length; i++) {
      var cid = conv_ids[i];
      // Remove child rows first
      remove_child_rows(cid);
      // Remove the parent row
      var row = document.getElementById('rcmrowconv-' + cid);
      if (row && row.parentNode) row.parentNode.removeChild(row);
      // Remove from the list widget
      if (conv_state.list_widget && conv_state.list_widget.remove_row) {
        conv_state.list_widget.remove_row('rcmrowconv-' + cid);
      }
      // Clean up state
      delete conv_state.conv_map[cid];
      delete conv_state.expanded[cid];
      // Remove from conversations array
      for (var j = conv_state.conversations.length - 1; j >= 0; j--) {
        if (conv_state.conversations[j].conversation_id === cid) {
          conv_state.conversations.splice(j, 1);
          break;
        }
      }
    }

    conv_state.total = Math.max(0, conv_state.total - conv_ids.length);
    emit_conv_selection_state();
    emit_conv_count_update();

    // If the current page is now empty, go back a page or refresh
    if (conv_state.conversations.length === 0 && conv_state.page > 1) {
      request_conversation_list(conv_state.page - 1);
    } else if (conv_state.conversations.length === 0) {
      // Show empty state
      if (dom.conv_empty) dom.conv_empty.style.display = '';
    }
  }

  /**
   * Emit a conversation count update event for the mass-action bar
   * to display the correct count (Bug 11).
   */
  function emit_conv_count_update() {
    if (typeof document === 'undefined' || typeof CustomEvent === 'undefined') return;
    document.dispatchEvent(new CustomEvent('stratus:conv-count-update', {
      detail: {
        total: conv_state.total,
        page: conv_state.page,
        pages: conv_state.pages
      }
    }));
  }

  /**
   * Apply the multiselect monkey-patch to the conversation list widget.
   * When multiselect mode is enabled, every mouse click behaves like Ctrl+click
   * to toggle individual rows (Bug 5).
   */
  function apply_conv_multiselect_patch() {
    var widget = conv_state.list_widget;
    if (!widget) return;

    // Only patch once — store the original on the widget
    if (!widget._orig_select_row_conv) {
      widget._orig_select_row_conv = widget.select_row;
    }

    widget.select_row = function(id, mod_key, with_mouse) {
      if (_conv_multiselect_mode && with_mouse && !mod_key) {
        mod_key = 1; // CONTROL_KEY — toggle individual row
      }
      return widget._orig_select_row_conv.call(this, id, mod_key, with_mouse);
    };
  }

  // Reference to the external multiselect mode flag (set by stratus:conv-set-multiselect event)
  var _conv_multiselect_mode = false;

  // Track individually selected child message UIDs (outside list widget)
  var _conv_selected_children = {}; // uid_string → true

  /**
   * Get UIDs of individually selected child rows.
   */
  function get_selected_child_uids() {
    var uids = [];
    for (var uid in _conv_selected_children) {
      if (_conv_selected_children[uid]) uids.push(uid);
    }
    return uids;
  }

  /**
   * Clear all child row selections (visual + state).
   */
  function clear_child_selections() {
    _conv_selected_children = {};
    if (dom.conv_table) {
      var selected = dom.conv_table.querySelectorAll('tr.conv-child-row.selected');
      for (var i = 0; i < selected.length; i++) {
        selected[i].classList.remove('selected');
        selected[i].removeAttribute('aria-selected');
      }
    }
  }

  /**
   * Toggle selection state of an individual child row.
   */
  function toggle_child_selection(tr, uid) {
    var uidStr = String(uid);
    if (_conv_selected_children[uidStr]) {
      delete _conv_selected_children[uidStr];
      tr.classList.remove('selected');
      tr.removeAttribute('aria-selected');
    } else {
      _conv_selected_children[uidStr] = true;
      tr.classList.add('selected');
      tr.setAttribute('aria-selected', 'true');
    }
    emit_conv_selection_state();
  }

  /**
   * Select all visible child rows in the conversation list.
   */
  function select_all_visible_children() {
    if (!dom.conv_table) return;
    var children = dom.conv_table.querySelectorAll('tr.conv-child-row');
    for (var i = 0; i < children.length; i++) {
      var uid = children[i].getAttribute('data-uid');
      if (uid) {
        _conv_selected_children[String(uid)] = true;
        children[i].classList.add('selected');
        children[i].setAttribute('aria-selected', 'true');
      }
    }
  }

  /**
   * Select child rows matching a filter function.
   * @param {Function} matchFn - receives (child_tr) → boolean
   */
  function select_children_by_filter(matchFn) {
    if (!dom.conv_table) return;
    _conv_selected_children = {};
    var children = dom.conv_table.querySelectorAll('tr.conv-child-row');
    for (var i = 0; i < children.length; i++) {
      var uid = children[i].getAttribute('data-uid');
      if (!uid) continue;
      if (matchFn(children[i])) {
        _conv_selected_children[String(uid)] = true;
        children[i].classList.add('selected');
        children[i].setAttribute('aria-selected', 'true');
      } else {
        children[i].classList.remove('selected');
        children[i].removeAttribute('aria-selected');
      }
    }
  }

  /**
   * Collect ALL selected UIDs — from selected parent conversations
   * plus individually selected child message rows, deduplicated.
   */
  function get_all_selected_uids() {
    var uid_set = {};
    // 1. All UIDs from selected parent conversations
    var sel_convs = get_selected_conv_ids();
    for (var i = 0; i < sel_convs.length; i++) {
      var conv = conv_state.conv_map[sel_convs[i]];
      if (!conv) continue;
      if (conv._messages && conv._messages.length) {
        for (var j = 0; j < conv._messages.length; j++) {
          if (conv._messages[j].uid) uid_set[String(conv._messages[j].uid)] = true;
        }
      } else if (conv.latest_uid) {
        uid_set[String(conv.latest_uid)] = true;
      }
    }
    // 2. Individually selected child UIDs
    for (var uid in _conv_selected_children) {
      if (_conv_selected_children[uid]) uid_set[uid] = true;
    }
    // 3. Return deduplicated array
    var result = [];
    for (var k in uid_set) {
      if (uid_set[k]) result.push(k);
    }
    return result;
  }

  // ──────────────────────────────────────────────
  //  DOM Helpers
  // ──────────────────────────────────────────────

  /**
   * Destroy the rcube_list_widget without removing the table element.
   */
  function destroy_conv_widget() {
    if (conv_state.list_widget) {
      if (conv_state.list_widget.destroy) {
        conv_state.list_widget.destroy();
      }
      conv_state.list_widget = null;
    }
  }

  /**
   * Restore the standard message list (exit conversation mode visually).
   */
  function restore_standard_list() {
    destroy_conv_widget();
    close_conversation_detail();

    // Clear the conversation table
    if (dom.conv_table) {
      var tbody = dom.conv_table.querySelector('tbody');
      if (tbody) tbody.innerHTML = '';
    }
    if (dom.conv_empty) dom.conv_empty.style.display = 'none';
    if (dom.conv_pagination) dom.conv_pagination.style.display = 'none';

    // Let Roundcube reload the standard message list
    if (rcmail.command) {
      rcmail.command('list', rcmail.env.mailbox);
    }

    emit_conv_selection_state();
  }

  function open_conversation(conv_id) {
    // Select the row in the widget (suppress the select event to avoid
    // a redundant AJAX request being fired by on_conv_select)
    if (conv_state.list_widget) {
      var row_id = 'rcmrowconv-' + conv_id;
      _conv_opening = true;
      conv_state.list_widget.select && conv_state.list_widget.select(row_id);
      _conv_opening = false;
    }
    request_open_conversation(conv_id);
  }

  function get_selected_conv_ids() {
    if (!conv_state.list_widget) return [];
    var sel = conv_state.list_widget.get_selection();
    return sel.map(function (id) {
      // id = "conv-<conv_id>" (from tr.uid)
      return id.replace(/^conv-/, '');
    });
  }

  function get_conversation_selection_state() {
    var sel = get_selected_conv_ids();
    var any_unread = false;
    var any_unflagged = false;
    var total_count = sel.length;

    // Build set of UIDs covered by parent selections
    var parent_covered = {};
    for (var i = 0; i < sel.length; i++) {
      var conv = conv_state.conv_map[sel[i]];
      if (!conv) continue;
      if ((conv.unread_count || 0) > 0) any_unread = true;
      if ((conv.flagged_count || 0) === 0) any_unflagged = true;
      if (conv._messages) {
        for (var j = 0; j < conv._messages.length; j++) {
          parent_covered[String(conv._messages[j].uid)] = true;
        }
      }
      if (conv.latest_uid) parent_covered[String(conv.latest_uid)] = true;
    }

    // Count individually selected children not covered by parents
    for (var uid in _conv_selected_children) {
      if (_conv_selected_children[uid] && !parent_covered[uid]) {
        total_count++;
        if (dom.conv_table) {
          var row = dom.conv_table.querySelector('tr.conv-child-row[data-uid="' + uid + '"]');
          if (row) {
            if (row.classList.contains('unread')) any_unread = true;
            if (!row.classList.contains('flagged')) any_unflagged = true;
          }
        }
      }
    }

    return {
      count: total_count,
      anyUnread: any_unread,
      anyUnflagged: any_unflagged
    };
  }

  function emit_conv_selection_state() {
    if (typeof document === 'undefined' || typeof CustomEvent === 'undefined') return;
    document.dispatchEvent(new CustomEvent('stratus:conv-selection-state', {
      detail: get_conversation_selection_state()
    }));
  }

  if (typeof document !== 'undefined') {
    document.addEventListener('stratus:conv-request-selection-state', function() {
      emit_conv_selection_state();
    });

    document.addEventListener('stratus:conv-clear-selection', function() {
      if (!conv_state.list_widget || conv_state.mode !== 'conversations') return;
      conv_state.list_widget.clear_selection && conv_state.list_widget.clear_selection();
      clear_child_selections();
      emit_conv_selection_state();
    });

    document.addEventListener('stratus:conv-massaction', function(e) {
      if (conv_state.mode !== 'conversations') return;
      var detail = e && e.detail ? e.detail : {};

      if (detail.action === 'mark-toggle') {
        apply_read_state_to_selected_conversations(detail.value === 'unread' ? 'unread' : 'read');
      } else if (detail.action === 'flag-toggle') {
        apply_flag_to_selected_conversations(detail.value === 'unflagged' ? 'unflagged' : 'flagged');
      } else if (detail.action === 'delete') {
        cmd_delete();
      } else if (detail.action === 'archive') {
        cmd_archive();
      } else if (detail.action === 'move') {
        // Move requires a target mailbox — dispatched with detail.target
        var target = detail.target;
        if (!target) return;
        var sel = get_selected_conv_ids();
        if (!sel.length) return;
        var uids = collect_uids_from_conversations(sel);
        if (!uids.length) return;
        rcmail.http_post('move', {
          _uid: uids.join(','),
          _target_mbox: target,
          _mbox: rcmail.env.mailbox || 'INBOX',
          _from: 'list'
        });
        remove_conversations_from_view(sel);
      }
    });

    // Bug 5: Listen for multiselect mode toggle from the mass-action bar
    document.addEventListener('stratus:conv-set-multiselect', function(e) {
      var detail = e && e.detail ? e.detail : {};
      _conv_multiselect_mode = !!detail.enabled;
      // Monkey-patch the current conv list widget's select_row if active
      apply_conv_multiselect_patch();
    });

    // Listen for bulk selection actions from the mass-action bar's select popup.
    // Handles: all, unread, flagged, invert — for both parents AND visible children.
    document.addEventListener('stratus:conv-select-action', function(e) {
      if (conv_state.mode !== 'conversations') return;
      var detail = e && e.detail ? e.detail : {};
      var type = detail.type;
      var widget = conv_state.list_widget;
      if (!widget) return;

      if (type === 'all') {
        widget.select_all();
        select_all_visible_children();
      } else if (type === 'unread') {
        conv_select_by_filter(widget, function(conv_id) {
          var conv = conv_state.conv_map[conv_id];
          return conv && conv.unread_count > 0;
        });
        select_children_by_filter(function(tr) {
          return tr.classList.contains('unread');
        });
      } else if (type === 'flagged') {
        conv_select_by_filter(widget, function(conv_id) {
          var conv = conv_state.conv_map[conv_id];
          return conv && conv.flagged_count > 0;
        });
        select_children_by_filter(function(tr) {
          return tr.classList.contains('flagged');
        });
      } else if (type === 'invert') {
        widget.invert_selection();
        // Invert child selections too
        if (dom.conv_table) {
          var children = dom.conv_table.querySelectorAll('tr.conv-child-row');
          for (var ci = 0; ci < children.length; ci++) {
            var cuid = children[ci].getAttribute('data-uid');
            if (!cuid) continue;
            if (_conv_selected_children[String(cuid)]) {
              delete _conv_selected_children[String(cuid)];
              children[ci].classList.remove('selected');
              children[ci].removeAttribute('aria-selected');
            } else {
              _conv_selected_children[String(cuid)] = true;
              children[ci].classList.add('selected');
              children[ci].setAttribute('aria-selected', 'true');
            }
          }
        }
      }
      emit_conv_selection_state();
    });

    // Bug 8: Listen for page navigation from the mass-action bar prev/next buttons
    document.addEventListener('stratus:conv-page', function(e) {
      if (conv_state.mode !== 'conversations') return;
      var detail = e && e.detail ? e.detail : {};
      if (detail.direction === 'prev' && conv_state.page > 1) {
        request_conversation_list(conv_state.page - 1);
      } else if (detail.direction === 'next' && conv_state.page < conv_state.pages) {
        request_conversation_list(conv_state.page + 1);
      }
    });
  }

  function update_toggle_button() {
    var btns = document.querySelectorAll('.conv-toggle');
    for (var i = 0; i < btns.length; i++) {
      if (conv_state.mode === 'conversations') {
        btns[i].classList.add('active', 'conv-active');
        btns[i].setAttribute('aria-pressed', 'true');
      } else {
        btns[i].classList.remove('active', 'conv-active');
        btns[i].setAttribute('aria-pressed', 'false');
      }
    }
  }

  function toggle_class(el, cls, add) {
    if (add) {
      el.classList.add(cls);
    } else {
      el.classList.remove(cls);
    }
  }

  function show_loading(show) {
    if (show) {
      if (rcmail.set_busy) {
        _busy_lock = rcmail.set_busy(true, 'loading');
      }
    } else {
      if (rcmail.set_busy) {
        rcmail.set_busy(false, null, _busy_lock);
      }
      _busy_lock = null;
    }
  }

  // ──────────────────────────────────────────────
  //  Localization helper
  // ──────────────────────────────────────────────

  function label(key) {
    if (rcmail.gettext) {
      // Try plugin-prefixed first, then bare key
      var txt = rcmail.gettext('conversation_mode.' + key);
      if (txt && txt !== 'conversation_mode.' + key) return txt;
      txt = rcmail.gettext(key);
      if (txt && txt !== key) return txt;
    }
    // Fallback map for critical strings
    var fallback = {
      no_conversations: 'No conversations in this folder.',
      back_to_list: 'Back to conversations',
      open_message: 'Open',
      plugin_name: 'Conversations',
      nosubject: '(no subject)',
      archive: 'Archive',
      'delete': 'Delete',
      flagged: 'Flagged',
      withattachment: 'Has attachment',
      previouspage: 'Prev',
      nextpage: 'Next',
      page_of: 'Page $current of $total',
      unread_count: '$n unread',
      markread: 'Mark as read',
      markunread: 'Mark as unread',
      reply: 'Reply',
      sent: 'Sent',
      received: 'Received',
      expand_conversation: 'Expand',
      collapse_conversation: 'Collapse'
    };
    return fallback[key] || key;
  }

  /**
   * Update #conv-empty text to be contextual: search message vs folder-empty.
   * Called after render_conversation_list() so the element already exists.
   */
  function update_conv_empty_label() {
    if (!dom.conv_empty) return;
    var isSearch = !!(rcmail.env.search_request || rcmail.env.qsearch);
    var textEl = dom.conv_empty.querySelector('.conv-empty-text');
    if (!textEl) return;
    if (isSearch) {
      var txt = rcmail.gettext('stratus_helper.search_no_conversations');
      if (!txt || txt === 'stratus_helper.search_no_conversations') {
        txt = 'No conversations found for your search.';
      }
      textEl.textContent = txt;
    } else {
      textEl.textContent = label('no_conversations');
    }
  }
  // ──────────────────────────────────────────────

  function format_date(timestamp) {
    if (!timestamp) return '';

    var d = new Date(timestamp * 1000);
    var now = new Date();
    var today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    var msg_day = new Date(d.getFullYear(), d.getMonth(), d.getDate());

    if (msg_day.getTime() === today.getTime()) {
      return pad(d.getHours()) + ':' + pad(d.getMinutes());
    }

    var yesterday = new Date(today);
    yesterday.setDate(yesterday.getDate() - 1);
    if (msg_day.getTime() === yesterday.getTime()) {
      return 'Yesterday';
    }

    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
      'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    if (d.getFullYear() === now.getFullYear()) {
      return months[d.getMonth()] + ' ' + d.getDate();
    }

    // Older than current year — friendly format: "Mar 3, 2025"
    return months[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
  }

  function pad(n) {
    return n < 10 ? '0' + n : '' + n;
  }

  // ──────────────────────────────────────────────
  //  Inline Expand / Collapse (Outlook-style)
  // ──────────────────────────────────────────────

  /**
   * Toggle inline expansion of a conversation's child messages.
   */
  function toggle_expand(conv_id) {
    // The expand arrow uses stopPropagation, so the list widget's row click
    // never fires and on_conv_select is skipped.  Manually clear any selected
    // child rows from OTHER conversations so only one item is highlighted.
    if (dom.conv_table) {
      var stale = dom.conv_table.querySelectorAll(
        'tr.conv-child-row.selected:not([data-parent-conv="' + conv_id + '"])'
      );
      for (var i = 0; i < stale.length; i++) {
        stale[i].classList.remove('selected');
      }
    }

    if (conv_state.expanded[conv_id]) {
      collapse_conversation(conv_id);
    } else {
      expand_conversation(conv_id);
    }
  }

  /**
   * Expand a conversation: show child message rows inline below the parent.
   * If messages are cached, renders immediately; otherwise fetches via AJAX.
   */
  function expand_conversation(conv_id) {
    var conv = conv_state.conv_map[conv_id];
    if (!conv || conv.message_count <= 1) return;
    if (conv_state.expanded[conv_id]) return;

    conv_state.expanded[conv_id] = true;
    update_expand_arrow(conv_id, true);

    if (conv._messages) {
      insert_child_rows(conv_id, conv._messages);
    } else {
      // Lazy-load via AJAX; on_render_open handles insertion
      request_open_conversation(conv_id);
    }
  }

  /**
   * Collapse a conversation: remove child rows and reset arrow.
   */
  function collapse_conversation(conv_id) {
    // Check if a child of THIS conversation was the active selection.
    // Must query BEFORE remove_child_rows() destroys the DOM elements.
    var had_selected_child = dom.conv_table &&
      !!dom.conv_table.querySelector(
        'tr.conv-child-row.selected[data-parent-conv="' + conv_id + '"]'
      );

    conv_state.expanded[conv_id] = false;
    remove_child_rows(conv_id);
    update_expand_arrow(conv_id, false);

    // Only re-select the parent if a child of this conversation was selected.
    // Otherwise leave the current selection (on a different row) untouched.
    if (had_selected_child && conv_state.list_widget && conv_state.list_widget.select) {
      var row_id = 'rcmrowconv-' + conv_id;
      _conv_opening = true;
      conv_state.list_widget.select(row_id);
      _conv_opening = false;
    }
  }

  /**
   * Insert child message rows into the table below the parent row.
   * messages[0] = latest (shown as parent), messages[1..n] = children.
   */
  function insert_child_rows(conv_id, messages) {
    var parent_row = document.getElementById('rcmrowconv-' + conv_id);
    if (!parent_row || !parent_row.parentNode) return;

    remove_child_rows(conv_id);

    var tbody = parent_row.parentNode;
    var ref = parent_row.nextSibling;

    // Skip messages[0] — it's the latest, already shown as the parent row
    for (var i = 1; i < messages.length; i++) {
      var child = build_child_row(messages[i], conv_id);
      tbody.insertBefore(child, ref);
    }

    // Add a subtle visual separator after the final inline child message
    // to clearly delimit the expanded conversation block.
    if (messages.length > 1) {
      var divider = build_child_divider_row(conv_id);
      tbody.insertBefore(divider, ref);
    }
  }

  /**
   * Remove all child rows for a conversation.
   */
  function remove_child_rows(conv_id) {
    if (!dom.conv_table) return;
    var rows = dom.conv_table.querySelectorAll(
      'tr.conv-child-row[data-parent-conv="' + conv_id + '"]'
      + ', tr.conv-child-divider-row[data-parent-conv="' + conv_id + '"]'
    );
    for (var i = 0; i < rows.length; i++) {
      rows[i].parentNode.removeChild(rows[i]);
    }
  }

  /**
   * Build a full-width divider row displayed after the last expanded child row.
   */
  function build_child_divider_row(conv_id) {
    var tr = document.createElement('tr');
    tr.className = 'conv-child-divider-row';
    tr.setAttribute('data-parent-conv', conv_id);

    var td = document.createElement('td');
    td.className = 'conv-child-divider-cell';
    td.colSpan = 4;

    var line = document.createElement('span');
    line.className = 'conv-child-divider-line';

    td.appendChild(line);
    tr.appendChild(td);
    return tr;
  }

  /**
   * Build a child message <tr> for inline display within the conversation.
   * Layout: [indent] [sender+direction · date / snippet+icons] [date+hover actions]
   */
  function build_child_row(msg, conv_id) {
    var is_unread = !msg.flags || msg.flags.indexOf('seen') === -1;
    var is_flagged = msg.flags && msg.flags.indexOf('flagged') !== -1;
    var is_sent = is_own_identity(msg.from);

    var tr = document.createElement('tr');
    tr.className = 'conv-child-row'
      + (is_unread ? ' unread' : '')
      + (is_flagged ? ' flagged' : '');
    tr.setAttribute('data-parent-conv', conv_id);
    tr.setAttribute('data-uid', msg.uid);

    // Click: in multiselect mode, toggle selection; otherwise preview
    (function (uid, row) {
      row.addEventListener('click', function (e) {
        e.stopPropagation();
        if (_conv_multiselect_mode) {
          toggle_child_selection(row, uid);
        } else {
          load_message_preview(uid);
          highlight_child_row(row);
        }
      });
    })(msg.uid, tr);

    // ─── Column 1: Empty expand cell (alignment / thread connector) ───
    var td_expand = document.createElement('td');
    td_expand.className = 'conv-expand-cell conv-child-indent';
    tr.appendChild(td_expand);

    // ─── Column 2: Avatar slot (kept empty to preserve row alignment) ───
    var td_avatar = document.createElement('td');
    td_avatar.className = 'conv-avatar';
    tr.appendChild(td_avatar);

    // ─── Column 3: Message info (2 lines: sender+date, snippet) ───
    var td_subject = document.createElement('td');
    td_subject.className = 'subject conv-child-subject';

    // Line 1: direction arrow + sender + date
    var line1 = document.createElement('span');
    line1.className = 'conv-line1';

    // Direction indicator (→ sent, ← received)
    var dir_el = document.createElement('span');
    dir_el.className = 'conv-direction-indicator ' + (is_sent ? 'conv-dir-sent' : 'conv-dir-received');
    dir_el.setAttribute('aria-label', is_sent ? label('sent') : label('received'));
    dir_el.setAttribute('title', is_sent ? label('sent') : label('received'));
    line1.appendChild(dir_el);

    var from_el = document.createElement('span');
    from_el.className = 'conv-sender';
    from_el.textContent = format_sender_name(msg.from);
    line1.appendChild(from_el);

    // Inline status icons (attachment, flag)
    var icons_el = document.createElement('span');
    icons_el.className = 'conv-status-icons';
    if (msg.has_attachment) {
      icons_el.appendChild(fa_icon('paperclip', label('withattachment')));
    }
    line1.appendChild(icons_el);

    // Date in line1 — right-aligned via flex
    var child_date_el = document.createElement('span');
    child_date_el.className = 'conv-date skip-on-drag';
    child_date_el.textContent = format_date(msg.timestamp);
    line1.appendChild(child_date_el);

    td_subject.appendChild(line1);

    // Line 2: snippet preview (replaces redundant subject)
    var child_snippet = clean_snippet(msg.snippet || msg.body_preview || '');
    var line2 = document.createElement('span');
    line2.className = 'conv-line3'; // reuse line3 class for snippet styling

    if (is_unread) {
      var dot = document.createElement('span');
      dot.className = 'conv-unread-dot';
      line2.appendChild(dot);
    }

    var snippet_el = document.createElement('span');
    snippet_el.className = 'conv-snippet-text';
    snippet_el.textContent = child_snippet || msg.subject || label('nosubject');
    line2.appendChild(snippet_el);

    td_subject.appendChild(line2);
    tr.appendChild(td_subject);

    // ─── Column 4: Flag indicator + hover action bar ───
    // Narrow cell (~2.5em) — date is now in td.subject .conv-line1.
    var td_flags = document.createElement('td');
    td_flags.className = 'flags conv-flags-cell';

    // Persistent flag indicator aligned with parent rows
    var flag_ind = document.createElement('span');
    flag_ind.className = 'conv-flag-indicator conv-icon conv-icon-flag';
    flag_ind.setAttribute('aria-label', label('flagged'));
    td_flags.appendChild(flag_ind);

    // Hover action bar — actions apply to THIS message, not the whole conversation
    var actions = document.createElement('span');
    actions.className = 'conv-hover-actions';

    actions.appendChild(child_action_btn('reply', 'reply', label('reply'), msg.uid));
    actions.appendChild(child_action_btn('delete', 'trash-alt', label('delete'), msg.uid));
    actions.appendChild(child_action_btn('flag', is_flagged ? 'flag' : 'flag-regular', is_flagged ? label('markunflagged') : label('markflagged'), msg.uid, is_flagged ? 'unflagged' : 'flagged'));

    td_flags.appendChild(actions);
    tr.appendChild(td_flags);

    return tr;
  }

  /**
   * Update the expand arrow visual state.
   */
  function update_expand_arrow(conv_id, expanded) {
    var parent_row = document.getElementById('rcmrowconv-' + conv_id);
    if (!parent_row) return;

    var arrow = parent_row.querySelector('.conv-expand-arrow');
    if (arrow) {
      if (expanded) {
        arrow.classList.add('expanded');
        arrow.setAttribute('aria-label', label('collapse_conversation'));
      } else {
        arrow.classList.remove('expanded');
        arrow.setAttribute('aria-label', label('expand_conversation'));
      }
    }

    if (expanded) {
      parent_row.classList.add('conv-expanded');
    } else {
      parent_row.classList.remove('conv-expanded');
    }
  }

  /**
   * Highlight a clicked child row for preview (single-select mode only).
   * Clears previous selections and deselects parents.
   */
  function highlight_child_row(tr) {
    if (!dom.conv_table) return;

    // Clear all child highlights and selection state
    clear_child_selections();

    // Deselect parent rows: clear the list widget's selection state (and its
    // visual "selected" class) while suppressing the on_conv_select callback.
    if (conv_state.list_widget && conv_state.list_widget.clear_selection) {
      _conv_opening = true;
      conv_state.list_widget.clear_selection();
      _conv_opening = false;
    }

    // Strip both .selected and .focused from ALL parent (conv-row) rows.
    // clear_selection() may leave or reassign .focused to another row,
    // causing a visible background highlight on an unrelated conversation.
    var parent_rows = dom.conv_table.querySelectorAll('tr.conv-row.selected, tr.conv-row.focused');
    for (var j = 0; j < parent_rows.length; j++) {
      parent_rows[j].classList.remove('selected', 'focused');
    }

    tr.classList.add('selected');
  }

  /**
   * Load a message into the standard Roundcube preview iframe.
   */
  function load_message_preview(uid) {
    if (!uid) return;

    if (rcmail.env.contentframe) {
      // get_frame_window returns the Window object; location_href needs a Window
      var win = rcmail.get_frame_window(rcmail.env.contentframe);
      if (win) {
        // Must use the 'preview' action and _framed=1 so Roundcube renders only
        // the message body inside the content iframe, not the full page layout.
        var params = rcmail.params_from_uid
          ? rcmail.params_from_uid(uid, { _framed: 1 })
          : { _uid: uid, _mbox: rcmail.env.mailbox, _framed: 1 };
        var url = rcmail.url('preview', params);
        rcmail.location_href(url, win, true);
        return;
      }
    }
    // Fallback: full-page navigation when there is no content frame
    rcmail.location_href(rcmail.url('show', { _uid: uid, _mbox: rcmail.env.mailbox }));
  }

})();
