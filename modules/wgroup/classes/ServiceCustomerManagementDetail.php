<?php

namespace Wgroup\Classes;

use Carbon\Carbon;
use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerManagementDetail;
use Wgroup\Models\CustomerManagementDetailRepository;
use Wgroup\Models\CustomerManagementPreventionReporistory;
use Wgroup\Models\ProgramManagementCategory;

class ServiceCustomerManagementDetail
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerManagementDetailRepository;

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

        $model = new CustomerManagementDetail();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerManagementDetailRepository = new CustomerManagementDetailRepository($model);

        if ($perPage > 0) {
            $this->customerManagementDetailRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_management_detail.management_id',
            'wg_customer_management_detail.question_id',
            'wg_customer_management_detail.rate_id',
            'wg_customer_management_detail.observation',
            'wg_customer_management_detail.status',
            'wg_customer_management_detail.updated_at',
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
                    $this->customerManagementDetailRepository->sortBy($colName, $dir);
                } else {
                    $this->customerManagementDetailRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerManagementDetailRepository->sortBy('wg_customer_management_detail.id', 'desc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_management_detail.management_id', $search);
            $filters[] = array('wg_customer_management_detail.question_id', $search);
            $filters[] = array('wg_customer_management_detail.rate_id', $search);
            $filters[] = array('wg_customer_management_detail.observation', $search);
            $filters[] = array('wg_customer_management_detail.status', $search);
            $filters[] = array('wg_customer_management_detail.updated_at', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_management_detail.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_management_detail.status', '0');
        }

        $this->customerManagementDetailRepository->setColumns(['wg_customer_management_detail.*']);

        return $this->customerManagementDetailRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {

        $model = new CustomerManagementDetail();
        $this->customerManagementDetailRepository = new CustomerManagementDetailRepository($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_management_detail.management_id', $search);
            $filters[] = array('wg_customer_management_detail.question_id', $search);
            $filters[] = array('wg_customer_management_detail.rate_id', $search);
            $filters[] = array('wg_customer_management_detail.observation', $search);
            $filters[] = array('wg_customer_management_detail.status', $search);
            $filters[] = array('wg_customer_management_detail.updated_at', $search);
        }

        $this->customerManagementDetailRepository->setColumns(['wg_customer_management_detail.*']);

        return $this->customerManagementDetailRepository->getFilteredsOptional($filters, true, "");
    }

    public function getCategoriesBy($programId)
    {
        return ProgramManagementCategory::whereProgramId($programId)->get();
    }
///$perPage = 10, $currentPage = 0
    public function getPrograms($managementId)
    {
        $sql = "select programa.id, name,  abbreviation, questions , answers, ROUND(IFNULL((answers / questions) * 100, 0), 2) advance
                      , ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END ), 0 ), 2 ) AS average
                      , ROUND(IFNULL(total, 0), 2) total
                    from(
                        select  pp.id, pp.`name`, pp.abbreviation ,count(*) questions
                                , sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
                                , SUM( CASE WHEN pp.isWeighted AND cdp.code IN ( 'cp', 'c' ) THEN ppq.weightedValue ELSE cdp.value END ) total
                                , pp.isWeighted
                                from wg_program_management pp
                                INNER JOIN `wg_program_management_economic_sector` pec ON `pec`.`program_id` = `pp`.`id`
                                INNER JOIN `wg_economic_sector` ec ON `ec`.`id` = `pec`.`economic_sector_id`
                                INNER JOIN wg_customer_management_program cmp ON cmp.program_economic_sector_id = pec.id
                                INNER JOIN wg_customer_management mp ON mp.id = cmp.management_id
                                INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_workplace`.`id` = `cmp`.customer_workplace_id
                                    AND `wg_customer_config_workplace`.`customer_id` = `mp`.`customer_id`
                                INNER JOIN wg_program_management_category ppc ON pp.id = ppc.program_id
                                INNER JOIN wg_program_management_question ppq ON ppc.id = ppq.category_id
                                left join (
                                            select wg_customer_management_detail.*, wg_rate.text, wg_rate.value, wg_rate.code from wg_customer_management_detail
                                            inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
                                            where management_id = :management_id
                                    ) cdp on ppq.id = cdp.question_id
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' AND cmp.active = 1 and cmp.management_id = :managementId
                                group by  pp.`name`, pp.id
                    )programa
                ORDER BY programa.id ASC";

        $results = DB::select($sql, array(
            'management_id' => $managementId,
            'managementId' => $managementId,
        ));

        return $results;
    }

    public function getQuestionsBy($managementId, $program_id)
    {
        $sql = "SELECT dp.id, dp.management_id, question_id, rate_id
                    , pq.description
                    , pq.article
                    , observation
                    , pc.id category_id
                    , wr.color
                    , cmdap.id actionPlanId
                FROM wg_program_management pp
                inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
                INNER JOIN wg_program_management_category pc ON pp.id = pc.program_id
                INNER JOIN wg_program_management_question pq ON pc.id = pq.category_id
                INNER JOIN wg_customer_management_detail dp ON dp.question_id = pq.id
                INNER JOIN wg_customer_management cd on cd.id = dp.management_id
                LEFT JOIN wg_rate wr ON wr.id = dp.rate_id
                LEFT JOIN wg_customer_management_detail_action_plan cmdap ON cmdap.management_detail_id = dp.id
                WHERE pp.`status` = 'activo' AND pc.`status` = 'activo' AND pq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :managementId
                        AND cd.id = :management_id AND pp.id = :program_id
                ORDER BY question_id";

        $results = DB::select($sql, array(
            'management_id' => $managementId,
            'program_id' => $program_id,
            'managementId' => $managementId,
        ));

        return $results;
    }

    public function getProgramsPaging($managementId, $programId, $perPage = 10, $currentPage = 0)
    {
        $startFrom = ($currentPage-1) * $perPage;

        $sql = "SELECT DISTINCT prg.* FROM
(
	select programa.id, name,  abbreviation, questions , answers, ROUND(IFNULL((answers / questions) * 100, 0), 2) advance
			, ROUND(IFNULL((total / questions), 0), 2) average, ROUND(IFNULL(total, 0), 2) total
		from(
				select  pp.id, pp.`name`, pp.abbreviation ,count(*) questions
								, sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
								, sum(cdp.value) total
								from wg_program_management pp
								inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
								inner join wg_program_management_category ppc ON pp.id = ppc.program_id
								inner join wg_program_management_question ppq on ppc.id = ppq.category_id
								left join (
														select wg_customer_management_detail.*, wg_rate.text, wg_rate.value from wg_customer_management_detail
														inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
														where management_id = :management_id1
										) cdp on ppq.id = cdp.question_id
								WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' AND cmp.active = 1 and cmp.management_id = :management_id2
								group by  pp.`name`, pp.id
		)programa
) prg
INNER JOIN (
SELECT dp.id, dp.management_id, question_id, rate_id
                    , pq.description
                    , pq.article
                    , observation
                    , pc.id category_id
                    , wr.color
                    , cmdap.id actionPlanId
										,pp.id programId
                FROM wg_program_management pp
                inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
                INNER JOIN wg_program_management_category pc ON pp.id = pc.program_id
                INNER JOIN wg_program_management_question pq ON pc.id = pq.category_id
                INNER JOIN wg_customer_management_detail dp ON dp.question_id = pq.id
                INNER JOIN wg_customer_management cd on cd.id = dp.management_id
                LEFT JOIN wg_rate wr ON wr.id = dp.rate_id
                LEFT JOIN wg_customer_management_detail_action_plan cmdap ON cmdap.management_detail_id = dp.id
                WHERE pp.`status` = 'activo' AND pc.`status` = 'activo' AND pq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :management_id3
                        AND cd.id = :management_id4 AND pp.id = :program_id ) qs ON prg.id = qs.programId
                ORDER BY prg.id ASC";

        $limit = " LIMIT $startFrom , $perPage";

        $sql .= $limit;

        $results = DB::select($sql, array(
            'management_id1' => $managementId,
            'management_id2' => $managementId,
            'management_id3' => $managementId,
            'management_id4' => $managementId,
            'program_id' => $programId,
        ));

        return $results;
    }

    public function getQuestionsByPaging($managementId, $program_id, $perPage = 10, $currentPage = 0)
    {
        $startFrom = ($currentPage-1) * $perPage;

        $sql = "SELECT qs.* FROM
(
	select programa.id, name,  abbreviation, questions , answers, ROUND(IFNULL((answers / questions) * 100, 0), 2) advance
			, ROUND(IFNULL((total / questions), 0), 2) average, ROUND(IFNULL(total, 0), 2) total
		from(
				select  pp.id, pp.`name`, pp.abbreviation ,count(*) questions
								, sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
								, sum(cdp.value) total
								from wg_program_management pp
								inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
								inner join wg_program_management_category ppc ON pp.id = ppc.program_id
								inner join wg_program_management_question ppq on ppc.id = ppq.category_id
								left join (
														select wg_customer_management_detail.*, wg_rate.text, wg_rate.value from wg_customer_management_detail
														inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
														where management_id = :management_id1
										) cdp on ppq.id = cdp.question_id
								WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' AND cmp.active = 1 and cmp.management_id = :management_id2
								group by  pp.`name`, pp.id
		)programa
) prg
INNER JOIN (
SELECT dp.id, dp.management_id, question_id, rate_id
                    , pq.description
                    , pq.article
                    , observation
                    , pc.id category_id
                    , wr.color
                    , cmdap.id actionPlanId
										,pp.id programId
                FROM wg_program_management pp
                inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
                INNER JOIN wg_program_management_category pc ON pp.id = pc.program_id
                INNER JOIN wg_program_management_question pq ON pc.id = pq.category_id
                INNER JOIN wg_customer_management_detail dp ON dp.question_id = pq.id
                INNER JOIN wg_customer_management cd on cd.id = dp.management_id
                LEFT JOIN wg_rate wr ON wr.id = dp.rate_id
                LEFT JOIN wg_customer_management_detail_action_plan cmdap ON cmdap.management_detail_id = dp.id
                WHERE pp.`status` = 'activo' AND pc.`status` = 'activo' AND pq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :management_id3
                        AND cd.id = :management_id4 AND pp.id = :program_id ) qs ON prg.id = qs.programId
                ORDER BY qs.question_id ASC";

        $limit = " LIMIT $startFrom , $perPage";

        $sql .= $limit;

        $results = DB::select($sql, array(
            'management_id1' => $managementId,
            'management_id2' => $managementId,
            'management_id3' => $managementId,
            'management_id4' => $managementId,
            'program_id' => $program_id,
        ));

        return $results;
    }

    public function getQuestionsByPagingCount($managementId, $program_id)
    {


        $sql = "SELECT COUNT(*) qty FROM
(
	select programa.id, name,  abbreviation, questions , answers, ROUND(IFNULL((answers / questions) * 100, 0), 2) advance
			, ROUND(IFNULL((total / questions), 0), 2) average, ROUND(IFNULL(total, 0), 2) total
		from(
				select  pp.id, pp.`name`, pp.abbreviation ,count(*) questions
								, sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
								, sum(cdp.value) total
								from wg_program_management pp
								inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
								inner join wg_program_management_category ppc ON pp.id = ppc.program_id
								inner join wg_program_management_question ppq on ppc.id = ppq.category_id
								left join (
														select wg_customer_management_detail.*, wg_rate.text, wg_rate.value from wg_customer_management_detail
														inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
														where management_id = :management_id1
										) cdp on ppq.id = cdp.question_id
								WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' AND cmp.active = 1 and cmp.management_id = :management_id2
								group by  pp.`name`, pp.id
		)programa
) prg
INNER JOIN (
SELECT dp.id, dp.management_id, question_id, rate_id
                    , pq.description
                    , pq.article
                    , observation
                    , pc.id category_id
                    , wr.color
                    , cmdap.id actionPlanId
										,pp.id programId
                FROM wg_program_management pp
                inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
                INNER JOIN wg_program_management_category pc ON pp.id = pc.program_id
                INNER JOIN wg_program_management_question pq ON pc.id = pq.category_id
                INNER JOIN wg_customer_management_detail dp ON dp.question_id = pq.id
                INNER JOIN wg_customer_management cd on cd.id = dp.management_id
                LEFT JOIN wg_rate wr ON wr.id = dp.rate_id
                LEFT JOIN wg_customer_management_detail_action_plan cmdap ON cmdap.management_detail_id = dp.id
                WHERE pp.`status` = 'activo' AND pc.`status` = 'activo' AND pq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :management_id3
                        AND cd.id = :management_id4 AND pp.id = :program_id ) qs ON prg.id = qs.programId
                ORDER BY qs.question_id ASC";


        $results = DB::select($sql, array(
            'management_id1' => $managementId,
            'management_id2' => $managementId,
            'management_id3' => $managementId,
            'management_id4' => $managementId,
            'program_id' => $program_id,
        ));

        return count($results) > 0 ? $results[0]->qty : 0;
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
                INNER JOIN `wg_program_management_economic_sector` pec ON `pec`.`program_id` = `pp`.`id`
                INNER JOIN `wg_economic_sector` ec ON `ec`.`id` = `pec`.`economic_sector_id`
                INNER JOIN wg_customer_management_program cmp ON cmp.program_economic_sector_id = pec.id
                INNER JOIN wg_customer_management mp ON mp.id = cmp.management_id
                INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_workplace`.`id` = `cmp`.customer_workplace_id
                    AND `wg_customer_config_workplace`.`customer_id` = `mp`.`customer_id`

                INNER JOIN wg_program_management_category pc ON pp.id = pc.program_id
                INNER JOIN wg_program_management_question pq ON pc.id = pq.category_id
                INNER JOIN wg_customer_management_detail dp ON dp.question_id = pq.id and mp.id = dp.management_id
                LEFT JOIN wg_rate wr ON wr.id = dp.rate_id
                LEFT JOIN wg_customer_management_detail_action_plan cmdap ON cmdap.management_detail_id = dp.id";

        $where = "  WHERE pp.`status` = 'activo' AND pc.`status` = 'activo' AND pq.`status` = 'activo' AND mp.id = :management_id and cmp.active = 1  and cmp.management_id = :managementId";
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
                                        INNER JOIN `wg_program_management_economic_sector` pec ON `pec`.`program_id` = `pp`.`id`
                                        INNER JOIN `wg_economic_sector` ec ON `ec`.`id` = `pec`.`economic_sector_id`
                                        INNER JOIN wg_customer_management_program cmp ON cmp.program_economic_sector_id = pec.id
                                        INNER JOIN wg_customer_management mp ON mp.id = cmp.management_id
                                        INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_workplace`.`id` = `cmp`.customer_workplace_id
                                            AND `wg_customer_config_workplace`.`customer_id` = `mp`.`customer_id`
                                        INNER JOIN wg_program_management_category ppc ON pp.id = ppc.program_id
                                        INNER JOIN wg_program_management_question ppq ON ppc.id = ppq.category_id
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

    public function fillMissingReportMonthly($managementId, $userId)
    {
        $track = DB::table('wg_customer_management_detail_tracking')
            ->select(DB::raw('MAX(`month`) `month`, MAX(`year`) `year`'))
            ->where('management_id', $managementId)
            ->first();

        if ($track != null) {
            $today = Carbon::now('America/Bogota');
            $lastTime = Carbon::createFromDate($track->year, $track->month, 1, 'America/Bogota');

            $diffInMonths = $today->diffInMonths($lastTime);

            for ($i = 1; $i < $diffInMonths; $i++) {
                $currentTime = $lastTime->addMonths(1);
                $this->duplicateReportMonthly($managementId, $track->year, $track->month, $currentTime->year, $currentTime->month, $userId);
            }
        }
    }

    public function duplicateReportMonthly($managementId, $fromYear, $fromMonth, $toYear, $toMonth, $userId)
    {

        $query = "INSERT INTO wg_customer_management_detail_tracking
select null id, O.management_id, O.program_id, O.questions , O.answers
						, O.avgProgress
						, O.avgTotal
						, O.total
						, O.accomplish
						, O.partial_accomplish
						, O.no_accomplish
						, O.no_apply
						, O.no_answer
						, :toYear currentYear
						, :toMonth currentMonth
						, :user_id createdBy
						, NOW() created_at
						, null updatedBy
						, null updated_at
from wg_customer_management_detail_tracking O
left join wg_customer_management_detail_tracking D on D.management_id = O.management_id AND D.`year` = :toYear_2 AND D.`month` = :toMonth_2
where O.management_id = :management_id and O.`month` = :fromMonth and O.`year` = :fromYear and D.id is null";


        DB::statement( $query, array(
            'management_id' => $managementId,
            'toYear' => $toYear,
            'toMonth' => $toMonth,
            'toYear_2' => $toYear,
            'toMonth_2' => $toMonth,
            'fromYear' => $fromYear,
            'fromMonth' => $fromMonth,
            'user_id' => $userId,
        ));
    }

    public function saveReportMonthly($managementId, $year, $month, $userId)
    {

        $query = "INSERT INTO wg_customer_management_detail_tracking
SELECT programa.*
FROM
	(

				select null id, $managementId management_id, programa.id program_id, questions , answers
						, round((answers / questions) * 100, 2) advance
						, ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END ), 0 ), 2 ) AS average
                        , total
						, accomplish
						, partial_accomplish
						, no_accomplish
						, no_apply
						, no_answers
						, $year currentYear
						, $month currentMonth
						, $userId createdBy
						, null updatedBy
						, NOW() created_at
						, null updated_at
				from  (
										select  pp.id, pp.`name`, pp.abbreviation, pp.isWeighted ,count(*) questions
												, sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
												, SUM( CASE WHEN pp.isWeighted AND cdp.code IN ( 'cp', 'c' ) THEN ppq.weightedValue ELSE cdp.value END ) total
												, sum(case when cdp.code = 'c' then 1 else 0 end) accomplish
												, sum(case when cdp.code = 'cp' then 1 else 0 end) partial_accomplish
												, sum(case when cdp.code = 'nc' then 1 else 0 end) no_accomplish
												, sum(case when cdp.code = 'na' then 1 else 0 end) no_apply
												, sum(case when ISNULL(cdp.id) then 1 else 0 end) no_answers
										from wg_program_management pp
                                        INNER JOIN `wg_program_management_economic_sector` pec ON `pec`.`program_id` = `pp`.`id`
                                        INNER JOIN `wg_economic_sector` ec ON `ec`.`id` = `pec`.`economic_sector_id`
                                        INNER JOIN wg_customer_management_program cmp ON cmp.program_economic_sector_id = pec.id
                                        INNER JOIN wg_customer_management mp ON mp.id = cmp.management_id
                                        INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_workplace`.`id` = `cmp`.customer_workplace_id
                                            AND `wg_customer_config_workplace`.`customer_id` = `mp`.`customer_id`
                                        INNER JOIN wg_program_management_category ppc ON pp.id = ppc.program_id
                                        INNER JOIN wg_program_management_question ppq ON ppc.id = ppq.category_id
										left join (
																select wg_customer_management_detail.*, wg_rate.text, wg_rate.value , wg_rate.code
																from wg_customer_management_detail
																inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
																where management_id = :management_id_1
												) cdp on ppq.id = cdp.question_id
										WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :management_id_2
										group by  pp.`name`, pp.id
				)programa
	) programa
	left join wg_customer_management_detail_tracking cdpt
	on  programa.management_id = cdpt.management_id
			and programa.program_id = cdpt.program_id
			and programa.currentYear = cdpt.year
			and programa.currentMonth = cdpt.`month`
where cdpt.management_id is null";


        DB::statement( $query, array(
            'management_id_1' => $managementId,
            'management_id_2' => $managementId
        ));
    }

    public function updateReportMonthly($managementId, $year, $month, $userId)
    {

        
        $query = "update wg_customer_management_detail_tracking as target
inner join
	(
			select null id, $managementId management_id, programa.id program_id, questions , answers
						, round((answers / questions) * 100, 2) advance
						, ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END ), 0 ), 2 ) AS average
						, total
                        , accomplish
						, partial_accomplish
						, no_accomplish
						, no_apply
						, no_answers
						, $year currentYear
						, $month currentMonth
						, $userId updatedBy
						, NOW() updated_at
				from  (
										select  pp.id, pp.`name`, pp.abbreviation, pp.isWeighted ,count(*) questions
												, sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
												, SUM( CASE WHEN pp.isWeighted AND cdp.code IN ( 'cp', 'c' ) THEN ppq.weightedValue ELSE cdp.value END ) total
												, sum(case when cdp.code = 'c' then 1 else 0 end) accomplish
												, sum(case when cdp.code = 'cp' then 1 else 0 end) partial_accomplish
												, sum(case when cdp.code = 'nc' then 1 else 0 end) no_accomplish
												, sum(case when cdp.code = 'na' then 1 else 0 end) no_apply
												, sum(case when ISNULL(cdp.id) then 1 else 0 end) no_answers
										from wg_program_management pp
                                        INNER JOIN `wg_program_management_economic_sector` pec ON `pec`.`program_id` = `pp`.`id`
                                        INNER JOIN `wg_economic_sector` ec ON `ec`.`id` = `pec`.`economic_sector_id`
                                        INNER JOIN wg_customer_management_program cmp ON cmp.program_economic_sector_id = pec.id
                                        INNER JOIN wg_customer_management mp ON mp.id = cmp.management_id
                                        INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_workplace`.`id` = `cmp`.customer_workplace_id
                                            AND `wg_customer_config_workplace`.`customer_id` = `mp`.`customer_id`
                                        INNER JOIN wg_program_management_category ppc ON pp.id = ppc.program_id
                                        INNER JOIN wg_program_management_question ppq ON ppc.id = ppq.category_id
										left join (
																select wg_customer_management_detail.*, wg_rate.text, wg_rate.value , wg_rate.code
																from wg_customer_management_detail
																inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
																where management_id = :management_id_1
												) cdp on ppq.id = cdp.question_id
										WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :management_id_2
										group by  pp.`name`, pp.id
				)programa
) as origin
	on  origin.management_id = target.management_id
			and origin.program_id = target.program_id
			and origin.currentYear = target.year
			and origin.currentMonth = target.`month`
set target.questions = origin.questions
		, target.answers = origin.answers
		, target.avgProgress = origin.advance
		, target.avgTotal = origin.average
		, target.total = origin.total
		, target.accomplish = origin.accomplish
		, target.`partial_accomplish` = origin.partial_accomplish
		, target.no_accomplish = origin.no_accomplish
		, target.no_apply = origin.no_apply
		, target.no_answer = origin. no_answers
		, target.updated_at = origin.updated_at
		, target.updatedBy = origin.updatedBy";

        DB::statement( $query, array(
            'management_id_1' => $managementId,
            'management_id_2' => $managementId
        ));


    }

    public function getExport($managementId)
    {
        $query = "SELECT
        `wg_program_management`.`name` AS `Programa`,
        `wg_program_management`.abbreviation AS `Abreviación`,
        `wg_program_management_question`.`description` AS `Descripción`,
        `wg_program_management_question`.`article` AS `Artículo`,
        IFNULL(wg_rate.text,'N/A') AS `Calificación`
    FROM
        `wg_customer_management_detail`
    INNER JOIN `wg_customer_management` ON `wg_customer_management`.`id` = `wg_customer_management_detail`.`management_id`
    INNER JOIN `wg_program_management_question` ON `wg_program_management_question`.`id` = `wg_customer_management_detail`.`question_id`
    INNER JOIN `wg_program_management_category` ON `wg_program_management_category`.`id` = `wg_program_management_question`.`category_id`
    INNER JOIN `wg_customer_management_program` ON `wg_customer_management_program`.`management_id` = `wg_customer_management`.`id`
    INNER JOIN `wg_program_management_economic_sector` ON `wg_program_management_economic_sector`.`id` = `wg_customer_management_program`.`program_economic_sector_id`
    INNER JOIN `wg_economic_sector` ON `wg_economic_sector`.`id` = `wg_program_management_economic_sector`.`economic_sector_id`
    INNER JOIN `wg_program_management` ON `wg_program_management`.`id` = `wg_program_management_economic_sector`.`program_id`
    AND `wg_program_management`.`id` = `wg_program_management_category`.`program_id`
    LEFT JOIN `wg_rate` ON `wg_rate`.`id` = `wg_customer_management_detail`.`rate_id`
    WHERE
        `wg_customer_management_program`.`active` = '1'
    AND `wg_program_management_category`.`status` = 'activo'
    AND `wg_program_management_question`.`status` = 'activo'
    AND `wg_customer_management_detail`.`management_id` = :management_id
    ORDER BY
        `wg_customer_management_detail`.`id` ASC";


        $results = DB::select($query, array(
            'management_id' => $managementId
        ));

        return $results;

    }

    public function getExportAll($managementId, $programId)
    {
        $query = "select pp.name programa,  pp.abbreviation codigo, questions preguntas, answers respuestas, round((answers / questions) * 100, 2)  avance, round((total / questions), 2) promedio, total
	,ppq.description descripcion
	,ppq.article articulo
	,IFNULL(cdp.text,'N/A') calificacion
from(

				select  pp.id, pp.`name`, pp.abbreviation ,count(*) questions
				, sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
				, sum(cdp.value) total
				from wg_program_management pp
				inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
				inner join wg_program_management_category ppc ON pp.id = ppc.program_id
				inner join wg_program_management_question ppq on ppc.id = ppq.category_id
				left join (
										select wg_customer_management_detail.*, wg_rate.text, wg_rate.value from wg_customer_management_detail
										inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
										where management_id = :management_id_1
						) cdp on ppq.id = cdp.question_id
				WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' AND cmp.active = 1
				group by  pp.`name`, pp.id


	)pp
inner join wg_customer_management_program ppc ON pp.id = ppc.program_id
inner join wg_program_management_question ppq on ppc.id = ppq.category_id
left join (
				select wg_customer_management_detail.*, wg_rate.text, wg_rate.value from wg_customer_management_detail
				inner join wg_rate ON wg_customer_management_detail.rate_id = wg_rate.id
				where management_id = :management_id_2
) cdp on ppq.id = cdp.question_id
WHERE ppc.active = 1 AND ppq.`status` = 'activo' AND pp.id = :program_id";


        $results = DB::select($query, array(
            'management_id_1' => $managementId,
            'management_id_2' => $managementId,
            'program_id' => $programId,
        ));

        return $results;

    }

    public function getAllComment($search, $perPage = 10, $currentPage = 0, $managementDetailId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
SELECT
	pc.id,
	pc.management_detail_id,
	pc.`comment`,
	u.`name` `user`,
	pc.created_at createdAt
FROM
	wg_customer_management_detail_comment pc
INNER JOIN
	users u ON pc.createdBy = u.id
WHERE pc.management_detail_id = :management_detail_id) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.comment like '%$search%' or p.name like '%$search%' or p.createdAt like '%$search%')";
        }

        $query.=$where;

        $order = " ORDER BY p.createdAt DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            'management_detail_id' => $managementDetailId
        ));

        return $results;

    }

    public function getAllCommentCount($search = "", $managementDetailId = 0)
    {

        $query = "SELECT * FROM (
SELECT
	pc.id,
	pc.management_detail_id,
	pc.`comment`,
	u.`name` `user`,
	pc.created_at createdAt
FROM
	wg_customer_management_detail_comment pc
INNER JOIN
	users u ON pc.createdBy = u.id
WHERE pc.management_detail_id = :management_detail_id) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.comment like '%$search%' or p.name like '%$search%' or p.createdAt like '%$search%')";
        }

        $query.=$where;

        $order = " ORDER BY p.createdAt DESC ";

        $query.=$order;

        $results = DB::select( $query, array(
            'management_detail_id' => $managementDetailId
        ));

        return count($results);
    }
}
