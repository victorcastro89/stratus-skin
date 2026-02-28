<?php
/**
 * Roundcube Plus Last Login plugin.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../xframework/common/Plugin.php");
require_once(__DIR__ . "/../xframework/common/Utils.php");
require_once(__DIR__ . "/../xframework/common/Geo.php");

class xlast_login extends XFramework\Plugin
{
    public $allowed_prefs = ["xsidebar_order", "xsidebar_collapsed"];
    protected bool $hasConfig = true;
    protected bool $hasSidebarBox = true;
    protected string $databaseVersion = "20151010";

    /**
     * Initializes the plugin.
     */
    public function initialize()
    {
        if ($this->rcmail->task == "mail" && $this->rcmail->action == "" && $this->showSidebarBox()) {
            $this->add_hook("render_page", [$this, "renderPage"]);
            $this->includeAsset("assets/styles/plugin.css");
        }

        $this->add_hook("xais_get_plugin_documentation", [$this, "xaisGetPluginDocumentation"]);
    }

    public function xaisGetPluginDocumentation(array $arg): array
    {
        if ($arg['plugin'] != $this->ID) {
            return $arg;
        }

        $d = [];
        $d[] = 'Last Login: shows details of the last successful login to help detect suspicious access';
        $d[] = '';
        $d[] = 'Where: Mail → right sidebar → "Last Login" box';
        $d[] = 'Shows: Country, IP, Time, "More data" link that opens an external service with additional information '.
            'about the IP address';

        $arg['text'] = implode("\n", $d);
        return $arg;
    }


    /**
     * Called from xframework to create the sidebar box. We're returning placeholders because at this point in time the real contents
     * are not available yet. We'll replace the placeholders in renderPage()
     *
     * @return array
     */
    public function getSidebarBox(): array
    {
        return ["title" => "[xlast_login_sidebar_box_title]", "html" => "[xlast_login_sidebar_box_html]"];
    }

    /**
     * Handles the render page hook. We're replacing the placeholders in the sidebar box with the real information here. We're not using
     * the login_after hook because if 2FA is used, the login is not an indication of a successful login.
     *
     * @param array $arg
     * @return array
     */
    public function renderPage(array $arg): array
    {
        // if it's the first time showing the mail page after login, read the last login info into session so it can be shown in the
        // sidebar box and save the current login info into the database
        if (empty($_SESSION['xlast_login'])) {
            $_SESSION['xlast_login'] = $this->getSidebarBoxContents();

            $data = XFramework\Geo::getDataFromIp(
                XFramework\Utils::getRemoteAddr(),
                $this->rcmail->config->get("maxmind_database", false),
                $this->rcmail->config->get("maxmind_city", false)
            );

            $data['time'] = date("Y-m-d H:i:s");
            $this->db->update("users", ["xlast_login" => json_encode($data)], ["user_id" => $this->rcmail->user->ID]);
        }

        // replace the sidebar placeholders with the real info
        $arg['content'] = str_replace("[xlast_login_sidebar_box_title]", $_SESSION['xlast_login']['title'], $arg['content']);
        $arg['content'] = str_replace("[xlast_login_sidebar_box_html]", $_SESSION['xlast_login']['html'], $arg['content']);

        return $arg;
    }

    /**
     * Retrieves the last login data from the database and returns the title and html content that will be shown in the sidebar box.
     *
     * @return array
     */
    private function getSidebarBoxContents(): array
    {
        $data = json_decode($this->db->value("xlast_login", "users", ["user_id" => $this->rcmail->user->ID]) ?? "", true);
        $title = rcube::Q($this->gettext("last_login"));

        // if no data, show a notification that there's no data
        if (empty($data['ip'])) {
            return [
                "title" => $title,
                "html" => html::div(["class" => "content-container"], $this->gettext("xlast_login.no_info"))
            ];
        }

        $flagName = "_unknown";
        if (!empty($data['country_code'])) {
            if (file_exists(__DIR__ . "/assets/flags/{$data['country_code']}.png")) {
                $flagName = $data['country_code'];
            }
        }

        $table = new html_table(['cols' => 2, 'class' => 'plain-table']);

        // add country
        $table->add(["class" => "geo-title"], $this->gettext("xlast_login.country"));
        $table->add(
            ["class" => "geo-value"],
            html::img(["src" => "plugins/xlast_login/assets/flags/$flagName.png", "alt" => "", "class" => "flag"]).
            $data['country_name']
        );

        // add city
        if (!empty($data['city'])) {
            $table->add(["class" => "geo-title"], $this->gettext("xlast_login.city"));
            $table->add(["class" => "geo-value"], $data['city']);
        }

        // add IP
        $table->add(["class" => "geo-title"], "IP");
        $table->add(["class" => "geo-value"], $data['ip']);

        // add time
        if (!empty($data['time'])) {
            try {
                $date = new DateTime($data['time']);
                $date->setTimezone(new DateTimeZone($this->rcmail->config->get("timezone")));
                $table->add(["class" => "geo-title"], $this->gettext("xlast_login.time"));
                $table->add(
                    ["class" => "geo-value"],
                    $date->format($this->format->getDateFormat() . " " . $this->format->getTimeFormat())
                );
            } catch (Exception $e) {}
        }

        // add bottom links
        $links = [];
        $moreDataUrl = $this->rcmail->config->get("more_data_url", "http://whatismyipaddress.com/ip/%s");

        if ($moreDataUrl) {
            $links[] = html::a(
                [
                    'href' => sprintf($moreDataUrl, $data['ip']),
                    'target' => '_blank',
                    'rel' => 'noopener',
                ],
                $this->gettext("xlast_login.more_data")
            );
        }

        if ($this->rcmail->config->get("show_map_link", true) && $data['latitude'] && $data['longitude']) {
            // fix the latitude and longitude (might have commas instead of periods)
            $lat = str_replace(",", ".", $data['latitude']);
            $lon = str_replace(",", ".", $data['longitude']);
            $links[] = html::a(
                [
                    'href' => "https://maps.google.com?q=$lat,$lon&ll=$lat,$lon&z=8",
                    'target' => '_blank',
                    'rel' => 'noopener',
                ],
                $this->gettext("xlast_login.map")
            );
        }

        if ($this->rcmail->config->get("show_whats_this_link", true)) {
            $links[] = html::a(
                [
                    "href" => "javascript:void(0)",
                    "onclick" => "$('.geo-explanation').dialog({modal:true, buttons:[{text:rcmail.gettext('close'), click:function(){ $(this).dialog('close') }}]})",
                ],
                $this->gettext("xlast_login.whats_this")
            );
        }

        return [
            "title" => $title . " " . html::span(["class" => "geo-code"], "(" . $data['country_name'] . ")"),
            "html" => html::div(["class" => "content-container"], $table->show()) .
                html::div(["class" => "geo-links bottom-links"], implode("", $links)) .
                html::div(
                    ["class" => "geo-explanation", "title" => $this->gettext("xlast_login.last_login")],
                    $this->gettext("xlast_login.explanation")
                )
        ];
    }
}