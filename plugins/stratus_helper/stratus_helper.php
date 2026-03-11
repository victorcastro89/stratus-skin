<?php

/**
 * Stratus Helper
 *
 * Companion plugin for the Stratus skin. Provides runtime color scheme
 * switching, Google Fonts integration, folder list refresh after
 * move/archive, user preferences UI under Settings → Stratus,
 * and localized message-list date formatting.
 *
 * Date rules for mail list:
 * - Today:     HH:MM (using user's time format preference when possible)
 * - Yesterday: localized "Yesterday"
 * - Older:     localized short date according to user preferences
 *
 * @version 0.2.0
 * @license GNU GPLv3+
 * @author  Stratus Team
 */
class stratus_helper extends rcube_plugin
{
    public $task = 'mail|settings';

    /**
     * @var rcmail
     */
    private $rcmail;

    /**
     * @var array|null
     */
    private $active_scheme;

    /**
     * @var array|null
     */
    private $active_font;

    public function init()
    {
        $this->rcmail = rcmail::get_instance();

        $this->load_config('config.inc.php.dist');
        $this->load_config();
        $this->add_texts('localization/', true);

        $skin = $this->rcmail->config->get('skin', 'elastic');
        if ($skin !== 'stratus') {
            return;
        }

        $this->inject_appearance();

        if ($this->rcmail->task === 'mail') {
            $this->init_mail();
        }

        if ($this->rcmail->task === 'settings') {
            $this->init_settings();
        }
    }

    private function init_mail()
    {
        $this->include_script('stratus_helper.js');

        $this->include_script('../../skins/stratus/js/smart-bar/selection-manager.js');
        $this->include_script('../../skins/stratus/js/smart-bar/multi-select-controller.js');
        $this->include_script('../../skins/stratus/js/smart-bar/mass-action-bar.js');
        $this->include_script('../../skins/stratus/js/smart-bar/action-dispatcher.js');
        $this->include_script('../../skins/stratus/js/smart-bar/sort-controller.js');
        $this->include_script('../../skins/stratus/js/smart-bar.js');

        $scheme = $this->get_active_scheme();
        $font   = $this->get_active_font();

        $this->rcmail->output->set_env('stratus_color_scheme', $this->get_scheme_key());
        $this->rcmail->output->set_env('stratus_scheme_primary', $scheme['primary']);
        $this->rcmail->output->set_env('stratus_scheme_primary_dark', $scheme['primary_dark']);
        $this->rcmail->output->set_env('stratus_font_key', $this->get_font_key());
        $this->rcmail->output->set_env('stratus_font_family', $font['family']);
        $this->rcmail->output->set_env('stratus_font_url', $font['url']);

        $this->register_action('plugin.stratus.set_scheme', [$this, 'action_set_scheme']);
        $this->register_action('plugin.stratus.set_font', [$this, 'action_set_font']);

        // Message-list date formatting
        $this->add_hook('messages_list', [$this, 'messages_list']);
    }

    private function init_settings()
    {
        $this->include_script('stratus_helper.js');

        $this->add_hook('preferences_sections_list', [$this, 'prefs_section']);
        $this->add_hook('preferences_list', [$this, 'prefs_list']);
        $this->add_hook('preferences_save', [$this, 'prefs_save']);
    }

    private function inject_appearance()
    {
        if (!method_exists($this->rcmail->output, 'add_header')) {
            return;
        }

        $scheme = $this->get_active_scheme();
        $font   = $this->get_active_font();

        $primary      = $this->sanitize_color($scheme['primary']);
        $primary_dark = $this->sanitize_color($scheme['primary_dark']);

        $css = ":root {\n";
        $css .= "  --stratus-primary: {$primary};\n";
        $css .= "  --stratus-primary-dark: {$primary_dark};\n";
        $css .= "  --stratus-primary-rgb: " . $this->hex_to_rgb($primary) . ";\n";
        $css .= "  --stratus-primary-dark-rgb: " . $this->hex_to_rgb($primary_dark) . ";\n";
        $css .= "}\n";

        if ($font['family']) {
            $css .= "body { --stratus-font-family: {$font['family']}; }\n";
        }

        $this->rcmail->output->add_header(
            '<style id="stratus-helper-vars">' . $css . '</style>'
        );

        if (!empty($font['url'])) {
            $url = htmlspecialchars($font['url'], ENT_QUOTES, 'UTF-8');
            $this->rcmail->output->add_header(
                '<link id="stratus-helper-font" rel="stylesheet" href="' . $url . '">'
            );
        }
    }

    public function action_set_scheme()
    {
        $key     = rcube_utils::get_input_string('_scheme', rcube_utils::INPUT_POST);
        $schemes = $this->rcmail->config->get('stratus_color_schemes', []);

        if (!isset($schemes[$key])) {
            $key = $this->rcmail->config->get('stratus_color_scheme_default', 'indigo');
        }

        $this->rcmail->user->save_prefs(['stratus_color_scheme' => $key]);

        $scheme = $schemes[$key];
        $this->rcmail->output->command('plugin.stratus.scheme_applied', [
            'key'          => $key,
            'primary'      => $scheme['primary'],
            'primary_dark' => $scheme['primary_dark'],
        ]);
        $this->rcmail->output->send();
    }

    public function action_set_font()
    {
        $key   = rcube_utils::get_input_string('_font', rcube_utils::INPUT_POST);
        $fonts = $this->rcmail->config->get('stratus_fonts', []);

        if (!isset($fonts[$key])) {
            $key = $this->rcmail->config->get('stratus_font_default', 'system');
        }

        $this->rcmail->user->save_prefs(['stratus_font_family' => $key]);

        $font = $fonts[$key];
        $this->rcmail->output->command('plugin.stratus.font_applied', [
            'key'    => $key,
            'family' => $font['family'],
            'url'    => $font['url'],
        ]);
        $this->rcmail->output->send();
    }

    public function prefs_section($args)
    {
        $args['list']['stratus'] = [
            'id'      => 'stratus',
            'section' => rcube::Q($this->gettext('section_title')),
        ];

        return $args;
    }

    public function prefs_list($args)
    {
        if ($args['section'] !== 'stratus') {
            return $args;
        }

        $dont_override = (array) $this->rcmail->config->get('dont_override', []);
        $blocks = [];

        if (!in_array('stratus_color_scheme', $dont_override, true)) {
            $schemes = $this->rcmail->config->get('stratus_color_schemes', []);
            $current = $this->get_scheme_key();

            $select = new html_select([
                'name'  => '_stratus_color_scheme',
                'id'    => 'ff_stratus_color_scheme',
                'class' => 'custom-select',
            ]);

            foreach ($schemes as $key => $scheme) {
                $select->add($scheme['label'], $key);
            }

            $blocks['color'] = [
                'name'    => rcube::Q($this->gettext('color_scheme')),
                'options' => [
                    'stratus_color_scheme' => [
                        'title'   => html::label(
                            'ff_stratus_color_scheme',
                            rcube::Q($this->gettext('color_scheme'))
                        ),
                        'content' => $select->show($current),
                    ],
                ],
            ];
        }

        if (!in_array('stratus_font_family', $dont_override, true)) {
            $fonts   = $this->rcmail->config->get('stratus_fonts', []);
            $current = $this->get_font_key();

            $select = new html_select([
                'name'  => '_stratus_font_family',
                'id'    => 'ff_stratus_font_family',
                'class' => 'custom-select',
            ]);

            foreach ($fonts as $key => $font) {
                $select->add($font['label'], $key);
            }

            $blocks['font'] = [
                'name'    => rcube::Q($this->gettext('font_family')),
                'options' => [
                    'stratus_font_family' => [
                        'title'   => html::label(
                            'ff_stratus_font_family',
                            rcube::Q($this->gettext('font_family'))
                        ),
                        'content' => $select->show($current),
                    ],
                ],
            ];
        }

        $args['blocks'] = array_merge($args['blocks'], $blocks);

        return $args;
    }

    public function prefs_save($args)
    {
        if ($args['section'] !== 'stratus') {
            return $args;
        }

        $dont_override = (array) $this->rcmail->config->get('dont_override', []);

        if (!in_array('stratus_color_scheme', $dont_override, true)) {
            $value   = rcube_utils::get_input_string('_stratus_color_scheme', rcube_utils::INPUT_POST);
            $schemes = $this->rcmail->config->get('stratus_color_schemes', []);

            if (isset($schemes[$value])) {
                $args['prefs']['stratus_color_scheme'] = $value;
            }
        }

        if (!in_array('stratus_font_family', $dont_override, true)) {
            $value = rcube_utils::get_input_string('_stratus_font_family', rcube_utils::INPUT_POST);
            $fonts = $this->rcmail->config->get('stratus_fonts', []);

            if (isset($fonts[$value])) {
                $args['prefs']['stratus_font_family'] = $value;
            }
        }

        return $args;
    }

    /**
     * Format message-list dates for Stratus.
     *
     * Rules:
     * - Today: HH:MM or user time_format
     * - Yesterday: localized label
     * - Older: localized short date from user preferences
     */
    // TODO: Add localization
public function messages_list($args)
{
    // rcube::write_log('errors', 'Stratus: messages_list hook fired');
    // rcube::write_log('errors', $args['messages']);
    if (empty($args['messages']) || !is_array($args['messages'])) {
        return $args;
    }

    $tz = $this->get_user_timezone();
    $now = new DateTime('now', $tz);
    $today_start = (clone $now)->setTime(0, 0, 0);
    $yesterday_start = (clone $today_start)->modify('-1 day');

    foreach ($args['messages'] as $idx => $msg) {
        $timestamp = null;

        if (!empty($msg->timestamp) && is_numeric($msg->timestamp)) {
            $timestamp = (int) $msg->timestamp;
        }
        elseif (!empty($msg->date)) {
            $timestamp = $this->get_message_timestamp($msg->date);
        }

        if (!$timestamp) {
            continue;
        }

        $msg_dt = new DateTime('@' . $timestamp);
        $msg_dt->setTimezone($tz);
        $msg_day = (clone $msg_dt)->setTime(0, 0, 0);

        if ($msg_day == $today_start) {
            $display = $this->format_today_time($timestamp);
        }
        elseif ($msg_day == $yesterday_start) {
            $display = $this->gettext('yesterday');
        }
        else {
            $display = $this->format_older_date($timestamp);
        }

        $msg->date = $display;

        if (!is_array($msg->list_cols)) {
            $msg->list_cols = [];
        }

        $msg->list_cols['date'] = rcube::Q($display);

        // rcube::write_log('errors', sprintf(
        //     'Stratus: uid=%s timestamp=%s final_date=%s',
        //     $msg->uid ?? 'n/a',
        //     $timestamp,
        //     $display
        // ));

        $args['messages'][$idx] = $msg;
    }

    return $args;
}

    /**
     * Resolve current user's timezone.
     */
    private function get_user_timezone(): DateTimeZone
    {
        $tz = $this->rcmail->config->get('timezone');

        if (is_string($tz) && $tz !== '' && $tz !== 'auto') {
            try {
                return new DateTimeZone($tz);
            }
            catch (Exception $e) {
            }
        }

        if (!empty($_SESSION['timezone']) && is_string($_SESSION['timezone'])) {
            try {
                return new DateTimeZone($_SESSION['timezone']);
            }
            catch (Exception $e) {
            }
        }

        return new DateTimeZone(date_default_timezone_get());
    }

    /**
     * Convert Roundcube's message date value into a Unix timestamp.
     */
    private function get_message_timestamp($value): ?int
    {
        if ($value instanceof DateTimeInterface) {
            return $value->getTimestamp();
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $ts = rcube_utils::anytodatetime($value);
            if ($ts instanceof DateTimeInterface) {
                return $ts->getTimestamp();
            }

            $ts = strtotime($value);
            if ($ts !== false) {
                return (int) $ts;
            }
        }

        return null;
    }

    /**
     * Format today's messages using user's preferred time format.
     * Falls back to HH:MM if preference is unavailable.
     */
    private function format_today_time(int $timestamp): string
    {
        $time_format = $this->rcmail->config->get('time_format');

        if (!is_string($time_format) || $time_format === '') {
            $time_format = 'H:i';
        }

        return $this->rcmail->format_date($timestamp, $time_format);
    }

    /**
     * Format older messages using user's preferred localized short date.
     */
    private function format_older_date(int $timestamp): string
    {
        $date_format = $this->rcmail->config->get('date_format');

        if (is_string($date_format) && $date_format !== '') {
            return $this->rcmail->format_date($timestamp, $date_format);
        }

        return $this->rcmail->format_date($timestamp, 'd');
    }

    private function get_scheme_key(): string
    {
        $key     = $this->rcmail->config->get('stratus_color_scheme');
        $schemes = $this->rcmail->config->get('stratus_color_schemes', []);

        if (!$key || !isset($schemes[$key])) {
            $key = $this->rcmail->config->get('stratus_color_scheme_default', 'indigo');
        }

        return isset($schemes[$key]) ? $key : 'indigo';
    }

    private function get_active_scheme(): array
    {
        if ($this->active_scheme !== null) {
            return $this->active_scheme;
        }

        $key     = $this->get_scheme_key();
        $schemes = $this->rcmail->config->get('stratus_color_schemes', []);

        $this->active_scheme = $schemes[$key] ?? [
            'primary'      => '#5c6bc0',
            'primary_dark' => '#7986cb',
            'label'        => 'Indigo',
        ];

        return $this->active_scheme;
    }

    private function get_font_key(): string
    {
        $key   = $this->rcmail->config->get('stratus_font_family');
        $fonts = $this->rcmail->config->get('stratus_fonts', []);

        if (!$key || !isset($fonts[$key])) {
            $key = $this->rcmail->config->get('stratus_font_default', 'system');
        }

        return isset($fonts[$key]) ? $key : 'system';
    }

    private function get_active_font(): array
    {
        if ($this->active_font !== null) {
            return $this->active_font;
        }

        $key   = $this->get_font_key();
        $fonts = $this->rcmail->config->get('stratus_fonts', []);

        $this->active_font = $fonts[$key] ?? [
            'family' => "system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif",
            'url'    => null,
            'label'  => 'System Default',
        ];

        return $this->active_font;
    }

    private function sanitize_color(string $color): string
    {
        $color = preg_replace('/[^#0-9a-fA-F]/', '', $color);

        if (strpos($color, '#') !== 0) {
            $color = '#' . $color;
        }

        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return '#5c6bc0';
        }

        return $color;
    }

    private function hex_to_rgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }
}