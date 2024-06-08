<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationLostNational;

use Eloquent;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Description of CustomerRepository
 *
 * @author TeamCloud
 */
class CustomerHealthDamageQualificationLostNationalRepository
{

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
    protected $columns = array('wg_customer_health_damage_ql_second_instance.*');

    /**
     * The current number of results to return per page
     * @var integer
     */
    protected $perPage = 10;
    protected $onlyActives = 1;

    public function __construct(CustomerHealthDamageQualificationLostNational $model)
    {
        $this->model = $model;
    }

    public function setOnlyActives($onlyActives)
    {
        $this->onlyActives = $onlyActives;
    }

    /**
     * Sets the number of items displayed per page of results
     * @param integer $perPage The number of items to display per page
     * @return EloquenFooRepository The current instance
     */
    public function paginate($perPage)
    {
        $this->perPage = (int)$perPage;

        return $this;
    }

    /**
     * Sets how the results are sorted
     * @param string $field The field being sorted
     * @param string $direction The direction to sort (ASC or DESC)
     * @return EloquenFooRepository The current instance
     */
    public function sortBy($field, $direction = 'DESC')
    {
        $direction = (strtoupper($direction) == 'ASC') ? 'ASC' : 'DESC';
        $this->currentSort = array();
        $this->currentSort[] = array($field, $direction);

        return $this;
    }

    /**
     * Creates a new QueryBuilder instance and applies the current sorting
     * @return Builder
     */
    protected function query()
    {

        $query = $this->model->newQuery();

        foreach ($this->currentSort as $key => $sort) {
            list($sortField, $sortDir) = $sort;
            $query->orderBy($sortField, $sortDir);
        }

        return $query;
    }

    /**
     * Retrieves a set of items based on a single value
     * @param string $fieldName The name of the field to match
     * @param string $fieldValue The value of the field to match
     * @return Paginator|Collection
     */
    public function getByField($fieldName, $fieldValue)
    {
        $query = $this->query()->where($fieldName, $fieldValue);

        return ($this->perPage > 0) ? $query->paginate($this->perPage, $this->columns) : $query->get($this->columns);
    }

    /**
     *
     * @return type
     */
    public function getAll()
    {
        $query = $this->query();
        return ($this->perPage > 0) ? $query->paginate($this->perPage, $this->columns) : $query->get($this->columns);
    }

    public function getFiltereds($filter = array(), $join = "", $count = false)
    {
        $query = $this->query();
        $query->select(['wg_customer_health_damage_ql_second_instance.*']);

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

    public function getFilteredsOptional($filter = array(), $isCount = false, $join = "")
    {
        $query = $this->query();
        $query->select(['wg_customer_health_damage_ql_second_instance.*']);


        $query->join("wg_customer_health_damage_ql", function ($join) {
            $join->on('wg_customer_health_damage_ql_second_instance.customer_health_damage_qualification_lost_id', '=', 'wg_customer_health_damage_ql.id');
        });


        $ands = array();

        foreach ($filter as $key => $item) {
            try {
                list($field, $valueField) = $item;

                if ($field == "wg_customer_health_damage_ql_second_instance.customer_health_damage_qualification_lost_id") {
                    $query->where($field, "=", $valueField, 'and');
                }

            } catch (Exception $exc) {
                Log::error($exc->getMessage());
            }
        }

        $query->where(function ($query) use ($filter) {
            // Add filters
            foreach ($filter as $key => $item) {
                try {
                    list($field, $valueField) = $item;

                    if ($field != "wg_customer_health_damage_ql_second_instance.customer_health_damage_qualification_lost_id") {
                        $query->where($field, "like", '%' . $valueField . '%', 'or');
                    }

                } catch (Exception $exc) {
                    Log::error($exc->getMessage());
                }
            }
        });

        if ($isCount) {
            return ($this->perPage > 0) ? $query->paginate($this->perPage)->total() : $query->get()->count();
        } else {
            return ($this->perPage > 0) ? $query->paginate($this->perPage, $this->columns) : $query->get($this->columns);
        }
    }

    public function getFilterContract($filter = array(), $isCount = false, $join = "")
    {
        $query = $this->query();
        $query->select(['wg_customer_health_damage_ql_second_instance.*']);


        $query->join("wg_customer_health_damage_ql", function ($join) {
            $join->on('wg_customer_health_damage_ql_second_instance.customer_health_damage_qualification_lost_id', '=', 'wg_customer_health_damage_ql.id');
        });

        foreach ($filter as $key => $item) {
            try {
                list($field, $valueField) = $item;

                if ($field == "wg_customer_health_damage_ql_second_instance.customer_health_damage_qualification_lost_id") {
                    $query->where($field, "=", $valueField, 'and');
                }

            } catch (Exception $exc) {
                Log::error($exc->getMessage());
            }
        }

        $query->where(function ($query) use ($filter) {
            // Add filters
            foreach ($filter as $key => $item) {
                try {
                    list($field, $valueField) = $item;

                    if ($field != "wg_customer_health_damage_ql_second_instance.customer_health_damage_qualification_lost_id") {
                        $query->where($field, "like", '%' . $valueField . '%', 'or');
                    }

                } catch (Exception $exc) {
                    Log::error($exc->getMessage());
                }
            }
        });

        if ($isCount) {
            return ($this->perPage > 0) ? $query->paginate($this->perPage)->total() : $query->get()->count();
        } else {
            return ($this->perPage > 0) ? $query->paginate($this->perPage, $this->columns) : $query->get($this->columns);
        }
    }

    public function addSortField($field, $direction = 'DESC')
    {
        $direction = (strtoupper($direction) == 'ASC') ? 'ASC' : 'DESC';

        $this->currentSort[] = array($field, $direction);

        return $this;
    }

    public function addColumn($column)
    {
        $this->columns[] = $column;
        return $this;
    }

    function getColumns()
    {
        return $this->columns;
    }

    function setColumns($columns)
    {
        $this->columns = $columns;
    }
}
