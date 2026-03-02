<?php

/**
 * Stratus Helper
 *
 * Companion plugin for the Stratus skin. Provides runtime color scheme
 * switching, Google Fonts integration, folder list refresh after
 * move/archive, and a user preferences UI under Settings → Stratus.
 *
 * @version 0.1.0
 * @license GNU GPLv3+
 * @author  Stratus Team
 */
class stratus_helper extends rcube_plugin
{
    /**
     * Tasks this plugin is active in.
    * - mail: folder refresh, inject appearance CSS/JS
    * - settings: user preferences UI
     */
    public $task = 'mail|settings';

    /**
     * @var rcmail
     */
    private $rcmail;

    /**
     * Resolved color scheme (cached for the request).
     * @var array|null
     */
    private $active_scheme;

    /**
     * Resolved font config (cached for the request).
     * @var array|null
     */
    private $active_font;

    // ──────────────────────────────────────────────
    //  Initialization
    // ──────────────────────────────────────────────

    public function init()
    {
        $this->rcmail = rcmail::get_instance();

        $this->load_config('config.inc.php.dist');  // defaults
        $this->load_config();                        // user overrides (if file exists)
        $this->add_texts('localization/', true);

        // Only activate full features when the stratus skin is active
        $skin = $this->rcmail->config->get('skin', 'elastic');
        if ($skin !== 'stratus') {
            return;
        }

        // Inject appearance (color scheme + font) on every page load
        $this->inject_appearance();

        if ($this->rcmail->task === 'mail') {
            $this->init_mail();
        }

        if ($this->rcmail->task === 'settings') {
            $this->init_settings();
        }
    }

    // ──────────────────────────────────────────────
    //  Mail task
    // ──────────────────────────────────────────────

    private function init_mail()
    {
        // Client JS for live scheme/font switching
        $this->include_script('stratus_helper.js');

        // Push current appearance prefs to client env
        $scheme = $this->get_active_scheme();
        $font   = $this->get_active_font();

        $this->rcmail->output->set_env('stratus_color_scheme', $this->get_scheme_key());
        $this->rcmail->output->set_env('stratus_scheme_primary', $scheme['primary']);
        $this->rcmail->output->set_env('stratus_scheme_primary_dark', $scheme['primary_dark']);
        $this->rcmail->output->set_env('stratus_font_key', $this->get_font_key());
        $this->rcmail->output->set_env('stratus_font_family', $font['family']);
        $this->rcmail->output->set_env('stratus_font_url', $font['url']);

        // Register AJAX actions for live switching
        $this->register_action('plugin.stratus.set_scheme', [$this, 'action_set_scheme']);
        $this->register_action('plugin.stratus.set_font', [$this, 'action_set_font']);
    }

    // ──────────────────────────────────────────────
    //  Settings task
    // ──────────────────────────────────────────────

    private function init_settings()
    {
        $this->include_script('stratus_helper.js');

        $this->add_hook('preferences_sections_list', [$this, 'prefs_section']);
        $this->add_hook('preferences_list',          [$this, 'prefs_list']);
        $this->add_hook('preferences_save',          [$this, 'prefs_save']);
    }

    // ──────────────────────────────────────────────
    //  Appearance Injection
    // ──────────────────────────────────────────────

    /**
     * Inject color scheme CSS custom properties and font stylesheet
     * into <head> on every page load.
     */
    private function inject_appearance()
    {
        // JSON/plain responses (e.g. AJAX) use output classes that don't
        // implement add_header(). Appearance injection is only meaningful
        // for HTML page renders.
        if (!method_exists($this->rcmail->output, 'add_header')) {
            return;
        }

        $scheme = $this->get_active_scheme();
        $font   = $this->get_active_font();

        // ── Color scheme CSS custom properties ──
        $primary      = $this->sanitize_color($scheme['primary']);
        $primary_dark = $this->sanitize_color($scheme['primary_dark']);

        // Derive additional colors from primary
        $css = ":root {\n";
        $css .= "  --stratus-primary: {$primary};\n";
        $css .= "  --stratus-primary-dark: {$primary_dark};\n";
        $css .= "  --stratus-primary-rgb: " . $this->hex_to_rgb($primary) . ";\n";
        $css .= "  --stratus-primary-dark-rgb: " . $this->hex_to_rgb($primary_dark) . ";\n";
        $css .= "}\n";

        // Font family override
        if ($font['family']) {
            $css .= "body { --stratus-font-family: {$font['family']}; }\n";
        }

        $this->rcmail->output->add_header(
            '<style id="stratus-helper-vars">' . $css . '</style>'
        );

        // ── Google Font stylesheet (if applicable) ──
        if (!empty($font['url'])) {
            $url = htmlspecialchars($font['url'], ENT_QUOTES, 'UTF-8');
            $this->rcmail->output->add_header(
                '<link id="stratus-helper-font" rel="stylesheet" href="' . $url . '">'
            );
        }
    }

    // ──────────────────────────────────────────────
    //  AJAX: Color Scheme
    // ──────────────────────────────────────────────

    /**
     * Save color scheme preference via AJAX and return new CSS vars.
     */
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

    // ──────────────────────────────────────────────
    //  AJAX: Font
    // ──────────────────────────────────────────────

    /**
     * Save font preference via AJAX and return font info.
     */
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

    // ──────────────────────────────────────────────
    //  Preferences: Section
    // ──────────────────────────────────────────────

    /**
     * Add "Stratus Appearance" section to Settings nav.
     */
    public function prefs_section($args)
    {
        $args['list']['stratus'] = [
            'id'      => 'stratus',
            'section' => rcube::Q($this->gettext('section_title')),
        ];
        return $args;
    }

    // ──────────────────────────────────────────────
    //  Preferences: List
    // ──────────────────────────────────────────────

    /**
     * Render preference fields in the Stratus section.
     */
    public function prefs_list($args)
    {
        if ($args['section'] !== 'stratus') {
            return $args;
        }

        $dont_override = (array) $this->rcmail->config->get('dont_override', []);

        $blocks = [];

        // ── Color Scheme ──
        if (!in_array('stratus_color_scheme', $dont_override)) {
            $schemes    = $this->rcmail->config->get('stratus_color_schemes', []);
            $current    = $this->get_scheme_key();

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
                        'title'   => html::label('ff_stratus_color_scheme', rcube::Q($this->gettext('color_scheme'))),
                        'content' => $select->show($current),
                    ],
                ],
            ];
        }

        // ── Font Family ──
        if (!in_array('stratus_font_family', $dont_override)) {
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
                        'title'   => html::label('ff_stratus_font_family', rcube::Q($this->gettext('font_family'))),
                        'content' => $select->show($current),
                    ],
                ],
            ];
        }

        $args['blocks'] = array_merge($args['blocks'], $blocks);
        return $args;
    }

    // ──────────────────────────────────────────────
    //  Preferences: Save
    // ──────────────────────────────────────────────

    /**
     * Persist Stratus preferences.
     */
    public function prefs_save($args)
    {
        if ($args['section'] !== 'stratus') {
            return $args;
        }

        $dont_override = (array) $this->rcmail->config->get('dont_override', []);

        // ── Color scheme ──
        if (!in_array('stratus_color_scheme', $dont_override)) {
            $value   = rcube_utils::get_input_string('_stratus_color_scheme', rcube_utils::INPUT_POST);
            $schemes = $this->rcmail->config->get('stratus_color_schemes', []);
            if (isset($schemes[$value])) {
                $args['prefs']['stratus_color_scheme'] = $value;
            }
        }

        // ── Font family ──
        if (!in_array('stratus_font_family', $dont_override)) {
            $value = rcube_utils::get_input_string('_stratus_font_family', rcube_utils::INPUT_POST);
            $fonts = $this->rcmail->config->get('stratus_fonts', []);
            if (isset($fonts[$value])) {
                $args['prefs']['stratus_font_family'] = $value;
            }
        }

        return $args;
    }

    // ──────────────────────────────────────────────
    //  Helpers: Scheme
    // ──────────────────────────────────────────────

    /**
     * Get the active color scheme key.
     */
    private function get_scheme_key(): string
    {
        $key     = $this->rcmail->config->get('stratus_color_scheme');
        $schemes = $this->rcmail->config->get('stratus_color_schemes', []);

        if (!$key || !isset($schemes[$key])) {
            $key = $this->rcmail->config->get('stratus_color_scheme_default', 'indigo');
        }

        return isset($schemes[$key]) ? $key : 'indigo';
    }

    /**
     * Get the active color scheme config array.
     */
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

    // ──────────────────────────────────────────────
    //  Helpers: Font
    // ──────────────────────────────────────────────

    /**
     * Get the active font key.
     */
    private function get_font_key(): string
    {
        $key   = $this->rcmail->config->get('stratus_font_family');
        $fonts = $this->rcmail->config->get('stratus_fonts', []);

        if (!$key || !isset($fonts[$key])) {
            $key = $this->rcmail->config->get('stratus_font_default', 'system');
        }

        return isset($fonts[$key]) ? $key : 'system';
    }

    /**
     * Get the active font config array.
     */
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

    // ──────────────────────────────────────────────
    //  Helpers: Color Utilities
    // ──────────────────────────────────────────────

    /**
     * Sanitize a hex color value.
     */
    private function sanitize_color(string $color): string
    {
        // Strip everything except hex chars and #
        $color = preg_replace('/[^#0-9a-fA-F]/', '', $color);

        // Ensure it starts with #
        if (strpos($color, '#') !== 0) {
            $color = '#' . $color;
        }

        // Validate 4 or 7 char hex
        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color)) {
            return '#5c6bc0'; // fallback to indigo
        }

        return $color;
    }

    /**
     * Convert hex color to comma-separated RGB string.
     * E.g. "#5c6bc0" → "92, 107, 192"
     */
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
