<?php

namespace AdeN\Api\Helpers;

use AdeN\Api\Classes\Criteria;

/**
 * Parse and build criteria expressions
 */
class KendoCriteriaHelper {

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
                $criteria->currentPage  = $request->page;
            }

            if (property_exists($request, "pageSize")) {
                $criteria->pageSize = $request->pageSize;
            } else {
                $criteria->pageSize = 0;
            }

            if (property_exists($request, "sort")) {
                $criteria->sorts = $request->sort;
            }

            if (property_exists($request, "filter")) {
                $criteria->filter = $request->filter;
            }

            if (property_exists($request, "take")) {
                $criteria->take = $request->take;
            }

            if (property_exists($request, "skip")) {
                $criteria->skip = $request->skip;
            }

            if (!property_exists($request, "draw")) {
                $criteria->draw = 0;
            }

            if ($mandatoryFilters != null) {
                if (is_array($mandatoryFilters)) {

                    foreach ($mandatoryFilters as $filter) {

                        $filterOption = KendoCriteriaHelper::parseFilter($filter);

                        if (property_exists($request, $filterOption->field)) {
                            $array = get_object_vars($request);
                            $mandatoryFilter = new \stdClass();
                            $mandatoryFilter->operator = $filterOption->operator;
                            $mandatoryFilter->value = $array[$filterOption->field];
                            $mandatoryFilter->field = $filterOption->field;
                            $criteria->mandatoryFilters[] = $mandatoryFilter;
                        }
                    }
                }
            }
        }

        return $criteria;
    }

    private static function parseFilter($filter)
    {
        $filterOption = new \stdClass();

        if (is_array($filter)) {
            $filterOption->field = $filter["field"];
            $filterOption->operator = $filter["operator"];
        } else {
            $filterOption->field = $filter["field"];
            $filterOption->operator = "eq";
        }

        return $filterOption;
    }
}