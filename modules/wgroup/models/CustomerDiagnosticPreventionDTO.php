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
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\Models\Customer;

/**
 * Description of CustomerDiagnosticDTO
 *
 * @author jdblandon
 */
class CustomerDiagnosticPreventionDTO {

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
     * @param $model: Modelo CustomerDiagnosticDTO
     */
    private function getBasicInfo($model) {
        $this->id = $model->id;
        $this->diagnosticId = $model->diagnostic_id;
        $this->questionId = $model->question_id;
        $this->rateId = $model->rate_id;
        $this->description = $model->description;
        $this->article = $model->article;
        $this->observation = $model->observation;
        $this->categoryId = $model->category_id;
        $this->color = $model->color;
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
            if (!($model = CustomerDiagnosticPrevention::find($object->id))) {
                // No existe
                $model = new CustomerDiagnosticPrevention();
                $isEdit = false;
            }
        } else {
            $model = new CustomerDiagnosticPrevention();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado
        $model->diagnostic_id = $object->diagnosticId;
        $model->rate_id = $object->rate->id;
        $model->observation = $object->observation;

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

        return CustomerDiagnosticPrevention::find($model->id);

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
                $this->getReportSummaryPrg($model);
                break;
            case "3":
                $this->getReportSummaryAdv($model);
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
                if ($model instanceof CustomerDiagnosticPrevention) {
                    $parsed[] = (new CustomerDiagnosticPreventionDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerDiagnosticPreventionDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerDiagnosticPrevention) {
            return (new CustomerDiagnosticPreventionDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerDiagnosticPreventionDTO();
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