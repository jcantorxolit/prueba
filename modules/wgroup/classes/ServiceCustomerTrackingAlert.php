<?php

namespace Wgroup\Classes;

use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerTracking;
use Wgroup\Models\CustomerTrackingAlert;
use Wgroup\Models\CustomerTrackingAlertReporistory;
use Wgroup\Models\CustomerTrackingReporistory;
use DB;

class ServiceCustomerTrackingAlert {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerTrackingAlertRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "") {

        $model = new CustomerTrackingAlert();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerTrackingRepository = new CustomerTrackingAlertReporistory($model);

        if ($perPage > 0) {
            $this->customerTrackingAlertRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_tracking_alert.type',
            'wg_customer_tracking_alert.agent_id',
            'wg_customer_tracking_alert.time',
            'wg_customer_tracking_alert.timeType',
            'wg_customer_tracking_alert.preference',
            'wg_customer_tracking_alert.updated_at'
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
                    $this->customerTrackingAlertRepository->sortBy($colName, $dir);
                } else {
                    $this->customerTrackingAlertRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerTrackingAlertRepository->sortBy('wg_customer_tracking_alert.id', 'desc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_tracking_alert.type', $search);
            $filters[] = array('wg_customer_tracking_alert.agent_id', $search);
            $filters[] = array('wg_customer_tracking_alert.time', $search);
            $filters[] = array('wg_customer_tracking_alert.timeType', $search);
            $filters[] = array('wg_customer_tracking_alert.preference', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_tracking_alert.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_tracking_alert.status', '0');
        }


        $this->customerTrackingAlertRepository->setColumns(['wg_customer_tracking_alert.*']);

        return $this->customerTrackingAlertRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "") {

        $model = new CustomerTracking();
        $this->customerTrackingAlertRepository = new CustomerTrackingReporistory($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_tracking_alert.type', $search);
            $filters[] = array('wg_customer_tracking_alert.agent_id', $search);
            $filters[] = array('wg_customer_tracking_alert.time', $search);
            $filters[] = array('wg_customer_tracking_alert.timeType', $search);
            $filters[] = array('wg_customer_tracking_alert.preference', $search);
        }

        $this->customerTrackingAlertRepository->setColumns(['wg_customer_tracking_alert.*']);

        return $this->customerTrackingAlertRepository->getFilteredsOptional($filters, true, "");
    }

    public function getAlertBeforeHours()
    {
        $query = "select cta.id, u.email, ct.eventDateTime, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(HOUR,NOW(),eventDateTime) hours
			, CONCAT(firstName,' ',lastName) fullName
			, c.businessName
			, ct.observation
	from
wg_customer_tracking ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_tracking_alert cta on ct.id = cta.customer_tracking_id
inner join wg_agent a on ct.agent_id = a.id
inner join users u on a.user_id = u.id
left join (Select * from wg_notified_alert
						where entityName = 'tracking') na on cta.id = na.entityId
where na.entityId is null and timeType = 'h' and (TIMESTAMPDIFF(HOUR,NOW(),eventDateTime)) <= time and preference = 'antes'";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertBeforeDays()
    {
        $query = "select cta.id, u.email, ct.eventDateTime, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(DAY,NOW(),eventDateTime) days
			, CONCAT(firstName,' ',lastName) fullName
			, c.businessName
			, ct.observation
from
wg_customer_tracking ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_tracking_alert cta on ct.id = cta.customer_tracking_id
inner join wg_agent a on ct.agent_id = a.id
inner join users u on a.user_id = u.id
left join (Select * from wg_notified_alert
						where entityName = 'tracking') na on cta.id = na.entityId
where na.entityId is null and timeType = 'd' and (TIMESTAMPDIFF(DAY,NOW(),eventDateTime)) <= time and preference = 'antes'";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertBeforeWeeks()
    {
        $query = "select cta.id, u.email, ct.eventDateTime, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(WEEK,CURDATE(),eventDateTime) weeks
			, CONCAT(firstName,' ',lastName) fullName
			, c.businessName
			, ct.observation
	from
wg_customer_tracking ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_tracking_alert cta on ct.id = cta.customer_tracking_id
inner join wg_agent a on ct.agent_id = a.id
inner join users u on a.user_id = u.id
left join (Select * from wg_notified_alert
						where entityName = 'tracking') na on cta.id = na.entityId
where na.entityId is null and timeType = 'w' and (TIMESTAMPDIFF(WEEK,NOW(),eventDateTime)) <= time and preference = 'antes'";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertBeforeMonths()
    {
        $query = "select cta.id, u.email, ct.eventDateTime, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(MONTH,CURDATE(),eventDateTime) months
			, CONCAT(firstName,' ',lastName) fullName
			, c.businessName
			, ct.observation
	from
wg_customer_tracking ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_tracking_alert cta on ct.id = cta.customer_tracking_id
inner join wg_agent a on ct.agent_id = a.id
inner join users u on a.user_id = u.id
left join (Select * from wg_notified_alert
						where entityName = 'tracking') na on cta.id = na.entityId
where na.entityId is null and timeType = 'm' and (TIMESTAMPDIFF(MONTH,NOW(),eventDateTime)) <= time and preference = 'antes'";

        $results = DB::select($query);

        return $results;
    }

    //DIAGNOSTIC
    public function getAlertActionPlanDiagnosticBeforeHours()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(HOUR,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(q.description) title
FROM wg_customer_diagnostic_prevention_action_plan ap
INNER JOIN wg_customer_diagnostic_prevention dp ON ap.diagnostic_detail_id = dp.id
INNER JOIN wg_progam_prevention_question q ON q.id = dp.question_id
INNER JOIN wg_customer_diagnostic d ON dp.diagnostic_id = d.id
INNER JOIN wg_customers c ON c.id = d.customer_id
INNER JOIN wg_customer_diagnostic_prevention_action_plan_alert apa ON apa.action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_diagnostic_prevention_action_plan_resp apr ON ap.id = apr.action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_diagnostic') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'h'
  AND (TIMESTAMPDIFF(HOUR,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertActionPlanDiagnosticBeforeDays()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(DAY,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(q.description) title
FROM wg_customer_diagnostic_prevention_action_plan ap
INNER JOIN wg_customer_diagnostic_prevention dp ON ap.diagnostic_detail_id = dp.id
INNER JOIN wg_progam_prevention_question q ON q.id = dp.question_id
INNER JOIN wg_customer_diagnostic d ON dp.diagnostic_id = d.id
INNER JOIN wg_customers c ON c.id = d.customer_id
INNER JOIN wg_customer_diagnostic_prevention_action_plan_alert apa ON apa.action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_diagnostic_prevention_action_plan_resp apr ON ap.id = apr.action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_diagnostic') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'd'
  AND (TIMESTAMPDIFF(DAY,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertActionPlanDiagnosticBeforeWeeks()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(WEEK,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(q.description) title
FROM wg_customer_diagnostic_prevention_action_plan ap
INNER JOIN wg_customer_diagnostic_prevention dp ON ap.diagnostic_detail_id = dp.id
INNER JOIN wg_progam_prevention_question q ON q.id = dp.question_id
INNER JOIN wg_customer_diagnostic d ON dp.diagnostic_id = d.id
INNER JOIN wg_customers c ON c.id = d.customer_id
INNER JOIN wg_customer_diagnostic_prevention_action_plan_alert apa ON apa.action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_diagnostic_prevention_action_plan_resp apr ON ap.id = apr.action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_diagnostic') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'w'
  AND (TIMESTAMPDIFF(WEEK,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertActionPlanDiagnosticBeforeMonths()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(MONTH,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(q.description) title
FROM wg_customer_diagnostic_prevention_action_plan ap
INNER JOIN wg_customer_diagnostic_prevention dp ON ap.diagnostic_detail_id = dp.id
INNER JOIN wg_progam_prevention_question q ON q.id = dp.question_id
INNER JOIN wg_customer_diagnostic d ON dp.diagnostic_id = d.id
INNER JOIN wg_customers c ON c.id = d.customer_id
INNER JOIN wg_customer_diagnostic_prevention_action_plan_alert apa ON apa.action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_diagnostic_prevention_action_plan_resp apr ON ap.id = apr.action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_diagnostic') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'm'
  AND (TIMESTAMPDIFF(MONTH,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }


    //MANAGEMENT
    public function getAlertActionPlanManagementBeforeHours()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(HOUR,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(q.description) title
FROM wg_customer_management_detail_action_plan ap
INNER JOIN wg_customer_management_detail md ON ap.management_detail_id = md.id
INNER JOIN wg_program_management_question q ON q.id = md.question_id
INNER JOIN wg_customer_management m ON md.management_id = m.id
INNER JOIN wg_customers c ON c.id = m.customer_id
INNER JOIN wg_customer_management_detail_action_plan_alert apa ON apa.action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_management_detail_action_plan_resp apr ON ap.id = apr.action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_management') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'h'
  AND (TIMESTAMPDIFF(HOUR,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertActionPlanManagementBeforeDays()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(DAY,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(q.description) title
FROM wg_customer_management_detail_action_plan ap
INNER JOIN wg_customer_management_detail md ON ap.management_detail_id = md.id
INNER JOIN wg_program_management_question q ON q.id = md.question_id
INNER JOIN wg_customer_management m ON md.management_id = m.id
INNER JOIN wg_customers c ON c.id = m.customer_id
INNER JOIN wg_customer_management_detail_action_plan_alert apa ON apa.action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_management_detail_action_plan_resp apr ON ap.id = apr.action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_management') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'd'
  AND (TIMESTAMPDIFF(DAY,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertActionPlanManagementBeforeWeeks()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(WEEK,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(q.description) title
FROM wg_customer_management_detail_action_plan ap
INNER JOIN wg_customer_management_detail md ON ap.management_detail_id = md.id
INNER JOIN wg_program_management_question q ON q.id = md.question_id
INNER JOIN wg_customer_management m ON md.management_id = m.id
INNER JOIN wg_customers c ON c.id = m.customer_id
INNER JOIN wg_customer_management_detail_action_plan_alert apa ON apa.action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_management_detail_action_plan_resp apr ON ap.id = apr.action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_management') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'w'
  AND (TIMESTAMPDIFF(WEEK,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertActionPlanManagementBeforeMonths()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(MONTH,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(q.description) title
FROM wg_customer_management_detail_action_plan ap
INNER JOIN wg_customer_management_detail md ON ap.management_detail_id = md.id
INNER JOIN wg_program_management_question q ON q.id = md.question_id
INNER JOIN wg_customer_management m ON md.management_id = m.id
INNER JOIN wg_customers c ON c.id = m.customer_id
INNER JOIN wg_customer_management_detail_action_plan_alert apa ON apa.action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_management_detail_action_plan_resp apr ON ap.id = apr.action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_management') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'm'
  AND (TIMESTAMPDIFF(MONTH,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }


    //CONTRACT
    public function getAlertActionPlanContractBeforeHours()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(HOUR,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(CONCAT('NRO CONTRATO ( ',m.contract, ' ) - ', q.requirement)) title
FROM wg_customer_contract_detail_action_plan ap
INNER JOIN wg_customer_contract_detail cd ON ap.contract_detail_id = cd.id
INNER JOIN wg_customer_periodic_requirement q ON q.id = cd.periodic_requirement_id
INNER JOIN wg_customer_contractor m ON cd.contractor_id = m.id
INNER JOIN wg_customers c ON c.id = m.contractor_id
INNER JOIN wg_customer_contract_detail_action_plan_alert apa ON apa.contract_action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_contract_detail_action_plan_resp apr ON ap.id = apr.contract_action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_contractor') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'h'
  AND (TIMESTAMPDIFF(HOUR,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertActionPlanContractBeforeDays()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(DAY,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(CONCAT('NRO CONTRATO ( ',m.contract, ' ) - ', q.requirement)) title
FROM wg_customer_contract_detail_action_plan ap
INNER JOIN wg_customer_contract_detail cd ON ap.contract_detail_id = cd.id
INNER JOIN wg_customer_periodic_requirement q ON q.id = cd.periodic_requirement_id
INNER JOIN wg_customer_contractor m ON cd.contractor_id = m.id
INNER JOIN wg_customers c ON c.id = m.contractor_id
INNER JOIN wg_customer_contract_detail_action_plan_alert apa ON apa.contract_action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_contract_detail_action_plan_resp apr ON ap.id = apr.contract_action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_contractor') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'd'
  AND (TIMESTAMPDIFF(DAY,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertActionPlanContractBeforeWeeks()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(WEEK,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(CONCAT('NRO CONTRATO ( ',m.contract, ' ) - ', q.requirement)) title
FROM wg_customer_contract_detail_action_plan ap
INNER JOIN wg_customer_contract_detail cd ON ap.contract_detail_id = cd.id
INNER JOIN wg_customer_periodic_requirement q ON q.id = cd.periodic_requirement_id
INNER JOIN wg_customer_contractor m ON cd.contractor_id = m.id
INNER JOIN wg_customers c ON c.id = m.contractor_id
INNER JOIN wg_customer_contract_detail_action_plan_alert apa ON apa.contract_action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_contract_detail_action_plan_resp apr ON ap.id = apr.contract_action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_contractor') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'w'
  AND (TIMESTAMPDIFF(WEEK,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertActionPlanContractBeforeMonths()
    {
        $query = "SELECT apa.id,
       UPPER(CONCAT(ct.name, ' ',ct.firstName, ' ', ct.lastName)) fullName,
       TIMESTAMPDIFF(MONTH,NOW(),ap.closeDateTIme) hours,
       i.`value` email,
       UPPER(ap.description) description,
       UPPER(c.businessName) businessName,
       ap.closeDateTIme,
       UPPER(CONCAT('NRO CONTRATO ( ',m.contract, ' ) - ', q.requirement)) title
FROM wg_customer_contract_detail_action_plan ap
INNER JOIN wg_customer_contract_detail cd ON ap.contract_detail_id = cd.id
INNER JOIN wg_customer_periodic_requirement q ON q.id = cd.periodic_requirement_id
INNER JOIN wg_customer_contractor m ON cd.contractor_id = m.id
INNER JOIN wg_customers c ON c.id = m.contractor_id
INNER JOIN wg_customer_contract_detail_action_plan_alert apa ON apa.contract_action_plan_id = ap.id
INNER JOIN
  (SELECT ct.*
   FROM wg_contact ct
   INNER JOIN wg_customers c ON c.id = ct.customer_id) ct ON ct.customer_id = c.id
INNER JOIN wg_customer_contract_detail_action_plan_resp apr ON ap.id = apr.contract_action_plan_id
AND apr.contact_id = ct.id
LEFT JOIN
  ( SELECT MIN(id) id,
           entityId,
           `value`
   FROM wg_info_detail
   WHERE entityName = 'Wgroup\\\\Models\\\\Contact'
     AND TYPE = 'email'
   GROUP BY entityId ) i ON i.entityId = ct.id
LEFT JOIN
  (SELECT *
   FROM wg_notified_alert
   WHERE entityName = 'action_plan_contractor') na ON apa.id = na.entityId
WHERE na.entityId IS NULL
  AND timeType = 'm'
  AND (TIMESTAMPDIFF(MONTH,NOW(),ap.closeDateTIme)) <= TIME
  AND preference = 'antes'
  AND (i.`value` IS NOT NULL
       OR i.`value` <> '')";

        $results = DB::select($query);

        return $results;
    }
}
