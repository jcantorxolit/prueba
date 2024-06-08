<?php

namespace Wgroup\CustomerInternalCertificateGrade;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;


class CustomerInternalCertificateGradeService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $quoteRepository;

    function __construct()
    {

    }

    public function init()
    {
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "")
    {

        $model = new CustomerInternalCertificateGrade();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->quoteRepository = new CustomerInternalCertificateGradeRepository($model);

        if ($perPage > 0) {
            $this->quoteRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_internal_certificate_grade.id',
            'wg_customer_internal_certificate_grade.code',
            'wg_customer_internal_certificate_grade.name',
            'wg_customer_internal_certificate_grade.description',
            'wg_customer_internal_certificate_grade.status',
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
                    $this->quoteRepository->sortBy($colName, $dir);
                } else {
                    $this->quoteRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->quoteRepository->sortBy('wg_customer_internal_certificate_grade.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_internal_certificate_grade.status', $search);
            $filters[] = array('wg_customer_internal_certificate_grade.name', $search);
            $filters[] = array('wg_customer_internal_certificate_grade.code', $search);
            $filters[] = array('wg_customer_internal_certificate_grade.description', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_internal_certificate_grade.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_internal_certificate_grade.status', '0');
        }


        $this->quoteRepository->setColumns(['wg_customer_internal_certificate_grade.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {

        $model = new CustomerInternalCertificateGrade();
        $this->quoteRepository = new CustomerInternalCertificateGradeRepository($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_internal_certificate_grade.status', $search);
            $filters[] = array('wg_customer_internal_certificate_grade.name', $search);
            $filters[] = array('wg_customer_internal_certificate_grade.code', $search);
            $filters[] = array('wg_customer_internal_certificate_grade.description', $search);
        }

        $this->quoteRepository->setColumns(['wg_customer_internal_certificate_grade.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, true, "");
    }


    public function getAllByFilters($search, $perPage = 10, $currentPage = 0, $status = '', $location = '', $agentId = 0, $programId = '', $startDate = '', $endDate = '', $customerId = 0)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM (
SELECT cg.id, `cg`.`name`, cp.`name` program, pc.item category, cp.capacity, IFNULL(cgp.`aggregate`,0) registered
, cp.capacity - IFNULL(cgp.`aggregate`,0) quota, gs.item `status`
, cp.id programId, cg.location, cg.`status` statusId
, cg.created_at
FROM `wg_customer_internal_certificate_grade` cg
LEFT JOIN wg_customer_internal_certificate_program cp on cg.customer_internal_certificate_program_id = cp.id
LEFT JOIN (SELECT * FROM `system_parameters` WHERE `namespace` = 'wgroup' and `group` = 'certificate_program_category') pc on pc.value = cp.category
LEFT JOIN (SELECT * FROM `system_parameters` WHERE `namespace` = 'wgroup' and `group` = 'certificate_grade_location') gl on gl.value = cg.location
LEFT JOIN (SELECT * FROM `system_parameters` WHERE `namespace` = 'wgroup' and `group` = 'certificate_grade_status') gs on gs.value = cg.`status`
LEFT JOIN (SELECT customer_internal_certificate_grade_id, count(*) as aggregate FROM `wg_customer_internal_certificate_grade_participant` group by customer_internal_certificate_grade_id) cgp on cgp.customer_internal_certificate_grade_id = cg.id
WHERE cp.customer_id = :customer_id
) p ";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.created_at DESC, p.name";

        $whereArray = array();

        $whereArray["customer_id"] = $customerId;

        $where = '';

        if ($status != '') {
            $where .= " WHERE p.statusId = :status";
            $whereArray["status"] = $status;
        }

        if ($location != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator  p.location = :location";
            $whereArray["location"] = $location;
        }

        if ($agentId != '' && $agentId != '0') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT customer_internal_certificate_grade_id FROM `wg_customer_internal_certificate_grade_agent` WHERE `wg_customer_internal_certificate_grade_agent`.`agent_id` = '$agentId')";
        }

        if ($programId != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.programId= :programId";
            $whereArray["programId"] = $programId;
        }

        if ($startDate != '' && $endDate != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT customer_internal_certificate_grade_id FROM `wg_customer_internal_certificate_grade_calendar` WHERE `wg_customer_internal_certificate_grade_calendar`.`startDate` between '$startDate' and '$endDate')";
        } else if ($startDate != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT customer_internal_certificate_grade_id FROM `wg_customer_internal_certificate_grade_calendar` WHERE `wg_customer_internal_certificate_grade_calendar`.`startDate` >= '$startDate')";
        } else if ($endDate != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT customer_internal_certificate_grade_id FROM `wg_customer_internal_certificate_grade_calendar` WHERE `wg_customer_internal_certificate_grade_calendar`.`startDate` <= '$startDate')";
        }

        if ($search != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.name like '%$search%' OR p.program like '%$search%' OR p.category like '%$search%' OR p.capacity like '%$search%' OR p.registered like '%$search%' OR p.quota like '%$search%'";
        }

        $sql = $query . $where;
        $sql .= $orderBy . $limit;


        $results = DB::select($sql, $whereArray);

        return $results;

    }

    public function getAllByFiltersCount($search, $status = '', $location = '', $agentId = 0, $programId = '', $startDate = '', $endDate = '', $customerId = 0)
    {

        $query = "SELECT * FROM (
SELECT cg.id, `cg`.`name`, cp.`name` program, pc.item category, cp.capacity, IFNULL(cgp.`aggregate`,0) registered
, cp.capacity - IFNULL(cgp.`aggregate`,0) quota, gs.item `status`
, cp.id programId, cg.location, cg.`status` statusId
FROM `wg_customer_internal_certificate_grade` cg
LEFT JOIN wg_customer_internal_certificate_program cp on cg.customer_internal_certificate_program_id = cp.id
LEFT JOIN (SELECT * FROM `system_parameters` WHERE `namespace` = 'wgroup' and `group` = 'certificate_program_category') pc on pc.value = cp.category
LEFT JOIN (SELECT * FROM `system_parameters` WHERE `namespace` = 'wgroup' and `group` = 'certificate_grade_location') gl on gl.value = cg.location
LEFT JOIN (SELECT * FROM `system_parameters` WHERE `namespace` = 'wgroup' and `group` = 'certificate_grade_status') gs on gs.value = cg.`status`
LEFT JOIN (SELECT customer_internal_certificate_grade_id, count(*) as aggregate FROM `wg_customer_internal_certificate_grade_participant` group by customer_internal_certificate_grade_id) cgp on cgp.customer_internal_certificate_grade_id = cg.id ) p ";

        $limit = " ";
        $orderBy = " ORDER BY p.name";

        $whereArray = array();

        $whereArray["customer_id"] = $customerId;

        $where = '';

        if ($status != '') {
            $where .= " WHERE p.statusId = :status";
            $whereArray["status"] = $status;
        }

        if ($location != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator  p.location = :location";
            $whereArray["location"] = $location;
        }

        if ($agentId != '' && $agentId != '0') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT customer_internal_certificate_grade_id FROM `wg_customer_internal_certificate_grade_agent` WHERE `wg_customer_internal_certificate_grade_agent`.`agent_id` = '$agentId')";
        }

        if ($programId != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.programId= :programId";
            $whereArray["programId"] = $programId;
        }

        if ($startDate != '' && $endDate != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT customer_internal_certificate_grade_id FROM `wg_customer_internal_certificate_grade_calendar` WHERE `wg_customer_internal_certificate_grade_calendar`.`startDate` between '$startDate' and '$endDate')";
        } else if ($startDate != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT customer_internal_certificate_grade_id FROM `wg_customer_internal_certificate_grade_calendar` WHERE `wg_customer_internal_certificate_grade_calendar`.`startDate` >= '$startDate')";
        } else if ($endDate != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT customer_internal_certificate_grade_id FROM `wg_customer_internal_certificate_grade_calendar` WHERE `wg_customer_internal_certificate_grade_calendar`.`startDate` <= '$startDate')";
        }

        if ($search != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.name like '%$search%' OR p.category like '%$search%' OR p.capacity like '%$search%' OR p.registered like '%$search%' OR p.quota like '%$search%'";
        }

        $sql = $query . $where;
        $sql .= $orderBy . $limit;


        $results = DB::select($sql, $whereArray);

        return count($results);
    }
}
