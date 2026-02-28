<?php
namespace XFramework;

/**
 * Roundcube Plus Framework plugin.
 *
 * This file provides the base class for all the cloud-storage-related plugins.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/Plugin.php");

abstract class Cloud extends Plugin
{
    protected bool $enableComposeInsert = false;
    protected bool $enableComposeAttach = false;
    protected bool $enableAttachmentSave = false;
    protected string $xaiPluginName = '';

    abstract protected function enabled();
    abstract protected function downloadFile(array $file, string &$errorMessage);

    /**
     * Initializes the plugin.
     */
    public function initialize()
    {
        $this->load_config();

        if ($this->enableComposeAttach || $this->enableComposeInsert) {
            $this->register_action($this->plugin . "_attach", [$this, 'attachFiles']);
            if ($this->rcmail->task == "mail" && $this->rcmail->action == "compose") {
                $this->add_hook("render_page", [$this, "renderPage"]);
            }
        }

        if ($this->enableAttachmentSave) {
            // output attachment files requested by the cloud service, notice that the request from the server
            // is not logged in, so we need to serve it outside the normal roundcube routing
            if (\rcube_utils::get_input_value("xcloud_save", \rcube_utils::INPUT_GET)) {
                $this->deployAttachment();
            }

            if ($this->rcmail->action == "SaveAttachmentDeployFile") {
                $this->saveAttachmentDeployFile();
            }

            if ($this->rcmail->action == "RemoveAttachmentDeployFile") {
                $this->removeAttachmentDeployFile();
            }
        }

        $this->add_hook('startup', [$this, 'startup']);
        $this->add_hook("xais_get_plugin_documentation", [$this, "xaisGetPluginDocumentation"]);

        $this->includeAsset("xframework/assets/scripts/xcloud.min.js");
        $this->includeAsset("xframework/assets/styles/xcloud.css");

        $this->rcmail->output->add_label("errorsaving", "save", "successfullysaved");

        // add plugin to the list of cloud plugins
        $cloudPlugins = xdata()->get("cloud_plugins", []);
        $cloudPlugins[$this->plugin] = [
            "enableComposeAttach" => $this->enableComposeAttach,
            "enableComposeInsert" => $this->enableComposeInsert,
            "enableAttachmentSave" => $this->enableAttachmentSave,
        ];
        xdata()->set("cloud_plugins", $cloudPlugins);
    }

    /**
     * Handles the startup hook.
     */
    public function startup()
    {
        // send the list of cloud-related plugins to frontend
        $this->setJsVar("xcloud_plugins", xdata()->get("cloud_plugins", []));
    }

    /**
     * [Hook for xai] Returns documentation that teaches the AI model about the plugin.
     * @param array $arg
     * @return array
     */
    public function xaisGetPluginDocumentation(array $arg): array
    {
        if ($arg['plugin'] != $this->ID) {
            return $arg;
        }

        $name = $this->xaiPluginName;
        $insert = $this->enableComposeInsert;
        $attach = $this->enableComposeAttach;
        $save = $this->enableAttachmentSave;

        $d = [];
        $d[] = "$name: cloud storage integration for composing and attachments";
        $d[] = '';
        $d[] = 'Where:';
        ($insert || $attach) && ($d[] = 'Compose → Options & attachments (under "Attach a file")');
        $save && ($d[] = 'Mail → message preview → attachment dropdown');


        $d[] = 'Actions:';
        if ($insert || $attach) {
            if ($insert && $attach) {
                $d[] = "- Compose: Use the [$name] button to pick files from $name and either attach them to the ".
                    "message or insert a $name link into the message body.";
            } elseif ($attach) {
                $d[] = "- Compose: Use the [$name] button to pick files from $name and attach them to the message.";
            } else { // insert only
                $d[] = "- Compose: Use the [$name] button to pick files from $name and insert a $name link into the ".
                    "message body.";
            }
        }
        $save && ($d[] = "- Mail: For each attachment, a 'Save to $name' option appears alongside Open/Download to ".
            "save the file to $name.");

        // Notes
        if ($insert || $attach) {
            $d[] = 'Notes:';
            $this->enableComposeAttach && ($d[] = '- Attaching downloads a copy into the email.');
            $this->enableComposeInsert && ($d[] = '- Inserting a link does not attach the file.');
        }

        $arg['text'] = implode("\n", $d);
        return $arg;
    }

    /**
     * This gets called once for each cloud plugin (dropbox, google drive, webdav)
     *
     * @param $arg
     * @return mixed
     */
    public function renderPage($arg)
    {
        // add the attach buttons to the compose area
        if (($this->enableComposeAttach || $this->enableComposeInsert) && $i = strpos($arg['content'], "compose-attachments")) {
            $insert = false;
            $button = "";

            $label = \rcube::Q(
                $this->getConf($this->plugin . "_name", $this->gettext("xframework.{$this->plugin}_name"))
            );

            // Elastic
            if ($this->isElastic() && $j = strpos($arg['content'], "btn btn-secondary attach", $i)) {
                if ($insert = strpos($arg['content'], "</button>", $j)) {
                    $insert += 9;
                    $button = "<button class='btn btn-secondary $this->plugin' data-popup='$this->plugin-compose-menu'>".
                        $label ."</button>";
                }
            // Larry
            } else if ($j = strpos($arg['content'], "rcmail.upload_input('uploadform')", $i)) {
                if ($insert = strpos($arg['content'], "</a>", $j)) {
                    $insert += 4;
                    $button = "<a href='javascript:void(0)' class='button' ".
                        "onclick='UI.toggle_popup(\"$this->plugin-compose-menu\", event)'>$label</a>";
                }
            }

            if ($insert) {
                $view = $this->view(
                    "elastic",
                    "xframework.compose_button",
                    [
                        "plugin" => $this->plugin,
                        "button" => $button,
                        "insertLabel" => $this->enableComposeInsert ?
                            \rcube::Q($this->gettext("xframework.insert_link")) : "",
                        "attachLabel" => $this->enableComposeAttach ?
                            \rcube::Q($this->gettext("xframework.download_and_attach")) : "",
                    ]
                );
                $arg['content'] = substr_replace($arg['content'], $view, $insert, 0);
            }
        }

        return $arg;
    }

    /**
     * Checks the size of the file to download from cloud and attach. The size can't be larger than the php upload size
     * limit and it must fit in the memory.
     *
     * @param $size
     * @param string $errorMessage
     * @return bool
     */
    public function checkAttachFileSize($size, string &$errorMessage): bool
    {
        $allowedSize = parse_bytes(ini_get("upload_max_filesize"));
        if ($size > $allowedSize) {
            $errorMessage = $this->gettext([
                "name" => "filesizeerror",
                "vars" => ["size" => $this->rcmail->show_bytes($allowedSize)]
            ]);
            return false;
        }

        // TODO: check if there's enough memory to download the file (the downloaded files are handled in the memory)

        return true;
    }

    /**
     * Downloads the file from cloud service and attach it to the message. Some of the attachment handling code has been adapted from
     * program/steps/mail/attachments.inc.
     *
     * The classes inheriting from this class must provide the downloadFile() method
     */
    public function attachFiles()
    {
        $uploadId = \rcube_utils::get_input_value("uploadId", \rcube_utils::INPUT_POST);
        $composeId = \rcube_utils::get_input_value("composeId", \rcube_utils::INPUT_POST);
        $files = \rcube_utils::get_input_value("files", \rcube_utils::INPUT_POST);

        $compose = null;
        $sessionKey = "";

        if ($composeId && $_SESSION['compose_data_' . $composeId]) {
            $sessionKey = 'compose_data_' . $composeId;
            $compose =& $_SESSION[$sessionKey];
        }

        if (!$compose) {
            exit("Invalid session var");
        }

        $this->rcmail->output->reset();

        try {
            if (empty($uploadId) || empty($composeId) || empty($files) || !is_array($files)) {
                throw new \Exception("Invalid upload data");
            }

            foreach ($files as $file) {
                if (!is_array($file)) {
                    throw new \Exception();
                }

                // use the plugin-specific download function to get the file from the cloud
                $errorMessage = "";
                $result = $this->downloadFile($file, $errorMessage);

                if (!is_array($result)) {
                    throw new \Exception($errorMessage);
                }

                // use the filesystem_attachments or the database_attachments plugin to process the file
                $attachment = $this->rcmail->plugins->exec_hook("attachment_save", [
                    'path' => false,
                    'data' => $result['data'],
                    'size' => $result['size'],
                    'name' => $result['name'],
                    'mimetype' => $result['mime'],
                    'group' => $composeId,
                ]);

                if (!$attachment['status'] || $attachment['abort']) {
                    throw new \Exception("Cannot save attachment");
                }

                unset($attachment['status'], $attachment['abort']);
                $this->rcmail->session->append("$sessionKey.attachments", $attachment['id'], $attachment);

                $fileLink = \html::a(
                    [
                        'href'    => "#load",
                        'class'   => 'filename',
                        'onclick' => sprintf(
                            "return %s.command('load-attachment','rcmfile%s', this, event)",
                            \rcmail_output::JS_OBJECT_NAME,
                            $attachment['id']
                        ),
                    ],
                    sprintf(
                        '<span class="attachment-name">%s</span><span class="attachment-size">(%s)</span>',
                        \rcube::Q($attachment['name']),
                        $this->rcmail->show_bytes($attachment['size'])
                    )
                );

                if (!empty($compose['deleteicon']) && is_file($compose['deleteicon'])) {
                    $deleteIcon = \html::img(['src' => $compose['deleteicon'], 'alt' => $this->rcmail->gettext("delete")]);
                } else if (!empty($compose['textbuttons'])) {
                    $deleteIcon = \rcube::Q($this->rcmail->gettext("delete"));
                } else {
                    $deleteIcon = "";
                }

                $deleteLink = \html::a(
                    [
                        'href'    => "#delete",
                        'onclick' => sprintf(
                            "return %s.command('remove-attachment','rcmfile%s', this, event)",
                            \rcmail_output::JS_OBJECT_NAME,
                            $attachment['id']
                        ),
                        'title'   => $this->rcmail->gettext('delete'),
                        'class'   => 'delete',
                        'aria-label' => $this->rcmail->gettext('delete') . ' ' . $attachment['name'],
                    ],
                    $deleteIcon
                );

                if (!empty($compose['icon_pos']) && $compose['icon_pos'] == 'left') {
                    $content = $deleteLink . $fileLink;
                } else {
                    $content = $fileLink . $deleteLink;
                }

                $this->rcmail->output->command(
                    "add2attachment_list",
                    "rcmfile" . $attachment['id'],
                    [
                        "html" => $content,
                        "name" => $attachment['name'],
                        "mimetype"  => $attachment['mimetype'],
                        "classname" => \rcube_utils::file2class($attachment['mimetype'], $attachment['name']),
                        "complete"  => true
                    ],
                    $uploadId
                );
            }
        } catch (\Exception $e) {
            $message = $e->getMessage() ?: $this->gettext("fileuploaderror");
            $this->rcmail->output->command("display_message", $message, "error");
            $this->rcmail->output->command("remove_from_attachment_list", $uploadId);
        }

        $this->rcmail->output->send();
    }

    /**
     * Clears the files from the temporary directory that have never been uploaded to cloud and have not been removed.
     */
    public function cleanUpOldAttachments()
    {
        $time = time();
        foreach (glob(Utils::addSlash($this->rcmail->config->get("temp_dir")) . "xcloud_save_*") as $file) {
            if ($time - filemtime($file) > 3600) {
                unlink($file);
            }
        }
    }

    /**
     * Send the temporary attachment file to the browser. Cloud requests this file directly from its server in order
     * to save it in the user's cloud folder.
     */
    public function deployAttachment()
    {
        try {
            if (!($code = \rcube_utils::get_input_value("xcloud_save", \rcube_utils::INPUT_GET))) {
                throw new \Exception();
            }

            $file = Utils::addSlash($this->rcmail->config->get("temp_dir")) . "xcloud_save_" . $code;

            if (!file_exists($file) || !($size = filesize($file))) {
                throw new \Exception();
            }

            header("Content-Length: $size");
            header("Content-type: application/octet-stream");
            header("Content-Disposition: attachment; filename=\"xcloud_attachment\"");
            header("Content-Transfer-Encoding: binary");

            if (ob_get_contents()) {
                @ob_end_clean();
            }

            $fp = fopen($file, 'rb');

            while(!feof($fp)) {
                set_time_limit(30);
                echo fread($fp, 8192);
                flush();
                @ob_flush();
            }
            fclose($fp);
            unlink($file);
            exit();

        } catch (\Exception $e) {
            header("HTTP/1.0 404");
            exit();
        }
    }

    /**
     * Saves the attachment to a temporary dir with a name based on the random code generated in js. This is currently only used by Dropbox.
     * Dropbox will fetch this file via a url.
     *
     * Cloud savers save files by connecting to a server and fetching files from the given urls. We can't simply pass it the attachment
     * download url, because those urls only work when the user is logged in, while Cloud won't be logged in when it requests the file. So
     * we save the file to a temporary directory and enable direct access to it via a url (?xcloud_save=[id]). After Cloud fetches the file,
     * we remove the temp file.
     */
    public function saveAttachmentDeployFile()
    {
        $handle = false;

        try {
            $messageId = $this->input->get("messageId");
            $mbox = $this->input->get("mbox");
            $mimeId = $this->input->get("mimeId");
            $code = $this->input->get("code");

            if (!$messageId || !$mbox || !$mimeId || !$code) {
                throw new \Exception();
            }

            if (!($message = new \rcube_message($messageId, $mbox))) {
                throw new \Exception("381933");
            }

            if (empty($message->mime_parts[$mimeId])) {
                throw new \Exception("194881");
            }

            // save attachment to a temporary file
            $dir = Utils::addSlash($this->rcmail->config->get("temp_dir"));
            $handle = @fopen($dir . "xcloud_save_" . $code, "w");

            if ($handle === false) {
                throw new \Exception("183229");
            }

            if ($message->get_part_body($mimeId, false, 0, $handle) === false) {
                throw new \Exception("910043");
            }

            fclose($handle);

            // use the opportunity to delete the old attachments
            $this->cleanUpOldAttachments();

            Response::success();

        } catch (\Exception $e) {
            $handle && fclose($handle);
            Response::error("Cannot save attachment (" . ($e->getMessage() ?: "4811934") . ").");
        }
    }

    /**
     * Removes the saved attachment file from the temporary directory. This is called if the user cancels the dropbox
     * save dialog.
     */
    public function removeAttachmentDeployFile()
    {
        if ($code = $this->input->get("code")) {
            @unlink(Utils::addSlash($this->rcmail->config->get("temp_dir")) . "xcloud_save_" . $code);
            Response::success();
        }

        Response::error();
    }
}