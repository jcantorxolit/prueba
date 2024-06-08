<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationSourceOpportunity;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerHealthDamageQualificationSourceOpportunityDTO {

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
     * @param $model: Modelo CustomerHealthDamageQualificationSourceOpportunity
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerHealthDamageQualificationSourceId = $model->customer_health_damage_qualification_source_id;
        $this->dateOf =  Carbon::parse($model->dateOf);
        $this->opinionNumber = $model->opinionNumber;
        $this->qualifyingEntity = $model->getQualifyingEntity();
        $this->notificationDate =  Carbon::parse($model->notificationDate);
        $this->description = $model->description;
        $this->filingDate =  Carbon::parse($model->filingDate);
        $this->isRemainedFirm = $model->isRemainedFirm == 1;

        $this->dateOfFormat =  Carbon::parse($model->dateOf)->format('d/m/Y');
        $this->notificationDateFormat =  Carbon::parse($model->notificationDate)->format('d/m/Y');
        $this->filingDateFormat =  Carbon::parse($model->filingDate)->format('d/m/Y');


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
            if (!($model = CustomerHealthDamageQualificationSourceOpportunity::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageQualificationSourceOpportunity();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageQualificationSourceOpportunity();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_health_damage_qualification_source_id = $object->customerHealthDamageQualificationSourceId;
        $model->dateOf = Carbon::parse($object->dateOf)->timezone('America/Bogota');
        $model->opinionNumber = $object->opinionNumber;
        $model->qualifyingEntity = $object->qualifyingEntity == null ? null : $object->qualifyingEntity->value;
        $model->notificationDate = Carbon::parse($object->notificationDate)->timezone('America/Bogota');
        $model->description = $object->description;
        $model->filingDate = Carbon::parse($object->filingDate)->timezone('America/Bogota');
        $model->isRemainedFirm = $object->isRemainedFirm;

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

        return CustomerHealthDamageQualificationSourceOpportunity::find($model->id);
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
                if ($model instanceof CustomerHealthDamageQualificationSourceOpportunity) {
                    $parsed[] = (new CustomerHealthDamageQualificationSourceOpportunityDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageQualificationSourceOpportunity) {
            return (new CustomerHealthDamageQualificationSourceOpportunityDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageQualificationSourceOpportunityDTO();
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
