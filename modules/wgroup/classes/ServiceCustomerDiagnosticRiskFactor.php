<?php

namespace Wgroup\Classes;

use Exception;
use Log;
use RainLab\User\Models\User;
use Str;
use DB;
use Wgroup\Models\CustomerDiagnosticRiskFactor;
use Wgroup\Models\CustomerDiagnosticRiskFactorReporistory;

class ServiceCustomerDiagnosticRiskFactor {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticRiskFactorRepository;

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

        $model = new CustomerDiagnosticRiskFactor();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticRiskFactorRepository = new CustomerDiagnosticRiskFactorReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticRiskFactorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customers_diagnostic_risk_factor.diagnostic_id',
            'wg_customers_diagnostic_risk_factor.risk',
            'wg_customers_diagnostic_risk_factor.exposed',
            'wg_customers_diagnostic_risk_factor.preventProgram',
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
                    $this->customerDiagnosticRiskFactorRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticRiskFactorRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticRiskFactorRepository->sortBy('wg_customers_diagnostic_risk_factor.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customers_diagnostic_risk_factor.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers_diagnostic_risk_factor.risk', $search);
            $filters[] = array('wg_customers_diagnostic_risk_factor.exposed', $search);
            $filters[] = array('wg_customers_diagnostic_risk_factor.observation', $search);
            $filters[] = array('frisk.item', $search);
        }

        $this->customerDiagnosticRiskFactorRepository->setColumns(['wg_customers_diagnostic_risk_factor.*']);

        return $this->customerDiagnosticRiskFactorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticRiskFactor();
        $this->customerDiagnosticRiskFactorRepository = new CustomerDiagnosticRiskFactorReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customers_diagnostic_risk_factor.risk', $search);
            $filters[] = array('wg_customers_diagnostic_risk_factor.exposed', $search);
            $filters[] = array('wg_customers_diagnostic_risk_factor.observation', $search);
        }

        $this->customerDiagnosticRiskFactorRepository->setColumns(['wg_customers_diagnostic_risk_factor.*']);

        return $this->customerDiagnosticRiskFactorRepository->getFilteredsOptional($filters, true, "");
    }
}
