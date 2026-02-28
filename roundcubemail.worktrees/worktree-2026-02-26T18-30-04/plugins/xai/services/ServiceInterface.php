<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

namespace XAi\Services;

interface ServiceInterface
{
    public function getSettings(): array;
    public function getUserLanguageCode(): string;
    public function getUserLanguageName(): string;
}