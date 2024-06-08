<?php

namespace AdeN\Api\Helpers;

use DB;
use Illuminate\Database\Query\Expression;
use Carbon\Carbon;
use Log;

/**
 * Parse and build sql expressions
 */
class SqlHelper
{

    public static function getOperator($filterOperator)
    {
        $operator = "=";

        switch ($filterOperator) {
            case "eq":
                $operator = "=";
                break;
            case "neq":
                $operator = "<>";
                break;
            case "like":
            case "contains":
            case "startswith":
            case "endswith":
                $operator = "LIKE";
                break;
            case "null":
                $operator = "IS NULL";
                break;
            case "notInRaw":
            case "inRaw":
            case "in":
            case "notId":
                $operator = "";
                break;
            case "doesnotcontain":
                $operator = "NOT LIKE";
                break;
            case "gt":
                $operator = ">";
                break;
            case "lt":
                $operator = "<";
                break;
            case "gte":
                $operator = ">=";
                break;
            case "lte":
                $operator = "<=";
                break;
        }

        return $operator;
    }

    public static function getCondition($filter, $defaultValue = 'or')
    {
        if (!is_object($filter)) {
            return $defaultValue;
        }

        if (!property_exists($filter, 'condition')) {
            return $defaultValue;
        }

        return is_object($filter->condition) ? $filter->condition->value : $filter->condition;
    }

    public static function getPreparedData($filter)
    {
        $value = is_object($filter->value) ? $filter->value->value : $filter->value;

        switch ($filter->operator) {
            case "eq":
            case "neq":
                $data = $value;
                break;
            case "contains":
            case "like":
                $data = "%$value%";
                break;
            case "startswith":
                $data = "$value%";
                break;
            case "endswith":
                $data = "%$value";
                break;
            case "doesnotcontain":
                $data = "%$value%";
                break;
            case "null":
                $data = null;
                break;
            case "in":
                $data = is_array($value) ? $value : [ $value ];
                break;
            default:
                $data = $value;
        }

        return $data;
    }

    public static function getPreparedField($field, $isSortField = false)
    {
        $instanceOfExpression = $field instanceof Expression;

        if ($instanceOfExpression) {
            $field = $field->getValue();
            if (!$isSortField) {
                $field = str_ireplace(' (', '(', $field);
                $field = str_ireplace(['MAX(', 'MIN(', 'COUNT(', 'AVG('], '(', $field);
            }
        }

        $position = strripos($field, " as ");

        if ($position === false) {
            $result = $field;
        } else {
            $result = substr($field, 0, $position);
        }

        return $instanceOfExpression ? DB::raw('(' . $result . ')') : $result;
    }

    public static function getPreparedOrderField($field)
    {
        return self::getPreparedField($field, true);
    }
}
