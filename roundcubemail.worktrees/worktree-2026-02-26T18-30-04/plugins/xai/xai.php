<?php
/**
 * Roundcube Plus AI plugin.
 *
 * Copyright 2023, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/../xframework/common/Plugin.php";

use XAi\Classes\Session;
use XAi\Services\Compose;
use XAi\Services\Summary;
use XAi\Providers\Provider;
use XFramework\Response;

class xai extends XFramework\Plugin
{
    const ACTION_COMPOSE_GENERATE = 'plugin.xai_compose_generate';
    const ACTION_GENERATE_VIEW_SUMMARY = 'plugin.xai_generate_view_summary';
    const ACTION_GENERATE_LIST_SUMMARY = 'plugin.xai_generate_list_summary';
    const DEFAULT_PROVIDER = 'openai';
    const DEFAULT_ENABLE_MESSAGE_GENERATION = true;
    const DEFAULT_ENABLE_VIEW_SUMMARIES = false;
    const DEFAULT_ENABLE_LIST_SUMMARIES = false;

    protected string $appUrl = '?_task=settings&_action=preferences&_section=xai';
    protected string $databaseVersion = '20241210';
    protected bool $hasConfig = true;
    protected array $providers = [
        'openai' => 'OpenAI',
        'ollama' => 'Ollama',
    ];

    protected Session $session;
    protected Compose $compose;
    protected Summary $summary;
    protected Provider $provider;

    /**
     * Initializes the plugin.
     */
    public function initialize(): void
    {
        try {
            // check if cURL is available
            if (!function_exists("curl_init")) {
                throw new Exception("Curl extension is not available.");
            }

            // create provider object
            $providerName = $this->rcmail->config->get('xai_provider', self::DEFAULT_PROVIDER);
            if (!array_key_exists($providerName, $this->providers)) {
                throw new Exception('Invalid xai provider.');
            }

            $className = "\\XAi\\Providers\\" . $this->providers[$providerName];
            $this->provider = new $className($providerName, $this->rcmail, $this->db);
        } catch (Exception $e) {
            $this->appUrl = ''; // don't add plugin to the apps menu since it should be disabled
            rcube::write_log("errors", "[xai] " . $e->getMessage());
            return;
        }

        $this->session = new Session();
        $this->compose = new Compose($this->provider, $this->session, $this->rcmail, $this->db);
        $this->summary = new Summary($this->provider, $this->session, $this->rcmail, $this->db);

        // create the array of properties that can be saved on the settings page
        $this->allowed_prefs = array_merge(
            array_keys($this->compose->getSettings()),
            array_keys($this->summary->getSettings())
        );

        // create handlers for ajax actions
        switch ($this->rcmail->action) {
            case self::ACTION_COMPOSE_GENERATE:
                $this->generateEmail();
                break;
            case self::ACTION_GENERATE_VIEW_SUMMARY:
                $this->generateSummary();
                break;
            case self::ACTION_GENERATE_LIST_SUMMARY:
                $this->generateListSummary();
                break;
        }

        $this->includeAsset("assets/scripts/plugin.js");
        $this->includeAsset("assets/styles/styles.css");

        $this->add_hook('messages_list', [$this, 'messageList']);
        $this->add_hook('message_objects', [$this, 'messageObjects']);
        $this->add_hook('template_container', [$this, 'templateContainer']);
        $this->add_hook('preferences_sections_list', [$this, 'preferencesSectionsList']);
        $this->add_hook('preferences_list', [$this, 'preferencesList']);
        $this->add_hook('preferences_save', [$this, 'preferencesSave']);

        if ($this->rcmail->task == "mail" &&
            ($this->rcmail->action == "" || $this->rcmail->action == "show" || $this->rcmail->action == "preview")
        ) {
            $this->add_hook("render_page", [$this, "renderMailPage"]);

            $this->setJsVar("xai_list_summary_placement", $this->rcmail->config->get('xai_list_summary_placement'));

            if ($this->rcmail->output) {
                $this->rcmail->output->add_label('xai.creating_summary');
            }
        } else if ($this->rcmail->task == "mail" && $this->rcmail->action == "compose") {

            if ($this->rcmail->config->get('xai_enable_message_generation', self::DEFAULT_ENABLE_MESSAGE_GENERATION)) {
                // send compose options and data to the frontend
                $composeInfo = ["url" => self::ACTION_COMPOSE_GENERATE];
                foreach ($this->compose->getSettings() as $key => $value) {
                    $composeInfo["{$key}_options"] = $value['options'];
                }
                $this->setJsVar("xai_compose_info", $composeInfo);
                $this->setJsVar("xai_compose_data", $this->session->getSection('compose'));

                $this->includeAsset("xframework/assets/bower_components/angular/angular.min.js");
                $this->includeAsset("assets/scripts/compose.js");

                $this->add_hook("render_page", [$this, "renderComposePage"]);

                if ($this->rcmail->output) {
                    $this->rcmail->output->add_label('xai.compose_no_instructions');
                }

                // reset names and instructions every time the user loads the compose page
                $this->compose->resetInputData();

                // add the AI button
                $this->add_button([
                    'id' => 'xai-open-compose-dialog',
                    'type' => 'link',
                    'href' => 'javascript:void(0)',
                    'role' => 'button',
                    'class' => 'button xai-compose',
                    'label' => 'xai.compose_dialog_button_text',
                    'title' => 'xai.compose_dialog_button_title',
                    'onclick' => 'xai.openComposeDialog()',
                ], 'toolbar');
            }
        } else if ($this->rcmail->task == "settings") {
            $this->add_hook("render_page", [$this, "renderSettingsPage"]);
        }
    }

    /**
     * [Ajax] Generates email summary and returns the text.
     *
     * @return void
     */
    public function generateSummary(): void
    {
        Response::success([
            'summary' => $this->summary->generateSummary(
                $this->input->get('id'),
                $this->input->get('email')
            )
        ]);
    }

    /**
     * [Ajax] Generates a summary
     * @return void
     */
    public function generateListSummary(): void
    {
        if ($this->listSummariesEnabled()) {
            $result = $this->summary->generateSummaryByUid(
                $this->input->get('uid'),
                $this->input->get('folder')
            );

            Response::success([
                'message_id' => $result['message_id'] ?? '',
                'summary' => $result['summary'] ?? '',
            ]);
        }

        Response::error();
    }

    /**
     * [Ajax] Generates email, writes the text directly to the output.
     *
     * @return void
     */
    public function generateEmail(): void
    {
        if (!($instructions = trim($this->input->get("instructions")))) {
            exit($this->rcmail->gettext("ai.compose_no_instructions"));
        }

        $data = [
            "instructions" => $instructions,
            "to" => $this->input->get("to"),
            "from" => $this->input->get("from"),
        ];

        foreach ($this->compose->getSettings() as $key => $val) {
            $data[$key] = $this->input->get($key);
        }

        $this->compose->generateEmail($data);
    }

    /**
     * [Hook] Goes over the emails in message list, gets summaries, if they exist, and adds them to xai_summaries
     * variable that is sent to the frontend. In the frontend we use that object as a source of list summaries.
     * The keys are email uids of the current message list, these are different from messageIds that we use.
     * There seems to be no way to add messageIds or summaries directly to the frontend via $arg, so we use this
     * separate list instead.
     *
     * @param array $arg
     * @return array
     */
    public function messageList(array $arg): array
    {
        if ($this->listSummariesEnabled()) {
            $summaries = [];

            foreach ($arg['messages'] as $message) {
                $messageId = $this->summary->createMessageId($message);
                if ($summary = $this->summary->getSummary($messageId)) {
                    $summaries[$message->uid] = ['message_id' => $messageId, 'summary' => $summary];
                }
            }

            $this->rcmail->output->set_env('xai_summaries', $summaries);
        }

        return $arg;
    }

    /**
     * [Hook] Processes content for different containers.
     *
     * @param array $arg
     * @return array
     */
    public function templateContainer(array $arg): array
    {
        // add 'Show summary' and 'Hide summary' links to the message view header links
        if ($arg['name'] == 'headerlinks' &&
            $this->rcmail->config->get('xai_enable_view_summaries', self::DEFAULT_ENABLE_VIEW_SUMMARIES)
        ) {
            $summaryVisible = $this->rcmail->config->get('xai_show_summary_on_mail_view');
            $spanProperties = ["class" => $this->isElastic() ? "inner" : "button-inner"];

            $arg['content'] .=
                html::a(
                    [
                        "href" => "javascript:void(0)",
                        "id" => "xai-action-show-summary",
                        'onclick' => 'xai.showViewSummary()',
                        'class' => $summaryVisible ? 'hidden' : '',
                    ],
                    html::span($spanProperties, rcube::Q($this->gettext("xai.show_summary")))
                ) .
                html::a(
                    [
                        "href" => "javascript:void(0)",
                        "id" => "xai-action-hide-summary",
                        'onclick' => 'xai.hideViewSummary()',
                        'class' => $summaryVisible ? '' : 'hidden',
                    ],
                    html::span($spanProperties, rcube::Q($this->gettext("xai.hide_summary")))
                );
        }

        return $arg;
    }

    /**
     * [Hook] Adds the summary box to the message view.
     *
     * @param array $arg
     * @return array
     */
    public function messageObjects(array $arg): array
    {
        if ($this->rcmail->config->get('xai_enable_view_summaries', self::DEFAULT_ENABLE_VIEW_SUMMARIES)) {
            if ($messageId = $this->summary->createMessageId($arg['message']->headers)) {
                $summary = $this->summary->getSummary($messageId, $this->userId);

                $arg['content'][] =
                    html::div(
                        [
                            'id' => 'xai-summary-box',
                            'data-message-id' => $messageId,
                            'data-has-summary' => $summary ? 1 : 0,
                            'data-generating' => 0,
                            'class' => 'info-box' .
                                ($this->rcmail->config->get('xai_show_summary_on_mail_view') ? '' : ' hidden'),
                        ],
                        html::span(['class' => 'icon'], '').
                        html::span(
                            ['id' => 'xai-summary-text', 'class' => 'text'],
                            $summary ? rcmail::Q($summary) : $this->gettext('xai.creating_summary')
                        ).
                        html::a(
                            [
                                'href' => 'javascript:void(0)',
                                'id' => 'xai-summary-menu-button',
                                'class' => 'active',
                                'data-popup' => 'xai-summary-menu',
                                'onclick' => $this->skinBase == 'larry' ?
                                    'UI.toggle_popup("xai-summary-menu", event); return false' : ''
                            ],
                            ''
                        )
                    );
            }
        }

        return $arg;
    }

    /**
     * [Hook] Modifies content of the mail page.
     *
     * @param array $arg
     * @return array
     */
    public function renderMailPage(array $arg): array
    {
        // add a popup box for displaying email summaries in the email list; js functionality for list summaries
        // will only be enabled if this popup exists
        if ($this->listSummariesEnabled()) {
            $this->html->insertBeforeBodyEnd(
                html::div(
                    ['id' => 'xai-list-summary-popup', 'class' => 'info-box'],
                    html::span(['class' => 'icon'], '') . html::span(['class' => 'text'], '')
                ),
                $arg['content']
            );
        }

        // add popup menu triggered by the menu button on the right side of the view summary box
        if ($this->rcmail->config->get('xai_enable_view_summaries', self::DEFAULT_ENABLE_VIEW_SUMMARIES)) {
            $this->html->insertAfterBodyStart(
                html::div(['id' => 'xai-summary-menu', 'class' => 'popupmenu'],
                    html::tag(
                        'ul',
                        [
                            'class' => 'menu ' . ($this->skinBase == 'larry' ? 'toolbarmenu' : 'listing'),
                            'role' => 'menu',
                        ],
                        html::tag('li', ['role' => 'menuitem'], html::a(
                            [
                                'class' => 'xai-regenerate-link active refresh',
                                'role' => 'button',
                                'aria-disabled' => 'false',
                                'href' => 'javascript:void(0)',
                                'onclick' => 'xai.generateViewSummary(true)',
                            ],
                            html::span([], rcube::Q($this->gettext('xai.recreate_summary')))
                        )).
                        html::tag('li', ['role' => 'menuitem'], html::a(
                            [
                                'class' => 'xai-settings-link active settings',
                                'role' => 'button',
                                'aria-disabled' => 'false',
                                'href' => 'javascript:void(0)',
                                'onclick' => "xai.redirect('$this->appUrl')",
                            ],
                            html::span([], rcube::Q($this->gettext('xai.ai_assistant_settings')))
                        ))
                    )
                ),
                $arg['content']
            );
        }

        return $arg;
    }

    public function renderSettingsPage(array $arg): array
    {
        return $arg;
    }

    /**
     * [Hook] Modifies contents of the compose page.
     *
     * @param array $arg
     * @return array
     */
    public function renderComposePage(array $arg): array
    {
        // insert the compose dialog at the end of the page
        $examples = [];
        $useText = rcube::Q($this->gettext("compose_help_use_example"));

        for ($i = 1; $i <= 18; $i++) {
            $examples[] = "<li id='xai-help-example-$i'>" .
                "<span>" . rcube::Q($this->gettext("compose_help_example_$i")) . "</span> ".
                "<a href='javascript:void(0)' ng-click='useExample($i)'>$useText</a>" .
                "</li>";
        }

        $this->html->insertAfterBodyStart(
            $this->view(
                "elastic",
                "xai.compose_dialog",
                [
                    "title" => rcube::Q($this->gettext("ai_assistant")),
                    "examples" => "<ul>" . implode("", $examples) . "</ul>"
                ],
                false
            ),
            $arg['content']
        );

        return $arg;
    }

    /**
     * [Hook] Adds the plugin link to the preferences sections list.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesSectionsList(array $arg): array
    {
        $arg['list']['xai'] = ['id' => 'xai', 'section' => $this->gettext("xai.ai_assistant")];
        return $arg;
    }

    /**
     * [Hook] Creates the preferences page.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesList(array $arg): array
    {
        if ($arg['section'] != "xai") {
            return $arg;
        }

        $skip = $this->rcmail->config->get("dont_override");
        $hasContent = false;

        // composing messages
        if ($this->rcmail->config->get('xai_enable_message_generation', self::DEFAULT_ENABLE_MESSAGE_GENERATION)) {
            $hasContent = true;
            $arg['blocks']['xai-composing']['name'] = $this->gettext("messagescomposition");

            foreach ($this->compose->getSettings() as $key => $val) {
                if (!in_array("xai_$key", $skip)) {
                    $this->getSettingSelect(
                        $arg,
                        "xai-composing",
                        $key,
                        array_flip($val['options']),
                        $val['value']
                    );
                }
            }
        }

        $arg['blocks']['xai-summarizing']['name'] = $this->gettext('xai.summarizing_messages');

        // summaries in message view
        if ($this->rcmail->config->get('xai_enable_view_summaries', self::DEFAULT_ENABLE_VIEW_SUMMARIES) &&
            !in_array('xai_show_summary_on_mail_view', $skip)
        ) {
            $hasContent = true;
            $this->getSettingCheckbox($arg, 'xai-summarizing', 'show_summary_on_mail_view');
        }

        // summaries in message list
        if ($this->rcmail->config->get('xai_enable_list_summaries', self::DEFAULT_ENABLE_LIST_SUMMARIES) &&
            !in_array('xai_show_summary_on_list_hover', $skip)
        ) {
            $hasContent = true;
            $this->getSettingCheckbox($arg, 'xai-summarizing', 'show_summary_on_list_hover');
        }

        if ($this->rcmail->config->get('xai_enable_list_summaries', self::DEFAULT_ENABLE_LIST_SUMMARIES) &&
            !in_array('xai_list_summary_placement', $skip)
        ) {
            $hasContent = true;
            $this->getSettingSelect(
                $arg,
                'xai-summarizing',
                'list_summary_placement',
                [
                    $this->gettext('xai.above') => 'above',
                    $this->gettext('xai.below') => 'below',
                    $this->gettext('xai.left') => 'left',
                    $this->gettext('xai.right') => 'right',
                ],
                $this->rcmail->config->get('xai_list_summary_placement', 'above')
            );
        }

        if ($hasContent) {
            $arg['blocks']['xai-disclaimer']['name'] = '';
            $arg['blocks']['xai-disclaimer']['options']["disclaimer"] = [
                "content" => "<div id='xai-settings-disclaimer' class='note-box'>".
                    "<span class='icon'></span>".
                    "<span class='text'>" . $this->gettext("xai.ai_disclaimer") . "</span>".
                "</div>"
            ];
        }

        return $arg;
    }

    /**
     * [Hook] Saves the preferences page.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesSave(array $arg): array
    {
        if ($arg['section'] != "xai") {
            return $arg;
        }

        // save compose values and update the session values on success
        foreach ($this->compose->getSettings() as $key => $val) {
            if ($this->saveSetting($arg, $key, "", "", array_keys($val['options']))) {
                $_SESSION['xai_compose_data'][$key] = rcube_utils::get_input_value($key, rcube_utils::INPUT_POST);
            }
        }

        // save summary values
        $this->saveSetting($arg, 'show_summary_on_mail_view', 'integer');
        $this->saveSetting($arg, 'show_summary_on_list_hover', 'integer');
        $this->saveSetting($arg, 'list_summary_placement', '', '', ['above', 'below', 'left', 'right']);

        return $arg;
    }

    /**
     * Helper function to check if list summaries are enabled.
     *
     * @return bool
     */
    protected function listSummariesEnabled(): bool
    {
        return $this->rcmail->config->get('xai_enable_list_summaries', self::DEFAULT_ENABLE_LIST_SUMMARIES) &&
               $this->rcmail->config->get('xai_show_summary_on_list_hover');
    }
}


