<?php

namespace AdeN\Api\Modules\Customer\AbsenteeismIndicator;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Carbon\Carbon;
use AdeN\Api\Helpers\SqlHelper;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Helpers\ExportHelper;

class CustomerAbsenteeismIndicatorService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getWorkplaceList($customerId)
    {
        return DB::table('wg_customer_absenteeism_indicator')
            ->join("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_absenteeism_indicator.workCenter', '=', 'wg_customer_config_workplace.id');
                $join->on('wg_customer_absenteeism_indicator.customer_id', '=', 'wg_customer_config_workplace.customer_id');
            })
            ->select(
                'wg_customer_config_workplace.id',
                DB::raw('wg_customer_config_workplace.name as item'),
                DB::raw('wg_customer_config_workplace.id as value')
            )
            ->whereRaw('wg_customer_absenteeism_indicator.customer_id = ?', [$customerId])
            ->groupBy('wg_customer_absenteeism_indicator.workCenter', 'wg_customer_absenteeism_indicator.customer_id')
            ->orderBy('wg_customer_config_workplace.name', 'ASC')
            ->get();
    }


    //---------------------------------------------------------------------------CHART RESOLUTION 1111

    public function getChartEventNumber($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator_target')
            ->select(
                DB::raw("'Meta' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 1 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 2 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 3 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 4 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 5 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 6 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 7 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 8 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 9 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 10 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 11 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 12 THEN wg_customer_absenteeism_indicator_target.targetEvent ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_absenteeism_indicator_target.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_indicator_target.periodDate', '=', $criteria->year)
            ->groupBy(
                'wg_customer_absenteeism_indicator_target.customer_id',
                DB::raw("YEAR(wg_customer_absenteeism_indicator_target.periodDate)")
            );

        $q2 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("'Indicatores' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 1 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 2 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 3 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 4 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 5 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 6 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 7 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 8 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 9 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 10 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 11 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 12 THEN wg_customer_absenteeism_indicator.eventNumber ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_indicator.periodDate', '=', $criteria->year)
            ->where('wg_customer_absenteeism_indicator.resolution', '=', $criteria->resolution)
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria->workPlace) {
            $q2->where('wg_customer_absenteeism_indicator.workCenter', '=', $criteria->workPlace);
        }

        if ($criteria->classification) {
            $q2->where('wg_customer_absenteeism_indicator.classification', '=', $criteria->classification);
        }

        $data = $q1->union($q2)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getCharttDisabilityDays($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator_target')
            ->select(
                DB::raw("'Meta' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 1 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 2 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 3 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 4 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 5 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 6 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 7 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 8 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 9 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 10 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 11 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 12 THEN wg_customer_absenteeism_indicator_target.targetDay ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_absenteeism_indicator_target.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_indicator_target.periodDate', '=', $criteria->year)
            ->groupBy(
                'wg_customer_absenteeism_indicator_target.customer_id',
                DB::raw("YEAR(wg_customer_absenteeism_indicator_target.periodDate)")
            );

        $q2 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("'Indicatores' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 1 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 2 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 3 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 4 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 5 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 6 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 7 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 8 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 9 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 10 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 11 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 12 THEN wg_customer_absenteeism_indicator.disabilityDays ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_indicator.periodDate', '=', $criteria->year)
            ->where('wg_customer_absenteeism_indicator.resolution', '=', $criteria->resolution)
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria->workPlace) {
            $q2->where('wg_customer_absenteeism_indicator.workCenter', '=', $criteria->workPlace);
        }

        if ($criteria->classification) {
            $q2->where('wg_customer_absenteeism_indicator.classification', '=', $criteria->classification);
        }

        $data = $q1->union($q2)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getCharttIF($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator_target')
            ->select(
                DB::raw("'Meta' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 1 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 2 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 3 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 4 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 5 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 6 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 7 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 8 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 9 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 10 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 11 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 12 THEN wg_customer_absenteeism_indicator_target.targetIF ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_absenteeism_indicator_target.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_indicator_target.periodDate', '=', $criteria->year)
            ->groupBy(
                'wg_customer_absenteeism_indicator_target.customer_id',
                DB::raw("YEAR(wg_customer_absenteeism_indicator_target.periodDate)")
            );

        $q2 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("'Indicatores' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 1 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 2 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 3 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 4 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 5 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 6 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 7 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 8 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 9 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 10 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 11 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 12 THEN wg_customer_absenteeism_indicator.frequencyIndex ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_indicator.periodDate', '=', $criteria->year)
            ->where('wg_customer_absenteeism_indicator.resolution', '=', $criteria->resolution)
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria->workPlace) {
            $q2->where('wg_customer_absenteeism_indicator.workCenter', '=', $criteria->workPlace);
        }

        if ($criteria->classification) {
            $q2->where('wg_customer_absenteeism_indicator.classification', '=', $criteria->classification);
        }

        $data = $q1->union($q2)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getCharttIS($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator_target')
            ->select(
                DB::raw("'Meta' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 1 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 2 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 3 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 4 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 5 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 6 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 7 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 8 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 9 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 10 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 11 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 12 THEN wg_customer_absenteeism_indicator_target.targetIS ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_absenteeism_indicator_target.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_indicator_target.periodDate', '=', $criteria->year)
            ->groupBy(
                'wg_customer_absenteeism_indicator_target.customer_id',
                DB::raw("YEAR(wg_customer_absenteeism_indicator_target.periodDate)")
            );

        $q2 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("'Indicatores' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 1 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 2 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 3 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 4 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 5 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 6 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 7 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 8 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 9 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 10 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 11 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 12 THEN wg_customer_absenteeism_indicator.severityIndex ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_indicator.periodDate', '=', $criteria->year)
            ->where('wg_customer_absenteeism_indicator.resolution', '=', $criteria->resolution)
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria->workPlace) {
            $q2->where('wg_customer_absenteeism_indicator.workCenter', '=', $criteria->workPlace);
        }

        if ($criteria->classification) {
            $q2->where('wg_customer_absenteeism_indicator.classification', '=', $criteria->classification);
        }

        $data = $q1->union($q2)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getCharttILI($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator_target')
            ->select(
                DB::raw("'Meta' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 1 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 2 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 3 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 4 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 5 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 6 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 7 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 8 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 9 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 10 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 11 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator_target.periodDate) = 12 THEN wg_customer_absenteeism_indicator_target.targetILI ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_absenteeism_indicator_target.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_indicator_target.periodDate', '=', $criteria->year)
            ->groupBy(
                'wg_customer_absenteeism_indicator_target.customer_id',
                DB::raw("YEAR(wg_customer_absenteeism_indicator_target.periodDate)")
            );

        $q2 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("'Indicatores' AS label"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 1 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 2 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 3 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 4 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 5 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 6 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 7 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 8 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 9 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 10 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 11 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_absenteeism_indicator.periodDate) = 12 THEN wg_customer_absenteeism_indicator.disablingInjuriesIndex ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_absenteeism_indicator.periodDate', '=', $criteria->year)
            ->where('wg_customer_absenteeism_indicator.resolution', '=', $criteria->resolution)
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria->workPlace) {
            $q2->where('wg_customer_absenteeism_indicator.workCenter', '=', $criteria->workPlace);
        }

        if ($criteria->classification) {
            $q2->where('wg_customer_absenteeism_indicator.classification', '=', $criteria->classification);
        }

        $data = $q1->union($q2)->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }


    //---------------------------------------------------------------------------CONSOLIDATE RESOLUTION 0312 & 1111

    public function consolidate($id, $resolution, $userId)
    { 
        $qCalendar = DB::table(DB::raw("(SELECT @row_num := 1) x, (SELECT @prev_value := '') y, wg_customer_absenteeism_disability"))
            ->join("wg_calendar", function ($join) {
                $join->on('wg_calendar.full_date', '>=', DB::raw('DATE(wg_customer_absenteeism_disability.start)'));
                $join->on('wg_calendar.full_date', '<=', DB::raw('DATE(wg_customer_absenteeism_disability.end)'));
            })
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_absenteeism_disability.customer_employee_id');
            })
            ->select(
                'wg_customer_absenteeism_disability.id AS customer_absenteeism_disability_id',
                DB::raw("EXTRACT(YEAR_MONTH FROM wg_calendar.full_date) AS period"),
                DB::raw("YEAR(wg_calendar.full_date) AS year"),
                DB::raw("MONTH(wg_calendar.full_date) AS month"),
                DB::raw("COUNT(*) AS days"),
                DB::raw("@row_num := CASE WHEN @prev_value = wg_customer_absenteeism_disability.id THEN @row_num + 1 ELSE 1 END AS sortorder"),
                DB::raw("@prev_value := wg_customer_absenteeism_disability.id AS current_group")
            )            
            ->groupBy(
                DB::raw("EXTRACT(YEAR_MONTH FROM wg_calendar.full_date)"),
                'wg_customer_absenteeism_disability.id'
            )
            ->orderBy('wg_customer_absenteeism_disability.id');

        if ($id != null) {
            $qCalendar->where('wg_customer_employee.customer_id', $id);
        }

        if ($resolution == '1111') {
            $this->consolidateInsertResolution1111($id, $resolution, $userId, $qCalendar);
            $this->consolidateUpdateResolution1111($id, $resolution, $userId, $qCalendar);
        } else if ($resolution == '0312') {
            $currentYear = Carbon::now('America/Bogota')->format('Y');
            $previousYear = Carbon::now('America/Bogota')->subYears(10)->format('Y');

            //$qCalendar->whereIn('wg_calendar.year', [$currentYear, $previousYear]);
            $qCalendar->whereBetween('wg_calendar.year', [$previousYear, $currentYear]);

            DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");

            $sql = 'CREATE TEMPORARY TABLE calendar ' . $qCalendar->toSql();
    
            DB::statement($sql, $qCalendar->getBindings());

            $this->consolidateDeleteResolution0312($id, $resolution, $userId, $qCalendar);
            $this->consolidateInsertResolution0312($id, $resolution, $userId, $qCalendar);
            $this->consolidateUpdateResolution0312($id, $resolution, $userId, $qCalendar);
            $this->consolidateNotExistsResolution0312($id, $resolution, $userId);

            DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");
        }

        return true;
    }

    private function consolidateInsertResolution1111($id, $resolution, $userId, $qCalendar)
    {
        DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");

        $sql = 'CREATE TEMPORARY TABLE calendar ' . $qCalendar->toSql();

        DB::statement($sql, $qCalendar->getBindings());

        $qEmployee = DB::table('wg_customer_employee')
            ->select(
                'wg_customer_employee.customer_id',
                'wg_customer_employee.workPlace',
                DB::raw("COUNT(*) AS poblation")
            )
            ->groupBy('wg_customer_employee.customer_id', 'wg_customer_employee.workPlace');

        $query = DB::table('wg_customer_absenteeism_disability')
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join("calendar", function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'calendar.customer_absenteeism_disability_id');
            })
            ->join(DB::raw("({$qEmployee->toSql()}) AS wg_customer_employee_active"), function ($join) {
                $join->on('wg_customer_employee_active.customer_id', '=', 'wg_customer_employee.customer_id');
                $join->on('wg_customer_employee_active.workPlace', '=', 'wg_customer_employee.workPlace');
            })
            ->mergeBindings($qEmployee)
            ->leftjoin("wg_customer_absenteeism_indicator", function ($join) use ($resolution) {
                $join->on('wg_customer_absenteeism_indicator.classification', '=', 'wg_customer_absenteeism_disability.cause');
                $join->on('wg_customer_absenteeism_indicator.period', '=', 'calendar.period');
                $join->on('wg_customer_absenteeism_indicator.workCenter', '<=>', DB::raw('wg_customer_employee.workPlace COLLATE utf8_general_ci'));
                $join->on('wg_customer_absenteeism_indicator.customer_id', '=', 'wg_customer_employee.customer_id');
                $join->where('wg_customer_absenteeism_indicator.resolution', '=', $resolution);
            })
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_employee.customer_id',
                'wg_customer_absenteeism_disability.cause',
                'calendar.period',
                DB::raw("CONCAT(calendar.period, '01') AS periodDate"),
                'wg_customer_employee.workPlace',
                'wg_customer_employee_active.poblation',
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_disability.directCostTotal, 0)) AS directCostTotal"),
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_disability.indirectCostTotal, 0)) AS indirectCostTotal"),
                DB::raw("SUM(CASE WHEN wg_customer_absenteeism_disability.type = 'Inicial' THEN 1 ELSE 0 END) AS eventNumber"),
                DB::raw("SUM(calendar.days) AS disabilityDays"),
                DB::raw("'$resolution' AS resolution"),
                DB::raw("'$userId' AS created_by"),
                DB::raw("NOW() AS created_at")
            )
            ->where('wg_customer_employee.customer_id', $id)
            ->where('wg_customer_absenteeism_disability.category', 'Incapacidad')
            ->whereIn('wg_customer_absenteeism_disability.cause', ['EG', 'AL'])
            ->whereNull('wg_customer_absenteeism_indicator.id')
            ->groupBy(
                'wg_customer_absenteeism_disability.cause',
                'calendar.year',
                'calendar.month',
                'wg_customer_employee.workPlace'
            )
            ->orderBy('calendar.year', 'DESC')
            ->orderBy('calendar.month', 'DESC');


        $sql = 'INSERT INTO wg_customer_absenteeism_indicator (`id`, `customer_id`, `classification`, `period`, `periodDate`, `workCenter`, `population`, `directCost`, `indirectCost`, `eventNumber`, `disabilityDays`, `resolution`, `createdBy`, `created_at`) ' . $query->toSql();

        DB::statement($sql, $query->getBindings());

        DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");
    }

    private function consolidateUpdateResolution1111($id, $resolution, $userId, $qCalendar)
    {
        DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");

        $sql = 'CREATE TEMPORARY TABLE calendar ' . $qCalendar->toSql();

        DB::statement($sql, $qCalendar->getBindings());

        $currentPeriod = Carbon::now('America/Bogota')->format('Ym');
        $LastPeriod = Carbon::now('America/Bogota')->subMonth()->format('Ym');

        $query = DB::table('wg_customer_absenteeism_disability')
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join("calendar", function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'calendar.customer_absenteeism_disability_id');
            })
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_employee.customer_id',
                'wg_customer_absenteeism_disability.cause',
                'calendar.period',
                DB::raw("CONCAT(calendar.period, '01') AS periodDate"),
                DB::raw('wg_customer_absenteeism_disability.workplace_id'),
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_disability.directCostTotal, 0)) AS directCostTotal"),
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_disability.indirectCostTotal, 0)) AS indirectCostTotal"),
                DB::raw("SUM(CASE WHEN wg_customer_absenteeism_disability.type = 'Inicial' THEN 1 ELSE 0 END) AS eventNumber"),
                DB::raw("SUM(calendar.days) AS disabilityDays")
            )
            ->where('wg_customer_employee.customer_id', $id)
            ->where('wg_customer_absenteeism_disability.category', 'Incapacidad')
            ->whereIn('wg_customer_absenteeism_disability.cause', ['EG', 'AL'])
            ->groupBy(
                'wg_customer_absenteeism_disability.cause',
                'calendar.year',
                'calendar.month',
                'wg_customer_absenteeism_disability.workplace_id'
            );

        DB::table('wg_customer_absenteeism_indicator')

            ->join(DB::raw("({$query->toSql()}) AS wg_customer_absenteeism"), function ($join) {
                $join->on('wg_customer_absenteeism_indicator.classification', '=', 'wg_customer_absenteeism.cause');
                $join->on('wg_customer_absenteeism_indicator.period', '=', 'wg_customer_absenteeism.period');
                $join->on('wg_customer_absenteeism_indicator.workCenter', '<=>', 'wg_customer_absenteeism.workplace_id');
                $join->on('wg_customer_absenteeism_indicator.customer_id', '=', 'wg_customer_absenteeism.customer_id');
            })
            ->mergeBindings($query)
            ->where('wg_customer_absenteeism_indicator.resolution', '=', $resolution)
            ->whereIn('wg_customer_absenteeism_indicator.period', [$currentPeriod, $LastPeriod])
            ->whereNotNull('wg_customer_absenteeism_indicator.id')
            ->update([
                'wg_customer_absenteeism_indicator.directCost' => DB::raw("wg_customer_absenteeism.directCostTotal"),
                'wg_customer_absenteeism_indicator.indirectCost' => DB::raw("wg_customer_absenteeism.indirectCostTotal"),
                'wg_customer_absenteeism_indicator.eventNumber' => DB::raw("wg_customer_absenteeism.eventNumber"),
                'wg_customer_absenteeism_indicator.disabilityDays' => DB::raw("wg_customer_absenteeism.disabilityDays"),
                'wg_customer_absenteeism_indicator.updatedBy' => DB::raw("$userId"),
                'wg_customer_absenteeism_indicator.updated_at' => DB::raw("NOW()"),
            ]);

        DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");
    }

    private function consolidateDeleteResolution0312($id, $resolution, $userId, $qCalendar)
    {
        // DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");

        // $sql = 'CREATE TEMPORARY TABLE calendar ' . $qCalendar->toSql();

        // DB::statement($sql, $qCalendar->getBindings());

        $qEmployee = DB::table('wg_customer_employee')
            ->select(
                'wg_customer_employee.customer_id',
                DB::raw("COUNT(*) AS poblation")
            )
            ->groupBy('wg_customer_employee.customer_id');


        $qDiagnostic = $this->prepareQueryInitialELC();

        $query = DB::table('wg_customer_absenteeism_disability')
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join("calendar", function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'calendar.customer_absenteeism_disability_id');
            })
            ->join(DB::raw("({$qEmployee->toSql()}) AS wg_customer_employee_active"), function ($join) {
                $join->on('wg_customer_employee_active.customer_id', '=', 'wg_customer_employee.customer_id');
            })
            ->mergeBindings($qEmployee)
            ->leftjoin(DB::raw("({$qDiagnostic->toSql()}) AS diagnostic"), function ($join) {
                $join->on('diagnostic.cause', '=', 'wg_customer_absenteeism_disability.cause');
                $join->on('diagnostic.start_year', '=', 'calendar.year');
                $join->on('diagnostic.customer_id', '=', 'wg_customer_employee.customer_id');
            })
            ->mergeBindings($qDiagnostic)
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_employee.customer_id',
                'wg_customer_absenteeism_disability.cause',
                'calendar.period',
                DB::raw("CONCAT(calendar.period, '01') AS periodDate"),
                'wg_customer_absenteeism_disability.workplace_id',
                DB::raw("SUM(CASE WHEN wg_customer_absenteeism_disability.type = 'Inicial' AND calendar.sortorder = 1 THEN 1 ELSE 0 END) AS eventNumber"),

                DB::raw("SUM(
                CASE WHEN (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL')
                            AND wg_customer_absenteeism_disability.type = 'Inicial' AND calendar.sortorder = 1  AND wg_customer_absenteeism_disability.accidentType = 'M' THEN 1
                        WHEN `wg_customer_absenteeism_disability`.`cause` = 'EL' OR `wg_customer_absenteeism_disability`.`cause` = 'ELC' AND calendar.sortorder = 1  AND wg_customer_absenteeism_disability.accidentType = 'M' THEN 1 ELSE 0 END
            ) AS eventMortalNumber"),

                DB::raw("SUM(calendar.days) AS disabilityDays"),

                DB::raw("SUM(
                CASE WHEN (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL')
                            AND wg_customer_absenteeism_disability.type = 'Inicial'
                            AND (wg_customer_absenteeism_disability.accidentType = 'M' OR wg_customer_absenteeism_disability.accidentType = 'G') THEN IFNULL(`wg_customer_absenteeism_disability`.chargedDays,0) ELSE 0 END
            ) AS chargedDays"),

                'wg_customer_employee_active.poblation',
                'diagnostic.diagnosticAll',
                DB::raw("'$resolution' AS resolution"),
                DB::raw("'$userId' AS created_by"),
                DB::raw("NOW() AS created_at")
            )

            ->where('wg_customer_employee.customer_id', $id)
            //->whereIn('wg_customer_absenteeism_disability.cause', ['EG', 'LM', 'LP', 'EL', 'AL', 'ELC'])
            ->whereIn('wg_customer_absenteeism_disability.cause', ['EG', 'AL', 'AT', 'ELC'])
            ->groupBy(
                'wg_customer_absenteeism_disability.cause',
                'calendar.year',
                'calendar.month',
                'wg_customer_absenteeism_disability.workplace_id'
            );

        DB::table('wg_customer_absenteeism_indicator')
            ->leftjoin(DB::raw("({$query->toSql()}) AS wg_customer_absenteeism_disability"), function ($join) {
                $join->on('wg_customer_absenteeism_disability.cause', '=', 'wg_customer_absenteeism_indicator.classification');
                $join->on('wg_customer_absenteeism_disability.period', '=', 'wg_customer_absenteeism_indicator.period');
                $join->on('wg_customer_absenteeism_disability.workplace_id', '<=>', DB::raw('wg_customer_absenteeism_indicator.workCenter COLLATE utf8_general_ci'));
                $join->on('wg_customer_absenteeism_disability.customer_id', '=', 'wg_customer_absenteeism_indicator.customer_id');
                $join->on('wg_customer_absenteeism_disability.resolution', '=', 'wg_customer_absenteeism_indicator.resolution');
            })
            ->mergeBindings($query)
            ->where('wg_customer_absenteeism_indicator.customer_id', $id)
            ->whereNull('wg_customer_absenteeism_disability.customer_id')
            ->delete();


        //DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");
    }

    private function consolidateInsertResolution0312($id, $resolution, $userId, $qCalendar)
    {
        // DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");

        // $sql = 'CREATE TEMPORARY TABLE calendar ' . $qCalendar->toSql();

        // DB::statement($sql, $qCalendar->getBindings());

        $qEmployee = DB::table('wg_customer_employee')
            ->select(
                'wg_customer_employee.customer_id',
                DB::raw("COUNT(*) AS poblation")
            )
            ->groupBy('wg_customer_employee.customer_id');


        $qDiagnostic = $this->prepareQueryInitialELC();

        $query = DB::table('wg_customer_absenteeism_disability')
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join("calendar", function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'calendar.customer_absenteeism_disability_id');
            })
            ->join(DB::raw("({$qEmployee->toSql()}) AS wg_customer_employee_active"), function ($join) {
                $join->on('wg_customer_employee_active.customer_id', '=', 'wg_customer_employee.customer_id');
            })
            ->mergeBindings($qEmployee)
            ->leftjoin(DB::raw("({$qDiagnostic->toSql()}) AS diagnostic"), function ($join) {
                $join->on('diagnostic.cause', '=', 'wg_customer_absenteeism_disability.cause');
                $join->on('diagnostic.start_year', '=', 'calendar.year');
                $join->on('diagnostic.customer_id', '=', 'wg_customer_employee.customer_id');
            })
            ->mergeBindings($qDiagnostic)
            ->leftjoin("wg_customer_absenteeism_indicator", function ($join) use ($resolution) {
                $join->on('wg_customer_absenteeism_indicator.classification', '=', 'wg_customer_absenteeism_disability.cause');
                $join->on('wg_customer_absenteeism_indicator.period', '=', 'calendar.period');
                $join->on('wg_customer_absenteeism_indicator.workCenter', '<=>', DB::raw('wg_customer_absenteeism_disability.workplace_id'));
                $join->on('wg_customer_absenteeism_indicator.customer_id', '=', 'wg_customer_employee.customer_id');
                $join->where('wg_customer_absenteeism_indicator.resolution', '=', $resolution);
            })
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_employee.customer_id',
                'wg_customer_absenteeism_disability.cause',
                'calendar.period',
                DB::raw("CONCAT(calendar.period, '01') AS periodDate"),
                'wg_customer_absenteeism_disability.workplace_id',
                DB::raw("SUM(
                    CASE WHEN wg_customer_absenteeism_disability.type = 'Inicial' AND calendar.sortorder = 1 THEN 1
                        WHEN wg_customer_absenteeism_disability.type = 'Sin Incapacidad'
                            AND (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL')
                            AND wg_customer_absenteeism_disability.accidentType = 'M'
                            AND (wg_customer_absenteeism_disability.customer_absenteeism_disability_parent_id IS NULL OR wg_customer_absenteeism_disability.customer_absenteeism_disability_parent_id = 0)
                            AND calendar.sortorder = 1 THEN 1
                        WHEN wg_customer_absenteeism_disability.type = 'Sin Incapacidad'
                            AND (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL'  OR `wg_customer_absenteeism_disability`.`cause` = 'ELC')
                            AND wg_customer_absenteeism_disability.accidentType IN ('L') THEN 1
                    ELSE 0 END
                ) AS eventNumber"),

                DB::raw("SUM(
                    CASE WHEN (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL')
                                AND (wg_customer_absenteeism_disability.type = 'Inicial' OR wg_customer_absenteeism_disability.type = 'Sin Incapacidad')
                                AND calendar.sortorder = 1
                                AND wg_customer_absenteeism_disability.accidentType = 'M' THEN 1
                            WHEN (`wg_customer_absenteeism_disability`.`cause` = 'EL' OR `wg_customer_absenteeism_disability`.`cause` = 'ELC')
                                AND calendar.sortorder = 1
                                AND wg_customer_absenteeism_disability.accidentType = 'M' THEN 1
                            ELSE 0 END
                ) AS eventMortalNumber"),

                DB::raw("SUM(IF(wg_customer_absenteeism_disability.type = 'Sin Incapacidad', 0, calendar.days)) AS disabilityDays"),

                DB::raw("SUM(
                    CASE WHEN (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL')
                                AND wg_customer_absenteeism_disability.type = 'Inicial'
                                AND (wg_customer_absenteeism_disability.accidentType = 'M' OR wg_customer_absenteeism_disability.accidentType = 'G') THEN IFNULL(`wg_customer_absenteeism_disability`.chargedDays,0)
                        WHEN (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL' OR `wg_customer_absenteeism_disability`.`cause` = 'ELC')
                                AND (wg_customer_absenteeism_disability.type = 'Sin Incapacidad')
                                AND (wg_customer_absenteeism_disability.accidentType = 'M') THEN IFNULL(`wg_customer_absenteeism_disability`.chargedDays,0)
                    ELSE 0 END
                ) AS chargedDays"),

                'wg_customer_employee_active.poblation',
                'diagnostic.diagnosticAll',
                DB::raw("'$resolution' AS resolution"),
                DB::raw("'$userId' AS created_by"),
                DB::raw("NOW() AS created_at")
            )

            ->where('wg_customer_employee.customer_id', $id)
            //->whereIn('wg_customer_absenteeism_disability.cause', ['EG', 'LM', 'LP', 'EL', 'AL', 'ELC'])
            ->whereIn('wg_customer_absenteeism_disability.cause', ['EG', 'AL', 'AT', 'ELC'])
            ->whereNull('wg_customer_absenteeism_indicator.id')
            ->groupBy(
                'wg_customer_absenteeism_disability.cause',
                'calendar.year',
                'calendar.month',
                'wg_customer_absenteeism_disability.workplace_id'
            )
            ->orderBy('calendar.year', 'DESC')
            ->orderBy('calendar.month', 'DESC');



        $sql = 'INSERT INTO wg_customer_absenteeism_indicator (`id`, `customer_id`, `classification`, `period`, `periodDate`, `workCenter`, `eventNumber`, `eventMortalNumber`, `disabilityDays`, `chargedDays`, `employeeQuantity`, `diagnosticAll`, `resolution`, `createdBy`, `created_at`) ' . $query->toSql();

        DB::statement($sql, $query->getBindings());

        //DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");
    }

    private function consolidateUpdateResolution0312($id, $resolution, $userId, $qCalendar)
    {
        // DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");

        // $sql = 'CREATE TEMPORARY TABLE calendar ' . $qCalendar->toSql();

        // DB::statement($sql, $qCalendar->getBindings());

        $qDiagnostic = $this->prepareQueryInitialELC();

        //$currentPeriod = Carbon::now('America/Bogota')->format('Ym');
        //$LastPeriod = Carbon::now('America/Bogota')->subMonth()->format('Ym');

        $query = DB::table('wg_customer_absenteeism_disability')
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->join("calendar", function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'calendar.customer_absenteeism_disability_id');
            })
            ->leftjoin(DB::raw("({$qDiagnostic->toSql()}) AS diagnostic"), function ($join) {
                $join->on('diagnostic.cause', '=', 'wg_customer_absenteeism_disability.cause');
                $join->on('diagnostic.start_year', '=', 'calendar.year');
                $join->on('diagnostic.customer_id', '=', 'wg_customer_employee.customer_id');
            })
            ->mergeBindings($qDiagnostic)
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_employee.customer_id',
                'wg_customer_absenteeism_disability.cause',
                'calendar.period',
                'calendar.year',
                'calendar.month',
                DB::raw("CONCAT(calendar.period, '01') AS periodDate"),
                'wg_customer_absenteeism_disability.workplace_id',
                //DB::raw('wg_customer_employee.workPlace COLLATE utf8_general_ci AS workPlace'),
                DB::raw("SUM(
                    CASE WHEN wg_customer_absenteeism_disability.type = 'Inicial' AND calendar.sortorder = 1 THEN 1
                        WHEN wg_customer_absenteeism_disability.type = 'Sin Incapacidad'
                            AND (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL')
                            AND wg_customer_absenteeism_disability.accidentType = 'M'
                            AND (wg_customer_absenteeism_disability.customer_absenteeism_disability_parent_id IS NULL OR wg_customer_absenteeism_disability.customer_absenteeism_disability_parent_id = 0)
                            AND calendar.sortorder = 1 THEN 1
                        WHEN wg_customer_absenteeism_disability.type = 'Sin Incapacidad'
                            AND (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL' OR `wg_customer_absenteeism_disability`.`cause` = 'ELC')
                            AND wg_customer_absenteeism_disability.accidentType IN ('L') THEN 1
                    ELSE 0 END
                ) AS eventNumber"),

                DB::raw("SUM(
                    CASE WHEN (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL')
                                AND (wg_customer_absenteeism_disability.type = 'Inicial' OR wg_customer_absenteeism_disability.type = 'Sin Incapacidad')
                                AND calendar.sortorder = 1
                                AND wg_customer_absenteeism_disability.accidentType = 'M' THEN 1
                            WHEN (`wg_customer_absenteeism_disability`.`cause` = 'EL' OR `wg_customer_absenteeism_disability`.`cause` = 'ELC')
                                AND calendar.sortorder = 1
                                AND wg_customer_absenteeism_disability.accidentType = 'M' THEN 1
                            ELSE 0 END
                ) AS eventMortalNumber"),

                DB::raw("SUM(IF(wg_customer_absenteeism_disability.type = 'Sin Incapacidad', 0, calendar.days)) AS disabilityDays"),

                DB::raw("SUM(
                    CASE WHEN (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL')
                                AND wg_customer_absenteeism_disability.type = 'Inicial'
                                AND (wg_customer_absenteeism_disability.accidentType = 'M' OR wg_customer_absenteeism_disability.accidentType = 'G') THEN IFNULL(`wg_customer_absenteeism_disability`.chargedDays,0)
                        WHEN (`wg_customer_absenteeism_disability`.`cause` = 'AT' OR `wg_customer_absenteeism_disability`.`cause` = 'AL' OR `wg_customer_absenteeism_disability`.`cause` = 'ELC')
                                AND (wg_customer_absenteeism_disability.type = 'Sin Incapacidad')
                                AND (wg_customer_absenteeism_disability.accidentType = 'M') THEN IFNULL(`wg_customer_absenteeism_disability`.chargedDays,0)
                    ELSE 0 END
                ) AS chargedDays"),

                'diagnostic.diagnosticAll'
            )

            ->where('wg_customer_employee.customer_id', $id)
            //->whereIn('wg_customer_absenteeism_disability.cause', ['EG', 'LM', 'LP', 'EL', 'AL', 'ELC'])
            ->whereRaw("wg_customer_absenteeism_disability.cause IN ('EG', 'AL', 'AT', 'ELC')")
            ->groupBy(
                'wg_customer_absenteeism_disability.cause',
                'calendar.year',
                'calendar.month',
                'wg_customer_absenteeism_disability.workplace_id'
            );

        DB::table('wg_customer_absenteeism_indicator')

            ->join(DB::raw("({$query->toSql()}) AS wg_customer_absenteeism"), function ($join) {
                $join->on('wg_customer_absenteeism_indicator.classification', '=', 'wg_customer_absenteeism.cause');
                $join->on('wg_customer_absenteeism_indicator.period', '=', 'wg_customer_absenteeism.period');
                $join->on('wg_customer_absenteeism_indicator.workCenter', '<=>', 'wg_customer_absenteeism.workplace_id');
                $join->on('wg_customer_absenteeism_indicator.customer_id', '=', 'wg_customer_absenteeism.customer_id');
            })
            ->mergeBindings($qCalendar)
            ->mergeBindings($query)
            ->whereRaw("wg_customer_absenteeism_indicator.resolution = '$resolution'")
            //->whereIn('wg_customer_absenteeism_indicator.period', [$currentPeriod, $LastPeriod])
            ->whereNotNull('wg_customer_absenteeism_indicator.id')
            ->update([
                'wg_customer_absenteeism_indicator.eventNumber' => DB::raw("wg_customer_absenteeism.eventNumber"),
                'wg_customer_absenteeism_indicator.eventMortalNumber' => DB::raw("wg_customer_absenteeism.eventMortalNumber"),
                'wg_customer_absenteeism_indicator.disabilityDays' => DB::raw("wg_customer_absenteeism.disabilityDays"),
                'wg_customer_absenteeism_indicator.chargedDays' => DB::raw("wg_customer_absenteeism.chargedDays"),
                'wg_customer_absenteeism_indicator.diagnosticAll' => DB::raw("wg_customer_absenteeism.diagnosticAll"),
                'wg_customer_absenteeism_indicator.updatedBy' => DB::raw("$userId"),
                'wg_customer_absenteeism_indicator.updated_at' => DB::raw("NOW()"),
            ]);

        //DB::statement("DROP TEMPORARY TABLE IF EXISTS calendar");
    }

    private function consolidateNotExistsResolution0312($customerId, $resolution, $userId)
    {     
        $currentDate = Carbon::now('America/Bogota')->format('Y-d-d');
        $statement = "TL_Consolidate_0312(" . $customerId . ", " . $userId . ", '" . $currentDate . "')";
        DB::statement('CALL ' . $statement);
    }

    private function prepareQueryInitialELC()
    {
        $qInnerInitialELC = DB::table('wg_customer_absenteeism_disability')
            ->join("wg_customer_employee", function ($join) {
                $join->on('wg_customer_absenteeism_disability.customer_employee_id', '=', 'wg_customer_employee.id');
            })
            ->select(
                'wg_customer_employee.customer_id',
                'wg_customer_absenteeism_disability.cause',
                DB::raw("YEAR(wg_customer_absenteeism_disability.`start`) AS start_year"),
                DB::raw("YEAR(wg_customer_absenteeism_disability.`end`) AS end_year"),
                DB::raw("COUNT(*) AS diagnosticAll")
            )
            ->whereRaw("wg_customer_absenteeism_disability.cause = 'ELC'")
            ->whereRaw("wg_customer_absenteeism_disability.type IN ('Inicial', 'Sin Incapacidad')")
            ->groupBy(
                'wg_customer_employee.customer_id',
                'wg_customer_absenteeism_disability.cause',
                'wg_customer_absenteeism_disability.type',
                DB::raw("YEAR(wg_customer_absenteeism_disability.`start`)")
            );

        return $this->prepareQuery($qInnerInitialELC->toSql(), 'disability_diagnostic')
            ->mergeBindings($qInnerInitialELC)
            ->select(
                'disability_diagnostic.customer_id',
                'disability_diagnostic.cause',
                'disability_diagnostic.start_year',
                DB::raw("SUM(disability_diagnostic.diagnosticAll) AS diagnosticAll")
            )
            ->groupBy(
                'disability_diagnostic.customer_id',
                'disability_diagnostic.cause',
                'disability_diagnostic.start_year'
            );
    }


    //---------------------------------------------------------------------------CHART RESOLUTION 0312

    public function getChartFrequencyAccidentality($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS yearValue"),
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventNumber) AS eventNumber"),
                DB::raw("wg_customer_absenteeism_indicator.employeeQuantity"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventNumber) / wg_customer_absenteeism_indicator.employeeQuantity * 100 AS result")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['AT', 'AL'])
            ->whereIn(DB::raw('YEAR(wg_customer_absenteeism_indicator.periodDate)'), $criteria->yearList)
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)"),
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        $data = $this->prepareQuery($q1->toSql(), 'wg_customer_absenteeism_indicator')
            ->mergeBindings($q1)
            ->select(
                DB::raw('wg_customer_absenteeism_indicator.yearValue AS label'),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 1 THEN wg_customer_absenteeism_indicator.result END) 'JAN'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 2 THEN wg_customer_absenteeism_indicator.result END) 'FEB'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 3 THEN wg_customer_absenteeism_indicator.result END) 'MAR'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 4 THEN wg_customer_absenteeism_indicator.result END) 'APR'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 5 THEN wg_customer_absenteeism_indicator.result END) 'MAY'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 6 THEN wg_customer_absenteeism_indicator.result END) 'JUN'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 7 THEN wg_customer_absenteeism_indicator.result END) 'JUL'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 8 THEN wg_customer_absenteeism_indicator.result END) 'AUG'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 9 THEN wg_customer_absenteeism_indicator.result END) 'SEP'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 10 THEN wg_customer_absenteeism_indicator.result END) 'OCT'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 11 THEN wg_customer_absenteeism_indicator.result END) 'NOV'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 12 THEN wg_customer_absenteeism_indicator.result END) 'DEC'")
            )
            ->groupBy(
                'wg_customer_absenteeism_indicator.yearValue'
            )
            ->orderBy('wg_customer_absenteeism_indicator.yearValue', 'DESC')
            ->get();


        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getChartSeverityAccidentality($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS yearValue"),
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.disabilityDays) AS disabilityDays"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.chargedDays) AS chargedDays"),
                DB::raw("wg_customer_absenteeism_indicator.employeeQuantity"),
                DB::raw("(SUM(wg_customer_absenteeism_indicator.disabilityDays) + SUM(wg_customer_absenteeism_indicator.chargedDays)) / wg_customer_absenteeism_indicator.employeeQuantity * 100 AS result")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['AT', 'AL'])
            ->whereIn(DB::raw('YEAR(wg_customer_absenteeism_indicator.periodDate)'), $criteria->yearList)
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)"),
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        $data = $this->prepareQuery($q1->toSql(), 'wg_customer_absenteeism_indicator')
            ->mergeBindings($q1)
            ->select(
                DB::raw('wg_customer_absenteeism_indicator.yearValue AS label'),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 1 THEN wg_customer_absenteeism_indicator.result END) 'JAN'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 2 THEN wg_customer_absenteeism_indicator.result END) 'FEB'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 3 THEN wg_customer_absenteeism_indicator.result END) 'MAR'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 4 THEN wg_customer_absenteeism_indicator.result END) 'APR'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 5 THEN wg_customer_absenteeism_indicator.result END) 'MAY'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 6 THEN wg_customer_absenteeism_indicator.result END) 'JUN'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 7 THEN wg_customer_absenteeism_indicator.result END) 'JUL'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 8 THEN wg_customer_absenteeism_indicator.result END) 'AUG'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 9 THEN wg_customer_absenteeism_indicator.result END) 'SEP'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 10 THEN wg_customer_absenteeism_indicator.result END) 'OCT'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 11 THEN wg_customer_absenteeism_indicator.result END) 'NOV'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 12 THEN wg_customer_absenteeism_indicator.result END) 'DEC'")
            )
            ->groupBy(
                'wg_customer_absenteeism_indicator.yearValue'
            )
            ->orderBy('wg_customer_absenteeism_indicator.yearValue', 'DESC')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getChartMortalProportionAccidentality($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS yearValue"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) AS eventMortalNumber"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventNumber) AS eventNumber"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) / SUM(wg_customer_absenteeism_indicator.eventNumber) * 100 AS result")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['AL', 'AT'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            //->whereIn(DB::raw('YEAR(wg_customer_absenteeism_indicator.periodDate)'), $criteria->yearList)
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        $data = $this->prepareQuery($q1->toSql(), 'wg_customer_absenteeism_indicator')
            ->mergeBindings($q1)
            ->select(
                DB::raw('wg_customer_absenteeism_indicator.yearValue AS label'),
                DB::raw("MAX(wg_customer_absenteeism_indicator.result) AS value")
            )
            ->groupBy(
                'wg_customer_absenteeism_indicator.yearValue'
            )
            ->orderBy('wg_customer_absenteeism_indicator.yearValue', 'DESC')
            ->get();

        $config = array(
            "labelColumn" => ['Periodo'],
            "valueColumns" => [
                ['labelField' => 'label', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartAbsenteeismMedicalCause($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS yearValue"),
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.disabilityDays) AS disabilityDays"),
                DB::raw("wg_customer_absenteeism_indicator.programedDays AS programedDays"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.disabilityDays) / wg_customer_absenteeism_indicator.programedDays * 100 AS result")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            //->whereIn('wg_customer_absenteeism_indicator.classification', ['EG', 'EL', 'AL', 'ELC'])
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['EG', 'AT', 'AL', 'ELC'])
            ->whereIn(DB::raw('YEAR(wg_customer_absenteeism_indicator.periodDate)'), $criteria->yearList)
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)"),
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        $data = $this->prepareQuery($q1->toSql(), 'wg_customer_absenteeism_indicator')
            ->mergeBindings($q1)
            ->select(
                DB::raw('wg_customer_absenteeism_indicator.yearValue AS label'),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 1 THEN wg_customer_absenteeism_indicator.result END) 'JAN'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 2 THEN wg_customer_absenteeism_indicator.result END) 'FEB'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 3 THEN wg_customer_absenteeism_indicator.result END) 'MAR'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 4 THEN wg_customer_absenteeism_indicator.result END) 'APR'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 5 THEN wg_customer_absenteeism_indicator.result END) 'MAY'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 6 THEN wg_customer_absenteeism_indicator.result END) 'JUN'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 7 THEN wg_customer_absenteeism_indicator.result END) 'JUL'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 8 THEN wg_customer_absenteeism_indicator.result END) 'AUG'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 9 THEN wg_customer_absenteeism_indicator.result END) 'SEP'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 10 THEN wg_customer_absenteeism_indicator.result END) 'OCT'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 11 THEN wg_customer_absenteeism_indicator.result END) 'NOV'"),
                DB::raw("MAX(CASE WHEN wg_customer_absenteeism_indicator.monthValue = 12 THEN wg_customer_absenteeism_indicator.result END) 'DEC'")
            )
            ->groupBy(
                'wg_customer_absenteeism_indicator.yearValue'
            )
            ->orderBy('wg_customer_absenteeism_indicator.yearValue', 'DESC')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getChartOccupationalDiseaseFatalityRate($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS yearValue"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) AS eventMortalNumber"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventNumber) AS eventNumber"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) / SUM(wg_customer_absenteeism_indicator.eventNumber) * 100 AS result")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
            //->whereIn(DB::raw('YEAR(wg_customer_absenteeism_indicator.periodDate)'), $criteria->yearList)
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        $data = $this->prepareQuery($q1->toSql(), 'wg_customer_absenteeism_indicator')
            ->mergeBindings($q1)
            ->select(
                DB::raw('wg_customer_absenteeism_indicator.yearValue AS label'),
                DB::raw("MAX(wg_customer_absenteeism_indicator.result) AS `value`")
            )
            ->groupBy(
                'wg_customer_absenteeism_indicator.yearValue'
            )
            ->orderBy('wg_customer_absenteeism_indicator.yearValue', 'DESC')
            ->get();

        $config = array(
            "labelColumn" => ['Periodo'],
            "valueColumns" => [
                ['labelField' => 'label', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartOccupationalDiseasePrevalence($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                'wg_customer_absenteeism_indicator.customer_id AS customer',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS yearValue"),
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                DB::raw("wg_customer_absenteeism_indicator.diagnosticAll + IFNULL((SELECT SUM(diagnosticAll)
                            FROM (SELECT IFNULL(MAX(wg_customer_absenteeism_indicator.diagnosticAll), 0) AS diagnosticAll,
                                            customer_id,
                                            classification,
                                            resolution,
                                            YEAR(wg_customer_absenteeism_indicator.periodDate) AS year_value
                                        FROM  wg_customer_absenteeism_indicator
                                        GROUP BY customer_id, classification, resolution, year_value
                        ) wg_customer_absenteeism_indicator
                            WHERE `wg_customer_absenteeism_indicator`.`customer_id` = customer
                                AND `wg_customer_absenteeism_indicator`.`classification` IN ('ELC')
                                AND `wg_customer_absenteeism_indicator`.`resolution` = '0312'
                                AND wg_customer_absenteeism_indicator.year_value < yearValue
                    ), 0) diagnosticAll"),
                DB::raw("SUM(`wg_customer_absenteeism_indicator`.`employeeQuantity`) AS employeeQuantity"),
                DB::raw("COUNT(*) qty")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
            //->whereIn(DB::raw('YEAR(wg_customer_absenteeism_indicator.periodDate)'), $criteria->yearList)
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            /*->whereIn('wg_customer_absenteeism_indicator.id', function ($query) use ($criteria) {
                $query->select(DB::raw("MAX(id)"))
                    ->from('wg_customer_absenteeism_indicator')
                    ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
                    ->where('wg_customer_absenteeism_indicator.resolution', '0312')
                    ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
                    ->groupBy(DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)"));
            })*/
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)"),
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        $data = $this->prepareQuery($q1->toSql(), 'wg_customer_absenteeism_indicator')
            ->mergeBindings($q1)
            ->select(
                DB::raw('wg_customer_absenteeism_indicator.yearValue AS label'),
                DB::raw("wg_customer_absenteeism_indicator.diagnosticAll / (wg_customer_absenteeism_indicator.employeeQuantity / wg_customer_absenteeism_indicator.qty) * 100000 AS value")
            )
            ->groupBy(
                'wg_customer_absenteeism_indicator.yearValue'
            )
            ->orderBy('wg_customer_absenteeism_indicator.yearValue', 'DESC')
            ->get();

        $config = array(
            "labelColumn" => ['Periodo'],
            "valueColumns" => [
                ['labelField' => 'label', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartOccupationalDiseaseIncidence($criteria)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS yearValue"),
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                'wg_customer_absenteeism_indicator.diagnosticAll AS diagnosticNew',
                DB::raw("SUM(`wg_customer_absenteeism_indicator`.`employeeQuantity`) AS employeeQuantity"),
                DB::raw("COUNT(*) qty")
            )
            ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
            //->whereIn(DB::raw('YEAR(wg_customer_absenteeism_indicator.periodDate)'), $criteria->yearList)
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            /*->whereIn('wg_customer_absenteeism_indicator.id', function ($query) use ($criteria) {
                $query->select(DB::raw("MAX(id)"))
                    ->from('wg_customer_absenteeism_indicator')
                    ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
                    ->where('wg_customer_absenteeism_indicator.resolution', '0312')
                    ->where('wg_customer_absenteeism_indicator.customer_id', $criteria->customerId)
                    ->groupBy(DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)"));
            })*/
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)"),
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        $data = $this->prepareQuery($q1->toSql(), 'wg_customer_absenteeism_indicator')
            ->mergeBindings($q1)
            ->select(
                DB::raw('wg_customer_absenteeism_indicator.yearValue AS label'),
                DB::raw("wg_customer_absenteeism_indicator.diagnosticNew / (wg_customer_absenteeism_indicator.employeeQuantity / wg_customer_absenteeism_indicator.qty) * 100000 AS value")
            )
            ->groupBy(
                'wg_customer_absenteeism_indicator.yearValue'
            )
            ->orderBy('wg_customer_absenteeism_indicator.yearValue', 'DESC')
            ->get();

        $config = array(
            "labelColumn" => ['Periodo'],
            "valueColumns" => [
                ['labelField' => 'label', 'field' => 'value']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }


    //---------------------------------------------------------------------------EXPORT

    public function getExportParent($criteria = null)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->join("wg_customer_config_workplace", function ($join) {
                $join->on('wg_customer_absenteeism_indicator.workCenter', '=', 'wg_customer_config_workplace.id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_indicator_period')), function ($join) {
                $join->on('wg_customer_absenteeism_indicator.period', '=', 'absenteeism_indicator_period.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_causes')), function ($join) {
                $join->on('wg_customer_absenteeism_indicator.classification', '=', 'absenteeism_disability_causes.value');
            })
            ->select(
                "absenteeism_disability_causes.item AS classification",
                "absenteeism_indicator_period.item AS period",
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_indicator.disabilityDays, 0)) AS disabilityDays"),
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_indicator.eventNumber, 0)) AS eventNumber"),
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_indicator.chargedDays, 0)) AS chargedDays"),
                DB::raw("SUM(IFNULL(wg_customer_absenteeism_indicator.eventMortalNumber, 0)) AS eventMortalNumber"),
                DB::raw("IFNULL(wg_customer_absenteeism_indicator.programedDays, 0) AS programedDays"),
                //DB::raw("SUM(IFNULL(wg_customer_absenteeism_indicator.programedDays, 0)) AS programedDays"),
                "wg_customer_absenteeism_indicator.employeeQuantity",
                "wg_customer_absenteeism_indicator.classification AS cause",
                "wg_customer_absenteeism_indicator.customer_id AS customerId",
                "wg_customer_absenteeism_indicator.resolution",
                "wg_customer_absenteeism_indicator.period AS periodCode"
            )
            ->groupBy(
                'wg_customer_absenteeism_indicator.classification',
                'wg_customer_absenteeism_indicator.period',
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.resolution'
            );


        $query = $this->prepareQuery($q1->toSql());

        $this->applyWhere($query, $criteria);

        $result = $query->orderBy('period', 'DESC')->get();

        $heading = [
            "CLASIFICACIÓN" => "classification",
            "PERIODO" => "period",
            "DÍAS INCAPACIDAD" => "disabilityDays",
            "EVENTOS" => "eventNumber",
            "DÍAS CARGADOS" => "chargedDays",
            "MORTALES" => "eventMortalNumber",
            "DÍAS PROGRAMADOS" => "programedDays",
            "EMPLEADOS" => "employeeQuantity"
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getExportFrequencyAccidentalityData($criteria = null)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                DB::raw("SUM(`wg_customer_absenteeism_indicator`.`eventNumber`) AS eventNumber"),
                'wg_customer_absenteeism_indicator.employeeQuantity',
                DB::raw("SUM((wg_customer_absenteeism_indicator.eventNumber / wg_customer_absenteeism_indicator.employeeQuantity) * 100) AS result")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['AL', 'AT'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == 'year') {
                        $q1->whereYear(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.periodDate'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = DB::table(DB::raw(SystemParameter::getRelationTable('month', 'wg_month')))
            ->leftjoin(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_indicator"), function ($join) {
                $join->on('wg_customer_absenteeism_indicator.monthValue', '=', 'wg_month.value');
            })
            ->select(
                'wg_month.item AS month',
                'wg_customer_absenteeism_indicator.eventNumber',
                'wg_customer_absenteeism_indicator.employeeQuantity',
                'wg_customer_absenteeism_indicator.result'
            )
            ->mergeBindings($q1)
            ->orderBy(DB::raw("CONVERT(wg_month.value, SIGNED)"));

        $result = $query->get();

        $heading = [
            "MES" => "month",
            "EVENTOS" => "eventNumber",
            "EMPLEADOS" => "employeeQuantity",
            "RESULTADO" => "result"
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getExportSeverityAccidentalityData($criteria = null)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.disabilityDays) AS disabilityDays"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.chargedDays) AS chargedDays"),
                'wg_customer_absenteeism_indicator.employeeQuantity',
                DB::raw("(SUM(wg_customer_absenteeism_indicator.disabilityDays) + SUM(wg_customer_absenteeism_indicator.chargedDays)) / wg_customer_absenteeism_indicator.employeeQuantity * 100 AS result")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['AL', 'AT'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == 'year') {
                        $q1->whereYear(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.periodDate'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = DB::table(DB::raw(SystemParameter::getRelationTable('month', 'wg_month')))
            ->leftjoin(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_indicator"), function ($join) {
                $join->on('wg_customer_absenteeism_indicator.monthValue', '=', 'wg_month.value');
            })
            ->select(
                'wg_month.item AS month',
                'wg_customer_absenteeism_indicator.disabilityDays',
                'wg_customer_absenteeism_indicator.chargedDays',
                'wg_customer_absenteeism_indicator.employeeQuantity',
                'wg_customer_absenteeism_indicator.result'
            )
            ->mergeBindings($q1)
            ->orderBy(DB::raw("CONVERT(wg_month.value, SIGNED)"));

        $result = $query->get();

        $heading = [
            "MES" => "month",
            "DÍAS INCAPACIDAD" => "disabilityDays",
            "DÍAS CARGADOS" => "chargedDays",
            "EMPLEADOS" => "employeeQuantity",
            "RESULTADO" => "result"
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getExportMortalProportionAccidentalityData($criteria = null)
    {
        $query = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS year"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) AS eventMortalNumber"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventNumber) AS eventNumber"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) / SUM(wg_customer_absenteeism_indicator.eventNumber) * 100 AS result")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['AL', 'AT'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            )
            ->orderBy(DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)"), "DESC");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $query->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == 'year') {
                        $query->whereYear(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.periodDate'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $result = $query->get();

        $heading = [
            "AÑO" => "year",
            "MORTALES" => "eventMortalNumber",
            "EVENTOS" => "eventNumber",
            "RESULTADO" => "result"
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getExportAbsenteeismMedicalCause($criteria = null)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS monthValue"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.disabilityDays) AS disabilityDays"),
                DB::raw("wg_customer_absenteeism_indicator.programedDays AS programedDays"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.disabilityDays) / wg_customer_absenteeism_indicator.programedDays * 100 AS result")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['EG', 'LM', 'LP', 'EL', 'AL'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == 'year') {
                        $q1->whereYear(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.periodDate'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = DB::table(DB::raw(SystemParameter::getRelationTable('month', 'wg_month')))
            ->leftjoin(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_indicator"), function ($join) {
                $join->on('wg_customer_absenteeism_indicator.monthValue', '=', 'wg_month.value');
            })
            ->select(
                'wg_month.item AS month',
                'wg_customer_absenteeism_indicator.disabilityDays',
                'wg_customer_absenteeism_indicator.programedDays',
                'wg_customer_absenteeism_indicator.result'
            )
            ->mergeBindings($q1)
            ->orderBy(DB::raw("CONVERT(wg_month.value, SIGNED)"));

        $result = $query->get();

        $heading = [
            "MES" => "month",
            "DÍAS INCAPACIDAD" => "disabilityDays",
            "DÍAS PROGRAMADOS" => "programedDays",
            "RESULTADO" => "result"
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getExportOccupationalDiseaseFatalityRate($criteria = null)
    {
        $query = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS `year`"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) AS eventMortalNumber"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventNumber) AS eventNumber"),
                DB::raw("SUM(wg_customer_absenteeism_indicator.eventMortalNumber) / SUM(wg_customer_absenteeism_indicator.eventNumber) * 100 AS result")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            )
            ->orderBy(DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)"), "DESC");

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $query->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    } else if ($item->field == 'year') {
                        $query->whereYear(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.periodDate'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $result = $query->get();

        $heading = [
            "AÑO" => "year",
            "MORTALES" => "eventMortalNumber",
            "EVENTOS" => "eventNumber",
            "RESULTADO" => "result"
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getExportOccupationalDiseasePrevalence($criteria = null)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                'wg_customer_absenteeism_indicator.customer_id AS customer',
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS year"),
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS month"),
                DB::raw("wg_customer_absenteeism_indicator.diagnosticAll + IFNULL((SELECT SUM(diagnosticAll)
                            FROM (SELECT IFNULL(MAX(wg_customer_absenteeism_indicator.diagnosticAll), 0) AS diagnosticAll,
                                            customer_id,
                                            classification,
                                            resolution,
                                            YEAR(wg_customer_absenteeism_indicator.periodDate) AS year_value
                                        FROM  wg_customer_absenteeism_indicator
                                        GROUP BY customer_id, classification, resolution, year_value
                        ) wg_customer_absenteeism_indicator
                            WHERE `wg_customer_absenteeism_indicator`.`customer_id` = customer
                                AND `wg_customer_absenteeism_indicator`.`classification` IN ('ELC')
                                AND `wg_customer_absenteeism_indicator`.`resolution` = '0312'
                                AND wg_customer_absenteeism_indicator.year_value < year
                    ), 0) diagnosticAll"),
                DB::raw("SUM(`wg_customer_absenteeism_indicator`.`employeeQuantity`) AS employeeQuantity"),
                DB::raw("COUNT(*) qty")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            /*->whereIn('wg_customer_absenteeism_indicator.id', function ($query) use ($criteria) {
                $query->select(DB::raw("MAX(id)"))
                    ->from('wg_customer_absenteeism_indicator')
                    ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
                    ->where('wg_customer_absenteeism_indicator.resolution', '0312')
                    ->groupBy(DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)"));
                if ($criteria != null) {
                    if ($criteria->mandatoryFilters != null) {
                        foreach ($criteria->mandatoryFilters as $item) {
                            if ($item->field == 'customerId') {
                                $query->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                            }
                        }
                    }
                }
            })*/
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)"),
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = DB::table(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_indicator"))
            ->mergeBindings($q1);

        $query
            ->select(
                'wg_customer_absenteeism_indicator.year',
                'wg_customer_absenteeism_indicator.diagnosticAll',
                DB::raw("wg_customer_absenteeism_indicator.employeeQuantity / wg_customer_absenteeism_indicator.qty AS employeeQuantity"),
                DB::raw("wg_customer_absenteeism_indicator.diagnosticAll / (wg_customer_absenteeism_indicator.employeeQuantity / wg_customer_absenteeism_indicator.qty) * 100000 AS result")
            )
            ->groupBy('wg_customer_absenteeism_indicator.year')
            ->orderBy('wg_customer_absenteeism_indicator.year', "DESC");

        $result = $query->get();

        $heading = [
            "AÑO" => "year",
            "CASOS" => "diagnosticAll",
            "EMPLEADOS" => "employeeQuantity",
            "RESULTADO" => "result"
        ];

        return ExportHelper::headings($result, $heading);
    }

    public function getExportOccupationalDiseaseIncidence($criteria = null)
    {
        $q1 = DB::table('wg_customer_absenteeism_indicator')
            ->select(
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate) AS year"),
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate) AS month"),
                'wg_customer_absenteeism_indicator.diagnosticAll AS diagnosticNew',
                DB::raw("SUM(`wg_customer_absenteeism_indicator`.`employeeQuantity`) AS employeeQuantity"),
                DB::raw("COUNT(*) qty")
            )
            ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
            ->where('wg_customer_absenteeism_indicator.resolution', '0312')
            /*->whereIn('wg_customer_absenteeism_indicator.id', function ($query) use ($criteria) {
                $query->select(DB::raw("MAX(id)"))
                    ->from('wg_customer_absenteeism_indicator')
                    ->whereIn('wg_customer_absenteeism_indicator.classification', ['ELC'])
                    ->where('wg_customer_absenteeism_indicator.resolution', '0312')
                    ->groupBy(DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)"));
                if ($criteria != null) {
                    if ($criteria->mandatoryFilters != null) {
                        foreach ($criteria->mandatoryFilters as $item) {
                            if ($item->field == 'customerId') {
                                $query->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                            }
                        }
                    }
                }
            })*/
            ->groupBy(
                'wg_customer_absenteeism_indicator.customer_id',
                'wg_customer_absenteeism_indicator.classification',
                //DB::raw("MONTH(wg_customer_absenteeism_indicator.periodDate)"),
                DB::raw("YEAR(wg_customer_absenteeism_indicator.periodDate)")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerId') {
                        $q1->where(SqlHelper::getPreparedField('wg_customer_absenteeism_indicator.customer_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = DB::table(DB::raw("({$q1->toSql()}) AS wg_customer_absenteeism_indicator"))
            ->mergeBindings($q1);

        $query
            ->select(
                'wg_customer_absenteeism_indicator.year',
                'wg_customer_absenteeism_indicator.diagnosticNew',
                DB::raw("wg_customer_absenteeism_indicator.employeeQuantity / wg_customer_absenteeism_indicator.qty AS employeeQuantity"),
                DB::raw("wg_customer_absenteeism_indicator.diagnosticNew / (wg_customer_absenteeism_indicator.employeeQuantity / wg_customer_absenteeism_indicator.qty) * 100000 AS result")
            )
            ->groupBy('wg_customer_absenteeism_indicator.year')
            ->orderBy('wg_customer_absenteeism_indicator.year', "DESC");

        $result = $query->get();

        $heading = [
            "AÑO" => "year",
            "CASOS" => "diagnosticNew",
            "EMPLEADOS" => "employeeQuantity",
            "RESULTADO" => "result"
        ];

        return ExportHelper::headings($result, $heading);
    }
}
