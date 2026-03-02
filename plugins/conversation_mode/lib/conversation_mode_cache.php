<?php

/**
 * Conversation Mode – Cache Layer
 *
 * Provides a lightweight in-memory + optional DB cache for conversation
 * summaries so they don't need to be rebuilt on every request.
 *
 * @license GNU GPLv3+
 */
class conversation_mode_cache
{
    /** @var rcmail */
    private $rcmail;

    /** @var int */
    private $ttl;

    /** @var array<string, array> In-memory cache keyed by "user:mailbox" */
    private $mem = [];

    public function __construct(rcmail $rcmail)
    {
        $this->rcmail = $rcmail;
        $this->ttl    = (int) $rcmail->config->get('conversation_mode_cache_ttl', 300);
    }

    // ──────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────

    /**
     * Retrieve cached conversations for a mailbox.
     *
     * @param string $mailbox
     * @return array|null  null = cache miss
     */
    public function get(string $mailbox): ?array
    {
        $key = $this->cache_key($mailbox);

        // 1. Check in-memory cache
        if (isset($this->mem[$key])) {
            $entry = $this->mem[$key];
            if ($entry['expires'] > time()) {
                return $entry['data'];
            }
            unset($this->mem[$key]);
        }

        // 2. Check session cache (lightweight, no DB dependency)
        $session_key = 'conv_cache_' . md5($key);
        if (isset($_SESSION[$session_key])) {
            $entry = $_SESSION[$session_key];
            if (is_array($entry) && isset($entry['expires']) && $entry['expires'] > time()) {
                // Promote to memory
                $this->mem[$key] = $entry;
                return $entry['data'];
            }
            unset($_SESSION[$session_key]);
        }

        return null;
    }

    /**
     * Store conversations for a mailbox.
     *
     * @param string $mailbox
     * @param array  $conversations
     */
    public function set(string $mailbox, array $conversations): void
    {
        $key = $this->cache_key($mailbox);
        $entry = [
            'data'    => $conversations,
            'expires' => time() + $this->ttl,
            'stored'  => time(),
        ];

        $this->mem[$key] = $entry;

        // Also store in session for cross-request persistence
        $session_key = 'conv_cache_' . md5($key);
        $_SESSION[$session_key] = $entry;
    }

    /**
     * Invalidate cache for a specific mailbox.
     */
    public function invalidate(string $mailbox): void
    {
        $key = $this->cache_key($mailbox);
        unset($this->mem[$key]);

        $session_key = 'conv_cache_' . md5($key);
        unset($_SESSION[$session_key]);
    }

    /**
     * Invalidate all conversation caches.
     */
    public function flush(): void
    {
        $this->mem = [];

        // Clear all session conv_cache keys
        if (isset($_SESSION) && is_array($_SESSION)) {
            foreach (array_keys($_SESSION) as $k) {
                if (strpos($k, 'conv_cache_') === 0) {
                    unset($_SESSION[$k]);
                }
            }
        }
    }

    // ──────────────────────────────────────────────
    //  Internal
    // ──────────────────────────────────────────────

    private function cache_key(string $mailbox): string
    {
        $user_id = $this->rcmail->user ? $this->rcmail->user->ID : 0;
        return $user_id . ':' . $mailbox;
    }
}
