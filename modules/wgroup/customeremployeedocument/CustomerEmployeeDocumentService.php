<?php

namespace Wgroup\CustomerEmployeeDocument;

use DB;
use Exception;
use Log;
use Str;

class CustomerEmployeeDocumentService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerEmployeeDocumentRepository;

    function __construct()
    {
        // $this->customerRepository = new CustomerReporistory();
    }

    public function init()
    {

    }

    /**
     * @param $search
     * @param int $perPage
     * @param int $currentPage
     * @param array $sorting
     * @param string $typeFilter
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0)
    {

        $model = new CustomerEmployeeDocument();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerEmployeeDocumentRepository = new CustomerEmployeeDocumentRepository($model);

        if ($perPage > 0) {
            $this->customerEmployeeDocumentRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_employee_document.id',
            'wg_customer_employee_document.requirement',
            'wg_customer_employee_document.description',
            'wg_customer_employee_document.version',
            'wg_customer_employee_document.status'
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
            $this->customerEmployeeDocumentRepository->sortBy('wg_customer_employee_document.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_employee_document.customer_employee_id', $customerEmployeeId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_employee_document.requirement', $search);
            $filters[] = array('wg_customer_employee_document.description', $search);
            $filters[] = array('wg_customer_employee_document.version', $search);
            $filters[] = array('wg_customer_employee_document.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_employee_document.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_employee_document.status', '0');
        }


        $this->customerEmployeeDocumentRepository->setColumns(['wg_customer_employee_document.*']);

        return $this->customerEmployeeDocumentRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllBySearch($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0, $customerId = 0, $hideCanceled = true)
    {

        $startFrom = ($currentPage - 1) * $perPage;

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
                   CASE WHEN d.isApprove IS NOT NULL THEN '' WHEN d.isDenied IS NOT NULL THEN observation ELSE '' END observation
FROM wg_customer_employee_document d
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
   FROM wg_customer_employee_document_tracking
   GROUP BY customer_employee_document_id) t ON d.id = t.customer_employee_document_id
WHERE (d.customer_employee_id = :customer_employee_id)) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.requirement like '%$search%' or p.description like '%$search%')";

        }

        if ($hideCanceled) {
            $where .= $where == '' ? " WHERE (p.status <> 'Anulado')" : " AND (p.status <> 'Anulado')";
        }

        $query .= $where;

        $order = " Order by p.created_at DESC ";

        $query .= $order . $limit;

        $results = DB::select($query, array(
            'customer_employee_id' => $customerEmployeeId,
            'customer_id' => $customerId
        ));

        return $results;

    }


    public function getAllByCriticalRequired($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0, $customerId = 0)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "select DISTINCT * from (
select distinct d.* from wg_customer_employee_critical_activity eca
inner join wg_customer_employee ce on eca.customer_employee_id = ce.id and eca.job_id = ce.job
inner join wg_customer_config_job_activity ja on ja.id = eca.job_activity_id
inner join wg_customer_config_job_activity_document jad on jad.job_activity_id = ja.activity_id
inner join ( SELECT *
   FROM wg_customer_parameter
   WHERE namespace = 'wgroup'
     AND `group` = 'employeeDocumentType'
     AND customer_id = $customerId
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
     AND `group` = 'wg_employee_attachment' ) d on jad.type COLLATE utf8_general_ci  = d.id
where eca.customer_employee_id = :customer_employee_id_1 and d.id not in(select requirement from wg_customer_employee_document where customer_employee_id = :customer_employee_id_2)
union all
select * from(
 SELECT *
   FROM wg_customer_parameter
   WHERE namespace = 'wgroup'
     AND `group` = 'employeeDocumentType'
     AND customer_id = $customerId
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
     AND `group` = 'wg_employee_attachment') p
where p.item = 1 and p.id not in(select requirement from wg_customer_employee_document where customer_employee_id = :customer_employee_id_3) ) p";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " WHERE (p.value like '%$search%' or p.item like '%$search%')";
            $query .= $where;
        }

        $order = " Order by p.value ASC";

        $query .= $order . $limit;

        $results = DB::select($query, array(
            ':customer_employee_id_1' => $customerEmployeeId,
            ':customer_employee_id_2' => $customerEmployeeId,
            ':customer_employee_id_3' => $customerEmployeeId
        ));

        return $results;

    }

    public function getAllByCriticalRequiredCount($search, $customerEmployeeId = 0, $customerId = 0)
    {

        $query = "select DISTINCT * from (
select distinct d.* from wg_customer_employee_critical_activity eca
inner join wg_customer_employee ce on eca.customer_employee_id = ce.id and eca.job_id = ce.job
inner join wg_customer_config_job_activity ja on ja.id = eca.job_activity_id
inner join wg_customer_config_job_activity_document jad on jad.job_activity_id = ja.activity_id
inner join ( SELECT *
   FROM wg_customer_parameter
   WHERE namespace = 'wgroup'
     AND `group` = 'employeeDocumentType'
     AND customer_id = $customerId
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
     AND `group` = 'wg_employee_attachment' ) d on jad.type COLLATE utf8_general_ci  = d.id
where eca.customer_employee_id = :customer_employee_id_1 and d.id not in(select requirement from wg_customer_employee_document where customer_employee_id = :customer_employee_id_2)
union all
select * from(
 SELECT *
   FROM wg_customer_parameter
   WHERE namespace = 'wgroup'
     AND `group` = 'employeeDocumentType'
     AND customer_id = $customerId
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
     AND `group` = 'wg_employee_attachment') p
where p.item = 1 and p.id not in(select requirement from wg_customer_employee_document where customer_employee_id = :customer_employee_id_3) ) p";

        if ($search != "") {
            $where = " WHERE (p.value like '%$search%' or p.item like '%$search%')";
            $query .= $where;
        }

        $order = " Order by p.value ASC";

        $query .= $order;

        $results = DB::select($query, array(
            ':customer_employee_id_1' => $customerEmployeeId,
            ':customer_employee_id_2' => $customerEmployeeId,
            ':customer_employee_id_3' => $customerEmployeeId
        ));

        return $results;

    }

    public function getAllByRequired($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "select p.`value` type
	, p.item isRequired
	, case when d.quantity is null then 0 else 1 end isApproved
	, case when d.quantity is null then 0 else d.quantity end quantity
from
wg_customer_employee ce
left join (
		select count(*) quantity, requirement, customer_employee_id  from
				wg_customer_employee_document
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
     AND `group` = 'wg_employee_attachment') p on d.requirement COLLATE utf8_general_ci = p.id
where ce.id = :customer_employee_id";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (p.`value` like '%$search%')";
            $query .= $where;
        }

        $order = " Order by p.id DESC ";

        $query .= $order . $limit;

        $results = DB::select($query, array(
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
				wg_customer_employee_document
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
     AND `group` = 'wg_employee_attachment') p on d.requirement COLLATE utf8_general_ci = p.id
where ce.id = :customer_employee_id";


        if ($search != "") {
            $where = " AND (p.`value` like '%$search%')";
            $query .= $where;
        }

        $order = " Order by p.id DESC ";

        $query .= $order;

        $results = DB::select($query, array(
            'customer_employee_id' => $customerEmployeeId
        ));

        return count($results);

    }


    public function getAllByRequiredValidate($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0)
    {

        $startFrom = ($currentPage - 1) * $perPage;

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
wg_customer_employee_document
GROUP BY requirement, customer_employee_id ) d on d.customer_employee_id = ce.id and d.requirement = p.id
where we.documentNumber = :customer_employee_id";

        $limit = " LIMIT $startFrom , $perPage";

        if ($search != "") {
            $where = " AND (p.`value` like '%$search%')";
            $query .= $where;
        }

        $order = " Order by p.id DESC ";

        $query .= $order . $limit;

        $results = DB::select($query, array(
            'customer_employee_id' => $customerEmployeeId
        ));

        return $results;

    }

    public function getAllByRequiredExport($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerEmployeeId = 0)
    {

        $startFrom = ($currentPage - 1) * $perPage;

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
wg_customer_employee_document
GROUP BY requirement, customer_employee_id ) d on d.customer_employee_id = ce.id and d.requirement = p.id
order by c.businessName, we.fullName, p.`value` ) p
where p.`Identificacion` = :document";

        $results = DB::select($query, array(
            'document' => $customerEmployeeId
        ));

        return $results;

    }

    public function getCount($search = "", $customerEmployeeId, $customerId, $hideCanceled = true)
    {

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
FROM wg_customer_employee_document d
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
   FROM wg_customer_employee_document_tracking
   GROUP BY customer_employee_document_id) t ON d.id = t.customer_employee_document_id
WHERE (d.customer_employee_id = :customer_employee_id)) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.requirement like '%$search%' or p.description like '%$search%')";
        }

        if ($hideCanceled) {
            $where .= $where == '' ? " WHERE (p.status <> 'Anulado')" : " AND (p.status <> 'Anulado')";
        }

        $query .= $where;

        $results = DB::select($query, array(
            'customer_employee_id' => $customerEmployeeId,
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllByExpiration($search, $perPage = 10, $currentPage = 0, $year = 0, $month = 0, $customerEmployeeId = 0, $customerId = 0)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT *
FROM
  (SELECT DISTINCT d.id,
                   d.customer_employee_id,
                   p.`value` requirement,
                   d.description,
                   d.version,
                   '' agent,
										d.startDate,
										d.endDate,
										d.created_at,
										wg_status.item status,
										CASE
												WHEN p.item = '1' THEN 'Requerido'
												ELSE 'Opcional'
										END isRequired,
										CASE
												WHEN d.isApprove IS NOT NULL THEN 'Aprobado'
												WHEN d.isDenied IS NOT NULL THEN 'Denegado'
												ELSE ''
										END isVerified,
										CASE
												WHEN d.isApprove IS NOT NULL THEN ''
												WHEN d.isDenied IS NOT NULL THEN observation
												ELSE ''
										END observation
   FROM wg_customer_employee_document d
   INNER JOIN wg_customer_employee ce ON ce.id = d.customer_employee_id
   LEFT JOIN
     (SELECT *
      FROM wg_customer_parameter
      WHERE namespace = 'wgroup'
        AND `group` = 'employeeDocumentType'
        AND customer_id = $customerId
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
      FROM wg_customer_employee_document_tracking
      GROUP BY customer_employee_document_id) t ON d.id = t.customer_employee_document_id
   WHERE (d.customer_employee_id = $customerEmployeeId) AND d.status = 1
	) p ";

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

        if ($search != null || !empty($search)) {
            if (empty($where)) {
                $where .= " WHERE (requirement LIKE :requirement OR  description LIKE :description OR startDate LIKE :startDate OR endDate LIKE :endDate OR version LIKE :version OR status = :status)";
            } else {
                $where .= " AND (requirement LIKE :requirement OR  description LIKE :description OR startDate LIKE :startDate OR endDate LIKE :endDate OR version LIKE :version OR status = :status)";
            }
            $whereArray["requirement"] = $search;
            $whereArray["description"] = $search;
            $whereArray["startDate"] = $search;
            $whereArray["endDate"] = $search;
            $whereArray["version"] = $search;
            $whereArray["status"] = $search;
        }

        $sql = $query . $where;
        $sql .= $orderBy . $limit;

        //Log::info($year);

        $results = DB::select($sql, $whereArray);

        return $results;

    }

    public function getAllByExpirationCount($year = 0, $month = 0, $customerEmployeeId = 0, $customerId = 0)
    {


        $query = "SELECT *
FROM
  (SELECT DISTINCT d.id,
                   d.customer_employee_id,
                   p.`value` requirement,
                   d.description,
                   d.version,
                   '' agent,
										d.startDate,
										d.endDate,
										d.created_at,
										wg_status.item status,
										CASE
												WHEN p.item = '1' THEN 'Requerido'
												ELSE 'Opcional'
										END isRequired,
										CASE
												WHEN d.isApprove IS NOT NULL THEN 'Aprobado'
												WHEN d.isDenied IS NOT NULL THEN 'Denegado'
												ELSE ''
										END isVerified,
										CASE
												WHEN d.isApprove IS NOT NULL THEN ''
												WHEN d.isDenied IS NOT NULL THEN observation
												ELSE ''
										END observation
   FROM wg_customer_employee_document d
   INNER JOIN wg_customer_employee ce ON ce.id = d.customer_employee_id
   LEFT JOIN
     (SELECT *
      FROM wg_customer_parameter
      WHERE namespace = 'wgroup'
        AND `group` = 'employeeDocumentType'
        AND customer_id = $customerId
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
      FROM wg_customer_employee_document_tracking
      GROUP BY customer_employee_document_id) t ON d.id = t.customer_employee_document_id
   WHERE (d.customer_employee_id = $customerEmployeeId)  AND d.status = 1
	) p ";

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

        $sql = $query . $where;
        $sql .= $orderBy . $limit;


        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getAllBySearchExpiration($search, $perPage = 10, $currentPage = 0, $customerId = 0, $audit = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT *
FROM
  (
    SELECT
	`wg_customer_employee_document`.`id`,
	`employee_document_type`.`item` AS `documentType`,
	`wg_employee`.`documentNumber`,
	`wg_employee`.`fullName`,
	`document_type`.`item` AS `requirement`,
	`wg_customer_employee_document`.`description`,
	`wg_customer_employee_document`.`startDate`,
	`wg_customer_employee_document`.`endDate`,
	`wg_customer_employee_document`.`version`,
	CASE
WHEN document_type.isRequired = '1' THEN
	'Requerido'
ELSE
	'Opcional'
END AS isRequired,
 `customer_document_status`.`item` AS `status`,
 CASE
WHEN wg_customer_employee_document.isApprove IS NOT NULL THEN
	'Aprobado'
WHEN wg_customer_employee_document.isDenied IS NOT NULL THEN
	'Denegado'
ELSE
	''
END AS isVerified,
 `wg_customer_employee_document`.`created_at`,
 `users`.`name` AS `createdBy`,
 `wg_customer_employee_document`.`status` AS `statusCode`,
 `document_type`.`isRequired` AS `isRequiredCode`,
 `wg_customer_employee`.`customer_id`,
 YEAR (`endDate`) AS YEAR,
 MONTH (`endDate`) AS MONTH
FROM
	`wg_customer_employee_document`
INNER JOIN `wg_customer_employee` ON `wg_customer_employee_document`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
LEFT JOIN (
	SELECT
		`id`,
		`namespace`,
		`group`,
		`item`,
		`value` COLLATE utf8_general_ci AS `value`,
		`code`
	FROM
		`system_parameters`
	WHERE
		`namespace` = 'wgroup'
	AND `group` = 'employee_document_type'
) employee_document_type ON `wg_employee`.`documentType` = `employee_document_type`.`value`
LEFT JOIN (
	SELECT
		id,
		NULL customer_id,
		item,
		`value` COLLATE utf8_general_ci AS `value`,
		0 isRequired,
		'System' origin
	FROM
		system_parameters
	WHERE
		namespace = 'wgroup'
	AND `group` = 'wg_employee_attachment'
	UNION ALL
		SELECT
			id,
			customer_id,
			`value` item,
			id `value`,
			item isRequired,
			'Customer' origin
		FROM
			wg_customer_parameter
		WHERE
			namespace = 'wgroup'
		AND `group` = 'employeeDocumentType'
) document_type ON `wg_customer_employee_document`.`requirement` = `document_type`.`value`
AND `wg_customer_employee_document`.`origin` = `document_type`.`origin`
LEFT JOIN (
	SELECT
		`id`,
		`namespace`,
		`group`,
		`item`,
		`value` COLLATE utf8_general_ci AS `value`,
		`code`
	FROM
		`system_parameters`
	WHERE
		`namespace` = 'wgroup'
	AND `group` = 'customer_document_status'
) customer_document_status ON `wg_customer_employee_document`.`status` = `customer_document_status`.`value`
LEFT JOIN `users` ON `wg_customer_employee_document`.`createdBy` = `users`.`id`
WHERE
	(
		`wg_customer_employee`.`customer_id` = `document_type`.`customer_id`
		OR `document_type`.`customer_id` IS NULL
	)
AND `wg_customer_employee`.`customer_id` = $customerId
AND `wg_customer_employee_document`.`status` = '1'
  ) p ";

        $limit = " LIMIT $startFrom , $perPage";

        if ($perPage == 0 && $currentPage == 0) {
            $limit = "";
        }

        $orderBy = " ORDER BY p.startDate DESC";

        $where = '';

        if ($audit != null) {
            $where = $this->getWhere($audit->filters);
        } else if ($search != '') {
            $where = " WHERE (p.documentNumber like '%$search%' or p.fullName like '%$search%' or p.status like '%$search%'
                            or p.isRequired like '%$search%' or p.requirement like '%$search%' or p.description like '%$search%' or p.version like '%$search%')";
        }

        $sql = $query . $where . $orderBy;
        $sql .= $limit;


        //var_dump($sql);

        $results = DB::select($sql);

        return $results;

    }

    public function getAllBySearchExpirationCount($search, $customerId = 0, $audit = null)
    {
        $query = "SELECT *
FROM
  (SELECT DISTINCT d.id,
                   d.customer_employee_id,
                   p.`value` requirement,
                   d.description,
                   d.version,
                   '' agent,
										d.startDate,
										d.endDate,
										YEAR(d.endDate) `year`,
										MONTH(d.endDate) `month`,
										d.created_at,
										wg_status.item status,
										CASE
												WHEN p.item = '1' THEN 'Requerido'
												ELSE 'Opcional'
										END isRequired,
										CASE
												WHEN d.isApprove IS NOT NULL THEN 'Aprobado'
												WHEN d.isDenied IS NOT NULL THEN 'Denegado'
												ELSE ''
										END isVerified,
										e.fullName,
										e.firstName,
										e.lastName,
										e.documentNumber,
										wg_employee_document_type.item documentType
   FROM wg_customer_employee_document d
   INNER JOIN wg_customer_employee ce ON ce.id = d.customer_employee_id
		INNER JOIN wg_employee e on e.id = ce.employee_id
   LEFT JOIN
     (SELECT *
      FROM wg_customer_parameter
      WHERE namespace = 'wgroup'
        AND `group` = 'employeeDocumentType'
        AND customer_id = $customerId
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
	 LEFT JOIN (select * from system_parameters sp where sp.`group` = 'employee_document_type') wg_employee_document_type
			on e.documentType COLLATE utf8_general_ci = wg_employee_document_type.value
   WHERE (ce.customer_id = $customerId) AND d.status = 1 AND ce.isActive = 1
	) p ";

        $orderBy = " ORDER BY p.startDate DESC";

        $where = '';

        if ($audit != null) {
            $where = $this->getWhere($audit->filters);
        } else if ($search != '') {
            $where = " WHERE (p.documentNumber like '%$search%' or p.fullName like '%$search%' or p.status like '%$search%'
                            or p.isRequired like '%$search%' or p.requirement like '%$search%' or p.description like '%$search%' or p.version like '%$search%')";
        }

        $sql = $query . $where . $orderBy;

        $results = DB::select($sql);

        return $results;
    }

    private function getWhere($filters)
    {
        //Log::info("where");

        $where = "";
        $lastFilter = null;
        foreach ($filters as $filter) {

            //Log::info("foreach");

            if ($lastFilter == null) {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            } else {

                switch ($filter->criteria->value) {
                    case "=":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " = '" . $filter->value . "' ";
                        break;

                    case "LIKE":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " LIKE '%" . $filter->value . "%' ";
                        break;

                    case "<>":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " <> '" . $filter->value . "' ";
                        break;

                    case "<":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " < '" . $filter->value . "' ";
                        break;

                    case ">":
                        $where .= $lastFilter->condition->value . " " . "p." . $filter->field->name . " > '" . $filter->value . "' ";
                        break;

                    default:

                }

                $lastFilter = $filter;
            }

        }

        //Log::info($where);
        //Log::info(count($filters));

        return $where == "" ? "" : " WHERE " . $where;
    }
}
