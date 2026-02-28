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

class Ollama extends Provider implements ProviderInterface
{
    /**
     * @throws \Exception
     */
    public function __construct(string $providerName, rcmail $rcmail, DatabaseGeneric $db)
    {
        parent::__construct($providerName, $rcmail, $db);

        if (!($this->apiUrl = (string)$this->rcmail->config->get("xai_ollama_url"))) {
            throw new \Exception('URL not specified.');
        }

        if (!($this->model = (string)$this->rcmail->config->get("xai_ollama_model"))) {
            throw new \Exception('Model not specified.');
        }
    }

    /**
     * Generates text using the specified parameters and returns the result.
     *
     * @param string $prompt
     * @param string $temperature
     * @param int $maxTokens
     * @return string|false
     */
    public function generateText(string $prompt, string $temperature = 'medium', int $maxTokens = 2000): mixed
    {
        return $this->apiGenerateText(
            $this->apiUrl,
            [
                "model" => $this->model,
                "prompt" => $prompt,
                "options" => [
                    "temperature" => $this->temperatures[$temperature] ?? 0.5,
                ],
                "stream" => false,
            ],
            [
                "Content-Type: application/json",
            ],
            function($result) {
                if (!($result = json_decode($result, true))) {
                    throw new \Exception("Cannot decode API json response");
                }

                if (empty($result['response'])) {
                    throw new \Exception('Empty message content');
                }

                return $result['response'];
            }
        );
    }

    /**
     * Generates text using the specified parameters and streams the result.
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
                "prompt" => $prompt,
                "options" => [
                    "temperature" => $this->temperatures[$temperature] ?? 0.5,
                ],
                "stream" => true,
            ],
            [
                "Content-Type: application/json",
            ],
            function($curl, $data)
            {
                if ($json = json_decode($data, true)) {
                    if (isset($json['done']) &&
                        isset($json['response']) &&
                        $json['done'] === false
                    ) {
                        echo $json['response'];
                        flush();
                    }
                }

                return strlen($data);
            }
        );
    }
}
