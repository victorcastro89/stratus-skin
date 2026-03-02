<?php

/**
 * Conversation Mode – Service
 *
 * High-level orchestrator that wires the grouper and cache together and
 * provides the three main endpoints consumed by the AJAX actions:
 *   • list_conversations()
 *   • open_conversation()
 *   • refresh()
 *
 * @license GNU GPLv3+
 */
class conversation_mode_service
{
    /** @var rcmail */
    private $rcmail;

    /** @var conversation_mode_grouper */
    private $grouper;

    /** @var conversation_mode_cache */
    private $cache;

    public function __construct(rcmail $rcmail)
    {
        $this->rcmail  = $rcmail;
        $this->grouper = new conversation_mode_grouper($rcmail);
        $this->cache   = new conversation_mode_cache($rcmail);
    }

    // ──────────────────────────────────────────────
    //  list_conversations
    // ──────────────────────────────────────────────

    /**
     * Return a page of conversations for the given mailbox.
     *
     * @param string $mailbox  IMAP folder name
     * @param int    $page     1-based page number
     * @param int    $page_size
     * @return array  { conversations: [...], total: int, page: int, pages: int }
     */
    public function list_conversations(string $mailbox, int $page = 1, int $page_size = 50): array
    {
        $conversations = $this->get_or_build($mailbox);

        $total = count($conversations);
        $pages = max(1, (int) ceil($total / $page_size));
        $page  = min($page, $pages);

        $offset = ($page - 1) * $page_size;
        $slice  = array_slice(array_values($conversations), $offset, $page_size);

        // Strip heavy data (full uid lists) for the list view
        $rows = array_map(function ($conv) {
            return [
                'conversation_id' => $conv['conversation_id'],
                'subject'         => $conv['subject'],
                'subject_norm'    => $conv['subject_norm'],
                'latest_date'     => $conv['latest_date'],
                'latest_timestamp' => $conv['latest_timestamp'],
                'latest_uid'      => $conv['latest_uid'],
                'message_count'   => $conv['message_count'],
                'unread_count'    => $conv['unread_count'],
                'flagged_count'   => $conv['flagged_count'],
                'has_attachments' => $conv['has_attachments'],
                'participants'    => $conv['participants'],
                'snippet'         => $conv['snippet'],
            ];
        }, $slice);

        return [
            'conversations' => $rows,
            'total'         => $total,
            'page'          => $page,
            'pages'         => $pages,
            'mailbox'       => $mailbox,
        ];
    }

    // ──────────────────────────────────────────────
    //  open_conversation
    // ──────────────────────────────────────────────

    /**
     * Return the messages of a single conversation, newest first.
     *
     * @param string $mailbox
     * @param string $conv_id
     * @return array  { conversation_id, subject, messages: [...] }
     */
    public function open_conversation(string $mailbox, string $conv_id): array
    {
        $conversations = $this->get_or_build($mailbox);

        if (!isset($conversations[$conv_id])) {
            return [
                'conversation_id' => $conv_id,
                'subject'         => '',
                'messages'        => [],
                'error'           => 'not_found',
            ];
        }

        $conv    = $conversations[$conv_id];
        $storage = $this->rcmail->get_storage();
        $uids    = $conv['uids']; // already sorted newest-first

        // Fetch full headers for the conversation messages
        $messages = [];
        $headers  = $storage->fetch_headers($mailbox, $uids);

        if (is_array($headers)) {
            // Index by UID for ordering
            $by_uid = [];
            foreach ($headers as $h) {
                $by_uid[$h->uid] = $h;
            }

            foreach ($uids as $uid) {
                if (!isset($by_uid[$uid])) {
                    continue;
                }
                $h = $by_uid[$uid];
                $messages[] = [
                    'uid'        => $h->uid,
                    'subject'    => $h->subject ?? '',
                    'from'       => $h->from ?? '',
                    'to'         => $h->to ?? '',
                    'date'       => $h->date ?? '',
                    'timestamp'  => (int) ($h->timestamp ?? 0),
                    'size'       => (int) ($h->size ?? 0),
                    'flags'      => $this->extract_flags($h),
                    'has_attachment' => !empty($h->has_attachment),
                ];
            }
        }

        return [
            'conversation_id' => $conv_id,
            'subject'         => $conv['subject'],
            'participants'    => $conv['participants'],
            'message_count'   => $conv['message_count'],
            'unread_count'    => $conv['unread_count'],
            'messages'        => $messages,
        ];
    }

    // ──────────────────────────────────────────────
    //  refresh
    // ──────────────────────────────────────────────

    /**
     * Rebuild conversations for the mailbox and return a delta or full set.
     *
     * @param string $mailbox
     * @return array
     */
    public function refresh(string $mailbox): array
    {
        // Invalidate cache and rebuild
        $this->cache->invalidate($mailbox);
        return $this->list_conversations($mailbox, 1);
    }

    // ──────────────────────────────────────────────
    //  Internal
    // ──────────────────────────────────────────────

    /**
     * Get conversations from cache or build from IMAP.
     *
     * @param string $mailbox
     * @return array<string, array>
     */
    private function get_or_build(string $mailbox): array
    {
        $cached = $this->cache->get($mailbox);
        if ($cached !== null) {
            return $cached;
        }

        $conversations = $this->build_from_imap($mailbox);
        $this->cache->set($mailbox, $conversations);

        return $conversations;
    }

    /**
     * Fetch all message headers from IMAP and group them.
     *
     * @param string $mailbox
     * @return array<string, array>
     */
    private function build_from_imap(string $mailbox): array
    {
        $storage = $this->rcmail->get_storage();
        $storage->set_folder($mailbox);

        // Get total count
        $count = $storage->count($mailbox, 'ALL');
        if ($count === 0) {
            return [];
        }

        // Fetch all message headers (sorted by date descending)
        // For very large mailboxes this should be paginated/streamed in future
        $max_fetch = 2000; // safety limit for MVP
        $fetch_count = min($count, $max_fetch);

        $index = $storage->index($mailbox, 'DATE', 'DESC');
        $uids  = $index->get();

        if (empty($uids)) {
            return [];
        }

        $uids = array_slice($uids, 0, $fetch_count);

        // Fetch headers in batches to avoid memory issues
        $batch_size = 500;
        $all_headers = [];

        for ($i = 0; $i < count($uids); $i += $batch_size) {
            $batch = array_slice($uids, $i, $batch_size);
            $headers = $storage->fetch_headers($mailbox, $batch);

            if (is_array($headers)) {
                $all_headers = array_merge($all_headers, $headers);
            }
        }

        if (empty($all_headers)) {
            return [];
        }

        return $this->grouper->group($all_headers);
    }

    /**
     * Extract flag information from a header object.
     */
    private function extract_flags($header): array
    {
        $flags = [];

        if (isset($header->flags) && is_array($header->flags)) {
            if (!empty($header->flags['SEEN'])) {
                $flags[] = 'seen';
            }
            if (!empty($header->flags['FLAGGED'])) {
                $flags[] = 'flagged';
            }
            if (!empty($header->flags['ANSWERED'])) {
                $flags[] = 'answered';
            }
            if (!empty($header->flags['DELETED'])) {
                $flags[] = 'deleted';
            }
            if (!empty($header->flags['DRAFT'])) {
                $flags[] = 'draft';
            }
        }

        return $flags;
    }
}
