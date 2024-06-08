<?php

namespace Wgroup\CustomerConfigActivity;

use DB;
use Exception;
use Log;
use Str;


class CustomerConfigActivityService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerConfigActivityRepository;

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

        $model = new CustomerConfigActivity();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigActivityRepository = new CustomerConfigActivityRepository($model);

        if ($perPage > 0) {
            $this->customerConfigActivityRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_config_activity.id',
            'wg_customer_config_activity.name',
            'wg_customer_config_activity.isCritical',
            'wg_customer_config_activity.status'
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
                    $this->customerConfigActivityRepository->sortBy($colName, $dir);
                } else {
                    $this->customerConfigActivityRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerConfigActivityRepository->sortBy('wg_customer_config_activity.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_config_activity.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_activity.name', $search);
            $filters[] = array('wg_customer_config_activity.status', $search);
        }

        $this->customerConfigActivityRepository->setColumns(['wg_customer_config_activity.*']);

        return $this->customerConfigActivityRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId) {

        $model = new CustomerConfigActivity();
        $this->customerConfigActivityRepository = new CustomerConfigActivityRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_config_activity.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_config_activity.name', $search);
            $filters[] = array('wg_customer_config_activity.status', $search);
        }

        $this->customerConfigActivityRepository->setColumns(['wg_customer_config_activity.*']);

        return $this->customerConfigActivityRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllListBy($customerId) {

        $query = "SELECT
	ja.*, a.`name`
FROM
	wg_customer_config_job_activity ja
INNER JOIN wg_customer_config_activity a ON ja.activity_id = a.id
INNER JOIN wg_customer_config_job j ON ja.job_id = j.id
INNER JOIN wg_customer_config_process p ON j.process_id = p.id
INNER JOIN wg_customer_config_macro_process mp ON p.macro_process_id = mp.id
INNER JOIN wg_customer_config_workplace w ON mp.workplace_id = w.id
WHERE
	a.`status` = 'Activo'
AND j.`status` = 'Activo'
AND p.`status` = 'Activo'
AND mp.`status` = 'Activo'
AND w.`status` = 'Activo'
AND w.customer_id = :customer_id
ORDER BY
	a.`name`";

        $results = DB::select( $query, array(
            "customer_id" => $customerId
        ));

        return $results;

    }
}
