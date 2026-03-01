<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */
namespace XAi\Providers;

use _PHPStan_b5fc9ecbb\Nette\Neon\Exception;
use rcmail;
use rcube_config;
use XFramework\DatabaseGeneric;

abstract class Provider implements ProviderInterface
{
    const DEFAULT_TIMEOUT = 200;
    protected string $providerName;
    protected rcmail $rcmail;
    protected DatabaseGeneric $db;
    protected rcube_config $config;
    protected string $apiUrl;
    protected string $apiKey;
    protected string $model;
    protected ?int $modelId = null;
    protected int $timeout;
    protected array $temperatures = [
        'low' => 0.2,
        'medium' => 0.5,
        'high' => 0.8,
    ];

    public function __construct(string $providerName, rcmail $rcmail, DatabaseGeneric $db)
    {
        $this->providerName = $providerName;
        $this->rcmail = $rcmail;
        $this->db = $db;

        // get and validate API timeout
        $this->timeout = filter_var(
            $this->rcmail->config->get("xai_api_timeout", self::DEFAULT_TIMEOUT),
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1, "max_range" => 60]]
        ) ?: self::DEFAULT_TIMEOUT;
    }

    /**
     * Retrieves the id for the current provider and model from the xai_models database. If the record doesn't exist,
     * the function inserts it into the database. Since we always need the id for further use in other database tables,
     * the function returns 0 on failure.
     *
     * @return int
     */
    public function getModelId(): int
    {
        if (isset($this->modelId)) {
            return $this->modelId;
        }

        if ($id = $this->db->value('id', 'xai_models', ['provider' => $this->providerName, 'model' => $this->model])) {
            return $this->modelId = $id;
        }

        if ($this->db->insert('xai_models', ['provider' => $this->providerName, 'model' => $this->model])) {
            return $this->modelId = $this->db->lastInsertId('xai_models');
        }

        return $this->modelId = 0;
    }

    /**
     * Connects to an api endpoint given the specified parameters, waits for response and returns the response text.
     *
     * @param string $apiUrl
     * @param array $post
     * @param array $header
     * @param callable $returnResult
     * @return string|false
     */
    protected function apiGenerateText(string $apiUrl, array $post, array $header, callable $returnResult): mixed
    {
        try {
            if (!($curl = curl_init())) {
                throw new \Exception("Cannot initialize curl");
            }

            try {
                curl_setopt_array($curl, [
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_POST => 1,
                    CURLOPT_TIMEOUT => $this->timeout,
                    CURLOPT_POSTFIELDS => json_encode($post),
                    CURLOPT_HTTPHEADER => $header,
                ]);

                $result = curl_exec($curl);

                if ($error = curl_error($curl)) {
                    throw new \Exception($error);
                }

                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                if ($httpCode != 200) {
                    throw new \Exception("API response error [$httpCode]");
                }

                if (empty($result)) {
                    throw new \Exception('Empty API response');
                }

                return $returnResult($result);

            } finally {
                curl_close($curl);
            }
        } catch (\Exception $e) {
            \rcube::write_log("errors", "[xai] " . $e->getMessage());
            return false;
        }
    }

    /**
     * Connects to an api endpoint given the specified parameters, streams and outputs the response text as it comes in.
     *
     * @param string $apiUrl
     * @param array $post
     * @param array $header
     * @param callable $writeResult
     * @return void
     */
    protected function apiStreamText(string $apiUrl, array $post, array $header, callable $writeResult): void
    {
        try {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            // turn off buffering on nginx
            header('X-Accel-Buffering: no');

            if (!($ch = curl_init())) {
                throw new \Exception("Cannot initialize curl");
            }

            try {
                curl_setopt_array($ch, [
                    CURLOPT_URL => $apiUrl,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_POST => 1,
                    CURLOPT_TIMEOUT => $this->timeout,
                    CURLOPT_POSTFIELDS => json_encode($post),
                    CURLOPT_HTTPHEADER => $header,
                    CURLOPT_WRITEFUNCTION => $writeResult,
                ]);

                curl_exec($ch);

                if ($error = curl_error($ch)) {
                    throw new \Exception($error);
                }

                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpCode != 200) {
                    throw new \Exception("API response error [$httpCode]");
                }

            } finally {
                curl_close($ch);
            }
        } catch (\Exception $e) {
            \rcube::write_log("errors", "[xai] " . $e->getMessage());
            echo $this->rcmail->gettext("xai.connection_error") . "\n\n";
            flush();
        }

        exit();
    }
}