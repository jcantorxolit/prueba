<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationSourceNationalDetail;

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
class CustomerHealthDamageQualificationSourceNationalDetailDTO {

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
     * @param $model: Modelo CustomerHealthDamageQualificationSourceNationalDetail
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerHealthDamageQsNationalBoardId = $model->customer_health_damage_qs_national_board_id;
        $this->diagnostic = $model->getDiagnostic();
        $this->qualifiedOrigin = $model->getQualifiedOrigin();
        $this->controversyStatus = $model->getControversyStatus();

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
            if (!($model = CustomerHealthDamageQualificationSourceNationalDetail::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageQualificationSourceNationalDetail();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageQualificationSourceNationalDetail();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_health_damage_qs_national_board_id = $object->customerHealthDamageQsNationalBoardId;
        $model->diagnostic = $object->diagnostic == null ? null : $object->diagnostic->id;
        $model->qualifiedOrigin = $object->qualifiedOrigin == null ? null : $object->qualifiedOrigin->value;
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

        return CustomerHealthDamageQualificationSourceNationalDetail::find($model->id);
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
                if ($model instanceof CustomerHealthDamageQualificationSourceNationalDetail) {
                    $parsed[] = (new CustomerHealthDamageQualificationSourceNationalDetailDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageQualificationSourceNationalDetail) {
            return (new CustomerHealthDamageQualificationSourceNationalDetailDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageQualificationSourceNationalDetailDTO();
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