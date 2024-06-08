<?php

namespace AdeN\Api\Modules\Customer\ConfigActivityExpress;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerConfigActivityExpressService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getList($criteria)
    {
        $q1 = DB::table('wg_customer_config_activity_express')
            ->select(
                'id',
                'name'
            )
            ->where('status', 1)
            ->where('customer_id', $criteria->customerId);
        
        $q2 = DB::table('wg_customer_config_workplace')
            ->join('wg_investigation_economic_activity', function ($join) {
                $join->on('wg_investigation_economic_activity.id', '=', 'wg_customer_config_workplace.economic_activity_id');
            })
            ->join('wg_economic_sector', function ($join) {
                $join->on('wg_economic_sector.id', '=', 'wg_investigation_economic_activity.economic_sector_id');
            })
            ->join('wg_economic_sector_task', function ($join) {
                $join->on('wg_economic_sector_task.economic_sector_id', '=', 'wg_economic_sector.id');
            })
            ->select(
                'wg_economic_sector_task.id',
                'wg_economic_sector_task.name'
            )
            ->where('wg_economic_sector_task.is_active', 1)
            ->where('wg_customer_config_workplace.customer_id', $criteria->customerId)
            ->where('wg_customer_config_workplace.id', $criteria->workplaceId);

        $q1->union($q2);

        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_customer_config_activity_express"))
            ->mergeBindings($q1)
            ->orderBy('wg_customer_config_activity_express.name');

        return $query->get();
    }
}
