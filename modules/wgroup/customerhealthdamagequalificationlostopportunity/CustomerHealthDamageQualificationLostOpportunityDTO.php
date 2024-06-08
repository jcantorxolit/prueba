<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationLostOpportunity;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\CustomerHealthDamageQualificationLostOpportunityDiagnostic\CustomerHealthDamageQualificationLostOpportunityDiagnosticDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerHealthDamageQualificationLostOpportunityDTO {

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
     * @param $model: Modelo CustomerHealthDamageQualificationLostOpportunity
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerHealthDamageQualificationLostId = $model->customer_health_damage_qualification_lost_id;
        $this->qualifyingEntity = $model->getQualifyingEntity();
        $this->dateOf =  Carbon::parse($model->dateOf);
        $this->notificationDate =  Carbon::parse($model->notificationDate);
        $this->origin = $model->getOrigin();
        $this->percentageRating = $model->percentageRating;
        $this->structuringDate =  Carbon::parse($model->structuringDate);
        $this->nonconformityDate =  Carbon::parse($model->nonconformityDate);
        $this->controversyStatus = $model->getControversyStatus();

        $this->dateOfFormat =  Carbon::parse($model->dateOf)->format('d/m/Y');
        $this->notificationDateFormat =  Carbon::parse($model->notificationDate)->format('d/m/Y');
        $this->structuringDateFormat =  Carbon::parse($model->structuringDate)->format('d/m/Y');
        $this->nonconformityDateFormat =  Carbon::parse($model->nonconformityDate)->format('d/m/Y');

        $this->diagnostics = $model->getDiagnostics();

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
            if (!($model = CustomerHealthDamageQualificationLostOpportunity::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageQualificationLostOpportunity();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageQualificationLostOpportunity();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_health_damage_qualification_lost_id = $object->customerHealthDamageQualificationLostId;
        $model->qualifyingEntity = $object->qualifyingEntity == null ? null : $object->qualifyingEntity->value;
        $model->dateOf = Carbon::parse($object->dateOf)->timezone('America/Bogota');
        $model->notificationDate = Carbon::parse($object->notificationDate)->timezone('America/Bogota');
        $model->origin = $object->origin == null ? null : $object->origin->value;
        $model->percentageRating = $object->percentageRating;
        $model->structuringDate = Carbon::parse($object->structuringDate)->timezone('America/Bogota');
        $model->nonconformityDate = Carbon::parse($object->nonconformityDate)->timezone('America/Bogota');
        $model->controversyStatus = $object->controversyStatus == null ? null : $object->controversyStatus->value;

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

        CustomerHealthDamageQualificationLostOpportunityDiagnosticDTO::bulkInsert($object->diagnostics, $model->id);

        return CustomerHealthDamageQualificationLostOpportunity::find($model->id);
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
                if ($model instanceof CustomerHealthDamageQualificationLostOpportunity) {
                    $parsed[] = (new CustomerHealthDamageQualificationLostOpportunityDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageQualificationLostOpportunity) {
            return (new CustomerHealthDamageQualificationLostOpportunityDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageQualificationLostOpportunityDTO();
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
