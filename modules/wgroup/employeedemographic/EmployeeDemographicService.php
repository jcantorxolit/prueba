<?php

namespace Wgroup\EmployeeDemographic;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;

class EmployeeDemographicService
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

    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array(), $employeeId, $category)
    {

        $model = new EmployeeDemographic();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new EmployeeDemographicRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_employee_demographic.employee_id',
            'wg_employee_demographic.category',
            'wg_employee_demographic.documentNumber',
            'wg_employee_demographic.item',
            'wg_employee_demographic.value'
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
            $this->employeeRepository->sortBy('wg_employee_demographic.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_employee_demographic.employee_id', $employeeId);
        $filters[] = array('wg_employee_demographic.category', $category);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee_demographic.item', $search);
            $filters[] = array('wg_employee_demographic.value', $search);
        }


        $this->employeeRepository->setColumns(['wg_employee_demographic.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllRecordsCount($search = "", $employeeId = 0, $category = '')
    {

        $model = new EmployeeDemographic();
        $this->employeeRepository = new EmployeeDemographicRepository($model);

        $filters = array();

        $filters[] = array('wg_employee_demographic.employee_id', $employeeId);
        $filters[] = array('wg_employee_demographic.category', $category);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee_demographic.item', $search);
            $filters[] = array('wg_employee_demographic.value', $search);
        }

        $this->employeeRepository->setColumns(['wg_employee_demographic.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, true, "");
    }
}
