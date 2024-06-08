<?php

namespace AdeN\Api\Modules\Customer\ConfigActivityStaging;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerConfigActivityStagingService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function findOneClassification($id)
    {
        return DB::table('wg_config_job_activity_hazard_classification')->find($id);
    }

    public function findOneType($id)
    {
        return DB::table('wg_config_job_activity_hazard_type')
            ->select(
                'id',
                DB::raw("CONCAT(`code`, ' ', REPLACE(`name`, `code`, '')) AS `name`")
            )
            ->find($id);
    }

    public function findOneDescription($id)
    {
        return DB::table('wg_config_job_activity_hazard_description')
            ->select(
                'id',
                DB::raw("TRIM(CONCAT(`code`, ' ', REPLACE(`name`, `code`, ''))) AS `name`")
            )
            ->find($id);
    }

    public function findOneHealthEffect($id)
    {
        return DB::table('wg_config_job_activity_hazard_effect')
            ->select(
                'id',
                DB::raw("CONCAT(`code`, ' ', REPLACE(`name`, `code`, '')) AS `name`")
            )
            ->find($id);
    }

    public function findOneMeasure($id)
    {
        return DB::table('wg_config_general')->find($id);
    }
}
