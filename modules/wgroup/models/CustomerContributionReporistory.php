<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Wgroup\SystemParameter\SystemParameter;

/**
 * Description of CustomerReporistory
 *
 * @author TeamCloud
 */
class CustomerContributionReporistory {

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    /**
     * The current sort field and direction
     * @var array
     */
    protected $currentSort = [array('id', 'desc')];
    protected $columns = array('wg_customer_arl_contribution.*');

    /**
     * The current number of results to return per page
     * @var integer
     */
    protected $perPage = 10;
    protected $onlyActives = 1;

    public function __construct(CustomerContribution $model) {
        $this->model = $model;
    }

    public function setOnlyActives($onlyActives) {
        $this->onlyActives = $onlyActives;
    }

    /**
     * Sets the number of items displayed per page of results
     * @param integer $perPage The number of items to display per page
     * @return EloquenFooRepository The current instance
     */
    public function paginate($perPage) {
        $this->perPage = (int) $perPage;

        return $this;
    }

    /**
     * Sets how the results are sorted
     * @param string $field The field being sorted
     * @param string $direction The direction to sort (ASC or DESC)
     * @return EloquenFooRepository The current instance
     */
    public function sortBy($field, $direction = 'DESC') {
        $direction = (strtoupper($direction) == 'ASC') ? 'ASC' : 'DESC';
        $this->currentSort = array();
        $this->currentSort[] = array($field, $direction);

        return $this;
    }

    /**
     * Creates a new QueryBuilder instance and applies the current sorting
     * @return Builder
     */
    protected function query() {

        $query = $this->model->newQuery();

        foreach ($this->currentSort as $key => $sort) {
            list($sortField, $sortDir) = $sort;
            $query->orderByRaw("$sortField $sortDir");
        }

        return $query;
    }

    /**
     * Retrieves a set of items based on a single value
     * @param string $fieldName  The name of the field to match
     * @param string $fieldValue The value of the field to match
     * @return Paginator|Collection
     */
    public function getByField($fieldName, $fieldValue) {
        $query = $this->query()->where($fieldName, $fieldValue);

        return ($this->perPage > 0) ? $query->paginate($this->perPage, $this->columns) : $query->get($this->columns);
    }

    /**
     *
     * @return type
     */
    public function getAll() {
        $query = $this->query();
        return ($this->perPage > 0) ? $query->paginate($this->perPage, $this->columns) : $query->get($this->columns);
    }

    public function getFiltereds($filter = array(), $join = "", $count = false) {
        $query = $this->query();
        $query->select(['wg_customer_arl_contribution.*']);

        // Add filters
        foreach ($filter as $key => $item) {
            try {
                list($field, $valueField) = $item;
                $query->where($field, $valueField);
            } catch (Exception $exc) {
                Log::error($exc->getMessage());
            }
        }

        if ($count) {
            return $query->get()->count();
        }

        return ($this->perPage > 0) ? $query->paginate($this->perPage, $this->columns) : $query->get($this->columns);
    }

    public function getFilteredsOptional($filter = array(), $isCount = false, $join = "", $year = null, $search = '') {
        $subqueryCosts = $this->getQueryExecutedCosts();

        $query = $this->query()
            ->leftJoin(DB::raw("({$subqueryCosts->toSql()}) AS sales"), function ($join) {
                $join->on('sales.customer_id', 'wg_customer_arl_contribution.customer_id');
                $join->on('sales.year', 'wg_customer_arl_contribution.year');
                $join->on('sales.month', 'wg_customer_arl_contribution.month');
            })
            ->when($year, function($query) use ($year) {
                $query->where('wg_customer_arl_contribution.year', $year);
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('month')), function ($join) {
                $join->on('month.value', '=', 'wg_customer_arl_contribution.month');
            })
            ->select([
                'wg_customer_arl_contribution.id',
                'wg_customer_arl_contribution.year',
                'month.item as month',
                'wg_customer_arl_contribution.input',
                'wg_customer_arl_contribution.percent_reinvestment_arl',
                'wg_customer_arl_contribution.percent_reinvestment_wg',
                DB::raw('input * percent_reinvestment_arl / 100 as reinvestmentARL'),
                DB::raw('(input * percent_reinvestment_arl / 100) * percent_reinvestment_wg / 100 as reinvestmentWG'),
                DB::raw('COALESCE(sales.sales, 0) AS sales'),
                DB::raw('COALESCE(((input * percent_reinvestment_arl / 100) * percent_reinvestment_wg / 100) - COALESCE(sales.sales, 0), 0) AS balance'),
                'wg_customer_arl_contribution.customer_id',
                'wg_customer_arl_contribution.created_at',
                'wg_customer_arl_contribution.updated_at'
            ]);


        foreach ($filter as $key => $item) {
            try {
                list($field, $valueField) = $item;

                if ($field == "wg_customer_arl_contribution.customer_id") {
                    $query->where($field, "=", $valueField, 'and');
                }

            } catch (Exception $exc) {
                Log::error($exc->getMessage());
            }
        }

        $query->where(function($query) use ($filter, $search) {
            // Add filters
            foreach ($filter as $key => $item) {
                try {
                    list($field, $valueField) = $item;

                    if ($field != "wg_customer_arl_contribution.customer_id") {
                        $query->where($field, "like", '%' . $valueField . '%', 'or');
                    }

                } catch (Exception $exc) {
                    Log::error($exc->getMessage());
                }
            }

            if (!empty($search)) {
                $query->orWhereRaw("(input * percent_reinvestment_arl / 100) like '%$search%'", 'or');
                $query->orWhereRaw("(input * percent_reinvestment_wg / 100) like '%$search%'", 'or');
                $query->orWhereRaw("(input * percent_reinvestment_arl / 100) + (input * percent_reinvestment_wg / 100) like '%$search%'", 'or');
                $query->orWhereRaw("COALESCE(((input * percent_reinvestment_arl / 100) * percent_reinvestment_wg / 100) - COALESCE(sales.sales, 0), 0) like '%$search%'", 'or');
            }
        });

        if ($isCount) {
            return ($this->perPage > 0) ? $query->paginate($this->perPage)->total() : $query->get()->count();
        } else {
            return ($this->perPage > 0) ? $query->paginate($this->perPage, $this->columns) : $query->get($this->columns);
        }
    }

    public function addSortField($field, $direction = 'DESC') {
        $direction = (strtoupper($direction) == 'ASC') ? 'ASC' : 'DESC';

        $this->currentSort[] = array($field, $direction);

        return $this;
    }

    public function addColumn($column) {
        $this->columns[] = $column;
        return $this;
    }

    function getColumns() {
        return $this->columns;
    }

    function setColumns($columns) {
        $this->columns = $columns;
    }


    private function getQueryExecutedCosts() {
        return DB::table('wg_customer_project_costs as pc')
            ->join('wg_customer_project as p', 'p.id', '=', 'pc.project_id')
            ->whereRaw("pc.status = 'SS002' ")
            ->whereRaw("( p.type = 'Intm' OR 
                         (p.type = 'SYL' and pc.concept = 'PCOS014') OR 
                         (p.type = 'RV' AND pc.concept = 'C03')
                        )")
            ->groupBy('p.customer_id', DB::raw('year(p.deliveryDate)'), DB::raw('month(deliveryDate)'))
            ->select(
                'p.customer_id',
                DB::raw("year(deliveryDate) as year"),
                DB::raw("month(deliveryDate) as month"),
                DB::raw("sum(pc.total_price) as sales")
            );
    }
}
