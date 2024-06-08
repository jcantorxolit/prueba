<?php

namespace AdeN\Api\Modules\Customer\OccupationalReportIncident;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;


class CustomerOccupationalReportIncidentService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getChartAccidentType($criteria)
    {
        $data = DB::table('wg_customer_occupational_report_incident')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_accident_type')), function ($join) {
                $join->on('wg_customer_occupational_report_incident.accident_type', '=', 'wg_report_accident_type.value');

            })->select(
                'wg_report_accident_type.item as label',
                DB::raw("COUNT(*) AS value"),
                DB::raw("YEAR(accident_date) AS yearValue")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->groupBy(
                'wg_customer_occupational_report_incident.customer_id',
                'wg_customer_occupational_report_incident.accident_type',
                DB::raw("YEAR(accident_date)")
            )
            ->get();

        return $this->chart->getChartPie($data);
    }

    public function getChartDeathCause($criteria)
    {
        $q1 = DB::table('wg_customer_occupational_report_incident')
            ->select(
                DB::raw("'SI' AS label"),
                DB::raw("COUNT(*) AS value"),
                DB::raw("YEAR(accident_date) AS yearValue")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->whereYear('wg_customer_occupational_report_incident.accident_death_cause', '=', 1);
            /*->groupBy(
                'wg_customer_occupational_report_incident.customer_id',                
                DB::raw("YEAR(accident_date)")
            );*/

        $q2 = DB::table('wg_customer_occupational_report_incident')
            ->select(
                DB::raw("'NO' AS label"),
                DB::raw("COUNT(*) AS value"),
                DB::raw("YEAR(accident_date) AS yearValue")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->whereYear('wg_customer_occupational_report_incident.accident_death_cause', '=', 0)
            ->groupBy(
                'wg_customer_occupational_report_incident.customer_id',
                DB::raw("YEAR(accident_date)")
            );

        $data = $q1->union($q2)->get();

        return $this->chart->getChartPie($data);
    }

    public function getChartLocation($criteria)
    {
        $data = DB::table('wg_customer_occupational_report_incident')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_location')), function ($join) {
                $join->on('wg_customer_occupational_report_incident.accident_location', '=', 'wg_report_location.value');

            })->select(
                'wg_report_location.item as label',
                DB::raw("COUNT(*) AS value"),
                DB::raw("YEAR(accident_date) AS yearValue")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->groupBy(
                'wg_customer_occupational_report_incident.customer_id',
                'wg_customer_occupational_report_incident.accident_location',
                DB::raw("YEAR(accident_date)")
            )
            ->get();

        return $this->chart->getChartPie($data);
    }

    public function getChartLink($criteria)
    {
        $data = DB::table('wg_customer_occupational_report_incident')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_employment_relationship')), function ($join) {
                $join->on('wg_customer_occupational_report_incident.customer_type_employment_relationship', '=', 'wg_report_employment_relationship.value');

            })->select(
                'wg_report_employment_relationship.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_report_incident.customer_type_employment_relationship')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartWorkTime($criteria)
    {
        $data = DB::table('wg_customer_occupational_report_incident')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_working_day')), function ($join) {
                $join->on('wg_customer_occupational_report_incident.accident_working_day', '=', 'wg_report_working_day.value');

            })->select(
                'wg_report_working_day.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_report_incident.accident_working_day')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartWeekDay($criteria)
    {
        $data = DB::table('wg_customer_occupational_report_incident')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_week_day')), function ($join) {
                $join->on('wg_customer_occupational_report_incident.accident_week_day', '=', 'wg_report_week_day.value');

            })->select(
                'wg_report_week_day.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_report_incident.accident_week_day')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartPlace($criteria)
    {
        $data = DB::table('wg_customer_occupational_report_incident')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_place')), function ($join) {
                $join->on('wg_customer_occupational_report_incident.accident_place', '=', 'wg_report_place.value');

            })->select(
                'wg_report_place.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_report_incident.accident_place')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartInjury($criteria)
    {
        $data = DB::table('wg_customer_occupational_report_incident')
            ->join("wg_customer_occupational_report_incident_lesion", function ($join) {
                $join->on('wg_customer_occupational_report_incident_lesion.customer_occupational_report_incident_id', '=', 'wg_customer_occupational_report_incident.id');

            })
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_lesion_type')), function ($join) {
                $join->on('wg_customer_occupational_report_incident_lesion.lesion_id', '=', 'wg_report_lesion_type.value');

            })->select(
                'wg_report_lesion_type.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_report_incident_lesion.lesion_id')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartBody($criteria)
    {
        $data = DB::table('wg_customer_occupational_report_incident')
            ->join("wg_customer_occupational_report_incident_body", function ($join) {
                $join->on('wg_customer_occupational_report_incident_body.customer_occupational_report_incident_id', '=', 'wg_customer_occupational_report_incident.id');

            })
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_body_part')), function ($join) {
                $join->on('wg_customer_occupational_report_incident_body.body_part_id', '=', 'wg_report_body_part.value');

            })->select(
                'wg_report_body_part.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_report_incident_body.body_part_id')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartFactor($criteria)
    {
        $data = DB::table('wg_customer_occupational_report_incident')
            ->join("wg_customer_occupational_report_incident_factor", function ($join) {
                $join->on('wg_customer_occupational_report_incident_factor.customer_occupational_report_incident_id', '=', 'wg_customer_occupational_report_incident.id');

            })
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_factor')), function ($join) {
                $join->on('wg_customer_occupational_report_incident_factor.factor_id', '=', 'wg_report_factor.value');

            })->select(
                'wg_report_factor.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_report_incident.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_report_incident_factor.factor_id')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartBar($data, $config);
    }    

    public function getChartStatus($criteria)
    {
        $data = DB::table('wg_customer_occupational_report_incident')
            ->join(DB::raw(SystemParameter::getRelationTable('report_incident_status')), function ($join) {
                $join->on('wg_customer_occupational_report_incident.status', '=', 'report_incident_status.value');

            })->select(
                'report_incident_status.item as label',
                DB::raw("COUNT(*) AS value"),
                DB::raw("YEAR(accident_date) AS yearValue")
            )
            ->where('wg_customer_occupational_report_incident.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_report_incident.accident_date', '=', $criteria->year)
            ->groupBy(
                'wg_customer_occupational_report_incident.customer_id',
                'wg_customer_occupational_report_incident.status',
                DB::raw("YEAR(accident_date)")
            )
            ->get();

        return $this->chart->getChartPie($data);
    }
}