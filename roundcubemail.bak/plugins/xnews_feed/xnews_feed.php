<?php
/**
 * Roundcube Plus News Feed plugin.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../xframework/common/Plugin.php");
require_once(__DIR__ . "/../xframework/vendor/autoload.php");

use XFramework\Utils;
use XFramework\Response;

class xnews_feed extends XFramework\Plugin
{
    public $allowed_prefs = ["xsidebar_order", "xsidebar_collapsed"];
    protected bool $hasConfig = true;
    protected bool $hasSidebarBox = true;
    protected string $appUrl = "?_task=settings&_action=preferences&_section=xnews_feed";

    protected array $default = [
        "title" => "BBC World",
        "url" => "http://feeds.bbci.co.uk/news/world/rss.xml",
        "count" => "10",
    ];

    protected array $predefined = [
        "Deutsch" => [
            "https://rss.dw.com/xml/rss-de-all" => "Deutsche Welle",
            "https://www.spiegel.de/index.rss" => "Spiegel",
        ],
        "English" => [
            "http://feeds.abcnews.com/abcnews/topstories" => "ABC",
            "https://feeds.bbci.co.uk/news/world/rss.xml" => "BBC - World",
            "https://www.cbsnews.com/latest/rss/main" => "CBS",
            "http://rss.cnn.com/rss/edition.rss" => "CNN",
            "https://www.nasa.gov/rss/dyn/breaking_news.rss" => "NASA",
        ],
        "Español" => [
            "https://www.bbc.co.uk/mundo/index.xml" => "BBC - Mundo",
            "https://feeds.elpais.com/mrss-s/pages/ep/site/elpais.com/section/ultimas-noticias/portada" => "El País",
            "https://www.elperiodico.com/es/rss/rss_portada.xml" => "El Periódico",
        ],
        "Français" => [
            "https://www.france24.com/fr/rss" => "France 24",
            "https://www.lemonde.fr/rss/une.xml" => "Le Monde",
        ],
        "Italiano" => [
            "https://xml2.corriereobjects.it/rss/homepage.xml" => "Corriere",
            "https://www.ansa.it/sito/ansait_rss.xml" => "ANSA",
        ],
        "Polski"=> [
            "https://wiadomosci.gazeta.pl/pub/rss/wiadomosci.htm" => "Gazeta.pl",
            "https://fakty.interia.pl/feed" => "Interia",
        ],
        "Portuguese" => [
            "https://feeds.bbci.co.uk/portuguese/rss.xml" => "BBC - Mundo",
            "https://rr.sapo.pt/rss/rssfeed.aspx?section=section_noticias" => "Sapo",
        ],
    ];

    /**
     * Initializes the plugin.
     * @codeCoverageIgnore
     */
    public function initialize()
    {
        if (!function_exists("curl_init")) {
            rcube::write_log("errors", "[xnews_feed] The cURl extension is not available");
            return;
        }

        // handle ajax call requesting the news
        if ($this->rcmail->action == "xnews_rss") {
            $this->getRss();
        }

        $includeAssets = false;
        $this->add_hook("xais_get_plugin_documentation", [$this, "xaisGetPluginDocumentation"]);

        if ($this->rcmail->task == "mail" && $this->rcmail->action == "") {
            $includeAssets = $this->showSidebarBox();
        } else if ($this->rcmail->task == "settings") {
            $this->add_hook('preferences_sections_list', [$this, 'preferencesSectionsList']);
            $this->add_hook('preferences_list', [$this, 'preferencesList']);
            $this->add_hook('preferences_save', [$this, 'preferencesSave']);
            $includeAssets = true;
        }

        if ($includeAssets) {
            $this->includeAsset("assets/scripts/plugin.min.js");
            $this->includeAsset("assets/styles/plugin.css");
            $this->rcmail->output->add_label("xnews_feed.feed_error");
        }
    }

    public function xaisGetPluginDocumentation(array $arg): array
    {
        if ($arg['plugin'] != $this->ID) {
            return $arg;
        }

        $d = [];
        $d[] = 'News Feed: show RSS headlines in the sidebar on the Mail page';
        $d[] = '';
        $d[] = 'Where: Mail → right sidebar → News Feed; Settings → Preferences → News Feed';
        $d[] = '';
        $d[] = 'Setup:';
        $d[] = '- Choose a feed: pick from predefined feeds (grouped by language) or enter your own Title and URL.';
        $d[] = '- Items to show — set how many headlines to display (e.g., 10).';
        $d[] = '- [Test news feed settings] — loads the feed below the form to verify it works.';
        $d[] = '';
        $d[] = 'Using it:';
        $d[] = '- Displays a list of article links.';
        $d[] = '- Hover a link to see a popup with more text.';
        $d[] = '- Sidebar boxes can be shown or hidden and reordered in Settings → Sidebar.';
        $d[] = '- Sidebar boxes can also be reordered by dragging and dropping directly on the Mail page.';

        $arg['text'] = implode("\n", $d);
        return $arg;
    }

    /**
     * Gets the rss feed from the specified url requested via ajax. We need to run this call via our function instead
     * of getting the rss feed directly in js because if RC runs on https and the feed url is on http, it won't get
     * fetched.
     *
     * @codeCoverageIgnore
     */
    protected function getRss()
    {
        $url = $this->input->get("url");
        $count = (int)$this->input->get("count");

        if (!$url) {
            Response::error();
        }

        if ($count < 5 || $count > 20) {
            $count = 10;
        }

        $rss = [];
        $error = "";
        $ch = null;

        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 10,
            ]);

            $xml = curl_exec($ch);

            if (curl_errno($ch) || !$xml) {
                $error = $this->gettext("feed_error_url") . ' (482294)';
                throw new Exception();
            }

            $dom = new DOMDocument();
            if (!@$dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)) {
                $error = $this->gettext("feed_error_url") . ' (274482)';
                throw new Exception();
            }

            $index = 0;

            foreach ($dom->getElementsByTagName("item") as $node) {
                $rss[] = [
                    "title" => htmlentities(html_entity_decode(
                        $node->getElementsByTagName("title")->item(0)->nodeValue ?? "")),
                    "description" => Utils::sanitizeHtml(
                        $node->getElementsByTagName('description')->item(0)->nodeValue ?? ""),
                    "link" => $node->getElementsByTagName('link')->item(0)->nodeValue,
                ];

                if ($index > $count - 1) {
                    break;
                }

                $index++;
            }

            if (!count($rss)) {
                $error = $this->gettext("feed_error_content");
                throw new Exception();
            }
        } catch (Exception $e) {
            $rss = false;
        } finally {
            if ($ch) {
                curl_close($ch);
            }
        }

        Response::success(["rss" => $rss, "error" => $error]);
    }

    public function getSidebarBox()
    {
        if (!($url = $this->getSetting("url"))) {
            return false;
        }

        $this->setJsVar(
            "xnews_feed",
            [
                "url" => $url,
                "count" => $this->getSetting("count"),
                "error" => $this->gettext("xnews_feed.feed_error")
            ]
        );

        $title = rcube::Q($this->getSetting("title"));
        $parts = parse_url($url);
        $logo = Utils::ensureFileName($parts['host'] ?? '') . ".png";

        if (file_exists(__DIR__ . "/assets/logos/$logo")) {
            $title = html::img(["src" => "plugins/xnews_feed/assets/logos/$logo", "alt" => ""]) . $title;
        }

        return ["title" => $title, "html" => "", "settingsUrl" => $this->appUrl];
    }

    /**
     * Adds the news feed section to the section list on the settings page.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesSectionsList(array $arg): array
    {
        // add the news feed section item
        $arg['list']['xnews_feed'] = ['id' => 'xnews_feed', 'section' => $this->gettext("xnews_feed.news_feed")];

		return $arg;
    }

    /**
     * Creates the news feed user preferences page.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesList(array $arg): array
    {
        if ($arg['section'] != "xnews_feed") {
            return $arg;
        }

        $skip = $this->rcmail->config->get("dont_override");

        $arg['blocks']['main']['name'] = $this->gettext("mainoptions");

        // predefined feeds (we're not using html::select because it doesn't support optgroups)
        $predefined = $this->predefined;

        if ($configPredefined = $this->rcmail->config->get("xnews_feed_predefined")) {
            $predefined = $configPredefined;
        }

        // add 'None' to the beginning of the array
        $predefined = ["" => $this->gettext("none")] + $predefined;

        $items = [];
        $url = $this->getSetting("url");

        foreach ($predefined as $key => $val) {
            if (is_array($val)) { // if a group
                foreach ($val as $address => $title) {
                    $title = rcube::Q($title);
                    $items[] = "<option value='$address'" . ($url == $address ? " selected" : "") . " data-title='$title'>" .
                        rcube::Q($key) . " - $title</option>";
                }
            } else { // if not a group
                $title = rcube::Q($val);
                $items[] = "<option value='$key'" . ($url == $key ? " selected" : "") . " data-title='$title'>$title</option>";
            }
        }

        $this->addSetting(
            $arg,
            "main",
            "predefined",
            "<select id='xnews_feed_predefined'>" . implode("", $items) . "</select>"
        );

        // title
        if (!in_array("xnews_feed_title", $skip)) {
            $this->getSettingInput($arg, "main", "title");
        }

        // url
        if (!in_array("xnews_feed_url", $skip)) {
            $this->getSettingInput($arg, "main", "url");
        }

        // count
        if (!in_array("xnews_feed_count", $skip)) {
            $this->getSettingSelect($arg, "main", "count", [
                "5" => "5", "10" => "10", "15" => "15", "20" => "20",
            ]);
        }

        // test button
        $arg['blocks']['main']['options']["test"] = [
            "title" => "",
            "content" => "<button type='button' class='button' onclick='xnews_feed.testSettings()'>" .
                rcube::Q($this->gettext("test_settings")) .
                "</button>"
        ];

        // preview
        $arg['blocks']['preview']['name'] = $this->gettext("xframework.preview");
        $arg['blocks']['preview']['options']["test"] = [
            "content" => "<div id='xnews_feed-test' class='box-xnews_feed'></div>"
        ];

		return $arg;
    }

    /**
     * Saves the user preferences.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesSave(array $arg): array
    {
        if ($arg['section'] == "xnews_feed") {
            $this->saveSetting($arg, "url");
            $this->saveSetting($arg, "title");
            $this->saveSetting($arg, "count");
        }

        return $arg;
    }
}