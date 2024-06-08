<?php

/**
 * Created by PhpStorm.
 * User: Usuario
 * Date: 4/25/2016
 * Time: 8:57 PM
 */

namespace AdeN\Api\Classes;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use AdeN\Api\Helpers\SqlHelper;

class BaseService
{
    protected $chart;

    function __construct()
    {
        $this->chart = new Chart();
    }

    public function getLimit(Criteria $criteria)
    {
        if ($criteria->pageSize == 0) {
            return "";
        }

        $startFrom = ($criteria->currentPage - 1) * $criteria->pageSize;
        return " LIMIT $startFrom , $criteria->pageSize";
    }

    public function prepareQuery($query, $alias = 'export')
    {
        return DB::table(DB::raw("($query) AS $alias"));
    }

    public function applyWhere($query, Criteria $criteria, $excludeMandatoryFields = [])
    {
        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    $applyFilter = ($excludeMandatoryFields == null || count($excludeMandatoryFields) == 0 || !in_array($item->field, $excludeMandatoryFields));
                    if ($applyFilter) {
                        switch ($item->operator) {
                            case 'in':
                            case 'inRaw':
                                $query->whereIn($item->field, SqlHelper::getPreparedData($item));
                                break;
                            case 'null':
                                $query->orWhereNull($item->field);
                                break;
                            default:
                                $query->where($item->field, SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                                break;
                        }
                    }
                }
            }

            if ($criteria->filter != null) {
                $filter = $criteria->filter;
                if (isset($filter->filters)) {
                    $query->where(function ($query) use ($filter) {
                        foreach ($filter->filters as $key => $item) {
                            try {
                                $query->where($item->field, SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), SqlHelper::getCondition($item));
                            } catch (Exception $ex) {
                            }
                        }
                    });
                }
            }
        }
    }

    public function getWhere(Criteria $criteria)
    {
        if ($criteria == null) {
            return '';
        }

        $where = '';

        /*if ($criteria->mandatoryFilters != null) {
            foreach ($criteria->mandatoryFilters as $item) {
                $where .= $this->where(SqlHelper::getPreparedField($item->field), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
            }
        }*/

        if ($criteria->filter != null) {
            $where .= $this->whereGrouped($criteria->filter->filters);
        }


        return $where == "" ? "" : " WHERE " . $this->removeConditional($where);
    }

    public function getWhereMandatory(Criteria $criteria)
    {
        if ($criteria == null) {
            return '';
        }

        $where = [];

        if ($criteria->mandatoryFilters != null) {
            foreach ($criteria->mandatoryFilters as $item) {
                $value = is_object($item->value) ? $item->value->value : $item->value;
                $where[$item->field] = $value;
            }
        }

        return $where;
    }

    private function where($field, $operator, $data, $conditional = 'and')
    {
        return $field . $operator . " '" . $data . "' " . trim($conditional) . ' ';
    }

    private function whereGrouped($filters)
    {
        $where = '';

        foreach ($filters as $key => $item) {
            $where .= $this->where(SqlHelper::getPreparedField($item->field), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'or');
        }

        return $where == '' ? '' : "(" . $this->removeConditional($where) . ")";
    }

    private function removeConditional($where)
    {
        return substr($where, 0, strlen($where) - 4);
    }


    /**
     * La columna que columna que se transforma debe tener el alias de dynamicColum
     * La columna a sumar debe tener el alias de total
     *
     * @param $subquery
     * @return array
     */
    protected function getQueryTransformRowToColumns($subquery, $orderedBy = []): array
    {
        $data = $subquery->get();

        $query = DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->groupBy('d.dynamicColumn')
            ->select( 'd.dynamicColumn as label');

        foreach ($orderedBy as $orderBy) {
            $query->orderBy($orderBy);
        }

        $dynamicColumns = [];
        $valueColumns = [];

        foreach ($data as $datum) {
            if (in_array($datum->label, $dynamicColumns)) {
                continue;
            }

            $dynamicColumns[] = $datum->label;
            $valueColumns[] = ['label' => $datum->label, 'field' => $datum->label, 'color' => $datum->color ?? null];

            $query->addSelect(
                DB::raw("SUM(CASE WHEN label = '{$datum->label}' THEN total ELSE 0 END) AS '{$datum->label}'")
            );
        }

        return array($query, $valueColumns);
    }


    /*
     * Obtener la suma total de la propiedad value
     */
    protected function getTotal($data, $property) {
        $total = 0;
        $data->each(function($item) use (&$total, $property) {
            $total += floatval($item->$property);
        });

        return $total;
    }


    protected function addPercentToLabel(Collection $data) {
        $total = $this->getTotal($data, 'value');

        $data->map(function($item) use ($total) {
            $percent = round(($item->value / $total) * 100);
            $item->label = "{$percent}% $item->label";
        });
    }


    protected function applyTextToMonths($subquery, $columnValue = 'value') {
        return DB::table(DB::raw("({$subquery->toSql()}) as i"))
            ->mergeBindings($subquery)
            ->groupBy('i.label')
            ->orderBy('i.month', 'asc')
            ->select(
                'label',
                DB::raw("sum(case when `month` = 1 then `$columnValue` else 0 end) as JAN"),
                DB::raw("sum(case when `month` = 2 then `$columnValue` else 0 end) as FEB"),
                DB::raw("sum(case when `month` = 3 then `$columnValue` else 0 end) as MAR"),
                DB::raw("sum(case when `month` = 4 then `$columnValue` else 0 end) as APR"),
                DB::raw("sum(case when `month` = 5 then `$columnValue` else 0 end) as MAY"),
                DB::raw("sum(case when `month` = 6 then `$columnValue` else 0 end) as JUN"),
                DB::raw("sum(case when `month` = 7 then `$columnValue` else 0 end) as JUL"),
                DB::raw("sum(case when `month` = 8 then `$columnValue` else 0 end) as AUG"),
                DB::raw("sum(case when `month` = 9 then `$columnValue` else 0 end) as SEP"),
                DB::raw("sum(case when `month` = 10 then `$columnValue` else 0 end) as OCT"),
                DB::raw("sum(case when `month` = 11 then `$columnValue` else 0 end) as NOV"),
                DB::raw("sum(case when `month` = 12 then `$columnValue` else 0 end) as `DEC`")
            );
    }
}
