<?php
/**
 * Roundcube Plus Dropbox plugin.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../xframework/common/Cloud.php");

class xdropbox extends XFramework\Cloud
{
    protected bool $hasLocalization = false;
    protected bool $enableComposeInsert = true;
    protected bool $enableComposeAttach = true;
    protected bool $enableAttachmentSave = true;
    protected string $xaiPluginName = 'Dropbox';

    public function initialize()
    {
        if (!$this->enabled()) {
            return;
        }

        parent::initialize();
        $this->includeAsset("https://www.dropbox.com/static/api/2/dropins.js");
        $this->includeAsset("assets/scripts/plugin.js");

        $this->setJsVar("xdropbox_app_key", $this->rcmail->config->get("dropbox_app_key"));
        $this->setJsVar("xdropbox_multiselect", $this->rcmail->config->get("dropbox_multiselect"));
        $this->setJsVar("xdropbox_extensions", $this->rcmail->config->get("dropbox_extensions"));
    }

    protected function enabled(): bool
    {
        return (bool)$this->rcmail->config->get("dropbox_app_key");
    }

    /**
     * Downloads file from the cloud and returns its info and contents so it can be saved and attached.
     *
     * @param array $file
     * @param string $errorMessage
     * @return boolean|array
     */
    protected function downloadFile(array $file, string &$errorMessage)
    {
        if (!$this->checkAttachFileSize($file['bytes'], $errorMessage)) {
            return false;
        }

        $data = @file_get_contents($file['link']);

        if (!$data) {
              $errorMessage = "Cannot download file from Dropbox";
              return false;
          }

        return [
            "name" => $file['name'],
            "size" => $file['bytes'],
            "mime" => @rcube_mime::file_content_type($file['name'], $file['name']),
            "data" => $data
        ];
    }
}