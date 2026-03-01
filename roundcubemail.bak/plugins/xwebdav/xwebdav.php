<?php
/**
 * Roundcube Plus WebDAV plugin.
 *
 * Copyright 2022, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../xframework/common/Cloud.php");

use XFramework\Utils;
use XFramework\Response;

class xwebdav extends XFramework\Cloud
{
    protected string $appUrl = "?_task=settings&_action=preferences&_section=xwebdav";
    protected array $providers = ["nextcloud" => "NextCloud"];
    protected bool $hasLocalization = true;
    protected array $settings = [];
    protected bool $requiresLogin = true;
    protected string $username = "";
    protected string $password = "";
    protected string $provider = "";
    protected string $sharedUrl = "";
    protected string $url = "";
    protected bool $enableAttachmentSave = false;
    protected bool $enableComposeAttach = false;
    protected bool $enableComposeInsert = false;

    public function initialize()
    {
        $this->provider = $this->rcmail->config->get("xwebdav_provider", "nextcloud");

        if (!array_key_exists($this->provider, $this->providers)) {
            return;
        }

        $this->url = Utils::addSlash($this->rcmail->config->get("xwebdav_url", ""));
        $this->sharedUrl = Utils::removeSlash($this->rcmail->config->get("xwebdav_shared_url", ""));
        $this->enableAttachmentSave = strlen($this->url) > 1;
        $this->enableComposeAttach = $this->enableAttachmentSave;
        $this->enableComposeInsert = strlen($this->sharedUrl) > 1;
        $this->xaiPluginName = $this->providers[$this->provider];

        parent::initialize();

        // run renderPage on compose and mail page (Cloud only runs it on the compose page)
        if ($this->rcmail->task == "mail" && $this->rcmail->action != "compose") {
            $this->add_hook("render_page", [$this, "renderPage"]);
        } else if ($this->rcmail->task == "settings") {
            $this->add_hook('preferences_sections_list', [$this, 'preferencesSectionsList']);
            $this->add_hook('preferences_list', [$this, 'preferencesList']);
            $this->add_hook('preferences_save', [$this, 'preferencesSave']);
        }

        // get the username and password
        $this->loadCredentialsFromPref();

        // set curl options from config
        switch ($this->getConf("xwebdav_auth")) {
            case "NONE":
                $this->requiresLogin = false;
                break;
            case "DIGEST":
                $this->settings[CURLOPT_HTTPAUTH] = CURLAUTH_DIGEST;
                break;
            case "NTLM":
                $this->settings[CURLOPT_HTTPAUTH] = CURLAUTH_NTLM;
                break;
            default: // BASIC
                $this->settings[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
        }

        if ($value = $this->getConf("xwebdav_timeout")) {
            $this->settings[CURLOPT_TIMEOUT] = $value;
        }

        // set the button text
        $this->rcmail->config->set("xwebdav_name", $this->providers[$this->provider]);

        // add assets
        $this->includeAsset("assets/scripts/plugin.js");
        $this->includeAsset("assets/styles/plugin.css");
        $this->includeAsset("xframework/assets/bower_components/angular/angular.min.js");
        $this->includeAsset("assets/scripts/app.min.js");

        // set js variables
        $this->setJsVar("xwebdav_authenticated", !$this->requiresLogin || ($this->username && $this->password));

        // add js labels
        $this->rcmail->output->add_label(
            "logout", "xwebdav.select_files", "xwebdav.all_files", "xwebdav.shared_files", "xwebdav.save_file",
            "xwebdav.file_saved", "xwebdav.specify_webdav_url"
        );

        // execute any xwebdav ajax calls
        $this->ajaxAction();
    }

    protected function enabled(): bool
    {
        return true;
    }

    protected function ajaxAction()
    {
        if (strpos($this->rcmail->action, "xwebdav-") !== 0) {
            return;
        }

        // check the request token
        if (!$this->rcmail->check_request()) {
            Response::error("Invalid token. Your session might have expired.");
        }

        if (($username = $this->input->get("username")) && ($password = $this->input->get("password"))) {
            $this->username = $username;
            $this->password = $password;
        }

        $error = "Unknown error (48839)";

        if ($this->rcmail->action == "xwebdav-get-list") {
            if ($data = $this->getList($this->input->get("type"), $this->input->get("dir"), $error)) {
                Response::success($data);
            }
        } else if ($this->rcmail->action == "xwebdav-upload-file") {
            if ($data = $this->uploadFile(
                $this->input->get("dir"),
                $this->input->get("messageId"),
                $this->input->get("mbox"),
                $this->input->get("mimeId"),
                $this->input->get("filename"),
                $error
            )) {
                Response::success($data);
            }
        } else if ($this->rcmail->action == "xwebdav-logout") {
            $this->removeCredentialsFromPref();
            Response::success();
        }

        Response::error($error);
    }

    /**
     * Hook for rendering the page: we're inserting the xwebdav angular controller html at the end of the page.
     *
     * @param $arg
     * @return mixed
     */
    public function renderPage($arg)
    {
        $arg = parent::renderPage($arg);

        $this->html->insertAfterBodyStart(
            $this->view(
                "elastic",
                "xwebdav.controller",
                [
                    "login" => rcube::Q($this->rcmail->gettext("login")),
                    "logout" => rcube::Q($this->rcmail->gettext("logout")),
                    "cancel" => rcube::Q($this->rcmail->gettext("cancel")),
                    "insert_link" => rcube::Q($this->rcmail->gettext("xwebdav.button_insert_link")),
                    "download_and_attach" => rcube::Q($this->gettext("xframework.download_and_attach")),
                    "login_to_provider" => rcube::Q($this->rcmail->gettext([
                        "name" => "xwebdav.login_to_provider",
                        "vars" => ["p" => $this->providers[$this->provider]],
                    ])),
                    "save" => rcube::Q($this->rcmail->gettext("save")),
                    "username" => $this->rcmail->gettext("username"),
                    "password" => $this->rcmail->gettext("password"),
                    "folder_empty" => rcube::Q($this->rcmail->gettext("xwebdav.folder_empty")),
                    "no_shares" => rcube::Q($this->rcmail->gettext("xwebdav.no_shares")),
                ],
                false
            ),
            $arg['content']
        );

        return $arg;
    }

    public function preferencesSectionsList(array $arg): array
    {
        $arg['list']['xwebdav'] = ['id' => 'xwebdav', 'section' => $this->gettext("xwebdav.webdav")];
        return $arg;
    }

    public function preferencesList(array $arg): array
    {
        if ($arg['section'] != "xwebdav") {
            return $arg;
        }

        $skip = $this->rcmail->config->get("dont_override");
        $arg['blocks']['main']['name'] = $this->gettext("mainoptions");

        if (!in_array("xwebdav_provider", $skip)) {
            $this->getSettingSelect($arg, "main", "provider", array_flip($this->providers));
        }

        if (!in_array("xwebdav_auth", $skip)) {
            $this->getSettingSelect($arg, "main", "auth",
                ['BASIC' => 'BASIC', 'DIGEST' => 'DIGEST', 'NTLM' => 'NTLM', 'NONE' => 'NONE']
            );
        }

        if (!in_array("xwebdav_url", $skip)) {
            $this->getSettingInput($arg, "main", "url");
        }

        if (!in_array("xwebdav_shared_url", $skip)) {
            $this->getSettingInput($arg, "main", "shared_url", null,
                "<div id='xwebdav-shared-url-link'><a href='javascript:void(0)' onclick='xwebdav.createSharedUrl()'>" .
                rcube::Q($this->gettext("settings_shared_url_link")) . "</a></div>"
            );
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
        if ($arg['section'] == "xwebdav") {
            $this->saveSetting($arg, "provider", false, false, array_keys($this->providers));
            $this->saveSetting($arg, "url");
            $this->saveSetting($arg, "shared_url");
            $this->saveSetting($arg, "auth", false, false, ['BASIC', 'DIGEST', 'NTLM', 'NONE']);
            $this->removeCredentialsFromPref();
        }

        return $arg;
    }

    protected function uploadFile(string $dir, $messageId, $mbox, $mimeId, string $filename, string &$errorMessage)
    {
        try {
            if (!$messageId || !$mbox || !$mimeId) {
                throw new Exception();
            }

            if (!($message = new rcube_message($messageId, $mbox))) {
                throw new Exception();
            }

            if (empty($message->mime_parts[$mimeId]) || !$message->mime_parts[$mimeId]) {
                throw new Exception();
            }

            if (!($content = $message->get_part_body($mimeId))) {
                throw new Exception();
            }

            return $this->serverAction(
                $this->url,
                [CURLOPT_CUSTOMREQUEST => "PUT", CURLOPT_POSTFIELDS => $content],
                Utils::addSlash($dir) . $filename,
                $errorMessage
            );
        } catch (Exception $e) {
            $errorMessage = $e->getMessage() ?: $this->gettext("cannot_save_attachment");
            return false;
        }
    }

    /**
     * Returns the list of directories and files from the server.
     *
     * @param string $type
     * @param string $dir
     * @param string $error
     * @return array|bool
     */
    protected function getList(string $type, string $dir, string &$error)
    {
        try {
            switch ($type) {
                case "LIST_ALL":
                case "LIST_DIR":
                    $url = $this->url;
                    $settings = [CURLOPT_CUSTOMREQUEST => "PROPFIND"];
                    break;

                case "LIST_SHARE":
                    $url = $this->sharedUrl;
                    $settings = [
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_HTTPHEADER => ['OCS-APIRequest: true']
                    ];
                    break;

                default:
                    throw new Exception("Invalid list type.");
            }

            if (!($dom = $this->serverAction($url, $settings, $dir, $error))) {
                throw new Exception($error);
            }

            // handle errors - $dom should be SimpleXMLElement, if not, check if it's an html string with a title, in
            // which case the title will have the error - let's extract it
            if (!is_object($dom)) {
                if (is_string($dom) && preg_match('/<title[^>]*>(.*?)<\\/title>/ims', $dom, $matches)) {
                    throw new Exception("Error: " . $matches[1]);
                }

                throw new Exception("Unknown error (38884)");
            }

            switch ($type) {
                case "LIST_ALL":
                case "LIST_DIR":
                    $list = $this->formatStandardList($dom, $dir);
                    break;

                case "LIST_SHARE":
                    $list = $this->formatShareList($dom);
                    break;
            }

            // sort alphabetically with directories on top
            usort($list, function($a, $b) {
                if (($a['type'] == "dir" && $b['type'] == "dir") ||
                    ($a['type'] != "dir" && $b['type'] != "dir")
                ) {
                    return strcasecmp($a['name'], $b['name']);
                }

                return $a['type'] == 'dir'? -1 : 1;
            });

            // create the breadcrumbs
            $breadcrumbs = [];
            $temp = "";

            if ($dir) {
                foreach (explode("/", $dir) as $val) {
                    $breadcrumbs[] = ["type" => "link", "url" => $temp . $val, "name" => $val];
                    $breadcrumbs[] = ["type" => "chevron"];
                    $temp .= $val . "/";
                }
            }

            if (count($breadcrumbs) > 4) {
                array_splice($breadcrumbs, 0, -4);
                array_unshift($breadcrumbs, ["type" => "ellipse"], ["type" => "chevron"]);
            }

            array_unshift($breadcrumbs, ["type" => "link", "url" => "", "name" => ""], ["type" => "chevron"]);
            array_pop($breadcrumbs);

            return [
                "breadcrumbs" => $breadcrumbs,
                "list" => $list,
            ];
        } catch (Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    /**
     * Downloads file from the cloud and returns its info and contents so it can be saved and attached.
     *
     * @param array $file
     * @param string $errorMessage
     * @return array|bool
     */
    protected function downloadFile(array $file, string &$errorMessage)
    {
        if (!$this->checkAttachFileSize($file['size'], $errorMessage)) {
            return false;
        }

        try {
            $result = $this->serverAction($this->url, [CURLOPT_CUSTOMREQUEST => "GET"], $file['url'], $errorMessage);
            return ["name" => $file['name'], "size" => $file['size'], "mime" => $file['type'], "data" => $result];
        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
            return false;
        }
    }

    /**
     * Returns an array of files and directories formatted for display in the dialog (all files - linkType = direct.)
     * This list includes directories that can be navigated.
     *
     * @param SimpleXMLElement $sx
     * @param string $dir
     * @param bool $includeFiles
     * @return array
     */
    private function formatStandardList(SimpleXMLElement $sx, string $dir, bool $includeFiles = true): array
    {
        $list = [];
        $first = true;

        foreach ($sx as $node) {
            // skip the current directory (..)
            if ($first) {
                $first = false;
                continue;
            }

            if (!($name = urldecode(basename((string)$node->href)))) {
                continue;
            }

            $item = [
                "name" => $name,
                "url" => ($dir ? Utils::addSlash($dir) : "") . $name,
                "date" => strtotime((string)$node->propstat->prop->getlastmodified),
                "type" => $node->propstat->prop->resourcetype->collection ? "dir" :
                    (string)$node->propstat->prop->getcontenttype,
                "checked" => false,
            ];

            // if we don't have the file type or we're not supposed to include files, continue
            if (empty($item['type']) || (!$includeFiles && $item['type'] != "dir")) {
                continue;
            }

            // show checkboxes for files only
            $item["hasCheckbox"] = $item['type'] != "dir";

            // add size or quota used for directories
            if ($node->propstat->prop->getcontentlength) {
                $item["size"] = (string)$node->propstat->prop->getcontentlength;
            } else if ($node->propstat->prop->{'quota-used-bytes'}) {
                $item["size"] = (string)$node->propstat->prop->{'quota-used-bytes'};
            }

            // add formatted date and size
            $item["dateFormatted"] = $this->format->formatDateTime($item["date"]);
            $item["sizeFormatted"] = $item["size"] ? Utils::sizeToString($item["size"]) : "";

            // add to list
            $list[] = $item;
        }

        return $list;
    }

    /**
     * Returns an array of files and directories formatted for display in the dialog (shared files - linkType = preview.)
     * The shared list doesn't have any subdirectories it's just a plain list of links that can be shared as urls. Directories
     * can also be selected to be inserted as links.
     *
     * @param SimpleXMLElement $sx
     * @return array
     */
    private function formatShareList(SimpleXMLElement $sx): array
    {
        $list = [];

        foreach ($sx->data->element as $node) {
            $list[] = [
                "name" => urldecode(basename((string)$node->path)),
                "url" => (string)$node->url,
                "date" => (string)$node->stime,
                "type" => (string)$node->item_type == "folder" ? "dir" : (string)$node->mimetype,
                "size" => "",
                "dateFormatted" => $this->format->formatDateTime((string)$node->stime),
                "sizeFormatted" => "",
                "checked" => false,
                "hasCheckbox" => true,
            ];
        }

        return $list;
    }

    /**
     * Runs a curl request to the server.
     *
     * @param string $url
     * @param array $settings
     * @param string $dir
     * @param string $error
     * @return bool|SimpleXMLElement|string
     */
    private function serverAction(string $url, array $settings, string $dir, string &$error)
    {
        try {
            if (empty($url) || strlen($url) <= 1) {
                throw new Exception($this->gettext("cannot_connect_to_server") . " (33910)");
            }

            if ($this->requiresLogin) {
                if (empty($this->username) || empty($this->password)) {
                    throw new Exception($this->gettext("invalid_login_credentials"));
                }

                $this->settings[CURLOPT_USERPWD] = $this->username . ":" . $this->password;

                // replace username in the url, if variable exists
                $url = str_replace("%username", $this->username, $url);
            }

            // url-encode directory names (but not slashes) - note that even with this it doesn't load the directories
            // that have invalid names, but at least we're encoding it the way NextCloud itself does in its urls
            $encodedDir = [];
            foreach (explode("/", $dir) as $val) {
                $encodedDir[] = rawurlencode($val);
            }

            // basic settings that apply to all + $this->settings + any additional settings
            $curlSettings = [
                CURLOPT_URL => $url . implode("/", $encodedDir),
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_RETURNTRANSFER => true,
            ] + $this->settings + $settings;

            $error = false;
            $ch = curl_init();
            curl_setopt_array($ch, $curlSettings);
            $result = curl_exec($ch);
            $curlError = curl_errno($ch);
            $curlMessage = curl_error($ch);
            curl_close($ch);

            // check if timed out
            if ($curlError != 0) {
                throw new Exception(($curlMessage ?: $this->gettext("cannot_connect_to_server")) . " (48993)");
            }

            if ($curlError) {
                throw new Exception(($curlMessage ?: $this->gettext("cannot_connect_to_server")) . " (28849)");
            }

            if ($curlSettings[CURLOPT_CUSTOMREQUEST] == "PUT") {
                // PUT doesn't return anything in $result
                $result = true;
            } else {
                if (empty($result)) {
                    throw new Exception($this->gettext("cannot_connect_to_server"));
                }

                // if the resulting data is xml, parse it and return an object, on login error, this will return false
                if (substr($result, 0, 5) == "<?xml") {
                    $result = $this->parseXml($result, $error);
                }

                if ($error == "Unauthorised") {
                    $this->removeCredentialsFromPref();
                    throw new Exception($this->gettext("incorrect_stored_credentials"));
                }

                if (!empty($result)) {
                    $this->saveCredentialsToPref($this->username, $this->password);
                }
            }

            return $result;

        } catch (Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    /**
     * Parses an xml string and returns DOMDocument object (or false)
     *
     * @param string $xml
     * @param string $error
     * @return bool|SimpleXMLElement
     */
    protected function parseXml(string $xml, string &$error)
    {
        try {
            $sx = simplexml_load_string($xml, null, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE);

            if ($sx === false) {
                throw new Exception("Cannot parse xml data.");
            }

            // handle the ocs (share) xml (no namespace)
            if ($sx->meta->statuscode) {
                if ($sx->meta->statuscode != 200) {
                    throw new Exception($sx->meta->message ?: "Unknown error (88392)");
                }

                return $sx;
            }

            // check for exception (namespace: http://sabredav.org/ns)
            if ($node = $sx->children("http://sabredav.org/ns")) {
                if ($node->exception) {
                    if ($node->exception == "Sabre\DAV\Exception\NotAuthenticated") {
                        $this->removeCredentialsFromPref();
                        throw new Exception($this->gettext("invalid_login_credentials") . " (33892)");
                    } else if ($node->exception == "Sabre\DAVException\NotFound") {
                        throw new Exception($this->gettext("cannot_find_directory"));
                    } else {
                        throw new Exception($node->message . " (88393)");
                    }
                }

                return $node;
            }

            // handle the sabre dav xml (namespace: DAV:
            if ($node = $sx->children("DAV:")) {
                return $node;
            }

            // don't know what kind of xml it is, just return the object
            return $sx;

        } catch (Exception $e) {
            $error = $e->getMessage();
            return false;
        }
    }

    /**
     * Loads the username and password from the user config to the local variables.
     */
    private function loadCredentialsFromPref()
    {
        if (($cred = $this->rcmail->config->get("xwebdav_cred")) &&
            ($cred = $this->rcmail->decrypt($cred)) &&
            ($cred = json_decode($cred, true))
        ) {
            $this->username = $cred[0];
            $this->password = $cred[1];
        }
    }

    /**
     * Saves the username and password in the user config.
     * @param string $username
     * @param string $password
     */
    private function saveCredentialsToPref(string $username, string $password)
    {
        if ($username && $password) {
            $prefs = $this->rcmail->user->get_prefs();
            $prefs['xwebdav_cred'] = $this->rcmail->encrypt(json_encode([$username, $password]));
            $this->rcmail->user->save_prefs($prefs);
        }
    }

    /**
     * Removes the username and password from the user config.
     */
    private function removeCredentialsFromPref()
    {
        $prefs = $this->rcmail->user->get_prefs();
        $prefs['xwebdav_cred'] = false;
        $this->rcmail->user->save_prefs($prefs);
        $this->username = "";
        $this->password = "";
    }
}

