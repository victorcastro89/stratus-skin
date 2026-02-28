<?php
/**
 * Roundcube Plus Email Schedule plugin.
 *
 * Copyright 2018, Tecorama LLC.
 *
 * @license Commercial. See the file LICENSE for details.
 */

const INSTALL_PATH = __DIR__ . "/../../";
chdir(INSTALL_PATH);
$_SERVER['REMOTE_ADDR'] = "127.0.0.1";
$_GET['xemail-schedule-cron'] = "1";
require("index.php");