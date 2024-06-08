<?php

namespace AdeN\Api\Modules\Config\JobActivityHazardType;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class ConfigJobActivityHazardTypeService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getList($criteria)
    {
        return DB::table('wg_config_job_activity_hazard_type')
            ->select(
                'id',
                'name',
                'code'
            )
            ->where('classification_id', $criteria->classificationId)
            ->get();
    }
}