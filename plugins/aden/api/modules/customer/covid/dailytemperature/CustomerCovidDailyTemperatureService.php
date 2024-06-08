<?php

namespace AdeN\Api\Modules\Customer\Covid\DailyTemperature;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Carbon\Carbon;
use Str;


class CustomerCovidDailyTemperatureService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getTemperatureOfMonth($criteria)
    {
        $startDate = Carbon::now();
        $qTemperature = DB::table('wg_customer_covid_temperature')
                ->select(
                    'customer_covid_id',
                    DB::raw("REPLACE(MAX(temperature),',','.' ) as temperature")
                )
                ->orderBy("temperature","desc")
                ->groupBy("customer_covid_id");

        $query = DB::table('wg_customer_covid')
                ->join(DB::raw("({$qTemperature->toSql()}) as wg_customer_covid_temperature"), function ($join) {
                    $join->on('wg_customer_covid_temperature.customer_covid_id', '=', 'wg_customer_covid.id');
                })
                ->mergeBindings($qTemperature)
                ->select("wg_customer_covid.registration_date as label", "temperature as value")
                ->where("customer_covid_head_id",$criteria->entityid)
                ->orderBy("wg_customer_covid.registration_date");

        if(!empty($criteria->month)) {
            $query->whereRaw("DATE_FORMAT(wg_customer_covid.registration_date, '%Y%m') = {$criteria->month}");
        } else {
            $query->whereBetween("wg_customer_covid.registration_date",[$startDate->firstOfMonth()->toDateString(),$startDate->lastOfMonth()->toDateString()]);
        }

        $data = $query->get()->toArray();
        if(!is_null($data) && count($data) == 1) {
            $empty = (object)["label" => "", "value" => 0];
            array_unshift($data,$empty);
            array_push($data,$empty);
        }

        $config = array(
            "labelColumn" => "label",
            "valueColumns" => [
                ['label' => 'Temperatura', 'field' => 'value', "color" => "#A2E99B"]
            ]
        );

        return $this->chart->getChartLine($data,$config);
    }

    public function getMaxTemperature($dailyId)
    {
        $total = DB::table("wg_customer_covid_temperature")
                ->where("customer_covid_id",$dailyId)
                ->orderBy("temperature","desc")
                ->whereRaw("temperature >= 37.3")
                ->count();
                
        if($total > 1) {
            return null;
        }

        return DB::table("wg_customer_covid_temperature")
                ->where("customer_covid_id",$dailyId)
                ->orderBy("temperature","desc")
                ->whereRaw("temperature >= 37.3")
                ->first();
    }

}