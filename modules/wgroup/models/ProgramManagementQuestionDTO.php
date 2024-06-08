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
use Wgroup\Models\Customer;

/**
 * Description of ProgramManagementQuestionDTO
 *
 * @author jdblandon
 */
class ProgramManagementQuestionDTO {

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
        $this->name = $model->description;
        $this->article = $model->article;
        $this->weightedValue = $model->weightedValue;
        $this->status = $model->getStatus();
        $this->category = $model->getCategory();
        if ($this->category != null) {
            $this->program = $this->category->program;
        }
    }

    public static function  fillAndSaveModel($object)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = ProgramManagementQuestion::find($object->id))) {
                // No existe
                $model = new ProgramManagementQuestion();
                $isEdit = false;
            }
        } else {
            $model = new ProgramManagementQuestion();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->description = $object->name;
        $model->article = $object->article;
        $model->status = $object->status == null ? null : $object->status->value;
        $model->category_id = $object->category == null ? null : $object->category->id;
        $model->weightedValue = isset($object->weightedValue) ? $object->weightedValue : 0;

        if ($isEdit) {

            $model->save();

            // Actualiza timestamp
            $model->touch();

        } else {
            // Guarda
            $model->save();

        }

        return ProgramManagementQuestion::find($model->id);
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
                if ($model instanceof ProgramManagementQuestion) {
                    $parsed[] = (new ProgramManagementQuestionDTO())->parseModel($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof ProgramManagementQuestion) {
            return (new ProgramManagementQuestionDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new ProgramManagementQuestionDTO();
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
