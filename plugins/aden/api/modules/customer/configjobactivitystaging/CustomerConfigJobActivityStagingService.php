<?php

namespace AdeN\Api\Modules\Customer\ConfigJobActivityStaging;

use AdeN\Api\Classes\BaseService;
use DB;
use Log;
use Str;


class CustomerConfigJobActivityStagingService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function findWorkplace($id)
    {
        return DB::table('wg_customer_config_workplace')->find($id);
    }

    public function findMacroprocess($id)
    {
        return DB::table('wg_customer_config_macro_process')->find($id);
    }

    public function findProcess($id)
    {
        return DB::table('wg_customer_config_process')->find($id);
    }

    public function findJob($id)
    {
        return DB::table('wg_customer_config_job_data')->find($id);
    }

    public function findActivity($id)
    {
        return DB::table('wg_customer_config_activity')->find($id);
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

    public function getProcessList($customerId)
    {
        return DB::table('wg_customer_config_process')        
            ->join('wg_customer_config_macro_process', function ($join) {
                $join->on('wg_customer_config_macro_process.id', '=', 'wg_customer_config_process.macro_process_id');
                $join->on('wg_customer_config_macro_process.workplace_id', '=', 'wg_customer_config_process.workplace_id');
            })
            ->join('wg_customer_config_workplace', function ($join) {
                $join->on('wg_customer_config_workplace.id', '=', 'wg_customer_config_process.workplace_id');
            })               
         
            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_macro_process.customer_id');
            })

            ->select('wg_customer_config_process.name')
            ->where('wg_customer_config_process.customer_id', $customerId)            
            ->where('wg_customer_config_process.status', '=', 'Activo')
            ->orderBy('wg_customer_config_process.name')
            ->groupBy('wg_customer_config_process.customer_id', 'wg_customer_config_process.name')
            ->get()
            ->toArray();
    }    

    public function getJobList($customerId)
    {
        return DB::table('wg_customer_config_job_data')        
           
            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_job_data.customer_id');
            })

            ->select('wg_customer_config_job_data.name')
            ->where('wg_customer_config_job_data.customer_id', $customerId)            
            ->where('wg_customer_config_job_data.status', '=', 'Activo')
            ->orderBy('wg_customer_config_job_data.name')
            ->groupBy('wg_customer_config_job_data.customer_id', 'wg_customer_config_job_data.name')
            ->get()
            ->toArray();
    }
    
    public function getActivityList($customerId)
    {
        return DB::table('wg_customer_config_activity')        
           
            ->join('wg_customers', function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_config_activity.customer_id');
            })

            ->select('wg_customer_config_activity.name')
            ->where('wg_customer_config_activity.customer_id', $customerId)            
            ->where('wg_customer_config_activity.status', '=', 'Activo')
            ->orderBy('wg_customer_config_activity.name')
            ->groupBy('wg_customer_config_activity.customer_id', 'wg_customer_config_activity.name')
            ->get()
            ->toArray();
    }
}