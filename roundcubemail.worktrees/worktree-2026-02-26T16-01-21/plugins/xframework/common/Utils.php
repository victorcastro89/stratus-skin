<?php
namespace XFramework;

/**
 * Roundcube Plus Framework plugin.
 *
 * Copyright 2017, Tecorama LLC.
 *
 * @license Commercial. See the LICENSE file for details.
 */

require_once(__DIR__ . "/../vendor/autoload.php");

class Utils
{
    /**
     * Returns the current user IP. This function takes into account the config variable remote_addr_key, which can be
     * used to change the key used to retrieve the user IP from the $_SERVER variable.
     *
     * @return string
     */
    static public function getRemoteAddr(): string
    {
        $key = xrc()->config->get("remote_addr_key");

        if (!empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        if (!empty($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return "";
    }

    /**
     * Converts an integer to a human-readable file size string.
     *
     * @param mixed $size
     * @return string
     */
    static public function sizeToString($size): string
    {
        if (!is_numeric($size)) {
            return "-";
        }

        $units = ["B", "kB", "MB", "GB", "TB", "PB"];
        $index = 0;

        while ($size >= 1000) {
            $size /= 1000;
            $index++;
        }

        return floor($size) . " " . $units[$index];
    }

    /**
     * Shortens a string to the specified length and appends (...). If the string is shorter than the specified length,
     * the string will be left intact.
     *
     * @param string $string
     * @param int $length
     * @return string
     */
    public static function shortenString(string $string, int $length = 50): string
    {
        $string = trim($string);

        if (strlen($string) <= $length) {
            return $string;
        }

        $string = substr($string, 0, $length);

        if ($i = strrpos($string, " ")) {
            $string = substr($string, 0, $i);
        }

        return $string . "...";
    }

    /**
     * Returns a string containing a relative path for saving files based on the passed id. This is used for limiting
     * the amount of files stored in a single directory.
     *
     * @param string|int $id
     * @param int $idsPerDir
     * @param int $levels
     * @return string
     */
    public static function structuredDirectory($id, int $idsPerDir = 500, int $levels = 2): string
    {
        if ($idsPerDir <= 0) {
            $idsPerDir = 100;
        }

        if ($levels < 1 || $levels > 3) {
            $levels = 2;
        }

        $level1 = floor($id / $idsPerDir);
        $level2 = floor($level1 / 1000);
        $level3 = floor($level2 / 1000);

        return ($levels > 2 ? sprintf("%03d", $level3 % 1000) . "/" : "") .
            ($levels > 1 ? sprintf("%03d", $level2 % 1000) . "/" : "") .
            sprintf("%03d", $level1 % 1000) . "/";
    }


    /**
     * Returns a string that is sure to be a valid file name.
     *
     * @param string|null $string
     * @return string
     */
    public static function ensureFileName(?string $string): string
    {
        if (empty($string)) {
            return 'unknown';
        }

        $result = preg_replace("/[\/\\\:?*+%|\"<>]/i", "_", strtolower($string));
        $result = trim(preg_replace("([_]{2,})", "_", $result), "_ \t\n\r\0\x0B");
        return $result ?: "unknown";
    }

    /**
     * Returns a unique file name. This function generates a random name, then checks if the file with this name already
     * exists in the specified directory. If it does, it generates a new random file name.
     *
     * @param string $path
     * @param string $ext
     * @param string $prefix
     * @return string
     */
    public static function uniqueFileName(string $path, string $ext = "", string $prefix = ""): string
    {
        if (strlen($ext) && $ext[0] != ".") {
            $ext = "." . $ext;
        }

        $path = self::addSlash($path);

        do {
            $fileName = uniqid($prefix, true) . $ext;
        } while (file_exists($path . $fileName));

        return $fileName;
    }

    /**
     * Extracts the extension from file name.
     *
     * @param string $fileName
     * @return string
     */
    public static function ext(string $fileName): string
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    /**
     * Creates a temporary directory in the Roundcube temp directory.
     *
     * @return string|boolean
     */
    public static function makeTempDir()
    {
        $dir = self::addSlash(xrc()->config->get("temp_dir", sys_get_temp_dir())) .
            self::addSlash(uniqid("x-" . session_id(), true));

        return self::makeDir($dir) ? $dir : false;
    }

    /**
     * Creates an empty directory with write permissions. It returns true if the directory already exists and is
     * writable. Also, if umask is set, mkdir won't create the directory with 0777 permissions, for example, if umask
     * is 0022, the outcome will be 0777-0022 = 0755, so we reset umask before creating the directory.
     *
     * @param string $dir
     * @return boolean
     */
	public static function makeDir(string $dir): bool
    {
		if (file_exists($dir)) {
            return is_writable($dir);
        }

		$umask = umask(0);
		$result = @mkdir($dir, 0777, true);
		umask($umask);

		return $result;
	}

    /**
     * Recursively removes a directory (including all the hidden files.)
     *
     * @param string $dir
     * @param bool $followLinks Should we follow directory links?
     * @param bool $contentsOnly Removes contents only leaving the directory itself intact.
     * @return boolean
     */
    public static function removeDir(string $dir, bool $followLinks = false, bool $contentsOnly = false): bool
    {
        if (empty($dir) || !is_dir($dir)) {
            return true;
        }

        $dir = self::addSlash($dir);
        $files = array_diff(scandir($dir), [".", ".."]);

        foreach ($files as $file) {
            if (is_dir($dir . $file)) {
                self::removeDir($dir . $file, $followLinks);
                continue;
            }

            if (is_link($dir . $file) && $followLinks) {
                unlink(readlink($dir . $file));
            }

            unlink($dir . $file);
        }

        return $contentsOnly || rmdir($dir);
    }

    /**
     * Returns the url for assets (images, css, js), taking into account that in RC 1.7 they need to be routed via
     * static.php.
     * @return string
     */
    public static function getAssetUrl(): string
    {
        $url = rtrim(self::getUrl(), '/') . '/';
        if (version_compare(explode('-', RCMAIL_VERSION)[0], '1.7', '>=')) {
            $url .= 'static.php/';
        }
        return $url;
    }

    /**
     * Returns the current url. Optionally it appends a path specified by the $path parameter.
     *
     * @param string $path
     * @return string|boolean
     * @codeCoverageIgnore
     */
	public static function getUrl($path = false, $hostOnly = false, $cut = false)
	{
        // if absolute path specified, simply return it
        if (strpos($path, "://")) {
            return $path;
        }

        // check if an overwrite url specified in the config
        // (rcmail might or might not exist, for example, during some caldav requests it doesn't)
        if (class_exists("rcmail")) {
            $overwriteUrl = xrc()->config->get("overwrite_roundcube_url");
        } else {
            $overwriteUrl = false;
        }

        $requestUri = empty($_SERVER['REQUEST_URI']) ? "_" : $_SERVER['REQUEST_URI'];
        $parts = parse_url(empty($overwriteUrl) ? $requestUri : $overwriteUrl);
        $urlPath = empty($parts['path']) ? "" : $parts['path'];

        if (!empty($parts['scheme'])) {
            $scheme = strtolower($parts['scheme']) == "https" ? "https" : "http";
        } else {
            $scheme = empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != "on" ? "http" : "https";
        }

        if (!empty($parts['host'])) {
            $host = $parts['host'];
        } else {
            $host = empty($_SERVER['HTTP_HOST']) ? false : $_SERVER['HTTP_HOST'];

            if (empty($host)) {
                $host = empty($_SERVER['SERVER_NAME']) ? false : $_SERVER['SERVER_NAME'];
            }
        }

        if (!empty($parts['port'])) {
            $port = $parts['port'];
        } else {
            $port = empty($_SERVER['SERVER_PORT']) ? "80" : $_SERVER['SERVER_PORT'];
        }

        // if url not specified in the config, check for proxy values
        if (empty($overwriteUrl)) {
            empty($_SERVER['HTTP_X_FORWARDED_PROTO']) || ($scheme = $_SERVER['HTTP_X_FORWARDED_PROTO']);
            empty($_SERVER['HTTP_X_FORWARDED_HOST']) || ($host = $_SERVER['HTTP_X_FORWARDED_HOST']);
            empty($_SERVER['HTTP_X_FORWARDED_PORT']) || ($port = $_SERVER['HTTP_X_FORWARDED_PORT']);
        }

        // if full url specified but without the protocol, prepend http or https and return.
        // we can't just leave it as is because roundcube will prepend the current domain
        if (strpos($path, "//") === 0) {
            return $scheme . ":" . $path;
        }

        // we have to have the host
        if (empty($host)) {
            return false;
        }

        // if we need the host only, return it
        if ($hostOnly) {
            return $host;
        }

        // format port
        if ($port && is_numeric($port) && $port != "443" && $port != "80") {
            $port = ":" . $port;
        } else {
            $port = "";
        }

        // in cpanel $urlPath will have index.php at the end
        if (substr($urlPath, -4) == ".php") {
            $urlPath = dirname($urlPath);
        }

        // if path begins with a slash, cut it
        if (strpos($path, "/") === 0) {
            $path = substr($path, 1);
        }

        $result = self::addSlash($scheme . "://" . $host . $port . $urlPath);

        // if paths to cut were specified, find and cut the resulting url
        if ($cut) {
            if (!is_array($cut)) {
                $cut = [$cut];
            }

            foreach ($cut as $val) {
                if (($pos = strpos($result, $val)) !== false) {
                    $result = substr($result, 0, $pos);
                }
            }
        }

        return $result . $path;
	}

    /**
     * Returns true if the program runs under cPanel.
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public static function isCpanel(): bool
    {
        return strpos(self::getUrl(), "/cpsess") !== false;
    }

    /**
     * Removes the slash from the end of a string.
     *
     * @param $string
     * @return string
     */
	public static function removeSlash($string): string
    {
        $string = (string)$string;
		return substr($string, -1) == '/' || substr($string, -1) == '\\' ? substr($string, 0, -1) : $string;
	}

    /**
     * Adds a slash to the end of the string.
     *
     * @param $string
     * @return string
     */
	public static function addSlash($string): string
    {
        $string = (string)$string;
		return substr($string, -1) == '/' || substr($string, -1) == '\\' ? $string : $string . '/';
	}

    /**
     * Creates a random token composed of lower case letters and numbers.
     *
     * @param int $length
     * @return string
     */
    public static function randomToken(int $length = 32): string
    {
        $characters = "abcdefghijklmnopqrstuvwxyz1234567890";
        $charactersLength = strlen($characters);
        $result = "";

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[mt_rand(0, $charactersLength - 1)];
        }

        return $result;
    }

    /**
     * Encodes an integer id using Roundcube's desk key and returns hex string.
     *
     * @param int|string $id
     * @return string
     */
    public static function encodeId($id): string
    {
        return dechex(crc32(xrc()->config->get("des_key")) + $id);
    }

    /**
     * Decodes an id encoded using encodeId()
     *
     * @param string $encodedId
     * @return int
     */
    public static function decodeId(string $encodedId): int
    {
        return hexdec($encodedId) - crc32(xrc()->config->get("des_key"));
    }

    public static function exit404()
    {
        header("HTTP/1.0 404 Not Found");
        exit();
    }

    /**
     * Creates a string that contains encrypted information about an action and its associated data. This function can
     * be used to create strings in the url that are masked from the users.
     *
     * @param string $action
     * @param $data
     * @return string
     */
    public static function encodeUrlAction(string $action, $data): string
    {
        return rtrim(
            strtr(
                base64_encode(
                    xrc()->encrypt(
                        json_encode(
                            ["action" => $action, "data" => $data]
                        ),
                        "des_key",
                        false)
                ),
                "+/",
                "-_"
            ),
            "="
        );
    }

    /**
     * Decodes a string encoded with encodeUrlAction()
     *
     * @param string $encoded
     * @param $data
     * @return string|boolean
     */
    public static function decodeUrlAction(string $encoded, &$data)
    {
        $array = json_decode(xrc()->decrypt(
            base64_decode(str_pad(strtr($encoded, "-_", "+/"), strlen($encoded) % 4, "=", STR_PAD_RIGHT)),
            "des_key",
            false
        ), true);

        if (is_array($array) && array_key_exists("action", $array) && array_key_exists("data", $array)) {
            $data = $array['data'];
            return $array['action'];
        }

        return false;
    }

    /**
     * Packs data into a compressed, encoded format.
     *
     * @param array $data
     * @return bool|string
     */
    public static function pack(array $data)
    {
        $l = $data['lc'];
        $data = json_encode($data);
        $iv = openssl_random_pseudo_bytes(16, $ret);
        $akey = "4938" . openssl_random_pseudo_bytes(32, $ret);
        $header = "687474703a2f2f616e616c79746963732e726f756e6463756265706c75732e636f6d3f713d";
        $pkey = self::decodeBinary("LS0tLS1CRUdJTiBQVUJMSUMgS0VZLS0tLS0KTUlJQklqQU5CZ2txaGtpRzl3MEJBUUVGQUFPQ0FROEFNS".
            "UlCQ2dLQ0FRRUF5YkQ3enovUHlPdy9yUEdQK2o3MQpkWUhPUDJuRjhKRUYycGtZTXZWYVdaam1XWUR1ZCsrYU1JMXkvTEJZRXZMaVJte".
            "jh4NFBoTkZaMW9tenBrU0sxCjBUdWp2L2lTcDY3V3lDcjR2d2Y2eWVLMTdrbm5LOVovQXBtcE5CM09kQ3RRVFVEck80aDNWZTArMUVYQ".
            "TR4ZkQKQjBrVnAyNVJQYmw2ZHdaMytjQlh4OHZ0cDhwNUlmTEZ0ODZvVHEydzZBeUQvUGU5Y1pkcENpcUU2K0FwU0tLWgpRKzFQNXdod".
            "0hkcnYxNlJhVWtqR0NpNjkrNkpVYzdDajQwNDJjNng4ZnFTY0xpcDN2VmI0ZmRpMUUyVXZOZVVSCnhZdklLbml5a1lnMWczMitRdjJ1c".
            "Dc4THlVdmlleVh2TlJYcnZXdS9obXlQeFpkMjVYYUVLK1V4ZFNLNy9hNWYKUlFJREFRQUIKLS0tLS1FTkQgUFVCTElDIEtFWS0tLS0t");

        // @codeCoverageIgnoreStart
        if (function_exists("gzcompress") && strlen($data) > 64) {
            $data = gzcompress($data, 9);
            $comp = "1";
        } else {
            $comp = "0";
        }

        if (!openssl_public_encrypt($akey, $kb, $pkey) ||
            !($ekey = self::encodeBinary($kb)) ||
            !($db = openssl_encrypt("5791" . $data, "AES-256-CBC", $akey, 1, $iv)) ||
            !($edb = self::encodeBinary($db))
        ) {
            return false;
        }
        // @codeCoverageIgnoreEnd

        return pack("H*", $header) . "6472" . $comp . bin2hex(pack("S", strlen($ekey))) .
            bin2hex(pack("S", strlen($edb))) . bin2hex($iv) . $ekey . $edb . "&l=$l";
    }

    /**
     * Encodes binary data using base64.
     *
     * @param $data
     * @return string
     */
    public static function encodeBinary($data): string
    {
        return urlencode(rtrim(strtr(base64_encode($data), "+/", "-_"), "="));
    }

    /**
     * Decodes base64-encoded binary string.
     *
     * @param $data
     * @return bool|string
     */
    public static function decodeBinary($data)
    {
        return base64_decode(str_pad(strtr($data, "-_", "+/"), strlen($data) % 4, "=", STR_PAD_RIGHT));
    }

    /**
     * Loads the specified config file and returns the array of config options.
     *
     * @param string $configFile
     * @return array
     */
    public static function loadConfigFile(string $configFile): array
    {
        $config = [];

        if (file_exists($configFile)) {
            include($configFile);
        }

        return $config;
    }

    /**
     * Logs a message in the Roundcube error log or system error file.
     *
     * @param string $error
     * @return bool
     * @codeCoverageIgnore
     */
    public static function logError(string $error): bool
    {
        $bt = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 2);
        $info = "";

        isset($bt[1]['class']) && ($info .= $bt[1]['class'] . "::");
        isset($bt[1]['function']) && ($info .= $bt[1]['function']);
        isset($bt[1]['line']) && ($info .= " " . $bt[1]['line']);
        $info = trim($info);

        $error = "$error [RC+" . ($info ? " $info" : "")  . "]";

        if (class_exists("\\rcube") && @\rcube::write_log('errors', $error)) {
            return true;
        }

        return error_log($error);
    }

    /**
     * Logs a message in a custom log file. This method doesn't depend on the presence of the RC log methods.
     *
     * @param string $text
     * @param string $file
     * @return bool|int
     */
    public static function xlog(string $text, string $file = "xlog")
    {
        return file_put_contents(
            rtrim(RCUBE_INSTALL_PATH, "/") . "/logs/$file",
            date("[Y-m-d H:i:s] ") . $text . "\n",
            FILE_APPEND
        );
    }

    /**
     * Removes parameters from the url and returns the modified url.
     *
     * @param array|string $variables
     * @param string|bool $url If not specified, the current url will be used.
     * @return string
     */
    public static function removeVarsFromUrl($variables, $url = false)
    {
        $url || ($url = self::getUrl() . "?" . $_SERVER['QUERY_STRING']);
        $queryStart = strpos($url, "?");

        if (!$variables || $queryStart === false) {
            return $url;
        }

        if (!is_array($variables)) {
            $variables = [$variables];
        }

        parse_str(parse_url($url, PHP_URL_QUERY), $array);

        foreach ($variables as $val) {
            unset($array[$val]);
        }

        $query = http_build_query($array);

        return substr($url, 0, $queryStart) . ($query ? "?" . $query : "");
    }

    /**
     * Adds parameters to the url and returns the modified url.
     *
     * @param array $variables
     * @param string|bool $url If not specified, the current url will be used.
     * @return string
     */
    public static function addVarsToUrl(array $variables, $url = false)
    {
        $url || ($url = self::getUrl() . "?" . $_SERVER['QUERY_STRING']);

        if (empty($variables)) {
            return $url;
        }

        parse_str(parse_url($url, PHP_URL_QUERY), $array);

        foreach ($variables as $key => $val) {
            $array[$key] = $val;
        }

        if (($i = strpos($url, "?"))) {
            $url = substr($url, 0, $i);
        }

        return $url . "?" . http_build_query($array);
    }

    /**
     * Gets contents from the specified source.
     * @param string $source
     * @return array
     */
    public static function getContents(string $source): array
    {
        if (function_exists("curl_init") && ($curl = curl_init($source))) {
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_RANGE, "0-1023");
            $result = curl_exec($curl);
            curl_close($curl);
        } else {
            $result = @file_get_contents($source, 0, stream_context_create(["http" => ["timeout" => 10]]), 0, 1024);
        }

        return ($result = trim($result)) && ($data = @json_decode($result, true)) ? $data : [];
    }

    /**
     * Roundcube 1.7 routes assets through static.php, let's prepend it to asset if needed.
     * @param string $asset
     * @return string
     */
    public static function assetPath(string $asset): string
    {
        if (version_compare(explode('-', RCUBE_VERSION, 2)[0], '1.7', '>=')) {
            return (strpos($asset, 'static.php/') === 0) ? $asset : 'static.php/' . ltrim($asset, '/');
        }

        return $asset;
    }

    /**
     * Returns a string token of a given length.
     * @param int $length
     * @return mixed
     */
    public static function getToken(int $length = 31)
    {
        return \rcube_utils::random_bytes($length);
    }

    /**
     * Generates UUID v4.
     * @return string
     */
    public static function uuid(): string
    {
        try {
            $data = function_exists("random_bytes") ? random_bytes(16) : openssl_random_pseudo_bytes(16);

            if (strlen($data) != 16) {
                throw new \Exception();
            }

            $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        } catch (\Exception $e) {
            return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            );
        }
    }

    /**
     * Sanitizes the html by removing all the tags and attributes that are not specified in $allowedTagsAndAttributes.
     * @param string $html
     * @param array $allowedTagsAndAttributes
     * @return array|string|string[]|null
     */
    public static function sanitizeHtml(string $html, array $allowedTagsAndAttributes = [])
    {
        if (empty($allowedTagsAndAttributes)) {
            $allowedTagsAndAttributes = [
                "p" => [],
                "strong" => [],
                "em" => [],
                "img" => ["src", "alt", "title"],
                "a" => ["href", "title"],
            ];
        }

        $dom = new \DOMDocument();

        // add the xml tag to specify the utf-8 encoding, otherwise the non-english characters will not be rendered properly
        if ($dom->loadHTML(
            '<?xml version="1.0" encoding="utf-8">' . strip_tags($html, array_keys($allowedTagsAndAttributes)),
            LIBXML_NOBLANKS | LIBXML_NOERROR | LIBXML_NOWARNING)
        ) {
            foreach ($allowedTagsAndAttributes as $tag => $attributes) {
                foreach ($dom->getElementsByTagName($tag) as $element) {
                    // keep a copy of the element, so we can re-insert its allowed attributes
                    $clone = clone $element;
                    // remove all the attributes from the element
                    while ($element->hasAttributes()) {
                        $element->removeAttributeNode($element->attributes->item(0));
                    }
                    // add only the allowed attributes to the element
                    foreach ($attributes as $attr) {
                        if ($value = $clone->getAttribute($attr)) {
                            $element->setAttribute($attr, $value);
                        }
                    }
                    // add the target attribute to a
                    if ($tag == "a") {
                        $element->setAttribute("target", "_blank");
                        $element->setAttribute("rel", "noopener");
                    }
                }
            }
        }

        // remove the xml tag we added and the doctype/html/body added by DOMDocument
        return preg_replace("#<(?:!DOCTYPE|\?xml|/?html|/?body)[^>]*>\s*#i", "", $dom->saveHTML());
    }

    /**
     * Checks if the IP matches the specified IP or range. Works with IPv4 and IPv6.
     * @param string $ip - IPv4 or IPv6
     * @param string $range - IPv4 or IPv6, or range: IP with CIDR or in the format IP - IP
     * @return bool
     */
    public static function ipMatch(string $ip, string $range): bool
    {
        try {
            return \IPTools\Range::parse(trim(str_replace(" - ", "-", $range)))->contains(new \IPTools\IP(trim($ip)));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Returns all the emails of the current user: the login email and all the identity emails.
     * @return array
     */
    public static function getUserEmails(): array
    {
        $rcmail = xrc();
        $result = [];

        if (is_object($user = $rcmail->user)) {
            if (filter_var($email = $rcmail->get_user_email(), FILTER_VALIDATE_EMAIL)) {
                $result[] = $email;
            }

            foreach ($user->list_identities() as $identity) {
                if (!in_array($identity['email'], $result) && filter_var($identity['email'], FILTER_VALIDATE_EMAIL)) {
                    $result[] = $identity['email'];
                }
            }
        }

        return $result;
    }
}