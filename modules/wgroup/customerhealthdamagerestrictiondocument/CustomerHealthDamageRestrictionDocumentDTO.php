<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageRestrictionDocument;

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
class CustomerHealthDamageRestrictionDocumentDTO {

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
     * @param $model: Modelo CustomerHealthDamageRestrictionDocument
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerHealthDamageRestrictionId = $model->customer_health_damage_restriction_id;
        $this->type = $model->getType();
        $this->name = $model->name;
        $this->description = $model->description;
        $this->version = $model->version;
        $this->status = $model->getStatus();
        $this->startDate = $model->startDate != null ? Carbon::parse($model->startDate) : null;
        $this->endDate = $model->endDate != null ? Carbon::parse($model->endDate) : null;
        $this->creator = $model->creator != null ? $model->creator->name : '';
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->document);

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
            if (!($model = CustomerHealthDamageRestrictionDocument::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageRestrictionDocument();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageRestrictionDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_health_damage_restriction_id = $object->customerHealthDamageRestrictionId;
        $model->type = $object->type != null ? $object->type->value : null;
        $model->name = $object->name;
        $model->description = $object->description;
        $model->version = $object->version;
        $model->status = $object->status == null ? null : $object->status->value;
        $model->startDate = Carbon::parse($object->startDate)->timezone('America/Bogota');
        $model->endDate = Carbon::parse($object->endDate)->timezone('America/Bogota');

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

        return CustomerHealthDamageRestrictionDocument::find($model->id);
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
                if ($model instanceof CustomerHealthDamageRestrictionDocument) {
                    $parsed[] = (new CustomerHealthDamageRestrictionDocumentDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageRestrictionDocument) {
            return (new CustomerHealthDamageRestrictionDocumentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageRestrictionDocumentDTO();
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
