<?php

namespace AdeN\Api\Helpers;

/**
 * Parse and build criteria expressions
 */
class HttpHelper {

    public static function parse($requestContent, $base64 = true)
    {
        //$base64 = false;

        if ($base64) {
            $data = base64_decode($requestContent);
        } else {
            $data = $requestContent;
        }

        return json_decode($data);
    }
}