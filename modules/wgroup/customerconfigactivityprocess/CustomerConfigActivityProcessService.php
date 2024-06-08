<?php

namespace Wgroup\CustomerConfigActivityProcess;

use DB;
use Exception;
use Log;
use Str;


class CustomerConfigActivityProcessService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerConfigActivityProcessRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $jobId) {

        $model = new CustomerConfigActivityProcess();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigActivityProcessRepository = new CustomerConfigActivityProcessRepository($model);

        if ($perPage > 0) {
            $this->customerConfigActivityProcessRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_config_activity_process.id',
            'wg_customer_config_activity_process.activity_id',
            'wg_customer_config_activity_process.isRoutine'
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
                    $this->customerConfigActivityProcessRepository->sortBy($colName, $dir);
                } else {
                    $this->customerConfigActivityProcessRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerConfigActivityProcessRepository->sortBy('wg_customer_config_activity_process.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_config_activity_process.activity_id', $jobId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_workplace.name', $search);
            $filters[] = array('wg_customer_config_macro_process.name', $search);
            $filters[] = array('wg_customer_config_process.name', $search);
        }

        $this->customerConfigActivityProcessRepository->setColumns(['wg_customer_config_activity_process.*']);

        return $this->customerConfigActivityProcessRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $jobId) {

        $model = new CustomerConfigActivityProcess();
        $this->customerConfigActivityProcessRepository = new CustomerConfigActivityProcessRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_config_activity_process.activity_id', $jobId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_config_workplace.name', $search);
            $filters[] = array('wg_customer_config_macro_process.name', $search);
            $filters[] = array('wg_customer_config_process.name', $search);
        }

        $this->customerConfigActivityProcessRepository->setColumns(['wg_customer_config_activity_process.*']);

        return $this->customerConfigActivityProcessRepository->getFilteredsOptional($filters, true, "");
    }
}
