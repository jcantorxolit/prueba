<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerContractorSafetyInspectionListItem;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use RainLab\User\Facades\Auth;
use Wgroup\CertificateProgram\CertificateProgramDTO;
use Wgroup\CustomerSafetyInspectionConfigListGroup\CustomerSafetyInspectionConfigListGroupDTO;
use Wgroup\Models\CustomerDto;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerContractorSafetyInspectionListItemDTO {

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
     * @param $model: Modelo CustomerContractorSafetyInspectionListItem
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->group = CustomerSafetyInspectionConfigListGroupDTO::parse($model->group);
        $this->description = $model->description;
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
            if (!($model = CustomerContractorSafetyInspectionListItem::find($object->id))) {
                // No existe
                $model = new CustomerContractorSafetyInspectionListItem();
                $isEdit = false;
            }
        } else {
            $model = new CustomerContractorSafetyInspectionListItem();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_safety_inspection_list_id = $object->customerSafetyInspectionListId;
        $model->customer_safety_inspection_config_list_item_id = $object->customerSafetyInspectionConfigListItemId;
        $model->observation = $object->observation;
        $model->dangerousnessValue = $object->dangerousness->id;
        $model->existingControlValue = $object->existingControl->id;
        $model->priorityValue = isset($object->priority) ? $object->priority->id : null;
        $model->action = $object->action->value;
        $model->isActive = 1;//$object->isActive;

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

        return CustomerContractorSafetyInspectionListItem::find($model->id);
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
                if (!($model = CustomerContractorSafetyInspectionListItem::find($object->id))) {
                    // No existe
                    $model = new CustomerContractorSafetyInspectionListItem();
                    $isEdit = false;
                }
            } else {
                $model = new CustomerContractorSafetyInspectionListItem();
                $isEdit = false;
            }

            /** :: ASIGNO DATOS BASICOS ::  **/
            $model->customer_safety_inspection_list_id = $parentId;
            $model->customer_safety_inspection_config_list_item_id = $object->customerSafetyInspectionConfigListItemId;
            $model->observation = $object->observation;
            $model->dangerousnessValue = $object->dangerousness->id;
            $model->existingControlValue = $object->existingControl->id;
            $model->priorityValue = $object->priority->id;
            $model->action = $object->action->value;
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


        return CustomerContractorSafetyInspectionListItem::find($model->id);
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
                if ($model instanceof CustomerContractorSafetyInspectionListItem) {
                    $parsed[] = (new CustomerContractorSafetyInspectionListItemDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerContractorSafetyInspectionListItem) {
            return (new CustomerContractorSafetyInspectionListItemDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerContractorSafetyInspectionListItemDTO();
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
