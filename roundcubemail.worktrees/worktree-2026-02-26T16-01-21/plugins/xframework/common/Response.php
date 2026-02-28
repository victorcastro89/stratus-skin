<?php
namespace XFramework;

/**
 * New ajax response class. Replacing Plugin::sendResponse()
 * Note, when using angularjs' $http.post, the data will be at response.data.data. When using xframework.ajax, it'll be as expected:
 * response.data.
 */
class Response
{
    public static function success($data = [], $message = "")
    {
        self::send(true, $data, $message);
    }

    public static function error($message = "")
    {
        self::send(false, [], $message);
    }

    public static function send($success = true, $data = [], $message = "", $statusCode = 200)
    {
        ob_get_contents() && @ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($statusCode);

        exit(json_encode([
            "success" => $success,
            "message" => $message,
            "data" => $data,
        ]));
    }
}