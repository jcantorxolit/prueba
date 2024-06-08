<?php

namespace Wgroup\CustomerInternalCertificateGradeParticipant;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;


class CustomerInternalCertificateGradeParticipantService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $quoteRepository;

    function __construct() {

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $certificateGradeId) {

        $model = new CustomerInternalCertificateGradeParticipant();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->quoteRepository = new CustomerInternalCertificateGradeParticipantRepository($model);

        if ($perPage > 0) {
            $this->quoteRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_internal_certificate_grade_participant.id',
            'wg_customer_internal_certificate_grade_participant.customer_internal_certificate_grade_id'
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
            $this->quoteRepository->sortBy('wg_customer_internal_certificate_grade_participant.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_internal_certificate_grade_participant.customer_internal_certificate_grade_id', $certificateGradeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.documentNumber', $search);
            $filters[] = array('employee_document_type.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_internal_certificate_grade_participant.isApproved', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_internal_certificate_grade_participant.isApproved', '0');
        }


        $this->quoteRepository->setColumns(['wg_customer_internal_certificate_grade_participant.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $certificateGradeId) {

        $model = new CustomerInternalCertificateGradeParticipant();
        $this->quoteRepository = new CustomerInternalCertificateGradeParticipantRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_internal_certificate_grade_participant.customer_internal_certificate_grade_id', $certificateGradeId);

        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.documentNumber', $search);
            $filters[] = array('employee_document_type.item', $search);
        }

        $this->quoteRepository->setColumns(['wg_customer_internal_certificate_grade_participant.*']);

        return $this->quoteRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllByFilter($search, $perPage = 10, $currentPage = 0, $audit = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select p.id, p.identificationNumber, p.documentType, p.name, p.lastName, p.customer, p.grade, p.certificateCreatedAt, p.certificateExpirationAt, p.origin
from (
        Select cgp.id, e.documentNumber identificationNumber, p.item documentType, e.firstName `name`, e.lastName, c.businessName customer, cg.name grade, certificateCreatedAt
        , case when cp.validityType = 'dias' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber DAY)
                    when cp.validityType = 'meses' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber MONTH)
                    when cp.validityType = 'anios' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber YEAR) END certificateExpirationAt
                    , 'Waygroup' origin
        from wg_customer_internal_certificate_grade_participant cgp
        inner join wg_customer_employee ce on cgp.customer_employee_id = ce.id
        inner join wg_employee e on ce.employee_id = e.id
        inner join wg_customer_internal_certificate_grade cg on cgp.customer_internal_certificate_grade_id = cg.id
        inner join wg_customers c on cgp.customer_id = c.id
        inner join wg_customer_internal_certificate_program cp on cg.customer_internal_certificate_program_id = cp.id
        left join (select * from system_parameters where system_parameters.group = 'employee_document_type') p on e.documentType COLLATE utf8_general_ci = p.value
        where cgp.hasCertificate = :hasCertificate
        ) p ";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.origin, p.certificateCreatedAt DESC ";

        if ($audit != null) {
            $query.= $this->getWhere($audit->filters);
        }

        $query.=$orderBy.$limit;

        $results = DB::select( $query, array(
            'hasCertificate' => 1
        ));

        return $results;

    }

    public function getAllByFilterCustomer($search, $perPage = 10, $currentPage = 0, $audit = null, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select p.id, p.identificationNumber, p.documentType, p.name, p.lastName, p.customer, p.grade, p.certificateCreatedAt, p.certificateExpirationAt, p.origin
from (
        Select cgp.id, e.documentNumber identificationNumber, p.item documentType, e.firstName `name`, e.lastName, c.businessName customer, cg.name grade, certificateCreatedAt
        , case when cp.validityType = 'dias' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber DAY)
                    when cp.validityType = 'meses' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber MONTH)
                    when cp.validityType = 'anios' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber YEAR) END certificateExpirationAt
                  ,'Waygroup' origin
        from wg_customer_internal_certificate_grade_participant cgp
        inner join wg_customer_employee ce on cgp.customer_employee_id = ce.id
        inner join wg_employee e on ce.employee_id = e.id
        inner join wg_customer_internal_certificate_grade cg on cgp.customer_internal_certificate_grade_id = cg.id
        inner join wg_customers c on cgp.customer_id = c.id
        inner join wg_customer_internal_certificate_program cp on cg.customer_internal_certificate_program_id = cp.id
        left join (select * from system_parameters where system_parameters.group = 'employee_document_type') p on e.documentType COLLATE utf8_general_ci = p.value
        where cgp.hasCertificate = :hasCertificate and cgp.customer_id = :customerId_1
        ) p ";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.origin, p.certificateCreatedAt DESC ";

        if ($audit != null) {
            $query.= $this->getWhere($audit->filters);
        }

        $query.=$orderBy.$limit;

        $results = DB::select( $query, array(
            'hasCertificate' => 1,
            'customerId_1' => $customerId
        ));

        return $results;

    }

    public function getAllByFilterCount($search, $perPage = 10, $currentPage = 0, $audit = null) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select p.id, p.identificationNumber, p.documentType, p.name, p.lastName, p.customer, p.grade, p.certificateCreatedAt, p.certificateExpirationAt, p.origin
from (
        Select cgp.id, e.documentNumber identificationNumber, p.item documentType, e.firstName `name`, e.lastName, c.businessName customer, cg.name grade, certificateCreatedAt
        , case when cp.validityType = 'dias' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber DAY)
                    when cp.validityType = 'meses' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber MONTH)
                    when cp.validityType = 'anios' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber YEAR) END certificateExpirationAt
                    ,'Waygroup' origin
        from wg_customer_internal_certificate_grade_participant cgp
        inner join wg_customer_employee ce on cgp.customer_employee_id = ce.id
        inner join wg_employee e on ce.employee_id = e.id
        inner join wg_customer_internal_certificate_grade cg on cgp.customer_internal_certificate_grade_id = cg.id
        inner join wg_customers c on cgp.customer_id = c.id
        inner join wg_customer_internal_certificate_program cp on cg.customer_internal_certificate_program_id = cp.id
        left join (select * from system_parameters where system_parameters.group = 'employee_document_type') p on e.documentType COLLATE utf8_general_ci = p.value
        where cgp.hasCertificate = :hasCertificate
        ) p ";

        $limit = " LIMIT $startFrom , $perPage";

        if ($audit != null) {
            $query.= $this->getWhere($audit->filters);
        }

        $results = DB::select( $query, array(
            'hasCertificate' => 1
        ));

        return $results;

    }

    public function getAllByFilterCountCustomer($search, $perPage = 10, $currentPage = 0, $audit = null, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select p.id, p.identificationNumber, p.documentType, p.name, p.lastName, p.customer, p.grade, p.certificateCreatedAt, p.certificateExpirationAt, p.origin
from (
        Select cgp.id, e.documentNumber identificationNumber, p.item documentType, e.firstName `name`, e.lastName, c.businessName customer, cg.name grade, certificateCreatedAt
        , case when cp.validityType = 'dias' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber DAY)
                    when cp.validityType = 'meses' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber MONTH)
                    when cp.validityType = 'anios' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber YEAR) END certificateExpirationAt
                    , 'Waygroup' origin
        from wg_customer_internal_certificate_grade_participant cgp
        inner join wg_customer_employee ce on cgp.customer_employee_id = ce.id
        inner join wg_employee e on ce.employee_id = e.id
        inner join wg_customer_internal_certificate_grade cg on cgp.customer_internal_certificate_grade_id = cg.id
        inner join wg_customers c on cgp.customer_id = c.id
        inner join wg_customer_internal_certificate_program cp on cg.customer_internal_certificate_program_id = cp.id
        left join (select * from system_parameters where system_parameters.group = 'employee_document_type') p on e.documentType COLLATE utf8_general_ci = p.value
        where cgp.hasCertificate = :hasCertificate and cgp.customer_id = :customerId_1
        ) p ";

        $limit = " LIMIT $startFrom , $perPage";

        if ($audit != null) {
            $query.= $this->getWhere($audit->filters);
        }

        $results = DB::select( $query, array(
            'hasCertificate' => 1,
            'customerId_1' => $customerId
        ));

        return $results;

    }

    public function getAllByExpiration($search, $perPage = 10, $currentPage = 0, $year = 0, $month = 0, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select p.id, p.identificationNumber, p.documentType, p.name, p.lastName, p.customer, p.grade, p.certificateCreatedAt, p.certificateExpirationAt, p.customerId, p.origin
from (
        Select cgp.id, e.documentNumber identificationNumber, p.item documentType, e.firstName `name`, e.lastName, c.id customerId, c.businessName customer, cg.name grade, certificateCreatedAt
        , case when cp.validityType = 'dias' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber DAY)
                    when cp.validityType = 'meses' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber MONTH)
                    when cp.validityType = 'anios' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber YEAR) END certificateExpirationAt
                    ,'Waygroup' origin
        from wg_customer_internal_certificate_grade_participant cgp
        inner join wg_customer_employee ce on cgp.customer_employee_id = ce.id
        inner join wg_employee e on ce.employee_id = e.id
        inner join wg_customer_internal_certificate_grade cg on cgp.customer_internal_certificate_grade_id = cg.id
        inner join wg_customers c on cgp.customer_id = c.id
        inner join wg_customer_internal_certificate_program cp on cg.customer_internal_certificate_program_id = cp.id
        left join (select * from system_parameters where system_parameters.group = 'employee_document_type') p on e.documentType COLLATE utf8_general_ci = p.value
        where cgp.hasCertificate = 1) p ";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.certificateCreatedAt DESC";

        $whereArray = array();
        $where = '';

        if ($month != 0) {
            $where .= " WHERE MONTH(p.certificateExpirationAt) = :month";
            $whereArray["month"] = $month;
        }

        if ($year != 0) {
            if (empty($where)) {
                $where .= " WHERE YEAR(p.certificateExpirationAt) = :year";
            } else {
                $where .= " AND YEAR(p.certificateExpirationAt) = :year";
            }
            $whereArray["year"] = $year;
        }

        if ($customerId != 0) {
            if (empty($where)) {
                $where .= " WHERE p.customerId = :customerId";
            } else {
                $where .= " AND p.customerId = :customerId";
            }
            $whereArray["customerId"] = $customerId;
        }

        $sql = $query.$where;
        $sql.=$orderBy.$limit;

        //Log::info($year);

        $results = DB::select( $sql, $whereArray);

        return $results;

    }

    public function getAllByExpirationCount($search, $perPage = 10, $currentPage = 0, $year = 0, $month = 0, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select p.id, p.identificationNumber, p.documentType, p.name, p.lastName, p.customer, p.grade, p.certificateCreatedAt, p.certificateExpirationAt, p.customerId
from (
        Select cgp.id, e.documentNumber identificationNumber, p.item documentType, e.firstName `name`, e.lastName, c.id customerId, c.businessName customer, cg.name grade, certificateCreatedAt
        , case when cp.validityType = 'dias' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber DAY)
                    when cp.validityType = 'meses' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber MONTH)
                    when cp.validityType = 'anios' then DATE_ADD(cgp.certificateCreatedAt, INTERVAL cp.validityNumber YEAR) END certificateExpirationAt
        from wg_customer_internal_certificate_grade_participant cgp
        inner join wg_customer_employee ce on cgp.customer_employee_id = ce.id
        inner join wg_employee e on ce.employee_id = e.id
        inner join wg_customer_internal_certificate_grade cg on cgp.customer_internal_certificate_grade_id = cg.id
        inner join wg_customers c on cgp.customer_id = c.id
        inner join wg_customer_internal_certificate_program cp on cg.customer_internal_certificate_program_id = cp.id
        left join (select * from system_parameters where system_parameters.group = 'employee_document_type') p on e.documentType COLLATE utf8_general_ci = p.value
        where cgp.hasCertificate = 1) p ";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.certificateCreatedAt DESC";

        $whereArray = array();
        $where = '';

        if ($month != 0) {
            $where .= " WHERE MONTH(p.certificateExpirationAt) = :month";
            $whereArray["month"] = $month;
        }

        if ($year != 0) {
            if (empty($where)) {
                $where .= " WHERE YEAR(p.certificateExpirationAt) = :year";
            } else {
                $where .= " AND YEAR(p.certificateExpirationAt) = :year";
            }
            $whereArray["year"] = $year;
        }

        if ($customerId != 0) {
            if (empty($where)) {
                $where .= " WHERE p.customerId = :customerId";
            } else {
                $where .= " AND p.customerId = :customerId";
            }
            $whereArray["customerId"] = $customerId;
        }

        $sql = $query.$where;
        $sql.=$orderBy;

        $results = DB::select( $sql, $whereArray);

        return $results;

    }

    private function getWhere($filters)
    {
        //Log::info("where");

        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            //Log::info("foreach");

            if ($lastFilter  == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value ."' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value ."%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value ."' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value ."' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value ."' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " = '" . $filter->value ."' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " LIKE '%" . $filter->value ."%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " <> '" . $filter->value ."' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " < '" . $filter->value ."' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value. " " . "p." . $filter->field->name . " > '" . $filter->value ."' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        //Log::info($where);
        //Log::info(count($filters));

        return $where == "" ? "" : " WHERE ".$where;
    }
}
