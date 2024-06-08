<?php

namespace Wgroup\Classes;
use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerDiagnosticArlIntermediary;
use Wgroup\Models\CustomerDiagnosticArlIntermediaryReporistory;

class ServiceCustomerDiagnosticArlIntermediary {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticArlIntermediaryRepository;

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

        $model = new CustomerDiagnosticArlIntermediary();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticArlIntermediaryRepository = new CustomerDiagnosticArlIntermediaryReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticArlIntermediaryRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customers_diagnostic_arl_intermediary.diagnostic_id',
            'wg_customers_diagnostic_arl_intermediary.arlProfessional',
            'wg_customers_diagnostic_arl_intermediary.availability',
            'wg_customers_diagnostic_arl_intermediary.observation',
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
                    $this->customerDiagnosticArlIntermediaryRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticArlIntermediaryRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticArlIntermediaryRepository->sortBy('wg_customers_diagnostic_arl_intermediary.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customers_diagnostic_arl_intermediary.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers_diagnostic_arl_intermediary.arlProfessional', $search);
            $filters[] = array('wg_customers_diagnostic_arl_intermediary.availability', $search);
            $filters[] = array('wg_customers_diagnostic_arl_intermediary.observation', $search);
        }

        $this->customerDiagnosticArlIntermediaryRepository->setColumns(['wg_customers_diagnostic_arl_intermediary.*']);

        return $this->customerDiagnosticArlIntermediaryRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticArlIntermediary();
        $this->customerDiagnosticArlIntermediaryRepository = new CustomerDiagnosticArlIntermediaryReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customers_diagnostic_arl_intermediary.arlProfessional', $search);
            $filters[] = array('wg_customers_diagnostic_arl_intermediary.availability', $search);
            $filters[] = array('wg_customers_diagnostic_arl_intermediary.observation', $search);
        }

        $this->customerDiagnosticArlIntermediaryRepository->setColumns(['wg_customers_diagnostic_arl_intermediary.*']);

        return $this->customerDiagnosticArlIntermediaryRepository->getFilteredsOptional($filters, true, "");
    }
}
