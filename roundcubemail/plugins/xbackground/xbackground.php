<?php
/**
 * Roundcube Plus Background plugin.
 *
 * Copyright 2016, Roundcubeplus.com.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../xframework/common/Plugin.php");

use XFramework\Utils;
use XFramework\Response;

class xbackground extends XFramework\Plugin
{
    public $allowed_prefs = ["xbackground_image"];
    protected bool $hasConfig = true;
    protected string $appUrl = "?_task=settings&_action=preferences&_section=xbackground";
    private array $images = [
        "tile_leather" => ["placement" => "tile", "textColor" => "dark"],
        "tile_leaves" => ["placement" => "tile", "textColor" => "dark"],
        "tile_linen" => ["placement" => "tile", "textColor" => "dark"],
        "tile_squares" => ["placement" => "tile", "textColor" => "dark"],
        "tile_wood" => ["placement" => "tile", "textColor" => "dark"],
        "tile_marble" => ["placement" => "tile", "textColor" => "dark"],
        "tile_material" => ["placement" => "tile", "textColor" => "dark"],
        "cover_fall" => ["placement" => "cover", "textColor" => "light"],
        "cover_fire" => ["placement" => "cover", "textColor" => "light"],
        "cover_lake" => ["placement" => "cover", "textColor" => "light"],
        "cover_log" => ["placement" => "cover", "textColor" => "light"],
        "cover_sky" => ["placement" => "cover", "textColor" => "light"],
        "cover_traffic" => ["placement" => "cover", "textColor" => "light"],
        "cover_wheat" => ["placement" => "cover", "textColor" => "dark"],

        "custom" => ["placement" => "tile", "textColor" => "dark"],
        "none" => ["placement" => "tile", "textColor" => "dark"],
    ];
    private array $blurValues = [];
    private array $grayValues = [];
    private array $opacityValues = [];
    private array $placementValues = ["tile", "cover"];
    private array $textColorValues = ["auto", "dark", "light"];
    private int $defaultContentOpacity = 80;
    private int $defaultImageOpacity = 100;
    private int $defaultBlur = 0;
    private int $defaultGray = 0;
    private string $defaultPlacement = "tile";
    private string $defaultTextColor = "auto";
    private bool $loginImage = false;

    /**
     * Initializes the plugin.
     * @codeCoverageIgnore
     */
    public function initialize()
    {
        if ($this->rcmail->action == "print") {
            return;
        }

        if (($this->rcmail->task == "login" || $this->rcmail->task == "logout") &&
            !$this->rcmail->output->get_env("xmobile") &&
            $this->rcmail->config->get("xbackground_login_image")
        ) {
            $this->loginImage = true;
            $this->add_hook("render_page", [$this, "renderLoginPage"]);
            $this->add_hook("startup", [$this, "startup"]);
            $this->includeAsset("assets/styles/login.css");
        }

        if ($this->rcmail->task == "login" ||
            $this->rcmail->task == "logout" ||
            $this->rcmail->output->get_env("xmobile")
        ) {
            return;
        }

        $this->add_hook("startup", [$this, "startup"]);
        $this->add_hook("render_page", [$this, "renderPage"]);
        $this->add_hook("xais_get_plugin_documentation", [$this, "xaisGetPluginDocumentation"]);

        if ($this->rcmail->task == "settings") {
            $this->add_hook('preferences_sections_list', [$this, 'preferencesSectionsList']);
            $this->add_hook('preferences_list', [$this, 'preferencesList']);
            $this->add_hook('preferences_save', [$this, 'preferencesSave']);
            $this->includeAsset("xframework/assets/bower_components/jquery-form/jquery.form.js");

            if ($this->rcmail->action == "xbackground_upload_image") {
                $this->uploadImage();
            }

            if ($this->rcmail->action == "xbackground_delete_image") {
                $this->deleteImage();
            }
        }

        $this->includeAsset("assets/styles/plugin.css");
        $this->includeAsset("assets/scripts/plugin.min.js");

        /// set value arrays
        $this->blurValues[$this->gettext("xbackground.none")] = 0;
        $this->grayValues[$this->gettext("xbackground.none")] = 0;

        for ($i = 1; $i <= 10; $i++) {
            $this->blurValues[($i * 10) . "%"] = $i * 10;
            $this->grayValues[($i * 10) . "%"] = $i * 10;
            $this->opacityValues[($i * 10) . "%"] = $i * 10;
        }
    }

    public function renderPage($arg)
    {
        $this->html->insertAfterBodyStart("<div id='xbackground-body'></div>", $arg['content']);
        return $arg;
    }

    /**
     * Displays an image on the login page, if specified in the config.
     *
     * @param array $arg
     * @return array
     */
    public function renderLoginPage(array $arg): array
    {
        $url = $this->rcmail->config->get("xbackground_login_image");
        $pi = pathinfo($url);
        $images = ["jpg", "jpeg", "png", "svg"];

        if (empty($pi['extension']) || !in_array($pi['extension'], $images)) {
            $day = empty($_GET['xd']) ? date("z") : (int)$_GET['xd'];

            if ($day <= 0 || $day > 366) {
                $day = date("z");
            }

            $path = rtrim($url, "/") . "/";

            $navigation =
                "<div onclick='xbgl.prev()' id='xlogin-image-left'></div>".
                "<div onclick='xbgl.next()' id='xlogin-image-right'></div>".
                "<script>
                    const xbgl = new function() {
                        this.path = '$path';
                        this.day = $day;

                        this.prev = function() {
                            this.day = this.day - 1 >= 1 ? this.day - 1 : 366;
                            $('#xlogin-image div').fadeOut(1500);
                            this.load();
                        };

                        this.next = function() {
                            this.day = this.day + 1 <= 366 ? this.day + 1 : 1;
                            $('#xlogin-image div').fadeOut(1500);
                            this.load();
                        };

                        this.load = function() {
                            const file = this.path + ('0000' + this.day).slice(-3) + '.jpg';
                            $('<img />').attr('src', file).on('load', function() {
                                const element = $('#xlogin-image div');
                                element.stop();
                                element.css('background-image', 'url(' + file + ')').fadeIn(300);
                            });
                        };
                    }

                    xbgl.load();
                </script>";
        } else {
            $navigation = "<style>#xlogin-image div { background-image: url($url); display: block; }</style>";
        }

        $opacity = $this->rcmail->config->get("xbackground_login_opacity");
        if (!empty($opacity) && $opacity >= 1 && $opacity <= 100) {
            $opacity = "opacity:" . round($opacity / 100, 1);
        } else {
            $opacity = false;
        }

        $arg['content'] = str_replace(
            "</body>",
            "<div id='xlogin-image'><div style='$opacity'></div></div>$navigation</body>",
            $arg['content']
        );

        return $arg;
    }

    /**
     * Plugin startup hook.
     */
    public function startup()
    {
        if ($this->loginImage) {
            // this must be called in startup otherwise it won't work
            $this->addBodyClass("xbackground-login-image");
            return;
        }

        // get the data we'll use to set up html and send to the frontend
        $data = [
            "image" => $this->rcmail->config->get("xbackground_image", "none"),
            "contentOpacity" => $this->rcmail->config->get("xbackground_content_opacity", $this->defaultContentOpacity),
            "imageOpacity" => $this->rcmail->config->get("xbackground_image_opacity", $this->defaultImageOpacity),
            "blur" => $this->rcmail->config->get("xbackground_blur", $this->defaultBlur),
            "gray" => $this->rcmail->config->get("xbackground_gray", $this->defaultGray),
            "placement" => $this->rcmail->config->get("xbackground_placement", $this->defaultPlacement),
            "textColor" => $this->rcmail->config->get("xbackground_text_color", $this->defaultTextColor),
            "customImageUrl" => $this->getUploadedImageUrl(),
            "images" => $this->images,
            "customDark" => $this->rcmail->config->get("xbackground_custom_dark", false),
        ];

        // this function is called before the prefs are saved, so we need to populate from post otherwise we'll be using old values
        if (rcube_utils::get_input_value("image", rcube_utils::INPUT_POST)) {
            $data['image'] = rcube_utils::get_input_value("image", rcube_utils::INPUT_POST);
            $data['contentOpacity'] = rcube_utils::get_input_value("content_opacity", rcube_utils::INPUT_POST);
            $data['imageOpacity'] = rcube_utils::get_input_value("image_opacity", rcube_utils::INPUT_POST);
            $data['blur'] = rcube_utils::get_input_value("blur", rcube_utils::INPUT_POST);
            $data['gray'] = rcube_utils::get_input_value("gray", rcube_utils::INPUT_POST);
            $data['placement'] = rcube_utils::get_input_value("placement", rcube_utils::INPUT_POST);
            $data['textColor'] = rcube_utils::get_input_value("text_color", rcube_utils::INPUT_POST);
        }

        // check values
        array_key_exists($data['image'], $this->images) || ($data['image'] = "none");
        in_array($data['contentOpacity'], $this->opacityValues) || ($data['contentOpacity'] = $this->defaultContentOpacity);
        in_array($data['imageOpacity'], $this->opacityValues) || ($data['imageOpacity'] = $this->imageOpacity);
        in_array($data['blur'], $this->blurValues) || ($data['blur'] = $this->defaultBlur);
        in_array($data['gray'], $this->grayValues) || ($data['ray'] = $this->defaultGray);
        in_array($data['placement'], $this->placementValues) || ($data['placement'] = $this->defaultPlacement);
        in_array($data['textColor'], $this->textColorValues) || ($data['textColor'] = $this->defaultTextColor);

        // add the inline style that will show the custom uploaded image and show the custom image selection box
        if ($this->hasUploadedImage()) {
            $this->addInlineStyle("body #xbackground-image-box-custom { display: inline-block; }");
            //$this->addInlineStyle("body.xbg.xbg-image-custom #xbackground-body { background-image: url({$data['customImageUrl']});}");
        } else {
            $data['customImageUrl'] = false;
            // the user could have deleted the image and not saved the form, so if there's no uploaded image, the
            // image can't be 'custom'
            if ($data['image'] == "custom") {
                $data['image'] = "none";
            }
        }

        // create the background selection boxes
        // don't include on elastic because there's no space for it
        if (!$this->elastic && $this->rcmail->config->get("xbackground_image_change_enabled", true)) {
            $this->addToInterfaceMenu(
                "background-select",
                html::div(
                    ["id" => "background-select", "class" => "section"],
                    html::div(["class" => "section-title"], $this->gettext("background")) .
                    $this->getBackgroundBoxes().
                    html::div(
                        ["id" => "background-more-actions"],
                        html::a(
                            ["href" => "?_task=settings&_action=preferences&_section=xbackground"],
                            rcube::Q($this->gettext("xbackground.more_background_options"))
                        )
                    )
                )
            );
        }

        // send data to frontend
        $this->rcmail->output->set_env("xbackgroundData", $data);
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

        $d = [];
        $d[] = 'Plugin: Background/Wallpaper';
        $d[] = 'Description: Customizes the UI wallpaper and visual effects.';
        $d[] = '';
        $d[] = 'Where: Settings → Background.';
        $d[] = 'Behavior: Choose a background image from the built-in list'.
            ($this->isUploadEnabled() ? ' or upload your own.' : '');
        $d[] = 'Options:';
        $d[] = '- Image blur (None, 10%–100%).';
        $d[] = '- Convert to gray (10%–100%).';
        $d[] = '- Image opacity (10%–100%).';
        $d[] = '- Content box opacity (10%–100%).';
        $d[] = 'Save: Click "Save" to apply.';

        $arg['text'] = implode("\n", $d);
        return $arg;
    }

    /**
     * Adds the settings section.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesSectionsList(array $arg): array
    {
        // add the section item
        $arg['list']['xbackground'] = [
            'id' => 'xbackground',
            'section' => $this->gettext("xbackground.background")
        ];

		return $arg;
    }

    /**
     * Creates user preferences page.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesList(array $arg): array
    {
        if ($arg['section'] != "xbackground") {
            return $arg;
        }

        $arg['blocks']['main']['name'] = $this->gettext("mainoptions");

        // setting: background image
        if ($this->rcmail->config->get("xbackground_image_change_enabled", true)) {
            $input = new html_hiddenfield(
                [
                    "id" => "xbackground-image-value",
                    "name" => "image",
                    "value" => $this->rcmail->config->get("xbackground_image", "none")
                ]
            );

            $this->addSetting($arg, "main", "image", $input->show() . $this->getBackgroundBoxes());

            $this->getSettingSelect(
                $arg,
                "main",
                "blur",
                $this->blurValues,
                $this->defaultBlur,
                "",
                ["onchange" => "xbackground.changeBlur()"]
            );

            $this->getSettingSelect(
                $arg,
                "main",
                "gray",
                $this->grayValues,
                $this->defaultGray,
                false,
                ["onchange" => "xbackground.changeGray()"]
            );

            $this->getSettingSelect(
                $arg,
                "main",
                "image_opacity",
                $this->opacityValues,
                $this->defaultImageOpacity,
                false,
                ["onchange" => "xbackground.changeImageOpacity()"]
            );
        }

        // setting: content opacity
        if ($this->rcmail->config->get("xbackground_opacity_change_enabled", true)) {
            $this->getSettingSelect(
                $arg,
                "main",
                "content_opacity",
                $this->opacityValues,
                $this->defaultContentOpacity,
                false,
                ["onchange" => "xbackground.changeContentOpacity()"]
            );
        }

        // setting textColor (only supported by larry-compatible skins)
        if (!$this->isElastic()) {
            $this->getSettingSelect(
                $arg,
                "main",
                "text_color",
                [
                    $this->gettext("xbackground.auto") => "auto",
                    $this->gettext("xbackground.light") => "light",
                    $this->gettext("xbackground.dark") => "dark",
                ],
                false,
                $this->getSettingHelp($this->gettext("xbackground.text_color_help")),
                ["onchange" => "xbackground.changeTextColor()"]
            );
        }

        // setting: upload
        if ($this->rcmail->config->get("xbackground_image_change_enabled", true) && $this->isUploadEnabled()) {
            $arg['blocks']['upload']['name'] = $this->gettext("upload_image");

            $uploadInput = new html_inputfield(
                [
                    "type" => "button",
                    "class" => "button",
                    "id" => "xbackground-upload-button",
                    "onclick" => "$('#xbackground-upload-input').click()"
                ]
            );

            $deleteInput = new html_inputfield(
                [
                    "type" => "button",
                    "class" => "button",
                    "id" => "xbackground-delete-button",
                    "onclick" => "xbackground.deleteImage()",
                    "style" => "display:" . ($this->hasUploadedImage() ? "inline" : "none"),
                ]
            );

            $arg['blocks']['upload']['options']["upload"] = [
                "title" => rcube::Q($this->gettext("xbackground.upload_background_image")),
                "content" =>
                    html::tag(
                        "div",
                        ["id" => "xbackground-upload-controls"],
                        html::tag(
                            "div",
                            ["id" => "xbackground-upload-buttons"],
                            $uploadInput->show(rcube::Q($this->gettext("upload"))) .
                            $deleteInput->show(rcube::Q($this->gettext("delete")))
                        ).
                        html::tag("div", ["id" => "xbackground-upload-error"])
                    )
            ];

            // setting: placement
            $this->getSettingSelect(
                $arg,
                "upload",
                "placement",
                [
                    $this->gettext("xbackground.tile") => "tile",
                    $this->gettext("xbackground.cover") => "cover",
                ],
                false,
                $this->getSettingHelp($this->gettext("xbackground.placement_help")),
                ["onchange" => "xbackground.changePlacement()"]
            );
        }

		return $arg;
    }

    /**
     * Saves user preferences.
     *
     * @param array $arg
     * @return array
     */
    public function preferencesSave(array $arg): array
    {
        if ($arg['section'] != "xbackground") {
            return $arg;
        }

        $this->saveSetting($arg, "image");
        $this->saveSetting($arg, "image_opacity");
        $this->saveSetting($arg, "blur");
        $this->saveSetting($arg, "gray");
        $this->saveSetting($arg, "placement");
        $this->saveSetting($arg, "text_color");
        $this->saveSetting($arg, "content_opacity");

        return $arg;
    }

    /**
     * Returns the html of the background image selection boxes.
     *
     * @return string
     */
    private function getBackgroundBoxes(): string
    {
        $html = "";
        foreach ($this->images as $image => $properties) {
            $attr = [
                "id" => "xbackground-image-box-$image",
                "class" => "xbackground-image-box settings",
                "onclick" => "xbackground.changeImage('$image')",
            ];

            if ($image == "custom") {
                if ($this->hasUploadedImage()) {
                    $attr['style'] = "background-image: url(" . $this->getUploadedImageUrl() . ") !important";
                }
            } else {
                $attr['style'] = "background-image: url(" .
                    Utils::assetPath("plugins/xbackground/assets/backgrounds/$image.png") . ") !important";
            }

            $html .= html::tag("span", $attr, " ");
        }

        return $html;
    }

    /**
     * Returns the file system directory for the uploaded background images.
     *
     * @return string
     */
    private function getUploadDir(): string
    {
        return rtrim(
            trim($this->rcmail->config->get("xbackground_upload_dir", RCUBE_INSTALL_PATH . "data/xbackground")),
        "/\\") . "/";
    }

    /**
     * Returns the url to the uploaded background image file.
     *
     * @return string
     */
    private function getUploadedImageUrl(): string
    {
        return rtrim(trim($this->rcmail->config->get("xbackground_upload_url", "data/xbackground")), "/") .
            "/" . Utils::structuredDirectory($this->userId) . Utils::encodeId($this->userId) .
            ".jpg" . "?t=" .
            ($this->hasUploadedImage() ? filemtime($this->getUploadedImageFile()) : "");
    }

    /**
     * Returns the file system path to the uploaded background image.
     *
     * @return string
     */
    private function getUploadedImageFile(): string
    {
        return $this->getUploadDir() . Utils::structuredDirectory($this->userId) . Utils::encodeId($this->userId) . ".jpg";
    }

    /**
     * Returns true if an uploaded background image exists, false otherwise.
     *
     * @return bool
     */
    private function hasUploadedImage(): bool
    {
        return file_exists($this->getUploadedImageFile());
    }

    /**
     * Returns true if the upload is enabled (config + directory writable), false otherwise.
     *
     * @return bool
     */
    private function isUploadEnabled(): bool
    {
        return $this->rcmail->config->get("xbackground_upload_enabled", true) && is_writable($this->getUploadDir());
    }

    /**
     * Handles the ajax request for uploading the custom background image. We're naming the file .jpg regardless of
     * what type of image it is, modern browsers can figure out the type and it saves us the trouble of having to save
     * the image type somewhere.
     *
     * @codeCoverageIgnore
     */
    private function uploadImage()
    {
        try {
            if (!$this->isUploadEnabled()) {
                throw new Exception();
            }

            if (!is_writable($dir = $this->getUploadDir())) {
                throw new Exception("Upload directory doesn't exist or is not writable.");
            }

            $maxSize = $this->rcmail->config->get("xbackground_upload_size_limit", 1000000);
            $targetFile = $dir . Utils::structuredDirectory($this->userId) . Utils::encodeId($this->userId) . ".jpg";
            $error = "";

            if (!$this->saveUploadedImage($_FILES['file'], $targetFile, $maxSize, $error, false)) {
                throw new Exception($error);
            }

            // save image brightness and send response
            $this->saveUploadedImageLightness($targetFile);
            Response::success(["url" => $this->getUploadedImageUrl()]);

        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    /**
     * Analyzes the image and saves the customDark setting, so when the textColor is set to auto, we can use this
     * value.
     *
     * @param string $file
     */
    private function saveUploadedImageLightness(string $file)
    {
        // analyze the image to determine whether it's mostly dark or light
        list($sourceWidth, $sourceHeight, $sourceType) = getimagesize($file);

        switch ($sourceType) {
            case IMAGETYPE_GIF:
                $sourceImage = imagecreatefromgif($file);
                break;
            case IMAGETYPE_JPEG:
                $sourceImage = imagecreatefromjpeg($file);
                break;
            case IMAGETYPE_PNG:
                $sourceImage = imagecreatefrompng($file);
                break;
            default:
                $sourceImage = false;
        }

        if (!$sourceImage) {
            return;
        }

        $targetSize = 50;
        $sourceAspectRatio = $sourceWidth / $sourceHeight;

        if ($sourceWidth <= $targetSize && $sourceHeight <= $targetSize) {
            $targetWidth = $sourceWidth;
            $targetHeight = $sourceHeight;
        } elseif ($sourceAspectRatio < 1) {
            $targetWidth = (int)($targetSize * $sourceAspectRatio);
            $targetHeight = $targetSize;
        } else {
            $targetWidth = $targetSize;
            $targetHeight = (int)($targetSize / $sourceAspectRatio);
        }

        $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight);
        imagefilter($targetImage, IMG_FILTER_GRAYSCALE);
        imagedestroy($sourceImage);

        $darkCount = 0;
        $lightCount = 0;
        $threshold = 150; // this is the threshold that determines whether the pixel is light or dark

        for ($x = 0; $x < $targetWidth; $x++) {
            for ($y = 0; $y < $targetHeight; $y++) {
                $rgb = imagecolorat($targetImage, $x, $y);
                $gray = ((($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF)) / 3; // 0 (black) - 255 (white)

                if ($gray < $threshold) {
                    $darkCount++;
                } else {
                    $lightCount++;
                }
            }
        }

        imagedestroy($targetImage);

        $pref = $this->rcmail->user->get_prefs();
        $pref['xbackground_custom_dark'] = $darkCount > $lightCount;
        $this->rcmail->user->save_prefs($pref);
    }

    /**
     * Handles ajax request for the deletion of the custom background image.
     *
     * @codeCoverageIgnore
     */
    private function deleteImage()
    {
        if (!$this->isUploadEnabled()) {
            Response::error();
        }

        $file = $this->getUploadedImageFile();
        Response::send(!file_exists($file) || @unlink($file));
    }
}