<?php

namespace Wgroup\CustomerInternalProject;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerManagement;
use Wgroup\Models\CustomerManagementRepository;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;
use Carbon\Carbon;

class CustomerInternalProjectService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerInternalProjectRepository;

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

        $model = new CustomerInternalProject();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerInternalProjectRepository = new CustomerInternalProjectRepository($model);

        if ($perPage > 0) {
            $this->customerInternalProjectRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_internal_project.customer_id',
            'wg_customer_internal_project.status',
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
                    $this->customerInternalProjectRepository->sortBy($colName, $dir);
                } else {
                    $this->customerInternalProjectRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerInternalProjectRepository->sortBy('wg_customer_internal_project.id', 'desc');
        }

        $filters = array();

        //$filters[] = array('wg_customer_internal_project.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_internal_project.status', $search);
            //$filters[] = array('wg_customer_user.name', $search);
            //$filters[] = array('diags.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_internal_project.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_internal_project.status', '0');
        }


        $this->customerInternalProjectRepository->setColumns(['wg_customer_internal_project.*']);

        return $this->customerInternalProjectRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllSettingBy($sorting = array(), $agentId = 0, $customerId = 0, $month = 0, $year = 0) {

        $query = "Select a.id, a.availability availabilityHours
                        , SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0)) assignedHours
                        , SUM(ROUND(IFNULL(pat.planeadas, 0), 0) + ROUND(IFNULL(pat.ejecutadas, 0), 0)) scheduledHours, SUM(ROUND(IFNULL(pat.ejecutadas, 0), 0)) runningHours
                    from wg_customer_user a
                    LEFT JOIN (SELECT p.id, pa.agent_id, pa.id project_agent_id, patp.planeadas, pate.ejecutadas, pa.estimatedHours FROM wg_customer_internal_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_internal_project_user pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
                                            from wg_customer_internal_project_user_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
                                            from wg_customer_internal_project_user_task pat
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
            $field = DB::table('wg_customer_user')->sum('availability');

            $field .= " - SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0))";
        }

        $query = "Select $field availabilityHours
				, SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0)) assignedHours
				, SUM(ROUND(IFNULL(pat.planeadas, 0), 0) + ROUND(IFNULL(pat.ejecutadas, 0), 0)) scheduledHours, SUM(ROUND(IFNULL(pat.ejecutadas, 0), 0)) runningHours
		from wg_customer_user a
		LEFT JOIN (SELECT c.id customer_id, p.id, pa.agent_id, pa.id project_agent_id, patp.planeadas, pate.ejecutadas, pa.estimatedHours FROM wg_customer_internal_project p
		inner join wg_customers c on p.customer_id = c.id
		inner join wg_customer_internal_project_user pa on p.id = pa.project_id
		left join (
														select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
														from wg_customer_internal_project_user_task pat
														where `status` = 'activo'
														group by pat.project_agent_id
												) patp On pa.id = patp.project_agent_id
		left join (
														select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
														from wg_customer_internal_project_user_task pat
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

        \Log::info($sql);

        $results = DB::select( $sql);
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSettingByAgent($sorting = array(), $agentId = 0, $month = 0, $year = 0) {


        $query = "Select a.availability availabilityHours
                        , SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0)) assignedHours
                        , SUM(ROUND(IFNULL(pat.planeadas, 0), 0) + ROUND(IFNULL(pat.ejecutadas, 0), 0)) scheduledHours, SUM(ROUND(IFNULL(pat.ejecutadas, 0), 0)) runningHours
                    from wg_customer_user a
                    LEFT JOIN (SELECT p.id, pa.agent_id, pa.id project_agent_id, patp.planeadas, pate.ejecutadas, pa.estimatedHours FROM wg_customer_internal_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_internal_project_user pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
                                            from wg_customer_internal_project_user_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
                                            from wg_customer_internal_project_user_task pat
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

    public function getAllSettingByCustomerId($sorting = array(), $customerId = 0, $monthStart = 0, $monthEnd = 0) {


        $query = "Select a.availability availabilityHours
                        , SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0)) assignedHours
                        , SUM(ROUND(IFNULL(pat.planeadas, 0), 0) + ROUND(IFNULL(pat.ejecutadas, 0), 0)) scheduledHours, SUM(ROUND(IFNULL(pat.ejecutadas, 0), 0)) runningHours
                    from wg_customer_user a
                    LEFT JOIN (SELECT p.id, pa.agent_id, c.id customer_id, pa.id project_agent_id, patp.planeadas, pate.ejecutadas, pa.estimatedHours FROM wg_customer_internal_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_internal_project_user pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
                                            from wg_customer_internal_project_user_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
                                            from wg_customer_internal_project_user_task pat
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


    public function getAllSummaryBy($sorting = array(), $agentId = 0, $customerId = 0, $month = 0, $year = 0, $arl = 0, $os = "-1", $type = null) {

        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, wg_customer_parameter.value AS type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        ,  CONCAT_WS(' ',  u.name, IFNULL(u.surname, '')) AS agentName
                        , p.serviceOrder
                    from wg_customer_internal_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_internal_project_user pa on p.id = pa.project_id
                    left join wg_customer_user a on pa.agent_id = a.id
                    left JOIN users u on u.id = a.user_id
                    left join wg_customer_parameter ON wg_customer_parameter.id = p.type
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
                                            from wg_customer_internal_project_user_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
                                            from wg_customer_internal_project_user_task pat
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

        if ($arl != 0) {
            $where .= " AND c.arl = :arl";
            $whereArray["arl"] = $arl;
        }

        if ($os != "-1" && $os != '') {
            $where .= " AND p.serviceOrder = :os";
            $whereArray["os"] = $os;
        }

        if ($type != null) {
            $where .= " AND p.type = :type";
            $whereArray["type"] = $type;
        }

        $sql = $query.$where;

        $results = DB::select($sql, $whereArray);

        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSummaryByStatus($agentId = 0, $customerId = 0, $month = 0, $year = 0, $arl = 0, $os = "-1") {

        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, p.type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        , CONCAT_WS(' ',  u.name, IFNULL(u.surname, '')) AS agentName
                        , p.serviceOrder
                        , u.email
                    from wg_customer_internal_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_internal_project_user pa on p.id = pa.project_id
                    left join wg_customer_user a on pa.agent_id = a.id
                    left join users u on a.user_id = u.id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
                                            from wg_customer_internal_project_user_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
                                            from wg_customer_internal_project_user_task pat
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

        if ($arl != 0) {
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

    public function getAllSummaryByAgent($sorting = array(), $agentId, $month = 0, $year = 0, $arl = 0, $os = "-1", $type = null) {


        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, wg_customer_parameter.value AS type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        , CONCAT_WS(' ',  u.name, IFNULL(u.surname, '')) AS agentName
                        , p.serviceOrder
                    from wg_customer_internal_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_internal_project_user pa on p.id = pa.project_id
                    inner join wg_customer_user a on pa.agent_id = a.id
                    inner join users u on a.user_id = u.id
                    left join wg_customer_parameter ON wg_customer_parameter.id = p.type
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
                                            from wg_customer_internal_project_user_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
                                            from wg_customer_internal_project_user_task pat
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

        if ($os != "-1" && $os != '') {
            $where .= " AND p.serviceOrder = :os";
            $whereArray["os"] = $os;
        }

        if ($type != null) {
            $where .= " AND p.type = :type";
            $whereArray["type"] = $type;
        }

        $sql = $query.$where;

        $results = DB::select($sql, $whereArray);
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSummaryByCustomer($sorting = array(), $customerId, $month, $year = 0, $os = "-1", $type = null) {

        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, wg_customer_parameter.value AS type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        , CONCAT_WS(' ',  u.name, IFNULL(u.surname, '')) AS agentName
                        , p.serviceOrder
                    from wg_customer_internal_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_internal_project_user pa on p.id = pa.project_id
                    left join wg_customer_user a on pa.agent_id = a.id
                    left join users u on a.user_id = u.id
                    left join wg_customer_parameter ON wg_customer_parameter.id = p.type
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
                                            from wg_customer_internal_project_user_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
                                            from wg_customer_internal_project_user_task pat
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

        if ($type != null) {
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
                    from wg_customer_internal_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_internal_project_user pa on p.id = pa.project_id
                    inner join wg_customer_user a on pa.agent_id = a.id
                    left join (
                                            select id, project_agent_id, task, observation, startDateTime, type
                                            from wg_customer_internal_project_user_task
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

        $query = "	select ct.id, concat(statusName COLLATE utf8mb4_unicode_ci, ' - (', c.businessName, ') :',observation) title, eventDateTime starts_at, eventDateTime ends_at, 'to-do' type, 'tracking' tableName
                    from (select *, case when status = 'iniciado' then 'Iniciado' when status = 'completado' then 'Completado' when status = 'cancelado' then 'Cancelado' end statusName
                    from wg_customer_tracking) ct
                    inner join wg_customers c on c.id = ct.customer_id
                    where ct.createdBy = :agent_id
                    union ALL
                    select ct.id, concat(statusName COLLATE utf8mb4_unicode_ci, ' - (', c.businessName, ') :',observation) title, eventDateTime starts_at, eventDateTime ends_at, 'job' type, 'tracking' tableName
                    from (select *, case when status = 'iniciado' then 'Iniciado' when status = 'completado' then 'Completado' when status = 'cancelado' then 'Cancelado' end statusName
                    from wg_customer_tracking) ct
                    inner join wg_customers c on c.id = ct.customer_id
                    where ct.agent_id = :agent_id1 and ct.isCustomer = 1
                    union all
                    select agt.id, concat(agt.statusName, ' - (', c.businessName, ') :',task ) title, startDateTime starts_at, endDateTime ends_at, 'cancelled' type, 'agentTask' tableName
                    from (select *, case when status = 'activo' then 'Programada' when  status = 'inactivo' then 'Completada' when status = 'cancelador' then 'Cancelada' end statusName
                    from wg_customer_internal_project_user_task) agt
                    inner join wg_customer_internal_project_user ag on agt.project_agent_id = ag.id
                    inner join wg_customer_internal_project cp on ag.project_id = cp.id
                    inner join wg_customers c on cp.customer_id = c.id
                    where agt.createdBy = :agentId
                    union all
                    SELECT ac.id, concat(ac.statusName, ' - (', c.businessName, ') :',description) title, closeDateTime starts_at, closeDateTime ends_at, 'off-site-work' type, 'actionPlan' tableName
                    from (select *, case when status = 'abierto' then 'Abierta' else 'Completada' end statusName
                    from wg_customer_management_detail_action_plan) ac
                    inner join wg_customer_management_detail md on ac.management_detail_id = md.id
                    inner join wg_customer_management cm on md.management_id = cm.id
                    inner join wg_customers c on cm.customer_id = c.id
                    where ac.createdBy = :_agentId";
        //Log::info($query);
        //Log::info($agentId);
        $results = DB::select( $query, array(
            'agent_id' => $agentId,
            'agent_id1' => $agentId,
            'agentId' => $agentId,
            '_agentId' => $agentId,

        ));
        //Log::info(json_encode($results));
        return $results;
    }


    public function getAllTaskByPlanerCustomer($sorting = array(), $customerId) {

        $query = "	select ct.id, concat(statusName COLLATE utf8mb4_unicode_ci, ' - (', c.businessName, ') :',observation) title, eventDateTime starts_at, eventDateTime ends_at, 'job' type, 'tracking' tableName
                    from (select *, case when status = 'iniciado' then 'Iniciado' when status = 'completado' then 'Completado' when status = 'cancelado' then 'Cancelado' end statusName
                    from wg_customer_tracking) ct
                    left join (select * from system_parameters where `group` = 'tracking_status') p on ct.status COLLATE utf8_general_ci = p.value
                    inner join wg_customers c on c.id = ct.customer_id
                    where ct.customer_id = :customer_id and ct.isVisible = 1
                    union all
                    select agt.id, concat(agt.statusName, ' - (', c.businessName, ') :',task ) title, startDateTime starts_at, endDateTime ends_at, 'cancelled' type, 'agentTask' tableName
                    from (select *, case when status = 'activo' then 'Programada' when  status = 'inactivo' then 'Completada' when status = 'cancelador' then 'Cancelada' end statusName
                    from wg_customer_internal_project_user_task) agt
                    inner join wg_customer_internal_project_user ag on agt.project_agent_id = ag.id
                    inner join wg_customer_internal_project cp on ag.project_id = cp.id
                    inner join wg_customers c on cp.customer_id = c.id
                    where cp.customer_id = :customer_id1
                    union all
                    SELECT ac.id, concat(ac.statusName, ' - (', c.businessName, ') :',description) title, closeDateTime starts_at, closeDateTime ends_at, 'off-site-work' type, 'actionPlan' tableName
                    from (select *, case when status = 'abierto' then 'Abierta' else 'Completada' end statusName
                    from wg_customer_management_detail_action_plan) ac
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

    public function getAllTaskByProjectAgent($search, $perPage = 10, $currentPage = 0, $projectAgentId) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
select pat.id, task, p.item type, startDateTime, endDateTime, pat.status
                    from wg_customer_internal_project_user_task pat
                    left join (
                                            select * from system_parameters
                                            where system_parameters.group = 'project_task_type'
                                            ) p on pat.type = p.value
                    where project_agent_id = :project_agent_id ) p";


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

    public function getAllTaskByProjectAgentCount($search, $perPage = 10, $currentPage = 0, $projectAgentId) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
select pat.id, task, p.item type, startDateTime, endDateTime, pat.status
                    from wg_customer_internal_project_user_task pat
                    left join (
                                            select * from system_parameters
                                            where system_parameters.group = 'project_task_type'
                                            ) p on pat.type = p.value
                    where project_agent_id = :project_agent_id ) p";

        $where = '';

        if ($search != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.task like '%$search%' OR p.observation like '%$search%' OR p.status like '%$search%'";
        }

        $sql = $query.$where;

        $results = DB::select( $sql, array(
            'project_agent_id' => $projectAgentId
        ));

        return count($results);
    }

    public function getAllTaskByProject($search, $perPage = 10, $currentPage = 0, $projectId = 0) {
        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
select pat.id, task, observation, p.item type, startDateTime, endDateTime, pat.status, CONCAT(a.firstName,' ',a.lastName) agent
				,TIMESTAMPDIFF(HOUR, startDateTime, endDateTime) duration, pa.project_id
from wg_customer_internal_project_user pa
inner join wg_customer_internal_project_user_task pat on pa.id = pat.project_agent_id
inner join wg_customer_user a on pa.agent_id = a.id
left join (select * from wg_customer_parameter where wg_customer_parameter.group = 'projectTaskType') p on pat.type = p.id
where pa.project_id = :project_id
order by id desc ) p";

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

    public function getAllTaskByProjectCount($search, $perPage = 10, $currentPage = 0, $projectId = 0) {

        $query = "SELECT * FROM (
select pat.id, task, observation, p.item type, startDateTime, endDateTime, pat.status, CONCAT(a.firstName,' ',a.lastName) agent
				,TIMESTAMPDIFF(HOUR, startDateTime, endDateTime) duration, pa.project_id
from wg_customer_internal_project_user pa
inner join wg_customer_internal_project_user_task pat on pa.id = pat.project_agent_id
inner join wg_customer_user a on pa.agent_id = a.id
left join (select * from system_parameters where system_parameters.group = 'project_task_type') p on pat.type = p.value
where pa.project_id = :project_id
order by id desc ) p";

        $where = '';

        if ($search != '') {
            $operator = ($where != '') ? "AND" : 'WHERE';
            $where .= " $operator p.task like '%$search%' OR p.observation like '%$search%' OR p.status like '%$search%' OR p.agent like '%$search%'";
        }

        $sql = $query.$where;

        $results = DB::select( $sql, array(
            'project_id' => $projectId
        ));

        return $results;
    }

    public function getAllAgentBy($sorting = array(), $skill, $customerId) {


        $query = "Select a.id, CONCAT_WS(' ',  u.name, IFNULL(u.surname, '')) AS name, a.availability availabilityHours
                        , ROUND(IFNULL(assignedHours, 0), 0) assignedHours
                        , (a.availability -  ROUND(IFNULL(notAssignedHours, 0), 0)) notAssignedHours
                        , ROUND(IFNULL(scheduledHours, 0), 0) scheduledHours
												, ROUND(IFNULL(runningHours, 0), 0) runningHours
									from wg_customer_user a
									INNER JOIN users u on u.id = a.user_id
                    INNER JOIN wg_customer_user_skill ak on a.id = ak.customer_user_id
                    LEFT JOIN (SELECT p.id, pa.agent_id, pa.id project_agent_id
                        , SUM(ROUND(IFNULL(pa.estimatedHours, 0), 0)) assignedHours
                        , SUM(ROUND(IFNULL(pa.estimatedHours, 0), 0)) notAssignedHours
                        , SUM(ROUND(IFNULL(patp.planeadas, 0), 0)) scheduledHours
												, SUM(ROUND(IFNULL(pate.ejecutadas, 0), 0)) runningHours
								FROM wg_customer_internal_project p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_customer_internal_project_user pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
                                            from wg_customer_internal_project_user_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
                                            from wg_customer_internal_project_user_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id
                WHERE MONTH(p.deliveryDate) =  MONTH(NOW()) and YEAR(p.deliveryDate) =  YEAR(NOW())
								group by pa.agent_id) pat on ak.customer_user_id = pat.agent_id
                WHERE ak.skill = :skill and u.wg_type IN ('customerUser', 'customerAdmin') and a.customer_id  = :customerId AND a.isActive = 1";
        //Log::info($query);
        //Log::info($skill);
        $results = DB::select( $query, array(
            'skill' => $skill,
            'customerId' => $customerId
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllCustomerBy($sorting = array(), $customerId) {

        $where = $customerId == '' ? '' : ' AND c.id = '. $customerId;
        $query = "select c.id, businessName name, p.item arl
from wg_customers c
left join (
												select * from system_parameters
												where system_parameters.group = 'arl'
												) p on c.arl = p.value
where (isDeleted is null or isDeleted = 0) and `status` = '1' $where
order by businessName";

        $results = DB::select( $query );

        return $results;
    }

    public function getCount($search = "") {

        $model = new CustomerInternalProject();
        $this->customerInternalProjectRepository = new CustomerInternalProjectRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_internal_project.customer_id', $search);
            $filters[] = array('wg_customer_internal_project.status', $search);
        }

        $this->customerInternalProjectRepository->setColumns(['wg_customer_internal_project.*']);

        return $this->customerInternalProjectRepository->getFilteredsOptional($filters, true, "");
    }


    public function getAllGanttEconomicGroup($sorting = array(), $agentId = 0, $customerId = 0, $month = 0, $year = 0)
    {

        $query = "select * from (
select eg.parent_id originalId, CONCAT('G-', eg.parent_id) id, null parentId, c.businessName
, MIN(cp.created_at) startDate, MAX(cp.deliveryDate) endDateTime, 1 type, 1 expanded, 1 summary
, 'GRUPO' classification
, h.`value` assignedHours
, ROUND(SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0) scheduledHours
, ROUND(SUM(IFNULL(pate.ejecutadas, 0)), 0) runningHours
, ROUND(IFNULL(SUM(IFNULL(pate.ejecutadas, 0)) / SUM(IFNULL(patp.planeadas, 0) + IFNULL(pate.ejecutadas, 0)), 0), 2) percentage
, ROUND(SUM(IFNULL(patp.amount, 0) + IFNULL(pate.amount, 0)), 0) amount
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.parent_id
left join wg_customer_internal_project cp on cp.customer_id = eg.customer_id
left join wg_customer_internal_project_user pa on pa.project_id = cp.id
left join (select * from wg_customer_parameter where wg_customer_parameter.`group` = 'economicGroupAssignedHours') h on h.customer_id = eg.parent_id
left join (
							select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
							, SUM(ROUND(IFNULL((pat.duration), 0), 0) * IFNULL(ptt.`data`,0))  amount
							from wg_customer_internal_project_user_task pat
							left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pat.type
							where `status` = 'activo'
							GROUP BY project_agent_id
					) patp On pa.id = patp.project_agent_id
left join (
							select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
							, SUM(ROUND(IFNULL((pat.duration), 0), 0) * IFNULL(ptt.`data`,0))  amount
							from wg_customer_internal_project_user_task pat
							left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pat.type
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
left join wg_customer_internal_project cp on cp.customer_id = eg.customer_id
left join wg_customer_internal_project_user pa on pa.project_id = cp.id
left join (
							select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
							, SUM(ROUND(IFNULL((pat.duration), 0), 0) * IFNULL(ptt.`data`,0))  amount
							from wg_customer_internal_project_user_task pat
							left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pat.type
							where `status` = 'activo'
							GROUP BY project_agent_id
					) patp On pa.id = patp.project_agent_id
left join (
							select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
							, SUM(ROUND(IFNULL((pat.duration), 0), 0) * IFNULL(ptt.`data`,0))  amount
							from wg_customer_internal_project_user_task pat
							left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pat.type
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
inner join wg_customer_internal_project cp on cp.customer_id = eg.customer_id
inner join wg_customer_internal_project_user pa on pa.project_id = cp.id
left join (
							select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
							, SUM(ROUND(IFNULL((pat.duration), 0), 0) * IFNULL(ptt.`data`,0))  amount
							from wg_customer_internal_project_user_task pat
							left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pat.type
							where `status` = 'activo'
							GROUP BY project_agent_id
					) patp On pa.id = patp.project_agent_id
left join (
							select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
							, SUM(ROUND(IFNULL((pat.duration), 0), 0) * IFNULL(ptt.`data`,0))  amount
							from wg_customer_internal_project_user_task pat
							left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pat.type
							where `status` = 'inactivo'
							GROUP BY project_agent_id
					) pate on pa.id = pate.project_agent_id
where eg.parent_id = :customer_id_3 AND (:month_5 = 0 OR MONTH(cp.deliveryDate) = :month_6) AND (:year_5 = 0 OR YEAR(cp.deliveryDate) = :year_6)  AND (:agent_id_5 = 0 OR pa.agent_id = :agent_id_6)
group by cp.id

union ALL

select pt.id originalId, CONCAT('T-', pt.id) id, CONCAT('P-',cp.id) parentId, pt.task COLLATE utf8_general_ci task, pt.startDateTime
, pt.endDateTime, 1 type, 1 expanded, 0 summary
, UPPER(IFNULL(ptt.`value`,'TAREA SIN TIPO')) classification
, 0 estimatedHours
, CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) end scheduledHours
, CASE WHEN pt.`status` = 'inactivo' then ROUND(IFNULL((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60, 0), 0) ELSE 0 end runningHours
, ROUND(IFNULL((CASE WHEN pt.`status` = 'inactivo' then ROUND(IFNULL((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60, 0), 0) ELSE 0 end
		/ CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) end), 0), 2)  percentage
, CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) * IFNULL(ptt.`data`,0) end amount
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.customer_id
inner join wg_customer_internal_project cp on cp.customer_id = eg.customer_id
inner join wg_customer_internal_project_user pa on pa.project_id = cp.id
inner join wg_customer_internal_project_user_task pt on pt.project_agent_id = pa.id
left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.id = pt.type
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
select DISTINCT a.id ID, a.fullName `Name`, '#f44336' Color
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.customer_id
inner join wg_customer_internal_project cp on cp.customer_id = eg.customer_id
inner join wg_customer_internal_project_user pa on pa.project_id = cp.id
inner join wg_customer_user a on pa.agent_id = a.id
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
inner join wg_customer_internal_project cp on cp.customer_id = eg.customer_id
inner join wg_customer_internal_project_user pa on pa.project_id = cp.id
inner join wg_customer_user a on pa.agent_id = a.id
where eg.parent_id = :customer_id_1 AND (:month_1 = 0 OR MONTH(cp.deliveryDate) = :month_2) AND (:year_1 = 0 OR YEAR(cp.deliveryDate) = :year_2)
group by cp.id

union ALL

select pt.id ID, CONCAT('T-', pt.id) TaskID, a.id ResourceID, 1 Units
from wg_customers c
inner join wg_customer_economic_group eg on c.id = eg.customer_id
inner join wg_customer_internal_project cp on cp.customer_id = eg.customer_id
inner join wg_customer_internal_project_user pa on pa.project_id = cp.id
inner join wg_customer_user a on pa.agent_id = a.id
inner join wg_customer_internal_project_user_task pt on pt.project_agent_id = pa.id
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
left join wg_customer_internal_project cp on cp.customer_id = c.id
left join wg_customer_internal_project_user pa on pa.project_id = cp.id
left join (
							select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
							, SUM(ROUND(IFNULL((pat.duration), 0), 0) * IFNULL(ptt.`data`,0))  amount
							from wg_customer_internal_project_user_task pat
							left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pat.type
							where `status` = 'activo'
							GROUP BY project_agent_id
					) patp On pa.id = patp.project_agent_id
left join (
							select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
							, SUM(ROUND(IFNULL((pat.duration), 0), 0) * IFNULL(ptt.`data`,0))  amount
							from wg_customer_internal_project_user_task pat
							left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pat.type
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
inner join wg_customer_internal_project cp on cp.customer_id = c.id
left join wg_customer_internal_project_user pa on pa.project_id = cp.id
left join (
							select pat.id, pat.project_agent_id , SUM(pat.duration) planeadas
							, SUM(ROUND(IFNULL((pat.duration), 0), 0) * IFNULL(ptt.`data`,0))  amount
							from wg_customer_internal_project_user_task pat
							left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pat.type
							where `status` = 'activo'
							GROUP BY project_agent_id
					) patp On pa.id = patp.project_agent_id
left join (
							select pat.id, pat.project_agent_id , SUM(pat.duration) ejecutadas
							, SUM(ROUND(IFNULL((pat.duration), 0), 0) * IFNULL(ptt.`data`,0))  amount
							from wg_customer_internal_project_user_task pat
							left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pat.type
							where `status` = 'inactivo'
							GROUP BY project_agent_id
					) pate on pa.id = pate.project_agent_id
where c.id = :customer_id_2 AND (:month_3 = 0 OR MONTH(cp.deliveryDate) = :month_4) AND (:year_3 = 0 OR YEAR(cp.deliveryDate) = :year_4)  AND (:agent_id_3 = 0 OR pa.agent_id = :agent_id_4)
group by cp.id

union ALL

select pt.id originalId, CONCAT('T-', pt.id) id, CONCAT('P-',cp.id) parentId, pt.task COLLATE utf8mb4_unicode_ci, pt.startDateTime
, pt.endDateTime, 1 type, 1 expanded, 0 summary
, UPPER(IFNULL(ptt.`value`,'TAREA SIN TIPO')) classification
, 0 estimatedHours
, CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) end scheduledHours
, CASE WHEN pt.`status` = 'inactivo' then ROUND(IFNULL((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60, 0), 0) ELSE 0 end runningHours
, ROUND(IFNULL((CASE WHEN pt.`status` = 'inactivo' then ROUND(IFNULL((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60, 0), 0) ELSE 0 end
		/ CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) end), 0), 2)  percentage
, CASE WHEN pt.`status` = 'activo' or pt.`status` = 'inactivo' then ROUND(IFNULL(((TIME_TO_SEC(TIMEDIFF(pt.endDateTime, pt.startDateTime)) / 60) / 60),0),0) * IFNULL(ptt.`data`,0) end amount
from wg_customers c
inner join wg_customer_internal_project cp on cp.customer_id = c.id
inner join wg_customer_internal_project_user pa on pa.project_id = cp.id
inner join wg_customer_internal_project_user_task pt on pt.project_agent_id = pa.id
left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.id = pt.type
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
select DISTINCT a.id ID, a.fullName `Name`, '#ff4081' Color
from wg_customers c
inner join wg_customer_internal_project cp on cp.customer_id = c.id
inner join wg_customer_internal_project_user pa on pa.project_id = cp.id
inner join wg_customer_user a on pa.agent_id = a.id
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
inner join wg_customer_internal_project cp on cp.customer_id = c.id
left join wg_customer_internal_project_user pa on pa.project_id = cp.id
inner join wg_customer_user a on pa.agent_id = a.id
where c.id  = :customer_id_1 AND (:month_1 = 0 OR MONTH(cp.deliveryDate) = :month_2) AND (:year_1 = 0 OR YEAR(cp.deliveryDate) = :year_2)
group by cp.id

union ALL

select pt.id ID, CONCAT('T-', pt.id) TaskID, a.id ResourceID, 1 Units
from wg_customers c
inner join wg_customer_internal_project cp on cp.customer_id = c.id
inner join wg_customer_internal_project_user pa on pa.project_id = cp.id
inner join wg_customer_internal_project_user_task pt on pt.project_agent_id = pa.id
left join (select * from wg_customer_parameter where `group` = 'projectTaskType') ptt on ptt.`id` = pt.type
inner join wg_customer_user a on pa.agent_id = a.id
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

    public function getAllYears() {

        $query = "SELECT DISTINCT YEAR(deliveryDate) id, YEAR(deliveryDate) item, YEAR(deliveryDate) value FROM `wg_customer_internal_project`";

        $results = DB::select($query);

        return $results;
    }

    public function saveRecurringProject()
    {
        $query = "INSERT INTO wg_customer_internal_project
SELECT
	null id, p.`customer_id`, p.`name`, p.`type`, p.`description`, p.`serviceOrder`,
	p.`defaultSkill`, p.`estimatedHours`,DATE_ADD(p.`deliveryDate`, INTERVAL 1 MONTH) deliveryDate , p.`isRecurrent`, p.`status`, 0 `isBilled`, '' `invoiceNumber`, p.`id` previous,
	p.`createdBy`, NULL `updatedBy`, NOW() `created_at`, NULL `updated_at`
FROM
	(SELECT * FROM wg_customer_internal_project WHERE isRecurrent = 1 AND DATE_FORMAT(deliveryDate, '%m%Y') = DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%m%Y')) p
LEFT JOIN
	(SELECT * FROM wg_customer_internal_project WHERE isRecurrent = 1 AND DATE_FORMAT(deliveryDate, '%m%Y') = DATE_FORMAT(CURDATE(), '%m%Y')) cp
		ON cp.previous_id = p.id
WHERE
	cp.id is null;";

        DB::statement( $query );

        return true;
    }

    public function saveRecurringProjectAgent()
    {
        $query = "INSERT INTO wg_customer_internal_project_agent
SELECT
	O.`id`, O.`project_id`, O.`agent_id`, O.`estimatedHours`, O.`createdBy`, O.`updatedBy`, O.`created_at`, O.`updated_at`
FROM
(
	SELECT
		NULL `id`, p.id `project_id`, `agent_id`, pa.`estimatedHours`, pa.`createdBy`, null `updatedBy`, NOW() `created_at`, NULL `updated_at`
	FROM
		(SELECT * FROM wg_customer_internal_project WHERE isRecurrent = 1 AND DATE_FORMAT(deliveryDate, '%m%Y') = DATE_FORMAT(CURDATE(), '%m%Y')) p
	INNER JOIN
		wg_customer_internal_project_agent pa ON p.previous_id = pa.project_id
) O
LEFT JOIN
	wg_customer_internal_project_agent D ON O.project_id = D.project_id AND O.agent_id = D.agent_id
WHERE D.id IS NULL;";

        DB::statement( $query );

        return true;
    }
}
