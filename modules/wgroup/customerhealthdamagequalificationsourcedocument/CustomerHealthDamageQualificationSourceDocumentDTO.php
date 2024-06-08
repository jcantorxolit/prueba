<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerHealthDamageQualificationSourceDocument;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerHealthDamageQualificationSourceDocumentDTO {

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
     * @param $model: Modelo CustomerHealthDamageQualificationSourceDocument
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerHealthDamageQualificationSourceId = $model->customer_health_damage_qualification_source_id;
        $this->entityId = $model->entityId;
        $this->entityCode = $model->entityCode;
        $this->entityName = $model->entityName;

        $this->type = $model->getType();
        $this->name = $model->name;
        $this->description = $model->description;
        $this->version = $model->version;
        $this->status = $model->getStatus();

        $this->startDate =  Carbon::parse($model->startDate);
        $this->startDateFormat =  Carbon::parse($model->startDate)->format('d/m/Y');

        $this->endDate =  Carbon::parse($model->endDate);
        $this->endDateFormat =  Carbon::parse($model->endDate)->format('d/m/Y');

        $this->createdAt =  Carbon::parse($model->created_at)->format('d/m/Y');

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
            if (!($model = CustomerHealthDamageQualificationSourceDocument::find($object->id))) {
                // No existe
                $model = new CustomerHealthDamageQualificationSourceDocument();
                $isEdit = false;
            }
        } else {
            $model = new CustomerHealthDamageQualificationSourceDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_health_damage_qualification_source_id = $object->customerHealthDamageQualificationSourceId;
        $model->entityId = $object->entityId;
        $model->entityCode = $object->entityCode;
        $model->entityName = $object->entityName;
        $model->type = $object->type == null ? null : $object->type->value;
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

        return CustomerHealthDamageQualificationSourceDocument::find($model->id);
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
                if ($model instanceof CustomerHealthDamageQualificationSourceDocument) {
                    $parsed[] = (new CustomerHealthDamageQualificationSourceDocumentDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerHealthDamageQualificationSourceDocument) {
            return (new CustomerHealthDamageQualificationSourceDocumentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerHealthDamageQualificationSourceDocumentDTO();
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
