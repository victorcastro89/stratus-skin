<?php
require_once("Data.php");
require_once("Database.php");
require_once("Input.php");
require_once("Ajax.php");
require_once("Format.php");
require_once("Html.php");

/**
 * Returns the rcube or rcmail instance, depending on whether it's called from caldav or not.
 */
if (!function_exists("xrc")) {
    function xrc() {
        return defined("XCALENDAR_CALDAV") ? rcube::get_instance() : rcmail::get_instance();
    }
}

if (!function_exists("xdata")) {
    function xdata(): \XFramework\Data {
        return \XFramework\Data::instance();
    }
}

if (!function_exists("xdb")) {
    function xdb($provider = null) {
        try {
            return \XFramework\Database::instance($provider);
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
}

if (!function_exists("xformat")) {
    function xformat(): \XFramework\Format {
        return \XFramework\Format::instance();
    }
}

if (!function_exists("xhtml")) {
    function xhtml(): \XFramework\Html {
        return \XFramework\Html::instance();
    }
}

if (!function_exists("xinput")) {
    function xinput(): \XFramework\Input {
        return \XFramework\Input::instance();
    }
}

if (!function_exists("xajax")) {
    function xajax(): \XFramework\Ajax {
        return \XFramework\Ajax::instance();
    }
}

if (!function_exists("xget")) {
    function xget(string $key, bool $skipTokenCheck = false) {
        return \XFramework\Input::instance()->get($key, $skipTokenCheck);
    }
}

/**
 * Debug helpers
 */
if (!function_exists("xdebug_var_dump")) {
    function xdebug_var_dump($var) {
        var_dump($var);
    }
}

if (!function_exists("dd")) {
    function dd($var) {
        xdebug_var_dump($var);
        exit;
    }
}

if (!function_exists("x")) {
    function x($var) {
        xdebug_var_dump($var);
    }
}
