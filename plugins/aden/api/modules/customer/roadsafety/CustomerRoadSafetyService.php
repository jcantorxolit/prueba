<?php

namespace AdeN\Api\Modules\Customer\RoadSafety;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;


class CustomerRoadSafetyService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getChartBar($criteria)
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

        $data = DB::select($sql, [
            'customer_road_safety_id' => $criteria->customerRoadSafetyId
        ]);

        $config = array(
            "labelColumn" => 'abbreviation',
            "valueColumns" => [
                ['label' => 'Sin Evaluar', 'field' => 'noChecked'],
                ['label' => 'Cumple', 'field' => 'accomplish'],
                ['label' => 'No Cumple', 'field' => 'noAccomplish'],
                ['label' => 'N/A Con Justificación', 'field' => 'noApplyWith'],
                ['label' => 'N/A Sin Justificación', 'field' => 'noApplyWithout'],
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartPie($criteria)
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

        $data = DB::select($sql, [
            'customer_road_safety_id' => $criteria->customerRoadSafetyId,
        ]);

        return $this->chart->getChartPie($data);
    }

    public function getStats($criteria)
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

        $data = DB::select($sql, [
            'customer_road_safety_id' => $criteria->customerRoadSafetyId
        ]);

        return count($data) > 0 ? $data[0] : null;
    }

    public function getCycles($customerRoadSafetyId)
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
            ) cemsi ON item.road_safety_id = cemsi.road_safety_item_id
            GROUP BY
                item.`name`,
                item.id
        ) cycle
        ORDER BY 1";

        return DB::select($sql, [
            'customer_road_safety_id' => $customerRoadSafetyId
        ]);
    }


    public static function getYearsByCustomerId(int $customerId)
    {
        return DB::table('wg_customer_road_safety_tracking as o')
            ->join('wg_customer_road_safety as rs', 'rs.id', '=', 'o.customer_road_safety_id')
            ->join(DB::raw(SystemParameter::getRelationTable('month')), function ($join) {
                $join->whereRaw('o.month = month.value');
            })
            ->where('rs.customer_id', $customerId)
            ->orderBy('o.year', 'DESC')
            ->orderBy('o.month', 'ASC')
            ->select('year', 'o.month', 'month.item as monthName')
            ->distinct()
            ->get();
    }


    public function getRoadSafetyChartBar($criteria)
    {
        $subquery = DB::table('wg_customer_road_safety_tracking as tr')
            ->join('wg_customer_road_safety as rs', 'rs.id', '=', 'tr.customer_road_safety_id')
            ->join('wg_config_road_safety_cycle as p', 'p.id', '=', 'tr.road_safety_cycle')
            ->where('rs.customer_id', $criteria->customerId)
            ->whereIn('tr.year',  [$criteria->period])
            ->whereIn('tr.month', [$criteria->month])
            ->groupBy(DB::raw('concat(tr.year, tr.month)'), 'p.id')
            ->select(
                DB::raw("concat(tr.year, '-', tr.month) as label"),
                'p.name as dynamicColumn',
                DB::raw('SUM(IFNULL(tr.avgTotal, 0)) AS total')
            );

        if (!empty($criteria->comparePeriod) && !empty($criteria->compareMonth)) {
            $subqueryCompare = DB::table('wg_customer_road_safety_tracking as tr')
                ->join('wg_customer_road_safety as rs', 'rs.id', '=', 'tr.customer_road_safety_id')
                ->join('wg_config_road_safety_cycle as p', 'p.id', '=', 'tr.road_safety_cycle')
                ->where('rs.customer_id', $criteria->customerId)
                ->whereIn('tr.year', [$criteria->comparePeriod])
                ->whereIn('tr.month', [$criteria->compareMonth])
                ->groupBy(DB::raw('concat(tr.year, tr.month)'), 'p.id')
                ->select(
                    DB::raw("concat(tr.year, '-', tr.month) as label"),
                    'p.name as dynamicColumn',
                    DB::raw('SUM(IFNULL(tr.avgTotal, 0)) AS total')
                );

            $subquery->union($subqueryCompare)->mergeBindings($subqueryCompare);
        }

        list($query, $valueColumns) = $this->getQueryTransformRowToColumns($subquery);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartBar($query->get(), $config);
    }



    public function getStatsPercentByCustomer($criteria)
    {
        $sql = "
            SELECT SUM(ROUND(ROUND(IFNULL(total, 0), 2) * (weightedValue / 100), 2)) total
            FROM (
            SELECT item.`weightedValue`,
                    SUM(CASE WHEN cemsi.`code` = 'cp' OR cemsi.`code` = 'nac' THEN item.`value` ELSE 0 END) `total`
            FROM (
               SELECT cycle.id,
                       cycle.`name`,
                       cycle.`weightedValue`,
                       ms.id road_safety_id,
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
                                msp.id road_safety_id,
                                msi.id road_safety_item_id,
                                msi.`value`
               FROM wg_config_road_safety_cycle `cycle`
               INNER JOIN wg_road_safety ms ON cycle.id = ms.cycle_id
               INNER JOIN wg_road_safety msp ON ms.parent_id = msp.id
               INNER JOIN wg_road_safety_item msi ON ms.id = msi.road_safety_id
               WHERE cycle.`status` = 'activo'
                 AND ms.isActive = 1
                 AND msi.`isActive` = 1
            ) item
            LEFT JOIN (
                SELECT wg_customer_road_safety_item.road_safety_item_id, wg_config_road_safety_rate.`code`
                FROM wg_customer_road_safety_item
                INNER JOIN wg_customer_road_safety ON wg_customer_road_safety.id = wg_customer_road_safety_item.customer_road_safety_id
                INNER JOIN wg_config_road_safety_rate ON wg_customer_road_safety_item.rate_id = wg_config_road_safety_rate.id
                WHERE wg_customer_road_safety.customer_id = :customerId
            ) cemsi ON item.road_safety_item_id = cemsi.road_safety_item_id
            GROUP BY item.`name`, item.id
            ) `cycle`";

        $data = DB::select($sql, [
            'customerId' => $criteria->customerId
        ]);

        return count($data) > 0 ? $data[0] : null;
    }

}
