<?php

/**
 * Conversation Mode
 *
 * Groups related messages into conversations and displays the latest
 * activity first.  Works with any Roundcube skin.
 *
 * @version 0.1.0
 * @license GNU GPLv3+
 * @author  Stratus Team
 */
class conversation_mode extends rcube_plugin
{
    /**
     * Tasks this plugin is active in.
     */
    public $task = 'mail|settings';

    /**
     * @var rcmail
     */
    private $rcmail;

    /**
     * @var conversation_mode_service
     */
    private $service;

    // ──────────────────────────────────────────────
    //  Initialization
    // ──────────────────────────────────────────────

    public function init()
    {
        $this->rcmail = rcmail::get_instance();

        $skin = (string) $this->rcmail->config->get('skin', 'elastic');
        if (!$this->is_supported_skin($skin)) {
            rcmail::write_log('errors', sprintf(
                'conversation_mode: plugin disabled on unsupported skin "%s" (supported: stratus, elastic)',
                $skin
            ));
            return;
        }

        $this->load_config('config.inc.php.dist');  // defaults
        $this->load_config();                        // user overrides (if file exists)
        $this->add_texts('localization/', true);

        $this->require_files();

        // Ensure threading headers are fetched from IMAP so the grouper
        // can link messages into conversations (MESSAGE-ID, IN-REPLY-TO,
        // REFERENCES are not in Roundcube's default fetch set).
        $this->add_hook('storage_init', [$this, 'hook_storage_init']);

        if ($this->rcmail->task === 'mail') {
            $this->init_mail();
        } elseif ($this->rcmail->task === 'settings') {
            $this->init_settings();
        }
    }

    /**
     * Load helper classes.
     */
    private function require_files()
    {
        require_once __DIR__ . '/lib/conversation_mode_service.php';
        require_once __DIR__ . '/lib/conversation_mode_grouper.php';
        require_once __DIR__ . '/lib/conversation_mode_cache.php';
    }

    // ──────────────────────────────────────────────
    //  Mail task
    // ──────────────────────────────────────────────

    private function init_mail()
    {
        // Client assets – loaded on every mail view so the toggle is available
        $this->include_script('conversation_mode.js');

        // Always load default CSS (data-conv-mode toggle rules, baseline layout)
        $this->include_stylesheet('skins/default/conversation_mode.css');
        // Load skin-specific CSS on top (elastic overrides, CSS custom properties)
        $skin_css = $this->local_skin_path() . '/conversation_mode.css';
        if ($skin_css !== 'skins/default/conversation_mode.css'
            && file_exists($this->home . '/' . $skin_css)) {
            $this->include_stylesheet($skin_css);
        }

        // Push current mode to client
        $mode = $this->get_list_mode();
        $this->rcmail->output->set_env('conversation_mode', $mode);
        $this->rcmail->output->set_env('conversation_page_size',
            (int) $this->rcmail->config->get('conversation_mode_page_size', 50));

        // Register AJAX actions
        $this->register_action('plugin.conv.list',    [$this, 'action_list']);
        $this->register_action('plugin.conv.open',    [$this, 'action_open']);
        $this->register_action('plugin.conv.refresh', [$this, 'action_refresh']);
        $this->register_action('plugin.conv.setmode', [$this, 'action_set_mode']);

        // Hook into the mail list rendering
        $this->add_hook('messages_list', [$this, 'hook_messages_list']);
        $this->add_hook('template_object_mailboxlist', [$this, 'hook_inject_toggle']);

        // Register toolbar button for mode toggle (works across skins)
        // $this->add_button([
        //     'type'       => 'link',
        //     'label'      => 'conversation_mode.toggle_conversations',
        //     'command'    => 'plugin.conv.toggle',
        //     'class'      => 'button conv-toggle',
        //     'classact'   => 'button conv-toggle active',
        //     'innerclass' => 'inner',
        //     'title'      => 'conversation_mode.toggle_conversations',
        //     'domain'     => $this->ID,
        // ], 'toolbar');
    }
// In your plugin, hook 'messages_list':
   
    // ──────────────────────────────────────────────
    //  Settings task
    // ──────────────────────────────────────────────

    private function init_settings()
    {
        $this->add_hook('preferences_list', [$this, 'prefs_list']);
        $this->add_hook('preferences_save', [$this, 'prefs_save']);
    }

    // ──────────────────────────────────────────────
    //  Preferences hooks
    // ──────────────────────────────────────────────

    /**
     * Inject preference fields into Settings → Mailbox View.
     */
    public function prefs_list($args)
    {
        if ($args['section'] !== 'mailbox') {
            return $args;
        }

        $dont_override = (array) $this->rcmail->config->get('dont_override', []);
        if (in_array('message_list_mode', $dont_override)) {
            return $args;
        }

        $mode = $this->get_list_mode();

        $select = new html_select([
            'name'  => '_message_list_mode',
            'id'    => 'ff_message_list_mode',
            'class' => 'custom-select',
        ]);
        $select->add($this->gettext('mode_list'),          'list');
        $select->add($this->gettext('mode_threads'),       'threads');
        $select->add($this->gettext('mode_conversations'), 'conversations');

        $args['blocks']['main']['options']['message_list_mode'] = [
            'title'   => html::label('ff_message_list_mode', rcube::Q($this->gettext('pref_list_mode'))),
            'content' => $select->show($mode),
        ];

        return $args;
    }

    /**
     * Save the list-mode preference.
     */
    public function prefs_save($args)
    {
        if ($args['section'] !== 'mailbox') {
            return $args;
        }

        $dont_override = (array) $this->rcmail->config->get('dont_override', []);
        if (in_array('message_list_mode', $dont_override)) {
            return $args;
        }

        $value = rcube_utils::get_input_string('_message_list_mode', rcube_utils::INPUT_POST);
        if (in_array($value, ['list', 'threads', 'conversations'])) {
            $args['prefs']['message_list_mode'] = $value;
        }

        return $args;
    }

    // ──────────────────────────────────────────────
    //  AJAX actions
    // ──────────────────────────────────────────────

    /**
     * Return paginated conversation list for the active mailbox.
     */
    public function action_list()
    {

  
        $mailbox  = rcube_utils::get_input_string('_mbox', rcube_utils::INPUT_GPC) ?: 'INBOX';
        $page     = max(1, (int) rcube_utils::get_input_string('_page', rcube_utils::INPUT_GPC));
        $page_size = (int) $this->rcmail->config->get('conversation_mode_page_size', 50);

        $match_uids_raw = rcube_utils::get_input_string('_match_uids', rcube_utils::INPUT_GPC);
        $match_uids     = $match_uids_raw !== '' && $match_uids_raw !== null
            ? array_map('intval', explode(',', $match_uids_raw))
            : [];

        $service  = $this->get_service();
        $result   = $service->list_conversations($mailbox, $page, $page_size, $match_uids);

        // Log the fetch operation
        $count = isset($result['conversations']) && is_array($result['conversations']) ? count($result['conversations']) : 0;
        rcmail::write_log('conversation_mode', sprintf(
            'Fetched conversation list: mailbox=%s, page=%d, page_size=%d, match_uids=%s, conversations_count=%d',
            $mailbox,
            $page,
            $page_size,
            is_array($match_uids) ? implode(',', $match_uids) : '',
            $count
        ));

        $this->rcmail->output->command('plugin.conv.render_list', $result);
        $this->rcmail->output->send();
    }

    /**
     * Return messages for a single conversation (newest-first).
     */
    public function action_open()
    {
        $mailbox = rcube_utils::get_input_string('_mbox', rcube_utils::INPUT_GPC) ?: 'INBOX';
        $conv_id = rcube_utils::get_input_string('_conv_id', rcube_utils::INPUT_GPC);

        $service = $this->get_service();
        $result  = $service->open_conversation($mailbox, $conv_id);

        // Log the fetch operation
        $count = isset($result['messages']) && is_array($result['messages']) ? count($result['messages']) : 0;
        rcmail::write_log('conversation_mode', sprintf(
            'Fetched conversation detail: mailbox=%s, conv_id=%s, messages_count=%d',
            $mailbox,
            $conv_id,
            $count
        ));

        $this->rcmail->output->command('plugin.conv.render_open', $result);
        $this->rcmail->output->send();
    }

    /**
     * Incremental refresh – check for new / changed conversations.
     */
    public function action_refresh()
    {
        $mailbox = rcube_utils::get_input_string('_mbox', rcube_utils::INPUT_GPC) ?: 'INBOX';

        $service = $this->get_service();
        $result  = $service->refresh($mailbox);

        $this->rcmail->output->command('plugin.conv.render_refresh', $result);
        $this->rcmail->output->send();
    }

    /**
     * Quick toggle – switch mode via AJAX without full page reload.
     */
    public function action_set_mode()
    {
        $mode = rcube_utils::get_input_string('_mode', rcube_utils::INPUT_POST);
        if (!in_array($mode, ['list', 'threads', 'conversations'])) {
            $mode = 'list';
        }

        $this->rcmail->user->save_prefs(['message_list_mode' => $mode]);
        $this->rcmail->output->set_env('conversation_mode', $mode);
        $this->rcmail->output->command('plugin.conv.mode_changed', ['mode' => $mode]);
        $this->rcmail->output->send();
    }

    // ──────────────────────────────────────────────
    //  Hook: messages_list
    // ──────────────────────────────────────────────

    /**
     * When in conversation mode this hook replaces the standard message rows
     * with conversation summary rows (server-side bridge).
     */
public function hook_messages_list($args)
{
    $storage = rcmail::get_instance()->get_storage();
    $folder  = $storage->get_folder();

    if (!($storage instanceof rcube_imap)) {
        return $args;
    }

    $threads     = $storage->thread_index($folder);
    $thread_data = $threads->get_thread_data();
    $depth_map   = $thread_data[0];
    $has_kids    = $thread_data[1];

    // Build root → children map
    $roots = [];
    $current_root = null;
    foreach ($depth_map as $uid => $depth) {
        if ($depth === 0) {
            $current_root = $uid;
            $roots[$uid] = [];
        } else if ($current_root !== null) {
            $roots[$current_root][] = $uid;
        }
    }

    // Index headers by UID for easy lookup
    $by_uid = [];
    foreach ($args['messages'] as $msg) {
        $by_uid[$msg->uid] = $msg;
    }

    // Log each thread as a group
    foreach ($roots as $root_uid => $child_uids) {
        $all_uids = array_merge([$root_uid], $child_uids);
        $count    = count($all_uids);

        // Find latest timestamp in thread
        $latest_ts = 0;
        foreach ($all_uids as $uid) {
            if (isset($by_uid[$uid])) {
                $latest_ts = max($latest_ts, $by_uid[$uid]->timestamp);
            }
        }

        $root_msg = $by_uid[$root_uid] ?? null;
        $subject  = $root_msg ? $root_msg->subject : '(not fetched)';

        rcube::write_log('thread-debug', "");
        rcube::write_log('thread-debug', "=== THREAD root=$root_uid | \"$subject\" | $count msgs | latest_ts=$latest_ts ===");

        foreach ($all_uids as $uid) {
            $m = $by_uid[$uid] ?? null;
            if (!$m) {
                rcube::write_log('thread-debug', "  UID $uid — headers not fetched (not on this page)");
                continue;
            }

            $depth  = $depth_map[$uid] ?? -1;
            $indent = str_repeat('  ', $depth + 1);

            rcube::write_log('thread-debug', sprintf(
                "%sdepth=%d uid=%d | %s | from=%s | to=%s | %s | flags=%s",
                $indent,
                $depth,
                $uid,
                date('Y-m-d H:i:s', $m->timestamp),
                $m->from,
                $m->to,
                $m->subject,
                implode(',', array_keys(array_filter($m->flags)))
            ));
        }
    }
     /**
    UID 241: Victor → Alice                    (root, no references)
   ????: Alice → Victor                    ← Message-ID 0feea5d1... (in Sent folder or not delivered)
UID 242: Victor → Alice (reply to Alice)   ← references include 0feea5d1...
   ????: Alice → Victor                    ← Message-ID f95eccd0... (same situation)  
UID 243: Victor → Alice (reply to Alice)   ← references include f95eccd0...
Two missing Alice replies. They're either in Alice's mailbox (separate account on the test server) and were never delivered to Victor's INBOX, or they landed and were moved/deleted.
For a full conversation, you have two options:
Option A — Search across folders (INBOX + Sent)
php// After getting the thread root's Message-ID, search Sent folder too
$root_msg_id = $by_uid[$root_uid]->messageID;

// Get all Message-IDs in the references chain
$all_refs = [];
foreach ($all_uids as $uid) {
    $m = $by_uid[$uid] ?? null;
    if ($m && $m->references) {
        $refs = preg_split('/\s+/', trim($m->references));
        $all_refs = array_merge($all_refs, $refs);
    }
    if ($m && $m->messageID) {
        $all_refs[] = $m->messageID;
    }
}
$all_refs = array_unique($all_refs);

// Search Sent folder for any of these Message-IDs
$sent_folder = rcmail::get_instance()->config->get('sent_mbox', 'Sent');
foreach ($all_refs as $ref) {
    $ref_clean = trim($ref, '<>');
    $result = $storage->search_once($sent_folder, "HEADER Message-ID $ref_clean");
    if ($result && $result->count() > 0) {
        $uids = $result->get();
        $headers = $storage->fetch_headers($sent_folder, $uids);
        // Merge into thread display
    }
}
```

This is what Gmail does behind the scenes — it queries across All Mail, not a single folder.

**Option B — Use a virtual "All Mail" folder**

If Dovecot is configured with a virtual mailbox plugin, you can create a virtual folder that spans INBOX + Sent:
```
# /etc/dovecot/conf.d/virtual-allmail.conf
namespace {
  prefix = Virtual/
  separator = /
  location = virtual:/etc/dovecot/virtual:INDEX=~/virtual
}

# /etc/dovecot/virtual/All/dovecot-virtual
INBOX
Sent
  ALL

  */
   
        if ($this->get_list_mode() !== 'conversations') {
            return $args;
        }

        // Let the standard list render normally — the JS client will
        // switch to the conversation list via AJAX immediately.
        // This hook is reserved for future server-side pre-rendering.
        return $args;
    }

    /**
     * Inject conversation toggle markup near the mailbox list.
     */
    public function hook_inject_toggle($args)
    {
        // The actual toggle is rendered by the toolbar button and JS.
        return $args;
    }

    // ──────────────────────────────────────────────
    //  Hook: storage_init
    // ──────────────────────────────────────────────

    /**
     * Add MESSAGE-ID, IN-REPLY-TO and REFERENCES to the IMAP FETCH
     * header list so the conversation grouper can link messages.
     */
    public function hook_storage_init($args)
    {
        $extra = 'MESSAGE-ID IN-REPLY-TO REFERENCES';
        $current = $args['fetch_headers'] ?? '';
        $args['fetch_headers'] = trim($current . ' ' . $extra);

        return $args;
    }

    // ──────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────

    /**
     * Return the user's current list mode.
     */
    private function get_list_mode(): string
    {
        $mode = $this->rcmail->config->get('message_list_mode');
        if (!$mode) {
            $mode = $this->rcmail->config->get('conversation_mode_default', 'list');
        }
        return in_array($mode, ['list', 'threads', 'conversations']) ? $mode : 'list';
    }

    /**
     * Lazy-load the conversation service.
     */
    private function get_service(): conversation_mode_service
    {
        if (!$this->service) {
            $this->service = new conversation_mode_service($this->rcmail);
        }
        return $this->service;
    }

    /**
     * Allow only skins that are known compatible with this plugin.
     */
    private function is_supported_skin(string $skin): bool
    {
        return in_array($skin, ['stratus', 'elastic'], true);
    }
}
