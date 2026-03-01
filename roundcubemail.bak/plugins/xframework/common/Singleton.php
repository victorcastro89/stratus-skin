<?php
namespace XFramework;

/**
 * Roundcube Plus Framework plugin.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 * @codeCoverageIgnore
 */

trait Singleton {
    protected static $instance;

    public static function instance($parameters = null)
    {
        return static::$instance ?? static::$instance = new static($parameters);
    }

    public static function hasInstance(): bool
    {
        return (bool)static::$instance;
    }

    public static function deleteInstance()
    {
        if (static::hasInstance()) {
            static::$instance = null;
        }
    }

    final public function __clone() {} // restrict cloning
    final public function __sleep() {} // restrict serializing
    final public function __wakeup() {} // restrict unserializing
}