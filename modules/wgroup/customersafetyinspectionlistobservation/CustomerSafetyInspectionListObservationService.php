<?php

namespace Wgroup\CustomerSafetyInspectionListObservation;

use DB;
use Exception;
use Log;
use Str;

class CustomerSafetyInspectionListObservationService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContractorRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerSafetyInspectionConfigListId = 0) {

        $model = new CustomerSafetyInspectionListObservation();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerSafetyInspectionListObservationRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_safety_inspection_list_observation.id',
            'wg_customer_safety_inspection_list_observation.observation',
            'wg_customer_safety_inspection_list_observation.isActive'
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
                    $this->customerContractorRepository->sortBy($colName, $dir);
                } else {
                    $this->customerContractorRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerContractorRepository->sortBy('wg_customer_safety_inspection_list_observation.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_safety_inspection.id', $customerSafetyInspectionConfigListId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_safety_inspection_list_observation.id', $search);
            $filters[] = array('wg_customer_safety_inspection_list_observation.observation', $search);
            $filters[] = array('wg_customer_safety_inspection_list_observation.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_safety_inspection_list_observation.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_safety_inspection_list_observation.isActive', '0');
        }

        $this->customerContractorRepository->setColumns(['wg_customer_safety_inspection_list_observation.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerSafetyInspectionConfigListId) {

        $model = new CustomerSafetyInspectionListObservation();
        $this->customerContractorRepository = new CustomerSafetyInspectionListObservationRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_safety_inspection.id', $customerSafetyInspectionConfigListId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_safety_inspection_list_observation.id', $search);
            $filters[] = array('wg_customer_safety_inspection_list_observation.observation', $search);
            $filters[] = array('wg_customer_safety_inspection_list_observation.isActive', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_safety_inspection_list_observation.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }
}
