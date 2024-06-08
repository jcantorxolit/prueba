<?php

namespace AdeN\Api\Helpers;

use AdeN\Api\Classes\Criteria;
use Carbon\Carbon;

/**
 * Parse and build criteria expressions
 */
class CriteriaHelper
{

    public static function parse($requestContent, $mandatoryFilters = null)
    {
        if ($requestContent == null || $requestContent == "") {
            return null;
        }

        if (is_string($requestContent)) {
            $request = json_decode($requestContent);
        } else if (is_object($requestContent)) {
            $request = $requestContent;
        } else {
            throw new \Exception("invalid parameters", 403);
        }


        $criteria = new Criteria();
        if ($request != null) {
            if (property_exists($request, "page")) {
                $criteria->currentPage = $request->page;
            }

            if (property_exists($request, "start")) {
                $criteria->currentPage = ($request->start / $request->length) + 1;
            }

            if (property_exists($request, "pageSize")) {
                $criteria->pageSize = $request->pageSize;
            }

            if (property_exists($request, "length")) {
                $criteria->pageSize = $request->length;
            }

            if (property_exists($request, "sort")) {
                $criteria->sorts = $request->sort;
            }

            if (property_exists($request, "order")) {
                $criteria->sorts = $request->order;
            }

            if (property_exists($request, "filter")) {
                $criteria->filter = $request->filter;
                $criteria->filter->filters = array_map(function ($filter) {
                    $filter->value = CriteriaHelper::parseValue($filter->value);
                    return $filter;
                }, $criteria->filter->filters);                
            } else {
                if (property_exists($request, "filters")) {
                    $criteria->filter = new \stdClass();
                    $criteria->filter->filters = array_map(function ($item) {
                        $filter = new \stdClass();
                        $filter->field = $item->field->name;
                        $filter->operator = $item->criteria->value;
                        $filter->value = CriteriaHelper::parseValue($item->value);
                        return $filter;
                    }, $request->filters);
                }
            }

            if (property_exists($request, "draw")) {
                $criteria->draw = $request->draw;
            }

            if (property_exists($request, "search")) {
                $criteria->search = CriteriaHelper::parseValue($request->search);
            }

            if (property_exists($request, "type")) {
                $criteria->type = $request->type;
            }

            if (property_exists($request, "columns")) {
                if ($criteria->filter == null) {

                }
            }

            if (property_exists($request, "lang")) {
                $criteria->lang = $request->lang;
            }

            if ($mandatoryFilters != null) {
                if (is_array($mandatoryFilters)) {

                    foreach ($mandatoryFilters as $filter) {

                        $filterOption = CriteriaHelper::parseFilter($filter);

                        if (property_exists($request, $filterOption->field)) {
                            if ($request->{"$filterOption->field"} !== null) {
                                $mandatoryFilter = new \stdClass();
                                $mandatoryFilter->operator = $filterOption->operator;
                                $mandatoryFilter->value = CriteriaHelper::parseValue($request->{"$filterOption->field"});
                                $mandatoryFilter->field = $filterOption->field;
                                $criteria->mandatoryFilters[] = $mandatoryFilter;
                            }
                        }
                    }
                }
            }
        }

        return $criteria;
    }

    public static function addFilters($criteria, $filters = null)
    {
        if ($criteria == null) {
            $criteria = new Criteria();
        }

        if ($filters != null) {
            if (is_array($filters)) {

                $criteriaFilters = [];

                foreach ($filters as $filter) {

                    $filterOption = CriteriaHelper::parseFilter($filter);
                    $criteriaFilter = new \stdClass();
                    $criteriaFilter->field = $filterOption->field;
                    $criteriaFilter->operator = $filterOption->operator;
                    $criteriaFilter->value = '';

                    if (property_exists($filterOption, "value")) {
                        $criteriaFilter->value = CriteriaHelper::parseValue($filterOption->value);
                    } else {
                        if (property_exists($criteria, $filterOption->field)) {
                            $criteriaFilter->value = CriteriaHelper::parseValue($criteria->{$filterOption->field});
                        }
                    }

                    if ($criteriaFilter->value != '') {
                        $criteriaFilters[] = $criteriaFilter;
                    }
                }

                if (property_exists($criteria, "filter")) {
                    if (is_object( $criteria->filter)) {
                        if (property_exists($criteria->filter, "filters")) {
                            $criteria->filter->filters = array_merge($criteriaFilters, $criteria->filter->filters);
                        } else {
                            $criteria->filter->filters = $criteriaFilters;
                        }
                    } else {
                        $criteria->filter = new \stdClass();
                        $criteria->filter->filters = $criteriaFilters;
                    }
                } else {
                    $criteria->filter = new \stdClass();
                    $criteria->filter->filters = $criteriaFilters;
                }
            }
        }

        return $criteria;
    }

    public static function addMandatoryFilter($criteria, $mandatoryFilters = null)
    {
        if ($criteria == null) {
            $criteria = new Criteria();
        }

        if ($mandatoryFilters != null) {
            if (is_array($mandatoryFilters)) {

                foreach ($mandatoryFilters as $filter) {

                    $filterOption = CriteriaHelper::parseFilter($filter);
                    
                    $mandatoryFilter = CriteriaHelper::filterField($criteria->mandatoryFilters, $filterOption->field);

                    if ($mandatoryFilter == null) {
                        $mandatoryFilter = new \stdClass();
                        $mandatoryFilter->operator = $filterOption->operator;
                        $mandatoryFilter->value = CriteriaHelper::parseValue($filterOption->value);
                        $mandatoryFilter->field = $filterOption->field;
                        $criteria->mandatoryFilters[] = $mandatoryFilter;
                    } else {
                        $mandatoryFilter->operator = $filterOption->operator;
                        $mandatoryFilter->value = CriteriaHelper::parseValue($filterOption->value);
                        $mandatoryFilter->field = $filterOption->field;
                    }
                }
            }
        }

        return $criteria;
    }

    public static function getFilter($criteria, $field)
    {
        if ($criteria == null) {
            return null;
        }

        if (!property_exists($criteria, "filter")) {
            return null;
        }

        if (!property_exists($criteria->filter, "filters")) {
            return null;
        }

        return CriteriaHelper::filterField($criteria->filter->filters, $field);
    }

    public static function getMandatoryFilter($criteria, $field)
    {
        if ($criteria == null) {
            return null;
        }

        if (!property_exists($criteria, "mandatoryFilters")) {
            return null;
        }

        return CriteriaHelper::filterField($criteria->mandatoryFilters, $field);
    }

    private static function filterField($filters, $field)
    {        
        if (!is_array($filters)) {
            return null;
        }

        foreach ($filters as $filter) {
            if ($filter->field == $field) {                
                return $filter;
            }
        }

        return null;
    }

    private static function parseFilter($filter)
    {
        $filterOption = new \stdClass();

        try {

        if (is_array($filter)) {
            $filterOption->field = $filter["field"];
            $filterOption->operator = $filter["operator"];
            if (array_key_exists('value', $filter)) {
                $filterOption->value = is_object($filter["value"]) && isset($filter["value"]->value) ? CriteriaHelper::parseValue($filter["value"]->value) : CriteriaHelper::parseValue($filter["value"]) ;
            }
        }

        return $filterOption;
        } catch (Exception $exception) {
            dd($exception, $filter);
        }
    }

    private static function parseValue($value)
    {
        $data = is_object($value) ? $value->value : $value;

        $formats = ['d-m-Y', 'd/m/Y', 'd-m-Y H:i:s', 'd/m/Y H:i:s', 'Y-m-d', 'Y/m/d', 'Y-m-d H:i:s', 'Y/m/d H:i:s'];

        $date = null;

        foreach ($formats as $format) {             
            try {
                $date = Carbon::createFromFormat( $format, $data );
                if ($date != null) {
                    break;
                }
            } catch (\Exception $e) {                
            }
        }

        if (is_object($value)) {
            $value->value = $date ? $date->format('Y-m-d') : $value->value;
        } else {
            $value = $date ? $date->format('Y-m-d') : $value;
        }

        return $value;
    }
}