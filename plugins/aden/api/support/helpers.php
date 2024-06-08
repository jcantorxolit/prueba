<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Str;

if (!function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('str_starts_with')) {
    function str_starts_with ($string, $startString)
    {
        $len = strlen($startString);
        return (substr($string, 0, $len) === $startString);
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with($string, $endString)
    {
        $len = strlen($endString);
        if ($len == 0) {
            return true;
        }
        return (substr($string, -$len) === $endString);
    }
}

if (!function_exists('envi')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function envi($key, $default = null)
    {
        if (!array_key_exists($key, $_ENV) || $_ENV[$key] === null) {
            loadEnvi();
        }

        $value = array_key_exists($key, $_ENV) ? $_ENV[$key] : false;

        if ($value === false) {
            return value($default);
        }

        if (is_array($value)) {
            return $value;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }

        if (strlen($value) > 1 && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return substr($value, 1, -1);
        }

        //var_dump("$key::$value");

        return $value;
    }
}


if (!function_exists('array_dot')) {
    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param  array   $array
     * @param  string  $prepend
     * @return array
     */
    function array_dot($array, $prepend = '')
    {
        return Illuminate\Support\Arr::dot($array, $prepend);
    }
}

if (!function_exists('loadEnvi')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param  string  $key
     * @param  mixed   $default
     * @return mixed
     */
    function loadEnvi()
    {
        $variables = require __DIR__.'/../../../../.env.php';

        foreach ($variables as $key => $value)
		{
            $_ENV[$key] = $value;
        }
    }
}
