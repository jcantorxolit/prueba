<?php

namespace Wgroup\SystemParameter;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;

class SystemParameterService
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

    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array(), $group = "")
    {

        $model = new SystemParameter();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new SystemParameterRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'system_parameters.item',
            'system_parameters.value',
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
            $this->employeeRepository->sortBy('system_parameters.code', 'asc');
        }

        $filters = array();

        $filters[] = array('system_parameters.group', $group);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('system_parameters.item', $search);
            $filters[] = array('system_parameters.value', $search);
        }

        $this->employeeRepository->setColumns(['system_parameters.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllRecordsCount($search = "", $group = "")
    {

        $model = new SystemParameter();
        $this->employeeRepository = new SystemParameterRepository($model);

        $filters = array();

        $filters[] = array('system_parameters.group', $group);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('system_parameters.item', $search);
            $filters[] = array('system_parameters.value', $search);
        }

        $this->employeeRepository->setColumns(['system_parameters.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllRelation($search, $perPage = 10, $currentPage = 0, $sorting = array(), $group = "", $parent = "")
    {

        $model = new SystemParameter();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new SystemParameterRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'system_parameters.item',
            'system_parameters.value',
            'parent_relation.value',
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
            $this->employeeRepository->sortBy('system_parameters.code', 'asc');
        }

        $filters = array();

        $filters[] = array('system_parameters.group', $group);
        //$filters[] = array('parent_relation.group', $parent);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('system_parameters.item', $search);
            $filters[] = array('system_parameters.value', $search);
            $filters[] = array('parent_relation.item', $search);
        }

        $this->employeeRepository->setColumns(['system_parameters.*']);

        return $this->employeeRepository->getFilteredRelation($filters, false, $parent);
    }

    public function getAllRelationCount($search, $group = "", $parent = "")
    {

        $model = new SystemParameter();

        $this->employeeRepository = new SystemParameterRepository($model);

        $filters = array();

        $filters[] = array('system_parameters.group', $group);
        //$filters[] = array('parent_relation.group', $parent);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('system_parameters.item', $search);
            $filters[] = array('system_parameters.value', $search);
            $filters[] = array('parent_relation.item', $search);
        }

        $this->employeeRepository->setColumns(['system_parameters.*']);

        return $this->employeeRepository->getFilteredRelation($filters, true, $parent);
    }

    public function getGroupParameter()
    {
        $query = "SELECT namespace, `group`, item text, `value`
FROM `system_parameters`
WHERE namespace = 'wgroup' and `group` = 'wg_system_parameter'
ORDER BY item;";

        $results = DB::select( $query );

        return $results;
    }

}
