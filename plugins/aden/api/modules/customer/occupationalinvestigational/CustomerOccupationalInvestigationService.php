<?php

namespace AdeN\Api\Modules\Customer\OccupationalInvestigationAl;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\AbsenteeismDisability\CustomerAbsenteeismDisabilityModel;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Modules\Customer\Employee\CustomerEmployeeModel;
use DB;
use Wgroup\SystemParameter\SystemParameter;
use Log;

class CustomerOccupationalInvestigationService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getChartAccidentType($criteria)
    {
        $data = DB::table('wg_customer_occupational_investigation_al')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_accident_type')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.accident_type', '=', 'wg_report_accident_type.value');
            })->select(
                'wg_report_accident_type.item as label',
                DB::raw("COUNT(*) AS value"),
                DB::raw("YEAR(accident_date) AS yearValue")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->groupBy(
                'wg_customer_occupational_investigation_al.customer_id',
                'wg_customer_occupational_investigation_al.accident_type',
                DB::raw("YEAR(accident_date)")
            )
            ->get();

        return $this->chart->getChartPie($data);
    }

    public function getChartDeathCause($criteria)
    {
        $q1 = DB::table('wg_customer_occupational_investigation_al')
            ->select(
                DB::raw("'SI' AS label"),
                DB::raw("COUNT(*) AS value"),
                DB::raw("YEAR(accident_date) AS yearValue")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->where('wg_customer_occupational_investigation_al.accident_death_cause', '=', 1);
        /*->groupBy(
        'wg_customer_occupational_investigation_al.customer_id',
        DB::raw("YEAR(accident_date)")
        );*/

        $q2 = DB::table('wg_customer_occupational_investigation_al')
            ->select(
                DB::raw("'NO' AS label"),
                DB::raw("COUNT(*) AS value"),
                DB::raw("YEAR(accident_date) AS yearValue")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->where('wg_customer_occupational_investigation_al.accident_death_cause', '=', 0)
            ->groupBy(
                'wg_customer_occupational_investigation_al.customer_id',
                DB::raw("YEAR(accident_date)")
            );

        $data = $q1->union($q2)->get();

        return $this->chart->getChartPie($data);
    }

    public function getChartLocation($criteria)
    {
        $data = DB::table('wg_customer_occupational_investigation_al')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_location')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.accident_location', '=', 'wg_report_location.value');
            })->select(
                'wg_report_location.item as label',
                DB::raw("COUNT(*) AS value"),
                DB::raw("YEAR(accident_date) AS yearValue")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->groupBy(
                'wg_customer_occupational_investigation_al.customer_id',
                'wg_customer_occupational_investigation_al.accident_location',
                DB::raw("YEAR(accident_date)")
            )
            ->get();

        return $this->chart->getChartPie($data);
    }

    public function getChartLink($criteria)
    {
        $data = DB::table('wg_customer_occupational_investigation_al')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_employment_relationship')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.customer_type_employment_relationship', '=', 'wg_report_employment_relationship.value');
            })->select(
                'wg_report_employment_relationship.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_investigation_al.customer_type_employment_relationship')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartWorkTime($criteria)
    {
        $data = DB::table('wg_customer_occupational_investigation_al')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_regular_work')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.accident_regular_work', '=', 'wg_report_regular_work.value');
            })->select(
                'wg_report_regular_work.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_investigation_al.accident_regular_work')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartWeekDay($criteria)
    {
        $data = DB::table('wg_customer_occupational_investigation_al')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_week_day')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.accident_week_day', '=', 'wg_report_week_day.value');
            })->select(
                'wg_report_week_day.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_investigation_al.accident_week_day')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartPlace($criteria)
    {
        $data = DB::table('wg_customer_occupational_investigation_al')
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_place')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.accident_place', '=', 'wg_report_place.value');
            })->select(
                'wg_report_place.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_investigation_al.accident_place')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartInjury($criteria)
    {
        $data = DB::table('wg_customer_occupational_investigation_al')
            ->join("wg_customer_occupational_investigation_al_lesion", function ($join) {
                $join->on('wg_customer_occupational_investigation_al_lesion.customer_occupational_report_al_id', '=', 'wg_customer_occupational_investigation_al.id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_lesion_type')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_lesion.lesion_id', '=', 'wg_report_lesion_type.value');
            })->select(
                'wg_report_lesion_type.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_investigation_al_lesion.lesion_id')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartBody($criteria)
    {
        $data = DB::table('wg_customer_occupational_investigation_al')
            ->join("wg_customer_occupational_investigation_al_body", function ($join) {
                $join->on('wg_customer_occupational_investigation_al_body.customer_occupational_report_al_id', '=', 'wg_customer_occupational_investigation_al.id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_body_part')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_body.body_part_id', '=', 'wg_report_body_part.value');
            })->select(
                'wg_report_body_part.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_investigation_al_body.body_part_id')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartFactor($criteria)
    {
        $data = DB::table('wg_customer_occupational_investigation_al')
            ->join("wg_customer_occupational_investigation_al_factor", function ($join) {
                $join->on('wg_customer_occupational_investigation_al_factor.customer_occupational_report_al_id', '=', 'wg_customer_occupational_investigation_al.id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_factor')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_factor.factor_id', '=', 'wg_report_factor.value');
            })->select(
                'wg_report_factor.item AS label',
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 1 THEN 1 ELSE 0 END) 'JAN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 2 THEN 1 ELSE 0 END) 'FEB'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 3 THEN 1 ELSE 0 END) 'MAR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 4 THEN 1 ELSE 0 END) 'APR'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 5 THEN 1 ELSE 0 END) 'MAY'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 6 THEN 1 ELSE 0 END) 'JUN'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 7 THEN 1 ELSE 0 END) 'JUL'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 8 THEN 1 ELSE 0 END) 'AUG'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 9 THEN 1 ELSE 0 END) 'SEP'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 10 THEN 1 ELSE 0 END) 'OCT'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 11 THEN 1 ELSE 0 END) 'NOV'"),
                DB::raw("SUM(CASE WHEN MONTH(wg_customer_occupational_investigation_al.accident_date) = 12 THEN 1 ELSE 0 END) 'DEC'")
            )
            ->where('wg_customer_occupational_investigation_al.customer_id', $criteria->customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accident_date', '=', $criteria->year)
            ->groupBy('wg_customer_occupational_investigation_al_factor.factor_id')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries(),
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getExportData($criteria = null)
    {
        $id = CriteriaHelper::getMandatoryFilter($criteria, 'customerOccupationalInvestigationAlId');

        $query = DB::table('wg_customer_occupational_investigation_al')
            ->leftjoin("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_occupational_investigation_al.customer_id');
            })
            ->leftjoin(DB::raw(CustomerModel::getRelationInfoDetail('customer_info_detail', $criteria->customerId)), function ($join) {
                $join->on('wg_customers.id', '=', 'customer_info_detail.entityId');
            })
            ->leftjoin("wg_customer_employee", function ($join) {
                $join->on('wg_customer_employee.id', '=', 'wg_customer_occupational_investigation_al.customer_employee_id');
            })
            ->leftjoin("wg_employee", function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin(DB::raw(CustomerEmployeeModel::getRelationInfoDetail('employee_info_detail', $criteria->customerEmployeeId)), function ($join) {
                $join->on('wg_employee.id', '=', 'employee_info_detail.entityId');
            })
            ->leftjoin("wg_customer_config_job", function ($join) {
                $join->on('wg_customer_config_job.id', '=', 'wg_customer_employee.job');
            })
            ->leftjoin("wg_customer_config_job_data", function ($join) {
                $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('eps')), function ($join) {
                $join->on('wg_employee.eps', '=', 'eps.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('afp')), function ($join) {
                $join->on('wg_employee.afp', '=', 'afp.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('arl')), function ($join) {
                $join->on('wg_employee.arl', '=', 'arl.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_report_accident_type')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.accidentType', '=', 'wg_report_accident_type.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('occupational_investigation_status')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.status', '=', 'occupational_investigation_status.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_type_linkage')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.employeeLinkType', '=', 'wg_type_linkage.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_report_working_day')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.accidentWorkingDay', '=', 'wg_report_working_day.value');
            })
            ->leftjoin(DB::raw("rainlab_user_states us"), function ($join) {
                $join->on('wg_employee.state_id', '=', 'us.id');
            })
            ->leftjoin(DB::raw("rainlab_user_states usc"), function ($join) {
                $join->on('wg_customers.state_id', '=', 'usc.id');
            })
            ->leftjoin(DB::raw("rainlab_user_states usb"), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.customer_branch_state_id', '=', 'usb.id');
            })
            ->leftjoin(DB::raw("rainlab_user_states usa"), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.accident_state_id', '=', 'usa.id');
            })
            ->leftjoin("wg_investigation_economic_activity", function ($join) {
                $join->on('wg_customer_occupational_investigation_al.customerPrincipalEconomicActivity', '=', 'wg_investigation_economic_activity.id');
            })
            ->leftjoin(DB::raw("wg_investigation_economic_activity wg_investigation_economic_activity_branch"), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.customerBranchEconomicActivity', '=', 'wg_investigation_economic_activity_branch.id');
            })
            ->leftjoin(DB::raw("wg_towns t"), function ($join) {
                $join->on('wg_employee.city_id', '=', 't.id');
            })
            ->leftjoin(DB::raw("wg_towns tc"), function ($join) {
                $join->on('wg_customers.city_id', '=', 'tc.id');
            })
            ->leftjoin(DB::raw("wg_towns tcb"), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.customer_branch_city_id', '=', 'tcb.id');
            })
            ->leftjoin(DB::raw("wg_towns tca"), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.accident_city_id', '=', 'tca.id');
            })
            ->leftjoin(DB::raw(CustomerOccupationalInvestigationModel::getRelationInfoDetail('branch_info_detail', $id ? $id->value : null)), function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'branch_info_detail.entityId');
            })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_occupational_investigation_al.createdBy', '=', 'users.id');
            })
            ->select(
                "wg_customer_occupational_investigation_al.id",
                DB::raw("eps.item AS eps"),
                DB::raw("eps.code AS eps_code"),
                DB::raw("arl.item AS arl"),
                DB::raw("arl.id AS arl_id"),
                DB::raw("arl.code AS arl_code"),
                DB::raw("afp.item AS afp"),
                DB::raw("afp.code AS afp_code"),
                DB::raw("1 AS employment_relationship"),

                DB::raw("wg_investigation_economic_activity.name AS economic_activity_customer"),
                DB::raw("wg_investigation_economic_activity.code AS economic_activity_customer_code"),
                DB::raw("wg_customers.businessName AS customer_business_name"),
                DB::raw("wg_customers.documentType AS customer_document_type"),
                DB::raw("wg_customers.documentNumber AS customer_document_number"),
                DB::raw("customer_info_detail.address AS customer_address"),
                DB::raw("customer_info_detail.email AS customer_email"),
                DB::raw("customer_info_detail.telephone AS customer_telephone"),
                DB::raw("customer_info_detail.fax AS customer_fax"),
                DB::raw("usc.name AS customer_state"),
                DB::raw("usc.value AS customer_state_code"),
                DB::raw("tc.name AS customer_city"),
                DB::raw("tc.code customer_city_code"),
                DB::raw("wg_customer_occupational_investigation_al.customerPrincipalZone AS customer_zone"),

                DB::raw("wg_customer_occupational_investigation_al.customerIsWorkingInHq AS is_customer_branch_same"),
                DB::raw("wg_investigation_economic_activity_branch.name AS economic_activity_branch"),
                DB::raw("wg_investigation_economic_activity_branch.code AS economic_activity_branch_code"),
                DB::raw("branch_info_detail.address AS customer_branch_address"),
                DB::raw("branch_info_detail.telephone AS customer_branch_telephone"),
                DB::raw("branch_info_detail.fax AS customer_branch_fax"),
                DB::raw("usb.name AS customer_branch_state"),
                DB::raw("usb.value AS customer_branch_state_code"),
                DB::raw("tcb.name AS customer_branch_city"),
                DB::raw("tcb.code customer_branch_city_code"),
                DB::raw("wg_customer_occupational_investigation_al.customerPrincipalZone customer_branch_zone"),

                DB::raw("wg_type_linkage.value AS type_linkage"),
                DB::raw("wg_type_linkage.code AS type_linkage_code"),

                DB::raw("wg_employee.lastName AS first_lastname"),
                DB::raw("wg_employee.firstName AS first_name"),
                DB::raw("wg_employee.documentType AS document_type"),
                DB::raw("wg_employee.documentNumber AS document_number"),
                DB::raw("DATE_FORMAT(wg_employee.birthdate, '%d') AS birth_day"),
                DB::raw("DATE_FORMAT(wg_employee.birthdate, '%m') AS birth_month"),
                DB::raw("YEAR(wg_employee.birthdate) AS birth_year"),
                "wg_employee.gender",
                DB::raw("employee_info_detail.address AS address"),
                DB::raw("employee_info_detail.telephone AS telephone"),
                DB::raw("employee_info_detail.fax AS fax"),
                DB::raw("us.name AS employee_state"),
                DB::raw("us.value AS employee_state_code"),
                DB::raw("t.name AS employee_city"),
                DB::raw("t.code AS employee_city_code"),
                DB::raw("wg_customer_occupational_investigation_al.employeeZone AS zone"),

                DB::raw("wg_customer_config_job_data.name AS job"),
                DB::raw("wg_customer_occupational_investigation_al.employeeHabitualOccupation AS employee_occupation"),
                DB::raw("wg_customer_occupational_investigation_al.employeeHabitualOccupationCode AS employee_occupation_code"),
                DB::raw("wg_customer_occupational_investigation_al.employeeHabitualOccupationTime AS occupation_time_day"),
                DB::raw("wg_customer_occupational_investigation_al.employeeDuration AS employee_duration_time"),
                "wg_customer_employee.salary",

                DB::raw("DATE_FORMAT(wg_customer_occupational_investigation_al.accidentDate, '%d') AS accident_day"),
                DB::raw("DATE_FORMAT(wg_customer_occupational_investigation_al.accidentDate, '%m') AS accident_month"),
                DB::raw("YEAR(wg_customer_occupational_investigation_al.accidentDate) AS accident_year"),
                DB::raw("DATE_FORMAT(wg_customer_occupational_investigation_al.accidentDate, '%H') AS accident_hour"),
                DB::raw("DATE_FORMAT(wg_customer_occupational_investigation_al.accidentDate, '%i') AS accident_minute"),
                DB::raw("DAYOFWEEK(wg_customer_occupational_investigation_al.accidentDate) AS accident_week_day"),

                DB::raw("wg_customer_occupational_investigation_al.accidentWorkingDay AS accident_working_day"),
                DB::raw("wg_customer_occupational_investigation_al.accidentOtherRegularWorkText AS report_regular_task"),
                DB::raw("wg_customer_occupational_investigation_al.accidentOtherRegularWorkTextCode AS report_regular_task_code"),
                DB::raw("wg_customer_occupational_investigation_al.accidentWorkTimeHour AS accident_work_time_hour"),
                DB::raw("wg_customer_occupational_investigation_al.accidentWorkTimeMinute AS accident_work_time_minute"),
                DB::raw("wg_customer_occupational_investigation_al.accidentCategory AS accident_type"),
                DB::raw("wg_customer_occupational_investigation_al.accidentIsDeathCause AS accident_death_cause"),
                DB::raw("wg_customer_occupational_investigation_al.accidentIsRegularWork AS accident_regular_work"),

                DB::raw("usa.name AS accident_state"),
                DB::raw("usa.value AS accident_state_code"),
                DB::raw("tca.name AS accident_city"),
                DB::raw("tca.code accident_city_code"),
                DB::raw("wg_customer_occupational_investigation_al.accidentZone AS accident_zone"),
                DB::raw("wg_customer_occupational_investigation_al.accidentLocation AS accident_location"),
                DB::raw("wg_customer_occupational_investigation_al.accidentPlace AS accident_place"),
                DB::raw("wg_customer_occupational_investigation_al.accidentInjuryTypeText AS accident_description"),

                DB::raw("DATE_FORMAT(wg_customer_occupational_investigation_al.reportDate, '%d') AS report_day"),
                DB::raw("DATE_FORMAT(wg_customer_occupational_investigation_al.reportDate, '%m') AS report_month"),
                DB::raw("YEAR(wg_customer_occupational_investigation_al.reportDate) AS report_year"),

                DB::raw("DATE_FORMAT(wg_customer_occupational_investigation_al.notificationArlDate, '%d') AS arl_notification_day"),
                DB::raw("DATE_FORMAT(wg_customer_occupational_investigation_al.notificationArlDate, '%m') AS arl_notification_month"),
                DB::raw("YEAR(wg_customer_occupational_investigation_al.notificationArlDate) AS arl_notification_year"),

                DB::raw("DATE_FORMAT(wg_customer_occupational_investigation_al.notificationDocumentDate, '%d') AS document_notification_day"),
                DB::raw("DATE_FORMAT(wg_customer_occupational_investigation_al.notificationDocumentDate, '%m') AS document_notification_month"),
                DB::raw("YEAR(wg_customer_occupational_investigation_al.notificationDocumentDate) AS document_notification_year"),

                DB::raw("wg_customer_occupational_investigation_al.id AS customerOccupationalInvestigationAlId")
            );

        $query = $this->prepareQuery($query->toSql());

        $this->applyWhere($query, $criteria);

        Log::info("export data::" . $query->toSql());

        $report = (array)$query->first();

        $report['witnessList'] = $this->getWitnesses($criteria);
        $report['acts'] = $this->getCauses($criteria, 'AI');
        $report['conditions'] = $this->getCauses($criteria, 'CI');
        $report['works'] = $this->getCauses($criteria, 'FT');
        $report['personals'] = $this->getCauses($criteria, 'FP');
        $report['measureList'] = $this->getMeasurements($criteria);
        $report['responsibleList'] = $this->getResponsibles($criteria);

        Log::info('Numero de responsables::' . count($report['responsibleList']));

        $model = SystemParameter::find($report['arl_id']);

        $report['arl_url'] = $model != null && $model->logo != null ? CmsHelper::getUrlSite() . $model->logo->path : '';
        $report['themeUrl'] = CmsHelper::getThemeUrl();
        $report['themePath'] = CmsHelper::getThemePath();

        $injury = $this->convertToArray($this->getInjuryData($criteria));
        $body = $this->convertToArray($this->getBodyData($criteria));
        $factor = $this->convertToArray($this->getFactorData($criteria));
        $mechanism = $this->convertToArray($this->getMechanismData($criteria));

        $data = array_merge($report, $injury, $body, $factor, $mechanism);

        return $data;
    }

    public function getBodyData($criteria)
    {
        $qSub = DB::table('wg_customer_occupational_investigation_al_body')
            ->join('wg_customer_occupational_investigation_al', function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_body.customer_occupational_investigation_id');
            })
            ->select(
                'wg_customer_occupational_investigation_al_body.body_part_id'
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerOccupationalInvestigationAlId') {
                        $qSub->where(SqlHelper::getPreparedField('wg_customer_occupational_investigation_al_body.customer_occupational_investigation_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = DB::table(DB::raw(SystemParameter::getRelationTable('wg_report_body_part', 'parameter')))
            ->join(DB::raw("({$qSub->toSql()}) as category"), function ($join) {
                $join->on('parameter.value', '=', 'category.body_part_id');
            })
            ->select(
                DB::raw("CONCAT('body_', REPLACE(parameter.value, '.', '_'))  AS value"),
                DB::raw("CASE WHEN category.body_part_id IS NOT NULL THEN 1 ELSE 0 END selected")
            )
            ->mergeBindings($qSub);

        return $query->get();
    }

    public function getFactorData($criteria)
    {
        $qSub = DB::table('wg_customer_occupational_investigation_al_factor')
            ->join('wg_customer_occupational_investigation_al', function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_factor.customer_occupational_investigation_id');
            })
            ->select(
                'wg_customer_occupational_investigation_al_factor.factor_id'
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerOccupationalInvestigationAlId') {
                        $qSub->where(SqlHelper::getPreparedField('wg_customer_occupational_investigation_al_factor.customer_occupational_investigation_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = DB::table(DB::raw(SystemParameter::getRelationTable('wg_report_factor', 'parameter')))
            ->join(DB::raw("({$qSub->toSql()}) as category"), function ($join) {
                $join->on('parameter.value', '=', 'category.factor_id');
            })
            ->select(
                DB::raw("CONCAT('factor_', REPLACE(parameter.value, '.', '_'))  AS value"),
                DB::raw("CASE WHEN category.factor_id IS NOT NULL THEN 1 ELSE 0 END selected")
            )
            ->mergeBindings($qSub);

        return $query->get();
    }

    public function getInjuryData($criteria)
    {
        $qSub = DB::table('wg_customer_occupational_investigation_al_lesion')
            ->join('wg_customer_occupational_investigation_al', function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_lesion.customer_occupational_investigation_id');
            })
            ->select(
                'wg_customer_occupational_investigation_al_lesion.lesion_id'
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerOccupationalInvestigationAlId') {
                        $qSub->where(SqlHelper::getPreparedField('wg_customer_occupational_investigation_al_lesion.customer_occupational_investigation_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = DB::table(DB::raw(SystemParameter::getRelationTable('wg_report_lesion_type', 'parameter')))
            ->join(DB::raw("({$qSub->toSql()}) as category"), function ($join) {
                $join->on('parameter.value', '=', 'category.lesion_id');
            })
            ->select(
                DB::raw("CONCAT('lesion_', REPLACE(parameter.value, '.', '_'))  AS value"),
                DB::raw("CASE WHEN category.lesion_id IS NOT NULL THEN 1 ELSE 0 END selected")
            )
            ->mergeBindings($qSub);

        return $query->get();
    }

    public function getMechanismData($criteria)
    {
        $qSub = DB::table('wg_customer_occupational_investigation_al_mechanism')
            ->join('wg_customer_occupational_investigation_al', function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_mechanism.customer_occupational_investigation_id');
            })
            ->select(
                'wg_customer_occupational_investigation_al_mechanism.mechanism_id'
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerOccupationalInvestigationAlId') {
                        $qSub->where(SqlHelper::getPreparedField('wg_customer_occupational_investigation_al_mechanism.customer_occupational_investigation_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        $query = DB::table(DB::raw(SystemParameter::getRelationTable('wg_report_mechanism', 'parameter')))
            ->join(DB::raw("({$qSub->toSql()}) as category"), function ($join) {
                $join->on('parameter.value', '=', 'category.mechanism_id');
            })
            ->select(
                DB::raw("CONCAT('mechanism_', REPLACE(parameter.value, '.', '_'))  AS value"),
                DB::raw("CASE WHEN category.mechanism_id IS NOT NULL THEN 1 ELSE 0 END selected")
            )
            ->mergeBindings($qSub);

        return $query->get();
    }

    public function getWitnesses($criteria)
    {
        $query = DB::table('wg_customer_occupational_investigation_al_witness')
            ->join('wg_customer_occupational_investigation_al', function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_witness.customer_occupational_investigation_id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('investigation_testimony_type')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_witness.type', '=', 'investigation_testimony_type.value');
            })
            ->select(
                'investigation_testimony_type.item AS type',
                'wg_customer_occupational_investigation_al_witness.isWatching',
                'wg_customer_occupational_investigation_al_witness.name',
                'wg_customer_occupational_investigation_al_witness.document_type',
                'wg_customer_occupational_investigation_al_witness.document_number',
                'wg_customer_occupational_investigation_al_witness.job',
                'wg_customer_occupational_investigation_al_witness.story'
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerOccupationalInvestigationAlId') {
                        $query->where(SqlHelper::getPreparedField('wg_customer_occupational_investigation_al_witness.customer_occupational_investigation_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        return $query->get();
    }

    public function getCauses($criteria, $factor)
    {
        $query = DB::table('wg_customer_occupational_investigation_al_cause')
            ->join('wg_customer_occupational_investigation_al', function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_cause.customer_occupational_investigation_id');
            })
            ->leftjoin("wg_investigation_cause", function ($join) {
                $join->on('wg_customer_occupational_investigation_al_cause.cause', '=', 'wg_investigation_cause.id');
            })
            ->select(
                'wg_investigation_cause.code',
                'wg_investigation_cause.name AS description'
            )
            ->where("wg_customer_occupational_investigation_al_cause.factor", $factor);

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerOccupationalInvestigationAlId') {
                        $query->where(SqlHelper::getPreparedField('wg_customer_occupational_investigation_al_cause.customer_occupational_investigation_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        return $query->get();
    }

    public function getMeasurements($criteria)
    {
        $query = DB::table('wg_customer_occupational_investigation_al_measure')
            ->join('wg_customer_occupational_investigation_al', function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_measure.customer_occupational_investigation_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('investigation_measure')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_measure.type', '=', 'investigation_measure.value');
            })
            ->leftjoin(DB::raw(CustomerAbsenteeismDisabilityModel::getImprovementRelation('customer_improvement_plan', 'AT')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_measure.id', '=', 'customer_improvement_plan.entityId');
            })
            ->select(
                'investigation_measure.item AS factor',
                'wg_customer_occupational_investigation_al_measure.description',
                DB::raw("(CASE WHEN customer_improvement_plan.qty > 0 THEN 'Si' ELSE 'No' END) AS hasImprovementPlan")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerOccupationalInvestigationAlId') {
                        $query->where(SqlHelper::getPreparedField('wg_customer_occupational_investigation_al_measure.customer_occupational_investigation_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        return $query->get();
    }

    public function getResponsibles($criteria)
    {
        $q1 = DB::table('wg_customer_employee')
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_employee.customer_id');
            })
            ->join("wg_employee", function ($join) {
                $join->on('wg_employee.id', '=', 'wg_customer_employee.employee_id');
            })
            ->leftjoin("wg_customer_config_job", function ($join) {
                $join->on('wg_customer_config_job.id', '=', 'wg_customer_employee.job');
            })
            ->leftjoin("wg_customer_config_job_data", function ($join) {
                $join->on('wg_customer_config_job_data.id', '=', 'wg_customer_config_job.job_id');
            })
            ->select(
                'wg_customer_employee.id',
                'wg_customer_employee.customer_id',
                DB::raw("'Employee' AS type"),
                'wg_employee.documentNumber',
                'wg_employee.fullName',
                'wg_customer_config_job_data.name AS job'
            );

        $q2 = DB::table('wg_customer_user')
            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_user.customer_id');
            })
            ->select(
                'wg_customer_user.id',
                'wg_customer_user.customer_id',
                DB::raw("'User' AS type"),
                'wg_customer_user.documentNumber',
                'wg_customer_user.fullName',
                DB::raw("NULL AS job")
            );

        $q3 = DB::table('wg_customer_agent')
            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_agent.customer_id');
            })
            ->join('wg_agent', function ($join) {
                $join->on('wg_agent.id', '=', 'wg_customer_agent.agent_id');
            })
            ->select(
                'wg_agent.id',
                'wg_customer_agent.customer_id',
                DB::raw("'Agent' AS type"),
                'wg_agent.documentNumber',
                'wg_agent.name',
                DB::raw("NULL AS job")
            )
            ->groupBy("wg_agent.id");

        if ($criteria != null && isset($criteria->customerId)) {
            $q1->where(SqlHelper::getPreparedField('wg_customer_employee.customer_id'), $criteria->customerId);
            $q2->where(SqlHelper::getPreparedField('wg_customer_user.customer_id'), $criteria->customerId);
            $q3->where(SqlHelper::getPreparedField('wg_customer_agent.customer_id'), $criteria->customerId);
        }

        $q1
        ->union($q2)->mergeBindings($q2)
        ->union($q3)->mergeBindings($q3);

        $query = DB::table("wg_customer_occupational_investigation_al_responsible")
            ->leftjoin(DB::raw("({$q1->toSql()}) as responsible"), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_responsible.entityType', '=', 'responsible.type');
                $join->on('wg_customer_occupational_investigation_al_responsible.entityId', '=', 'responsible.id');
            })
            ->mergeBindings($q1)
            ->leftjoin('wg_customer_occupational_investigation_al', function ($join) {
                $join->on('wg_customer_occupational_investigation_al.id', '=', 'wg_customer_occupational_investigation_al_responsible.customer_occupational_investigation_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_productivity_stata_person_type')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_responsible.type', '=', 'wg_customer_productivity_stata_person_type.value');
            })
            ->select(
                'wg_customer_productivity_stata_person_type.item AS type',
                DB::raw("CASE WHEN wg_customer_occupational_investigation_al_responsible.type = 'IN' THEN responsible.documentNumber ELSE wg_customer_occupational_investigation_al_responsible.documentNumber END AS documentNumber"),
                DB::raw("CASE WHEN wg_customer_occupational_investigation_al_responsible.type = 'IN' THEN responsible.fullName ELSE wg_customer_occupational_investigation_al_responsible.name END AS fullName"),
                DB::raw("CASE WHEN wg_customer_occupational_investigation_al_responsible.type = 'IN' THEN responsible.job ELSE wg_customer_occupational_investigation_al_responsible.job END AS job")
            );

        if ($criteria != null) {
            if ($criteria->mandatoryFilters != null) {
                foreach ($criteria->mandatoryFilters as $item) {
                    if ($item->field == 'customerOccupationalInvestigationAlId') {
                        $query->where(SqlHelper::getPreparedField('wg_customer_occupational_investigation_al_responsible.customer_occupational_investigation_id'), SqlHelper::getOperator($item->operator), SqlHelper::getPreparedData($item), 'and');
                    }
                }
            }
        }

        Log::info($query->toSql());

        return $query->get();
    }

    private function convertToArray($data)
    {
        $result = array();

        foreach ($data as $record) {
            $result[$record->value] = $record->selected;
        }

        return $result;
    }

    public function getYears(int $customerId)
    {
        $q1 = DB::table('wg_customer_occupational_investigation_al')
            ->where('customer_id', $customerId)
            ->select(DB::raw("YEAR(accidentDate) AS value"), DB::raw("YEAR(accidentDate) AS item"));

        $q2 = DB::table('wg_customer_health_damage_qs as o')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
            ->where('ce.customer_id', $customerId)
            ->select(DB::raw("YEAR(o.created_at) AS value"), DB::raw("YEAR(o.created_at) AS item"));

        $q3 = DB::table('wg_customer_health_damage_ql as o')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
            ->where('ce.customer_id', $customerId)
            ->select(DB::raw("YEAR(o.created_at) AS value"), DB::raw("YEAR(o.created_at) AS item"));

        $q4 = DB::table('wg_customer_health_damage_restriction as o')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
            ->where('ce.customer_id', $customerId)
            ->select(DB::raw("YEAR(o.created_at) AS value"), DB::raw("YEAR(o.created_at) AS item"));

        $subquery = $q1->unionAll($q2)->mergeBindings($q2)
            ->unionAll($q3)->mergeBindings($q3)
            ->unionAll($q4)->mergeBindings($q4);

        $query = DB::table(DB::raw("({$subquery->toSql()}) as d"))
            ->mergeBindings($subquery)
            ->groupBy('d.value')
            ->select('d.item', 'd.value')
            ->orderBy('d.value', 'desc');

        return $query->get();
    }


    public function getInfoToDashboard(int $customerId, int $period)
    {
        $result = new \stdClass();


        $result->gender = $this->getKpiByGender($customerId, $period);
        $result->workingDay = $this->getkpiByWorkingDay($customerId, $period);
        $result->amountByDayOfWeek = $this->getAmountByDayOfWeek($customerId, $period);

        $result->getChartPie = $this->getkpiByWorkingDay($customerId, $period);

        $result->totalInvestigationAt = DB::table('wg_customer_occupational_investigation_al')
            ->where('customer_id', $customerId)
            ->when($period, function ($query) use ($period) {
                $query->whereYear('accidentDate', $period);
            })
            ->count() ?? 0;

        $result->totalCasesWithOrigen = $this->getTotalCasesWithOrigen($customerId, $period);
        $result->totalMissedGrade = $this->getMissedGrade($customerId, $period);
        $result->totalCasesWithRestrictions = $this->getTotalCasesWithRestrictions($customerId, $period);

        return $result;
    }

    public function getKpiByGender(int $customerId, int $period)
    {
        return DB::table('wg_customer_occupational_investigation_al as inv')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'inv.customer_employee_id')
            ->join('wg_employee as e', 'e.id', '=', 'ce.employee_id')
            ->where('inv.customer_id', $customerId)
            ->when($period, function ($query) use ($period) {
                $query->whereYear('accidentDate', $period);
            })
            ->select(
                DB::raw("COUNT(IF(e.gender = 'M', 1, NULL)) as male"),
                DB::raw("COUNT(IF(e.gender = 'F', 1, NULL)) as female")
            )
            ->first();
    }

    public function getKpiByWorkingDay(int $customerId, int $period)
    {
        return DB::table('wg_customer_occupational_investigation_al as inv')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_report_working_day', 'accidentWorkingDay')), function ($join) {
                $join->on('inv.accidentWorkingDay', '=', 'accidentWorkingDay.value');
            })
            ->where('inv.customer_id', $customerId)
            ->when($period, function ($query) use ($period) {
                $query->whereYear('accidentDate', $period);
            })
            ->groupBy('accidentWorkingDay.value')
            ->select(
                DB::raw("CASE WHEN accidentWorkingDay.value IS NULL THEN 'No Definido' ELSE accidentWorkingDay.item END as item"),
                DB::raw("
                    CASE WHEN accidentWorkingDay.item = 'Normal Diurna' THEN 'fa-sun'
                         WHEN accidentWorkingDay.item = 'Normal Nocturna' THEN 'fa-moon'
                         WHEN accidentWorkingDay.item = 'Extra Diurna' THEN 'fa-sun'
                         WHEN accidentWorkingDay.item = 'Extra Nocturna' THEN 'fa-moon'
                         ELSE 'fa-cloud'
                    END AS icon
                "),
                DB::raw("COUNT(*) as count")
            )
            ->get();
    }


    public function getAmountByDayOfWeek(int $customerId, int $period)
    {
        return DB::table('wg_customer_occupational_investigation_al as inv')
            ->where('inv.customer_id', $customerId)
            ->when($period, function ($query) use ($period) {
                $query->whereYear('accidentDate', $period);
            })
            ->groupBy(DB::raw("DAYOFWEEK(accidentDate)"))
            ->select(
                DB::raw("DAYOFWEEK(accidentDate) as day"),
                DB::raw("COUNT(*) as count")
            )
            ->get();
    }


    public function getTotalCasesWithOrigen(int $customerId, int $period)
    {
        return DB::table('wg_customer_health_damage_qs as o')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
            ->where('ce.customer_id', $customerId)
            ->when($period, function ($query) use ($period) {
                $query->whereYear('o.created_at', $period);
            })
            ->count();
    }

    public function getMissedGrade(int $customerId, int $period)
    {
        return DB::table('wg_customer_health_damage_ql as o')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
            ->where('ce.customer_id', $customerId)
            ->when($period, function ($query) use ($period) {
                $query->whereYear('o.created_at', $period);
            })
            ->count();
    }

    public function getTotalCasesWithRestrictions(int $customerId, int $period)
    {
        return DB::table('wg_customer_health_damage_restriction as o')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
            ->where('ce.customer_id', $customerId)
            ->when($period, function ($query) use ($period) {
                $query->whereYear('o.created_at', $period);
            })
            ->count();
    }

    public function chartBarBody(int $customerId, int $period)
    {
        $subquery = DB::table('wg_customer_occupational_investigation_al')
            ->join("wg_customer_occupational_investigation_al_body", function ($join) {
                $join->on('wg_customer_occupational_investigation_al_body.customer_occupational_investigation_id', '=', 'wg_customer_occupational_investigation_al.id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_body_part')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_body.body_part_id', '=', 'wg_report_body_part.value');
            })
            ->where('wg_customer_occupational_investigation_al.customer_id', $customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accidentDate', '=', $period)
            ->groupBy('wg_customer_occupational_investigation_al_body.body_part_id')
            ->select(
                DB::raw("'Total' AS label"),
                DB::raw("wg_report_body_part.item AS dynamicColumn"),
                DB::raw("count(*) AS total")
            );

        list($query, $valueColumns) = $this->getQueryTransformRowToColumns($subquery);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartBar($query->get(), $config);
    }

    public function chartBarFactor(int $customerId, int $period)
    {
        $subquery = DB::table('wg_customer_occupational_investigation_al')
            ->join("wg_customer_occupational_investigation_al_factor", function ($join) {
                $join->on('wg_customer_occupational_investigation_al_factor.customer_occupational_investigation_id', '=', 'wg_customer_occupational_investigation_al.id');
            })
            ->join(DB::raw(SystemParameter::getRelationTable('wg_report_factor', 'sp')), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_factor.factor_id', '=', 'sp.value');
            })
            ->where('wg_customer_occupational_investigation_al.customer_id', $customerId)
            ->whereYear('wg_customer_occupational_investigation_al.accidentDate', '=', $period)
            ->groupBy('sp.value')
            ->select(
                DB::raw("'Total' AS label"),
                DB::raw("sp.item AS dynamicColumn"),
                DB::raw("count(*) AS total")
            );

        list($query, $valueColumns) = $this->getQueryTransformRowToColumns($subquery);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartBar($query->get(), $config);
    }

    public function getChartStackedBarAusentismVsInvestigationAT(int $customerId, int $period, $workplaceId)
    {
        // $countAbsenteeism = DB::table('wg_customer_absenteeism_disability as o')
        //     ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
        //     ->leftJoin('wg_customer_config_workplace as workplace', 'workplace.id', '=', 'o.workplace_id')
        //     ->where('ce.customer_id', $customerId)
        //     ->where('o.cause', 'AL')
        //     ->when($period, function($join) use ($period) {
        //         $join->whereYear('o.start', $period);
        //     })
        //     ->when($workplaceId, function($join) use ($workplaceId) {
        //         $join->where('workplace.id', $workplaceId);
        //     })
        //     ->count();

        $countAbsenteeism = DB::table('wg_customer_absenteeism_indicator as abs')
            ->join('wg_customer_config_workplace', 'wg_customer_config_workplace.id', '=', 'abs.workCenter')
            ->where('abs.resolution', '0312')
            ->whereIn('abs.classification', ['AL', 'ELC', 'EG'])
            ->where('abs.customer_id', $customerId)
            ->when($period, function ($query) use ($period) {
                $query->whereYear('periodDate', $period);
            })
            ->when($workplaceId, function ($query) use ($workplaceId) {
                $query->whereYear('abs.workCenter', $workplaceId);
            })
            ->whereIn('abs.classification', ['AL', 'AT'])
            ->groupBy('abs.classification', DB::raw('YEAR(abs.periodDate)'))
            ->select(
                'abs.classification as cause',
                DB::raw("SUM(IFNULL(abs.eventNumber, 0)) AS eventNumber")
            )
            ->first();

        $countInvestigationAT = DB::table('wg_customer_occupational_investigation_al as o')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
            ->leftJoin('wg_customer_config_workplace as workplace', 'workplace.id', '=', 'ce.workPlace')
            ->where('ce.customer_id', $customerId)
            ->when($period, function ($join) use ($period) {
                $join->whereYear('o.accidentDate', $period);
            })
            ->when($workplaceId, function ($join) use ($workplaceId) {
                $join->where('workplace.id', $workplaceId);
            })
            ->count();

        $data = new \stdClass();
        $data->label = 'Accidentes de Trabajo';
        $data->countAbsenteeism = $countAbsenteeism ? $countAbsenteeism->eventNumber - $countInvestigationAT : 0 - $countInvestigationAT;
        $data->countInvestigationAT = $countInvestigationAT;
        $allData = [$data];

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Sin Investigación', 'field' => 'countAbsenteeism', 'color' => '#3271b2'],
                ['label' => 'Con Investigación', 'field' => 'countInvestigationAT', 'color' => '#f69848'],
            ],
        );

        return $this->chart->getChartBar($allData, $config);
    }


    public function getKpiOccupationalMedicineDashboard(int $customerId, $period, $workplaceId)
    {
        $subquery = DB::table('wg_customer_absenteeism_indicator as abs')
            ->join('wg_customer_config_workplace', 'wg_customer_config_workplace.id', '=', 'abs.workCenter')
            ->where('abs.resolution', '0312')
            ->whereIn('abs.classification', ['AL', 'ELC', 'EG'])
            ->where('abs.customer_id', $customerId)
            ->when($period, function ($query) use ($period) {
                $query->whereYear('periodDate', $period);
            })
            ->when($workplaceId, function ($query) use ($workplaceId) {
                $query->where('wg_customer_config_workplace.id', $workplaceId);
            })
            ->groupBy('abs.classification', DB::raw('YEAR(abs.periodDate)'))
            ->select(
                'abs.classification as cause',
                DB::raw("SUM(IFNULL(abs.eventNumber, 0)) AS eventNumber"),
                DB::raw("SUM(IFNULL(abs.disabilityDays, 0)) AS disabilityDays")
            );

        return DB::table(DB::raw("({$subquery->toSql()}) as o"))
            ->mergeBindings($subquery)
            ->select(
                DB::raw("SUM(IF(cause = 'AL', eventNumber, 0)) AS eventNumberAT"),
                DB::raw("SUM(IF(cause = 'ELC', eventNumber, 0)) AS eventNumberEL"),
                DB::raw("SUM(IF(cause = 'EG', eventNumber, 0)) AS eventNumberEC"),
                DB::raw("SUM(IF(cause = 'AL', disabilityDays, 0)) AS disabilityDaysAT"),
                DB::raw("SUM(IF(cause = 'ELC', disabilityDays, 0)) AS disabilityDaysEL"),
                DB::raw("SUM(IF(cause = 'EG', disabilityDays, 0)) AS disabilityDaysEC"),
                DB::raw("SUM(disabilityDays) disabilityDaysTotal"),
                DB::raw("SUM(eventNumber) eventNumberTotal")
            )
            ->first();
    }

    public function getChartPieAbsenteeisByCause(int $customerId, $period, $workplaceId)
    {
        $data = DB::table('wg_customer_absenteeism_disability as o')
            ->join('wg_customer_employee as ce', 'ce.id', '=', 'o.customer_employee_id')
            ->join(DB::raw(SystemParameter::getRelationTable('absenteeism_disability_accident_type', 'sp')), function ($join) {
                $join->on('o.accidentType', '=', 'sp.value');
            })
            ->whereIn('o.accidentType', ['L', 'G', 'M'])
            ->where('ce.customer_id', $customerId)
            ->where('o.type', '<>', 'Prorroga')
            ->whereIn('o.cause', ['AL', 'AT'])
            ->whereNull('o.customer_absenteeism_disability_parent_id')
            ->when($period, function ($join) use ($period) {
                $join->whereYear('o.start', $period);
            })
            ->when($workplaceId, function ($join) use ($workplaceId) {
                $join->where('o.workplace_id', $workplaceId);
            })
            ->groupBy('o.accidentType')
            ->select(
                'sp.item as label',
                DB::raw("COUNT(*) AS value")
            )
            ->get();

        return $this->chart->getChartPie($data);
    }
}
