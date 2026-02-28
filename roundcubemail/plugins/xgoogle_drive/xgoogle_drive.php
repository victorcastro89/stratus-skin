<?php
/**
 * Roundcube Plus Google Drive plugin.
 *
 * Copyright 2016, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../xframework/common/Cloud.php");

class xgoogle_drive extends XFramework\Cloud
{
    protected bool $hasLocalization = false;
    protected bool $enableComposeInsert = true;
    protected bool $enableComposeAttach = true;
    protected bool $enableAttachmentSave = true;
    protected string $xaiPluginName = 'Google Drive';

    public function initialize()
    {
        if (!$this->enabled()) {
            return;
        }

        parent::initialize();

        $this->includeAsset("https://apis.google.com/js/platform.js");
        $this->includeAsset("https://apis.google.com/js/client.js");
        $this->includeAsset("assets/scripts/plugin.js");

        $this->setJsVar("xgoogle_drive_client_id", $this->rcmail->config->get("google_drive_client_id"));

        $selectView = $this->rcmail->config->get("google_drive_select_view");
        is_array($selectView) || ($selectView = ["DOCS" => ""]);
        $this->setJsVar("xgoogle_drive_select_view", $selectView);

        $selectScope = $this->rcmail->config->get("google_drive_select_scope");
        is_array($selectScope) || ($selectScope = ["https://www.googleapis.com/auth/drive.readonly"]);
        $this->setJsVar("xgoogle_drive_select_scope", $selectScope);

        $selectFeatures = $this->rcmail->config->get("google_drive_select_features");
        is_array($selectFeatures) || ($selectFeatures = ["MULTISELECT_ENABLED"]);
        $this->setJsVar("xgoogle_drive_select_features", $selectFeatures);
    }

    protected function enabled(): bool
    {
        return (bool)$this->rcmail->config->get("google_drive_client_id");
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
        if (!$this->checkAttachFileSize($file['sizeBytes'], $errorMessage)) {
            return false;
        }

        $authToken = rcube_utils::get_input_value("authToken", rcube_utils::INPUT_POST);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/drive/v2/files/" . $file['id'] . "?alt=media");
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $authToken"]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT,10);
        $data = curl_exec($ch);
        $response = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorMessage = curl_error($ch);
        curl_close($ch);

        if ($errorMessage) {
            return false;
        }

        if ($response != 200) {
            $array = json_decode($data, true);
            $errorMessage = empty($array['error']['message']) ? "Error" : $array['error']['message'];
            return false;
        }

        return [
            "name" => $file['name'],
            "size" => $file['sizeBytes'],
            "mime" => $file['mimeType'],
            "data" => $data
        ];
    }
}