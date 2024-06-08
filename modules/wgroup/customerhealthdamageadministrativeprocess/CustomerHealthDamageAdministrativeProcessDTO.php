<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageAdministrativeProcess;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use RainLab\User\Models\User;
use Wgroup\CustomerAbsenteeismIndirectCost\CustomerAbsenteeismIndirectCostDTO;
use Wgroup\CustomerEmployee\CustomerEmployeeDTO;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnosticDTO;


/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerHealthDamageAdministrativeProcessDTO {

    function __construct($model = null) {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1") {

        // recupera informacion basica del formulario
        if ($model) {
            $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model: Modelo CustomerHealthDamageAdministrativeProcess
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->employee = CustomerEmployeeDTO::parse($model->employee);
        $this->arl = $model->getArl();
        $this->isRelocated =  $model->isRelocated == 1;
        $this->observationRelocated = $model->observationRelocated;

        $this->isEnhancedStability =  $model->isEnhancedStability == 1;
        $this->observationEnhancedStability = $model->observationEnhancedStability;

        $this->isDisabilityPayment =  $model->isDisabilityPayment == 1;
        $this->observationDisabilityPayment = $model->observationDisabilityPayment;

        $this->isTutelage =  $model->isTutelage == 1;
        $this->resultTutelage = $model->resultTutelage;

        $this->isComplain =  $model->isComplain == 1;
        $this->resultComplain = $model->resultComplain;

        $this->whatCustomerSay = $model->whatCustomerSay;
        $this->whatCustomerExpect = $model->whatCustomerExpect;
        $this->medicalOccupationalConcept = $model->medicalOccupationalConcept;

        $this->createdAt = $model->created_at ? Carbon::parse($model->created_at)->timezone('America/Bogota')->format('d/m/Y H:i') : '';

        $this->tokensession = $this->getTokenSession(true);
    }


    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerHealthDamageAdministrativeProcess::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageAdministrativeProcess();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageAdministrativeProcess();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_employee_id = $object->employee->id;
        $model->arl = $object->arl == null ? null : $object->arl->value;

        $model->isRelocated =  $object->isRelocated;
        $model->observationRelocated = $object->observationRelocated;

        $model->isEnhancedStability =  $object->isEnhancedStability;
        $model->observationEnhancedStability = $object->observationEnhancedStability;

        $model->isDisabilityPayment =  $object->isDisabilityPayment;
        $model->observationDisabilityPayment = $object->observationDisabilityPayment;

        $model->isTutelage =  $object->isTutelage;
        $model->resultTutelage = $object->resultTutelage;

        $model->isComplain =  $object->isComplain;
        $model->resultComplain = $object->resultComplain;

        $model->whatCustomerSay = $object->whatCustomerSay;
        $model->whatCustomerExpect = $object->whatCustomerExpect;
        $model->medicalOccupationalConcept = $object->medicalOccupationalConcept;

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

        return CustomerHealthDamageAdministrativeProcess::find($model->id);
    }

    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1") {

        // parse model
        if ($model) {
            $this->setInfo($model, $fmt_response);
        }

        return $this;
    }

    public static function parse($info, $fmt_response = "1") {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerHealthDamageAdministrativeProcess) {
                    $parsed[] = (new CustomerHealthDamageAdministrativeProcessDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageAdministrativeProcess) {
            return (new CustomerHealthDamageAdministrativeProcessDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageAdministrativeProcessDTO();
            }
        }
    }

    private function getUserSsession() {
        if (!Auth::check())
            return null;

        return Auth::getUser();
    }

    private function getTokenSession($encode = false) {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }
}
