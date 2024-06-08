<?php

namespace Wgroup\Employee;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;

class EmployeeService
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

        $model = new Employee();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new EmployeeRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_employee.documentType',
            'wg_employee.documentNumber',
            'wg_employee.name',
            'wg_employee.type',
            'wg_employee.gender'
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
            $this->employeeRepository->sortBy('wg_employee.lastName', 'asc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee.type', $search);
            $filters[] = array('wg_employee.documentType', $search);
            $filters[] = array('wg_employee.documentNumber', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_employee.gender', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_employee.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_employee.isActive', '0');
        }

        $this->employeeRepository->setColumns(['wg_employee.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllRecordsCount($search = "")
    {

        $model = new Employee();
        $this->employeeRepository = new EmployeeRepository($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee.type', $search);
            $filters[] = array('wg_employee.documentType', $search);
            $filters[] = array('wg_employee.documentNumber', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_employee.gender', $search);
        }

        $this->employeeRepository->setColumns(['wg_employee.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, true, "");
    }
}
