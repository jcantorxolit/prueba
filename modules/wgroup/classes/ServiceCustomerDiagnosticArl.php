<?php

namespace Wgroup\Classes;
use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerDiagnosticArl;
use Wgroup\Models\CustomerDiagnosticArlReporistory;

class ServiceCustomerDiagnosticArl {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticArlRepository;

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

        $model = new CustomerDiagnosticArl();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticArlRepository = new CustomerDiagnosticArlReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticArlRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customers_diagnostic_arl.diagnostic_id',
            'wg_customers_diagnostic_arl.arlProfessional',
            'wg_customers_diagnostic_arl.availability',
            'wg_customers_diagnostic_arl.observation',
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
                    $this->customerDiagnosticArlRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticArlRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticArlRepository->sortBy('wg_customers_diagnostic_arl.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customers_diagnostic_arl.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers_diagnostic_arl.arlProfessional', $search);
            $filters[] = array('wg_customers_diagnostic_arl.availability', $search);
            $filters[] = array('wg_customers_diagnostic_arl.observation', $search);
        }

        $this->customerDiagnosticArlRepository->setColumns(['wg_customers_diagnostic_arl.*']);

        return $this->customerDiagnosticArlRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticArl();
        $this->customerDiagnosticArlRepository = new CustomerDiagnosticArlReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customers_diagnostic_arl.arlProfessional', $search);
            $filters[] = array('wg_customers_diagnostic_arl.availability', $search);
            $filters[] = array('wg_customers_diagnostic_arl.observation', $search);
        }

        $this->customerDiagnosticArlRepository->setColumns(['wg_customers_diagnostic_arl.*']);

        return $this->customerDiagnosticArlRepository->getFilteredsOptional($filters, true, "");
    }
}
