<?php

namespace Wgroup\Models;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;

class ProgramManagementCategoryService
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

        $model = new ProgramManagementCategory();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new ProgramManagementCategoryRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_program_management_category.name',
            'wg_program_management_category.status',
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
            $this->employeeRepository->sortBy('wg_program_management_category.code', 'asc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_program_management_category.name', $search);
            $filters[] = array('wg_program_management_category.status', $search);
            $filters[] = array('wg_program_management.name', $search);
        }

        $this->employeeRepository->setColumns(['wg_program_management_category.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllRecordsCount($search = "")
    {

        $model = new ProgramManagementCategory();
        $this->employeeRepository = new ProgramManagementCategoryRepository($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_program_management_category.name', $search);
            $filters[] = array('wg_program_management_category.status', $search);
            $filters[] = array('wg_program_management.name', $search);
        }

        $this->employeeRepository->setColumns(['wg_program_management_category.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, true, "");
    }
}
