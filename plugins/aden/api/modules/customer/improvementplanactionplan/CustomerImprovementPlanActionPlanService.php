<?php

namespace AdeN\Api\Modules\Customer\ImprovementPlanActionPlan;

use AdeN\Api\Classes\BaseService;
use DB;
use Carbon\Carbon;

class CustomerImprovementPlanActionPlanService extends BaseService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function bulkCancel($id, $reason, $userId)
    {
        $query = DB::table('wg_customer_improvement_plan_action_plan')
            ->join("wg_customer_improvement_plan", function ($join) {
                $join->on('wg_customer_improvement_plan_action_plan.customer_improvement_plan_id', '=', 'wg_customer_improvement_plan.id');

            })
            ->select(
                DB::raw('NULL AS id'),
                DB::raw('wg_customer_improvement_plan_action_plan.id AS customer_improvement_plan_action_plan_id'),
                DB::raw("'$reason' AS reason"),
                DB::raw("NULL AS oldStatus"),
                DB::raw("NOW() AS createdAt"),
                DB::raw("'$userId' AS createdBy"),
                DB::raw("NOW() AS updatedAt"),
                DB::raw("'$userId' AS updatedBy")
            )
            ->where("wg_customer_improvement_plan.id", $id);

        $sql = 'INSERT INTO wg_customer_improvement_plan_action_plan_comment ' . $query->toSql();

        DB::statement($sql, $query->getBindings());

        return DB::table('wg_customer_improvement_plan_action_plan')
            ->where('wg_customer_improvement_plan_action_plan.customer_improvement_plan_id', $id)
            ->update(["status" => 'CA', "updated_at" => Carbon::now(), "updatedBy" => $userId]);
    }
}
