<?php

namespace AdeN\Api\Modules\Customer\ConfigProcessExpressRelation;

use AdeN\Api\Classes\BaseService;
use DB;
use Illuminate\Database\Eloquent\Collection;
use Log;
use Str;


class CustomerConfigProcessExpressRelationService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getDataToCopy($criteria)
    {
        $query = DB::table('wg_customer_config_process_express_relation')            
            ->join('wg_customer_config_process_express', function ($join) {
                $join->on('wg_customer_config_process_express.id', '=', 'wg_customer_config_process_express_relation.customer_process_express_id');
                $join->on('wg_customer_config_process_express.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
            })
            ->join('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
                $join->on('wg_customer_config_job_express_relation.customer_id', '=', 'wg_customer_config_process_express_relation.customer_id');
            })
            ->join('wg_customer_config_job_express', function ($join) {
                $join->on('wg_customer_config_job_express.id', '=', 'wg_customer_config_job_express_relation.customer_job_express_id');
                $join->on('wg_customer_config_job_express.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
            })
            ->join('wg_customer_config_activity_express_relation', function ($join) {
                $join->on('wg_customer_config_activity_express_relation.customer_job_express_relation_id', '=', 'wg_customer_config_job_express_relation.id');
                $join->on('wg_customer_config_activity_express_relation.customer_id', '=', 'wg_customer_config_job_express_relation.customer_id');
            })
            ->join('wg_customer_config_activity_express', function ($join) {
                $join->on('wg_customer_config_activity_express.id', '=', 'wg_customer_config_activity_express_relation.customer_activity_express_id');
                $join->on('wg_customer_config_activity_express.customer_id', '=', 'wg_customer_config_activity_express_relation.customer_id');
            })
            ->select(
                'wg_customer_config_process_express.id AS process_express_id',
                'wg_customer_config_process_express_relation.customer_workplace_id',
                'wg_customer_config_process_express_relation.customer_id',
                'wg_customer_config_job_express.id AS job_express_id',
                'wg_customer_config_activity_express.id AS activity_express_id',
                'wg_customer_config_activity_express_relation.is_routine',
                DB::raw("? AS module")
            )
            ->addBinding($criteria->module, "select")
            ->where('wg_customer_config_process_express_relation.id', $criteria->processFromId);

        $collection = new Collection($query->get());

        $process = $collection->groupBy('process_express_id')->map(function ($items, $key) {

            $jobs = new Collection($items);

            $item = $jobs->first();
            $process = new \stdClass();
            $process->id = 0;
            $process->customerId = $item->customer_id;
            $process->processExpressId = $item->process_express_id;
            $process->workplaceId = $item->customer_workplace_id;
            $process->module = $item->module;

            $process->jobList = $jobs->groupBy('job_express_id')->filter(function ($item) use ($key) {
                return $item[0]->process_express_id == $key;
            })->map(function ($items, $key) {

                $activities = new Collection($items);

                $item = $activities->first();
                $job = new \stdClass();
                $job->id = 0;
                $job->customerId = $item->customer_id;
                $job->jobExpressId = $item->job_express_id;
                $job->module = $item->module;

                $job->activityList = $activities->filter(function ($item) use ($key) {
                    return $item->job_express_id == $key;
                })->map(function ($item, $key) {
                    $activity = new \stdClass();
                    $activity->id = 0;
                    $activity->customerId = $item->customer_id;
                    $activity->activityExpressId = $item->activity_express_id;
                    $activity->isRoutine = (string) $item->is_routine;

                    return $activity;
                });

                return $job;
            });

            return $process;
        })->first();

        return $process;
    }

    public function updateIsFullyConfigured($customerWorkplaceId, $userId)
    {
        $qJobRelation = DB::table('wg_customer_config_job_express_relation')
            ->select(
                'wg_customer_config_job_express_relation.customer_process_express_relation_id',
                DB::raw('SUM(CASE WHEN wg_customer_config_job_express_relation.is_fully_configured THEN 1 ELSE 0 END) qtyJobFullyConfigured'),
                DB::raw('COUNT(*) qtyJob')
            )
            ->groupBy('wg_customer_config_job_express_relation.customer_process_express_relation_id');

        $query = DB::table('wg_customer_config_process_express_relation')
            ->leftjoin('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
            })
            ->leftjoin(DB::raw("({$qJobRelation->toSql()}) AS wg_customer_config_job_express_relation_group"), function ($join) {
                $join->on('wg_customer_config_job_express_relation_group.customer_process_express_relation_id', '=', 'wg_customer_config_process_express_relation.id');
            })
            ->mergeBindings($qJobRelation)
            ->select(
                'wg_customer_config_process_express_relation.id',
                DB::raw('SUM(CASE WHEN wg_customer_config_job_express_relation.customer_process_express_relation_id IS NOT NULL THEN 1 ELSE 0 END) qty'),
                DB::raw('CASE WHEN wg_customer_config_job_express_relation_group.qtyJob = wg_customer_config_job_express_relation_group.qtyJobFullyConfigured AND wg_customer_config_job_express_relation_group.qtyJobFullyConfigured > 0 THEN 1 ELSE 0 END is_process_fully_configured')
            )
            ->where('wg_customer_config_process_express_relation.customer_workplace_id', $customerWorkplaceId)
            ->whereNotNull('wg_customer_config_job_express_relation_group.customer_process_express_relation_id')
            ->groupBy('wg_customer_config_process_express_relation.id');


        return DB::table('wg_customer_config_process_express_relation')
            ->leftjoin(DB::raw("({$query->toSql()}) AS wg_customer_config_process_express_relation_fully"), function ($join) {
                $join->on('wg_customer_config_process_express_relation_fully.id', '=', 'wg_customer_config_process_express_relation.id');
            })
            ->mergeBindings($query)
            ->where('wg_customer_config_process_express_relation.customer_workplace_id', $customerWorkplaceId)
            ->update([
                'wg_customer_config_process_express_relation.is_fully_configured' => DB::raw('CASE WHEN wg_customer_config_process_express_relation_fully.is_process_fully_configured IS NOT NULL THEN wg_customer_config_process_express_relation_fully.is_process_fully_configured ELSE 0 END'),
                'wg_customer_config_process_express_relation.updated_by' => DB::raw($userId),
                'wg_customer_config_process_express_relation.updated_at' => DB::raw('NOW()')
            ]);
    }

}
