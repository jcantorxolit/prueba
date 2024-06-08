<?php

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandardTracking0312;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Carbon\Carbon;
use AdeN\Api\Helpers\ExportHelper;

class CustomerEvaluationMinimumStandardTracking0312Service extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function createMissingMonthlyReport($criteria)
    {
        $model = DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->select(DB::raw('MAX(`month`) `month`, MAX(`year`) `year`'))
            ->where('customer_evaluation_minimum_standard_id', $criteria->customerEvaluationMinimumStandardId)
            ->first();

        if ($model != null) {
            $today = Carbon::now('America/Bogota');
            $lastTime = Carbon::createFromDate($model->year, $model->month, 1, 'America/Bogota');

            $diffInMonths = $today->diffInMonths($lastTime);

            for ($i = 1; $i < $diffInMonths; $i++) {
                $currentTime = $lastTime->addMonths(1);
                $criteria->fromYear = $model->year;
                $criteria->fromMonth = $model->month;
                $criteria->toYear = $currentTime->year;
                $criteria->toMonth = $currentTime->month;
                $this->duplicateMonthlyReport($criteria);
            }
        }
    }

    public function migratePreviousMonthlyReport($criteria)
    {
        $this->duplicateMonthlyReport($criteria);
    }

    public function insertMonthlyReport($criteria)
    {
        DB::table("wg_customer_evaluation_minimum_standard_tracking_0312")
            ->where('customer_evaluation_minimum_standard_id', $criteria->customerEvaluationMinimumStandardId)
            ->where('year', $criteria->currentYear)
            ->where('month', $criteria->currentMonth)
            ->delete();

        $qDetail = $this->prepareQueryDetail($criteria);

        $query = DB::table(DB::raw("({$qDetail->toSql()}) as wg_customer_evaluation_minimum_standard_items_0312"))
            ->mergeBindings($qDetail)
            ->leftjoin("wg_customer_evaluation_minimum_standard_tracking_0312", function ($join) use ($criteria) {
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id', '=', 'wg_customer_evaluation_minimum_standard_items_0312.customer_evaluation_minimum_standard_id');
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_cycle', '=', 'wg_customer_evaluation_minimum_standard_items_0312.minimum_standard_cycle_id');
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_parent_id', '=', 'wg_customer_evaluation_minimum_standard_items_0312.minimum_standard_id');
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.year', '=', 'wg_customer_evaluation_minimum_standard_items_0312.current_year');
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.month', '=', 'wg_customer_evaluation_minimum_standard_items_0312.current_month');
            })
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_evaluation_minimum_standard_items_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_items_0312.minimum_standard_cycle_id',
                'wg_customer_evaluation_minimum_standard_items_0312.minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_items_0312.items',
                'wg_customer_evaluation_minimum_standard_items_0312.checked',
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_items_0312.checked / wg_customer_evaluation_minimum_standard_items_0312.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(wg_customer_evaluation_minimum_standard_items_0312.total, 0) ,2) AS average"),
                'wg_customer_evaluation_minimum_standard_items_0312.total',
                'wg_customer_evaluation_minimum_standard_items_0312.accomplish',
                'wg_customer_evaluation_minimum_standard_items_0312.no_accomplish',
                'wg_customer_evaluation_minimum_standard_items_0312.no_apply_without_justification',
                'wg_customer_evaluation_minimum_standard_items_0312.no_apply_with_justification',
                'wg_customer_evaluation_minimum_standard_items_0312.no_checked',
                'wg_customer_evaluation_minimum_standard_items_0312.current_year',
                'wg_customer_evaluation_minimum_standard_items_0312.current_month',
                'wg_customer_evaluation_minimum_standard_items_0312.created_at',
                'wg_customer_evaluation_minimum_standard_items_0312.created_by',
                'wg_customer_evaluation_minimum_standard_items_0312.updated_at',
                'wg_customer_evaluation_minimum_standard_items_0312.updated_by'
            )
            ->whereNull('wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id');

        $sql = 'INSERT INTO `wg_customer_evaluation_minimum_standard_tracking_0312` (`id`, `customer_evaluation_minimum_standard_id`, `minimum_standard_cycle`, `minimum_standard_parent_id`, `items`, `checked`, `avg_progress`, `avg_total`, `total`, `accomplish`, `no_accomplish`, `no_apply_with_justification`, `no_apply_without_justification`, `no_checked`, `year`, `month`, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public function updateMonthlyReport($criteria)
    {
        $qDetail = $this->prepareQueryDetail($criteria);

        DB::table("wg_customer_evaluation_minimum_standard_tracking_0312")
            ->mergeBindings($qDetail)
            ->join(DB::raw("({$qDetail->toSql()}) as wg_customer_evaluation_minimum_standard_items_0312"), function ($join) use ($criteria) {
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id', '=', 'wg_customer_evaluation_minimum_standard_items_0312.customer_evaluation_minimum_standard_id');
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_cycle', '=', 'wg_customer_evaluation_minimum_standard_items_0312.minimum_standard_cycle_id');
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_parent_id', '=', 'wg_customer_evaluation_minimum_standard_items_0312.minimum_standard_id');
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.year', '=', 'wg_customer_evaluation_minimum_standard_items_0312.current_year');
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.month', '=', 'wg_customer_evaluation_minimum_standard_items_0312.current_month');
            })
            ->update([
                'wg_customer_evaluation_minimum_standard_tracking_0312.items' => DB::raw('wg_customer_evaluation_minimum_standard_items_0312.items'),
                'wg_customer_evaluation_minimum_standard_tracking_0312.checked' => DB::raw('wg_customer_evaluation_minimum_standard_items_0312.checked'),
                'wg_customer_evaluation_minimum_standard_tracking_0312.avg_progress' => DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_items_0312.checked / wg_customer_evaluation_minimum_standard_items_0312.items) * 100, 0) ,2)"),
                'wg_customer_evaluation_minimum_standard_tracking_0312.avg_total' => DB::raw("ROUND(IFNULL(wg_customer_evaluation_minimum_standard_items_0312.total, 0) ,2)"),
                'wg_customer_evaluation_minimum_standard_tracking_0312.total' => DB::raw('wg_customer_evaluation_minimum_standard_items_0312.total'),
                'wg_customer_evaluation_minimum_standard_tracking_0312.accomplish' => DB::raw('wg_customer_evaluation_minimum_standard_items_0312.accomplish'),
                'wg_customer_evaluation_minimum_standard_tracking_0312.no_accomplish' => DB::raw('wg_customer_evaluation_minimum_standard_items_0312.no_accomplish'),
                'wg_customer_evaluation_minimum_standard_tracking_0312.no_apply_with_justification' => DB::raw('wg_customer_evaluation_minimum_standard_items_0312.no_apply_with_justification'),
                'wg_customer_evaluation_minimum_standard_tracking_0312.no_apply_without_justification' => DB::raw('wg_customer_evaluation_minimum_standard_items_0312.no_apply_without_justification'),
                'wg_customer_evaluation_minimum_standard_tracking_0312.no_checked' => DB::raw('wg_customer_evaluation_minimum_standard_items_0312.no_checked'),
                'wg_customer_evaluation_minimum_standard_tracking_0312.updated_at' => Carbon::now(),
                'wg_customer_evaluation_minimum_standard_tracking_0312.updated_by' => $criteria->updatedBy
            ]);
    }


    public function getExportSummaryCycleData($criteria)
    {
        $q1 = DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->join("wg_config_minimum_standard_cycle_0312", function ($join) {
                $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_cycle');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_tracking_0312.year',
                DB::raw("SUM(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'JAN'"),
                DB::raw("SUM(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'FEB'"),
                DB::raw("SUM(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'MAR'"),
                DB::raw("SUM(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'APR'"),
                DB::raw("SUM(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'MAY'"),
                DB::raw("SUM(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'JUN'"),
                DB::raw("SUM(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'JUL'"),
                DB::raw("SUM(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'AUG'"),
                DB::raw("SUM(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'SEP'"),
                DB::raw("SUM(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'OCT'"),
                DB::raw("SUM(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'NOV'"),
                DB::raw("SUM(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'DEC'")
            )
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year)
            ->groupBy(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_customer_evaluation_minimum_standard_tracking_0312.year',
                'wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id'
            );

        $q2 = DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->select(
                DB::raw("5 AS id"),
                DB::raw("'TOTAL' AS name"),
                DB::raw("'PUNTAJE' AS abbreviation"),
                'wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_tracking_0312.year',
                DB::raw("SUM(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'JAN'"),
                DB::raw("SUM(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'FEB'"),
                DB::raw("SUM(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'MAR'"),
                DB::raw("SUM(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'APR'"),
                DB::raw("SUM(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'MAY'"),
                DB::raw("SUM(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'JUN'"),
                DB::raw("SUM(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'JUL'"),
                DB::raw("SUM(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'AUG'"),
                DB::raw("SUM(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'SEP'"),
                DB::raw("SUM(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'OCT'"),
                DB::raw("SUM(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'NOV'"),
                DB::raw("SUM(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'DEC'")
            )
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year)
            ->groupBy(
                'wg_customer_evaluation_minimum_standard_tracking_0312.year',
                'wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id'
            );

        $q1->union($q2)->mergeBindings($q2);

        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_customer_evaluation_minimum_standard_tracking_0312"))
            ->mergeBindings($q1)
            ->select(
                "wg_customer_evaluation_minimum_standard_tracking_0312.abbreviation",
                "wg_customer_evaluation_minimum_standard_tracking_0312.name",
                "wg_customer_evaluation_minimum_standard_tracking_0312.JAN",
                "wg_customer_evaluation_minimum_standard_tracking_0312.FEB",
                "wg_customer_evaluation_minimum_standard_tracking_0312.MAR",
                "wg_customer_evaluation_minimum_standard_tracking_0312.APR",
                "wg_customer_evaluation_minimum_standard_tracking_0312.MAY",
                "wg_customer_evaluation_minimum_standard_tracking_0312.JUN",
                "wg_customer_evaluation_minimum_standard_tracking_0312.JUL",
                "wg_customer_evaluation_minimum_standard_tracking_0312.AUG",
                "wg_customer_evaluation_minimum_standard_tracking_0312.SEP",
                "wg_customer_evaluation_minimum_standard_tracking_0312.OCT",
                "wg_customer_evaluation_minimum_standard_tracking_0312.NOV",
                "wg_customer_evaluation_minimum_standard_tracking_0312.DEC",
                "wg_customer_evaluation_minimum_standard_tracking_0312.id",
                "wg_customer_evaluation_minimum_standard_tracking_0312.year",
                "wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id"
            )
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year)
            ->orderBy("wg_customer_evaluation_minimum_standard_tracking_0312.id");

        $heading = [
            "CÓDIGO" => "abbreviation",
            "CICLO" => "name",
            "ENE" => "JAN",
            "FEB" => "FEB",
            "MAR" => "MAR",
            "ABR" => "APR",
            "MAY" => "MAY",
            "JUN" => "JUN",
            "JUL" => "JUL",
            "AGO" => "AUG",
            "SEP" => "SEP",
            "OCT" => "OCT",
            "NOV" => "NOV",
            "DIC" => "DEC"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getExportSummaryIndicatorData($criteria)
    {

        $q1 = $this->prepareSubQuery(1, 'Preguntas', 'items');
        $q2 = $this->prepareSubQuery(2, 'Respuestas', 'checked');
        $q3 = $this->prepareSubQuery(3, 'Cumple', 'accomplish');
        $q4 = $this->prepareSubQuery(4, 'No Aplica', 'no_apply_with_justification');
        $q5 = $this->prepareSubQuery(5, 'No Cumple', 'no_accomplish');
        //$q6 = $this->prepareSubQuery(6, 'No Aplica con Justificacion', 'no_apply_with_justification');
        $q7 = $this->prepareSubQuery(7, 'Sin Respuesta', 'no_checked');

        $q1->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year);

        $q2->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year);

        $q3->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year);

        $q4->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year);

        $q5->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year);

        $q7->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year);

        $q1->union($q2)
            ->mergeBindings($q2)
            ->union($q3)
            ->mergeBindings($q3)
            ->union($q4)
            ->mergeBindings($q4)
            ->union($q5)
            ->mergeBindings($q5)
            ->union($q7)
            ->mergeBindings($q7);

        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_customer_evaluation_minimum_standard_tracking_0312"))
            ->mergeBindings($q1)
            ->select(
                "wg_customer_evaluation_minimum_standard_tracking_0312.indicator",
                DB::raw("MAX(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'JAN'"),
                DB::raw("MAX(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'FEB'"),
                DB::raw("MAX(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'MAR'"),
                DB::raw("MAX(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'APR'"),
                DB::raw("MAX(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'MAY'"),
                DB::raw("MAX(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'JUN'"),
                DB::raw("MAX(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'JUL'"),
                DB::raw("MAX(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'AUG'"),
                DB::raw("MAX(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'SEP'"),
                DB::raw("MAX(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'OCT'"),
                DB::raw("MAX(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'NOV'"),
                DB::raw("MAX(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'DEC'"),
                "wg_customer_evaluation_minimum_standard_tracking_0312.year",
                "wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id"
            )
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year)
            ->groupBy("wg_customer_evaluation_minimum_standard_tracking_0312.indicator");

        $heading = [
            "INDICADOR" => "indicator",
            "ENE" => "JAN",
            "FEB" => "FEB",
            "MAR" => "MAR",
            "ABR" => "APR",
            "MAY" => "MAY",
            "JUN" => "JUN",
            "JUL" => "JUL",
            "AGO" => "AUG",
            "SEP" => "SEP",
            "OCT" => "OCT",
            "NOV" => "NOV",
            "DIC" => "DEC"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function prepareSubQuery($position, $label, $field)
    {
        return DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->select(
                DB::raw("$position AS position"),
                DB::raw("'$label' AS indicator"),
                'wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_tracking_0312.year',
                'wg_customer_evaluation_minimum_standard_tracking_0312.month',
                DB::raw("SUM(wg_customer_evaluation_minimum_standard_tracking_0312.$field) AS value")
            )
            ->groupBy(
                'wg_customer_evaluation_minimum_standard_tracking_0312.year',
                'wg_customer_evaluation_minimum_standard_tracking_0312.month',
                'wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id'
            );
    }

    public function prepareSubQueryDetail($position, $label, $field)
    {
        return DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->select(
                DB::raw("$position AS position"),
                DB::raw("'$label' AS indicator"),
                'wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_tracking_0312.year',
                'wg_customer_evaluation_minimum_standard_tracking_0312.month',
                'wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_cycle',
                DB::raw("SUM(wg_customer_evaluation_minimum_standard_tracking_0312.$field) AS value")
            )
            ->groupBy(
                'wg_customer_evaluation_minimum_standard_tracking_0312.year',
                'wg_customer_evaluation_minimum_standard_tracking_0312.month',
                'wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_cycle',
                'wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id'
            );
    }

    private function duplicateMonthlyReport($criteria)
    {
        $query = DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->leftjoin(DB::raw("wg_customer_evaluation_minimum_standard_tracking_0312 as wg_customer_evaluation_minimum_standard_tracking_destination_0312"), function ($join) use ($criteria) {
                $join->on('wg_customer_evaluation_minimum_standard_tracking_destination_0312.customer_evaluation_minimum_standard_id', '=', 'wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id');
                $join->where('wg_customer_evaluation_minimum_standard_tracking_destination_0312.year', '=', $criteria->toYear);
                $join->where('wg_customer_evaluation_minimum_standard_tracking_destination_0312.month', '=', $criteria->toMonth);
            })
            ->select(
                DB::raw('NULL id'),
                'wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_cycle',
                'wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_parent_id',
                'wg_customer_evaluation_minimum_standard_tracking_0312.items',
                'wg_customer_evaluation_minimum_standard_tracking_0312.checked',
                'wg_customer_evaluation_minimum_standard_tracking_0312.avg_progress',
                'wg_customer_evaluation_minimum_standard_tracking_0312.avg_total',
                'wg_customer_evaluation_minimum_standard_tracking_0312.total',
                'wg_customer_evaluation_minimum_standard_tracking_0312.accomplish',
                'wg_customer_evaluation_minimum_standard_tracking_0312.no_accomplish',
                'wg_customer_evaluation_minimum_standard_tracking_0312.no_apply_with_justification',
                'wg_customer_evaluation_minimum_standard_tracking_0312.no_apply_without_justification',
                'wg_customer_evaluation_minimum_standard_tracking_0312.no_checked',
                DB::raw('? AS year'),
                DB::raw('? AS month'),
                DB::raw("NOW() AS created_at"),
                DB::raw("? AS created_by"),
                DB::raw("NOW() AS updated_at"),
                DB::raw("? AS updated_by")
            )
            ->addBinding($criteria->toYear, "select")
            ->addBinding($criteria->toMonth, "select")
            ->addBinding($criteria->createdBy, "select")
            ->addBinding($criteria->createdBy, "select")
            ->where('wg_customer_evaluation_minimum_standard_tracking_0312.year', $criteria->fromYear)
            ->where('wg_customer_evaluation_minimum_standard_tracking_0312.month', $criteria->fromMonth)
            ->whereNull('wg_customer_evaluation_minimum_standard_tracking_destination_0312.id');

        if (isset($criteria->customerEvaluationMinimumStandardId)) {
            $query->where('wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id', $criteria->customerEvaluationMinimumStandardId);
        }

        $sql = 'INSERT INTO `wg_customer_evaluation_minimum_standard_tracking_0312` (`id`, `customer_evaluation_minimum_standard_id`, `minimum_standard_cycle`, `minimum_standard_parent_id`, `items`, `checked`, `avg_progress`, `avg_total`, `total`, `accomplish`, `no_accomplish`, `no_apply_with_justification`, `no_apply_without_justification`, `no_checked`, `year`, `month`, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    private function prepareQueryDetail($criteria)
    {
        $q1 = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_id',
                'wg_minimum_standard_parent_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->whereRaw("wg_config_minimum_standard_cycle_0312.status = 'activo'")
            ->whereRaw("wg_minimum_standard_0312.is_active = 1")
            ->whereRaw("wg_minimum_standard_item_0312.is_active =  1");

        $q2 = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_id',
                'wg_minimum_standard_parent_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->whereRaw("wg_config_minimum_standard_cycle_0312.status = 'activo'")
            ->whereRaw("wg_minimum_standard_0312.is_active = 1")
            ->whereRaw("wg_minimum_standard_item_0312.is_active =  1")
            ->whereRaw("wg_customers.id = {$criteria->customerId}");

        $q3 = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->join("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->whereRaw("wg_customer_evaluation_minimum_standard_item_0312.status = 'activo'")
            ->whereRaw("wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id = {$criteria->customerEvaluationMinimumStandardId}");

        return DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312_stats"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_minimum_standard_item_0312.id', '=', 'wg_minimum_standard_item_0312_stats.id');
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_item_0312_stats.minimum_standard_id');
                $join->on('wg_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312_stats.minimum_standard_item_id');
            })
            ->leftjoin(DB::raw("({$q3->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->mergeBindings($q3)
            ->select(
                'wg_minimum_standard_item_0312_stats.id AS minimum_standard_cycle_id',
                'wg_minimum_standard_item_0312_stats.name',
                'wg_minimum_standard_item_0312_stats.minimum_standard_id',
                'wg_minimum_standard_item_0312_stats.description',
                'wg_minimum_standard_item_0312_stats.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("COUNT(*) - SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id)
                            AND wg_minimum_standard_item_0312.id IS NOT NULL THEN 1 ELSE 0 END) AS checked"),
                DB::raw("SUM(CASE WHEN (wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac')
                                OR (wg_customer_evaluation_minimum_standard_item_0312.id IS NULL AND wg_minimum_standard_item_0312.id IS NULL)
                            THEN wg_minimum_standard_item_0312_stats.value ELSE 0 END) AS total"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nas' THEN 1 ELSE 0 END) AS no_apply_with_justification"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nac'
                                OR (wg_customer_evaluation_minimum_standard_item_0312.id IS NULL AND wg_minimum_standard_item_0312.id IS NULL)
                            THEN 1 ELSE 0 END) AS no_apply_without_justification"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) AND wg_minimum_standard_item_0312.id IS NOT NULL
                            THEN 1 ELSE 0 END) AS no_checked"),
                DB::raw("{$criteria->customerEvaluationMinimumStandardId} AS customer_evaluation_minimum_standard_id"),
                DB::raw("{$criteria->currentYear} AS current_year"),
                DB::raw("{$criteria->currentMonth} AS current_month"),
                DB::raw("NOW() AS created_at"),
                DB::raw("{$criteria->createdBy} AS created_by"),
                DB::raw("NOW() AS updated_at"),
                DB::raw("{$criteria->createdBy} AS updated_by")
            )
            //->addBinding($criteria->customerEvaluationMinimumStandardId, "select")
            //->addBinding($criteria->currentYear, "select")
            //->addBinding($criteria->currentMonth, "select")
            //->addBinding($criteria->createdBy, "select")
            //->addBinding($criteria->createdBy, "select")
            ->groupBy(
                'wg_minimum_standard_item_0312_stats.id',
                'wg_minimum_standard_item_0312_stats.name',
                'wg_minimum_standard_item_0312_stats.minimum_standard_id',
                'wg_minimum_standard_item_0312_stats.description'
            );
    }
}
