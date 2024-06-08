<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 22/05/2017
 * Time: 6:15 PM
 */

namespace AdeN\Api\Modules\Customer\ImprovementPlan;

use AdeN\Api\Classes\CamelCasing;
use October\Rain\Database\Model;
use System\Models\Parameters;
use DB;

class CustomerImprovementPlanModel extends Model
{
	//use CamelCasing;

    /**
     * @var string The database table used by the model.
     */
    protected $table = "wg_customer_improvement_plan";

    public $belongsTo = [
        'creator' => ['October\Rain\Auth\Models\User', 'key' => 'createdBy', 'otherKey' => 'id'],
        'updater' => ['October\Rain\Auth\Models\User', 'key' => 'updatedBy', 'otherKey' => 'id']
    ];

    public static function getRelatedActionPlanStatsRaw()
    {
        return DB::table('wg_customer_improvement_plan_action_plan')
            ->whereRaw("wg_customer_improvement_plan_action_plan.status <> 'CA'")
            ->select(
                "wg_customer_improvement_plan_action_plan.customer_improvement_plan_id",
                DB::raw("COUNT(*) AS qty"),
                DB::raw("SUM(CASE WHEN wg_customer_improvement_plan_action_plan.status = 'CO' THEN 1 ELSE 0 END) as completed")
            )
            ->groupBy("wg_customer_improvement_plan_action_plan.customer_improvement_plan_id");
    }

	protected function getParameterByValue($value, $group, $ns = "wgroup")
    {
        return Parameters::whereNamespace($ns)->whereGroup($group)->whereValue($value)->first();
    }

    public static function getConfigGeneralRelation($table, $type)
    {
        return "(SELECT * FROM `wg_config_general` WHERE `type` = '$type') $table ";
    }
}
