<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2024, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

namespace XAi\Services;

use XAi\Providers\Provider;

require_once "Service.php";

class Summary extends Service implements ServiceInterface
{
    const DEFAULT_PROMPT = 'Extract important facts from this email and use them to write a very short, one-sentence ' .
        'summary in $language, using active or passive voice depending on the context. Do not include any preamble ' .
        'or introduction. If a summary cannot be created, state the reason using passive voice, without saying you ' .
        'are sorry or requesting user action. Here is the email: $email';

    // used to enable saving these values in the settings
    protected array $settings = [
        'show_summary_on_mail_view' => [],
        'show_summary_on_list_hover' => [],
        'list_summary_placement' => [],
    ];

    /**
     * Creates a message id for our internal use by hashing the message date, from, to, and subject. We can't use the
     * message-ID value as provided by the imap server, because it's not available for emails in the message list. We
     * also can't use the imap's id/folder identifier pair that RC uses, because if the message is moved to another
     * folder, the id will change.
     *
     * @param \rcube_message_header $header
     * @return string|null
     */
    public function createMessageId(\rcube_message_header $header): ?string
    {
        return empty($header->date) || empty($header->from) || empty($header->to) ? null :
            md5(trim("$header->date|$header->from|$header->to|$header->subject"));
    }

    /**
     * Retrieves email summary from the database and returns it. If the summary doesn't exist, it returns null.
     *
     * @param string|null $messageId Hash of the message header information.
     * @param int|null $userId
     * @return null|string
     */
    public function getSummary(?string $messageId, ?int $userId = null): ?string
    {
        if (empty($messageId)) {
            return null;
        }

        $userId || ($userId = $this->rcmail->get_user_id());

        if (!($encrypted = $this->db->value(
            'summary',
            'xai_message_summaries',
            [
                'user_id' => $userId,
                'message_id' => $messageId,
                'language_code' => $this->getUserLanguageCode(),
                'generated_at IS NOT NULL',
            ]
        ))) {
            return null;
        }

        // if summary can't be decrypted, remove the db record
        if (!($decrypted = $this->rcmail->decrypt($encrypted))) {
            $this->db->remove(
                'xai_message_summaries',
                [
                    'user_id' => $userId,
                    'message_id' => $messageId,
                    'language_code' => $this->getUserLanguageCode(),
                ]
            );
            return null;
        }

        return $decrypted;
    }

    /**
     * Generates an email summary by messageId and email text.
     *
     * @param string|null $messageId Hash of the message header information.
     * @param string $email
     * @return string|null
     */
    public function generateSummary(?string $messageId, string $email): ?string
    {
        $userId = $this->rcmail->get_user_id();
        $languageCode = $this->getUserLanguageCode();

        if (empty($messageId) || empty($userId) || empty($languageCode)) {
            return null;
        }

        // check if this summary is already being generated in another thread, and if so, wait for it to finish
        // and return the generated value
        if ($summary = $this->waitForGeneratedSummary($userId, $messageId, $languageCode)) {
            return $summary;
        }

        $email = trim($email);
        $where = [
            'user_id' => $userId,
            'message_id' => $messageId,
            'language_code' => $languageCode,
        ];

        if (empty($email)) {
            // if the email is empty, return the text saying there's no text
            $summary = $this->rcmail->gettext('xai.email_has_no_text');
        } else {
            // create an empty record for this summary and set its started_at value to prevent this function from
            // generating this summary again in another thread
            if (!$this->saveSummary(['started_at' => date("Y-m-d H:i:s")], $where)) {
                return null;
            }

            // get the prompt and replace variables in the string
            $prompt = (string)$this->rcmail->config->get('xai_summary_prompt') ?: self::DEFAULT_PROMPT;
            $prompt = str_replace('$email', $email, $prompt);
            $prompt = str_replace('$language', $this->languages[$languageCode] ?? 'English', $prompt);

            $summary = $this->provider->generateText($prompt);

            if ($summary === false) {
                $this->db->remove('xai_message_summaries', $where);
                return $this->rcmail->gettext("xai.connection_error");
            }
        }

        $values = [
            'summary' => $this->rcmail->encrypt($summary),
            'model_id' => $this->provider->getModelId(),
            'started_at' => null,
            'generated_at' => date("Y-m-d H:i:s"),
        ];

        // save the generated summary to database
        if (!$this->saveSummary($values, $where)) {
            $this->db->remove('xai_message_summaries', $where);
            return null;
        }

        return $summary;
    }

    /**
     * Generates email summary by message imap uid and folder. We use the imap object to retrieve the message headers
     * and body, generate the messageId (hash) and use the generateSummary() function to generate summary.
     *
     * @param string $uid
     * @param string $folder
     * @return array|null
     */
    public function generateSummaryByUid(string $uid, string $folder): ?array
    {
        if (!($storage = $this->rcmail->get_storage())) {
            return null;
        }

        $storage->set_folder($folder);

        if (($header = $storage->get_message_headers($uid, $folder)) &&
            ($messageId = $this->createMessageId($header)) &&
            ($body = $storage->get_body($uid))
        ) {
            return [
                'message_id' => $messageId,
                'summary' => $this->getSummary($messageId) ?? $this->generateSummary($messageId, $body)
            ];
        }

        return null;
    }

    /**
     * Checks if the summary is being generated in another thread (startedAt is set), if so, waits until the generation
     * process is finished, retrieves and returns the summary. It also checks if the startedAt value is expired (older
     * than api timeout) and returns null if it is, allowing the calling function to generate a new summary.
     *
     * @param int $userId
     * @param string $messageId
     * @param string $languageCode
     * @return string|null
     */
    protected function waitForGeneratedSummary(int $userId, string $messageId, string $languageCode): ?string
    {
        // check if the summary is being generated (another thread)
        if (!($startedAt = $this->getStartedAt($userId, $messageId, $languageCode))) {
            return null;
        }

        // get the number of seconds between now and startedAt and check if it's timed out
        try {
            $interval = (new \DateTime())->getTimestamp() - (new \DateTime($startedAt))->getTimestamp();
        } catch (\Exception $e) {
            return null;
        }
        $timeout = $this->rcmail->config->get("xai_api_timeout", Provider::DEFAULT_TIMEOUT);

        if ($interval > $timeout) {
            return null;
        }

        $count = 0;

        while ($this->getStartedAt($userId, $messageId, $languageCode)) {
            sleep(1);
            $count++;

            if ($count >= $timeout) {
                break;
            }
        }

        return $this->getSummary($messageId, $userId);
    }

    /**
     * Retrieves the startedAt value from the summary db table.
     *
     * @param int $userId
     * @param string $messageId
     * @param string $languageCode
     * @return string|null Returns datetime string or null
     */
    protected function getStartedAt(int $userId, string $messageId, string $languageCode): ?string
    {
        if (empty($userId) || empty($messageId) || empty($languageCode)) {
            return null;
        }

        return $this->db->value(
            'started_at',
            'xai_message_summaries',
            [
                'user_id' => $userId,
                'message_id' => $messageId,
                'language_code' => $languageCode,
                'started_at IS NOT NULL',
            ]
        );
    }

    /**
     * Saves summary to the database by inserting or updating the record.
     *
     * @param array $values
     * @param array $where
     * @return bool
     */
    protected function saveSummary(array $values, array $where): bool
    {
        return $this->db->value('user_id', 'xai_message_summaries', $where)
            ? $this->db->update('xai_message_summaries', $values, $where)
            : $this->db->insert('xai_message_summaries', array_merge($values, $where));
    }
}