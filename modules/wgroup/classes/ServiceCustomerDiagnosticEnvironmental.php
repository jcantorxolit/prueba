<?php

namespace Wgroup\Classes;
use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerDiagnosticEnvironmental;
use Wgroup\Models\CustomerDiagnosticEnvironmentalReporistory;

class ServiceCustomerDiagnosticEnvironmental {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticEnvironmentalRepository;

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

        $model = new CustomerDiagnosticEnvironmental();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticEnvironmentalRepository = new CustomerDiagnosticEnvironmentalReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticEnvironmentalRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customers_diagnostic_environmental_measures.diagnostic_id',
            'wg_customers_diagnostic_environmental_measures.measure',
            'wg_customers_diagnostic_environmental_measures.observation',
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
                    $this->customerDiagnosticEnvironmentalRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticEnvironmentalRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticEnvironmentalRepository->sortBy('wg_customers_diagnostic_environmental_measures.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customers_diagnostic_environmental_measures.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers_diagnostic_environmental_measures.measure', $search);
            $filters[] = array('wg_customers_diagnostic_environmental_measures.observation', $search);
        }

        $this->customerDiagnosticEnvironmentalRepository->setColumns(['wg_customers_diagnostic_environmental_measures.*']);

        return $this->customerDiagnosticEnvironmentalRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticEnvironmental();
        $this->customerDiagnosticEnvironmentalRepository = new CustomerDiagnosticEnvironmentalReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customers_diagnostic_environmental_measures.measure', $search);
            $filters[] = array('wg_customers_diagnostic_environmental_measures.observation', $search);
        }

        $this->customerDiagnosticEnvironmentalRepository->setColumns(['wg_customers_diagnostic_environmental_measures.*']);

        return $this->customerDiagnosticEnvironmentalRepository->getFilteredsOptional($filters, true, "");
    }
}
