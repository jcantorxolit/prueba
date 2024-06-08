<?php

namespace Wgroup\Classes;

use Wgroup\Controllers\CustomerDiagnosticProcess;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDiagnostic;
use Wgroup\Models\CustomerDiagnosticDTO;
use Wgroup\Models\CustomerDiagnosticObservation;
use Wgroup\Models\CustomerDiagnosticObservationReporistory;
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

class ServiceCustomerDiagnosticObservation {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticObservationRepository;

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

        $model = new CustomerDiagnosticObservation();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticObservationRepository = new CustomerDiagnosticObservationReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticObservationRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_diagnostic_observation.description',
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
                    $this->customerDiagnosticObservationRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticObservationRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticObservationRepository->sortBy('wg_customer_diagnostic_observation.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_diagnostic_observation.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_diagnostic_observation.description', $search);
        }

        $this->customerDiagnosticObservationRepository->setColumns(['wg_customer_diagnostic_observation.*']);

        return $this->customerDiagnosticObservationRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticObservation();
        $this->customerDiagnosticObservationRepository = new CustomerDiagnosticObservationReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_diagnostic_observation.description', $search);
        }

        $this->customerDiagnosticObservationRepository->setColumns(['wg_customer_diagnostic_observation.*']);

        return $this->customerDiagnosticObservationRepository->getFilteredsOptional($filters, true, "");
    }
}
