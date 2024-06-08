<?php

namespace Wgroup\CustomerMatrixDataActionPlanRespTask;

use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDto;
use Wgroup\Models\CustomerProjectAgentTask;
use Wgroup\Models\CustomerProjectAgentTaskRepository;
use Wgroup\Models\CustomerReporistory;
use Exception;
use Log;
use RainLab\User\Models\User;
use Str;
use Wgroup\Models\CustomerTracking;
use Wgroup\Models\CustomerTrackingReporistory;

class CustomerMatrixDataActionPlanRespTaskService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerProjectAgentTaskRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
    }

    public function init() {
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0, $isCustomerVisible = false) {

        $model = new CustomerMatrixDataActionPlanRespTask();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerProjectAgentTaskRepository = new CustomerMatrixDataActionPlanRespTaskRepository($model);

        if ($perPage > 0) {
            $this->customerProjectAgentTaskRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_internal_project_user_task.id',
            'wg_customer_internal_project_user_task.type',
            'wg_customer_internal_project_user_task.agent_id',
            'wg_customer_internal_project_user_task.observation',
            'wg_customer_internal_project_user_task.status',
            'wg_customer_internal_project_user_task.eventDateTime'
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
                    $this->customerProjectAgentTaskRepository->sortBy($colName, $dir);
                } else {
                    $this->customerProjectAgentTaskRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerProjectAgentTaskRepository->sortBy('wg_customer_internal_project_user_task.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_internal_project_user_task.customer_id', $customerId);

        if ($isCustomerVisible)
        {
            $filters[] = array('wg_customer_internal_project_user_task.isVisible', 1);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_internal_project_user_task.type', $search);
            $filters[] = array('wg_customer_internal_project_user_task.agent_id', $search);
            $filters[] = array('wg_customer_internal_project_user_task.observation', $search);
            $filters[] = array('wg_customer_internal_project_user_task.status', $search);
            $filters[] = array('wg_customer_internal_project_user_task.eventDateTime', $search);
            $filters[] = array('wg_agent.name', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_internal_project_user_task.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_internal_project_user_task.status', '0');
        }


        $this->customerProjectAgentTaskRepository->setColumns(['wg_customer_internal_project_user_task.*']);

        return $this->customerProjectAgentTaskRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId) {

        $model = new CustomerMatrixDataActionPlanRespTask();
        $this->customerProjectAgentTaskRepository = new CustomerMatrixDataActionPlanRespTaskRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_internal_project_user_task.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_internal_project_user_task.type', $search);
            $filters[] = array('wg_customer_internal_project_user_task.agent_id', $search);
            $filters[] = array('wg_customer_internal_project_user_task.observation', $search);
            $filters[] = array('wg_customer_internal_project_user_task.status', $search);
            $filters[] = array('wg_customer_internal_project_user_task.eventDateTime', $search);
        }

        $this->customerProjectAgentTaskRepository->setColumns(['wg_customer_internal_project_user_task.*']);

        return $this->customerProjectAgentTaskRepository->getFilteredsOptional($filters, true, "");
    }
}
