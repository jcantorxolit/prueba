<?php

namespace Wgroup\Classes;
use Wgroup\Models\CustomerDiagnosticProcessReporistory;
use Wgroup\Models\CustomerDiagnosticProcess;
use Exception;
use Log;
use RainLab\User\Models\User;
use Str;
use DB;

class ServiceCustomerDiagnosticProcess {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticProcessRepository;

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

        $model = new CustomerDiagnosticProcess();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticProcessRepository = new CustomerDiagnosticProcessReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticProcessRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customers_diagnostic_process.diagnostic_id',
            'wg_customers_diagnostic_process.input',
            'wg_customers_diagnostic_process.production',
            'wg_customers_diagnostic_process.endProduct',
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
                    $this->customerDiagnosticProcessRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticProcessRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticProcessRepository->sortBy('wg_customers_diagnostic_process.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customers_diagnostic_process.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers_diagnostic_process.input', $search);
            $filters[] = array('wg_customers_diagnostic_process.production', $search);
            $filters[] = array('wg_customers_diagnostic_process.endProduct', $search);
        }

        $this->customerDiagnosticProcessRepository->setColumns(['wg_customers_diagnostic_process.*']);

        return $this->customerDiagnosticProcessRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticProcess();
        $this->customerDiagnosticProcessRepository = new CustomerDiagnosticProcessReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customers_diagnostic_process.input', $search);
            $filters[] = array('wg_customers_diagnostic_process.production', $search);
            $filters[] = array('wg_customers_diagnostic_process.endProduct', $search);
        }

        $this->customerDiagnosticProcessRepository->setColumns(['wg_customers_diagnostic_process.*']);

        return $this->customerDiagnosticProcessRepository->getFilteredsOptional($filters, true, "");
    }
}
