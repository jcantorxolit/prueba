<?php

namespace AdeN\Api\Modules\Customer\ConfigJobExpressRelation;

use AdeN\Api\Classes\BaseService;
use DB;
use Illuminate\Support\Collection;
use Log;
use Str;


class CustomerConfigJobExpressRelationService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function updateIsFullyConfigured($customerProcessExpressRelationId, $userId)
    {
        return DB::table('wg_customer_config_job_express_relation')
            ->leftjoin('wg_customer_config_activity_express_relation', function ($join) {
                $join->on('wg_customer_config_activity_express_relation.customer_job_express_relation_id', '=', 'wg_customer_config_job_express_relation.id');
            })
            ->where('wg_customer_config_job_express_relation.is_active', 1)
            ->where('wg_customer_config_job_express_relation.customer_process_express_relation_id', $customerProcessExpressRelationId)            
            ->update([
                'wg_customer_config_job_express_relation.is_fully_configured' => DB::raw('CASE WHEN wg_customer_config_activity_express_relation.id IS NOT NULL THEN 1 ELSE 0 END'),
                'wg_customer_config_job_express_relation.updated_by' => $userId,
                'wg_customer_config_job_express_relation.updated_at' => DB::raw('NOW()')
            ]);
    }
}
