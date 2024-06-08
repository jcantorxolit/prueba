<?php

namespace Wgroup\CustomerSafetyInspectionConfigListGroup;

use DB;
use Exception;
use Log;
use Str;

class CustomerSafetyInspectionConfigListGroupService {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0) {

        $model = new CustomerSafetyInspectionConfigListGroup();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerSafetyInspectionConfigListGroupRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_safety_inspection_config_list_group.id',
            'wg_customer_safety_inspection_config_list_group.description',
            'wg_customer_safety_inspection_config_list_group.isActive'
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
            $this->customerContractorRepository->sortBy('wg_customer_safety_inspection_config_list_group.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_safety_inspection_config_list_group.customer_safety_inspection_config_list_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_safety_inspection_config_list_group.id', $search);
            $filters[] = array('wg_customer_safety_inspection_config_list_group.description', $search);
            $filters[] = array('wg_customer_safety_inspection_config_list_group.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_safety_inspection_config_list_group.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_safety_inspection_config_list_group.isActive', '0');
        }

        $this->customerContractorRepository->setColumns(['wg_customer_safety_inspection_config_list_group.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId) {

        $model = new CustomerSafetyInspectionConfigListGroup();
        $this->customerContractorRepository = new CustomerSafetyInspectionConfigListGroupRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_safety_inspection_config_list_group.customer_safety_inspection_config_list_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_safety_inspection_config_list_group.id', $search);
            $filters[] = array('wg_customer_safety_inspection_config_list_group.description', $search);
            $filters[] = array('wg_customer_safety_inspection_config_list_group.isActive', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_safety_inspection_config_list_group.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }
}
