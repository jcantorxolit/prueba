<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageRestrictionDetail;

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
class CustomerHealthDamageRestrictionDetailDTO {

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
     * @param $model: Modelo CustomerHealthDamageRestrictionDetail
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerHealthDamageRestrictionId = $model->customer_health_damage_restriction_id;
        $this->dateOfIssue =  Carbon::parse($model->dateOfIssue);
        $this->timeInMonths = $model->timeInMonths;
        $this->expirationDate =  Carbon::parse($model->expirationDate);
        $this->isPermanentManagement = $model->isPermanentManagement == 1;
        $this->restriction = $model->getRestriction();
        $this->description = $model->description;
        $this->whoPerceived = $model->getWhoPerceived();
        $this->observation = $model->observation;
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->document);
        $this->dateOfIssueFormat =  Carbon::parse($model->dateOfIssue)->format('d/m/Y');
        $this->expirationDateFormat =  Carbon::parse($model->expirationDate)->format('d/m/Y');

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
            if (!($model = CustomerHealthDamageRestrictionDetail::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageRestrictionDetail();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageRestrictionDetail();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_health_damage_restriction_id = $object->customerHealthDamageRestrictionId;
        $model->dateOfIssue = Carbon::parse($object->dateOfIssue)->timezone('America/Bogota');
        $model->timeInMonths = $object->timeInMonths;
        $model->expirationDate = Carbon::parse($object->expirationDate)->timezone('America/Bogota');
        $model->isPermanentManagement = $object->isPermanentManagement;
        $model->restriction = $object->restriction == null ? null : $object->restriction->value;
        $model->description = $object->description;
        $model->whoPerceived = $object->whoPerceived == null ? null : $object->whoPerceived->value;
        $model->observation = $object->observation;

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

        return CustomerHealthDamageRestrictionDetail::find($model->id);
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
                if ($model instanceof CustomerHealthDamageRestrictionDetail) {
                    $parsed[] = (new CustomerHealthDamageRestrictionDetailDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageRestrictionDetail) {
            return (new CustomerHealthDamageRestrictionDetailDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageRestrictionDetailDTO();
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
