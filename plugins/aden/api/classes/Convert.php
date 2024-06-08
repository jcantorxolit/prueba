<?php
/**
 * Created by PhpStorm.
 * User: David Blandon
 * Date: 4/25/2016
 * Time: 5:43 PM
 */

namespace AdeN\Api\Classes;


class Convert
{
    public static function toInt($value) {
        return intval($value);
    }

    public static function toDecimal($value) {
        return str_replace(".", ",", floatval($value));
    }
}