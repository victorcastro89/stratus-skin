<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

namespace XAi\Services;

use rcmail;
use rcube_config;
use XFramework\DatabaseGeneric;
use XAi\Providers\Provider;
use XAi\Classes\Session;

class Service implements ServiceInterface
{
    protected rcmail $rcmail;
    protected rcube_config $config;
    protected DatabaseGeneric $db;
    protected Provider $provider;
    protected Session $session;
    protected array $settings = [];
    protected array $languages = [];

    public function __construct(Provider $provider, Session $session, rcmail $rcmail, DatabaseGeneric $db)
    {
        $this->provider = $provider;
        $this->session = $session;
        $this->rcmail = $rcmail;
        $this->db = $db;

        // load the list of languages from RC localization
        $rcube_languages = [];
        @include(RCUBE_LOCALIZATION_DIR . 'index.inc');
        $this->languages = $rcube_languages;
    }

    /**
     * Returns the array of the settings that will be displayed on the settings page.
     *
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * Returns the currently selected user language code.
     *
     * @return string
     */
    public function getUserLanguageCode(): string
    {
        $code = $this->rcmail->config->get('language');
        return isset($this->languages[$code]) ? $code : 'en_US';
    }

    /**
     * Returns the currently selected user language name.
     *
     * @return string
     */
    public function getUserLanguageName(): string
    {
        return $this->languages[$this->rcmail->config->get('language')] ?? 'English (US)';
    }
}