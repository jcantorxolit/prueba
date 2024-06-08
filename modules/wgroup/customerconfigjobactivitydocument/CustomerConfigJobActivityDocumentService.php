<?php

namespace Wgroup\CustomerConfigJobActivityDocument;

use DB;
use Exception;
use Log;
use Str;


class CustomerConfigJobActivityDocumentService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $jobId) {

        $model = new CustomerConfigJobActivityDocument();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobActivityDocumentRepository($model);

        if ($perPage > 0) {
            $this->customerConfigWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_config_job_activity_document.id',
            'wg_customer_config_job_activity_document.job_activity_id',
            'wg_customer_config_job_activity_document.type'
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
            $this->customerConfigWorkPlaceRepository->sortBy('wg_customer_config_job_activity_document.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_config_job_activity_document.job_activity_id', $jobId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_config_job_activity_document.type', $search);
            $filters[] = array('wg_customer_config_job_activity_document.created_at', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job_activity_document.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $jobId) {

        $model = new CustomerConfigJobActivityDocument();
        $this->customerConfigWorkPlaceRepository = new CustomerConfigJobActivityDocumentRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_config_job_activity_document.job_activity_id', $jobId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_config_job_activity_document.type', $search);
            $filters[] = array('wg_customer_config_job_activity_document.created_at', $search);
        }

        $this->customerConfigWorkPlaceRepository->setColumns(['wg_customer_config_job_activity_document.*']);

        return $this->customerConfigWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }
}
