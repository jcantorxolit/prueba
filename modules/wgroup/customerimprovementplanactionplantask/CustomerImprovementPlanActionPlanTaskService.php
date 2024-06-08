<?php

namespace Wgroup\CustomerImprovementPlanActionPlanTask;

use DB;
use Exception;
use Log;
use Str;


class CustomerImprovementPlanActionPlanTaskService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $repository;

    function __construct()
    {
        // $this->customerRepository = new CustomerRepository();
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
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerImprovementPlanActionPlanId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlanActionPlanTask();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerImprovementPlanActionPlanTaskRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_improvement_plan_action_plan_task.id',
            'wg_customer_improvement_plan_action_plan_task.customer_improvement_plan_action_plan_id',
            'wg_customer_improvement_plan_action_plan_task.startDate',
            'wg_customer_improvement_plan_action_plan_task.responsible',
            'wg_customer_improvement_plan_action_plan_task.responsibleType',
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
            $this->repository->sortBy('wg_customer_improvement_plan_action_plan_task.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_action_plan_task.customer_improvement_plan_action_plan_id', $customerImprovementPlanActionPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_action_plan_task.startDate', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan_task.endDate', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan_task.description', $search);
            $filters[] = array('improvement_plan_action_plan_task_status.item', $search);
            $filters[] = array('project_task_type.item', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan_action_plan_task.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerImprovementPlanActionPlanId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlanActionPlanTask();
        $this->repository = new CustomerImprovementPlanActionPlanTaskRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_action_plan_task.customer_improvement_plan_action_plan_id', $customerImprovementPlanActionPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_action_plan_task.startDate', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan_task.endDate', $search);
            $filters[] = array('wg_customer_improvement_plan_action_plan_task.description', $search);
            $filters[] = array('improvement_plan_action_plan_task_status.item', $search);
            $filters[] = array('project_task_type.item', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan_action_plan_task.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }
}
