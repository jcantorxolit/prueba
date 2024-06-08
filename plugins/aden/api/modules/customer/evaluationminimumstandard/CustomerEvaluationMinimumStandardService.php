<?php

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandard;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerEvaluationMinimumStandardService extends BaseService
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
                item.minimum_standard_id,
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
                    customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id
            ) cemsi ON item.minimum_standard_item_id = cemsi.minimum_standard_item_id
            GROUP BY
                item.`name`,
                item.id
        ) cycle
    ORDER BY
        cycle.id";

        $data = DB::select($sql, [
            'customer_evaluation_minimum_standard_id' => $criteria->evaluationMinimumStandardId
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
        ROUND(IFNULL((total), 0), 2) `value`,
        cycle.color,
        cycle.highlightColor
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
            GROUP BY
                cycle.`name`,
                cycle.id
        ) cycle
    ORDER BY 2";

        $data = DB::select($sql, [
            'customer_evaluation_minimum_standard_id' => $criteria->evaluationMinimumStandardId
        ]);

        return $this->chart->getChartPie($data);
    }

    public function getStats($criteria)
    {
        $sql = "SELECT
        MAX(ROUND(IFNULL(total, 0), 2)) total
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
            GROUP BY cemsi.customer_evaluation_minimum_standard_id
        ) cycle";

        $data = DB::select($sql, [
            'customer_evaluation_minimum_standard_id' => $criteria->evaluationMinimumStandardId
        ]);

        return count($data) > 0 ? $data[0] : null;
    }

    public function getCycles($customerEvaluationMinimumStandardId)
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
                ) total
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
                item.id
        ) cycle
        ORDER BY 1";

        return DB::select($sql, [
            'customer_evaluation_minimum_standard_id' => $customerEvaluationMinimumStandardId,
        ]);
    }

    public function getChartStatus($criteria)
    {
        $sql = "SELECT
        spp.item `label`,
        IFNULL(accomplish, 0) accomplish,
        IFNULL(no_apply_with_justification, 0) noApplyWith,
        IFNULL(no_accomplish, 0) noAccomplish,
        IFNULL(no_apply_without_justification, 0) noApplyWithout,
        IFNULL(no_checked, 0) noChecked
    FROM
        system_parameters spp
    LEFT JOIN (
        SELECT
            IFNULL(SUM(accomplish), 0) accomplish,
            IFNULL(SUM(no_apply_with_justification),0) no_apply_with_justification,
            IFNULL(SUM(no_accomplish), 0) no_accomplish,
            IFNULL(SUM(no_apply_without_justification),0) no_apply_without_justification,
            IFNULL(sum(no_checked), 0) no_checked,
            `month`,
            `year`
        FROM
            wg_customer_evaluation_minimum_standard_tracking cdpt
        INNER JOIN wg_config_minimum_standard_cycle pp ON pp.id = cdpt.minimum_standard_cycle
        WHERE
            customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id AND `year` = :year
        GROUP BY
            customer_evaluation_minimum_standard_id,`month`
    ) rm ON spp.`value` = rm.`month`
    WHERE
        spp.`group` = 'month'";

        $data = DB::select($sql, [
            'customer_evaluation_minimum_standard_id' => $criteria->evaluationMinimumStandardId,
            'year' => $criteria->year,
        ]);

        $config = array(
            "labelColumn" => 'label',
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

    public function getChartAverage($criteria)
    {
        $data = DB::table('wg_customer_evaluation_minimum_standard_tracking')
            ->join("wg_config_minimum_standard_cycle", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_tracking.minimum_standard_cycle', '=', 'wg_config_minimum_standard_cycle.id');
            })
            ->select(
                'wg_config_minimum_standard_cycle.abbreviation AS label',
                DB::raw("SUM(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'JAN'"),
                DB::raw("SUM(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'FEB'"),
                DB::raw("SUM(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'MAR'"),
                DB::raw("SUM(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'APR'"),
                DB::raw("SUM(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'MAY'"),
                DB::raw("SUM(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'JUN'"),
                DB::raw("SUM(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'JUL'"),
                DB::raw("SUM(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'AUG'"),
                DB::raw("SUM(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'SEP'"),
                DB::raw("SUM(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'OCT'"),
                DB::raw("SUM(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'NOV'"),
                DB::raw("SUM(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking.avgTotal,0),2) END) 'DEC'")
            )
            ->where('wg_customer_evaluation_minimum_standard_tracking.customer_evaluation_minimum_standard_id', $criteria->evaluationMinimumStandardId)
            ->where('wg_customer_evaluation_minimum_standard_tracking.year', $criteria->year)
            ->groupBy('wg_customer_evaluation_minimum_standard_tracking.minimum_standard_cycle')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getChartTotal($criteria)
    {
        $sql = "SELECT
        i.label
           , MAX(CASE WHEN `month` = 1 THEN ROUND(IFNULL(`value`,0),2) END) JAN
           , MAX(CASE WHEN `month` = 2 THEN ROUND(IFNULL(`value`,0),2) END) FEB
           , MAX(CASE WHEN `month` = 3 THEN ROUND(IFNULL(`value`,0),2) END) MAR
           , MAX(CASE WHEN `month` = 4 THEN ROUND(IFNULL(`value`,0),2) END) APR
           , MAX(CASE WHEN `month` = 5 THEN ROUND(IFNULL(`value`,0),2) END) MAY
           , MAX(CASE WHEN `month` = 6 THEN ROUND(IFNULL(`value`,0),2) END) JUN
           , MAX(CASE WHEN `month` = 7 THEN ROUND(IFNULL(`value`,0),2) END) JUL
           , MAX(CASE WHEN `month` = 8 THEN ROUND(IFNULL(`value`,0),2) END) AUG
           , MAX(CASE WHEN `month` = 9 THEN ROUND(IFNULL(`value`,0),2) END) SEP
           , MAX(CASE WHEN `month` = 10 THEN ROUND(IFNULL(`value`,0),2) END) OCT
           , MAX(CASE WHEN `month` = 11 THEN ROUND(IFNULL(`value`,0),2) END) NOV
           , MAX(CASE WHEN `month` = 12 THEN ROUND(IFNULL(`value`,0),2) END) 'DEC'
        FROM (
                       SELECT customer_evaluation_minimum_standard_id, 'Promedio Total % (calificación)' label, (SUM(total)) `value`, `month`, `year`
                       FROM wg_customer_evaluation_minimum_standard_tracking
                       GROUP BY customer_evaluation_minimum_standard_id, `month`, `year`
                   ) i
       WHERE customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id AND i.`year` = :year";

        $data = DB::select($sql, [
            'customer_evaluation_minimum_standard_id' => $criteria->evaluationMinimumStandardId,
            'year' => $criteria->year,
        ]);

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getChartAdvance($criteria)
    {
        $sql = "SELECT
        i.label
           , MAX(CASE WHEN `month` = 1 THEN ROUND(IFNULL(`value`,0),2) END) JAN
           , MAX(CASE WHEN `month` = 2 THEN ROUND(IFNULL(`value`,0),2) END) FEB
           , MAX(CASE WHEN `month` = 3 THEN ROUND(IFNULL(`value`,0),2) END) MAR
           , MAX(CASE WHEN `month` = 4 THEN ROUND(IFNULL(`value`,0),2) END) APR
           , MAX(CASE WHEN `month` = 5 THEN ROUND(IFNULL(`value`,0),2) END) MAY
           , MAX(CASE WHEN `month` = 6 THEN ROUND(IFNULL(`value`,0),2) END) JUN
           , MAX(CASE WHEN `month` = 7 THEN ROUND(IFNULL(`value`,0),2) END) JUL
           , MAX(CASE WHEN `month` = 8 THEN ROUND(IFNULL(`value`,0),2) END) AUG
           , MAX(CASE WHEN `month` = 9 THEN ROUND(IFNULL(`value`,0),2) END) SEP
           , MAX(CASE WHEN `month` = 10 THEN ROUND(IFNULL(`value`,0),2) END) OCT
           , MAX(CASE WHEN `month` = 11 THEN ROUND(IFNULL(`value`,0),2) END) NOV
           , MAX(CASE WHEN `month` = 12 THEN ROUND(IFNULL(`value`,0),2) END) 'DEC'
        FROM (
                SELECT customer_evaluation_minimum_standard_id, 'Avance % (respuestas / preguntas)' label, ((SUM(checked) / SUM(items)) * 100) `value`, `month`, `year`
                FROM wg_customer_evaluation_minimum_standard_tracking
                GROUP BY customer_evaluation_minimum_standard_id, `month`, `year`
            ) i
       WHERE customer_evaluation_minimum_standard_id = :customer_evaluation_minimum_standard_id AND i.`year` = :year";

        $data = DB::select($sql, [
            'customer_evaluation_minimum_standard_id' => $criteria->evaluationMinimumStandardId,
            'year' => $criteria->year,
        ]);

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }


    // DASHBOARD

    public function getChartFirst($criteria)
    {
        $sql = "SELECT
        MAX(ROUND(IFNULL(total, 0), 2)) total
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
            GROUP BY cemsi.customer_evaluation_minimum_standard_id
        ) cycle";

        $data = DB::select($sql, [
            'customer_evaluation_minimum_standard_id' => $criteria->evaluationMinimumStandardId
        ]);

        foreach ($data as $pie) {
            $completed = $pie->{"total"};
        }
        $remaining = 100 - $completed;

        $data[0] = new \stdClass();
        $data[0]->value = $completed;
        $data[0]->label = "% COMPLETADO";
        $data[1] = new \stdClass();
        $data[1]->value = $remaining;
        $data[1]->label = "% RESTANTE";

        return $this->chart->getChartPie($data);
    }

    public function getChartSecond($criteria)
    {

        $dataPlanear = $this->getCycleMinimumStandard(1);
        $dataHacer = $this->getCycleMinimumStandard(2);
        $dataVerificar = $this->getCycleMinimumStandard(3);
        $dataActuar = $this->getCycleMinimumStandard(4);

        //Log::info($dataPlanear);

        $arrayMetas = array(
            $this->getMetaMinimumStandard($dataPlanear),
            $this->getMetaMinimumStandard($dataHacer),
            $this->getMetaMinimumStandard($dataVerificar),
            $this->getMetaMinimumStandard($dataActuar)
        );

        $arrayCumplimientos = array(
            $this->getCumplimientoMinimumStandard($dataPlanear),
            $this->getCumplimientoMinimumStandard($dataHacer),
            $this->getCumplimientoMinimumStandard($dataVerificar),
            $this->getCumplimientoMinimumStandard($dataActuar)
        );

        $dataChart[0] = new \stdClass();
        $dataChart[0]->label = "Meta";
        $dataChart[0]->data = $arrayMetas;
        $dataChart[1] = new \stdClass();
        $dataChart[1]->label = "Valor";
        $dataChart[1]->data = $arrayCumplimientos;


        //Log::info($dataChart);

        //return $this->chart->getChartRadar($dataChart, $config);

        return [
            'labels' => ['PLANEAR', 'HACER', 'VERIFICAR', 'ACTUAR'],
            'datasets' => $dataChart,
        ];
    }

    public function getMetaMinimumStandard($data)
    {
        $meta = 0;
        $index = 0;
        foreach ($data as $item) {
            $cycleId = (float)$item->{"cycle_id"};
            $id = (float)$item->{"id"};
            $dataItem[] = $this->getItemsMinimumStandard($cycleId, $id);

            foreach ($dataItem[$index] as $individualItem) {
                $meta = $meta + (float)$individualItem->{"item_value"};
            }
            $index++;
        }

        return $meta;
    }

    public function getCumplimientoMinimumStandard($data)
    {
        $cumplimiento = 0;
        foreach ($data as $item) {
            $cycleId = (float)$item->{"cycle_id"};
            $id = (float)$item->{"id"};
            $cumplimiento = $cumplimiento + (float)$item->{"average"};
        }

        return $cumplimiento;
    }

    public function getCycleMinimumStandard($cycleId)
    {
        $sql = "SELECT
        `category`.`id`,
        `category`.`description`,
        SUM(items) AS items,
        SUM(checked) AS checked,
        ROUND(
            IFNULL(SUM((checked / items) * 100), 0),
            2
        ) AS advance,
        ROUND(IFNULL(SUM(total), 0), 2) AS average,
        ROUND(IFNULL(SUM(total), 0), 2) AS total,
        `category`.`cycle_id`,
        `category`.`parent_id`
    FROM
        (
            SELECT
                `wg_minimum_standard`.`id`,
                `wg_minimum_standard`.`description`,
                `wg_minimum_standard`.`parent_id`,
                `wg_config_minimum_standard_cycle`.`id` AS `cycle_id`,
                `wg_config_minimum_standard_cycle`.`name`,
                COUNT(*) items,
                SUM(
                    CASE
                    WHEN ISNULL(detail.id) THEN
                        0
                    ELSE
                        1
                    END
                ) AS checked,
                SUM(
                    CASE
                    WHEN detail.`code` = 'cp'
                    OR detail.`code` = 'nac' THEN
                        wg_minimum_standard_item.`value`
                    ELSE
                        0
                    END
                ) AS total
            FROM
                `wg_config_minimum_standard_cycle`
            INNER JOIN `wg_minimum_standard` ON `wg_config_minimum_standard_cycle`.`id` = `wg_minimum_standard`.`cycle_id`
            INNER JOIN `wg_minimum_standard_item` ON `wg_minimum_standard`.`id` = `wg_minimum_standard_item`.`minimum_standard_id`
            LEFT JOIN (
                SELECT
                    `wg_customer_evaluation_minimum_standard_item`.*, `wg_config_minimum_standard_rate`.`text`,
                    `wg_config_minimum_standard_rate`.`value`,
                    `wg_config_minimum_standard_rate`.`code`
                FROM
                    `wg_customer_evaluation_minimum_standard_item`
                INNER JOIN `wg_config_minimum_standard_rate` ON `wg_customer_evaluation_minimum_standard_item`.`rate_id` = `wg_config_minimum_standard_rate`.`id`
                WHERE
                    `customer_evaluation_minimum_standard_id` = '1'
            ) AS detail ON `wg_minimum_standard_item`.`id` = `detail`.`minimum_standard_item_id`
            WHERE
                wg_config_minimum_standard_cycle.`status` = 'activo'
            AND wg_minimum_standard.`isActive` = '1'
            AND wg_minimum_standard_item.`isActive` = '1'
            GROUP BY
                `wg_minimum_standard`.`description`,
                `wg_minimum_standard`.`id`
        ) AS category
    WHERE
        `category`.`cycle_id` = :cycle_id
    GROUP BY
        `category`.`id`
    ORDER BY
        `category`.`description` ASC
    LIMIT 10 OFFSET 0";

        $data = DB::select($sql, [
            'cycle_id' => $cycleId,
        ]);
        return $data;
    }

    public function getItemsMinimumStandard($cycleId, $id)
    {
        $sql = "SELECT
        `wg_config_minimum_standard_rate`.`code` AS `rate_code`,
        `wg_config_minimum_standard_rate`.`text` AS `rate_text`,
        `wg_minimum_standard_item`.`value` AS `item_value`
    FROM
        `wg_customer_evaluation_minimum_standard_item`
    INNER JOIN `wg_customer_evaluation_minimum_standard` ON `wg_customer_evaluation_minimum_standard`.`id` = `wg_customer_evaluation_minimum_standard_item`.`customer_evaluation_minimum_standard_id`
    INNER JOIN `wg_minimum_standard_item` ON `wg_minimum_standard_item`.`id` = `wg_customer_evaluation_minimum_standard_item`.`minimum_standard_item_id`
    INNER JOIN `wg_minimum_standard` ON `wg_minimum_standard`.`id` = `wg_minimum_standard_item`.`minimum_standard_id`
    INNER JOIN `wg_config_minimum_standard_cycle` ON `wg_config_minimum_standard_cycle`.`id` = `wg_minimum_standard`.`cycle_id`
    LEFT JOIN `wg_config_minimum_standard_rate` ON `wg_config_minimum_standard_rate`.`id` = `wg_customer_evaluation_minimum_standard_item`.`rate_id`
    WHERE
        `wg_config_minimum_standard_cycle`.`status` = 'activo'
    AND `wg_minimum_standard`.`isActive` = '1'
    AND `wg_minimum_standard_item`.`isActive` = '1'
    AND `wg_customer_evaluation_minimum_standard_item`.`customer_evaluation_minimum_standard_id` = '1'
    AND `wg_config_minimum_standard_cycle`.`id` = :cycle_id
    AND `wg_minimum_standard`.`id` = :id
    ORDER BY
        `wg_customer_evaluation_minimum_standard_item`.`id` ASC";

        $data = DB::select($sql, [
            'cycle_id' => $cycleId,
            'id' => $id
        ]);

        return $data;
    }



    public function getMinimalStandardProgress($criteria)
    {
        $subquery = DB::table('wg_customer_evaluation_minimum_standard_item_0312 as msi_0312')
            ->join('wg_customer_evaluation_minimum_standard_0312 as ms_0312', 'ms_0312.id', '=', 'msi_0312.customer_evaluation_minimum_standard_id')
            ->join('wg_minimum_standard_item_0312', 'wg_minimum_standard_item_0312.id', '=', 'msi_0312.minimum_standard_item_id')
            ->join('wg_minimum_standard_0312', 'wg_minimum_standard_0312.id', '=', 'wg_minimum_standard_item_0312.minimum_standard_id')
            ->join('wg_config_minimum_standard_cycle_0312', 'wg_config_minimum_standard_cycle_0312.id', '=', 'wg_minimum_standard_0312.cycle_id')
            ->leftJoin('wg_config_minimum_standard_rate_0312 as wg_config_minimum_standard_rate_0312', 'wg_config_minimum_standard_rate_0312.id', '=', 'msi_0312.rate_id')
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', true)
            ->where('wg_minimum_standard_item_0312.is_active', true)
            ->where('msi_0312.status', 'activo')
            //->where('msi_0312.is_freezed', true)
            ->where('ms_0312.customer_id', $criteria->customerId)
            ->where('ms_0312.period', $criteria->period)
            ->select(
                'ms_0312.id as customerEvaluationMinimumStandardId',
                'msi_0312.minimum_standard_item_id as minimumStandardItemId',
                'msi_0312.id as customerEvaluationMinimumStandardItemId',
                'wg_config_minimum_standard_rate_0312.code as rateCode'
            );

        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join('wg_minimum_standard_0312', 'wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id')
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join('wg_minimum_standard_item_0312', 'wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id')
            ->join(DB::raw("({$subquery->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimumStandardItemId', 'wg_minimum_standard_item_0312.id');
            })
            ->mergeBindings($subquery)
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', true)
            ->where('wg_minimum_standard_item_0312.is_active', true)
            ->groupBy('wg_config_minimum_standard_cycle_0312.id', 'wg_config_minimum_standard_cycle_0312.name')
            ->orderBy('wg_config_minimum_standard_cycle_0312.id')
            ->select(
                'wg_config_minimum_standard_cycle_0312.id as cycleId',
                'wg_config_minimum_standard_cycle_0312.name',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'cp' OR
                                 wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'nac' OR
                                 wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardItemId IS NULL
                            THEN wg_minimum_standard_item_0312.value
                            ELSE 0
                       END) AS percent"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'nac' OR
                           wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardItemId IS NULL
                        THEN 1
                      ELSE 0
                 END) AS no_apply_with_justification"),
                DB::raw("SUM(CASE WHEN
                          wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardId IS NOT NULL AND
                          ISNULL(wg_customer_evaluation_minimum_standard_item_0312.rateCode) THEN 1
                      ELSE 0
                 END) AS no_checked")
            );

        return DB::table(DB::raw("({$query->toSql()}) as o"))
            ->mergeBindings($query)
            ->orderBy('o.cycleId')
            ->select(
                'name',
                'items',
                'percent',
                'accomplish',
                'no_accomplish',
                'no_apply_with_justification',
                'no_checked',
                DB::raw('sum(percent) over() as total'),
                DB::raw("round(accomplish/items*100) as accomplish_percent"),
                DB::raw("round(no_accomplish/items*100) as no_accomplish_percent"),
                DB::raw("round(no_apply_with_justification/items*100) as no_apply_with_justification_percent"),
                DB::raw("round(no_checked/items*100) as no_checked_percent"),

                DB::raw("round( sum(accomplish) over () / (sum(items) over()) *100) as accomplish_percent_total"),
                DB::raw("round(sum(no_accomplish) over() / (sum(items) over()) *100) as no_accomplish_percent_total"),
                DB::raw("round(sum(no_apply_with_justification) over() / (sum(items) over()) *100) as no_apply_with_justification_percent_total"),
                DB::raw("round(sum(no_checked) over() / (sum(items) over()) *100) as no_checked_percent_total")
            )
            ->get();
    }


    public function getStatsBoard($criteria)
    {
        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            });

        $qItems = $this->prepareQueryForItems($criteria);
        $query->leftjoin(DB::raw("({$qItems->toSql()}) AS wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimumStandardItemId', '=', 'wg_minimum_standard_item_0312.id');
        })->mergeBindings($qItems);

        $query
            ->select(
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'nac' OR wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardItemId IS NULL THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total")
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);

        return $query->first();
    }

    private function prepareQueryForItems($criteria)
    {
        $query = DB::table('wg_customer_evaluation_minimum_standard_item_0312');

        $query->join('wg_customer_evaluation_minimum_standard_0312', function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
        })->join('wg_minimum_standard_item_0312', function ($join) {
            $join->on('wg_minimum_standard_item_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');
        })->join('wg_minimum_standard_0312', function ($join) {
            $join->on('wg_minimum_standard_0312.id', '=', 'wg_minimum_standard_item_0312.minimum_standard_id');
        })->join('wg_config_minimum_standard_cycle_0312', function ($join) {
            $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_minimum_standard_0312.cycle_id');
        })->leftjoin('wg_config_minimum_standard_rate_0312', function ($join) {
            $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
        });

        $query
            ->select(
                'wg_customer_evaluation_minimum_standard_0312.id AS customerEvaluationMinimumStandardId',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id AS minimumStandardItemId',
                'wg_customer_evaluation_minimum_standard_item_0312.id AS customerEvaluationMinimumStandardItemId',
                'wg_config_minimum_standard_cycle_0312.id AS cycleId',
                'wg_config_minimum_standard_cycle_0312.name AS cycle',
                'wg_minimum_standard_item_0312.numeral',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.value',
                'wg_minimum_standard_0312.id AS minimumStandardParentId',
                'wg_config_minimum_standard_rate_0312.id AS rateId',
                "wg_config_minimum_standard_rate_0312.text AS rateText",
                "wg_config_minimum_standard_rate_0312.code AS rateCode",
                "wg_config_minimum_standard_rate_0312.value AS rateValue",
                "wg_config_minimum_standard_rate_0312.color AS rateColor",
                'wg_customer_evaluation_minimum_standard_item_0312.created_at',
                'wg_customer_evaluation_minimum_standard_item_0312.updated_at'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            //->where('wg_customer_evaluation_minimum_standard_item_0312.is_freezed', 1)
            ->where('wg_customer_evaluation_minimum_standard_0312.customer_id', $criteria->customerId)
            ->where('wg_customer_evaluation_minimum_standard_0312.period', $criteria->period)
            ->orderBy('wg_config_minimum_standard_cycle_0312.id');

        return $query;
    }
}
