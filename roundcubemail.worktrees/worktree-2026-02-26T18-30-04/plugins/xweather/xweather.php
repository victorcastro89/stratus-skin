<?php
/**
 * Roundcube Plus Weather plugin.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../xframework/common/Plugin.php");
require_once(__DIR__ . "/../xframework/common/Geo.php");

use XFramework\Utils;
use XFramework\Response;

class xweather extends XFramework\Plugin
{
    const ACTION_GET_WEATHER_DATA = 'plugin.xweather_get_weather_data';
    public $allowed_prefs = [
        "xsidebar_order",
        "xsidebar_collapsed",
        "xweather_latitude",
        "xweather_longitude",
    ];
    protected bool $hasConfig = true;
    protected bool $hasSidebarBox = true;
    protected string $appUrl = "?_task=settings&_action=preferences&_section=xweather";
    protected array $showOptions = ["description", "pressure", "humidity", "clouds", "wind", "sunrise", "sunset"];
    protected array $icons = ["cutout", "glossy", "monochrome", "moonlight", "stickers"];

    /**
     * Initializes the plugin.
     */
    public function initialize()
    {
        if (!trim($this->rcmail->config->get("xweather_open_weather_map_key", ""))) {
            $this->hasSidebarBox = false;
            return;
        }

        $this->register_action(self::ACTION_GET_WEATHER_DATA, [$this, 'getWeatherData']);
        $this->add_hook("xais_get_plugin_documentation", [$this, "xaisGetPluginDocumentation"]);

        if ($this->rcmail->task == "mail" && $this->rcmail->action == "") {
            $includeAssets = $this->showSidebarBox();
        } else if ($this->rcmail->task == "settings") {
            $this->add_hook('preferences_sections_list', [$this, 'preferencesSectionsList']);
            $this->add_hook('preferences_list', [$this, 'preferencesList']);
            $this->add_hook('preferences_save', [$this, 'preferencesSave']);
            $includeAssets = true;
        } else {
            $includeAssets = false;
        }

        if ($includeAssets) {
            $this->includeAsset("xframework/assets/bower_components/moment/min/moment.min.js");
            $this->includeAsset("assets/styles/plugin.css");
            $this->includeAsset("assets/scripts/plugin.min.js");

            // add settings to js vars
            $countryCode = $this->rcmail->config->get("xweather_country_code", "US");
            $countryName = \XFramework\Geo::getCountryName($countryCode);
            $this->setJsVar("xweather_cache_minutes", $this->rcmail->config->get("xweather_cache_minutes", 15));
            $this->setJsVar("xweather_refresh_minutes", $this->rcmail->config->get("xweather_refresh_minutes", 15));
            $this->setJsVar("xweather_country_code", $countryCode);
            $this->setJsVar("xweather_country_name", $countryName ?: $countryCode);
            $this->setJsVar("xweather_city", $this->rcmail->config->get("xweather_city", "New York"));
            $this->setJsVar("xweather_metric", $this->rcmail->config->get("xweather_units", "metric") == "metric");
            $this->setJsVar("xweather_icons", $this->rcmail->config->get("xweather_icons", "moonlight"));
            $this->setJsVar("xweather_latitude", $this->rcmail->config->get("xweather_latitude"));
            $this->setJsVar("xweather_longitude", $this->rcmail->config->get("xweather_longitude"));
        }
    }

    public function xaisGetPluginDocumentation(array $arg): array
    {
        if ($arg['plugin'] != $this->ID) {
            return $arg;
        }

        $d = [];
        $d[] = 'Weather: display local weather in the Mail sidebar';
        $d[] = '';
        $d[] = 'Where: Mail → right sidebar → Weather; Settings → Preferences → Weather';
        $d[] = '';
        $d[] = 'Setup:';
        $d[] = '- Location → Find location by: Country and city or coordinates.';
        $d[] = '- Country / City or Latitude / Longitude fields.';
        $d[] = '- [Check location] - verifies whether the specified location can be found in the weather service';
        $d[] = '- Options:';
        $d[] = '  • Units — Metric / Imperial';
        $d[] = '  • Icons — 5 different icon sets with preview';
        $d[] = '- Display (toggle what to show on sidebar): Description, Pressure, Humidity, Clouds, Wind, Sunrise, Sunset.';
        $d[] = '';
        $d[] = 'Using it:';
        $d[] = '- The Weather box shows the selected location’s conditions based on your display options.';
        $d[] = '';
        $d[] = 'Notes:';
        $d[] = '- Sidebar boxes can be shown or hidden and reordered in Settings → Sidebar.';
        $d[] = '- Sidebar boxes can also be reordered by dragging and dropping directly on the Mail page.';

        $arg['text'] = implode("\n", $d);
        return $arg;
    }

    /**
     * Gets the weather data from open weather map. We're routing this via this class instead of connecting directly
     * to open weather map from js because https requests are only supported for paid accounts, so if rc runs on ssl
     * the customers will need paid accounts to access the weather data, otherwise they'll get ssl warnings.
     * Routing this via this class solves the problem because from the server we can connect over http without causing
     * any problems.
     */
    public function getWeatherData()
    {
        $locationType = $this->ajax->getString('locationType');
        $param1 = $this->ajax->getString('param1');
        $param2 = $this->ajax->getString('param2');
        $error = '';

        if ($data = $this->retrieveWeatherDataFromServer($locationType, $param1, $param2, $error)) {
            $this->ajax->success(self::ACTION_GET_WEATHER_DATA, $data);
        }

        $this->ajax->error(self::ACTION_GET_WEATHER_DATA, $error ?: 'Error connecting to weather API.');
    }

    /**
     * Retrieves weather data from the API endpoint.
     * @param string $locationType
     * @param string $param1 - latitude or country (depending on $locationType)
     * @param string $param2 - longitude or city (depending on $locationType)
     * @param string $errorMessage
     * @return false|mixed
     */
    private function retrieveWeatherDataFromServer(string $locationType, string $param1, string $param2,
                                                   string &$errorMessage)
    {
        $param1 = urlencode($param1);
        $param2 = urlencode($param2);

        try {
            $errorMessage = '';
            $url = "http://api.openweathermap.org/data/2.5/weather" .
                "?APPID=" . trim($this->rcmail->config->get("xweather_open_weather_map_key")) .
                "&lang=" . substr($this->rcmail->user->language, 0, 2) .
                "&units=" . ($this->rcmail->config->get("xweather_units", "metric") == "metric" ? "metric" : "imperial");

            if ($locationType == "coordinates") {
                $url .= "&lat=$param1&lon=$param2";
            } else {
                $url .= "&q=$param2,$param1";
            }

            if (!function_exists("curl_init")) {
                throw new Exception("cURL not available");
            }

            if (!($curl = curl_init())) {
                throw new Exception("Cannot initialize cURL");
            }

            try {
                curl_setopt_array($curl, [
                    CURLOPT_URL => $url,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 30,
                ]);

                $result = curl_exec($curl);

                if ($error = curl_error($curl)) {
                    throw new Exception("Cannot connect to Openweathermap API: $error");
                }

                if (!$result) {
                    throw new Exception("Empty cURL result");
                }
            } finally {
                curl_close($curl);
            }

            if (!($result = json_decode($result, true))) {
                throw new Exception("Cannot decode API json response.");
            }

            if (empty($result['sys']['country']) || empty($result['name'])) {
                $errorMessage = !empty($result['message']) ?
                    ucfirst($result['message']) : $this->gettext("location_not_found");
                return false;
            }

            $result['country'] = \XFramework\Geo::getCountryName($result['sys']['country']);

            if ($result['country'] == '-') {
                $result['country'] = $result['sys']['country'];
            }

            // openweathermap returns pressure in hPa regardless of the unit selected,
            // let's convert it to inHg if using imperial
            if ($this->rcmail->config->get("xweather_units") == 'imperial' &&
                !empty($result['main']['pressure']) &&
                is_numeric($result['main']['pressure'])
            ) {
                $result['main']['pressure'] = round($result['main']['pressure'] * 0.02953, 2);
            }

            return $result;

        } catch (Exception $e) {
            rcube::write_log("errors", "[xweather] " . $e->getMessage());
            return false;
        }
    }

    /**
     * Used for unit testing.
     *
     * @return array
     */
    public function getShowOptions(): array
    {
        return $this->showOptions;
    }

    public function getSidebarBox(): array
    {
        $this->rcmail->output->add_label("xweather.more");
        $table = new html_table(['cols' => 2, "id" => "xweather-options", "class" => "plain-table"]);
        $table->add([
            "class" => "key " . $this->rcmail->config->get("xweather_icons", "moonlight"),
            "id" => "xweather-icon"
        ], "");
        $table->add(["class" => "val", "id" => "xweather-temperature"], "");

        foreach ($this->showOptions as $option) {
            if ($this->rcmail->config->get("xweather_show_$option")) {
                $table->add(["class" => "key"], $this->gettext("xweather.$option"));
                $table->add(["class" => "val", "id" => "xweather-$option"], "");
            }
        }

        $html = html::div(["id" => "xweather-loader", "class" => "xspinner"]) .
            html::div(
                ["id" => "xweather-data"],
                html::div(["id" => "xweather-location", "class" => "content-container"], "") .
                html::div(["class" => "content-container"], $table->show()) .
                html::div(
                    ["id" => "xweather-links", "class" => "bottom-links"],
                    html::a(
                        [
                            "id" => "xweather-link-more",
                            "target" => "_blank",
                            "rel" => "noopener",
                        ],
                        $this->gettext("xweather.more_data")
                    )
                )
            ) .
            html::div(["id" => "xweather-error"], $this->gettext("loading_error"));

        return [
            "title" => rcube::Q($this->gettext("weather")) . html::span(["id" => "xweather-title-span"], ""),
            "html" => $html,
            "settingsUrl" => $this->appUrl,
        ];
    }

    /**
     * Adds the Weather settings sections.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesSectionsList(array $arg): array
    {
        // add the RSS feed section item
        $arg['list']['xweather'] = ['id' => 'xweather', 'section' => $this->gettext("xweather.weather")];
        return $arg;
    }

    /**
     * Creates the Weather user preferences page.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesList(array $arg): array
    {
        if ($arg['section'] != "xweather") {
            return $arg;
        }

        $skip = $this->rcmail->config->get("dont_override");
        $arg['blocks']['location']['name'] = $this->gettext("location");

        $this->getSettingSelect(
            $arg,
            "location",
            "location_type",
            [$this->gettext("xweather.country_and_city") => "city", $this->gettext("xweather.coordinates") => "coordinates"]
        );

        $countries = array_flip(\XFramework\Geo::getCountryArray(false));
        ksort($countries);

        $this->getSettingSelect($arg, "location", "country_code", $countries, "US");
        $this->getSettingInput($arg, "location", "city", "New York");

        $this->getSettingInput($arg, "location", "latitude", "40.730610");
        $this->getSettingInput(
            $arg,
            "location",
            "longitude",
            "-73.935242",
            html::div(['id' => 'coordinates-link'], html::a(
                ['href' => 'https://www.mapdevelopers.com/geocode_tool.php', 'target' => '_blank', 'rel' => 'noopener'],
                rcube::Q($this->gettext('xweather.coordinates_link'))
            ))
        );

        $arg['blocks']['location']['options']['check_button'] = [
            'title' => '',
            'content' => html::div(
                ['id' => 'city-check-container'],
                html::div([], html::tag('button',
                    ['type' => 'button', 'class' => 'button city-check-button', 'onclick' => 'xweather.checkCity()'],
                    rcube::Q($this->gettext('xweather.check_location'))
                )) .
                html::div(['class' => 'city-check-result city-check-progress xspinner'], '') .
                html::div(
                    ['class' => 'city-check-result city-check-success'],
                    html::span([], rcube::Q($this->gettext('location_found'))) . ' ' .
                    html::span(['id' => 'city-check-found'], '')
                ) .
                html::div(
                    ['class' => 'city-check-result city-check-error'],
                    rcube::Q($this->gettext('location_not_found'))
                )
            )
        ];

        $arg['blocks']['main']['name'] = $this->gettext("options");

        $iconArray = [];
        foreach ($this->icons as $val) {
            $iconArray[$this->gettext("icons_$val")] = $val;
        }

        if (!in_array("xweather_units", $skip)) {
            $this->getSettingSelect(
                $arg,
                "main",
                "units",
                [$this->gettext("metric") => "metric", $this->gettext("imperial") => "imperial"]
            );
        }

        if (!in_array("xweather_icons", $skip)) {
            $this->getSettingSelect(
                $arg,
                "main",
                "icons",
                $iconArray,
                null,
                "<script>$(document).ready(function() { xweather.iconPreview();});</script>",
                ["onchange" => "xweather.iconPreview()"]
            );

        }

        $iconPreview = "";
        foreach ($this->icons as $val) {
            $iconPreview .= html::span(
                ["class" => "xweather-icon-preview", "id" => "xweather-icon-preview-$val"],
                html::img(["src" => Utils::assetPath("plugins/xweather/assets/icons/$val/01d.png")]) .
                html::img(["src" => Utils::assetPath("plugins/xweather/assets/icons/$val/09d.png")]) .
                html::img(["src" => Utils::assetPath("plugins/xweather/assets/icons/$val/13n.png")])
            );
        }

        $arg['blocks']['main']['options']['check_button'] = [
            "title" => $this->gettext("xweather.icon_preview"),
            "content" => $iconPreview
        ];

        $arg['blocks']['display']['name'] = $this->gettext("display");

        foreach ($this->showOptions as $option) {
            if (!in_array("xweather_show_$option", $skip)) {
                $this->getSettingCheckbox(
                    $arg,
                    "display",
                    "show_$option"
                );
            }
        }

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
        if ($arg['section'] == "xweather") {
            $this->saveSetting($arg, "location_type");
            $this->saveSetting($arg, "country_code");
            $this->saveSetting($arg, "city");
            $this->saveSetting($arg, "units");
            $this->saveSetting($arg, "icons");
            $this->saveSetting($arg, "latitude");
            $this->saveSetting($arg, "longitude");

            foreach ($this->showOptions as $option) {
                $this->saveSetting($arg, "show_$option");
            }
        }

        return $arg;
    }
}