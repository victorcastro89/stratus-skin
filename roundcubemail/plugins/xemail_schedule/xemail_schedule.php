<?php
/**
 * Roundcube Plus Email Schedule plugin.
 *
 * Copyright 2018, Tecorama LLC.
 *
 * @license Commercial. See the file LICENSE for details.
 */

require_once(__DIR__ . "/../xframework/common/Plugin.php");

use XFramework\Response;
use XFramework\Utils;

class xemail_schedule extends XFramework\Plugin
{
    protected bool $hasConfig = true;
    protected string $databaseVersion = "20240911";
    protected string $appUrl = "?_task=settings&_action=preferences&_section=xemail_schedule";

    protected array $scheduleOptions = [
        "immediately" => false,
        "in_5_minutes" => 5,
        "in_10_minutes" => 10,
        "in_15_minutes" => 15,
        "in_30_minutes" => 30,
        "in_1_hour" => 60,
        "in_2_hours" => 120,
        "in_6_hours" => 360,
    ];

    protected array $countdownOptions = [
        "disable_countdown" => false,
        "3_seconds" => 3,
        "5_seconds" => 5,
        "10_seconds" => 10,
        "15_seconds" => 15,
    ];

    protected int $countdownDefault = 5;
    private bool $cronRequest = false;
    private bool $cronRunning = false;
    private array $serverConfig = [];

    public function initialize()
    {
        if (!in_array($this->db->getProvider(), ["mysql", "postgres"])) {
            exit("The plugin xemail_schedule is not compatible with " . $this->db->getProvider());
        }

        // check if we should run the cron function
        $this->cronRequest = rcube_utils::get_input_value("xemail-schedule-cron", rcube_utils::INPUT_GET) &&
            !$this->isDemo();

        // cron is executed in startup() instead of in initialize() because it exposes some hooks, which are registered
        // by other plugins in their initialize() functions
        $this->add_hook("startup", [$this, "startup"]);

        // if it's a cron request, don't initialize anything else
        if ($this->cronRequest) {
            return;
        }

        // check if cron is enabled and running
        $this->cronRunning = $this->checkCron() || $this->isDemo();

        // get the drafts folder and check if it's enabled
        if ($this->rcmail->task == "mail" && !$this->rcmail->config->get('drafts_mbox')) {
            $this->rcmail->output->show_message("xemail_schedule.draft_folder_not_specified", "error");
            return;
        }

        $this->add_hook('message_before_send', [$this, 'messageBeforeSend']);
        $this->add_hook("render_page", [$this, "renderPage"]);
        $this->add_hook('message_compose', [$this, 'messageCompose']);
        $this->add_hook("xais_get_plugin_documentation", [$this, "xaisGetPluginDocumentation"]);

        if ($this->rcmail->task == "mail") {
            if ($this->rcmail->action == "" || $this->rcmail->action == "show") {
                $this->add_button(
                    [
                        "type"     => "link",
                        "label"    => "xemail_schedule.scheduled",
                        "href"       => "javascript:void(0)",
                        "class"    => "button scheduled",
                        "classact" => "button scheduled",
                        "title"    => "xemail_schedule.show_scheduled_messages",
                        "domain"   => $this->ID,
                        "onclick" => "xemailSchedule.showMessageListDialog()",
                        "innerclass" => "inner",
                    ],
                    "toolbar"
                );
            } else if ($this->rcmail->action == "getScheduledMessageList") {
                $this->getScheduledMessageList();
            }
        } else if ($this->rcmail->task == "settings") {
            $this->add_hook('preferences_sections_list', [$this, 'preferencesSectionsList']);
            $this->add_hook('preferences_list', [$this, 'preferencesList']);
            $this->add_hook('preferences_save', [$this, 'preferencesSave']);
        }

        // send config values to frontend
        $this->setJsVar(
            "xemail_schedule_countdown",
            $this->rcmail->config->get("xemail_schedule_countdown", $this->countdownDefault)
        );

        // send labels to frontend
        $this->rcmail->output->add_label(
            "close", "messagesent", "send", "xemail_schedule.schedule", "xemail_schedule.message_scheduled",
            "xemail_schedule.schedule_now", "xemail_schedule.send_now"
        );
    }

    /**
     * Hook: handles the startup actions.
     */
    public function startup()
    {
        if ($this->cronRequest) {
            $this->runCron();
        }

        // include datetime picker
        if ($this->rcmail->task == "mail") {
            if ($this->rcmail->action == "compose") {
                $this->includeFlatpickr();
            }

            $this->includeAsset("assets/scripts/plugin.min.js");
        }

        if ($this->rcmail->task == "mail" || $this->rcmail->task == "settings") {
            $this->includeAsset("assets/styles/plugin.css");
        }
    }

    /**
     * Returns plugin documentation for AI integration.
     *
     * @param array $arg
     * @return array
     */
    public function xaisGetPluginDocumentation(array $arg): array
    {
        if ($arg['plugin'] != $this->ID) {
            return $arg;
        }

        $d = [];
        $d[] = "Email Scheduler: schedule outgoing emails, manage scheduled emails, countdown before sending";
        $d[] = '';
        $d[] = 'Where: Compose → right sidebar → "Send message" dropdown; Mail → toolbar → [Scheduled]; Settings → '.
            'Email Scheduler.';
        $d[] = '';

        $d[] = 'Compose page:';
        $d[] = '- Right sidebar: "Send message" dropdown with options:';
        $d[] = '  Immediately, in 5, 10, 15, 30 minutes, in 1, 2, 6 hours, or At specified time';
        $d[] = '- If anything other than "Immediately" is selected, the [Send] button becomes [Schedule] and the '.
            'message will be scheduled for the selected time.';
        $d[] = '- Optional countdown dialog on Send/Schedule (set in Settings) lets you cancel before the message is '.
            'sent.';
        $d[] = '';

        $d[] = 'Mail page:';
        $d[] = '- Toolbar: [Scheduled] opens a dialog listing all scheduled messages.';
        $d[] = '- From the dialog:';
        $d[] = '  Edit — opens the message in Compose (must be re-scheduled).';
        $d[] = '  Delete — removes the scheduled message.';
        $d[] = '- If sending fails at the scheduled time, an error is shown here; you can Edit or Delete the message.';
        $d[] = '';

        $d[] = 'Settings:';
        $d[] = '- Countdown before sending messages — Disable or set delay (3, 5, 10, 15 seconds).';
        $d[] = '- Default delay for sending messages — e.g., "In 5 minutes".';
        $d[] = '- Highlight scheduled messages that are about to be sent — toggles highlight.';
        $d[] = '';

        $arg['text'] = implode("\n", $d);
        return $arg;
    }

    /**
     * Hook: renders the mail or compose page and handles the delete scheduled message ajax request.
     *
     * @param array $arg
     * @return array
     */
    public function renderPage(array $arg): array
    {
        if ($this->rcmail->task == "mail") {
            // catching this ajax request here because rcmail->storage is not available in init or startup
            if ($this->rcmail->action == "deleteScheduledMessage") {
                $this->deleteScheduledMessage();
            }

            if ($this->rcmail->action == "compose") {
                $this->addComposeHtml($arg);
            } else {
                $this->addMailHtml($arg);
            }
        }

        return $arg;
    }

    /**
     * Hook: save scheduled message. If the value of the schedule combo box is set, we abort the sending and save
     * the message to the db instead.
     *
     * @param array $arg
     * @return array
     * @throws Exception
     */
    public function messageBeforeSend(array $arg): array
    {
        if (!($scheduleSelect = rcube_utils::get_input_value("_xschedule-select", rcube_utils::INPUT_POST))) {
            return $arg;
        }

        $arg['abort'] = true;
        $arg['result'] = true;

        try {
            if ($this->isDemo()) {
                throw new Exception("This functionality is disabled in demo mode.");
            }

            // Check whether all attachments have been encoded and included in the message body. If not, throw an
            // exception, because those attachments will not be scheduled or sent. This can happen when PHP does not
            // have enough memory (memory_limit) to encode the message properly. Roundcube estimates this as
            // total_message_size * 1.33 * 12. In such cases, the message is written to a temporary file instead of
            // being embedded in the message body, meaning it won't be saved to the database when scheduled, and the
            // email will be sent without the attachment. To prevent that, we check whether any attachments are stored
            // in 'body_file' instead of 'body'. (Reflection because parts is protected.)
            try {
                $ref = new ReflectionClass($arg['message']);
                $prop = $ref->getProperty('parts');
                $prop->setAccessible(true);

                foreach ($prop->getValue($arg['message']) as $part) {
                    if ($part['disposition'] == 'attachment' && !empty($part['body_file'])) {
                        // get memory_limit and calculate the max attachment size allowed
                        if ($bytes = parse_bytes(ini_get('memory_limit'))) {
                            throw new Exception($this->gettext([
                                'name' => "xemail_schedule.attachment_tool_large",
                                'vars' => ['size' => Utils::sizeToString($bytes / (1.33 * 12))],
                            ]));
                        } else {
                            throw new Exception('Cannot obtain memory limit (381995)');
                        }
                    }
                }
            } catch (ReflectionException $e) {
                rcmail::write_log('errors', '[xemail_schedule] Unable to inspect Mail_mime parts (281049)');
                throw new Exception('System error 281049');
            }

            // remove < and > from message id: they cause problems when using the id to edit messages in js
            $headers = $arg['message']->headers();
            $data = [
                'user_id' => $this->userId,
                'message_id' => str_replace(["<", ">"], "", $headers['Message-ID']),
            ];

            // check if this message is already scheduled
            if ($this->db->row("xemail_schedule_queue", ['message_id' => $data['message_id']])) {
                throw new Exception($this->gettext('xemail_schedule.message_already_scheduled'));
            }

            // get the message properties to save
            if (!($data['address_from'] = $arg['from'])) {
                throw new Exception($this->gettext("nosenderwarning"));
            }

            if (!($data['address_to'] = $arg['mailto'])) {
                throw new Exception($this->gettext("norecipientwarning"));
            }

            // limit the length of the address_to field - not strictly necessary, but since we changed it to TEXT in the
            // db we don't want to leave it open to exploits
            if (strlen($data['address_to']) > 4096) {
                throw new Exception("Address field too long.");
            }

            // not getting subject from $headers because headers() encodes non-latin characters;
            // we need it unencoded to save it in the db
            if (!($data['subject'] = rcube_utils::get_input_value("_subject", rcube_utils::INPUT_POST))) {
                throw new Exception($this->gettext("nosubjecttitle"));
            }

            // calculate the send time
            $saveTimezone = false;

            if (is_numeric($scheduleSelect)) {
                // user selected a number of minutes to delay the sending of the message
                if ($sendTime = strtotime("+ $scheduleSelect minutes")) {
                    $data['send_time'] = date("Y-m-d H:i:00", $sendTime);
                } else {
                    $data['send_time'] = false;
                }
            } else {
                // user gave a specific date/time to send the message
                if (($sendTime = strtotime(rcube_utils::get_input_value("_xschedule-time", rcube_utils::INPUT_POST))) &&
                    ($timezone = rcube_utils::get_input_value("_xschedule-timezone", rcube_utils::INPUT_POST))
                ) {
                    if ($timezone == "auto") {
                        $data['send_time'] = date("Y-m-d H:i:00", $sendTime);
                    } else {
                        $dateTime = new DateTime(date("Y-m-d H:i:00", $sendTime));
                        $dateTime->setTimezone(new DateTimeZone($timezone));
                        $data['send_time'] = $dateTime->format("Y-m-d H:i:00");
                        $saveTimezone = $timezone;
                    }
                } else {
                    $data['send_time'] = false;
                }
            }

            if (empty($data['send_time'])) {
                throw new Exception($this->gettext("xemail_schedule.invalid_time_format"));
            }

            if (strtotime($data['send_time']) < time() + 5) {
                throw new Exception($this->gettext("xemail_schedule.time_has_already_passed"));
            }

            // save the timezone
            if ($saveTimezone) {
                $pref = $this->rcmail->user->get_prefs();
                $pref['xemail_schedule_timezone'] = $saveTimezone;
                $this->rcmail->user->save_prefs($pref);
            }

            // update the mail mime object and set the new date on the message
            $arg['message']->headers(["Date" => date("r", $sendTime)], true);

            // serialize the message to store it in the db
            $data['mail_mime'] = $this->serializeMailMime($arg['message']);
            $data['server_config'] = [];

            // unless no_save_sent_messages is set, let's save sent_mbox as it's selected on the compose page, this way
            // we'll know where to save the sent message; if the value is empty, it indicates that the message shouldn't
            // be saved
            if (!$this->rcmail->config->get('no_save_sent_messages')) {
                $data['server_config']['sent_mbox'] =
                    rcube_utils::get_input_string('_store_target', rcube_utils::INPUT_POST, true);
            }

            // store all smtp values with the message if specified in the config
            if ($this->rcmail->config->get("xemail_schedule_store_credentials_with_messages")) {
                if (method_exists('rcube_utils', 'parse_host_uri')) {
                    // roundcube 1.6
                    $smtpHost = rcube_utils::parse_host($this->rcmail->config->get('smtp_host', 'localhost'));
                    list($smtpHost, $smtpScheme, $smtpPort) = rcube_utils::parse_host_uri($smtpHost, 587, 465);
                } else {
                    // roundcube 1.5
                    $smtpHost = rcube_utils::parse_host($this->rcmail->config->get('smtp_server', 'localhost'));
                    $smtpPort = $this->rcmail->config->get('smtp_port', '25');
                    $smtpPort = is_numeric($smtpPort) ? (int)$smtpPort : 25;
                    // parse_url will only parse if something precedes the host, adding // if there's no schema
                    $smtpHostUrl = parse_url(strpos($smtpHost, '://') !== false ? $smtpHost : "//$smtpHost");
                    $smtpScheme = $smtpHostUrl['scheme'] ?? null;
                    isset($smtpHostUrl['host']) && ($smtpHost = $smtpHostUrl['host']);
                    isset($smtpHostUrl['port']) && ($smtpPort = (int)$smtpHostUrl['port']);
                }

                $smtpScheme = in_array($smtpScheme, ['ssl', 'tls'], true) ? $smtpScheme : null;
                $smtpHost = $smtpHost ?: 'localhost';

                $smtpHelo = $this->rcmail->config->get('smtp_helo_host') ?: rcube_utils::server_name();
                $smtpHelo = rcube_utils::idn_to_ascii($smtpHelo);
                $smtpHelo = preg_match('/^[a-zA-Z0-9.:-]+$/', $smtpHelo) ? $smtpHelo : 'localhost';

                $smtpUser = str_replace(
                    '%u',
                    $this->rcmail->get_user_name(),
                    (string)$this->rcmail->config->get('smtp_user', '%u')
                );

                $smtpPassConfig = (string)$this->rcmail->config->get('smtp_pass', '%p');
                $smtpPass = $smtpPassConfig === '%p'
                    ? (string)$_SESSION['password'] // already encrypted
                    : (string)$this->rcmail->encrypt($smtpPassConfig);

                $data['server_config']['smtp_host'] = $smtpHost;
                $data['server_config']['smtp_port'] = $smtpPort;
                $data['server_config']['smtp_ssl_mode'] = $smtpScheme;
                $data['server_config']['smtp_helo_host'] = $smtpHelo;
                $data['server_config']['smtp_user'] = $smtpUser;
                $data['server_config']['smtp_pass'] = $smtpPass;
            }

            // execute custom hook (plugins like xmultibox can add smtp server config to $data here)
            $data = $this->rcmail->plugins->exec_hook('xemail_schedule_before_save', $data);

            if (!empty($data['abort'])) {
                return $arg;
            }

            unset($data['abort']);

            if (!empty($data['server_config']) && is_array($data['server_config'])) {
                $data['server_config'] = json_encode($data['server_config']);
            }

            // save the record
            if (!$this->db->insert("xemail_schedule_queue", $data)) {
                throw new Exception($this->gettext("dberror"));
            }

            // read and try to unserialize the message to make sure it will work
            $record = $this->db->row("xemail_schedule_queue", ['message_id' => $data['message_id']]);
            if (empty($record) || !$this->unserializeMailMime($record['mail_mime'])) {
                $this->db->remove("xemail_schedule_queue", ['message_id' => $data['message_id']]);
                throw new Exception("Unserialize error (481129)");
            }

            // make sure the message is not saved in sent
            $this->rcmail->config->set('no_save_sent_messages', true);

        } catch (Exception $e) {
            $arg['result'] = false;
            $arg['error'] = $this->gettext("xemail_schedule.cannot_schedule_message") . " " . $e->getMessage();
        }

        return $arg;
    }

    /**
     * Hook: Edits the scheduled message. Steps:
     * 1. Get message id from $_GET
     * 2. Retrieve the scheduled message from the db
     * 3. Create a draft for the message
     * 4. Delete the scheduled message from the db
     * 5. Edit the draft.
     *
     * @param array $arg
     * @return array
     * @throws Exception
     */
    public function messageCompose(array $arg): array
    {
        // reset this so new messages won't have some old schedule set on the compose page
        $_SESSION['xschedule_send_time'] = false;

        // if xesid is specified in url, edit the scheduled draft
        if (!($id = rcube_utils::get_input_value("xesid", rcube_utils::INPUT_GET))) {
            return $arg;
        }

        try {
            if (!($record = $this->db->row("xemail_schedule_queue", ["message_id" => $id]))) {
                throw new Exception('(481194)');
            }

            if (!($mm = $this->unserializeMailMime($record['mail_mime']))) {
                rcube::write_log('errors', '[xemail_schedule] Cannot unserialize message (381995)');
                throw new Exception('(381995)');
            }

            $hookArg = ['record' => $record, 'abort' => false];
            $hookArg = $this->rcmail->plugins->exec_hook('xemail_schedule_before_edit', $hookArg);

            if (!empty($hookArg['abort'])) {
                return $arg;
            }

            // save message as draft, set draft_uid so RC loads it on the compose page
            // $msg is passed by reference, it needs to be a variable to avoid warnings
            $msg = $mm->getMessage();
            if (!($uid = $this->rcmail->storage->save_message(
                $this->rcmail->config->get('drafts_mbox'),
                $msg
            ))) {
                rcube::write_log('errors', '[xemail_schedule] Cannot save message in drafts (581194)');
                throw new Exception((581194));
            }

            // delete scheduled message from db
            if (!$this->db->remove("xemail_schedule_queue", ["message_id" => $record['message_id']])) {
                rcube::write_log('errors', '[xemail_schedule] Cannot delete scheduled message from db (733884)');
            }

            $arg['param']['draft_uid'] = $uid;
            $_SESSION['xschedule_send_time'] = $record['send_time'];

        } catch (Exception $e) {
            $arg['param']['body'] = $this->gettext("xemail_schedule.error_editing");
        }

        return $arg;
    }

    /**
     * Hook: intercepts smtp login when cron sends the messages and sets the smtp username/password if specified in the
     * plugin's config.
     *
     * @param array $arg
     * @return array
     */
    public function smtpConnect(array $arg): array
    {
        $timeout = (int)$this->rcmail->config->get('xemail_schedule_smtp_timeout', 5);
        if ($timeout > 0 && $timeout <= 60) {
            $arg['smtp_timeout'] = $timeout;
        }

        if (isset($this->serverConfig['smtp_host'])) {
            // roundcube 1.5: smtp_server/smtp_port
            $arg['smtp_server'] =
                (empty($this->serverConfig['smtp_ssl_mode']) ? "" : $this->serverConfig['smtp_ssl_mode'] . "://") .
                ($this->serverConfig['smtp_host'] ?: "");
            $arg['smtp_port'] = $this->serverConfig['smtp_port'] ?: "25";
            // roundcube 1.6: smtp_host
            $arg['smtp_host'] = $arg['smtp_server'] . ":" . $arg['smtp_port'];

            $arg['smtp_helo_host'] = $this->serverConfig['smtp_helo_host'] ?: $this->serverConfig['smtp_host'];
        }

        if (isset($this->serverConfig['smtp_user'])) {
            $arg['smtp_user'] = $this->serverConfig['smtp_user'];
        } else if ($user = $this->rcmail->config->get("xemail_schedule_smtp_user")) {
            $arg['smtp_user'] = $user;
        }

        if (isset($this->serverConfig['smtp_pass'])) {
            $arg['smtp_pass'] = $this->serverConfig['smtp_pass'] ?
                $this->rcmail->decrypt($this->serverConfig['smtp_pass']) : "";
        } else if ($password = $this->rcmail->config->get("xemail_schedule_smtp_pass")) {
            $arg['smtp_pass'] = $password;
        }

        return $arg;
    }

    /**
     * Adds the item to the section list of the settings page.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesSectionsList(array $arg): array
    {
        $arg['list']['xemail_schedule'] = [
            'id' => 'xemail_schedule',
            'section' => $this->gettext("xemail_schedule.email_schedule")
        ];

		return $arg;
    }

    /**
     * Creates the user preferences page.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesList(array $arg): array
    {
        if ($arg['section'] != "xemail_schedule") {
            return $arg;
        }

        $skip = $this->rcmail->config->get("dont_override", []);
        is_array($skip) || ($skip = []);

        $arg['blocks']['main']['name'] = $this->gettext("mainoptions");

        if (!in_array("xemail_schedule_countdown", $skip)) {
            $countdownOptions = [];
            foreach ($this->countdownOptions as $key => $val) {
                $countdownOptions[$this->gettext($key)] = $val;
            }

            $this->getSettingSelect($arg, "main", "countdown", $countdownOptions);
        }

        if (!in_array("xemail_schedule_delay", $skip)) {
            $scheduleOptions = [];
            foreach ($this->scheduleOptions as $key => $val) {
                $scheduleOptions[$this->gettext($key)] = $val;
            }

            $this->getSettingSelect($arg, "main", "delay", $scheduleOptions);
        }

        if (!in_array("xemail_schedule_highlight", $skip)) {
            $this->getSettingCheckbox($arg, "main", "highlight");
        }

		return $arg;
    }

    /**
     * Saves the user preferences.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesSave(array $arg): array
    {
        if ($arg['section'] == "xemail_schedule") {
            $this->saveSetting($arg, "delay", false, false, array_values($this->scheduleOptions));
            $this->saveSetting($arg, "highlight", "boolean");
            $this->saveSetting($arg, "countdown", false, false, array_values($this->countdownOptions));
        }

        return $arg;
    }

    /**
     * Handles the ajax call that requests the content of the scheduled message list dialog.
     * Notice how for display purposes we need to add $this->getTimezoneDifference(), which is the difference between
     * the server timezone and user (RC) timezone in seconds. If the server is not in the user timezone, the display
     * value will show the wrong time (according to the value stored in the db.)
     */
    private function getScheduledMessageList()
    {
        $this->input->checkToken();
        $rows = [];
        $index = 0;
        $time = time();

        $records = $this->db->all(
            "SELECT message_id, subject, address_to, address_from, send_time, error_message FROM {xemail_schedule_queue} ".
            "WHERE user_id = ? AND sent_at IS NULL ORDER BY send_time",
            $this->userId
        );

        foreach ($records as $record) {
            $classes = [];
            $sendTime = strtotime($record['send_time']) + $this->getTimezoneDifference();
            $index++;

            if ($unsent = $sendTime < $time) {
                $classes[] = "unsent";
            } else if ($sendTime < $time + 5 * 60) {
                if ($this->rcmail->config->get("xemail_schedule_highlight")) {
                    $classes[] = "highlight";
                }
            }

            $rows[] = "<tr class='smid-$index scheduled-row " . implode(" ", $classes) . "'>".
                "<td class='subject'>" . htmlentities($record['subject']) . "</td>".
                "<td class='to'>" . htmlentities($record['address_to']) . "</td>".
                "<td class='from'>" . htmlentities($record['address_from']) . "</td>".
                "<td class='time'>" .
                    htmlentities($this->format->formatDateTime($sendTime)) .
                "</td>".
                "<td class='last buttons'>".
                    "<button type='button' class='button btn btn-sm btn-primary' ".
                        "onclick='xemailSchedule.edit(\"{$record['message_id']}\", this)'>" .
                        htmlentities($this->gettext("edit")) .
                    "</button> ".
                    "<button type='button' class='button btn btn-sm btn-danger' ".
                        "onclick='xemailSchedule.remove(\"{$record['message_id']}\", $index, this)'>" .
                        htmlentities($this->gettext("delete")) .
                    "</button>".
                "</td>".
                "</tr>";

            if ($unsent && !empty($record['error_message'])) {
                $rows[] = "<tr class='smid-$index unsent-error-row unsent'><td colspan='5'>".
                    rcmail::Q($record['error_message']) .
                    "</td></tr>";
            }
        }

        $content = "<div class='no-scheduled-messages'>" .
            $this->gettext("xemail_schedule.no_scheduled_messages") .
            "</div>";

        if (!empty($rows)) {
            if ($this->hasUnsent()) {
                $content .= "<div class='schedule-warning-box unsent'>" .
                    $this->gettext("xemail_schedule.warning_unsent_messages") .
                    "</div>";
            }

            $content .= "<table id='scheduled-table'><tr>".
                "<th>" . htmlentities($this->gettext("subject")) . "</th>".
                "<th>" . htmlentities($this->gettext("to")) . "</th>".
                "<th>" . htmlentities($this->gettext("from")) . "</th>".
                "<th>" . htmlentities($this->gettext("schedule")) . "</th>".
                "<th class='last'></th>".
                "</tr>".
                implode("", $rows) .
                "</table>";
        }

        Response::success($content);
    }

    /**
     * Handles the ajax call to delete a scheduled message.
     */
    public function deleteScheduledMessage()
    {
        $this->input->checkToken();

        if (($id = $this->input->get("id")) && $this->trashMessage($id)) {
            Response::success();
        }

        Response::error($this->gettext("xemail_schedule.error_deleting"));
    }

    /**
     * Adds html code to the mail page.
     *
     * @param array $arg
     */
    private function addMailHtml(array &$arg)
    {
        $warning = "";

        if ($arg['template'] == "mail") {
            // save the sent messages in the sent folder
            $this->saveSentMessages();

            if ($this->hasUnsent()) {
                $warning = "<script>$(document).ready(function() { xemailSchedule.showMessageListDialog(); });</script>";
            }
        }

        $this->html->insertAfterBodyStart(
            "<div id='scheduled-message-delete-confirm' title='" . rcube::Q($this->gettext("confirmationtitle")) . "'>".
                "<p>" . rcube::Q($this->gettext("xemail_schedule.message_delete_confirm")) . "</p>".
            "</div>".
            "<div id='scheduled-message-dialog' title='" . rcube::Q($this->gettext("xemail_schedule.scheduled_messages")) . "'>".
                "<div class='xspinner'></div><div class='content'></div>".
            "</div>" . $warning,
            $arg['content']
        );
    }

    /**
     * Adds html code to the compose page.
     *
     * @param array $arg
     */
    private function addComposeHtml(array &$arg)
    {
        $this->html->insertAfterBodyStart(
            "<div id='xes-countdown-mask'><div id='xes-countdown-box'>".
            "<div class='box-content'>".
                "<div id='xes-countdown-value'>5</div>".
                "<div id='xes-countdown-label'>" . rcube::Q($this->gettext("xemail_schedule.email_being_sent")) . "</div>".

            "</div>".
            "<div class='box-buttons'>".
                "<button type='button' id='countdown-send-now' class='mainaction send button btn btn-primary' onclick='xemailSchedule.confirmCountdownSending()'>" .
                    rcube::Q($this->gettext("xemail_schedule.send_now")) . "</button>".
                "<button type='button' class='cancel button btn btn-secondary' onclick='xemailSchedule.cancelCountdownSending()'>" .
                    rcube::Q($this->gettext("xemail_schedule.cancel_sending")) . "</button>".
            "</div>".
            "</div></div>",
            $arg['content']
        );

        if ($this->isElastic()) {
            $html = "<div class='form-group row'><label for='xschedule-select' class='col-form-label col-6'>" .
                $this->gettext("send_message") . "</label><div class='col-6'>" . $this->getComposeHtml() . "</div></div>";

            $this->html->insertAtEnd('id="compose-options"', $html, $arg['content']);
        } else {
            // larry
            if (($i = strpos($arg['content'], "headers-table compose-headers")) &&
                ($j = strpos($arg['content'], "</tbody>", $i))
            ) {
                $arg['content'] = substr_replace(
                    $arg['content'],
                    "<tr><td class='title'>" . rcmail::Q($this->gettext("send_message")) . "</td>" .
                    "<td class='editfield'>" . $this->getComposeHtml() . "</td></tr>",
                    $j,
                    0
                );
            }
        }
    }

    /**
     * Returns HTML for the scheduling dropdown and datetime input.
     *
     * @return string
     */
    private function getComposeHtml(): string
    {
        if ($this->cronRunning) {
            $options = $this->scheduleOptions;
            $options["at_a_specific_time"] = "X";

            // editing draft, check if it's scheduled
            if (empty($_SESSION['xschedule_send_time'])) {
                $sendTime = false;
            } else {
                $sendTime = $_SESSION['xschedule_send_time'];
            }

            // set default for the select element
            if ($sendTime) {
                $draftNote = rcube::Q($this->gettext("xemail_schedule.draft_note"));
                $timeValue = date("r", strtotime($sendTime));
                $inputValue = $this->format->formatDateTime(strtotime($sendTime));
                $selectValue = "X";
            } else {
                $draftNote = "";
                $timeValue = "";
                $inputValue = $this->format->formatDateTime(time());
                $selectValue = (int)$this->rcmail->config->get("xemail_schedule_delay");

                if (!in_array($selectValue, $options)) {
                    $selectValue = "";
                }
            }

            $select = new html_select([
                "name" => "_xschedule-select",
                "id" => "xschedule-select",
                "tabindex" => "1",
                "onchange" => "xemailSchedule.updateControls()",
            ]);

            foreach ($options as $key => $val) {
                $select->add($this->gettext($key), $val);
            }

            $dateFormat = $this->format->getDateTimeFormat("flatpickr");
            $time24h = strpos($dateFormat, "K") === false ? "true" : "false";
            $confirmText = rcube::Q($this->gettext('ok'));

            if ($confirmText == "[ok]") {
                $confirmText = "";
            }

            // create the timezone select

            $timezoneSelect = new html_select([
                "name" => "_xschedule-timezone",
                "id" => "xschedule-timezone",
                "tabindex" => "2",
            ]);

            $timezoneSelect->add($this->rcmail->gettext('autodetect'), 'auto');
            $zones = [];
            foreach (DateTimeZone::listIdentifiers() as $tzs) {
                if ($data = rcmail_action_settings_index::timezone_standard_time_data($tzs)) {
                    $zones[$data['key']] = [$tzs, $data['offset']];
                }
            }

            ksort($zones);

            foreach ($zones as $zone) {
                list($tzs, $offset) = $zone;
                $timezoneSelect->add("(GMT $offset) " . rcmail_action_settings_index::timezone_label($tzs), $tzs);
            }

            return
                $select->show($selectValue) .
                "<div id='xschedule-input-container'>".
                    "<input type='text' id='xschedule-input' name='_xschedule-input' class='form-control' value='$inputValue' />".
                "</div>".
                $timezoneSelect->show($this->rcmail->config->get("xemail_schedule_timezone", "auto")).
                "<input type='hidden' id='xschedule-time' name='_xschedule-time' value='$timeValue' />".
                "<script>
                    xemailSchedule.dateTimePicker = $('#xschedule-input').flatpickr({
                        enableTime: true,
                        dateFormat: '$dateFormat',
                        time_24hr: $time24h,
                        minDate: new Date(),
                        monthSelectorType: 'static',
                        disableMobile: true,
                        plugins: [new confirmDatePlugin({ 
                            ".($confirmText ? "confirmIcon: ''," : "") ."
                            confirmText: '$confirmText',
                            showAlways: true,
                        })]
                    });
                    
                    if (window.flatpickr.l10ns['{$this->userLanguage}'] !== undefined) {
                        xemailSchedule.dateTimePicker.set('locale', '{$this->userLanguage}');
                    }
                </script>".
                ($draftNote ? "<div class='schedule-draft-note schedule-warning'>$draftNote</div>" : "");
        }

        return "<div class='schedule-warning'>" . $this->gettext("xemail_schedule.disabled") . "</div>";
    }

    /**
     * Permanently deletes a message from the schedule queue db table.
     *
     * @param $messageId
     * @return bool
     */
    private function deleteMessage($messageId): bool
    {
       return $this->db->remove("xemail_schedule_queue", ["message_id" => $messageId]);
    }

    /**
     * Moves a message from the schedule queue db table to the trash mailbox and deletes the message from the db.
     *
     * @param $messageId
     * @return boolean
     */
    private function trashMessage($messageId): bool
    {
        if (!($record = $this->db->row("xemail_schedule_queue", ["message_id" => $messageId]))) {
            return false;
        }

        if (!($mm = $this->unserializeMailMime($record['mail_mime']))) {
            return false;
        }

        // $msg is passed by reference, it needs to be a variable to avoid warnings
        $msg = $mm->getMessage();
        if (!$this->rcmail->storage->save_message(
            $this->rcmail->config->get('trash_mbox'),
            $msg,
            '',
            false,
            ['SEEN']
        )) {
            return false;
        }

        return $this->deleteMessage($record['message_id']);
    }

    /**
     * Checks if cron is running: checks the date of last run, it should be within the last 2 minutes
     */
    private function checkCron(): bool
    {
        $date = $this->db->value("value", "system", ["name" => "xemail_schedule_cron"]);
        $seconds = $this->rcmail->config->get("xemail_schedule_cron_error_delay", 120);

        if ($seconds < 60) {
            $seconds = 120;
        }

        return $date && strtotime($date) > time() - $seconds;
    }

    /**
     * Checks if there are any scheduled but unsent messages in the database.
     *
     * @return boolean
     */
    private function hasUnsent(): bool
    {
        if (!($records = $this->db->all(
            "SELECT send_time FROM {xemail_schedule_queue} WHERE user_id = ? AND sent_at IS NULL",
            $this->userId
        ))) {
            return false;
        }

        foreach ($records as $record) {
            if (strtotime($record['send_time']) < time()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sends all due scheduled emails for all users.
     */
    public function runCron()
    {
        set_time_limit(0);
        $sentCount = 0;
        $notSentCount = 0;
        $errors = [];
        $time = date("Y-m-d H:i:s");
        echo "Roundcube Plus xemail_schedule cron ($time)";

        // get and verify the delivery pause value
        $pause = $this->rcmail->config->get("xemail_schedule_delivery_pause");
        if (!is_numeric($pause) || $pause <= 0) {
            $pause = false;
        }

        // if cron execution delay specified, wait; this can be used to offset the server load if two cron jobs are
        // running at the same time
        $sleep = $this->rcmail->config->get("xemail_schedule_cron_execution_delay");
        if (is_numeric($sleep) && $sleep > 0 && $sleep < 30) {
            sleep($sleep);
        }

        // set the hook so we can use the smtp username/password specified in the config when sending messages
        $this->add_hook("smtp_connect", [$this, "smtpConnect"]);

        try {
            // save the cron execution time
            if ($this->db->value("value", "system", ["name" => "xemail_schedule_cron"])) {
                if (!$this->db->update("system", ["value" => $time], ["name" => "xemail_schedule_cron"])) {
                    throw new Exception("Cannot update cron execution time. (447365)");
                }
            } else {
                if (!$this->db->insert("system", ["name" => "xemail_schedule_cron", "value" => $time])) {
                    throw new Exception("Cannot add cron execution time. (447366)");
                }
            }

            // Loop through the messages to be sent: get the records one at a time to make this script thread-safe
            // We select the record in a transaction using FOR UPDATE which locks the record until we set its sent_at
            // field and commit. In case there's another cron job running at the same time, it won't select this
            // record and won't sent the same message twice. Multiple cron jobs could be running if there are two containers
            // with their own cron jobs accessing the same database or if the sending of the messages takes longer
            // than 60 seconds and the script gets executed by the next cron job.
            while (1) {
                $this->db->beginTransaction();

                $st = $this->db->query(
                    "SELECT * FROM {xemail_schedule_queue} WHERE sending = 0 AND sent_at IS NULL AND send_time <= ? ".
                    "LIMIT 1 FOR UPDATE",
                    [date("Y-m-d H:i:s")]
                );

                if (!$st || !($record = $st->fetch(PDO::FETCH_ASSOC))) {
                    $this->db->rollBack();
                    break;
                }

                if (!$this->db->update("xemail_schedule_queue", ["sending" => 1], ["message_id" => $record['message_id']])) {
                    $this->db->rollBack();
                    throw new Exception("Cannot update sending record. (447367)");
                }

                $this->db->commit();

                if ($this->deliverMessage($record, $error)) {
                    $sentCount++;
                } else {
                    $this->db->update("xemail_schedule_queue", ["error_message" => $error], ["message_id" => $record['message_id']]);
                    $errors[] = "Cannot deliver message " . $this->anonId($record['message_id']) . " ($error)";
                    $notSentCount++;
                }

                if ($pause) {
                    usleep($pause * 1000);
                }
            }
        } catch (Exception $e) {
            exit($e->getMessage());
        }

        echo "<br />Sent: $sentCount<br />Not sent: $notSentCount";

        if (!empty($errors)) {
            echo "<br /><br />" . implode("<br />", $errors);
        }

        exit();
    }

    /**
     * Sends a scheduled message and saves a copy in the sent imap folder.
     *
     * @param $record
     * @param $error
     * @return boolean
     */
    public function deliverMessage($record, &$error): bool
    {
        $error = false;

        try {
            if (!($message = $this->unserializeMailMime($record['mail_mime']))) {
                throw new Exception("Cannot unserialize mime message. (48730)");
            }

            // update the message date to now
            $time = time();
            $message->headers(["Date" => date("r", $time)], true);

            // execute the before deliver hook
            $arg = $this->rcmail->plugins->exec_hook(
                'xemail_schedule_before_deliver',
                [
                    'record' => $record,
                    'server_config' => json_decode($record['server_config'], true) ?: [],
                    'message' => $message,
                ]
            );

            // store the config for this server in the local variable, it'll be used in smtpConnect, and below
            $this->serverConfig = $arg['server_config'] ?? [];
            is_array($this->serverConfig) || ($this->serverConfig = []);

            // send message
            $err = null;
            if (!$this->rcmail->deliver_message($message, $record['address_from'], $record['address_to'], $err)) {
                $errorArray = [];
                if (is_array($err)) {
                    empty($err['label']) || ($errorArray[] = "Error: " . $err['label']);
                    empty($err['vars']['msg']) || ($errorArray[] = $err['vars']['msg']);
                }

                throw new Exception(
                    empty($errorArray) ?
                        "Cannot deliver message. Does your server require authentication? (48732)" :
                        implode(" | ", $errorArray)
                );
            }

            // execute the after deliver hook
            $arg = $this->rcmail->plugins->exec_hook(
                'xemail_schedule_after_deliver',
                [
                    'record' => $record,
                    'server_config' => $this->serverConfig,
                    'message' => $message,
                ]
            );

            $this->serverConfig = $arg['server_config'] ?? [];
            is_array($this->serverConfig) || ($this->serverConfig = []);

            // if the message shouldn't be saved in 'sent', delete it from the db, otherwise set its sent_at
            // value, so we can create it in the sent folder when the user logs in to his/her account
            if ($this->rcmail->config->get('no_save_sent_messages') ||
                (isset($this->serverConfig['sent_mbox']) && empty($this->serverConfig['sent_mbox'])) ||
                (!isset($this->serverConfig['sent_mbox']) && !$this->rcmail->config->get('sent_mbox'))
            ) {
                $this->deleteMessage($record['message_id']);
            } else {
                if (!$this->db->update(
                    "xemail_schedule_queue",
                    ["sent_at" => date("Y-m-d H:i:s", $time)],
                    ["message_id" => $record['message_id']]
                )) {
                    throw new Exception("Cannot update database record. (48734)");
                }
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            return false;
        }

        return true;
    }

    /**
     * Removes the email address from message id so we don't show it in cron output on errors.
     *
     * @param $id
     * @return string
     */
    public function anonId($id): string
    {
        return ($i = strpos($id, "@")) ? substr($id, 0, $i) . "@xxx" : $id;
    }

    /**
     * Saves the messages marked as sent in the sent folder. We can't save them right after they're sent because the
     * cron job is not logged in when it sends the messages, so it has no access to the individual users' folders.
     * So it just marks the messages as sent so this function can save them in the sent folder when the user
     * accesses the mail page.
     */
    private function saveSentMessages()
    {
        if ($records = $this->db->all(
            "SELECT * FROM {xemail_schedule_queue} WHERE user_id = ? AND sent_at IS NOT NULL",
            $this->userId
        )) {
            foreach ($records as $record) {
                if (!($mm = $this->unserializeMailMime($record['mail_mime']))) {
                    $this->deleteMessage($record['message_id']);
                    continue;
                }

                // $msg is passed by reference, it needs to be a variable to avoid warnings
                $msg = $mm->getMessage();
                if ($this->rcmail->storage->save_message(
                    $this->rcmail->config->get('sent_mbox'),
                    $msg,
                    '',
                    false,
                    ['SEEN']
                )) {
                    $this->deleteMessage($record['message_id']);
                }
            }
        }
    }

    /**
     * When we serialize the mail mime object, php's serialize function adds null characters to the string to indicate
     * the protected properties. Postgres can't store those null bytes properly even in a binary column and it
     * truncates the string. We replace the null bytes with a marker and restore them on unserialize.
     * More info: http://php.net/manual/en/function.serialize.php
     *
     * Starting with version 1.2.2 we encode the message to make sure that any inserted/attached images/files get inserted
     * into the db properly. This would work properly without encoding in some cases and wouldn't work in others; now it
     * should work in all situations. We add a header to indicate that the string has been encoded so we don't try to
     * decode the messages scheduled without having been encoded.
     *
     * @param $object
     * @return string
     */
    private function serializeMailMime($object): string
    {
        return "[~(ENCODED_BASE_64)~]" . base64_encode(str_replace("\0", "[~(NULL)~(BYTE)~]", serialize($object)));
    }

    /**
     * Unserializes a Mail_mime object previously encoded for storage.
     *
     * @param string $string
     * @return mixed
     */
    private function unserializeMailMime($string)
    {
        if (substr($string, 0, 21) == "[~(ENCODED_BASE_64)~]") {
            $string = base64_decode(substr($string, 21));
        }

        return unserialize(str_replace("[~(NULL)~(BYTE)~]", "\0", $string));
    }
}