<?php

namespace AdeN\Api\Modules\Customer\ConfigJobExpress;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerConfigJobExpressService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getList($criteria)
    {
        return DB::table('wg_customer_config_job_express')
            ->select(
                'id',
                'name'
            )
            ->where('status', 1)
            ->where('customer_id', $criteria->customerId)
            ->orderBy('name')
            ->get();
    }
}
