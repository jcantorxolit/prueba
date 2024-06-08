<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\ConfigProcessExpressRelation;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;
use Illuminate\Support\Collection;

class CustomerConfigProcessExpressRelationModel extends Model
{
    use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_config_process_express_relation";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public function getJobList()
    {
        $data = DB::table('wg_customer_config_job_express')
            ->join('wg_customer_config_job_express_relation', function ($join) {
                $join->on('wg_customer_config_job_express_relation.customer_job_express_id', '=', 'wg_customer_config_job_express.id');
            })
            ->leftjoin('wg_customer_config_activity_express_relation', function ($join) {
                $join->on('wg_customer_config_activity_express_relation.customer_job_express_relation_id', '=', 'wg_customer_config_job_express_relation.id');
            })
            ->leftjoin('wg_customer_config_activity_express', function ($join) {
                $join->on('wg_customer_config_activity_express.id', '=', 'wg_customer_config_activity_express_relation.customer_activity_express_id');
            })
            ->select(
                'wg_customer_config_job_express_relation.id',
                'wg_customer_config_job_express.customer_id AS customerId',
                'wg_customer_config_job_express.name',
                'wg_customer_config_job_express_relation.is_active AS isActive',

                'wg_customer_config_activity_express_relation.id AS activityRelationId',
                'wg_customer_config_activity_express.id AS activityId',
                'wg_customer_config_activity_express.name AS activityName',
                'wg_customer_config_activity_express_relation.is_routine AS isRoutine'
            )
            ->where('wg_customer_config_job_express_relation.customer_process_express_relation_id', $this->id)
            ->get();

        $collection = new Collection($data);

        $jobs = $collection->groupBy('id');

        return $jobs->map(function ($items, $key) {
            $jobs = new Collection($items);
            $item = $jobs->first();
            $job = new \stdClass();
            $job->id = $item->id;
            $job->customerId = $item->customerId;
            $job->name = $item->name;
            $job->isActive = $item->isActive == 1;

            $job->activityList = $jobs->filter(function($item) {
                return $item->activityRelationId != null;
            })->map(function ($item, $key) {
                $activity = new \stdClass();
                $activity->id = $item->activityRelationId;
                $activity->customerId = $item->customerId;
                $activity->jobExpressRelationId = $item->id;
                $activity->activityExpressId = $item->activityId;
                $activity->name = $item->activityName;
                $activity->isRoutine = (string) $item->isRoutine;

                return $activity;
            });

            return $job;
        })->values();
    }
}
