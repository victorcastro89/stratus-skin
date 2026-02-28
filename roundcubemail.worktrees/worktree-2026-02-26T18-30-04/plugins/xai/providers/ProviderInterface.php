<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

namespace XAi\Providers;

interface ProviderInterface
{
    public function generateText(string $prompt, string $temperature = 'medium', int $maxTokens = 2000): mixed;
    public function streamText(string $prompt, string $temperature = 'medium', int $maxTokens = 2000): void;
}