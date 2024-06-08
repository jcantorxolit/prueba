<?php

namespace AdeN\Api\Modules\Customer\AbsenteeismDisabilityDayCharged;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;
use Carbon\Carbon;

class CustomerAbsenteeismDisabilityDayChargedService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getDayChargedIsDeathValue()
    {
        return DB::table('wg_config_day_charged_part')
            ->join("wg_config_day_charged_classification", function ($join) {
                $join->on('wg_config_day_charged_classification.id', '=', 'wg_config_day_charged_part.config_day_charged_classification_id');
            })
            ->join("wg_config_day_charged_type", function ($join) {
                $join->on('wg_config_day_charged_type.id', '=', 'wg_config_day_charged_classification.config_day_charged_type_id');
            })
            ->select(
                'wg_config_day_charged_part.value'
            )
            ->where('wg_config_day_charged_type.is_death', 1)
            ->first();
    }

    public function updateAbsenteeismDisabilityDayCharged($customerDisabilityId)
    {
        $q1 = DB::table('wg_customer_absenteeism_disability_day_charged')
            ->join("wg_customer_absenteeism_disability", function ($join) {
                $join->on('wg_customer_absenteeism_disability_day_charged.customer_disability_id', '=', 'wg_customer_absenteeism_disability.id');
            })
            ->join("wg_config_day_charged_part", function ($join) {
                $join->on('wg_config_day_charged_part.id', '=', 'wg_customer_absenteeism_disability_day_charged.config_day_charged_part_id');
            })
            ->join("wg_config_day_charged_classification", function ($join) {
                $join->on('wg_config_day_charged_classification.id', '=', 'wg_config_day_charged_part.config_day_charged_classification_id');
            })
            ->select(
                'wg_customer_absenteeism_disability_day_charged.customer_disability_id',
                DB::raw('MAX(wg_config_day_charged_part.value) AS value')
            )
            ->whereRaw('wg_customer_absenteeism_disability_day_charged.is_deleted = 0')
            ->groupBy('wg_customer_absenteeism_disability_day_charged.customer_disability_id');

        return DB::table('wg_customer_absenteeism_disability')
            ->mergeBindings($q1)
            ->leftjoin(DB::raw("({$q1->toSql()}) as wg_customer_absenteeism_disability_day_charged"), function ($join) {
                $join->on('wg_customer_absenteeism_disability.id', '=', 'wg_customer_absenteeism_disability_day_charged.customer_disability_id');
            })
            ->where('wg_customer_absenteeism_disability.id', $customerDisabilityId)
            ->update([
                "wg_customer_absenteeism_disability.chargedDays" => DB::raw("IFNULL(wg_customer_absenteeism_disability_day_charged.value, 0)"),
                "wg_customer_absenteeism_disability.updated_at" => Carbon::now(),
            ]);
    }
}
