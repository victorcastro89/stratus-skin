<?php
/**
 * Roundcube Plus Skin plugin.
 *
 * Copyright 2019, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../xframework/common/Plugin.php");

class xskin extends XFramework\Plugin
{
    protected bool $settings = false;
    private string $lookAndFeelUrl = "?_task=settings&_action=preferences&_section=xskin";
    private array $disablePluginsConfig = [];

    public function initialize()
    {
        $this->addSkinInterfaceMenuItem();
        $this->addLanguageInterfaceMenuItem();

        // include scripts (doing it here so the quick skin change works in elastic/larry)
        $this->includeAsset("assets/scripts/xskin.min.js");

        // return if we're not running a Roundcube Plus skin (but add custom css so it applies to all skins)
        if (!$this->rcpSkin) {
            $this->includeCustomCss();
            return;
        }

        if (!$this->elastic) {
            $this->disablePluginsConfig = $this->rcmail->config->get("disable_plugins_on_mobile", []);
        }

        // add hooks
        $this->add_hook("startup", [$this, "startup"]);
        $this->add_hook("config_get", [$this, $this->elastic ? "elasticGetConfig" : "larryGetConfig"]);
        $this->add_hook("render_page", [$this, $this->elastic ? "elasticRenderPage" : "larryRenderPage"]);

        if ($this->rcmail->task == "settings") {
            $this->add_hook('preferences_sections_list', [$this, 'preferencesSectionsList']);
            $this->add_hook("preferences_list", [$this, "preferencesList"]);
            $this->add_hook("preferences_save", [$this, "preferencesSave"]);
        }

        // include assets
        $this->includeAsset("assets/scripts/xskin.min.js");
        $this->includeAsset("assets/styles/styles.css");
        $this->includeSkinConfig();

        if ($this->skinBase == "larry") {
            $this->larrySetSkin();
            $this->addDisableMobileInterfaceMenuItem();

            if ($this->rcmail->output->get_env("xskin_type") == "mobile") {
                $this->includeAsset("assets/scripts/hammer.min.js");
                $this->includeAsset("assets/scripts/jquery.hammer.js");
                $this->includeAsset("assets/scripts/larry_mobile.min.js");
                $this->includeAsset("assets/styles/larry_mobile.css");
                $this->includeAsset("../../skins/$this->skin/assets/styles/mobile.css");
            } else {
                $this->includeAsset("assets/scripts/larry_desktop.min.js");
                $this->includeAsset("assets/styles/larry_desktop.css");
                $this->includeAsset("../../skins/$this->skin/assets/styles/desktop.css");
            }
        } else {
            $this->includeAsset("../../skins/$this->skin/assets/styles/styles.css");
            $this->includeAsset("../../skins/$this->skin/assets/scripts/scripts.min.js");
        }

        // removed the cairo font (included with previous versions) because of line spacing issues - fix any old font settings
        if ($this->rcmail->config->get("xskin_font_family_$this->skin") == "cairo") {
            $this->rcmail->config->set("xskin_font_family_$this->skin", "noto-sans");
        }

        // if remote assets are disabled, set the font to roboto (loaded from elastic) and don't load fonts from google
        if ($this->rcmail->config->get("disable_remote_skin_fonts")) {
            // set these to a value that doesn't exist in _options.scss so it won't set the font
            $this->rcmail->config->set("xskin_font_family", "inherited-local");
            $this->rcmail->config->set("xskin_font_family_$this->skin", "inherited-local");
        } else {
            $this->include_stylesheet("https://fonts.googleapis.com/css2?family=Roboto&display=block");
            $this->include_stylesheet("https://fonts.googleapis.com/css2?family=Noto+Sans&display=block");
            $this->include_stylesheet("https://fonts.googleapis.com/css2?family=Ubuntu&display=block");
            $this->include_stylesheet("https://fonts.googleapis.com/css2?family=Montserrat+Alternates&display=block");
            $this->include_stylesheet("https://fonts.googleapis.com/css2?family=Sarala&display=block");
            $this->include_stylesheet("https://fonts.googleapis.com/css2?family=Quattrocento&display=block");
            $this->include_stylesheet("https://fonts.googleapis.com/css2?family=Merienda&display=block");
        }

        $this->ensureSkinLogo();
        $this->setPreviewBranding();
        $this->includeCustomCss();
    }

    public function startup()
    {
        if ($this->elastic) {
            // add labels to env (for creating the mobile interface in js)
            $this->rcmail->output->add_label("login");
        } else {
            // litecube is the only skin not using font icons in desktop; but it does use it on mobile
            if ($this->skin == "litecube" && $this->rcmail->output->get_env("xmobile")) {
                $this->rcmail->config->set("xlarry_font_icons", true);
            }

            // add larry-based classes to body
            $bodyClasses = ["x" . $this->rcmail->output->get_env("xskin_type")];
            $this->rcmail->config->get("xlarry_font_icons") && ($bodyClasses[] = "xlarry-font-icons");
            $this->rcmail->config->get("xlarry_square_ui") && ($bodyClasses[] = "xlarry-square-ui");
            $this->rcmail->config->get("xlarry_light_ui") && ($bodyClasses[] = "xlarry-light-ui");
            $this->rcmail->task == "logout" && ($bodyClasses[] = "login-page");
            $this->addBodyClass(implode(" ", $bodyClasses));

            // add labels to env (for creating the mobile interface in js)
            $this->rcmail->output->add_label("login", "folders", "search", "attachment", "section", "options");

            // disable composing in html on mobile devices unless config option set to allow
            if ($this->rcmail->output->get_env("xmobile") && !$this->rcmail->config->get("allow_mobile_html_composing")) {
                global $CONFIG;
                $CONFIG['htmleditor'] = false;
            }
        }

        $this->rcmail->output->set_env("rcp_skin", $this->rcpSkin);
        $this->addClasses();
    }

    /**
     * Hook retrieving config options (including user settings).
     */
    public function elasticGetConfig($arg)
    {
        // Substitute the skin name retrieved from the config file with "elastic" for the plugins that treat
        // elastic-based skins as "elastic."
        if (empty($arg['name']) || $arg['name'] != "skin" || !array_key_exists(str_replace("_elastic", "", $arg['result']), $this->getSkins())) {
            return $arg;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);

        // this is a call from the rc core, let's hope they fix this
        if (!empty($trace[3]['class']) && $trace[3]['class'] == "jqueryui") {
            $arg['result'] = "elastic";
        }

        // check if the calling file is in the list of plugins to fix or it's a unit test and set the skin to elastic
        $fixPlugins = $this->rcmail->config->get("fix_plugins", []);
        if (!empty($trace[3]['file']) &&
            (in_array(basename(dirname($trace[3]['file'])), $fixPlugins) || basename($trace[3]['file']) == "TestCase.php")
        ) {
            $arg['result'] = "elastic";
        }

        return $arg;
    }

    function larryGetConfig($arg)
    {
        if ($this->rcmail->output->get_env("xskin_type") == "mobile") {
            // disable unwanted plugins on mobile devices
            $disablePlugins = ["preview_pane", "google_ads", "threecol"];

            if (!empty($this->larryDisabledPluginsConfig) && is_array($this->larryDisabledPluginsConfig)) {
                $disablePlugins = array_merge($disablePlugins, $this->larryDisabledPluginsConfig);
            }

            foreach ($disablePlugins as $val) {
                if (isset($arg['name']) && strpos($arg['name'], $val) !== false) {
                    $arg['result'] = false;
                    return $arg;
                }
            }

            // set the layout to list on mobile devices so it can be displayed properly
            // IMPORTANT: we have to unset $_GET['_layout'] because on RC 1.4 setting $arg here results in adding
            // the new layout value to GET, which is then picked up and saved into the database by
            // program/steps/mail/list.inc. So the 'list' value we set here for mobile is then applied to desktop
            // as well. Unsetting GET fixes the issue.
            if (isset($arg['name']) && $arg['name'] == "layout") {
                $arg['result'] = "list";
                unset($_GET['_layout']);
                return $arg;
            }
        }

        // Substitute the skin name retrieved from the config file with "larry" for the plugins that treat larry-based
        // skins as "classic."
        if (empty($arg['name']) || $arg['name'] != "skin" || !$this->isRcpSkin($arg['result'])) {
            return $arg;
        }

        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);

        // check if the calling file is in the list of plugins to fix or it's a unit test and set the skin to larry
        $fixPlugins = $this->rcmail->config->get("fix_plugins", []);
        if (!empty($trace[3]['file']) &&
            (in_array(basename(dirname($trace[3]['file'])), $fixPlugins) || basename($trace[3]['file']) == "TestCase.php")
        ) {
            $arg['result'] = "larry";
        }

        return $arg;
    }

    public function elasticRenderPage($arg)
    {
        $this->addLoginRcpBranding($arg);
        return $arg;
    }

    public function larryRenderPage($arg)
    {
        // check if it's an error page
        if (strpos($arg['content'], "uibox centerbox errorbox")) {
            return;
        }

        $this->addLoginRcpBranding($arg);

        if ($this->rcmail->task != "login" && $this->rcmail->task != "logout") {
            $this->larryModifyPageHtml($arg);
        }

        return $arg;
    }

    /**
     * Modifies the html of the non-login Roundcube pages.
     * Unit tested via renderPage()
     *
     * @param array $arg
     * @codeCoverageIgnore
     */
    protected function larryModifyPageHtml(array &$arg)
    {
        // check if it's an error page
        if (strpos($arg['content'], "uibox centerbox errorbox")) {
            return;
        }

        // if using a desktop skin on mobile devices after clicked "use desktop skin" show a link to revert to
        // mobile skin in the top bar
        if (isset($_COOKIE['rcs_disable_mobile_skin'])) {
            $this->replace(
                '<div class="topleft">',
                '<div class="topleft">'.
                html::a(
                    [
                        "class" => "enable-mobile-skin",
                        "href" => "javascript:void(0)",
                        "onclick" => "xskin.enableMobileSkin()",
                    ],
                    rcube::Q($this->rcmail->gettext("xskin.enable_mobile_skin"))
                ),
                $arg['content']
            );
        }

        // add the toolbar-bg element that is used by alpha
        $this->replace(
            '<div id="mainscreencontent',
            '<div id="toolbar-bg"></div><div id="mainscreencontent',
            $arg['content']
        );
    }

    /**
     * Adds the skin config files from <skin>/config.inc.php to the main config, if the file exists.
     */
    protected function includeSkinConfig()
    {
        // include the default setting values from the skin's meta.json in the config
        // values from meta.json get automatically included in the config, but at the same time they're included
        // in dontoverride, which is not good because we want admins to be able to include/exclude it from dontoverride
        // so we set the default values in meta as 'xskin_default_*' and here we translate them to 'xskin_*'
        // this way the values 'xskin_*' can be used normally in dontoverride
        foreach ($this->rcmail->config->all() as $key => $val) {
            if (strpos($key, "xskin_default") === 0) {
                $this->rcmail->config->set("xskin" . substr($key, 13), $val);
            }
        }

        $file = RCUBE_INSTALL_PATH . "skins/" . $this->skin . "/config.inc.php";

        if (!file_exists($file)) {
            return;
        }

        $config = [];
        @include($file);

        if (is_array($config)) {
            foreach ($config as $key => $val) {
                $this->rcmail->config->set($key, $val);
            }
        }
    }

    /**
     * Sets the current skin and color and fills in the correct properties for the desktop, tablet and phone skin.
     * Larry only.
     */
    public function larrySetSkin()
    {
        // check if already set
        if ($this->rcmail->output->get_env("xskin")) {
            return;
        }

        if ($this->rcmail->output->get_env("xphone")) {
            $skinType = "mobile";
        } else if ($this->rcmail->output->get_env("xtablet")) {
            $skinType = "mobile";
        } else {
            $skinType = "desktop";
        }

        // litecube-f doesn't support mobile, set the device to desktop to avoid errors
        // also set device to desktop if mobile interface is disabled in config
        if ($this->skin == "litecube-f" || $this->rcmail->config->get("disable_mobile_interface")) {
            $this->setDevice(true);
            $skinType = "desktop";
        }

        // change the skin in the environment
        if (isset($GLOBALS['OUTPUT']) && method_exists($GLOBALS['OUTPUT'], "set_skin")) {
            $GLOBALS['OUTPUT']->set_skin($this->skin);
        }

        // if running a mobile skin, remove the apps menu before it gets added using js
        if ($skinType != "desktop") {
            $this->setJsVar("appsMenu", "");
        }

        // sent environment variables
        $this->rcmail->output->set_env("xskin", $this->skin);
        $this->rcmail->output->set_env("xskin_type", $skinType);
        $this->rcmail->output->set_env("rcp_skin", $this->rcpSkin);
    }

    protected function addLanguageInterfaceMenuItem()
    {
        if ($this->getDontOverride("language") || $this->rcmail->config->get("disable_menu_languages")) {
            return;
        }

        $languages = $this->rcmail->list_languages();
        asort($languages);

        $select = new html_select(["onchange" => "xskin.quickLanguageChange()", "class"=>"form-control", "name" => "quick-language-change"]);
        $select->add(array_values($languages), array_keys($languages));

        $this->addToInterfaceMenu(
            "quick-language-change",
            html::div(
                ["id" => "quick-language-change", "class" => "section"],
                html::div(["class" => "section-title"], $this->gettext("language")) . $select->show($this->rcmail->user->language)
            )
        );
    }

    public function preferencesSectionsList(array $arg): array
    {
        $arg['list']['xskin'] = ['id' => 'xskin', 'section' => $this->gettext("skin_look_and_feel")];
        return $arg;
    }

    /**
     * Replaces the preference skin selection with a dialog-based selection that allows specifying separate desktop
     * table and phone skins.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesList(array $arg): array
    {
        if ($arg['section'] != 'xskin' || $this->getDontOverride("look_and_feel")) {
            return $arg;
        }

        $arg['blocks']['skin_look_and_feel']['name'] = $this->gettext("skin_look_and_feel");
        $skin = $this->skin;

        if (!$this->getDontOverride("xskin_icons") && ($this->elastic || $this->rcmail->config->get("xlarry_font_icons"))) {
            $this->getSettingSelect(
                $arg,
                "skin_look_and_feel",
                "icons_$skin",
                [
                    $this->gettext("icons_solid") => "solid",
                    $this->gettext("icons_traditional") => "traditional",
                    $this->gettext("icons_outlined") => "outlined",
                    $this->gettext("icons_material") => "material",
                    $this->gettext("icons_cartoon") => "cartoon",
                ],
                $this->getCurrentIcons(),
                false,
                ["onchange" => "xskin.applySetting(this, 'xicons', 'html')"],
                "icons"
            );
        }

        if (!$this->getDontOverride("xskin_list_icons") && ($this->elastic || $this->rcmail->config->get("xlarry_font_icons"))) {
            $this->getSettingCheckbox(
                $arg,
                "skin_look_and_feel",
                "list_icons_$skin",
                $this->getCurrentListIcons(),
                false,
                ["onchange" => "xskin.applySetting(this, 'xlist-icons', 'body')"],
                "list_icons"
            );
        }

        // larry-based skins don't have icons on buttons, disabling this option for larry
        if (!$this->getDontOverride("xskin_button_icons") && $this->elastic) {
            $this->getSettingCheckbox(
                $arg,
                "skin_look_and_feel",
                "button_icons_$skin",
                $this->getCurrentButtonIcons(),
                false,
                ["onchange" => "xskin.applySetting(this, 'xbutton-icons', 'body')"],
                "button_icons"
            );
        }

        // if remote assets are disabled, don't give the users the choice of a font because they load from google
        if (!$this->getDontOverride("xskin_font_family") && !$this->rcmail->config->get("disable_remote_skin_fonts")) {
            $fonts = [];
            $fontList = ["Arial", "Courier", "Merienda", "Montserrat", "Noto Sans", "Quattrocento", "Sarala", "Roboto", "Times", "Ubuntu"];

            foreach ($fontList as $font) {
                $fonts[$font] = strtolower(str_replace(" ", "-", $font));
            }

            ksort($fonts);

            $this->getSettingSelect(
                $arg,
                "skin_look_and_feel",
                "font_family_$skin",
                $fonts,
                $this->getCurrentFontFamily(),
                false,
                ["onchange" => "xskin.applySetting(this, 'xfont-family', 'html')"],
                "font_family"
            );
        }

        if (!$this->getDontOverride("xskin_font_size")) {
            $this->getSettingSelect(
                $arg,
                "skin_look_and_feel",
                "font_size_$skin",
                [
                    $this->gettext("font_size_xs") => "xs",
                    $this->gettext("font_size_s") => "s",
                    $this->gettext("font_size_n") => "n",
                    $this->gettext("font_size_l") => "l",
                    $this->gettext("font_size_xl") => "xl",
                ],
                $this->getCurrentFontSize(),
                false,
                ["onchange" => "xskin.applySetting(this, 'xfont-size', 'html')"],
                "font_size"
            );
        }

        if (!$this->getDontOverride("xskin_thick_font")) {
            $this->getSettingCheckbox(
                $arg,
                "skin_look_and_feel",
                "thick_font_$skin",
                $this->getCurrentThickFont(),
                false,
                ["onchange" => "xskin.applySetting(this, 'xthick-font', 'html')"],
                "thick_font"
            );
        }

        if (!$this->getDontOverride("xskin_color")) {
            $colorBoxes = "";
            foreach ($this->rcmail->config->get("xskin_colors") as $color) {
                $colorBoxes .= html::a(
                    [
                        "class" => "color-box",
                        "onclick" => "xskin.applySetting('#xcolor-input', 'xcolor', 'body', '$color')",
                        "style" => "background:#$color !important",
                    ],
                    " "
                );
            }

            $this->addSetting(
                $arg,
                "skin_look_and_feel",
                "color_$skin",
                $colorBoxes . "<input id='xcolor-input' type='hidden' name='color_$skin' value='" .
                $this->getCurrentColor() . "' />",
                "",
                "color"
            );
        }

        $arg['blocks']["skin_look_and_feel"]['options']["save_hint"] = [
            "title" => "",
            "content" => "<span class='xsave-hint'>" . rcube::Q($this->gettext("save_hint")) . "</span>" .
                "<script>xskin.updateIFrameClasses();</script>"
        ];

        return $arg;
    }

    /**
     * Saves the skin selection preferences.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesSave(array $arg): array
    {
        if ($arg['section'] == "xskin") {
            $this->saveSetting($arg, "icons_$this->skin");
            $this->saveSetting($arg, "list_icons_$this->skin");
            $this->saveSetting($arg, "button_icons_$this->skin");
            $this->saveSetting($arg, "font_family_$this->skin");
            $this->saveSetting($arg, "font_size_$this->skin");
            $this->saveSetting($arg, "thick_font_$this->skin");
            $this->saveSetting($arg, "color_$this->skin");
            $this->addClasses();
        }

        return $arg;
    }

    public function addSkinInterfaceMenuItem()
    {
        if ($this->getDontOverride("skin") || $this->rcmail->config->get("disable_menu_skins")) {
            return;
        }

        if ($html = $this->getShortcutSkinsHtml()) {
            $this->addToInterfaceMenu(
                "skin-options",
                html::div(
                    ["id" => "xskin-options", "class" => "section"],
                    html::div(["class" => "section-title"], $this->gettext("skin")) . $html
                )
            );
        }
    }

    protected function getShortcutSkinsHtml()
    {
        if (count($this->getInstalledSkins()) <= 1 ||
            $this->getDontOverride("skin") ||
            $this->rcmail->config->get("disable_menu_skins")
        ) {
            return false;
        }

        $select = new html_select(["onchange" => "xskin.quickSkinChange()", "class" => "form-control", "name" => "quick-skin-change"]);
        $added = 0;

        foreach ($this->getInstalledSkins() as $installedSkin) {
            if (array_key_exists($installedSkin, $this->skins)) {
                $select->add($this->skins[$installedSkin], $installedSkin);
                $added++;
            } else if ($installedSkin == "elastic" || $installedSkin == "larry") {
                $select->add(ucfirst($installedSkin), $installedSkin);
                $added++;
            }
        }

        if ($added > 1) {
            if ($this->rcpSkin) {
                $lookAndFeelHtml = html::div(
                    ["id" => "look-and-feel-shortcut"],
                    html::a(
                        ["href" => $this->lookAndFeelUrl, "class" => "btn btn-sm btn-success"],
                        rcube::Q($this->gettext("skin_look_and_feel_shortcut"))
                    )
                );
            } else {
                $lookAndFeelHtml = "";
            }

            return html::div(["id" => "xshortcut-skins", "class" => "shortcut-item"], $select->show($this->skin)) . $lookAndFeelHtml;
        }

        return false;
    }

    protected function getCurrentColor()
    {
        if ($this->getDontOverride("xskin_color")) {
            return $this->rcmail->config->get("xskin_color");
        }

        $color = $this->rcmail->config->get(
            "xskin_color_" . $this->skin,
            $this->rcmail->config->get("xskin_color", "")
        );

        // have to do strlen because in_array thinks that "0" == "000000"
        $colors = $this->rcmail->config->get("xskin_colors");

        if (strlen($color) != 6 || !is_array($colors) || !in_array($color, $colors)) {
            $color = $this->rcmail->config->get("xskin_color");
        }

        return $color;
    }

    protected function getCurrentFontFamily()
    {
        if ($this->getDontOverride("xskin_font_family")) {
            return $this->rcmail->config->get("xskin_font_family");
        }

        return $this->rcmail->config->get("xskin_font_family_$this->skin", $this->rcmail->config->get("xskin_font_family"));
    }

    protected function getCurrentFontSize()
    {
        if ($this->getDontOverride("xskin_font_size")) {
            return $this->rcmail->config->get("xskin_font_size");
        }

        return $this->rcmail->config->get("xskin_font_size_$this->skin", $this->rcmail->config->get("xskin_font_size"));
    }

    protected function getCurrentThickFont()
    {
        if ($this->getDontOverride("xskin_thick_font")) {
            return $this->rcmail->config->get("xskin_thick_font");
        }

        return $this->rcmail->config->get("xskin_thick_font_$this->skin", $this->rcmail->config->get("xskin_thick_font"));
    }

    protected function getCurrentIcons()
    {
        if ($this->getDontOverride("xskin_icons")) {
            return $this->rcmail->config->get("xskin_icons");
        }

        return $this->rcmail->config->get("xskin_icons_$this->skin", $this->rcmail->config->get("xskin_icons"));
    }

    protected function getCurrentListIcons()
    {
        if ($this->getDontOverride("xskin_list_icons")) {
            return $this->rcmail->config->get("xskin_list_icons");
        }

        return $this->rcmail->config->get("xskin_list_icons_$this->skin", $this->rcmail->config->get("xskin_list_icons"));
    }

    protected function getCurrentButtonIcons()
    {
        if ($this->getDontOverride("xskin_button_icons")) {
            return $this->rcmail->config->get("xskin_button_icons");
        }

        return $this->rcmail->config->get("xskin_button_icons_$this->skin", $this->rcmail->config->get("xskin_button_icons"));
    }

    protected function addClasses()
    {
        // add html classes
        $classes = [
            "xfont-family-" . $this->getCurrentFontFamily(),
            "xfont-size-" . $this->getCurrentFontSize(),
            "xthick-font-" . ($this->getCurrentThickFont() ? "yes" : "no"),
        ];

        $this->addHtmlClass(implode(" ", $classes));

        // add body classes
        $classes = [
            "{$this->rcmail->task}-page",
            "xskin",
            "skin-" . $this->skin,
            "xcolor-" . $this->getCurrentColor(),
            "xlist-icons-" . ($this->getCurrentListIcons() ? "yes" : "no"),
            "xbutton-icons-" . ($this->getCurrentButtonIcons() ? "yes" : "no"),
        ];

        // add body classes from skin's meta.json
        $classes[] = $this->rcmail->config->get("xbody-classes", "");

        if ($this->rcmail->task == "logout") {
            $classes[] = "login-page";
        }

        $this->addBodyClass(implode(" ", $classes));

        // this needs to be added to html so the icon() scss function works properly
        $this->addHtmlClass("xicons-" . $this->getCurrentIcons());
    }

    /**
     * Adds the Roundcube Plus icon to the login page.
     *
     * @param $arg
     */
    protected function addLoginRcpBranding(&$arg)
    {
        if ($this->rcmail->task != "login" && $this->rcmail->task != "logout") {
            return;
        }

        if (!$this->rcmail->config->get("remove_vendor_branding")) {
            $this->replace(
                "</body>",
                html::a(
                    [
                        "id" => "vendor-branding",
                        "href" => "https://roundcubeplus.com",
                        "target" => "_blank",
                        "rel" => "noopener",
                        "title" => "More Roundcube skins and plugins at roundcubeplus.com",
                    ],
                    html::span([], "+")
                ).
                "</body>",
                $arg['content']
            );
        }
    }

    /**
     * Performs string replacement with error checking. If the string to search for cannot be found it exits with an
     * error message.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @param string|int $errorNumber
     * @return int
     * @codeCoverageIgnore
     */
    protected function replace(string $search, string $replace, string &$subject, $errorNumber = ""): int
    {
        $count = 0;
        $subject = str_replace($search, $replace, $subject, $count);

        if ($errorNumber && !$count) {
            exit(
                "<p>ERROR $errorNumber: Roundcube is not running properly or it is not compatible with the Roundcube ".
                "Plus skin. Disable the xskin plugin in config.inc.php and refresh this page to check for errors.</p>"
            );
        }

        return $count;
    }

    protected function setPreviewBranding()
    {
        // set the preview background logo (loaded using js in [skin]/watermark.html)
        $this->rcmail->output->set_env(
            "xwatermark",
            $this->rcmail->config->get("preview_branding", "../../plugins/xskin/assets/images/watermark.png")
        );

    }

    protected function includeCustomCss()
    {
        // include the custom css if specified in the xskin config
        if ($overwriteCss = $this->rcmail->config->get("overwrite_css")) {
            $this->includeAsset($overwriteCss);
        }

        // include the custom css if specified in skin json
        if ($customCss = $this->rcmail->config->get("custom_css")) {
            $this->includeAsset($customCss);
        }
    }

    /**
     * Larry only.
     */
    protected function addDisableMobileInterfaceMenuItem()
    {
        // create the 'use mobile skin' button (added only if user switched to desktop skin on mobile)
        $skinType = $this->rcmail->output->get_env("xskin_type");

        if ($skinType == "desktop" && isset($_COOKIE['rcs_disable_mobile_skin'])) {
            $this->addToInterfaceMenu(
                "enable-mobile-skin",
                html::div(
                    ["id" => "enable-mobile-skin", "class" => "section"],
                    "<input type='button' class='button mainaction' onclick='xskin.enableMobileSkin()' value='" .
                    rcube::Q($this->rcmail->gettext("xskin.enable_mobile_skin")) . "' />"

                )
            );
        } else if ($skinType != "desktop") {
            $this->addToInterfaceMenu(
                "disable-mobile-skin",
                html::div(
                    ["id" => "disable-mobile-skin", "class" => "section"],
                    "<input type='button' class='button mainaction' onclick='xskin.disableMobileSkin()' value='" .
                    rcube::Q($this->rcmail->gettext("xskin.disable_mobile_skin")) . "' />"
                )
            );
        }
    }

    /**
     * Sets the default logo images to RC+ if they're not set up otherwise in the config.
     */
    protected function ensureSkinLogo()
    {
        if (empty($this->rcmail->config->get("skin_logo"))) {
            $this->rcmail->config->set(
                "skin_logo",
                [
                    "*" => "skins/$this->skin/assets/images/logo_header.png",
                    "[print]" => "skins/$this->skin/assets/images/logo_print.png",
                    "login" => false,
                ]
            );
        }
    }
}