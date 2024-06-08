<?php

namespace Wgroup\NephosIntegration;

use DB;
use Exception;
use Log;
use Str;


class NephosIntegrationService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerConfigWorkPlaceRepository;

    function __construct() {
       // $this->customerRepository = new CustomerRepository();
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId) {

        $model = new NephosIntegration();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigWorkPlaceRepository = new NephosIntegrationRepository($model);

        if ($perPage > 0) {
            $this->customerConfigWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_config_job.name',
            'wg_customer_config_job.status'
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
                    $this->customerConfigWorkPlaceRepository->sortBy($colName, $dir);
                } else {
                    $this->customerConfigWorkPlaceRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerConfigWorkPlaceRepository->sortBy('wg_customer_config_job.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_config_job.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_job.name', $search);
            $filters[] = array('wg_customer_config_job.status', $search);
            $filters[] = array('wg_customer_config_workplace.name', $search);
            $filters[] = array('wg_customer_config_macro_process.name', $search);
            $filters[] = array('wg_customer_config_process.name', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId) {

        $model = new NephosIntegration();
        $this->customerConfigWorkPlaceRepository = new NephosIntegrationRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_config_job.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_config_job.name', $search);
            $filters[] = array('wg_customer_config_job.status', $search);
            $filters[] = array('wg_customer_config_workplace.name', $search);
            $filters[] = array('wg_customer_config_macro_process.name', $search);
            $filters[] = array('wg_customer_config_process.name', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAll($search, $perPage = 10, $currentPage = 0, $filter = null, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
Select
	c.documentType, c.documentNumber, c.businessName, n.action command, n.customer_id, pp.name plan,
	n.id, n.instance_id instanceId, n.plan_id planId, n.adminUser, n.adminPwd, n.users, n.contractors, n.disk, n.employees
from
	wg_nephos_customer_tracking n
left join wg_customers c on n.customer_id = c.id
left join wg_product_plan pp on pp.id = n.plan_id
)p";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.id DESC ";

        $where = ' WHERE p.customer_id = '. $customerId;

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " AND (p.instanceId like '%$search%' or p.planId like '%$search%' or p.command like '%$search%')";
        }


        $sql = $query.$where.$orderBy;
        $sql.=$limit;

        $results = DB::select( $sql );

        return $results;
    }

    public function getAllCountBy($search, $perPage = 10, $currentPage = 0, $filter = null, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
Select
	c.documentType, c.documentNumber, c.businessName, n.action command, n.customer_id, pp.name plan,
	n.id, n.instance_id instanceId, n.plan_id planId, n.adminUser, n.adminPwd, n.users, n.contractors, n.disk, n.employees
from
	wg_nephos_customer_tracking n
left join wg_customers c on n.customer_id = c.id
left join wg_product_plan pp on pp.id = n.plan_id
)p";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.id DESC ";

        $where = ' WHERE p.customer_id = '. $customerId;

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.instanceId like '%$search%' or p.planId like '%$search%' or p.command like '%$search%')";
        }


        $sql = $query.$where.$orderBy;

        $results = DB::select( $sql );

        return count($results);
    }

}
