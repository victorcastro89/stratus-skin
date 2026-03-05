<?php

/**
 * Conversation Grouper
 *
 * Groups IMAP messages into conversation buckets using:
 *   1. RFC headers (Message-ID, In-Reply-To, References)
 *   2. Fallback: normalized subject + time-window heuristic
 *
 * @license GNU GPLv3+
 */
class conversation_mode_grouper
{
    /** @var rcmail */
    private $rcmail;

    /** @var bool */
    private $use_subject_fallback;

    /** @var int */
    private $subject_window_days;

    public function __construct(rcmail $rcmail)
    {
        $this->rcmail = $rcmail;
        $this->use_subject_fallback = (bool) $rcmail->config->get('conversation_mode_subject_fallback', true);
        $this->subject_window_days  = (int) $rcmail->config->get('conversation_mode_subject_window_days', 30);
    }

    // ──────────────────────────────────────────────
    //  Public API
    // ──────────────────────────────────────────────

    /**
     * Group an array of message headers into conversation buckets.
     *
     * @param rcube_message_header[] $headers
     * @return array<string, array>  Keyed by conversation_id, each value is:
     *   [
     *     'conversation_id' => string,
     *     'subject_norm'    => string,
     *     'latest_date'     => string (IMAP internal date),
     *     'latest_uid'      => int,
     *     'message_count'   => int,
     *     'unread_count'    => int,
     *     'flagged_count'   => int,
     *     'has_attachments'  => bool,
     *     'participants'    => string[],
     *     'uids'            => int[],
     *     'snippet'         => string,
     *   ]
     */
    public function group(array $headers): array
    {
        // Phase 1: build a graph of message-id → conversation root
        $id_map     = [];  // message_id → conversation root id
        $convs      = [];  // root_id   → [uid, uid, ...]
        $header_map = [];  // uid       → header object

        foreach ($headers as $header) {
            $header_map[$header->uid] = $header;
        }

        // First pass: link via RFC headers
        foreach ($headers as $header) {
            $msg_id     = $this->clean_message_id($header->messageID ?? '');
            $in_reply   = $this->clean_message_id($header->in_reply_to ?? '');
            $references = $this->parse_references($header->references ?? '');

            // Collect all related IDs
            $related = array_filter(array_merge([$msg_id, $in_reply], $references));

            if (empty($related)) {
                // Isolated message — use uid as temp key
                $root = 'uid:' . $header->uid;
                $id_map[$root] = $root;
                $convs[$root][] = $header->uid;
                continue;
            }

            // Find existing root for any of the related IDs
            $root = null;
            foreach ($related as $rid) {
                if (isset($id_map[$rid])) {
                    $root = $this->find_root($id_map, $id_map[$rid]);
                    break;
                }
            }
            if ($root === null) {
                // New conversation — use first reference (or message-id) as root
                $root = $related[0];
            }

            // Map all related IDs to this root
            foreach ($related as $rid) {
                if (!empty($rid)) {
                    $existing_root = isset($id_map[$rid]) ? $this->find_root($id_map, $id_map[$rid]) : null;
                    if ($existing_root !== null && $existing_root !== $root) {
                        // Merge two conversations
                        $this->merge_convs($convs, $id_map, $existing_root, $root);
                    }
                    $id_map[$rid] = $root;
                }
            }

            $convs[$root][] = $header->uid;
        }

        // Phase 2: subject fallback grouping for isolated messages
        if ($this->use_subject_fallback) {
            $convs = $this->apply_subject_fallback($convs, $header_map, $id_map);
        }

        // Phase 3: build conversation summary objects
        return $this->build_summaries($convs, $header_map);
    }

    // ──────────────────────────────────────────────
    //  Subject fallback
    // ──────────────────────────────────────────────

    /**
     * Merge isolated (single-message) conversations that share a normalized
     * subject within the configured time window.
     */
    private function apply_subject_fallback(array $convs, array $header_map, array &$id_map): array
    {
        $subject_groups = []; // norm_subject => [root_id, ...]

        foreach ($convs as $root => $uids) {
            // Only consider small conversations for merging
            if (count($uids) > 3) {
                continue; // already linked by headers
            }
            $first_uid = $uids[0];
            if (!isset($header_map[$first_uid])) {
                continue;
            }
            $subj = $this->normalize_subject($header_map[$first_uid]->subject ?? '');
            if ($subj === '') {
                continue;
            }
            $subject_groups[$subj][] = $root;
        }

        foreach ($subject_groups as $subj => $roots) {
            if (count($roots) < 2) {
                continue;
            }

            // Check time window and merge
            $primary = $roots[0];
            for ($i = 1; $i < count($roots); $i++) {
                $candidate = $roots[$i];
                if ($this->within_time_window($convs[$primary], $convs[$candidate], $header_map)) {
                    $this->merge_convs($convs, $id_map, $candidate, $primary);
                }
            }
        }

        // Remove empty conversation slots
        return array_filter($convs, function ($uids) { return !empty($uids); });
    }

    /**
     * Check if two sets of UIDs fall within the subject time window.
     */
    private function within_time_window(array $uids_a, array $uids_b, array $header_map): bool
    {
        $latest_a = $this->latest_timestamp($uids_a, $header_map);
        $latest_b = $this->latest_timestamp($uids_b, $header_map);

        if ($latest_a === 0 || $latest_b === 0) {
            return false;
        }

        $diff_days = abs($latest_a - $latest_b) / 86400;
        return $diff_days <= $this->subject_window_days;
    }

    private function latest_timestamp(array $uids, array $header_map): int
    {
        $ts = 0;
        foreach ($uids as $uid) {
            if (isset($header_map[$uid]) && !empty($header_map[$uid]->timestamp)) {
                $ts = max($ts, (int) $header_map[$uid]->timestamp);
            }
        }
        return $ts;
    }

    // ──────────────────────────────────────────────
    //  Build summaries
    // ──────────────────────────────────────────────

    private function build_summaries(array $convs, array $header_map): array
    {
        $result = [];

        foreach ($convs as $root => $uids) {
            $uids = array_unique($uids);
            if (empty($uids)) {
                continue;
            }

            $latest_uid  = null;
            $latest_ts   = 0;
            $unread      = 0;
            $flagged     = 0;
            $attachments = false;
            $participants = [];
            $subject     = '';
            $snippet     = '';

            foreach ($uids as $uid) {
                if (!isset($header_map[$uid])) {
                    continue;
                }
                $h = $header_map[$uid];
                $ts = (int) ($h->timestamp ?? 0);

                if ($ts >= $latest_ts) {
                    $latest_ts  = $ts;
                    $latest_uid = $uid;
                    $snippet    = $this->extract_snippet($h);
                }

                if (empty($subject) && !empty($h->subject)) {
                    $subject = $h->subject;
                }

                // Canonical unread check: Roundcube stores flags as associative array
                // e.g. ['SEEN' => true, 'FLAGGED' => true]. A message is unread when
                // the SEEN flag is absent or falsy.
                if (empty($h->flags['SEEN'])) {
                    $unread++;
                }

                if (!empty($h->flags['FLAGGED'])) {
                    $flagged++;
                }

                if (!empty($h->has_attachment) || !empty($h->attachments)) {
                    $attachments = true;
                }

                // Collect participants
                $from = $this->extract_address($h->from ?? '');
                if ($from && !in_array($from, $participants)) {
                    $participants[] = $from;
                }
            }

            // Sort UIDs newest first (by timestamp)
            usort($uids, function ($a, $b) use ($header_map) {
                $ta = isset($header_map[$a]) ? (int) ($header_map[$a]->timestamp ?? 0) : 0;
                $tb = isset($header_map[$b]) ? (int) ($header_map[$b]->timestamp ?? 0) : 0;
                return $tb - $ta;  // newest first
            });

            $conv_id = md5($root);

            $result[$conv_id] = [
                'conversation_id' => $conv_id,
                'root_id'         => $root,
                'subject_norm'    => $this->normalize_subject($subject),
                'subject'         => $subject,
                'latest_date'     => $latest_ts ? date('r', $latest_ts) : '',
                'latest_timestamp' => $latest_ts,
                'latest_uid'      => $latest_uid,
                'message_count'   => count($uids),
                'unread_count'    => $unread,
                'flagged_count'   => $flagged,
                'has_attachments' => $attachments,
                'participants'    => $participants,
                'uids'            => $uids,
                'snippet'         => $snippet,
            ];
        }

        // Sort conversations by latest timestamp descending
        uasort($result, function ($a, $b) {
            return $b['latest_timestamp'] - $a['latest_timestamp'];
        });

        return $result;
    }

    // ──────────────────────────────────────────────
    //  Utilities
    // ──────────────────────────────────────────────

    /**
     * Normalize a subject line by stripping Re:, Fwd:, etc.
     */
    public function normalize_subject(string $subject): string
    {
        // Strip leading Re: / Fwd: / Fw: (case-insensitive, repeated)
        $subject = preg_replace('/^(\s*(re|fwd?|aw|sv|vs)\s*(\[\d+\])?\s*:\s*)+/i', '', $subject);
        // Trim whitespace
        $subject = trim($subject);
        // Lowercase for comparison
        return mb_strtolower($subject, 'UTF-8');
    }

    /**
     * Clean angle brackets from a Message-ID.
     */
    private function clean_message_id(string $id): string
    {
        $id = trim($id);
        $id = trim($id, '<>');
        return $id;
    }

    /**
     * Parse a References header into an array of message-IDs.
     */
    private function parse_references(string $refs): array
    {
        if (empty($refs)) {
            return [];
        }
        preg_match_all('/<([^>]+)>/', $refs, $matches);
        return $matches[1] ?? [];
    }

    /**
     * Find the root of a union-find chain.
     */
    private function find_root(array &$map, string $id): string
    {
        $visited = [];
        while (isset($map[$id]) && $map[$id] !== $id) {
            $visited[] = $id;
            $id = $map[$id];
        }
        // Path compression
        foreach ($visited as $v) {
            $map[$v] = $id;
        }
        return $id;
    }

    /**
     * Merge conversation $from into conversation $into.
     */
    private function merge_convs(array &$convs, array &$id_map, string $from, string $into): void
    {
        if ($from === $into) {
            return;
        }

        if (!isset($convs[$from])) {
            return;
        }

        if (!isset($convs[$into])) {
            $convs[$into] = [];
        }

        $convs[$into] = array_merge($convs[$into], $convs[$from]);
        unset($convs[$from]);

        $id_map[$from] = $into;
    }

    /**
     * Extract a short snippet from a message.
     *
     * Fetches the first 200 characters of the plain-text body part via IMAP
     * BODY.PEEK to generate a preview snippet for the conversation list.
     */
    private function extract_snippet($header): string
    {
        // Try cached/preloaded body text first (rarely available)
        if (!empty($header->body_structure_text)) {
            return $this->clean_snippet($header->body_structure_text);
        }

        // Fetch the plain-text body part from IMAP
        try {
            $storage = $this->rcmail->get_storage();
            $uid = $header->uid ?? null;
            $folder = $header->folder ?? ($storage->get_folder() ?: 'INBOX');

            if (!$uid) {
                return '';
            }

            // Use rcube_message to get the text part efficiently
            $message = new rcube_message($uid, $folder);

            // Get the first text/plain part
            $text = '';
            if ($message->first_text_part()) {
                $text = $message->first_text_part();
            }

            if (empty($text)) {
                return '';
            }

            return $this->clean_snippet($text);
        } catch (\Exception $e) {
            // Fail silently — snippet is non-critical
            return '';
        }
    }

    /**
     * Clean and truncate text for use as a snippet.
     */
    private function clean_snippet(string $text): string
    {
        // Strip HTML tags
        $text = strip_tags($text);
        // Collapse whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        // Trim
        $text = trim($text);
        // Truncate to 120 chars
        return mb_substr($text, 0, 120);
    }

    /**
     * Extract a display-friendly email address from a From header.
     */
    private function extract_address(string $from): string
    {
        if (preg_match('/"?([^"<]+)"?\s*</', $from, $m)) {
            return trim($m[1]);
        }
        if (preg_match('/<(.+?)>/', $from, $m)) {
            return trim($m[1]);
        }
        return trim($from);
    }
}
