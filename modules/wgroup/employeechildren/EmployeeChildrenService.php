<?php

namespace Wgroup\EmployeeChildren;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;

class EmployeeChildrenService
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

    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array(), $employeeId = 0)
    {

        $model = new EmployeeChildren();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new EmployeeChildrenRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_employee_children.employee_id',
            'wg_employee_children.name',
            'wg_employee_children.lastName',
            'wg_employee_children.age'
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
            $this->employeeRepository->sortBy('wg_employee_children.lastName', 'asc');
        }

        $filters = array();

        $filters[] = array('wg_employee_children.employee_id', $employeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee_children.name', $search);
            $filters[] = array('wg_employee_children.lastName', $search);
            $filters[] = array('wg_employee_children.age', $search);
        }

        $this->employeeRepository->setColumns(['wg_employee_children.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllRecordsCount($search = "", $employeeId = 0)
    {

        $model = new EmployeeChildren();
        $this->employeeRepository = new EmployeeChildrenRepository($model);

        $filters = array();

        $filters[] = array('wg_employee_children.employee_id', $employeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee_children.name', $search);
            $filters[] = array('wg_employee_children.lastName', $search);
            $filters[] = array('wg_employee_children.age', $search);
        }

        $this->employeeRepository->setColumns(['wg_employee_children.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, true, "");
    }
}
