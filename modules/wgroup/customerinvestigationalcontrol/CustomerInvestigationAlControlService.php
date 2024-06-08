<?php

namespace Wgroup\CustomerInvestigationAlControl;

use Carbon\Carbon;
use DB;
use Exception;
use Log;
use Str;
use System\Models\Parameters;

class CustomerInvestigationAlControlService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContractorRepository;

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
     * @param string $customerInvestigationId
     * @return mixed
     */
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $customerInvestigationId = 0)
    {

        $model = new CustomerInvestigationAlControl();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorRepository = new CustomerInvestigationAlControlRepository($model);

        if ($perPage > 0) {
            $this->customerContractorRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_investigation_al_control.id',
            'wg_customer_investigation_al_control.controlType',
            'wg_customer_investigation_al_control.dateValue',
            'wg_customer_investigation_al_control.located',
            'wg_customer_investigation_al_control.dateLocated',
            'wg_customer_investigation_al_control.customer_investigation_id',
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
                    $this->customerContractorRepository->sortBy($colName, $dir);
                } else {
                    $this->customerContractorRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerContractorRepository->sortBy('wg_customer_investigation_al_control.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_control.customer_investigation_id', $customerInvestigationId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('investigation_control_type_date.item', $search);
            $filters[] = array('wg_customer_investigation_al_control.dateValue', $search);
            $filters[] = array('wg_customer_investigation_al_control.located', $search);
            $filters[] = array('wg_customer_investigation_al_control.dateLocated', $search);
            $filters[] = array('wg_customer_investigation_al_control.source', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_investigation_al_control.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerInvestigationId)
    {

        $model = new CustomerInvestigationAlControl();
        $this->customerContractorRepository = new CustomerInvestigationAlControlRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_investigation_al_control.customer_investigation_id', $customerInvestigationId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('investigation_control_type_date.item', $search);
            $filters[] = array('wg_customer_investigation_al_control.dateValue', $search);
            $filters[] = array('wg_customer_investigation_al_control.located', $search);
            $filters[] = array('wg_customer_investigation_al_control.dateLocated', $search);
            $filters[] = array('wg_customer_investigation_al_control.source', $search);
        }

        $this->customerContractorRepository->setColumns(['wg_customer_investigation_al_control.*']);

        return $this->customerContractorRepository->getFilteredsOptional($filters, true, "");
    }

    public function getFactors($investigationId)
    {
        $sql = "SELECT factor id,
        UPPER(p.item) `name`
FROM `wg_customer_investigation_al_control` f
INNER JOIN
  (SELECT *
   FROM system_parameters
   WHERE `group` = 'investigation_factor'
     AND namespace = 'wgroup') p ON f.factor = p.`value` COLLATE utf8_general_ci
WHERE customer_investigation_id = :customer_investigation_id
GROUP BY p.item;";

        $results = DB::select($sql, array(
            'customer_investigation_id' => $investigationId
        ));

        return $results;
    }

    public function getCauses($investigationId)
    {
        $sql = "SELECT factor,
       cause `name`
FROM `wg_customer_investigation_al_control` f
WHERE customer_investigation_id = :customer_investigation_id
ORDER BY `sort`;";

        $results = DB::select($sql, array(
            'customer_investigation_id' => $investigationId
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

        //Log::info($where);
        //Log::info(count($filters));

        return $where == "" ? "" : " WHERE " . $where;
    }

    public function getNextControl($investigationId)
    {
        $data = $this->getLastControlType($investigationId);

        $today = Carbon::today('America/Bogota');

        $result = null;

        if ($data == null) {
            $result = $this->getParameterByValue('date_ia_customer', 'investigation_control_type_date');
        } else {
            switch ($data->controlType) {
                case 'date_ia_customer':
                    if ($data->status == 'Pendiente') {
                        //var_dump($data);
                        //if ($data->dateValue == null) {

                        $date = Carbon::parse($data->accidentDate)->timezone('America/Bogota');
                        $date = $date->addDays(15);
                        $days = $today->diffInDays($date, false);

                        //var_dump($days);

                        if ($days <= 0) {
                            $result = $this->getParameterByValue('date_request_investigation', 'investigation_control_type_date');
                        }

                        //}
                    } else {
                        if ($data->status == 'Terminado') {
                            if ($data->dateValue != null) {

                                $date = Carbon::parse($data->dateValue)->timezone('America/Bogota');
                                $date = $date->addDays(21);
                                $days = $today->diffInDays($date, false);

                                $result = $this->getParameterByValue('date_technical_concept', 'investigation_control_type_date');
                                if ($days <= 0) {
                                }
                            }
                        }
                    }
                    break;

                case 'date_request_investigation':

                    if ($data->status == 'Pendiente') {
                        if ($data->dateValue == null) {

                            $date = Carbon::parse($data->accidentDate)->timezone('America/Bogota');
                            $date = $date->addDays(30);
                            $days = $today->diffInDays($date, false);

                            if ($days <= 0) {
                                $result = $this->getParameterByValue('date_second_request_investigation', 'investigation_control_type_date');
                            }

                        }
                    }

                    break;

                case 'date_second_request_investigation':

                    if ($data->status == 'Pendiente') {
                        if ($data->dateValue == null) {

                            $date = Carbon::parse($data->accidentDate)->timezone('America/Bogota');
                            $date = $date->addDays(60);
                            $days = $today->diffInDays($date, false);

                            if ($days <= 0) {
                                $result = $this->getParameterByValue('date_report_ministry', 'investigation_control_type_date');
                            }

                        }
                    }

                    break;


                case 'date_technical_concept':

                    $report = $this->getControlType($investigationId, 'date_ia_customer');

                    if ($data->status == 'Terminado') {
                        if ($data->dateValue != null && $report->dateValue != null && $report->accidentType == '004') {

                            $date = Carbon::parse($report->dateValue)->timezone('America/Bogota');
                            $date = $date->addDays(14);
                            $days = $today->diffInDays($date);

                            $result = $this->getParameterByValue('date_report_ministry', 'investigation_control_type_date');
                            if ($days <= 0) {
                            }
                        }
                    }

                    break;
            }
        }

        return $result;
    }

    private function getLastControlType($investigationId)
    {
        $sql = "SELECT i.id,
       IFNULL(ic.controlType, 'date_ia_customer') controlType,
       CASE
           WHEN ic.dateValue IS NULL THEN 0
           ELSE 1
       END hasDate,
       IFNULL(ic.status, 'pendiente') `status`,
       ic.dateValue,
       i.accidentDate,
       DATE_ADD(i.accidentDate, INTERVAL 15 DAY) expirationDate,
       DATEDIFF(DATE_ADD(i.accidentDate, INTERVAL 15 DAY),NOW()) days,
       ic.located,
       ic.dateLocated
FROM wg_customer_investigation_al i
INNER JOIN
  (SELECT MAX(id) id,
          customer_investigation_id
   FROM wg_customer_investigation_al_control
   WHERE controlType IN ('date_ia_customer',
                         'date_request_investigation',
                         'date_second_request_investigation',
                         'date_report_ministry',
                         'date_technical_concept',
                         'date_notification_ministry')
   GROUP BY customer_investigation_id) icx ON icx.customer_investigation_id = i.id
INNER JOIN wg_customer_investigation_al_control ic ON ic.id = icx.id
WHERE i.id = :investigation_id
GROUP BY controlType,
         i.id";

        $results = DB::select($sql, array(
            'investigation_id' => $investigationId
        ));

        return count($results) ? $results[0] : null;
    }

    private function getControlType($investigationId, $type)
    {
        $sql = "SELECT i.id,
       IFNULL(ic.controlType, 'date_ia_customer') controlType,
       CASE
           WHEN ic.dateValue IS NULL THEN 0
           ELSE 1
       END hasDate,
       IFNULL(ic.status, 'pendiente') `status`,
       ic.dateValue,
       i.accidentDate,
       DATE_ADD(i.accidentDate, INTERVAL 15 DAY) expirationDate,
       DATEDIFF(DATE_ADD(i.accidentDate, INTERVAL 15 DAY),NOW()) days,
       ic.located,
       ic.dateLocated,
       i.accidentType
FROM wg_customer_investigation_al i
INNER JOIN
  (SELECT MAX(id) id,
          customer_investigation_id,
          dateValue,
          dateLocated,
          located,
          controlType,
					`status`
   FROM wg_customer_investigation_al_control
   WHERE controlType = :type
   GROUP BY customer_investigation_id) ic ON ic.customer_investigation_id = i.id
WHERE i.id = :investigation_id
GROUP BY controlType,
         i.id";

        $results = DB::select($sql, array(
            'investigation_id' => $investigationId,
            'type' => $type
        ));

        return count($results) ? $results[0] : null;
    }

    private function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }


    public function getAllByExpiration($length, $currentPage, $audit)
    {
        $startFrom = ($currentPage-1) * $length;

        $sql = "SELECT * FROM (
SELECT
	`wg_customer_investigation_al`.id,
	`wg_customer_investigation_al`.customer_id,
	`wg_customer_investigation_al`.accidentDate,
	`investigation_accident_type`.item accidentType,
	`wg_customers`.businessName,
	`wg_customer_document_type`.`item` documentType,
	`wg_customers`.`documentNumber`,
	`wg_employee`.`fullName`,
		CASE WHEN ic.controlType IS NULL THEN 'Fecha de radicación IA empresa' ELSE `investigation_control_type_date`.`item` END `controlType` ,
		CASE WHEN ic.controlType IS NULL THEN 'date_ia_customer' ELSE `ic`.`controlType` END `controlTypeValue` ,
	`ic`.`dateValue`,
	`ic`.`located`,
	`ic`.`dateLocated`,
	CASE WHEN ic.controlType IS NULL THEN 'Pendiente' ELSE `ic`.`status` END `status` ,
	CASE WHEN ic.controlType IS NULL THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY)
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Pendiente' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY)
				WHEN ic.controlType = 'date_request_investigation' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 30 DAY)
				WHEN ic.controlType = 'date_second_request_investigation' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 60 DAY)
				WHEN ic.controlType = 'date_report_ministry' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 90 DAY)
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Terminado' THEN
						DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 21 DAY)
				WHEN ic.controlType = 'date_technical_concept' THEN
						DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 14 DAY)
		END expirationDate,

	CASE WHEN ic.controlType IS NULL THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY) , NOW())
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Pendiente' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY), NOW())
				WHEN ic.controlType = 'date_request_investigation' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 30 DAY), NOW())
				WHEN ic.controlType = 'date_second_request_investigation' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 60 DAY), NOW())
				WHEN ic.controlType = 'date_report_ministry' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 90 DAY), NOW())
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Terminado' THEN
						DATEDIFF(DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 21 DAY), NOW())
				WHEN ic.controlType = 'date_technical_concept' THEN
						DATEDIFF(DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 14 DAY), NOW())
		END days,
	 (SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1) date
FROM
	`wg_customer_investigation_al`
INNER JOIN `wg_customers` ON `wg_customer_investigation_al`.`customer_id` = `wg_customers`.`id`
INNER JOIN `wg_customer_employee` ON `wg_customer_investigation_al`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
LEFT JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'tipodoc'
) wg_customer_document_type ON `wg_customers`.`documentType` = `wg_customer_document_type`.`value`
LEFT JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'investigation_accident_type'
) investigation_accident_type ON `wg_customer_investigation_al`.`accidentType` COLLATE utf8_general_ci = `investigation_accident_type`.`value`
LEFT JOIN (SELECT wg_customer_investigation_al_control.* FROM wg_customer_investigation_al_control
							INNER JOIN (
														SELECT MAX(id) id,
																			customer_investigation_id
															 FROM wg_customer_investigation_al_control
															 WHERE controlType IN ('date_ia_customer',
																										 'date_request_investigation',
																										 'date_second_request_investigation',
																										 'date_report_ministry',
																										 'date_technical_concept',
																										 'date_notification_ministry')
															 GROUP BY customer_investigation_id
												)icx ON icx.id = wg_customer_investigation_al_control.id
					)  ic ON ic.customer_investigation_id = wg_customer_investigation_al.id
LEFT JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'investigation_control_type_date'
) investigation_control_type_date ON `ic`.`controlType` COLLATE utf8_general_ci = `investigation_control_type_date`.`value`
WHERE `wg_customer_investigation_al`.accidentType IN ('003', '004')
) p";

        $limit = " LIMIT $startFrom , $length";

        $where = "";

        $whereArray = array();

        if ($audit != null) {
            if ($audit->type != '' && $audit->type != 'all') {
                $where .= " WHERE p.controlTypeValue = :type";
                $whereArray["type"] = $audit->type;
            }

            if ($audit->customerId != 0) {
                if ($where == '') {
                    $where .= " WHERE p.customer_id = :customer_id";
                } else {
                    $where .= " AND p.customer_id = :customer_id";
                }
                $whereArray["customer_id"] = $audit->customerId;
            }
        }


        $sql .= $where . $limit;

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getAllByExpirationCount($audit)
    {
        $sql = "SELECT * FROM (
SELECT
	`wg_customer_investigation_al`.id,
	`wg_customer_investigation_al`.customer_id,
	`wg_customer_investigation_al`.accidentDate,
	`investigation_accident_type`.item accidentType,
	`wg_customers`.businessName,
	`wg_customer_document_type`.`item` documentType,
	`wg_customers`.`documentNumber`,
	`wg_employee`.`fullName`,
		CASE WHEN ic.controlType IS NULL THEN 'Fecha de radicación IA empresa' ELSE `investigation_control_type_date`.`item` END `controlType` ,
		CASE WHEN ic.controlType IS NULL THEN 'date_ia_customer' ELSE `ic`.`controlType` END `controlTypeValue` ,
	`ic`.`dateValue`,
	`ic`.`located`,
	`ic`.`dateLocated`,
	CASE WHEN ic.controlType IS NULL THEN 'Pendiente' ELSE `ic`.`status` END `status` ,
	CASE WHEN ic.controlType IS NULL THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY)
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Pendiente' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY)
				WHEN ic.controlType = 'date_request_investigation' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 30 DAY)
				WHEN ic.controlType = 'date_second_request_investigation' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 60 DAY)
				WHEN ic.controlType = 'date_report_ministry' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 90 DAY)
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Terminado' THEN
						DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 21 DAY)
				WHEN ic.controlType = 'date_technical_concept' THEN
						DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 14 DAY)
		END expirationDate,

	CASE WHEN ic.controlType IS NULL THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY) , NOW())
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Pendiente' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY), NOW())
				WHEN ic.controlType = 'date_request_investigation' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 30 DAY), NOW())
				WHEN ic.controlType = 'date_second_request_investigation' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 60 DAY), NOW())
				WHEN ic.controlType = 'date_report_ministry' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 90 DAY), NOW())
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Terminado' THEN
						DATEDIFF(DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 21 DAY), NOW())
				WHEN ic.controlType = 'date_technical_concept' THEN
						DATEDIFF(DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 14 DAY), NOW())
		END days,
	 (SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1) date
FROM
	`wg_customer_investigation_al`
INNER JOIN `wg_customers` ON `wg_customer_investigation_al`.`customer_id` = `wg_customers`.`id`
INNER JOIN `wg_customer_employee` ON `wg_customer_investigation_al`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
LEFT JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'tipodoc'
) wg_customer_document_type ON `wg_customers`.`documentType` = `wg_customer_document_type`.`value`
LEFT JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'investigation_accident_type'
) investigation_accident_type ON `wg_customer_investigation_al`.`accidentType` COLLATE utf8_general_ci = `investigation_accident_type`.`value`
LEFT JOIN (SELECT wg_customer_investigation_al_control.* FROM wg_customer_investigation_al_control
							INNER JOIN (
														SELECT MAX(id) id,
																			customer_investigation_id
															 FROM wg_customer_investigation_al_control
															 WHERE controlType IN ('date_ia_customer',
																										 'date_request_investigation',
																										 'date_second_request_investigation',
																										 'date_report_ministry',
																										 'date_technical_concept',
																										 'date_notification_ministry')
															 GROUP BY customer_investigation_id
												)icx ON icx.id = wg_customer_investigation_al_control.id
					)  ic ON ic.customer_investigation_id = wg_customer_investigation_al.id
LEFT JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'investigation_control_type_date'
) investigation_control_type_date ON `ic`.`controlType` COLLATE utf8_general_ci = `investigation_control_type_date`.`value`
WHERE `wg_customer_investigation_al`.accidentType IN ('003', '004')
) p";

        $where = "";

        $whereArray = array();

        if ($audit != null) {
            if ($audit->type != '' && $audit->type != 'all') {
                $where .= " WHERE p.controlTypeValue = :type";
                $whereArray["type"] = $audit->type;
            }

            if ($audit->customerId != 0) {
                if ($where == '') {
                    $where .= " WHERE p.customer_id = :customer_id";
                } else {
                    $where .= " AND p.customer_id = :customer_id";
                }
                $whereArray["customer_id"] = $audit->customerId;
            }
        }

        $sql .= $where;

        $results = DB::select($sql, $whereArray);

        return count($results);
    }


    public function getAllByExpirationExport($audit)
    {
        $sql = "SELECT * FROM (
SELECT
	`wg_customer_investigation_al`.id,
	`wg_customer_investigation_al`.customer_id,
	`wg_customer_investigation_al`.accidentDate,
	`investigation_accident_type`.item accidentType,
	`wg_customers`.businessName,
	`wg_customer_document_type`.`item` documentType,
	`wg_customers`.`documentNumber`,
	`wg_employee`.`fullName`,
		CASE WHEN ic.controlType IS NULL THEN 'Fecha de radicación IA empresa' ELSE `investigation_control_type_date`.`item` END `controlType` ,
		CASE WHEN ic.controlType IS NULL THEN 'date_ia_customer' ELSE `ic`.`controlType` END `controlTypeValue` ,
	`ic`.`dateValue`,
	`ic`.`located`,
	`ic`.`dateLocated`,
	CASE WHEN ic.controlType IS NULL THEN 'Pendiente' ELSE `ic`.`status` END `status` ,
	CASE WHEN ic.controlType IS NULL THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY)
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Pendiente' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY)
				WHEN ic.controlType = 'date_request_investigation' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 30 DAY)
				WHEN ic.controlType = 'date_second_request_investigation' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 60 DAY)
				WHEN ic.controlType = 'date_report_ministry' THEN DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 90 DAY)
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Terminado' THEN
						DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 21 DAY)
				WHEN ic.controlType = 'date_technical_concept' THEN
						DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 14 DAY)
		END expirationDate,

	CASE WHEN ic.controlType IS NULL THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY) , NOW())
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Pendiente' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 15 DAY), NOW())
				WHEN ic.controlType = 'date_request_investigation' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 30 DAY), NOW())
				WHEN ic.controlType = 'date_second_request_investigation' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 60 DAY), NOW())
				WHEN ic.controlType = 'date_report_ministry' THEN DATEDIFF(DATE_ADD(wg_customer_investigation_al.accidentDate, INTERVAL 90 DAY), NOW())
				WHEN ic.controlType = 'date_ia_customer' AND ic.`status` = 'Terminado' THEN
						DATEDIFF(DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 21 DAY), NOW())
				WHEN ic.controlType = 'date_technical_concept' THEN
						DATEDIFF(DATE_ADD((SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1), INTERVAL 14 DAY), NOW())
		END days,
	 (SELECT dateValue FROM `wg_customer_investigation_al_control` ic
						WHERE `ic`.customer_investigation_id = `wg_customer_investigation_al`.id and ic.controlType = 'date_ia_customer' LIMIT 1) date
FROM
	`wg_customer_investigation_al`
INNER JOIN `wg_customers` ON `wg_customer_investigation_al`.`customer_id` = `wg_customers`.`id`
INNER JOIN `wg_customer_employee` ON `wg_customer_investigation_al`.`customer_employee_id` = `wg_customer_employee`.`id`
INNER JOIN `wg_employee` ON `wg_customer_employee`.`employee_id` = `wg_employee`.`id`
LEFT JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'tipodoc'
) wg_customer_document_type ON `wg_customers`.`documentType` = `wg_customer_document_type`.`value`
LEFT JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'investigation_accident_type'
) investigation_accident_type ON `wg_customer_investigation_al`.`accidentType` COLLATE utf8_general_ci = `investigation_accident_type`.`value`
LEFT JOIN (SELECT wg_customer_investigation_al_control.* FROM wg_customer_investigation_al_control
							INNER JOIN (
														SELECT MAX(id) id,
																			customer_investigation_id
															 FROM wg_customer_investigation_al_control
															 WHERE controlType IN ('date_ia_customer',
																										 'date_request_investigation',
																										 'date_second_request_investigation',
																										 'date_report_ministry',
																										 'date_technical_concept',
																										 'date_notification_ministry')
															 GROUP BY customer_investigation_id
												)icx ON icx.id = wg_customer_investigation_al_control.id
					)  ic ON ic.customer_investigation_id = wg_customer_investigation_al.id
LEFT JOIN (
	SELECT
		*
	FROM
		system_parameters
	WHERE
		system_parameters.namespace = 'wgroup'
	AND system_parameters.`group` = 'investigation_control_type_date'
) investigation_control_type_date ON `ic`.`controlType` COLLATE utf8_general_ci = `investigation_control_type_date`.`value`
WHERE `wg_customer_investigation_al`.accidentType IN ('003', '004')
) p";


        $where = "";

        $whereArray = array();

        if ($audit != null) {
            if ($audit->type != '' && $audit->type != 'all') {
                $where .= " WHERE p.controlTypeValue = :type";
                $whereArray["type"] = $audit->type;
            }

            if ($audit->customerId != 0) {
                if ($where == '') {
                    $where .= " WHERE p.customer_id = :customer_id";
                } else {
                    $where .= " AND p.customer_id = :customer_id";
                }
                $whereArray["customer_id"] = $audit->customerId;
            }
        }


        $sql .= $where;

        $sql = "SELECT
        	        id AS `Consecutivo`,
	                accidentDate AS `Fecha accidente`,
	                accidentType AS `Tipo accidente`,
                    businessName AS `Cliente`,
                    `documentType` AS `Tipo Identificación`,
                    `documentNumber` AS `Nro Identificación`,
                    `fullName` AS `Nombre Empleado`,
                    expirationDate AS `Fecha límite`,
                    CASE WHEN status = 'Pendiente' THEN days ELSE 'Informe Radicado' END  AS `Días faltantes`,
                    controlType AS `Tipo control`,
                    dateValue AS `Fecha control`,
                    located AS `Radicado`,
                    dateLocated AS `Fecha radicación`
                FROM (".$sql.") T";

        $results = DB::select($sql, $whereArray);

        return $results;
    }
}
