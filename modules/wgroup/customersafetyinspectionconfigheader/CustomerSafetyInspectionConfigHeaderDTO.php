<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerSafetyInspectionConfigHeader;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\CustomerSafetyInspectionConfigHeaderField\CustomerSafetyInspectionConfigHeaderField;
use Wgroup\CustomerSafetyInspectionConfigHeaderField\CustomerSafetyInspectionConfigHeaderFieldDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerSafetyInspectionConfigHeaderDTO {

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
     * @param $model: Modelo CustomerSafetyInspectionConfigHeader
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->name = $model->name;
        $this->description = $model->description;
        $this->dateFrom = Carbon::parse($model->dateFrom);
        $this->dateFromText = Carbon::parse($model->dateFrom)->format("d/m/Y");
        $this->version = $model->version;
        $this->isActive = $model->isActive == 1 ? true : false;

        $this->fields =  CustomerSafetyInspectionConfigHeaderFieldDTO::parse($model->field);

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
            if (!($model = CustomerSafetyInspectionConfigHeader::find($object->id))) {
                // No existe
                $model = new CustomerSafetyInspectionConfigHeader();
                $isEdit = false;
            }
        } else {
            $model = new CustomerSafetyInspectionConfigHeader();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->name = $object->name;
        $model->description = $object->description;
        $model->dateFrom = $object->dateFrom;
        $model->version = $object->version;
        $model->isActive = $object->isActive;

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

        CustomerSafetyInspectionConfigHeaderFieldDTO::bulkInsertOrUpdate($object->fields, $model->id);

        return CustomerSafetyInspectionConfigHeader::find($model->id);
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
                if ($model instanceof CustomerSafetyInspectionConfigHeader) {
                    $parsed[] = (new CustomerSafetyInspectionConfigHeaderDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerSafetyInspectionConfigHeader) {
            return (new CustomerSafetyInspectionConfigHeaderDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerSafetyInspectionConfigHeaderDTO();
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
