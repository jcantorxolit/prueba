<?php

namespace AdeN\Api\Modules\Customer\RoadSafety40595\Container\RoadSafetyTracking40595;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Carbon\Carbon;
use AdeN\Api\Helpers\ExportHelper;

class CustomerRoadSafetyTracking40595Service extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function createMissingMonthlyReport($criteria)
    {
        $model = DB::table('wg_customer_road_safety_tracking_40595')
            ->select(DB::raw('MAX(`month`) `month`, MAX(`year`) `year`'))
            ->where('customer_road_safety_id', $criteria->customerRoadSafetyId)
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
        DB::table("wg_customer_road_safety_tracking_40595")
            ->where('customer_road_safety_id', $criteria->customerRoadSafetyId)
            ->where('year', $criteria->currentYear)
            ->where('month', $criteria->currentMonth)
            ->delete();

        $qDetail = $this->prepareQueryDetail($criteria);

        $query = DB::table(DB::raw("({$qDetail->toSql()}) as wg_customer_road_safety_items_40595"))
            ->mergeBindings($qDetail)
            ->leftjoin("wg_customer_road_safety_tracking_40595", function ($join) use ($criteria) {
                $join->on('wg_customer_road_safety_tracking_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_items_40595.customer_road_safety_id');
                $join->on('wg_customer_road_safety_tracking_40595.road_safety_cycle', '=', 'wg_customer_road_safety_items_40595.road_safety_cycle_id');
                $join->on('wg_customer_road_safety_tracking_40595.road_safety_parent_id', '=', 'wg_customer_road_safety_items_40595.road_safety_id');
                $join->on('wg_customer_road_safety_tracking_40595.year', '=', 'wg_customer_road_safety_items_40595.current_year');
                $join->on('wg_customer_road_safety_tracking_40595.month', '=', 'wg_customer_road_safety_items_40595.current_month');
            })
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_road_safety_items_40595.customer_road_safety_id',
                'wg_customer_road_safety_items_40595.road_safety_cycle_id',
                'wg_customer_road_safety_items_40595.road_safety_id',
                'wg_customer_road_safety_items_40595.items',
                'wg_customer_road_safety_items_40595.checked',
                DB::raw("IFNULL((wg_customer_road_safety_items_40595.checked / wg_customer_road_safety_items_40595.items) * 100, 0) AS advance"),
                DB::raw("IFNULL(wg_customer_road_safety_items_40595.total, 0) AS average"),
                'wg_customer_road_safety_items_40595.total',
                'wg_customer_road_safety_items_40595.accomplish',
                'wg_customer_road_safety_items_40595.no_accomplish',
                'wg_customer_road_safety_items_40595.no_apply_without_justification',
                'wg_customer_road_safety_items_40595.no_apply_with_justification',
                'wg_customer_road_safety_items_40595.no_checked',
                'wg_customer_road_safety_items_40595.current_year',
                'wg_customer_road_safety_items_40595.current_month',
                'wg_customer_road_safety_items_40595.created_at',
                'wg_customer_road_safety_items_40595.created_by',
                'wg_customer_road_safety_items_40595.updated_at',
                'wg_customer_road_safety_items_40595.updated_by'
            )
            ->whereNull('wg_customer_road_safety_tracking_40595.customer_road_safety_id');

        $sql = 'INSERT INTO `wg_customer_road_safety_tracking_40595` (`id`, `customer_road_safety_id`, `road_safety_cycle`, `road_safety_parent_id`, `items`, `checked`, `avg_progress`, `avg_total`, `total`, `accomplish`, `no_accomplish`, `no_apply_with_justification`, `no_apply_without_justification`, `no_checked`, `year`, `month`, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public function updateMonthlyReport($criteria)
    {
        $qDetail = $this->prepareQueryDetail($criteria);

        DB::table("wg_customer_road_safety_tracking_40595")
            ->mergeBindings($qDetail)
            ->join(DB::raw("({$qDetail->toSql()}) as wg_customer_road_safety_items_40595"), function ($join) use ($criteria) {
                $join->on('wg_customer_road_safety_tracking_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_items_40595.customer_road_safety_id');
                $join->on('wg_customer_road_safety_tracking_40595.road_safety_cycle', '=', 'wg_customer_road_safety_items_40595.road_safety_cycle_id');
                $join->on('wg_customer_road_safety_tracking_40595.road_safety_parent_id', '=', 'wg_customer_road_safety_items_40595.road_safety_id');
                $join->on('wg_customer_road_safety_tracking_40595.year', '=', 'wg_customer_road_safety_items_40595.current_year');
                $join->on('wg_customer_road_safety_tracking_40595.month', '=', 'wg_customer_road_safety_items_40595.current_month');
            })
            ->update([
                'wg_customer_road_safety_tracking_40595.items' => DB::raw('wg_customer_road_safety_items_40595.items'),
                'wg_customer_road_safety_tracking_40595.checked' => DB::raw('wg_customer_road_safety_items_40595.checked'),
                'wg_customer_road_safety_tracking_40595.avg_progress' => DB::raw("ROUND(IFNULL((wg_customer_road_safety_items_40595.checked / wg_customer_road_safety_items_40595.items) * 100, 0) ,2)"),
                'wg_customer_road_safety_tracking_40595.avg_total' => DB::raw("ROUND(IFNULL(wg_customer_road_safety_items_40595.total, 0) ,2)"),
                'wg_customer_road_safety_tracking_40595.total' => DB::raw('wg_customer_road_safety_items_40595.total'),
                'wg_customer_road_safety_tracking_40595.accomplish' => DB::raw('wg_customer_road_safety_items_40595.accomplish'),
                'wg_customer_road_safety_tracking_40595.no_accomplish' => DB::raw('wg_customer_road_safety_items_40595.no_accomplish'),
                'wg_customer_road_safety_tracking_40595.no_apply_with_justification' => DB::raw('wg_customer_road_safety_items_40595.no_apply_with_justification'),
                'wg_customer_road_safety_tracking_40595.no_apply_without_justification' => DB::raw('wg_customer_road_safety_items_40595.no_apply_without_justification'),
                'wg_customer_road_safety_tracking_40595.no_checked' => DB::raw('wg_customer_road_safety_items_40595.no_checked'),
                'wg_customer_road_safety_tracking_40595.updated_at' => Carbon::now(),
                'wg_customer_road_safety_tracking_40595.updated_by' => $criteria->updatedBy
            ]);
    }


    public function getExportSummaryCycleData($criteria)
    {
        $q1 = DB::table('wg_customer_road_safety_tracking_40595')
            ->join("wg_road_safety_cycle_40595", function ($join) {
                $join->on('wg_road_safety_cycle_40595.id', '=', 'wg_customer_road_safety_tracking_40595.road_safety_cycle');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id',
                'wg_customer_road_safety_tracking_40595.year',
                DB::raw("SUM(CASE WHEN month = 1 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JAN'"),
                DB::raw("SUM(CASE WHEN month = 2 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'FEB'"),
                DB::raw("SUM(CASE WHEN month = 3 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'MAR'"),
                DB::raw("SUM(CASE WHEN month = 4 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'APR'"),
                DB::raw("SUM(CASE WHEN month = 5 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'MAY'"),
                DB::raw("SUM(CASE WHEN month = 6 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JUN'"),
                DB::raw("SUM(CASE WHEN month = 7 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JUL'"),
                DB::raw("SUM(CASE WHEN month = 8 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'AUG'"),
                DB::raw("SUM(CASE WHEN month = 9 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'SEP'"),
                DB::raw("SUM(CASE WHEN month = 10 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'OCT'"),
                DB::raw("SUM(CASE WHEN month = 11 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'NOV'"),
                DB::raw("SUM(CASE WHEN month = 12 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'DEC'")
            )
            ->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year)
            ->groupBy(
                'wg_road_safety_cycle_40595.id',
                'wg_customer_road_safety_tracking_40595.year',
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id'
            );

        $q2 = DB::table('wg_customer_road_safety_tracking_40595')
            ->select(
                DB::raw("5 AS id"),
                DB::raw("'TOTAL' AS name"),
                DB::raw("'PUNTAJE' AS abbreviation"),
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id',
                'wg_customer_road_safety_tracking_40595.year',
                DB::raw("SUM(CASE WHEN month = 1 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JAN'"),
                DB::raw("SUM(CASE WHEN month = 2 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'FEB'"),
                DB::raw("SUM(CASE WHEN month = 3 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'MAR'"),
                DB::raw("SUM(CASE WHEN month = 4 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'APR'"),
                DB::raw("SUM(CASE WHEN month = 5 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'MAY'"),
                DB::raw("SUM(CASE WHEN month = 6 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JUN'"),
                DB::raw("SUM(CASE WHEN month = 7 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'JUL'"),
                DB::raw("SUM(CASE WHEN month = 8 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'AUG'"),
                DB::raw("SUM(CASE WHEN month = 9 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'SEP'"),
                DB::raw("SUM(CASE WHEN month = 10 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'OCT'"),
                DB::raw("SUM(CASE WHEN month = 11 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'NOV'"),
                DB::raw("SUM(CASE WHEN month = 12 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END) AS 'DEC'")
            )
            ->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year)
            ->groupBy(
                'wg_customer_road_safety_tracking_40595.year',
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id'
            );

        $q1->union($q2)->mergeBindings($q2);

        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_customer_road_safety_tracking_40595"))
            ->mergeBindings($q1)
            ->select(
                "wg_customer_road_safety_tracking_40595.abbreviation",
                "wg_customer_road_safety_tracking_40595.name",
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.JAN, 2) AS 'JAN'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.FEB, 2) AS 'FEB'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.MAR, 2) AS 'MAR'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.APR, 2) AS 'APR'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.MAY, 2) AS 'MAY'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.JUN, 2) AS 'JUN'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.JUL, 2) AS 'JUL'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.AUG, 2) AS 'AUG'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.SEP, 2) AS 'SEP'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.OCT, 2) AS 'OCT'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.NOV, 2) AS 'NOV'"),
                DB::raw("ROUND(wg_customer_road_safety_tracking_40595.DEC, 2) AS 'DEC'"),
                "wg_customer_road_safety_tracking_40595.id",
                "wg_customer_road_safety_tracking_40595.year",
                "wg_customer_road_safety_tracking_40595.customer_road_safety_id"
            )
            ->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year)
            ->orderBy("wg_customer_road_safety_tracking_40595.id");

        $heading = [
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

        $q1->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year);

        $q2->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year);

        $q3->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year);

        $q4->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year);

        $q5->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year);

        $q7->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year);

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

        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_customer_road_safety_tracking_40595"))
            ->mergeBindings($q1)
            ->select(
                "wg_customer_road_safety_tracking_40595.indicator",
                DB::raw("MAX(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'JAN'"),
                DB::raw("MAX(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'FEB'"),
                DB::raw("MAX(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'MAR'"),
                DB::raw("MAX(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'APR'"),
                DB::raw("MAX(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'MAY'"),
                DB::raw("MAX(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'JUN'"),
                DB::raw("MAX(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'JUL'"),
                DB::raw("MAX(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'AUG'"),
                DB::raw("MAX(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'SEP'"),
                DB::raw("MAX(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'OCT'"),
                DB::raw("MAX(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'NOV'"),
                DB::raw("MAX(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'DEC'"),
                "wg_customer_road_safety_tracking_40595.year",
                "wg_customer_road_safety_tracking_40595.customer_road_safety_id"
            )
            ->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year)
            ->groupBy("wg_customer_road_safety_tracking_40595.indicator");

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
        return DB::table('wg_customer_road_safety_tracking_40595')
            ->select(
                DB::raw("$position AS position"),
                DB::raw("'$label' AS indicator"),
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id',
                'wg_customer_road_safety_tracking_40595.year',
                'wg_customer_road_safety_tracking_40595.month',
                DB::raw("SUM(wg_customer_road_safety_tracking_40595.$field) AS value")
            )
            ->groupBy(
                'wg_customer_road_safety_tracking_40595.year',
                'wg_customer_road_safety_tracking_40595.month',
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id'
            );
    }

    public function prepareSubQueryDetail($position, $label, $field)
    {
        return DB::table('wg_customer_road_safety_tracking_40595')
            ->select(
                DB::raw("$position AS position"),
                DB::raw("'$label' AS indicator"),
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id',
                'wg_customer_road_safety_tracking_40595.year',
                'wg_customer_road_safety_tracking_40595.month',
                'wg_customer_road_safety_tracking_40595.road_safety_cycle',
                DB::raw("SUM(wg_customer_road_safety_tracking_40595.$field) AS value")
            )
            ->groupBy(
                'wg_customer_road_safety_tracking_40595.year',
                'wg_customer_road_safety_tracking_40595.month',
                'wg_customer_road_safety_tracking_40595.road_safety_cycle',
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id'
            );
    }

    private function duplicateMonthlyReport($criteria)
    {
        $query = DB::table('wg_customer_road_safety_tracking_40595')
            ->leftjoin(DB::raw("wg_customer_road_safety_tracking_40595 as wg_customer_road_safety_tracking_destination_40595"), function ($join) use ($criteria) {
                $join->on('wg_customer_road_safety_tracking_destination_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_tracking_40595.customer_road_safety_id');
                $join->where('wg_customer_road_safety_tracking_destination_40595.year', '=', $criteria->toYear);
                $join->where('wg_customer_road_safety_tracking_destination_40595.month', '=', $criteria->toMonth);
            })
            ->select(
                DB::raw('NULL id'),
                'wg_customer_road_safety_tracking_40595.customer_road_safety_id',
                'wg_customer_road_safety_tracking_40595.road_safety_cycle',
                'wg_customer_road_safety_tracking_40595.road_safety_parent_id',
                'wg_customer_road_safety_tracking_40595.items',
                'wg_customer_road_safety_tracking_40595.checked',
                'wg_customer_road_safety_tracking_40595.avg_progress',
                'wg_customer_road_safety_tracking_40595.avg_total',
                'wg_customer_road_safety_tracking_40595.total',
                'wg_customer_road_safety_tracking_40595.accomplish',
                'wg_customer_road_safety_tracking_40595.no_accomplish',
                'wg_customer_road_safety_tracking_40595.no_apply_with_justification',
                'wg_customer_road_safety_tracking_40595.no_apply_without_justification',
                'wg_customer_road_safety_tracking_40595.no_checked',
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
            ->where('wg_customer_road_safety_tracking_40595.year', $criteria->fromYear)
            ->where('wg_customer_road_safety_tracking_40595.month', $criteria->fromMonth)
            ->whereNull('wg_customer_road_safety_tracking_destination_40595.id');

        if (isset($criteria->customerRoadSafetyId)) {
            $query->where('wg_customer_road_safety_tracking_40595.customer_road_safety_id', $criteria->customerRoadSafetyId);
        }

        $sql = 'INSERT INTO `wg_customer_road_safety_tracking_40595` (`id`, `customer_road_safety_id`, `road_safety_cycle`, `road_safety_parent_id`, `items`, `checked`, `avg_progress`, `avg_total`, `total`, `accomplish`, `no_accomplish`, `no_apply_with_justification`, `no_apply_without_justification`, `no_checked`, `year`, `month`, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    private function prepareQueryDetail($criteria)
    {
        $q1 = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
                $join->where('wg_customer_road_safety_40595.id', '=', $criteria->customerRoadSafetyId);
                $join->on('wg_customer_road_safety_40595.size', '=', 'wg_road_safety_item_40595.size');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->whereRaw("wg_road_safety_cycle_40595.status = 'activo'")
            ->whereRaw("wg_road_safety_40595.is_active = 1")
            ->whereRaw("wg_road_safety_item_40595.is_active =  1");

        $q2 = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
                $join->where('wg_customer_road_safety_40595.id', '=', $criteria->customerRoadSafetyId);
                $join->on('wg_customer_road_safety_40595.size', '=', 'wg_road_safety_item_40595.size');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->whereRaw("wg_road_safety_cycle_40595.status = 'activo'")
            ->whereRaw("wg_road_safety_40595.is_active = 1")
            ->whereRaw("wg_road_safety_item_40595.is_active =  1");

        $q3 = DB::table('wg_customer_road_safety_item_40595')
            ->join("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->whereRaw("wg_customer_road_safety_item_40595.status = 'activo'")
            ->whereRaw("wg_customer_road_safety_item_40595.customer_road_safety_id = {$criteria->customerRoadSafetyId}");

        return DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595_stats"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_road_safety_item_40595"), function ($join) {
                $join->on('wg_road_safety_item_40595.id', '=', 'wg_road_safety_item_40595_stats.id');
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_item_40595_stats.road_safety_id');
                $join->on('wg_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595_stats.road_safety_item_id');
            })
            ->leftjoin(DB::raw("({$q3->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->mergeBindings($q3)
            ->select(
                'wg_road_safety_item_40595_stats.id AS road_safety_cycle_id',
                'wg_road_safety_item_40595_stats.name',
                'wg_road_safety_item_40595_stats.road_safety_id',
                'wg_road_safety_item_40595_stats.description',
                'wg_road_safety_item_40595_stats.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("COUNT(*) - SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id)
                            AND wg_road_safety_item_40595.id IS NOT NULL THEN 1 ELSE 0 END) AS checked"),
                DB::raw("SUM(CASE WHEN (wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac')
                                OR (wg_customer_road_safety_item_40595.id IS NULL AND wg_road_safety_item_40595.id IS NULL)
                            THEN wg_road_safety_item_40595_stats.value ELSE 0 END) AS total"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nas' THEN 1 ELSE 0 END) AS no_apply_with_justification"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nac'
                                OR (wg_customer_road_safety_item_40595.id IS NULL AND wg_road_safety_item_40595.id IS NULL)
                            THEN 1 ELSE 0 END) AS no_apply_without_justification"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) AND wg_road_safety_item_40595.id IS NOT NULL
                            THEN 1 ELSE 0 END) AS no_checked"),
                DB::raw("{$criteria->customerRoadSafetyId} AS customer_road_safety_id"),
                DB::raw("{$criteria->currentYear} AS current_year"),
                DB::raw("{$criteria->currentMonth} AS current_month"),
                DB::raw("NOW() AS created_at"),
                DB::raw("{$criteria->createdBy} AS created_by"),
                DB::raw("NOW() AS updated_at"),
                DB::raw("{$criteria->createdBy} AS updated_by")
            )
            ->groupBy(
                'wg_road_safety_item_40595_stats.id',
                'wg_road_safety_item_40595_stats.name',
                'wg_road_safety_item_40595_stats.road_safety_id',
                'wg_road_safety_item_40595_stats.description'
            );
    }
}
