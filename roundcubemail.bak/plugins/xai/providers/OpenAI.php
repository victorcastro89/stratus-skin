<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

namespace XAi\Providers;

use rcmail;
use XFramework\DatabaseGeneric;

class OpenAI extends Provider implements ProviderInterface
{
    protected string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    protected string $systemPrompt = 'You are a helpful personal assistant.';

    /**
     * @throws \Exception
     */
    public function __construct(string $providerName, rcmail $rcmail, DatabaseGeneric $db)
    {
        parent::__construct($providerName, $rcmail, $db);

        // get the api key
        if (!($this->apiKey = (string)$this->rcmail->config->get("xai_openai_api_key"))) {
            throw new \Exception("Invalid OpenAI API key");
        }

        // get the api model
        if (!($this->model = (string)$this->rcmail->config->get(
            'xai_openai_model',
            $this->rcmail->config->get("xai_openai_compose_model") // fallback to old value
        ))) {
            throw new \Exception('Invalid OpenAI model');
        }
    }

    /**
     * Generates text using the specified parameters and returns the result.
     *
     * @param string $prompt
     * @param string $temperature
     * @param int $maxTokens
     * @return string
     */
    public function generateText(string $prompt, string $temperature = 'medium', int $maxTokens = 2000): string
    {
        return $this->apiGenerateText(
            $this->apiUrl,
            [
                "model" => $this->model,
                "messages" => [
                    ["role" => "system", "content" => $this->systemPrompt],
                    ["role" => "user", "content" => $prompt]
                ],
                "max_tokens" => $maxTokens,
                "temperature" => $this->temperatures[$temperature] ?? 0.5,
                "n" => 1,
                "stream" => false,
            ],
            [
                "Content-Type: application/json",
                "Authorization: Bearer $this->apiKey",
            ],
            function($result) {
                if (!($result = json_decode($result, true))) {
                    throw new \Exception("Cannot decode API json response");
                }

                if (!isset($result['choices'][0]['message']['content'])) {
                    throw new \Exception('Empty message content');
                }

                return $result['choices'][0]['message']['content'];
            }
        );
    }

    /**
     *  Generates text using the specified parameters and streams the result.
     *
     * @param string $prompt
     * @param string $temperature
     * @param int $maxTokens
     * @return void
     */
    public function streamText(string $prompt, string $temperature = 'medium', int $maxTokens = 2000): void
    {
        $this->apiStreamText(
            $this->apiUrl,
            [
                "model" => $this->model,
                "messages" => [
                    ["role" => "system", "content" => $this->systemPrompt],
                    ["role" => "user", "content" => $prompt]
                ],
                "max_tokens" => $maxTokens,
                "temperature" => $this->temperatures[$temperature] ?? 0.5,
                "n" => 1,
                "stream" => true,
            ],
            [
                "Content-Type: application/json",
                "Authorization: Bearer $this->apiKey",
            ],
            function($curl, $data)
            {
                $text = "";

                foreach (explode("\n", $data) as $line) {
                    if (strlen($line) > 6 &&
                        substr($line, 0, 6) == "data: " &&
                        ($json = json_decode(substr($line, 6), true)) &&
                        isset($json['choices'][0]['delta']['content'])
                    ) {
                        $text .= $json['choices'][0]['delta']['content'];
                    }
                }

                if ($text) {
                    echo $text;
                    flush();
                }

                return strlen($data);
            }
        );
    }
}