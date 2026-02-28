<?php
/**
 * Roundcube Plus Quotes plugin.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../xframework/common/Plugin.php");

class xquote extends XFramework\Plugin
{
    public $allowed_prefs = ["xsidebar_order", "xsidebar_collapsed"];
    protected bool $hasConfig = false;
    protected bool $hasSidebarBox = true;
    protected array $languages = ["de", "en", "es", "fr", "nl", "it", "pl", "pt"];

    /**
     * Initializes the plugin.
     * @codeCoverageIgnore
     */
    public function initialize()
    {
        $this->add_hook("xais_get_plugin_documentation", [$this, "xaisGetPluginDocumentation"]);
    }

    public function xaisGetPluginDocumentation(array $arg): array
    {
        if ($arg['plugin'] != $this->ID) {
            return $arg;
        }

        $d = [];
        $d[] = 'Quote: inspirational quotes from famous people';
        $d[] = '';
        $d[] = 'Where: Mail → right sidebar → Quote';
        $d[] = '';
        $d[] = 'Behavior:';
        $d[] = '- Displays a quote in the current Roundcube interface language.';
        $d[] = '- The author’s name is a link to their Wikipedia page.';
        $d[] = '';
        $d[] = 'Notes:';
        $d[] = '- If quotes are not available for the selected language, nothing is shown.';
        $d[] = '- No settings; read-only display.';
        $d[] = '- Sidebar boxes can be shown or hidden and reordered in Settings → Sidebar.';
        $d[] = '- Sidebar boxes can also be reordered by dragging and dropping directly on the Mail page.';

        $arg['text'] = implode("\n", $d);
        return $arg;
    }


    /**
     * @param $day - Used for unit testing.
     * @return boolean|array
     */
    public function getSidebarBox($day = null)
    {
        $lan = substr($_SESSION['language'], 0, 2);
        in_array($lan, $this->languages) || $lan = "en";
        $lines = file(__DIR__ . "/data/$lan.inc", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (!is_array($lines)) {
            return false;
        }

        $line = $lines[$day ?: date("z")];

        if (empty($line)) {
            return false;
        }

        $array = explode("|", $line);

        if (count($array) != 2) {
            return false;
        }

        $quote = trim($array[0]);
        $author = trim($array[1]);

        return [
            "title" => rcube::Q($this->gettext("daily_quote")),
            "html" => html::div(["class" => "xquote-text"], rcube::Q($quote)).
                (empty($author) ? "" :
                html::div(
                    ["class" => "bottom-links xquote-author", "style" => "text-align:right;font-style:italic;"],
                    html::a(
                        [
                            "href" => "https://$lan.wikipedia.org/wiki/Special:Search/" . str_replace(" ", "_", $author),
                            "target" => "_blank",
                            "rel" => "noopener",
                        ],
                        rcube::Q($author)
                    )
                )),
        ];
    }
}