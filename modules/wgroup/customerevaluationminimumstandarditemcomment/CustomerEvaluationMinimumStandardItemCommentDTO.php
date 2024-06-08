<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\CustomerEvaluationMinimumStandardItemComment;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Mail;
use DB;
use RainLab\User\Facades\Auth;

/**
 * Description of CustomerTrackinDTO
 *
 * @author jdblandon
 */
class CustomerEvaluationMinimumStandardItemCommentDTO {

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
     * @param $model: Modelo CustomerEvaluationMinimumStandardItemComment
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->customerEvaluationMinimumStandardItemId = $model->customer_evaluation_minimum_standard_item_id;
        $this->comment = $model->comment;
        if ($user = DB::table('users')->where("id", $model->createdBy)->first()) {
            $this->user['name'] = $user->name;
        } else {
            $this->user = null;
        }

        $this->createdAt = $model->created_at ? Carbon::parse($model->created_at)->timezone('America/Bogota')->format('d/m/Y H:m:s') : null;

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
            if (!($model = CustomerEvaluationMinimumStandardItemComment::find($object->id))) {
                // No existe
                $model = new CustomerEvaluationMinimumStandardItemComment();
                $isEdit = false;
            }
        } else {
            $model = new CustomerEvaluationMinimumStandardItemComment();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_evaluation_minimum_standard_item_id = $object->customerEvaluationMinimumStandardItemId;
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

        return CustomerEvaluationMinimumStandardItemComment::find($model->id);
    }

    public static function insert($object)
    {

        $isEdit = true;
        $isAlertEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        $model = new CustomerEvaluationMinimumStandardItemComment();
        $isEdit = false;

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->customer_evaluation_minimum_standard_item_id = $object->customerEvaluationMinimumStandardItemId;
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

        return CustomerEvaluationMinimumStandardItemComment::find($model->id);
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
                if ($model instanceof CustomerEvaluationMinimumStandardItemComment) {
                    $parsed[] = (new CustomerEvaluationMinimumStandardItemCommentDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerEvaluationMinimumStandardItemCommentDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerEvaluationMinimumStandardItemComment) {
            return (new CustomerEvaluationMinimumStandardItemCommentDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerEvaluationMinimumStandardItemCommentDTO();
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
