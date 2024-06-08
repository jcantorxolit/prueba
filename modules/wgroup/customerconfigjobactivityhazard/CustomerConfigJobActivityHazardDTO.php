<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerConfigJobActivityHazard;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\CustomerConfigJob\CustomerConfigJobDTO;
use Wgroup\CustomerConfigJobActivityHazardTracking\CustomerConfigJobActivityHazardTrackingDTO;
use Wgroup\CustomerConfigJobActivityIntervention\CustomerConfigJobActivityInterventionDTO;
use Wgroup\CustomerConfigMacroProcesses\CustomerConfigMacroProcessesDTO;
use Wgroup\CustomerConfigProcesses\CustomerConfigProcessesDTO;
use Wgroup\CustomerConfigWorkPlace\CustomerConfigWorkPlaceDTO;
use Wgroup\Models\Customer;
use AdeN\Api\Modules\Customer\ConfigJobActivityHazardRelation\CustomerConfigJobActivityHazardRelationRepository;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerConfigJobActivityHazardDTO
{

    function __construct($model = null)
    {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1")
    {

        // recupera informacion basica del formulario
        if ($model) {
            $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model: Modelo CustomerDiagnosticDTO
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;

        $this->jobActivityId = $model->job_activity_id;
        $this->classification = $model->classificationModel;
        $this->type = $model->typeModel;
        $this->description = $model->descriptionModel;
        $this->health = $model->effectModel;
        $this->exposure = $model->time_exposure;
        $this->observation = $model->observation;
        $this->controlMethodText = $model->control_method_text;
        $this->controlMethodSourceText = $model->control_method_source_text;
        $this->controlMethodMediumText = $model->control_method_medium_text;
        $this->controlMethodPersonText = $model->control_method_person_text;
        $this->controlMethodAdministrativeText = $model->control_method_administrative_text;

        $this->exposed = $model->exposed;
        $this->contractors = $model->contractors;
        $this->visitors = $model->visitors;
        $this->status = $model->status;


        $this->measureND = $model->getMeasureND();
        $this->measureNE = $model->getMeasureNE();
        $this->measureNC = $model->getMeasureNC();

        $riskValue = (($this->measureND->value * $this->measureNE->value) * $this->measureNC->value);

        if ($riskValue >= 600 && $riskValue <= 4000) {
            $this->riskValue = 'No Aceptable';
        } else if ($riskValue >= 150 && $riskValue <= 500) {
            $this->riskValue = 'No Aceptable o Aceptable con control especifico';
        } else if ($riskValue >= 40 && $riskValue <= 120) {
            $this->riskValue = 'Mejorable';
        } else if ($riskValue >= 10 && $riskValue <= 39) {
            $this->riskValue = 'Aceptable';
        } else {
            $this->riskValue = null;
        }

        $this->interventions = CustomerConfigJobActivityInterventionDTO::parse($model->getInterventions());
        $this->tokensession = $this->getTokenSession(true);
    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerConfigJobActivityHazard::find($object->id))) {
                // No existe
                $model = new CustomerConfigJobActivityHazard();
                $isEdit = false;
            }
        } else {
            $model = new CustomerConfigJobActivityHazard();
            $isEdit = false;
        }

        $oldContent = $isEdit ? CustomerConfigJobActivityHazardDTO::entityToString(CustomerConfigJobActivityHazardDTO::parse($model)) : null;
        $oldContentJson = $isEdit ? CustomerConfigJobActivityHazardDTO::entityToJsonString(CustomerConfigJobActivityHazardDTO::parse($model)) : null;

        /** :: ASIGNO DATOS BASICOS ::  **/
        // cliente asociado
        $model->job_activity_id = $object->jobActivityId;
        $model->classification = $object->classification == null ? null : $object->classification->id;
        $model->type = $object->type == null ? null : $object->type->id;
        $model->description = $object->description == null ? null : $object->description->id;
        $model->health_effect = $object->health == null ? null : $object->health->id;
        $model->time_exposure = $object->exposure;
        $model->observation = $object->observation;
        $model->control_method_source_text = $object->controlMethodSourceText;
        $model->control_method_medium_text = $object->controlMethodMediumText;
        $model->control_method_person_text = $object->controlMethodPersonText;
        $model->control_method_administrative_text = isset($object->controlMethodAdministrativeText) ? $object->controlMethodAdministrativeText : '';
        $model->measure_nd = $object->measureND == null ? null : $object->measureND->id;
        $model->measure_ne = $object->measureNE == null ? null : $object->measureNE->id;
        $model->measure_nc = $object->measureNC == null ? null : $object->measureNC->id;

        $model->exposed = $object->exposed;
        $model->contractors = $object->contractors;
        $model->visitors = $object->visitors;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            $newContent = CustomerConfigJobActivityHazardDTO::entityToString(CustomerConfigJobActivityHazardDTO::parse($model));
            $newContentJson = CustomerConfigJobActivityHazardDTO::entityToJsonString(CustomerConfigJobActivityHazardDTO::parse($model));

            //CustomerConfigJobActivityHazardTrackingDTO::create($model->id, "Editar", "Valor anterior:\n$oldContent\n\nNuevo Valor:\n$newContent", $conte, null, "Matriz Manual");
            CustomerConfigJobActivityHazardTrackingDTO::create(
                $model->id,
                "Editar",
                "Valor anterior:\n$oldContent\n\nNuevo Valor:\n$newContent",
                $oldContentJson,
                $newContentJson,
                $object->reason ? $object->reason->value : null,
                $object->reasonObservation,
                "Matriz Manual"
            );
            // Actualiza timestamp
            $model->touch();
        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;
            $model->status = "Pendiente";

            // Guarda
            $model->save();

            $newContent = CustomerConfigJobActivityHazardDTO::entityToString(CustomerConfigJobActivityHazardDTO::parse($model));
            $newContentJson = CustomerConfigJobActivityHazardDTO::entityToJsonString(CustomerConfigJobActivityHazardDTO::parse($model));

            CustomerConfigJobActivityHazardTrackingDTO::create(
                $model->id,
                "Crear",
                "Crea el registro satisfactoriamente con los siguientes valores:\n$newContent",
                $newContentJson,
                null,
                $object->reason ? $object->reason->value : null,
                $object->reasonObservation,
                "Matriz Manual"
            );
        }

        $object->id = $model->id;

        if (isset($object->customerConfigJobActivityId)) {
            CustomerConfigJobActivityHazardRelationRepository::create($object->customerConfigJobActivityId, $model->id);
        }

        CustomerConfigJobActivityInterventionDTO::bulkInsert($object);



        return CustomerConfigJobActivityHazard::find($model->id);
    }

    public static function update($object)
    {
        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (($model = CustomerConfigJobActivityHazard::find($object->id))) {
                if (isset($object->observation)) {
                    $model->reason = $object->observation;
                }
                $model->status = $object->status;
                $model->save();
                return CustomerConfigJobActivityHazard::find($model->id);
            }
        }

        return null;
    }

    private static function entityToJsonString($entity)
    {
        $data = [
            "classification" => $entity->classification ? $entity->classification->name : null,
            "type" => $entity->type ? $entity->type->name : null,
            "description" => $entity->description ? $entity->description->name : null,
            "healthEffect" => $entity->health ? $entity->health->name : null,
            "observation" => $entity->observation,
            "exposure" => $entity->exposure,
            "controlMethodSourceText" => $entity->controlMethodSourceText,
            "controlMethodMediumText" => $entity->controlMethodMediumText,
            "controlMethodPersonText" => $entity->controlMethodPersonText,
            "ND" => $entity->measureND ? $entity->measureND->name : null,
            "NE" => $entity->measureNE ? $entity->measureNE->name : null,
            "NC" => $entity->measureNC ? $entity->measureNC->name : null,
            "riskValue" => $entity->riskValue
        ];

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    private static function entityToString($entity)
    {
        $data = [
            "Clasificación" => $entity->classification == null ? null : $entity->classification->name,
            "Tipo Peligro" => $entity->type == null ? null : $entity->type->name,
            "Descripción" => $entity->description == null ? null : $entity->description->name,
            "Posibles Efectos Salud" => $entity->health == null ? null : $entity->health->name,
            "Observación" => $entity->observation,
            "Timpo Exposición" => $entity->exposure,
            "MC Fuente" => $entity->controlMethodSourceText,
            "MC Medio" => $entity->controlMethodMediumText,
            "MC Persona" => $entity->controlMethodPersonText,
            "ND" => $entity->measureND ? $entity->measureND->name : null,
            "NE" => $entity->measureNE ? $entity->measureNE->name : null,
            "NC" => $entity->measureNC ? $entity->measureNC->name : null,
            "Valoración Riesgo" => $entity->riskValue
        ];

        return str_replace("Array", "", print_r($data, true));
    }



    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {

        // parse model
        if ($model) {
            $this->setInfo($model, $fmt_response);
        }

        return $this;
    }

    private function parseArray($model, $fmt_response = "1")
    {

        // parse model
        switch ($fmt_response) {
            case "2":
                $this->getReportSummaryPrg($model);
                break;
            case "3":
                $this->getReportSummaryAdv($model);
                break;
            default:
                $this->getBasicInfo($model);
        }

        return $this;
    }

    public static function parse($info, $fmt_response = "1")
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerConfigJobActivityHazard) {
                    $parsed[] = (new CustomerConfigJobActivityHazardDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerConfigJobActivityHazardDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerConfigJobActivityHazard) {
            return (new CustomerConfigJobActivityHazardDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerConfigJobActivityHazardDTO();
            }
        }
    }

    private function getUserSsession()
    {
        if (!Auth::check())
            return null;

        return Auth::getUser();
    }

    private function getTokenSession($encode = false)
    {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }
}
