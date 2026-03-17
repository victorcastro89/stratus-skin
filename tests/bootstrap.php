<?php

/**
 * PHPUnit bootstrap for Stratus skin plugin tests.
 *
 * Bootstraps the Roundcube framework (available under roundcubemail/) so that
 * rcmail, rcube_plugin, rcube_utils, etc. are all defined before any test runs.
 */

if (php_sapi_name() !== 'cli') {
    die("Tests must be run from the CLI.");
}

if (!defined('INSTALL_PATH')) {
    define('INSTALL_PATH', realpath(__DIR__ . '/../roundcubemail') . '/');
}

define('ROUNDCUBE_TEST_MODE', true);

// Point to the RC test helper directory so plugin load paths resolve correctly.
define('TESTS_DIR', realpath(__DIR__ . '/../roundcubemail/tests') . '/');

require_once INSTALL_PATH . 'program/include/iniset.php';

// Boot a minimal RC instance in test mode (no DB, no session).
rcmail::get_instance(0, 'test')->config->set('devel_mode', false);

// Extend the include path so plugins can locate each other.
$include_path = ini_get('include_path') . PATH_SEPARATOR . realpath(__DIR__ . '/..');
set_include_path($include_path);
