<?php

namespace Wgroup\QuoteDetail;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerManagement;
use Wgroup\Models\CustomerManagementRepository;
use Wgroup\Models\CustomerProject;
use Wgroup\Models\CustomerProjectRepository;

class QuoteDetailService {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $reportRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId) {

        $model = new CustomerProject();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->reportRepository = new CustomerProjectRepository($model);

        if ($perPage > 0) {
            $this->reportRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_report.customer_id',
            'wg_report.status',
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
                    $this->reportRepository->sortBy($colName, $dir);
                } else {
                    $this->reportRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->reportRepository->sortBy('wg_report.id', 'desc');
        }

        $filters = array();

        //$filters[] = array('wg_report.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_report.status', $search);
            //$filters[] = array('wg_agent.name', $search);
            //$filters[] = array('diags.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_report.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_report.status', '0');
        }


        $this->reportRepository->setColumns(['wg_report.*']);

        return $this->reportRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllSettingBy($sorting = array(), $agentId = 0, $customerId = 0, $month = 0) {

        $query = "Select a.id, a.availability availabilityHours
                        , SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0)) assignedHours
                        , SUM(ROUND(IFNULL(pat.planeadas, 0), 0) + ROUND(IFNULL(pat.ejecutadas, 0), 0)) scheduledHours, SUM(ROUND(IFNULL(pat.ejecutadas, 0), 0)) runningHours
                    from wg_agent a
                    LEFT JOIN (SELECT p.id, pa.agent_id, pa.id project_agent_id, patp.planeadas, pate.ejecutadas, pa.estimatedHours FROM wg_report p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_report_agent pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_report_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_report_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id
                WHERE MONTH(p.deliveryDate) =  MONTH(NOW()) and YEAR(p.deliveryDate) =  YEAR(NOW())) pat on pat.agent_id = a.id
                WHERE pat.agent_id = :agent_id
                GROUP BY pat.agent_id";

        //Log::info($query);
        //Log::info($agentId);
        $results = DB::select( $query, array(
            'agent_id' => $agentId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSettingByCustomerId($sorting = array(), $customerId = 0, $monthStart = 0, $monthEnd = 0) {

        $query = "Select a.id, a.availability availabilityHours
                        , SUM(ROUND(IFNULL(pat.estimatedHours, 0), 0)) assignedHours
                        , SUM(ROUND(IFNULL(pat.planeadas, 0), 0) + ROUND(IFNULL(pat.ejecutadas, 0), 0)) scheduledHours, SUM(ROUND(IFNULL(pat.ejecutadas, 0), 0)) runningHours
                    from wg_agent a
                    LEFT JOIN (SELECT p.id, pa.agent_id, pa.id project_agent_id, patp.planeadas, pate.ejecutadas, pa.estimatedHours FROM wg_report p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_report_agent pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_report_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_report_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id
                WHERE MONTH(p.deliveryDate) =  MONTH(NOW()) and YEAR(p.deliveryDate) =  YEAR(NOW())) pat on pat.agent_id = a.id
                WHERE pat.agent_id = :agent_id
                GROUP BY pat.agent_id";

        //Log::info($query);
        //Log::info($customerId);
        $results = DB::select( $query, array(
            'agent_id' => $customerId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }


    public function getAllSummaryBy($sorting = array(), $agentId = 0, $customerId = 0, $month = 0) {

        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, p.type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        , a.name agentName
                    from wg_report p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_report_agent pa on p.id = pa.project_id
                    inner join wg_agent a on pa.agent_id = a.id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_report_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_report_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id ";
        //Log::info($query);
        //Log::info($agentId);

        $where = "  WHERE YEAR(p.deliveryDate) =  YEAR(NOW()) ";

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

        $sql = $query.$where;

        $results = DB::select($sql, $whereArray);

        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSummaryByAgent($sorting = array(), $agentId, $month = 0) {


        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, p.type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        , a.name agentName
                    from wg_report p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_report_agent pa on p.id = pa.project_id
                    inner join wg_agent a on pa.agent_id = a.id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_report_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_report_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id ";
        //Log::info($query);
        //Log::info($agentId);
        $where = "  WHERE YEAR(p.deliveryDate) =  YEAR(NOW()) ";

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

        $sql = $query.$where;

        $results = DB::select($sql, $whereArray);
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSummaryByCustomer($sorting = array(), $customerId, $month) {


        $query = "Select p.id, pa.id project_agent_id, p.customer_id, c.businessName customerName, p.description, p.name, p.estimatedHours, p.type, pa.estimatedHours assignedHours
                        , ROUND(IFNULL(patp.planeadas, 0), 0) + ROUND(IFNULL(pate.ejecutadas, 0), 0) scheduledHours, ROUND(IFNULL(pate.ejecutadas, 0), 0) runningHours
                        , a.name agentName
                    from wg_report p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_report_agent pa on p.id = pa.project_id
                    left join wg_agent a on pa.agent_id = a.id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_report_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_report_agent_task pat
                                            where `status` = 'inactivo'
                                            group by pat.project_agent_id
                                        ) pate on pa.id = pate.project_agent_id";
        //Log::info($query);
        //Log::info($customerId);

        $where = "  WHERE YEAR(p.deliveryDate) =  YEAR(NOW()) ";

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

        $sql = $query.$where;

        $results = DB::select($sql, $whereArray);

        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllTaskBy($sorting = array(), $agentId) {


        $query = "	Select patp.*
                    from wg_report p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_report_agent pa on p.id = pa.project_id
                    inner join wg_agent a on pa.agent_id = a.id
                    left join (
                                            select id, project_agent_id, task, observation, startDateTime, type
                                            from wg_report_agent_task
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

        $query = "	select ct.id, concat('(', c.businessName, ') :',observation) title, eventDateTime starts_at, eventDateTime ends_at, 'to-do' type, 'tracking' tableName
                    from wg_customer_tracking ct
                    inner join wg_customers c on c.id = ct.customer_id
                    where ct.createdBy = :agent_id and ct.status = 'iniciado'
                    union ALL
                    select agt.id, concat('(', c.businessName, ') :',task ) title, startDateTime starts_at, endDateTime ends_at, 'cancelled' type, 'agentTask' tableName
                    from wg_report_agent_task agt
                    inner join wg_report_agent ag on agt.project_agent_id = ag.id
                    inner join wg_report cp on ag.project_id = cp.id
                    inner join wg_customers c on cp.customer_id = c.id
                    where agt.createdBy = :agentId and agt.status = 'activo'
                    union all
                    SELECT ac.id, concat('(', c.businessName, ') :',description) title, closeDateTime starts_at, closeDateTime ends_at, 'off-site-work' type, 'actionPlan' tableName
                    from wg_customer_management_detail_action_plan ac
                    inner join wg_customer_management_detail md on ac.management_detail_id = md.id
                    inner join wg_customer_management cm on md.management_id = cm.id
                    inner join wg_customers c on cm.customer_id = c.id
                    where ac.createdBy = :_agentId and ac.status = 'abierto'" ;
        //Log::info($query);
        //Log::info($agentId);
        $results = DB::select( $query, array(
            'agent_id' => $agentId,
            'agentId' => $agentId,
            '_agentId' => $agentId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllTaskByProjectAgent($sorting = array(), $projectAgentId) {

        $query = "select pat.id, task, p.item type, startDateTime, endDateTime
                    from wg_report_agent_task pat
                    left join (
                                            select * from system_parameters
                                            where system_parameters.group = 'project_task_type'
                                            ) p on pat.type = p.value
                    where project_agent_id = :project_agent_id and pat.status = 'activo'";
        //Log::info($query);
        //Log::info($projectAgentId);
        $results = DB::select( $query, array(
            'project_agent_id' => $projectAgentId
        ));
        //Log::info(json_encode($results));
        return $results;
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
								FROM wg_report p
                    inner join wg_customers c on p.customer_id = c.id
                    inner join wg_report_agent pa on p.id = pa.project_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) planeadas
                                            from wg_report_agent_task pat
                                            where `status` = 'activo'
                                            group by pat.project_agent_id
                                        ) patp On pa.id = patp.project_agent_id
                    left join (
                                            select pat.id, pat.project_agent_id , SUM((TIME_TO_SEC(TIMEDIFF(pat.endDateTime, pat.startDateTime)) / 60) / 60) ejecutadas
                                            from wg_report_agent_task pat
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

    public function getAllCustomerBy($sorting = array(), $customerId) {

        $query = "select c.id, businessName name, p.item arl
                    from wg_customers c
                    left join (
                                            select * from system_parameters
                                            where system_parameters.group = 'arl'
                                            ) p on c.arl = p.value
                    order by businessName";
        //Log::info($query);
        //Log::info($customerId);
        $results = DB::select( $query );
        //Log::info(json_encode($results));
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
                                inner join wg_report_program cmp ON pp.id = cmp.program_id
                                inner join wg_program_management_category ppc ON pp.id = ppc.program_id
                                inner join wg_program_management_question ppq on ppc.id = ppq.category_id
                                left join (
                                            select wg_report_detail.*, wg_rate.text, wg_rate.value from wg_report_detail
                                            inner join wg_rate ON wg_report_detail.rate_id = wg_rate.id
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
                inner join wg_report_program cmp ON pp.id = cmp.program_id
                inner join wg_program_management_category pc on pp.id = pc.program_id
                inner join wg_program_management_question pq on pc.id = pq.category_id
                inner join wg_report_detail dp on pq.id 	= dp.question_id
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
                                        inner join wg_report_program cmp ON pp.id = cmp.program_id
                                        inner join wg_program_management_category ppc ON pp.id = ppc.program_id
                                        inner join wg_program_management_question ppq on ppc.id = ppq.category_id
                                        left join (
                                                                select wg_report_detail.*, wg_rate.text, wg_rate.value from wg_report_detail
                                                                inner join wg_rate ON wg_report_detail.rate_id = wg_rate.id
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
        $this->reportRepository = new CustomerProjectRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_report.customer_id', $search);
            $filters[] = array('wg_report.status', $search);
        }

        $this->reportRepository->setColumns(['wg_report.*']);

        return $this->reportRepository->getFilteredsOptional($filters, true, "");
    }

    public function saveManagementProgram($model)
    {
        $query = "insert into wg_report_program
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
        $query = "insert into wg_report_detail
                  select null id, :management_id diagnostic, pq.id question_id, null rate_id, null observation, 'activo' status
                        , :createdBy created, null updatedBy
                        , now() created_at, null updated_at
                    from wg_program_management pp
                    inner join wg_program_management_category pc on pp.id = pc.program_id
                    inner join wg_program_management_question pq on pc.id = pq.category_id
                    inner join wg_report cd on cd.id = :management_id2
                    left join wg_report_detail dp on dp.management_id = cd.id and dp.question_id = pq.id
                    where pp.`status` = 'activo' and pc.`status` = 'activo' and pq.`status` = 'activo' and dp.question_id is null";


        $results = DB::statement( $query, array(
            'management_id' => $model->id,
            'createdBy' => $model->createdBy,
            'management_id2' => $model->id
        ));

        //Log::info($results);

        return true;
    }
}
