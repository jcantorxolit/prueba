<?php

namespace Wgroup\CustomerConfigJobData;

use DB;
use Exception;
use Log;
use Str;


class CustomerConfigJobDataService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerConfigWorkPlaceRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId)
    {

        $model = new CustomerConfigJobData();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobDataRepository($model);

        if ($perPage > 0) {
            $this->customerConfigWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_config_job_data.name',
            'wg_customer_config_job_data.status'
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
            $this->customerConfigWorkPlaceRepository->sortBy('wg_customer_config_job_data.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_config_job_data.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_job_data.name', $search);
            $filters[] = array('config_workplace_status.item', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job_data.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerConfigJobData();
        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobDataRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_config_job_data.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_job_data.name', $search);
            $filters[] = array('config_workplace_status.item', $search);
        }


        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job_data.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }
}
