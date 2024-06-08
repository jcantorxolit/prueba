<?php

namespace Wgroup\CustomerEmployeeDocumentCritical;

use DB;
use Exception;
use Log;
use Str;

class CustomerEmployeeDocumentCriticalService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerEmployeeDocumentRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0) {

        $model = new CustomerEmployeeDocumentCritical();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerEmployeeDocumentRepository = new CustomerEmployeeDocumentCriticalRepository($model);

        if ($perPage > 0) {
            $this->customerEmployeeDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_employee_document_critical.id',
            'wg_customer_employee_document_critical.requirement',
            'wg_customer_employee_document_critical.job_id',
            'wg_customer_employee_document_critical.customer_employee_id',
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
                    $this->customerEmployeeDocumentRepository->sortBy($colName, $dir);
                } else {
                    $this->customerEmployeeDocumentRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerEmployeeDocumentRepository->sortBy('wg_customer_employee_document_critical.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_employee_document_critical.customer_employee_id', $customerEmployeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_employee_document_critical.requirement', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_employee_document_critical.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_employee_document_critical.status', '0');
        }


        $this->customerEmployeeDocumentRepository->setColumns(['wg_customer_employee_document_critical.*']);

        return $this->customerEmployeeDocumentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllBySearch($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
SELECT DISTINCT d.id,
                d.customer_employee_id,
                p.`value` requirement,
                d.description,
                d.version,
                '' agent ,
                   d.startDate ,
                   d.endDate ,
                   d.created_at ,
                   wg_status.item status ,
                   CASE WHEN p.item = '1' THEN 'Requerido' ELSE 'Opcional' END isRequired ,
                   CASE WHEN d.isApprove IS NOT NULL THEN 'Aprobado' WHEN d.isDenied IS NOT NULL THEN 'Denegado' ELSE '' END isVerified ,
                   observation
FROM wg_customer_employee_document_critical d
INNER JOIN wg_customer_employee ce ON ce.id = d.customer_employee_id
LEFT JOIN
  (SELECT *
   FROM wg_customer_parameter
   WHERE namespace = 'wgroup'
     AND `group` = 'employeeDocumentType'
     AND customer_id = :customer_id
   UNION ALL SELECT `value`,
                    $customerId customer_id,
                    namespace,
                    `group`,
                    `value`,
                    item,
                    '' `data`,
                       NOW()
   FROM system_parameters
   WHERE namespace = 'wgroup'
     AND `group` = 'wg_employee_attachment') p ON d.requirement = p.id
AND p.customer_id = ce.customer_id
LEFT JOIN
  (SELECT *
   FROM system_parameters sp
   WHERE sp.`group` = 'customer_document_status') wg_status ON d.status COLLATE utf8_general_ci = wg_status.value
LEFT JOIN
  (SELECT customer_employee_document_id,
          GROUP_CONCAT(observation SEPARATOR '<br>') observation
   FROM wg_customer_employee_document_critical_tracking
   GROUP BY customer_employee_document_id) t ON d.id = t.customer_employee_document_id
WHERE (d.customer_employee_id = :customer_employee_id)) p";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (p.requirement like '%$search%' or p.description like '%$search%')";
            $query.=$where;
        }

        $order = " Order by p.created_at DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            'customer_employee_id' => $customerEmployeeId,
            'customer_id' => $customerId
        ));

        return $results;

    }

    public function getAllByRequired($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select p.`value` type
	, p.item isRequired
	, case when d.quantity is null then 0 else 1 end isApproved
	, case when d.quantity is null then 0 else d.quantity end quantity
from
wg_customer_employee ce
left join (
		select count(*) quantity, requirement, customer_employee_id  from
				wg_customer_employee_document_critical
		GROUP BY requirement, customer_employee_id ) d on d.customer_employee_id = ce.id
inner join
(SELECT *
   FROM wg_customer_parameter
   WHERE namespace = 'wgroup'
     AND `group` = 'employeeDocumentType'
   UNION ALL SELECT `value` id,
                    '' customer_id,
                    namespace,
                    `group`,
                    0 as item,
                    item `value`,
                    '' `data`,
                       NOW()
   FROM system_parameters
   WHERE namespace = 'wgroup'
     AND `group` = 'wg_employee_attachment') p on d.requirement = p.id
where ce.id = :customer_employee_id";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (p.`value` like '%$search%')";
            $query.=$where;
        }

        $order = " Order by p.id DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            'customer_employee_id' => $customerEmployeeId
        ));

        return $results;

    }

    public function getAllByRequiredCount($search, $customerEmployeeId = 0)
    {

        $query = "select p.`value` type
	, p.item isRequired
	, case when d.quantity is null then 0 else 1 end isApproved
	, case when d.quantity is null then 0 else d.quantity end quantity
from
wg_customer_employee ce
left join (
		select count(*) quantity, requirement, customer_employee_id  from
				wg_customer_employee_document_critical
		GROUP BY requirement, customer_employee_id ) d on d.customer_employee_id = ce.id
inner join
(SELECT *
   FROM wg_customer_parameter
   WHERE namespace = 'wgroup'
     AND `group` = 'employeeDocumentType'
   UNION ALL SELECT `value` id,
                    '' customer_id,
                    namespace,
                    `group`,
                    0 as item,
                    item `value`,
                    '' `data`,
                       NOW()
   FROM system_parameters
   WHERE namespace = 'wgroup'
     AND `group` = 'wg_employee_attachment') p on d.requirement = p.id
where ce.id = :customer_employee_id";



        if ($search != "") {
            $where = " AND (p.`value` like '%$search%')";
            $query.=$where;
        }

        $order = " Order by p.id DESC ";

        $query.=$order;

        $results = DB::select( $query, array(
            'customer_employee_id' => $customerEmployeeId
        ));

        return  count($results);

    }


    public function getAllByRequiredValidate($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select p.`value` type
	, p.item isRequired
	, case when d.quantity is null then 0 else 1 end isApproved
	, case when d.quantity is null then 0 else d.quantity end quantity
from
wg_customer_employee ce
inner join wg_employee we on ce.employee_id = we.id
inner join wg_customers c on ce.customer_id = c.id
inner join (select * from wg_customer_parameter where wg_customer_parameter.`group` = 'employeeDocumentType') p on p.customer_id = ce.customer_id
left join (select count(*) quantity, requirement, customer_employee_id  from
wg_customer_employee_document_critical
GROUP BY requirement, customer_employee_id ) d on d.customer_employee_id = ce.id and d.requirement = p.id
where we.documentNumber = :customer_employee_id";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (p.`value` like '%$search%')";
            $query.=$where;
        }

        $order = " Order by p.id DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            'customer_employee_id' => $customerEmployeeId
        ));

        return $results;

    }

    public function getAllByRequiredExport($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "select * from (
select
	c.businessName Cliente,
	we.fullName Empleado,
	we.documentNumber `Identificacion`
	,p.`value` `Tipo Documento Soporte`
	, case when p.item = 1 then 'SI' else 'NO' end `Obligatorio`
	, case when d.quantity is null then 'NO' else 'SI' end `Diligenciado`
	, case when d.quantity is null then 0 else d.quantity end `Nro Anexos`
	, wg_contract_type.item `Tipo Contrato`
from
wg_customer_employee ce
inner join wg_employee we on ce.employee_id = we.id
inner join wg_customers c on ce.customer_id = c.id
left join (SELECT * FROM system_parameters sp where namespace = 'wgroup' and sp.group = 'employee_contract_type') wg_contract_type on ce.contractType = wg_contract_type.value COLLATE utf8_general_ci
inner join (select * from wg_customer_parameter where wg_customer_parameter.`group` = 'employeeDocumentType') p on p.customer_id = ce.customer_id
left join (select count(*) quantity, requirement, customer_employee_id  from
wg_customer_employee_document_critical
GROUP BY requirement, customer_employee_id ) d on d.customer_employee_id = ce.id and d.requirement = p.id
order by c.businessName, we.fullName, p.`value` ) p
where p.`Identificacion` = :document";

        $results = DB::select( $query, array(
            'document' => $customerEmployeeId
        ));

        return $results;

    }

    public function getCount($search = "", $customerEmployeeId, $customerId) {

        $query = "SELECT * FROM (
SELECT DISTINCT d.id,
                d.customer_employee_id,
                p.`value` requirement,
                d.description,
                d.version,
                '' agent ,
                   d.startDate ,
                   d.endDate ,
                   d.created_at ,
                   wg_status.item status ,
                   CASE WHEN p.item = '1' THEN 'Requerido' ELSE 'Opcional' END isRequired ,
                   CASE WHEN d.isApprove IS NOT NULL THEN 'Aprobado' WHEN d.isDenied IS NOT NULL THEN 'Denegado' ELSE '' END isVerified ,
                   observation
FROM wg_customer_employee_document_critical d
INNER JOIN wg_customer_employee ce ON ce.id = d.customer_employee_id
LEFT JOIN
  (SELECT *
   FROM wg_customer_parameter
   WHERE namespace = 'wgroup'
     AND `group` = 'employeeDocumentType'
     AND customer_id = :customer_id
   UNION ALL SELECT `value`,
                    $customerId customer_id,
                    namespace,
                    `group`,
                    `value`,
                    item,
                    '' `data`,
                       NOW()
   FROM system_parameters
   WHERE namespace = 'wgroup'
     AND `group` = 'wg_employee_attachment') p ON d.requirement = p.id
AND p.customer_id = ce.customer_id
LEFT JOIN
  (SELECT *
   FROM system_parameters sp
   WHERE sp.`group` = 'customer_document_status') wg_status ON d.status COLLATE utf8_general_ci = wg_status.value
LEFT JOIN
  (SELECT customer_employee_document_id,
          GROUP_CONCAT(observation SEPARATOR '<br>') observation
   FROM wg_customer_employee_document_critical_tracking
   GROUP BY customer_employee_document_id) t ON d.id = t.customer_employee_document_id
WHERE (d.customer_employee_id = :customer_employee_id)) p";

        if ($search != "") {
            $where = " AND (p.requirement like '%$search%' or p.description like '%$search%')";
            $query.=$where;
        }

        $results = DB::select( $query, array(
            'customer_employee_id' => $customerEmployeeId,
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllByExpiration($search, $perPage = 10, $currentPage = 0, $year = 0, $month = 0, $customerEmployeeId = 0, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
select DISTINCT d.id, d.customer_employee_id, p.`value` requirement, d.description, d.version, '' agent
            , d.startDate
            , d.endDate
            , d.created_at
            , wg_status.item status
						, case when p.item = '1' then 'Requerido' else 'Opcional' end isRequired
						, '' isVerified
						, GROUP_CONCAT(t.observation SEPARATOR '<br>') observation
from wg_customer_employee_document_critical d
inner join wg_customer_employee ce on ce.id = d.customer_employee_id
left join (select * from wg_customer_parameter where wg_customer_parameter.`group` = 'employeeDocumentType') p on d.requirement = p.id and p.customer_id = ce.customer_id
left join (select * from system_parameters sp where sp.`group` = 'customer_document_status') wg_status on d.status COLLATE utf8_general_ci = wg_status.value
left join wg_customer_employee_document_critical_tracking t on d.id = t.customer_employee_document_id
where (d.customer_employee_id = $customerEmployeeId)
group by t.customer_employee_document_id ) p ";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.startDate DESC";

        $whereArray = array();
        $where = '';

        if ($month != 0) {
            $where .= " WHERE MONTH(p.endDate) = :month";
            $whereArray["month"] = $month;
        }

        if ($year != 0) {
            if (empty($where)) {
                $where .= " WHERE YEAR(p.endDate) = :year";
            } else {
                $where .= " AND YEAR(p.endDate) = :year";
            }
            $whereArray["year"] = $year;
        }


        $sql = $query.$where;
        $sql.=$orderBy.$limit;

        //Log::info($year);

        $results = DB::select( $sql, $whereArray);

        return $results;

    }

    public function getAllByExpirationCount($search, $perPage = 10, $currentPage = 0, $year = 0, $month = 0, $customerEmployeeId = 0, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
select DISTINCT d.id, d.customer_employee_id, p.`value` requirement, d.description, d.version, '' agent
            , d.startDate
            , d.endDate
            , d.created_at
            , wg_status.item status
						, case when p.item = '1' then 'Requerido' else 'Opcional' end isRequired
from wg_customer_employee_document_critical d
inner join wg_customer_employee ce on ce.id = d.customer_employee_id
left join (select * from wg_customer_parameter where wg_customer_parameter.`group` = 'employeeDocumentType') p on d.requirement = p.id and p.customer_id = ce.customer_id
left join (select * from system_parameters sp where sp.`group` = 'customer_document_status') wg_status on d.status COLLATE utf8_general_ci = wg_status.value
where (d.customer_employee_id = $customerEmployeeId) ) p ";

        $limit = "";
        $orderBy = " ORDER BY p.startDate DESC";

        $whereArray = array();
        $where = '';

        if ($month != 0) {
            $where .= " WHERE MONTH(p.endDate) = :month";
            $whereArray["month"] = $month;
        }

        if ($year != 0) {
            if (empty($where)) {
                $where .= " WHERE YEAR(p.endDate) = :year";
            } else {
                $where .= " AND YEAR(p.endDate) = :year";
            }
            $whereArray["year"] = $year;
        }

        $sql = $query.$where;
        $sql.=$orderBy.$limit;

        //Log::info($year);

        $results = DB::select( $sql, $whereArray);

        return $results;
    }

    public function getAllBySearchExpiration($search, $perPage = 10, $currentPage = 0, $year = 0, $month = 0, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
select DISTINCT d.id, d.customer_employee_id, p.`value` requirement, d.description, d.version, '' agent
            , d.startDate
            , d.endDate
            , d.created_at
            , wg_status.item status
						, case when p.item = '1' then 'Requerido' else 'Opcional' end isRequired
						, '' isVerified
						, e.fullName
						, e.documentNumber
						, wg_employee_document_type.item documentType

from wg_customer_employee_document_critical d
inner join wg_customer_employee ce on ce.id = d.customer_employee_id
inner join wg_employee e on e.id = ce.employee_id
left join (select * from wg_customer_parameter where wg_customer_parameter.`group` = 'employeeDocumentType') p on d.requirement = p.id and p.customer_id = ce.customer_id
left join (select * from system_parameters sp where sp.`group` = 'customer_document_status') wg_status on d.status COLLATE utf8_general_ci = wg_status.value
left join (select * from system_parameters sp where sp.`group` = 'employee_document_type') wg_employee_document_type on e.documentType COLLATE utf8_general_ci = wg_employee_document_type.value
where (ce.customer_id = $customerId) and d.status = 1 ) p ";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.startDate DESC";

        $whereArray = array();
        $where = '';

        if ($month != 0) {
            $where .= " WHERE MONTH(p.endDate) = :month";
            $whereArray["month"] = $month;
        }

        if ($year != 0) {
            if (empty($where)) {
                $where .= " WHERE YEAR(p.endDate) = :year";
            } else {
                $where .= " AND YEAR(p.endDate) = :year";
            }
            $whereArray["year"] = $year;
        }


        $sql = $query.$where;
        $sql.=$orderBy.$limit;

        //Log::info($year);

        $results = DB::select( $sql, $whereArray);

        return $results;

    }

    public function getAllBySearchExpirationCount($search, $perPage = 10, $currentPage = 0, $year = 0, $month = 0, $customerId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
select DISTINCT d.id, d.customer_employee_id, p.`value` requirement, d.description, d.version, '' agent
            , d.startDate
            , d.endDate
            , d.created_at
            , wg_status.item status
						, case when p.item = '1' then 'Requerido' else 'Opcional' end isRequired
						, '' isVerified
						, e.fullName
						, e.documentNumber
						, wg_employee_document_type.item documentType
from wg_customer_employee_document_critical d
inner join wg_customer_employee ce on ce.id = d.customer_employee_id
inner join wg_employee e on e.id = ce.employee_id
left join (select * from wg_customer_parameter where wg_customer_parameter.`group` = 'employeeDocumentType') p on d.requirement = p.id and p.customer_id = ce.customer_id
left join (select * from system_parameters sp where sp.`group` = 'customer_document_status') wg_status on d.status COLLATE utf8_general_ci = wg_status.value
left join (select * from system_parameters sp where sp.`group` = 'employee_document_type') wg_employee_document_type on e.documentType COLLATE utf8_general_ci = wg_employee_document_type.value
where (ce.customer_id = $customerId) and d.status = 1 ) p ";
        $limit = "";
        $orderBy = " ORDER BY p.startDate DESC";

        $whereArray = array();
        $where = '';

        if ($month != 0) {
            $where .= " WHERE MONTH(p.endDate) = :month";
            $whereArray["month"] = $month;
        }

        if ($year != 0) {
            if (empty($where)) {
                $where .= " WHERE YEAR(p.endDate) = :year";
            } else {
                $where .= " AND YEAR(p.endDate) = :year";
            }
            $whereArray["year"] = $year;
        }

        $sql = $query.$where;
        $sql.=$orderBy.$limit;

        //Log::info($year);

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
