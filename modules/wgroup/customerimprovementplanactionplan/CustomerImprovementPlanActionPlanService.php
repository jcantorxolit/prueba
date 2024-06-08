<?php

namespace Wgroup\CustomerImprovementPlanActionPlan;

use DB;
use Exception;
use Log;
use Str;

class CustomerImprovementPlanActionPlanService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $repository;

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
     * @param int $customerImprovementPlanCauseId
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerImprovementPlanId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlanActionPlan();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerImprovementPlanActionPlanRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_improvement_plan_action_plan.id',
            'wg_customer_improvement_plan_action_plan.endDate',
            'wg_customer_improvement_plan_action_plan.activity',
            'wg_customer_improvement_plan_action_plan.amount'
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
            $this->repository->sortBy('wg_customer_improvement_plan_action_plan.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_action_plan.customer_improvement_plan_id', $customerImprovementPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_action_plan.endDate', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan.activity', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan.amount', $search);
            $filters[] = array('wg_improvement_plan_cause_category.name', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.cause', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
            $filters[] = array('wg_budget.item', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan_action_plan.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerImprovementPlanId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlanActionPlan();
        $this->repository = new CustomerImprovementPlanActionPlanRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_action_plan.customer_improvement_plan_id', $customerImprovementPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_action_plan.endDate', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan.activity', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan.amount', $search);
            $filters[] = array('wg_improvement_plan_cause_category.name', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.cause', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
            $filters[] = array('wg_budget.item', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan_action_plan.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }


    public function getAllByRootCause($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerImprovementPlanCauseRootCauseId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlanActionPlan();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerImprovementPlanActionPlanRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_improvement_plan_action_plan.id',
            'wg_customer_improvement_plan_action_plan.endDate',
            'wg_customer_improvement_plan_action_plan.activity',
            'wg_customer_improvement_plan_action_plan.amount'
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
            $this->repository->sortBy('wg_customer_improvement_plan_action_plan.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_action_plan.customer_improvement_plan_cause_root_cause_id', $customerImprovementPlanCauseRootCauseId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_action_plan.endDate', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan.activity', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan.amount', $search);
            $filters[] = array('wg_improvement_plan_cause_category.name', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.cause', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
            $filters[] = array('wg_budget.item', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan_action_plan.*']);

        return $this->repository->getFilteredOptionalCause($filters, false, "");
    }

    public function getCountByRootCause($search = "", $customerImprovementPlanCauseRootCauseId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlanActionPlan();
        $this->repository = new CustomerImprovementPlanActionPlanRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_action_plan.customer_improvement_plan_cause_root_cause_id', $customerImprovementPlanCauseRootCauseId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_action_plan.endDate', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan.activity', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan.amount', $search);
            $filters[] = array('wg_improvement_plan_cause_category.name', $search);
            $filters[] = array('wg_customer_improvement_plan_cause_root_cause.cause', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
            $filters[] = array('wg_budget.item', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan_action_plan.*']);

        return $this->repository->getFilteredOptionalCause($filters, true, "");
    }
}
