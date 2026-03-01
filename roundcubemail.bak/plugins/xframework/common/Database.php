<?php
namespace XFramework;

/**
 * Roundcube Plus Framework plugin.
 *
 * This class retrieves request data sent by Angular ajax requests. Angular json-encodes the parameters and php doesn't
 * decode them properly into $_POST, so we get the data and decode it manually.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once "Singleton.php";
require_once("DatabaseMysql.php");
require_once("DatabaseSqlite.php");
require_once("DatabasePostgres.php");

class Database
{
    use Singleton;
    static private $provider;

    /**
     * @throws \Exception
     */
    public static function instance($provider = null)
    {
        if (static::$instance && (!$provider || $provider == static::$provider)) {
            return static::$instance;
        }

        static::$provider = $provider ?? xrc()->get_dbh()->db_provider;

        switch (static::$provider) {
            case "mysql":
                return static::$instance = new DatabaseMysql();
            case "sqlite":
                return static::$instance = new DatabaseSqlite();
            case "postgres":
                return static::$instance = new DatabasePostgres();
            default:
                throw new \Exception("Error: This plugin does not support database provider $provider.");
        }
    }
}