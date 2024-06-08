<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerMatrixData;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerMatrixDataControl\CustomerMatrixDataControlDTO;
use Wgroup\CustomerMatrixDataResponsible\CustomerMatrixDataResponsible;
use Wgroup\CustomerMatrixDataResponsible\CustomerMatrixDataResponsibleDTO;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerMatrixDataDTO
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
     * @param $model : Modelo CustomerDiagnosticDTO
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->customerMatrixId = $model->customer_matrix_id;

        $this->project = $model->getCustomerMatrixProject();
        $this->activity = $model->getCustomerMatrixActivity();
        $this->environmentalAspect = $model->getCustomerMatrixEnvironmentalAspect();
        $this->environmentalImpact = $model->getCustomerMatrixEnvironmentalImpact();

        $this->environmentalImpactIn = $model->getEnvironmentalImpactIn();
        $this->environmentalImpactEx = $model->getEnvironmentalImpactEx();
        $this->environmentalImpactPr = $model->getEnvironmentalImpactPr();
        $this->environmentalImpactRe = $model->getEnvironmentalImpactRe();
        $this->environmentalImpactRv = $model->getEnvironmentalImpactRv();
        $this->environmentalImpactSe = $model->getEnvironmentalImpactSe();
        $this->environmentalImpactFr = $model->getEnvironmentalImpactFr();

        $this->legalImpactE = $model->getLegalImpactE();
        $this->legalImpactC = $model->getLegalImpactC();
        //$this->legalImpactCriterion = $model->getlegalImpactCriterion();

        $this->interestedPartAc = $model->getInterestedPartAc();
        $this->interestedPartGe = $model->getInterestedPartGe();
        //$this->interestedPartCriterion = $model->getInterestedPartCriterion();

        $this->nature = $model->getNature();
        $this->emergencyConditionIn = $model->getEmergencyConditionIn();
        $this->emergencyConditionEx = $model->getEmergencyConditionEx();
        $this->emergencyConditionPr = $model->getEmergencyConditionPr();
        $this->emergencyConditionRe = $model->getEmergencyConditionRe();
        $this->emergencyConditionRv = $model->getEmergencyConditionRv();
        $this->emergencyConditionSe = $model->getEmergencyConditionSe();
        $this->emergencyConditionFr = $model->getEmergencyConditionFr();
        $this->scope = $model->getScope();

        $this->associateProgram = $model->associate_program;
        $this->registry = $model->registry;

        $this->controlList = $model->getControls();
        $this->responsibleList = $model->getResponsible();

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');

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
            if (!($model = CustomerMatrixData::find($object->id))) {
                // No existe
                $model = new CustomerMatrixData();
                $isEdit = false;
            }
        } else {
            $model = new CustomerMatrixData();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->customer_matrix_id = $object->customerMatrixId;
        $model->customer_matrix_project_id = $object->project != null ? $object->project->id : null;
        $model->customer_matrix_activity_id = $object->activity != null ? $object->activity->id : null;
        $model->customer_matrix_environmental_aspect_id = $object->environmentalAspect != null ? $object->environmentalAspect->id : null;
        $model->customer_matrix_environmental_impact_id = $object->environmentalImpact != null ? $object->environmentalImpact->id : null;

        $model->environmental_impact_in = $object->environmentalImpactIn != null ? $object->environmentalImpactIn->value : null;
        $model->environmental_impact_ex = $object->environmentalImpactEx != null ? $object->environmentalImpactEx->value : null;
        $model->environmental_impact_pr = $object->environmentalImpactPr != null ? $object->environmentalImpactPr->value : null;
        $model->environmental_impact_re = $object->environmentalImpactRe != null ? $object->environmentalImpactRe->value : null;
        $model->environmental_impact_rv = $object->environmentalImpactRv != null ? $object->environmentalImpactRv->value : null;
        $model->environmental_impact_se = $object->environmentalImpactSe != null ? $object->environmentalImpactSe->value : null;
        $model->environmental_impact_fr = $object->environmentalImpactFr != null ? $object->environmentalImpactFr->value : null;


        $model->legal_impact_e = $object->legalImpactE != null ? $object->legalImpactE->value : null;
        $model->legal_impact_c = $object->legalImpactC != null ? $object->legalImpactC->value : null;

        $model->interested_part_ac = $object->interestedPartAc != null ? $object->interestedPartAc->value : null;
        $model->interested_part_ge = $object->interestedPartGe != null ? $object->interestedPartGe->value : null;
        $model->nature = $object->nature != null ? $object->nature->value : null;


        $model->emergency_condition_in = $object->emergencyConditionIn != null ? $object->emergencyConditionIn->value : null;
        $model->emergency_condition_ex = $object->emergencyConditionEx != null ? $object->emergencyConditionEx->value : null;
        $model->emergency_condition_pr = $object->emergencyConditionPr != null ? $object->emergencyConditionPr->value : null;
        $model->emergency_condition_re = $object->emergencyConditionRe != null ? $object->emergencyConditionRe->value : null;
        $model->emergency_condition_rv = $object->emergencyConditionRv != null ? $object->emergencyConditionRv->value : null;
        $model->emergency_condition_se = $object->emergencyConditionSe != null ? $object->emergencyConditionSe->value : null;
        $model->emergency_condition_fr = $object->emergencyConditionFr != null ? $object->emergencyConditionFr->value : null;

        $model->scope = $object->scope != null ? $object->scope->value : null;
        $model->associate_program = isset($object->associateProgram) ? $object->associateProgram : null;
        $model->registry = isset($object->registry) ? $object->registry : null;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

        }

        CustomerMatrixDataControlDTO::bulkInsert($object->controlList, $model->id);
        CustomerMatrixDataResponsibleDTO::bulkInsert($object->responsibleList, $model->id);

        return CustomerMatrixData::find($model->id);
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

                break;
            case "3":

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
                if ($model instanceof CustomerMatrixData) {
                    $parsed[] = (new CustomerMatrixDataDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerMatrixDataDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerMatrixData) {
            return (new CustomerMatrixDataDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerMatrixDataDTO();
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
