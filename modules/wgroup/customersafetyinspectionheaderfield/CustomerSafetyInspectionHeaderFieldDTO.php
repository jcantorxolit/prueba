<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerSafetyInspectionHeaderField;

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
class CustomerSafetyInspectionHeaderFieldDTO {

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
     * @param $model: Modelo CustomerSafetyInspectionHeaderField
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->customerSafetyInspectionHeaderId = $model->customer_safety_inspection_header_id;
        $this->name = $model->name;
        $this->dataType = $model->getDataType();
        $this->sort = $model->sort;
        $this->isActive = $model->isActive == 1 ? true : false;

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
            if (!($model = CustomerSafetyInspectionHeaderField::find($object->id))) {
                // No existe
                $model = new CustomerSafetyInspectionHeaderField();
                $isEdit = false;
            }
        } else {
            $model = new CustomerSafetyInspectionHeaderField();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_safety_inspection_id = $object->customerSafetyInspectionId;
        $model->customer_safety_inspection_config_header_field_id = $object->customerSafetyInspectionConfigHeaderFieldId;
        $model->varcharValue = $object->varcharValue;
        $model->numericValue = $object->numericValue;
        $model->dateValue = $object->dateValue;
        $model->isActive = 1;

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

        return customersafetyinspectionconfigheaderField::find($model->id);
    }

    public static function  bulkInsertOrUpdate($entities, $parentId)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        foreach ($entities as $object) {
            if (!$object) {
                return false;
            }

            /** :: DETERMINO SI ES EDICION O CREACION ::  **/
            if ($object->id) {
                // Existe
                if (!($model = CustomerSafetyInspectionHeaderField::find($object->id))) {
                    // No existe
                    $model = new CustomerSafetyInspectionHeaderField();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerSafetyInspectionHeaderField();
                $isEdit = false;
            }

            /** :: ASIGNO DATOS BASICOS ::  **/
            $model->customer_safety_inspection_id = $parentId;
            $model->customer_safety_inspection_config_header_field_id = $object->customerSafetyInspectionConfigHeaderFieldId;
            $model->varcharValue = $object->varcharValue;
            $model->numericValue = $object->numericValue;
            $model->dateValue = $object->dateValue;
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

        }


        return CustomerSafetyInspectionHeaderField::find($model->id);
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
                if ($model instanceof CustomerSafetyInspectionHeaderField) {
                    $parsed[] = (new CustomerSafetyInspectionHeaderFieldDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerSafetyInspectionHeaderField) {
            return (new CustomerSafetyInspectionHeaderFieldDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerSafetyInspectionHeaderFieldDTO();
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