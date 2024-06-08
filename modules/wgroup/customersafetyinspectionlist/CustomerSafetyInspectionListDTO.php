<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerSafetyInspectionList;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\CustomerSafetyInspectionConfigList\CustomerSafetyInspectionConfigListDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerSafetyInspectionListDTO {

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
     * @param $model: Modelo CustomerSafetyInspectionList
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerSafetyInspectionId = $model->customer_safety_inspection_id;
        $this->customerSafetyInspectionConfigListId = $model->customer_safety_inspection_config_list_id;
        $this->list = CustomerSafetyInspectionConfigListDTO::parse($model->configList);
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
            if (!($model = CustomerSafetyInspectionList::find($object->id))) {
                // No existe
                $model = new CustomerSafetyInspectionList();
                $isEdit = false;
            }
        } else {
            $model = new CustomerSafetyInspectionList();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_safety_inspection_id = $object->customerSafetyInspectionId;
        $model->customer_safety_inspection_config_list_id = $object->customerSafetyInspectionConfigList->id;
        $model->isActive = $object->isActive;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {

            if (CustomerSafetyInspectionListDTO::isUnique($object)) {
                // Creado por
                $model->createdBy = $userAdmn->id;
                $model->updatedBy = $userAdmn->id;

                // Guarda
                $model->save();
            }
        }

        return CustomerSafetyInspectionList::find($model->id);
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
                if (!($model = CustomerSafetyInspectionList::find($object->id))) {
                    // No existe
                    $model = new CustomerSafetyInspectionList();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerSafetyInspectionList();
                $isEdit = false;
            }

            /** :: ASIGNO DATOS BASICOS ::  **/
            $model->customer_safety_inspection_id = $parentId;
            $model->customer_safety_inspection_config_list_id = $object->list->id;
            $model->isActive = $object->isActive;

            if ($isEdit) {

                // actualizado por
                $model->updatedBy = $userAdmn->id;

                // Guarda
                $model->save();

                // Actualiza timestamp
                $model->touch();

            } else {

                if (CustomerSafetyInspectionListDTO::isUnique($object, $parentId)) {
                    // Creado por
                    $model->createdBy = $userAdmn->id;
                    $model->updatedBy = $userAdmn->id;

                    // Guarda
                    $model->save();
                }

            }

        }


        return CustomerSafetyInspectionList::find($model->id);
    }

    private static function isUnique($entity, $parentId)
    {
        $model = null;

        if ($entity->id == 0) {
            $model = CustomerSafetyInspectionList::whereCustomerSafetyInspectionId($parentId)
                                                    ->whereCustomerSafetyInspectionConfigListId($entity->list->id)->first();
        } else {
            $model = $entity;
        }

        return $model == null;
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
                if ($model instanceof CustomerSafetyInspectionList) {
                    $parsed[] = (new CustomerSafetyInspectionListDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerSafetyInspectionList) {
            return (new CustomerSafetyInspectionListDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerSafetyInspectionListDTO();
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
