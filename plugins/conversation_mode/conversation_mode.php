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
        $this->add_button([
            'type'       => 'link',
            'label'      => 'conversation_mode.toggle_conversations',
            'command'    => 'plugin.conv.toggle',
            'class'      => 'button conv-toggle',
            'classact'   => 'button conv-toggle active',
            'innerclass' => 'inner',
            'title'      => 'conversation_mode.toggle_conversations',
            'domain'     => $this->ID,
        ], 'toolbar');
    }

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

        $service  = $this->get_service();
        $result   = $service->list_conversations($mailbox, $page, $page_size);

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
