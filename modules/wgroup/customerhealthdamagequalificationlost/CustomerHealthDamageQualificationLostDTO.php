<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationLost;

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
use Wgroup\CustomerHealthDamageQualificationLostJustice\CustomerHealthDamageQualificationLostJusticeDTO;
use Wgroup\CustomerHealthDamageQualificationLostNational\CustomerHealthDamageQualificationLostNationalDTO;
use Wgroup\CustomerHealthDamageQualificationLostOpportunity\CustomerHealthDamageQualificationLostOpportunityDTO;
use Wgroup\CustomerHealthDamageQualificationLostRegional\CustomerHealthDamageQualificationLostRegionalDTO;
use Wgroup\DisabilityDiagnostic\DisabilityDiagnosticDTO;


/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerHealthDamageQualificationLostDTO {

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
     * @param $model: Modelo CustomerHealthDamageQualificationLost
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->employee = CustomerEmployeeDTO::parse($model->employee);
        $this->arl = $model->getArl();
        $this->stepQualificationRegionalBoard =  $model->stepQualificationRegionalBoard == 1;
        $this->stepQualificationNationalBoard =  $model->stepQualificationNationalBoard == 1;
        $this->stepQualificationLaborJustice =  $model->stepQualificationLaborJustice == 1;
        $this->isActive =  $model->isActive == 1;
        $this->stepSecondInstance =  $model->stepSecondInstance == 1;

        $this->opportunity =  CustomerHealthDamageQualificationLostOpportunityDTO::parse($model->getOpportunity());
        $this->regional =  CustomerHealthDamageQualificationLostRegionalDTO::parse($model->getRegional());
        $this->national =  CustomerHealthDamageQualificationLostNationalDTO::parse($model->getNational());
        $this->justiceFirst =  CustomerHealthDamageQualificationLostJusticeDTO::parse($model->getJusticeFirst());
        $this->justiceSecond =  CustomerHealthDamageQualificationLostJusticeDTO::parse($model->getJusticeSecond());

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
            if (!($model = CustomerHealthDamageQualificationLost::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageQualificationLost();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageQualificationLost();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_employee_id = $object->employee->id;
        $model->arl = $object->arl == null ? null : $object->arl->value;
        $model->stepQualificationRegionalBoard = $object->stepQualificationRegionalBoard;
        $model->stepQualificationNationalBoard =  $object->stepQualificationNationalBoard;
        $model->stepQualificationLaborJustice =  $object->stepQualificationLaborJustice;
        $model->isActive =  $object->isActive;
        $model->stepSecondInstance =  $object->stepSecondInstance;


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

        return CustomerHealthDamageQualificationLost::find($model->id);
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
                if ($model instanceof CustomerHealthDamageQualificationLost) {
                    $parsed[] = (new CustomerHealthDamageQualificationLostDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageQualificationLost) {
            return (new CustomerHealthDamageQualificationLostDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageQualificationLostDTO();
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
