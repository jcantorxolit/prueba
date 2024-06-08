<?php

namespace Wgroup\CustomerImprovementPlanTracking;

use DB;
use Exception;
use Log;
use Str;


class CustomerImprovementPlanTrackingService
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerImprovementPlanId)
    {

        $model = new CustomerImprovementPlanTracking();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerImprovementPlanTrackingRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_improvement_plan_tracking.id',
            'wg_customer_improvement_plan_tracking.customer_improvement_plan_id',
            'wg_customer_improvement_plan_tracking.startDate',
            'wg_customer_improvement_plan_tracking.responsible',
            'wg_customer_improvement_plan_tracking.responsibleType',
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
            $this->repository->sortBy('wg_customer_improvement_plan_tracking.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_tracking.customer_improvement_plan_id', $customerImprovementPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_tracking.startDate', $search);
            $filters[] = array('wg_customer_improvement_plan_tracking.observation', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
            $filters[] = array('improvement_plan_tracking_status.item', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan_tracking.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerImprovementPlanId)
    {

        $model = new CustomerImprovementPlanTracking();
        $this->repository = new CustomerImprovementPlanTrackingRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan_tracking.customer_improvement_plan_id', $customerImprovementPlanId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_improvement_plan_tracking.startDate', $search);
            $filters[] = array('wg_customer_improvement_plan_tracking.observation', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
            $filters[] = array('improvement_plan_tracking_status.item', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan_tracking.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }
}
