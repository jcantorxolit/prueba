<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Wgroup\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use RainLab\User\Facades\Auth;
use Wgroup\Controllers\CustomerDiagnosticController;
use Wgroup\Models\Customer;

/**
 * Description of CustomerManagementProgramDTO
 *
 * @author jdblandon
 */
class CustomerManagementProgramDTO
{

    function __construct($model = null)
    {
        if ($model) {
            $this->parse($model);
        }
    }

    public function setInfo($model = null, $fmt_response = "1")
    {

        // recupera informacion basica del formulario
        if ($model) {
            $this->getBasicInfo($model);
        }
    }

    /**
     * @param $model : Modelo CustomerManagementProgramDTO
     */
    private function getBasicInfo($model)
    {
        $this->id = $model->id;
        $this->managementId = $model->management_id;
        $this->active = $model->active;

        $this->created_at = $model->created_at->format('d/m/Y');
        $this->updated_at = $model->updated_at->format('d/m/Y');
    }

    private function getBasicInfoSummary($model)
    {
        $this->id = $model->id;
        $this->abbreviation = $model->abbreviation;
        $this->name = $model->name;
        $this->questions = $model->questions;
        $this->answers = $model->answers;
        $this->average = $model->average;
        $this->advance = $model->advance;
    }

    private function getReportSummaryPrg($model)
    {

        //Log::info(json_encode($model));

        ////Log::info($model->datasets);

        $this->labels = $model["labels"];
        $this->datasets = $model["datasets"];
        /*
                $this->label = $model->label;
                $this->fillColor = $model->fillColor;
                $this->strokeColor = $model->strokeColor;
                $this->highlightFill = $model->highlightFill;
                $this->highlightStroke = $model->highlightStroke;
                $this->data = $model->data;
                */
    }

    private function getReportSummaryAdv($model)
    {
        //Log::info(json_encode($model));

        $this->value = (float) $model->value;
        $this->color = $model->color;
        $this->highlight = $model->highlightColor;
        $this->label = $model->label;

    }

    public static function  fillAndSaveModel($object, $finalize = false)
    {

        $isEdit = true;
        $userAdmn = Auth::getUser();

        if (!$object) {
            return false;
        }

        /** :: DETERMINO SI ES EDICION O CREACION ::  **/
        if ($object->id) {
            // Existe
            if (!($model = CustomerManagementProgram::find($object->id))) {
                // No existe
                $model = new CustomerManagementProgram();
                $isEdit = false;
            }
        } else {
            $model = new CustomerManagementProgram();
            $isEdit = false;
        }

        /** :: ASIGNO DATOS BASICOS ::  **/

        // cliente asociado

        $model->management_id = $object->managementId;
        $model->active = $object->active;

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

        return CustomerManagementProgram::find($model->id);

    }


    /***
     * @param $model
     * @param string $fmt_response
     * @return $this
     */
    private function parseModel($model, $fmt_response = "1")
    {
        if($fmt_response != "1"){
            // parse model
            switch ($fmt_response) {
                case "2":
                    $this->getReportSummaryPrg($model);
                    break;
                case "3":
                    $this->getReportSummaryAdv($model);
                    break;
                default:
                    $this->getBasicInfoSummary($model);
            }
        }else{
            // parse model
            if ($model) {
                $this->setInfo($model, $fmt_response);
            }
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
                $this->getBasicInfoSummary($model);
        }

        return $this;
    }

    public static function parse($info, $fmt_response = "1")
    {

        if ($info instanceof Paginator || $info instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $data = $info->all();
        } else {
            $data = $info;
        }

        if (is_array($data) || $data instanceof Collection) {
            $parsed = array();
            foreach ($data as $model) {
                if ($model instanceof CustomerManagementProgram) {
                    $parsed[] = (new CustomerManagementProgramDTO())->parseModel($model, $fmt_response);
                } else {
                    $parsed[] = (new CustomerManagementProgramDTO())->parseArray($model, $fmt_response);
                }
            }
            return $parsed;
        } else if ($info instanceof CustomerManagementProgram) {
            return (new CustomerManagementProgramDTO())->parseModel($data, $fmt_response);
        } else {
            // return empty instance
            if ($fmt_response == "1") {
                return "";
            } else {
                return new CustomerManagementProgramDTO();
            }
        }
    }

    private function getUserSsession()
    {
        if (!Auth::check())
            return null;

        return Auth::getUser();
    }

    private function getTokenSession($encode = false)
    {
        $token = Session::getId();
        if ($encode) {
            $token = base64_encode($token);
        }
        return $token;
    }
}
