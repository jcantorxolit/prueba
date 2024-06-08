<?php

namespace AdeN\Api\Modules\Customer\ImprovementPlan;

use AdeN\Api\Classes\BaseService;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use DB;
use Log;
use Str;
use Wgroup\SystemParameter\SystemParameter;

class CustomerImprovementPlanService extends BaseService
{
    function __construct()
    {
        parent::__construct();
    }

    public function getExportExcelData($criteria)
    {
        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);
        $qActionPlan = CustomerImprovementPlanModel::getRelatedActionPlanStatsRaw();

        $query = DB::table('wg_customer_improvement_plan')
            ->join(DB::raw(SystemParameter::getRelationTable('improvement_plan_origin')), function ($join) {
                $join->on('wg_customer_improvement_plan.entityName', '=', 'improvement_plan_origin.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('improvement_plan_type')), function ($join) {
                $join->on('wg_customer_improvement_plan.type', '=', 'improvement_plan_type.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('improvement_plan_status')), function ($join) {
                $join->on('wg_customer_improvement_plan.status', '=', 'improvement_plan_status.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_common_active_status', 'require_analysis')), function ($join) {
                $join->on('wg_customer_improvement_plan.isRequiresAnalysis', '=', 'require_analysis.value');
            })
            ->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible"), function ($join) {
                $join->on('wg_customer_improvement_plan.responsible', '=', 'responsible.id');
                $join->on('wg_customer_improvement_plan.responsibleType', '=', 'responsible.type');
                $join->on('wg_customer_improvement_plan.customer_id', '=', 'responsible.customer_id');
            })
            ->leftjoin(DB::raw("({$qActionPlan->toSql()}) as actionPlan"), function ($join) {
                $join->on('wg_customer_improvement_plan.id', '=', 'actionPlan.customer_improvement_plan_id');
            })
            ->leftjoin("users", function ($join) {
                $join->on('wg_customer_improvement_plan.createdBy', '=', 'users.id');
            })
            ->select(
                "wg_customer_improvement_plan.id",
                DB::raw("CASE WHEN wg_customer_improvement_plan.entityName = 'EM_0312' THEN CONCAT(improvement_plan_origin.item, ' (', wg_customer_improvement_plan.period, ')') ELSE improvement_plan_origin.item END AS origin"),
                "wg_customer_improvement_plan.classificationName AS classification",
                "improvement_plan_type.item as type",
                "wg_customer_improvement_plan.description",
                "require_analysis.item AS isRequireAnalysisText",
                "responsible.name as responsibleName",
                "wg_customer_improvement_plan.endDate",
                DB::raw("IF(actionPlan.qty > 0, 'Si', 'No') AS hasActionPlan"),
                "improvement_plan_status.item AS status",
                DB::raw("CASE WHEN (IFNULL(actionPlan.qty, 0) - IFNULL(actionPlan.completed, 0)) = 0 AND actionPlan.qty > 0 THEN 1
                                                ELSE 0 END  AS canComplete"),
                "wg_customer_improvement_plan.observation",
                "wg_customer_improvement_plan.status AS statusCode",
                "wg_customer_improvement_plan.isRequiresAnalysis",
                "responsible.email as responsibleEmail",
                "wg_customer_improvement_plan.customer_id",
                "wg_customer_improvement_plan.entityId",
                "wg_customer_improvement_plan.entityName",
                "wg_customer_improvement_plan.created_at"
            )
            ->mergeBindings($qAgentUser)
            ->mergeBindings($qActionPlan)
            ->where('wg_customer_improvement_plan.customer_id', $criteria->customerId);

        $heading = [
            "ORIGEN" => "origin",
            "CLASIFICACIÓN" => "classification",
            "TIPO" => "type",
            "HALLAZGO" => "description",
            "OBSERVACIÓN" => "observation",
            "RESPONSABLE" => "responsibleName",
            "FECHA DE CREACIÓN" => "created_at",
            "FECHA DE CIERRE" => "endDate",
            "ESTADO" => "status",
            "TIENE PLAN DE ACCIÓN" => "hasActionPlan"
        ];

        return ExportHelper::headings($query->get(), $heading);
    }


    public function getPeriods(int $customerId)
    {
        return DB::table('wg_customer_improvement_plan')
            ->where('customer_id', $customerId)
            ->orderBy('endDate', 'DESC')
            ->select(
                DB::raw("YEAR(endDate) as value"),
                DB::raw("YEAR(endDate) as item")
            )
            ->distinct()
            ->get();
    }


    public function getChartStackedBarPlanByStatus($criteria)
    {
        $period = $criteria->period;

        $queryBase = DB::table('wg_customer_improvement_plan as plan')
            ->join(DB::raw(SystemParameter::getRelationTable('improvement_plan_origin', 'origin')), function ($join) {
                $join->on('plan.entityName', '=', 'origin.value');
            })
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('improvement_plan_status', 'status')), function ($join) {
                $join->on('plan.status', '=', 'status.value');
            })
            ->where('plan.customer_id', $criteria->customerId)
            ->when($period, function ($query) use ($period) {
                $query->whereYear('endDate', $period);
            })
            ->select(
                DB::raw("YEAR(endDate) as period"),
                DB::raw("CASE WHEN plan.entityName = 'EM_0312' THEN
                              CONCAT(origin.item, ' (', plan.period, ')')
                            ELSE origin.item
                       END AS origin"),
                'status.item as status'
            );

        $subquery = DB::table(DB::raw("({$queryBase->toSql()}) as o"))
            ->mergeBindings($queryBase)
            ->groupBy('o.origin', 'o.status')
            ->select(
                'o.status as label',
                'o.origin as dynamicColumn',
                DB::raw("COUNT(o.status) as total")
            );


        list($query, $valueColumns) = $this->getQueryTransformRowToColumns($subquery);

        $valueColumns = array_map(function ($item) {
            switch ($item['label']) {
                case 'Abierto':
                    $item['color'] = '#3877b4';
                    break;

                case 'Cancelado':
                    $item['color'] = '#e7e7e7';
                    break;

                case 'Completado':
                    $item['color'] = '#f59747';
                    break;
            }
            return $item;
        }, $valueColumns);

        $config = array(
            "labelColumn" => 'label',
            "valueColumns" => $valueColumns
        );

        return $this->chart->getChartBar($query->get(), $config);
    }
}
