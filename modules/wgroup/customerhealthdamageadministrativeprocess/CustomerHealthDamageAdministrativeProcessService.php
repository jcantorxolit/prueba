<?php

namespace Wgroup\CustomerHealthDamageAdministrativeProcess;

use DB;
use Exception;
use Log;
use Str;

class CustomerHealthDamageAdministrativeProcessService
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $customerId = 0, $audit = null)
    {

        $model = new CustomerHealthDamageAdministrativeProcess();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerAbsenteeismDisabilityRepository = new CustomerHealthDamageAdministrativeProcessRepository($model);

        if ($perPage > 0) {
            $this->customerAbsenteeismDisabilityRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_health_damage_administrative_process.id',
            'wg_customer_health_damage_administrative_process.examinationType',
            'wg_customer_health_damage_administrative_process.medicalConcept',
            'wg_employee.firstName',
            'wg_employee.lastName',
        ];

        $i = 0;

        $sorting = [];

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
            $this->customerAbsenteeismDisabilityRepository->sortBy('wg_customer_health_damage_administrative_process.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_employee.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee.documentNumber', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.observationRelocated', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.observationEnhancedStability', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.observationDisabilityPayment', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.resultTutelage', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.resultComplain', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.whatCustomerSay', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.whatCustomerExpect', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.medicalOccupationalConcept', $search);
        }

        /*if ($typeFilter == "1") {
            $filters[] = array('wg_customer_health_damage_administrative_process.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_health_damage_administrative_process.status', '0');
        }*/

        $this->customerAbsenteeismDisabilityRepository->setColumns(['wg_customer_health_damage_administrative_process.*']);

        return $this->customerAbsenteeismDisabilityRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerHealthDamageAdministrativeProcess();
        $this->customerAbsenteeismDisabilityRepository = new CustomerHealthDamageAdministrativeProcessRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_employee.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_employee.documentNumber', $search);
            $filters[] = array('wg_employee.firstName', $search);
            $filters[] = array('wg_employee.lastName', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.observationRelocated', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.observationEnhancedStability', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.observationDisabilityPayment', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.resultTutelage', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.resultComplain', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.whatCustomerSay', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.whatCustomerExpect', $search);
            $filters[] = array('wg_customer_health_damage_administrative_process.medicalOccupationalConcept', $search);
        }

        $this->customerAbsenteeismDisabilityRepository->setColumns(['wg_customer_health_damage_administrative_process.*']);

        return $this->customerAbsenteeismDisabilityRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAllByBilling($customerId = 0)
    {

        $query = "SELECT
	`wg_customer_health_damage_administrative_process`.id,
	`wg_customer_health_damage_administrative_process`.amountPaid,
	IFNULL(`wg_customer_health_damage_administrative_process`.charged,0) charged,
	wg_employee.fullName,
	`start`,
	`end`,
	category,
	`wg_customer_health_damage_administrative_process`.type,
	cause,
	'true' alive,
	numberDays
FROM
	`wg_customer_health_damage_administrative_process`
INNER JOIN `wg_customer_employee` ON `wg_customer_health_damage_administrative_process`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
INNER JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_health_damage_administrative_process`.`type` = `dtype`.`value`
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
	`wg_customer_employee`.`customer_id` = :customer_id and charged is null or charged = 0
ORDER BY
	`wg_customer_health_damage_administrative_process`.`start` DESC";

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
	`wg_customer_health_damage_administrative_process`.id,
	`wg_employee`.documentNumber,
	`wg_employee`.firstName,
	`wg_employee`.lastName,
	`employee_contract_type`.item contractType,
	`dtype`.item typeText,
	`wg_customer_health_damage_administrative_process`.category,
	`absenteeism_disability_causes`.item causeItem,
	`absenteeism_disability_causes`.value causeValue,
	DATE_FORMAT(`wg_customer_health_damage_administrative_process`.`start`,'%d/%m/%Y') startDateFormat,
	DATE_FORMAT(`wg_customer_health_damage_administrative_process`.`end`,'%d/%m/%Y') endDateFormat,
	`wg_customer_health_damage_administrative_process`.`start`,
	`wg_customer_health_damage_administrative_process`.`end`,
	IFNULL(`ap`.`id`,0)  planId,
	 case when dr.qty > 0 then 1 else 0 end hasReport,
	 case when ap.qty > 0 then 1 else 0 end hasActionPlan,
	 case when inc.qty > 0 then 1 else 0 end hasInhability,
	 case when inv.qty > 0 then 1 else 0 end hasInvestigation
FROM
	`wg_customer_health_damage_administrative_process`
INNER JOIN `wg_customer_employee` ON `wg_customer_health_damage_administrative_process`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_health_damage_administrative_process_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_health_damage_administrative_process`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_health_damage_administrative_process_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_health_damage_administrative_process`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_health_damage_administrative_process_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_health_damage_administrative_process`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_health_damage_administrative_process_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_health_damage_administrative_process`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_health_damage_administrative_process`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_health_damage_administrative_process.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
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
	`wg_customer_health_damage_administrative_process`.id,
	`wg_employee`.documentNumber,
	`wg_employee`.firstName,
	`wg_employee`.lastName,
	`employee_contract_type`.item contractType,
	`dtype`.item typeText,
	`wg_customer_health_damage_administrative_process`.category,
	`absenteeism_disability_causes`.item causeItem,
	`absenteeism_disability_causes`.value causeValue,
	DATE_FORMAT(`wg_customer_health_damage_administrative_process`.`start`,'%d/%m/%Y') startDateFormat,
	DATE_FORMAT(`wg_customer_health_damage_administrative_process`.`end`,'%d/%m/%Y') endDateFormat,
	`wg_customer_health_damage_administrative_process`.`start`,
	`wg_customer_health_damage_administrative_process`.`end`,
	IFNULL(`ap`.`id`,0)  planId,
	 case when dr.qty > 0 then 1 else 0 end hasReport,
	 case when ap.qty > 0 then 1 else 0 end hasActionPlan,
	 case when inc.qty > 0 then 1 else 0 end hasInhability,
	 case when inv.qty > 0 then 1 else 0 end hasInvestigation
FROM
	`wg_customer_health_damage_administrative_process`
INNER JOIN `wg_customer_employee` ON `wg_customer_health_damage_administrative_process`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_health_damage_administrative_process_report_al group by customer_disability_id) dr
	ON `dr`.`customer_disability_id` = `wg_customer_health_damage_administrative_process`.`id`
LEFT JOIN (select COUNT(*) qty, id, customer_disability_id from wg_customer_health_damage_administrative_process_action_plan group by customer_disability_id) ap
	ON `ap`.`customer_disability_id` = `wg_customer_health_damage_administrative_process`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_health_damage_administrative_process_document where type = 'INC' group by customer_disability_id) inc
	ON `inc`.`customer_disability_id` = `wg_customer_health_damage_administrative_process`.`id`
LEFT JOIN (select COUNT(*) qty, customer_disability_id from wg_customer_health_damage_administrative_process_document where type = 'INV' group by customer_disability_id) inv
	ON `inv`.`customer_disability_id` = `wg_customer_health_damage_administrative_process`.`id`
LEFT JOIN (
	SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_type'
) dtype ON `wg_customer_health_damage_administrative_process`.`type` = `dtype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'employee_contract_type'
	) ctype ON wg_customer_employee.contractType COLLATE utf8_general_ci = `ctype`.`value`
LEFT JOIN (
		SELECT * FROM system_parameters WHERE system_parameters.namespace = 'wgroup' AND system_parameters.`group` = 'absenteeism_disability_causes'
	) absenteeism_disability_causes ON wg_customer_health_damage_administrative_process.cause COLLATE utf8_general_ci = `absenteeism_disability_causes`.`value`
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
