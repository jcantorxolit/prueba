<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerDiagnosticPreventionComment;

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
class CustomerDiagnosticPreventionCommentDTO {

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
     * @param $model: Modelo CustomerDiagnosticPreventionComment
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->diagnosticDetailId = $model->diagnostic_detail_id;
        $this->comment = $model->comment;
        $this->user = $model->user;
        $this->createdAt = Carbon::parse($model->createdAt)->timezone('America/Bogota')->format('d/m/Y H:m:s');

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
            if (!($model = CustomerDiagnosticPreventionComment::find($object->id))) {
                // No existe
                $model = new CustomerDiagnosticPreventionComment();
                $isEdit = false;
            }
        } else {
            $model = new CustomerDiagnosticPreventionComment();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->diagnostic_detail_id = $object->diagnosticDetailId;
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

            // Guarda
            $model->save();

        }

        return CustomerDiagnosticPreventionComment::find($model->id);
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
                if ($model instanceof CustomerDiagnosticPreventionComment) {
                    $parsed[] = (new CustomerDiagnosticPreventionCommentDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDiagnosticPreventionCommentDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDiagnosticPreventionComment) {
            return (new CustomerDiagnosticPreventionCommentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDiagnosticPreventionCommentDTO();
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
