<?php

/**
 * Created by PhpStorm.
 * User: DAB
 * Date: 6/20/2017
 * Time: 7:27 AM
 */

namespace AdeN\Api\Modules\Customer\ConfigActivityStaging;

use AdeN\Api\Classes\BaseRepository;
use AdeN\Api\Helpers\SqlHelper;

use DB;
use Exception;
use Log;
use Carbon\Carbon;

class CustomerConfigActivityStagingRepository extends BaseRepository
{
    protected $service;

    public function __construct()
    {
        parent::__construct(new CustomerConfigActivityStagingModel());

        $this->service = new CustomerConfigActivityStagingService();
    }

    public function all($criteria)
    {
        $this->setColumns([
            "id" => "wg_customer_config_activity_staging.id",        
            "index" => "wg_customer_config_activity_staging.index",    
            "name" => "wg_customer_config_activity_staging.name",
            "status" => "wg_customer_config_activity_staging.status",            
            "isCritical" => "wg_customer_config_activity_staging.is_critical",            
            "classification" => "wg_customer_config_activity_staging.classification",            
            "type" => "wg_customer_config_activity_staging.type",            
            "description" => "wg_customer_config_activity_staging.description",            
            "healthEffect" => "wg_customer_config_activity_staging.health_effect",
            "observationHazard" => "wg_customer_config_activity_staging.observation_hazard",
            "timeExposure" => "wg_customer_config_activity_staging.time_exposure",
            "controlMethodSourceText" => "wg_customer_config_activity_staging.control_method_source_text",
            "controlMethodMediumText" => "wg_customer_config_activity_staging.control_method_medium_text",
            "controlMethodPersonText" => "wg_customer_config_activity_staging.control_method_person_text",                        
            "measureNd" => "wg_customer_config_activity_staging.measure_nd",            
            "measureNe" => "wg_customer_config_activity_staging.measure_ne",            
            "measureNc" => "wg_customer_config_activity_staging.measure_nc",
            "exposed" => "wg_customer_config_activity_staging.exposed",
            "contractors" => "wg_customer_config_activity_staging.contractors",
            "visitors" => "wg_customer_config_activity_staging.visitors",            
            "typeIntervention" => "wg_customer_config_activity_staging.type_intervention",
            "descriptionIntervention" => "wg_customer_config_activity_staging.description_intervention",            
            "trackingIntervention" => "wg_customer_config_activity_staging.tracking_intervention",
            "observationIntervention" => "wg_customer_config_activity_staging.observation_intervention",
            "observation" => "wg_customer_config_activity_staging.observation",            
            "isValid" => "wg_customer_config_activity_staging.is_valid",
            "sessionId" => "wg_customer_config_activity_staging.session_id",
            "customerId" => "wg_customer_config_activity_staging.customer_id",
        ]);

        $this->parseCriteria($criteria);

        $query = $this->query();

        $this->applyCriteria($query, $criteria);

        return $this->get($query, $criteria);
    }

    public function insertOrUpdate($entity)
    {
        if (!($entityModel = $this->find($entity->id))) {
            throw new \Exception('Record not found');        
        }

        $entityModel->customerId = $entity->customerId;
        $entityModel->name = $entity->name;
        $entityModel->status = $entity->status ? $entity->status->value : null;        
        $entityModel->isCritical = $entity->isCritical ? $entity->isCritical->value : null;
        $entityModel->classificationId = $entity->classification ? $entity->classification->id : null;
        $entityModel->classification = $entity->classification ? $entity->classification->name : null;
        $entityModel->typeId = $entity->type ? $entity->type->id : null;
        $entityModel->type = $entity->type ? $entity->type->name : null;
        $entityModel->descriptionId = $entity->description ? $entity->description->id : null;
        $entityModel->description = $entity->description ? $entity->description->name : null;
        $entityModel->healthEffectId = $entity->healthEffect ? $entity->healthEffect->id : null;
        $entityModel->healthEffect = $entity->healthEffect ? $entity->healthEffect->name : null;
        $entityModel->observationHazard = $entity->observationHazard;
        $entityModel->timeExposure = $entity->timeExposure;
        $entityModel->controlMethodSourceText = $entity->controlMethodSourceText;
        $entityModel->controlMethodMediumText = $entity->controlMethodMediumText;
        $entityModel->controlMethodPersonText = $entity->controlMethodPersonText;
        $entityModel->controlMethodAdministrativeText = $entity->controlMethodAdministrativeText;
        $entityModel->measureNdId = $entity->measureNd ? $entity->measureNd->id : null;
        $entityModel->measureNd = $entity->measureNd ? $entity->measureNd->name : null;
        $entityModel->measureNeId = $entity->measureNe ? $entity->measureNe->id : null;
        $entityModel->measureNe = $entity->measureNe ? $entity->measureNe->name : null;
        $entityModel->measureNcId = $entity->measureNc ? $entity->measureNc->id : null;
        $entityModel->measureNc = $entity->measureNc ? $entity->measureNc->name : null;
        $entityModel->exposed = $entity->exposed;
        $entityModel->contractors = $entity->contractors;
        $entityModel->visitors = $entity->visitors;
        $entityModel->typeInterventionId = $entity->typeIntervention ? $entity->typeIntervention->value : null;
        $entityModel->typeIntervention = $entity->typeIntervention ? $entity->typeIntervention->item : null;
        $entityModel->descriptionIntervention = $entity->descriptionIntervention;
        $entityModel->trackingInterventionId = $entity->trackingIntervention ? $entity->trackingIntervention->value : null;
        $entityModel->trackingIntervention = $entity->trackingIntervention ? $entity->trackingIntervention->item : null;
        $entityModel->observationIntervention = $entity->observationIntervention;
        $entityModel->oldValue = $this->getOldValue($entity);
        $entityModel->observation = null;
        $entityModel->index = $entity->index;
        $entityModel->sessionId = $entity->sessionId;
        $entityModel->isValid = true;

        $entityModel->save();    

        DB::statement("CALL TL_Activity_Staging({$entity->customerId}, '$entityModel->session_id')");

        return $this->parseModelWithRelations($entityModel);
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

            //Mapping fields
            $entity = new \stdClass();

            $entity->id = $model->id;
            $entity->customerId = $model->customerId;
            $entity->name = $model->name;
            $entity->status = $model->getStatus();            
            $entity->isCritical =  [ "name" => $model->isCritical, "value" => $model->isCritical ];            
            $entity->classification = $this->service->findOneClassification($model->classificationId);
            $entity->type = $this->service->findOneType($model->typeId);            
            $entity->description = $this->service->findOneDescription($model->descriptionId);            
            $entity->healthEffect = $this->service->findOneHealthEffect($model->healthEffectId);            
            $entity->observationHazard = $model->observationHazard;
            $entity->timeExposure = $model->timeExposure;
            $entity->controlMethodSourceText = $model->controlMethodSourceText;
            $entity->controlMethodMediumText = $model->controlMethodMediumText;
            $entity->controlMethodPersonText = $model->controlMethodPersonText;
            $entity->controlMethodAdministrativeText = $model->controlMethodAdministrativeText;
            $entity->measureNd = $this->service->findOneMeasure($model->measureNdId);            
            $entity->measureNe = $this->service->findOneMeasure($model->measureNeId);            
            $entity->measureNc = $this->service->findOneMeasure($model->measureNcId);            
            $entity->exposed = $model->exposed;
            $entity->contractors = $model->contractors;
            $entity->visitors = $model->visitors;
            $entity->typeIntervention = $model->getTypeIntervention();            
            $entity->descriptionIntervention = $model->descriptionIntervention;
            $entity->trackingIntervention = $model->getTrackingIntervention();            
            $entity->observationIntervention = $model->observationIntervention;
            $entity->observation = $model->observation;
            $entity->index = $model->index;
            $entity->sessionId = $model->sessionId;      
            $entity->isValid = $model->isValid == 1;

            return $entity;
        } else {
            return null;
        }
    }

    private function getOldValue($entity)
    {
 
        $data = [
            "classification" =>  $entity->classification ? $entity->classification->name : null,
            "type" => $entity->type ? $entity->type->name : null,
            "description" => $entity->description ? $entity->description->name : null,
            "healthEffect" => $entity->healthEffect ? $entity->healthEffect->name : null,
            "observation" => $entity->observationHazard,
            "exposure" => $entity->timeExposure,
            "controlMethodSourceText" => $entity->controlMethodSourceText,
            "controlMethodMediumText" => $entity->controlMethodMediumText,
            "controlMethodPersonText" => $entity->controlMethodPersonText,
            "ND" => $entity->measureNd ? $entity->measureNd->name : null,
            "NE" => $entity->measureNe ? $entity->measureNe->name : null,
            "NC" => $entity->measureNc ? $entity->measureNc->name : null,
            "riskValue" => $this->getRiskValue($entity)
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private function getRiskValue($entity)
    {
        $measureND = $entity->measureNd ? $entity->measureNd->value : 0;
        $measureNE = $entity->measureNe ? $entity->measureNe->value : 0;
        $measureNC = $entity->measureNc ? $entity->measureNc->value : 0;

        $riskValue = (($measureND * $measureNE) * $measureNC);

        if ($riskValue >= 600 && $riskValue <= 4000) {
            $riskText = 'No Aceptable';
        } else if ($riskValue >= 150 && $riskValue <= 500) {
            $riskText = 'No Aceptable o Aceptable con control especifico';
        } else if ($riskValue >= 40 && $riskValue <= 120) {
            $riskText = 'Mejorable';
        } else if ($riskValue >= 10 && $riskValue <= 39) {
            $riskText = 'Aceptable';
        } else {
            $riskText = null;
        }

        return $riskText;
    }
}
