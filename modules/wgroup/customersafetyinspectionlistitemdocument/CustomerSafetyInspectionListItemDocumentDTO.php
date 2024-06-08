<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerSafetyInspectionListItemDocument;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerAudit\CustomerAudit;
use Carbon\Carbon;

/**
 * Description of CustomerSafetyInspectionListItemDocumentDTO
 *
 * @author jdblandon
 */
class CustomerSafetyInspectionListItemDocumentDTO {

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
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfo($model) {

        $this->id = $model->id;
        $this->customerSafetyInspectionListItemId = $model->customer_safety_inspection_list_item_id;
        $this->type = $model->getDocumentType();
        $this->description = $model->description;
        $this->version = $model->version;
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->document);
        $this->agent = $model->agent;
        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);

    }

    /**
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfoPermission($model) {

        $documentModel = CustomerSafetyInspectionListItemDocument::find($model->id);

        $this->id = $model->id;
        $this->customerSafetyInspectionListItemId = $model->customer_safety_inspection_list_item_id;
        $this->documentType = $model->documentType;
        $this->description = $model->description;
        $this->version = $model->version;
        $this->status = "Activo";
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($documentModel->document);
        $this->agent = $model->agent;
        $this->date = $model->created_at;
        //$this->userType = $model->userType;

        $this->tokensession = $this->getTokenSession(true);

    }

    private function getBasicInfoUser($model) {


        $this->id = $model->id;
        $this->name = $model->name;
        $this->type = $model->type;
        $this->hasPermission = $model->hasPermission == 1 ? true : false;
        $this->isPublic = $model->isPublic == 1 ? true : false;
        $this->isProtected = $model->isProtected == 1 ? true : false;
        $this->securityId = $model->securityId;


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
            if (!($model = CustomerSafetyInspectionListItemDocument::find($object->id))) {
                // No existe
                $model = new CustomerSafetyInspectionListItemDocument();
                $isEdit = false;
            } else {
                $model->status = 2;
                $model->save();

                $model = new CustomerSafetyInspectionListItemDocument();
                $isEdit = false;
            }

        } else {
            $model = new CustomerSafetyInspectionListItemDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_safety_inspection_list_item_id = $object->customerSafetyInspectionListItemId;
        $model->type = $object->type  == null ? null : $object->type->value;
        $model->description = $object->description;
        $model->status = "Activo";
        $model->version = 1;

        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;
            // Guarda
            $model->save();

            // Actualiza timestamp
            $model->touch();

/*
            $customerAudit = new CustomerAudit();
            $customerAudit->customer_id = $model->customer_id;
            $customerAudit->model_name = "Anexos";
            $customerAudit->model_id = $model->customer_id;
            $customerAudit->user_type = $userAdmn->wg_type;
            $customerAudit->user_id = $userAdmn->id;
            $customerAudit->action = "Editar";
            $customerAudit->observation = "Se realiza modificacion exitosa del anexo: (". $object->description .")";
            $customerAudit->date = Carbon::now('America/Bogota');
            $customerAudit->save();
*/

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;

            // Guarda
            $model->save();

/*
            $customerAudit = new CustomerAudit();
            $customerAudit->customer_id = $model->customer_id;
            $customerAudit->model_name = "Anexos";
            $customerAudit->model_id = $model->customer_id;
            $customerAudit->user_type = $userAdmn->wg_type;
            $customerAudit->user_id = $userAdmn->id;
            $customerAudit->action = "Guardar";
            $customerAudit->observation = "Se realiza adicion exitosa del anexo: (". $object->description .")";
            $customerAudit->date = Carbon::now('America/Bogota');
            $customerAudit->save();
*/
        }

        return CustomerSafetyInspectionListItemDocument::find($model->id);
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

    private function parseArray($model, $fmt_response = "1")
    {

        // parse model
        switch ($fmt_response) {
            case "2":
                $this->getBasicInfoPermission($model);
                break;

            case "3":
                $this->getBasicInfoUser($model);
                break;
            default:
                $this->getBasicInfo($model);
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
                if ($model instanceof CustomerSafetyInspectionListItemDocument) {
                    $parsed[] = (new CustomerSafetyInspectionListItemDocumentDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerSafetyInspectionListItemDocumentDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerSafetyInspectionListItemDocument) {
            return (new CustomerSafetyInspectionListItemDocumentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerSafetyInspectionListItemDocumentDTO();
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