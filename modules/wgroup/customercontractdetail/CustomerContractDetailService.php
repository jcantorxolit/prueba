<?php

namespace Wgroup\CustomerContractDetail;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerManagementDetail;
use Wgroup\Models\CustomerManagementDetailRepository;
use Wgroup\Models\CustomerManagementPreventionReporistory;
use Wgroup\Models\ProgramManagementCategory;

class CustomerContractDetailService
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerContractorDetailRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "")
    {

        $model = new CustomerContractDetail();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerContractorDetailRepository = new CustomerContractDetailRepository($model);

        if ($perPage > 0) {
            $this->customerContractorDetailRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_contract_detail.contractor_id',
            'wg_customer_contract_detail.periodic_requirement_id',
            'wg_customer_contract_detail.rate_id',
            'wg_customer_contract_detail.observation',
            'wg_customer_contract_detail.status',
            'wg_customer_contract_detail.updated_at',
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
                    $this->customerContractorDetailRepository->sortBy($colName, $dir);
                } else {
                    $this->customerContractorDetailRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerContractorDetailRepository->sortBy('wg_customer_contract_detail.id', 'desc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_contract_detail.contractor_id', $search);
            $filters[] = array('wg_customer_contract_detail.periodic_requirement_id', $search);
            $filters[] = array('wg_customer_contract_detail.rate_id', $search);
            $filters[] = array('wg_customer_contract_detail.observation', $search);
            $filters[] = array('wg_customer_contract_detail.status', $search);
            $filters[] = array('wg_customer_contract_detail.updated_at', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_contract_detail.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_contract_detail.status', '0');
        }

        $this->customerContractorDetailRepository->setColumns(['wg_customer_contract_detail.*']);

        return $this->customerContractorDetailRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {

        $model = new CustomerContractDetail();
        $this->customerContractorDetailRepository = new CustomerContractDetailRepository($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_contract_detail.contractor_id', $search);
            $filters[] = array('wg_customer_contract_detail.periodic_requirement_id', $search);
            $filters[] = array('wg_customer_contract_detail.rate_id', $search);
            $filters[] = array('wg_customer_contract_detail.observation', $search);
            $filters[] = array('wg_customer_contract_detail.status', $search);
            $filters[] = array('wg_customer_contract_detail.updated_at', $search);
        }

        $this->customerContractorDetailRepository->setColumns(['wg_customer_contract_detail.*']);

        return $this->customerContractorDetailRepository->getFilteredsOptional($filters, true, "");
    }

    public function getPeriods($contractorId)
    {

        $query = "SELECT period,
       requirements,
       answers,
       ROUND( IFNULL( (answers / requirements) * 100, 0 ), 2 ) advance,
       ROUND( IFNULL((total / requirements), 0), 2 ) average,
       IFNULL(total, 0) total,
       NAME fullName,
       startDate,
       endDate
FROM
  ( SELECT ccd.period,
           count(*) requirements,
           SUM(CASE
                   WHEN ISNULL(ccd.rate_id) THEN 0
                   ELSE 1
               END) answers,
           SUM(wg_rate.`value`) total,
           MIN(ccd.updated_at) startDate,
           MAX(ccd.updated_at) endDate,
           u. NAME
   FROM wg_customer_contract_detail ccd
   INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
   INNER JOIN
     (
				SELECT *, 1 `month` FROM wg_customer_periodic_requirement where jan = 1
				UNION ALL
				SELECT *, 2 `month` FROM wg_customer_periodic_requirement where feb = 1
				UNION ALL
				SELECT *, 3 `month` FROM wg_customer_periodic_requirement where mar = 1
				UNION ALL
				SELECT *, 4 `month` FROM wg_customer_periodic_requirement where apr = 1
				UNION ALL
				SELECT *, 5 `month` FROM wg_customer_periodic_requirement where may = 1
				UNION ALL
				SELECT *, 6 `month` FROM wg_customer_periodic_requirement where jun = 1
				UNION ALL
				SELECT *, 7 `month` FROM wg_customer_periodic_requirement where jul = 1
				UNION ALL
				SELECT *, 8 `month` FROM wg_customer_periodic_requirement where aug = 1
				UNION ALL
				SELECT *, 9 `month` FROM wg_customer_periodic_requirement where sep = 1
				UNION ALL
				SELECT *, 10 `month` FROM wg_customer_periodic_requirement where oct = 1
				UNION ALL
				SELECT *, 11 `month` FROM wg_customer_periodic_requirement where nov = 1
				UNION ALL
				SELECT *, 12 `month` FROM wg_customer_periodic_requirement where `dec` = 1
		 ) cpr ON ccd.periodic_requirement_id = cpr.id AND ccd.`month` = cpr.`month`
   LEFT JOIN wg_rate ON ccd.rate_id = wg_rate.id
   INNER JOIN users u ON ccd.createdBy = u.id
   WHERE ccd.contractor_id = :contractor_id
     AND cpr.isActive = 1
   GROUP BY ccd.period ) periodic";

        $results = DB::select( $query, array(
            'contractor_id' => $contractorId
        ));

        return $results;
    }

    public function getRequirementsBy($contractorId, $period = 0, $perPage = 10, $currentPage = 0)
    {
        $startFrom = ($currentPage-1) * $perPage;

        $sql = "SELECT * FROM (
SELECT ccd.id,
       ccd.period,
       cpr.requirement,
       ccd.observation,
       wg_rate.color,
       cmdap.id actionPlanId,
       ccd.rate_id,
       ccd.periodic_requirement_id,
       IF(ccd.period = DATE_FORMAT(NOW(),'%Y%m'),1,0) isActive,
       ccd.contractor_id
FROM wg_customer_contract_detail ccd
INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
INNER JOIN
  (
				SELECT *, 1 `month` FROM wg_customer_periodic_requirement where jan = 1
				UNION ALL
				SELECT *, 2 `month` FROM wg_customer_periodic_requirement where feb = 1
				UNION ALL
				SELECT *, 3 `month` FROM wg_customer_periodic_requirement where mar = 1
				UNION ALL
				SELECT *, 4 `month` FROM wg_customer_periodic_requirement where apr = 1
				UNION ALL
				SELECT *, 5 `month` FROM wg_customer_periodic_requirement where may = 1
				UNION ALL
				SELECT *, 6 `month` FROM wg_customer_periodic_requirement where jun = 1
				UNION ALL
				SELECT *, 7 `month` FROM wg_customer_periodic_requirement where jul = 1
				UNION ALL
				SELECT *, 8 `month` FROM wg_customer_periodic_requirement where aug = 1
				UNION ALL
				SELECT *, 9 `month` FROM wg_customer_periodic_requirement where sep = 1
				UNION ALL
				SELECT *, 10 `month` FROM wg_customer_periodic_requirement where oct = 1
				UNION ALL
				SELECT *, 11 `month` FROM wg_customer_periodic_requirement where nov = 1
				UNION ALL
				SELECT *, 12 `month` FROM wg_customer_periodic_requirement where `dec` = 1
	) cpr ON ccd.periodic_requirement_id = cpr.id AND ccd.`month` = cpr.`month`
INNER JOIN users u ON ccd.createdBy = u.id
LEFT JOIN wg_rate ON ccd.rate_id = wg_rate.id
LEFT JOIN wg_customer_contract_detail_action_plan cmdap ON cmdap.contract_detail_id = ccd.id
WHERE ccd.contractor_id = :contractor_id
  AND cpr.isActive = 1
ORDER BY ccd.periodic_requirement_id ) p";

        if ($period != 0) {
            $sql .= " WHERE p.period = $period";
        }

        $orderBy = " ORDER BY p.period, p.periodic_requirement_id";

        $limit = " LIMIT $startFrom , $perPage";

        $sql .= $orderBy. $limit;

        $results = DB::select($sql, array(
            'contractor_id' => $contractorId
        ));

        return $results;
    }

    public function getRequirementsByCount($contractorId, $period = 0)
    {
        $sql = "SELECT * FROM (

SELECT ccd.id,
       ccd.period,
       cpr.requirement,
       ccd.observation,
       wg_rate.color,
       cmdap.id actionPlanId,
       ccd.rate_id,
       ccd.periodic_requirement_id,
       ccd.contractor_id
FROM wg_customer_contract_detail ccd
INNER JOIN wg_customer_contractor cc ON ccd.contractor_id = cc.id
INNER JOIN
  (
				SELECT *, 1 `month` FROM wg_customer_periodic_requirement where jan = 1
				UNION ALL
				SELECT *, 2 `month` FROM wg_customer_periodic_requirement where feb = 1
				UNION ALL
				SELECT *, 3 `month` FROM wg_customer_periodic_requirement where mar = 1
				UNION ALL
				SELECT *, 4 `month` FROM wg_customer_periodic_requirement where apr = 1
				UNION ALL
				SELECT *, 5 `month` FROM wg_customer_periodic_requirement where may = 1
				UNION ALL
				SELECT *, 6 `month` FROM wg_customer_periodic_requirement where jun = 1
				UNION ALL
				SELECT *, 7 `month` FROM wg_customer_periodic_requirement where jul = 1
				UNION ALL
				SELECT *, 8 `month` FROM wg_customer_periodic_requirement where aug = 1
				UNION ALL
				SELECT *, 9 `month` FROM wg_customer_periodic_requirement where sep = 1
				UNION ALL
				SELECT *, 10 `month` FROM wg_customer_periodic_requirement where oct = 1
				UNION ALL
				SELECT *, 11 `month` FROM wg_customer_periodic_requirement where nov = 1
				UNION ALL
				SELECT *, 12 `month` FROM wg_customer_periodic_requirement where `dec` = 1
	) cpr ON ccd.periodic_requirement_id = cpr.id AND ccd.`month` = cpr.`month`
INNER JOIN users u ON ccd.createdBy = u.id
LEFT JOIN wg_rate ON ccd.rate_id = wg_rate.id
LEFT JOIN wg_customer_contract_detail_action_plan cmdap ON cmdap.contract_detail_id = ccd.id
WHERE ccd.contractor_id = :contractor_id
  AND cpr.isActive = 1
) p";

        if ($period != 0) {
            $sql .= " WHERE p.period = $period";
        }

        $orderBy = " ORDER BY p.period, p.periodic_requirement_id";

        $sql .= $orderBy;

        $results = DB::select($sql, array(
            'contractor_id' => $contractorId
        ));

        return count($results);
    }

    public function getQuestionsByStatus($managementId, $program_id, $rate)
    {
        $sql = "SELECT 0 david2, dp.id, dp.management_id, pp.id program_id, question_id, rate_id
                    , pq.description
                    , pq.article
                    , observation
                    , pc.id category_id
                    , wr.color
                    , wr.code
                    , wr.text rateText
                    , cmdap.id actionPlanId
                FROM wg_program_management pp
                inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
                INNER JOIN wg_program_management_category pc ON pp.id = pc.program_id
                INNER JOIN wg_program_management_question pq ON pc.id = pq.category_id
                INNER JOIN wg_customer_management_detail dp ON dp.question_id = pq.id
                INNER JOIN wg_customer_management cd on cd.id = dp.management_id
                LEFT JOIN wg_rate wr ON wr.id = dp.rate_id
                LEFT JOIN wg_customer_management_detail_action_plan cmdap ON cmdap.management_detail_id = dp.id";

        $where = "  WHERE pp.`status` = 'activo' AND pc.`status` = 'activo' AND pq.`status` = 'activo' AND cd.id = :management_id and cmp.active = 1  and cmp.management_id = :managementId";
        $orderBy = " ORDER BY cmdap.closeDateTime, question_id";
        $whereArray = array(
            'management_id' => $managementId,
            'managementId' => $managementId,
        );

        if ($rate != 0) {
            $where .= " AND dp.rate_id = :rate";
            $whereArray["rate"] = $rate;
        }

        if ($program_id != 0) {
            $where .= " AND pp.id = :program_id";
            $whereArray["program_id"] = $program_id;
        }

        $sql .= $where.$orderBy;

        $results = DB::select($sql, $whereArray);

        return $results;
    }

    public function getDashboardByCategory($managementId, $program_id)
    {
        $sql = "select programa.category_id, SUM(questions) questions, SUM(answers) answers
                    , ROUND(IFNULL(SUM((answers / questions) * 100), 0), 2) advance
                    , ROUND(IFNULL(SUM(total	/ questions), 0), 2) average
                    , ROUND(IFNULL(SUM(total), 0), 2) total
            from(
                        select  ppc.id, ppc.`name`,count(*) questions
                        , sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
                        , sum(cdp.value) total
                        ,	cdp.rate_id, cdp.text, cdp.color, cdp.highlightColor
                        , ppq.category_id
                        , pp.id program_id
                        from wg_program_management pp
                        inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
                        inner join wg_program_management_category ppc ON pp.id = ppc.program_id
                        inner join wg_program_management_question ppq on ppc.id = ppq.category_id
                        left join (
                                    select wg_customer_management_detail.*, wg_rate.text, wg_rate.value, wg_rate.color, wg_rate.highlightColor
                                    from wg_customer_management_detail
                                    inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
                                    where management_id = :management_id
                            ) cdp on ppq.id = cdp.question_id
                        WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :managementId
                        group by  ppc.`name`, ppc.id
            )programa
            where program_id = :program_id
            group by programa.category_id
            order by 1;";

        $results = DB::select($sql, array(
            'management_id' => $managementId,
            'program_id' => $program_id,
            'managementId' => $managementId,
        ));

        return $results;
    }

    public function getDashboardByProgram($managementId)
    {
        $sql = "select programa.program_id, questions
                        , answers
                        , ROUND(IFNULL(((answers / questions) * 100), 0), 2) advance
                        , ROUND(IFNULL((total / questions),0), 2) average
                        , ROUND(IFNULL(total, 0), 2) total
                    from(
                                        select  pp.id program_id, pp.`name`,count(*) questions
                                        , sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
                                        , sum(cdp.value) total
                                        from wg_program_management pp
                                        inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
                                        inner join wg_program_management_category ppc ON pp.id = ppc.program_id
                                        inner join wg_program_management_question ppq on ppc.id = ppq.category_id
                                        left join (
                                                                select wg_customer_management_detail.*, wg_rate.text, wg_rate.value from wg_customer_management_detail
                                                                inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
                                                                where management_id = :management_id
                                                ) cdp on ppq.id = cdp.question_id
                                        WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :managementId
                                        group by  pp.`name`, pp.id
                )programa
                order by 1";

        $results = DB::select($sql, array(
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
                                        inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
                                        inner join wg_program_management_category ppc ON pp.id = ppc.program_id
                                        inner join wg_program_management_question ppq on ppc.id = ppq.category_id
                                        left join (
                                                                select wg_customer_management_detail.*, wg_rate.text, wg_rate.value from wg_customer_management_detail
                                                                inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
                                                                where management_id = :management_id
                                                ) cdp on ppq.id = cdp.question_id
                                        WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :managementId
                )programa";

        $results = DB::select($sql, array(
            'management_id' => $managementId,
            'managementId' => $managementId,
        ));

        return $results;
    }

    public function bulkInsert($contractorId, $createdBy, $period, $year, $month)
    {
        $query = "insert into wg_customer_contract_detail
                    select DISTINCT null id, :contractor_id contractor_id, prq.id periodic_requirement_id, :period period, :year currentYear, :month currentMonth, null rate_id, null observation, 'activo' status
                                            , :createdBy created, null updatedBy
                                            , now() created_at, null updated_at
                    from
                     	(
		SELECT
			r.id,
			r.customer_id,
			r.requirement,
			r.isActive,
			t.customer_contractor_type_id,
			CASE
		WHEN 1 = $month THEN
			jan
		WHEN 2 = $month THEN
			feb
		WHEN 3 = $month THEN
			mar
		WHEN 4 = $month THEN
			apr
		WHEN 5 = $month THEN
			may
		WHEN 6 = $month THEN
			jun
		WHEN 7 = $month THEN
			jul
		WHEN 8 = $month THEN
			aug
		WHEN 9 = $month THEN
			sep
		WHEN 10 = $month THEN
			oct
		WHEN 11 = $month THEN
			nov
		WHEN 12 = $month THEN
			`dec`
		END canShow
		FROM
			wg_customer_periodic_requirement r
		INNER JOIN wg_customer_periodic_requirement_contractor_type t on r.id = t.customer_periodic_requirement_id
	) prq
                    inner join wg_customer_contractor cc on prq.customer_id = cc.customer_id  and cc.contractor_type_id = prq.customer_contractor_type_id
                    left join wg_customer_contract_detail ccd on ccd.contractor_id = cc.id and ccd.periodic_requirement_id = prq.id and ccd.period = :onPeriod
                    where cc.id = $contractorId and prq.isActive = 1 AND prq.canShow = 1 and ccd.id is null";


        $results = DB::statement( $query, array(
            'contractor_id' => $contractorId,
            'createdBy' => $createdBy,
            'period' => $period,
            'onPeriod' => $period,
            'year' => $year,
            'month' => $month,
        ));

        //Log::info($results);

        return true;
    }

    public function bulkInsertSafety($contractorId, $createdBy, $period, $year, $month)
    {
        $query = "INSERT INTO `wg_customer_contractor_safety_inspection_list_item`
        SELECT
            NULL id,
            cc.id as customer_contractor_id,
            ccsi.id as  customer_contractor_safety_inspection_id,
            csil.id customer_safety_inspection_list_id,
            csicli.id customer_safety_inspection_config_list_item_id,
            NULL observation,
            NULL dangerousnessValue,
            NULL existingControlValue,
            NULL priorityValue,
            NULL action,
            1 isActive,
            :period as period,
            :month as `month`,
            :year as `year`,
            :createdBy createdBy,
            NULL updatedBy,
            NOW() created_at,
            NULL updated_at
        FROM
            wg_customer_safety_inspection csi
        INNER JOIN wg_customer_contractor cc
            on cc.customer_id = csi.customer_id AND cc.contractor_type_id = csi.contractorType
        INNER JOIN wg_customer_contractor_safety_inspection ccsi
            on ccsi.customer_safety_inspection_id  = csi.id and ccsi.customer_id = cc.contractor_id
        INNER JOIN wg_customer_safety_inspection_list csil on csil.customer_safety_inspection_id = csi.id
        INNER JOIN wg_customer_safety_inspection_config_list csicl on csicl.id = csil.customer_safety_inspection_config_list_id
        INNER JOIN wg_customer_safety_inspection_config_list_group csiclg on csiclg.customer_safety_inspection_config_list_id = csicl.id
        INNER JOIN wg_customer_safety_inspection_config_list_item csicli on csicli.customer_safety_inspection_config_list_group_id = csiclg.id
        LEFT JOIN wg_customer_contractor_safety_inspection_list_item csili
                on csili.customer_safety_inspection_list_id = csil.id
                    and csili.customer_safety_inspection_config_list_item_id = csicli.id
                    and csili.customer_contractor_id = cc.id
                    and csili.period = :onPeriod
        WHERE cc.id = :contractor_id and csicl.isActive = 1 and csiclg.isActive and csicli.isActive and csili.id is null ";


        $results = DB::statement( $query, array(
            'contractor_id' => $contractorId,
            'createdBy' => $createdBy,
            'period' => $period,
            'onPeriod' => $period,
            'year' => $year,
            'month' => $month,
        ));

        //Log::info($results);

        return true;
    }

    function decrypt($string, $key)
    {
        $result = "";
        $string = base64_decode($string);
        for($i=0; $i<strlen($string); $i++)
        {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char)-ord($keychar));
            $result.=$char;
        }
        return $result;
    }

    //p4v4svasquez
    function encrypt($string, $key)
    {
        $result = "";
        for($i=0; $i<strlen($string); $i++)
        {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $char = chr(ord($char)+ord($keychar));
            $result.=$char;
        }
        return base64_encode($result);
    }
}
