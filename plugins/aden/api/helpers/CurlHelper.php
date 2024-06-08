<?php

namespace AdeN\Api\Helpers;

use Request;
use Illuminate\Support\Facades\Config;
use Wgroup\SystemParameter\SystemParameter;

/**
 * Parse and build sql expressions
 */
class CurlHelper
{
    public static function downloadFileFromUrl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, false);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $result = curl_exec($ch);
        curl_close($ch);
        return ($result);
    }
}
