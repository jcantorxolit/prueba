<?php

namespace Wgroup\CustomerImprovementPlanAudit;

use DB;
use Exception;
use Log;
use Str;

class CustomerImprovementPlanAuditService
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
     * @param int $customerImprovementPlanId
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerImprovementPlanId = 0)
    {

        $model = new CustomerImprovementPlanAudit();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerImprovementPlanAuditRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_improvement_plan_audit.id',
            'wg_customer_improvement_plan_audit.entityId',
            'wg_customer_improvement_plan_audit.entityName',
            'wg_customer_improvement_plan_audit.type',
            'wg_customer_improvement_plan_audit.reason',
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
            $this->repository->sortBy('wg_customer_improvement_plan_audit.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_audit.customer_improvement_plan_id', $customerImprovementPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_audit.entityId', $search);
            $filters[] = array('wg_customer_improvement_plan_audit.entityName', $search);
            $filters[] = array('wg_customer_improvement_plan_audit.type', $search);
            $filters[] = array('wg_customer_improvement_plan_audit.reason', $search);
            $filters[] = array('wg_customer_improvement_plan_audit.created_at', $search);
            $filters[] = array('users.name', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan_audit.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerImprovementPlanId)
    {

        $model = new CustomerImprovementPlanAudit();
        $this->repository = new CustomerImprovementPlanAuditRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_audit.customer_improvement_plan_id', $customerImprovementPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_audit.entityId', $search);
            $filters[] = array('wg_customer_improvement_plan_audit.entityName', $search);
            $filters[] = array('wg_customer_improvement_plan_audit.type', $search);
            $filters[] = array('wg_customer_improvement_plan_audit.reason', $search);
            $filters[] = array('wg_customer_improvement_plan_audit.created_at', $search);
            $filters[] = array('users.name', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan_audit.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }
}
