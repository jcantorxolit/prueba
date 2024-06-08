<?php

namespace Wgroup\Classes;

use Wgroup\Models\Customer;
use Wgroup\Models\CustomerDiagnostic;
use Wgroup\Models\CustomerDiagnosticDTO;
use Wgroup\Models\CustomerDiagnosticReporistory;
use Exception;
use Log;
use RainLab\User\Models\User;
use Str;
use DB;

class ServiceCustomerDiagnostic
{

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerDiagnosticRepository;

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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "", $customerId)
    {

        $model = new CustomerDiagnostic();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerDiagnosticRepository = new CustomerDiagnosticReporistory($model);

        if ($perPage > 0) {
            $this->customerDiagnosticRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_diagnostic.customer_id',
            'wg_customer_diagnostic.status',
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
                    $this->customerDiagnosticRepository->sortBy($colName, $dir);
                } else {
                    $this->customerDiagnosticRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerDiagnosticRepository->sortBy('wg_customer_diagnostic.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_diagnostic.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_diagnostic.status', $search);
            $filters[] = array('wg_agent.name', $search);
            $filters[] = array('diags.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_diagnostic.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_diagnostic.status', '0');
        }


        $this->customerDiagnosticRepository->setColumns(['wg_customer_diagnostic.*']);

        return $this->customerDiagnosticRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllSummryBy($sorting = array(), $diagnosticId)
    {

        $columnNames = ["abbreviation", "name", "questions", "answers", "advance", "average"];
        $columnOrder = "id";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "abbreviation") {
				$columnOrder = "id";
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
                                inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id
                                left join (
                                            select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
                                            inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                                            where diagnostic_id = :diagnostic_id
                                    ) cdp on ppq.id = cdp.question_id
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                                and ppqc.customer_size IN (select size from wg_customers c inner join wg_customer_diagnostic cd on cd.customer_id = c.id where cd.id = $diagnosticId)
                                group by  pp.`name`, pp.id
                    )programa
                    order by $columnOrder $dirOrder";
        //Log::info($query);
        //Log::info($diagnosticId);
        $results = DB::select($query, array(
            'diagnostic_id' => $diagnosticId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSummryByExport($sorting = array(), $diagnosticId)
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
                                inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id
                                left join (
                                            select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
                                            inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                                            where diagnostic_id = :diagnostic_id
                                    ) cdp on ppq.id = cdp.question_id
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                                and ppqc.customer_size IN (select size from wg_customers c inner join wg_customer_diagnostic cd on cd.customer_id = c.id where cd.id = $diagnosticId)
                                group by  pp.`name`, pp.id
                    )programa
                    order by $columnOrder $dirOrder";
        //Log::info($query);
        //Log::info($diagnosticId);
        $results = DB::select($query, array(
            'diagnostic_id' => $diagnosticId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getYearFilter($diagnosticId)
    {

        $query = "SELECT
	DISTINCT 0 id, o.`year` item, o.`year` `value`
FROM
	wg_customer_diagnostic_prevention_tracking o
WHERE diagnostic_id = :diagnostic_id
ORDER BY o.`year` DESC
";
        $results = DB::select($query, array(
            'diagnostic_id' => $diagnosticId
        ));

        return $results;
    }

    public function getAllSummaryByProgram($sorting = array(), $diagnosticId, $year)
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
where diagnostic_id = :diagnostic_id and o.`year` = :year
group by program_id";


        $results = DB::select($query, array(
            'diagnostic_id' => $diagnosticId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByProgramExport($sorting = array(), $diagnosticId, $year)
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
where diagnostic_id = :diagnostic_id and o.`year` = :year
group by program_id";


        $results = DB::select($query, array(
            'diagnostic_id' => $diagnosticId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByIndicator($sorting = array(), $diagnosticId, $year)
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
	select 1 `position`, diagnostic_id, 'Preguntas' indicator, SUM(questions) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 2 `position`, diagnostic_id, 'Respuestas' indicator, SUM(answers) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 3 `position`, diagnostic_id, 'Cumple' indicator, SUM(accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 4 `position`, diagnostic_id, 'Cumple Parcial' indicator, SUM(partial_accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 5 `position`, diagnostic_id, 'No Cumple' indicator, SUM(no_accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 6 `position`, diagnostic_id, 'No Aplica' indicator, SUM(no_apply) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 7 `position`, diagnostic_id, 'Sin Respuesta' indicator, SUM(no_answer) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 8 `position`, diagnostic_id, 'Promedio Total %' indicator, (SUM(total) / SUM(questions)) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
) i
where diagnostic_id = :diagnostic_id and `year` = :year
group by indicator
order by position";


        $results = DB::select($query, array(
            'diagnostic_id' => $diagnosticId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByIndicatorExport($sorting = array(), $diagnosticId, $year)
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
	select 1 `position`, diagnostic_id, 'Preguntas' indicator, SUM(questions) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 2 `position`, diagnostic_id, 'Respuestas' indicator, SUM(answers) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 3 `position`, diagnostic_id, 'Cumple' indicator, SUM(accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 4 `position`, diagnostic_id, 'Cumple Parcial' indicator, SUM(partial_accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 5 `position`, diagnostic_id, 'No Cumple' indicator, SUM(no_accomplish) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 6 `position`, diagnostic_id, 'No Aplica' indicator, SUM(no_apply) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 7 `position`, diagnostic_id, 'Sin Respuesta' indicator, SUM(no_answer) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
	union ALL
	select 8 `position`, diagnostic_id, 'Promedio Total %' indicator, (SUM(total) / SUM(questions)) `value`, `month`, `year`
	from wg_customer_diagnostic_prevention_tracking
	group by diagnostic_id, `month`, `year`
) i
where diagnostic_id = :diagnostic_id and `year` = :year
group by indicator
order by position";


        $results = DB::select($query, array(
            'diagnostic_id' => $diagnosticId,
            'year' => $year
        ));


        return $results;
    }

    public function getDashboardPie($diagnosticId)
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
                                inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id
                                left join (
                                                                                select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
                                                                                inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                                                                                where diagnostic_id = :diagnostic_id
                                                ) cdp on ppq.id = cdp.question_id
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                                and ppqc.customer_size IN (select size from wg_customers c inner join wg_customer_diagnostic cd on cd.customer_id = c.id where cd.id = $diagnosticId)
                                group by  pp.`name`, pp.id
                )programa
                order by 1";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId,
        ));

        return $results;
    }

    public function getDashboardBar($diagnosticId)
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
                inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = pq.id
                inner join wg_customer_diagnostic_prevention dp on pq.id 	= dp.question_id
                left join wg_rate wr on dp.rate_id = wr.id
                where dp.diagnostic_id = :diagnostic_id
                and ppqc.customer_size IN (select size from wg_customers c inner join wg_customer_diagnostic cd on cd.customer_id = c.id where cd.id = $diagnosticId)
                group by pp.`name`
                order by pp.id";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId,
        ));

        return $results;
    }

    public function getDashboardBarMonthly($diagnosticId, $year)
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
	where diagnostic_id = :diagnostic_id and year = :year
	group by diagnostic_id, month
) rm on spp.value = rm.month
where spp.`group` = 'month'";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardProgramLineMonthly($diagnosticId, $year)
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
where diagnostic_id = :diagnostic_id and o.`year` = :year
group by program_id";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId,
            'year' => $year,
        ));

        return $results;
    }

    public function getDashboardTotalLineMonthly($diagnosticId, $year)
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
				select 8 `position`, diagnostic_id, 'Promedio Total % (calificación / preguntas)' indicator, (SUM(total) / SUM(questions)) `value`, `month`, `year`
				from wg_customer_diagnostic_prevention_tracking
				group by diagnostic_id, `month`, `year`
			) i
where diagnostic_id = :diagnostic_id and i.`year` = :year";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId,
            'year' => $year,
        ));

        return $results;
    }

    public function getDashboardAvgLineMonthly($diagnosticId, $year)
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
				select 8 `position`, diagnostic_id, 'Avance % (respuestas / preguntas)' indicator, ((SUM(answers) / SUM(questions)) * 100) `value`, `month`, `year`
				from wg_customer_diagnostic_prevention_tracking
				group by diagnostic_id, `month`, `year`
			) i
where diagnostic_id = :diagnostic_id and i.`year` = :year";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId,
            'year' => $year,
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
                                        inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = ppq.id
                                        left join (
                                                                select wg_customer_diagnostic_prevention.*, wg_rate.text, wg_rate.value from wg_customer_diagnostic_prevention
                                                                inner join wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                                                                where diagnostic_id = :diagnostic_id
                                                ) cdp on ppq.id = cdp.question_id
                                        WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                                        and ppqc.customer_size IN (select size from wg_customers c inner join wg_customer_diagnostic cd on cd.customer_id = c.id where cd.id = $diagnosticId)
                )programa";

        $results = DB::select($sql, array(
            'diagnostic_id' => $diagnosticId
        ));

        return $results;
    }

    public function getCount($search = "")
    {

        $model = new CustomerDiagnostic();
        $this->customerDiagnosticRepository = new CustomerDiagnosticReporistory($model);

        $filters = array();
        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_diagnostic.customer_id', $search);
            $filters[] = array('wg_customer_diagnostic.status', $search);
        }

        $this->customerDiagnosticRepository->setColumns(['wg_customer_diagnostic.*']);

        return $this->customerDiagnosticRepository->getFilteredsOptional($filters, true, "");
    }


    public function saveDiagnosticQuestion($model)
    {
        $diagnosticId = $model->id;

        $query = "insert into wg_customer_diagnostic_prevention
                  select null id, :diagnostic_id diagnostic, pq.id question_id, null rate_id, null observation, 'activo' status
                        , :createdBy created, null updatedBy
                        , now() created_at, null updated_at
                    from wg_progam_prevention pp
                    inner join wg_progam_prevention_category pc on pp.id = pc.program_id
                    inner join wg_progam_prevention_question pq on pc.id = pq.category_id
                    inner join wg_progam_prevention_question_classification ppqc on ppqc.program_prevention_question_id = pq.id
                    inner join wg_customer_diagnostic cd on cd.id = :diagnostic_id2
                    left join wg_customer_diagnostic_prevention dp on dp.diagnostic_id = cd.id and dp.question_id = pq.id
                    where pp.`status` = 'activo' and pc.`status` = 'activo' and pq.`status` = 'activo'
                    and ppqc.customer_size IN (select size from wg_customers c inner join wg_customer_diagnostic cd on cd.customer_id = c.id where cd.id = $diagnosticId)
                    and dp.question_id is null";


        $results = DB::statement($query, array(
            'diagnostic_id' => $model->id,
            'createdBy' => $model->createdBy,
            'diagnostic_id2' => $model->id
        ));

        //Log::info($results);

        return true;
    }

    public function saveDiagnosticAccident($model)
    {
        $query = "insert into wg_customer_diagnostic_accident
                  select null id, :diagnostic_id diagnostic, wa.id question_id, 0 numberOfAT, 0 disabilityDay, 0 unsafeAct, 0 unsafeCondition, '' description, '' correctiveMeasure
                        , :createdBy created, null updatedBy
                        , now() created_at, null updated_at
                    from wg_accident wa
                    where wa.`status` = 'activo'";


        $results = DB::statement($query, array(
            'diagnostic_id' => $model->id,
            'createdBy' => $model->createdBy
        ));

        //Log::info($results);

        return true;
    }


    /* public function getAllSummryByEconomicGroup($sorting = array(), $customerId) {

         $columnNames = ["id", "questions", "answers", "average"];
         $columnOrder = "id";
         $dirOrder = "asc";

         if (!empty($sorting)){
             $columnOrder =  $columnNames[$sorting[0]["column"]];
             if ($columnOrder == "id")
             {
                 $dirOrder =  "asc";
             }
             else
                 $dirOrder =  $sorting[0]["dir"];
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
                                             where diagnostic_id = :diagnostic_id
                                     ) cdp on ppq.id = cdp.question_id
                                 WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
                                 group by  pp.`name`, pp.id
                     )programa
                     order by $columnOrder $dirOrder";

         $results = DB::select( $query, array(
             'parent_id' => $customerId,
         ));

         return $results;
     }*/

    public function findAllEconomicGroup()
    {
        $query = "SELECT c.id,
       c.id `value`,
       c.businessName `item`
FROM wg_customers c
INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
LEFT JOIN
  ( SELECT MAX(id) id,
           customer_id
   FROM wg_customer_diagnostic
   WHERE `status` = 'iniciado'
   GROUP BY customer_id) d ON ceg.id = d.customer_id
WHERE c.hasEconomicGroup = 1
GROUP BY c.id
ORDER BY c.businessName";

        $results = DB::select($query);

        return $results;
    }

    public function findCustomerEconomicGroup($customerId)
    {
        $query = "SELECT c.id,
       c.id `value`,
       c.businessName `item`
FROM wg_customers c
INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
LEFT JOIN
  ( SELECT MAX(id) id,
           customer_id
   FROM wg_customer_diagnostic
   WHERE `status` = 'iniciado'
   GROUP BY customer_id) d ON ceg.id = d.customer_id
WHERE c.hasEconomicGroup = 1 AND c.id = :customer_id
GROUP BY c.id
ORDER BY c.businessName";

        $results = DB::select($query, array(
            "customer_id" => $customerId
        ));

        return $results;
    }

    public function findAllCustomerEconomicGroup($parentId)
    {
        $query = "SELECT c.id,
       c.id `value`,
       c.businessName `item`,
       d.id diagnosticId
FROM wg_customers c
LEFT JOIN
  ( SELECT MAX(id) id,
           customer_id
   FROM wg_customer_diagnostic
   WHERE `status` = 'iniciado'
   GROUP BY customer_id) d ON c.id = d.customer_id
WHERE c.hasEconomicGroup = 1 AND c.id = :parent_id_1

UNION ALL

SELECT ceg.id,
       ceg.id `value`,
       ceg.businessName `item`,
       d.id diagnosticId
FROM wg_customers c
INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
LEFT JOIN
  ( SELECT MAX(id) id,
           customer_id
   FROM wg_customer_diagnostic
   WHERE `status` = 'iniciado'
   GROUP BY customer_id) d ON ceg.id = d.customer_id
WHERE c.hasEconomicGroup = 1 AND eg.parent_id = :parent_id_2
ORDER BY item";

        $results = DB::select($query, array(
            'parent_id_1' => $parentId,
            'parent_id_2' => $parentId,
        ));

        return $results;
    }

    public function findAllSummaryEconomicGroup($parentId)
    {
        $query = "SELECT
	q.id, q.name,  q.abbreviation,
	SUM(questions) questions,
	SUM(IFNULL(answers,0)) answers,
	round((SUM(IFNULL(answers,0)) / SUM(questions)) * 100, 2) advance,
	round((SUM(total) / SUM(questions)), 2) average,
	SUM(total) total
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_1 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id, sum(wg_rate.`value`) total
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	inner join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_2 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id
GROUP BY q.id";

        $results = DB::select($query, array(
            'parent_id_1' => $parentId,
            'parent_id_2' => $parentId,
        ));

        return $results;
    }

    public function getDashboardPieEconomicGroup($parentId)
    {
        $query = "SELECT
	q.`name` label,
	ROUND(IFNULL((SUM(total) / SUM(questions)),0), 2) `value`,
	q.color, q.highlightColor
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id, pp.color, pp.highlightColor
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_1 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id, sum(wg_rate.`value`) total
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	inner join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_2 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id
GROUP BY q.id
ORDER BY 1";

        $results = DB::select($query, array(
            'parent_id_1' => $parentId,
            'parent_id_2' => $parentId,
        ));

        return $results;
    }

    public function getDashboardBarEconomicGroup($parentId)
    {
        $query = "	select pp.`name`, pp.color, pp.highlightColor
                    , sum(case when ISNULL(wr.`code`) then 1 else 0 end) nocontesta
                    , sum(case when wr.`code` = 'c' then 1 else 0 end) cumple
                    , sum(case when wr.`code` = 'cp' then 1 else 0 end) parcial
                    , sum(case when wr.`code` = 'nc' then 1 else 0 end) nocumple
                    , sum(case when wr.`code` = 'na' then 1 else 0 end) noaplica
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	left join wg_rate wr ON p.rate_id = wr.id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_1 ) d ON p.diagnostic_id = d.diagnosticId
WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
GROUP BY pp.`name`
ORDER BY pp.id";

        $results = DB::select($query, array(
            'parent_id_1' => $parentId
        ));

        return $results;
    }

    public function getDashboardBarEconomicGroupTotalAverage($parentId)
    {
        $query = "SELECT
	round((SUM(total) / SUM(questions)), 2) average
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_1 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id, sum(wg_rate.`value`) total
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	inner join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_2 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id;";

        $results = DB::select($query, array(
            'parent_id_1' => $parentId,
            'parent_id_2' => $parentId
        ));

        return count($results) ? $results[0] : null;
    }

    public function findAllCustomer()
    {
        $query = "SELECT c.id,
       c.id `value`,
       c.businessName `item`
FROM wg_customers c
LEFT JOIN
  ( SELECT MAX(id) id,
           customer_id
   FROM wg_customer_diagnostic
   WHERE `status` = 'iniciado'
   GROUP BY customer_id) d ON c.id = d.customer_id
GROUP BY c.id
ORDER BY  c.businessName";

        $results = DB::select($query);

        return $results;
    }

    public function findCustomer($customerId)
    {
        $query = "SELECT c.id,
       c.id `value`,
       c.businessName `item`
FROM wg_customers c
LEFT JOIN
  ( SELECT MAX(id) id,
           customer_id
   FROM wg_customer_diagnostic
   WHERE `status` = 'iniciado'
   GROUP BY customer_id) d ON c.id = d.customer_id
WHERE c.id = :customer_id
GROUP BY c.id
ORDER BY  c.businessName";

        $results = DB::select($query, array(
            "customer_id" => $customerId
        ));

        return $results;
    }

    public function findAllContracting()
    {
        $query = "SELECT c.id,
       c.id `value`,
       c.businessName `item`
FROM wg_customers c
INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
LEFT JOIN
  ( SELECT MAX(id) id,
           customer_id
   FROM wg_customer_diagnostic
   WHERE `status` = 'iniciado'
   GROUP BY customer_id) d ON ceg.id = d.customer_id
GROUP BY c.id
ORDER BY c.businessName";

        $results = DB::select($query);

        return $results;
    }

    public function findCustomerContracting($customerId)
    {
        $query = "SELECT c.id,
       c.id `value`,
       c.businessName `item`
FROM wg_customers c
INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
LEFT JOIN
  ( SELECT MAX(id) id,
           customer_id
   FROM wg_customer_diagnostic
   WHERE `status` = 'iniciado'
   GROUP BY customer_id) d ON ceg.id = d.customer_id
WHERE c.id = :customer_id
GROUP BY c.id
ORDER BY c.businessName";

        $results = DB::select($query, array(
            "customer_id" => $customerId
        ));

        return $results;
    }

    public function findAllCustomerContracting($parentId)
    {
        $query = "SELECT DISTINCT c.id,
       c.id `value`,
       c.businessName `item`,
       d.id diagnosticId
FROM wg_customers c
INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
LEFT JOIN
  ( SELECT MAX(id) id,
           customer_id
   FROM wg_customer_diagnostic
   WHERE `status` = 'iniciado'
   GROUP BY customer_id) d ON c.id = d.customer_id
WHERE c.classification = 'Contratante' AND c.id = :parent_id_1

UNION ALL

SELECT ceg.id,
       ceg.id `value`,
       ceg.businessName `item`,
       d.id diagnosticId
FROM wg_customers c
INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
LEFT JOIN
  ( SELECT MAX(id) id,
           customer_id
   FROM wg_customer_diagnostic
   WHERE `status` = 'iniciado'
   GROUP BY customer_id) d ON ceg.id = d.customer_id
WHERE eg.customer_id = :parent_id_2
ORDER BY item";

        $results = DB::select($query, array(
            'parent_id_1' => $parentId,
            'parent_id_2' => $parentId,
        ));

        return $results;
    }

    public function findAllSummaryContracting($parentId)
    {
        $query = "SELECT
	q.id, q.name,  q.abbreviation,
	SUM(questions) questions,
	SUM(IFNULL(answers,0)) answers,
	round((SUM(IFNULL(answers,0)) / SUM(questions)) * 100, 2) advance,
	round((SUM(total) / SUM(questions)), 2) average,
	SUM(total) total
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT ceg.id,
                                            ceg.id `value`,
                                            ceg.businessName `item`,
                                            d.id diagnosticId
                            FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
                            INNER JOIN
                                (SELECT MAX(id) id,
                                                customer_id
                                 FROM wg_customer_diagnostic
                                 WHERE `status` = 'iniciado'
                                 GROUP BY customer_id) d ON ceg.id = d.customer_id
                            WHERE eg.customer_id = :parent_id_1 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id, sum(wg_rate.`value`) total
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	inner join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT ceg.id,
                                            ceg.id `value`,
                                            ceg.businessName `item`,
                                            d.id diagnosticId
                            FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
                            INNER JOIN
                                (SELECT MAX(id) id,
                                                customer_id
                                 FROM wg_customer_diagnostic
                                 WHERE `status` = 'iniciado'
                                 GROUP BY customer_id) d ON ceg.id = d.customer_id
                            WHERE eg.customer_id = :parent_id_2 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id
GROUP BY q.id";

        $results = DB::select($query, array(
            'parent_id_1' => $parentId,
            'parent_id_2' => $parentId,
        ));

        return $results;
    }

    public function getDashboardPieContracting($parentId)
    {
        $query = "SELECT
	q.`name` label,
	ROUND(IFNULL((SUM(total) / SUM(questions)),0), 2) `value`,
	q.color, q.highlightColor
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id, pp.color, pp.highlightColor
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT ceg.id,
                                            ceg.id `value`,
                                            ceg.businessName `item`,
                                            d.id diagnosticId
                            FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
                            INNER JOIN
                                (SELECT MAX(id) id,
                                                customer_id
                                 FROM wg_customer_diagnostic
                                 WHERE `status` = 'iniciado'
                                 GROUP BY customer_id) d ON ceg.id = d.customer_id
                            WHERE eg.customer_id = :parent_id_1 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id, sum(wg_rate.`value`) total
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	inner join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT ceg.id,
                                            ceg.id `value`,
                                            ceg.businessName `item`,
                                            d.id diagnosticId
                            FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
                            INNER JOIN
                                (SELECT MAX(id) id,
                                                customer_id
                                 FROM wg_customer_diagnostic
                                 WHERE `status` = 'iniciado'
                                 GROUP BY customer_id) d ON ceg.id = d.customer_id
                            WHERE eg.customer_id = :parent_id_2 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id
GROUP BY q.id
ORDER BY 1";

        $results = DB::select($query, array(
            'parent_id_1' => $parentId,
            'parent_id_2' => $parentId,
        ));

        return $results;
    }

    public function getDashboardBarContracting($parentId)
    {
        $query = "	select pp.`name`, pp.color, pp.highlightColor
                    , sum(case when ISNULL(wr.`code`) then 1 else 0 end) nocontesta
                    , sum(case when wr.`code` = 'c' then 1 else 0 end) cumple
                    , sum(case when wr.`code` = 'cp' then 1 else 0 end) parcial
                    , sum(case when wr.`code` = 'nc' then 1 else 0 end) nocumple
                    , sum(case when wr.`code` = 'na' then 1 else 0 end) noaplica
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	left join wg_rate wr ON p.rate_id = wr.id
	INNER JOIN
						 ( SELECT ceg.id,
                                            ceg.id `value`,
                                            ceg.businessName `item`,
                                            d.id diagnosticId
                            FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
                            INNER JOIN
                                (SELECT MAX(id) id,
                                                customer_id
                                 FROM wg_customer_diagnostic
                                 WHERE `status` = 'iniciado'
                                 GROUP BY customer_id) d ON ceg.id = d.customer_id
                            WHERE eg.customer_id = :parent_id_1 ) d ON p.diagnostic_id = d.diagnosticId
WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
GROUP BY pp.`name`
ORDER BY pp.id";

        $results = DB::select($query, array(
            'parent_id_1' => $parentId
        ));

        return $results;
    }

    public function getDashboardBarContractingTotalAverage($parentId)
    {
        $query = "SELECT
	round((SUM(total) / SUM(questions)), 2) average
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT ceg.id,
                                            ceg.id `value`,
                                            ceg.businessName `item`,
                                            d.id diagnosticId
                            FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
                            INNER JOIN
                                (SELECT MAX(id) id,
                                                customer_id
                                 FROM wg_customer_diagnostic
                                 WHERE `status` = 'iniciado'
                                 GROUP BY customer_id) d ON ceg.id = d.customer_id
                            WHERE eg.customer_id = :parent_id_1 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id, sum(wg_rate.`value`) total
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	inner join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT ceg.id,
                                            ceg.id `value`,
                                            ceg.businessName `item`,
                                            d.id diagnosticId
                            FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
                            INNER JOIN
                                (SELECT MAX(id) id,
                                                customer_id
                                 FROM wg_customer_diagnostic
                                 WHERE `status` = 'iniciado'
                                 GROUP BY customer_id) d ON ceg.id = d.customer_id
                            WHERE eg.customer_id = :parent_id_2 ) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id";

        $results = DB::select($query, array(
            'parent_id_1' => $parentId,
            'parent_id_2' => $parentId
        ));

        return count($results) ? $results[0] : null;
    }


    //--------------------------------------------------------------------------INDICATORS
    public function getMinimumStandardEconomicGroup($customerId)
    {
        $query = "SELECT
                d.id customerMinimumStandardId
                FROM wg_customers c
                INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
                INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
                INNER JOIN
                (SELECT MAX(id) id,
                customer_id
                FROM wg_customer_evaluation_minimum_standard
                WHERE `status` = 'iniciado'
                GROUP BY customer_id) d ON ceg.id = d.customer_id
                WHERE c.hasEconomicGroup = 1
                AND eg.parent_id = :parent_id_1 AND ceg.status = 1

                union ALL

                SELECT
                d.id customerMinimumStandardId
                FROM wg_customers c
                INNER JOIN
                (SELECT MAX(id) id,
                customer_id
                FROM wg_customer_evaluation_minimum_standard
                WHERE `status` = 'iniciado'
                GROUP BY customer_id) d ON c.id = d.customer_id
                WHERE c.hasEconomicGroup = 1
                AND c.id = :parent_id_2";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId
        ));

        $this->deleteIndicator($customerId);

        foreach ($results as $row) {
            $this->bulkMinimumStandardEconomicGroupResults($row->customerMinimumStandardId, $customerId);
        }

        $query = "SELECT
	`id`,
	`name`,
	`abbreviation`,
	COUNT(*) entities,
	ROUND(SUM(`items`)) items,
	ROUND(SUM(`target`) / COUNT(*),2) target,
	ROUND(SUM(`valoration`)/ COUNT(*),2) valoration,
	ROUND(SUM(`noChecked`)/ COUNT(*),2) noChecked,
	ROUND(SUM(`accomplish`)/ COUNT(*),2) accomplish,
	ROUND(SUM(`noAccomplish`)/ COUNT(*),2) noAccomplish,
	ROUND(SUM(`noApplyWith`)/ COUNT(*),2) noApplyWith,
	ROUND(SUM(`noApplyWithout`)/ COUNT(*),2) noApplyWithout,
	`customerId`
FROM
	wg_indicators_staging
WHERE
	customerId = :parent_id_1
GROUP BY
	customerId,
	`name`
order by cycle";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId
        ));

        return $results;
    }

    public function getMinimumStandardEconomicGroupCustomer($customerId)
    {
        $query = "
                SELECT
                d.id customerMinimumStandardId
                FROM wg_customers c
                INNER JOIN
                (SELECT MAX(id) id,
                customer_id
                FROM wg_customer_evaluation_minimum_standard
                WHERE `status` = 'iniciado'
                GROUP BY customer_id) d ON c.id = d.customer_id
                WHERE c.id = :parent_id_1";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId
        ));

        $this->deleteIndicator($customerId);

        foreach ($results as $row) {
            $this->bulkMinimumStandardEconomicGroupResults($row->customerMinimumStandardId, $customerId);
        }

        $query = "SELECT
	`id`,
	`name`,
	`abbreviation`,
	COUNT(*) entities,
	ROUND(SUM(`items`)) items,
	ROUND(SUM(`target`) / COUNT(*),2) target,
	ROUND(SUM(`valoration`)/ COUNT(*),2) valoration,
	ROUND(SUM(`noChecked`)/ COUNT(*),2) noChecked,
	ROUND(SUM(`accomplish`)/ COUNT(*),2) accomplish,
	ROUND(SUM(`noAccomplish`)/ COUNT(*),2) noAccomplish,
	ROUND(SUM(`noApplyWith`)/ COUNT(*),2) noApplyWith,
	ROUND(SUM(`noApplyWithout`)/ COUNT(*),2) noApplyWithout,
	`customerId`
FROM
	wg_indicators_staging
WHERE
	customerId = :parent_id_1
GROUP BY
	customerId,
	`name`
	order by cycle";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId
        ));

        return $results;
    }

    public function getMinimumStandardEconomicGroupContractor($customerId)
    {
        $query = "
SELECT
	customerMinimumStandardId
FROM (
	SELECT
		d.id customerMinimumStandardId
	FROM wg_customers c
	INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
	INNER JOIN wg_customer_contractor cct ON cct.customer_id = eg.customer_id
	INNER JOIN wg_customers cc ON cc.id = cct.contractor_id
	INNER JOIN
	(
		SELECT MAX(id) id,
			customer_id
		FROM wg_customer_evaluation_minimum_standard
		WHERE `status` = 'iniciado'
		GROUP BY customer_id
	) d ON cc.id = d.customer_id
	WHERE c.hasEconomicGroup = 1
		AND c.id = :parent_id_1 AND cc.classification = 'Contratista' AND cc.status = 1 AND c.status = 1

	union ALL

	SELECT
		d.id customerMinimumStandardId
	FROM wg_customers c
	INNER JOIN wg_customer_contractor cct ON cct.customer_id = c.id
	INNER JOIN wg_customers cc ON cc.id = cct.contractor_id
	INNER JOIN
	(
		SELECT MAX(id) id,
			customer_id
		FROM wg_customer_evaluation_minimum_standard
		WHERE `status` = 'iniciado'
		GROUP BY customer_id
	) d ON cc.id = d.customer_id
	WHERE c.id = :parent_id_2 AND cc.classification = 'Contratista' AND cc.status = 1 AND c.status = 1) p ";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId,
        ));

        $this->deleteIndicator($customerId);

        foreach ($results as $row) {
            $this->bulkMinimumStandardEconomicGroupResults($row->customerMinimumStandardId, $customerId);
        }

        $query = "SELECT
	`id`,
	`name`,
	`abbreviation`,
	COUNT(*) entities,
	ROUND(SUM(`items`)) items,
	ROUND(SUM(`target`) / COUNT(*),2) target,
	ROUND(SUM(`valoration`)/ COUNT(*),2) valoration,
	ROUND(SUM(`noChecked`)/ COUNT(*),2) noChecked,
	ROUND(SUM(`accomplish`)/ COUNT(*),2) accomplish,
	ROUND(SUM(`noAccomplish`)/ COUNT(*),2) noAccomplish,
	ROUND(SUM(`noApplyWith`)/ COUNT(*),2) noApplyWith,
	ROUND(SUM(`noApplyWithout`)/ COUNT(*),2) noApplyWithout,
	`customerId`
FROM
	wg_indicators_staging
WHERE
	customerId = :parent_id_1
GROUP BY
	customerId,
	`name`";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId
        ));

        return $results;
    }


    public function getMinimumStandardContracting($customerId)
    {
        $query = "SELECT
                d.id customerMinimumStandardId
                FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
                INNER JOIN
                (SELECT MAX(id) id,
                customer_id
                FROM wg_customer_evaluation_minimum_standard
                WHERE `status` = 'iniciado'
                GROUP BY customer_id) d ON ceg.id = d.customer_id
                WHERE c.classification = 'Contratante'
                AND eg.customer_id = :parent_id_1 AND c.status = 1 AND ceg.status = 1 AND c.status = 1

                union ALL

                SELECT
                d.id customerMinimumStandardId
                FROM wg_customers c
                INNER JOIN
                (SELECT MAX(id) id,
                customer_id
                FROM wg_customer_evaluation_minimum_standard
                WHERE `status` = 'iniciado'
                GROUP BY customer_id) d ON c.id = d.customer_id
                WHERE c.classification = 'Contratante'
                AND c.id = :parent_id_2 AND c.status = 1";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId
        ));

        $this->deleteIndicator($customerId);

        foreach ($results as $row) {
            $this->bulkMinimumStandardEconomicGroupResults($row->customerMinimumStandardId, $customerId);
        }

        $query = "SELECT
	`id`,
	`name`,
	`abbreviation`,
	COUNT(*) entities,
	ROUND(SUM(`items`)) items,
	ROUND(SUM(`target`) / COUNT(*),2) target,
	ROUND(SUM(`valoration`)/ COUNT(*),2) valoration,
	ROUND(SUM(`noChecked`)/ COUNT(*),2) noChecked,
	ROUND(SUM(`accomplish`)/ COUNT(*),2) accomplish,
	ROUND(SUM(`noAccomplish`)/ COUNT(*),2) noAccomplish,
	ROUND(SUM(`noApplyWith`)/ COUNT(*),2) noApplyWith,
	ROUND(SUM(`noApplyWithout`)/ COUNT(*),2) noApplyWithout,
	`customerId`
FROM
	wg_indicators_staging
WHERE
	customerId = :parent_id_1
GROUP BY
	customerId,
	`name`
order by cycle";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId
        ));

        return $results;
    }


    private function deleteIndicator($customerId)
    {
        $delete = "DELETE FROM wg_indicators_staging where customerId = $customerId";

        $results = DB::statement($delete);
    }

    private function bulkMinimumStandardEconomicGroupResults($customerEvaluationMinimumStandardId, $customerId)
    {


        $query = "
INSERT INTO wg_indicators_staging
SELECT
	NULL id,
	cycle.id cycleId,
	name,
	abbreviation,
	items,
	target,
	valoration,
	noChecked,
	accomplish,
	noAccomplish,
	noApplyWith,
	noApplyWithout,
	$customerId customerId
FROM
	(
		SELECT
			item.id,
			item.`name`,
			item.minimum_standard_id,
			item.abbreviation,
			count(*) items
		, SUM(item.`value`) target
		, SUM(CASE WHEN cemsi.`code` = 'cp' OR cemsi.`code` = 'nac' THEN item.`value` ELSE 0 END) valoration
		, SUM(CASE WHEN ISNULL(cemsi.`code`) THEN item.`value` ELSE 0 END) noChecked
		, SUM(CASE WHEN cemsi.`code` = 'cp' THEN item.`value` ELSE 0 END) accomplish
		, SUM(CASE WHEN cemsi.`code` = 'nc' THEN item.`value` ELSE 0 END) noAccomplish
		, SUM(CASE WHEN cemsi.`code` = 'nac' THEN item.`value` ELSE 0 END) noApplyWith
		, SUM(CASE WHEN cemsi.`code` = 'nas' THEN item.`value` ELSE 0 END) noApplyWithout
		FROM
			(
				SELECT
					cycle.id,
					cycle.`name`,
					cycle.abbreviation,
					ms.id minimum_standard_id,
					msi.id minimum_standard_item_id,
					msi.`value`
				FROM
					wg_config_minimum_standard_cycle cycle
				INNER JOIN wg_minimum_standard ms ON cycle.id = ms.cycle_id
				INNER JOIN wg_minimum_standard_item msi ON ms.id = msi.minimum_standard_id
				WHERE
					cycle.`status` = 'activo'
				AND ms.isActive = 1
				AND msi.`isActive` = 1
				AND ms.type = 'P'
				UNION ALL
					SELECT
						cycle.id,
						cycle.`name`,
						cycle.abbreviation,
						msp.id minimum_standard_id,
						msi.id minimum_standard_item_id,
						msi.`value`
					FROM
						wg_config_minimum_standard_cycle cycle
					INNER JOIN wg_minimum_standard ms ON cycle.id = ms.cycle_id
					INNER JOIN wg_minimum_standard msp ON ms.parent_id = msp.id
					INNER JOIN wg_minimum_standard_item msi ON ms.id = msi.minimum_standard_id
					WHERE
						cycle.`status` = 'activo'
					AND ms.isActive = 1
					AND msi.`isActive` = 1
			) item
		LEFT JOIN (
			SELECT
				wg_customer_evaluation_minimum_standard_item.*
				, wg_config_minimum_standard_rate.text
				, wg_config_minimum_standard_rate.`value`
				, wg_config_minimum_standard_rate.`code`

			FROM
				wg_customer_evaluation_minimum_standard_item
			INNER JOIN wg_config_minimum_standard_rate ON wg_customer_evaluation_minimum_standard_item.rate_id = wg_config_minimum_standard_rate.id
			WHERE
				customer_evaluation_minimum_standard_id = :parent_id_1
		) cemsi ON item.minimum_standard_item_id = cemsi.minimum_standard_item_id
		GROUP BY
			item.`name`,
			item.id
	) cycle
ORDER BY
	cycle.id";

        $results = DB::statement($query, array(
            'parent_id_1' => $customerEvaluationMinimumStandardId,
        ));
    }

    public function getDiagnosticEconomicGroup($customerId)
    {
        $query = "SELECT
	q.id, q.abbreviation name,
	SUM(questions) questions,
	SUM(IFNULL(answers,0)) answers,
	round(100, 2) target,
	round((SUM(total) / SUM(questions)), 2) valoration,
	round((SUM(cumple) / SUM(questions)), 2) cumple,
	round((SUM(nocumple) / SUM(questions)), 2) nocumple,
	round((SUM(noaplica) / SUM(questions)), 2) noaplica,
	SUM(total) total
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_1 AND ceg.status = 1 AND c.status = 1
                            UNION ALL
                            SELECT
                                c.id,
                                c.id `value`,
                                c.businessName `item`,
                                d.id diagnosticId
                            FROM
                                wg_customers c
                            INNER JOIN (
                                SELECT
                                    MAX(id) id,
                                    customer_id
                                FROM wg_customer_diagnostic
                                WHERE `status` = 'iniciado'
                                GROUP BY customer_id
                            ) d ON c.id = d.customer_id
                            WHERE c.hasEconomicGroup = 1 AND c.id = :parent_id_2 AND c.status = 1
								) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id
			, sum(IFNULL(wg_rate.`value`,0)) total
			, sum(case when wg_rate.`code` = 'c' then wg_rate.`value` else 0 end) cumple
			, sum(case when wg_rate.`code` = 'nc' then wg_rate.`value` else 0 end) nocumple
			, sum(case when wg_rate.`code` = 'na' then wg_rate.`value` else 0 end) noaplica
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	left join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_3 AND ceg.status = 1 AND c.status = 1

								UNION ALL
                            SELECT
                                c.id,
                                c.id `value`,
                                c.businessName `item`,
                                d.id diagnosticId
                            FROM
                                wg_customers c
                            INNER JOIN (
                                SELECT
                                    MAX(id) id,
                                    customer_id
                                FROM wg_customer_diagnostic
                                WHERE `status` = 'iniciado'
                                GROUP BY customer_id
                            ) d ON c.id = d.customer_id
                            WHERE c.hasEconomicGroup = 1 AND c.id = :parent_id_4 AND c.status = 1) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id
GROUP BY q.id";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId,
            'parent_id_3' => $customerId,
            'parent_id_4' => $customerId
        ));

        return $results;
    }

    public function getDiagnosticContracting($customerId)
    {
        $query = "SELECT
	q.id, q.abbreviation name,
	SUM(questions) questions,
	SUM(IFNULL(answers,0)) answers,
	round(100, 2) target,
	round((SUM(total) / SUM(questions)), 2) valoration,
	round((SUM(cumple) / SUM(questions)), 2) cumple,
	round((SUM(nocumple) / SUM(questions)), 2) nocumple,
	round((SUM(noaplica) / SUM(questions)), 2) noaplica,
	SUM(total) total
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.classification = 'Contratante'
								AND eg.customer_id = :parent_id_1 AND ceg.status = 1 AND c.status = 1
                            UNION ALL
                            SELECT
                                c.id,
                                c.id `value`,
                                c.businessName `item`,
                                d.id diagnosticId
                            FROM
                                wg_customers c
                            INNER JOIN (
                                SELECT
                                    MAX(id) id,
                                    customer_id
                                FROM wg_customer_diagnostic
                                WHERE `status` = 'iniciado'
                                GROUP BY customer_id
                            ) d ON c.id = d.customer_id
                            WHERE c.classification = 'Contratante' AND c.id = :parent_id_2 AND c.status = 1
								) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id
			, sum(IFNULL(wg_rate.`value`,0)) total
			, sum(case when wg_rate.`code` = 'c' then wg_rate.`value` else 0 end) cumple
			, sum(case when wg_rate.`code` = 'nc' then wg_rate.`value` else 0 end) nocumple
			, sum(case when wg_rate.`code` = 'na' then wg_rate.`value` else 0 end) noaplica
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	left join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.classification = 'Contratante'
								AND eg.customer_id = :parent_id_3 AND ceg.status = 1 AND c.status = 1

								UNION ALL
                            SELECT
                                c.id,
                                c.id `value`,
                                c.businessName `item`,
                                d.id diagnosticId
                            FROM
                                wg_customers c
                            INNER JOIN (
                                SELECT
                                    MAX(id) id,
                                    customer_id
                                FROM wg_customer_diagnostic
                                WHERE `status` = 'iniciado'
                                GROUP BY customer_id
                            ) d ON c.id = d.customer_id
                            WHERE c.classification = 'Contratante' AND c.id = :parent_id_4 AND c.status = 1) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id
GROUP BY q.id";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId,
            'parent_id_3' => $customerId,
            'parent_id_4' => $customerId
        ));

        return $results;
    }

    public function getDiagnosticCustomer($customerId)
    {
        $query = "SELECT
	q.id, q.abbreviation name,
	SUM(questions) questions,
	SUM(IFNULL(answers,0)) answers,
	round(100, 2) target,
	round((SUM(total) / SUM(questions)), 2) valoration,
	round((SUM(cumple) / SUM(questions)), 2) cumple,
	round((SUM(nocumple) / SUM(questions)), 2) nocumple,
	round((SUM(noaplica) / SUM(questions)), 2) noaplica,
	SUM(total) total
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT c.id,
											c.id `value`,
											c.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON c.id = d.customer_id
							WHERE c.id = :parent_id_1 AND c.status = 1) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id
			, sum(IFNULL(wg_rate.`value`,0)) total
			, sum(case when wg_rate.`code` = 'c' then wg_rate.`value` else 0 end) cumple
			, sum(case when wg_rate.`code` = 'nc' then wg_rate.`value` else 0 end) nocumple
			, sum(case when wg_rate.`code` = 'na' then wg_rate.`value` else 0 end) noaplica
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	left join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT c.id,
											c.id `value`,
											c.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON c.id = d.customer_id
							WHERE c.id = :parent_id_2 AND c.status = 1) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id
GROUP BY q.id";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId
        ));

        return $results;
    }

    public function getDiagnosticEconomicContractor($customerId)
    {
        $query = "SELECT
	q.id, q.abbreviation name,
	SUM(questions) questions,
	SUM(IFNULL(answers,0)) answers,
	round(100, 2) target,
	round((SUM(total) / SUM(questions)), 2) valoration,
	round((SUM(cumple) / SUM(questions)), 2) cumple,
	round((SUM(nocumple) / SUM(questions)), 2) nocumple,
	round((SUM(noaplica) / SUM(questions)), 2) noaplica,
	SUM(total) total
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_1 AND ceg.classification = 'Contratista' AND ceg.status = 1 AND c.status = 1) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id
			, sum(IFNULL(wg_rate.`value`,0)) total
			, sum(case when wg_rate.`code` = 'c' then wg_rate.`value` else 0 end) cumple
			, sum(case when wg_rate.`code` = 'nc' then wg_rate.`value` else 0 end) nocumple
			, sum(case when wg_rate.`code` = 'na' then wg_rate.`value` else 0 end) noaplica
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	left join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
							INNER JOIN wg_customer_economic_group eg ON c.id = eg.parent_id
							INNER JOIN wg_customers ceg ON eg.customer_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE c.hasEconomicGroup = 1
								AND eg.parent_id = :parent_id_2 AND ceg.classification = 'Contratista' AND ceg.status = 1 AND c.status = 1) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id
GROUP BY q.id";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId
        ));

        return $results;
    }

    public function getDiagnosticContractingContractor($customerId)
    {
        $query = "SELECT
	q.id, q.abbreviation name,
	SUM(questions) questions,
	SUM(IFNULL(answers,0)) answers,
	round(100, 2) target,
	round((SUM(total) / SUM(questions)), 2) valoration,
	round((SUM(cumple) / SUM(questions)), 2) cumple,
	round((SUM(nocumple) / SUM(questions)), 2) nocumple,
	round((SUM(noaplica) / SUM(questions)), 2) noaplica,
	SUM(total) total
FROM (
	select COUNT(*) questions, diagnostic_id,  pp.`name`, pp.abbreviation, pp.id
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE eg.customer_id = :parent_id_1 AND ceg.classification = 'Contratista' AND ceg.status = 1 AND c.status = 1) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id
) q
LEFT JOIN (
	select COUNT(*) answers, diagnostic_id,  pp.`name`, pp.abbreviation , pp.id
			, sum(IFNULL(wg_rate.`value`,0)) total
			, sum(case when wg_rate.`code` = 'c' then wg_rate.`value` else 0 end) cumple
			, sum(case when wg_rate.`code` = 'nc' then wg_rate.`value` else 0 end) nocumple
			, sum(case when wg_rate.`code` = 'na' then wg_rate.`value` else 0 end) noaplica
	from wg_progam_prevention pp
	inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
	inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
	inner join wg_customer_diagnostic_prevention p on ppq.id = p.question_id
	left join wg_rate ON p.rate_id = wg_rate.id
	INNER JOIN
						 ( SELECT ceg.id,
											ceg.id `value`,
											ceg.businessName `item`,
											d.id diagnosticId
							FROM wg_customers c
                            INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                            INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
							INNER JOIN
								(SELECT MAX(id) id,
												customer_id
								 FROM wg_customer_diagnostic
								 WHERE `status` = 'iniciado'
								 GROUP BY customer_id) d ON ceg.id = d.customer_id
							WHERE eg.customer_id = :parent_id_2 AND ceg.classification = 'Contratista' AND ceg.status = 1 AND c.status = 1) d ON p.diagnostic_id = d.diagnosticId
	where pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
	GROUP BY diagnostic_id,  pp.`name`, pp.id

) a ON q.diagnostic_id = a.diagnostic_id AND q.id = a.id
GROUP BY q.id";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId
        ));

        return $results;
    }


    public function getEmployeesEconomicGroup($customerId)
    {
        $query = "
SELECT
	SUM(total) total,
	SUM(totalActive) totalActive,
	SUM(totalAuthorized) totalAuthorized,
	SUM(totalNoAuthorized) totalNoAuthorized
FROM (
SELECT
	COUNT(*) total,
	SUM(case when ce.isActive = 1 then 1 else 0 end) totalActive,
	SUM(case when ce.isAuthorized = 1 then 1 else 0 end) totalAuthorized,
	SUM(case when ce.isAuthorized is null then 1 else 0 end) totalNoAuthorized
FROM wg_customers c
INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
INNER JOIN wg_customers cc ON cc.id = eg.customer_id
INNER JOIN wg_customer_employee ce ON ce.customer_id = cc.id
WHERE c.hasEconomicGroup = 1
	AND c.id = :parent_id_1 AND cc.status = 1 AND c.status = 1 -- AND cc.classification = 'Contratista'

UNION ALL

SELECT
	COUNT(*) total,
	SUM(case when ce.isActive = 1 then 1 else 0 end) totalActive,
	SUM(case when ce.isAuthorized = 1 then 1 else 0 end) totalAuthorized,
	SUM(case when ce.isAuthorized is null then 1 else 0 end) totalNoAuthorized
FROM wg_customers c
INNER JOIN wg_customer_employee ce ON ce.customer_id = c.id
WHERE c.hasEconomicGroup = 1
	AND c.id = :parent_id_2 AND c.status = 1-- AND cc.classification = 'Contratista'
) p
";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId
        ));

        return count($results) ? $results[0] : null;
    }

    public function getEmployeesContracting($customerId)
    {
        $query = "
SELECT
	SUM(total) total,
	SUM(totalActive) totalActive,
	SUM(totalAuthorized) totalAuthorized,
	SUM(totalNoAuthorized) totalNoAuthorized
FROM (
SELECT
	COUNT(*) total,
	SUM(case when ce.isActive = 1 then 1 else 0 end) totalActive,
	SUM(case when ce.isAuthorized = 1 then 1 else 0 end) totalAuthorized,
	SUM(case when ce.isAuthorized is null then 1 else 0 end) totalNoAuthorized
FROM wg_customers c
INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
INNER JOIN wg_customer_employee ce ON ce.customer_id = cc.id
WHERE c.id = :parent_id_1 AND c.classification = 'Contratante' AND cc.status = 1 AND c.status = 1

UNION ALL

SELECT
	COUNT(*) total,
	SUM(case when ce.isActive = 1 then 1 else 0 end) totalActive,
	SUM(case when ce.isAuthorized = 1 then 1 else 0 end) totalAuthorized,
	SUM(case when ce.isAuthorized is null then 1 else 0 end) totalNoAuthorized
FROM wg_customers c
INNER JOIN wg_customer_employee ce ON ce.customer_id = c.id
WHERE c.id = :parent_id_2 AND c.classification = 'Contratante' AND c.status = 1
) p
";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId
        ));

        return count($results) ? $results[0] : null;
    }

    public function getEmployeesCustomer($customerId)
    {
        $query = "
SELECT
	COUNT(*) total,
	SUM(case when ce.isActive = 1 then 1 else 0 end) totalActive,
	SUM(case when ce.isAuthorized = 1 then 1 else 0 end) totalAuthorized,
	SUM(case when ce.isAuthorized is null then 1 else 0 end) totalNoAuthorized
FROM wg_customers c
INNER JOIN wg_customer_employee ce ON ce.customer_id = c.id
WHERE  c.id = :parent_id_1 AND c.status = 1
";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId
        ));

        return count($results) ? $results[0] : null;
    }

    public function getEmployeesEconomicGroupContractor($customerId)
    {
        $query = "
SELECT
	COUNT(*) total,
	SUM(case when ce.isActive = 1 then 1 else 0 end) totalActive,
	SUM(case when ce.isAuthorized = 1 then 1 else 0 end) totalAuthorized,
	SUM(case when ce.isAuthorized is null then 1 else 0 end) totalNoAuthorized
FROM wg_customers c
INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
INNER JOIN wg_customers cc ON cc.id = eg.customer_id
INNER JOIN wg_customer_employee ce ON ce.customer_id = cc.id
WHERE c.hasEconomicGroup = 1
	AND c.id = :parent_id_1 AND cc.classification = 'Contratista' AND cc.status = 1 AND c.status = 1
";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId
        ));

        return count($results) ? $results[0] : null;
    }

    public function getEconomicGroupContractor($customerId)
    {
        $query = "
SELECT SUM(total) total
FROM (
SELECT
	COUNT(*) total
FROM wg_customers c
INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
INNER JOIN wg_customer_contractor cct ON cct.customer_id = eg.customer_id
INNER JOIN wg_customers cc ON cc.id = cct.contractor_id
WHERE c.hasEconomicGroup = 1
	AND c.id = :parent_id_1 AND cc.classification = 'Contratista' AND cc.status = 1 AND c.status = 1

union ALL

SELECT
	COUNT(*) total
FROM wg_customers c
INNER JOIN wg_customer_contractor cct ON cct.customer_id = c.id
INNER JOIN wg_customers cc ON cc.id = cct.contractor_id
WHERE c.id = :parent_id_2 AND cc.classification = 'Contratista' AND cc.status = 1 AND c.status = 1) p

";

        $results = DB::select($query, array(
            'parent_id_1' => $customerId,
            'parent_id_2' => $customerId,
        ));

        return count($results) ? $results[0] : null;
    }

    public function getCustomerContractor($customerId)
    {
        $query = "
SELECT SUM(total) total
FROM (
SELECT
	COUNT(*) total
FROM wg_customers c
INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
INNER JOIN wg_customer_contractor cct ON cct.customer_id = eg.customer_id
INNER JOIN wg_customers cc ON cc.id = cct.contractor_id
WHERE c.hasEconomicGroup = 1
	AND c.id = :parent_id_1 AND cc.classification = 'Contratista' AND cc.status = 1 AND c.status = 1

union ALL

SELECT
	COUNT(*) total
FROM wg_customers c
INNER JOIN wg_customer_contractor cct ON cct.customer_id = c.id
INNER JOIN wg_customers cc ON cc.id = cct.contractor_id
WHERE c.id = :parent_id_2 AND cc.classification = 'Contratista' AND cc.status = 1 AND c.status = 1) p
";

        $results = DB::select($query, array(
            'parent_id_1' => 0,
            'parent_id_2' => $customerId,
        ));

        return count($results) ? $results[0] : null;
    }

    public function getEconomicGroupDisabilityDays($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(disabilityDays) disabilityDays,
	SUM(targetDisabilityDays) targetDisabilityDays
FROM (
		select 	classification,
				SUM(indicator.disabilityDays) disabilityDays,
				SUM(indicator.targetDisabilityDays) targetDisabilityDays
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.disabilityDays) disabilityDays,
				SUM(indicator.targetDisabilityDays) targetDisabilityDays
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }

    public function getEconomicGroupEvents($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(eventNumber) eventNumber,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }

    public function getEconomicGroupDiseaseRate($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(diseaseRate) diseaseRate,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.diseaseRate) diseaseRate,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.diseaseRate) diseaseRate,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }

    public function getEconomicGroupFrequencyIndex($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(frequencyIndex) frequencyIndex,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.frequencyIndex) frequencyIndex,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.frequencyIndex) frequencyIndex,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }

    public function getEconomicGroupSeverityIndex($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(severityIndex) severityIndex,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.severityIndex) severityIndex,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.severityIndex) severityIndex,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }

    public function getEconomicGroupEventsChart($customerId, $year, $classification)
    {
        $query = "
        SELECT
		label,
		color,
		customer_id,
		year,
		SUM(IFNULL(ENE,0)) ENE,
		SUM(IFNULL(FEB,0)) FEB,
		SUM(IFNULL(MAR,0)) MAR,
		SUM(IFNULL(ABR,0)) ABR,
		SUM(IFNULL(MAY,0)) MAY,
		SUM(IFNULL(JUN,0)) JUN,
		SUM(IFNULL(JUL,0)) JUL,
		SUM(IFNULL(AGO,0)) AGO,
		SUM(IFNULL(SEP,0)) SEP,
		SUM(IFNULL(OCT,0)) OCT,
		SUM(IFNULL(NOV,0)) NOV,
		SUM(IFNULL(DIC,0)) DIC,
		SUM(IFNULL(Total,0)) Total
FROM
(
		Select
				'Eventos' label
				, '#e0d653' color
				, customer_id
				, YEAR(wgc.periodDate) year
				, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ENE
				, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.eventNumber,0),2) end) FEB
				, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAR
				, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ABR
				, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAY
				, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUN
				, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUL
				, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.eventNumber,0),2) end) AGO
				, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.eventNumber,0),2) end) SEP
				, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.eventNumber,0),2) end) OCT
				, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.eventNumber,0),2) end) NOV
				, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.eventNumber,0),2) end) DIC
				, sum(wgc.eventNumber) 'Total'
		from wg_customer_absenteeism_indicator wgc
		WHERE YEAR(wgc.periodDate) = :year_1 and customer_id = :customer_id_1 and classification = :classification_1
		group by customer_id, YEAR(wgc.periodDate)

		UNION ALL

		Select
				'Eventos' label
				, '#e0d653' color
				, c.id
				, YEAR(wgc.periodDate) year
				, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ENE
				, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.eventNumber,0),2) end) FEB
				, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAR
				, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ABR
				, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAY
				, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUN
				, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUL
				, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.eventNumber,0),2) end) AGO
				, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.eventNumber,0),2) end) SEP
				, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.eventNumber,0),2) end) OCT
				, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.eventNumber,0),2) end) NOV
				, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.eventNumber,0),2) end) DIC
				, sum(wgc.eventNumber) 'Total'
				FROM wg_customers c
						INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
						INNER JOIN wg_customers cc ON cc.id = eg.customer_id
						INNER JOIN wg_customer_absenteeism_indicator wgc ON wgc.customer_id = eg.customer_id
		WHERE YEAR(wgc.periodDate) = :year_2 and c.id = :customer_id_2 and wgc.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(wgc.periodDate)) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }


    public function getEconomicGroupContractorEvents($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(eventNumber) eventNumber,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
		AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }


    public function getEconomicGroupCustomerDisabilityDays($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(disabilityDays) disabilityDays,
	SUM(targetDisabilityDays) targetDisabilityDays
FROM (
		select 	classification,
				SUM(indicator.disabilityDays) disabilityDays,
				SUM(indicator.targetDisabilityDays) targetDisabilityDays
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.disabilityDays) disabilityDays,
				SUM(indicator.targetDisabilityDays) targetDisabilityDays
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerEvents($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(eventNumber) eventNumber,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerDiseaseRate($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(diseaseRate) diseaseRate,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.diseaseRate) diseaseRate,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.diseaseRate) diseaseRate,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerFrequencyIndex($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(frequencyIndex) frequencyIndex,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.frequencyIndex) frequencyIndex,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.frequencyIndex) frequencyIndex,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerSeverityIndex($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(severityIndex) severityIndex,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.severityIndex) severityIndex,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.severityIndex) severityIndex,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
				INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
				INNER JOIN wg_customers cc ON cc.id = eg.customer_id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerEventsChart($customerId, $year, $classification)
    {
        $query = "
        SELECT
		label,
		color,
		customer_id,
		year,
		SUM(IFNULL(ENE,0)) ENE,
		SUM(IFNULL(FEB,0)) FEB,
		SUM(IFNULL(MAR,0)) MAR,
		SUM(IFNULL(ABR,0)) ABR,
		SUM(IFNULL(MAY,0)) MAY,
		SUM(IFNULL(JUN,0)) JUN,
		SUM(IFNULL(JUL,0)) JUL,
		SUM(IFNULL(AGO,0)) AGO,
		SUM(IFNULL(SEP,0)) SEP,
		SUM(IFNULL(OCT,0)) OCT,
		SUM(IFNULL(NOV,0)) NOV,
		SUM(IFNULL(DIC,0)) DIC,
		SUM(IFNULL(Total,0)) Total
FROM
(
		Select
				'Eventos' label
				, '#e0d653' color
				, customer_id
				, YEAR(wgc.periodDate) year
				, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ENE
				, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.eventNumber,0),2) end) FEB
				, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAR
				, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ABR
				, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAY
				, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUN
				, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUL
				, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.eventNumber,0),2) end) AGO
				, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.eventNumber,0),2) end) SEP
				, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.eventNumber,0),2) end) OCT
				, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.eventNumber,0),2) end) NOV
				, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.eventNumber,0),2) end) DIC
				, sum(wgc.eventNumber) 'Total'
		from wg_customer_absenteeism_indicator wgc
		WHERE YEAR(wgc.periodDate) = :year_1 and customer_id = :customer_id_1 and classification = :classification_1
		group by customer_id, YEAR(wgc.periodDate)

		UNION ALL

		Select
				'Eventos' label
				, '#e0d653' color
				, c.id
				, YEAR(wgc.periodDate) year
				, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ENE
				, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.eventNumber,0),2) end) FEB
				, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAR
				, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ABR
				, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAY
				, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUN
				, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUL
				, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.eventNumber,0),2) end) AGO
				, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.eventNumber,0),2) end) SEP
				, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.eventNumber,0),2) end) OCT
				, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.eventNumber,0),2) end) NOV
				, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.eventNumber,0),2) end) DIC
				, sum(wgc.eventNumber) 'Total'
				FROM wg_customers c
						INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
						INNER JOIN wg_customers cc ON cc.id = eg.customer_id
						INNER JOIN wg_customer_absenteeism_indicator wgc ON wgc.customer_id = eg.customer_id
		WHERE YEAR(wgc.periodDate) = :year_2 and c.id = :customer_id_2 and wgc.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(wgc.periodDate)) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }


    public function getEconomicGroupAccidentType($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_accident_type' ) lty ON lty.value = cora.accident_type COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), accident_type

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_accident_type' ) lty ON lty.value = cora.accident_type COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.status = 1 AND c.status = 1
			-- AND cc.classification = 'Contratista'

		group by c.id, YEAR(accident_date), accident_type
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupGender($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'gender' ) lty ON lty.value = cora.gender COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), gender

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'gender' ) lty ON lty.value = cora.gender COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1
			AND cc.status = 1 AND c.status = 1
		-- AND cc.classification = 'Contratista'
		group by c.id, YEAR(accident_date), gender
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupWorkingDay($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_week_day' ) lty ON lty.value = cora.accident_week_day COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_week_day

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_week_day' ) lty ON lty.value = cora.accident_week_day COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1
			AND cc.status = 1 AND c.status = 1
			-- AND cc.classification = 'Contratista'
		group by c.id, YEAR(accident_date), accident_week_day
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupWorkingTime($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_regular_work' ) lty ON lty.value = cora.accident_regular_work
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_regular_work

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_regular_work' ) lty ON lty.value = cora.accident_regular_work
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 -- AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_regular_work
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupInjury($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_lesion p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_lesion_type' ) lty ON lty.value = p.lesion_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), p.lesion_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_lesion p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_lesion_type' ) lty ON lty.value = p.lesion_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 -- AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), p.lesion_id
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupFactor($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_factor p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_factor' ) lty ON lty.value = p.factor_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), p.factor_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_factor p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_factor' ) lty ON lty.value = p.factor_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 -- AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), p.factor_id
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupLocation($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_location' ) lty ON lty.value = cora.accident_location COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_location

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_location' ) lty ON lty.value = cora.accident_location COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1
			AND cc.status = 1 AND c.status = 1
		-- AND cc.classification = 'Contratista'
		group by c.id, YEAR(accident_date), accident_location
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupMechanism($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_mechanism p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_mechanism' ) lty ON lty.value = p.mechanism_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), p.mechanism_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_mechanism p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_mechanism' ) lty ON lty.value = p.mechanism_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1
			AND cc.status = 1 AND c.status = 1
			-- AND cc.classification = 'Contratista'
		group by c.id, YEAR(accident_date), p.mechanism_id
) p
GROUP BY p.label
";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupBody($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_body p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_body_part' ) lty ON lty.value = p.body_part_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), p.body_part_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_body p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_body_part' ) lty ON lty.value = p.body_part_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1
			AND cc.status = 1 AND c.status = 1
			-- AND cc.classification = 'Contratista'
		group by c.id, YEAR(accident_date), p.body_part_id
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupLink($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_employment_relationship' ) lty ON lty.value = cora.customer_type_employment_relationship COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), customer_type_employment_relationship

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_employment_relationship' ) lty ON lty.value = cora.customer_type_employment_relationship COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1
			AND cc.status = 1 AND c.status = 1
		-- AND cc.classification = 'Contratista'
		group by c.id, YEAR(accident_date), customer_type_employment_relationship
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupPlace($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_place' ) lty ON lty.value = cora.accident_place COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_place

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_place' ) lty ON lty.value = cora.accident_place COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1
			AND cc.status = 1 AND c.status = 1
		-- AND cc.classification = 'Contratista'
		group by c.id, YEAR(accident_date), accident_place
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupZone($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_zone' ) lty ON lty.value = cora.accident_zone COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_zone

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_zone' ) lty ON lty.value = cora.accident_zone COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1
			AND cc.status = 1 AND c.status = 1
		-- AND cc.classification = 'Contratista'
		group by c.id, YEAR(accident_date), accident_zone
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getEconomicGroupRegularWork($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select SUM(case when accident_regular_work = 1 then 1 else 0 end) value, case when accident_regular_work = 1 then 'Si' else 'No' end  label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_regular_work

		UNION ALL

		select SUM(case when accident_regular_work = 1 then 1 else 0 end) value, case when accident_regular_work = 1 then 'Si' else 'No' end label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1
			AND cc.status = 1 AND c.status = 1
		-- AND cc.classification = 'Contratista'
		group by c.id, YEAR(accident_date), accident_regular_work
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }


    public function getEconomicGroupCustomerAccidentType($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_accident_type' ) lty ON lty.value = cora.accident_type COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_type

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_accident_type' ) lty ON lty.value = cora.accident_type COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1
			AND cc.status = 1 AND c.status = 1
		-- AND cc.classification = 'Contratista'
		group by c.id, YEAR(accident_date), accident_type
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerGender($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'gender' ) lty ON lty.value = cora.gender COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), gender

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'gender' ) lty ON lty.value = cora.gender COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), gender
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerWorkingDay($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_week_day' ) lty ON lty.value = cora.accident_week_day COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_week_day

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_week_day' ) lty ON lty.value = cora.accident_week_day COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_week_day
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerWorkingTime($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_regular_work' ) lty ON lty.value = cora.accident_regular_work
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_regular_work

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_regular_work' ) lty ON lty.value = cora.accident_regular_work
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_regular_work
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerInjury($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_lesion p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_lesion_type' ) lty ON lty.value = p.lesion_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), p.lesion_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_lesion p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_lesion_type' ) lty ON lty.value = p.lesion_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), p.lesion_id
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerFactor($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_factor p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_factor' ) lty ON lty.value = p.factor_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), p.factor_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_factor p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_factor' ) lty ON lty.value = p.factor_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), p.factor_id
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerLocation($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_location' ) lty ON lty.value = cora.accident_location COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_location

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_location' ) lty ON lty.value = cora.accident_location COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_location
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerMechanism($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_mechanism p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_mechanism' ) lty ON lty.value = p.mechanism_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), p.mechanism_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_mechanism p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_mechanism' ) lty ON lty.value = p.mechanism_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), p.mechanism_id
) p
GROUP BY p.label
";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerBody($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_body p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_body_part' ) lty ON lty.value = p.body_part_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), p.body_part_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_body p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_body_part' ) lty ON lty.value = p.body_part_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), p.body_part_id
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerLink($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_employment_relationship' ) lty ON lty.value = cora.customer_type_employment_relationship COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), customer_type_employment_relationship

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_employment_relationship' ) lty ON lty.value = cora.customer_type_employment_relationship COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), customer_type_employment_relationship
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerPlace($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_place' ) lty ON lty.value = cora.accident_place COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_place

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_place' ) lty ON lty.value = cora.accident_place COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_place
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerZone($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_zone' ) lty ON lty.value = cora.accident_zone COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_zone

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_zone' ) lty ON lty.value = cora.accident_zone COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_zone
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }

    public function getEconomicGroupCustomerRegularWork($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select SUM(case when accident_regular_work = 1 then 1 else 0 end) value, case when accident_regular_work = 1 then 'Si' else 'No' end  label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1 AND c.status = 1
		group by customer_id, YEAR(accident_date), accident_regular_work

		UNION ALL

		select SUM(case when accident_regular_work = 1 then 1 else 0 end) value, case when accident_regular_work = 1 then 'Si' else 'No' end label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_economic_group eg ON eg.parent_id = c.id
		INNER JOIN wg_customers cc ON cc.id = eg.customer_id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 and c.hasEconomicGroup = 1 AND cc.classification = 'Contratista'
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_regular_work
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0
        ));

        return $results;
    }


    public function getContractingDisabilityDays($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(disabilityDays) disabilityDays,
	SUM(targetDisabilityDays) targetDisabilityDays
FROM (
		select 	classification,
				SUM(indicator.disabilityDays) disabilityDays,
				SUM(indicator.targetDisabilityDays) targetDisabilityDays
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.disabilityDays) disabilityDays,
				SUM(indicator.targetDisabilityDays) targetDisabilityDays
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }

    public function getContractingEvents($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(eventNumber) eventNumber,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }

    public function getContractingDiseaseRate($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(diseaseRate) diseaseRate,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.diseaseRate) diseaseRate,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.diseaseRate) diseaseRate,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }

    public function getContractingFrequencyIndex($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(frequencyIndex) frequencyIndex,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.frequencyIndex) frequencyIndex,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.frequencyIndex) frequencyIndex,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }

    public function getContractingSeverityIndex($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(severityIndex) severityIndex,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.severityIndex) severityIndex,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.severityIndex) severityIndex,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }

    public function getContractingEventsChart($customerId, $year, $classification)
    {
        $query = "
        SELECT
		label,
		color,
		customer_id,
		year,
		SUM(IFNULL(ENE,0)) ENE,
		SUM(IFNULL(FEB,0)) FEB,
		SUM(IFNULL(MAR,0)) MAR,
		SUM(IFNULL(ABR,0)) ABR,
		SUM(IFNULL(MAY,0)) MAY,
		SUM(IFNULL(JUN,0)) JUN,
		SUM(IFNULL(JUL,0)) JUL,
		SUM(IFNULL(AGO,0)) AGO,
		SUM(IFNULL(SEP,0)) SEP,
		SUM(IFNULL(OCT,0)) OCT,
		SUM(IFNULL(NOV,0)) NOV,
		SUM(IFNULL(DIC,0)) DIC,
		SUM(IFNULL(Total,0)) Total
FROM
(
		Select
				'Eventos' label
				, '#e0d653' color
				, customer_id
				, YEAR(wgc.periodDate) year
				, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ENE
				, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.eventNumber,0),2) end) FEB
				, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAR
				, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ABR
				, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAY
				, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUN
				, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUL
				, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.eventNumber,0),2) end) AGO
				, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.eventNumber,0),2) end) SEP
				, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.eventNumber,0),2) end) OCT
				, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.eventNumber,0),2) end) NOV
				, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.eventNumber,0),2) end) DIC
				, sum(wgc.eventNumber) 'Total'
		from wg_customer_absenteeism_indicator wgc
		WHERE YEAR(wgc.periodDate) = :year_1 and customer_id = :customer_id_1 and classification = :classification_1
		group by customer_id, YEAR(wgc.periodDate)

		UNION ALL

		Select
				'Eventos' label
				, '#e0d653' color
				, c.id
				, YEAR(wgc.periodDate) year
				, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ENE
				, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.eventNumber,0),2) end) FEB
				, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAR
				, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ABR
				, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAY
				, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUN
				, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUL
				, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.eventNumber,0),2) end) AGO
				, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.eventNumber,0),2) end) SEP
				, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.eventNumber,0),2) end) OCT
				, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.eventNumber,0),2) end) NOV
				, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.eventNumber,0),2) end) DIC
				, sum(wgc.eventNumber) 'Total'
				FROM wg_customers c
                        INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                        INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
						INNER JOIN wg_customer_absenteeism_indicator wgc ON wgc.customer_id = eg.customer_id
		WHERE YEAR(wgc.periodDate) = :year_2 and c.id = :customer_id_2 and wgc.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(wgc.periodDate)) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }


    public function getContractingContractorEvents($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(eventNumber) eventNumber,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year,
            'classification_1' => $classification,
            'classification_2' => $classification
        ));

        return $results;
    }


    public function getContractingCustomerDisabilityDays($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(disabilityDays) disabilityDays,
	SUM(targetDisabilityDays) targetDisabilityDays
FROM (
		select 	classification,
				SUM(indicator.disabilityDays) disabilityDays,
				SUM(indicator.targetDisabilityDays) targetDisabilityDays
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.disabilityDays) disabilityDays,
				SUM(indicator.targetDisabilityDays) targetDisabilityDays
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }

    public function getContractingCustomerEvents($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(eventNumber) eventNumber,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.eventNumber) eventNumber,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }

    public function getContractingCustomerDiseaseRate($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(diseaseRate) diseaseRate,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.diseaseRate) diseaseRate,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.diseaseRate) diseaseRate,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }

    public function getContractingCustomerFrequencyIndex($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(frequencyIndex) frequencyIndex,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.frequencyIndex) frequencyIndex,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.frequencyIndex) frequencyIndex,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }

    public function getContractingCustomerSeverityIndex($customerId, $year, $classification)
    {
        $query = "SELECT
	classification,
	SUM(severityIndex) severityIndex,
	SUM(targetEvent) targetEvent
FROM (
		select 	classification,
				SUM(indicator.severityIndex) severityIndex,
				SUM(indicator.targetEvent) targetEvent
		from wg_customer_absenteeism_indicator indicator
		where indicator.customer_id = :customer_id_1 and YEAR(indicator.periodDate) = :year_1 and classification = :classification_1
		group by YEAR(indicator.periodDate)

		union ALL

		select 	indicator.classification,
				SUM(indicator.severityIndex) severityIndex,
				SUM(indicator.targetEvent) targetEvent
		FROM wg_customers c
                INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
				INNER JOIN wg_customer_absenteeism_indicator indicator ON indicator.customer_id = eg.customer_id
		where c.id = :customer_id_2 and YEAR(indicator.periodDate) = :year_2 and indicator.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by YEAR(indicator.periodDate) ) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }

    public function getContractingCustomerEventsChart($customerId, $year, $classification)
    {
        $query = "
        SELECT
		label,
		color,
		customer_id,
		year,
		SUM(IFNULL(ENE,0)) ENE,
		SUM(IFNULL(FEB,0)) FEB,
		SUM(IFNULL(MAR,0)) MAR,
		SUM(IFNULL(ABR,0)) ABR,
		SUM(IFNULL(MAY,0)) MAY,
		SUM(IFNULL(JUN,0)) JUN,
		SUM(IFNULL(JUL,0)) JUL,
		SUM(IFNULL(AGO,0)) AGO,
		SUM(IFNULL(SEP,0)) SEP,
		SUM(IFNULL(OCT,0)) OCT,
		SUM(IFNULL(NOV,0)) NOV,
		SUM(IFNULL(DIC,0)) DIC,
		SUM(IFNULL(Total,0)) Total
FROM
(
		Select
				'Eventos' label
				, '#e0d653' color
				, customer_id
				, YEAR(wgc.periodDate) year
				, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ENE
				, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.eventNumber,0),2) end) FEB
				, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAR
				, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ABR
				, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAY
				, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUN
				, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUL
				, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.eventNumber,0),2) end) AGO
				, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.eventNumber,0),2) end) SEP
				, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.eventNumber,0),2) end) OCT
				, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.eventNumber,0),2) end) NOV
				, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.eventNumber,0),2) end) DIC
				, sum(wgc.eventNumber) 'Total'
		from wg_customer_absenteeism_indicator wgc
		WHERE YEAR(wgc.periodDate) = :year_1 and customer_id = :customer_id_1 and classification = :classification_1
		group by customer_id, YEAR(wgc.periodDate)

		UNION ALL

		Select
				'Eventos' label
				, '#e0d653' color
				, c.id
				, YEAR(wgc.periodDate) year
				, sum(case when MONTH(wgc.periodDate) = 1 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ENE
				, sum(case when MONTH(wgc.periodDate) = 2 then ROUND(IFNULL(wgc.eventNumber,0),2) end) FEB
				, sum(case when MONTH(wgc.periodDate) = 3 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAR
				, sum(case when MONTH(wgc.periodDate) = 4 then ROUND(IFNULL(wgc.eventNumber,0),2) end) ABR
				, sum(case when MONTH(wgc.periodDate) = 5 then ROUND(IFNULL(wgc.eventNumber,0),2) end) MAY
				, sum(case when MONTH(wgc.periodDate) = 6 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUN
				, sum(case when MONTH(wgc.periodDate) = 7 then ROUND(IFNULL(wgc.eventNumber,0),2) end) JUL
				, sum(case when MONTH(wgc.periodDate) = 8 then ROUND(IFNULL(wgc.eventNumber,0),2) end) AGO
				, sum(case when MONTH(wgc.periodDate) = 9 then ROUND(IFNULL(wgc.eventNumber,0),2) end) SEP
				, sum(case when MONTH(wgc.periodDate) = 10 then ROUND(IFNULL(wgc.eventNumber,0),2) end) OCT
				, sum(case when MONTH(wgc.periodDate) = 11 then ROUND(IFNULL(wgc.eventNumber,0),2) end) NOV
				, sum(case when MONTH(wgc.periodDate) = 12 then ROUND(IFNULL(wgc.eventNumber,0),2) end) DIC
				, sum(wgc.eventNumber) 'Total'
				FROM wg_customers c
                        INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
                        INNER JOIN wg_customers cc ON eg.contractor_id = cc.id
						INNER JOIN wg_customer_absenteeism_indicator wgc ON wgc.customer_id = eg.customer_id
		WHERE YEAR(wgc.periodDate) = :year_2 and c.id = :customer_id_2 and wgc.classification = :classification_2
			AND cc.status = 1 AND c.status = 1
		group by c.id, YEAR(wgc.periodDate)) p";

        $results = DB::select($query, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => 0,
            'year_1' => $year,
            'year_2' => 0,
            'classification_1' => $classification,
            'classification_2' => 0
        ));

        return $results;
    }


    public function getContractingAccidentType($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_accident_type' ) lty ON lty.value = cora.accident_type COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), accident_type

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_accident_type' ) lty ON lty.value = cora.accident_type COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_type
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingGender($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'gender' ) lty ON lty.value = cora.gender COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), gender

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'gender' ) lty ON lty.value = cora.gender COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), gender
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingWorkingDay($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_week_day' ) lty ON lty.value = cora.accident_week_day COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), accident_week_day

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_week_day' ) lty ON lty.value = cora.accident_week_day COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_week_day
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingWorkingTime($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_regular_work' ) lty ON lty.value = cora.accident_regular_work
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), accident_regular_work

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_regular_work' ) lty ON lty.value = cora.accident_regular_work
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_regular_work
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingInjury($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_lesion p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_lesion_type' ) lty ON lty.value = p.lesion_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), p.lesion_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_lesion p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_lesion_type' ) lty ON lty.value = p.lesion_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), p.lesion_id
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingFactor($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_factor p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_factor' ) lty ON lty.value = p.factor_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), p.factor_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_factor p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_factor' ) lty ON lty.value = p.factor_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), p.factor_id
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingLocation($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_location' ) lty ON lty.value = cora.accident_location COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), accident_location

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_location' ) lty ON lty.value = cora.accident_location COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_location
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingMechanism($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_mechanism p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_mechanism' ) lty ON lty.value = p.mechanism_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), p.mechanism_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_mechanism p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_mechanism' ) lty ON lty.value = p.mechanism_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), p.mechanism_id
) p
GROUP BY p.label
";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingBody($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customer_occupational_report_al_body p on cora.id = p.customer_occupational_report_al_id
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_body_part' ) lty ON lty.value = p.body_part_id COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), p.body_part_id

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join wg_customer_occupational_report_al_body p on cora.id = p.customer_occupational_report_al_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_body_part' ) lty ON lty.value = p.body_part_id COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), p.body_part_id
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingLink($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_employment_relationship' ) lty ON lty.value = cora.customer_type_employment_relationship COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), customer_type_employment_relationship

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_employment_relationship' ) lty ON lty.value = cora.customer_type_employment_relationship COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), customer_type_employment_relationship
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingPlace($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_place' ) lty ON lty.value = cora.accident_place COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), accident_place

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_place' ) lty ON lty.value = cora.accident_place COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_place
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingZone($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_zone' ) lty ON lty.value = cora.accident_zone COLLATE utf8_general_ci
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), accident_zone

		UNION ALL

		select count(*) value,  lty.item label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		inner join ( SELECT *
			 FROM system_parameters
			 WHERE `group` = 'wg_report_zone' ) lty ON lty.value = cora.accident_zone COLLATE utf8_general_ci
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_zone
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }

    public function getContractingRegularWork($customerId, $year)
    {
        $sql = "
SELECT SUM(p.value) value,  p.label FROM (
		select SUM(case when accident_regular_work = 1 then 1 else 0 end) value, case when accident_regular_work = 1 then 'Si' else 'No' end  label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		from
		wg_customer_occupational_report_al cora
		inner join wg_customers c on c.id = cora.customer_id
		WHERE customer_id = :customer_id_1 and YEAR(accident_date) = :year_1 and c.hasEconomicGroup = 1
		group by customer_id, YEAR(accident_date), accident_regular_work

		UNION ALL

		select SUM(case when accident_regular_work = 1 then 1 else 0 end) value, case when accident_regular_work = 1 then 'Si' else 'No' end label, '#5AD3D1' highlight, '#46BFBD' color, YEAR(accident_date) yearValue
		FROM wg_customers c
		INNER JOIN wg_customer_contractor eg ON c.id = eg.customer_id
		INNER JOIN wg_customers ceg ON eg.contractor_id = ceg.id
		INNER JOIN wg_customer_occupational_report_al cora ON cora.customer_id = eg.customer_id
		WHERE c.id = :customer_id_2 and YEAR(accident_date) = :year_2 AND c.classification = 'Contratante'
			AND ceg.status = 1 AND c.status = 1
		group by c.id, YEAR(accident_date), accident_regular_work
) p
GROUP BY p.label";

        $results = DB::select($sql, array(
            'customer_id_1' => $customerId,
            'customer_id_2' => $customerId,
            'year_1' => $year,
            'year_2' => $year
        ));

        return $results;
    }
}
