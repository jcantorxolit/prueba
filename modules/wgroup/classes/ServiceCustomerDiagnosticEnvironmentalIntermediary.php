<?php

namespace Wgroup\Classes;
use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerDiagnosticEnvironmentalIntermediary;
use Wgroup\Models\CustomerDiagnosticEnvironmentalIntermediaryReporistory;

class ServiceCustomerDiagnosticEnvironmentalIntermediary {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticEnvironmentalIntermediaryRepository;

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

        $model = new CustomerDiagnosticEnvironmentalIntermediary();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticEnvironmentalIntermediaryRepository = new CustomerDiagnosticEnvironmentalIntermediaryReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticEnvironmentalIntermediaryRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customers_diagnostic_environmental_measures_inter.diagnostic_id',
            'wg_customers_diagnostic_environmental_measures_inter.measure',
            'wg_customers_diagnostic_environmental_measures_inter.observation',
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
                    $this->customerDiagnosticEnvironmentalIntermediaryRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticEnvironmentalIntermediaryRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticEnvironmentalIntermediaryRepository->sortBy('wg_customers_diagnostic_environmental_measures_inter.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customers_diagnostic_environmental_measures_inter.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers_diagnostic_environmental_measures_inter.measure', $search);
            $filters[] = array('wg_customers_diagnostic_environmental_measures_inter.observation', $search);
        }

        $this->customerDiagnosticEnvironmentalIntermediaryRepository->setColumns(['wg_customers_diagnostic_environmental_measures_inter.*']);

        return $this->customerDiagnosticEnvironmentalIntermediaryRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticEnvironmentalIntermediary();
        $this->customerDiagnosticEnvironmentalIntermediaryRepository = new CustomerDiagnosticEnvironmentalIntermediaryReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customers_diagnostic_environmental_measures_inter.measure', $search);
            $filters[] = array('wg_customers_diagnostic_environmental_measures_inter.observation', $search);
        }

        $this->customerDiagnosticEnvironmentalIntermediaryRepository->setColumns(['wg_customers_diagnostic_environmental_measures_inter.*']);

        return $this->customerDiagnosticEnvironmentalIntermediaryRepository->getFilteredsOptional($filters, true, "");
    }
}
