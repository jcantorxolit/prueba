<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigActivitySpecialRelation;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;
use AdeN\Api\Modules\Customer\ConfigActivitySpecial\CustomerConfigActivitySpecialRepository;
use AdeN\Api\Modules\Customer\ConfigAreaJobSpecialRelation\CustomerConfigAreaJobSpecialRelationRepository;
use AdeN\Api\Modules\Customer\ConfigAreaSpecial\CustomerConfigAreaSpecialRepository;
use AdeN\Api\Modules\Customer\ConfigBusinessUnitSpecialRelation\CustomerConfigBusinessUnitSpecialRelationModel;
use AdeN\Api\Modules\Customer\ConfigBusinessUnitSpecialRelation\CustomerConfigBusinessUnitSpecialRelationRepository;
use AdeN\Api\Modules\Customer\ConfigJobSpecial\CustomerConfigJobSpecialRepository;
use AdeN\Api\Modules\Customer\ConfigOfficeSpecial\CustomerConfigOfficeSpecialModel;
use AdeN\Api\Modules\Customer\ConfigOfficeSpecialExchangeControl\CustomerConfigOfficeSpecialExchangeControlRepository;
use AdeN\Api\Modules\Customer\ConfigProcessSpecial\CustomerConfigProcessSpecialModel;
use AdeN\Api\Modules\Customer\ConfigProcessSpecialRelation\CustomerConfigProcessSpecialRelationModel;
use AdeN\Api\Modules\Customer\ConfigSubprocessSpecial\CustomerConfigSubprocessSpecialModel;
use AdeN\Api\Modules\Customer\ConfigSubprocessSpecialRelation\CustomerConfigSubprocessSpecialRelationModel;
use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigActivitySpecialRelationRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigActivitySpecialRelationModel());

        $this->service = new CustomerConfigActivitySpecialRelationService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_activity_special_relation.id",
            "customerId" => "wg_customer_config_activity_special_relation.customer_id",
            "customerAreJobSpecialRelationId" => "wg_customer_config_activity_special_relation.customer_area_job_special_relation_id",
            "customerActivitySpecialId" => "wg_customer_config_activity_special_relation.customer_activity_special_id",
            "isRoutine" => "wg_customer_config_activity_special_relation.is_routine",
            "createdBy" => "wg_customer_config_activity_special_relation.created_by",
            "updatedBy" => "wg_customer_config_activity_special_relation.updated_by",
            "createdAt" => "wg_customer_config_activity_special_relation.created_at",
            "updatedAt" => "wg_customer_config_activity_special_relation.updated_at",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        /* Example relation
		$query->leftjoin("tableParent", function ($join) {
            $join->on('wg_customer_config_activity_special_relation.parent_id', '=', 'tableParent.id');
		}
		*/


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

        $entityModelOrigin = clone $entityModel;
        $entityModel->customerId = $entity->customerId;
        $entityModel->customerAreaJobSpecialRelationId = $entity->areaJobSpecialRelationId;
        $entityModel->customerActivitySpecialId = $entity->activitySpecialId;
        $entityModel->isRoutine = $entity->isRoutine == 1;


        if ($isNewRecord) {
            $entityModel->createdBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->updatedAt = Carbon::now();
            $entityModel->save();
        } else {
            $entityModel->updatedBy = $authUser ? $authUser->id : 1;
            $entityModel->save();
        }

        $this->parseObjectAndUpdateExchangeControl($entity, $entityModelOrigin, $isNewRecord);
        $result = $entityModel;

        return $result;
    }

    public static function bulkInsertOrUpdate($activityList, $parentId, $origin = null)
    {
        $reposity = new self;
        foreach ($activityList as $activity) {
            $activity->id = $activity->id >= 0 ? $activity->id : 0;
            $activity->areaJobSpecialRelationId = $parentId;
            $activity->activitySpecialId = (new self)->findOrCreateArea($activity)->id;
            $reposity->insertOrUpdate($activity);
        }
    }

    private function findOrCreateArea($entity)
    {
        $newEntity = new \stdClass();
        $newEntity->id = 0;
        $newEntity->customerId = $entity->customerId;
        $newEntity->name = $entity->name;
        return (new CustomerConfigActivitySpecialRepository)->findOrCreate($newEntity);
    }

    public function delete($id)
    {
        if (!($entityModel = $this->find($id))) {
            throw new Exception("Record not found to delete.");
        }

        $entityModel->name = (new CustomerConfigActivitySpecialRepository)->find($entityModel->customer_activity_special_id)->name;
        $entityModel->areaJobSpecialRelationId = $entityModel->customer_area_job_special_relation_id;
        $this->parseObjectAndUpdateExchangeControl($entityModel, null, false, true);
        $entityModel->delete();
        return $entityModel;
    }

    public function parseModelWithRelations($model)
    {
        $modelClass = get_class($this->model);

        if ($model instanceof $modelClass) {
            $model = (object) $model;
            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->customerAreJobSpecialRelationId = $model->customerAreJobSpecialRelationId;
            $entity->customerActivitySpecialId = $model->customerActivitySpecialId;
            $entity->isRoutine = $model->isRoutine;
            $entity->createdBy = $model->createdBy;
            $entity->updatedBy = $model->updatedBy;
            $entity->createdAt = $model->createdAt;
            $entity->updatedAt = $model->updatedAt;


            return $entity;
        } else {
            return null;
        }
    }

    public function parseObjectAndUpdateExchangeControl($entity, $origin, $isNewRecord, $isDelete = false)
    {

        $subprocess = null;
        $areaJob = (new CustomerConfigAreaJobSpecialRelationRepository)->find($entity->areaJobSpecialRelationId);
        $area = (new CustomerConfigAreaSpecialRepository)->find($areaJob->customer_area_special_id);
        $job = (new CustomerConfigJobSpecialRepository)->find($areaJob->customer_job_special_id);

        if ($areaJob->entity_type == "PROCESS") {
            $process = CustomerConfigProcessSpecialRelationModel::find($areaJob->customer_entity_special_relation_id);
            $processName = CustomerConfigProcessSpecialModel::find($process->customer_process_special_id);
        } else {
            $subprocessRelation = CustomerConfigSubprocessSpecialRelationModel::find($areaJob->customer_entity_special_relation_id);
            $subprocess = CustomerConfigSubprocessSpecialModel::find($subprocessRelation->customer_subprocess_special_id);
            $process = CustomerConfigProcessSpecialRelationModel::find($subprocessRelation->customer_process_special_relation_id);
            $processName = CustomerConfigProcessSpecialModel::find($process->customer_process_special_id);
        }

        $parent = (new CustomerConfigBusinessUnitSpecialRelationRepository)->parseModelWithRelations(CustomerConfigBusinessUnitSpecialRelationModel::find($process->customer_business_unit_special_relation_id));
        $office = CustomerConfigOfficeSpecialModel::find($parent->officeSpecialId);
        if (!$office->registration_date) {
            return;
        }

        $location = "Sede: {$office->name} / \n";
        $location .= "Unidad de Negocio: {$parent->businessUnitSpecial->name}  / \n";
        $location .= "Proceso: {$processName->name} / \n";
        if ($subprocess) {
            $location .= "Subproceso: {$subprocess->name} / \n";
        }
        $location .= "Area: {$area->name} / \n";
        $location .= "Cargo: {$job->name} / \n";
        $location .= "Actividad: {$entity->name}";

        $clasification = "ACT";
        if ($isNewRecord) {
            $clasification = "CRE";
            $description = "Se creó la información.";
        } elseif ($isDelete) {
            $description = "Se eliminó la información.";
        } else {
            if ($origin->isRoutine != $entity->isRoutine) {
                $description = "Se hizo un cambio en: \n ";
                $description .= "Tarea rutinaria: " . ($entity->isRoutine == 1 ? "SI" : "NO");
            }
        }

        if (!empty($description)) {
            $exchangeControl = (object)[
                "id" => 0,
                "customerId" => $entity->customerId,
                "clasification" => $clasification,
                "locationDescription" => $location,
                "description" => $description,
                "officeId" => $office->id,
                "item" => "006"
            ];

            CustomerConfigOfficeSpecialExchangeControlRepository::insertOrUpdate($exchangeControl);
        }
    }
}
