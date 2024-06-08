<?php

namespace Wgroup\CustomerEvaluationMinimumStandardItem;

use Carbon\Carbon;
use DB;
use Exception;
use Log;
use Str;
use Wgroup\MinimumStandard\MinimumStandard;
use Wgroup\Models\Customer;

class CustomerEvaluationMinimumStandardItemService
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
    public function getAllBy($search, $perPage = 10, $currentPage = 0, $sorting = array(), $typeFilter = "")
    {

        $model = new CustomerEvaluationMinimumStandardItem();

        // set current page
                \Illuminate\Pagination\Paginator::currentPageResolver(function () use ($currentPage) {
            return ($currentPage != null) ? $currentPage : 1;
        });

        $this->repository = new CustomerEvaluationMinimumStandardItemRepository($model);

        if ($perPage > 0) {
            $this->repository->paginate($perPage);
        }

        // sorting
        $columns = [
            'wg_customer_evaluation_minimum_standard_item.id',
            'wg_customer_evaluation_minimum_standard_item.customer_evaluation_minimum_standard_id',
            'wg_customer_evaluation_minimum_standard_item.minimum_standard_item_id',
            'wg_customer_evaluation_minimum_standard_item.rate_id',
            'wg_customer_evaluation_minimum_standard_item.status'
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
                    $this->repository->sortBy($colName, $dir);
                } else {
                    $this->repository->addSortField($colName, $dir);
                }
            } catch (Exception $exc) {

            }
            $i++;
        }

        if (empty($sorting)) {
            $this->repository->sortBy('wg_customer_evaluation_minimum_standard_item.id', 'desc');
        }

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_evaluation_minimum_standard_item.customer_evaluation_minimum_standard_id', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard_item.minimum_standard_item_id', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard_item.rate_id', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard_item.status', $search);
        }

        if ($typeFilter == "1") {
            $filters[] = array('wg_customer_evaluation_minimum_standard_item.status', '1');
        } else if ($typeFilter == "0") {
            $filters[] = array('wg_customer_evaluation_minimum_standard_item.status', '0');
        }

        $this->repository->setColumns(['wg_customer_evaluation_minimum_standard_item.*']);

        return $this->repository->getFilteredsOptional($filters, false, "");
    }

    public function getCount($search = "")
    {

        $model = new CustomerEvaluationMinimumStandardItem();
        $this->repository = new CustomerEvaluationMinimumStandardItemRepository($model);

        $filters = array();

        if (strlen(trim($search)) > 0) {
            $filters[] = array('wg_customer_evaluation_minimum_standard_item.customer_evaluation_minimum_standard_id', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard_item.minimum_standard_item_id', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard_item.rate_id', $search);
            $filters[] = array('wg_customer_evaluation_minimum_standard_item.status', $search);
        }

        $this->repository->setColumns(['wg_customer_evaluation_minimum_standard_item.*']);

        return $this->repository->getFilteredsOptional($filters, true, "");
    }

    public function getMinimumStandardParents($cycle)
    {
        return MinimumStandard::whereCycleId($cycle)->whereType('P')->get();
    }

    public function getPrograms($customerEvaluationMinimumStandardId)
    {
        $sql = "SELECT cycle.id,
       name,
       abbreviation,
       items,
       checked,
       ROUND(IFNULL((checked / items) * 100, 0), 2) advance ,
       -- ROUND(IFNULL((total / items), 0), 2) average,
       ROUND(IFNULL((total), 0), 2) average,
       ROUND(IFNULL(total, 0), 2) total
FROM
  ( SELECT cycle.id, cycle.`name`, cycle.abbreviation, count(*) items
		, SUM(CASE
						WHEN ISNULL(cemsi.id) THEN 0
							ELSE 1
						END) checked
		, SUM(cemsi.value) total
   FROM wg_config_minimum_standard_cycle cycle
   INNER JOIN wg_minimum_standard ms ON cycle.id = ms.cycle_id
   INNER JOIN wg_minimum_standard_item msi ON ms.id = msi.minimum_standard_id
   LEFT JOIN
     ( SELECT wg_customer_evaluation_minimum_standard_item.*, wg_config_minimum_standard_rate.text, wg_config_minimum_standard_rate.value
      FROM wg_customer_evaluation_minimum_standard_item
      INNER JOIN wg_config_minimum_standard_rate ON wg_customer_evaluation_minimum_standard_item.rate_id = wg_config_minimum_standard_rate.id
      WHERE customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id )cemsi ON msi.id = cemsi.minimum_standard_item_id
   WHERE cycle.`status` = 'activo'
     AND ms.isActive = 1
     AND msi.`isActive` = 1
   GROUP BY cycle.`name`, cycle.id ) cycle
ORDER BY cycle.id ASC";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId
        ));

        return $results;
    }

    public function getMinimumStandardItems($customerEvaluationMinimumStandardId, $cycle)
    {
        $sql = "SELECT cemsi.id
		, customer_evaluation_minimum_standard_id
		, minimum_standard_item_id
		, rate_id
		, msi.description
		, msi.numeral
		, msi.`value`
		, ms.id minimum_standard_parent_id
		, wr.color
		-- , cmdap.id actionPlanId
FROM wg_config_minimum_standard_cycle cycle
INNER JOIN wg_minimum_standard ms ON cycle.id = ms.cycle_id
INNER JOIN wg_minimum_standard_item msi ON ms.id = msi.minimum_standard_id
INNER JOIN wg_customer_evaluation_minimum_standard_item cemsi ON cemsi.minimum_standard_item_id = msi.id
INNER JOIN wg_customer_evaluation_minimum_standard cems on cems.id = cemsi.customer_evaluation_minimum_standard_id
LEFT JOIN wg_config_minimum_standard_rate wr ON wr.id = cemsi.rate_id
-- LEFT JOIN wg_customer_evaluation_minimum_standard_item_action_plan cmdap ON cmdap.diagnostic_detail_id = cemsi.id
WHERE cycle.`status` = 'activo' AND ms.`isActive` = 1 AND msi.`isActive` = 1
				AND cems.id = :customer_evaluation_minimum_standard_id AND cycle.id = :cycle_id
ORDER BY minimum_standard_item_id";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'cycle_id' => $cycle,
        ));

        return $results;
    }

    public function getMinimumStandardItemsByStatus($customerEvaluationMinimumStandardId, $cycle, $rate)
    {
        $sql = "SELECT * FROM
(SELECT cemsi.id
		, customer_evaluation_minimum_standard_id
		, minimum_standard_item_id
		, rate_id
		, msi.description
		, msi.numeral
		, ms.id minimum_standard_parent_id
		, wr.color
		, cycle.id cycle_id
		-- , cmdap.id actionPlanId
FROM wg_config_minimum_standard_cycle cycle
INNER JOIN wg_minimum_standard ms ON cycle.id = ms.cycle_id
INNER JOIN wg_minimum_standard_item msi ON ms.id = msi.minimum_standard_id
INNER JOIN wg_customer_evaluation_minimum_standard_item cemsi ON cemsi.minimum_standard_item_id = msi.id
INNER JOIN wg_customer_evaluation_minimum_standard cems on cems.id = cemsi.customer_evaluation_minimum_standard_id
LEFT JOIN wg_config_minimum_standard_rate wr ON wr.id = cemsi.rate_id
-- LEFT JOIN wg_customer_evaluation_minimum_standard_item_action_plan cmdap ON cmdap.diagnostic_detail_id = cemsi.id
WHERE cycle.`status` = 'activo' AND ms.`isActive` = 1 AND msi.`isActive` = 1
				AND cems.id = :customer_evaluation_minimum_standard_id) p";

        $where = '';

        $filter = array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId
        );

        if ($rate != 0) {
            $where .=  $where != "" ? " AND p.rate_id = :rate" : "  p.rate_id = :rate";
            $filter["rate"] = $rate;
        }

        if ($cycle != 0) {
            $where .=  $where != "" ? " AND p.cycle_id = :cycle_id" : "  p.cycle_id = :cycle_id";
            $filter["cycle_id"] = $cycle;
        }

        $orderBy = " ORDER BY minimum_standard_item_id";

        $where = $where != '' ? ' WHERE ' . $where : '';

        $sql .= $where . $orderBy;

        //var_dump($sql);

        $results = DB::select($sql, $filter);

        return $results;
    }

    public function getDashboardMinimumStandardGroupByParent($customerEvaluationMinimumStandardId, $cycle)
    {
        $sql = "SELECT
	p.minimum_standard_id,
	SUM(items) items,
	SUM(checked) checked,
	ROUND(IFNULL(SUM((checked / items) * 100), 0),2) advance,
	-- ROUND(IFNULL(SUM(total / items), 0),2) average,
	ROUND(IFNULL(SUM(total), 0),2) average,
	ROUND(IFNULL(SUM(total), 0), 2) total
FROM
	(
		SELECT
			ms.id,
			ms.`description`,
			COUNT(*) items,
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
			) total,
			cemsi.rate_id,
			cemsi.text,
			cemsi.color,
			cemsi.highlightColor,
			msi.minimum_standard_id,
			cycle.id cycle_id
		FROM
			wg_config_minimum_standard_cycle cycle
		INNER JOIN wg_minimum_standard ms ON cycle.id = ms.cycle_id
		INNER JOIN wg_minimum_standard_item msi ON ms.id = msi.minimum_standard_id
		LEFT JOIN (
			SELECT
				wg_customer_evaluation_minimum_standard_item.*
				, wg_config_minimum_standard_rate.text
				, wg_config_minimum_standard_rate.code
				, wg_config_minimum_standard_rate.`value`
				, wg_config_minimum_standard_rate.color
				, wg_config_minimum_standard_rate.highlightColor
			FROM
				wg_customer_evaluation_minimum_standard_item
			INNER JOIN wg_config_minimum_standard_rate ON wg_customer_evaluation_minimum_standard_item.rate_id = wg_config_minimum_standard_rate.id
			WHERE
				customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
		) cemsi ON msi.id = cemsi.minimum_standard_item_id
		WHERE
			cycle.`status` = 'activo'
		AND ms.`isActive` = 1
		AND msi.`isActive` = 1
		GROUP BY
			ms.`description`,
			ms.id
	) p
WHERE
	cycle_id = :cycle_id
GROUP BY
	p.minimum_standard_id
ORDER BY
	1;";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'cycle_id' => $cycle,
        ));

        return $results;
    }

    public function getDashboardMinimumStandardGroupByCycle($customerEvaluationMinimumStandardId)
    {
        $sql = "SELECT
	p.minimum_standard_id,
	SUM(items) items,
	SUM(checked) checked,
	ROUND(IFNULL(SUM((checked / items) * 100), 0),2) advance,
	-- ROUND(IFNULL(SUM(total / items), 0),2) average,
	ROUND(IFNULL(SUM(total), 0),2) average,
	ROUND(IFNULL(SUM(total), 0), 2) total
FROM
	(
		SELECT
			ms.id AS minimum_standard_id,
			ms.`description`,
			COUNT(*) items,
			SUM(
				CASE
				WHEN ISNULL(cemsi.id) THEN
					0
				ELSE
					1
				END
			) checked,
			SUM(cemsi.`value`) total
		FROM
			wg_config_minimum_standard_cycle cycle
		INNER JOIN wg_minimum_standard ms ON cycle.id = ms.cycle_id
		INNER JOIN wg_minimum_standard_item msi ON ms.id = msi.minimum_standard_id
		LEFT JOIN (
			SELECT
				wg_customer_evaluation_minimum_standard_item.*, wg_config_minimum_standard_rate.text,
				wg_config_minimum_standard_rate.`value`

			FROM
				wg_customer_evaluation_minimum_standard_item
			INNER JOIN wg_config_minimum_standard_rate ON wg_customer_evaluation_minimum_standard_item.rate_id = wg_config_minimum_standard_rate.id
			WHERE
				customer_evaluation_minimum_standard_id = 5
		) cemsi ON msi.id = cemsi.minimum_standard_item_id
	) p
ORDER BY
	1;";

        $results = DB::select($sql, array(
            'diagnostic_id' => $customerEvaluationMinimumStandardId
        ));

        return $results;
    }

    public function getDashboardMinimumStandard($customerEvaluationMinimumStandardId)
    {
        $sql = "SELECT
                  items
                  , checked
                  , ROUND(IFNULL(((checked / items) * 100), 0), 2) advance
                  -- , ROUND(IFNULL((total / items),0), 2) average
                  , ROUND(IFNULL((total),0), 2) average
                  , ROUND(IFNULL(total, 0), 2) total
FROM
	(
		SELECT
			cycle.id,
			cycle.`name`,
			cycle.color,
			cycle.highlightColor,
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
			wg_config_minimum_standard_cycle cycle
		INNER JOIN wg_minimum_standard ms ON cycle.id = ms.cycle_id
		INNER JOIN wg_minimum_standard_item msi ON ms.id = msi.minimum_standard_id
		LEFT JOIN (
			SELECT
				wg_customer_evaluation_minimum_standard_item.*,
				wg_config_minimum_standard_rate.text,
				wg_config_minimum_standard_rate.`value`,
				wg_config_minimum_standard_rate.`code`
			FROM
				wg_customer_evaluation_minimum_standard_item
			INNER JOIN wg_config_minimum_standard_rate ON wg_customer_evaluation_minimum_standard_item.rate_id = wg_config_minimum_standard_rate.id
			WHERE
				customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
		) cemsi ON msi.id = cemsi.minimum_standard_item_id
		WHERE
			cycle.`status` = 'activo'
		AND ms.isActive = 1
		AND msi.`isActive` = 1
		-- GROUP BY cemsi.customer_evaluation_minimum_standard_id
	) cycle";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId
        ));

        return count($results) > 0 ? $results[0] : null;
    }


    public function getMinimumStandardItemsImprovementPlan($customerEvaluationMinimumStandardId)
    {
        $sql = "SELECT cemsi.id
		, customer_evaluation_minimum_standard_id
		, minimum_standard_item_id
		, rate_id
		, msi.description
		, msi.numeral
		, msi.`value`
		, ms.id minimum_standard_parent_id
		, wr.color
		,ip.id improvement_plan_id
		,ip.description improvement_plan_description
		,DATE_FORMAT(ip.endDate, '%d/%m/%Y') improvement_plan_endDate
		,responsible.name as improvement_plan_responsible
		,responsible.type
		,responsible.email
		-- , cmdap.id actionPlanId
FROM wg_config_minimum_standard_cycle cycle
INNER JOIN wg_minimum_standard ms ON cycle.id = ms.cycle_id
INNER JOIN wg_minimum_standard_item msi ON ms.id = msi.minimum_standard_id
INNER JOIN wg_customer_evaluation_minimum_standard_item cemsi ON cemsi.minimum_standard_item_id = msi.id
INNER JOIN wg_customer_evaluation_minimum_standard cems on cems.id = cemsi.customer_evaluation_minimum_standard_id
INNER JOIN wg_customer_improvement_plan ip ON ip.entityId = cemsi.id AND ip.entityName = 'EM' AND ip.customer_id = cems.customer_id
LEFT JOIN ".Customer::getRelatedAgentAndUser()." ON ip.responsible = responsible.id AND ip.responsibleType = responsible.type AND ip.customer_id = responsible.customer_id
LEFT JOIN wg_config_minimum_standard_rate wr ON wr.id = cemsi.rate_id
-- LEFT JOIN wg_customer_evaluation_minimum_standard_item_action_plan cmdap ON cmdap.diagnostic_detail_id = cemsi.id
WHERE cycle.`status` = 'activo' AND ms.`isActive` = 1 AND msi.`isActive` = 1
				AND cems.id = :customer_evaluation_minimum_standard_id
ORDER BY minimum_standard_item_id";

        $results = DB::select($sql, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId
        ));

        return $results;
    }


    public function fillMissingMonthlyReport($customerEvaluationMinimumStandardId, $userId)
    {
        $track = DB::table('wg_customer_evaluation_minimum_standard_tracking')
            ->select(DB::raw('MAX(`month`) `month`, MAX(`year`) `year`'))
            ->where('customer_evaluation_minimum_standard_id', $customerEvaluationMinimumStandardId)
            ->first();

        if ($track != null) {
            $today = Carbon::now('America/Bogota');
            $lastTime = Carbon::createFromDate($track->year, $track->month, 1, 'America/Bogota');

            $diffInMonths = $today->diffInMonths($lastTime);

            for ($i = 1; $i < $diffInMonths; $i++) {
                $currentTime = $lastTime->addMonths(1);
                $this->duplicateMonthlyReport($customerEvaluationMinimumStandardId, $track->year, $track->month, $currentTime->year, $currentTime->month, $userId);
            }
        }
    }

    public function insertVerificationMode($customerEvaluationMinimumStandardId)
    {

        $query = "INSERT INTO `wg_customer_config_minimum_standard_item_detail` (
	`id`,
	`customer_id`,
	`minimum_standard_item_detail_id`,
	`createdBy`,
	`updatedBy`,
	`created_at`,
	`updated_at`
) SELECT DISTINCT
	NULL id,
	cems.customer_id,
	msid.id minimum_standard_item_detail_id,
	1 createdBy,
	NULL updatedBy,
	NOW() created_at,
	NULL updated_at
FROM
	wg_minimum_standard_item_detail msid
JOIN wg_customer_evaluation_minimum_standard cems
LEFT JOIN (
	SELECT
		cems.customer_id,
		minimum_standard_item_detail_id
	FROM
		wg_customer_evaluation_minimum_standard cems
	JOIN wg_customer_config_minimum_standard_item_detail ccmsid ON cems.customer_id = ccmsid.customer_id
	WHERE
		cems.id = :customer_evaluation_minimum_standard_id_1
) ccmsid ON ccmsid.minimum_standard_item_detail_id = msid.id AND ccmsid.customer_id = cems.customer_id
WHERE
	msid.type = 'verification-mode'
AND cems.id = :customer_evaluation_minimum_standard_id_2
AND ccmsid.minimum_standard_item_detail_id IS NULL";


        DB::statement($query, array(
            'customer_evaluation_minimum_standard_id_1' => $customerEvaluationMinimumStandardId,
            'customer_evaluation_minimum_standard_id_2' => $customerEvaluationMinimumStandardId,
        ));
    }

    public function duplicateMonthlyReport($customerEvaluationMinimumStandardId, $fromYear, $fromMonth, $toYear, $toMonth, $userId)
    {

        $query = "INSERT INTO wg_customer_evaluation_minimum_standard_tracking
SELECT
	NULL id,
	O.customer_evaluation_minimum_standard_id,
	O.minimum_standard_cycle,
	O.minimum_standard_parent_id,
	O.items,
	O.checked,
	O.avgProgress,
	O.avgTotal,
	O.total,
	O.accomplish,
	O.no_accomplish,
	O.no_apply_with_justification,
	O.no_apply_without_justification,
	O.no_checked,
	:toYear currentYear,
	:toMonth currentMonth,
	:user_id createdBy,
	NOW() created_at,
	NULL updatedBy,
	NULL updated_at
FROM
	wg_customer_evaluation_minimum_standard_tracking O
LEFT JOIN wg_customer_evaluation_minimum_standard_tracking D ON D.customer_evaluation_minimum_standard_id = O.customer_evaluation_minimum_standard_id
	AND D.`year` = :toYear_2
	AND D.`month` = :toMonth_2
WHERE
	O.customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
AND O.`month` = :fromMonth
AND O.`year` = :fromYear
AND D.id IS NULL";


        DB::statement($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
            'toYear' => $toYear,
            'toMonth' => $toMonth,
            'toYear_2' => $toYear,
            'toMonth_2' => $toMonth,
            'fromYear' => $fromYear,
            'fromMonth' => $fromMonth,
            'user_id' => $userId,
        ));
    }

    public function saveMonthlyReport($customerEvaluationMinimumStandardId, $year, $month, $userId)
    {

        $query = "INSERT INTO wg_customer_evaluation_minimum_standard_tracking
SELECT
	p.*
FROM
	(
		SELECT
			NULL id,
			$customerEvaluationMinimumStandardId customer_evaluation_minimum_standard_id,
			custom.id minimum_standard_cycle,
			custom.minimum_standard_id minimum_standard_parent_id,
			items,
			checked,
			round((checked / items) * 100, 2) advance,
			-- round((total / items), 2) average,
			round((total), 2) average,
			total,
			accomplish,
			no_accomplish,
			no_apply_without_justification,
			no_apply_with_justification,
			no_checked,
			$year currentYear,
			$month currentMonth,
			$userId createdBy,
			NULL updatedBy,
			NOW() created_at,
			NULL updated_at
		FROM
			(
                SELECT
                    item.id,
                    item.`name`,
                    item.minimum_standard_id,
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
                    ) total,
					SUM(
						CASE WHEN cemsi.`code` = 'cp' THEN 1 ELSE 0 END
					) accomplish,
					SUM(
						CASE WHEN cemsi.`code` = 'nc' THEN 1 ELSE 0 END
					) no_accomplish,
					SUM(
						CASE WHEN cemsi.`code` = 'nas' THEN 1 ELSE 0 END
					) no_apply_without_justification,
					SUM(
						CASE WHEN cemsi.`code` = 'nac' THEN 1 ELSE 0 END
					) no_apply_with_justification,
					SUM(
						CASE WHEN ISNULL(cemsi.id) THEN 1 ELSE 0 END
					) no_checked
                FROM
                    (
                        SELECT
                            cycle.id,
                            cycle.`name`,
                            cycle.abbreviation,
                            ms.id minimum_standard_id,
                            ms.description,
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
                                msp.description,
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
                        customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
                ) cemsi ON item.minimum_standard_item_id = cemsi.minimum_standard_item_id
                GROUP BY
                    item.`name`,
                    item.id,
                    item.minimum_standard_id,
                    item.description
			) custom
	) p
LEFT JOIN wg_customer_evaluation_minimum_standard_tracking cemsit ON p.customer_evaluation_minimum_standard_id = cemsit.customer_evaluation_minimum_standard_id
AND p.minimum_standard_cycle = cemsit.minimum_standard_cycle
AND p.minimum_standard_parent_id = cemsit.minimum_standard_parent_id
AND p.currentYear = cemsit.`year`
AND p.currentMonth = cemsit.`month`
WHERE
	cemsit.customer_evaluation_minimum_standard_id IS NULL";

        //var_dump($query);

        DB::statement($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId
        ));
    }

    public function updateMonthlyReport($customerEvaluationMinimumStandardId, $year, $month, $userId)
    {

        $query = "UPDATE wg_customer_evaluation_minimum_standard_tracking AS target
INNER JOIN
	(
		SELECT
			NULL id,
			$customerEvaluationMinimumStandardId customer_evaluation_minimum_standard_id,
			custom.id minimum_standard_cycle,
			custom.minimum_standard_id minimum_standard_parent_id,
			items,
			checked,
			round((checked / items) * 100, 2) advance,
			round((total), 2) average,
			total,
			accomplish,
			no_accomplish,
			no_apply_without_justification,
			no_apply_with_justification,
			no_checked,
			$year currentYear,
			$month currentMonth,
			$userId createdBy,
			NULL updatedBy,
			NOW() created_at,
			NULL updated_at
		FROM
			(
				SELECT
                    item.id,
                    item.`name`,
                    item.minimum_standard_id,
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
                    ) total,
					SUM(
						CASE WHEN cemsi.`code` = 'cp' THEN 1 ELSE 0 END
					) accomplish,
					SUM(
						CASE WHEN cemsi.`code` = 'nc' THEN 1 ELSE 0 END
					) no_accomplish,
					SUM(
						CASE WHEN cemsi.`code` = 'nas' THEN 1 ELSE 0 END
					) no_apply_without_justification,
					SUM(
						CASE WHEN cemsi.`code` = 'nac' THEN 1 ELSE 0 END
					) no_apply_with_justification,
					SUM(
						CASE WHEN ISNULL(cemsi.id) THEN 1 ELSE 0 END
					) no_checked
                FROM
                    (
                        SELECT
                            cycle.id,
                            cycle.`name`,
                            cycle.abbreviation,
                            ms.id minimum_standard_id,
                            ms.description,
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
                                msp.description,
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
                        customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
                ) cemsi ON item.minimum_standard_item_id = cemsi.minimum_standard_item_id
                GROUP BY
                    item.`name`,
                    item.id,
                    item.minimum_standard_id,
                    item.description
			) custom
) AS p
	ON  p.customer_evaluation_minimum_standard_id = target.customer_evaluation_minimum_standard_id
			AND p.minimum_standard_cycle = target.minimum_standard_cycle
			AND p.minimum_standard_parent_id = target.minimum_standard_parent_id
			AND p.currentYear = target.`year`
			AND p.currentMonth = target.`month`
SET target.items = p.items
		, target.checked = p.checked
		, target.avgProgress = p.advance
		, target.avgTotal = p.average
		, target.total = p.total
		, target.accomplish = p.accomplish
		, target.`no_accomplish` = p.no_accomplish
		, target.no_apply_with_justification = p.no_apply_with_justification
		, target.no_apply_without_justification = p.no_apply_without_justification
		, target.no_checked = p. no_checked
		, target.updated_at = p.updated_at
		, target.updatedBy = p.updatedBy";


       // var_dump($query);

        DB::statement($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId
        ));

    }

    public function getExport($customerEvaluationMinimumStandardId)
    {
        $query = "SELECT
	cycle.`name` AS `Ciclo`,
	cycle.abbreviation AS `Codigo`,
	msi.numeral AS `Numeral`,
	msi.description AS `Descripcion`,
	IFNULL(cemsi.text, 'N/A') AS `Calificacion`,
	msi.value AS `Valor`
FROM
	wg_config_minimum_standard_cycle cycle
INNER JOIN wg_minimum_standard ms ON cycle.id = ms.cycle_id
INNER JOIN wg_minimum_standard_item msi ON ms.id = msi.minimum_standard_id
LEFT JOIN (
	SELECT
		wg_customer_evaluation_minimum_standard_item.*, wg_config_minimum_standard_rate.text,
		wg_config_minimum_standard_rate.`value`,
		wg_config_minimum_standard_rate.`code`
	FROM
		wg_customer_evaluation_minimum_standard_item
	INNER JOIN wg_config_minimum_standard_rate ON wg_customer_evaluation_minimum_standard_item.rate_id = wg_config_minimum_standard_rate.id
	WHERE
		customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
) cemsi ON msi.id = cemsi.minimum_standard_item_id
WHERE
	cycle.`status` = 'activo'
AND ms.`isActive` = 1
AND msi.`isActive` = 1";


        $results = DB::select($query, array(
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId
        ));

        return $results;

    }

    public function getExportAll($customerEvaluationMinimumStandardId)
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
										select wg_customer_evaluation_minimum_standard_item.*, wg_rate.text, wg_rate.value from wg_customer_evaluation_minimum_standard_item
										inner join wg_rate ON wg_customer_evaluation_minimum_standard_item.rate_id = wg_rate.id
										where diagnostic_id = :diagnostic_id_1
						) cdp on ppq.id = cdp.question_id
				WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
				group by  pp.`name`, pp.id
	)pp
inner join wg_progam_prevention_category ppc ON pp.id = ppc.program_id
inner join wg_progam_prevention_question ppq on ppc.id = ppq.category_id
left join (
				select wg_customer_evaluation_minimum_standard_item.*, wg_rate.text, wg_rate.value from wg_customer_evaluation_minimum_standard_item
				inner join wg_rate ON wg_customer_evaluation_minimum_standard_item.rate_id = wg_rate.id
				where diagnostic_id = :diagnostic_id_2
) cdp on ppq.id = cdp.question_id
WHERE ppc.`status` = 'activo' AND ppq.`status` = 'activo'";


        $results = DB::select($query, array(
            'diagnostic_id_1' => $customerEvaluationMinimumStandardId,
            'diagnostic_id_2' => $customerEvaluationMinimumStandardId
        ));

        return $results;

    }
}
