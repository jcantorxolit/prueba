<?php

namespace Wgroup\Classes;

use Exception;
use Log;
use RainLab\User\Models\User;
use Str;
use DB;
use Wgroup\Models\CustomerDiagnosticRiskFactor;
use Wgroup\Models\CustomerDiagnosticRiskFactorReporistory;
use Wgroup\Models\CustomerDiagnosticRiskTask;
use Wgroup\Models\CustomerDiagnosticRiskTaskReporistory;

class ServiceCustomerDiagnosticRiskTask {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticRiskTaskRepository;

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

        $model = new CustomerDiagnosticRiskTask();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticRiskTaskRepository = new CustomerDiagnosticRiskTaskReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticRiskTaskRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customers_diagnostic_risk_task.diagnostic_id',
            'wg_customers_diagnostic_risk_task.risk',
            'wg_customers_diagnostic_risk_task.exposed',
            'wg_customers_diagnostic_risk_task.observation',
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
                    $this->customerDiagnosticRiskTaskRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticRiskTaskRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticRiskTaskRepository->sortBy('wg_customers_diagnostic_risk_task.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customers_diagnostic_risk_task.diagnostic_id', $diagnosticId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customers_diagnostic_risk_task.task', $search);
            $filters[] = array('wg_customers_diagnostic_risk_task.exposed', $search);
            $filters[] = array('wg_customers_diagnostic_risk_task.observation', $search);
            $filters[] = array('ftask.item', $search);
        }

        $this->customerDiagnosticRiskTaskRepository->setColumns(['wg_customers_diagnostic_risk_task.*']);

        return $this->customerDiagnosticRiskTaskRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerDiagnosticRiskTask();
        $this->customerDiagnosticRiskTaskRepository = new CustomerDiagnosticRiskTaskReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customers_diagnostic_risk_task.task', $search);
            $filters[] = array('wg_customers_diagnostic_risk_task.exposed', $search);
            $filters[] = array('wg_customers_diagnostic_risk_task.observation', $search);
            $filters[] = array('ftask.item', $search);
        }

        $this->customerDiagnosticRiskTaskRepository->setColumns(['wg_customers_diagnostic_risk_task.*']);

        return $this->customerDiagnosticRiskTaskRepository->getFilteredsOptional($filters, true, "");
    }
}
