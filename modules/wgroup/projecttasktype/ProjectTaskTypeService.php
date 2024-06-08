<?php

namespace Wgroup\ProjectTaskType;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;

class ProjectTaskTypeService
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

        $model = new ProjectTaskType();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new ProjectTaskTypeRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_project_task_type.description',
            'wg_project_task_type.price',
            'wg_project_task_type.isActive',
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
            $this->employeeRepository->sortBy('wg_project_task_type.code', 'asc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_project_task_type.description', $search);
            $filters[] = array('wg_project_task_type.price', $search);
            $filters[] = array('wg_project_task_type.isActive', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_project_task_type.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_project_task_type.isActive', '0');
        }

        $this->employeeRepository->setColumns(['wg_project_task_type.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllRecordsCount($search = "")
    {

        $model = new ProjectTaskType();
        $this->employeeRepository = new ProjectTaskTypeRepository($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_project_task_type.description', $search);
            $filters[] = array('wg_project_task_type.price', $search);
            $filters[] = array('wg_project_task_type.isActive', $search);
        }

        $this->employeeRepository->setColumns(['wg_project_task_type.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, true, "");
    }
}
