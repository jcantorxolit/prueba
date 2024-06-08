<?php

namespace Wgroup\CustomerRoadSafety;

use DB;
use Exception;
use Log;
use Str;

class CustomerRoadSafetyService
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

        $model = new CustomerRoadSafety();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerRoadSafetyRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_road_safety.id',
            'wg_customer_road_safety.customer_id',
            'wg_customer_road_safety.startDate',
            'wg_customer_road_safety.endDate',
            'wg_customer_road_safety.status',
            'wg_customer_road_safety.type',
            'wg_customer_road_safety.description'
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
            $this->repository->sortBy('wg_customer_road_safety.id', 'desc');
        }

        $filters = array();

        $filters[] = array('wg_customer_road_safety.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_road_safety.startDate', $search);
            $filters[] = array('wg_customer_road_safety.endDate', $search);
            $filters[] = array('wg_customer_road_safety.status', $search);
            $filters[] = array('wg_customer_road_safety.type', $search);
            $filters[] = array('wg_customer_road_safety.description', $search);
        }

        $this->repository->setColumns(['wg_customer_road_safety.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "", $customerId = 0)
    {

        $model = new CustomerRoadSafety();
        $this->repository = new CustomerRoadSafetyRepository($model);

        $filters = array();

        $filters[] = array('wg_customer_road_safety.customer_id', $customerId);

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_road_safety.startDate', $search);
            $filters[] = array('wg_customer_road_safety.endDate', $search);
            $filters[] = array('wg_customer_road_safety.status', $search);
            $filters[] = array('wg_customer_road_safety.type', $search);
            $filters[] = array('wg_customer_road_safety.description', $search);
        }

        $this->repository->setColumns(['wg_customer_road_safety.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }

    public function insertRoadSafetyItems($model)
    {
        $query = "INSERT INTO wg_customer_road_safety_item
SELECT
	NULL id,
	:customer_road_safety_id_1 road_safety,
	msi.id road_safety_item_id,
	NULL rate_id,
	'activo' `status`,
	NULL apply,
	NULL evidence,
	NULL requirement,
	:created_by created,
	NULL updatedBy,
	now() created_at,
	NULL updated_at
FROM
	wg_config_road_safety_cycle cycle
INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
INNER JOIN wg_customer_road_safety cems ON cems.id = :customer_road_safety_id_2
LEFT JOIN wg_customer_road_safety_item cemsi ON cemsi.customer_road_safety_id = cems.id
AND cemsi.road_safety_item_id = msi.id
WHERE
	cycle.`status` = 'activo'
AND ms.`isActive` = 1
AND msi.`isActive` = 1
AND cemsi.road_safety_item_id IS NULL;";


        DB::statement($query, array(
            'customer_road_safety_id_1' => $model->id,
            'customer_road_safety_id_2' => $model->id,
            'created_by' => $model->createdBy
        ));

        return true;
    }

    public function getAllSummary($sorting = array(), $customerRoadSafetyId)
    {

        $columnNames = ["name", "description", "items", "checked", "advance", "total"];
        $columnOrder = "id";
        $dirOrder = "asc";

        if (!empty($sorting)) {
            $columnOrder = $columnNames[$sorting[0]["column"]];
            if ($columnOrder == "name") {
                $columnOrder = "id";
                $dirOrder = "asc";
            } else
                $dirOrder = $sorting[0]["dir"];
        }

        $query = "SELECT
	cycle.id,
	name,
	abbreviation,
	description,
	items,
	checked,
	ROUND(
		IFNULL((checked / items) * 100, 0),
		2
	) advance,
	ROUND(IFNULL((total / items), 0), 2) average,
	ROUND(IFNULL(total, 0), 2) total
FROM
	(
		SELECT
			item.id,
			item.`name`,
			item.road_safety_id,
			item.description,
			item.abbreviation,
			count(*) items,
			SUM(
				CASE
				WHEN ISNULL(cemsi.id) THEN
					0
				ELSE
					1
				END
			) checked,
            SUM(
				CASE
				WHEN cemsi.`code` = 'cp' OR  cemsi.`code` = 'nac' THEN
					item.`value`
				ELSE
					0
				END
			) total
		FROM
			(
				SELECT
					cycle.id,
					cycle.`name`,
					cycle.abbreviation,
					ms.id road_safety_id,
					ms.description,
					msi.id road_safety_item_id,
					msi.`value`
				FROM
					wg_config_road_safety_cycle cycle
				INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
				INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
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
						msp.id road_safety_id,
						msp.description,
						msi.id road_safety_item_id,
						msi.`value`
					FROM
						wg_config_road_safety_cycle cycle
					INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
					INNER JOIN wg_road_safety msp ON ms.parent_id = msp.id
					INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
					WHERE
						cycle.`status` = 'activo'
					AND ms.isActive = 1
					AND msi.`isActive` = 1
			) item
		LEFT JOIN (
			SELECT
				wg_customer_road_safety_item.*
				, wg_config_road_safety_rate.text
				, wg_config_road_safety_rate.`value`
				, wg_config_road_safety_rate.`code`

			FROM
				wg_customer_road_safety_item
			INNER JOIN wg_config_road_safety_rate ON wg_customer_road_safety_item.rate_id = wg_config_road_safety_rate.id
			WHERE
				customer_road_safety_id = :customer_road_safety_id
		) cemsi ON item.road_safety_item_id = cemsi.road_safety_item_id
		GROUP BY
			item.`name`,
			item.id,
			item.road_safety_id,
			item.description
	) cycle
ORDER BY
	$columnOrder $dirOrder";

        $results = DB::select($query, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
        ));

        return $results;
    }

	public function getAllSummaryWeighted($sorting = array(), $customerRoadSafetyId)
	{

		$columnNames = ["name", "total", "weightedValue", "result"];
		$columnOrder = "id";
		$dirOrder = "asc";

		if (!empty($sorting)) {
			$columnOrder = $columnNames[$sorting[0]["column"]];
			if ($columnOrder == "name") {
				$columnOrder = "id";
				$dirOrder = "asc";
			} else
				$dirOrder = $sorting[0]["dir"];
		}

		$query = "SELECT cycle.id,
       name,
       abbreviation,
       items,
       `checked`,
       ROUND( IFNULL((`checked` / items) * 100, 0), 2 ) advance,
       ROUND(IFNULL((total / items), 0), 2) average,
       ROUND(IFNULL(total, 0), 2) total,
       weightedValue,
       ROUND(ROUND(IFNULL(total, 0), 2) * (weightedValue / 100), 2) result
FROM
  ( SELECT item.id,
           item.`name`,
           item.`weightedValue`,
           item.road_safety_id,
           item.description,
           item.abbreviation,
           count(*) items,
           SUM( CASE
                    WHEN ISNULL(cemsi.id) THEN 0
                    ELSE 1
                END ) `checked`,
					 SUM( CASE
									 WHEN cemsi.`code` = 'cp'
												OR cemsi.`code` = 'nac' THEN item.`value`
									 ELSE 0
							 END ) `total`
   FROM
     ( SELECT cycle.id,
              cycle.`name`,
              cycle.`weightedValue`,
              cycle.abbreviation,
              ms.id road_safety_id,
              ms.description,
              msi.id road_safety_item_id,
              msi.`value`
      FROM wg_config_road_safety_cycle `cycle`
      INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
      INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
      WHERE cycle.`status` = 'activo'
        AND ms.isActive = 1
        AND msi.`isActive` = 1
        AND ms.type = 'P'
      UNION ALL SELECT cycle.id,
                       cycle.`name`,
												cycle.`weightedValue`,
                       cycle.abbreviation,
                       msp.id road_safety_id,
                       msp.description,
                       msi.id road_safety_item_id,
                       msi.`value`
      FROM wg_config_road_safety_cycle `cycle`
      INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
      INNER JOIN wg_road_safety msp ON ms.parent_id = msp.id
      INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
      WHERE cycle.`status` = 'activo'
        AND ms.isActive = 1
        AND msi.`isActive` = 1 ) item
   LEFT JOIN
     ( SELECT wg_customer_road_safety_item.* ,
              wg_config_road_safety_rate.text ,
              wg_config_road_safety_rate.`value` ,
              wg_config_road_safety_rate.`code`
      FROM wg_customer_road_safety_item
      INNER JOIN wg_config_road_safety_rate ON wg_customer_road_safety_item.rate_id = wg_config_road_safety_rate.id
      WHERE customer_road_safety_id = :customer_road_safety_id ) cemsi ON item.road_safety_item_id = cemsi.road_safety_item_id
   GROUP BY item.`name`,
            item.id ) `cycle`
ORDER BY
	$columnOrder $dirOrder";

		$results = DB::select($query, array(
			'customer_road_safety_id' => $customerRoadSafetyId,
		));

		return $results;
	}

    public function getAllSummaryExport($sorting = array(), $customerRoadSafetyId)
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

        $query = "SELECT
	name Ciclo,
	description `Seguridad Vial`,
	items `Items`,
	checked `Evaluados`,
	ROUND(
		IFNULL((checked / items) * 100, 0),
		2
	) `Valor Estandard` ,
	ROUND(IFNULL((total / items), 0), 2) `Promedio`,
	ROUND(IFNULL(total, 0), 2) `Valoración`
FROM
	(
		SELECT
			item.id,
			item.`name`,
			item.road_safety_id,
			item.description,
			item.abbreviation,
			count(*) items,
			SUM(
				CASE
				WHEN ISNULL(cemsi.id) THEN
					0
				ELSE
					1
				END
			) checked,
            SUM(
				CASE
				WHEN cemsi.`code` = 'cp' OR  cemsi.`code` = 'nac' THEN
					item.`value`
				ELSE
					0
				END
			) total
		FROM
			(
				SELECT
					cycle.id,
					cycle.`name`,
					cycle.abbreviation,
					ms.id road_safety_id,
					ms.description,
					msi.id road_safety_item_id,
					msi.`value`
				FROM
					wg_config_road_safety_cycle cycle
				INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
				INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
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
						msp.id road_safety_id,
						msp.description,
						msi.id road_safety_item_id,
						msi.`value`
					FROM
						wg_config_road_safety_cycle cycle
					INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
					INNER JOIN wg_road_safety msp ON ms.parent_id = msp.id
					INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
					WHERE
						cycle.`status` = 'activo'
					AND ms.isActive = 1
					AND msi.`isActive` = 1
			) item
		LEFT JOIN (
			SELECT
				wg_customer_road_safety_item.*
				, wg_config_road_safety_rate.text
				, wg_config_road_safety_rate.`value`
				, wg_config_road_safety_rate.`code`

			FROM
				wg_customer_road_safety_item
			INNER JOIN wg_config_road_safety_rate ON wg_customer_road_safety_item.rate_id = wg_config_road_safety_rate.id
			WHERE
				customer_road_safety_id = :customer_road_safety_id
		) cemsi ON item.road_safety_item_id = cemsi.road_safety_item_id
		GROUP BY
			item.`name`,
			item.id,
			item.road_safety_id,
			item.description
	) cycle
ORDER BY  $columnOrder $dirOrder";

        $results = DB::select($query, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
        ));

        return $results;
    }

    public function getYearFilter($customerRoadSafetyId)
    {

        $query = "SELECT
	DISTINCT 0 id, o.`year` item, o.`year` `value`
FROM
	wg_customer_road_safety_tracking o
WHERE customer_road_safety_id = :customer_road_safety_id
ORDER BY o.`year` DESC
";
        $results = DB::select($query, array(
            'customer_road_safety_id' => $customerRoadSafetyId
        ));

        return $results;
    }

    public function getAllSummaryByYear($customerRoadSafetyId, $year)
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
	wg_customer_road_safety_tracking o
inner join wg_progam_prevention p on o.road_safety_cycle = p.id
where customer_road_safety_id = :customer_road_safety_id and o.`year` = :year
group by road_safety_cycle";


        $results = DB::select($query, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByProgram($sorting = array(), $customerRoadSafetyId, $year)
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

        $query = "select  p.`name`, p.abbreviation
	, SUM(case when `month` = 1 then ROUND(IFNULL(o.avgTotal,0),2) end) ENE
	, SUM(case when `month` = 2 then ROUND(IFNULL(o.avgTotal,0),2) end) FEB
	, SUM(case when `month` = 3 then ROUND(IFNULL(o.avgTotal,0),2) end) MAR
	, SUM(case when `month` = 4 then ROUND(IFNULL(o.avgTotal,0),2) end) ABR
	, SUM(case when `month` = 5 then ROUND(IFNULL(o.avgTotal,0),2) end) MAY
	, SUM(case when `month` = 6 then ROUND(IFNULL(o.avgTotal,0),2) end) JUN
	, SUM(case when `month` = 7 then ROUND(IFNULL(o.avgTotal,0),2) end) JUL
	, SUM(case when `month` = 8 then ROUND(IFNULL(o.avgTotal,0),2) end) AGO
	, SUM(case when `month` = 9 then ROUND(IFNULL(o.avgTotal,0),2) end) SEP
	, SUM(case when `month` = 10 then ROUND(IFNULL(o.avgTotal,0),2) end) OCT
	, SUM(case when `month` = 11 then ROUND(IFNULL(o.avgTotal,0),2) end) NOV
	, SUM(case when `month` = 12 then ROUND(IFNULL(o.avgTotal,0),2) end) DIC
from
	wg_customer_road_safety_tracking o
inner join wg_config_road_safety_cycle p on o.road_safety_cycle = p.id
where customer_road_safety_id = :customer_road_safety_id and o.`year` = :year
group by p.id";


        $results = DB::select($query, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByProgramExport($sorting = array(), $customerRoadSafetyId, $year)
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
	wg_customer_road_safety_tracking o
inner join wg_config_road_safety_cycle p on o.road_safety_cycle = p.id
where customer_road_safety_id = :customer_road_safety_id and o.`year` = :year
group by p.id";


        $results = DB::select($query, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByIndicator($sorting = array(), $customerRoadSafetyId, $year)
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
	select 1 `position`, customer_road_safety_id, 'Preguntas' indicator, SUM(items) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 2 `position`, customer_road_safety_id, 'Respuestas' indicator, SUM(checked) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 3 `position`, customer_road_safety_id, 'Cumple' indicator, SUM(accomplish) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 4 `position`, customer_road_safety_id, 'No Aplica sin Justificacion' indicator, SUM(no_apply_without_justification) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 5 `position`, customer_road_safety_id, 'No Cumple' indicator, SUM(no_accomplish) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 6 `position`, customer_road_safety_id, 'No Aplica con Justificacion' indicator, SUM(no_apply_with_justification) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 7 `position`, customer_road_safety_id, 'Sin Respuesta' indicator, SUM(no_checked) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 8 `position`, customer_road_safety_id, 'Promedio Total %' indicator, (SUM(total) / SUM(items)) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
) i
where customer_road_safety_id = :customer_road_safety_id and `year` = :year
group by indicator
order by position";


        $results = DB::select($query, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
            'year' => $year
        ));


        return $results;
    }

    public function getAllSummaryByIndicatorExport($sorting = array(), $customerRoadSafetyId, $year)
    {

        $columnNames = ["name", "items", "answers", "average"];
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
	select 1 `position`, customer_road_safety_id, 'Preguntas' indicator, SUM(items) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 2 `position`, customer_road_safety_id, 'Respuestas' indicator, SUM(checked) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 3 `position`, customer_road_safety_id, 'Cumple' indicator, SUM(accomplish) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 4 `position`, customer_road_safety_id, 'No Aplica sin Justificacion' indicator, SUM(no_apply_without_justification) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 5 `position`, customer_road_safety_id, 'No Cumple' indicator, SUM(no_accomplish) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 6 `position`, customer_road_safety_id, 'No Aplica con Justificacion' indicator, SUM(no_apply_with_justification) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 7 `position`, customer_road_safety_id, 'Sin Respuesta' indicator, SUM(no_checked) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
	union ALL
	select 8 `position`, customer_road_safety_id, 'Promedio Total %' indicator, (SUM(total) / SUM(items)) `value`, `month`, `year`
	from wg_customer_road_safety_tracking
	group by customer_road_safety_id, `month`, `year`
) i
where customer_road_safety_id = :customer_road_safety_id and `year` = :year
group by indicator
order by position";


        $results = DB::select($query, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
            'year' => $year
        ));


        return $results;
    }

    public function getDashboardCycle($customerRoadSafetyId)
    {
        $sql = "SELECT
	cycle.id,
	name,
	abbreviation,
	items,
	checked,
	ROUND(
		IFNULL((checked / items) * 100, 0),
		2
	) advance,
	ROUND(IFNULL((total / items), 0), 2) average,
	ROUND(IFNULL(total, 0), 2) total
FROM
	(
		SELECT
			item.id,
			item.`name`,
			item.road_safety_id,
			item.description,
			item.abbreviation,
			count(*) items,
			SUM(
				CASE
				WHEN ISNULL(cemsi.id) THEN
					0
				ELSE
					1
				END
			) checked,
            SUM(
				CASE
				WHEN cemsi.`code` = 'cp' OR  cemsi.`code` = 'nac' THEN
					item.`value`
				ELSE
					0
				END
			) total
		FROM
			(
				SELECT
					cycle.id,
					cycle.`name`,
					cycle.abbreviation,
					ms.id road_safety_id,
					ms.description,
					msi.id road_safety_item_id,
					msi.`value`
				FROM
					wg_config_road_safety_cycle cycle
				INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
				INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
				WHERE
					cycle.`status` = 'activo'
				AND ms.isActive = 1
				AND msi.`isActive` = 1

			) item
		LEFT JOIN (
			SELECT
				wg_customer_road_safety_item.*
				, wg_config_road_safety_rate.text
				, wg_config_road_safety_rate.`value`
				, wg_config_road_safety_rate.`code`

			FROM
				wg_customer_road_safety_item
			INNER JOIN wg_config_road_safety_rate ON wg_customer_road_safety_item.rate_id = wg_config_road_safety_rate.id
			WHERE
				customer_road_safety_id = :customer_road_safety_id
		) cemsi ON item.road_safety_item_id = cemsi.road_safety_item_id
		GROUP BY
			item.`name`,
			item.id
	) cycle
	ORDER BY 1";

        $results = DB::select($sql, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
        ));

        return $results;
    }

    public function getDashboardPie($customerRoadSafetyId)
    {
        $sql = "SELECT
	cycle.`name` label,
	ROUND(IFNULL((total), 0), 2) `valueTotal`,
	cycle.color,
	cycle.highlightColor,
	ROUND(ROUND(IFNULL(total, 0), 2) * (weightedValue / 100), 2) `value`
FROM
	(
		SELECT
			cycle.id,
			cycle.`name`,
			cycle.color,
			cycle.highlightColor,
			cycle.weightedValue,
			count(*) items,
			SUM(
				CASE
				WHEN ISNULL(cemsi.id) THEN
					0
				ELSE
					1
				END
			) checked,
			SUM(
				CASE
				WHEN cemsi.`code` = 'cp'
				OR cemsi.`code` = 'nac' THEN
					msi.`value`
				ELSE
					0
				END
			) total
		FROM
			wg_config_road_safety_cycle cycle
		INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
		INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
		LEFT JOIN (
			SELECT
				wg_customer_road_safety_item.*,
				wg_config_road_safety_rate.text,
				wg_config_road_safety_rate.`value`,
				wg_config_road_safety_rate.`code`
			FROM
				wg_customer_road_safety_item
			INNER JOIN wg_config_road_safety_rate ON wg_customer_road_safety_item.rate_id = wg_config_road_safety_rate.id
			WHERE
				customer_road_safety_id = :customer_road_safety_id
		) cemsi ON msi.id = cemsi.road_safety_item_id
		WHERE
			cycle.`status` = 'activo'
		AND ms.isActive = 1
		AND msi.`isActive` = 1
		GROUP BY
			cycle.`name`,
			cycle.id
	) cycle
ORDER BY 2";

        $results = DB::select($sql, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
        ));

        return $results;
    }

    public function getDashboardBar($customerRoadSafetyId)
    {
        $sql = "SELECT
	cycle.id,
	name,
	abbreviation,
	items,
	noChecked,
	accomplish,
	noAccomplish,
	noApplyWith,
	noApplyWithout
FROM
	(
		SELECT
			item.id,
			item.`name`,
			item.road_safety_id,
			item.abbreviation,
			count(*) items
		, SUM(CASE WHEN ISNULL(cemsi.`code`) THEN 1 ELSE 0 END) noChecked
		, SUM(CASE WHEN cemsi.`code` = 'cp' THEN 1 ELSE 0 END) accomplish
		, SUM(CASE WHEN cemsi.`code` = 'nc' THEN 1 ELSE 0 END) noAccomplish
		, SUM(CASE WHEN cemsi.`code` = 'nac' THEN 1 ELSE 0 END) noApplyWith
		, SUM(CASE WHEN cemsi.`code` = 'nas' THEN 1 ELSE 0 END) noApplyWithout
		FROM
			(
				SELECT
					cycle.id,
					cycle.`name`,
					cycle.abbreviation,
					ms.id road_safety_id,
					msi.id road_safety_item_id,
					msi.`value`
				FROM
					wg_config_road_safety_cycle cycle
				INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
				INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
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
						msp.id road_safety_id,
						msi.id road_safety_item_id,
						msi.`value`
					FROM
						wg_config_road_safety_cycle cycle
					INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
					INNER JOIN wg_road_safety msp ON ms.parent_id = msp.id
					INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
					WHERE
						cycle.`status` = 'activo'
					AND ms.isActive = 1
					AND msi.`isActive` = 1
			) item
		LEFT JOIN (
			SELECT
				wg_customer_road_safety_item.*
				, wg_config_road_safety_rate.text
				, wg_config_road_safety_rate.`value`
				, wg_config_road_safety_rate.`code`

			FROM
				wg_customer_road_safety_item
			INNER JOIN wg_config_road_safety_rate ON wg_customer_road_safety_item.rate_id = wg_config_road_safety_rate.id
			WHERE
				customer_road_safety_id = :customer_road_safety_id
		) cemsi ON item.road_safety_item_id = cemsi.road_safety_item_id
		GROUP BY
			item.`name`,
			item.id
	) cycle
ORDER BY
	cycle.id";

        $results = DB::select($sql, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
        ));

        return $results;
    }

    public function getDashboardBarMonthly($customerRoadSafetyId, $year)
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
			, IFNULL(sum(no_apply_without_justification),0) partial_accomplish
			, IFNULL(sum(no_accomplish),0)  no_accomplish
			, IFNULL(sum(no_apply_without_justification),0) no_apply
			, IFNULL(sum(no_checked),0) no_answer
			, month
			, year
	from
	wg_customer_road_safety_tracking cdpt
	inner join wg_config_road_safety_cycle pp on pp.id = cdpt.road_safety_cycle
	where customer_road_safety_id = :customer_road_safety_id and year = :year
	group by customer_road_safety_id, month
) rm on spp.value = rm.month
where spp.`group` = 'month'";

        $results = DB::select($sql, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
            'year' => $year
        ));

        return $results;
    }

    public function getDashboardProgramLineMonthly($customerRoadSafetyId, $year)
    {
        $sql = "select  p.`id`, p.`name`, p.abbreviation, p.color
	, SUM(case when `month` = 1 then ROUND(IFNULL(o.avgTotal,0),2) end) ENE
	, SUM(case when `month` = 2 then ROUND(IFNULL(o.avgTotal,0),2) end) FEB
	, SUM(case when `month` = 3 then ROUND(IFNULL(o.avgTotal,0),2) end) MAR
	, SUM(case when `month` = 4 then ROUND(IFNULL(o.avgTotal,0),2) end) ABR
	, SUM(case when `month` = 5 then ROUND(IFNULL(o.avgTotal,0),2) end) MAY
	, SUM(case when `month` = 6 then ROUND(IFNULL(o.avgTotal,0),2) end) JUN
	, SUM(case when `month` = 7 then ROUND(IFNULL(o.avgTotal,0),2) end) JUL
	, SUM(case when `month` = 8 then ROUND(IFNULL(o.avgTotal,0),2) end) AGO
	, SUM(case when `month` = 9 then ROUND(IFNULL(o.avgTotal,0),2) end) SEP
	, SUM(case when `month` = 10 then ROUND(IFNULL(o.avgTotal,0),2) end) OCT
	, SUM(case when `month` = 11 then ROUND(IFNULL(o.avgTotal,0),2) end) NOV
	, SUM(case when `month` = 12 then ROUND(IFNULL(o.avgTotal,0),2) end) DIC
from
	wg_customer_road_safety_tracking o
inner join wg_config_road_safety_cycle p on o.road_safety_cycle = p.id
where customer_road_safety_id = :customer_road_safety_id and o.`year` = :year
group by road_safety_cycle";

        $results = DB::select($sql, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
            'year' => $year,
        ));

        return $results;
    }

    public function getDashboardTotalLineMonthly($customerRoadSafetyId, $year)
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
				select 8 `position`, customer_road_safety_id, 'Promedio Total % (calificación)' indicator, (SUM(total)) `value`, `month`, `year`
				from wg_customer_road_safety_tracking
				group by customer_road_safety_id, `month`, `year`
			) i
where customer_road_safety_id = :customer_road_safety_id and i.`year` = :year";

        $results = DB::select($sql, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
            'year' => $year,
        ));

        return $results;
    }

    public function getDashboardAvgLineMonthly($customerRoadSafetyId, $year)
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
				select 8 `position`, customer_road_safety_id, 'Avance % (respuestas / preguntas)' indicator, ((SUM(checked) / SUM(items)) * 100) `value`, `month`, `year`
				from wg_customer_road_safety_tracking
				group by customer_road_safety_id, `month`, `year`
			) i
where customer_road_safety_id = :customer_road_safety_id and i.`year` = :year";

        $results = DB::select($sql, array(
            'customer_road_safety_id' => $customerRoadSafetyId,
            'year' => $year,
        ));

        return $results;
    }

    public function getTotalAvg($customerRoadSafetyId)
    {
        $sql = "SELECT
       SUM(ROUND(ROUND(IFNULL(total, 0), 2) * (weightedValue / 100), 2)) total
FROM
  ( SELECT item.id,
           item.`name`,
           item.`weightedValue`,
           item.road_safety_id,
           item.description,
           item.abbreviation,
           count(*) items,
           SUM( CASE
                    WHEN ISNULL(cemsi.id) THEN 0
                    ELSE 1
                END ) `checked`,
					 SUM( CASE
									 WHEN cemsi.`code` = 'cp'
												OR cemsi.`code` = 'nac' THEN item.`value`
									 ELSE 0
							 END ) `total`
   FROM
     ( SELECT cycle.id,
              cycle.`name`,
              cycle.`weightedValue`,
              cycle.abbreviation,
              ms.id road_safety_id,
              ms.description,
              msi.id road_safety_item_id,
              msi.`value`
      FROM wg_config_road_safety_cycle `cycle`
      INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
      INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
      WHERE cycle.`status` = 'activo'
        AND ms.isActive = 1
        AND msi.`isActive` = 1
        AND ms.type = 'P'
      UNION ALL SELECT cycle.id,
                       cycle.`name`,
												cycle.`weightedValue`,
                       cycle.abbreviation,
                       msp.id road_safety_id,
                       msp.description,
                       msi.id road_safety_item_id,
                       msi.`value`
      FROM wg_config_road_safety_cycle `cycle`
      INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
      INNER JOIN wg_road_safety msp ON ms.parent_id = msp.id
      INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
      WHERE cycle.`status` = 'activo'
        AND ms.isActive = 1
        AND msi.`isActive` = 1 ) item
   LEFT JOIN
     ( SELECT wg_customer_road_safety_item.* ,
              wg_config_road_safety_rate.text ,
              wg_config_road_safety_rate.`value` ,
              wg_config_road_safety_rate.`code`
      FROM wg_customer_road_safety_item
      INNER JOIN wg_config_road_safety_rate ON wg_customer_road_safety_item.rate_id = wg_config_road_safety_rate.id
      WHERE customer_road_safety_id = :customer_road_safety_id ) cemsi ON item.road_safety_item_id = cemsi.road_safety_item_id
   GROUP BY item.`name`,
            item.id ) `cycle`";

        $results = DB::select($sql, array(
            'customer_road_safety_id' => $customerRoadSafetyId
        ));

        return count($results) > 0 ? $results[0]->total : 0;
    }
}
