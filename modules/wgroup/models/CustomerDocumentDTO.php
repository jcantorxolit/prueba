<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\CustomerAudit\CustomerAudit;
use Carbon\Carbon;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerDocumentDTO {

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
        $this->customerId = $model->customer_id;
        $this->type = $model->getDocumentType();
        $this->classification = $model->getClassification();
        $this->description = $model->description;
        $this->status = $model->getStatusType();
        $this->version = $model->version;
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($model->document);
        $this->agent = $model->agent;
        $this->program = $model->program;
        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
        $this->tokensession = $this->getTokenSession(true);

    }

    /**
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfoPermission($model) {

        $documentModel = CustomerDocument::find($model->id);

        $this->id = $model->id;
        $this->customerId = $model->customer_id;
        $this->documentType = $model->documentType;
        $this->classification = $model->classification;
        $this->description = $model->description;
        $this->status = $model->status;
        $this->version = $model->version;
        $this->document = \AdeN\Api\Helpers\FileSystemHelper::attachInstance($documentModel->document);
        $this->agent = $model->agent;
        $this->protectionType = $model->protectionType;
        $this->hasPermission = $model->hasPermission;
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
        $agent = Agent::where("user_id", $userAdmn->id)->first();

        if ($agent == null) {

            $agent = Agent::where("user_id","<>", "")->first();
        }

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerDocument::find($object->id))) {
                // No existe
                $model = new CustomerDocument();
                $isEdit = false;
            } else {
                $model->status = 2;
                $model->updatedBy = $userAdmn->id;
                $model->updated_at = Carbon::now('America/Bogota');
                $model->save();


                $customerAudit = new CustomerAudit();
                $customerAudit->customer_id = $model->customer_id;
                $customerAudit->model_name = "Anexos";
                $customerAudit->model_id = $model->customer_id;
                $customerAudit->user_type = $userAdmn->wg_type;
                $customerAudit->user_id = $userAdmn->id;
                $customerAudit->action = "Anular";
                $customerAudit->observation = "Se realiza anulación exitosa del anexo: (". $object->description .")";
                $customerAudit->date = Carbon::now('America/Bogota');
                $customerAudit->save();

                $model = new CustomerDocument();
                $isEdit = false;
            }

        } else {
            $model = new CustomerDocument();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_id = $object->customerId;
        $model->type = $object->type == null ? null : $object->type->value;
        $model->origin = $object->type == null ? null : $object->type->origin;
        $model->classification = $object->classification == null ? null : $object->classification->value;
        $model->description = $object->description;
        $model->status = 1;//$object->status->value == "-S-" ? null : $object->status->value;
        $model->version = $object->version;
        $model->program = isset($object->program) ? $object->program : "";


        if ($isEdit) {

            // actualizado por
            $model->updatedBy = $userAdmn->id;
            $model->agent_id = $agent->id;
            $model->updated_at = Carbon::now('America/Bogota');
            // Guarda
            $model->save();

            // Actualiza timestamp
            //$model->touch();


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

        } else {

            // Creado por
            $model->createdBy = $userAdmn->id;
            $model->updatedBy = $userAdmn->id;
            $model->updated_at = Carbon::now('America/Bogota');
            $model->created_at = Carbon::now('America/Bogota');

            $model->agent_id = $agent->id;
            // Guarda
            $model->save();


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

        }

        return CustomerDocument::find($model->id);
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
                if ($model instanceof CustomerDocument) {
                    $parsed[] = (new CustomerDocumentDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDocumentDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDocument) {
            return (new CustomerDocumentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDocumentDTO();
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
