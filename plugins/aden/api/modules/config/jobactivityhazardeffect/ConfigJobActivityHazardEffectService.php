<?php

namespace AdeN\Api\Modules\Config\JobActivityHazardEffect;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class ConfigJobActivityHazardEffectService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getList($criteria)
    {
        return DB::table('wg_config_job_activity_hazard_effect')
            ->select(
                'id',
                'name',
                'code'
            )
            ->where('type_id', $criteria->typeId)
            ->get();
    }
}