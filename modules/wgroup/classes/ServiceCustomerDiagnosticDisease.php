<?php

namespace Wgroup\Classes;

use Wgroup\Controllers\CustomerDiagnosticProcess;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDiagnostic;
use Wgroup\Models\CustomerDiagnosticDisease;
use Wgroup\Models\CustomerDiagnosticDiseaseReporistory;
use Wgroup\Models\CustomerDiagnosticDTO;
use Wgroup\Models\CustomerDiagnosticProcessReporistory;
use Wgroup\Models\CustomerDiagnosticReporistory;
use Exception;
use Log;
use RainLab\User\Models\User;
use Str;
use DB;
use Wgroup\Models\CustomerDiagnosticRiskFactor;
use Wgroup\Models\CustomerDiagnosticWorkPlace;
use Wgroup\Models\CustomerDiagnosticWorkPlaceDTO;
use Wgroup\Models\CustomerDiagnosticWorkPlaceReporistory;

class ServiceCustomerDiagnosticDisease {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticDiseaseRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $diagnosticId) {

        $model = new CustomerDiagnosticDisease();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticDiseaseRepository = new CustomerDiagnosticDiseaseReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticDiseaseRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_diagnostic_disease.description',
            'wg_customer_diagnostic_disease.diagnosed',
            'wg_customer_diagnostic_disease.status',
            'wg_customer_diagnostic_disease.numberOfEmployees',
            'wg_customer_diagnostic_disease.riskFactor'
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
                    $this->customerDiagnosticDiseaseRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticDiseaseRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticDiseaseRepository->sortBy('wg_customer_diagnostic_disease.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_diagnostic_disease.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_diagnostic_disease.description', $search);
            $filters[] = array('wg_customer_diagnostic_disease.diagnosed', $search);
            $filters[] = array('wg_customer_diagnostic_disease.status', $search);
            $filters[] = array('wg_customer_diagnostic_disease.numberOfEmployees', $search);
            $filters[] = array('wg_customer_diagnostic_disease.riskFactor', $search);
        }

        $this->customerDiagnosticDiseaseRepository->setColumns(['wg_customer_diagnostic_disease.*']);

        return $this->customerDiagnosticDiseaseRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticDisease();
        $this->customerDiagnosticDiseaseRepository = new CustomerDiagnosticDiseaseReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_diagnostic_disease.description', $search);
            $filters[] = array('wg_customer_diagnostic_disease.diagnosed', $search);
            $filters[] = array('wg_customer_diagnostic_disease.status', $search);
            $filters[] = array('wg_customer_diagnostic_disease.numberOfEmployees', $search);
            $filters[] = array('wg_customer_diagnostic_disease.riskFactor', $search);
        }

        $this->customerDiagnosticDiseaseRepository->setColumns(['wg_customer_diagnostic_disease.*']);

        return $this->customerDiagnosticDiseaseRepository->getFilteredsOptional($filters, true, "");
    }
}
