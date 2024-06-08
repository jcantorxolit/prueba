<?php

namespace Wgroup\CustomerImprovementPlan;

use DB;
use Exception;
use Log;
use Str;

class CustomerImprovementPlanService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $repository;

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

        $model = new CustomerImprovementPlan();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerImprovementPlanRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_improvement_plan.id',
            'wg_customer_improvement_plan.customer_id',
            'wg_customer_improvement_plan.entityName',
            'wg_customer_improvement_plan.entityId',
            'wg_customer_improvement_plan.type',
            'wg_customer_improvement_plan.endDate',
            'wg_customer_improvement_plan.description',
            'wg_customer_improvement_plan.observation',
            'wg_customer_improvement_plan.responsible',
            'wg_customer_improvement_plan.responsibleType',
            'wg_customer_improvement_plan.status',
            'wg_customer_improvement_plan.isRequiresAnalysis'
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
                    $this->repository->sortBy($colName, $dir);
                } else {
                    $this->repository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->repository->sortBy('wg_customer_improvement_plan.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('improvement_plan_origin.item', $search);
            $filters[] = array('wg_customer_improvement_plan.classificationName', $search);
            $filters[] = array('improvement_plan_type.item', $search);
            $filters[] = array('improvement_plan_status.item', $search);
            $filters[] = array('wg_customer_improvement_plan.description', $search);
            $filters[] = array('wg_customer_improvement_plan.endDate', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
        }

        /*if ($typeFilter == "1") {
            $filters[] = array('wg_customer_improvement_plan.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_improvement_plan.status', '0');
        }*/

        $this->repository->setColumns(['wg_customer_improvement_plan.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId = 0, $audit = null)
    {

        $model = new CustomerImprovementPlan();
        $this->repository = new CustomerImprovementPlanRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('improvement_plan_origin.item', $search);
            $filters[] = array('improvement_plan_type.item', $search);
            $filters[] = array('improvement_plan_status.item', $search);
            $filters[] = array('wg_customer_improvement_plan.description', $search);
            $filters[] = array('wg_customer_improvement_plan.endDate', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }

    public function getAllByEntity($search, $perPage = 10, $currentPage = 0, $entityId = 0, $entityType = '', $audit = null)
    {

        $model = new CustomerImprovementPlan();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerImprovementPlanRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_improvement_plan.id',
            'wg_customer_improvement_plan.customer_id',
            'wg_customer_improvement_plan.entityName',
            'wg_customer_improvement_plan.entityId',
            'wg_customer_improvement_plan.type',
            'wg_customer_improvement_plan.endDate',
            'wg_customer_improvement_plan.description',
            'wg_customer_improvement_plan.observation',
            'wg_customer_improvement_plan.responsible',
            'wg_customer_improvement_plan.responsibleType',
            'wg_customer_improvement_plan.status',
            'wg_customer_improvement_plan.isRequiresAnalysis'
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
                    $this->repository->sortBy($colName, $dir);
                } else {
                    $this->repository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->repository->sortBy('wg_customer_improvement_plan.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan.entityId', $entityId);
        $filters[] = array('wg_customer_improvement_plan.entityName', $entityType);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('improvement_plan_origin.item', $search);
            $filters[] = array('wg_customer_improvement_plan.classificationName', $search);
            $filters[] = array('improvement_plan_type.item', $search);
            $filters[] = array('improvement_plan_status.status', $search);
            $filters[] = array('wg_customer_improvement_plan.description', $search);
            $filters[] = array('wg_customer_improvement_plan.endDate', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
        }

        /*if ($typeFilter == "1") {
            $filters[] = array('wg_customer_improvement_plan.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_improvement_plan.status', '0');
        }*/

        $this->repository->setColumns(['wg_customer_improvement_plan.*']);

        return $this->repository->getFilteredOptionalEntity($filters, false, "");
    }

    public function getCountEntity($search = "", $entityId = 0, $entityType, $audit = null)
    {

        $model = new CustomerImprovementPlan();
        $this->repository = new CustomerImprovementPlanRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_improvement_plan.entityId', $entityId);
        $filters[] = array('wg_customer_improvement_plan.entityName', $entityType);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('improvement_plan_origin.item', $search);
            $filters[] = array('improvement_plan_type.item', $search);
            $filters[] = array('improvement_plan_status.status', $search);
            $filters[] = array('wg_customer_improvement_plan.description', $search);
            $filters[] = array('wg_customer_improvement_plan.endDate', $search);
            $filters[] = array('responsible.name', $search);
            $filters[] = array('responsible.type', $search);
        }

        $this->repository->setColumns(['wg_customer_improvement_plan.*']);

        return $this->repository->getFilteredOptionalEntity($filters, true, "");
    }


    public function getAlertBeforeHours()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, ct.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(HOUR,NOW(),endDate) hours
			, responsible.name fullName
			, ct.description
			, ct.responsible
			, ct.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON ct.responsible = responsible.id AND ct.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan') na on cta.id = na.entityId
where na.entityId is null and timeType = 'h' and (TIMESTAMPDIFF(HOUR,NOW(),endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertBeforeDays()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, ct.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(DAY,NOW(),endDate) days
			, responsible.name fullName
			, ct.description
			, ct.responsible
			, ct.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON ct.responsible = responsible.id AND ct.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan') na on cta.id = na.entityId
where na.entityId is null and timeType = 'd' and (TIMESTAMPDIFF(DAY,NOW(),endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertBeforeWeeks()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, ct.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(WEEK,NOW(),endDate) weeks
			, responsible.name fullName
			, ct.description
			, ct.responsible
			, ct.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON ct.responsible = responsible.id AND ct.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan') na on cta.id = na.entityId
where na.entityId is null and timeType = 'w' and (TIMESTAMPDIFF(WEEK,NOW(),endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getAlertBeforeMonths()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, ct.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(MONTH,NOW(),endDate) months
			, responsible.name fullName
			, ct.description
			, ct.responsible
			, ct.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON ct.responsible = responsible.id AND ct.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan') na on cta.id = na.entityId
where na.entityId is null and timeType = 'm' and (TIMESTAMPDIFF(MONTH,NOW(),endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }



    public function getTrackingAlertBeforeHours()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.startDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(HOUR,NOW(),t.startDate) hours
			, responsible.name fullName
			, ct.description
			, t.observation
			, t.responsible
			, t.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_tracking t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON t.responsible = responsible.id AND t.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_tracking') na on cta.id = na.entityId
where na.entityId is null and timeType = 'h' and (TIMESTAMPDIFF(HOUR,NOW(),t.startDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getTrackingAlertBeforeDays()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.startDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(DAY,NOW(),t.startDate) days
			, responsible.name fullName
			, ct.description
			, t.observation
			, t.responsible
			, t.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_tracking t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON t.responsible = responsible.id AND t.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_tracking') na on cta.id = na.entityId
where na.entityId is null and timeType = 'd' and (TIMESTAMPDIFF(DAY,NOW(),t.startDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getTrackingAlertBeforeWeeks()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.startDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(WEEK,NOW(),t.startDate) weeks
			, responsible.name fullName
			, ct.description
			, t.observation
			, t.responsible
			, t.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_tracking t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON t.responsible = responsible.id AND t.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_tracking') na on cta.id = na.entityId
where na.entityId is null and timeType = 'w' and (TIMESTAMPDIFF(WEEK,NOW(),t.startDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getTrackingAlertBeforeMonths()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.startDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(MONTH,NOW(),t.startDate) months
			, responsible.name fullName
			, ct.description
			, t.observation
			, t.responsible
			, t.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_tracking t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON t.responsible = responsible.id AND t.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_tracking') na on cta.id = na.entityId
where na.entityId is null and timeType = 'm' and (TIMESTAMPDIFF(MONTH,NOW(),t.startDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }



    public function getActionPlanAlertBeforeHours()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(HOUR,NOW(),t.endDate) hours
			, responsible.name fullName
			, ct.description
			, t.activity
			, t.responsible
			, t.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON t.responsible = responsible.id AND t.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan') na on cta.id = na.entityId
where na.entityId is null and timeType = 'h' and (TIMESTAMPDIFF(HOUR,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getActionPlanAlertBeforeDays()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(DAY,NOW(),t.endDate) days
			, responsible.name fullName
			, ct.description
			, t.activity
			, t.responsible
			, t.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON t.responsible = responsible.id AND t.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan') na on cta.id = na.entityId
where na.entityId is null and timeType = 'd' and (TIMESTAMPDIFF(DAY,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getActionPlanAlertBeforeWeeks()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(WEEK,NOW(),t.endDate) weeks
			, responsible.name fullName
			, ct.description
			, t.activity
			, t.responsible
			, t.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON t.responsible = responsible.id AND t.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan') na on cta.id = na.entityId
where na.entityId is null and timeType = 'w' and (TIMESTAMPDIFF(WEEK,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getActionPlanAlertBeforeMonths()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(MONTH,NOW(),t.endDate) months
			, responsible.name fullName
			, ct.description
			, t.activity
			, t.responsible
			, t.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON t.responsible = responsible.id AND t.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan') na on cta.id = na.entityId
where na.entityId is null and timeType = 'm' and (TIMESTAMPDIFF(MONTH,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }



    public function getActionPlanNotificationAlertBeforeHours()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(HOUR,NOW(),t.endDate) hours
			, responsible.name fullName
			, ct.description
			, t.activity
			, tn.responsible
			, tn.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_action_plan_notified tn on t.id = tn.customer_improvement_plan_action_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON tn.responsible = responsible.id AND tn.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan_notification') na on cta.id = na.entityId
where na.entityId is null and timeType = 'h' and (TIMESTAMPDIFF(HOUR,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getActionPlanNotificationAlertBeforeDays()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(DAY,NOW(),t.endDate) days
			, responsible.name fullName
			, ct.description
			, t.activity
			, tn.responsible
			, tn.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_action_plan_notified tn on t.id = tn.customer_improvement_plan_action_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON tn.responsible = responsible.id AND tn.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan_notification') na on cta.id = na.entityId
where na.entityId is null and timeType = 'd' and (TIMESTAMPDIFF(DAY,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getActionPlanNotificationAlertBeforeWeeks()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(WEEK,NOW(),t.endDate) weeks
			, responsible.name fullName
			, ct.description
			, t.activity
			, tn.responsible
			, tn.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_action_plan_notified tn on t.id = tn.customer_improvement_plan_action_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON tn.responsible = responsible.id AND tn.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan_notification') na on cta.id = na.entityId
where na.entityId is null and timeType = 'w' and (TIMESTAMPDIFF(WEEK,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getActionPlanNotificationAlertBeforeMonths()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, t.endDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(MONTH,NOW(),t.endDate) months
			, responsible.name fullName
			, ct.description
			, t.activity
			, tn.responsible
			, tn.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_action_plan_notified tn on t.id = tn.customer_improvement_plan_action_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON tn.responsible = responsible.id AND tn.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan_notification') na on cta.id = na.entityId
where na.entityId is null and timeType = 'm' and (TIMESTAMPDIFF(MONTH,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }



    public function getActionPlanTaskAlertBeforeHours()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, tn.startDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(HOUR,NOW(),tn.startDate) hours
			, responsible.name fullName
            , ct.description as descriptionPlan
            , t.activity
            , tn.description
			, tn.responsible
			, tn.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_action_plan_task tn on t.id = tn.customer_improvement_plan_action_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON tn.responsible = responsible.id AND tn.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan_task') na on cta.id = na.entityId
where na.entityId is null and timeType = 'h' and (TIMESTAMPDIFF(HOUR,NOW(),tn.startDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getActionPlanTaskAlertBeforeDays()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, tn.startDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(DAY,NOW(),tn.startDate) days
			, responsible.name fullName
            , ct.description as descriptionPlan
            , t.activity
            , tn.description
			, tn.responsible
			, tn.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_action_plan_task tn on t.id = tn.customer_improvement_plan_action_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON tn.responsible = responsible.id AND tn.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan_task') na on cta.id = na.entityId
where na.entityId is null and timeType = 'd' and (TIMESTAMPDIFF(DAY,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getActionPlanTaskAlertBeforeWeeks()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, tn.startDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(WEEK,NOW(),tn.startDate) weeks
			, responsible.name fullName
            , ct.description as descriptionPlan
            , t.activity
            , tn.description
			, tn.responsible
			, tn.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_action_plan_task tn on t.id = tn.customer_improvement_plan_action_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON tn.responsible = responsible.id AND tn.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan_task') na on cta.id = na.entityId
where na.entityId is null and timeType = 'w' and (TIMESTAMPDIFF(WEEK,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }

    public function getActionPlanTaskAlertBeforeMonths()
    {
        $query = "select
			improvement_plan_origin.item as module
			, c.businessName
			, cta.id, responsible.email, tn.startDate, cta.time, cta.preference, cta.timeType, cta.type
			, TIMESTAMPDIFF(MONTH,NOW(),tn.startDate) months
			, responsible.name fullName
            , ct.description as descriptionPlan
            , t.activity
            , tn.description
			, tn.responsible
			, tn.responsibleType
	from
wg_customer_improvement_plan ct
inner join wg_customers c on c.id = ct.customer_id
inner join wg_customer_improvement_plan_action_plan t on ct.id = t.customer_improvement_plan_id
inner join wg_customer_improvement_plan_action_plan_task tn on t.id = tn.customer_improvement_plan_action_plan_id
inner join wg_customer_improvement_plan_alert cta on ct.id = cta.customer_improvement_plan_id
INNER JOIN ( SELECT DISTINCT * FROM ( SELECT a.id, ca.customer_id, a.`name`, 'Asesor' type, u.email COLLATE utf8_general_ci email FROM wg_agent a
		INNER JOIN wg_customer_agent ca ON a.id = ca.agent_id
		LEFT JOIN users u on u.id = a.user_id
		UNION ALL
        SELECT c.id, c.customer_id, CONCAT_WS(' ',  users.name, IFNULL(users.surname, '')) AS fullName, 'Cliente Usuario' type, users.email FROM wg_customer_user c
        INNER JOIN users ON users.id = c.user_id) p ) responsible
ON tn.responsible = responsible.id AND tn.responsibleType = responsible.type AND ct.customer_id = responsible.customer_id
INNER JOIN (select * from system_parameters where `group` = 'improvement_plan_origin') improvement_plan_origin ON improvement_plan_origin.value COLLATE utf8_general_ci = ct.entityName
left join (Select * from wg_notified_alert_improvement_plan
						where entityName = 'improvement_plan_action_plan_task') na on cta.id = na.entityId
where na.entityId is null and timeType = 'm' and (TIMESTAMPDIFF(MONTH,NOW(),t.endDate)) <= time and preference = 'antes'
LIMIT 10";

        $results = DB::select($query);

        return $results;
    }


}
