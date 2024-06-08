<?php

namespace Wgroup\ImprovementPlanCause;

use DB;
use Exception;
use Log;
use Str;

class ImprovementPlanCauseService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $repository;

    function __construct()
    {
    }

    public function init()
    {
        parent::init();
    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param string $typeFilter
     * @param string $investigationCauseCategoryAbbreviation
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $investigationCauseCategoryAbbreviation = 0)
    {

        $model = new ImprovementPlanCause();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new ImprovementPlanCauseRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_investigation_cause.id',
            'wg_investigation_cause.code',
            'wg_investigation_cause.name',
            'wg_investigation_cause.isActive',
            'wg_investigation_cause.improvement_plan_cause_category_id'
        ];

        $i = 0;

        foreach ($sorting as $key => $value) {
            try {

                if (isset($value["column"]) === false) {
                    continue;
                }

                $col = $value["column"];
                $dir = $value["dir"];

                $colName = $columns[$col];

                if ($colName == "") {
                    continue;
                }

                if ($dir == null || $dir == "") {
                    $dir = " asc ";
                }

                if ($i == 0) {
                    $this->repository->sortBy($colName, $dir);
                } else {
                    $this->repository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->repository->sortBy('wg_investigation_cause.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_investigation_cause_category.abbreviation', $investigationCauseCategoryAbbreviation);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_investigation_cause_parent.name', $search);
            $filters[] = array('wg_investigation_cause_parent.code', $search);
            $filters[] = array('wg_investigation_cause.name', $search);
            $filters[] = array('wg_investigation_cause.code', $search);
        }

        $this->repository->setColumns(['wg_investigation_cause.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $investigationCauseCategoryAbbreviation)
    {

        $model = new ImprovementPlanCause();
        $this->repository = new ImprovementPlanCauseRepository($model);

        $filters = array();

        $filters[] = array('wg_investigation_cause.cause_category_id', $investigationCauseCategoryAbbreviation);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_investigation_cause_parent.name', $search);
            $filters[] = array('wg_investigation_cause_parent.code', $search);
            $filters[] = array('wg_investigation_cause.name', $search);
            $filters[] = array('wg_investigation_cause.code', $search);
        }

        $this->repository->setColumns(['wg_investigation_cause.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }

    private function getWhere($filters)
    {
        //Log::info("where");

        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            //Log::info("foreach");

            if ($lastFilter == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        //Log::info($where);
        //Log::info(count($filters));

        return $where == "" ? "" : " WHERE " . $where;
    }
}
