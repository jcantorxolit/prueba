<?php

namespace Wgroup\CustomerImprovementPlanCauseRootCause;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\CustomerImprovementPlanCause\CustomerImprovementPlanCause;

class CustomerImprovementPlanCauseRootCauseService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContractorRepository;

    function __construct()
    {
        // $this->customerRepository = new CustomerReporistory();
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
     * @param string $customerImprovementPlanId
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerImprovementPlanId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlanCause();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerImprovementPlanCauseRootCauseRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_improvement_plan_cause.id',
            'wg_customer_improvement_plan_cause.created_at',
            'users.name',
            'wg_improvement_plan_cause_category.name',
            'wg_customer_improvement_plan_cause_root_cause.cause',
            'wg_customer_improvement_plan_cause_root_cause.factor',
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
                    $this->customerContractorRepository->sortBy($colName, $dir);
                } else {
                    $this->customerContractorRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerContractorRepository->sortBy('wg_customer_improvement_plan_cause.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_cause.customer_improvement_plan_id', $customerImprovementPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_cause.created_at', $search);
            $filters[] = array('wg_improvement_plan_cause_category.name', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.cause', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.factor', $search);
            $filters[] = array('users.name', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_improvement_plan_cause.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerImprovementPlanId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlanCauseRootCause();
        $this->customerContractorRepository = new CustomerImprovementPlanCauseRootCauseRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_cause.customer_improvement_plan_id', $customerImprovementPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_cause.created_at', $search);
            $filters[] = array('wg_improvement_plan_cause_category.name', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.cause', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.factor', $search);
            $filters[] = array('users.name', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_improvement_plan_cause.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
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
