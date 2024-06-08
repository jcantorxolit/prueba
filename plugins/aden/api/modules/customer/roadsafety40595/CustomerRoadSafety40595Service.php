<?php

namespace AdeN\Api\Modules\Customer\RoadSafety40595;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Classes\Criteria;
use DB;
use Log;
use Str;
use Carbon\Carbon;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Helpers\SqlHelper;

use Illuminate\Database\Eloquent\Collection;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\CriteriaHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use Wgroup\SystemParameter\SystemParameter;

class CustomerRoadSafety40595Service extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function canCreate($criteria)
    {
        if ($criteria instanceof Criteria) {
            $customerField = CriteriaHelper::getMandatoryFilter($criteria, 'customerId');
        }

        return DB::table('wg_customer_road_safety_40595')
            ->where('customer_id', $customerField ? $customerField->value : $criteria->customerId)
            //->where('period', '=', DB::raw("YEAR(NOW())"))
            ->count() == 0;
    }

    public function getMigrateFromId($period, $customerId)
    {
        return ($item = DB::table('wg_customer_road_safety_40595')
            ->select('id')
            ->where('period', '<', $period)
            ->where('status', 'C')
            ->where('customer_id', $customerId)
            ->orderBy('period', 'DESC')
            ->first()) ? $item->id : 0;
    }

    public function migrateFrom($criteria)
    {
        $qDetail = DB::table('wg_customer_road_safety_item_40595')
            ->select(
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id'
            )
            ->whereRaw("wg_customer_road_safety_item_40595.status = 'activo'")
            ->whereRaw("wg_customer_road_safety_item_40595.customer_road_safety_id = {$criteria->fromId}");

        DB::table('wg_customer_road_safety_item_40595')
            ->join(DB::raw("({$qDetail->toSql()}) as wg_customer_road_safety_item_40595_from"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595_from.road_safety_item_id', '=', 'wg_customer_road_safety_item_40595.road_safety_item_id');
            })->mergeBindings($qDetail)
            ->where('wg_customer_road_safety_item_40595.customer_road_safety_id', $criteria->id)
            ->update([
                'wg_customer_road_safety_item_40595.rate_id' => DB::raw('wg_customer_road_safety_item_40595_from.rate_id'),
                'wg_customer_road_safety_item_40595.updated_at' => DB::raw('NOW()'),
                'wg_customer_road_safety_item_40595.updated_by' => $criteria->userId
            ]);
    }

    public function bulkCancelRoadSafetyImprovementPlanActionPlanTask()
    {
        DB::table('wg_customer_improvement_plan_action_plan_task')
            ->join("wg_customer_improvement_plan_action_plan", function ($join) {
                $join->on('wg_customer_improvement_plan_action_plan.id', '=', 'wg_customer_improvement_plan_action_plan_task.customer_improvement_plan_action_plan_id');
            })
            ->join("wg_customer_improvement_plan", function ($join) {
                $join->on('wg_customer_improvement_plan.id', '=', 'wg_customer_improvement_plan_action_plan.customer_improvement_plan_id');
            })
            ->join("wg_customer_road_safety_item_40595", function ($join) {
                $join->on('wg_customer_road_safety_item_40595.id', '=', 'wg_customer_improvement_plan.entityId');
            })
            ->join("wg_customer_road_safety_40595", function ($join) {
                $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
            })
            ->where('wg_customer_road_safety_40595.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_road_safety_40595.status', 'A')
            ->where('wg_customer_improvement_plan_action_plan_task.status', 'A')
            ->where('wg_customer_improvement_plan.entityName', 'PESV_40595')
            ->whereRaw("DATE(wg_customer_improvement_plan.endDate) < DATE(NOW())")
            ->update([
                'wg_customer_improvement_plan_action_plan_task.reason' => DB::raw("CONCAT(wg_customer_improvement_plan_action_plan_task.reason,'Cancelar:FINALIZACIÓN DE PERIODO DE AUTOEVALUACIÓN |')"),
                'wg_customer_improvement_plan_action_plan_task.updated_at' => DB::raw('NOW()'),
                'wg_customer_improvement_plan_action_plan_task.status' => 'C'
            ]);
    }

    public function bulkInsertRoadSafetyImprovementPlanActionPlanComment()
    {
        $query = DB::table('wg_customer_improvement_plan_action_plan')
            ->join("wg_customer_improvement_plan", function ($join) {
                $join->on('wg_customer_improvement_plan.id', '=', 'wg_customer_improvement_plan_action_plan.customer_improvement_plan_id');
            })
            ->join("wg_customer_road_safety_item_40595", function ($join) {
                $join->on('wg_customer_road_safety_item_40595.id', '=', 'wg_customer_improvement_plan.entityId');
            })
            ->join("wg_customer_road_safety_40595", function ($join) {
                $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
            })
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_improvement_plan_action_plan.id AS customer_improvement_plan_action_plan_id',
                DB::raw("'FINALIZACIÓN DE PERIODO DE AUTOEVALUACIÓN' AS reason"),
                'wg_customer_improvement_plan_action_plan.status AS oldStatus',
                DB::raw("NOW() AS createdAt"),
                DB::raw("1 AS createdBy"),
                DB::raw("NOW() AS updatedAt"),
                DB::raw("1 AS updatedBy")
            )
            ->where('wg_customer_improvement_plan.entityName', 'PESV_40595')
            ->where('wg_customer_road_safety_40595.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_road_safety_40595.status', 'A')
            ->where('wg_customer_improvement_plan_action_plan.status', 'AB')
            ->whereRaw("DATE(wg_customer_improvement_plan.endDate) < DATE(NOW())");

        $sql = 'INSERT INTO wg_customer_improvement_plan_action_plan_comment (`id`, `customer_improvement_plan_action_plan_id`, `reason`, `old_status`, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public function bulkCancelRoadSafetyImprovementPlanActionPlan()
    {
        DB::table('wg_customer_improvement_plan_action_plan')
            ->join("wg_customer_improvement_plan", function ($join) {
                $join->on('wg_customer_improvement_plan.id', '=', 'wg_customer_improvement_plan_action_plan.customer_improvement_plan_id');
            })
            ->join("wg_customer_road_safety_item_40595", function ($join) {
                $join->on('wg_customer_road_safety_item_40595.id', '=', 'wg_customer_improvement_plan.entityId');
            })
            ->join("wg_customer_road_safety_40595", function ($join) {
                $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
            })
            ->where('wg_customer_improvement_plan.entityName', 'PESV_40595')
            ->where('wg_customer_road_safety_40595.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_road_safety_40595.status', 'A')
            ->where('wg_customer_improvement_plan_action_plan.status', 'AB')
            ->whereRaw("DATE(wg_customer_improvement_plan.endDate) < DATE(NOW())")
            ->update([
                'wg_customer_improvement_plan_action_plan.updated_at' => DB::raw('NOW()'),
                'wg_customer_improvement_plan_action_plan.status' => 'CA'
            ]);
    }

    public function bulkInsertRoadSafetyImprovementPlanComment()
    {
        $query = DB::table('wg_customer_improvement_plan')
            ->join("wg_customer_road_safety_item_40595", function ($join) {
                $join->on('wg_customer_road_safety_item_40595.id', '=', 'wg_customer_improvement_plan.entityId');
            })
            ->join("wg_customer_road_safety_40595", function ($join) {
                $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
            })
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_improvement_plan.id AS customer_improvement_plan_id',
                DB::raw("'FINALIZACIÓN DE PERIODO DE AUTOEVALUACIÓN' AS reason"),
                'wg_customer_improvement_plan.status AS oldStatus',
                DB::raw("NOW() AS createdAt"),
                DB::raw("1 AS createdBy"),
                DB::raw("NOW() AS updatedAt"),
                DB::raw("1 AS updatedBy")
            )
            ->where('wg_customer_improvement_plan.entityName', 'PESV_40595')
            ->where('wg_customer_road_safety_40595.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_road_safety_40595.status', 'A')
            ->where('wg_customer_improvement_plan.status', 'AB')
            ->whereRaw("DATE(wg_customer_improvement_plan.endDate) < DATE(NOW())");

        $sql = 'INSERT INTO wg_customer_improvement_plan_comment (`id`, `customer_improvement_plan_id`, `reason`, `old_status`, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public function bulkCancelRoadSafetyImprovementPlan()
    {
        DB::table('wg_customer_improvement_plan')
            ->join("wg_customer_road_safety_item_40595", function ($join) {
                $join->on('wg_customer_road_safety_item_40595.id', '=', 'wg_customer_improvement_plan.entityId');
            })
            ->join("wg_customer_road_safety_40595", function ($join) {
                $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
            })
            ->where('wg_customer_improvement_plan.entityName', 'PESV_40595')
            ->where('wg_customer_road_safety_40595.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_road_safety_40595.status', 'A')
            ->where('wg_customer_improvement_plan.status', 'AB')
            ->whereRaw("DATE(wg_customer_improvement_plan.endDate) < DATE(NOW())")
            ->update([
                'wg_customer_improvement_plan.updated_at' => DB::raw('NOW()'),
                'wg_customer_improvement_plan.status' => 'CA'
            ]);
    }

    public function bulkCancelRoadSafety()
    {
        DB::table('wg_customer_road_safety_40595')
            ->where('wg_customer_road_safety_40595.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_road_safety_40595.status', 'A')
            ->update([
                'wg_customer_road_safety_40595.updated_at' => DB::raw('NOW()'),
                'wg_customer_road_safety_40595.end_date' => DB::raw('NOW()'),
                'wg_customer_road_safety_40595.status' => 'C'
            ]);
    }

    public function bulkInsertRoadSafetyChangePeriod()
    {
        $query = DB::table('wg_customer_road_safety_40595')
            ->leftjoin("wg_customer_road_safety_40595 AS wg_customer_road_safety_40595_destination", function ($join) {
                $join->on('wg_customer_road_safety_40595_destination.customer_id', '=', 'wg_customer_road_safety_40595.customer_id');
                $join->on('wg_customer_road_safety_40595_destination.period', '=', DB::raw('YEAR(NOW())'));
            })
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_road_safety_40595.customer_id',
                DB::raw("NULL AS start_date"),
                DB::raw("NULL AS end_date"),
                DB::raw("'A' AS status"),
                DB::raw("'EM' AS type"),
                DB::raw("YEAR(NOW()) AS period"),
                DB::raw("'Auto Evaluación' AS description"),
                DB::raw("NOW() AS created_at"),
                DB::raw("1 AS created_by"),
                DB::raw("NULL AS updated_at"),
                DB::raw("NULL AS updated_by")
            )
            ->where('wg_customer_road_safety_40595.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_road_safety_40595.status', 'C')
            ->whereNull('wg_customer_road_safety_40595_destination.id');

        $sql = 'INSERT INTO wg_customer_road_safety_40595 (`id`, `customer_id`, `start_date`, `end_date`, status, `type`, `period`, `description`, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();
        Log::info("bulkInsertRoadSafetyChangePeriod::" . $sql);
        DB::statement($sql, $query->getBindings());
    }

    public function bulkInsertRoadSafetyItemsChangePeriod()
    {
        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join("wg_customer_road_safety_40595", function ($join) {
                $join->on('wg_customer_road_safety_40595.period', '=', DB::raw('YEAR(NOW())'));
                $join->where('wg_customer_road_safety_40595.status', '=', 'A');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_road_safety_40595.customer_id');
            })
            ->join("wg_road_safety_item_criterion_40595", function ($join) {
                $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
                $join->on('wg_road_safety_item_criterion_40595.size', '=', 'wg_customers.totalEmployee');
            })
            ->leftjoin("wg_customer_road_safety_item_40595", function ($join) {
                $join->on('wg_customer_road_safety_item_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_40595.id');
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            })
            ->select(
                DB::raw("NULL AS id"),
                "wg_customer_road_safety_40595.id AS customer_road_safety_id",
                "wg_road_safety_item_40595.id AS road_safety_item_id",
                DB::raw("NULL AS rate_id"),
                DB::raw("'activo' AS status"),
                DB::raw("NOW() AS created_at"),
                DB::raw("1 AS created_by"),
                DB::raw("NOW() AS updated_at"),
                DB::raw("1 AS updated_by")
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->whereNull('wg_customer_road_safety_item_40595.road_safety_item_id');

        $sql = 'INSERT INTO wg_customer_road_safety_item_40595 (`id`, `customer_road_safety_id`, `road_safety_item_id`, `rate_id`, status, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        Log::info($sql);
        Log::info(json_encode($query->getBindings()));

        DB::statement($sql, $query->getBindings());
    }

    public function bulkInsertRoadSafetyItems($criteria)
    {
        if ($criteria == null) {
            return;
        }

        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
                $join->where('wg_customer_road_safety_40595.id', '=', $criteria->id);
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_road_safety_40595.customer_id');
            })
            // ->join("wg_road_safety_item_criterion_40595", function ($join) {
            //     $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            //     $join->on('wg_road_safety_item_criterion_40595.size', '=', 'wg_customer_road_safety_40595.size');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
                $join->on('wg_road_safety_item_40595.size', '=', 'wg_customer_road_safety_40595.size');
            })
            ->leftjoin("wg_customer_road_safety_item_40595", function ($join) {
                $join->on('wg_customer_road_safety_item_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_40595.id');
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_40595.id');
                $join->on('wg_road_safety_item_40595.size', '=', 'wg_customer_road_safety_40595.size');
            })
            ->select(
                DB::raw("NULL AS id"),
                DB::raw("? AS customer_road_safety_id"),
                "wg_road_safety_40595.id AS road_safety_item_id",
                DB::raw("NULL AS rate_id"),
                DB::raw("'activo' AS status"),
                DB::raw("NOW() AS created_at"),
                DB::raw("? AS created_by"),
                DB::raw("NOW() AS updated_at"),
                DB::raw("? AS updated_by")
            )
            ->addBinding($criteria->id, "select")
            ->addBinding($criteria->createdBy, "select")
            ->addBinding($criteria->createdBy, "select")
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->whereNull('wg_customer_road_safety_item_40595.road_safety_item_id');

        $sql = 'INSERT INTO wg_customer_road_safety_item_40595 (`id`, `customer_road_safety_id`, `road_safety_item_id`, `rate_id`, status, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    public function bulkUpdateRoadSafetyItems($criteria)
    {
        if ($criteria == null) {
            return;
        }

        $qDetail = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
                $join->where('wg_customer_road_safety_40595.id', '=', $criteria->id);
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_road_safety_40595.customer_id');
            })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
                $join->on('wg_road_safety_item_40595.size', '=', 'wg_customer_road_safety_40595.size');
            })
            ->join("wg_customer_road_safety_item_40595", function ($join) {
                $join->on('wg_customer_road_safety_item_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_40595.id');
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_40595.id');
                $join->on('wg_road_safety_item_40595.size', '=', 'wg_customer_road_safety_40595.size');
            })
            ->select(
                "wg_customer_road_safety_40595.id AS customer_road_safety_id",
                "wg_road_safety_40595.id AS road_safety_item_id"
            )
            ->whereRaw('wg_road_safety_40595.is_active = 1')
            ->whereRaw('wg_road_safety_item_40595.is_active = 1')
            ->whereRaw("wg_customer_road_safety_40595.id = {$criteria->id}")
            ->whereRaw("wg_road_safety_cycle_40595.status = 'activo'");

        return DB::table('wg_customer_road_safety_item_40595')
            ->join(DB::raw("({$qDetail->toSql()}) as wg_customer_road_safety_40595"), function ($join) {
                $join->on('wg_customer_road_safety_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
                $join->on('wg_customer_road_safety_40595.road_safety_item_id', '=', 'wg_customer_road_safety_item_40595.road_safety_item_id');
            })
            ->mergeBindings($qDetail)
            ->update([
                "wg_customer_road_safety_item_40595.status" => 'activo',
                //"wg_customer_road_safety_item_40595.updated_at" => Carbon::now(),
                "wg_customer_road_safety_item_40595.updated_by" => $criteria->updatedBy
            ]);
    }

    public function bulkDeleteRoadSafetyItems($criteria)
    {
        if ($criteria == null) {
            return;
        }

        $qDetail = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
                $join->where('wg_customer_road_safety_40595.id', '=', $criteria->id);
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_road_safety_40595.customer_id');
            })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
                $join->on('wg_road_safety_item_40595.size', '=', 'wg_customer_road_safety_40595.size');
            })
            ->join("wg_customer_road_safety_item_40595", function ($join) {
                $join->on('wg_customer_road_safety_item_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_40595.id');
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_40595.id');
                $join->on('wg_road_safety_item_40595.size', '=', 'wg_customer_road_safety_40595.size');
            })
            ->select(
                "wg_customer_road_safety_40595.id AS customer_road_safety_id",
                "wg_road_safety_40595.id AS road_safety_item_id"
            )
            ->whereRaw('wg_road_safety_40595.is_active = 1')
            ->whereRaw('wg_road_safety_item_40595.is_active = 1')
            ->whereRaw("wg_customer_road_safety_40595.id = {$criteria->id}")
            ->whereRaw("wg_road_safety_cycle_40595.status = 'activo'");

        $result = DB::table('wg_customer_road_safety_item_40595')
            ->leftjoin(DB::raw("({$qDetail->toSql()}) as wg_customer_road_safety_40595"), function ($join) {
                $join->on('wg_customer_road_safety_40595.customer_road_safety_id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
                $join->on('wg_customer_road_safety_40595.road_safety_item_id', '=', 'wg_customer_road_safety_item_40595.road_safety_item_id');
            })
            ->mergeBindings($qDetail)
            ->whereNull("wg_customer_road_safety_40595.road_safety_item_id")
            ->where("wg_customer_road_safety_item_40595.customer_road_safety_id", $criteria->id)
            ->update([
                "wg_customer_road_safety_item_40595.status" => 'inactivo',
                "wg_customer_road_safety_item_40595.rate_id" => null,
                //"wg_customer_road_safety_item_40595.updated_at" => Carbon::now(),
                "wg_customer_road_safety_item_40595.updated_by" => $criteria->updatedBy
            ]);


         $itemsToDelete =   DB::table('wg_customer_road_safety_item_40595')
            ->join("wg_customer_road_safety_40595", function ($join) {
                $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
            })
            ->where('wg_customer_road_safety_item_40595.status', 'inactivo')
            ->select('wg_customer_road_safety_item_40595.id')
            ->get()
            ->map(function($item) {
                return $item->id;
            })
            ->toArray();

        if (count($itemsToDelete) > 0) {
            DB::table('wg_customer_road_safety_item_comment_40595')->whereIn('customer_road_safety_item_id', $itemsToDelete)->delete();
            DB::table('wg_customer_road_safety_item_detail_40595')->whereIn('customer_road_safety_item_id', $itemsToDelete)->delete();
            DB::table('wg_customer_road_safety_item_document_40595')->whereIn('customer_road_safety_item_id', $itemsToDelete)->delete();
            DB::table('wg_customer_road_safety_item_verification_40595')->whereIn('customer_road_safety_item_id', $itemsToDelete)->delete();

            DB::table('wg_customer_improvement_plan')->whereIn('entityId', $itemsToDelete)->where('entityName', 'PESV_40595')->delete();
        }

        return $result;
    }

    public function getStats($criteria)
    {

        $entity = $this->findRoadSafety($criteria->customerRoadSafetyId);

        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
                $join->where('wg_customer_road_safety_40595.id', '=', $criteria->customerRoadSafetyId);
                $join->on('wg_customer_road_safety_40595.size', '=', 'wg_road_safety_item_40595.size');
            });

        if ($entity == null || $entity->status == 'A') {
            $qItems = $this->prepareQueryForItems($criteria);
            $query->leftjoin(DB::raw("({$qItems->toSql()}) AS wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.roadSafetyItemId', '=', 'wg_road_safety_40595.id');
            })->mergeBindings($qItems);
        } else {
            $qItems = $this->prepareQueryForItemsClosed($criteria);
            $query->leftjoin(DB::raw("({$qItems->toSql()}) AS wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.roadSafetyItemId', '=', 'wg_road_safety_40595.id');
            })->mergeBindings($qItems);
        }

        $query
            ->select(
                DB::raw("ROUND(SUM(CASE WHEN wg_customer_road_safety_item_40595.rateCode = 'cp' OR wg_customer_road_safety_item_40595.rateCode = 'nac' OR wg_customer_road_safety_item_40595.customerRoadSafetyItemId IS NULL THEN wg_road_safety_item_40595.value ELSE 0 END), 2) AS total")
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1);

        \Log::info($query->toSql());

        return $query->first();
    }

    public function getCycles($criteria)
    {
        $entity = $this->findRoadSafety($criteria->customerRoadSafetyId);

        if ($entity == null || $entity->status == 'A') {
            $qSub = $this->prepareSubQuery($criteria);
        } else {
            $qSub = $this->prepareSubQueryClosed($criteria);
        }

        $qSub->select(
            'wg_road_safety_item_40595.id',
            'wg_road_safety_item_40595.name',
            'wg_road_safety_item_40595.road_safety_id',
            'wg_road_safety_item_40595.description',
            'wg_road_safety_item_40595.abbreviation',
            DB::raw("COUNT(*) AS items"),
            DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 0 ELSE 1 END) AS checked"),
            DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac' THEN wg_road_safety_item_40595.value ELSE 0 END) AS total"),
            DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
            DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
            DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nas' THEN 1 ELSE 0 END) AS no_apply_with_justification"),
            DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nac' THEN 1 ELSE 0 END) AS no_apply_without_justification"),
            DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 1 ELSE 0 END) AS no_checked")
        )->groupBy(
            'wg_road_safety_item_40595.id',
            'wg_road_safety_item_40595.name'
        );

        $query = DB::table(DB::raw("({$qSub->toSql()}) as wg_customer_road_safety_40595"))
            ->mergeBindings($qSub)
            ->select(
                "wg_customer_road_safety_40595.name",
                "wg_customer_road_safety_40595.description",
                "wg_customer_road_safety_40595.items",
                "wg_customer_road_safety_40595.checked",
                DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.checked / wg_customer_road_safety_40595.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
                "wg_customer_road_safety_40595.id",
                "wg_customer_road_safety_40595.abbreviation",
                DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.total / wg_customer_road_safety_40595.items), 0), 2) AS average")
            )
            ->orderBy("wg_customer_road_safety_40595.id");

        \Log::info($query->toSql());

        return $query->get()->toArray();
    }

    public function getParent($criteria)
    {
        $q1 = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join("wg_road_safety_item_criterion_40595", function ($join) {
                $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.totalEmployee', '=', 'wg_road_safety_item_criterion_40595.size');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->where('wg_customers.id', $criteria->customerId);

        $q2 = DB::table('wg_customer_road_safety_item_40595')
            ->join("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->where('wg_customer_road_safety_item_40595.status', 'activo')
            ->where('wg_customer_road_safety_item_40595.customer_road_safety_id', $criteria->customerRoadSafetyId);


        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_road_safety_item_40595.id AS cycleId',
                'wg_road_safety_item_40595.name AS cycleName',
                'wg_road_safety_item_40595.road_safety_id AS roadSafetyParentId',
                'wg_road_safety_item_40595.description',
                'wg_road_safety_item_40595.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac' THEN wg_road_safety_item_40595.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description'
            )
            ->orderBy('wg_road_safety_item_40595.id');

        return array_map(function ($row) {
            $row->description = "({$row->cycleName}) {$row->description}";
            return $row;
        }, $query->get()->toArray());
    }

    public function getChildren($criteria)
    {
        $q1 = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join("wg_road_safety_item_criterion_40595", function ($join) {
                $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.totalEmployee', '=', 'wg_road_safety_item_criterion_40595.size');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_40595.parent_id AS road_safety_parent_id',
                'wg_road_safety_40595.description',
                'wg_road_safety_40595.numeral',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->where('wg_customers.id', $criteria->customerId);

        $q2 = DB::table('wg_customer_road_safety_item_40595')
            ->join("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->where('wg_customer_road_safety_item_40595.status', 'activo')
            ->where('wg_customer_road_safety_item_40595.customer_road_safety_id', $criteria->customerRoadSafetyId);


        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_road_safety_item_40595.id AS cycleId',
                'wg_road_safety_item_40595.name AS cycleName',
                'wg_road_safety_item_40595.road_safety_id AS roadSafetyId',
                'wg_road_safety_item_40595.road_safety_parent_id AS roadSafetyParentId',
                'wg_road_safety_item_40595.description',
                'wg_road_safety_item_40595.abbreviation',
                'wg_road_safety_item_40595.numeral',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac' THEN wg_road_safety_item_40595.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description'
            )
            ->orderBy('wg_road_safety_item_40595.id');

        return array_map(function ($row) {
            $row->description = "{$row->numeral} - {$row->description}";
            return $row;
        }, $query->get()->toArray());
    }

    public function getItems($criteria)
    {
        $query = $this->prepareQueryForItems($criteria);

        return $query->get()->toArray();
    }

    public function getYears($criteria)
    {
        return DB::table('wg_customer_road_safety_tracking_40595')
            ->select(
                "wg_customer_road_safety_tracking_40595.year AS item",
                "wg_customer_road_safety_tracking_40595.year AS value"
            )
            ->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->groupBy(
                "wg_customer_road_safety_tracking_40595.customer_road_safety_id"
            )
            ->orderBy("wg_customer_road_safety_tracking_40595.year", "DESC")
            ->get()
            ->toArray();
    }

    public function getReport($criteria)
    {
        $cycles = $this->getCycles($criteria);
        $items = $this->getItems($criteria);

        foreach ($cycles as $cycle) {
            foreach ($items as $item) {
                if ($item->cycleId == $cycle->id) {
                    $item->rate = null;
                    if ($item->rateId) {
                        $item->rate = [
                            "id" => $item->rateId,
                            "text" => $item->rateText,
                            "code" => $item->rateCode,
                            "value" => $item->rateValue,
                            "color" => $item->rateColor,
                        ];
                    }

                    $cycle->children[] = $item;
                }
            }
        }

        return array_filter($cycles, function ($cycle) {
            return isset($cycle->children) && count($cycle->children) > 0;
        });
    }

    public function getChartBar($criteria)
    {
        $entity = $this->findRoadSafety($criteria->customerRoadSafetyId);

        if ($entity == null || $entity->status == 'A') {
            $qItems = $this->prepareQueryForItems($criteria);
        } else {
            $qItems = $this->prepareQueryForItemsClosed($criteria);
        }

        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join(DB::raw("({$qItems->toSql()}) AS wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.roadSafetyItemId', '=', 'wg_road_safety_40595.id');
                $join->on('wg_customer_road_safety_item_40595.size', '=', 'wg_road_safety_item_40595.size');
            })
            ->mergeBindings($qItems)
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_cycle_40595.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.customerRoadSafetyItemId) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.rateCode = 'cp' OR wg_customer_road_safety_item_40595.rateCode = 'nac' OR wg_customer_road_safety_item_40595.customerRoadSafetyItemId IS NULL THEN wg_road_safety_item_40595.value ELSE 0 END) AS total"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.rateCode = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.rateCode = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.rateCode = 'nac' OR wg_customer_road_safety_item_40595.customerRoadSafetyItemId IS NULL THEN 1 ELSE 0 END) AS no_apply_with_justification"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.customerRoadSafetyId IS NOT NULL AND ISNULL(wg_customer_road_safety_item_40595.rateCode) THEN 1 ELSE 0 END) AS no_checked")
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->groupBy(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name'
            )
            ->orderBy('wg_road_safety_cycle_40595.id');

        /*$qSub = $this->prepareSubQuery($criteria);

        $data = $qSub->select(
            'wg_road_safety_item_40595.id',
            'wg_road_safety_item_40595.name',
            'wg_road_safety_item_40595.road_safety_id',
            'wg_road_safety_item_40595.abbreviation',
            DB::raw("COUNT(*) AS items"),
            DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 0 ELSE 1 END) AS checked"),
            DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac' THEN wg_road_safety_item_40595.value ELSE 0 END) AS total"),
            DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
            DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
            DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nas' THEN 1 ELSE 0 END) AS no_apply_with_justification"),
            DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nac' THEN 1 ELSE 0 END) AS no_apply_without_justification"),
            DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 1 ELSE 0 END) AS no_checked")
        )
            ->groupBy(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name'
            )
            ->orderBy('wg_road_safety_item_40595.id')
            ->get();*/

        //\Log::info($query->toSql());

        $data = $query->get();

        $config = array(
            "labelColumn" => 'abbreviation',
            "valueColumns" => [
                ['label' => 'Sin Evaluar', 'field' => 'no_checked'],
                ['label' => 'Cumple', 'field' => 'accomplish'],
                ['label' => 'No Cumple', 'field' => 'no_accomplish']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartPie($criteria)
    {
        $qSub = $this->preparePieSubQuery($criteria);

        //        \Log::info($qSub->toSql());

        $data = $qSub->select(
            'wg_road_safety_item_40595_stats.name AS label',
            DB::raw("ROUND(SUM(CASE WHEN (wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac')
                                OR (wg_customer_road_safety_item_40595.id IS NULL AND wg_road_safety_item_40595.id IS NULL)
                            THEN wg_road_safety_item_40595_stats.value ELSE 0 END), 2) AS `value`")
        )
            ->groupBy(
                'wg_road_safety_item_40595_stats.id',
                'wg_road_safety_item_40595_stats.name'
            )
            ->orderBy('wg_road_safety_item_40595_stats.id')
            ->get();

        return $this->chart->getChartPie($data);
    }

    public function getChartStatus($criteria)
    {
        $qSub = DB::table('wg_customer_road_safety_tracking_40595')
            ->join("wg_road_safety_cycle_40595", function ($join) {
                $join->on('wg_road_safety_cycle_40595.id', '=', 'wg_customer_road_safety_tracking_40595.road_safety_cycle');
            })
            ->select(
                DB::raw("IFNULL(SUM(wg_customer_road_safety_tracking_40595.accomplish), 0) AS accomplish"),
                DB::raw("IFNULL(SUM(wg_customer_road_safety_tracking_40595.no_apply_with_justification), 0) AS no_apply_with_justification"),
                DB::raw("IFNULL(SUM(wg_customer_road_safety_tracking_40595.no_accomplish), 0) AS no_accomplish"),
                DB::raw("IFNULL(SUM(wg_customer_road_safety_tracking_40595.no_apply_without_justification), 0) AS no_apply_without_justification"),
                DB::raw("IFNULL(SUM(wg_customer_road_safety_tracking_40595.no_checked), 0) AS no_checked"),
                "wg_customer_road_safety_tracking_40595.year",
                "wg_customer_road_safety_tracking_40595.month"
            )
            ->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year)
            ->groupBy(
                "wg_customer_road_safety_tracking_40595.customer_road_safety_id",
                "wg_customer_road_safety_tracking_40595.month"
            );

        $data = DB::table('system_parameters')
            ->leftjoin(DB::raw("({$qSub->toSql()}) as wg_customer_road_safety_tracking_40595"), function ($join) {
                $join->on('wg_customer_road_safety_tracking_40595.month', '=', 'system_parameters.value');
            })
            ->mergeBindings($qSub)
            ->select(
                "system_parameters.item AS label",
                DB::raw("IFNULL(wg_customer_road_safety_tracking_40595.accomplish, 0) AS accomplish"),
                DB::raw("IFNULL(wg_customer_road_safety_tracking_40595.no_apply_with_justification, 0) AS no_apply_with_justification"),
                DB::raw("IFNULL(wg_customer_road_safety_tracking_40595.no_accomplish, 0) AS no_accomplish"),
                DB::raw("IFNULL(wg_customer_road_safety_tracking_40595.no_apply_without_justification, 0) AS no_apply_without_justification"),
                DB::raw("IFNULL(wg_customer_road_safety_tracking_40595.no_checked, 0) AS no_checked")
            )
            ->where("system_parameters.group", 'month')
            ->get();

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => [
                ['label' => 'Sin Evaluar', 'field' => 'no_checked'],
                ['label' => 'Cumple', 'field' => 'accomplish'],
                ['label' => 'No Cumple', 'field' => 'no_accomplish'],
                ['label' => 'No Aplica', 'field' => 'no_apply_with_justification'],
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartAverage($criteria)
    {
        $data = DB::table('wg_customer_road_safety_tracking_40595')
            ->join("wg_road_safety_cycle_40595", function ($join) {
                $join->on('wg_road_safety_cycle_40595.id', '=', 'wg_customer_road_safety_tracking_40595.road_safety_cycle');
            })
            ->select(
                'wg_road_safety_cycle_40595.abbreviation AS label',
                DB::raw("ROUND(SUM(CASE WHEN month = 1 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'JAN'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 2 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'FEB'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 3 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'MAR'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 4 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'APR'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 5 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'MAY'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 6 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'JUN'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 7 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'JUL'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 8 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'AUG'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 9 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'SEP'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 10 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'OCT'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 11 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'NOV'"),
                DB::raw("ROUND(SUM(CASE WHEN month = 12 THEN IFNULL(wg_customer_road_safety_tracking_40595.avg_total,0) END),2) 'DEC'")
            )
            ->where('wg_customer_road_safety_tracking_40595.customer_road_safety_id', $criteria->customerRoadSafetyId)
            ->where('wg_customer_road_safety_tracking_40595.year', $criteria->year)
            ->groupBy('wg_customer_road_safety_tracking_40595.road_safety_cycle')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getChartTotal($criteria)
    {
        $qSub = DB::table('wg_customer_road_safety_tracking_40595')
            ->select(
                DB::raw("'Puntaje Total % (calificación)' AS label"),
                DB::raw("ROUND(SUM(wg_customer_road_safety_tracking_40595.total), 2) AS value"),
                "wg_customer_road_safety_tracking_40595.customer_road_safety_id",
                "wg_customer_road_safety_tracking_40595.year",
                "wg_customer_road_safety_tracking_40595.month"
            )
            ->groupBy(
                "wg_customer_road_safety_tracking_40595.customer_road_safety_id",
                "wg_customer_road_safety_tracking_40595.year",
                "wg_customer_road_safety_tracking_40595.month"
            );

        $data = DB::table(DB::raw("({$qSub->toSql()}) as wg_customer_road_safety_tracking_40595"))
            ->mergeBindings($qSub)
            ->select(
                "wg_customer_road_safety_tracking_40595.label",
                DB::raw("MAX(CASE WHEN month = 1 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'JAN'"),
                DB::raw("MAX(CASE WHEN month = 2 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'FEB'"),
                DB::raw("MAX(CASE WHEN month = 3 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'MAR'"),
                DB::raw("MAX(CASE WHEN month = 4 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'APR'"),
                DB::raw("MAX(CASE WHEN month = 5 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'MAY'"),
                DB::raw("MAX(CASE WHEN month = 6 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'JUN'"),
                DB::raw("MAX(CASE WHEN month = 7 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'JUL'"),
                DB::raw("MAX(CASE WHEN month = 8 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'AUG'"),
                DB::raw("MAX(CASE WHEN month = 9 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'SEP'"),
                DB::raw("MAX(CASE WHEN month = 10 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'OCT'"),
                DB::raw("MAX(CASE WHEN month = 11 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'NOV'"),
                DB::raw("MAX(CASE WHEN month = 12 THEN IFNULL(wg_customer_road_safety_tracking_40595.value,0) END) 'DEC'")
            )
            ->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year)
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getChartAdvance($criteria)
    {
        $qSub = DB::table('wg_customer_road_safety_tracking_40595')
            ->select(
                DB::raw("'Avance % (respuestas / preguntas)' AS label"),
                DB::raw("(SUM(wg_customer_road_safety_tracking_40595.checked) / SUM(wg_customer_road_safety_tracking_40595.items)) * 100 AS value"),
                "wg_customer_road_safety_tracking_40595.customer_road_safety_id",
                "wg_customer_road_safety_tracking_40595.year",
                "wg_customer_road_safety_tracking_40595.month"
            )
            ->groupBy(
                "wg_customer_road_safety_tracking_40595.customer_road_safety_id",
                "wg_customer_road_safety_tracking_40595.year",
                "wg_customer_road_safety_tracking_40595.month"
            );

        $data = DB::table(DB::raw("({$qSub->toSql()}) as wg_customer_road_safety_tracking_40595"))
            ->mergeBindings($qSub)
            ->select(
                "wg_customer_road_safety_tracking_40595.label",
                DB::raw("MAX(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'JAN'"),
                DB::raw("MAX(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'FEB'"),
                DB::raw("MAX(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'MAR'"),
                DB::raw("MAX(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'APR'"),
                DB::raw("MAX(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'MAY'"),
                DB::raw("MAX(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'JUN'"),
                DB::raw("MAX(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'JUL'"),
                DB::raw("MAX(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'AUG'"),
                DB::raw("MAX(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'SEP'"),
                DB::raw("MAX(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'OCT'"),
                DB::raw("MAX(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'NOV'"),
                DB::raw("MAX(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_road_safety_tracking_40595.value,0),2) END) 'DEC'")
            )
            ->where("wg_customer_road_safety_tracking_40595.customer_road_safety_id", $criteria->customerRoadSafetyId)
            ->where("wg_customer_road_safety_tracking_40595.year", $criteria->year)
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getExportSummaryData($criteria)
    {
        $q1 = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            // ->join("wg_road_safety_item_criterion_40595", function ($join) {
            //     $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            // })
            ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
                $join->where('wg_customer_road_safety_40595.id', '=', $criteria->customerRoadSafetyId);
                $join->on('wg_customer_road_safety_40595.size', '=', 'wg_road_safety_item_40595.size');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_cycle_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1);

        $q2 = DB::table('wg_customer_road_safety_item_40595')
            ->join("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->where('wg_customer_road_safety_item_40595.status', 'activo');

        //$q1->where(SqlHelper::getPreparedField('wg_customers.id'), $criteria->customerId);
        $q2->where(SqlHelper::getPreparedField('wg_customer_road_safety_item_40595.customer_road_safety_id'), $criteria->customerRoadSafetyId);

        $sQuery = DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description',
                'wg_road_safety_item_40595.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac' THEN wg_road_safety_item_40595.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name'
                //'wg_road_safety_item_40595.road_safety_id',
                //'wg_road_safety_item_40595.description'
            );

        $query = DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_road_safety_40595"))
            ->mergeBindings($sQuery)
            ->select(
                "wg_customer_road_safety_40595.name",
                "wg_customer_road_safety_40595.description",
                "wg_customer_road_safety_40595.items",
                "wg_customer_road_safety_40595.checked",
                DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.checked / wg_customer_road_safety_40595.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
                "wg_customer_road_safety_40595.id",
                "wg_customer_road_safety_40595.abbreviation",
                DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.total / wg_customer_road_safety_40595.items), 0), 2) AS average"),
                DB::raw("CASE WHEN wg_customer_road_safety_40595.checked = wg_customer_road_safety_40595.items THEN 'Completado' WHEN wg_customer_road_safety_40595.checked > 0 THEN 'Iniciado' ELSE 'Sin Iniciar' END AS status")
            );

        \Log::info($query->toSql());

        $heading = [
            "CICLO" => "name",
            "PARÁMETRO" => "description",
            "VARIABLES" => "items",
            "EVALUADOS" => "checked",
            "AVANCE CICLO (%)" => "advance",
            "VALORACIÓN VARIABLES (%)" => "total",
            "ESTADO" => "status"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }

    public function getExportSummaryDataClosed($criteria)
    {
        $q1 = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_parent_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1);

        $q2 = DB::table('wg_customer_road_safety_item_40595')
            ->leftjoin("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->where(SqlHelper::getPreparedField('wg_customer_road_safety_item_40595.customer_road_safety_id'), $criteria->customerRoadSafetyId)
            ->where('wg_customer_road_safety_item_40595.status', 'activo')
            ->where('wg_customer_road_safety_item_40595.is_freezed', 1);

        $sQuery = DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595"))
            ->join(DB::raw("({$q2->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description',
                'wg_road_safety_item_40595.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac' THEN wg_road_safety_item_40595.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description'
            );

        $query = DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_road_safety_40595"))
            ->mergeBindings($sQuery)
            ->select(
                "wg_customer_road_safety_40595.name",
                "wg_customer_road_safety_40595.description",
                "wg_customer_road_safety_40595.items",
                "wg_customer_road_safety_40595.checked",
                DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.checked / wg_customer_road_safety_40595.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
                "wg_customer_road_safety_40595.id",
                "wg_customer_road_safety_40595.abbreviation",
                DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.total / wg_customer_road_safety_40595.items), 0), 2) AS average"),
                DB::raw("CASE WHEN wg_customer_road_safety_40595.checked = wg_customer_road_safety_40595.items THEN 'Completado' WHEN wg_customer_road_safety_40595.checked > 0 THEN 'Iniciado' ELSE 'Sin Iniciar' END AS status")
            );

        $heading = [
            "CICLO" => "name",
            "ESTÁNDAR" => "description",
            "ITEMS" => "items",
            "EVALUADOS" => "checked",
            "VALOR ESTÁNDAR (%)" => "advance",
            "VALORACIÓN ITEMS (%)" => "total",
            "ESTADO" => "status"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }


    public function getExportPdfData($criteria)
    {
        $entity = $this->findRoadSafety($criteria->customerRoadSafetyId);

        if ($entity == null || $entity->status == 'A') {
            $qItems = $this->prepareQueryForItems($criteria);
            $qStats = $this->prepareQueryForStandarStats($criteria);
        } else {
            $qItems = $this->prepareQueryForItemsClosed($criteria);
            $qStats = $this->prepareQueryForStandarStatsClosed($criteria);
        }

        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join("wg_road_safety_item_40595", function ($join) {
            //     $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            // })
            // ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
            //     $join->where('wg_customer_road_safety_40595.id', '=', $criteria->customerRoadSafetyId);
            //     $join->on('wg_customer_road_safety_40595.size', '=', 'wg_road_safety_item_40595.size');
            // })
            ->leftjoin(DB::raw("({$qItems->toSql()}) AS wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.roadSafetyItemId', '=', 'wg_road_safety_40595.id');
            })
            ->mergeBindings($qItems)
            ->leftjoin(DB::raw("({$qStats->toSql()}) AS road_safety_stats_40595"), function ($join) {
                $join->on('road_safety_stats_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->mergeBindings($qStats)
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_cycle_40595.description',
                'wg_road_safety_cycle_40595.id AS road_safety_parent_id',
                'wg_road_safety_cycle_40595.description AS road_safety_parent_description',

                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_40595.description AS road_safety_description',

                'road_safety_stats_40595.items AS road_safety_items',
                'road_safety_stats_40595.checked AS road_safety_checked',
                'road_safety_stats_40595.advance AS road_safety_advance',
                'road_safety_stats_40595.total AS road_safety_total',
                'road_safety_stats_40595.average AS road_safety_average',


                'wg_road_safety_40595.id AS road_safety_item_id',
                'wg_road_safety_40595.numeral AS road_safety_item_numeral',
                'wg_road_safety_40595.description AS road_safety_item_description',
                DB::raw("0 AS road_safety_item_value"),

                //'wg_customer_road_safety_item_40595.criterion',
                'wg_customer_road_safety_item_40595.rateCode',
                'wg_customer_road_safety_item_40595.rateId',
                'wg_customer_road_safety_item_40595.rateText',
                'wg_customer_road_safety_item_40595.rateValue',
                'wg_customer_road_safety_item_40595.customerRoadSafetyItemId'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            //->where('wg_road_safety_item_40595.is_active', 1)
            ->orderBy("wg_road_safety_cycle_40595.id")
            //->orderBy("wg_road_safety_parent_40595.id")
            ->orderBy("wg_road_safety_40595.id")
            ->orderBy("wg_road_safety_40595.numeral");

        \Log::info($query->toSql());

        $data = $query->get();

        if ($entity == null || $entity->status == 'A') {

            $customer = DB::table('wg_customers')
                ->join('wg_customer_road_safety_40595', function ($join) {
                    $join->on('wg_customer_road_safety_40595.customer_id', '=', 'wg_customers.id');
                })
                ->leftjoin(DB::raw(CustomerModel::getRelationInfoDetail('customer_info_detail')), function ($join) {
                    $join->on('customer_info_detail.entityId', '=', 'wg_customers.id');
                })
                ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_road_safety_misionallity')), function ($join) {
                    $join->on('wg_customer_road_safety_misionallity.value', '=', 'wg_customer_road_safety_40595.misionallity');
                })
                ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_road_safety_company_size')), function ($join) {
                    $join->on('wg_customer_road_safety_company_size.value', '=', 'wg_customer_road_safety_40595.size');
                })
                ->select(
                    'wg_customers.businessName AS name',
                    'wg_customers.documentNumber',
                    'customer_info_detail.address',
                    'customer_info_detail.telephone AS phone',
                    'wg_customer_road_safety_misionallity.item AS misionallity',
                    'wg_customer_road_safety_company_size.item AS misionallitySize'
                )
                ->where('wg_customers.id', $criteria->customerId)
                ->first();
        } else {
            $customer = DB::table('wg_customers')
                ->join('wg_customer_road_safety_40595', function ($join) {
                    $join->on('wg_customer_road_safety_40595.customer_id', '=', 'wg_customers.id');
                })
                ->leftjoin(DB::raw(CustomerModel::getRelationInfoDetail('customer_info_detail')), function ($join) {
                    $join->on('customer_info_detail.entityId', '=', 'wg_customers.id');
                })
                ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_road_safety_misionallity')), function ($join) {
                    $join->on('wg_customer_road_safety_misionallity.value', '=', 'wg_customer_road_safety_40595.misionallity');
                })
                ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_road_safety_company_size')), function ($join) {
                    $join->on('wg_customer_road_safety_company_size.value', '=', 'wg_customer_road_safety_40595.size');
                })
                ->select(
                    'wg_customers.businessName AS name',
                    'wg_customers.documentNumber',
                    'customer_info_detail.address',
                    'customer_info_detail.telephone AS phone',
                    'wg_customer_road_safety_misionallity.item AS misionallity',
                    'wg_customer_road_safety_company_size.item AS misionallitySize'
                )
                ->where('wg_customers.id', $criteria->customerId)
                ->where('wg_customer_road_safety_40595.id', $criteria->customerRoadSafetyId)
                ->first();
        }

        $header = DB::table(DB::raw("({$qItems->toSql()}) AS wg_customer_road_safety_item_40595"))
            ->mergeBindings($qItems)
            ->select(
                DB::raw('MIN(wg_customer_road_safety_item_40595.created_at) AS firstDate'),
                DB::raw('MAX(wg_customer_road_safety_item_40595.updated_at) AS lastDate')
            )
            ->groupBy('wg_customer_road_safety_item_40595.customerRoadSafetyId')
            ->first();

        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);

        $plans = $qItems
            //->mergeBindings($qItems)
            ->join('wg_customer_improvement_plan', function ($join) {
                $join->on('wg_customer_improvement_plan.customer_id', '=', 'wg_customer_road_safety_40595.customer_id');
                $join->on('wg_customer_improvement_plan.entityId', '=', 'wg_customer_road_safety_item_40595.id');
                $join->where('wg_customer_improvement_plan.entityName', '=', 'PESV_40595');
            })
            ->leftjoin("wg_customer_improvement_plan_action_plan", function ($join) {
                $join->on('wg_customer_improvement_plan_action_plan.customer_improvement_plan_id', '=', 'wg_customer_improvement_plan.id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('improvement_plan_status')), function ($join) {
                $join->on('wg_customer_improvement_plan.status', '=', 'improvement_plan_status.value');
            })
            ->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible_improvement_plan"), function ($join) {
                $join->on('wg_customer_improvement_plan.responsible', '=', 'responsible_improvement_plan.id');
                $join->on('wg_customer_improvement_plan.responsibleType', '=', 'responsible_improvement_plan.type');
                $join->on('wg_customer_improvement_plan.customer_id', '=', 'responsible_improvement_plan.customer_id');
            })
            ->mergeBindings($qAgentUser)
            ->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible_improvement_plan_action_plan"), function ($join) {
                $join->on('wg_customer_improvement_plan_action_plan.responsible', '=', 'responsible_improvement_plan_action_plan.id');
                $join->on('wg_customer_improvement_plan_action_plan.responsibleType', '=', 'responsible_improvement_plan_action_plan.type');
                $join->on('wg_customer_improvement_plan.customer_id', '=', 'responsible_improvement_plan_action_plan.customer_id');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('improvement_plan_action_plan_status')), function ($join) {
                $join->on('wg_customer_improvement_plan_action_plan.status', '=', 'improvement_plan_action_plan_status.value');

            })
            ->mergeBindings($qAgentUser)
            // ->join("wg_road_safety_40595", function ($join) {
            //     $join->on('wg_road_safety_40595.id', '=', 'wg_road_safety_item_40595.road_safety_id');
            // })
            ->select(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_40595.numeral',
                'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.value',

                'wg_customer_improvement_plan.id AS improvement_plan_id',
                'wg_customer_improvement_plan.description AS improvement_plan_description',
                'wg_customer_improvement_plan.endDate AS improvement_plan_end_date',
                'responsible_improvement_plan.name AS improvement_plan_responsible',

                'wg_customer_improvement_plan_action_plan.id AS action_plan_id',
                'wg_customer_improvement_plan_action_plan.activity AS action_plan_description',
                'wg_customer_improvement_plan_action_plan.endDate AS action_plan_end_date',
                'responsible_improvement_plan_action_plan.name AS action_plan_responsible',

                'improvement_plan_status.item AS improvement_plan_status',
                'improvement_plan_action_plan_status.item as action_plan_status'
            )
            ->orderBy('wg_road_safety_item_40595.id')
            ->get();

        $qChart = $this->preparePieSubQuery($criteria);

        $chart = $qChart
            ->select(
                'wg_road_safety_item_40595_stats.name AS label',
                DB::raw("ROUND(SUM(CASE WHEN (wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac')
                                OR (wg_customer_road_safety_item_40595.id IS NULL AND wg_road_safety_item_40595.id IS NULL)
                            THEN wg_road_safety_item_40595_stats.value ELSE 0 END), 2) AS `value`")
            )
            ->groupBy(
                'wg_road_safety_item_40595_stats.id',
                'wg_road_safety_item_40595_stats.name'
            )
            ->orderBy('wg_road_safety_item_40595_stats.id')
            ->get();

        $chartData = array_map(function ($item) {
            return [$item->label . ': ' . $item->value, floatval($item->value)];
        }, $chart->toArray());

        array_unshift($chartData, ['Cycle', 'Value']);

        return [
            "header" => [
                "date" => Carbon::now('America/Bogota')->format('d/m/Y'),
                "startDate" => $header ? Carbon::parse($header->firstDate)->timezone('America/Bogota')->format('d/m/Y') : null,
                "endDate" => $header ? Carbon::parse($header->lastDate)->timezone('America/Bogota')->format('d/m/Y') : null,
            ],
            "cycles" => $this->prepareStandardDataForPdf($data),
            "customer" => $customer,
            "chart" => [
                "total" => ($stats = $this->getStats($criteria)) ? floatval($stats->total) : 0,
                "data" => json_encode($chartData)
            ],
            "barChart" => [
                "data" => json_encode($this->getPdfChartBar($criteria))
            ],
            "plans" => $this->preparePlanDataForPdf($plans),
            "themeUrl" => CmsHelper::getThemeUrl(),
            "themePath" => CmsHelper::getThemePath()
        ];
    }

    private function getPdfChartBar($criteria)
    {
        $entity = $this->findRoadSafety($criteria->customerRoadSafetyId);

        if ($entity == null || $entity->status == 'A') {
            $qItems = $this->prepareQueryForItems($criteria);
        } else {
            $qItems = $this->prepareQueryForItemsClosed($criteria);
        }

        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join(DB::raw("({$qItems->toSql()}) AS wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.roadSafetyItemId', '=', 'wg_road_safety_40595.id');
                $join->on('wg_customer_road_safety_item_40595.size', '=', 'wg_road_safety_item_40595.size');
            })
            ->mergeBindings($qItems)
            ->select(
                // 'wg_road_safety_cycle_40595.name',
                // 'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_cycle_40595.abbreviation',
                //DB::raw("COUNT(*) AS items"),
                //DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.customerRoadSafetyItemId) THEN 0 ELSE 1 END) AS checked"),
                //DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.rateCode = 'cp' OR wg_customer_road_safety_item_40595.rateCode = 'nac' OR wg_customer_road_safety_item_40595.customerRoadSafetyItemId IS NULL THEN wg_road_safety_item_40595.value ELSE 0 END) AS total"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.rateCode = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.rateCode = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
                //DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.rateCode = 'nac' OR wg_customer_road_safety_item_40595.customerRoadSafetyItemId IS NULL THEN 1 ELSE 0 END) AS no_apply_with_justification"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.customerRoadSafetyId IS NOT NULL AND ISNULL(wg_customer_road_safety_item_40595.rateCode) THEN 1 ELSE 0 END) AS no_checked")
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->groupBy(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name'
            )
            ->orderBy('wg_road_safety_cycle_40595.id');

        $data = collect($query->get())->map(function($item) {
            return [
                $item->abbreviation, (float)$item->accomplish, (float)$item->no_accomplish, (float)$item->no_checked
            ];
        })->values()->toArray();

        array_unshift($data, ['Label', 'Cumple', 'No Cumple', 'Sin Contestar']);

        return $data;
    }

    private function prepareStandardDataForPdf($data)
    {
        $collection = new Collection($data);

        $cycle = $collection->groupBy('id');
        $parent = $collection->groupBy('road_safety_parent_id');
        $children = $collection->groupBy('road_safety_id');

        $defaultRate = DB::table('wg_road_safety_rate_40595')
            ->select('id', 'code', 'text', 'value', 'color', 'highlightColor')
            ->where('code', 'nac')
            ->first();

        $result = $cycle->map(function ($item, $key) use ($parent, $children, $defaultRate) {
            $cItem = (new Collection($item))->first();
            $cycle = new \stdClass();
            $cycle->id = $cItem->id;
            $cycle->name = $cItem->name;
            $cycle->abbreviation = $cItem->abbreviation;
            $cycle->items = count($item);

            $cycle->standards = $parent->filter(function ($item) use ($cycle) {
                return $item[0]->id == $cycle->id;
            })->map(function ($item, $key) use ($children, $defaultRate) {
                $pItem = (new Collection($item))->first();
                $parent = new \stdClass();
                $parent->id = $pItem->road_safety_parent_id;
                $parent->description = $pItem->road_safety_parent_description;
                $parent->total = count($item);

                $parent->children = $children->filter(function ($item) use ($parent) {
                    return $item[0]->road_safety_parent_id == $parent->id;
                })->map(function ($item, $key) use ($defaultRate) {
                    $childCollection = new Collection($item);
                    $childItem = $childCollection->first();
                    $standard = new \stdClass();
                    $standard->id = $childItem->road_safety_id;
                    $standard->description = $childItem->road_safety_description;
                    $standard->weight = $childCollection->sum("road_safety_item_value");
                    $standard->totalAverage = $childCollection->reduce(function ($carry, $item) use ($defaultRate) {
                        if ($item->customerRoadSafetyItemId && ($item->rateCode == 'cp' || $item->rateCode == 'nac')) {
                            $rateValue = $item->road_safety_item_value;
                        } else if (!$item->customerRoadSafetyItemId) {
                            $rateValue = $item->road_safety_item_value;
                        } else {
                            $rateValue = 0;
                        }
                        return $carry + floatval($rateValue);
                    });

                    $standard->items = $childCollection->map(function ($item, $key) use ($defaultRate) {
                        $cItem = new \stdClass();
                        $cItem->id = !empty($item->road_safety_item_id) ? $item->road_safety_item_id : null;
                        $cItem->description = $item->road_safety_item_description;
                        $cItem->numeral = !starts_with($item->road_safety_item_description, $item->road_safety_item_numeral) ? $item->road_safety_item_numeral : null;
                        //$cItem->value = floatval($item->road_safety_item_value);
                        $cItem->rate = $item->customerRoadSafetyItemId ? $this->parseRate($item) : $defaultRate;
                        return $cItem;
                    });

                    return $standard;
                });

                return $parent;
            });

            return $cycle;
        });

        return $result;
    }

    private function preparePlanDataForPdf($data)
    {
        $collection = new Collection($data);

        $plans = $collection->groupBy('improvement_plan_id');

        $result = $plans->map(function ($item, $key) {
            $planCollection = new Collection($item);
            $cItem = $planCollection->first();
            $plan = new \stdClass();
            $plan->description = str_replace($cItem->numeral, '', $cItem->description);
            $plan->numeral = $cItem->numeral;
            $plan->value = floatval($cItem->value);
            $plan->improvement_plan_description = $cItem->improvement_plan_description;
            $plan->improvement_plan_responsible = $cItem->improvement_plan_responsible;
            $plan->improvement_plan_status = $cItem->improvement_plan_status;
            $plan->improvement_plan_endDate = $cItem->improvement_plan_end_date ? Carbon::parse($cItem->improvement_plan_end_date)->format('d/m/Y') : null;

            $plan->actions = $planCollection->filter(function ($item) {
                return $item->action_plan_id != null;
            })->map(function ($item, $key) {
                $action = new \stdClass();
                $action->activity = $item->action_plan_description;
                $action->responsible = $item->action_plan_responsible;
                $action->status = $item->action_plan_status;
                $action->endDate = $item->action_plan_end_date ? Carbon::parse($item->action_plan_end_date)->format('d/m/Y') : null;

                return $action;
            });

            return $plan;
        });

        return $result;
    }

    private function parseRate($item)
    {
        $rate = new \stdClass();
        $rate->id = $item->rateId;
        $rate->code = $item->rateCode;
        $rate->text = $item->rateText;
        $rate->value = floatval($item->rateValue);

        return $rate;
    }

    private function preparePieSubQuery($criteria)
    {
        $q1 = $this->prepareQueryForConfigStandard($criteria);

        $entity = $this->findRoadSafety($criteria->customerRoadSafetyId);

        if ($entity == null || $entity->status == 'A') {
            $q2 = $this->prepareQueryForStandardParent($criteria);
            $q3 = $this->prepareQueryForItemsInnerJoinRate($criteria);
        } else {
            $q2 = $this->prepareQueryForStandardParentClosed($criteria);
            $q3 = $this->prepareQueryForItemsInnerJoinRateClosed($criteria);
        }

        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595_stats"))
            ->mergeBindings($q1)
            ->join(DB::raw("({$q2->toSql()}) as wg_road_safety_item_40595"), function ($join) {
                $join->on('wg_road_safety_item_40595.id', '=', 'wg_road_safety_item_40595_stats.id');
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_item_40595_stats.road_safety_id');
                $join->on('wg_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595_stats.road_safety_item_id');
            })
            ->mergeBindings($q2);

        if ($entity == null || $entity->status == 'A') {
            $query->leftjoin(DB::raw("({$q3->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_id');
            })
                ->mergeBindings($q3);
        } else {
            $query->join(DB::raw("({$q3->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_id');
            })
                ->mergeBindings($q3);
        }

        return $query;
    }

    private function prepareSubQuery($criteria)
    {
        $q1 = $this->prepareQueryForStandardParent($criteria);

        $q2 = $this->prepareQueryForItemsInnerJoinRate($criteria);

        return DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2);
    }

    private function prepareSubQueryClosed($criteria)
    {
        $q1 = $this->prepareQueryForStandardParentClosed($criteria);

        $q2 = $this->prepareQueryForItemsInnerJoinRateClosed($criteria);

        return DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2);
    }

    private function prepareQueryForConfigStandard($criteria)
    {
        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->whereRaw("wg_road_safety_cycle_40595.status = 'activo'")
            //->where('wg_road_safety_40595.is_active', 1)
            ->whereRaw('wg_road_safety_item_40595.is_active = 1');

        return $query;
    }


    private function prepareQueryForStandardParent($criteria)
    {
        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            // ->join("wg_road_safety_item_criterion_40595", function ($join) {
            //     $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            // })
            ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
                $join->where('wg_customer_road_safety_40595.id', '=', $criteria->customerRoadSafetyId);
                $join->on('wg_customer_road_safety_40595.size', '=', 'wg_road_safety_item_40595.size');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_cycle_40595.description',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->whereRaw("wg_road_safety_cycle_40595.status = 'activo'")
            ->whereRaw('wg_road_safety_40595.is_active = 1')
            ->whereRaw('wg_road_safety_item_40595.is_active = 1');

        return $query;
    }

    private function prepareQueryForStandardParentClosed($criteria)
    {
        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            // ->join(DB::raw("wg_road_safety_40595 AS wg_road_safety_parent_40595"), function ($join) {
            //     $join->on('wg_road_safety_parent_40595.id', '=', 'wg_road_safety_40595.parent_id');
            // })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1);

        return $query;
    }

    private function prepareQueryForStandard($criteria)
    {
        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            // ->join("wg_road_safety_item_criterion_40595", function ($join) {
            //     $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            // })
            // ->join("wg_customers", function ($join) {
            //     $join->on('wg_customers.totalEmployee', '=', 'wg_road_safety_item_criterion_40595.size');
            // })
            ->join("wg_customer_road_safety_40595", function ($join) use ($criteria) {
                $join->where('wg_customer_road_safety_40595.id', '=', $criteria->customerRoadSafetyId);
                $join->on('wg_customer_road_safety_40595.size', '=', 'wg_road_safety_item_40595.size');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->whereRaw("wg_road_safety_cycle_40595.status = 'activo'")
            ->whereRaw('wg_road_safety_40595.is_active = 1')
            ->whereRaw('wg_road_safety_item_40595.is_active = 1');
        //->where('wg_customers.id', $criteria->customerId)

        return $query;
    }

    private function prepareQueryForStandardClosed($criteria)
    {
        $query = DB::table('wg_road_safety_cycle_40595')
            ->join("wg_road_safety_40595", function ($join) {
                $join->on('wg_road_safety_40595.cycle_id', '=', 'wg_road_safety_cycle_40595.id');
            })
            ->join("wg_road_safety_item_40595", function ($join) {
                $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            })
            ->join("wg_road_safety_item_criterion_40595", function ($join) {
                $join->on('wg_road_safety_item_criterion_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.id');
            })
            ->select(
                'wg_road_safety_cycle_40595.id',
                'wg_road_safety_cycle_40595.name',
                'wg_road_safety_cycle_40595.abbreviation',
                'wg_road_safety_40595.id AS road_safety_id',
                'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.id AS road_safety_item_id',
                'wg_road_safety_item_40595.value'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1);

        return $query;
    }

    private function prepareQueryForItemsInnerJoinRate($criteria)
    {
        $query = DB::table('wg_customer_road_safety_item_40595')
            ->join("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->whereRaw("wg_customer_road_safety_item_40595.status = 'activo'")
            ->where('wg_customer_road_safety_item_40595.customer_road_safety_id', $criteria->customerRoadSafetyId);

        return $query;
    }

    private function prepareQueryForItemsInnerJoinRateClosed($criteria)
    {
        $query = DB::table('wg_customer_road_safety_item_40595')
            ->leftjoin("wg_road_safety_rate_40595", function ($join) {
                $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
            })
            ->select(
                'wg_customer_road_safety_item_40595.id',
                'wg_customer_road_safety_item_40595.customer_road_safety_id',
                'wg_customer_road_safety_item_40595.road_safety_item_id',
                'wg_customer_road_safety_item_40595.rate_id',
                'wg_customer_road_safety_item_40595.status',
                'wg_road_safety_rate_40595.text',
                'wg_road_safety_rate_40595.value',
                'wg_road_safety_rate_40595.code'
            )
            ->where('wg_customer_road_safety_item_40595.status', 'activo')
            ->where('wg_customer_road_safety_item_40595.is_freezed', 1)
            ->where('wg_customer_road_safety_item_40595.customer_road_safety_id', $criteria->customerRoadSafetyId);

        return $query;
    }


    private function prepareQueryForStandarStats($criteria)
    {
        $q1 = $this->prepareQueryForStandard($criteria);

        $q2 = $this->prepareQueryForItemsInnerJoinRate($criteria);

        $qSub = DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description',
                'wg_road_safety_item_40595.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac' THEN wg_road_safety_item_40595.value ELSE 0 END) AS total"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nas' THEN 1 ELSE 0 END) AS no_apply_with_justification"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nac' THEN 1 ELSE 0 END) AS no_apply_without_justification"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 1 ELSE 0 END) AS no_checked")
            )
            ->groupBy(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description'
            );

        return DB::table(DB::raw("({$qSub->toSql()}) as wg_customer_road_safety_40595"))
            ->mergeBindings($qSub)
            ->select(
                "wg_customer_road_safety_40595.road_safety_id",
                "wg_customer_road_safety_40595.name",
                "wg_customer_road_safety_40595.description",
                "wg_customer_road_safety_40595.items",
                "wg_customer_road_safety_40595.checked",
                DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.checked / wg_customer_road_safety_40595.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
                "wg_customer_road_safety_40595.id",
                "wg_customer_road_safety_40595.abbreviation",
                DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.total / wg_customer_road_safety_40595.items), 0), 2) AS average")
            );
    }

    private function prepareQueryForStandarStatsClosed($criteria)
    {
        $q1 = $this->prepareQueryForStandardClosed($criteria);

        $q2 = $this->prepareQueryForItemsInnerJoinRateClosed($criteria);

        $qSub = DB::table(DB::raw("({$q1->toSql()}) as wg_road_safety_item_40595"))
            ->join(DB::raw("({$q2->toSql()}) as wg_customer_road_safety_item_40595"), function ($join) {
                $join->on('wg_customer_road_safety_item_40595.road_safety_item_id', '=', 'wg_road_safety_item_40595.road_safety_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description',
                'wg_road_safety_item_40595.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' OR wg_customer_road_safety_item_40595.code = 'nac' THEN wg_road_safety_item_40595.value ELSE 0 END) AS total"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nas' THEN 1 ELSE 0 END) AS no_apply_with_justification"),
                DB::raw("SUM(CASE WHEN wg_customer_road_safety_item_40595.code = 'nac' THEN 1 ELSE 0 END) AS no_apply_without_justification"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_road_safety_item_40595.id) THEN 1 ELSE 0 END) AS no_checked")
            )
            ->groupBy(
                'wg_road_safety_item_40595.id',
                'wg_road_safety_item_40595.name',
                'wg_road_safety_item_40595.road_safety_id',
                'wg_road_safety_item_40595.description'
            );

        return DB::table(DB::raw("({$qSub->toSql()}) as wg_customer_road_safety_40595"))
            ->mergeBindings($qSub)
            ->select(
                "wg_customer_road_safety_40595.road_safety_id",
                "wg_customer_road_safety_40595.name",
                "wg_customer_road_safety_40595.description",
                "wg_customer_road_safety_40595.items",
                "wg_customer_road_safety_40595.checked",
                DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.checked / wg_customer_road_safety_40595.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
                "wg_customer_road_safety_40595.id",
                "wg_customer_road_safety_40595.abbreviation",
                DB::raw("ROUND(IFNULL((wg_customer_road_safety_40595.total / wg_customer_road_safety_40595.items), 0), 2) AS average")
            );
    }

    private function prepareQueryForItems($criteria)
    {
        $query = DB::table('wg_customer_road_safety_item_40595');

        $query->join('wg_customer_road_safety_40595', function ($join) {
            $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
        })->join('wg_road_safety_40595', function ($join) {
            $join->on('wg_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.road_safety_item_id');
        })->join('wg_road_safety_item_40595', function ($join) {
            $join->on('wg_road_safety_item_40595.road_safety_id', '=', 'wg_road_safety_40595.id');
            $join->on('wg_road_safety_item_40595.size', '=', 'wg_customer_road_safety_40595.size');
        })->join('wg_road_safety_cycle_40595', function ($join) {
            $join->on('wg_road_safety_cycle_40595.id', '=', 'wg_road_safety_40595.cycle_id');
        })->leftjoin('wg_road_safety_rate_40595', function ($join) {
            $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
        });

        $query
            ->select(
                'wg_customer_road_safety_40595.id AS customerRoadSafetyId',
                'wg_customer_road_safety_item_40595.road_safety_item_id AS roadSafetyItemId',
                'wg_customer_road_safety_item_40595.id AS customerRoadSafetyItemId',
                'wg_road_safety_cycle_40595.id AS cycleId',
                'wg_road_safety_cycle_40595.name AS cycle',
                'wg_road_safety_40595.numeral',
                'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.value',
                'wg_road_safety_40595.id AS roadSafetyParentId',
                'wg_road_safety_rate_40595.id AS rateId',
                "wg_road_safety_rate_40595.text AS rateText",
                "wg_road_safety_rate_40595.code AS rateCode",
                "wg_road_safety_rate_40595.value AS rateValue",
                "wg_road_safety_rate_40595.color AS rateColor",
                "wg_customer_road_safety_40595.size",
                'wg_customer_road_safety_item_40595.created_at',
                'wg_customer_road_safety_item_40595.updated_at'
            )
            ->whereRaw("wg_road_safety_cycle_40595.status = 'activo'")
            ->whereRaw('wg_road_safety_40595.is_active = 1')
            ->whereRaw('wg_road_safety_item_40595.is_active = 1')
            ->whereRaw("wg_customer_road_safety_item_40595.status = 'activo'")
            ->where('wg_customer_road_safety_40595.id', $criteria->customerRoadSafetyId)
            ->orderBy('wg_road_safety_cycle_40595.id');

        if (isset($criteria->rateId) && $criteria->rateId) {
            $query->where('wg_customer_road_safety_item_40595.rate_id', $criteria->rateId);
        }

        return $query;
    }

    private function prepareQueryForItemsClosed($criteria)
    {
        $query = DB::table('wg_customer_road_safety_item_40595');

        $query->join('wg_customer_road_safety_40595', function ($join) {
            $join->on('wg_customer_road_safety_40595.id', '=', 'wg_customer_road_safety_item_40595.customer_road_safety_id');
        })->join('wg_road_safety_item_40595', function ($join) {
            $join->on('wg_road_safety_item_40595.id', '=', 'wg_customer_road_safety_item_40595.road_safety_item_id');
        })->join('wg_road_safety_40595', function ($join) {
            $join->on('wg_road_safety_40595.id', '=', 'wg_road_safety_item_40595.road_safety_id');
        })->join('wg_road_safety_cycle_40595', function ($join) {
            $join->on('wg_road_safety_cycle_40595.id', '=', 'wg_road_safety_40595.cycle_id');
        })->leftjoin('wg_road_safety_rate_40595', function ($join) {
            $join->on('wg_road_safety_rate_40595.id', '=', 'wg_customer_road_safety_item_40595.rate_id');
        });

        $query
            ->select(
                'wg_customer_road_safety_40595.id AS customerRoadSafetyId',
                'wg_customer_road_safety_item_40595.road_safety_item_id AS roadSafetyItemId',
                'wg_customer_road_safety_item_40595.id AS customerRoadSafetyItemId',
                'wg_road_safety_cycle_40595.id AS cycleId',
                'wg_road_safety_cycle_40595.name AS cycle',
                'wg_road_safety_40595.numeral',
                'wg_road_safety_40595.description',
                'wg_road_safety_item_40595.value',
                'wg_road_safety_40595.id AS roadSafetyParentId',
                'wg_road_safety_rate_40595.id AS rateId',
                "wg_road_safety_rate_40595.text AS rateText",
                "wg_road_safety_rate_40595.code AS rateCode",
                "wg_road_safety_rate_40595.value AS rateValue",
                "wg_road_safety_rate_40595.color AS rateColor",
                'wg_customer_road_safety_item_40595.created_at',
                'wg_customer_road_safety_item_40595.updated_at'
            )
            ->where('wg_road_safety_cycle_40595.status', 'activo')
            ->where('wg_road_safety_40595.is_active', 1)
            ->where('wg_road_safety_item_40595.is_active', 1)
            ->where('wg_customer_road_safety_item_40595.status', 'activo')
            ->where('wg_customer_road_safety_item_40595.is_freezed', 1)
            ->where('wg_customer_road_safety_40595.id', $criteria->customerRoadSafetyId)
            ->orderBy('wg_road_safety_cycle_40595.id');

        if (isset($criteria->rateId) && $criteria->rateId) {
            $query->where('wg_customer_road_safety_item_40595.rate_id', $criteria->rateId);
        }

        return $query;
    }

    private function findRoadSafety($id)
    {
        return DB::table('wg_customer_road_safety_40595')->where('id', $id)->first();
    }

    public static function getPeriodsByCustomer(int $customerId)
    {
        return DB::table('wg_customer_road_safety_40595 as ms_40595')
            ->join('wg_customer_road_safety_item_40595 as msi_40595', 'msi_40595.customer_road_safety_id', '=', 'ms_40595.id')
            ->where('ms_40595.customer_id', $customerId)
            ->where('msi_40595.status', 'activo')
            //->where('msi_40595.is_freezed', true)
            ->orderBy('ms_40595.period', 'desc')
            ->select('ms_40595.period as value', 'ms_40595.period as item')
            ->distinct()
            ->get();
    }


    public function getTotalByCustomerAndYearChartLine($criteria)
    {
        $periods = [$criteria->period];

        if ($criteria->comparePeriod) {
            $periods[] = $criteria->comparePeriod;
        }

        $qSub = DB::table('wg_customer_road_safety_tracking_40595 as o')
            //->join('wg_customer_road_safety_40595 as d', 'd.id', '=', 'o.customer_road_safety_id')
            ->join('wg_customer_road_safety_40595 as d', function ($join) {
                $join->on('d.id', '=', 'o.customer_road_safety_id');
                $join->on('d.period', '=', 'o.year');
            })
            ->where('d.customer_id', $criteria->customerId)
            ->whereIn("o.year", $periods)
            ->select(
                "o.year as label",
                "o.month",
                //DB::raw("(SUM(total) / SUM(items)) as value")
                DB::raw("(SUM(avg_total)) as value")
            )
            ->groupBy("o.year", "o.month");

        $data = DB::table(DB::raw("({$qSub->toSql()}) as o"))
            ->mergeBindings($qSub)
            ->groupBy("o.label")
            ->select(
                "o.label",
                DB::raw("MAX(CASE WHEN month = 1 THEN ROUND(IFNULL(o.value,0),2) END) 'JAN'"),
                DB::raw("MAX(CASE WHEN month = 2 THEN ROUND(IFNULL(o.value,0),2) END) 'FEB'"),
                DB::raw("MAX(CASE WHEN month = 3 THEN ROUND(IFNULL(o.value,0),2) END) 'MAR'"),
                DB::raw("MAX(CASE WHEN month = 4 THEN ROUND(IFNULL(o.value,0),2) END) 'APR'"),
                DB::raw("MAX(CASE WHEN month = 5 THEN ROUND(IFNULL(o.value,0),2) END) 'MAY'"),
                DB::raw("MAX(CASE WHEN month = 6 THEN ROUND(IFNULL(o.value,0),2) END) 'JUN'"),
                DB::raw("MAX(CASE WHEN month = 7 THEN ROUND(IFNULL(o.value,0),2) END) 'JUL'"),
                DB::raw("MAX(CASE WHEN month = 8 THEN ROUND(IFNULL(o.value,0),2) END) 'AUG'"),
                DB::raw("MAX(CASE WHEN month = 9 THEN ROUND(IFNULL(o.value,0),2) END) 'SEP'"),
                DB::raw("MAX(CASE WHEN month = 10 THEN ROUND(IFNULL(o.value,0),2) END) 'OCT'"),
                DB::raw("MAX(CASE WHEN month = 11 THEN ROUND(IFNULL(o.value,0),2) END) 'NOV'"),
                DB::raw("MAX(CASE WHEN month = 12 THEN ROUND(IFNULL(o.value,0),2) END) 'DEC'")
            )
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getRateList()
    {
        return DB::table('wg_road_safety_rate_40595')->where('id', '>', 2)->get();
    }

    public function getRealRateList()
    {
        return DB::table('wg_road_safety_rate_40595')->get();
    }
}
