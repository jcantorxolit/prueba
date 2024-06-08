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

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerDocumentSecurityDTO {

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


    }

    /**
     * @param $model: Modelo CustomerTracking
     */
    private function getBasicInfoPermission($model) {

        $documentModel = CustomerDocumentSecurity::find($model->id);

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
        //$this->userType = $model->userType;

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

        if ($object->users) {
            foreach ($object->users as $user) {


                if ($user->securityId) {
                    // Existe
                    if (!($model = CustomerDocumentSecurity::find($user->securityId))) {
                        // No existe
                        $model = new CustomerDocumentSecurity();
                        $isEdit = false;
                    }
                } else {
                    $model = new CustomerDocumentSecurity();
                    $isEdit = false;
                }

                /** :: ASIGNO DATOS BASICOS ::  **/
                $model->customer_id = $object->customerId;
                $model->user_id = $user->id;
                $model->documentType = $object->documentType ? $object->documentType->value : null;
                $model->protectionType = $user->isPublic ? "public" : "private";
                $model->isPasswordProtected = $user->isProtected;
                $model->isActive = $user->hasPermission;

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
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/


        return CustomerDocumentSecurity::find($model->id);
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
                if ($model instanceof CustomerDocumentSecurity) {
                    $parsed[] = (new CustomerDocumentSecurityDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDocumentSecurityDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDocumentSecurity) {
            return (new CustomerDocumentSecurityDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDocumentSecurityDTO();
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
