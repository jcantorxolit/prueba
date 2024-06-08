<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ImprovementPlan;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\CmsHelper;
use AdeN\Api\Helpers\ExportHelper;
use AdeN\Api\Modules\Customer\CustomerModel;
use AdeN\Api\Modules\Customer\ImprovementPlanComment\CustomerImprovementPlanCommentRepository;
use DB;
use Exception;
use Wgroup\SystemParameter\SystemParameter;
use AdeN\Api\Modules\Customer\ImprovementPlanActionPlan\CustomerImprovementPlanActionPlanRepository;
use Carbon\Carbon;
use Queue;


class CustomerImprovementPlanRepository extends BaseRepository
{
    protected $service;

    const ENTITY_NAME = "Wgroup\\Employee\\Employee";

    public function __construct()
    {
        parent::__construct(new CustomerImprovementPlanModel());

        $this->service = new CustomerImprovementPlanService();
    }

    public static function getCustomFilters()
    {
        return [
            ["alias" => "Centro Trabajo", "name" => "workplace"],
            ["alias" => "Macroproceso", "name" => "macroProcess"],
            ["alias" => "Proceso", "name" => "process"],
            ["alias" => "Cargo", "name" => "job"],
            ["alias" => "Actividad", "name" => "activity"],
            ["alias" => "Rutinario", "name" => "isRoutine"],
            ["alias" => "Clasificación", "name" => "classification"],
            ["alias" => "Tipo Peligro", "name" => "type"],
            ["alias" => "Descripcion", "name" => "description"],
            ["alias" => "Posibles efectos salud", "name" => "effect"],
            ["alias" => "Tiempo exposicion", "name" => "timeExposure"],
            ["alias" => "Metodo control", "name" => "controlMethodSourceText"],
            ["alias" => "Control", "name" => "controlMethodMediumText"],
            ["alias" => "Control", "name" => "controlMethodPersonText"],
            ["alias" => "Control", "name" => "controlMethodAdministrativeText"],
            ["alias" => "Nivel deficiencia ND", "name" => "measureND"],
            ["alias" => "Nivel exposicion NE", "name" => "measureNE"],
            ["alias" => "Nivel consecuencia", "name" => "measureNC"],
            ["alias" => "Nivel probabilidad", "name" => "levelP"],
            ["alias" => "Interpretación nivel probabilidad", "name" => "levelIP"],
            ["alias" => "Nivel riesgo", "name" => "levelR"],
            ["alias" => "Interpretación nivel riesgo", "name" => "levelIR"],
            ["alias" => "Valoración Riesgo", "name" => "riskValue"],
        ];
    }

    public function getMandatoryFilters()
    {
        return [
            array("field" => 'isActive', "operator" => 'eq', "value" => '1'),
        ];
    }

    private function allResult($criteria)
    {
        $this->parseCriteria($criteria);

        $query = $this->query();

        $qAgentUser = CustomerModel::getRelatedAgentAndUserRaw($criteria);
        $qActionPlan = CustomerImprovementPlanModel::getRelatedActionPlanStatsRaw($criteria);

        $query->join(DB::raw(SystemParameter::getRelationTable('improvement_plan_origin')), function ($join) {
            $join->on('wg_customer_improvement_plan.entityName', '=', 'improvement_plan_origin.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('improvement_plan_type')), function ($join) {
            $join->on('wg_customer_improvement_plan.type', '=', 'improvement_plan_type.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('improvement_plan_status')), function ($join) {
            $join->on('wg_customer_improvement_plan.status', '=', 'improvement_plan_status.value');
        })->leftjoin(DB::raw(SystemParameter::getRelationTable('wg_common_active_status', 'require_analysis')), function ($join) {
            $join->on('wg_customer_improvement_plan.isRequiresAnalysis', '=', 'require_analysis.value');
        })->leftjoin(DB::raw("({$qAgentUser->toSql()}) as responsible"), function ($join) {
            $join->on('wg_customer_improvement_plan.responsible', '=', 'responsible.id');
            $join->on('wg_customer_improvement_plan.responsibleType', '=', 'responsible.type');
            $join->on('wg_customer_improvement_plan.customer_id', '=', 'responsible.customer_id');
        })->leftjoin(DB::raw("({$qActionPlan->toSql()}) as actionPlan"), function ($join) {
            $join->on('wg_customer_improvement_plan.id', '=', 'actionPlan.customer_improvement_plan_id');
        })->leftjoin("users", function ($join) {
            $join->on('wg_customer_improvement_plan.createdBy', '=', 'users.id');
        })
            ->mergeBindings($qAgentUser)
            ->mergeBindings($qActionPlan);

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_improvement_plan.id",
            "origin" => DB::raw("CASE WHEN wg_customer_improvement_plan.entityName = 'EM_0312' THEN CONCAT(improvement_plan_origin.item, ' (', wg_customer_improvement_plan.period, ')') ELSE improvement_plan_origin.item END AS origin"),
            "classification" => "wg_customer_improvement_plan.classificationName AS classification",
            "type" => "improvement_plan_type.item as type",
            "description" => "wg_customer_improvement_plan.description",
            "isRequireAnalysisText" => "require_analysis.item AS isRequireAnalysisText",
            "responsibleName" => "responsible.name as responsibleName",
            "endDate" => "wg_customer_improvement_plan.endDate",
            "hasActionPlan" => DB::raw("IF(actionPlan.qty > 0, 'Si', 'No') AS hasActionPlan"),
            "status" => "improvement_plan_status.item AS status",

            "canComplete" => DB::raw("CASE WHEN (IFNULL(actionPlan.qty, 0) - IFNULL(actionPlan.completed, 0)) = 0 AND actionPlan.qty > 0 THEN 1 
                                            ELSE 0 END  AS canComplete"),
            "observation" => "wg_customer_improvement_plan.observation",
            "statusCode" => "wg_customer_improvement_plan.status AS statusCode",
            "isRequireAnalysis" => "wg_customer_improvement_plan.isRequiresAnalysis",
            "responsibleEmail" => "responsible.email as responsibleEmail",
            "customerId" => "wg_customer_improvement_plan.customer_id",
            "entityId" => "wg_customer_improvement_plan.entityId",
            "entityName" => "wg_customer_improvement_plan.entityName",
        ]);

        $authUser = $this->getAuthUser();

        return $this->allResult($criteria);
    }

    public function allEntity($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_improvement_plan.id",
            "origin" => DB::raw("CASE WHEN wg_customer_improvement_plan.entityName = 'EM_0312' THEN CONCAT(improvement_plan_origin.item, ' (', wg_customer_improvement_plan.period, ')') ELSE improvement_plan_origin.item END AS origin"),
            "classification" => "wg_customer_improvement_plan.classificationName AS classification",
            "type" => "improvement_plan_type.item as type",
            "description" => "wg_customer_improvement_plan.description",
            "responsibleName" => "responsible.name as responsibleName",
            "endDate" => "wg_customer_improvement_plan.endDate",
            "status" => "improvement_plan_status.item AS status",

            "observation" => "wg_customer_improvement_plan.observation",
            "responsibleEmail" => "responsible.email as responsibleEmail",
            "customerId" => "wg_customer_improvement_plan.customer_id",
            "entityId" => "wg_customer_improvement_plan.entityId",
            "entityName" => "wg_customer_improvement_plan.entityName",
        ]);

        $authUser = $this->getAuthUser();

        return $this->allResult($criteria);
    }

    public function allMatrix($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_improvement_plan.id",
            "type" => "improvement_plan_type.item as type",
            "endDate" => "wg_customer_improvement_plan.endDate",
            "description" => "wg_customer_improvement_plan.description",
            "observation" => "wg_customer_improvement_plan.observation",
            "responsibleName" => "responsible.name as responsibleName",
            "responsibleEmail" => "responsible.email as responsibleEmail",

            "customerId" => "wg_customer_improvement_plan.customer_id",
            "entityId" => "wg_customer_improvement_plan.entityId",
            "entityName" => "wg_customer_improvement_plan.entityName",
        ]);

        $authUser = $this->getAuthUser();

        return $this->allResult($criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
    }

    public function updateStatus($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            return;
        }

        $entity->oldStatus = $entityModel->status;

        $entityModel->status = $entity->status ? $entity->status->value : null;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->save();

        if (isset($entity->reason) && $entity->reason != null) {
            CustomerImprovementPlanCommentRepository::create($entity);

            try {
                if ($entity->status->value == 'CA') {
                    $userId = $authUser ? $authUser->id : 0;
                    CustomerImprovementPlanActionPlanRepository::bulkCancel($entity->id, $entity->reason, $userId);
                }
            } catch (\Exception $ex) {
                \Log::error($ex);
            }
        }

        //Change Investigacion AT status
        $query = DB::table("wg_customer_occupational_investigation_al")
        ->join("wg_customer_occupational_investigation_al_measure", function ($join) {
            $join->on('wg_customer_occupational_investigation_al_measure.customer_occupational_investigation_id', '=', 'wg_customer_occupational_investigation_al.id');
        })
        ->join("wg_customer_improvement_plan", function ($join) {
            $join->on('wg_customer_improvement_plan.entityId', '=', 'wg_customer_occupational_investigation_al_measure.id');
            $join->where('wg_customer_improvement_plan.entityName', '=', 'AT');
        })
        ->whereRaw("wg_customer_occupational_investigation_al.status = 'open'")
        ->whereRaw("wg_customer_improvement_plan.status IN ('AB')")
        ->select(DB::raw("COUNt(*) qty, wg_customer_occupational_investigation_al.id AS customer_occupational_investigation_id"))
        ->groupBy('wg_customer_occupational_investigation_al.id');

        DB::table("wg_customer_occupational_investigation_al")
            ->join("wg_customer_occupational_investigation_al_measure", function ($join) {
                $join->on('wg_customer_occupational_investigation_al_measure.customer_occupational_investigation_id', '=', 'wg_customer_occupational_investigation_al.id');
            })
            ->join("wg_customer_improvement_plan", function ($join) {
                $join->on('wg_customer_improvement_plan.entityId', '=', 'wg_customer_occupational_investigation_al_measure.id');
                $join->where('wg_customer_improvement_plan.entityName', '=', 'AT');
            })
            ->leftjoin(DB::raw("({$query->ToSql()}) AS wg_customer_occupational_investigation_al_open"), function ($join) {
                $join->on('wg_customer_occupational_investigation_al_open.customer_occupational_investigation_id', '=', 'wg_customer_occupational_investigation_al.id');    
            })
            ->mergeBindings($query)
            ->where("wg_customer_occupational_investigation_al.status", "open")
            ->whereIn("wg_customer_improvement_plan.status", ['CO', 'CA'])
            ->whereRaw("(wg_customer_occupational_investigation_al_open.customer_occupational_investigation_id IS NULL OR wg_customer_occupational_investigation_al_open.qty = 0)")
            ->update([
                'wg_customer_occupational_investigation_al.status' => 'close',
                'wg_customer_occupational_investigation_al.updatedBy' => $authUser ? $authUser->id : 1,
                'wg_customer_occupational_investigation_al.updated_at' => Carbon::now()
            ]);

        return $this->parseModelWithRelations($entityModel);
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->delete();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;

            return $entity;
        } else {
            return null;
        }
    }

    public function exportExcel($criteria)
    {
        $data = $this->service->getExportExcelData($criteria);
        $filename = 'PLANES_MEJORAMIENTO_' . Carbon::now()->timestamp;
        ExportHelper::excel($filename, 'PM', $data);
    }


    public function getPeriods(int $customerId)
    {
        return $this->service->getPeriods($customerId);
    }

    public function getChartStackedBarPlanByStatus($criteria)
    {
        return $this->service->getChartStackedBarPlanByStatus($criteria);
    }
}
