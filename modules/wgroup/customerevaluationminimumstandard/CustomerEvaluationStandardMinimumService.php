<?php

namespace Wgroup\CustomerEvaluationStandardMinimum;

use DB;
use Exception;
use Log;
use Str;

class CustomerEvaluationStandardMinimumService
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

        $model = new CustomerEvaluationStandardMinimum();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerEvaluationStandardMinimumRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_evaluation_minimum_standard.id',
            'wg_customer_evaluation_minimum_standard.customer_id',
            'wg_customer_evaluation_minimum_standard.startDate',
            'wg_customer_evaluation_minimum_standard.endDate',
            'wg_customer_evaluation_minimum_standard.status',
            'wg_customer_evaluation_minimum_standard.type',
            'wg_customer_evaluation_minimum_standard.description'
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
            $this->repository->sortBy('wg_customer_evaluation_minimum_standard.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_evaluation_minimum_standard.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_evaluation_minimum_standard.startDate', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard.endDate', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard.status', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard.type', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard.description', $search);
        }

        $this->repository->setColumns(['wg_customer_evaluation_minimum_standard.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId)
    {

        $model = new CustomerEvaluationStandardMinimum();
        $this->repository = new CustomerEvaluationStandardMinimumRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_evaluation_minimum_standard.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_evaluation_minimum_standard.startDate', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard.endDate', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard.status', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard.type', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard.description', $search);
        }

        $this->repository->setColumns(['wg_customer_evaluation_minimum_standard.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }

    public function saveDiagnosticQuestion($model)
    {
        $query = "insert into wg_customer_diagnostic_prevention
                  select null id, :customer_evaluation_minimum_standard_id diagnostic, pq.id question_id, null rate_id, null observation, 'activo' status
                        , :createdBy created, null updatedBy
                        , now() created_at, null updated_at
                    from wg_progam_prevention pp
                    inner join wg_progam_prevention_category pc on pp.id = pc.program_id
                    inner join wg_progam_prevention_question pq on pc.id = pq.category_id
                    inner join wg_customer_diagnostic cd on cd.id = :customer_evaluation_minimum_standard_id2
                    left join wg_customer_diagnostic_prevention dp on dp.customer_evaluation_minimum_standard_id = cd.id and dp.question_id = pq.id
                    where pp.`status` = 'activo' and pc.`status` = 'activo' and pq.`status` = 'activo' and dp.question_id is null";


        $results = DB::statement($query, array(
            'customer_evaluation_minimum_standard_id' => $model->id,
            'createdBy' => $model->createdBy,
            'customer_evaluation_minimum_standard_id2' => $model->id
        ));

        //Log::info($results);

        return true;
    }

    public function saveDiagnosticAccident($model)
    {
        $query = "insert into wg_customer_diagnostic_accident
                  select null id, :customer_evaluation_minimum_standard_id diagnostic, wa.id question_id, 0 numberOfAT, 0 disabilityDay, 0 unsafeAct, 0 unsafeCondition, '' description, '' correctiveMeasure
                        , :createdBy created, null updatedBy
                        , now() created_at, null updated_at
                    from wg_accident wa
                    where wa.`status` = 'activo'";


        $results = DB::statement($query, array(
            'customer_evaluation_minimum_standard_id' => $model->id,
            'createdBy' => $model->createdBy
        ));

        //Log::info($results);

        return true;
    }

    public function getAllSummary($sorting = array(), $customerEvaluationMinimumStandardId)
    {

        $columnNames = ["id", "questions", "answers", "average"];
        $columnOrder = "id";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "id") {
                $dirOrder = "asc";
            } else
                $dirOrder = $sorting[0]["dir"];
        }

        $query = "select programa.id, name,  abbreviation, questions , answers, round((answers / questions) * 100, 2) advance, round((total / questions), 2) average, total
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
                                            where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
                                    ) cdp on ppq.id = cdp.question_id
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                                group by  pp.`name`, pp.id
                    )programa
                    order by $columnOrder $dirOrder";

        $results = DB::select($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
        ));

        return $results;
    }

    public function getAllSummaryExport($sorting = array(), $customerEvaluationMinimumStandardId)
    {

        $columnNames = ["id", "questions", "answers", "average"];
        $columnOrder = "id";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "id") {
                $dirOrder = "asc";
            } else
                $dirOrder = $sorting[0]["dir"];
        }

        $query = "select name Programa,  abbreviation Codigo, questions `Nro Preguntas`, answers `Nro Respuestas`, round((answers / questions) * 100, 2) `% Avannce`, round((total / questions), 2) `Promedio`, total `Total`
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
                                            where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
                                    ) cdp on ppq.id = cdp.question_id
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                                group by  pp.`name`, pp.id
                    )programa
                    order by $columnOrder $dirOrder";
        //Log::info($query);
        //Log::info($customerEvaluationMinimumStandardId);
        $results = DB::select($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getYearFilter($customerEvaluationMinimumStandardId)
    {

        $query = "SELECT
	DISTINCT 0 id, o.`year` item, o.`year` `value`
FROM
	wg_customer_diagnostic_prevention_tracking o
WHERE customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
ORDER BY o.`year` DESC
";
        $results = DB::select($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId
        ));

        return $results;
    }

    public function getAllSummaryByYear($customerEvaluationMinimumStandardId, $year)
    {

        $query = "select  p.`id`, p.`name`, p.abbreviation
	, MAX(case when `month` = 1 then ROUND(IFNULL(o.avgTotal,0),2) end) ENE
	, MAX(case when `month` = 2 then ROUND(IFNULL(o.avgTotal,0),2) end) FEB
	, MAX(case when `month` = 3 then ROUND(IFNULL(o.avgTotal,0),2) end) MAR
	, MAX(case when `month` = 4 then ROUND(IFNULL(o.avgTotal,0),2) end) ABR
	, MAX(case when `month` = 5 then ROUND(IFNULL(o.avgTotal,0),2) end) MAY
	, MAX(case when `month` = 6 then ROUND(IFNULL(o.avgTotal,0),2) end) JUN
	, MAX(case when `month` = 7 then ROUND(IFNULL(o.avgTotal,0),2) end) JUL
	, MAX(case when `month` = 8 then ROUND(IFNULL(o.avgTotal,0),2) end) AGO
	, MAX(case when `month` = 9 then ROUND(IFNULL(o.avgTotal,0),2) end) SEP
	, MAX(case when `month` = 10 then ROUND(IFNULL(o.avgTotal,0),2) end) OCT
	, MAX(case when `month` = 11 then ROUND(IFNULL(o.avgTotal,0),2) end) NOV
	, MAX(case when `month` = 12 then ROUND(IFNULL(o.avgTotal,0),2) end) DIC
from
	wg_customer_diagnostic_prevention_tracking o
inner join wg_progam_prevention p on o.program_id = p.id
where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id and o.`year` = :year
group by program_id";


        $results = DB::select($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByProgramExport($sorting = array(), $customerEvaluationMinimumStandardId, $year)
    {

        $columnNames = ["name", "questions", "answers", "average"];
        $columnOrder = "id";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "id") {
                $dirOrder = "asc";
            } else
                $dirOrder = $sorting[0]["dir"];
        }

        $query = "select  p.`name` Programa, p.abbreviation Codigo
	, MAX(case when `month` = 1 then ROUND(IFNULL(o.avgTotal,0),2) end) ENE
	, MAX(case when `month` = 2 then ROUND(IFNULL(o.avgTotal,0),2) end) FEB
	, MAX(case when `month` = 3 then ROUND(IFNULL(o.avgTotal,0),2) end) MAR
	, MAX(case when `month` = 4 then ROUND(IFNULL(o.avgTotal,0),2) end) ABR
	, MAX(case when `month` = 5 then ROUND(IFNULL(o.avgTotal,0),2) end) MAY
	, MAX(case when `month` = 6 then ROUND(IFNULL(o.avgTotal,0),2) end) JUN
	, MAX(case when `month` = 7 then ROUND(IFNULL(o.avgTotal,0),2) end) JUL
	, MAX(case when `month` = 8 then ROUND(IFNULL(o.avgTotal,0),2) end) AGO
	, MAX(case when `month` = 9 then ROUND(IFNULL(o.avgTotal,0),2) end) SEP
	, MAX(case when `month` = 10 then ROUND(IFNULL(o.avgTotal,0),2) end) OCT
	, MAX(case when `month` = 11 then ROUND(IFNULL(o.avgTotal,0),2) end) NOV
	, MAX(case when `month` = 12 then ROUND(IFNULL(o.avgTotal,0),2) end) DIC
from
	wg_customer_diagnostic_prevention_tracking o
inner join wg_progam_prevention p on o.program_id = p.id
where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id and o.`year` = :year
group by program_id";


        $results = DB::select($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByIndicator($sorting = array(), $customerEvaluationMinimumStandardId, $year)
    {

        $columnNames = ["name", "questions", "answers", "average"];
        $columnOrder = "id";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "id") {
                $dirOrder = "asc";
            } else
                $dirOrder = $sorting[0]["dir"];
        }

        $query = "SELECT
 i.indicator
	, MAX(case when `month` = 1 then ROUND(IFNULL(`value`,0),2) end) ENE
	, MAX(case when `month` = 2 then ROUND(IFNULL(`value`,0),2) end) FEB
	, MAX(case when `month` = 3 then ROUND(IFNULL(`value`,0),2) end) MAR
	, MAX(case when `month` = 4 then ROUND(IFNULL(`value`,0),2) end) ABR
	, MAX(case when `month` = 5 then ROUND(IFNULL(`value`,0),2) end) MAY
	, MAX(case when `month` = 6 then ROUND(IFNULL(`value`,0),2) end) JUN
	, MAX(case when `month` = 7 then ROUND(IFNULL(`value`,0),2) end) JUL
	, MAX(case when `month` = 8 then ROUND(IFNULL(`value`,0),2) end) AGO
	, MAX(case when `month` = 9 then ROUND(IFNULL(`value`,0),2) end) SEP
	, MAX(case when `month` = 10 then ROUND(IFNULL(`value`,0),2) end) OCT
	, MAX(case when `month` = 11 then ROUND(IFNULL(`value`,0),2) end) NOV
	, MAX(case when `month` = 12 then ROUND(IFNULL(`value`,0),2) end) DIC

 FROM (
	select 1 `position`, customer_evaluation_minimum_standard_id, 'Preguntas' indicator, SUM(questions) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 2 `position`, customer_evaluation_minimum_standard_id, 'Respuestas' indicator, SUM(answers) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 3 `position`, customer_evaluation_minimum_standard_id, 'Cumple' indicator, SUM(accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 4 `position`, customer_evaluation_minimum_standard_id, 'Cumple Parcial' indicator, SUM(partial_accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 5 `position`, customer_evaluation_minimum_standard_id, 'No Cumple' indicator, SUM(no_accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 6 `position`, customer_evaluation_minimum_standard_id, 'No Aplica' indicator, SUM(no_apply) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 7 `position`, customer_evaluation_minimum_standard_id, 'Sin Respuesta' indicator, SUM(no_answer) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 8 `position`, customer_evaluation_minimum_standard_id, 'Promedio Total %' indicator, (SUM(total) / SUM(questions)) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
) i
where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id and `year` = :year
group by indicator
order by position";


        $results = DB::select($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByIndicatorExport($sorting = array(), $customerEvaluationMinimumStandardId, $year)
    {

        $columnNames = ["name", "questions", "checked", "average"];
        $columnOrder = "id";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "id") {
                $dirOrder = "asc";
            } else
                $dirOrder = $sorting[0]["dir"];
        }

        $query = "SELECT
 i.indicator Indicador
	, MAX(case when `month` = 1 then ROUND(IFNULL(`value`,0),2) end) ENE
	, MAX(case when `month` = 2 then ROUND(IFNULL(`value`,0),2) end) FEB
	, MAX(case when `month` = 3 then ROUND(IFNULL(`value`,0),2) end) MAR
	, MAX(case when `month` = 4 then ROUND(IFNULL(`value`,0),2) end) ABR
	, MAX(case when `month` = 5 then ROUND(IFNULL(`value`,0),2) end) MAY
	, MAX(case when `month` = 6 then ROUND(IFNULL(`value`,0),2) end) JUN
	, MAX(case when `month` = 7 then ROUND(IFNULL(`value`,0),2) end) JUL
	, MAX(case when `month` = 8 then ROUND(IFNULL(`value`,0),2) end) AGO
	, MAX(case when `month` = 9 then ROUND(IFNULL(`value`,0),2) end) SEP
	, MAX(case when `month` = 10 then ROUND(IFNULL(`value`,0),2) end) OCT
	, MAX(case when `month` = 11 then ROUND(IFNULL(`value`,0),2) end) NOV
	, MAX(case when `month` = 12 then ROUND(IFNULL(`value`,0),2) end) DIC

 FROM (
	select 1 `position`, customer_evaluation_minimum_standard_id, 'Preguntas' indicator, SUM(questions) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 2 `position`, customer_evaluation_minimum_standard_id, 'Respuestas' indicator, SUM(answers) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 3 `position`, customer_evaluation_minimum_standard_id, 'Cumple' indicator, SUM(accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 4 `position`, customer_evaluation_minimum_standard_id, 'Cumple Parcial' indicator, SUM(partial_accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 5 `position`, customer_evaluation_minimum_standard_id, 'No Cumple' indicator, SUM(no_accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 6 `position`, customer_evaluation_minimum_standard_id, 'No Aplica' indicator, SUM(no_apply) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 7 `position`, customer_evaluation_minimum_standard_id, 'Sin Respuesta' indicator, SUM(no_answer) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
	union ALL
	select 8 `position`, customer_evaluation_minimum_standard_id, 'Promedio Total %' indicator, (SUM(total) / SUM(questions)) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by customer_evaluation_minimum_standard_id, `month`, `year`
) i
where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id and `year` = :year
group by indicator
order by position";


        $results = DB::select($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'year' => $year
        ));


        return $results;
    }

    public function getDashboardPie($customerEvaluationMinimumStandardId)
    {
        $sql = "select programa.name label
                        , ROUND(IFNULL((total / questions),0), 2) value
                        , programa.color, programa.highlightColor
                from(
                                select  pp.id program_id, pp.`name`, pp.color, pp.highlightColor,count(*) questions
                                , sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
                                , sum(cdp.value) total
                                from wg_progam_prevention pp
                                inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
                                inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
                                left join (
                                                                                select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
                                                                                inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                                                                                where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
                                                ) cdp on ppq.id = cdp.question_id
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                                group by  pp.`name`, pp.id
                )programa
                order by 1";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
        ));

        return $results;
    }

    public function getDashboardBar($customerEvaluationMinimumStandardId)
    {
        $sql = "select pp.`name`, pp.color, pp.highlightColor
                    , sum(case when ISNULL(wr.`code`) then 1 else 0 end) nocontesta
                    , sum(case when wr.`code` = 'c' then 1 else 0 end) cumple
                    , sum(case when wr.`code` = 'cp' then 1 else 0 end) parcial
                  , sum(case when wr.`code` = 'nc' then 1 else 0 end) nocumple
                    , sum(case when wr.`code` = 'na' then 1 else 0 end) noaplica
                from wg_progam_prevention pp
                inner join wg_progam_prevention_category pc on pp.id = pc.program_id
                inner join wg_progam_prevention_question pq on pc.id = pq.category_id
                inner join wg_customer_diagnostic_prevention dp on pq.id 	= dp.question_id
                left join wg_rate wr on dp.rate_id = wr.id
                where dp.customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
                group by pp.`name`
                order by pp.id";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
        ));

        return $results;
    }

    public function getDashboardBarMonthly($customerEvaluationMinimumStandardId, $year)
    {
        $sql = "select
	spp.item name, IFNULL(accomplish,0) accomplish
	, IFNULL(partial_accomplish,0) partial_accomplish
	, IFNULL(no_accomplish,0) no_accomplish
	, IFNULL(no_apply,0) no_apply
	, IFNULL(no_answer,0) no_answer
from
	system_parameters spp
left join
(
	select
			IFNULL(sum(accomplish),0) accomplish
			, IFNULL(sum(partial_accomplish),0) partial_accomplish
			, IFNULL(sum(no_accomplish),0)  no_accomplish
			, IFNULL(sum(no_apply),0) no_apply
			, IFNULL(sum(no_answer),0) no_answer
			, month
			, year
	from
	wg_customer_diagnostic_prevention_tracking cdpt
	inner join wg_progam_prevention pp on pp.id = cdpt.program_id
	where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id and year = :year
	group by customer_evaluation_minimum_standard_id, month
) rm on spp.value = rm.month
where spp.`group` = 'month'";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardProgramLineMonthly($customerEvaluationMinimumStandardId, $year)
    {
        $sql = "select  p.`id`, p.`name`, p.abbreviation, p.color
	, MAX(case when `month` = 1 then ROUND(IFNULL(o.avgTotal,0),2) end) ENE
	, MAX(case when `month` = 2 then ROUND(IFNULL(o.avgTotal,0),2) end) FEB
	, MAX(case when `month` = 3 then ROUND(IFNULL(o.avgTotal,0),2) end) MAR
	, MAX(case when `month` = 4 then ROUND(IFNULL(o.avgTotal,0),2) end) ABR
	, MAX(case when `month` = 5 then ROUND(IFNULL(o.avgTotal,0),2) end) MAY
	, MAX(case when `month` = 6 then ROUND(IFNULL(o.avgTotal,0),2) end) JUN
	, MAX(case when `month` = 7 then ROUND(IFNULL(o.avgTotal,0),2) end) JUL
	, MAX(case when `month` = 8 then ROUND(IFNULL(o.avgTotal,0),2) end) AGO
	, MAX(case when `month` = 9 then ROUND(IFNULL(o.avgTotal,0),2) end) SEP
	, MAX(case when `month` = 10 then ROUND(IFNULL(o.avgTotal,0),2) end) OCT
	, MAX(case when `month` = 11 then ROUND(IFNULL(o.avgTotal,0),2) end) NOV
	, MAX(case when `month` = 12 then ROUND(IFNULL(o.avgTotal,0),2) end) DIC
from
	wg_customer_diagnostic_prevention_tracking o
inner join wg_progam_prevention p on o.program_id = p.id
where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id and o.`year` = :year
group by program_id";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'year' => $year,
        ));

        return $results;
    }

    public function getDashboardTotalLineMonthly($customerEvaluationMinimumStandardId, $year)
    {
        $sql = "SELECT
 i.indicator
	, MAX(case when `month` = 1 then ROUND(IFNULL(`value`,0),2) end) ENE
	, MAX(case when `month` = 2 then ROUND(IFNULL(`value`,0),2) end) FEB
	, MAX(case when `month` = 3 then ROUND(IFNULL(`value`,0),2) end) MAR
	, MAX(case when `month` = 4 then ROUND(IFNULL(`value`,0),2) end) ABR
	, MAX(case when `month` = 5 then ROUND(IFNULL(`value`,0),2) end) MAY
	, MAX(case when `month` = 6 then ROUND(IFNULL(`value`,0),2) end) JUN
	, MAX(case when `month` = 7 then ROUND(IFNULL(`value`,0),2) end) JUL
	, MAX(case when `month` = 8 then ROUND(IFNULL(`value`,0),2) end) AGO
	, MAX(case when `month` = 9 then ROUND(IFNULL(`value`,0),2) end) SEP
	, MAX(case when `month` = 10 then ROUND(IFNULL(`value`,0),2) end) OCT
	, MAX(case when `month` = 11 then ROUND(IFNULL(`value`,0),2) end) NOV
	, MAX(case when `month` = 12 then ROUND(IFNULL(`value`,0),2) end) DIC
 FROM (
				select 8 `position`, customer_evaluation_minimum_standard_id, 'Promedio Total % (calificación / preguntas)' indicator, (SUM(total) / SUM(questions)) `value`, `month`, `year`
				from wg_customer_diagnostic_prevention_tracking
				group by customer_evaluation_minimum_standard_id, `month`, `year`
			) i
where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id and i.`year` = :year";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'year' => $year,
        ));

        return $results;
    }

    public function getDashboardAvgLineMonthly($customerEvaluationMinimumStandardId, $year)
    {
        $sql = "SELECT
	i.indicator
	, MAX(case when `month` = 1 then ROUND(IFNULL(`value`,0),2) end) ENE
	, MAX(case when `month` = 2 then ROUND(IFNULL(`value`,0),2) end) FEB
	, MAX(case when `month` = 3 then ROUND(IFNULL(`value`,0),2) end) MAR
	, MAX(case when `month` = 4 then ROUND(IFNULL(`value`,0),2) end) ABR
	, MAX(case when `month` = 5 then ROUND(IFNULL(`value`,0),2) end) MAY
	, MAX(case when `month` = 6 then ROUND(IFNULL(`value`,0),2) end) JUN
	, MAX(case when `month` = 7 then ROUND(IFNULL(`value`,0),2) end) JUL
	, MAX(case when `month` = 8 then ROUND(IFNULL(`value`,0),2) end) AGO
	, MAX(case when `month` = 9 then ROUND(IFNULL(`value`,0),2) end) SEP
	, MAX(case when `month` = 10 then ROUND(IFNULL(`value`,0),2) end) OCT
	, MAX(case when `month` = 11 then ROUND(IFNULL(`value`,0),2) end) NOV
	, MAX(case when `month` = 12 then ROUND(IFNULL(`value`,0),2) end) DIC
 FROM (
				select 8 `position`, customer_evaluation_minimum_standard_id, 'Avance % (respuestas / preguntas)' indicator, ((SUM(answers) / SUM(questions)) * 100) `value`, `month`, `year`
				from wg_customer_diagnostic_prevention_tracking
				group by customer_evaluation_minimum_standard_id, `month`, `year`
			) i
where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id and i.`year` = :year";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'year' => $year,
        ));

        return $results;
    }

    public function getDashboardByDiagnostic($customerEvaluationMinimumStandardId)
    {
        $sql = "select programa.customer_evaluation_minimum_standard_id, questions
                            , answers
                            , ROUND(IFNULL(((answers / questions) * 100), 0), 2) advance
                            , ROUND(IFNULL((total / questions),0), 2) average
                            , ROUND(IFNULL(total, 0), 2) total
                    from(
                                        select  cdp.customer_evaluation_minimum_standard_id, count(*) questions
                                        , sum(case when ISNULL(cdp.id) then 0 else 1 end) answers
                                        , sum(cdp.value) total
                                        from wg_progam_prevention pp
                                        inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
                                        inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
                                        left join (
                                                                select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
                                                                inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                                                                where customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
                                                ) cdp on ppq.id = cdp.question_id
                                        WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                )programa";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId
        ));

        return $results;
    }
}
