<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationLostJustice;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\CustomerHealthDamageQualificationLostJusticeDiagnostic\CustomerHealthDamageQualificationLostJusticeDiagnosticDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerHealthDamageQualificationLostJusticeDTO {

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
     * @param $model: Modelo CustomerHealthDamageQualificationLostJustice
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerHealthDamageQualificationLostId = $model->customer_health_damage_qualification_lost_id;


        $this->dateOf =  Carbon::parse($model->dateOf);
        $this->structuringDate =  Carbon::parse($model->structuringDate);
        $this->sentenceType = $model->sentenceType;
        $this->origin = $model->getOrigin();
        $this->sentence = $model->sentence;
        $this->percentageRating = $model->percentageRating;

        $this->dateOfFormat =  Carbon::parse($model->dateOf)->format('d/m/Y');
        $this->structuringDateFormat =  Carbon::parse($model->structuringDate)->format('d/m/Y');

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
            if (!($model = CustomerHealthDamageQualificationLostJustice::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageQualificationLostJustice();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageQualificationLostJustice();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_health_damage_qualification_lost_id = $object->customerHealthDamageQualificationLostId;

        $model->dateOf = Carbon::parse($object->dateOf)->timezone('America/Bogota');
        $model->structuringDate = Carbon::parse($object->structuringDate)->timezone('America/Bogota');
        $model->sentenceType = $object->sentenceType;
        $model->origin = $object->origin == null ? null : $object->origin->value;
        $model->sentence = $object->sentence;
        $model->percentageRating = $object->percentageRating;

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

        CustomerHealthDamageQualificationLostJusticeDiagnosticDTO::bulkInsert($object->diagnostics, $model->id);

        return CustomerHealthDamageQualificationLostJustice::find($model->id);
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
                if ($model instanceof CustomerHealthDamageQualificationLostJustice) {
                    $parsed[] = (new CustomerHealthDamageQualificationLostJusticeDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageQualificationLostJustice) {
            return (new CustomerHealthDamageQualificationLostJusticeDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageQualificationLostJusticeDTO();
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
