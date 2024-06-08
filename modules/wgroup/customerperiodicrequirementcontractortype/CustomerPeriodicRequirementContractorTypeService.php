<?php

namespace Wgroup\CustomerPeriodicRequirementContractorType;

use DB;
use Exception;
use Log;
use Str;

class CustomerPeriodicRequirementContractorTypeService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerPeriodicRequirementRepository;

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

        $model = new CustomerPeriodicRequirementContractorType();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerPeriodicRequirementRepository = new CustomerPeriodicRequirementContractorTypeRepository($model);

        if ($perPage > 0) {
            $this->customerPeriodicRequirementRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_periodic_requirement.id',
            'wg_customer_periodic_requirement.requirement',
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
                    $this->customerPeriodicRequirementRepository->sortBy($colName, $dir);
                } else {
                    $this->customerPeriodicRequirementRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerPeriodicRequirementRepository->sortBy('wg_customer_periodic_requirement.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_periodic_requirement.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_periodic_requirement.id', $search);
            $filters[] = array('wg_customer_periodic_requirement.customer_id', $search);
            $filters[] = array('wg_customer_periodic_requirement.requirement', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.documentType', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_periodic_requirement.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_periodic_requirement.isActive', '0');
        }

        $this->customerPeriodicRequirementRepository->setColumns(['wg_customer_periodic_requirement.*']);

        return $this->customerPeriodicRequirementRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId) {

        $model = new CustomerPeriodicRequirementContractorType();
        $this->customerPeriodicRequirementRepository = new CustomerPeriodicRequirementContractorTypeRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_periodic_requirement.customer_id', $customerId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_periodic_requirement.id', $search);
            $filters[] = array('wg_customer_periodic_requirement.customer_id', $search);
            $filters[] = array('wg_customer_periodic_requirement.requirement', $search);
            $filters[] = array('wg_customers.businessName', $search);
            $filters[] = array('wg_customers.documentNumber', $search);
            $filters[] = array('wg_customers.documentType', $search);
        }

        $this->customerPeriodicRequirementRepository->setColumns(['wg_customer_periodic_requirement.*']);

        return $this->customerPeriodicRequirementRepository->getFilteredsOptional($filters, true, "");
    }
}
