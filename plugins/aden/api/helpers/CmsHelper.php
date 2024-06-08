<?php

namespace AdeN\Api\Helpers;

use Request;
use Illuminate\Support\Facades\Config;
use Wgroup\SystemParameter\SystemParameter;

/**
 * Parse and build sql expressions
 */
class CmsHelper
{
    public static function getThemeUrl()
    {
        $themeDir = Config::get('cms.themesPath');
        $activeTheme = Config::get('cms.activeTheme');
        $urlSite = Config::get('cms.urlSite');

        return $urlSite . $themeDir . '/' . $activeTheme;
    }

    public static function getThemePath()
    {
        $themeDir = Config::get('cms.themesPath');
        $activeTheme = Config::get('cms.activeTheme');
        $basePath = base_path($themeDir . '/' . $activeTheme);

        return $basePath;
    }

    public static function getUrlSite()
    {
        $urlSite = Config::get('cms.urlSite');

        return $urlSite;
    }

    public static function getInstance()
    {
        $instance = Config::get('cms.instance');

        return $instance;
    }

    public static function getMimeTypes()
    {
        $default = 'mimes:jpg,png,jpeg,bmp,gif,pdf,xls,xlsx,doc,docx,msg,ppt,pptx,tif';

        $param = SystemParameter::whereNamespace('config')->whereGroup('mime_types')->first();

        return $param && !empty(trim($param->item)) ? $param->item : $default;
    }

    public static function getAppPath($path)
    {
        return '/storage/' . ($path ? $path : $path);
    }

    public static function getStorageTemplateDir($path)
    {
        return base_path(). "/storage/" . ($path ? $path : $path);
    }

    /**
     * Define the storage path, override this method to define.
     */
    public static function getStorageDirectory($path = '')
    {
        $uploadsDir = Config::get('cms.storage.export.path');

        return base_path() . $uploadsDir . '/public/' . ($path ? $path : $path);
    }

    /**
     * Define the public address for the storage path.
     */
    public static function getPublicDirectory($path = '')
    {
        $uploadsDir = Config::get('cms.storage.export.path');
        return Request::getBasePath() . $uploadsDir . '/public/' . ($path ? $path : $path);
    }

    public static function csvToStr(array $fields)
    {
        $f = fopen('php://memory', 'r+');
        foreach ($fields as $value) {
            if (fputcsv($f, array_map("utf8_decode", $value)) === false) {
                return false;
            }
        }
        rewind($f);
        $csv = stream_get_contents($f);
        return $csv;
    }

    public static function makeDir($dirPath, $mode = 0777)
    {
        return is_dir($dirPath) || mkdir($dirPath, $mode, true);
    }

    public static function prependEmptyItemInArray($data)
    {
        if (count($data) == 0) {
            return $data;
        }

        $keys = array_keys($data[0]);

        $newItem = [];

        foreach ($keys as $key) {
            $newItem[$key] = null;
        }

        array_unshift($data, $newItem);

        return $data;
    }

    public static function parseToStdClass($value)
    {
        if (is_array($value)) {
            return self::jsonDecode(json_encode($value));
        } else if (is_string($value)) {
            return self::jsonDecode($value);
        }
    }

    public static function parseToArray($value)
    {
        if (is_object($value)) {
            return self::jsonDecode(json_encode($value), true);
        } else if (is_string($value)) {
            return self::jsonDecode($value, true);
        }
    }

    public static function jsonDecode($json, $assoc = false, $depth = 512, $options = 0)
    {
        // search and remove comments like /* */ and //
        $json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', $json);

        if (version_compare(phpversion(), '5.4.0', '>=')) {
            return json_decode($json, $assoc, $depth, $options);
        } elseif (version_compare(phpversion(), '5.3.0', '>=')) {
            return json_decode($json, $assoc, $depth);
        } else {
            return json_decode($json, $assoc);
        }
    }

    public static function isEmptyOrNull($value)
    {
        return ($value === null || empty(trim($value))) && $value != '0';
    }

    public static function boolVal($val, $return_null = false)
    {
        $boolval = (is_string($val) ? filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) : (bool) $val);
        return ($boolval === null && !$return_null ? false : $boolval);
    }
}
