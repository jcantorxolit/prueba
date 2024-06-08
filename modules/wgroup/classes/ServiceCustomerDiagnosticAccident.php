<?php

namespace Wgroup\Classes;

use Wgroup\Controllers\CustomerDiagnosticProcess;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDiagnostic;
use Wgroup\Models\CustomerDiagnosticAccident;
use Wgroup\Models\CustomerDiagnosticAccidentReporistory;
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

class ServiceCustomerDiagnosticAccident {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticAccidentRepository;

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

        $model = new CustomerDiagnosticAccident();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticAccidentRepository = new CustomerDiagnosticAccidentReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticAccidentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_diagnostic_accident.description',
            'wg_customer_diagnostic_accident.correctiveMeasure',
            'wg_customer_diagnostic_accident.directEmployees',
            'wg_customer_diagnostic_accident.contact',
            'wg_customer_diagnostic_accident.status'
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
                    $this->customerDiagnosticAccidentRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticAccidentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticAccidentRepository->sortBy('wg_customer_diagnostic_accident.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_diagnostic_accident.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_diagnostic_accident.activity', $search);
            $filters[] = array('wg_customer_diagnostic_accident.risk', $search);
            $filters[] = array('wg_customer_diagnostic_accident.directEmployees', $search);
            $filters[] = array('wg_customer_diagnostic_accident.contact', $search);
            $filters[] = array('wg_customer_diagnostic_accident.status', $search);
        }

        $this->customerDiagnosticAccidentRepository->setColumns(['wg_customer_diagnostic_accident.*']);

        return $this->customerDiagnosticAccidentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticAccident();
        $this->customerDiagnosticAccidentRepository = new CustomerDiagnosticAccidentReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_diagnostic_accident.activity', $search);
            $filters[] = array('wg_customer_diagnostic_accident.risk', $search);
            $filters[] = array('wg_customer_diagnostic_accident.directEmployees', $search);
            $filters[] = array('wg_customer_diagnostic_accident.contact', $search);
            $filters[] = array('wg_customer_diagnostic_accident.status', $search);
        }

        $this->customerDiagnosticAccidentRepository->setColumns(['wg_customer_diagnostic_accident.*']);

        return $this->customerDiagnosticAccidentRepository->getFilteredsOptional($filters, true, "");
    }
}
