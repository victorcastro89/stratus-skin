<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2024, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

namespace XAi\Classes;

class Session
{
    public function __construct()
    {
        if (!isset($_SESSION['xai'])) {
            $_SESSION['xai'] = [];
        }
    }

    /**
     * Checks if a key exists in a specific session section.
     *
     * @param string $section The session section.
     * @param string $key The key to check.
     * @return bool True if the key exists, false otherwise.
     */
    public function has(string $section, string $key): bool
    {
        return isset($_SESSION['xai'][$section][$key]);
    }

    /**
     * Sets a value in a specific session section and key.
     *
     * @param string $section The session section.
     * @param string $key The key to set.
     * @param mixed $value The value to store.
     */
    public function set(string $section, string $key, $value): void
    {
        if (!isset($_SESSION['xai'][$section]) || !is_array($_SESSION['xai'][$section])) {
            $_SESSION['xai'][$section] = [];
        }

        $_SESSION['xai'][$section][$key] = $value;
    }


    /**
     * Retrieves a value from a specific session section and key.
     *
     * @param string $section The session section.
     * @param string $key The key to retrieve.
     * @return mixed|null The value or null if not found.
     */
    public function get(string $section, string $key): mixed
    {
        return $_SESSION['xai'][$section][$key] ?? null;
    }


    /**
     * Checks if a session section exists.
     *
     * @param string $section The session section.
     * @return bool True if the section exists, false otherwise.
     */
    public function hasSection(string $section): bool
    {
        return isset($_SESSION['xai'][$section]);
    }

    /**
     * Retrieves all data from a specific session section.
     *
     * @param string $section The session section.
     * @return array|null The section data or null if not found.
     */
    public function getSection(string $section): ?array
    {
        return $_SESSION['xai'][$section] ?? null;
    }

    /**
     * Sets data for a specific session section.
     *
     * @param string $section The session section.
     * @param array $data The data to store.
     */
    public function setSection(string $section, array $data): void
    {
        $_SESSION['xai'][$section] = $data;
    }
}