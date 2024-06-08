<?php

namespace Wgroup\DisabilityDiagnostic;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;

class DisabilityDiagnosticService
{

    protected static $instance;
    protected $sessionKey = 'service_agent';
    protected $employeeRepository;

    function __construct()
    {
        //$this->employeeRepository = new CustomerReporistory();
    }

    public function init()
    {
        parent::init();
    }

    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "")
    {

        $model = new DisabilityDiagnostic();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new DisabilityDiagnosticRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_disability_diagnostic.code',
            'wg_disability_diagnostic.description',
            'wg_disability_diagnostic.isActive',
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
                    $this->employeeRepository->sortBy($colName, $dir);
                } else {
                    $this->employeeRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->employeeRepository->sortBy('wg_disability_diagnostic.code', 'asc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_disability_diagnostic.code', $search);
            $filters[] = array('wg_disability_diagnostic.description', $search);
            $filters[] = array('wg_disability_diagnostic.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_disability_diagnostic.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_disability_diagnostic.isActive', '0');
        }

        $this->employeeRepository->setColumns(['wg_disability_diagnostic.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllRecordsCount($search = "")
    {

        $model = new DisabilityDiagnostic();
        $this->employeeRepository = new DisabilityDiagnosticRepository($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_disability_diagnostic.code', $search);
            $filters[] = array('wg_disability_diagnostic.description', $search);
            $filters[] = array('wg_disability_diagnostic.isActive', $search);
        }

        $this->employeeRepository->setColumns(['wg_disability_diagnostic.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllByEmployee($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerEmployeeId = 0)
    {

        $model = new DisabilityDiagnostic();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new DisabilityDiagnosticRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_disability_diagnostic.code',
            'wg_disability_diagnostic.description',
            'wg_disability_diagnostic.isActive',
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
                    $this->employeeRepository->sortBy($colName, $dir);
                } else {
                    $this->employeeRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->employeeRepository->sortBy('wg_disability_diagnostic.code', 'asc');
        }

        $filters = array();

        $filters[] = array('wg_customer_absenteeism_disability.customer_employee_id', $customerEmployeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_disability_diagnostic.code', $search);
            $filters[] = array('wg_disability_diagnostic.description', $search);
            $filters[] = array('wg_disability_diagnostic.isActive', $search);
        }

        $this->employeeRepository->setColumns(['wg_disability_diagnostic.*']);

        return $this->employeeRepository->getFilteredEmployeeOptional($filters, false, "");
    }

    public function getAllByEmployeeRecordsCount($search = "", $customerEmployeeId = 0)
    {

        $model = new DisabilityDiagnostic();
        $this->employeeRepository = new DisabilityDiagnosticRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_absenteeism_disability.customer_employee_id', $customerEmployeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_disability_diagnostic.code', $search);
            $filters[] = array('wg_disability_diagnostic.description', $search);
            $filters[] = array('wg_disability_diagnostic.isActive', $search);
        }

        $this->employeeRepository->setColumns(['wg_disability_diagnostic.*']);

        return $this->employeeRepository->getFilteredEmployeeOptional($filters, true, "");
    }

    public function getAllByDiagnosticSourceEmployee($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerEmployeeId = 0)
    {

        $model = new DisabilityDiagnostic();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new DisabilityDiagnosticRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_disability_diagnostic.code',
            'wg_disability_diagnostic.description',
            'wg_disability_diagnostic.isActive',
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
                    $this->employeeRepository->sortBy($colName, $dir);
                } else {
                    $this->employeeRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->employeeRepository->sortBy('wg_disability_diagnostic.code', 'asc');
        }

        $filters = array();

        $filters[] = array('wg_customer_health_damage_diagnostic_source.customer_employee_id', $customerEmployeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_disability_diagnostic.code', $search);
            $filters[] = array('wg_disability_diagnostic.description', $search);
            $filters[] = array('wg_disability_diagnostic.isActive', $search);
        }

        $this->employeeRepository->setColumns(['wg_disability_diagnostic.*']);

        return $this->employeeRepository->getFilteredDiagnosticSourceEmployeeOptional($filters, false, "");
    }

    public function getAllByDiagnosticSourceEmployeeRecordsCount($search = "", $customerEmployeeId = 0)
    {

        $model = new DisabilityDiagnostic();
        $this->employeeRepository = new DisabilityDiagnosticRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_health_damage_diagnostic_source.customer_employee_id', $customerEmployeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_disability_diagnostic.code', $search);
            $filters[] = array('wg_disability_diagnostic.description', $search);
            $filters[] = array('wg_disability_diagnostic.isActive', $search);
        }

        $this->employeeRepository->setColumns(['wg_disability_diagnostic.*']);

        return $this->employeeRepository->getFilteredDiagnosticSourceEmployeeOptional($filters, true, "");
    }
}
