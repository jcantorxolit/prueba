<?php

namespace Wgroup\Classes;

use DB;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDto;
use Wgroup\Models\CustomerReporistory;
use Exception;
use Log;
use RainLab\User\Models\User;
use Str;
use Wgroup\Models\CustomerTracking;
use Wgroup\Models\CustomerTrackingReporistory;

class ServiceCustomerTracking {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerTrackingRepository;

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

        $model = new CustomerTracking();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerTrackingRepository = new CustomerTrackingReporistory($model);

        if ($perPage > 0) {
            $this->customerTrackingRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_tracking.id',
            'wg_customer_tracking.type',
            'wg_customer_tracking.agent_id',
            'wg_customer_tracking.observation',
            'wg_customer_tracking.status',
            'wg_customer_tracking.eventDateTime'
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
                    $this->customerTrackingRepository->sortBy($colName, $dir);
                } else {
                    $this->customerTrackingRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerTrackingRepository->sortBy('wg_customer_tracking.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_tracking.customer_id', $customerId);

        if ($isCustomerVisible)
        {
            $filters[] = array('wg_customer_tracking.isVisible', 1);
        }

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_tracking.type', $search);
            $filters[] = array('wg_customer_tracking.agent_id', $search);
            $filters[] = array('wg_customer_tracking.observation', $search);
            $filters[] = array('wg_customer_tracking.status', $search);
            $filters[] = array('wg_customer_tracking.eventDateTime', $search);
            $filters[] = array('wg_agent.name', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_tracking.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_tracking.status', '0');
        }


        $this->customerTrackingRepository->setColumns(['wg_customer_tracking.*']);

        return $this->customerTrackingRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId) {

        $model = new CustomerTracking();
        $this->customerTrackingRepository = new CustomerTrackingReporistory($model);

        $filters = array();

        $filters[] = array('wg_customer_tracking.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_tracking.type', $search);
            $filters[] = array('wg_customer_tracking.agent_id', $search);
            $filters[] = array('wg_customer_tracking.observation', $search);
            $filters[] = array('wg_customer_tracking.status', $search);
            $filters[] = array('wg_customer_tracking.eventDateTime', $search);
        }

        $this->customerTrackingRepository->setColumns(['wg_customer_tracking.*']);

        return $this->customerTrackingRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllAgentBy($sorting = array(), $customerId) {

        $query = "	Select * from
	(
		Select a.id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email from wg_agent a
		inner join wg_customer_agent ca on a.id = ca.agent_id
		left join users u on u.id = a.user_id
		where ca.customer_id = :customer_id_1
        union all
        select c.id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email from wg_customer_user c
        inner join users ON users.id = c.user_id
		where c.customer_id = :customer_id_2 AND c.isActive = 1
	) u
order by u.`name`";

        $results = DB::select( $query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
        ));

        return $results;
    }
}
