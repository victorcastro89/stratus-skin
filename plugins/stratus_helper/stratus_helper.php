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
    public $task = 'mail|settings|login|calendar|addressbook';

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

    /**
     * @var array|null
     */
    private $active_font_size;

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

        $this->rcmail->load_language(null, [], ['username' => 'Email']);

        // Auto-derive missing palette tokens for minimal schemes
        $schemes = $this->rcmail->config->get('stratus_color_schemes', []);
        $modified = false;
        foreach ($schemes as $key => &$scheme) {
            if (!empty($scheme['primary']) && (
                empty($scheme['text_accent']) || empty($scheme['sidebar_bg'])
                || empty($scheme['dark_background'])
            )) {
                $scheme = $this->derive_full_palette($scheme);
                $modified = true;
            }
        }
        unset($scheme);
        if ($modified) {
            $this->rcmail->config->set('stratus_color_schemes', $schemes);
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
        $this->rcmail->output->set_env('stratus_text_accent', $scheme['text_accent'] ?? '#3949ab');
        $this->rcmail->output->set_env('stratus_text_accent_dark', $scheme['text_accent_dark'] ?? '#9fa8da');
        $this->rcmail->output->set_env('stratus_scheme_data', $this->get_scheme_js_data($scheme));
        $this->rcmail->output->set_env('stratus_font_key', $this->get_font_key());
        $this->rcmail->output->set_env('stratus_font_family', $font['family']);
        $this->rcmail->output->set_env('stratus_font_url', $font['url']);

        $font_size = $this->get_active_font_size();
        $this->rcmail->output->set_env('stratus_font_size_key', $this->get_font_size_key());
        $this->rcmail->output->set_env('stratus_font_size', $font_size['size']);
        $this->rcmail->output->set_env('stratus_font_line_height', $font_size['line_height']);

        $this->register_action('plugin.stratus.set_scheme', [$this, 'action_set_scheme']);
        $this->register_action('plugin.stratus.set_font', [$this, 'action_set_font']);
        $this->register_action('plugin.stratus.set_fontsize', [$this, 'action_set_fontsize']);

        // Message-list date formatting
        $this->add_hook('messages_list', [$this, 'messages_list']);

        // Outlook-style reply divider
        $this->add_hook('message_compose_body', [$this, 'outlook_reply_divider']);
    }

    private function init_settings()
    {
        $this->include_script('stratus_helper.js');

        $this->add_hook('preferences_sections_list', [$this, 'prefs_section']);
        $this->add_hook('preferences_list', [$this, 'prefs_list']);
        $this->add_hook('preferences_save', [$this, 'prefs_save']);

        $this->register_action('plugin.stratus.set_scheme',   [$this, 'action_set_scheme']);
        $this->register_action('plugin.stratus.set_font',     [$this, 'action_set_font']);
        $this->register_action('plugin.stratus.set_fontsize', [$this, 'action_set_fontsize']);

        // Pass font and size lookup maps to JS via rcmail.env for reliable live preview.
        // html_select does not guarantee data-* attribute pass-through.
        $fonts     = $this->rcmail->config->get('stratus_fonts', []);
        $font_data = [];
        foreach ($fonts as $k => $f) {
            $font_data[$k] = ['family' => $f['family'], 'url' => $f['url'] ?? null];
        }
        $this->rcmail->output->set_env('stratus_fonts_data', $font_data);

        $sizes     = $this->rcmail->config->get('stratus_font_sizes', []);
        $size_data = [];
        foreach ($sizes as $k => $sz) {
            $size_data[$k] = ['size' => $sz['size'], 'line_height' => $sz['line_height']];
        }
        $this->rcmail->output->set_env('stratus_font_sizes_data', $size_data);
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

        $text_accent      = $this->sanitize_color($scheme['text_accent'] ?? '#3949ab');
        $text_accent_dark = $this->sanitize_color($scheme['text_accent_dark'] ?? '#9fa8da');

        $css = ":root {\n";
        $css .= "  --stratus-primary: {$primary};\n";
        $css .= "  --stratus-primary-dark: {$primary_dark};\n";
        $css .= "  --stratus-primary-rgb: " . $this->hex_to_rgb($primary) . ";\n";
        $css .= "  --stratus-primary-dark-rgb: " . $this->hex_to_rgb($primary_dark) . ";\n";
        $css .= "  --stratus-text-accent: {$text_accent};\n";
        $css .= "  --stratus-text-accent-dark: {$text_accent_dark};\n";

        // Sidebar tokens
        if (!empty($scheme['sidebar_bg'])) {
            $css .= "  --stratus-sidebar-bg: " . $this->sanitize_css_value($scheme['sidebar_bg']) . ";\n";
        }
        if (!empty($scheme['sidebar_gradient'])) {
            $css .= "  --stratus-sidebar-gradient: " . $this->sanitize_css_value($scheme['sidebar_gradient']) . ";\n";
        }
        if (!empty($scheme['sidebar_text'])) {
            $css .= "  --stratus-sidebar-text: " . $this->sanitize_css_value($scheme['sidebar_text']) . ";\n";
        }
        if (!empty($scheme['sidebar_text_hover'])) {
            $css .= "  --stratus-sidebar-text-hover: " . $this->sanitize_css_value($scheme['sidebar_text_hover']) . ";\n";
        }
        if (!empty($scheme['sidebar_text_active'])) {
            $css .= "  --stratus-sidebar-text-active: " . $this->sanitize_css_value($scheme['sidebar_text_active']) . ";\n";
        }
        if (!empty($scheme['sidebar_active_bg'])) {
            $css .= "  --stratus-sidebar-active-bg: " . $this->sanitize_css_value($scheme['sidebar_active_bg']) . ";\n";
        }

        // Surface tint tokens
        if (!empty($scheme['surface_tint'])) {
            $css .= "  --stratus-surface-tint: " . $this->sanitize_css_value($scheme['surface_tint']) . ";\n";
        }
        if (!empty($scheme['hover_bg'])) {
            $css .= "  --stratus-hover-bg: " . $this->sanitize_css_value($scheme['hover_bg']) . ";\n";
        }
        if (!empty($scheme['selected_bg'])) {
            $css .= "  --stratus-selected-bg: " . $this->sanitize_css_value($scheme['selected_bg']) . ";\n";
        }
        if (!empty($scheme['focus_ring'])) {
            $css .= "  --stratus-focus-ring: " . $this->sanitize_css_value($scheme['focus_ring']) . ";\n";
        }

        // Gradient tokens
        $css .= "  --stratus-gradient: linear-gradient(135deg, {$primary} 0%, " . $this->sanitize_color($this->lighten_hex($primary, 15)) . " 100%);\n";
        $css .= "  --stratus-gradient-hover: linear-gradient(135deg, " . $this->sanitize_color($this->darken_hex($primary, 6)) . " 0%, " . $this->sanitize_color($this->lighten_hex($primary, 9)) . " 100%);\n";

        // Dark mode complementary light color
        $css .= "  --stratus-primary-dark-light: {$primary_dark};\n";

        // Typography & border tokens — scheme-derived hue-tinted values
        if (!empty($scheme['font'])) {
            $css .= "  --stratus-font: " . $this->sanitize_color($scheme['font']) . ";\n";
        }
        if (!empty($scheme['font_secondary'])) {
            $css .= "  --stratus-font-secondary: " . $this->sanitize_color($scheme['font_secondary']) . ";\n";
        }
        if (!empty($scheme['border'])) {
            $css .= "  --stratus-border: " . $this->sanitize_color($scheme['border']) . ";\n";
        }

        // Dark mode palette tokens — scheme-aware dark surfaces, text, borders
        if (!empty($scheme['dark_background'])) {
            $dark_bg = $this->sanitize_color($scheme['dark_background']);
            $css .= "  --stratus-dark-background: {$dark_bg};\n";
            $css .= "  --stratus-dark-background-rgb: " . $this->hex_to_rgb($dark_bg) . ";\n";
        }
        if (!empty($scheme['dark_surface'])) {
            $dark_sf = $this->sanitize_color($scheme['dark_surface']);
            $css .= "  --stratus-dark-surface: {$dark_sf};\n";
            $css .= "  --stratus-dark-surface-rgb: " . $this->hex_to_rgb($dark_sf) . ";\n";
        }
        if (!empty($scheme['dark_surface_raised'])) {
            $css .= "  --stratus-dark-surface-raised: " . $this->sanitize_color($scheme['dark_surface_raised']) . ";\n";
        }
        if (!empty($scheme['dark_font'])) {
            $css .= "  --stratus-dark-font: " . $this->sanitize_color($scheme['dark_font']) . ";\n";
        }
        if (!empty($scheme['dark_font_secondary'])) {
            $css .= "  --stratus-dark-font-secondary: " . $this->sanitize_color($scheme['dark_font_secondary']) . ";\n";
        }
        if (!empty($scheme['dark_border'])) {
            $dark_bd = $this->sanitize_color($scheme['dark_border']);
            $css .= "  --stratus-dark-border: {$dark_bd};\n";
            $css .= "  --stratus-dark-border-rgb: " . $this->hex_to_rgb($dark_bd) . ";\n";
        }

        // Dark utility tokens — pre-computed lighten() replacements
        if (!empty($scheme['dark_background'])) {
            $css .= "  --stratus-dark-input-bg-focus: " . $this->lighten_hex($this->sanitize_color($scheme['dark_background']), 3) . ";\n";
            $css .= "  --stratus-dark-message-loading-bg: " . $this->lighten_hex($this->sanitize_color($scheme['dark_background']), 10) . ";\n";
        }
        if (!empty($scheme['dark_surface_raised'])) {
            $css .= "  --stratus-dark-input-addon-focus-bg: " . $this->lighten_hex($this->sanitize_color($scheme['dark_surface_raised']), 8) . ";\n";
        }

        $css .= "}\n";

        if ($font['family']) {
            $css .= ":root { --stratus-font-family: {$font['family']}; }\n";
        }

        $font_size = $this->get_active_font_size();
        if ($font_size['size']) {
            $css .= ":root { --stratus-font-size: {$font_size['size']}; --stratus-line-height: {$font_size['line_height']}; }\n";
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

        $scheme = $this->rcmail->config->get('stratus_color_schemes', [])[$key];
        $this->rcmail->output->command('plugin.stratus.scheme_applied',
            array_merge(['key' => $key], $this->get_scheme_js_data($scheme))
        );
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

    public function action_set_fontsize()
    {
        $key   = rcube_utils::get_input_string('_fontsize', rcube_utils::INPUT_POST);
        $sizes = $this->rcmail->config->get('stratus_font_sizes', []);

        if (!isset($sizes[$key])) {
            $key = $this->rcmail->config->get('stratus_font_size_default', 'default');
        }

        $this->rcmail->user->save_prefs(['stratus_font_size' => $key]);

        $sz = $sizes[$key];
        $this->rcmail->output->command('plugin.stratus.fontsize_applied', [
            'key'         => $key,
            'size'        => $sz['size'],
            'line_height' => $sz['line_height'],
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

            // Build the visual swatch card grid (radio buttons styled as clickable cards).
            // Each radio carries the full scheme token set as data-scheme JSON so the JS
            // can apply it client-side instantly — no round-trip needed for preview.
            $picker_html = '<div class="stratus-scheme-picker">';
            foreach ($schemes as $key => $scheme) {
                $id          = 'stratus-scheme-' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8');
                $checked     = ($key === $current) ? ' checked="checked"' : '';
                $sidebar_bg  = rcube::Q($scheme['sidebar_bg'] ?? '#1a1f36');
                $primary     = rcube::Q($this->sanitize_color($scheme['primary']));
                $label_text  = rcube::Q($scheme['label']);
                $scheme_json = htmlspecialchars(
                    json_encode($this->get_scheme_js_data($scheme), JSON_HEX_TAG | JSON_HEX_QUOT),
                    ENT_QUOTES,
                    'UTF-8'
                );

                $picker_html .= '<span class="stratus-scheme-card">'
                    . '<input type="radio" name="_stratus_color_scheme" id="' . $id . '" value="' . rcube::Q($key) . '"' . $checked . ' data-scheme="' . $scheme_json . '">'
                    . '<label for="' . $id . '">'
                    . '<span class="stratus-scheme-swatch-card" style="background: linear-gradient(90deg, ' . $sidebar_bg . ' 50%, ' . $primary . ' 50%);"></span>'
                    . '<span class="stratus-scheme-label">' . $label_text . '</span>'
                    . '</label>'
                    . '</span>';
            }
            $picker_html .= '</div>';

            $blocks['color'] = [
                'name'    => rcube::Q($this->gettext('color_scheme')),
                'options' => [
                    'stratus_color_scheme' => [
                        'title'   => rcube::Q($this->gettext('color_scheme')),
                        'content' => $picker_html,
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

        if (!in_array('stratus_font_size', $dont_override, true)) {
            $sizes   = $this->rcmail->config->get('stratus_font_sizes', []);
            $current = $this->get_font_size_key();

            $select = new html_select([
                'name'  => '_stratus_font_size',
                'id'    => 'ff_stratus_font_size',
                'class' => 'custom-select',
            ]);

            foreach ($sizes as $key => $sz) {
                $select->add($sz['label'], $key);
            }

            $blocks['fontsize'] = [
                'name'    => rcube::Q($this->gettext('font_size')),
                'options' => [
                    'stratus_font_size' => [
                        'title'   => html::label(
                            'ff_stratus_font_size',
                            rcube::Q($this->gettext('font_size'))
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
                $scheme = $schemes[$value];
                $this->rcmail->output->command('plugin.stratus.scheme_applied',
                    array_merge(['key' => $value], $this->get_scheme_js_data($scheme))
                );
            }
        }

        if (!in_array('stratus_font_family', $dont_override, true)) {
            $value = rcube_utils::get_input_string('_stratus_font_family', rcube_utils::INPUT_POST);
            $fonts = $this->rcmail->config->get('stratus_fonts', []);

            if (isset($fonts[$value])) {
                $args['prefs']['stratus_font_family'] = $value;
                $font = $fonts[$value];
                $this->rcmail->output->command('plugin.stratus.font_applied', [
                    'key'    => $value,
                    'family' => $font['family'],
                    'url'    => $font['url'],
                ]);
            }
        }

        if (!in_array('stratus_font_size', $dont_override, true)) {
            $value = rcube_utils::get_input_string('_stratus_font_size', rcube_utils::INPUT_POST);
            $sizes = $this->rcmail->config->get('stratus_font_sizes', []);

            if (isset($sizes[$value])) {
                $args['prefs']['stratus_font_size'] = $value;
                $sz = $sizes[$value];
                $this->rcmail->output->command('plugin.stratus.fontsize_applied', [
                    'key'         => $value,
                    'size'        => $sz['size'],
                    'line_height' => $sz['line_height'],
                ]);
            }
        }

        return $args;
    }

    /**
     * Replace the default Roundcube reply intro + blockquote with an
     * Outlook-style horizontal rule and From/Date/To/Subject header.
     *
     * Roundcube generates:
     *   <p id="reply-intro">On [date], [sender] wrote:</p>
     *   <blockquote>[original message]</blockquote>
     *
     * We transform it to:
     *   <hr id="reply-divider" style="...">
     *   <div id="reply-header" style="...">From / Date / To / Subject</div>
     *   [original message]  ← blockquote wrapper removed
     */
    public function outlook_reply_divider($args)
    {
        if ($args['mode'] !== 'reply' || empty($args['html'])) {
            return $args;
        }

        $body = $args['body'];

        if (strpos($body, '<p id="reply-intro">') === false) {
            return $args;
        }

        $message = !empty($args['message']) ? $args['message'] : null;
        if (empty($message) || empty($message->headers)) {
            return $args;
        }

        $date    = $this->rcmail->format_date($message->get_header('date'), $this->rcmail->config->get('date_long'));
        $from    = rcube::Q($message->get_header('from'));
        $to      = rcube::Q($message->get_header('to'));
        $subject = rcube::Q($message->subject);

        $lbl_from    = rcube::Q($this->rcmail->gettext('from'));
        $lbl_date    = rcube::Q($this->rcmail->gettext('date'));
        $lbl_to      = rcube::Q($this->rcmail->gettext('to'));
        $lbl_subject = rcube::Q($this->rcmail->gettext('subject'));

        $divider = '<hr id="reply-divider" style="border:0;border-top:1px solid #e0e0e0;margin:20px 0">'
            . '<div id="reply-header" style="color:#555555;font-size:0.875em;line-height:1.8;margin-bottom:16px">'
            . '<b>' . $lbl_from    . ':</b> ' . $from    . '<br>'
            . '<b>' . $lbl_date    . ':</b> ' . rcube::Q($date) . '<br>'
            . '<b>' . $lbl_to      . ':</b> ' . $to      . '<br>'
            . '<b>' . $lbl_subject . ':</b> ' . $subject
            . '</div>';

        // Replace reply-intro paragraph + opening blockquote with the divider.
        // The optional leading <br> is present for top-posting mode.
        $body = preg_replace(
            '/(?:<br\s*\/?>)?\s*<p id="reply-intro">[^<]*<\/p>\s*<blockquote>/si',
            $divider,
            $body
        );

        // Remove the outer </blockquote> (always the last one in the body —
        // it was added by create_reply_body() as a direct wrapper around the
        // entire quoted message).
        $last = strrpos($body, '</blockquote>');
        if ($last !== false) {
            $body = substr_replace($body, '', $last, strlen('</blockquote>'));
        }

        $args['body'] = $body;
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

    /**
     * Build the full scheme data array for JS consumption.
     * Contains all tokens needed by applyScheme() in stratus_helper.js.
     */
    private function get_scheme_js_data(array $scheme): array
    {
        $primary      = $scheme['primary'];
        $primary_dark = $scheme['primary_dark'];

        return [
            'primary'            => $primary,
            'primary_dark'       => $primary_dark,
            'text_accent'        => $scheme['text_accent'] ?? '#3949ab',
            'text_accent_dark'   => $scheme['text_accent_dark'] ?? '#9fa8da',
            'sidebar_bg'         => $scheme['sidebar_bg'] ?? '',
            'sidebar_gradient'   => $scheme['sidebar_gradient'] ?? '',
            'sidebar_text'       => $scheme['sidebar_text'] ?? '',
            'sidebar_text_hover' => $scheme['sidebar_text_hover'] ?? '',
            'sidebar_text_active'=> $scheme['sidebar_text_active'] ?? '',
            'sidebar_active_bg'  => $scheme['sidebar_active_bg'] ?? '',
            'surface_tint'       => $scheme['surface_tint'] ?? '',
            'hover_bg'           => $scheme['hover_bg'] ?? '',
            'selected_bg'        => $scheme['selected_bg'] ?? '',
            'focus_ring'         => $scheme['focus_ring'] ?? '',
            'gradient'           => 'linear-gradient(135deg, ' . $primary . ' 0%, ' . $this->lighten_hex($primary, 15) . ' 100%)',
            'gradient_hover'     => 'linear-gradient(135deg, ' . $this->darken_hex($primary, 6) . ' 0%, ' . $this->lighten_hex($primary, 9) . ' 100%)',
            'primary_dark_light' => $primary_dark,
            'font'               => $scheme['font'] ?? '',
            'font_secondary'     => $scheme['font_secondary'] ?? '',
            'border'             => $scheme['border'] ?? '',
            'dark_background'       => $scheme['dark_background'] ?? '',
            'dark_surface'          => $scheme['dark_surface'] ?? '',
            'dark_surface_raised'   => $scheme['dark_surface_raised'] ?? '',
            'dark_font'             => $scheme['dark_font'] ?? '',
            'dark_font_secondary'   => $scheme['dark_font_secondary'] ?? '',
            'dark_border'           => $scheme['dark_border'] ?? '',
        ];
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

    private function get_font_size_key(): string
    {
        $key   = $this->rcmail->config->get('stratus_font_size');
        $sizes = $this->rcmail->config->get('stratus_font_sizes', []);

        if (!$key || !isset($sizes[$key])) {
            $key = $this->rcmail->config->get('stratus_font_size_default', 'default');
        }

        return isset($sizes[$key]) ? $key : 'default';
    }

    private function get_active_font_size(): array
    {
        if ($this->active_font_size !== null) {
            return $this->active_font_size;
        }

        $key   = $this->get_font_size_key();
        $sizes = $this->rcmail->config->get('stratus_font_sizes', []);

        $this->active_font_size = $sizes[$key] ?? [
            'size'        => '0.875rem',
            'line_height' => '1.5',
            'label'       => 'Default',
        ];

        return $this->active_font_size;
    }

    // ── Auto-Derivation Engine ──────────────────────────────────────────────

    private function hex_to_hsl(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            $d = $max - $min;
            $s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);

            if ($max === $r) {
                $h = ($g - $b) / $d + ($g < $b ? 6 : 0);
            } elseif ($max === $g) {
                $h = ($b - $r) / $d + 2;
            } else {
                $h = ($r - $g) / $d + 4;
            }
            $h /= 6;
        }

        return [$h * 360, $s * 100, $l * 100];
    }

    private function hsl_to_hex(float $h, float $s, float $l): string
    {
        $h /= 360;
        $s /= 100;
        $l /= 100;

        if ($s == 0) {
            $r = $g = $b = $l;
        } else {
            $q = $l < 0.5 ? $l * (1 + $s) : $l + $s - $l * $s;
            $p = 2 * $l - $q;
            $r = $this->hue_to_rgb($p, $q, $h + 1/3);
            $g = $this->hue_to_rgb($p, $q, $h);
            $b = $this->hue_to_rgb($p, $q, $h - 1/3);
        }

        return sprintf('#%02x%02x%02x',
            (int) round($r * 255),
            (int) round($g * 255),
            (int) round($b * 255)
        );
    }

    private function hue_to_rgb(float $p, float $q, float $t): float
    {
        if ($t < 0) $t += 1;
        if ($t > 1) $t -= 1;
        if ($t < 1/6) return $p + ($q - $p) * 6 * $t;
        if ($t < 1/2) return $q;
        if ($t < 2/3) return $p + ($q - $p) * (2/3 - $t) * 6;
        return $p;
    }

    private function relative_luminance(string $hex): float
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $r = $r <= 0.04045 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.04045 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.04045 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    private function contrast_ratio(string $hex1, string $hex2): float
    {
        $l1 = $this->relative_luminance($hex1);
        $l2 = $this->relative_luminance($hex2);

        $lighter = max($l1, $l2);
        $darker  = min($l1, $l2);

        return ($lighter + 0.05) / ($darker + 0.05);
    }

    private function derive_text_accent(string $color, string $surface, float $target_ratio): string
    {
        $hsl = $this->hex_to_hsl($color);
        $surface_lum = $this->relative_luminance($surface);
        $darken = $surface_lum > 0.5;  // light surface → darken the accent

        for ($i = 0; $i < 50; $i++) {
            $candidate = $this->hsl_to_hex($hsl[0], $hsl[1], $hsl[2]);
            if ($this->contrast_ratio($candidate, $surface) >= $target_ratio) {
                return $candidate;
            }
            $hsl[2] += $darken ? -2 : 2;
            $hsl[2] = max(0, min(100, $hsl[2]));
        }

        // Fallback
        return $darken ? '#3949ab' : '#9fa8da';
    }

    private function derive_full_palette(array $scheme): array
    {
        $primary = $scheme['primary'];
        $hsl = $this->hex_to_hsl($primary);
        $h = $hsl[0];
        $s = $hsl[1];

        // primary_dark
        if (empty($scheme['primary_dark'])) {
            $scheme['primary_dark'] = $this->hsl_to_hex($h, $s, min($hsl[2] + 18, 75));
        }

        // text_accent
        if (empty($scheme['text_accent'])) {
            $scheme['text_accent'] = $this->derive_text_accent($primary, '#ffffff', 4.5);
        }

        // text_accent_dark
        if (empty($scheme['text_accent_dark'])) {
            $scheme['text_accent_dark'] = $this->derive_text_accent(
                $scheme['primary_dark'], '#1a1f36', 4.5
            );
        }

        // sidebar_bg
        if (empty($scheme['sidebar_bg'])) {
            $scheme['sidebar_bg'] = $this->hsl_to_hex($h, max($s * 0.4, 15), 10);
        }

        // sidebar_gradient
        if (empty($scheme['sidebar_gradient'])) {
            $top = $this->hsl_to_hex($h, max($s * 0.4, 15), 13);
            $bottom = $this->hsl_to_hex($h, max($s * 0.4, 15), 7);
            $scheme['sidebar_gradient'] = "linear-gradient(180deg, {$top} 0%, {$bottom} 100%)";
        }

        // sidebar_text
        if (empty($scheme['sidebar_text'])) {
            $scheme['sidebar_text'] = $this->hsl_to_hex($h, max($s * 0.35, 12), 55);
        }

        // sidebar_text_hover
        if (empty($scheme['sidebar_text_hover'])) {
            $scheme['sidebar_text_hover'] = '#ffffff';
        }

        // sidebar_text_active
        if (empty($scheme['sidebar_text_active'])) {
            $scheme['sidebar_text_active'] = '#ffffff';
        }

        // sidebar_active_bg — rgba from primary RGB
        if (empty($scheme['sidebar_active_bg'])) {
            $rgb = $this->hex_to_rgb($primary);
            $scheme['sidebar_active_bg'] = "rgba({$rgb}, 0.20)";
        }

        // surface_tint
        if (empty($scheme['surface_tint'])) {
            $rgb = $this->hex_to_rgb($primary);
            $scheme['surface_tint'] = "rgba({$rgb}, 0.03)";
        }

        // hover_bg
        if (empty($scheme['hover_bg'])) {
            $rgb = $this->hex_to_rgb($primary);
            $scheme['hover_bg'] = "rgba({$rgb}, 0.06)";
        }

        // selected_bg
        if (empty($scheme['selected_bg'])) {
            $rgb = $this->hex_to_rgb($primary);
            $scheme['selected_bg'] = "rgba({$rgb}, 0.13)";
        }

        // focus_ring
        if (empty($scheme['focus_ring'])) {
            $rgb = $this->hex_to_rgb($primary);
            $scheme['focus_ring'] = "rgba({$rgb}, 0.25)";
        }

        // font — hue-tinted body text (matches @color-font at s=43%: hsl(h, 25, 38))
        if (empty($scheme['font'])) {
            $font_s = max(10, min((int)round($s * 0.58), 28));
            $scheme['font'] = $this->hsl_to_hex($h, $font_s, 38);
        }

        // font_secondary — muted text (matches @color-font-secondary at s=43%: hsl(h, 18, 51))
        if (empty($scheme['font_secondary'])) {
            $font_sec_s = max(8, min((int)round($s * 0.42), 22));
            $scheme['font_secondary'] = $this->hsl_to_hex($h, $font_sec_s, 51);
        }

        // border — very light hue-tinted divider (matches @color-border at s=43%: hsl(h, 32, 91))
        if (empty($scheme['border'])) {
            $border_s = max(10, min((int)round($s * 0.74), 38));
            $scheme['border'] = $this->hsl_to_hex($h, $border_s, 91);
        }

        // ── Dark mode palette — hue-tinted dark tokens ─────────────────────
        // Each scheme gets its own dark palette derived from the primary hue.
        // Saturation is clamped to stay subtle; lightness creates the depth layers.

        // dark_background
        if (empty($scheme['dark_background'])) {
            $dark_bg_s = max(8, min((int)round($s * 0.30), 20));
            $scheme['dark_background'] = $this->hsl_to_hex($h, $dark_bg_s, 8);
        }

        // dark_surface
        if (empty($scheme['dark_surface'])) {
            $dark_sf_s = max(8, min((int)round($s * 0.30), 20));
            $scheme['dark_surface'] = $this->hsl_to_hex($h, $dark_sf_s, 12);
        }

        // dark_surface_raised
        if (empty($scheme['dark_surface_raised'])) {
            $dark_sr_s = max(8, min((int)round($s * 0.28), 18));
            $scheme['dark_surface_raised'] = $this->hsl_to_hex($h, $dark_sr_s, 17);
        }

        // dark_font — primary text (>= 7:1 AAA against dark_background)
        if (empty($scheme['dark_font'])) {
            $dark_f_s = max(3, min((int)round($s * 0.15), 12));
            $candidate = $this->hsl_to_hex($h, $dark_f_s, 83);
            // Iteratively adjust lightness if contrast is insufficient
            $bg = $scheme['dark_background'];
            $l = 83;
            while ($this->contrast_ratio($candidate, $bg) < 7.0 && $l < 98) {
                $l += 1;
                $candidate = $this->hsl_to_hex($h, $dark_f_s, $l);
            }
            $scheme['dark_font'] = $candidate;
        }

        // dark_font_secondary — muted text (>= 4.5:1 AA against dark_background)
        if (empty($scheme['dark_font_secondary'])) {
            $dark_fs_s = max(5, min((int)round($s * 0.18), 14));
            $candidate = $this->hsl_to_hex($h, $dark_fs_s, 58);
            $bg = $scheme['dark_background'];
            $l = 58;
            while ($this->contrast_ratio($candidate, $bg) < 4.5 && $l < 80) {
                $l += 1;
                $candidate = $this->hsl_to_hex($h, $dark_fs_s, $l);
            }
            $scheme['dark_font_secondary'] = $candidate;
        }

        // dark_border
        if (empty($scheme['dark_border'])) {
            $dark_b_s = max(6, min((int)round($s * 0.25), 16));
            $scheme['dark_border'] = $this->hsl_to_hex($h, $dark_b_s, 20);
        }

        return $scheme;
    }

    private function lighten_hex(string $hex, float $amount): string
    {
        $hsl = $this->hex_to_hsl($hex);
        $hsl[2] = min(100, $hsl[2] + $amount);
        return $this->hsl_to_hex($hsl[0], $hsl[1], $hsl[2]);
    }

    private function darken_hex(string $hex, float $amount): string
    {
        $hsl = $this->hex_to_hsl($hex);
        $hsl[2] = max(0, $hsl[2] - $amount);
        return $this->hsl_to_hex($hsl[0], $hsl[1], $hsl[2]);
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

    /**
     * Sanitize a CSS custom property value (color, gradient, rgba).
     * Strips anything that could break out of the CSS declaration.
     */
    private function sanitize_css_value(string $value): string
    {
        // Allow hex colors, rgba(), linear-gradient(), named colors, and whitespace
        // Strip any characters that could inject CSS (semicolons, braces, comments)
        return preg_replace('/[;{}]|\/\*|\*\//', '', $value);
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