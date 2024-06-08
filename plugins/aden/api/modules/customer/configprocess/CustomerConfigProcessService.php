<?php

namespace AdeN\Api\Modules\Customer\ConfigProcess;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerConfigProcessService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getMacroprocessList($customerId)
    {
        return DB::table('wg_customer_config_macro_process')
            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_macro_process.customer_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_macro_process.workplace_id');
            })
            ->select('wg_customer_config_macro_process.name')
            ->where('wg_customer_config_macro_process.customer_id', $customerId)            
            ->where('wg_customer_config_macro_process.status', '=', 'Activo')
            ->orderBy('wg_customer_config_macro_process.name')
            ->groupBy('wg_customer_config_macro_process.customer_id', 'wg_customer_config_macro_process.name')
            ->get()
            ->toArray();
    }
}
