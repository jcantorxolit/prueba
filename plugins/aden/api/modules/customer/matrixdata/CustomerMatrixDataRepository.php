<?php
/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\MatrixData;

use AdeN\Api\Classes\BaseRepository;
use Carbon\Carbon;
use DB;
use Exception;
use Wgroup\SystemParameter\SystemParameter;

class CustomerMatrixDataRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerMatrixDataModel());

        $this->service = new CustomerMatrixDataService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_matrix_data.id",
            "project" => "wg_customer_matrix_project.name AS project",
            "activity" => "wg_customer_matrix_activity.name AS activity",
            "aspect" => "wg_customer_matrix_environmental_aspect.name AS aspect",
            "impact" => "wg_customer_matrix_environmental_impact.name AS impact",
            "environmentalImpactIn" => "wg_customer_matrix_data.environmental_impact_in",
            "environmentalImpactEx" => "wg_customer_matrix_data.environmental_impact_ex",
            "environmentalImpactPr" => "wg_customer_matrix_data.environmental_impact_pr",
            "environmentalImpactRe" => "wg_customer_matrix_data.environmental_impact_re",
            "environmentalImpactRv" => "wg_customer_matrix_data.environmental_impact_rv",
            "environmentalImpactSe" => "wg_customer_matrix_data.environmental_impact_se",
            "environmentalImpactFr" => "wg_customer_matrix_data.environmental_impact_fr",

            "nia" => DB::raw("(3 * IFNULL(wg_customer_matrix_data.environmental_impact_in,0)) + (2 * IFNULL(wg_customer_matrix_data.environmental_impact_ex,0)) + IFNULL(wg_customer_matrix_data.environmental_impact_pr,0) + IFNULL(wg_customer_matrix_data.environmental_impact_re,0) + IFNULL(wg_customer_matrix_data.environmental_impact_rv,0) + IFNULL(wg_customer_matrix_data.environmental_impact_se,0) + IFNULL(wg_customer_matrix_data.environmental_impact_fr,0) AS nia"),

            "legalImpactE" => "wg_customer_matrix_data.legal_impact_e",
            "legalImpactC" => "wg_customer_matrix_data.legal_impact_c",

            "legalImpactCriterion" => DB::raw("IFNULL(wg_customer_matrix_data.legal_impact_e,0) + IFNULL(wg_customer_matrix_data.legal_impact_c,0) AS legalImpactCriterion"),

            "interestedPartAc" => "wg_customer_matrix_data.interested_part_ac",
            "interestedPartGe" => "wg_customer_matrix_data.interested_part_ge",

            "interestedPartCriterion" => DB::raw("IFNULL(wg_customer_matrix_data.interested_part_ac,0) + IFNULL(wg_customer_matrix_data.interested_part_ge,0) AS interestedPartCriterion"),
            "totalAspect" => DB::raw("(3 * IFNULL(wg_customer_matrix_data.environmental_impact_in,0)) + (2 * IFNULL(wg_customer_matrix_data.environmental_impact_ex,0)) + IFNULL(wg_customer_matrix_data.environmental_impact_pr,0) + IFNULL(wg_customer_matrix_data.environmental_impact_re,0) + IFNULL(wg_customer_matrix_data.environmental_impact_rv,0) + IFNULL(wg_customer_matrix_data.environmental_impact_se,0) + IFNULL(wg_customer_matrix_data.environmental_impact_fr,0) + IFNULL(wg_customer_matrix_data.legal_impact_e,0) + IFNULL(wg_customer_matrix_data.legal_impact_c,0) + IFNULL(wg_customer_matrix_data.interested_part_ac,0) + IFNULL(wg_customer_matrix_data.interested_part_ge,0) AS totalAspect"),

            "nature" => "matrix_nature.item AS nature",
            "emergencyConditionIn" => "wg_customer_matrix_data.emergency_condition_in",
            "emergencyConditionEx" => "wg_customer_matrix_data.emergency_condition_ex",
            "emergencyConditionPr" => "wg_customer_matrix_data.emergency_condition_pr",
            "emergencyConditionRe" => "wg_customer_matrix_data.emergency_condition_re",
            "emergencyConditionRv" => "wg_customer_matrix_data.emergency_condition_rv",
            "emergencyConditionSe" => "wg_customer_matrix_data.emergency_condition_se",
            "emergencyConditionFr" => "wg_customer_matrix_data.emergency_condition_fr",

            "emergencyNia" => DB::raw("(3 * IFNULL(wg_customer_matrix_data.emergency_condition_in,0)) + (2 * IFNULL(wg_customer_matrix_data.emergency_condition_ex,0)) + IFNULL(wg_customer_matrix_data.emergency_condition_pr,0) + IFNULL(wg_customer_matrix_data.emergency_condition_re,0) + IFNULL(wg_customer_matrix_data.emergency_condition_rv,0) + IFNULL(wg_customer_matrix_data.emergency_condition_se,0) + IFNULL(wg_customer_matrix_data.emergency_condition_fr,0) AS emergencyNia"),

            "controlTypeE" => DB::raw("MAX(CASE WHEN wg_customer_matrix_data_control.type = '001' THEN wg_customer_matrix_data_control.description END) AS controlTypeE"),
            "controlTypeS" => DB::raw("MAX(CASE WHEN wg_customer_matrix_data_control.type = '002' THEN wg_customer_matrix_data_control.description END) AS controlTypeS"),
            "controlTypeCI" => DB::raw("MAX(CASE WHEN wg_customer_matrix_data_control.type = '003' THEN wg_customer_matrix_data_control.description END) AS controlTypeCI"),
            "controlTypeCA" => DB::raw("MAX(CASE WHEN wg_customer_matrix_data_control.type = '004' THEN wg_customer_matrix_data_control.description END) AS controlTypeCA"),
            "controlTypeSL" => DB::raw("MAX(CASE WHEN wg_customer_matrix_data_control.type = '005' THEN wg_customer_matrix_data_control.description END) AS controlTypeSL"),
            "controlTypeEPP" => DB::raw("MAX(CASE WHEN wg_customer_matrix_data_control.type = '006' THEN wg_customer_matrix_data_control.description END) AS controlTypeEPP"),

            "associateProgram" => "wg_customer_matrix_data.associate_program",
            "registry" => "wg_customer_matrix_data.registry",
            "responsible" => "responsible.responsible",
            "customerMatrixId" => "wg_customer_matrix_data.customer_matrix_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $qResponsible = DB::table('wg_customer_matrix_data_responsible')
            ->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_responsible')), function ($join) {
                $join->on('matrix_responsible.value', '=', 'wg_customer_matrix_data_responsible.responsible');

            })
            ->select(
                'wg_customer_matrix_data_responsible.customer_matrix_data_id',
                DB::raw("GROUP_CONCAT(matrix_responsible.item) AS responsible")
            );

        $query->join("wg_customer_matrix", function ($join) {
            $join->on('wg_customer_matrix_data.customer_matrix_id', '=', 'wg_customer_matrix.id');

        })->join("wg_customer_matrix_project", function ($join) {
            $join->on('wg_customer_matrix_project.id', '=', 'wg_customer_matrix_data.customer_matrix_project_id');

        })->join("wg_customer_matrix_activity", function ($join) {
            $join->on('wg_customer_matrix_activity.id', '=', 'wg_customer_matrix_data.customer_matrix_activity_id');

        })->leftjoin("wg_customer_matrix_environmental_aspect", function ($join) {
            $join->on('wg_customer_matrix_environmental_aspect.id', '=', 'wg_customer_matrix_data.customer_matrix_environmental_aspect_id');

        })->leftjoin("wg_customer_matrix_environmental_impact", function ($join) {
            $join->on('wg_customer_matrix_environmental_impact.id', '=', 'wg_customer_matrix_data.customer_matrix_environmental_impact_id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_in')), function ($join) {
            $join->on('matrix_environmental_impact_in.value', '=', 'wg_customer_matrix_data.environmental_impact_in');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_ex')), function ($join) {
            $join->on('matrix_environmental_impact_ex.value', '=', 'wg_customer_matrix_data.environmental_impact_ex');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_pr')), function ($join) {
            $join->on('matrix_environmental_impact_pr.value', '=', 'wg_customer_matrix_data.environmental_impact_pr');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_re')), function ($join) {
            $join->on('matrix_environmental_impact_re.value', '=', 'wg_customer_matrix_data.environmental_impact_re');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_rv')), function ($join) {
            $join->on('matrix_environmental_impact_rv.value', '=', 'wg_customer_matrix_data.environmental_impact_rv');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_se')), function ($join) {
            $join->on('matrix_environmental_impact_se.value', '=', 'wg_customer_matrix_data.environmental_impact_se');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_fr')), function ($join) {
            $join->on('matrix_environmental_impact_fr.value', '=', 'wg_customer_matrix_data.environmental_impact_fr');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_legal_impact_e')), function ($join) {
            $join->on('matrix_legal_impact_e.value', '=', 'wg_customer_matrix_data.legal_impact_e');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_legal_impact_e', 'matrix_legal_impact_c')), function ($join) {
            $join->on('matrix_legal_impact_c.value', '=', 'wg_customer_matrix_data.legal_impact_c');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_interested_part_ac')), function ($join) {
            $join->on('matrix_interested_part_ac.value', '=', 'wg_customer_matrix_data.interested_part_ac');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_interested_part_ge')), function ($join) {
            $join->on('matrix_interested_part_ge.value', '=', 'wg_customer_matrix_data.interested_part_ge');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_nature')), function ($join) {
            $join->on('matrix_nature.value', '=', 'wg_customer_matrix_data.nature');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_in', 'matrix_emergency_condition_in')), function ($join) {
            $join->on('matrix_emergency_condition_in.value', '=', 'wg_customer_matrix_data.emergency_condition_in');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_ex', 'matrix_emergency_condition_ex')), function ($join) {
            $join->on('matrix_emergency_condition_ex.value', '=', 'wg_customer_matrix_data.emergency_condition_ex');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_pr', 'matrix_emergency_condition_pr')), function ($join) {
            $join->on('matrix_emergency_condition_pr.value', '=', 'wg_customer_matrix_data.emergency_condition_pr');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_re', 'matrix_emergency_condition_re')), function ($join) {
            $join->on('matrix_emergency_condition_re.value', '=', 'wg_customer_matrix_data.emergency_condition_re');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_rv', 'matrix_emergency_condition_rv')), function ($join) {
            $join->on('matrix_emergency_condition_rv.value', '=', 'wg_customer_matrix_data.emergency_condition_rv');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_se', 'matrix_emergency_condition_se')), function ($join) {
            $join->on('matrix_emergency_condition_se.value', '=', 'wg_customer_matrix_data.emergency_condition_se');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_environmental_impact_fr', 'matrix_emergency_condition_fr')), function ($join) {
            $join->on('matrix_emergency_condition_fr.value', '=', 'wg_customer_matrix_data.emergency_condition_fr');

        })->leftjoin("wg_customer_matrix_data_control", function ($join) {
            $join->on('wg_customer_matrix_data_control.customer_matrix_data_id', '=', 'wg_customer_matrix_data.id');

        })->leftjoin(DB::raw(SystemParameter::getRelationTable('matrix_control_type')), function ($join) {
            $join->on('matrix_control_type.value', '=', 'wg_customer_matrix_data_control.type');

        })->leftjoin(DB::raw("({$qResponsible->toSql()}) AS responsible"), function ($join) {
            $join->on('responsible.customer_matrix_data_id', '=', 'wg_customer_matrix_data.id');

        })
        ->mergeBindings($qResponsible)
        ->groupBy('wg_customer_matrix_data.id');

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        $isNewRecord = false;

        $authUser = $this->getAuthUser();

        if (!($entityModel = $this->find($entity->id))) {
            $entityModel = $this->model->newInstance();
            $isNewRecord = true;
        }

        $entityModel->customerMatrixId = $entity->customerMatrixId;
        $entityModel->customerMatrixProjectId = $entity->customerMatrixProjectId;
        $entityModel->customerMatrixActivityId = $entity->customerMatrixActivityId;
        $entityModel->customerMatrixEnvironmentalAspectId = $entity->customerMatrixEnvironmentalAspectId ? $entity->customerMatrixEnvironmentalAspectId->id : null;
        $entityModel->customerMatrixEnvironmentalImpactId = $entity->customerMatrixEnvironmentalImpactId ? $entity->customerMatrixEnvironmentalImpactId->id : null;
        $entityModel->environmentalImpactIn = $entity->environmentalImpactIn;
        $entityModel->environmentalImpactEx = $entity->environmentalImpactEx;
        $entityModel->environmentalImpactPr = $entity->environmentalImpactPr;
        $entityModel->environmentalImpactRe = $entity->environmentalImpactRe;
        $entityModel->environmentalImpactRv = $entity->environmentalImpactRv;
        $entityModel->environmentalImpactSe = $entity->environmentalImpactSe;
        $entityModel->environmentalImpactFr = $entity->environmentalImpactFr;
        $entityModel->legalImpactE = $entity->legalImpactE;
        $entityModel->legalImpactC = $entity->legalImpactC;
        $entityModel->interestedPartAc = $entity->interestedPartAc;
        $entityModel->interestedPartGe = $entity->interestedPartGe;
        $entityModel->nature = $entity->nature ? $entity->nature->value : null;
        $entityModel->emergencyConditionIn = $entity->emergencyConditionIn;
        $entityModel->emergencyConditionEx = $entity->emergencyConditionEx;
        $entityModel->emergencyConditionPr = $entity->emergencyConditionPr;
        $entityModel->emergencyConditionRe = $entity->emergencyConditionRe;
        $entityModel->emergencyConditionRv = $entity->emergencyConditionRv;
        $entityModel->emergencyConditionSe = $entity->emergencyConditionSe;
        $entityModel->emergencyConditionFr = $entity->emergencyConditionFr;
        $entityModel->scope = $entity->scope ? $entity->scope->value : null;
        $entityModel->associateProgram = $entity->associateProgram;
        $entityModel->registry = $entity->registry;
        $entityModel->createdby = $entity->createdby;
        $entityModel->updatedby = $entity->updatedby;

        if ($isNewRecord) {
            $entityModel->isDeleted = false;
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $result = $entityModel;

        return $result;
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $authUser = $this->getAuthUser();
        $entityModel->isDeleted = true;
        $entityModel->updatedBy = $authUser ? $authUser->id : 1;
        $entityModel->updatedAt = Carbon::now();
        $entityModel->save();

        $result["result"] = true;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerMatrixId = $model->customerMatrixId;
            $entity->customerMatrixProjectId = $model->customerMatrixProjectId;
            $entity->customerMatrixActivityId = $model->customerMatrixActivityId;
            $entity->customerMatrixEnvironmentalAspectId = $model->customerMatrixEnvironmentalAspectId;
            $entity->customerMatrixEnvironmentalImpactId = $model->customerMatrixEnvironmentalImpactId;
            $entity->environmentalImpactIn = $model->environmentalImpactIn;
            $entity->environmentalImpactEx = $model->environmentalImpactEx;
            $entity->environmentalImpactPr = $model->environmentalImpactPr;
            $entity->environmentalImpactRe = $model->environmentalImpactRe;
            $entity->environmentalImpactRv = $model->environmentalImpactRv;
            $entity->environmentalImpactSe = $model->environmentalImpactSe;
            $entity->environmentalImpactFr = $model->environmentalImpactFr;
            $entity->legalImpactE = $model->legalImpactE;
            $entity->legalImpactC = $model->legalImpactC;
            $entity->interestedPartAc = $model->interestedPartAc;
            $entity->interestedPartGe = $model->interestedPartGe;
            $entity->nature = $model->getNature();
            $entity->emergencyConditionIn = $model->emergencyConditionIn;
            $entity->emergencyConditionEx = $model->emergencyConditionEx;
            $entity->emergencyConditionPr = $model->emergencyConditionPr;
            $entity->emergencyConditionRe = $model->emergencyConditionRe;
            $entity->emergencyConditionRv = $model->emergencyConditionRv;
            $entity->emergencyConditionSe = $model->emergencyConditionSe;
            $entity->emergencyConditionFr = $model->emergencyConditionFr;
            $entity->scope = $model->getScope();
            $entity->associateProgram = $model->associateProgram;
            $entity->registry = $model->registry;
            $entity->createdby = $model->createdby;
            $entity->updatedby = $model->updatedby;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;

            return $entity;
        } else {
            return null;
        }
    }
}
