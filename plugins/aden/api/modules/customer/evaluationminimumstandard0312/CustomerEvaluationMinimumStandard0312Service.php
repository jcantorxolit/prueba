<?php

namespace AdeN\Api\Modules\Customer\EvaluationMinimumStandard0312;

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

class CustomerEvaluationMinimumStandard0312Service extends BaseService
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

        return DB::table('wg_customer_evaluation_minimum_standard_0312')
            ->where('customer_id', $customerField ? $customerField->value : $criteria->customerId)
            //->where('period', '=', DB::raw("YEAR(NOW())"))
            ->count() == 0;
    }

    public function getMigrateFromId($period, $customerId)
    {
        return ($item = DB::table('wg_customer_evaluation_minimum_standard_0312')
            ->select('id')
            ->where('period', '<', $period)
            ->where('status', 'C')
            ->where('customer_id', $customerId)
            ->orderBy('period', 'DESC')
            ->first()) ? $item->id : 0;
    }

    public function migrateFrom($criteria)
    {
        $qDetail = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id'
            )
            ->whereRaw("wg_customer_evaluation_minimum_standard_item_0312.status = 'activo'")
            ->whereRaw("wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id = {$criteria->fromId}");

        DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->join(DB::raw("({$qDetail->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312_from"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312_from.minimum_standard_item_id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');
            })->mergeBindings($qDetail)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id', $criteria->id)
            ->update([
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id' => DB::raw('wg_customer_evaluation_minimum_standard_item_0312_from.rate_id'),
                'wg_customer_evaluation_minimum_standard_item_0312.updated_at' => DB::raw('NOW()'),
                'wg_customer_evaluation_minimum_standard_item_0312.updated_by' => $criteria->userId
            ]);
    }

    public function bulkCancelMinimunStandardImprovementPlanActionPlanTask()
    {
        DB::table('wg_customer_improvement_plan_action_plan_task')
            ->join("wg_customer_improvement_plan_action_plan", function ($join) {
                $join->on('wg_customer_improvement_plan_action_plan.id', '=', 'wg_customer_improvement_plan_action_plan_task.customer_improvement_plan_action_plan_id');
            })
            ->join("wg_customer_improvement_plan", function ($join) {
                $join->on('wg_customer_improvement_plan.id', '=', 'wg_customer_improvement_plan_action_plan.customer_improvement_plan_id');
            })
            ->join("wg_customer_evaluation_minimum_standard_item_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.id', '=', 'wg_customer_improvement_plan.entityId');
            })
            ->join("wg_customer_evaluation_minimum_standard_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
            })
            ->where('wg_customer_evaluation_minimum_standard_0312.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_evaluation_minimum_standard_0312.status', 'A')
            ->where('wg_customer_improvement_plan_action_plan_task.status', 'A')
            ->where('wg_customer_improvement_plan.entityName', 'EM_0312')
            ->whereRaw("DATE(wg_customer_improvement_plan.endDate) < DATE(NOW())")
            ->update([
                'wg_customer_improvement_plan_action_plan_task.reason' => DB::raw("CONCAT(wg_customer_improvement_plan_action_plan_task.reason,'Cancelar:FINALIZACIÓN DE PERIODO DE AUTOEVALUACIÓN |')"),
                'wg_customer_improvement_plan_action_plan_task.updated_at' => DB::raw('NOW()'),
                'wg_customer_improvement_plan_action_plan_task.status' => 'C'
            ]);
    }

    public function bulkInsertMinimunStandardImprovementPlanActionPlanComment()
    {
        $query = DB::table('wg_customer_improvement_plan_action_plan')
            ->join("wg_customer_improvement_plan", function ($join) {
                $join->on('wg_customer_improvement_plan.id', '=', 'wg_customer_improvement_plan_action_plan.customer_improvement_plan_id');
            })
            ->join("wg_customer_evaluation_minimum_standard_item_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.id', '=', 'wg_customer_improvement_plan.entityId');
            })
            ->join("wg_customer_evaluation_minimum_standard_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
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
            ->where('wg_customer_improvement_plan.entityName', 'EM_0312')
            ->where('wg_customer_evaluation_minimum_standard_0312.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_evaluation_minimum_standard_0312.status', 'A')
            ->where('wg_customer_improvement_plan_action_plan.status', 'AB')
            ->whereRaw("DATE(wg_customer_improvement_plan.endDate) < DATE(NOW())");

        $sql = 'INSERT INTO wg_customer_improvement_plan_action_plan_comment (`id`, `customer_improvement_plan_action_plan_id`, `reason`, `old_status`, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public function bulkCancelMinimunStandardImprovementPlanActionPlan()
    {
        DB::table('wg_customer_improvement_plan_action_plan')
            ->join("wg_customer_improvement_plan", function ($join) {
                $join->on('wg_customer_improvement_plan.id', '=', 'wg_customer_improvement_plan_action_plan.customer_improvement_plan_id');
            })
            ->join("wg_customer_evaluation_minimum_standard_item_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.id', '=', 'wg_customer_improvement_plan.entityId');
            })
            ->join("wg_customer_evaluation_minimum_standard_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
            })
            ->where('wg_customer_improvement_plan.entityName', 'EM_0312')
            ->where('wg_customer_evaluation_minimum_standard_0312.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_evaluation_minimum_standard_0312.status', 'A')
            ->where('wg_customer_improvement_plan_action_plan.status', 'AB')
            ->whereRaw("DATE(wg_customer_improvement_plan.endDate) < DATE(NOW())")
            ->update([
                'wg_customer_improvement_plan_action_plan.updated_at' => DB::raw('NOW()'),
                'wg_customer_improvement_plan_action_plan.status' => 'CA'
            ]);
    }

    public function bulkInsertMinimunStandardImprovementPlanComment()
    {
        $query = DB::table('wg_customer_improvement_plan')
            ->join("wg_customer_evaluation_minimum_standard_item_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.id', '=', 'wg_customer_improvement_plan.entityId');
            })
            ->join("wg_customer_evaluation_minimum_standard_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
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
            ->where('wg_customer_improvement_plan.entityName', 'EM_0312')
            ->where('wg_customer_evaluation_minimum_standard_0312.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_evaluation_minimum_standard_0312.status', 'A')
            ->where('wg_customer_improvement_plan.status', 'AB')
            ->whereRaw("DATE(wg_customer_improvement_plan.endDate) < DATE(NOW())");

        $sql = 'INSERT INTO wg_customer_improvement_plan_comment (`id`, `customer_improvement_plan_id`, `reason`, `old_status`, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        DB::statement($sql, $query->getBindings());
    }

    public function bulkCancelMinimunStandardImprovementPlan()
    {
        DB::table('wg_customer_improvement_plan')
            ->join("wg_customer_evaluation_minimum_standard_item_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.id', '=', 'wg_customer_improvement_plan.entityId');
            })
            ->join("wg_customer_evaluation_minimum_standard_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
            })
            ->where('wg_customer_improvement_plan.entityName', 'EM_0312')
            ->where('wg_customer_evaluation_minimum_standard_0312.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_evaluation_minimum_standard_0312.status', 'A')
            ->where('wg_customer_improvement_plan.status', 'AB')
            ->whereRaw("DATE(wg_customer_improvement_plan.endDate) < DATE(NOW())")
            ->update([
                'wg_customer_improvement_plan.updated_at' => DB::raw('NOW()'),
                'wg_customer_improvement_plan.status' => 'CA'
            ]);
    }

    public function bulkCancelMinimunStandard()
    {
        DB::table('wg_customer_evaluation_minimum_standard_0312')
            ->where('wg_customer_evaluation_minimum_standard_0312.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_evaluation_minimum_standard_0312.status', 'A')
            ->update([
                'wg_customer_evaluation_minimum_standard_0312.updated_at' => DB::raw('NOW()'),
                'wg_customer_evaluation_minimum_standard_0312.end_date' => DB::raw('NOW()'),
                'wg_customer_evaluation_minimum_standard_0312.status' => 'C'
            ]);
    }

    public function bulkInsertMinimumStandardChangePeriod()
    {
        $query = DB::table('wg_customer_evaluation_minimum_standard_0312')
            ->leftjoin("wg_customer_evaluation_minimum_standard_0312 AS wg_customer_evaluation_minimum_standard_0312_destination", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312_destination.customer_id', '=', 'wg_customer_evaluation_minimum_standard_0312.customer_id');
                $join->on('wg_customer_evaluation_minimum_standard_0312_destination.period', '=', DB::raw('YEAR(NOW())'));
            })
            ->select(
                DB::raw("NULL AS id"),
                'wg_customer_evaluation_minimum_standard_0312.customer_id',
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
            ->where('wg_customer_evaluation_minimum_standard_0312.period', DB::raw('YEAR(NOW()) - 1'))
            ->where('wg_customer_evaluation_minimum_standard_0312.status', 'C')
            ->whereNull('wg_customer_evaluation_minimum_standard_0312_destination.id');

        $sql = 'INSERT INTO wg_customer_evaluation_minimum_standard_0312 (`id`, `customer_id`, `start_date`, `end_date`, status, `type`, `period`, `description`, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();
        Log::info("bulkInsertMinimumStandardChangePeriod::" . $sql);
        DB::statement($sql, $query->getBindings());
    }

    public function bulkInsertMinimumStandardItemsChangePeriod()
    {
        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_customer_evaluation_minimum_standard_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.period', '=', DB::raw('YEAR(NOW())'));
                $join->where('wg_customer_evaluation_minimum_standard_0312.status', '=', 'A');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_evaluation_minimum_standard_0312.customer_id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
                $join->on('wg_minimum_standard_item_criterion_0312.size', '=', 'wg_customers.totalEmployee');
                $join->on('wg_minimum_standard_item_criterion_0312.risk_level', '=', 'wg_customers.riskLevel');
            })
            ->leftjoin("wg_customer_evaluation_minimum_standard_item_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id', '=', 'wg_customer_evaluation_minimum_standard_0312.id');
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->select(
                DB::raw("NULL AS id"),
                "wg_customer_evaluation_minimum_standard_0312.id AS customer_evaluation_minimum_standard_id",
                "wg_minimum_standard_item_0312.id AS minimum_standard_item_id",
                DB::raw("NULL AS rate_id"),
                DB::raw("'activo' AS status"),
                DB::raw("NOW() AS created_at"),
                DB::raw("1 AS created_by"),
                DB::raw("NOW() AS updated_at"),
                DB::raw("1 AS updated_by")
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->whereNull('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');

        $sql = 'INSERT INTO wg_customer_evaluation_minimum_standard_item_0312 (`id`, `customer_evaluation_minimum_standard_id`, `minimum_standard_item_id`, `rate_id`, status, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        Log::info($sql);
        Log::info(json_encode($query->getBindings()));

        DB::statement($sql, $query->getBindings());
    }

    public function bulkInsertMinimumStandardItems($criteria)
    {
        if ($criteria == null) {
            return;
        }

        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_customer_evaluation_minimum_standard_0312", function ($join) use ($criteria) {
                $join->where('wg_customer_evaluation_minimum_standard_0312.id', '=', $criteria->id);
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.id', '=', 'wg_customer_evaluation_minimum_standard_0312.customer_id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
                $join->on('wg_minimum_standard_item_criterion_0312.size', '=', 'wg_customers.totalEmployee');
                $join->on('wg_minimum_standard_item_criterion_0312.risk_level', '=', 'wg_customers.riskLevel');
            })
            ->leftjoin("wg_customer_evaluation_minimum_standard_item_0312", function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id', '=', 'wg_customer_evaluation_minimum_standard_0312.id');
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->select(
                DB::raw("NULL AS id"),
                DB::raw("? AS customer_evaluation_minimum_standard_id"),
                "wg_minimum_standard_item_0312.id AS minimum_standard_item_id",
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
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->whereNull('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');

        $sql = 'INSERT INTO wg_customer_evaluation_minimum_standard_item_0312 (`id`, `customer_evaluation_minimum_standard_id`, `minimum_standard_item_id`, `rate_id`, status, `created_at`, `created_by`, `updated_at`, `updated_by`)  ' . $query->toSql();

        Log::info($query->toSql());

        DB::statement($sql, $query->getBindings());
    }

    public function bulkUpdateMinimumStandardItems($criteria)
    {
        if ($criteria == null) {
            return;
        }

        $qDetail = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
            })
            ->join("wg_customer_evaluation_minimum_standard_0312", function ($join) use ($criteria) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.customer_id', '=', 'wg_customers.id');
            })
            ->select(
                "wg_customer_evaluation_minimum_standard_0312.id AS customer_evaluation_minimum_standard_id",
                "wg_minimum_standard_item_0312.id AS minimum_standard_item_id"
            )
            ->whereRaw('wg_minimum_standard_0312.is_active = 1')
            ->whereRaw('wg_minimum_standard_item_0312.is_active = 1')
            ->whereRaw("wg_customer_evaluation_minimum_standard_0312.id = {$criteria->id}")
            ->whereRaw("wg_config_minimum_standard_cycle_0312.status = 'activo'");

        return DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->join(DB::raw("({$qDetail->toSql()}) as wg_customer_evaluation_minimum_standard_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.customer_evaluation_minimum_standard_id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
                $join->on('wg_customer_evaluation_minimum_standard_0312.minimum_standard_item_id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($qDetail)
            ->update([
                "wg_customer_evaluation_minimum_standard_item_0312.status" => 'activo',
                //"wg_customer_evaluation_minimum_standard_item_0312.updated_at" => Carbon::now(),
                "wg_customer_evaluation_minimum_standard_item_0312.updated_by" => $criteria->updatedBy
            ]);
    }

    public function bulkDeleteMinimumStandardItems($criteria)
    {
        if ($criteria == null) {
            return;
        }

        $qDetail = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
            })
            ->join("wg_customer_evaluation_minimum_standard_0312", function ($join) use ($criteria) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.customer_id', '=', 'wg_customers.id');
            })
            ->select(
                "wg_customer_evaluation_minimum_standard_0312.id AS customer_evaluation_minimum_standard_id",
                "wg_minimum_standard_item_0312.id AS minimum_standard_item_id"
            )
            ->whereRaw('wg_minimum_standard_0312.is_active = 1')
            ->whereRaw('wg_minimum_standard_item_0312.is_active = 1')
            ->whereRaw("wg_customer_evaluation_minimum_standard_0312.id = {$criteria->id}")
            ->whereRaw("wg_config_minimum_standard_cycle_0312.status = 'activo'");

        return DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->leftjoin(DB::raw("({$qDetail->toSql()}) as wg_customer_evaluation_minimum_standard_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_0312.customer_evaluation_minimum_standard_id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
                $join->on('wg_customer_evaluation_minimum_standard_0312.minimum_standard_item_id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($qDetail)
            ->whereNull("wg_customer_evaluation_minimum_standard_0312.minimum_standard_item_id")
            ->where("wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id", $criteria->id)
            ->update([
                "wg_customer_evaluation_minimum_standard_item_0312.status" => 'inactivo',
                //"wg_customer_evaluation_minimum_standard_item_0312.updated_at" => Carbon::now(),
                "wg_customer_evaluation_minimum_standard_item_0312.updated_by" => $criteria->updatedBy
            ]);
    }

    public function getStats($criteria)
    {

        $entity = $this->findMinimumStandard($criteria->customerEvaluationMinimumStandardId);

        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            });

        if ($entity == null || $entity->status == 'A') {
            $qItems = $this->prepareQueryForItems($criteria);
            $query->leftjoin(DB::raw("({$qItems->toSql()}) AS wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimumStandardItemId', '=', 'wg_minimum_standard_item_0312.id');
            })->mergeBindings($qItems);
        } else {
            $qItems = $this->prepareQueryForItemsClosed($criteria);
            $query->leftjoin(DB::raw("({$qItems->toSql()}) AS wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimumStandardItemId', '=', 'wg_minimum_standard_item_0312.id');
            })->mergeBindings($qItems);
        }

        $query
            ->select(
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'nac' OR wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardItemId IS NULL THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total")
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);

        return $query->first();
    }

    public function getCycles($criteria)
    {
        $entity = $this->findMinimumStandard($criteria->customerEvaluationMinimumStandardId);

        if ($entity == null || $entity->status == 'A') {
            $qSub = $this->prepareSubQuery($criteria);
        } else {
            $qSub = $this->prepareSubQueryClosed($criteria);
        }

        $qSub->select(
            'wg_minimum_standard_item_0312.id',
            'wg_minimum_standard_item_0312.name',
            'wg_minimum_standard_item_0312.minimum_standard_id',
            'wg_minimum_standard_item_0312.description',
            'wg_minimum_standard_item_0312.abbreviation',
            DB::raw("COUNT(*) AS items"),
            DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 0 ELSE 1 END) AS checked"),
            DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total"),
            DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
            DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
            DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nas' THEN 1 ELSE 0 END) AS no_apply_with_justification"),
            DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN 1 ELSE 0 END) AS no_apply_without_justification"),
            DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 1 ELSE 0 END) AS no_checked")
        )->groupBy(
            'wg_minimum_standard_item_0312.id',
            'wg_minimum_standard_item_0312.name'
        );

        return DB::table(DB::raw("({$qSub->toSql()}) as wg_customer_evaluation_minimum_standard_0312"))
            ->mergeBindings($qSub)
            ->select(
                "wg_customer_evaluation_minimum_standard_0312.name",
                "wg_customer_evaluation_minimum_standard_0312.description",
                "wg_customer_evaluation_minimum_standard_0312.items",
                "wg_customer_evaluation_minimum_standard_0312.checked",
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.checked / wg_customer_evaluation_minimum_standard_0312.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
                "wg_customer_evaluation_minimum_standard_0312.id",
                "wg_customer_evaluation_minimum_standard_0312.abbreviation",
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.total / wg_customer_evaluation_minimum_standard_0312.items), 0), 2) AS average")
            )
            ->orderBy("wg_customer_evaluation_minimum_standard_0312.id")
            ->get()
            ->toArray();
    }

    public function getParent($criteria)
    {
        $q1 = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_id',
                'wg_minimum_standard_parent_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customers.id', $criteria->customerId);

        $q2 = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->join("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            ->where('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id', $criteria->customerEvaluationMinimumStandardId);


        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_minimum_standard_item_0312.id AS cycleId',
                'wg_minimum_standard_item_0312.name AS cycleName',
                'wg_minimum_standard_item_0312.minimum_standard_id AS minimumStandardParentId',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description'
            )
            ->orderBy('wg_minimum_standard_item_0312.id');

        return array_map(function ($row) {
            $row->description = "({$row->cycleName}) {$row->description}";
            return $row;
        }, $query->get()->toArray());
    }

    public function getChildren($criteria)
    {
        $q1 = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_0312.id AS minimum_standard_id',
                'wg_minimum_standard_0312.parent_id AS minimum_standard_parent_id',
                'wg_minimum_standard_0312.description',
                'wg_minimum_standard_0312.numeral',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customers.id', $criteria->customerId);

        $q2 = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->join("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            ->where('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id', $criteria->customerEvaluationMinimumStandardId);


        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_minimum_standard_item_0312.id AS cycleId',
                'wg_minimum_standard_item_0312.name AS cycleName',
                'wg_minimum_standard_item_0312.minimum_standard_id AS minimumStandardId',
                'wg_minimum_standard_item_0312.minimum_standard_parent_id AS minimumStandardParentId',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.abbreviation',
                'wg_minimum_standard_item_0312.numeral',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description'
            )
            ->orderBy('wg_minimum_standard_item_0312.id');

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
        return DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->select(
                "wg_customer_evaluation_minimum_standard_tracking_0312.year AS item",
                "wg_customer_evaluation_minimum_standard_tracking_0312.year AS value"
            )
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->groupBy(
                "wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id"
            )
            ->orderBy("wg_customer_evaluation_minimum_standard_tracking_0312.year", "DESC")
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
        $entity = $this->findMinimumStandard($criteria->customerEvaluationMinimumStandardId);

        if ($entity == null || $entity->status == 'A') {
            $qItems = $this->prepareQueryForItems($criteria);
        } else {
            $qItems = $this->prepareQueryForItemsClosed($criteria);
        }

        $data = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join(DB::raw("({$qItems->toSql()}) AS wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimumStandardItemId', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->mergeBindings($qItems)
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardItemId) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'nac' OR wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardItemId IS NULL THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.rateCode = 'nac' OR wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardItemId IS NULL THEN 1 ELSE 0 END) AS no_apply_with_justification"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardId IS NOT NULL AND ISNULL(wg_customer_evaluation_minimum_standard_item_0312.rateCode) THEN 1 ELSE 0 END) AS no_checked")
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->groupBy(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name'
            )
            ->orderBy('wg_config_minimum_standard_cycle_0312.id')
            ->get();

        /*$qSub = $this->prepareSubQuery($criteria);

        $data = $qSub->select(
            'wg_minimum_standard_item_0312.id',
            'wg_minimum_standard_item_0312.name',
            'wg_minimum_standard_item_0312.minimum_standard_id',
            'wg_minimum_standard_item_0312.abbreviation',
            DB::raw("COUNT(*) AS items"),
            DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 0 ELSE 1 END) AS checked"),
            DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total"),
            DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
            DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
            DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nas' THEN 1 ELSE 0 END) AS no_apply_with_justification"),
            DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN 1 ELSE 0 END) AS no_apply_without_justification"),
            DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 1 ELSE 0 END) AS no_checked")
        )
            ->groupBy(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name'
            )
            ->orderBy('wg_minimum_standard_item_0312.id')
            ->get();*/

        $config = array(
            "labelColumn" => 'abbreviation',
            "valueColumns" => [
                ['label' => 'Sin Evaluar', 'field' => 'no_checked'],
                ['label' => 'Cumple', 'field' => 'accomplish'],
                ['label' => 'No Cumple', 'field' => 'no_accomplish'],
                ['label' => 'No Aplica', 'field' => 'no_apply_with_justification']
            ]
        );

        return $this->chart->getChartBar($data, $config);
    }

    public function getChartPie($criteria)
    {
        $qSub = $this->preparePieSubQuery($criteria);

        $data = $qSub->select(
            'wg_minimum_standard_item_0312_stats.name AS label',
            DB::raw("SUM(CASE WHEN (wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac')
                                OR (wg_customer_evaluation_minimum_standard_item_0312.id IS NULL AND wg_minimum_standard_item_0312.id IS NULL)
                            THEN wg_minimum_standard_item_0312_stats.value ELSE 0 END) AS `value`")
        )
            ->groupBy(
                'wg_minimum_standard_item_0312_stats.id',
                'wg_minimum_standard_item_0312_stats.name'
            )
            ->orderBy('wg_minimum_standard_item_0312_stats.id')
            ->get();

        return $this->chart->getChartPie($data);
    }

    public function getChartStatus($criteria)
    {
        $qSub = DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->join("wg_config_minimum_standard_cycle_0312", function ($join) {
                $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_cycle');
            })
            ->select(
                DB::raw("IFNULL(SUM(wg_customer_evaluation_minimum_standard_tracking_0312.accomplish), 0) AS accomplish"),
                DB::raw("IFNULL(SUM(wg_customer_evaluation_minimum_standard_tracking_0312.no_apply_with_justification), 0) AS no_apply_with_justification"),
                DB::raw("IFNULL(SUM(wg_customer_evaluation_minimum_standard_tracking_0312.no_accomplish), 0) AS no_accomplish"),
                DB::raw("IFNULL(SUM(wg_customer_evaluation_minimum_standard_tracking_0312.no_apply_without_justification), 0) AS no_apply_without_justification"),
                DB::raw("IFNULL(SUM(wg_customer_evaluation_minimum_standard_tracking_0312.no_checked), 0) AS no_checked"),
                "wg_customer_evaluation_minimum_standard_tracking_0312.year",
                "wg_customer_evaluation_minimum_standard_tracking_0312.month"
            )
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year)
            ->groupBy(
                "wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id",
                "wg_customer_evaluation_minimum_standard_tracking_0312.month"
            );

        $data = DB::table('system_parameters')
            ->leftjoin(DB::raw("({$qSub->toSql()}) as wg_customer_evaluation_minimum_standard_tracking_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_tracking_0312.month', '=', 'system_parameters.value');
            })
            ->mergeBindings($qSub)
            ->select(
                "system_parameters.item AS label",
                DB::raw("IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.accomplish, 0) AS accomplish"),
                DB::raw("IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.no_apply_with_justification, 0) AS no_apply_with_justification"),
                DB::raw("IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.no_accomplish, 0) AS no_accomplish"),
                DB::raw("IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.no_apply_without_justification, 0) AS no_apply_without_justification"),
                DB::raw("IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.no_checked, 0) AS no_checked")
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
        $data = DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->join("wg_config_minimum_standard_cycle_0312", function ($join) {
                $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_cycle');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.abbreviation AS label',
                DB::raw("SUM(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'JAN'"),
                DB::raw("SUM(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'FEB'"),
                DB::raw("SUM(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'MAR'"),
                DB::raw("SUM(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'APR'"),
                DB::raw("SUM(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'MAY'"),
                DB::raw("SUM(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'JUN'"),
                DB::raw("SUM(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'JUL'"),
                DB::raw("SUM(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'AUG'"),
                DB::raw("SUM(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'SEP'"),
                DB::raw("SUM(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'OCT'"),
                DB::raw("SUM(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'NOV'"),
                DB::raw("SUM(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.avg_total,0),2) END) 'DEC'")
            )
            ->where('wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id', $criteria->customerEvaluationMinimumStandardId)
            ->where('wg_customer_evaluation_minimum_standard_tracking_0312.year', $criteria->year)
            ->groupBy('wg_customer_evaluation_minimum_standard_tracking_0312.minimum_standard_cycle')
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getChartTotal($criteria)
    {
        $qSub = DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->select(
                DB::raw("'Puntaje Total % (calificación)' AS label"),
                DB::raw("SUM(wg_customer_evaluation_minimum_standard_tracking_0312.total) AS value"),
                "wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id",
                "wg_customer_evaluation_minimum_standard_tracking_0312.year",
                "wg_customer_evaluation_minimum_standard_tracking_0312.month"
            )
            ->groupBy(
                "wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id",
                "wg_customer_evaluation_minimum_standard_tracking_0312.year",
                "wg_customer_evaluation_minimum_standard_tracking_0312.month"
            );

        $data = DB::table(DB::raw("({$qSub->toSql()}) as wg_customer_evaluation_minimum_standard_tracking_0312"))
            ->mergeBindings($qSub)
            ->select(
                "wg_customer_evaluation_minimum_standard_tracking_0312.label",
                DB::raw("MAX(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'JAN'"),
                DB::raw("MAX(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'FEB'"),
                DB::raw("MAX(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'MAR'"),
                DB::raw("MAX(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'APR'"),
                DB::raw("MAX(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'MAY'"),
                DB::raw("MAX(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'JUN'"),
                DB::raw("MAX(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'JUL'"),
                DB::raw("MAX(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'AUG'"),
                DB::raw("MAX(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'SEP'"),
                DB::raw("MAX(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'OCT'"),
                DB::raw("MAX(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'NOV'"),
                DB::raw("MAX(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'DEC'")
            )
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year)
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getChartAdvance($criteria)
    {
        $qSub = DB::table('wg_customer_evaluation_minimum_standard_tracking_0312')
            ->select(
                DB::raw("'Avance % (respuestas / preguntas)' AS label"),
                DB::raw("(SUM(wg_customer_evaluation_minimum_standard_tracking_0312.checked) / SUM(wg_customer_evaluation_minimum_standard_tracking_0312.items)) * 100 AS value"),
                "wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id",
                "wg_customer_evaluation_minimum_standard_tracking_0312.year",
                "wg_customer_evaluation_minimum_standard_tracking_0312.month"
            )
            ->groupBy(
                "wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id",
                "wg_customer_evaluation_minimum_standard_tracking_0312.year",
                "wg_customer_evaluation_minimum_standard_tracking_0312.month"
            );

        $data = DB::table(DB::raw("({$qSub->toSql()}) as wg_customer_evaluation_minimum_standard_tracking_0312"))
            ->mergeBindings($qSub)
            ->select(
                "wg_customer_evaluation_minimum_standard_tracking_0312.label",
                DB::raw("MAX(CASE WHEN month = 1 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'JAN'"),
                DB::raw("MAX(CASE WHEN month = 2 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'FEB'"),
                DB::raw("MAX(CASE WHEN month = 3 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'MAR'"),
                DB::raw("MAX(CASE WHEN month = 4 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'APR'"),
                DB::raw("MAX(CASE WHEN month = 5 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'MAY'"),
                DB::raw("MAX(CASE WHEN month = 6 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'JUN'"),
                DB::raw("MAX(CASE WHEN month = 7 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'JUL'"),
                DB::raw("MAX(CASE WHEN month = 8 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'AUG'"),
                DB::raw("MAX(CASE WHEN month = 9 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'SEP'"),
                DB::raw("MAX(CASE WHEN month = 10 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'OCT'"),
                DB::raw("MAX(CASE WHEN month = 11 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'NOV'"),
                DB::raw("MAX(CASE WHEN month = 12 THEN ROUND(IFNULL(wg_customer_evaluation_minimum_standard_tracking_0312.value,0),2) END) 'DEC'")
            )
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.customer_evaluation_minimum_standard_id", $criteria->customerEvaluationMinimumStandardId)
            ->where("wg_customer_evaluation_minimum_standard_tracking_0312.year", $criteria->year)
            ->get();

        $config = array(
            "labelColumn" => $this->chart->getMonthLabels(),
            "valueColumns" => $this->chart->getMonthColumnValueSeries()
        );

        return $this->chart->getChartLine($data, $config);
    }

    public function getExportSummaryData($criteria)
    {
        $q1 = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_id',
                'wg_minimum_standard_parent_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);

        $q2 = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->join("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo');

        $q1->where(SqlHelper::getPreparedField('wg_customers.id'), $criteria->customerId);
        $q2->where(SqlHelper::getPreparedField('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id'), $criteria->customerEvaluationMinimumStandardId);

        $sQuery = DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description'
            );

        $query = DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_evaluation_minimum_standard_0312"))
            ->mergeBindings($sQuery)
            ->select(
                "wg_customer_evaluation_minimum_standard_0312.name",
                "wg_customer_evaluation_minimum_standard_0312.description",
                "wg_customer_evaluation_minimum_standard_0312.items",
                "wg_customer_evaluation_minimum_standard_0312.checked",
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.checked / wg_customer_evaluation_minimum_standard_0312.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
                "wg_customer_evaluation_minimum_standard_0312.id",
                "wg_customer_evaluation_minimum_standard_0312.abbreviation",
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.total / wg_customer_evaluation_minimum_standard_0312.items), 0), 2) AS average"),
                DB::raw("CASE WHEN wg_customer_evaluation_minimum_standard_0312.checked = wg_customer_evaluation_minimum_standard_0312.items THEN 'Completado' WHEN wg_customer_evaluation_minimum_standard_0312.checked > 0 THEN 'Iniciado' ELSE 'Sin Iniciar' END AS status")
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

    public function getExportSummaryDataClosed($criteria)
    {
        $q1 = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_id',
                'wg_minimum_standard_parent_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);

        $q2 = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->leftjoin("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->where(SqlHelper::getPreparedField('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id'), $criteria->customerEvaluationMinimumStandardId)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            ->where('wg_customer_evaluation_minimum_standard_item_0312.is_freezed', 1);

        $sQuery = DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312"))
            ->join(DB::raw("({$q2->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total")
            )
            ->groupBy(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description'
            );

        $query = DB::table(DB::raw("({$sQuery->toSql()}) as wg_customer_evaluation_minimum_standard_0312"))
            ->mergeBindings($sQuery)
            ->select(
                "wg_customer_evaluation_minimum_standard_0312.name",
                "wg_customer_evaluation_minimum_standard_0312.description",
                "wg_customer_evaluation_minimum_standard_0312.items",
                "wg_customer_evaluation_minimum_standard_0312.checked",
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.checked / wg_customer_evaluation_minimum_standard_0312.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
                "wg_customer_evaluation_minimum_standard_0312.id",
                "wg_customer_evaluation_minimum_standard_0312.abbreviation",
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.total / wg_customer_evaluation_minimum_standard_0312.items), 0), 2) AS average"),
                DB::raw("CASE WHEN wg_customer_evaluation_minimum_standard_0312.checked = wg_customer_evaluation_minimum_standard_0312.items THEN 'Completado' WHEN wg_customer_evaluation_minimum_standard_0312.checked > 0 THEN 'Iniciado' ELSE 'Sin Iniciar' END AS status")
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
        $entity = $this->findMinimumStandard($criteria->customerEvaluationMinimumStandardId);

        if ($entity == null || $entity->status == 'A') {
            $qItems = $this->prepareQueryForItems($criteria);
            $qStats = $this->prepareQueryForStandarStats($criteria);
        } else {
            $qItems = $this->prepareQueryForItemsClosed($criteria);
            $qStats = $this->prepareQueryForStandarStatsClosed($criteria);
        }

        $data = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->leftjoin(DB::raw("({$qItems->toSql()}) AS wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimumStandardItemId', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->mergeBindings($qItems)
            ->leftjoin(DB::raw("({$qStats->toSql()}) AS minimum_standard_stats_0312"), function ($join) {
                $join->on('minimum_standard_stats_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->mergeBindings($qStats)
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_parent_id',
                'wg_minimum_standard_parent_0312.description AS minimum_standard_parent_description',

                'wg_minimum_standard_0312.id AS minimum_standard_id',
                'wg_minimum_standard_0312.description AS minimum_standard_description',

                'minimum_standard_stats_0312.items AS minimum_standard_items',
                'minimum_standard_stats_0312.checked AS minimum_standard_checked',
                'minimum_standard_stats_0312.advance AS minimum_standard_advance',
                'minimum_standard_stats_0312.total AS minimum_standard_total',
                'minimum_standard_stats_0312.average AS minimum_standard_average',


                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.numeral AS minimum_standard_item_numeral',
                'wg_minimum_standard_item_0312.description AS minimum_standard_item_description',
                'wg_minimum_standard_item_0312.value AS minimum_standard_item_value',

                //'wg_customer_evaluation_minimum_standard_item_0312.criterion',
                'wg_customer_evaluation_minimum_standard_item_0312.rateCode',
                'wg_customer_evaluation_minimum_standard_item_0312.rateId',
                'wg_customer_evaluation_minimum_standard_item_0312.rateText',
                'wg_customer_evaluation_minimum_standard_item_0312.rateValue',
                'wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardItemId'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->orderBy("wg_config_minimum_standard_cycle_0312.id")
            ->orderBy("wg_minimum_standard_parent_0312.id")
            ->orderBy("wg_minimum_standard_0312.id")
            ->orderBy("wg_minimum_standard_item_0312.numeral")
            ->get();

        if ($entity == null || $entity->status == 'A') {

            $customer = DB::table('wg_customers')
                ->leftjoin(DB::raw(CustomerModel::getRelationInfoDetail('customer_info_detail')), function ($join) {
                    $join->on('customer_info_detail.entityId', '=', 'wg_customers.id');
                })
                ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_employee_number')), function ($join) {
                    $join->on('wg_customer_employee_number.value', '=', 'wg_customers.totalEmployee');
                })
                ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_risk_level')), function ($join) {
                    $join->on('wg_customer_risk_level.value', '=', 'wg_customers.riskLevel');
                })
                ->select(
                    'wg_customers.businessName AS name',
                    'wg_customers.documentNumber',
                    'customer_info_detail.address',
                    'customer_info_detail.telephone AS phone',
                    'wg_customer_employee_number.item AS totalEmployee',
                    'wg_customer_risk_level.item AS riskLevel'
                )
                ->where('wg_customers.id', $criteria->customerId)
                ->first();
        } else {
            $customer = DB::table('wg_customers')
                ->join('wg_customer_evaluation_minimum_standard_0312', function ($join) {
                    $join->on('wg_customer_evaluation_minimum_standard_0312.customer_id', '=', 'wg_customers.id');
                })
                ->leftjoin(DB::raw(CustomerModel::getRelationInfoDetail('customer_info_detail')), function ($join) {
                    $join->on('customer_info_detail.entityId', '=', 'wg_customers.id');
                })
                ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_employee_number')), function ($join) {
                    $join->on('wg_customer_employee_number.value', '=', 'wg_customer_evaluation_minimum_standard_0312.total_employee');
                })
                ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_customer_risk_level')), function ($join) {
                    $join->on('wg_customer_risk_level.value', '=', 'wg_customer_evaluation_minimum_standard_0312.risk_level');
                })
                ->select(
                    'wg_customers.businessName AS name',
                    'wg_customers.documentNumber',
                    'customer_info_detail.address',
                    'customer_info_detail.telephone AS phone',
                    'wg_customer_employee_number.item AS totalEmployee',
                    'wg_customer_risk_level.item AS riskLevel'
                )
                ->where('wg_customers.id', $criteria->customerId)
                ->where('wg_customer_evaluation_minimum_standard_0312.id', $criteria->customerEvaluationMinimumStandardId)
                ->first();
        }

        $header = DB::table(DB::raw("({$qItems->toSql()}) AS wg_customer_evaluation_minimum_standard_item_0312"))
            ->mergeBindings($qItems)
            ->select(
                DB::raw('MIN(wg_customer_evaluation_minimum_standard_item_0312.created_at) AS firstDate'),
                DB::raw('MAX(wg_customer_evaluation_minimum_standard_item_0312.updated_at) AS lastDate')
            )
            ->groupBy('wg_customer_evaluation_minimum_standard_item_0312.customerEvaluationMinimumStandardId')
            ->first();

        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);

        $plans = $qItems
            //->mergeBindings($qItems)
            ->join('wg_customer_improvement_plan', function ($join) {
                $join->on('wg_customer_improvement_plan.customer_id', '=', 'wg_customer_evaluation_minimum_standard_0312.customer_id');
                $join->on('wg_customer_improvement_plan.entityId', '=', 'wg_customer_evaluation_minimum_standard_item_0312.id');
                $join->where('wg_customer_improvement_plan.entityName', '=', 'EM_0312');
            })
            ->leftjoin("wg_customer_improvement_plan_action_plan", function ($join) {
                $join->on('wg_customer_improvement_plan_action_plan.customer_improvement_plan_id', '=', 'wg_customer_improvement_plan.id');
            })
            /*
            ->leftjoin(DB::raw(CustomerModel::getRelatedAgentAndUser('responsible_improvement_plan')), function ($join) {
                $join->on('wg_customer_improvement_plan.responsible', '=', 'responsible_improvement_plan.id');
                $join->on('wg_customer_improvement_plan.responsibleType', '=', 'responsible_improvement_plan.type');
                $join->on('wg_customer_improvement_plan.customer_id', '=', 'responsible_improvement_plan.customer_id');
            })
            ->leftjoin(DB::raw(CustomerModel::getRelatedAgentAndUser('responsible_improvement_plan_action_plan')), function ($join) {
                $join->on('wg_customer_improvement_plan_action_plan.responsible', '=', 'responsible_improvement_plan_action_plan.id');
                $join->on('wg_customer_improvement_plan_action_plan.responsibleType', '=', 'responsible_improvement_plan_action_plan.type');
                $join->on('wg_customer_improvement_plan.customer_id', '=', 'responsible_improvement_plan_action_plan.customer_id');
            })    */
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
            ->mergeBindings($qAgentUser)
            ->select(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.numeral',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.value',

                'wg_customer_improvement_plan.id AS improvement_plan_id',
                'wg_customer_improvement_plan.description AS improvement_plan_description',
                'wg_customer_improvement_plan.endDate AS improvement_plan_end_date',
                'responsible_improvement_plan.name AS improvement_plan_responsible',

                'wg_customer_improvement_plan_action_plan.id AS action_plan_id',
                'wg_customer_improvement_plan_action_plan.activity AS action_plan_description',
                'wg_customer_improvement_plan_action_plan.endDate AS action_plan_end_date',
                'responsible_improvement_plan_action_plan.name AS action_plan_responsible'
            )
            ->orderBy('wg_minimum_standard_item_0312.id')
            ->get();

        $qChart = $this->preparePieSubQuery($criteria);

        $chart = $qChart
            ->select(
                'wg_minimum_standard_item_0312_stats.name AS label',
                DB::raw("SUM(CASE WHEN (wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac')
                                OR (wg_customer_evaluation_minimum_standard_item_0312.id IS NULL AND wg_minimum_standard_item_0312.id IS NULL)
                            THEN wg_minimum_standard_item_0312_stats.value ELSE 0 END) AS `value`")
            )
            ->groupBy(
                'wg_minimum_standard_item_0312_stats.id',
                'wg_minimum_standard_item_0312_stats.name'
            )
            ->orderBy('wg_minimum_standard_item_0312_stats.id')
            ->get();

        $chartData = array_map(function ($item) {
            return [$item->label . ': ' . $item->value, floatval($item->value)];
        }, $chart->toArray());

        array_unshift($chartData, ['Cycle', 'Value']);

        return [
            "header" => [
                "date" => Carbon::now('America/Bogota')->format('d/m/Y'),
                "startDate" => $header ? Carbon::parse($header->firstDate)->format('d/m/Y') : null,
                "endDate" => $header ? Carbon::parse($header->lastDate)->format('d/m/Y') : null,
            ],
            "cycles" => $this->prepareStandardDataForPdf($data),
            "customer" => $customer,
            "chart" => [
                "total" => ($stats = $this->getStats($criteria)) ? floatval($stats->total) : 0,
                "data" => json_encode($chartData)
            ],
            "plans" => $this->preparePlanDataForPdf($plans),
            "themeUrl" => CmsHelper::getThemeUrl(),
            "themePath" => CmsHelper::getThemePath()
        ];
    }

    private function prepareStandardDataForPdf($data)
    {
        $collection = new Collection($data);

        $cycle = $collection->groupBy('id');
        $parent = $collection->groupBy('minimum_standard_parent_id');
        $children = $collection->groupBy('minimum_standard_id');

        $defaultRate = DB::table('wg_config_minimum_standard_rate_0312')
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
                $parent->id = $pItem->minimum_standard_parent_id;
                $parent->description = $pItem->minimum_standard_parent_description;
                $parent->total = count($item);

                $parent->children = $children->filter(function ($item) use ($parent) {
                    return $item[0]->minimum_standard_parent_id == $parent->id;
                })->map(function ($item, $key) use ($defaultRate) {
                    $childCollection = new Collection($item);
                    $childItem = $childCollection->first();
                    $standard = new \stdClass();
                    $standard->id = $childItem->minimum_standard_id;
                    $standard->description = $childItem->minimum_standard_description;
                    $standard->weight = $childCollection->sum("minimum_standard_item_value");
                    $standard->totalAverage = $childCollection->reduce(function ($carry, $item) use ($defaultRate) {
                        if ($item->customerEvaluationMinimumStandardItemId && ($item->rateCode == 'cp' || $item->rateCode == 'nac')) {
                            $rateValue = $item->minimum_standard_item_value;
                        } else if (!$item->customerEvaluationMinimumStandardItemId) {
                            $rateValue = $item->minimum_standard_item_value;
                        } else {
                            $rateValue = 0;
                        }
                        return $carry + floatval($rateValue);
                    });

                    $standard->items = $childCollection->map(function ($item, $key) use ($defaultRate) {
                        $cItem = new \stdClass();
                        $cItem->id = $item->minimum_standard_item_id;
                        $cItem->description = $item->minimum_standard_item_description;
                        $cItem->numeral = !starts_with($item->minimum_standard_item_description, $item->minimum_standard_item_numeral) ? $item->minimum_standard_item_numeral : null;
                        $cItem->value = floatval($item->minimum_standard_item_value);
                        $cItem->rate = $item->customerEvaluationMinimumStandardItemId ? $this->parseRate($item) : $defaultRate;
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
            $plan->improvement_plan_endDate = $cItem->improvement_plan_end_date ? Carbon::parse($cItem->improvement_plan_end_date)->format('d/m/Y') : null;

            $plan->actions = $planCollection->filter(function ($item) {
                return $item->action_plan_id != null;
            })->map(function ($item, $key) {
                $action = new \stdClass();
                $action->activity = $item->action_plan_description;
                $action->responsible = $item->action_plan_responsible;
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

        $entity = $this->findMinimumStandard($criteria->customerEvaluationMinimumStandardId);

        if ($entity == null || $entity->status == 'A') {
            $q2 = $this->prepareQueryForStandardParent($criteria);
            $q3 = $this->prepareQueryForItemsInnerJoinRate($criteria);
        } else {
            $q2 = $this->prepareQueryForStandardParentClosed($criteria);
            $q3 = $this->prepareQueryForItemsInnerJoinRateClosed($criteria);
        }

        $query = DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312_stats"))
            ->join(DB::raw("({$q2->toSql()}) as wg_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_minimum_standard_item_0312.id', '=', 'wg_minimum_standard_item_0312_stats.id');
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_item_0312_stats.minimum_standard_id');
                $join->on('wg_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312_stats.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2);

        if ($entity == null || $entity->status == 'A') {
            $query->leftjoin(DB::raw("({$q3->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
                ->mergeBindings($q3);
        } else {
            $query->join(DB::raw("({$q3->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
                ->mergeBindings($q3);
        }

        return $query;
    }

    private function prepareSubQuery($criteria)
    {
        $q1 = $this->prepareQueryForStandardParent($criteria);

        $q2 = $this->prepareQueryForItemsInnerJoinRate($criteria);

        return DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2);
    }

    private function prepareSubQueryClosed($criteria)
    {
        $q1 = $this->prepareQueryForStandardParentClosed($criteria);

        $q2 = $this->prepareQueryForItemsInnerJoinRateClosed($criteria);

        return DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2);
    }

    private function prepareQueryForConfigStandard($criteria)
    {
        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_id',
                'wg_minimum_standard_parent_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);

        return $query;
    }


    private function prepareQueryForStandardParent($criteria)
    {
        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_id',
                'wg_minimum_standard_parent_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customers.id', $criteria->customerId);

        return $query;
    }

    private function prepareQueryForStandardParentClosed($criteria)
    {
        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join(DB::raw("wg_minimum_standard_0312 AS wg_minimum_standard_parent_0312"), function ($join) {
                $join->on('wg_minimum_standard_parent_0312.id', '=', 'wg_minimum_standard_0312.parent_id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_parent_0312.id AS minimum_standard_id',
                'wg_minimum_standard_parent_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);

        return $query;
    }

    private function prepareQueryForStandard($criteria)
    {
        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->join("wg_customers", function ($join) {
                $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
                $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_0312.id AS minimum_standard_id',
                'wg_minimum_standard_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customers.id', $criteria->customerId);

        return $query;
    }

    private function prepareQueryForStandardClosed($criteria)
    {
        $query = DB::table('wg_config_minimum_standard_cycle_0312')
            ->join("wg_minimum_standard_0312", function ($join) {
                $join->on('wg_minimum_standard_0312.cycle_id', '=', 'wg_config_minimum_standard_cycle_0312.id');
            })
            ->join("wg_minimum_standard_item_0312", function ($join) {
                $join->on('wg_minimum_standard_item_0312.minimum_standard_id', '=', 'wg_minimum_standard_0312.id');
            })
            ->join("wg_minimum_standard_item_criterion_0312", function ($join) {
                $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
            })
            ->select(
                'wg_config_minimum_standard_cycle_0312.id',
                'wg_config_minimum_standard_cycle_0312.name',
                'wg_config_minimum_standard_cycle_0312.abbreviation',
                'wg_minimum_standard_0312.id AS minimum_standard_id',
                'wg_minimum_standard_0312.description',
                'wg_minimum_standard_item_0312.id AS minimum_standard_item_id',
                'wg_minimum_standard_item_0312.value'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1);

        return $query;
    }

    private function prepareQueryForItemsInnerJoinRate($criteria)
    {
        $query = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->join("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            ->where('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id', $criteria->customerEvaluationMinimumStandardId);

        return $query;
    }

    private function prepareQueryForItemsInnerJoinRateClosed($criteria)
    {
        $query = DB::table('wg_customer_evaluation_minimum_standard_item_0312')
            ->leftjoin("wg_config_minimum_standard_rate_0312", function ($join) {
                $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
            })
            ->select(
                'wg_customer_evaluation_minimum_standard_item_0312.id',
                'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id',
                'wg_customer_evaluation_minimum_standard_item_0312.rate_id',
                'wg_customer_evaluation_minimum_standard_item_0312.status',
                'wg_config_minimum_standard_rate_0312.text',
                'wg_config_minimum_standard_rate_0312.value',
                'wg_config_minimum_standard_rate_0312.code'
            )
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            ->where('wg_customer_evaluation_minimum_standard_item_0312.is_freezed', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id', $criteria->customerEvaluationMinimumStandardId);

        return $query;
    }


    private function prepareQueryForStandarStats($criteria)
    {
        $q1 = $this->prepareQueryForStandard($criteria);

        $q2 = $this->prepareQueryForItemsInnerJoinRate($criteria);

        $qSub = DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312"))
            ->leftjoin(DB::raw("({$q2->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nas' THEN 1 ELSE 0 END) AS no_apply_with_justification"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN 1 ELSE 0 END) AS no_apply_without_justification"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 1 ELSE 0 END) AS no_checked")
            )
            ->groupBy(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description'
            );

        return DB::table(DB::raw("({$qSub->toSql()}) as wg_customer_evaluation_minimum_standard_0312"))
            ->mergeBindings($qSub)
            ->select(
                "wg_customer_evaluation_minimum_standard_0312.minimum_standard_id",
                "wg_customer_evaluation_minimum_standard_0312.name",
                "wg_customer_evaluation_minimum_standard_0312.description",
                "wg_customer_evaluation_minimum_standard_0312.items",
                "wg_customer_evaluation_minimum_standard_0312.checked",
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.checked / wg_customer_evaluation_minimum_standard_0312.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
                "wg_customer_evaluation_minimum_standard_0312.id",
                "wg_customer_evaluation_minimum_standard_0312.abbreviation",
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.total / wg_customer_evaluation_minimum_standard_0312.items), 0), 2) AS average")
            );
    }

    private function prepareQueryForStandarStatsClosed($criteria)
    {
        $q1 = $this->prepareQueryForStandardClosed($criteria);

        $q2 = $this->prepareQueryForItemsInnerJoinRateClosed($criteria);

        $qSub = DB::table(DB::raw("({$q1->toSql()}) as wg_minimum_standard_item_0312"))
            ->join(DB::raw("({$q2->toSql()}) as wg_customer_evaluation_minimum_standard_item_0312"), function ($join) {
                $join->on('wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.minimum_standard_item_id');
            })
            ->mergeBindings($q1)
            ->mergeBindings($q2)
            ->select(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.abbreviation',
                DB::raw("COUNT(*) AS items"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 0 ELSE 1 END) AS checked"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' OR wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN wg_minimum_standard_item_0312.value ELSE 0 END) AS total"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'cp' THEN 1 ELSE 0 END) AS accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nc' THEN 1 ELSE 0 END) AS no_accomplish"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nas' THEN 1 ELSE 0 END) AS no_apply_with_justification"),
                DB::raw("SUM(CASE WHEN wg_customer_evaluation_minimum_standard_item_0312.code = 'nac' THEN 1 ELSE 0 END) AS no_apply_without_justification"),
                DB::raw("SUM(CASE WHEN ISNULL(wg_customer_evaluation_minimum_standard_item_0312.id) THEN 1 ELSE 0 END) AS no_checked")
            )
            ->groupBy(
                'wg_minimum_standard_item_0312.id',
                'wg_minimum_standard_item_0312.name',
                'wg_minimum_standard_item_0312.minimum_standard_id',
                'wg_minimum_standard_item_0312.description'
            );

        return DB::table(DB::raw("({$qSub->toSql()}) as wg_customer_evaluation_minimum_standard_0312"))
            ->mergeBindings($qSub)
            ->select(
                "wg_customer_evaluation_minimum_standard_0312.minimum_standard_id",
                "wg_customer_evaluation_minimum_standard_0312.name",
                "wg_customer_evaluation_minimum_standard_0312.description",
                "wg_customer_evaluation_minimum_standard_0312.items",
                "wg_customer_evaluation_minimum_standard_0312.checked",
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.checked / wg_customer_evaluation_minimum_standard_0312.items) * 100, 0) ,2) AS advance"),
                DB::raw("ROUND(IFNULL(total, 0), 2) AS total"),
                "wg_customer_evaluation_minimum_standard_0312.id",
                "wg_customer_evaluation_minimum_standard_0312.abbreviation",
                DB::raw("ROUND(IFNULL((wg_customer_evaluation_minimum_standard_0312.total / wg_customer_evaluation_minimum_standard_0312.items), 0), 2) AS average")
            );
    }

    private function prepareQueryForItems($criteria)
    {
        $query = DB::table('wg_customer_evaluation_minimum_standard_item_0312');

        $query->join('wg_customer_evaluation_minimum_standard_0312', function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
        })->join('wg_minimum_standard_item_0312', function ($join) {
            $join->on('wg_minimum_standard_item_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');
        })->join('wg_minimum_standard_0312', function ($join) {
            $join->on('wg_minimum_standard_0312.id', '=', 'wg_minimum_standard_item_0312.minimum_standard_id');
        })->join('wg_config_minimum_standard_cycle_0312', function ($join) {
            $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_minimum_standard_0312.cycle_id');
        })->join("wg_minimum_standard_item_criterion_0312", function ($join) {
            $join->on('wg_minimum_standard_item_criterion_0312.minimum_standard_item_id', '=', 'wg_minimum_standard_item_0312.id');
        })->join("wg_customers", function ($join) {
            $join->on('wg_customers.riskLevel', '=', 'wg_minimum_standard_item_criterion_0312.risk_level');
            $join->on('wg_customers.totalEmployee', '=', 'wg_minimum_standard_item_criterion_0312.size');
            $join->on('wg_customers.id', '=', 'wg_customer_evaluation_minimum_standard_0312.customer_id');
        })->leftjoin('wg_config_minimum_standard_rate_0312', function ($join) {
            $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
        });

        $query
            ->select(
                'wg_customer_evaluation_minimum_standard_0312.id AS customerEvaluationMinimumStandardId',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id AS minimumStandardItemId',
                'wg_customer_evaluation_minimum_standard_item_0312.id AS customerEvaluationMinimumStandardItemId',
                'wg_config_minimum_standard_cycle_0312.id AS cycleId',
                'wg_config_minimum_standard_cycle_0312.name AS cycle',
                'wg_minimum_standard_item_0312.numeral',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.value',
                'wg_minimum_standard_0312.id AS minimumStandardParentId',
                'wg_config_minimum_standard_rate_0312.id AS rateId',
                "wg_config_minimum_standard_rate_0312.text AS rateText",
                "wg_config_minimum_standard_rate_0312.code AS rateCode",
                "wg_config_minimum_standard_rate_0312.value AS rateValue",
                "wg_config_minimum_standard_rate_0312.color AS rateColor",
                'wg_minimum_standard_item_criterion_0312.description AS criterion',
                'wg_customer_evaluation_minimum_standard_item_0312.created_at',
                'wg_customer_evaluation_minimum_standard_item_0312.updated_at'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            ->where('wg_customer_evaluation_minimum_standard_0312.id', $criteria->customerEvaluationMinimumStandardId)
            ->orderBy('wg_config_minimum_standard_cycle_0312.id');

        if (isset($criteria->rateId) && $criteria->rateId) {
            $query->where('wg_customer_evaluation_minimum_standard_item_0312.rate_id', $criteria->rateId);
        }

        return $query;
    }

    private function prepareQueryForItemsClosed($criteria)
    {
        $query = DB::table('wg_customer_evaluation_minimum_standard_item_0312');

        $query->join('wg_customer_evaluation_minimum_standard_0312', function ($join) {
            $join->on('wg_customer_evaluation_minimum_standard_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.customer_evaluation_minimum_standard_id');
        })->join('wg_minimum_standard_item_0312', function ($join) {
            $join->on('wg_minimum_standard_item_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id');
        })->join('wg_minimum_standard_0312', function ($join) {
            $join->on('wg_minimum_standard_0312.id', '=', 'wg_minimum_standard_item_0312.minimum_standard_id');
        })->join('wg_config_minimum_standard_cycle_0312', function ($join) {
            $join->on('wg_config_minimum_standard_cycle_0312.id', '=', 'wg_minimum_standard_0312.cycle_id');
        })->leftjoin('wg_config_minimum_standard_rate_0312', function ($join) {
            $join->on('wg_config_minimum_standard_rate_0312.id', '=', 'wg_customer_evaluation_minimum_standard_item_0312.rate_id');
        });

        $query
            ->select(
                'wg_customer_evaluation_minimum_standard_0312.id AS customerEvaluationMinimumStandardId',
                'wg_customer_evaluation_minimum_standard_item_0312.minimum_standard_item_id AS minimumStandardItemId',
                'wg_customer_evaluation_minimum_standard_item_0312.id AS customerEvaluationMinimumStandardItemId',
                'wg_config_minimum_standard_cycle_0312.id AS cycleId',
                'wg_config_minimum_standard_cycle_0312.name AS cycle',
                'wg_minimum_standard_item_0312.numeral',
                'wg_minimum_standard_item_0312.description',
                'wg_minimum_standard_item_0312.value',
                'wg_minimum_standard_0312.id AS minimumStandardParentId',
                'wg_config_minimum_standard_rate_0312.id AS rateId',
                "wg_config_minimum_standard_rate_0312.text AS rateText",
                "wg_config_minimum_standard_rate_0312.code AS rateCode",
                "wg_config_minimum_standard_rate_0312.value AS rateValue",
                "wg_config_minimum_standard_rate_0312.color AS rateColor",
                'wg_customer_evaluation_minimum_standard_item_0312.created_at',
                'wg_customer_evaluation_minimum_standard_item_0312.updated_at'
            )
            ->where('wg_config_minimum_standard_cycle_0312.status', 'activo')
            ->where('wg_minimum_standard_0312.is_active', 1)
            ->where('wg_minimum_standard_item_0312.is_active', 1)
            ->where('wg_customer_evaluation_minimum_standard_item_0312.status', 'activo')
            ->where('wg_customer_evaluation_minimum_standard_item_0312.is_freezed', 1)
            ->where('wg_customer_evaluation_minimum_standard_0312.id', $criteria->customerEvaluationMinimumStandardId)
            ->orderBy('wg_config_minimum_standard_cycle_0312.id');

        if (isset($criteria->rateId) && $criteria->rateId) {
            $query->where('wg_customer_evaluation_minimum_standard_item_0312.rate_id', $criteria->rateId);
        }

        return $query;
    }

    private function findMinimumStandard($id)
    {
        return DB::table('wg_customer_evaluation_minimum_standard_0312')->where('id', $id)->first();
    }

    public static function getPeriodsByCustomer(int $customerId)
    {
        return DB::table('wg_customer_evaluation_minimum_standard_0312 as ms_0312')
            ->join('wg_customer_evaluation_minimum_standard_item_0312 as msi_0312', 'msi_0312.customer_evaluation_minimum_standard_id', '=', 'ms_0312.id')
            ->where('ms_0312.customer_id', $customerId)
            ->where('msi_0312.status', 'activo')
            //->where('msi_0312.is_freezed', true)
            ->orderBy('ms_0312.period', 'desc')
            ->select('ms_0312.period as value', 'ms_0312.period as item')
            ->distinct()
            ->get();
    }


    public function getTotalByCustomerAndYearChartLine($criteria)
    {
        $periods = [$criteria->period];

        if ($criteria->comparePeriod) {
            $periods[] = $criteria->comparePeriod;
        }

        $qSub = DB::table('wg_customer_evaluation_minimum_standard_tracking_0312 as o')
            //->join('wg_customer_evaluation_minimum_standard_0312 as d', 'd.id', '=', 'o.customer_evaluation_minimum_standard_id')
            ->join('wg_customer_evaluation_minimum_standard_0312 as d', function ($join) {
                $join->on('d.id', '=', 'o.customer_evaluation_minimum_standard_id');
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
}
