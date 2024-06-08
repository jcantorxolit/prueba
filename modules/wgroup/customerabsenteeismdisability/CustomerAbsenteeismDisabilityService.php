<?php

namespace Wgroup\CustomerAbsenteeismDisability;

use DB;
use Exception;
use Log;
use Str;

class CustomerAbsenteeismDisabilityService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerAbsenteeismDisabilityRepository;

    function __construct()
    {
        // $this->customerRepository = new CustomerReporistory();
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId = 0)
    {

        $model = new CustomerAbsenteeismDisability();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerAbsenteeismDisabilityRepository = new CustomerAbsenteeismDisabilityRepository($model);

        if ($perPage > 0) {
            $this->customerAbsenteeismDisabilityRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_absenteeism_disability.id',
            'wg_customer_absenteeism_disability.type',
            'wg_customer_absenteeism_disability.cause',
            'wg_employee.firstName',
            'wg_employee.lastName',
            'wg_customer_absenteeism_disability.start',
            'wg_customer_absenteeism_disability.end'
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
                    $this->customerAbsenteeismDisabilityRepository->sortBy($colName, $dir);
                } else {
                    $this->customerAbsenteeismDisabilityRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerAbsenteeismDisabilityRepository->sortBy('wg_customer_absenteeism_disability.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_employee.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_absenteeism_disability.type', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_customer_absenteeism_disability.start', $search);
            $filters[] = array('wg_customer_absenteeism_disability.end', $search);
            $filters[] = array('dtype.item', $search);
            $filters[] = array('ctype.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_absenteeism_disability.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_absenteeism_disability.status', '0');
        }

        $this->customerAbsenteeismDisabilityRepository->setColumns(['wg_customer_absenteeism_disability.*']);

        return $this->customerAbsenteeismDisabilityRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllByEmployee($perPage = 10, $currentPage = 0, $customerId, $cause = "")
    {
        $startFrom = ($currentPage - 1) * $perPage;

        $query = "select count(*) quantity, p.item cause, DATE_FORMAT(`start`,'%Y%m') period, ct.item contractType
from wg_customer_absenteeism_disability cd
inner join wg_customer_employee ce on ce.id = cd.customer_employee_id
inner join wg_customers c on c.id = ce.customer_id
left join (select * from system_parameters where `group` = 'absenteeism_disability_causes') p on cd.cause = p.value
left join (select * from system_parameters where `group` = 'absenteeism_disability_type') dt on cd.type COLLATE utf8_general_ci = dt.value
left join (select * from system_parameters where `group` = 'employee_contract_type') ct on ce.contractType COLLATE utf8_general_ci = ct.value";

        $whereArray = array();

        $where = " where ce.customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;

        $group = " group by p.item, DATE_FORMAT(`start`,'%Y%m'), ct.item";

        $limit = " LIMIT $startFrom , $perPage";

        if ($cause != "") {
            $where .= " AND p.value = :cause";
            $whereArray["cause"] = $cause;
        }

        $sql = $query . $where . $group . $limit;

        //Log::info($cause);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getSummaryDisability($perPage = 10, $currentPage = 0, $customerId, $cause = "", $year= "")
    {
        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM (
SELECT COUNT(*) quantity,
	IFNULL(CASE WHEN cd.category = 'Administrativo' THEN pa.item ELSE p.item END, 'SIN CAUSA')  cause,
	cd.cause AS causeCode,
	DATE_FORMAT(`start`,'%Y%m') period, ct.item contractType,
    YEAR(`start`) year,
    customer_id
from wg_customer_absenteeism_disability cd
inner join wg_customer_employee ce on ce.id = cd.customer_employee_id
inner join wg_customers c on c.id = ce.customer_id
left join (select * from system_parameters where `group` = 'absenteeism_disability_causes') p on cd.cause = p.value
left join (select * from system_parameters where `group` = 'absenteeism_disability_causes_admin') pa on cd.cause = pa.value
left join (select * from system_parameters where `group` = 'absenteeism_disability_type') dt on cd.type COLLATE utf8_general_ci = dt.value
left join (select * from system_parameters where `group` = 'employee_contract_type') ct on ce.contractType COLLATE utf8_general_ci = ct.value
GROUP BY p.item, DATE_FORMAT(`start`,'%Y%m'), ct.item
) p";

        $whereArray = array();

        $where = " where p.customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;

        $group = " ";

        $limit = " LIMIT $startFrom , $perPage";

        if ($cause != "") {
            $where .= " AND p.causeCode = :cause";
            $whereArray["cause"] = $cause;
        }

        if ($year != "") {
            $where .= " AND p.year = :year";
            $whereArray["year"] = $year;
        }

        $sql = $query . $where . $group . $limit;

        //Log::info($cause);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getSummaryDisabilityCount($customerId, $cause = "", $year= "")
    {
        $query = "SELECT * FROM (
SELECT COUNT(*) quantity,
	IFNULL(CASE WHEN cd.category = 'Administrativo' THEN pa.item ELSE p.item END, 'SIN CAUSA')  cause,
	cd.cause AS causeCode,
	DATE_FORMAT(`start`,'%Y%m') period, ct.item contractType,
    YEAR(`start`) year,
    customer_id
from wg_customer_absenteeism_disability cd
inner join wg_customer_employee ce on ce.id = cd.customer_employee_id
inner join wg_customers c on c.id = ce.customer_id
left join (select * from system_parameters where `group` = 'absenteeism_disability_causes') p on cd.cause = p.value
left join (select * from system_parameters where `group` = 'absenteeism_disability_causes_admin') pa on cd.cause = pa.value
left join (select * from system_parameters where `group` = 'absenteeism_disability_type') dt on cd.type COLLATE utf8_general_ci = dt.value
left join (select * from system_parameters where `group` = 'employee_contract_type') ct on ce.contractType COLLATE utf8_general_ci = ct.value
GROUP BY p.item, DATE_FORMAT(`start`,'%Y%m'), ct.item
) p";

        $whereArray = array();

        $where = " where p.customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;

        $group = " ";

        if ($cause != "") {
            $where .= " AND p.causeCode = :cause";
            $whereArray["cause"] = $cause;
        }

        if ($year != "") {
            $where .= " AND p.year = :year";
            $whereArray["year"] = $year;
        }

        $sql = $query . $where . $group;

        //Log::info($cause);

        $results = DB::select($sql, $whereArray);

        return count($results);
    }

    public function getSummaryDisabilityReport($customerId, $year, $cause = "")
    {
        $query = "Select
                    sum(case when MONTH(wgc.start) = 1 then wgc.amountPaid else 0 end) 'Enero',
                    sum(case when MONTH(wgc.start) = 2 then wgc.amountPaid else 0 end) 'Febrero',
                    sum(case when MONTH(wgc.start) = 3 then wgc.amountPaid else 0 end) 'Marzo',
                    sum(case when MONTH(wgc.start) = 4 then wgc.amountPaid else 0 end) 'Abril',
                    sum(case when MONTH(wgc.start) = 5 then wgc.amountPaid else 0 end) 'Mayo',
                    sum(case when MONTH(wgc.start) = 6 then wgc.amountPaid else 0 end) 'Junio',
                    sum(case when MONTH(wgc.start) = 7 then wgc.amountPaid else 0 end) 'Julio',
                    sum(case when MONTH(wgc.start) = 8 then wgc.amountPaid else 0 end) 'Agosto',
                    sum(case when MONTH(wgc.start) = 9 then wgc.amountPaid else 0 end) 'Septiembre',
                    sum(case when MONTH(wgc.start) = 10 then wgc.amountPaid else 0 end) 'Octubre',
                    sum(case when MONTH(wgc.start) = 11 then wgc.amountPaid else 0 end) 'Noviembre',
                    sum(case when MONTH(wgc.start) = 12 then wgc.amountPaid else 0 end) 'Diciembre'
            from wg_customer_absenteeism_disability wgc
            inner join wg_customer_employee ce on ce.id = wgc.customer_employee_id";

        $whereArray = array();

        $where = " WHERE YEAR(wgc.start) = :currentYear and ce.customer_id = :customer_id";

        $whereArray["customer_id"] = $customerId;
        $whereArray["currentYear"] = $year;

        if ($cause != "") {
            $where .= " AND cause = :cause";
            $whereArray["cause"] = $cause;
        }

        $sql = $query . $where;

        //Log::info($sql);

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getSummaryDisabilityReportYears($customerId)
    {
        $sql = "select distinct YEAR(wgc.start) id, YEAR(wgc.start) item, YEAR(wgc.start) value
                from wg_customer_absenteeism_disability wgc
                inner join wg_customer_employee ce on ce.id = wgc.customer_employee_id
                where ce.customer_id = :customer_id
                order by 1 desc";

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerAbsenteeismDisability();
        $this->customerAbsenteeismDisabilityRepository = new CustomerAbsenteeismDisabilityRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_employee.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_absenteeism_disability.type', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_customer_absenteeism_disability.start', $search);
            $filters[] = array('wg_customer_absenteeism_disability.end', $search);
            $filters[] = array('dtype.item', $search);
            $filters[] = array('ctype.item', $search);
        }

        $this->customerAbsenteeismDisabilityRepository->setColumns(['wg_customer_absenteeism_disability.*']);

        return $this->customerAbsenteeismDisabilityRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllByBilling($customerId = 0)
    {

        $query = "SELECT
	`wg_customer_absenteeism_disability`.id,
	`wg_customer_absenteeism_disability`.amountPaid,
	IFNULL(`wg_customer_absenteeism_disability`.charged,0) charged,
	wg_employee.fullName,
	`start`,
	`end`,
	category,
	`wg_customer_absenteeism_disability`.type,
	cause,
	'true' alive,
	numberDays
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
INNER JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'employee_contract_type'
) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id and (charged is null or charged = 0)
ORDER BY
	`wg_customer_absenteeism_disability`.`start` DESC";

        $results = DB::select($query, array(
            'customer_id' => $customerId
        ));

        foreach ($results as $record) {
            if (isset($record->charged)) {
                $record->charged = $record->charged == 1 ? true : false;
            }
        }

        return $results;

    }


    public function getAll($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
	`wg_customer_absenteeism_disability`.id,
	`wg_employee`.documentNumber,
	`wg_employee`.firstName,
	`wg_employee`.lastName,
	`employee_contract_type`.item contractType,
	`dtype`.item typeText,
	`wg_customer_absenteeism_disability`.category,
	`absenteeism_disability_causes`.item causeItem,
	`absenteeism_disability_causes`.value causeValue,
	DATE_FORMAT(`wg_customer_absenteeism_disability`.`start`,'%d/%m/%Y') startDateFormat,
	DATE_FORMAT(`wg_customer_absenteeism_disability`.`end`,'%d/%m/%Y') endDateFormat,
	`wg_customer_absenteeism_disability`.`start`,
	`wg_customer_absenteeism_disability`.`end`,
	IFNULL(`ap`.`id`,0)  planId,
	 case when dr.qty > 0 then 1 else 0 end hasReport,
	 case when ap.qty > 0 then 1 else 0 end hasActionPlan,
	 case when inc.qty > 0 then 1 else 0 end hasInhability,
	 case when inv.qty > 0 then 1 else 0 end hasInvestigation,
	 case when rep.qty > 0 then 1 else 0 end hasReportEps,
	 case when rem.qty > 0 then 1 else 0 end hasReportMin
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'REP' group by customer_disability_id) rep
	ON `rep`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'REM' group by customer_disability_id) rem
	ON `rem`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id
) p";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.id DESC ";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.firstName like '%$search%' or p.lastName like '%$search%' or p.documentNumber like '%$search%')";
        }


        $sql = $query . $where . $orderBy;
        $sql .= $limit;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllCountBy($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
	`wg_customer_absenteeism_disability`.id,
	`wg_employee`.documentNumber,
	`wg_employee`.firstName,
	`wg_employee`.lastName,
	`employee_contract_type`.item contractType,
	`dtype`.item typeText,
	`wg_customer_absenteeism_disability`.category,
	`absenteeism_disability_causes`.item causeItem,
	`absenteeism_disability_causes`.value causeValue,
	DATE_FORMAT(`wg_customer_absenteeism_disability`.`start`,'%d/%m/%Y') startDateFormat,
	DATE_FORMAT(`wg_customer_absenteeism_disability`.`end`,'%d/%m/%Y') endDateFormat,
	`wg_customer_absenteeism_disability`.`start`,
	`wg_customer_absenteeism_disability`.`end`,
	IFNULL(`ap`.`id`,0)  planId,
	 case when dr.qty > 0 then 1 else 0 end hasReport,
	 case when ap.qty > 0 then 1 else 0 end hasActionPlan,
	 case when inc.qty > 0 then 1 else 0 end hasInhability,
	 case when inv.qty > 0 then 1 else 0 end hasInvestigation
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.firstName like '%$search%' or p.lastName like '%$search%' or p.documentNumber like '%$search%')";
        }

        $sql = $query . $where;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return count($results);
    }


    public function getAllDiagnostic($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
    dd.description disability,
    wg_customer_absenteeism_disability.diagnostic_id,
    COUNT(*) records,
	/*`wg_employee`.documentNumber,
	`wg_employee`.firstName,
	`wg_employee`.lastName,
	`employee_contract_type`.item contractType,
	`dtype`.item typeText,
	CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Inicial' Then 'Si' else 'No' end origin,
	CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Prorroga' Then 'Si' else 'No' end extension,
	`wg_customer_absenteeism_disability`.`type`,
	`wg_customer_absenteeism_disability`.category,
	`absenteeism_disability_causes`.item causeItem,
	`absenteeism_disability_causes`.value causeValue,*/
	DATE_FORMAT(MIN(`wg_customer_absenteeism_disability`.`start`),'%d/%m/%Y') startDate,
	DATE_FORMAT(MAX(`wg_customer_absenteeism_disability`.`end`),'%d/%m/%Y') endDate,
	SUM(`wg_customer_absenteeism_disability`.`numberDays`) as days
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id AND `wg_customer_absenteeism_disability`.category = 'Incapacidad'
	GROUP BY disability
) p";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.disability, p.startDate, p.endDate ";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.disability like '%$search%' or p.startDate like '%$search%' or p.endDate like '%$search%' or p.days like '%$search%')";
        }


        $sql = $query . $where . $orderBy;
        $sql .= $limit;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllDiagnosticCountBy($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
    dd.description disability,
    COUNT(*) records,
	/*`wg_employee`.documentNumber,
	`wg_employee`.firstName,
	`wg_employee`.lastName,
	`employee_contract_type`.item contractType,
	`dtype`.item typeText,
	CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Inicial' Then 'Si' else 'No' end origin,
	CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Prorroga' Then 'Si' else 'No' end extension,
	`wg_customer_absenteeism_disability`.`type`,
	`wg_customer_absenteeism_disability`.category,
	`absenteeism_disability_causes`.item causeItem,
	`absenteeism_disability_causes`.value causeValue,*/
	DATE_FORMAT(MIN(`wg_customer_absenteeism_disability`.`start`),'%d/%m/%Y') startDate,
	DATE_FORMAT(MAX(`wg_customer_absenteeism_disability`.`end`),'%d/%m/%Y') endDate,
	SUM(`wg_customer_absenteeism_disability`.`numberDays`) as days
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id AND `wg_customer_absenteeism_disability`.category = 'Incapacidad'
	GROUP BY disability
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.disability like '%$search%' or p.startDate like '%$search%' or p.endDate like '%$search%' or p.days like '%$search%')";
        }

        $sql = $query . $where;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return count($results);
    }


    public function getAllDays($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
    dd.description disability,
    `absenteeism_disability_causes`.item causeItem,
    wg_customer_absenteeism_disability.diagnostic_id,
    wg_customer_absenteeism_disability.cause type,
    COUNT(*) records,
	/*`wg_employee`.documentNumber,
	`wg_employee`.firstName,
	`wg_employee`.lastName,
	`employee_contract_type`.item contractType,
	`dtype`.item typeText,
	CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Inicial' Then 'Si' else 'No' end origin,
	CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Prorroga' Then 'Si' else 'No' end extension,
	`wg_customer_absenteeism_disability`.`type`,
	`wg_customer_absenteeism_disability`.category,
	`absenteeism_disability_causes`.item causeItem,
	`absenteeism_disability_causes`.value causeValue,*/
	DATE_FORMAT(MIN(`wg_customer_absenteeism_disability`.`start`),'%d/%m/%Y') startDate,
	DATE_FORMAT(MAX(`wg_customer_absenteeism_disability`.`end`),'%d/%m/%Y') endDate,
	SUM(`wg_customer_absenteeism_disability`.`numberDays`) as days
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id AND `wg_customer_absenteeism_disability`.category = 'Incapacidad'
	GROUP BY disability, `absenteeism_disability_causes`.item
) p";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.disability, p.causeItem, p.startDate, p.endDate ";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.disability like '%$search%' or p.startDate like '%$search%' or p.endDate like '%$search%' or p.days like '%$search%' or p.causeItem like '%$search%')";
        }


        $sql = $query . $where . $orderBy;
        $sql .= $limit;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllDaysCountBy($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null)
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
    dd.description disability,
    `absenteeism_disability_causes`.item causeItem,
    wg_customer_absenteeism_disability.diagnostic_id,
    wg_customer_absenteeism_disability.cause type,
    COUNT(*) records,

	DATE_FORMAT(MIN(`wg_customer_absenteeism_disability`.`start`),'%d/%m/%Y') startDate,
	DATE_FORMAT(MAX(`wg_customer_absenteeism_disability`.`end`),'%d/%m/%Y') endDate,
	SUM(`wg_customer_absenteeism_disability`.`numberDays`) as days
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id AND `wg_customer_absenteeism_disability`.category = 'Incapacidad'
	GROUP BY disability, `absenteeism_disability_causes`.item
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.disability like '%$search%' or p.startDate like '%$search%' or p.endDate like '%$search%' or p.days like '%$search%' or p.causeItem like '%$search%')";
        }

        $sql = $query . $where;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return count($results);
    }


    public function getAllPerson($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null, $diagnosticId = 0, $type = '')
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
	`wg_customer_absenteeism_disability`.id,
		dd.description disability,
		wg_customer_absenteeism_disability.diagnostic_id,
	`wg_employee`.documentNumber,
	`wg_employee`.firstName,
	`wg_employee`.lastName,
	 CONCAT_WS(' ',`wg_employee`.lastName,`wg_employee`.firstName) employee,

	`absenteeism_disability_causes`.item origin,
    `dtype`.item `type`,
	-- CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Prorroga' Then 'Si' else 'No' end extension,
	DATE_FORMAT(`wg_customer_absenteeism_disability`.`start`,'%d/%m/%Y') startDate,
	DATE_FORMAT(`wg_customer_absenteeism_disability`.`end`,'%d/%m/%Y') endDate,
	`wg_customer_absenteeism_disability`.`start`,
	`wg_customer_absenteeism_disability`.`end`,
	`wg_customer_absenteeism_disability`.`numberDays` as days,
	wg_customer_absenteeism_disability.cause,
	CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Inicial' THEN 0 else `wg_customer_absenteeism_disability`.`numberDays` end as acumulateDays
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id AND `wg_customer_absenteeism_disability`.category = 'Incapacidad'
) p";

        $limit = " LIMIT $startFrom , $perPage";
        $orderBy = " ORDER BY p.employee, p.start, p.end, p.type ";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.firstName like '%$search%' or p.lastName like '%$search%' or p.disability like '%$search%' or p.startDate like '%$search%'
                         or p.endDate like '%$search%' or p.days like '%$search%' or p.acumulateDays like '%$search%')";
        }

        if ($diagnosticId > 0) {
            if (!empty(trim($where))) {
                $where .= " AND p.diagnostic_id = $diagnosticId ";
            } else {
                $where = " WHERE p.diagnostic_id = $diagnosticId ";
            }
        }

        if ($type != '') {
            if (!empty(trim($where))) {
                $where .= " AND p.cause = '$type' ";
            } else {
                $where = " WHERE p.cause = '$type' ";
            }
        }


        $sql = $query . $where . $orderBy;
        $sql .= $limit;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllPersonCountBy($search, $perPage = 10, $currentPage = 0, $customerId = 0, $filter = null, $diagnosticId = 0, $type = '')
    {

        $startFrom = ($currentPage - 1) * $perPage;

        $query = "SELECT * FROM
(
SELECT
    `wg_customer_absenteeism_disability`.id,
    dd.description disability,
    wg_customer_absenteeism_disability.diagnostic_id,
	`wg_employee`.firstName,
	`wg_employee`.lastName,
	 CONCAT_WS(' ',`wg_employee`.lastName,`wg_employee`.firstName) employee,

	`absenteeism_disability_causes`.item origin,
	wg_customer_absenteeism_disability.cause,
    `dtype`.item `type`,
	-- CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Prorroga' Then 'Si' else 'No' end extension,
	DATE_FORMAT(`wg_customer_absenteeism_disability`.`start`,'%d/%m/%Y') startDate,
	DATE_FORMAT(`wg_customer_absenteeism_disability`.`end`,'%d/%m/%Y') endDate,
	`wg_customer_absenteeism_disability`.`start`,
	`wg_customer_absenteeism_disability`.`end`,
	`wg_customer_absenteeism_disability`.`numberDays` as days,
	`wg_customer_absenteeism_disability`.`numberDays` as acumulateDays
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id AND `wg_customer_absenteeism_disability`.category = 'Incapacidad'
) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($filter != null) {
            $where = $this->getWhere($filter->filters);
        } else if ($search != '') {
            $where = " WHERE (p.firstName like '%$search%' or p.lastName like '%$search%' or p.disability like '%$search%' or p.startDate like '%$search%'
                         or p.endDate like '%$search%' or p.days like '%$search%' or p.acumulateDays like '%$search%')";
        }

        if ($diagnosticId > 0) {
            if (!empty(trim($where))) {
                $where .= " AND p.diagnostic_id = $diagnosticId ";
            } else {
                $where = " WHERE p.diagnostic_id = $diagnosticId ";
            }
        }

        if ($type != '') {
            if (!empty(trim($where))) {
                $where .= " AND p.cause = '$type' ";
            } else {
                $where = " WHERE p.cause = '$type' ";
            }
        }


        $sql = $query . $where;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return count($results);
    }


    public function getAllDiagnosticExport($customerId = 0)
    {


        $query = "SELECT * FROM
(
SELECT
    dd.description `Diagnóticos`,
    COUNT(*) `Num Casos`,
	/*`wg_employee`.documentNumber,
	`wg_employee`.firstName,
	`wg_employee`.lastName,
	`employee_contract_type`.item contractType,
	`dtype`.item typeText,
	CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Inicial' Then 'Si' else 'No' end origin,
	CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Prorroga' Then 'Si' else 'No' end extension,
	`wg_customer_absenteeism_disability`.`type`,
	`wg_customer_absenteeism_disability`.category,
	`absenteeism_disability_causes`.item causeItem,
	`absenteeism_disability_causes`.value causeValue,*/
	DATE_FORMAT(MIN(`wg_customer_absenteeism_disability`.`start`),'%d/%m/%Y') `Fecha Inicial`,
	DATE_FORMAT(MAX(`wg_customer_absenteeism_disability`.`end`),'%d/%m/%Y') `Fecha Final`,
	SUM(`wg_customer_absenteeism_disability`.`numberDays`) as `Num Días Acumulados`,
    wp.name AS `Centro de Trabajo`
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
    LEFT JOIN wg_customer_config_workplace wp on wg_customer_absenteeism_disability.workplace_id = wp.id    
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id AND `wg_customer_absenteeism_disability`.category = 'Incapacidad'
	GROUP BY dd.description
) p";

        $orderBy = " ORDER BY `Fecha Inicial` ";

        $sql = $query . $orderBy;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllPersonDiagnosticExport($customerId = 0)
    {
        $query = "SELECT * FROM
(
SELECT
	CONCAT_WS(' ',`wg_employee`.lastName,`wg_employee`.firstName) `Empleado`,
	DATE_FORMAT(`wg_customer_absenteeism_disability`.`start`,'%d/%m/%Y') `F.Inicio`,
	DATE_FORMAT(`wg_customer_absenteeism_disability`.`end`,'%d/%m/%Y') `F.Final`,
	`absenteeism_disability_causes`.item `Origen`,
	`dtype`.item `Tipo`,
	`wg_customer_absenteeism_disability`.`numberDays` as `Num Días`,
	CASE WHEN `wg_customer_absenteeism_disability`.`type` = 'Inicial' THEN 0 else `wg_customer_absenteeism_disability`.`numberDays` end as `Num Días Acumulados`,
	dd.description `Diagnóstico`
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id AND `wg_customer_absenteeism_disability`.category = 'Incapacidad'
ORDER BY `wg_customer_absenteeism_disability`.`start`
) p";

        $orderBy = "";

        $sql = $query . $orderBy;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

        return $results;
    }

    public function getAllDaysDiagnosticExport($customerId = 0)
    {


        $query = "SELECT * FROM
(
SELECT
    dd.description `Diagnóstico`,
    `absenteeism_disability_causes`.item `Origen`,
    DATE_FORMAT(MIN(`wg_customer_absenteeism_disability`.`start`),'%d/%m/%Y') `F.Inicio`,
    DATE_FORMAT(MAX(`wg_customer_absenteeism_disability`.`end`),'%d/%m/%Y') `F.Final`,
    COUNT(*) `Num Casos`,
    SUM(`wg_customer_absenteeism_disability`.`numberDays`) as `Num Días Acumulados`,
    wp.name AS `Centro de Trabajo`
FROM
	`wg_customer_absenteeism_disability`
INNER JOIN `wg_customer_employee` ON `wg_customer_absenteeism_disability`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN wg_disability_diagnostic dd on wg_customer_absenteeism_disability.diagnostic_id = dd.id
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_absenteeism_disability_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_absenteeism_disability_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_absenteeism_disability`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_absenteeism_disability`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_absenteeism_disability.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) employee_contract_type ON `wg_customer_employee`.contractType COLLATE utf8_general_ci = `employee_contract_type`.`value`
LEFT JOIN wg_customer_config_workplace wp on wg_customer_absenteeism_disability.workplace_id = wp.id
WHERE
	`wg_customer_employee`.`customer_id` = :customer_id AND `wg_customer_absenteeism_disability`.category = 'Incapacidad'
	GROUP BY dd.description, `absenteeism_disability_causes`.item
) p";

        $orderBy = " ORDER BY `F.Inicio` ";

        $sql = $query . $orderBy;

        $results = DB::select($sql, array(
            'customer_id' => $customerId
        ));

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

        return $where == "" ? "" : " WHERE " . $where;
    }
}
