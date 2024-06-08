<?php

namespace AdeN\Api\Modules\Customer\ArlServiceCost;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Carbon\Carbon;
use Str;


class CustomerArlServiceCostService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }


    public function getAllYears($customerId)
    {
        return DB::table('wg_customer_arl_service_cost')
            ->where('customer_id', $customerId)
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->select(DB::raw("YEAR(wg_customer_arl_service_cost.registration_date) AS year"))
            ->get()
            ->toArray();
    }
}
