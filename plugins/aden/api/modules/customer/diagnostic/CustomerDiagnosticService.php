<?php

namespace AdeN\Api\Modules\Customer\Diagnostic;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerDiagnosticService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getChartBar($criteria)
    {
        $sql = "select pp.`name`, pp.`abbreviation`,
        SUM(CASE WHEN ISNULL(wr.`code`) THEN 1 ELSE 0 END) nocontesta,
        SUM(CASE WHEN wr.`code` = 'c' THEN 1 ELSE 0 END) cumple,
        SUM(CASE WHEN wr.`code` = 'cp' THEN 1 ELSE 0 END) parcial,
        SUM(CASE WHEN wr.`code` = 'nc' THEN 1 ELSE 0 END) nocumple,
        SUM(CASE WHEN wr.`code` = 'na' THEN 1 ELSE 0 END) noaplica
FROM wg_progam_prevention pp
INNER JOIN wg_progam_prevention_category pc ON pp.id = pc.program_id
INNER JOIN wg_progam_prevention_question pq ON pc.id = pq.category_id
INNER JOIN wg_progam_prevention_question_classification ppqc ON ppqc.program_prevention_question_id = pq.id
INNER JOIN wg_customer_diagnostic_prevention dp ON pq.id = dp.question_id
LEFT JOIN wg_rate wr on dp.rate_id = wr.id
WHERE dp.diagnostic_id = :diagnostic_id_1
AND ppqc.customer_size IN (SELECT size FROM wg_customers c INNER JOIN wg_customer_diagnostic cd ON cd.customer_id = c.id WHERE cd.id = :diagnostic_id_2)
GROUP BY pp.`name`
ORDER BY pp.id";

        $data = DB::select($sql, [
            'diagnostic_id_1' => $criteria->diagnosticId,
            'diagnostic_id_2' => $criteria->diagnosticId,
        ]);

        $rates = DB::table('wg_rate')->get();

        $config = array(
            "labelColumn" => 'abbreviation',
            "valueColumns" => [
                ['label' => 'Sin Contestar', 'field' => 'nocontesta'],
                ['label' => 'Cumple', 'field' => 'cumple', 'code' => 'c'],
                ['label' => 'Cumple Parcial', 'field' => 'parcial', 'code' => 'cp'],
                ['label' => 'No Cumple', 'field' => 'nocumple', 'code' => 'nc'],
                ['label' => 'No Aplica', 'field' => 'noaplica', 'code' => 'na'],
            ],
            "seriesLabel" => $rates
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartPie($criteria)
    {
        $sql = "SELECT program.`name` label,
        ROUND(IFNULL((total / questions),0), 2) `value`
FROM
(
    SELECT  pp.id program_id,
                    pp.`name`,
                    COUNT(*) questions,
                    SUM(CASE WHEN ISNULL(cdp.id) THEN 0 ELSE 1 END) answers,
                    SUM(cdp.`value`) total
    FROM wg_progam_prevention pp
    INNER JOIN wg_progam_prevention_category ppc ON pp.id = ppc.program_id
    INNER JOIN wg_progam_prevention_question ppq ON ppc.id = ppq.category_id
    INNER JOIN wg_progam_prevention_question_classification ppqc ON ppqc.program_prevention_question_id = ppq.id
    LEFT JOIN (
                                SELECT wg_customer_diagnostic_prevention.*, `wg_rate`.text, wg_rate.`value`
                                FROM wg_customer_diagnostic_prevention
                                INNER JOIN wg_rate ON `wg_customer_diagnostic_prevention`.rate_id = wg_rate.id
                                WHERE diagnostic_id = :diagnostic_id_1
                        ) cdp on ppq.id = cdp.question_id
    WHERE pp.`status` = 'activo' AND ppc.`status` = 'activo' AND ppq.`status` = 'activo'
        AND ppqc.customer_size IN (SELECT size FROM wg_customers c INNER JOIN wg_customer_diagnostic cd ON cd.customer_id = c.id WHERE cd.id = :diagnostic_id_2)
    GROUP BY  pp.`name`, pp.id
) program
ORDER BY 1";

        $data = DB::select($sql, [
            'diagnostic_id_1' => $criteria->diagnosticId,
            'diagnostic_id_2' => $criteria->diagnosticId,
        ]);

        return $this->chart->getChartPie($data);
    }

    public function getStats($criteria)
    {
        $sql = "SELECT
        diagnostic_id,
        questions,
        answers,
        ROUND((answers / questions) * 100, 2) advance,
        ROUND((total / questions), 2) average,
        total
    FROM
        (
            SELECT
                `cdp`.diagnostic_id,
                COUNT(*) questions,
                SUM(CASE WHEN ISNULL(cdp.id) THEN 0 ELSE 1 END) answers,
                SUM(cdp.`value`) total
            FROM
                wg_progam_prevention pp
            INNER JOIN wg_progam_prevention_category ppc ON pp.id = ppc.program_id
            INNER JOIN wg_progam_prevention_question ppq ON ppc.id = ppq.category_id
            INNER JOIN wg_progam_prevention_question_classification ppqc ON ppqc.program_prevention_question_id = ppq.id
            LEFT JOIN (
                SELECT
                    wg_customer_diagnostic_prevention.*, wg_rate.text,
                    wg_rate.`value`
                FROM
                    wg_customer_diagnostic_prevention
                INNER JOIN wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                WHERE
                    diagnostic_id = :diagnostic_id_1
            ) cdp ON ppq.id = cdp.question_id
            WHERE
                pp.`status` = 'activo'
            AND ppc.`status` = 'activo'
            AND ppq.`status` = 'activo'
            AND ppqc.customer_size IN (SELECT size FROM wg_customers c INNER JOIN wg_customer_diagnostic cd ON cd.customer_id = c.id WHERE cd.id = :diagnostic_id_2)
        ) programa
    ORDER BY 1";

        $result = DB::select($sql, [
            'diagnostic_id_1' => $criteria->diagnosticId,
            'diagnostic_id_2' => $criteria->diagnosticId,
        ]);

        return count($result) > 0 ? $result[0] : null;
    }

    public function getPrograms($diagnosticId)
    {
        $sql = "SELECT
        programa.id,
        `name`,
        abbreviation,
        questions,
        answers,
        round((answers / questions) * 100, 2) advance,
        round((total / questions), 2) average,
        total
    FROM
        (
            SELECT
                pp.id,
                pp.`name`,
                pp.abbreviation,
                COUNT(*) questions,
                SUM(CASE WHEN ISNULL(cdp.id) THEN 0 ELSE 1 END) answers,
                SUM(cdp.`value`) total
            FROM
                wg_progam_prevention pp
            INNER JOIN wg_progam_prevention_category ppc ON pp.id = ppc.program_id
            INNER JOIN wg_progam_prevention_question ppq ON ppc.id = ppq.category_id
            INNER JOIN wg_progam_prevention_question_classification ppqc ON ppqc.program_prevention_question_id = ppq.id
            LEFT JOIN (
                SELECT
                    wg_customer_diagnostic_prevention.*, wg_rate.text,
                    wg_rate.`value`
                FROM
                    wg_customer_diagnostic_prevention
                INNER JOIN wg_rate ON wg_customer_diagnostic_prevention.rate_id = wg_rate.id
                WHERE
                    diagnostic_id = :diagnostic_id_1
            ) cdp ON ppq.id = cdp.question_id
            WHERE
                pp.`status` = 'activo'
            AND ppc.`status` = 'activo'
            AND ppq.`status` = 'activo'
            AND ppqc.customer_size IN (SELECT size FROM wg_customers c INNER JOIN wg_customer_diagnostic cd ON cd.customer_id = c.id WHERE cd.id = :diagnostic_id_2)
            GROUP BY pp.`name`, pp.id
        ) programa
    ORDER BY 1";

        return DB::select($sql, [
            'diagnostic_id_1' => $diagnosticId,
            'diagnostic_id_2' => $diagnosticId,
        ]);
    }


    public function getPeriodsByCustomer(int $customerId)
    {
        return  DB::table('wg_customer_diagnostic')
            ->where('customer_id', $customerId)
            ->orderBy('created_at')
            ->select(
                DB::raw('YEAR(created_at) as value'),
                DB::raw('YEAR(created_at) as item')
            )
            ->distinct()
            ->get();
    }

    public function getPeriodsByCustomerCompare(int $customerId)
    {
        return  DB::table('wg_customer_diagnostic_prevention_tracking as o')
            ->join('wg_customer_diagnostic as d', 'd.id', '=', 'o.diagnostic_id')
            ->where('d.customer_id', $customerId)
            ->orderBy('o.year')
            ->select(
                DB::raw('o.year as value'),
                DB::raw('o.year as item')
            )
            ->distinct()
            ->get();
    }

    public function getTotalByCustomerAndYearChartLine($criteria)
    {
        $periods = [$criteria->period];

        if ($criteria->comparePeriod) {
            $periods[] = $criteria->comparePeriod;
        }

        $qSub = DB::table('wg_customer_diagnostic_prevention_tracking as o')
            ->join('wg_customer_diagnostic as d', 'd.id', '=', 'o.diagnostic_id')
            ->where('d.customer_id', $criteria->customerId)
            ->whereIn("o.year", $periods)
            ->select(
                "o.year as label",
                "o.month",
                DB::raw("(SUM(total) / SUM(questions)) as value")
            )
            ->groupBy("o.year", "o.month");

        $data = DB::table(DB::raw("({$qSub->toSql()}) as o"))
            ->mergeBindings($qSub)
            ->groupBy("o.label")
            ->select(
                "o.label",
                DB::raw("MAX(CASE WHEN month = 1 THEN ROUND(IFNULL(o.value,0),2) END) 'JAN'"),
                DB::raw("MAX(CASE WHEN month = 2 THEN ROUND(IFNULL(o.value,0),2) END) 'FEB'"),
                DB::raw("MAX(CASE WHEN month = 3 THEN ROUND(IFNULL(o.value,0),2) END) 'MAR'"),
                DB::raw("MAX(CASE WHEN month = 4 THEN ROUND(IFNULL(o.value,0),2) END) 'APR'"),
                DB::raw("MAX(CASE WHEN month = 5 THEN ROUND(IFNULL(o.value,0),2) END) 'MAY'"),
                DB::raw("MAX(CASE WHEN month = 6 THEN ROUND(IFNULL(o.value,0),2) END) 'JUN'"),
                DB::raw("MAX(CASE WHEN month = 7 THEN ROUND(IFNULL(o.value,0),2) END) 'JUL'"),
                DB::raw("MAX(CASE WHEN month = 8 THEN ROUND(IFNULL(o.value,0),2) END) 'AUG'"),
                DB::raw("MAX(CASE WHEN month = 9 THEN ROUND(IFNULL(o.value,0),2) END) 'SEP'"),
                DB::raw("MAX(CASE WHEN month = 10 THEN ROUND(IFNULL(o.value,0),2) END) 'OCT'"),
                DB::raw("MAX(CASE WHEN month = 11 THEN ROUND(IFNULL(o.value,0),2) END) 'NOV'"),
                DB::raw("MAX(CASE WHEN month = 12 THEN ROUND(IFNULL(o.value,0),2) END) 'DEC'")
            )
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }


    public function getDiagnosticProgress($criteria)
    {
        $subquery = DB::table('wg_progam_prevention as pp')
            ->join('wg_progam_prevention_category as pc', 'pc.program_id', '=', 'pp.id')
            ->join('wg_progam_prevention_question as pq', 'pq.category_id', '=', 'pc.id')
            ->join('wg_progam_prevention_question_classification as ppqc', 'ppqc.program_prevention_question_id', '=', 'pq.id')
            ->join('wg_customer_diagnostic_prevention as dp', 'dp.question_id', '=', 'pq.id')
            ->join('wg_customer_diagnostic as cd', 'cd.id', '=', 'dp.diagnostic_id')
            ->leftJoin('wg_rate as wr', 'wr.id', '=', 'dp.rate_id')
            ->where('cd.customer_id', $criteria->customerId)
            ->whereYear('cd.created_at', $criteria->period)
            ->whereRaw("ppqc.customer_size IN (SELECT size FROM wg_customers c WHERE c.id = {$criteria->customerId})")
            ->groupBy('pp.name')
            ->orderBy('pp.id')
            ->select(
                'pp.id', 'pp.abbreviation as name',
                DB::raw("COUNT(*) AS total_by_category"),
                DB::raw("SUM(CASE WHEN ISNULL(wr.`code`) THEN 1 ELSE 0 END) nocontesta"),
                DB::raw("SUM(CASE WHEN wr.`code` = 'c' THEN 1 ELSE 0 END) cumple"),
                DB::raw("SUM(CASE WHEN wr.`code` = 'cp' THEN 1 ELSE 0 END) parcial"),
                DB::raw("SUM(CASE WHEN wr.`code` = 'nc' THEN 1 ELSE 0 END) nocumple"),
                DB::raw("SUM(CASE WHEN wr.`code` = 'na' THEN 1 ELSE 0 END) noaplica"),
                DB::raw("SUM(wr.`value`) total_rate")
            );

        return DB::table(DB::raw("({$subquery->toSql()}) as o"))
            ->mergeBindings($subquery)
            ->orderBy('o.id')
            ->select(
                'o.name',
                'total_by_category', 'nocontesta', 'cumple', 'parcial', 'nocumple', 'noaplica',
                DB::raw("ROUND((nocontesta / total_by_category) * 100, 2) nocontesta_percent"),
                DB::raw("ROUND((cumple / total_by_category) * 100, 2) cumple_percent"),
                DB::raw("ROUND((parcial / total_by_category) * 100, 2) parcial_percent"),
                DB::raw("ROUND((nocumple / total_by_category) * 100, 2) nocumple_percent"),
                DB::raw("ROUND((noaplica / total_by_category) * 100, 2) noaplica_percent"),

                DB::raw("SUM(total_by_category) OVER () total"),
                DB::raw("ROUND((SUM(nocontesta) OVER () / SUM(total_by_category) OVER ()) * 100, 2) nocontesta_percent_total"),
                DB::raw("ROUND((SUM(cumple) OVER () / SUM(total_by_category) OVER ()) * 100, 2) cumple_percent_total"),
                DB::raw("ROUND((SUM(parcial) OVER () / SUM(total_by_category) OVER ()) * 100, 2) parcial_percent_total"),
                DB::raw("ROUND((SUM(nocumple) OVER () / SUM(total_by_category) OVER ()) * 100, 2) nocumple_percent_total"),
                DB::raw("ROUND((SUM(noaplica) OVER () / SUM(total_by_category) OVER ()) * 100, 2) noaplica_percent_total"),

                DB::raw("round(sum(total_rate) over() / (SUM(total_by_category) OVER ()) , 2) as total_percent"),
                DB::raw("ROUND(total_rate / total_by_category, 2) as percent")
            )
            ->get();
    }

}
