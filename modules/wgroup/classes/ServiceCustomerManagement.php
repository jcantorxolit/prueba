<?php

namespace Wgroup\Classes;

use DB;
use Exception;
use Log;
use Str;
use Wgroup\Models\CustomerManagement;
use Wgroup\Models\CustomerManagementRepository;

class ServiceCustomerManagement {

    protected static $instance;
    protected $sessionKey = 'service_api';
    protected $customerManagementRepository;

    function __construct() {
       // $this->customerRepository = new CustomerReporistory();
    }

    public function init() {

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

        $model = new CustomerManagement();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->customerManagementRepository = new CustomerManagementRepository($model);

        if ($perPage > 0) {
            $this->customerManagementRepository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_management.customer_id',
            'wg_customer_management.status',
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
                    $this->customerManagementRepository->sortBy($colName, $dir);
                } else {
                    $this->customerManagementRepository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->customerManagementRepository->sortBy('wg_customer_management.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_management.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_management.status', $search);
            $filters[] = array('wg_agent.name', $search);
            $filters[] = array('diags.item', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_management.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_management.status', '0');
        }


        $this->customerManagementRepository->setColumns(['wg_customer_management.*']);

        return $this->customerManagementRepository->getFilteredsOptional($filters, false, "");
    }

    public function getAllSettingBy($sorting = array(), $managementId) {

        $columnNames = ["id", "name", "abbreviation", "active"];
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

        $query = "SELECT cpm.id, name, abbreviation, active, management_id  FROM wg_customer_management_program cpm
                    INNER JOIN wg_program_management pm on cpm.program_id = pm.id
                    WHERE cpm.management_id = :management_id
                    order by $columnOrder $dirOrder";
        //Log::info($query);
        //Log::info($managementId);
        $results = DB::select( $query, array(
            'management_id' => $managementId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getAllSummaryBy($sorting = array(), $managementId) {

        $columnNames = ["id", "abbreviation", "name", "questions", "answers", "advance", "average"];
        $columnOrder = "id";
        $dirOrder = "asc";

        if (!empty($sorting)){
            $columnOrder =  $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "abbreviation")
            {
                $columnOrder = "id";
                $dirOrder =  "asc";
            }
            else
                $dirOrder =  $sorting[0]["dir"];
        }

        $query = "select programa.id, workplace, name,  abbreviation, questions , answers, round((answers / questions) * 100, 2) advance, 
            ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END ), 0 ), 2 ) AS average, 
            total
                    from(
                                select  pp.id, wg_customer_config_workplace.`name` as workplace, pp.`name`, pp.abbreviation ,count(*) questions
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
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :managementId
                                group by  pp.`name`, pp.id
                    )programa
                    order by $columnOrder $dirOrder";

        $results = DB::select( $query, array(
            'management_id' => $managementId,
            'managementId' => $managementId,
        ));

        return $results;
    }

    public function getAllSummaryByExport($sorting = array(), $managementId) {

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

        $query = "select workplace as `Centro de Trabajo`,  abbreviation Código, name Programa, questions `Nro Preguntas`, answers `Nro Respuestas`, round((answers / questions) * 100, 2) `% Avance` , 
                    ROUND( IFNULL( SUM( CASE WHEN isWeighted = 1 THEN total ELSE total / questions END ), 0 ), 2 ) AS `Promedio`, total `Total`
                    from(
                                select  pp.id, wg_customer_config_workplace.name as workplace, pp.`name`, pp.abbreviation ,count(*) questions
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
                                WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo' and cmp.active = 1 and cmp.management_id = :managementId
                                group by  pp.`name`, pp.id
                    )programa
                    order by $columnOrder $dirOrder";
        //Log::info($query);
        //Log::info($managementId);
        $results = DB::select( $query, array(
            'management_id' => $managementId,
            'managementId' => $managementId,
        ));
        //Log::info(json_encode($results));
        return $results;
    }

    public function getYearFilter($managementId) {

        $query = "SELECT
	DISTINCT 0 id, o.`year` item, o.`year` `value`
FROM
	wg_customer_management_detail_tracking o
WHERE management_id = :management_id
ORDER BY o.`year` DESC
";
        $results = DB::select( $query, array(
            'management_id' => $managementId
        ));

        return $results;
    }

    public function getProgramFilter($managementId) {

        $query = "select  p.`id`, p.`name`, p.abbreviation
from
	wg_customer_management_detail_tracking o
inner join wg_program_management p on o.program_id = p.id
where management_id = :management_id
group by program_id";
        $results = DB::select( $query, array(
            'management_id' => $managementId
        ));

        return $results;
    }

    public function getAllSummaryByProgram($sorting = array(), $managementId, $year) {

        $columnNames = ["name", "questions", "answers", "average"];
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
	wg_customer_management_detail_tracking o
inner join wg_program_management p on o.program_id = p.id
where management_id = :management_id and o.`year` = :year
group by program_id";


        $results = DB::select( $query, array(
            'management_id' => $managementId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByProgramExport($sorting = array(), $managementId, $year) {

        $columnNames = ["name", "questions", "answers", "average"];
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

        $query = "select  p.`name` Codigo, p.abbreviation Programa
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
	wg_customer_management_detail_tracking o
inner join wg_program_management p on o.program_id = p.id
where management_id = :management_id and o.`year` = :year
group by program_id";


        $results = DB::select( $query, array(
            'management_id' => $managementId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByIndicator($sorting = array(), $managementId, $year, $program = 0) {

        $columnNames = ["name", "questions", "answers", "average"];
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

        $conditionalProgram = "";

        if ($program != 0) {
            $conditionalProgram = " WHERE program_id = $program";
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
	select 1 `position`, management_id, 'Preguntas' indicator, SUM(questions) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
	$conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 2 `position`, management_id, 'Respuestas' indicator, SUM(answers) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
	$conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 3 `position`, management_id, 'Cumple' indicator, SUM(accomplish) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
	$conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 4 `position`, management_id, 'Cumple Parcial' indicator, SUM(partial_accomplish) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
	$conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 5 `position`, management_id, 'No Cumple' indicator, SUM(no_accomplish) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
	$conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 6 `position`, management_id, 'No Aplica' indicator, SUM(no_apply) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
	$conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 7 `position`, management_id, 'Sin Respuesta' indicator, SUM(no_answer) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
	$conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 8 `position`, management_id, 'Promedio Total %' indicator, CASE WHEN wg_program_management.isWeighted = 1 THEN SUM(total) ELSE (SUM(total) / SUM(questions)) END `value`, `month`, `year`
	from wg_customer_management_detail_tracking
    inner join wg_program_management on wg_program_management.id = wg_customer_management_detail_tracking.program_id
	$conditionalProgram
	group by management_id, `month`, `year`
) i
where management_id = :management_id and `year` = :year
group by indicator
order by position";


        $results = DB::select( $query, array(
            'management_id' => $managementId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByIndicatorExport($sorting = array(), $managementId, $year, $program = 0) {

        $columnNames = ["name", "questions", "answers", "average"];
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

        $conditionalProgram = "";

        if ($program != 0) {
            $conditionalProgram = " WHERE program_id = $program";
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
	select 1 `position`, management_id, 'Preguntas' indicator, SUM(questions) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
    $conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 2 `position`, management_id, 'Respuestas' indicator, SUM(answers) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
    $conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 3 `position`, management_id, 'Cumple' indicator, SUM(accomplish) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
    $conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 4 `position`, management_id, 'Cumple Parcial' indicator, SUM(partial_accomplish) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
    $conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 5 `position`, management_id, 'No Cumple' indicator, SUM(no_accomplish) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
    $conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 6 `position`, management_id, 'No Aplica' indicator, SUM(no_apply) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
    $conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 7 `position`, management_id, 'Sin Respuesta' indicator, SUM(no_answer) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
    $conditionalProgram
	group by management_id, `month`, `year`
	union ALL
	select 8 `position`, management_id, 'Promedio Total %' indicator, (SUM(total) / SUM(questions)) `value`, `month`, `year`
	from wg_customer_management_detail_tracking
    $conditionalProgram
	group by management_id, `month`, `year`
) i
where management_id = :management_id and `year` = :year
group by indicator
order by position";


        $results = DB::select( $query, array(
            'management_id' => $managementId,
            'year' => $year
        ));


        return $results;
    }

    public function getDashboardBarMonthly($managementId, $year)
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
	wg_customer_management_detail_tracking cdpt
	inner join wg_program_management pp on pp.id = cdpt.program_id
	where management_id = :management_id and year = :year
	group by management_id, month
) rm on spp.value = rm.month
where spp.`group` = 'month'";

        $results = DB::select( $sql, array(
            'management_id' => $managementId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardProgramLineMonthly($managementId, $year)
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
	wg_customer_management_detail_tracking o
inner join wg_program_management p on o.program_id = p.id
where management_id = :management_id and o.`year` = :year
group by program_id";

        $results = DB::select( $sql, array(
            'management_id' => $managementId,
            'year' => $year,
        ));

        return $results;
    }

    public function getDashboardTotalLineMonthly($managementId, $year)
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
				select 8 `position`, management_id, 'Promedio Total % (calificación / preguntas)' indicator, CASE WHEN wg_program_management.isWeighted THEN SUM(total) ELSE (SUM(total) / SUM(questions)) END `value`, `month`, `year`
				from wg_customer_management_detail_tracking
                inner join wg_program_management ON wg_program_management.id = wg_customer_management_detail_tracking.program_id
				group by management_id, `month`, `year`
			) i
where management_id = :management_id and i.`year` = :year";

        $results = DB::select( $sql, array(
            'management_id' => $managementId,
            'year' => $year,
        ));

        return $results;
    }

    public function getDashboardAvgLineMonthly($managementId, $year)
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
				select 8 `position`, management_id, 'Avance % (respuestas / preguntas)' indicator, ((SUM(answers) / SUM(questions)) * 100) `value`, `month`, `year`
				from wg_customer_management_detail_tracking
				group by management_id, `month`, `year`
			) i
where management_id = :management_id and i.`year` = :year";

        $results = DB::select( $sql, array(
            'management_id' => $managementId,
            'year' => $year,
        ));

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
                inner join wg_customer_management_program cmp ON pp.id = cmp.program_id
                inner join wg_program_management_category pc on pp.id = pc.program_id
                inner join wg_program_management_question pq on pc.id = pq.category_id
                inner join wg_customer_management_detail dp on pq.id 	= dp.question_id
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

        $results = DB::select( $sql, array(
            'management_id' => $managementId,
            'managementId' => $managementId
        ));

        return $results;
    }

    public function getCount($search = "") {

        $model = new CustomerManagement();
        $this->customerManagementRepository = new CustomerManagementRepository($model);

        $filters = array();
        if ( strlen(trim($search) ) > 0) {
            $filters[] = array('wg_customer_management.customer_id', $search);
            $filters[] = array('wg_customer_management.status', $search);
        }

        $this->customerManagementRepository->setColumns(['wg_customer_management.*']);

        return $this->customerManagementRepository->getFilteredsOptional($filters, true, "");
    }

    public function saveManagementProgram($model)
    {
        $query = "insert into wg_customer_management_program
                    select null id, :management_id management_id, id program_id, 0 active
                                , :createdBy created, null updatedBy, now() created_at, null updated_at
                    from wg_program_management pm
                    where pm.`status` = 'activo'
                          and pm.id not in (SELECT program_id from wg_customer_management_program wcmp where wcmp.management_id = :management_id_2)";

        $results = DB::statement( $query, array(
            'management_id' => $model->id,
            'management_id_2' => $model->id,
            'createdBy' => $model->createdBy
        ));

        //Log::info($results);

        return true;
    }

    public function saveManagementQuestion($model)
    {
        $query = "insert into wg_customer_management_detail
                  select null id, :management_id diagnostic, pq.id question_id, null rate_id, null observation, 'activo' status
                        , :createdBy created, null updatedBy
                        , now() created_at, null updated_at
                    from wg_program_management pp
                    INNER JOIN `wg_program_management_economic_sector` pec ON `pec`.`program_id` = `pp`.`id`
                    INNER JOIN `wg_economic_sector` ec ON `ec`.`id` = `pec`.`economic_sector_id`
                    INNER JOIN wg_customer_management_program cmp ON cmp.program_economic_sector_id = pec.id
                    INNER JOIN wg_customer_management mp ON mp.id = cmp.management_id and mp.id = :management_id2
                    INNER JOIN `wg_customer_config_workplace` ON `wg_customer_config_workplace`.`id` = `cmp`.customer_workplace_id
                                    AND `wg_customer_config_workplace`.`customer_id` = `mp`.`customer_id`
                    INNER JOIN wg_program_management_category pc on pp.id = pc.program_id
                    INNER JOIN wg_program_management_question pq on pc.id = pq.category_id
                    left join wg_customer_management_detail dp on dp.management_id = mp.id and dp.question_id = pq.id
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
