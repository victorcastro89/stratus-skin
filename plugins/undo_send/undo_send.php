<?php

/**
 * Undo Send
 *
 * Gmail-style undo-send for Roundcube. Delays message delivery by a
 * configurable number of seconds and shows a native #messagestack toast
 * with an "Undo" link. Purely client-side — no database, no cron, no
 * external dependencies.
 *
 * Works with elastic, larry-based skins, and stratus.
 *
 * @version 0.1.0
 * @license GNU GPLv3+
 * @author  Stratus Team
 */
class undo_send extends rcube_plugin
{
    /**
     * Tasks this plugin is active in.
     * - mail: send interception on compose page
     * - settings: user preferences UI
     */
    public $task = 'mail|settings';

    /**
     * @var rcmail
     */
    private $rcmail;

    /**
     * Allowed delay values (seconds). 0 = disabled.
     */
    private $delay_options = [0, 3, 5, 10, 15, 30];

    // ──────────────────────────────────────────────
    //  Initialization
    // ──────────────────────────────────────────────

    public function init()
    {
        $this->rcmail = rcmail::get_instance();

        $this->load_config('config.inc.php.dist');  // defaults
        $this->load_config();                        // user overrides (if file exists)
        $this->add_texts('localization/', true);

        if ($this->rcmail->task === 'mail' && $this->rcmail->action === 'compose') {
            $this->init_compose();
        }

        if ($this->rcmail->task === 'settings') {
            $this->init_settings();
        }
    }

    // ──────────────────────────────────────────────
    //  Compose task
    // ──────────────────────────────────────────────

    private function init_compose()
    {
        // Load CSS: default baseline + skin-specific overlay
        $this->include_stylesheet('skins/default/undo_send.css');
        $skin_css = $this->local_skin_path() . '/undo_send.css';
        if ($skin_css !== 'skins/default/undo_send.css'
            && file_exists($this->home . '/' . $skin_css)) {
            $this->include_stylesheet($skin_css);
        }

        $this->include_script('undo_send.js');

        // Push delay preference to client
        $delay = (int) $this->rcmail->config->get('undo_send_delay', 3);
        if (!in_array($delay, $this->delay_options)) {
            $delay = 3;
        }
        $this->rcmail->output->set_env('undo_send_delay', $delay);

        // Push localized labels to client
        $this->rcmail->output->add_label(
            'undo_send.sending_in',
            'undo_send.undo',
            'undo_send.send_now',
            'undo_send.sending_cancelled',
            'undo_send.sending'
        );
    }

    // ──────────────────────────────────────────────
    //  Settings task
    // ──────────────────────────────────────────────

    private function init_settings()
    {
        $this->add_hook('preferences_sections_list', [$this, 'prefs_section']);
        $this->add_hook('preferences_list',          [$this, 'prefs_list']);
        $this->add_hook('preferences_save',          [$this, 'prefs_save']);
    }

    // ──────────────────────────────────────────────
    //  Preferences: Section
    // ──────────────────────────────────────────────

    /**
     * Add "Undo Send" section to Settings → Preferences.
     */
    public function prefs_section($args)
    {
        $args['list']['undo_send'] = [
            'id'      => 'undo_send',
            'section' => $this->gettext('section_title'),
        ];
        return $args;
    }

    // ──────────────────────────────────────────────
    //  Preferences: List
    // ──────────────────────────────────────────────

    /**
     * Render preference fields in the Undo Send section.
     */
    public function prefs_list($args)
    {
        if ($args['section'] !== 'undo_send') {
            return $args;
        }

        $dont_override = (array) $this->rcmail->config->get('dont_override', []);

        if (!in_array('undo_send_delay', $dont_override)) {
            $current = (int) $this->rcmail->config->get('undo_send_delay', 5);

            $select = new html_select([
                'name'  => '_undo_send_delay',
                'id'    => 'ff_undo_send_delay',
                'class' => 'custom-select',
            ]);

            foreach ($this->delay_options as $seconds) {
                if ($seconds === 0) {
                    $label = $this->gettext('delay_disabled');
                } else {
                    $label = $this->gettext(['name' => 'delay_seconds', 'vars' => ['n' => $seconds]]);
                }
                $select->add($label, $seconds);
            }

            $args['blocks']['main']['name'] = rcube::Q($this->gettext('section_title'));
            $args['blocks']['main']['options']['undo_send_delay'] = [
                'title'   => html::label('ff_undo_send_delay', rcube::Q($this->gettext('undo_send_delay'))),
                'content' => $select->show($current),
            ];
        }

        return $args;
    }

    // ──────────────────────────────────────────────
    //  Preferences: Save
    // ──────────────────────────────────────────────

    /**
     * Persist undo send preference.
     */
    public function prefs_save($args)
    {
        if ($args['section'] !== 'undo_send') {
            return $args;
        }

        $dont_override = (array) $this->rcmail->config->get('dont_override', []);

        if (!in_array('undo_send_delay', $dont_override)) {
            $value = (int) rcube_utils::get_input_string('_undo_send_delay', rcube_utils::INPUT_POST);
            if (in_array($value, $this->delay_options)) {
                $args['prefs']['undo_send_delay'] = $value;
            }
        }

        return $args;
    }
}
