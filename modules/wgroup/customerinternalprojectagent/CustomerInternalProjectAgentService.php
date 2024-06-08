<?php

namespace Wgroup\CustomerInternalProjectAgent;

use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDto;
use Wgroup\Models\CustomerProjectAgent;
use Wgroup\Models\CustomerReporistory;
use Exception;
use Log;
use RainLab\User\Models\User;
use Str;
use Wgroup\Models\CustomerTracking;
use Wgroup\Models\CustomerTrackingAlert;
use Wgroup\Models\CustomerTrackingAlertReporistory;
use Wgroup\Models\CustomerTrackingReporistory;

class CustomerInternalProjectAgentService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerProjectAgentRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "") {

        $model = new CustomerInternalProjectAgent();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerTrackingRepository = new CustomerInternalProjectAgentReporistory($model);

        if ($perPage > 0) {
            $this->customerProjectAgentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_internal_project_user.type',
            'wg_customer_internal_project_user.agent_id',
            'wg_customer_internal_project_user.time',
            'wg_customer_internal_project_user.timeType',
            'wg_customer_internal_project_user.preference',
            'wg_customer_internal_project_user.updated_at'
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
                    $this->customerProjectAgentRepository->sortBy($colName, $dir);
                } else {
                    $this->customerProjectAgentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerProjectAgentRepository->sortBy('wg_customer_internal_project_user.id', 'desc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_internal_project_user.type', $search);
            $filters[] = array('wg_customer_internal_project_user.agent_id', $search);
            $filters[] = array('wg_customer_internal_project_user.time', $search);
            $filters[] = array('wg_customer_internal_project_user.timeType', $search);
            $filters[] = array('wg_customer_internal_project_user.preference', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_internal_project_user.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_internal_project_user.status', '0');
        }


        $this->customerProjectAgentRepository->setColumns(['wg_customer_internal_project_user.*']);

        return $this->customerProjectAgentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerProjectAgent();
        $this->customerProjectAgentRepository = new CustomerInternalProjectAgentReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_internal_project_user.type', $search);
            $filters[] = array('wg_customer_internal_project_user.agent_id', $search);
            $filters[] = array('wg_customer_internal_project_user.time', $search);
            $filters[] = array('wg_customer_internal_project_user.timeType', $search);
            $filters[] = array('wg_customer_internal_project_user.preference', $search);
        }

        $this->customerProjectAgentRepository->setColumns(['wg_customer_internal_project_user.*']);

        return $this->customerProjectAgentRepository->getFilteredsOptional($filters, true, "");
    }
}
