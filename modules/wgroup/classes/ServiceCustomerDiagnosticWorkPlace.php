<?php

namespace Wgroup\Classes;

use Wgroup\Controllers\CustomerDiagnosticProcess;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDiagnostic;
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

class ServiceCustomerDiagnosticWorkPlace {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticWorkPlaceRepository;

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

        $model = new CustomerDiagnosticWorkPlace();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticWorkPlaceRepository = new CustomerDiagnosticWorkPlaceReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticWorkPlaceRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customers_diagnostic_workplace.activity',
            'wg_customers_diagnostic_workplace.risk',
            'wg_customers_diagnostic_workplace.directEmployees',
            'wg_customers_diagnostic_workplace.contact',
            'wg_customers_diagnostic_workplace.status'
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
                    $this->customerDiagnosticWorkPlaceRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticWorkPlaceRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticWorkPlaceRepository->sortBy('wg_customers_diagnostic_workplace.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customers_diagnostic_workplace.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers_diagnostic_workplace.activity', $search);
            $filters[] = array('wg_customers_diagnostic_workplace.risk', $search);
            $filters[] = array('wg_customers_diagnostic_workplace.directEmployees', $search);
            $filters[] = array('wg_customers_diagnostic_workplace.contact', $search);
            $filters[] = array('wg_customers_diagnostic_workplace.status', $search);
            $filters[] = array('rainlab_user_countries.name', $search);
            $filters[] = array('rainlab_user_states.name', $search);
            $filters[] = array('wg_towns.name', $search);
        }

        $this->customerDiagnosticWorkPlaceRepository->setColumns(['wg_customers_diagnostic_workplace.*']);

        return $this->customerDiagnosticWorkPlaceRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticWorkPlace();
        $this->customerDiagnosticWorkPlaceRepository = new CustomerDiagnosticWorkPlaceReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customers_diagnostic_workplace.activity', $search);
            $filters[] = array('wg_customers_diagnostic_workplace.risk', $search);
            $filters[] = array('wg_customers_diagnostic_workplace.directEmployees', $search);
            $filters[] = array('wg_customers_diagnostic_workplace.contact', $search);
            $filters[] = array('wg_customers_diagnostic_workplace.status', $search);
            $filters[] = array('rainlab_user_countries.name', $search);
            $filters[] = array('rainlab_user_states.name', $search);
            $filters[] = array('wg_towns.name', $search);
        }

        $this->customerDiagnosticWorkPlaceRepository->setColumns(['wg_customers_diagnostic_workplace.*']);

        return $this->customerDiagnosticWorkPlaceRepository->getFilteredsOptional($filters, true, "");
    }
}
