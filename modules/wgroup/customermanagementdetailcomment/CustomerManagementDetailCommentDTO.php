<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerManagementDetailComment;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Models\Customer;
use Mail;
use Wgroup\Models\CustomerDiagnosticPrevention;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerManagementDetailCommentDTO {

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
     * @param $model: Modelo CustomerManagementDetailComment
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->managementDetailId = $model->management_detail_id;
        $this->comment = $model->comment;
        $this->user = $model->user;
        $this->createdAt = Carbon::parse($model->createdAt)->format('d/m/Y H:i:s');

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
            if (!($model = CustomerManagementDetailComment::find($object->id))) {
                // No existe
                $model = new CustomerManagementDetailComment();
                $isEdit = false;
            }
        } else {
            $model = new CustomerManagementDetailComment();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->management_detail_id = $object->managementDetailId;
        $model->comment = $object->comment;

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
            $model->created_at = Carbon::now()->timezone('America/Bogota');

            // Guarda
            $model->save();

        }

        return CustomerManagementDetailComment::find($model->id);
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
                if ($model instanceof CustomerManagementDetailComment) {
                    $parsed[] = (new CustomerManagementDetailCommentDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerManagementDetailCommentDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerManagementDetailComment) {
            return (new CustomerManagementDetailCommentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerManagementDetailCommentDTO();
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
