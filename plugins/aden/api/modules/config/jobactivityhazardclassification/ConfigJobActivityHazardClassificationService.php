<?php

namespace AdeN\Api\Modules\Config\JobActivityHazardClassification;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class ConfigJobActivityHazardClassificationService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getList()
    {
        return DB::table('wg_config_job_activity_hazard_classification')
            ->select(
                'id',
                'name',
                'code'
            )
            ->get();
    }
}
