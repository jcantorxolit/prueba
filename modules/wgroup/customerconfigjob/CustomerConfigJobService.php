<?php

namespace Wgroup\CustomerConfigJob;

use DB;
use Exception;
use Log;
use Str;


class CustomerConfigJobService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $jobId, $customerId) {

        $model = new CustomerConfigJob();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobRepository($model);

        if ($perPage > 0) {
            $this->customerConfigWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_config_job.id',
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
        $filters[] = array('wg_customer_config_job.job_id', $jobId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('config_workplace_status.item', $search);
            $filters[] = array('wg_customer_config_workplace.name', $search);
            $filters[] = array('wg_customer_config_macro_process.name', $search);
            $filters[] = array('wg_customer_config_process.name', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $jobId, $customerId) {

        $model = new CustomerConfigJob();
        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_config_job.customer_id', $customerId);
        $filters[] = array('wg_customer_config_job.job_id', $jobId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('config_workplace_status.item', $search);
            $filters[] = array('wg_customer_config_workplace.name', $search);
            $filters[] = array('wg_customer_config_macro_process.name', $search);
            $filters[] = array('wg_customer_config_process.name', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }
}
