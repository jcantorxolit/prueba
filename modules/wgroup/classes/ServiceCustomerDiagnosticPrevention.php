<?php

namespace Wgroup\Classes;

use Carbon\Carbon;
use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDiagnostic;
use Wgroup\Models\CustomerDiagnosticDTO;
use Wgroup\Models\CustomerDiagnosticPrevention;
use Wgroup\Models\CustomerDiagnosticPreventionReporistory;
use Wgroup\Models\CustomerDiagnosticReporistory;
use Exception;
use Log;
use RainLab\User\Models\User;
use Str;
use Wgroup\Models\ProgramPrevention;
use Wgroup\Models\ProgramPreventionCategory;
use DB;

class ServiceCustomerDiagnosticPrevention
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticPreventionRepository;

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

        $model = new CustomerDiagnosticPrevention();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticPreventionRepository = new CustomerDiagnosticPreventionReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticPreventionRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_diagnostic_prevention.diagnostic_id',
            'wg_customer_diagnostic_prevention.question_id',
            'wg_customer_diagnostic_prevention.rate_id',
            'wg_customer_diagnostic_prevention.observation',
            'wg_customer_diagnostic_prevention.status',
            'wg_customer_diagnostic_prevention.updated_at',
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
                    $this->customerDiagnosticPreventionRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticPreventionRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticPreventionRepository->sortBy('wg_customer_diagnostic_prevention.id', 'desc');
        }

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_diagnostic_prevention.diagnostic_id', $search);
            $filters[] = array('wg_customer_diagnostic_prevention.question_id', $search);
            $filters[] = array('wg_customer_diagnostic_prevention.rate_id', $search);
            $filters[] = array('wg_customer_diagnostic_prevention.observation', $search);
            $filters[] = array('wg_customer_diagnostic_prevention.status', $search);
            $filters[] = array('wg_customer_diagnostic_prevention.updated_at', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_diagnostic_prevention.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_diagnostic_prevention.status', '0');
        }


        $this->customerDiagnosticPreventionRepository->setColumns(['wg_customer_diagnostic_prevention.*']);

        return $this->customerDiagnosticPreventionRepository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {

        $model = new CustomerDiagnosticPrevention();
        $this->customerDiagnosticPreventionRepository = new CustomerDiagnosticPreventionReporistory($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_diagnostic_prevention.diagnostic_id', $search);
            $filters[] = array('wg_customer_diagnostic_prevention.question_id', $search);
            $filters[] = array('wg_customer_diagnostic_prevention.rate_id', $search);
            $filters[] = array('wg_customer_diagnostic_prevention.observation', $search);
            $filters[] = array('wg_customer_diagnostic_prevention.status', $search);
            $filters[] = array('wg_customer_diagnostic_prevention.updated_at', $search);
        }

        $this->customerDiagnosticPreventionRepository->setColumns(['wg_customer_diagnostic_prevention.*']);

        return $this->customerDiagnosticPreventionRepository->getFilteredsOptional($filters, true, "");
    }

    public function getCategoriesBy($programId)
    {
        return ProgramPreventionCategory::whereProgramId($programId)->get();
    }

    public function getPrograms($diagnosticId)
    {
        $sql = "select programa.id, name,  abbreviation, questions , answers, ROUND(IFNULL((answers / questions) * 100, 0), 2) advance
                      , ROUND(IFNULL((total / questions), 0), 2) average, ROUND(IFNULL(total, 0), 2) total
                    from(
                        select  pp.id, pp.`name`, pp.abbreviation ,count(*) questions
                                , sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
                                , sum(cdp.value) total
                                from wg_progam_prevention pp
                                inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
                                inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
                                left join (
                                            select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
                                            inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                                            where diagnostic_id = :diagnostic_id
                                    ) cdp on ppq.id = cdp.question_id
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                                group by  pp.`name`, pp.id
                    )programa
                ORDER BY programa.id ASC";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId
        ));

        return $results;
    }

    public function getQuestionsBy($diagnosticId, $program_id)
    {
        $sql = "SELECT dp.id, diagnostic_id, question_id, rate_id
                    , pq.description
                    , pq.article
                    , observation
                    , pc.id category_id
                    , wr.color
                    , cmdap.id actionPlanId
                FROM wg_progam_prevention pp
                INNER JOIN wg_progam_prevention_category pc ON pp.id = pc.program_id
                INNER JOIN wg_progam_prevention_question pq ON pc.id = pq.category_id
                inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = pq.id
                INNER JOIN wg_customer_diagnostic_prevention dp ON dp.question_id = pq.id
                INNER JOIN wg_customer_diagnostic cd on cd.id = dp.diagnostic_id
                LEFT JOIN wg_rate wr ON wr.id = dp.rate_id
                LEFT JOIN wg_customer_diagnostic_prevention_action_plan cmdap ON cmdap.diagnostic_detail_id = dp.id
                WHERE pp.`status` = 'activo' AND pc.`status` = 'activo' AND pq.`status` = 'activo'
                        AND cd.id = :diagnostic_id AND pp.id = :program_id
                        and ppqc.customer_size IN (select size from wg_customers c inner join wg_customer_diagnostic cd on cd.customer_id = c.id where cd.id = $diagnosticId)
                ORDER BY question_id";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId,
            'program_id' => $program_id,
        ));

        return $results;
    }

    public function getQuestionsByStatus($diagnosticId, $program_id, $rate)
    {
        $sql = "SELECT dp.id, diagnostic_id, pp.id program_id, question_id, rate_id
                    , pq.description
                    , pq.article
                    , observation
                    , pc.id category_id
                    , wr.color
                    , wr.code
                    , wr.text rateText
                    , cmdap.id actionPlanId
                FROM wg_progam_prevention pp
                INNER JOIN wg_progam_prevention_category pc ON pp.id = pc.program_id
                INNER JOIN wg_progam_prevention_question pq ON pc.id = pq.category_id
                INNER JOIN wg_customer_diagnostic_prevention dp ON dp.question_id = pq.id
                INNER JOIN wg_customer_diagnostic cd on cd.id = dp.diagnostic_id
                LEFT JOIN wg_rate wr ON wr.id = dp.rate_id
                LEFT JOIN wg_customer_diagnostic_prevention_action_plan cmdap ON cmdap.diagnostic_detail_id = dp.id ";

        $where = "  WHERE pp.`status` = 'activo' AND pc.`status` = 'activo' AND pq.`status` = 'activo' AND cd.id = :diagnostic_id ";
        $orderBy = " ORDER BY question_id";
        $whereArray = array(
            'diagnostic_id' => $diagnosticId
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

    public function getDashboardByCategory($diagnosticId, $program_id)
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
                        from wg_progam_prevention pp
                        inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
                        inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
                        left join (
                                    select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value, wg_rate.color, wg_rate.highlightColor
                                    from wg_customer_diagnostic_prevention
                                    inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                                    where diagnostic_id = :diagnostic_id
                            ) cdp on ppq.id = cdp.question_id
                        WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                        group by  ppc.`name`, ppc.id
            )programa
            where program_id = :program_id
            group by programa.category_id
            order by 1;";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId,
            'program_id' => $program_id,
        ));

        return $results;
    }

    public function getDashboardByProgram($diagnosticId)
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
                                        from wg_progam_prevention pp
                                        inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
                                        inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
                                        left join (
                                                                select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
                                                                inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                                                                where diagnostic_id = :diagnostic_id
                                                ) cdp on ppq.id = cdp.question_id
                                        WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                                        group by  pp.`name`, pp.id
                )programa
                order by 1";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId
        ));

        return $results;
    }

    public function getDashboardByDiagnostic($diagnosticId)
    {
        $sql = "select programa.diagnostic_id, questions
                            , answers
                            , ROUND(IFNULL(((answers / questions) * 100), 0), 2) advance
                            , ROUND(IFNULL((total / questions),0), 2) average
                            , ROUND(IFNULL(total, 0), 2) total
                    from(
                                        select  cdp.diagnostic_id, count(*) questions
                                        , sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
                                        , sum(cdp.value) total
                                        from wg_progam_prevention pp
                                        inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
                                        inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
                                        left join (
                                                                select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
                                                                inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                                                                where diagnostic_id = :diagnostic_id
                                                ) cdp on ppq.id = cdp.question_id
                                        WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                )programa";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId
        ));

        return $results;
    }

    public function fillMissingReportMonthly($diagnosticId, $userId)
    {
        $track = DB::table('wg_customer_diagnostic_prevention_tracking')
            ->select(DB::raw('MAX(`month`) `month`, MAX(`year`) `year`'))
            ->where('diagnostic_id', $diagnosticId)
            ->first();

        if ($track != null) {
            $today = Carbon::now('America/Bogota');
            $lastTime = Carbon::createFromDate($track->year, $track->month, 1, 'America/Bogota');

            $diffInMonths = $today->diffInMonths($lastTime);

            for ($i = 1; $i < $diffInMonths; $i++) {
                $currentTime = $lastTime->addMonths(1);
                $this->duplicateReportMonthly($diagnosticId, $track->year, $track->month, $currentTime->year, $currentTime->month, $userId);
            }
        }
    }

    public function duplicateReportMonthly($diagnosticId, $fromYear, $fromMonth, $toYear, $toMonth, $userId)
    {

        $query = "INSERT INTO wg_customer_diagnostic_prevention_tracking
select null id, O.diagnostic_id, O.program_id, O.questions , O.answers
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
from wg_customer_diagnostic_prevention_tracking O
left join wg_customer_diagnostic_prevention_tracking D on D.diagnostic_id = O.diagnostic_id AND D.`year` = :toYear_2 AND D.`month` = :toMonth_2
where O.diagnostic_id = :diagnostic_id and O.`month` = :fromMonth and O.`year` = :fromYear and D.id is null";


        DB::statement( $query, array(
            'diagnostic_id' => $diagnosticId,
            'toYear' => $toYear,
            'toMonth' => $toMonth,
            'toYear_2' => $toYear,
            'toMonth_2' => $toMonth,
            'fromYear' => $fromYear,
            'fromMonth' => $fromMonth,
            'user_id' => $userId,
        ));
    }

    public function saveReportMonthly($diagnosticId, $year, $month, $userId)
    {

        $query = "
insert into wg_customer_diagnostic_prevention_tracking
select programa.*
FROM
(

    select null id, $diagnosticId diagnostic_id, programa.id program_id, questions , answers
		, round((answers / questions) * 100, 2) advance
		, round((total / questions), 2) average, total
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
from(
    select  pp.id, pp.`name`, pp.abbreviation ,count(*) questions
						, sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
						, sum(cdp.value) total
						, sum(case when cdp.code = 'c' then 1 else 0 end) accomplish
						, sum(case when cdp.code = 'cp' then 1 else 0 end) partial_accomplish
						, sum(case when cdp.code = 'nc' then 1 else 0 end) no_accomplish
						, sum(case when cdp.code = 'na' then 1 else 0 end) no_apply
						, sum(case when ISNULL(cdp.id) then 1 else 0 end) no_answers
						from wg_progam_prevention pp
						inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
						inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
						left join (
        select
														wg_customer_diagnostic_prevention.*
														, wg_rate.text, wg_rate.value, wg_rate.code
												from wg_customer_diagnostic_prevention
												inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
												where diagnostic_id = :diagnostic_id
								) cdp on ppq.id = cdp.question_id
						WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
						group by  pp.`name`, pp.id
)programa ) programa
left join wg_customer_diagnostic_prevention_tracking cdpt
	on  programa.diagnostic_id = cdpt.diagnostic_id
    and programa.program_id = cdpt.program_id
    and programa.currentYear = cdpt.year
    and programa.currentMonth = cdpt.`month`
where cdpt.diagnostic_id is null";

        DB::statement( $query, array(
            'diagnostic_id' => $diagnosticId
        ));
    }

    public function updateReportMonthly($diagnosticId, $year, $month, $userId)
    {

        $query = "
update wg_customer_diagnostic_prevention_tracking as target
inner join
	(
			select null id, $diagnosticId diagnostic_id, programa.id program_id, questions , answers
					, round((answers / questions) * 100, 2) advance
					, round((total / questions), 2) average, total
					, accomplish
					, partial_accomplish
					, no_accomplish
					, no_apply
					, no_answers
					, $year currentYear
					, $month currentMonth
					, $userId updatedBy
					, NOW() updated_at
			from(
									select  pp.id, pp.`name`, pp.abbreviation ,count(*) questions
									, sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
									, sum(cdp.value) total
									, sum(case when cdp.code = 'c' then 1 else 0 end) accomplish
									, sum(case when cdp.code = 'cp' then 1 else 0 end) partial_accomplish
									, sum(case when cdp.code = 'nc' then 1 else 0 end) no_accomplish
									, sum(case when cdp.code = 'na' then 1 else 0 end) no_apply
									, sum(case when ISNULL(cdp.id) then 1 else 0 end) no_answers
									from wg_progam_prevention pp
									inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
									inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
									left join (
															select
																	wg_customer_diagnostic_prevention.*
																	, wg_rate.text, wg_rate.value, wg_rate.code
															from wg_customer_diagnostic_prevention
															inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
															where diagnostic_id = :diagnostic_id
											) cdp on ppq.id = cdp.question_id
									WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
									group by  pp.`name`, pp.id
			)programa
) as programa
	on  programa.diagnostic_id = target.diagnostic_id
			and programa.program_id = target.program_id
			and programa.currentYear = target.year
			and programa.currentMonth = target.`month`
set target.questions = programa.questions
		, target.answers = programa.answers
		, target.avgProgress = programa.advance
		, target.avgTotal = programa.average
		, target.total = programa.total
		, target.accomplish = programa.accomplish
		, target.`partial_accomplish` = programa.partial_accomplish
		, target.no_accomplish = programa.no_accomplish
		, target.no_apply = programa.no_apply
		, target.no_answer = programa. no_answers
		, target.updated_at = programa.updated_at
		, target.updatedBy = programa.updatedBy";


        DB::statement( $query, array(
            'diagnostic_id' => $diagnosticId
        ));

    }

    public function getExport($diagnosticId)
    {
        $query = "
select  pp.`name` programa, pp.abbreviation codigo
,ppq.description descripcion
,ppq.article articulo
,IFNULL(cdp.text,'N/A') calificacion
from wg_progam_prevention pp
inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
left join (
select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
where diagnostic_id = :diagnostic_id
) cdp on ppq.id = cdp.question_id
WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'";


        $results = DB::select($query, array(
            'diagnostic_id' => $diagnosticId
        ));

        return $results;

    }

    public function getExportAll($diagnosticId)
    {
        $query = "
select pp.name programa,  abbreviation codigo, questions preguntas, answers respuestas, round((answers / questions) * 100, 2)  avance, round((total / questions), 2) promedio, total
	,ppq.description descripcion
	,ppq.article articulo
	,IFNULL(cdp.text,'N/A') calificacion
from(
				select  pp.id, pp.`name`, pp.abbreviation ,count(*) questions
				, sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
				, sum(cdp.value) total
				from wg_progam_prevention pp
				inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
				inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
				left join (
										select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
										inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
										where diagnostic_id = :diagnostic_id_1
						) cdp on ppq.id = cdp.question_id
				WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
				group by  pp.`name`, pp.id
	)pp
inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
left join (
				select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
				inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
				where diagnostic_id = :diagnostic_id_2
) cdp on ppq.id = cdp.question_id
WHERE ppc.`status` = 'activo' AND ppq.`status` = 'activo'";


        $results = DB::select($query, array(
            'diagnostic_id_1' => $diagnosticId,
            'diagnostic_id_2' => $diagnosticId
        ));

        return $results;

    }

    public function getAllComment($search, $perPage = 10, $currentPage = 0, $diagnosticDetailId = 0) {

        $startFrom = ($currentPage-1) * $perPage;

        $query = "SELECT * FROM (
SELECT
	pc.id,
	pc.diagnostic_detail_id,
	pc.`comment`,
	u.`name` `user`,
	pc.created_at createdAt
FROM
	wg_customer_diagnostic_prevention_comment pc
INNER JOIN
	users u ON pc.createdBy = u.id
WHERE pc.diagnostic_detail_id = :diagnostic_detail_id) p";

        $limit = " LIMIT $startFrom , $perPage";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.comment like '%$search%' or p.name like '%$search%' or p.createdAt like '%$search%')";
        }

        $query.=$where;

        $order = " ORDER BY p.createdAt DESC ";

        $query.=$order.$limit;

        $results = DB::select( $query, array(
            'diagnostic_detail_id' => $diagnosticDetailId
        ));

        return $results;

    }

    public function getAllCommentCount($search = "", $diagnosticDetailId = 0)
    {

        $query = "SELECT * FROM (
SELECT
	pc.id,
	pc.diagnostic_detail_id,
	pc.`comment`,
	u.`name` `user`,
	pc.created_at createdAt
FROM
	wg_customer_diagnostic_prevention_comment pc
INNER JOIN
	users u ON pc.createdBy = u.id
WHERE pc.diagnostic_detail_id = :diagnostic_detail_id) p";

        $where = '';

        if ($search != "") {
            $where = " WHERE (p.comment like '%$search%' or p.name like '%$search%' or p.createdAt like '%$search%')";
        }

        $query.=$where;

        $order = " ORDER BY p.createdAt DESC ";

        $query.=$order;

        $results = DB::select( $query, array(
            'diagnostic_detail_id' => $diagnosticDetailId
        ));

        return count($results);
    }


}
