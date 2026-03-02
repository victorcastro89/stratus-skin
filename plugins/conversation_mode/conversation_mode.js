/**
 * Conversation Mode – Client-side logic (v3: template-binding architecture)
 *
 * Binds to pre-existing containers defined in the mail.html template
 * override (plugins/conversation_mode/skins/elastic/templates/mail.html).
 * The template provides two parallel panels inside #layout-list:
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
    open_conv_id: null,     // currently expanded conversation
    conversations: [],      // current page of conversation rows
    conv_map: {},           // conv_id → conversation data (for quick lookup)
    loading: false,
    list_widget: null       // rcube_list_widget instance for conversation list
  };

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
      ? dom.layout_content.querySelector('.conv-standard-preview') : null;
  }

  // ──────────────────────────────────────────────
  //  Initialization
  // ──────────────────────────────────────────────

  rcmail.addEventListener('init', function () {
    resolve_dom();

    conv_state.mode = rcmail.env.conversation_mode || 'list';

    // Register commands
    rcmail.register_command('plugin.conv.toggle', cmd_toggle, true);
    rcmail.register_command('plugin.conv.archive', cmd_archive, false);
    rcmail.register_command('plugin.conv.delete', cmd_delete, false);
    rcmail.register_command('plugin.conv.flag', cmd_flag, false);

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
    rcmail.addEventListener('selectfolder', function () {
      if (conv_state.mode === 'conversations') {
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
  });

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
    if (!sel.length) return;
    // Placeholder: will be wired in Phase 1.5 §3
    rcmail.display_message('Archive not yet implemented', 'notice');
  }

  function cmd_delete() {
    var sel = get_selected_conv_ids();
    if (!sel.length) return;
    rcmail.display_message('Delete not yet implemented', 'notice');
  }

  function cmd_flag() {
    var sel = get_selected_conv_ids();
    if (!sel.length) return;
    rcmail.display_message('Flag not yet implemented', 'notice');
  }

  // ──────────────────────────────────────────────
  //  AJAX Requests
  // ──────────────────────────────────────────────

  function request_conversation_list(page) {
    if (conv_state.loading) return;
    conv_state.loading = true;
    show_loading(true);

    rcmail.http_request('plugin.conv.list', {
      _mbox: rcmail.env.mailbox || 'INBOX',
      _page: page || 1
    });
  }

  function request_open_conversation(conv_id) {
    if (conv_state.loading) return;
    conv_state.loading = true;
    show_loading(true);

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

    if (!data || !data.conversations) return;

    conv_state.conversations = data.conversations;
    conv_state.total = data.total || 0;
    conv_state.page = data.page || 1;
    conv_state.pages = data.pages || 1;
    conv_state.open_conv_id = null;
    conv_state.conv_map = {};

    for (var i = 0; i < data.conversations.length; i++) {
      var c = data.conversations[i];
      conv_state.conv_map[c.conversation_id] = c;
    }

    render_conversation_list(data.conversations);
    render_pagination();
    hide_detail_panel();
  }

  function on_render_open(data) {
    conv_state.loading = false;
    show_loading(false);

    if (!data || !data.messages) return;

    conv_state.open_conv_id = data.conversation_id;
    render_conversation_detail(data);
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
      restore_standard_list();
    }
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

    // Show/hide empty state
    if (dom.conv_empty) {
      dom.conv_empty.style.display = conversations.length ? 'none' : '';
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

    var row_class = 'message conv-row'
      + (is_unread ? ' unread' : '')
      + (is_flagged ? ' flagged' : '');

    var tr = document.createElement('tr');
    tr.id = 'rcmrowconv-' + conv.conversation_id;
    tr.uid = 'conv-' + conv.conversation_id;
    tr.className = row_class;
    tr.setAttribute('data-conv-id', conv.conversation_id);

    // ─── Column 1: Avatar ───
    var td_avatar = document.createElement('td');
    td_avatar.className = 'conv-avatar';
    var sender_name = (conv.participants && conv.participants.length)
      ? conv.participants[0] : '?';
    td_avatar.appendChild(build_avatar(sender_name));
    tr.appendChild(td_avatar);

    // ─── Column 2: Subject (3-line flex cell) ───
    var td_subject = document.createElement('td');
    td_subject.className = 'subject';

    // Line 1: sender(s) + count + date
    var line1 = document.createElement('span');
    line1.className = 'conv-line1 skip-on-drag';

    var sender_el = document.createElement('span');
    sender_el.className = 'conv-sender';
    sender_el.textContent = format_participants(conv.participants || []);
    line1.appendChild(sender_el);

    if (conv.message_count > 1) {
      var count_el = document.createElement('span');
      count_el.className = 'conv-count';
      count_el.textContent = conv.message_count;
      line1.appendChild(count_el);
    }

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

    // Inline status icons
    var icons = document.createElement('span');
    icons.className = 'conv-status-icons skip-on-drag';

    if (conv.has_attachments) {
      icons.appendChild(fa_icon('paperclip', label('withattachment')));
    }
    if (is_flagged) {
      icons.appendChild(fa_icon('flag', label('flagged')));
    }

    line2.appendChild(icons);
    td_subject.appendChild(line2);

    // Line 3: snippet
    if (conv.snippet) {
      var line3 = document.createElement('span');
      line3.className = 'conv-line3 skip-on-drag';
      line3.textContent = conv.snippet;
      td_subject.appendChild(line3);
    }

    tr.appendChild(td_subject);

    // ─── Column 3: Hover action bar + flags ───
    var td_flags = document.createElement('td');
    td_flags.className = 'flags conv-flags-cell';

    // Hover action bar (visible on hover only)
    var actions = document.createElement('span');
    actions.className = 'conv-hover-actions';

    actions.appendChild(action_btn('archive', 'archive', label('archive')));
    actions.appendChild(action_btn('delete', 'trash-alt', label('delete')));
    actions.appendChild(action_btn('flag', is_flagged ? 'flag' : 'flag', label('flagged')));

    td_flags.appendChild(actions);
    tr.appendChild(td_flags);

    return tr;
  }

  // ──────────────────────────────────────────────
  //  List Widget Event Handlers
  // ──────────────────────────────────────────────

  function on_conv_select(o) {
    var sel = get_selected_conv_ids();
    var has_sel = sel.length > 0;

    // Enable/disable action commands
    rcmail.enable_command('plugin.conv.archive', has_sel);
    rcmail.enable_command('plugin.conv.delete', has_sel);
    rcmail.enable_command('plugin.conv.flag', has_sel);
  }

  function on_conv_dblclick(o) {
    var tr = document.getElementById(o.id);
    if (!tr) return;
    var conv_id = tr.getAttribute('data-conv-id');
    if (conv_id) {
      open_conversation(conv_id);
    }
  }

  function on_conv_keypress(o) {
    if (o.key_pressed === 13) { // Enter
      var sel = conv_state.list_widget.get_selection();
      if (sel.length === 1) {
        var tr = document.getElementById(sel[0]);
        if (tr) {
          var conv_id = tr.getAttribute('data-conv-id');
          if (conv_id) open_conversation(conv_id);
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
   */
  function show_detail_panel() {
    if (dom.conv_detail) dom.conv_detail.style.display = '';
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
    open_link.innerHTML = '<i class="fa fa-external-link-alt"></i> ' + label('open_message');
    open_link.addEventListener('click', function (e) {
      e.preventDefault();
      if (rcmail.show_message) {
        rcmail.show_message(msg.uid);
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
    var span = document.createElement('i');
    span.className = 'fa fa-' + icon_name;
    if (title) span.setAttribute('title', title);
    span.setAttribute('aria-hidden', 'true');
    return span;
  }

  /**
   * Create a hover-action button.
   */
  function action_btn(action, icon_name, title) {
    var btn = document.createElement('a');
    btn.className = 'conv-action-btn conv-action-' + action;
    btn.href = '#';
    btn.setAttribute('title', title || '');
    btn.setAttribute('role', 'button');
    btn.appendChild(fa_icon(icon_name, title));

    btn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation(); // Don't trigger row select
      if (action === 'archive') cmd_archive();
      else if (action === 'delete') cmd_delete();
      else if (action === 'flag') cmd_flag();
    });

    return btn;
  }

  /**
   * Format participants list for display (max 3 names, then "+N").
   */
  function format_participants(participants) {
    if (!participants || !participants.length) return '';

    // Strip email addresses, keep display names
    var names = participants.map(function (p) {
      return p.replace(/<[^>]+>/g, '').trim() || p;
    });

    if (names.length <= 3) {
      return names.join(', ');
    }

    return names.slice(0, 3).join(', ') + ' +' + (names.length - 3);
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
  }

  function open_conversation(conv_id) {
    // Select the row in the widget
    if (conv_state.list_widget) {
      var row_id = 'rcmrowconv-' + conv_id;
      conv_state.list_widget.select && conv_state.list_widget.select(row_id);
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
      rcmail.set_busy && rcmail.set_busy(true, 'loading');
    } else {
      rcmail.set_busy && rcmail.set_busy(false);
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
      unread_count: '$n unread'
    };
    return fallback[key] || key;
  }

  // ──────────────────────────────────────────────
  //  Utility
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

    if (d.getFullYear() === now.getFullYear()) {
      var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
        'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
      return months[d.getMonth()] + ' ' + d.getDate();
    }

    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
  }

  function pad(n) {
    return n < 10 ? '0' + n : '' + n;
  }

})();
