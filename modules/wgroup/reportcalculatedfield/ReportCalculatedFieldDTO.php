<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\ReportCalculatedField;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;

/**
 * Description of ReportCalculatedFieldDTO
 *
 * @author jdblandon
 */
class ReportCalculatedFieldDTO {

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
     * @param $model: Modelo ReportCalculatedField
     */
    private function getBasicInfo($model) {

        //Codigo
        $this->id = $model->id;
        $this->report = $model->report;
        $this->name = $model->name;
        $this->title = $model->title;
        $this->expression = $model->expression;
        $this->jsonFields = $model->jsonFields;
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
            if (!($model = ReportCalculatedField::find($object->id))) {
                // No existe
                $model = new ReportCalculatedField();
                $isEdit = false;
            }
        } else {
            $model = new ReportCalculatedField();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/
        $model->report_id = $object->report->id;
        $model->name = $object->name;
        $model->title = $object->title;
        $model->expression = $object->expression;
        $model->jsonFields = "";
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

        return ReportCalculatedField::find($model->id);
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
                if ($model instanceof ReportCalculatedField) {
                    $parsed[] = (new ReportCalculatedFieldDTO())->parseModel($model, $fmt_response);
                }else {
                    $parsed[] = (new ReportCalculatedFieldDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof ReportCalculatedField) {
            return (new ReportCalculatedFieldDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new ReportCalculatedFieldDTO();
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
