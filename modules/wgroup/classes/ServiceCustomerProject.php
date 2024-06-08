<?php

namespace Wgroup\Classes;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerManagement;
use Wgroup\Models\CustomerManagementRepository;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;
use Carbon\Carbon;

class ServiceCustomerProject {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerProjectRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId) {

        $model = new CustomerProject();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerProjectRepository = new CustomerProjectRepository($model);

        if ($perPage > 0) {
            $this->customerProjectRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_project.customer_id',
            'wg_customer_project.status',
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
                    $this->customerProjectRepository->sortBy($colName, $dir);
                } else {
                    $this->customerProjectRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerProjectRepository->sortBy('wg_customer_project.id', 'desc');
        }

        $filters = array();

        //$filters[] = array('wg_customer_project.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_project.status', $search);
            //$filters[] = array('wg_agent.name', $search);
            //$filters[] = array('diags.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_project.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_project.status', '0');
        }


        $this->customerProjectRepository->setColumns(['wg_customer_project.*']);

        return $this->customerProjectRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllSettingBy($sorting = array(), $agentId = 0, $customerId = 0, $month = 0, $year = 0, $arl = 0, $os = "-1") {

        $query = "Select a.id, a.availability availabilityHours
                        , SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0)) assignedHours
                        , SUM(ROUND(IFNULL(pat.planeadas, 0), 0) + ROUND(IFNULL(pat.ejecutadas, 0), 0)) scheduledHours, SUM(ROUND(IFNULL(pat.ejecutadas, 0), 0)) runningHours
                    from wg_agent a
                    LEFT JOIN (SELECT p.id, pa.agent_id, pa.id project_agent_id, patp.planeadas, pate.ejecutadas, pa.estimatedHours FROM wg_customer_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_project_agent pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id
                WHERE MONTH(p.deliveryDate) =  MONTH(NOW()) and YEAR(p.deliveryDate) =  $year) pat on pat.agent_id = a.id
                WHERE pat.agent_id = :agent_id
                GROUP BY pat.agent_id";

        $dt = Carbon::now('America/Bogota');

        $currentMonth = $dt->month;
        $currentYear = $dt->year;

        if ($month != 0) {
            $currentMonth = $month;
        }

        if ($year != 0) {
            $currentYear = $year;
        }

        if ($agentId != 0) {
            $field = "a.availability - SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0))";
        } else if ($customerId != 0) {
            $field = "SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0))";
        } else {
            //$field = "SUM(a.availability) - SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0))";
            $field = DB::table('wg_agent')->sum('availability');

            $field .= " - SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0))";
        }

        $query = "Select $field availabilityHours
				, SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0)) assignedHours
				, SUM(ROUND(IFNULL(pat.planeadas, 0), 0) + ROUND(IFNULL(pat.ejecutadas, 0), 0)) scheduledHours, SUM(ROUND(IFNULL(pat.ejecutadas, 0), 0)) runningHours
		from wg_agent a
		LEFT JOIN (SELECT c.id customer_id, p.id, pa.agent_id, pa.id project_agent_id, patp.planeadas, pate.ejecutadas, pa.estimatedHours FROM wg_customer_project p
		inner join wg_customers c on p.customer_id = c.id
		inner join wg_customer_project_agent pa on p.id = pa.project_id
		left join (
														select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
														from wg_customer_project_agent_task pat
														where `status` = 'activo'
														group by pat.project_agent_id
												) patp On pa.id = patp.project_agent_id
		left join (
														select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
														from wg_customer_project_agent_task pat
														where `status` = 'inactivo'
														group by pat.project_agent_id
												) pate on pa.id = pate.project_agent_id
WHERE MONTH(p.deliveryDate) =  $currentMonth and YEAR(p.deliveryDate) =  $currentYear) pat on pat.agent_id = a.id";

        //Log::info($agentId);

        $where = " ";

        $whereArray = array();

        if ($agentId != 0) {
            $where .= " WHERE pat.agent_id = $agentId";
            //Log::info($agentId);
            //Log::info($where);
            $whereArray["agent_id"] = $agentId;
        }

        if ($customerId != 0) {
            if (empty($where)) {
                $where .= " WHERE pat.customer_id = $customerId";
            } else {
                $where .= " AND pat.customer_id = $customerId";
            }
            $whereArray["customer_id"] = $customerId;
        }
        /*
                if ($month != 0) {
                    $where .= " AND MONTH(p.deliveryDate) = :month";
                    $whereArray["month"] = $month;
                } else {
                    $where .= " AND MONTH(p.deliveryDate) =  MONTH(NOW())";
                }
        */


        $sql = $query.$where;

        //Log::info($query);
        //Log::info($agentId);
        /*$results = DB::select( $query, array(
            'agent_id' => $agentId,
        ));*/

        $results = DB::select( $sql);
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSettingByAgent($sorting = array(), $agentId = 0, $month = 0, $year = 0, $arl = 0, $os = "-1") {


        $query = "Select a.availability availabilityHours
                        , SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0)) assignedHours
                        , SUM(ROUND(IFNULL(pat.planeadas, 0), 0) + ROUND(IFNULL(pat.ejecutadas, 0), 0)) scheduledHours, SUM(ROUND(IFNULL(pat.ejecutadas, 0), 0)) runningHours
                    from wg_agent a
                    LEFT JOIN (SELECT p.id, pa.agent_id, pa.id project_agent_id, patp.planeadas, pate.ejecutadas, pa.estimatedHours FROM wg_customer_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_project_agent pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id
                WHERE MONTH(p.deliveryDate) =  :monthValue and YEAR(p.deliveryDate) =  :yearValue) pat on pat.agent_id = a.id
                WHERE pat.agent_id = :agent_id
                GROUP BY pat.agent_id";

        $dt = Carbon::now('America/Bogota');

        $currentMonth = $dt->month;
        $currentYear = $dt->year;

        if ($month != 0) {
            $currentMonth = $month;
        }

        if ($year != 0) {
            $currentYear = $year;
        }

        //Log::info($query);
        //Log::info($agentId);
        $results = DB::select( $query, array(
            'agent_id' => $agentId,
            'monthValue' => $currentMonth,
            'yearValue' => $currentYear
        ));


        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSettingByCustomerId($sorting = array(), $customerId = 0, $os = "-1") {


        $query = "Select a.availability availabilityHours
                        , SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0)) assignedHours
                        , SUM(ROUND(IFNULL(pat.planeadas, 0), 0) + ROUND(IFNULL(pat.ejecutadas, 0), 0)) scheduledHours, SUM(ROUND(IFNULL(pat.ejecutadas, 0), 0)) runningHours
                    from wg_agent a
                    LEFT JOIN (SELECT p.id, pa.agent_id, c.id customer_id, pa.id project_agent_id, patp.planeadas, pate.ejecutadas, pa.estimatedHours FROM wg_customer_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_project_agent pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id
                WHERE MONTH(p.deliveryDate) =  MONTH(NOW()) and YEAR(p.deliveryDate) =  YEAR(NOW())) pat on pat.agent_id = a.id
                WHERE pat.customer_id = :agent_id
                GROUP BY pat.customer_id";

        //Log::info($query);
        //Log::info($customerId);
        $results = DB::select( $query, array(
            'agent_id' => $customerId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSummaryByStatus($agentId = 0, $customerId = 0, $month = 0, $year = 0, $arl = '', $os = "-1") {

        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, p.type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        , a.name agentName
                        , p.serviceOrder
                        , u.email
                    from wg_customer_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_project_agent pa on p.id = pa.project_id
                    inner join wg_agent a on pa.agent_id = a.id
                    inner join users u on a.user_id = u.id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id ";
        //Log::info($query);
        //Log::info($agentId);

        $dt = Carbon::now('America/Bogota');

        $currentYear = $dt->year;

        if ($year != 0) {
            $currentYear = $year;
        }

        $where = "  WHERE YEAR(p.deliveryDate) =  $currentYear ";

        $whereArray = array();

        if ($agentId != 0) {
            $where .= " AND pa.agent_id = :agent_id";
            $whereArray["agent_id"] = $agentId;
        }

        if ($customerId != 0) {
            $where .= " AND c.id = :customer_id";
            $whereArray["customer_id"] = $customerId;
        }

        if ($month != 0) {
            $where .= " AND MONTH(p.deliveryDate) = :month";
            $whereArray["month"] = $month;
        } else {
            $where .= " AND MONTH(p.deliveryDate) =  MONTH(NOW())";
        }

        if ($arl != '') {
            $where .= " AND c.arl = :arl";
            $whereArray["arl"] = $arl;
        }

        if ($os != "-1" && $os != '') {
            $where .= " AND p.serviceOrder = :os";
            $whereArray["os"] = $os;
        }

        $sql = $query.$where;

        $results = DB::select($sql, $whereArray);

        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSummaryBy($sorts, $agentId = 0, $customerId = 0, $month = 0, $year = 0, $arl = '', $os = "-1", $type = '', $isBilled = '') {

        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, p.type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        , a.name agentName
                        , p.serviceOrder
                        , u.email
                        , p.isBilled
                        , p.invoiceNumber
                        , project_type.item typeDescription
                    from wg_customer_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_project_agent pa on p.id = pa.project_id
                    inner join wg_agent a on pa.agent_id = a.id
                    inner join users u on a.user_id = u.id
                    left join (select * from system_parameters where `group` = 'project_type') project_type on project_type.value = p.type
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id ";
        //Log::info($query);
        //Log::info($agentId);

        $dt = Carbon::now('America/Bogota');

        $currentYear = $dt->year;

        if ($year != 0) {
            $currentYear = $year;
        }

        $where = "  WHERE YEAR(p.deliveryDate) =  $currentYear ";

        $whereArray = array();

        if ($agentId != 0) {
            $where .= " AND pa.agent_id = :agent_id";
            $whereArray["agent_id"] = $agentId;
        }

        if ($customerId != 0) {
            $where .= " AND c.id = :customer_id";
            $whereArray["customer_id"] = $customerId;
        }

        if ($month != 0) {
            $where .= " AND MONTH(p.deliveryDate) = :month";
            $whereArray["month"] = $month;
        } else {
            $where .= " AND MONTH(p.deliveryDate) =  MONTH(NOW())";
        }

        if ($arl != '') {
            $where .= " AND c.arl = :arl";
            $whereArray["arl"] = $arl;
        }

        if ($type != '') {
            $where .= " AND p.type = :type";
            $whereArray["type"] = $type;
        }

        if ($os != "-1" && $os != '') {
            $where .= " AND p.serviceOrder = :os";
            $whereArray["os"] = $os;
        }

        if ($isBilled != '') {
            $where .= " AND (p.isBilled IS NULL OR p.isBilled = :isBilled)";
            $whereArray["isBilled"] = $isBilled;
        }

        $sql = $query.$where;

        $results = DB::select($sql, $whereArray);

        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSummaryByBilling($sorts, $agentId = 0, $customerId = 0, $month = 0, $year = 0, $arl = 0, $os = "-1", $isBilled = '') {

        $query = "SELECT * FROM (
Select p.id, p.customer_id, c.businessName customerName, c.arl, p.description, p.name
	, p.type
	, SUM(p.estimatedHours) estimatedHours
	, SUM(pa.estimatedHours) assignedHours
	, SUM(ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0)) scheduledHours
	, SUM(ROUND(IFNULL(pate.ejecutadas, 0), 0)) runningHours
	, p.serviceOrder
	, p.isBilled
	, p.invoiceNumber
    , p.deliveryDate
from wg_customer_project p
inner join wg_customers c on p.customer_id = c.id
inner join wg_customer_project_agent pa on p.id = pa.project_id
inner join wg_agent a on pa.agent_id = a.id
inner join users u on a.user_id = u.id
left join (select * from system_parameters where `group` = 'project_type') project_type on project_type.value = p.type
left join (
												select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
												from wg_customer_project_agent_task pat
												where `status` = 'activo'
												group by pat.project_agent_id
										) patp On pa.id = patp.project_agent_id
left join (
												select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
												from wg_customer_project_agent_task pat
												where `status` = 'inactivo'
												group by pat.project_agent_id
										) pate on pa.id = pate.project_agent_id
GROUP BY p.id) p";
        //Log::info($query);
        //Log::info($agentId);

        $dt = Carbon::now('America/Bogota');

        $currentYear = $dt->year;

        if ($year != 0) {
            $currentYear = $year;
        }

        $where = "  WHERE YEAR(p.deliveryDate) =  $currentYear ";

        $whereArray = array();

        if ($agentId != '' && $agentId != '0') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.id in (SELECT project_id FROM `wg_customer_project_agent` WHERE `wg_customer_project_agent`.`agent_id` = '$agentId')";
        }

        if ($customerId != 0) {
            $where .= " AND p.customer_id = :customer_id";
            $whereArray["customer_id"] = $customerId;
        }

        if ($month != 0) {
            $where .= " AND MONTH(p.deliveryDate) = :month";
            $whereArray["month"] = $month;
        } else {
            $where .= " AND MONTH(p.deliveryDate) =  MONTH(NOW())";
        }

        if ($arl != 0) {
            $where .= " AND p.arl = :arl";
            $whereArray["arl"] = $arl;
        }

        if ($os != "-1" && $os != '') {
            $where .= " AND p.serviceOrder = :os";
            $whereArray["os"] = $os;
        }

        if ($isBilled != '') {
            $where .= " AND (p.isBilled IS NULL OR p.isBilled = :isBilled)";
            $whereArray["isBilled"] = $isBilled;
        }

        $sql = $query.$where;

        $results = DB::select($sql, $whereArray);

        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllGanttEconomicGroup($sorting = array(), $agentId = 0, $customerId = 0, $month = 0, $year = 0)
    {

        $query = "select * from (
select eg.parent_id originalId, CONCAT('G-', eg.parent_id) id, null parentId, c.businessName
, MIN(cp.created_at) startDate, MAX(cp.deliveryDate) endDateTime, 1 type, 1 expanded, 1 summary
, 'GRUPO' classification
, h.value assignedHours
, ROUND(SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0) scheduledHours
, ROUND(SUM(IFNULL(pate.ejecutadas, 0)), 0) runningHours
, ROUND(IFNULL(SUM(IFNULL(pate.ejecutadas, 0)) / SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0), 2) percentage
, ROUND(SUM(IFNULL(patp.amount, 0) + IFNULL(pate.amount, 0)), 0) amount
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.parent_id
left join wg_customer_project cp on cp.customer_id = eg.customer_id
left join wg_customer_project_agent pa on pa.project_id = cp.id
left join (select * from wg_customer_parameter where wg_customer_parameter.`group` = 'economicGroupAssignedHours') h on h.customer_id = eg.parent_id
left join (
							select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
							, SUM(ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60), 0), 0) * IFNULL(ptt.price,0))  amount
							from wg_customer_project_agent_task pat
							left join wg_project_task_type ptt on ptt.`code` = pat.type
							where `status` = 'activo'
							GROUP BY project_agent_id
					) patp On pa.id = patp.project_agent_id
left join (
							select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
							, SUM(ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60), 0), 0) * IFNULL(ptt.price,0))  amount
							from wg_customer_project_agent_task pat
							left join wg_project_task_type ptt on ptt.`code` = pat.type
							where `status` = 'inactivo'
							GROUP BY project_agent_id
					) pate on pa.id = pate.project_agent_id
where eg.parent_id = :customer_id_1 AND (:month_1 = 0 OR MONTH(cp.deliveryDate) = :month_2) AND (:year_1 = 0 OR YEAR(cp.deliveryDate) = :year_2)  AND (:agent_id_1 = 0 OR pa.agent_id = :agent_id_2)
group by eg.parent_id

UNION ALL

select c.id originalId, CONCAT('C-', c.id) id, CONCAT('G-',eg.parent_id) parentId, c.businessName, MIN(cp.created_at) startDateTime
, MAX(cp.deliveryDate) endDateTime, 1 type, 1 expanded, 1 summary
, 'CLIENTE GRUPO' classification
, SUM(cp.estimatedHours) estimatedHours
, ROUND(SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0) scheduledHours
, ROUND(SUM(IFNULL(pate.ejecutadas, 0)), 0) runningHours
, ROUND(IFNULL(SUM(IFNULL(pate.ejecutadas, 0)) / SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0), 2) percentage
, ROUND(SUM(IFNULL(patp.amount, 0) + IFNULL(pate.amount, 0)), 0) amount
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.customer_id
left join wg_customer_project cp on cp.customer_id = eg.customer_id
left join wg_customer_project_agent pa on pa.project_id = cp.id
left join (
							select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
							, SUM(ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60), 0), 0) * IFNULL(ptt.price,0))  amount
							from wg_customer_project_agent_task pat
							left join wg_project_task_type ptt on ptt.`code` = pat.type
							where `status` = 'activo'
							GROUP BY project_agent_id
					) patp On pa.id = patp.project_agent_id
left join (
							select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
							, SUM(ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60), 0), 0) * IFNULL(ptt.price,0))  amount
							from wg_customer_project_agent_task pat
							left join wg_project_task_type ptt on ptt.`code` = pat.type
							where `status` = 'inactivo'
							GROUP BY project_agent_id
					) pate on pa.id = pate.project_agent_id
where eg.parent_id = :customer_id_2 AND (:month_3 = 0 OR MONTH(cp.deliveryDate) = :month_4) AND (:year_3 = 0 OR YEAR(cp.deliveryDate) = :year_4)  AND (:agent_id_3 = 0 OR pa.agent_id = :agent_id_4)
group by eg.customer_id

union ALL

select cp.id originalId, CONCAT('P-', cp.id) id, CONCAT('C-',cp.customer_id) parentId, cp.`name`, cp.created_at, cp.deliveryDate, 1 type, 0 expanded, 1 summary
, 'PROYECTO' classification
, cp.estimatedHours
, ROUND(SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0) scheduledHours
, ROUND(SUM(IFNULL(pate.ejecutadas, 0)), 0) runningHours
, ROUND(IFNULL(SUM(IFNULL(pate.ejecutadas, 0)) / SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0), 2) percentage
, ROUND(SUM(IFNULL(patp.amount, 0) + IFNULL(pate.amount, 0)), 0) amount
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.customer_id
inner join wg_customer_project cp on cp.customer_id = eg.customer_id
inner join wg_customer_project_agent pa on pa.project_id = cp.id
left join (
							select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
							, SUM(ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60), 0), 0) * IFNULL(ptt.price,0))  amount
							from wg_customer_project_agent_task pat
							left join wg_project_task_type ptt on ptt.`code` = pat.type
							where `status` = 'activo'
							GROUP BY project_agent_id
					) patp On pa.id = patp.project_agent_id
left join (
							select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
							, SUM(ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60), 0), 0) * IFNULL(ptt.price,0))  amount
							from wg_customer_project_agent_task pat
							left join wg_project_task_type ptt on ptt.`code` = pat.type
							where `status` = 'inactivo'
							GROUP BY project_agent_id
					) pate on pa.id = pate.project_agent_id
where eg.parent_id = :customer_id_3 AND (:month_5 = 0 OR MONTH(cp.deliveryDate) = :month_6) AND (:year_5 = 0 OR YEAR(cp.deliveryDate) = :year_6)  AND (:agent_id_5 = 0 OR pa.agent_id = :agent_id_6)
group by cp.id

union ALL

select pt.id originalId, CONCAT('T-', pt.id) id, CONCAT('P-',cp.id) parentId, pt.task COLLATE utf8_general_ci, pt.startDateTime
, pt.endDateTime, 1 type, 1 expanded, 0 summary
, UPPER(IFNULL(ptt.description,'TAREA SIN TIPO')) classification
, 0 estimatedHours
, CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) end scheduledHours
, CASE WHEN pt.`status` = 'inactivo' then ROUND(IFNULL((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60, 0), 0) ELSE 0 end runningHours
, ROUND(IFNULL((CASE WHEN pt.`status` = 'inactivo' then ROUND(IFNULL((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60, 0), 0) ELSE 0 end
		/ CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) end), 0), 2)  percentage
, CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) * IFNULL(ptt.price,0) end amount
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.customer_id
inner join wg_customer_project cp on cp.customer_id = eg.customer_id
inner join wg_customer_project_agent pa on pa.project_id = cp.id
inner join wg_customer_project_agent_task pt on pt.project_agent_id = pa.id
left join wg_project_task_type ptt on ptt.`code` = pt.type
where eg.parent_id = :customer_id_4 AND (:month_7 = 0 OR MONTH(cp.deliveryDate) = :month_8) AND (:year_7 = 0 OR YEAR(cp.deliveryDate) = :year_8) AND (:agent_id_7 = 0 OR pa.agent_id = :agent_id_8)
AND (pt.`status` = 'activo' OR pt.`status` = 'inactivo')

 ) p";

        //Log::info($query);
        //Log::info($agentId);

        $dt = Carbon::now('America/Bogota');

        $currentYear = $dt->year;

        if ($year != 0) {
            $currentYear = $year;
        }

        //$where = "  WHERE YEAR(p.deliveryDate) =  $currentYear ";

        $whereArray = array();

        $whereArray["customer_id_1"] = $customerId;
        $whereArray["customer_id_2"] = $customerId;
        $whereArray["customer_id_3"] = $customerId;
        $whereArray["customer_id_4"] = $customerId;

        $whereArray["month_1"] = $month;
        $whereArray["month_2"] = $month;
        $whereArray["month_3"] = $month;
        $whereArray["month_4"] = $month;
        $whereArray["month_5"] = $month;
        $whereArray["month_6"] = $month;
        $whereArray["month_7"] = $month;
        $whereArray["month_8"] = $month;

        $whereArray["year_1"] = $year;
        $whereArray["year_2"] = $year;
        $whereArray["year_3"] = $year;
        $whereArray["year_4"] = $year;
        $whereArray["year_5"] = $year;
        $whereArray["year_6"] = $year;
        $whereArray["year_7"] = $year;
        $whereArray["year_8"] = $year;

        $whereArray["agent_id_1"] = $agentId;
        $whereArray["agent_id_2"] = $agentId;
        $whereArray["agent_id_3"] = $agentId;
        $whereArray["agent_id_4"] = $agentId;
        $whereArray["agent_id_5"] = $agentId;
        $whereArray["agent_id_6"] = $agentId;
        $whereArray["agent_id_7"] = $agentId;
        $whereArray["agent_id_8"] = $agentId;

        $results = DB::select($query, $whereArray);

        return $results;
    }

    public function getAllGanttEconomicGroupResource($sorting = array(), $agentId = 0, $customerId = 0, $month = 0, $year = 0)
    {
        //$customerId = 6;
        $query = "select * from (
select DISTINCT a.id ID, a.`name` `Name`, '#f44336' Color
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.customer_id
inner join wg_customer_project cp on cp.customer_id = eg.customer_id
inner join wg_customer_project_agent pa on pa.project_id = cp.id
inner join wg_agent a on pa.agent_id = a.id
where eg.parent_id = :customer_id_1 AND (:month_1 = 0 OR MONTH(cp.deliveryDate) = :month_2) AND (:year_1 = 0 OR YEAR(cp.deliveryDate) = :year_2)

 ) p";

        //Log::info($query);
        //Log::info($agentId);

        $dt = Carbon::now('America/Bogota');

        $currentYear = $dt->year;

        if ($year != 0) {
            $currentYear = $year;
        }

        //$where = "  WHERE YEAR(p.deliveryDate) =  $currentYear ";

        $whereArray = array();

        $whereArray["customer_id_1"] = $customerId;


        $whereArray["month_1"] = $month;
        $whereArray["month_2"] = $month;


        $whereArray["year_1"] = $year;
        $whereArray["year_2"] = $year;


        $results = DB::select($query, $whereArray);

        return $results;
    }

    public function getAllGanttEconomicGroupResourceAssignment($sorting = array(), $agentId = 0, $customerId = 0, $month = 0, $year = 0)
    {

        $query = "select * from (
select cp.id ID, CONCAT('P-', cp.id) TaskID, a.id ResourceID, 1 Units
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.customer_id
inner join wg_customer_project cp on cp.customer_id = eg.customer_id
inner join wg_customer_project_agent pa on pa.project_id = cp.id
inner join wg_agent a on pa.agent_id = a.id
where eg.parent_id = :customer_id_1 AND (:month_1 = 0 OR MONTH(cp.deliveryDate) = :month_2) AND (:year_1 = 0 OR YEAR(cp.deliveryDate) = :year_2)
group by cp.id

union ALL

select pt.id ID, CONCAT('T-', pt.id) TaskID, a.id ResourceID, 1 Units
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.customer_id
inner join wg_customer_project cp on cp.customer_id = eg.customer_id
inner join wg_customer_project_agent pa on pa.project_id = cp.id
inner join wg_agent a on pa.agent_id = a.id
inner join wg_customer_project_agent_task pt on pt.project_agent_id = pa.id
where eg.parent_id = :customer_id_2 AND (:month_3 = 0 OR MONTH(cp.deliveryDate) = :month_4) AND (:year_3 = 0 OR YEAR(cp.deliveryDate) = :year_4)

 ) p";

        //Log::info($query);
        //Log::info($agentId);

        $dt = Carbon::now('America/Bogota');

        $currentYear = $dt->year;

        if ($year != 0) {
            $currentYear = $year;
        }

        //$where = "  WHERE YEAR(p.deliveryDate) =  $currentYear ";

        $whereArray = array();

        $whereArray["customer_id_1"] = $customerId;
        $whereArray["customer_id_2"] = $customerId;

        $whereArray["month_1"] = $month;
        $whereArray["month_2"] = $month;
        $whereArray["month_3"] = $month;
        $whereArray["month_4"] = $month;

        $whereArray["year_1"] = $year;
        $whereArray["year_2"] = $year;
        $whereArray["year_3"] = $year;
        $whereArray["year_4"] = $year;

        $results = DB::select($query, $whereArray);

        return $results;
    }

    public function getAllGanttCustomer($sorting = array(), $agentId = 0, $customerId = 0, $month = 0, $year = 0)
    {

        $query = "select * from (

select c.id originalId, CONCAT('C-', c.id) id, NULL parentId, c.businessName, MIN(cp.created_at) startDate
, MAX(cp.deliveryDate) endDateTime, 1 type, 1 expanded, 1 summary
, 'CLIENTE' classification
, SUM(cp.estimatedHours) assignedHours
, ROUND(SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0) scheduledHours
, ROUND(SUM(IFNULL(pate.ejecutadas, 0)), 0) runningHours
, ROUND(IFNULL(SUM(IFNULL(pate.ejecutadas, 0)) / SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0), 2) percentage
, ROUND(SUM(IFNULL(patp.amount, 0) + IFNULL(pate.amount, 0)), 0) amount
from wg_customers c
left join wg_customer_project cp on cp.customer_id = c.id
left join wg_customer_project_agent pa on pa.project_id = cp.id
left join (
							select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
							, SUM(ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60), 0), 0) * IFNULL(ptt.price,0))  amount
							from wg_customer_project_agent_task pat
							left join wg_project_task_type ptt on ptt.`code` = pat.type
							where `status` = 'activo'
							GROUP BY project_agent_id
					) patp On pa.id = patp.project_agent_id
left join (
							select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
							, SUM(ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60), 0), 0) * IFNULL(ptt.price,0))  amount
							from wg_customer_project_agent_task pat
							left join wg_project_task_type ptt on ptt.`code` = pat.type
							where `status` = 'inactivo'
							GROUP BY project_agent_id
					) pate on pa.id = pate.project_agent_id
where c.id = :customer_id_1 AND (:month_1 = 0 OR MONTH(cp.deliveryDate) = :month_2) AND (:year_1 = 0 OR YEAR(cp.deliveryDate) = :year_2)  AND (:agent_id_1 = 0 OR pa.agent_id = :agent_id_2)
group by c.id

union ALL

select cp.id originalId, CONCAT('P-', cp.id) id, CONCAT('C-',cp.customer_id) parentId, cp.`name`, cp.created_at, cp.deliveryDate, 1 type, 0 expanded, 1 summary
, 'PROYECTO' classification
, cp.estimatedHours
, ROUND(SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0) scheduledHours
, ROUND(SUM(IFNULL(pate.ejecutadas, 0)), 0) runningHours
, ROUND(IFNULL(SUM(IFNULL(pate.ejecutadas, 0)) / SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0), 2) percentage
, ROUND(SUM(IFNULL(patp.amount, 0) + IFNULL(pate.amount, 0)), 0) amount
from wg_customers c
inner join wg_customer_project cp on cp.customer_id = c.id
left join wg_customer_project_agent pa on pa.project_id = cp.id
left join (
							select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
							, SUM(ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60), 0), 0) * IFNULL(ptt.price,0))  amount
							from wg_customer_project_agent_task pat
							left join wg_project_task_type ptt on ptt.`code` = pat.type
							where `status` = 'activo'
							GROUP BY project_agent_id
					) patp On pa.id = patp.project_agent_id
left join (
							select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
							, SUM(ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60), 0), 0) * IFNULL(ptt.price,0))  amount
							from wg_customer_project_agent_task pat
							left join wg_project_task_type ptt on ptt.`code` = pat.type
							where `status` = 'inactivo'
							GROUP BY project_agent_id
					) pate on pa.id = pate.project_agent_id
where c.id = :customer_id_2 AND (:month_3 = 0 OR MONTH(cp.deliveryDate) = :month_4) AND (:year_3 = 0 OR YEAR(cp.deliveryDate) = :year_4)  AND (:agent_id_3 = 0 OR pa.agent_id = :agent_id_4)
group by cp.id

union ALL

select pt.id originalId, CONCAT('T-', pt.id) id, CONCAT('P-',cp.id) parentId, pt.task COLLATE utf8_general_ci, pt.startDateTime
, pt.endDateTime, 1 type, 1 expanded, 0 summary
, UPPER(IFNULL(ptt.description,'TAREA SIN TIPO')) classification
, 0 estimatedHours
, CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) end scheduledHours
, CASE WHEN pt.`status` = 'inactivo' then ROUND(IFNULL((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60, 0), 0) ELSE 0 end runningHours
, ROUND(IFNULL((CASE WHEN pt.`status` = 'inactivo' then ROUND(IFNULL((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60, 0), 0) ELSE 0 end
		/ CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) end), 0), 2)  percentage
, CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) * IFNULL(ptt.price,0) end amount
from wg_customers c
inner join wg_customer_project cp on cp.customer_id = c.id
inner join wg_customer_project_agent pa on pa.project_id = cp.id
inner join wg_customer_project_agent_task pt on pt.project_agent_id = pa.id
left join wg_project_task_type ptt on ptt.`code` = pt.type
where c.id = :customer_id_3 AND (:month_5 = 0 OR MONTH(cp.deliveryDate) = :month_6) AND (:year_5 = 0 OR YEAR(cp.deliveryDate) = :year_6)  AND (:agent_id_5 = 0 OR pa.agent_id = :agent_id_6)
AND (pt.`status` = 'activo' OR pt.`status` = 'inactivo')

 ) p";

        //Log::info($query);
        //Log::info($agentId);

        $dt = Carbon::now('America/Bogota');

        $currentYear = $dt->year;

        if ($year != 0) {
            $currentYear = $year;
        }

        //$where = "  WHERE YEAR(p.deliveryDate) =  $currentYear ";

        $whereArray = array();

        $whereArray["customer_id_1"] = $customerId;
        $whereArray["customer_id_2"] = $customerId;
        $whereArray["customer_id_3"] = $customerId;
        $whereArray["month_1"] = $month;
        $whereArray["month_2"] = $month;
        $whereArray["month_3"] = $month;
        $whereArray["month_4"] = $month;
        $whereArray["month_5"] = $month;
        $whereArray["month_6"] = $month;

        $whereArray["year_1"] = $year;
        $whereArray["year_2"] = $year;
        $whereArray["year_3"] = $year;
        $whereArray["year_4"] = $year;
        $whereArray["year_5"] = $year;
        $whereArray["year_6"] = $year;

        $whereArray["agent_id_1"] = $agentId;
        $whereArray["agent_id_2"] = $agentId;
        $whereArray["agent_id_3"] = $agentId;
        $whereArray["agent_id_4"] = $agentId;
        $whereArray["agent_id_5"] = $agentId;
        $whereArray["agent_id_6"] = $agentId;
        /*
                if ($agentId != 0) {
                    $where .= " AND pa.agent_id = :agent_id";
                    $whereArray["agent_id"] = $agentId;
                }

                if ($customerId != 0) {
                    $where .= " AND c.id = :customer_id";
                    $whereArray["customer_id"] = $customerId;
                }

                if ($month != 0) {
                    $where .= " AND MONTH(p.deliveryDate) = :month";
                    $whereArray["month"] = $month;
                } else {
                    $where .= " AND MONTH(p.deliveryDate) =  MONTH(NOW())";
                }

                $sql = $query.$where;
        */

        $results = DB::select($query, $whereArray);

        return $results;
    }

    public function getAllGanttCustomerResource($sorting = array(), $agentId = 0, $customerId = 0, $month = 0, $year = 0)
    {

        $query = "select * from (
select DISTINCT a.id ID, a.`name` `Name`, '#ff4081' Color
from wg_customers c
inner join wg_customer_project cp on cp.customer_id = c.id
inner join wg_customer_project_agent pa on pa.project_id = cp.id
inner join wg_agent a on pa.agent_id = a.id
where c.id = :customer_id_1 AND (:month_1 = 0 OR MONTH(cp.deliveryDate) = :month_2) AND (:year_1 = 0 OR YEAR(cp.deliveryDate) = :year_2)

 ) p";

        //Log::info($query);
        //Log::info($agentId);

        $dt = Carbon::now('America/Bogota');

        $currentYear = $dt->year;

        if ($year != 0) {
            $currentYear = $year;
        }

        //$where = "  WHERE YEAR(p.deliveryDate) =  $currentYear ";

        $whereArray = array();

        $whereArray["customer_id_1"] = $customerId;


        $whereArray["month_1"] = $month;
        $whereArray["month_2"] = $month;


        $whereArray["year_1"] = $year;
        $whereArray["year_2"] = $year;

        $results = DB::select($query, $whereArray);

        return $results;
    }

    public function getAllGanttCustomerResourceAssignment($sorting = array(), $agentId = 0, $customerId = 0, $month = 0, $year = 0)
    {

        $query = "select * from (
select cp.id ID, CONCAT('P-', cp.id) TaskID, a.id ResourceID, 1 Units
from wg_customers c
inner join wg_customer_project cp on cp.customer_id = c.id
left join wg_customer_project_agent pa on pa.project_id = cp.id
inner join wg_agent a on pa.agent_id = a.id
where c.id  = :customer_id_1 AND (:month_1 = 0 OR MONTH(cp.deliveryDate) = :month_2) AND (:year_1 = 0 OR YEAR(cp.deliveryDate) = :year_2)
group by cp.id

union ALL

select pt.id ID, CONCAT('T-', pt.id) TaskID, a.id ResourceID, 1 Units
from wg_customers c
inner join wg_customer_project cp on cp.customer_id = c.id
inner join wg_customer_project_agent pa on pa.project_id = cp.id
inner join wg_customer_project_agent_task pt on pt.project_agent_id = pa.id
left join wg_project_task_type ptt on ptt.`code` = pt.type
inner join wg_agent a on pa.agent_id = a.id
where c.id = :customer_id_2 AND (:month_3 = 0 OR MONTH(cp.deliveryDate) = :month_4) AND (:year_3 = 0 OR YEAR(cp.deliveryDate) = :year_4)
AND (pt.`status` = 'activo' OR pt.`status` = 'inactivo')
 ) p";

        //Log::info($query);
        //Log::info($agentId);

        $dt = Carbon::now('America/Bogota');

        $currentYear = $dt->year;

        if ($year != 0) {
            $currentYear = $year;
        }

        //$where = "  WHERE YEAR(p.deliveryDate) =  $currentYear ";

        $whereArray = array();

        $whereArray["customer_id_1"] = $customerId;
        $whereArray["customer_id_2"] = $customerId;
        $whereArray["month_1"] = $month;
        $whereArray["month_2"] = $month;
        $whereArray["month_3"] = $month;
        $whereArray["month_4"] = $month;

        $whereArray["year_1"] = $year;
        $whereArray["year_2"] = $year;
        $whereArray["year_3"] = $year;
        $whereArray["year_4"] = $year;


        $results = DB::select($query, $whereArray);

        return $results;
    }

    public function getAllSummaryByAgent($sorting = array(), $agentId, $month = 0, $year = 0, $arl = 0, $os = "-1", $type = 0, $customerId = 0) {


        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, p.type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        , a.name agentName
                        , p.serviceOrder
                        , project_type.item typeDescription
                    from wg_customer_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_project_agent pa on p.id = pa.project_id
                    inner join wg_agent a on pa.agent_id = a.id
                    left join (select * from system_parameters where `group` = 'project_type') project_type on project_type.value = p.type
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id ";

        $dt = Carbon::now('America/Bogota');

        $currentYear = $dt->year;

        if ($year != 0) {
            $currentYear = $year;
        }

        $where = "  WHERE YEAR(p.deliveryDate) =  $currentYear ";

        $whereArray = array();

        if ($agentId != 0) {
            $where .= " AND pa.agent_id = :agent_id";
            $whereArray["agent_id"] = $agentId;
        }

        if ($month != 0) {
            $where .= " AND MONTH(p.deliveryDate) = :month";
            $whereArray["month"] = $month;
        } else {
            $where .= " AND MONTH(p.deliveryDate) =  MONTH(NOW())";
        }

        if ($arl != 0) {
            $where .= " AND c.arl = :arl";
            $whereArray["arl"] = $arl;
        }

        if ($type != 0) {
            $where .= " AND p.type = :type";
            $whereArray["type"] = $type;
        }

        //Log::info($os);

        if ($os != "-1" && $os != '') {
            $where .= " AND p.serviceOrder = :os";
            $whereArray["os"] = $os;
        }

        if ($customerId != 0) {
            $where .= " AND c.id = :customer_id";
            $whereArray["customer_id"] = $customerId;
        }

        $sql = $query.$where;

        $results = DB::select($sql, $whereArray);
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSummaryByCustomer($sorting = array(), $customerId, $month, $year = 0, $os = "-1", $type = 0) {

        $dt = Carbon::now('America/Bogota');

        if ($year == 0) {
            $year = $dt->year;
        }

        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, p.type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        , a.name agentName
                        , p.serviceOrder
                        , project_type.item typeDescription
                    from wg_customer_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_project_agent pa on p.id = pa.project_id
                    left join wg_agent a on pa.agent_id = a.id
                    left join (select * from system_parameters where `group` = 'project_type') project_type on project_type.value = p.type
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id";
        //Log::info($query);
        //Log::info($customerId);

        $where = "  WHERE YEAR(p.deliveryDate) =  $year ";

        $whereArray = array();

        if ($customerId != 0) {
            $where .= " AND c.id = :customer_id";
            $whereArray["customer_id"] = $customerId;
        }

        if ($month != 0) {
            $where .= " AND MONTH(p.deliveryDate) = :month";
            $whereArray["month"] = $month;
        } else {
            $where .= " AND MONTH(p.deliveryDate) =  MONTH(NOW())";
        }

        if ($os != "-1" && $os != '') {
            $where .= " AND p.serviceOrder = :os";
            $whereArray["os"] = $os;
        }

        if ($type != 0) {
            $where .= " AND p.type = :type";
            $whereArray["type"] = $type;
        }

        $sql = $query.$where;

        $results = DB::select($sql, $whereArray);

        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllTaskBy($sorting = array(), $agentId) {


        $query = "	Select patp.*
                    from wg_customer_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_project_agent pa on p.id = pa.project_id
                    inner join wg_agent a on pa.agent_id = a.id
                    left join (
                                            select id, project_agent_id, task, observation, startDateTime, type
                                            from wg_customer_project_agent_task
                                        ) patp On pa.id = patp.project_agent_id

                    WHERE pa.agent_id = :agent_id
                    ORDER BY startDateTime desc";
        //Log::info($query);
        //Log::info($agentId);
        $results = DB::select( $query, array(
            'agent_id' => $agentId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllTaskByPlaner($sorting = array(), $agentId) {

        $query = "	select ct.id, concat(statusName , ' - (', c.businessName, ') :',observation) title, eventDateTime starts_at, eventDateTime ends_at, 'to-do' type, 'tracking' tableName
                    from (select *, case when status = 'iniciado' then 'Iniciado' when status = 'completado' then 'Completado' when status = 'cancelado' then 'Cancelado' end statusName
                    from wg_customer_tracking) ct
                    inner join wg_customers c on c.id = ct.customer_id
                    where ct.createdBy = :agent_id_1
                    union ALL
                    select ct.id, concat(statusName , ' - (', c.businessName, ') :',observation) title, eventDateTime starts_at, eventDateTime ends_at, 'job' type, 'tracking' tableName
                    from (select *, case when status = 'iniciado' then 'Iniciado' when status = 'completado' then 'Completado' when status = 'cancelado' then 'Cancelado' end statusName
                    from wg_customer_tracking) ct
                    inner join wg_customers c on c.id = ct.customer_id
                    where ct.agent_id = :agent_id_2 and ct.isCustomer = 1
                    union all
                    select agt.id, concat(agt.statusName, ' - (', c.businessName, ') :',task ) title, startDateTime starts_at, endDateTime ends_at, 'cancelled' type, 'agentTask' tableName
                    from (select *, case when status = 'activo' then 'Programada' when  status = 'inactivo' then 'Completada' when status = 'cancelador' then 'Cancelada' end statusName
                    from wg_customer_project_agent_task) agt
                    inner join wg_customer_project_agent ag on agt.project_agent_id = ag.id
                    inner join wg_customer_project cp on ag.project_id = cp.id
                    inner join wg_customers c on cp.customer_id = c.id
                    where agt.createdBy = :agent_id_3
                    union all
                    SELECT ac.id, concat('PROGRAMAS EMPRESARIALES - ', ac.statusName, ' - (', c.businessName, ') :',description) title, closeDateTime starts_at, closeDateTime ends_at, 'off-site-work' type, 'actionPlan' tableName
                    from (select *, case when status = 'abierto' then 'Abierta' else 'Completada' end statusName
                    from wg_customer_management_detail_action_plan) ac
                    inner join wg_customer_management_detail md on ac.management_detail_id = md.id
                    inner join wg_customer_management cm on md.management_id = cm.id
                    inner join wg_customers c on cm.customer_id = c.id
                    where ac.createdBy = :agent_id_4
                    union all
                    SELECT ac.id, concat('SG-SST - ', ac.statusName, ' - (', c.businessName, ') :',description) title, closeDateTime starts_at, closeDateTime ends_at, 'off-site-work' type, 'actionPlan' tableName
                    from (select *, case when status = 'abierto' then 'Abierta' else 'Completada' end statusName
                    from wg_customer_diagnostic_prevention_action_plan) ac
                    inner join wg_customer_diagnostic_prevention md on ac.diagnostic_detail_id = md.id
                    inner join wg_customer_diagnostic cm on md.diagnostic_id = cm.id
                    inner join wg_customers c on cm.customer_id = c.id
                    where ac.createdBy = :agent_id_5
                    union all
                    SELECT ac.id, concat('CONTRATISTAS - ', ac.statusName, ' - (', c.businessName, ') :',description) title, closeDateTime starts_at, closeDateTime ends_at, 'off-site-work' type, 'actionPlan' tableName
                    from (select *, case when status = 'abierto' then 'Abierta' else 'Completada' end statusName
                    from wg_customer_contract_detail_action_plan) ac
                    inner join wg_customer_contract_detail md on ac.contract_detail_id = md.id
                    inner join wg_customer_contractor cm on md.contractor_id = cm.id
                    inner join wg_customers c on cm.contractor_id = c.id
                    where ac.createdBy = :agent_id_6
                    ";
        //Log::info($query);
        //Log::info($agentId);
        $results = DB::select( $query, array(
            'agent_id_1' => $agentId,
            'agent_id_2' => $agentId,
            'agent_id_3' => $agentId,
            'agent_id_4' => $agentId,
            'agent_id_5' => $agentId,
            'agent_id_6' => $agentId

        ));
        //Log::info(json_encode($results));
        return $results;
    }


    public function getAllTaskByPlanerCustomer($sorting = array(), $customerId) {

        $query = "	select ct.id, concat(statusName, ' - (', c.businessName, ') :',observation) title, eventDateTime starts_at, eventDateTime ends_at, 'job' type, 'tracking' tableName
                        ,CONCAT(a.firstName,' ',a.lastName) responsible
                    from (select *, case when status = 'iniciado' then 'Iniciado' when status = 'completado' then 'Completado' when status = 'cancelado' then 'Cancelado' end statusName
                    from wg_customer_tracking) ct
                    left join (select * from system_parameters where `group` = 'tracking_status') p on ct.status COLLATE utf8_general_ci = p.value
                    left join wg_customer_agent ca on ca.agent_id = ct.agent_id and ca.customer_id = ct.customer_id
                    left join wg_agent a on a.id = ct.agent_id
                    inner join wg_customers c on c.id = ct.customer_id
                    where ct.customer_id = :customer_id and ct.isVisible = 1
                    union all
                    select agt.id, concat(agt.statusName, ' - (', c.businessName, ') :',task ) title, startDateTime starts_at, endDateTime ends_at, 'cancelled' type, 'agentTask' tableName
                        ,CONCAT(a.firstName,' ',a.lastName) responsible
                    from (select *, case when status = 'activo' then 'Programada' when  status = 'inactivo' then 'Completada' when status = 'cancelador' then 'Cancelada' end statusName
                    from wg_customer_project_agent_task) agt
                    inner join wg_customer_project_agent ag on agt.project_agent_id = ag.id
                    left join wg_agent a on a.id = ag.agent_id
                    inner join wg_customer_project cp on ag.project_id = cp.id
                    inner join wg_customers c on cp.customer_id = c.id
                    where cp.customer_id = :customer_id1
                    union all
                    SELECT ac.id, concat(ac.statusName, ' - (', c.businessName, ') :',description) title, closeDateTime starts_at, closeDateTime ends_at, 'off-site-work' type, 'actionPlan' tableName
                        ,u.`name` COLLATE utf8_general_ci responsible
                    from (select *, case when status = 'abierto' then 'Abierta' else 'Completada' end statusName
                    from wg_customer_management_detail_action_plan) ac
                    left join users u on ac.createdBy = u.id
                    inner join wg_customer_management_detail md on ac.management_detail_id = md.id
                    inner join wg_customer_management cm on md.management_id = cm.id
                    inner join wg_customers c on cm.customer_id = c.id
                    where cm.customer_id = :customer_id2" ;
        //Log::info($query);
        ////Log::info($agentId);
        $results = DB::select( $query, array(
            'customer_id' => $customerId,
            'customer_id1' => $customerId,
            'customer_id2' => $customerId
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllTaskByProjectAgent($search, $perPage = 10, $currentPage = 0, $projectAgentId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
select pat.id, task, ptt.description type, startDateTime, endDateTime, pat.status, pat.duration
                    from wg_customer_project_agent_task pat
                    left join wg_project_task_type ptt on ptt.`code` = pat.type
                    where project_agent_id = :project_agent_id) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.task like '%$search%' OR p.observation like '%$search%' OR p.status like '%$search%'";
        }

        $sql = $query.$where;
        $sql.=$limit;

        $results = DB::select( $sql, array(
            'project_agent_id' => $projectAgentId
        ));

        return $results;
    }

    public function getAllTaskByProjectAgentCount($search, $perPage = 10, $currentPage = 0, $projectAgentId = 0) {

        $query = "SELECT * FROM (
select pat.id, task, ptt.description type, startDateTime, endDateTime, pat.status, pat.duration
                    from wg_customer_project_agent_task pat
                    left join wg_project_task_type ptt on ptt.`code` = pat.type
                    where project_agent_id = :project_agent_id) p";

        $where = '';

        if ($search != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.task like '%$search%' OR p.observation like '%$search%' OR p.status like '%$search%'";
        }

        $sql = $query.$where;

        $results = DB::select( $sql, array(
            'project_agent_id' => $projectAgentId
        ));

        return count( $results );
    }

    public function getAllTaskByProject($search, $perPage = 10, $currentPage = 0, $projectId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
select pat.id, task, observation, ptt.description type, startDateTime, endDateTime, pat.status, CONCAT(a.firstName,' ',a.lastName) agent
				, duration, pa.project_id
from wg_customer_project_agent pa
inner join wg_customer_project_agent_task pat on pa.id = pat.project_agent_id
inner join wg_agent a on pa.agent_id = a.id
left join wg_project_task_type ptt on ptt.`code` = pat.type
where pa.project_id = :project_id
order by id desc) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.task like '%$search%' OR p.observation like '%$search%' OR p.status like '%$search%' OR p.agent like '%$search%'";
        }

        $sql = $query.$where;
        $sql.=$limit;

        $results = DB::select( $sql, array(
            'project_id' => $projectId
        ));

        return $results;
    }

    public function getAllTaskByProjectCount($search, $perPage = 10, $currentPage = 0, $projectId) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
select pat.id, task, observation, ptt.description type, startDateTime, endDateTime, pat.status, CONCAT(a.firstName,' ',a.lastName) agent
				,TIMESTAMPDIFF(HOUR, startDateTime, endDateTime) duration, pa.project_id
from wg_customer_project_agent pa
inner join wg_customer_project_agent_task pat on pa.id = pat.project_agent_id
inner join wg_agent a on pa.agent_id = a.id
left join wg_project_task_type ptt on ptt.`code` = pat.type
where pa.project_id = :project_id
order by id desc) p";

        $where = '';

        if ($search != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.task like '%$search%' OR p.observation like '%$search%' OR p.status like '%$search%' OR p.agent like '%$search%'";
        }

        $sql = $query.$where;

        $results = DB::select( $sql, array(
            'project_id' => $projectId
        ));

        return count($results);
    }

    public function getAllAgentBy($sorting = array(), $skill) {


        $query = "Select a.id, a.`name`, a.availability availabilityHours
                        , ROUND(IFNULL(assignedHours, 0), 0) assignedHours
                        , (a.availability -  ROUND(IFNULL(notAssignedHours, 0), 0)) notAssignedHours
                        , ROUND(IFNULL(scheduledHours, 0), 0) scheduledHours
												, ROUND(IFNULL(runningHours, 0), 0) runningHours
									from wg_agent a
                    INNER JOIN wg_agent_skill ak on a.id = ak.agent_id
                    LEFT JOIN (SELECT p.id, pa.agent_id, pa.id project_agent_id
                        , SUM(ROUND(IFNULL(pa.estimatedHours, 0), 0)) assignedHours
                        , SUM(ROUND(IFNULL(pa.estimatedHours, 0), 0)) notAssignedHours
                        , SUM(ROUND(IFNULL(patp.planeadas, 0), 0)) scheduledHours
												, SUM(ROUND(IFNULL(pate.ejecutadas, 0), 0)) runningHours
								FROM wg_customer_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_project_agent pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_customer_project_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id
                WHERE MONTH(p.deliveryDate) =  MONTH(NOW()) and YEAR(p.deliveryDate) =  YEAR(NOW())
								group by pa.agent_id) pat on ak.agent_id = pat.agent_id
                WHERE ak.skill = :skill ";
        //Log::info($query);
        //Log::info($skill);
        $results = DB::select( $query, array(
            'skill' => $skill,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllCustomerBy($sorting = array()) {

        $query = "select c.id, businessName name, p.item arl
                    from wg_customers c
                    left join (
                                            select * from system_parameters
                                            where system_parameters.group = 'arl'
                                            ) p on c.arl = p.value
                    where (isDeleted = 0 or isDeleted is null) and `status` = '1'
                    order by businessName";

        $results = DB::select( $query );

        return $results;
    }

    public function getAllCustomerByAgentId($sorting = array(), $agentId = 0) {

        $query = "select c.id, businessName name, p.item arl
                    from wg_customers c
                    left join (
                                            select * from system_parameters
                                            where system_parameters.group = 'arl'
                                            ) p on c.arl = p.value
                    where (isDeleted = 0 or isDeleted is null) and `status` = '1'
                          and c.id in (SELECT customer_id FROM wg_customer_agent where agent_id = $agentId)
                    order by businessName";

        $results = DB::select( $query );

        return $results;
    }

    public function getDashboardPie($managementId)
    {
        $sql = "select programa.name label
                        , ROUND(IFNULL((total / questions),0), 2) value
                        , programa.color, programa.highlightColor
                from(
                                select  pp.id program_id, pp.`name`, pp.color, pp.highlightColor,count(*) questions
                                , sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
                                , sum(cdp.value) total
                                from wg_program_management pp
                                inner join wg_customer_project_program cmp ON pp.id = cmp.program_id
                                inner join wg_program_management_category ppc ON pp.id = ppc.program_id
                                inner join wg_program_management_question ppq on ppc.id = ppq.category_id
                                left join (
                                            select wg_customer_project_detail.*, wg_rate.text, wg_rate.value from wg_customer_project_detail
                                            inner join wg_rate ON wg_customer_project_detail.rate_id = wg_rate.id
                                            where management_id = :management_id
                                            ) cdp on ppq.id = cdp.question_id
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :managementId
                                group by  pp.`name`, pp.id
                )programa
                order by 1";

        $results = DB::select( $sql, array(
            'management_id' => $managementId,
            'managementId' => $managementId,
        ));

        return $results;
    }

    public function getDashboardBar($managementId)
    {
        $sql = "select pp.`name`, pp.`abbreviation`, pp.color, pp.highlightColor
                    , sum(case when ISNULL(wr.`code`) then 1 else 0 end) nocontesta
                    , sum(case when wr.`code` = 'c' then 1 else 0 end) cumple
                    , sum(case when wr.`code` = 'cp' then 1 else 0 end) parcial
                    , sum(case when wr.`code` = 'nc' then 1 else 0 end) nocumple
                    , sum(case when wr.`code` = 'na' then 1 else 0 end) noaplica
                from wg_program_management pp
                inner join wg_customer_project_program cmp ON pp.id = cmp.program_id
                inner join wg_program_management_category pc on pp.id = pc.program_id
                inner join wg_program_management_question pq on pc.id = pq.category_id
                inner join wg_customer_project_detail dp on pq.id 	= dp.question_id
                left join wg_rate wr on dp.rate_id = wr.id
                where dp.management_id = :management_id and cmp.active = 1 and cmp.management_id = :managementId
                group by pp.`name`
                order by pp.id";

        $results = DB::select( $sql, array(
            'management_id' => $managementId,
            'managementId' => $managementId,
        ));

        return $results;
    }

    public function getDashboardByManagement($managementId)
    {
        $sql = "select programa.management_id, questions
                            , answers
                            , ROUND(IFNULL(((answers / questions) * 100), 0), 2) advance
                            , ROUND(IFNULL((total / questions),0), 2) average
                            , ROUND(IFNULL(total, 0), 2) total
                    from(
                                        select  cdp.management_id, count(*) questions
                                        , sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
                                        , sum(cdp.value) total
                                        from wg_program_management pp
                                        inner join wg_customer_project_program cmp ON pp.id = cmp.program_id
                                        inner join wg_program_management_category ppc ON pp.id = ppc.program_id
                                        inner join wg_program_management_question ppq on ppc.id = ppq.category_id
                                        left join (
                                                                select wg_customer_project_detail.*, wg_rate.text, wg_rate.value from wg_customer_project_detail
                                                                inner join wg_rate ON wg_customer_project_detail.rate_id = wg_rate.id
                                                                where management_id = :management_id
                                                ) cdp on ppq.id = cdp.question_id
                                        WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :managementId
                )programa";

        $results = DB::select( $sql, array(
            'management_id' => $managementId,
            'managementId' => $managementId
        ));

        return $results;
    }

    public function getCount($search = "") {

        $model = new CustomerProject();
        $this->customerProjectRepository = new CustomerProjectRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_project.customer_id', $search);
            $filters[] = array('wg_customer_project.status', $search);
        }

        $this->customerProjectRepository->setColumns(['wg_customer_project.*']);

        return $this->customerProjectRepository->getFilteredsOptional($filters, true, "");
    }

    public function saveManagementProgram($model)
    {
        $query = "insert into wg_customer_project_program
                    select null id, :management_id management_id, id program_id, 0 active
                                , :createdBy created, null updatedBy, now() created_at, null updated_at
                    from wg_program_management pm
                    where pm.`status` = 'activo'";

        $results = DB::statement( $query, array(
            'management_id' => $model->id,
            'createdBy' => $model->createdBy
        ));

        //Log::info($results);

        return true;
    }

    public function saveManagementQuestion($model)
    {
        $query = "insert into wg_customer_project_detail
                  select null id, :management_id diagnostic, pq.id question_id, null rate_id, null observation, 'activo' status
                        , :createdBy created, null updatedBy
                        , now() created_at, null updated_at
                    from wg_program_management pp
                    inner join wg_program_management_category pc on pp.id = pc.program_id
                    inner join wg_program_management_question pq on pc.id = pq.category_id
                    inner join wg_customer_project cd on cd.id = :management_id2
                    left join wg_customer_project_detail dp on dp.management_id = cd.id and dp.question_id = pq.id
                    where pp.`status` = 'activo' and pc.`status` = 'activo' and pq.`status` = 'activo' and dp.question_id is null";


        $results = DB::statement( $query, array(
            'management_id' => $model->id,
            'createdBy' => $model->createdBy,
            'management_id2' => $model->id
        ));

        //Log::info($results);

        return true;
    }


    public function getAllYears() {

        $query = "SELECT DISTINCT YEAR(deliveryDate) id, YEAR(deliveryDate) item, YEAR(deliveryDate) value FROM `wg_customer_project`";

        $results = DB::select($query);

        return $results;
    }

    public function saveRecurringProject()
    {
        $query = "INSERT INTO wg_customer_project
SELECT
	null id, p.`customer_id`, p.`name`, p.`type`, p.`description`, p.`serviceOrder`,
	p.`defaultSkill`, p.`estimatedHours`,DATE_ADD(p.`deliveryDate`, INTERVAL 1 MONTH) deliveryDate , p.`isRecurrent`, p.`status`, 0 `isBilled`, '' `invoiceNumber`, p.`id` previous,
	p.item, p.`createdBy`, NULL `updatedBy`, NOW() `created_at`, NULL `updated_at`
FROM
	(SELECT * FROM wg_customer_project WHERE isRecurrent = 1 AND DATE_FORMAT(deliveryDate, '%m%Y') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%m%Y')) p
LEFT JOIN
	(SELECT * FROM wg_customer_project WHERE isRecurrent = 1 AND DATE_FORMAT(deliveryDate, '%m%Y') = DATE_FORMAT(CURDATE(), '%m%Y')) cp
		ON cp.previous_id = p.id
WHERE
	cp.id is null;";

        DB::statement( $query );

        return true;
    }

    public function saveRecurringProjectAgent()
    {
        $query = "INSERT INTO wg_customer_project_agent
SELECT
	O.`id`, O.`project_id`, O.`agent_id`, O.`estimatedHours`, O.`createdBy`, O.`updatedBy`, O.`created_at`, O.`updated_at`
FROM
(
	SELECT
		NULL `id`, p.id `project_id`, `agent_id`, pa.`estimatedHours`, pa.`createdBy`, null `updatedBy`, NOW() `created_at`, NULL `updated_at`
	FROM
		(SELECT * FROM wg_customer_project WHERE isRecurrent = 1 AND DATE_FORMAT(deliveryDate, '%m%Y') = DATE_FORMAT(CURDATE(), '%m%Y')) p
	INNER JOIN
		wg_customer_project_agent pa ON p.previous_id = pa.project_id
) O
LEFT JOIN
	wg_customer_project_agent D ON O.project_id = D.project_id AND O.agent_id = D.agent_id
WHERE D.id IS NULL;";

        DB::statement( $query );

        return true;
    }
}
