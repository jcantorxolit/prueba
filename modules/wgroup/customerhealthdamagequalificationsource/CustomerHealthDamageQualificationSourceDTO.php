<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationSource;

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
use Wgroup\CustomerHealthDamageQualificationSourceDiagnostic\CustomerHealthDamageQualificationSourceDiagnosticDTO;
use Wgroup\CustomerHealthDamageQualificationSourceJustice\CustomerHealthDamageQualificationSourceJusticeDTO;
use Wgroup\CustomerHealthDamageQualificationSourceNational\CustomerHealthDamageQualificationSourceNationalDTO;
use Wgroup\CustomerHealthDamageQualificationSourceOpportunity\CustomerHealthDamageQualificationSourceOpportunityDTO;
use Wgroup\CustomerHealthDamageQualificationSourceRegional\CustomerHealthDamageQualificationSourceRegionalDTO;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnosticDTO;


/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerHealthDamageQualificationSourceDTO {

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
     * @param $model: Modelo CustomerHealthDamageQualificationSource
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->employee = CustomerEmployeeDTO::parse($model->employee);
        $this->arl = $model->getArl();
        $this->stepQualificationFirstOpportunity =  $model->stepQualificationFirstOpportunity == 1;
        $this->stepQualificationRegionalBoard =  $model->stepQualificationRegionalBoard == 1;
        $this->stepQualificationNationalBoard =  $model->stepQualificationNationalBoard == 1;
        $this->stepQualificationLaborJustice =  $model->stepQualificationLaborJustice == 1;
        $this->stepSecondInstance =  $model->stepSecondInstance == 1;
        $this->stepThirdInstance =  $model->stepThirdInstance == 1;
        $this->isActive =  $model->isActive == 1;
        $this->diagnostic =  CustomerHealthDamageQualificationSourceDiagnosticDTO::parse($model->getDiagnostic());
        $this->opportunity =  CustomerHealthDamageQualificationSourceOpportunityDTO::parse($model->getOpportunity());
        $this->regional =  CustomerHealthDamageQualificationSourceRegionalDTO::parse($model->getRegional());
        $this->national =  CustomerHealthDamageQualificationSourceNationalDTO::parse($model->getNational());
        $this->justiceFirst =  CustomerHealthDamageQualificationSourceJusticeDTO::parse($model->getJusticeFirst());
        $this->justiceSecond =  CustomerHealthDamageQualificationSourceJusticeDTO::parse($model->getJusticeSecond());
        $this->justiceThird =  CustomerHealthDamageQualificationSourceJusticeDTO::parse($model->getJusticeThird());

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
            if (!($model = CustomerHealthDamageQualificationSource::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageQualificationSource();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageQualificationSource();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_employee_id = $object->employee->id;
        $model->arl = $object->arl == null ? null : $object->arl->value;
        $model->stepQualificationFirstOpportunity =  $object->stepQualificationFirstOpportunity;
        $model->stepQualificationRegionalBoard = $object->stepQualificationRegionalBoard;
        $model->stepQualificationNationalBoard =  $object->stepQualificationNationalBoard;
        $model->stepQualificationLaborJustice = $object->stepQualificationLaborJustice;
        $model->stepSecondInstance = $object->stepSecondInstance;
        $model->stepThirdInstance = $object->stepThirdInstance;
        $model->isActive =  $object->isActive;


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

        return CustomerHealthDamageQualificationSource::find($model->id);
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
                if ($model instanceof CustomerHealthDamageQualificationSource) {
                    $parsed[] = (new CustomerHealthDamageQualificationSourceDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageQualificationSource) {
            return (new CustomerHealthDamageQualificationSourceDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageQualificationSourceDTO();
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
