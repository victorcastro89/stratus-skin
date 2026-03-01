<?php
/**
 * Roundcube Plus Compose plugin.
 *
 * Copyright 2016, Tecorama LLC
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../xframework/common/Plugin.php");

class xcompose extends XFramework\Plugin
{
    protected bool $hasConfig = true;
    private array $composeFontSizes = ["8pt", "9pt", "10pt", "11pt", "12pt", "13pt", "14pt"];

    /**
     * Initializes the plugin.
     */
    public function initialize()
    {
        $this->add_hook("startup", [$this, "startup"]);
        $this->add_hook("preferences_list", [$this, "preferencesList"]);
        $this->add_hook("preferences_save", [$this, "preferencesSave"]);
    }

    public function startup()
    {
        if ($this->rcmail->task != "mail" || $this->rcmail->action != "compose") {
            return;
        }

        $styles = [];

        $size = $this->rcmail->config->get("xcompose_plain_font_size", "9pt");

        if (in_array($size, $this->composeFontSizes) && $size != "9pt") {
            $styles[] = "font-size:$size;";
        }

        if ($family = $this->rcmail->config->get("xcompose_plain_font_family")) {
             $styles[] = "font-family:$family;";
        }

        $this->addInlineStyle(rcube::Q("#composebody{" . implode(";", $styles) . "}"));
    }

    /**
     * Displays plugin preferences.
     *
     * @param array $arg
     * @return array
          *@global type $RCMAIL
     */
    function preferencesList(array $arg): array
    {
        if ($arg['section'] == "compose") {
            $this->getSettingSelect(
                $arg,
                "main",
                "plain_font_size",
                array_combine($this->composeFontSizes, $this->composeFontSizes), // add keys that are same as values
                "9pt"
            );
        }

        return $arg;
    }

    /**
     * Saves plugin preferences.
     *
     * @param array $arg
     * @return array
     */
    function preferencesSave(array $arg): array
    {
        if ($arg['section'] == "compose") {
            $this->saveSetting($arg, "plain_font_size");
        }

        return $arg;
    }
}