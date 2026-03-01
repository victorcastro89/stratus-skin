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
use XFramework\DatabaseGeneric;
use XAi\Providers\Provider;
use XAi\Classes\Session;

class Compose extends Service implements ServiceInterface
{
    const SESSION_SECTION = 'compose';
    const DEFAULT_INPUT_CHARS = 500;
    const DEFAULT_MAX_TOKENS = 2000;
    const DEFAULT_PROMPT = 'Write a $style email without a subject or preamble. From: $from. To: $to. '.
        'Email length: $length. Email language: $language. Email content: $instructions.';

    const SETTINGS_VALUES = [
        'compose_style' => [
            'default' => 'professional',
            'options' => ['assertive', 'casual', 'enthusiastic', 'funny', 'informational', 'professional', 'urgent',
                'witty'],
        ],
        'compose_length' => [
            'default' => 'medium',
            'options' => ['short', 'medium', 'long'],
        ],
        'compose_creativity' => [
            'default' => 'medium',
            'options' => ['low', 'medium', 'high'],
        ],
        'compose_language' => [
            'default' => 'en_US',
            'options' => [],
        ]
    ];

    public function __construct(Provider $provider, Session $session, rcmail $rcmail, DatabaseGeneric $db)
    {
        parent::__construct($provider, $session, $rcmail, $db);

        // create the settings array in this format:
        // $this->settings = [
        //     'compose_style' => [
        //         'default' => 'professional',
        //         'value' => 'casual',
        //         'options' => [
        //             'assertive' => 'Assertive',
        //             'casual' => 'Casual',
        foreach (self::SETTINGS_VALUES as $key => $val) {
            if ($key == 'compose_language') {
                $this->settings[$key]['options'] = $this->languages;
            } else {
                // set options to the format key => translation (e.g. low => Low)
                $this->settings[$key]['options'] = [];
                foreach ($val['options'] as $name) {
                    $this->settings[$key]['options'][$name] = $this->rcmail->gettext("xai.{$key}_$name");
                }
            }

            $this->settings[$key]['default'] = $val['default'];
            $this->settings[$key]['value'] = $this->rcmail->config->get("xai_$key");

            if (!isset($this->settings[$key]['options'][$this->settings[$key]['value']])) {
                $this->settings[$key]['value'] = $this->settings[$key]['default'];
            }
        }

        // set the default options in session
        if (!$this->session->hasSection(self::SESSION_SECTION)) {
            foreach (['to', 'from', 'instructions'] as $key) {
                $this->session->set(self::SESSION_SECTION, $key, '');
            }

            foreach ($this->settings as $key => $val) {
                $this->session->set(self::SESSION_SECTION, $key, $val['value']);
            }
        }
    }

    /**
     * Resets the 'to' and 'instructions' fields in the session.
     *
     * @return void
     */
    public function resetInputData()
    {
        $this->session->set(self::SESSION_SECTION, 'to', '');
        $this->session->set(self::SESSION_SECTION, 'instructions', '');
    }

    /**
     * Generates an email given the specific set of parameters.
     *
     * @param array $data
     * @return void
     */
    public function generateEmail(array $data): void
    {
        // get and validate config options
        $maxInputChars = filter_var(
            $this->rcmail->config->get("xai_compose_max_input_chars", self::DEFAULT_INPUT_CHARS),
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 100]]
        ) ?: self::DEFAULT_INPUT_CHARS;

        $maxTokens = filter_var(
            $this->rcmail->config->get("xai_compose_max_tokens", self::DEFAULT_MAX_TOKENS),
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 500]]
        ) ?: self::DEFAULT_MAX_TOKENS;

        // validate user data
        if (strlen($data['instructions']) > $maxInputChars) {
            exit($this->rcmail->gettext([
                'name' => "xai.compose_instructions_too_long",
                'vars' => ['n' => $maxInputChars]
            ]));
        }

        $data['from'] = $data['from'] ?: "[The name is unknown]";
        $data['to'] = $data['to'] ?: "[The name is unknown]";

        foreach ($this->getSettings() as $key => $val) {
            if (empty($val['options'][$data[$key]])) {
                $data[$key] = $val['default'];
            }

            // update the setting in session so next time the compose dialog will show the same settings
            $this->session->set(self::SESSION_SECTION, $key, $data[$key]);
        }

        // change the language from key to name so it's more descriptive (en_US to English (US))
        $data['language'] = $this->languages[$data['compose_language']] ?? 'English (US)';

        // get the prompt and replace variables in the string
        $prompt = (string)$this->rcmail->config->get('xai_compose_prompt') ?: self::DEFAULT_PROMPT;

        foreach ($data as $key => $value) {
            $prompt = str_replace('$' . $key, $value, $prompt);
        }

        $this->provider->streamText($prompt, $data['compose_creativity'], $maxTokens);
    }
}