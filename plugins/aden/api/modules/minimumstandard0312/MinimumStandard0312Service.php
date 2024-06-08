<?php

namespace AdeN\Api\Modules\MinimumStandard0312;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class MinimumStandard0312Service extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getParentList()
    {
        return DB::table('wg_minimum_standard_0312')
            ->leftjoin("wg_config_minimum_standard_cycle_0312", function ($join) {
                $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_minimum_standard_0312.cycle_id');
            })
            ->select(
                'wg_minimum_standard_0312.id',
                'wg_minimum_standard_0312.type',
                'wg_minimum_standard_0312.cycle_id',
                'wg_config_minimum_standard_cycle_0312.name AS cycle',                
                'wg_minimum_standard_0312.numeral',
                'wg_minimum_standard_0312.description',
                'wg_minimum_standard_0312.is_active'
            )
            ->where('type', 'P')
            ->where('is_active', 1)
            ->get();
    }

    public function getRateList()
    {
        return DB::table('wg_config_minimum_standard_rate_0312')->where('id', '>', 2)->get();
    }

    public function getRealRateList()
    {
        return DB::table('wg_config_minimum_standard_rate_0312')->get();
    }
}
