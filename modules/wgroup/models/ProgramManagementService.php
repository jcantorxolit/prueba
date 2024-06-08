<?php

namespace Wgroup\Models;

use DB;
use Exception;
use Illuminate\Support\Facades\Input;
use Log;
use Str;

class ProgramManagementService
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
    }

    public function getAll($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "")
    {

        $model = new ProgramManagement();

        // set current page
        \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->employeeRepository = new ProgramManagementRepository($model);

        if ($perPage > 0) {
            $this->employeeRepository->paginate($perPage);
        }

        // sorting

        $columns = [
            'wg_program_management.id',
            'wg_program_management.name',
            'wg_program_management.abbreviation',
            'wg_program_management.color',
            'wg_program_management.color',
            'wg_program_management.status',
            'wg_program_management.isWeighted',
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
            $this->employeeRepository->sortBy('wg_program_management.code', 'asc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_program_management.name', $search);
            $filters[] = array('wg_program_management.abbreviation', $search);
            $filters[] = array('wg_program_management.color', $search);
            $filters[] = array('wg_program_management.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_program_management.isActive', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_program_management.isActive', '0');
        }

        $this->employeeRepository->setColumns(['wg_program_management.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllRecordsCount($search = "")
    {

        $model = new ProgramManagement();
        $this->employeeRepository = new ProgramManagementRepository($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_program_management.name', $search);
            $filters[] = array('wg_program_management.abbreviation', $search);
            $filters[] = array('wg_program_management.color', $search);
            $filters[] = array('wg_program_management.status', $search);
        }

        $this->employeeRepository->setColumns(['wg_program_management.*']);

        return $this->employeeRepository->getFilteredsOptional($filters, true, "");
    }

    public function getProgramList()
    {
        $sql = "SELECT
                    `wg_program_management`.*,
                    IFNULL(SUM( wg_program_management_question.weightedValue ),0) weightedValueTotal 
                FROM
                    `wg_program_management`
                    LEFT JOIN `wg_program_management_category` ON `wg_program_management_category`.`program_id` = `wg_program_management`.`id`
                    LEFT JOIN `wg_program_management_question` ON `wg_program_management_question`.`category_id` = `wg_program_management_category`.`id` 
                
                GROUP BY
                    `wg_program_management`.`id` 
                ORDER BY
                    wg_program_management.NAME";

        return DB::select($sql);
    }
}
